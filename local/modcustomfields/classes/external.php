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
 * Resource external API
 *
 * @package    mod_resource
 * @category   external
 * @copyright  2015 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.0
 */

use tool_monitor\output\managesubs\subs;

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/externallib.php");
require_once($CFG->dirroot .'/course/lib.php');
require_once($CFG->libdir .'/filelib.php');
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->dirroot.'/mod/questionnaire/locallib.php');
require_once($CFG->dirroot . '/lib/testing/generator/lib.php');
require_once($CFG->dirroot . '/lib/phpunit/classes/util.php');
require_once($CFG->dirroot .'/mod/quiz/locallib.php');
/**
 * Resource external functions
 *
 * @package    mod_resource
 * @category   external
 * @copyright  2015 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.0
 */
class local_modcustomfields_external extends external_api {

    /**
     * Create new resource module
     *
     * @param int $resourceid the resource instance id
     * @return array of warnings and status result
     * @since Moodle 3.0
     * @throws moodle_exception
     */
    public static function add_resource($courseid, $sectionid, $resourcename, $path, $restricted_by_activity_id, $duration_hours, $duration_min, $intro) {
        global $DB, $CFG;
        $module = self::add_resource_coursemodule($courseid, $sectionid, $resourcename, $restricted_by_activity_id, $duration_hours, $duration_min, $intro);
        //self::create_file_from_pathname($module->coursemodule, $path);
        $result = array();
        $result['moduleid'] = $module->instance;
        return $result;
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.0
     */
    public static function add_resource_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'courseid instance id'),
                'sectionid' => new external_value(PARAM_INT, 'section id'),
                'resourcename' => new external_value(PARAM_TEXT, 'resource name'),
                'path' => new external_value(PARAM_PATH, 'path'),
                'restricted_by_activity_id' => new external_value(PARAM_INT, 'restricted_by_activity_id', 2),
                'duration_hours' => new external_value(PARAM_INT, 'restricted_by_activity_id', 2) ,
                'duration_min' => new external_value(PARAM_INT, 'restricted_by_activity_id', 2),
                'intro' => new external_value(PARAM_TEXT, 'description '),
            )
        );
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function add_resource_returns() {
        return new external_single_structure(
                array(
                    'moduleid' => new external_value(PARAM_INT, 'moduleid of the resource'),
                ));
    }

    public static  function create_file_from_pathname($coursemodule, $pathname){
        $fs = get_file_storage();
        $context = context_module::instance($coursemodule);
    
        // Prepare file record object
        $fileinfo = array(
            'contextid' => $context->id, 
            'component' => 'mod_resource',     
            'filearea' => 'content',    
            'itemid' => 0,               
            'filepath' => '/',          
            'filename' =>  basename($pathname));    
        return $fs->create_file_from_pathname($fileinfo, $pathname);
    }

    public static  function add_resource_coursemodule($courseid, $sectionid, $name, $restricted_by_activity_id=null, $duration_hours, $duration_min, $intro){
        global $DB;
        $course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);
        $quiz = new stdClass();
        $quiz->course    = $courseid;
        $quiz->name      = $name;
        $quiz->module   = 18;
        $quiz->modulename = 'resource';
        $quiz->section = $sectionid;
        $quiz->visible = 1;
        $quiz->intro = $intro;
        $quiz->completion = 2;
        $quiz->completionview = 1;
        $quiz->completionscoredisabled = 1;
        $quiz->completionstatusrequired = 4;
        $quiz->customfield_duration_hours = $duration_hours;
        $quiz->customfield_duration_mins = $duration_min;
        //if($restricted_by_activity_id != null)
        //    $quiz->availabilityconditionsjson = '{"op":"&","c":[{"type":"completion","cm":' . $restricted_by_activity_id. ',"e":1}],"showc":[true]}';
    
        $moduleinfo = add_moduleinfo($quiz, $course);
        return $moduleinfo;
    }

    function create_questionnaire($courseid, $sectionid, $quizintro, $quizname, $qperpage, $opendate, $closedate, $questions) {
        $generator = new testing_data_generator();
        $generator = $generator->get_plugin_generator('mod_questionnaire');
        global $DB;
        $questionnaire = $generator->create_instance(
            array('course' => $courseid, 
                  'section' => $sectionid,
                  'progressbar'=>1, 
                  'intro' => $quizintro,
                  'name' => $quizname,
                  'completion' => 2,
                  'completionview' => 1,
                  'completionscoredisabled' => 1,
                  'completionstatusrequired' => 4,
                  'closedate' => $closedate,
                  'opendate' => $opendate));
        $nr = 1;
        foreach($questions as $key => $question){
            $questiondata['surveyid'] = $questionnaire->sid;
            $questiondata['name'] = substr($question["name"],0,20);
            $questiondata['content'] = $question["description"];
            $questiondata['required'] = $question["required"];
            $questiondata['position'] = $question["position"];
            if($question["tipo"] == 5){ // essay
                $questiondata['type_id'] = 3;                
                $generator->create_question($questionnaire, $questiondata);
            } elseif($question["tipo"] == 2){ // number
                $questiondata['type_id'] = 10;                
                $generator->create_question($questionnaire, $questiondata);
            } elseif($question["tipo"] == 4){ // dropdown
                $questiondata['type_id'] = 6;
                $opt = explode("~", $question["options"]);
                $quest = $generator->create_question($questionnaire, $questiondata);
                foreach ($opt as $key => $choice) {
                    $option = new stdClass();
                    $option->question_id = $quest->id;
                    $option->content = $choice;
                    $option->value = $choice;                    
                    $DB->insert_record('questionnaire_quest_choice', $choice);
                }                
            } elseif($question["tipo"] == 3){ // multichoice
                if($question["single"] == 1)
                    $questiondata['type_id'] = 4; //radio
                else
                    $questiondata['type_id'] = 5; //checkbox
                $opt = explode("@@", $question["options"]);
                $res_opt = array();
                foreach($opt as $o){
                    $tmp = explode(",," , $o);
                    if($tmp[1] == "0")
                        $name_opt = $tmp[0];
                    else
                        $name_opt = "!other={$tmp[0]}";
                    $res_opt[] = $name_opt;
                }
                $generator->create_question($questionnaire, $questiondata, $res_opt);
            } elseif($question["tipo"] == 1){ // raiting question
                $questiondata['type_id'] = 8; 
                $questiondata['length'] = sizeof(explode('","', $question["headers"]));        
                $questiondata['extradata'] = $question["headers"];
                $questiondata['precise'] = $question["precise"];
                $opt = explode("@@", $question["options"]);
                $generator->create_question($questionnaire, $questiondata, $opt);
            }
            if($nr == $qperpage){
                $generator->create_question(
                    $questionnaire, 
                    [
                        'surveyid' => $questionnaire->sid,
                        'name' => 'pagebreak '.$key,
                        'type_id' => QUESPAGEBREAK
                    ]);
                $nr = 0;
            }
            $nr++;
        }
        $result = array();
        $result['moduleid'] = $questionnaire->sid;
        return $result;
    }   

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.0
     */
    public static function create_questionnaire_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'courseid instance id'),
                'sectionid' => new external_value(PARAM_INT, 'section id'),
                'quizintro' => new external_value(PARAM_RAW, 'courseid instance id'),
                'quizname' => new external_value(PARAM_RAW, 'section id'),
                'qperpage' => new external_value(PARAM_INT, 'courseid instance id'),
                'opendate' => new external_value(PARAM_INT, 'section id'),
                'closedate' => new external_value(PARAM_INT, 'section id'),
                'questions' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'name' => new external_value(PARAM_RAW, 'full name'),
                            'tipo' => new external_value(PARAM_INT, 'full name'),
                            'description' => new external_value(PARAM_RAW, 'course short name'),
                            'position' => new external_value(PARAM_RAW, 'course short name'),
                            'headers' => new external_value(PARAM_TEXT, 'full name', VALUE_OPTIONAL),
                            'required' => new external_value(PARAM_TEXT, 'full name',VALUE_OPTIONAL),
                            'precise' => new external_value(PARAM_TEXT, 'full name',VALUE_OPTIONAL),
                            'single' => new external_value(PARAM_TEXT, 'full name',VALUE_OPTIONAL),
                            'options' => new external_value(PARAM_TEXT, 'course short name', VALUE_OPTIONAL)
                        )
                    )
                )
            )
        );
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function create_questionnaire_returns() {
        return new external_single_structure(
                array(
                    'moduleid' => new external_value(PARAM_INT, 'moduleid of the resource'),
                ));
    }

    function create_quiz($courseid, $sectionid, $quiz_intro, $quiz_name, $attempts, $qperpage, $timeopen, $timeclose, 
                             $duration_hours, $duration_min, $israndom, $maxquestions,
                             $restricted_by_activity_id, $pages, $questions) {
        $generator = new testing_data_generator();
        $generator = $generator->get_plugin_generator('mod_quiz');
        $available = "";
        //if($restricted_by_activity_id != null)
        //$available = '{"op":"&","c":[{"type":"completion","cm":' . $restricted_by_activity_id. ',"e":1}],"showc":[true]}';

        $cm = self::add_quiz_coursemodule($courseid, $sectionid, $quiz_name, $quiz_intro, $qperpage, $attempts, 
                                            $timeopen, $timeclose, $duration_hours, $duration_min, $restricted_by_activity_id);
        $quiz = new stdClass();
        $quiz->id = $cm->instance;
        $quiz->questionsperpage = $cm->questionsperpage;
        $quiz->coursemodule = $cm->coursemodule;
        $questiontext = "";
        $page = 0;
        $pages = explode("@@", $pages);
        foreach($questions as $key => $question){
            $questiontype = "";
            $questiontext = $question["name"];
            switch($question["tipo"]){
                case 3:
                    $questiontype = "multichoice";
                    break;
                case 4:
                    $questiontype = "multichoice"; //dropdown
                    $questiontext = $question["name"] + " {1:MCS:" + $question["answers"] + "}";
                    break;
                case 5:
                    $questiontype = "shortanswer";
                    break;
            }   
            $question_module = self::create_question($cm->coursemodule, $question["name"], $questiontype, $questiontext);
            if($question["tipo"] == 3){
                self::create_question_options($question_module, $question["single"]);
                $answers = explode("@@", $question["answers"]);
                for($i=0; $i<sizeof($answers); $i++){
                    $answer = $answers[$i];
                    $ans_name = explode("%%", $answer)[0];
                    $ans_fraction = explode("%%", $answer)[1];
                    self::create_question_answers($question_module, $ans_name, $ans_fraction);
                } 
            }
            // associate the newly created question to the quiz  
            if($israndom != 1){
                self::quiz_add_question($question_module->id, $quiz);
            }                              
            if($key % $qperpage == 0){  
                if(sizeof($pages) > 0 && $pages[$page] != null  && $pages[$page] != "")   
                    self::create_quiz_section($quiz->id, $pages[$page], $key+1);
                $page++;
            }
        }
        // if the quiz takes random questions
        if($israndom == 1){
            self::quiz_add_question_random_module_qbank($quiz, $maxquestions);
        }
        $resp = array("moduleid" => $quiz->id);
        return $resp;
    }
    
    public static function add_quiz_coursemodule($courseid, $sectionid, $quizname, $intro, $questionsperpage, $attempts, $timeopen, $timeclose, $duration_hours, $duration_min, $restricted_by_activity_id){
        global $DB;
        $course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);
        $quiz = new stdClass();
        $quiz->course    = $courseid;
        $quiz->name      = $quizname;
        $quiz->module = 17;
        $quiz->modulename = 'quiz';
        $quiz->section = $sectionid;
        $quiz->visible = 1;
        $quiz->quizpassword = "";
        $quiz->intro = $intro;
        $quiz->introformat = 1;
        $quiz->attempts = $attempts;
        $quiz->timeopen = $timeopen;
        $quiz->timeclose = $timeclose;
        $quiz->completion = 2;
        $quiz->completionminattemptsenabled = 1;
        $quiz->completionminattempts = $attempts;
        $quiz->questionsperpage = $questionsperpage;
        $quiz->preferredbehaviour = "deferredfeedback";
        $quiz->customfield_duration_hours = $duration_hours;
        $quiz->customfield_duration_mins = $duration_min;
        $quiz->attemptduring = 1;
        $quiz->attemptimmediately = 1;
        $quiz->correctnessimmediately = 1;
        $quiz->marksimmediately = 1;
        $quiz->specificfeedbackimmediately = 1;
        $quiz->generalfeedbackimmediately = 1;
        $quiz->rightanswerimmediately = 1;
        $quiz->overallfeedbackimmediately = 1;
        $quiz->attemptopen = 1;
        $quiz->correctnessopen = 1;
        $quiz->marksopen = 1;
        $quiz->specificfeedbackopen = 1;
        $quiz->generalfeedbackopen = 1;
        $quiz->rightansweropen = 1;
        $quiz->overallfeedbackopen = 1;
        $moduleinfo = add_moduleinfo($quiz, $course);
        return $moduleinfo;
    }

    public static function create_question($quizid, $qtext, $questiontype, $questiontext=0){
        global $DB,$CFG;
        $context = context_module::instance($quizid);
        // Create a question in the default category.
        $contexts = new question_edit_contexts($context);
        $cat = question_make_default_categories($contexts->all());

        $contextid = context_module::instance($quizid);
        $cat = question_get_default_category($contextid->id);
        $question = new stdClass();
        $question->questiontext = $qtext;
        $question->generalfeedback = "";
        $question->name = substr($qtext, 0, 40);
        $question->category = $cat->id;
        $question->timecreated = time();
        $question->timemodified = time();
        $question->qtype = $questiontype;
        $question->questiontext = $questiontext;
        $question->questiontextformat = 1;
        $question->createdby = 2;
        $question->modifiedby = 2;
        $question->id = $DB->insert_record('question', $question);
        return $question;
    }
    
    public static function create_question_answers($question, $answer_name, $fraction) {
        global $DB;
        $answer = new stdClass();
        $answer->question = $question->id;
        $answer->answer = $answer_name;
        $answer->feedback = '';
        $answer->fraction = $fraction;
        $answer->answerformat = 1;
        $answer->feedbackformat = 1;
        $answer->id = $DB->insert_record('question_answers', $answer);  
    }
    
    public static function create_quiz_section($quizid, $heading, $slot) {
        global $DB;
        if($slot==1){
            $section = $DB->get_record('quiz_sections', array('quizid' => $quizid), '*', MUST_EXIST);
            $section->heading = $heading;
            $DB->update_record('quiz_sections', $section);
        }else{
            $answer = new stdClass();
            $answer->quizid = $quizid;
            $answer->heading = $heading;
            $answer->firstslot = $slot;
            $answer->id = $DB->insert_record('quiz_sections', $answer); 
        }
    }
    
    public static function create_question_options($question, $single) {
        global $DB;
        // Create a default question options record.
        $options = new stdClass();
        $options->questionid = $question->id;
    
        // Get the default strings and just set the format.
        $options->correctfeedback = get_string('correctfeedbackdefault', 'question');
        $options->correctfeedbackformat = FORMAT_HTML;
        $options->partiallycorrectfeedback = get_string('partiallycorrectfeedbackdefault', 'question');;
        $options->partiallycorrectfeedbackformat = FORMAT_HTML;
        $options->incorrectfeedback = get_string('incorrectfeedbackdefault', 'question');
        $options->incorrectfeedbackformat = FORMAT_HTML;
    
        $config = get_config('qtype_multichoice');
        $options->single = $single;
        if (isset($question->layout)) {
            $options->layout = $question->layout;
        }
        $options->answernumbering = $config->answernumbering;
        $options->shuffleanswers = $config->shuffleanswers;
        $options->showstandardinstruction = 0;
        $options->shownumcorrect = 1;
        $DB->insert_record('qtype_multichoice_options', $options);
    
        return $options;
    }
    
    public static function quiz_add_question($questionid, $quiz, $page=0){
        quiz_add_quiz_question($questionid, $quiz, $page);
    }
    
    function quiz_add_question_random_course_qbank($courseid, $quiz, $number,  $page=0){
        $contextid = context_course::instance($courseid);
        $cat = question_get_default_category($contextid->id);
        quiz_add_random_questions($quiz, $page, $cat->id, $number, false);
        
    }
    
    public static function quiz_add_question_random_module_qbank($quiz, $number,  $page=0){    
        $contextid = context_module::instance($quiz->coursemodule);
        $cat = question_get_default_category($contextid->id);
        quiz_add_random_questions($quiz, $page, $cat->id, $number, false);
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.0
     */
    public static function create_quiz_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'courseid instance id'),
                'sectionid' => new external_value(PARAM_INT, 'section id'),
                'quizintro' => new external_value(PARAM_RAW, 'courseid instance id'),
                'quizname' => new external_value(PARAM_RAW, 'section id'),
                'attempts' => new external_value(PARAM_INT, 'section id'),
                'qperpage' => new external_value(PARAM_INT, 'courseid instance id'),
                'timeopen' => new external_value(PARAM_INT, 'section id'),
                'timeclose' => new external_value(PARAM_INT, 'section id'),
                'duration_hours' => new external_value(PARAM_INT, 'section id'),
                'duration_min' => new external_value(PARAM_INT, 'section id'),
                'israndom' => new external_value(PARAM_INT, 'section id'),
                'maxquestions' => new external_value(PARAM_INT, 'section id'),
                'restricted_by_activity_id' => new external_value(PARAM_INT, 'section id', VALUE_OPTIONAL, true),
                'pages' => new external_value(PARAM_RAW, 'pages'),
                'questions' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'name' => new external_value(PARAM_RAW, 'full name'),
                            'tipo' => new external_value(PARAM_INT, 'full name'),
                            'single' => new external_value(PARAM_INT, 'course short name', VALUE_OPTIONAL),
                            'answers' => new external_value(PARAM_RAW, 'answers', VALUE_OPTIONAL)
                        )
                    )
                )
            )
        );
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function create_quiz_returns() {
        return new external_single_structure(
                array(
                    'moduleid' => new external_value(PARAM_INT, 'moduleid of the resource'),
                ));
    }

    /**
     * Create progress block inside course
     * Add activities with non empty weights inside it
     */
    function create_progress_block($courseid){
        global $DB;       
        $config = array();
        $courseactivities = $DB->get_records_sql("
            SELECT distinct(cm.instance), m.name
            FROM {customfield_data} cf
            INNER JOIN {course_modules} cm ON cm.id=cf.instanceid
            INNER JOIN {modules} m ON m.id=cm.module
            WHERE
                cm.course=? and cf.intvalue>0", array($courseid));
        foreach($courseactivities as $activity){
            $module = $activity->name;
            $newinstanceid = $activity->instance;
            $config["monitor_$module$newinstanceid"] = 1;
            $date = "2022-12-31 17:00";
            $config["date_time_$module$newinstanceid"] = strtotime($date);
            $config["action_$module$newinstanceid"] = "activity_completion";
        }
        $config["progressTitle"] = "Barra di avanzamento";
        $config["progressBarIcons"] = 0;
        $config["showpercentage"] = 1;
        $configdata = base64_encode(serialize((object)$config));    
    
        $context = context_course::instance($courseid);
        $blockinstance = new stdClass;
        $blockinstance->blockname = "progress";
        $blockinstance->parentcontextid = $context->id;
        $blockinstance->showinsubcontexts = false;
        $blockinstance->defaultregion = "content";
        $blockinstance->pagetypepattern = "course-view-*";
        $blockinstance->defaultweight = 0;
        $blockinstance->configdata = $configdata;
        $blockinstance->timecreated = time();
        $blockinstance->timemodified = $blockinstance->timecreated;
        $blockinstance->id = $DB->insert_record('block_instances', $blockinstance);
    
        $blockinstance = new stdClass;
        $blockinstance->blockname = "activity_modules";
        $blockinstance->parentcontextid = $context->id;
        $blockinstance->showinsubcontexts = false;
        $blockinstance->defaultregion = "content";
        $blockinstance->pagetypepattern = "course-view-*";
        $blockinstance->defaultweight = 0;
        $blockinstance->timecreated = time();
        $blockinstance->timemodified = $blockinstance->timecreated;
        $blockinstance->id = $DB->insert_record('block_instances', $blockinstance);
        $ret["blockid"] = $blockinstance->id;
        return $ret;
    }
    
    public static function create_progress_block_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'courseid instance id'),

            )
        );
    }

    public static function create_progress_block_returns() {
        return new external_single_structure(
                array(
                    'blockid' => new external_value(PARAM_INT, 'blockid of the resource'),
                ));
    }

    /**
     * Update activity dependency - condition of availability
     */
    function update_activity_dependency($sourceactivity, $sourcemodule, $targetactivity, $targetmodule){
        global $DB;  
        $cm_source = get_coursemodule_from_instance($sourcemodule, $sourceactivity);
        $cm_target = get_coursemodule_from_instance($targetmodule, $targetactivity);
        $availability = '{"op":"&","c":[{"type":"completion","cm":' . $cm_source->id . ',"e":1}],"showc":[true]}';
        $DB->execute("
            UPDATE {course_modules}
            SET availability = ? 
            WHERE
                id=?", array($availability, $cm_target->id));   
        rebuild_course_cache($cm_source->course, true); 
        $ret["sourceid"] = $sourceactivity;
        return $ret;
    }
    
    public static function update_activity_dependency_parameters() {
        return new external_function_parameters(
            array(
                'sourceactivity' => new external_value(PARAM_INT, 'courseid instance id'),
                'sourcemodule' => new external_value(PARAM_TEXT, 'courseid instance id'),
                'targetactivity' => new external_value(PARAM_INT, 'courseid instance id'),
                'targetmodule' => new external_value(PARAM_TEXT, 'courseid instance id')
            )
        );
    }

    public static function update_activity_dependency_returns() {
        return new external_single_structure(
                array(
                    'sourceid' => new external_value(PARAM_INT, 'id of the source'),
                ));
    }
}
