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
 * @package   local_gradesync
 * @copyright 2020 Michael Vangelovski <michael.vangelovski@hotmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * Add the gradesync link to Moodle's global navigation.
 *
 * @param global_navigation $navigation
 */
/*function local_gradesync_extend_navigation(global_navigation $navigation) {
    global $USER, $PAGE;



    // Get the course set against the page, by default this will be the site.
    $course = $PAGE->course;
    $addmenu = false;
    // Only proceed if we are inside a course and we are _not_ on the frontpage.
    if ($PAGE->context->get_course_context(false) == true && $course->id != SITEID) {
        if ($gradesnode = $navigation->find('grades', global_navigation::TYPE_SETTING)) {
            if (has_capability('moodle/site:config', context_user::instance($USER->id))) {
            	$addmenu = true;
            }
        }
    }


    if ($addmenu) {
    	$mappingurl = new moodle_url('/local/gradesync/map.php', ['courseid' => $course->id]);
    	$icon = new pix_icon('i/grades', '');
	    $mappingnode = navigation_node::create(
	        get_string('grademappings', 'local_gradesync'),
	        $mappingurl,
	        global_navigation::TYPE_SETTING,
	        null,
	        'gradesyncmap',
	        $icon
	    );
	    $mappingnode->nodetype = 1;
	    $mappingnode->showinflatnavigation = false;
	    $mappingnode->isexpandable = false;
	    $mappingnode->jsenabled = false;
	    $mappingnode->collapse = false;

	    $parent = $gradesnode->parent;
	    $mappingnode->set_parent($gradesnode);
	    $parent->add_node($mappingnode, '1');
	    
    }
}*/


function local_gradesync_extend_navigation_course(navigation_node $parentnode, stdClass $course, context_course $context) {
    global $USER, $PAGE;

    // Get the course set against the page.
    $course = $PAGE->course;
    $addmenu = false;
    // Only proceed if we are inside a course and we are _not_ on the frontpage.
    if ($PAGE->context->get_course_context(false) == true && $course->id != SITEID) {
        if ($gradebooksetup = $parentnode->find('gradebooksetup', navigation_node::TYPE_SETTING)) {
            if (has_capability('moodle/site:config', context_user::instance($USER->id))) {
            	$addmenu = true;
            }
        }
    }

    if ($addmenu) {
    	$mappingurl = new moodle_url('/local/gradesync/map.php', ['courseid' => $course->id]);
    	$icon = new pix_icon('i/grades', '');
	    $mappingnode = navigation_node::create(
	        get_string('gradesyncsetup', 'local_gradesync'),
	        $mappingurl,
	        navigation_node::TYPE_SETTING,
	        null,
	        'gradesyncsetup',
	        $icon
	    );
	    $mappingnode->nodetype = 0;
	    $mappingnode->showinflatnavigation = false;
	    $mappingnode->isexpandable = false;
	    $mappingnode->jsenabled = false;
	    $mappingnode->collapse = false;
	    $parentnode->add_node($mappingnode, 'coursebadges');
	}
}



