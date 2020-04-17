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
 * Strings for local_gradesync
 *
 * @package   local_gradesync
 * @copyright 2020 Michael Vangelovski <michael.vangelovski@hotmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

$string['title'] = 'Grade Sync';
$string['pluginname'] = 'Grade Sync';
$string['privacy:metadata'] = 'Grade Sync does not store any personal data.';
$string['grademappings'] = 'Grade Mappings';

$string['config:extsystem'] = 'External system name';
$string['config:extsystemcss'] = 'External system header CSS';
$string['config:extsystemcss_desc'] = 'Inline css that will be injected into the style attribute of the system header column on the mapping page.';
$string['config:moodlecss'] = 'Moodle header CSS';
$string['config:moodlecss_desc'] = 'Inline css that will be injected into the style attribute of the system header column on the mapping page.';

$string['config:dbtype'] = 'Database driver';
$string['config:dbtype_desc'] = 'ADOdb database driver name, type of the external database engine.';
$string['config:dbhost'] = 'Database host';
$string['config:dbhost_desc'] = 'Type database server IP address or host name. Use a system DSN name if using ODBC. Use a PDO DSN if using PDO.';
$string['config:dbname'] = 'Database name';
$string['config:dbuser'] = 'Database user';
$string['config:dbpass'] = 'Database password';
$string['config:coursefield'] = 'Course field';
$string['config:sqlextassessments'] = 'External assessments SQL';
$string['config:sqlextassessments_desc'] = 'The SQL used to fetch assessments from the external system based on a Moodle course code. The SQL is a configurable field because it is unique to the implementation of the external system at your institution. The plugin will execute the SQL and expects the following return values: rownum, class, id, description1, description2, description3. It is passed a single parameter for the external course id. See the README documentation for more information.';
$string['missingsettings'] = 'Required settings for local_gradesync are missing.';

$string['map:assessmentclasses'] = 'Multiple related classes were found in {$a}. Please select one to begin mapping grade items.';
$string['map:coursefinalgrade'] = 'Course final grade';
$string['map:mappingtitle'] = 'Grade mappings for this course';
$string['map:overrides'] = 'Override grade mappings for individual classes';

$string['crontask_gradesync'] = 'Sync grades to the staging table';
$string['gradesyncsetup'] = 'Grade sync setup';
