<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Adhoc task to sync grades for a single course.
 *
 * @package   local_gradesync
 * @copyright 2020 Michael Vangelovski <michael.vangelovski@hotmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_gradesync\task;

defined('MOODLE_INTERNAL') || die();


require_once($CFG->dirroot . '/group/lib.php');


class adhoc_task_gradesync extends \core\task\adhoc_task {

    // Use the logging trait to get some nice, juicy, logging.
    use \core\task\logging_trait;

    /**
     * @var array The courseid for this task.
     */
    protected $courseid = 0;

    /**
     * @var array Existing staged grades.
     */
    protected $existinggrades = array();

    /**
     * @var array Grades to be stored.
     */
    protected $grades = array();

    /**
     * @var moodle_database.
     */
    protected $externalDB = null;

    /**
     * @var stdClass plugin conig.
     */
    protected $config = null;

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('crontask_gradesync', 'local_gradesync');
    }

    /**
     * Execute the scheduled task.
     */
    public function execute() {
        global $DB;

        // Initiate ext db conn.
        $this->config = get_config('local_gradesync');
        $this->externalDB = \moodle_database::get_driver_instance($this->config->dbtype, 'native', true);        
        $this->externalDB->connect($this->config->dbhost, $this->config->dbuser, $this->config->dbpass, $this->config->dbname, '');

        $this->courseid = $this->get_custom_data();
        $this->log_start("Processing grade sync for course {$this->courseid}");
        $course = $DB->get_record('course', array('id' => $this->courseid));
        if (empty($course)) {
            $this->log("Error - course record not found.", 1);
            return;
        }
        $this->log("Course record found: $course->fullname", 1);

        // Load in all of the existing staged grades for this course.
        $this->log("Caching existing grades for course.", 1);
        $this->cache_existing_grades();

        // Get the student roleid.
        $studentroleid = $DB->get_field('role', 'id', array('shortname'=> 'student'));

        // Get a list of users in this course.
        $courseuserroles = enrol_get_course_users_roles($this->courseid);

        // Pluck the student ids from the list of enrolments.
        $students = array_filter($courseuserroles, function($userroles) use ($studentroleid) {
            foreach ($userroles as $role) {
                if ($role->roleid == $studentroleid) {
                    return true;
                }
            }
        });
        $students = array_keys($students);

        // Get the mappings at the course level.
        $this->log("Caching grades mapped at the course level.", 1);
        $sql = "SELECT *
                  FROM {gradesync_mappings}
                 WHERE courseid = ?
                   AND groupid = 0";
        $params = array($this->courseid);
        $mappings = $DB->get_records_sql($sql, $params);
        foreach ($mappings as $mapping) {
            // Cache each student's grade for this mapping.
            foreach ($students as $studentid) {
                $this->cache_grade($studentid, $mapping);
            }
        }

        // Override mappings at the group/class level.
        $this->log("Overriding grades mapped at the group/class level.", 1);
        $sql = "SELECT *
                  FROM {gradesync_mappings}
                 WHERE courseid = ?
                   AND groupid != 0";
        $params = array($this->courseid);
        $mappings = $DB->get_records_sql($sql, $params);
        foreach ($mappings as $mapping) {
            // Get a list of users in the group.
            $members = array_keys(groups_get_members($mapping->groupid, 'u.id'));
            // Pluck the student members.
            $members = array_intersect($members, $students);

            // Cache/override each member's grade for this mapping.
            foreach ($members as $studentid) {
                $this->cache_grade($studentid, $mapping);
            }
        }

        // Save student grades for each mapping.
        $this->log("Saving grades to database.", 1);
        $this->save_grades();

        // Delete remaining grades to complete the sync.
        $this->log("Deleting old grades.", 1);
        $this->delete_grades();
        
        $this->log_finish("Done");
    }

    /**
     * Load in all of the existing staged grades for the course.
     */
    protected function cache_existing_grades() {
        global $DB;

        $sql = "SELECT *
                  FROM {gradesync_grades}
                 WHERE courseid = ?";
        $grades = $DB->get_records_sql($sql, array('courseid' => $this->courseid));
        foreach ($grades as $grade) {
            $key = $grade->externalclass . '-' . $grade->externalgradeid  . '-' . $grade->username;
            $this->log("Caching existing staged grades {$grade->externalclass}/{$grade->externalgradeid} for {$grade->username}", 2);
            $this->existinggrades[$key] = $grade;
        }
    }

    /**
     * Get grade for a student based on a mapping.
     *
     * @param int studentid
     * @param stdClass mapping
     */
    protected function cache_grade($studentid, $mapping) {
        global $DB;

        $sql = "SELECT *
                  FROM {grade_grades}
                 WHERE userid = ?
                  AND  itemid = ?
                  AND  ( NOT (rawgrade = 0) )";
        $params = array(
            $studentid,
            $mapping->gradeitemid,
        );
        $grade = $DB->get_record_sql($sql, $params);
        
        if (empty($grade)) {
            return;
        }

        // Check markoutof from both assessment sources and and skip if they do not match.
        $gradeitem = $DB->get_record('grade_items', array('id' => $grade->itemid));
        $extassessment = $this->externalDB->get_record_sql($this->config->sqlextassessment, array($mapping->externalclass, intval($mapping->externalgradeid)));
        $outofa = intval($gradeitem->grademax);
        $outofb = intval($extassessment->markoutof);
        if ($outofa != $outofb) {
            $this->log("Skipping {$mapping->externalclass}/{$mapping->externalgradeid} because markoutof values do not match: {$outofa} != {$outofb}", 2);
            return;
        }

        $username = $DB->get_field('user', 'username', array('id' => $studentid));
        $key = $mapping->externalclass . '-' . $mapping->externalgradeid . '-' . $username;
        $this->log("Caching grade {$mapping->externalclass}/{$mapping->externalgradeid} for {$username}", 2);
        $this->log("Markoutof matches for {$mapping->externalclass}/{$mapping->externalgradeid}: {$outofa} == {$outofb}", 3);
        $gradeobj = new \stdClass();
        $gradeobj->username        = $username;
        $gradeobj->courseid        = $mapping->courseid;
        $gradeobj->groupid         = $mapping->groupid;
        $gradeobj->mappingid       = $mapping->id;
        $gradeobj->externalclass   = $mapping->externalclass;
        $gradeobj->externalgradeid = $mapping->externalgradeid;
        $gradeobj->rawgrade        = $grade->rawgrade;
        $gradeobj->gradegradesid   = $grade->id;
        $this->grades[$key] = $gradeobj;
    }

    /**
     * Save the grades for this course to the db.
     *
     */
    protected function save_grades() {
        global $DB;

        foreach ($this->grades as $key => $grade) {
            // Check if the grade already staged.
            $params = array(
                'externalclass' => $grade->externalclass,
                'externalgradeid' => $grade->externalgradeid,
                'username' => $grade->username,
            );
            if ($stagedgrade = $DB->get_record('gradesync_grades', $params)) {
                // Update the existing record.
                $grade->id = $stagedgrade->id;
                $DB->update_record('gradesync_grades', $grade);
                $this->log("Updated grade {$grade->externalclass}/{$grade->externalgradeid} for {$grade->username}", 2);
            } else {
                // Insert a new grade.
                $DB->insert_record('gradesync_grades', $grade);
                $this->log("Inserted grade {$grade->externalclass}/{$grade->externalgradeid} for {$grade->username}", 2);
            }
            unset($this->existinggrades[$key]);
        }
    }

    /**
     * Delete old grades.
     *
     */
    protected function delete_grades() {
        global $DB;

        foreach ($this->existinggrades as $grade) {
            if ($grade->id) {
                $DB->delete_records('gradesync_grades', array('id' => $grade->id));
                $this->log("Deleted old grade {$grade->externalclass}/{$grade->externalgradeid} for {$grade->username}", 2);
            }
        }
    }

}