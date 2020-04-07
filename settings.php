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
 * Defines the global settings of the plugin.
 *
 * @package   local_gradesync
 * @copyright 2020 Michael Vangelovski <michael.vangelovski@hotmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {

    $settings = new admin_settingpage('local_gradesync', get_string('pluginname', 'local_gradesync'));
    $ADMIN->add('localplugins', $settings);

    // External system name
    $name = 'local_gradesync/extsystem';
    $title = get_string('config:extsystem', 'local_gradesync');
    $description = '';
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $settings->add($setting);

    // External system css
    $name = 'local_gradesync/extsystemcss';
    $title = get_string('config:extsystemcss', 'local_gradesync');
    $description = get_string('config:extsystemcss_desc', 'local_gradesync');
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $settings->add($setting);

    // Moodle css
    $name = 'local_gradesync/moodlecss';
    $title = get_string('config:moodlecss', 'local_gradesync');
    $description = get_string('config:moodlecss_desc', 'local_gradesync');
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $settings->add($setting);

    // DB type.
    $name = 'local_gradesync/dbtype';
    $title = get_string('config:dbtype', 'local_gradesync');
    $description = get_string('config:dbtype_desc', 'local_gradesync');
    $default = '';
    $options = array('', "mysqli", "oci", "pdo", "pgsql", "sqlite3", "sqlsrv");
    $options = array_combine($options, $options);
    $setting = new admin_setting_configselect($name, $title, $description, $default, $options);
    $settings->add($setting);

    // DB host.
    $name = 'local_gradesync/dbhost';
    $title = get_string('config:dbhost', 'local_gradesync');
    $description = get_string('config:dbhost_desc', 'local_gradesync');
    $default = 'localhost';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $settings->add($setting);

    // DB user.
    $name = 'local_gradesync/dbuser';
    $title = get_string('config:dbuser', 'local_gradesync');
    $description = '';
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $settings->add($setting);

    // DB pass.
    $name = 'local_gradesync/dbpass';
    $title = get_string('config:dbpass', 'local_gradesync');
    $description = '';
    $default = '';
    $setting = new admin_setting_configpasswordunmask($name, $title, $description, $default);
    $settings->add($setting);

    // DB name.
    $name = 'local_gradesync/dbname';
    $title = get_string('config:dbname', 'local_gradesync');
    $description = '';
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $settings->add($setting);

    // Course field.
    $name = 'local_gradesync/coursefield';
    $title = get_string('config:coursefield', 'local_gradesync');
    $description = '';
    $default = 'idnumber';
    $options = array('id' => 'id', 'idnumber' => 'idnumber', 'shortname' => 'shortname');
    $setting = new admin_setting_configselect($name, $title, $description, $default, $options);
    $settings->add($setting);

    // SQL External Assessments.
    $name = 'local_gradesync/sqlextassessments';
    $title = get_string('config:sqlextassessments', 'local_gradesync');
    $default = 'EXEC usp_get_assessment_tasks_for_course ?';
    $description = get_string('config:sqlextassessments_desc', 'local_gradesync');
    $setting = new admin_setting_configtextarea($name, $title, $description, $default);
    $settings->add($setting);

}
