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
 * Mapping page.
 *
 * @package   local_gradesync
 * @copyright 2020 Michael Vangelovski <michael.vangelovski@hotmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

// Include required files and classes.
require_once('../../config.php');
require_once('lib.php');

$courseid = required_param('courseid', PARAM_INT);
$context = context_course::instance($courseid);
$course = $DB->get_record('course', array('id' => $courseid));

// Set up page parameters.
$url = new moodle_url('/local/gradesync/map.php', array('courseid' => $courseid));
$PAGE->set_context($context);
$PAGE->set_url($url->out());
$title = get_string('grademappings', 'local_gradesync');
$PAGE->set_heading($title);
$PAGE->set_title($SITE->fullname . ': ' . $title);
$PAGE->navbar->add($course->fullname, new moodle_url('/course/view.php', array('id' => $courseid)));
$PAGE->navbar->add($title, $url);

// Ensure user is logged in and has capability to update course.
require_login();
require_capability('moodle/course:update', $context, $USER->id); 

// Include page CSS.
$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/local/gradesync/styles.css', array('nocache' => rand().rand())));

// Output header.
echo $OUTPUT->header();

// Load and check the global settings.
$config = get_config('local_gradesync');
if (empty($config->dbtype) || 
    empty($config->dbhost) || 
    empty($config->dbuser) || 
    empty($config->dbpass) || 
    empty($config->dbname) || 
    empty($config->sqlextassessments) || 
    empty($config->coursefield)) {
        $notification = new \core\output\notification(
            get_string('missingsettings', 'local_gradesync'),
            \core\output\notification::NOTIFY_ERROR
        );
        $notification->set_show_closebutton(false);
        echo $OUTPUT->render($notification);
        echo $OUTPUT->footer();
        exit;
}

// Get preferred driver. Last parameter (external = true) means we are connecting to an external database.
$externalDB = moodle_database::get_driver_instance($config->dbtype, 'native', true);        
// Connect to the DB.
$externalDB->connect($config->dbhost, $config->dbuser, $config->dbpass, $config->dbname, '');

// Get the external assessments based on the Moodle course.
$courseextid = $DB->get_field('course', $config->coursefield, array('id' => $courseid));
$extassessments = array_values($externalDB->get_records_sql($config->sqlextassessments, array($courseextid)));

// Extract and sort distinct assessment codes.
$classes = array_column($extassessments, 'class');
$classes = array_unique($classes);
sort($classes);

// Get the grade items for this course.
$gradeitems = array();
$rs = array_values($DB->get_records('grade_items', array('courseid' => $courseid)));
foreach ($rs as $gradeitem) {
    if ($gradeitem->itemtype == 'course') {
        $gradeitem->itemname = get_string('map:coursefinalgrade', 'local_gradesync');
    }
    elseif ($gradeitem->itemtype == 'category') {
        $gradecat = $DB->get_record('grade_categories', array('id' => $gradeitem->iteminstance));
        $gradeitem->itemname = $gradecat->fullname . ' (grade category)';
    }
    else {
        $gradeitem->itemname .= ' (' . $gradeitem->itemmodule . ')';
    }
    $gradeitems[] = (array) $gradeitem;
}
//echo "<pre>"; var_export($gradeitems); exit;

// Set up the page data.
$data = array(
    'config' => $config,
    'is_multiple_classes' => (count($classes) > 1),
    'classes' => $classes,
    'assessments' => $extassessments,
    'gradeitems' => $gradeitems,
);
//echo "<pre>"; var_export($data); exit;

// Output page template.
echo $OUTPUT->render_from_template('local_gradesync/map', $data);

// Include page scripts.
$PAGE->requires->js_call_amd('local_gradesync/map', 'init');

// Output footer.
echo $OUTPUT->footer();
