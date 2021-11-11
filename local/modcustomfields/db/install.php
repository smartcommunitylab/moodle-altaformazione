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
 * post installation hook for adding data.
 *
 * @package    local_modcustomfields
 * @copyright  2021 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Post installation procedure
 */
function xmldb_local_modcustomfields_install() {
    global $DB;

    $cf_category = new stdClass;
    $cf_category->name = "Custom Fields";
    $cf_category->component = "local_modcustomfields";
    $cf_category->area = "mod";
    $cf_category->contextid = 1;
    $cf_category->descriptionformat = 0;
    $cf_category->sortorder = 0;
    $cf_category->itemid = 0;
    $cf_category->timecreated = time();
    $cf_category->timemodified= time();
    $id = $DB->insert_record('customfield_category', $cf_category, true);

    $cf_field = new stdClass;
    $cf_field->shortname = "duration_hours";
    $cf_field->name = "Duration in hours";
    $cf_field->type = "duration";
    $cf_field->timecreated = time();
    $cf_field->timemodified= time();
    $cf_field->categoryid=$id;
    $cf_field->sortorder=1;
    $cf_field->configdata = '{"required":"0","uniquevalues":"0","defaultvalue":"0","displaysize":50,"maxlength":1333,"ispassword":"0","link":"","locked":"0","visibility":"2","units":"3600"}';
    $cf_field->description = '<p dir="ltr" style="text-align: left;">Define the duration in hours for each activity module</p>';
    $result = $DB->insert_record('customfield_field', $cf_field);

    $cf_field = new stdClass;
    $cf_field->shortname = "duration_mins";
    $cf_field->name = "Duration in minutes";
    $cf_field->type = "duration";
    $cf_field->timecreated = time();
    $cf_field->timemodified= time();
    $cf_field->categoryid=$id;
    $cf_field->sortorder=2;
    $cf_field->configdata = '{"required":"0","uniquevalues":"0","defaultvalue":"0","displaysize":50,"maxlength":1333,"ispassword":"0","link":"","locked":"0","visibility":"2","units":"60"}';
    $cf_field->description = '<p dir="ltr" style="text-align: left;">Define the duration in minutes for each activity module</p>';
    $result = $DB->insert_record('customfield_field', $cf_field);

    return $result;
}