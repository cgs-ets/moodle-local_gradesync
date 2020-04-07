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
 * Provides {@link local_gradesync\external\save_mapping} trait.
 *
 * @package   local_gradesync
 * @category  external
 * @copyright 2020 Michael Vangelovski <michael.vangelovski@hotmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

namespace local_gradesync\external;

defined('MOODLE_INTERNAL') || die();

use \local_gradesync\mappinglib;
use external_function_parameters;
use external_value;

require_once($CFG->libdir.'/externallib.php');

/**
 * Trait implementing the external function.
 */
trait save_mapping {

    /**
     * Describes the structure of parameters for the function.
     *
     * @return external_function_parameters
     */
    public static function save_mapping_parameters() {
        return new external_function_parameters([
            'externalclass' => new external_value(PARAM_RAW, 'External system grade class'),
            'externalgradeid' => new external_value(PARAM_INT, 'External system grade id'),
            'courseid' => new external_value(PARAM_INT, 'Moodle course id'),
            'groupid' => new external_value(PARAM_INT, 'Moodle group id'),
            'gradeitemid' => new external_value(PARAM_INT, 'Grade item id in Moodle course.'),
        ]);
    }

    /**
     * Save a grade mapping.
     *
     * @param string $externalclass
     * @param int $externalgradeid
     * @param int $gradeitemid
     */
    public static function save_mapping($externalclass, $externalgradeid, $courseid, $groupid, $gradeitemid) {
        self::validate_parameters(self::save_mapping_parameters(), compact('externalclass', 'externalgradeid', 'courseid', 'groupid', 'gradeitemid'));
        $result = mappinglib::save($externalclass, $externalgradeid, $courseid, $groupid, $gradeitemid);
        return $result;
    }

    /**
     * Describes the structure of the function return value.
     *
     * @return external_single_structure
     */
    public static function save_mapping_returns() {
         return new external_value(PARAM_RAW, 'Id of saved result or false if error.');
    }
}