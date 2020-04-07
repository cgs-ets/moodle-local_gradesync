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
     * @var array Mappings to be stored.
     */
    protected $grades = array();

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

        $courseid = $this->get_custom_data();
        $this->log_start("Processing grade sync for course {$courseid}");
        $course = $DB->get_record('course', array('id' => $courseid));
        if (empty($course)) {
            $this->log("Error - course record not found.", 1);
            return;
        }
        $this->log("Course record found: $course->fullname", 1);

        // Load in all of the existing staged grades for this course.
        $this->log("Caching existing grades for course.", 1);
        $this->cache_existing_grades($courseid);

        // Get the student roleid.
        $studentroleid = $DB->get_field('role', 'id', array('shortname'=> 'student'));

        // Get a list of users in this course.
        $courseuserroles = enrol_get_course_users_roles($courseid);

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
        $this->log("Caching grades at the course level.", 1);
        $sql = "SELECT *
                  FROM {gradesync_mappings}
                 WHERE courseid = ?
                   AND groupid = 0";
        $params = array($courseid);
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
        $params = array($courseid);
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
     * Load in all of the existing staged grades for this course.
     *
     * @param int courseid
     */
    protected function cache_existing_grades($courseid) {
        global $DB;

        $sql = "SELECT *
                  FROM {gradesync_grades}
                 WHERE courseid = ?";
        $grades = $DB->get_records_sql($sql, array('courseid' => $courseid));
        foreach ($grades as $grade) {
            $key = $username . '-' . $grade->externalclass . '-' . $grade->externalgradeid;
            $this->log("Cachning existing grade {$grade->externalclass}/{$grade->externalgradeid} for {$grade->username}", 2);
            $this->grades[$key] = $grade;
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

        $rawgradene = $DB->sql_isnotempty('grade_grades', 'rawgrade', true, false);
        $sql = "SELECT *
                  FROM {grade_grades}
                 WHERE userid = ?
                  AND  itemid = ?
                  AND  $rawgradene";
        $params = array(
            $studentid,
            $mapping->gradeitemid,
        );
        $grade = $DB->get_record_sql($sql, $params);
        
        if (empty($grade)) {
            return;
        }

        $username = $DB->get_field('user', 'username', array('id' => $studentid));
        $key = $username . '-' . $grade->externalclass . '-' . $grade->externalgradeid;
        $this->log("Caching grade for {$username} and mapping {$mapping->id}", 2);
        $gradeobj = new \stdClass();
        $gradeobj->mappingid = $mapping->id;
        $gradeobj->externalclass = $mapping->externalclass;
        $gradeobj->externalgradeid = $mapping->externalgradeid;
        $gradeobj->username = $username;
        $gradeobj->rawgrade = $grade->rawgrade;
        $gradeobj->gradegradesid = $grade->id;
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
            unset($this->grades[$key]);
        }
    }

    /**
     * Delete old grades.
     *
     */
    protected function delete_grades() {
        global $DB;

        foreach ($this->grades as $grade) {
            if ($grade->id) {
                $DB->delete_records('gradesync_grades', array('id' => $grade->id));
                $this->log("Deleted old grade {$grade->externalclass}/{$grade->externalgradeid} for {$grade->username}", 2);
            }
        }
    }

}