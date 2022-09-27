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
 * Course renderer.
 *
 * @package    theme_moove
 * @copyright  2022 Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_moove\external;

use renderer_base;
use moodle_url;
/**
 * Renderers to align Moove's course elements to what is expect
 *
 * @package    theme_moove
 * @copyright  2022 Willian Mano {@link https://conecti.me}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_summary_exporter extends \core_course\external\course_summary_exporter {
    
    /**
     * Constructor - saves the persistent object, and the related objects.
     *
     * @param mixed $data - Either an stdClass or an array of values.
     * @param array $related - An optional list of pre-loaded objects related to this object.
     */
    public function __construct($data, $related = array()) {
        if (!array_key_exists('isfavourite', $related)) {
            $related['isfavourite'] = false;
        }
        parent::__construct($data, $related);
    }
    
    public function get_sum_activities_weights($courseid){
        global $DB;
        $hours = 0;
        $mins = 0;
        $weights = $DB->get_record_sql("
            SELECT sum(intvalue) as total
            FROM {customfield_data} d
            INNER JOIN {customfield_field} f ON d.fieldid=f.id
            INNER JOIN {course_modules} m ON d.instanceid = m.id
            WHERE
                f.shortname in ('duration_hours', 'duration_mins') AND m.course=?", array($courseid));
        if($weights){
            $total = $weights->total;
            if ($total != null){
                $hours = intval($total/3600);
                $mins = intval($total/60);
                if ($mins > 60)
                    $mins = $mins - $hours  * 60;
            }
        }       
        return $hours. "h ". str_pad($mins, 2, 0, STR_PAD_LEFT). "m";
    }

    protected function get_other_values(renderer_base $output) {
        global $CFG;
        $courseimage = self::get_course_image($this->data);
        if (!$courseimage) {
            $courseimage = $output->get_generated_image_for_id($this->data->id);
        }
        $progress = self::get_course_progress($this->data);
        $hasprogress = false;
        if ($progress === 0 || $progress > 0) {
            $hasprogress = true;
        }
        $progress = floor($progress);
        $weight = $this->get_sum_activities_weights($this->data->id);
        $coursecategory = \core_course_category::get($this->data->category, MUST_EXIST, true);
        return array(
            'fullnamedisplay' => get_course_display_name_for_list($this->data),
            'viewurl' => (new moodle_url('/course/view.php', array('id' => $this->data->id)))->out(false),
            'courseimage' => $courseimage,
            'progress' => $progress,
            'hasprogress' => $hasprogress,
            'isfavourite' => $this->related['isfavourite'],
            'hidden' => boolval(get_user_preferences('block_myoverview_hidden_course_' . $this->data->id, 0)),
            'showshortname' => $CFG->courselistshortnames ? true : false,
            'coursecategory' => $coursecategory->name,
            'enddate_formatted' => strVal(date('d/m/Y', $this->data->enddate)), # optional: get_string('strftimedate', 'core_langconfig')
            'startdate_formatted' => strVal(date('d/m/Y', $this->data->startdate)),
            'weight' => $weight,
            'testing' => "1233333333333333333333"
        );
    }

    public static function define_other_properties() {
        return array(
            'fullnamedisplay' => array(
                'type' => PARAM_TEXT,
            ),
            'viewurl' => array(
                'type' => PARAM_URL,
            ),
            'courseimage' => array(
                'type' => PARAM_RAW,
            ),
            'progress' => array(
                'type' => PARAM_INT,
                'optional' => true
            ),
            'hasprogress' => array(
                'type' => PARAM_BOOL
            ),
            'isfavourite' => array(
                'type' => PARAM_BOOL
            ),
            'hidden' => array(
                'type' => PARAM_BOOL
            ),
            'timeaccess' => array(
                'type' => PARAM_INT,
                'optional' => true
            ),
            'showshortname' => array(
                'type' => PARAM_BOOL
            ),
            'coursecategory' => array(
                'type' => PARAM_TEXT
            ),
            'startdate_formatted' => array(
                'type' => PARAM_TEXT
            ),
            'enddate_formatted' => array(
                'type' => PARAM_TEXT
            ),
            'weight' => array(
                'type' => PARAM_TEXT
            ),
            'testing' => array(
                'type' => PARAM_TEXT
            )
        );
    }
}
