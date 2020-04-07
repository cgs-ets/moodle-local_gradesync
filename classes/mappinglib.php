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
 * Provides the {@link local_gradesync\mappinglib} class.
 *
 * @package   local_gradesync
 * @copyright 2020 Michael Vangelovski <michael.vangelovski@hotmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_gradesync;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/gradesync/lib.php');

class mappinglib {

    /**
     * Save a grade mapping to the db.
     *
     * @param string $externalclass
     * @param int $externalgradeid
     * @param int $courseid
     * @param int $groupid
     * @param int $gradeitemid
     * @return int|bool. id of the saved row or false for error.
     */
    public static function save($externalclass, $externalgradeid, $courseid, $groupid, $gradeitemid) {
        global $DB, $USER;

        // Delete the record.
        if ($gradeitemid == -1) {
            $DB->delete_records('gradesync_mappings', array(
                'externalclass' => $externalclass,
                'externalgradeid' => $externalgradeid,
                'courseid' => $courseid,
                'groupid' => $groupid,
            ));
            return -1;
        }

        // Build the record.
        $record = new \stdClass();
        $record->externalclass = $externalclass;
        $record->externalgradeid = $externalgradeid;
        $record->courseid = $courseid;
        $record->groupid = $groupid;
        $record->gradeitemid = $gradeitemid;
        $record->usernamecreated = $USER->username;
        $record->usernamemodified = $USER->username;
        $record->timecreated = time();
        $record->timemodified = time();

        // Check if mapping already exists.
        $sql = "SELECT *
                  FROM {gradesync_mappings}
                 WHERE externalclass = ?
                   AND externalgradeid = ?
                   AND courseid = ?
                   AND groupid = ?";
        $params = array($externalclass, $externalgradeid, $courseid, $groupid);
        if ($mapping = $DB->get_record_sql($sql, $params)) {
            // Update id.
            $record->id = $mapping->id;
            // Restore created fields.
            $record->usernamecreated = $mapping->usernamecreated;
            $record->timecreated = $mapping->timecreated;
            // Update the record.
            $DB->update_record('gradesync_mappings', $record);
            return $record->id;
        } else {
            return $DB->insert_record('gradesync_mappings', $record);
        }

        return false;
    }
}
