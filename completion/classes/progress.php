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
 * Contains class used to return completion progress information.
 *
 * @package    core_completion
 * @copyright  2017 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_completion;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot.'/blocks/progress/lib.php');
/**
 * Class used to return completion progress information.
 *
 * @package    core_completion
 * @copyright  2017 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class progress {

    /**
     * Returns the course percentage completed by a certain user, returns null if no completion data is available.
     *
     * @param \stdClass $course Moodle course object
     * @param int $userid The id of the user, 0 for the current user
     * @return null|float The percentage, or null if completion is not supported in the course,
     *         or there are no activities that support completion.
     */
    public static function get_course_progress_percentage($course, $userid = 0) {
        global $USER;

        // Make sure we continue with a valid userid.
        if (empty($userid)) {
            $userid = $USER->id;
        }

        $completion = new \completion_info($course);

        // First, let's make sure completion is enabled.
        if (!$completion->is_enabled()) {
            return null;
        }

        if (!$completion->is_tracked_user($userid)) {
            return null;
        }

        // Before we check how many modules have been completed see if the course has.
        if ($completion->is_course_complete($userid)) {
            return 100;
        }
/*
        // Get the number of modules that support completion.
        $modules = $completion->get_activities();
        $count = count($modules);
        if (!$count) {
            return null;
        }

        // Get the number of modules that have been completed.
        $completed = 0;
        foreach ($modules as $module) {
            $data = $completion->get_data($module, true, $userid);
            $completed += $data->completionstate == COMPLETION_INCOMPLETE ? 0 : 1;
        }

	return ($completed / $count) * 100;
 */
	// Get the number of modules that support completion.
        $modules = $completion->get_activities();
        $count = count($modules);
        if (!$count) {
            return null;
        }
        return \core_completion\progress::calculate_progress_block($course, $userid, $modules, $completion);
    }

    public static function calculate_progress_block($course, $userid, $modules, $completion){
        $courseid = $course->id;
        global $DB;
        $sql = "SELECT bi.id,
                           bp.id AS blockpositionid,
                           COALESCE(bp.region, bi.defaultregion) AS region,
                           COALESCE(bp.weight, bi.defaultweight) AS weight,
                           COALESCE(bp.visible, 1) AS visible,
                           bi.configdata
                      FROM {block_instances} bi
                 LEFT JOIN {block_positions} bp ON bp.blockinstanceid = bi.id
                                               AND ".$DB->sql_like('bp.pagetype', ':pagetype', false)."
                     WHERE bi.blockname = 'progress'
                       AND bi.parentcontextid = :contextid
                  ORDER BY region, weight, bi.id";
        $modules_enabled = block_progress_modules_in_use($courseid);
        $context = block_progress_get_course_context($courseid);
        $params = array('contextid' => $context->id, 'pagetype' => 'course-view-%');
        $blockinstances = $DB->get_records_sql($sql, $params);
        foreach ($blockinstances as $blockid => $blockinstance) {
            $blockinstance->config = unserialize(base64_decode($blockinstance->configdata));
            if (!empty($blockinstance->config)) {
                $blockinstance->events = block_progress_event_information(
                                             $blockinstance->config,
                                             $modules_enabled,
                                             $course->id);
                $blockinstance->events = block_progress_filter_visibility($blockinstance->events,
                                             $userid, $context, $course);
            }
            $attempts = block_progress_attempts($modules_enabled,
                                                $blockinstance->config,
                                                $blockinstance->events,
                                                $userid,
                                                $courseid);
            $progress = block_progress_percentage($blockinstance->events, $attempts);
            // Considering only the first progress block
            return $progress;
        }
        return \core_completion\progress::all_activities_weights($modules, $completion, $userid);
    }

    /**
     * If it doesn't exist a progress block then consider all activities with no empty weights
     */
    public static function all_activities_weights($modules, $completion, $userid){
        global $DB;
        $completed = 0;
        $total_weight = 0;
        foreach ($modules as $module) {
            $data = $completion->get_data($module, true, $userid);
            $weights = $DB->get_records_sql("
                SELECT sum(intvalue) as total_secs
                FROM {customfield_data} d
                INNER JOIN {customfield_field} f ON d.fieldid=f.id
                WHERE
                    f.shortname in ('duration_hours', 'duration_mins') AND d.instanceid=?", array($data->coursemoduleid));
            foreach ($weights as $key => $value) {
                $total_weight += intval($key);
                $completed += $data->completionstate == COMPLETION_INCOMPLETE ? 0 : intval($key);
            }
        }
        if($total_weight == 0){
            return \core_completion\progress::count_completed_activities($modules, $completion, $userid);
        } else{
            return (int)round(($completed / $total_weight) * 100);

        }
    }

    /**
     * Standard way of counting completed activities
     */
    public static function count_completed_activities($modules, $completion, $userid){
        // Get the number of modules that have been completed.
        $count = count($modules);
        $completed = 0;
        foreach ($modules as $module) {
            $data = $completion->get_data($module, true, $userid);
            $completed += $data->completionstate == COMPLETION_INCOMPLETE ? 0 : 1;
        }
        return ($completed / $count) * 100;
    }
}
