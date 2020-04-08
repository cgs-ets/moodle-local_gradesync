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
 * The main scheduled task to set up syncing of grades to the staging table. 
 * The effort is divided into independent adhoc tasks that process the sync for a single course.
 *
 * @package   local_gradesync
 * @copyright 2020 Michael Vangelovski <michael.vangelovski@hotmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_gradesync\task;

defined('MOODLE_INTERNAL') || die();


class cron_task_gradesync extends \core\task\scheduled_task {

    // Use the logging trait to get some nice, juicy, logging.
    use \core\task\logging_trait;

    /**
    * A list of unique courses in the gradesync_mappings table.
    */
    protected $mappingcourses = array();

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

        $this->log_start("Starting gradesync.");

        // Get courses that have mappings configured.
        $sql = "SELECT id, courseid
                  FROM {gradesync_mappings}";
        $mappings = $DB->get_records_sql($sql);
        if (empty($mappings)) {
            return;
        }
        $this->mappingcourses = array_unique(array_column($mappings, 'courseid'));


        $this->sync_grades();

        $this->cleanup_mappings();
        
        $this->log_finish("Done");

    }

    /**
     * The main syncing process.
     *
     */
    protected function sync_grades() {
        global $DB;

        // Create an adhoc task for each course.
        foreach ($this->mappingcourses as $courseid) {
            // Look up the course, skip if not visible or ended.
            $sql = "SELECT *
                      FROM {course}
                     WHERE id = ?
                       AND visible = 1
                       AND (enddate = 0 OR enddate > ?)";
            $params = array($courseid, time());
            if ($course = $DB->get_record_sql($sql, $params)) {
                $this->log("Creating adhoc gradesync task for $course->fullname ($course->id)", 1);
                $task = new \local_gradesync\task\adhoc_task_gradesync();
                $task->set_custom_data($course->id);
                $task->set_component('local_gradesync');
                \core\task\manager::queue_adhoc_task($task);
            }
        }
    }

    /**
     * Delete mappings where course/group no longer available.
     *
     */
    protected function cleanup_mappings() {
        global $DB;

        // Get list of visible and active courseids.
        $now = time();
        $sql = "SELECT id
                  FROM {course}
                 WHERE visible = 1
                   AND (enddate = 0 OR enddate > {$now})";
        $courseids = array_values($DB->get_records_sql($sql));
        // Determine inactive courses.
        $activemappings = array_intersect($courseids, $this->mappingcourses);
        $inactivemappings = array_diff($activemappings, $this->mappingcourses);
        // Delete inactive mappings.
        $this->log("Delete mappings for inactive courses: " . implode(', ', $inactivemappings), 2);
        list($insql, $inparams) = $DB->get_in_or_equal($inactivemappings);
        $sql = "DELETE FROM {gradesync_mappings} WHERE courseid $insql";
        $DB->execute($sql, $inparams);


        // Get list of viable course groups.
        list($insql, $inparams) = $DB->get_in_or_equal($courseids);
        $sql = "SELECT id
                  FROM {groups}
                 WHERE courseid $insql";
        $groupids = array_values($DB->get_records_sql($sql));
        // Determine inactive groups.
        $sql = "SELECT id, groupid
                  FROM {gradesync_mappings}";
        $mappings = $DB->get_records_sql($sql);
        if (empty($mappings)) {
            return;
        }
        $mappinggroups = array_unique(array_column($mappings, 'groupid'));
        $activemappings = array_intersect($groupids, $mappinggroups);
        $inactivemappings = array_diff($activemappings, $mappinggroups);
        // Delete inactive mappings.
        $this->log("Delete mappings for inactive groups: " . implode(', ', $inactivemappings), 2);
        list($insql, $inparams) = $DB->get_in_or_equal($inactivemappings);
        $sql = "DELETE FROM {gradesync_mappings} WHERE groupid $insql";
        $DB->execute($sql, $inparams);

    }

}