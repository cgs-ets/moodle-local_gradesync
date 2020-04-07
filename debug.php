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
 * Debugger
 *
 * @package   local_gradesync
 * @copyright 2020 Michael Vangelovski <michael.vangelovski@hotmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once(dirname(__FILE__) . '/../../config.php');

// Set context.
$context = context_system::instance();

// Set up page parameters.
$PAGE->set_context($context);
$pageurl = new moodle_url('/local/gradesync/debug.php');
$PAGE->set_url($pageurl);
$title = get_string('pluginname', 'local_gradesync');
$PAGE->set_heading($title);
$PAGE->set_title($SITE->fullname . ': ' . $title);
$PAGE->navbar->add($title);
// Add css
$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/gradesync/styles.css', array('nocache' => rand().rand())));

// Ensure user is logged in and has capability to update course.
require_login();
require_capability('moodle/site:config', $context, $USER->id); 

// Build page output
$output = '';
$output .= $OUTPUT->header();



// Debug code here.
echo "<pre>";



$courseid = 2;
$sql = "SELECT *
      FROM {gradesync_mappings}
     WHERE courseid = ?
       AND groupid != 0";
    $params = array($courseid);
    $mappings = $DB->get_records_sql($sql, $params);
    foreach ($mappings as $mapping) {
        // Get a list of students in the group.
        $members = array_keys(groups_get_members($mapping->groupid, 'u.id'));
        var_export($members); exit;
    }





// Final outputs
$output .= $OUTPUT->footer();
echo $output;