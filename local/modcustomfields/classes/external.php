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

use mod_questionnaire\generator\question_response,
    mod_questionnaire\generator\question_response_rank,
    mod_questionnaire\question\question;

global $CFG;
require_once($CFG->dirroot.'/mod/questionnaire/locallib.php');
require_once("$CFG->libdir/externallib.php");
require_once($CFG->dirroot .'/course/lib.php');
require_once($CFG->libdir .'/filelib.php');
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->dirroot.'/mod/questionnaire/locallib.php');
require_once($CFG->dirroot . '/lib/testing/generator/lib.php');
require_once($CFG->dirroot . '/lib/phpunit/classes/util.php');
require_once($CFG->dirroot .'/mod/quiz/locallib.php');
require_once($CFG->dirroot .'/mod/scorm/locallib.php');
require_once($CFG->dirroot .'/mod/wiki/locallib.php');
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
    public static function add_resource($courseid, $sectionid, $resourcename, $path, $duration_hours, $duration_min, $intro, $stealth) {
        global $DB, $CFG;
        $module = self::add_resource_coursemodule($courseid, $sectionid, $resourcename, $duration_hours, $duration_min, $intro, $stealth);
        //self::create_file_from_pathname($module->coursemodule, 'mod_resource', 0, $path, "content");
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
                'duration_hours' => new external_value(PARAM_INT, 'restricted_by_activity_id') ,
                'duration_min' => new external_value(PARAM_INT, 'restricted_by_activity_id'),
                'intro' => new external_value(PARAM_TEXT, 'description '),
                'stealth' => new external_value(PARAM_INT, 'stealth mode ')
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

    public static  function create_file_from_pathname($coursemodule, $modulename, $itemid, $pathname, $filearea){
        if (empty($pathname))
            return;
        $fs = get_file_storage();
        $context = context_module::instance($coursemodule);
    
        // Prepare file record object
        $fileinfo = array(
            'contextid' => $context->id, 
            'component' => $modulename,     
            'filearea' => $filearea,    
            'itemid' => $itemid,               
            'filepath' => '/',          
            'filename' =>  basename($pathname));    
        return $fs->create_file_from_pathname($fileinfo, $pathname);
    }

    public static  function add_resource_coursemodule($courseid, $sectionid, $name, $duration_hours, $duration_min, $intro, $stealth=0){
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
        if($stealth == 1){
            $quiz->visibleoncoursepage = 0;
        }
        
        $moduleinfo = add_moduleinfo($quiz, $course);
        return $moduleinfo;
    }

    function create_questionnaire($courseid, $sectionid, $quizintro, $quizname, $qperpage, $opendate, $closedate, $questions, $stealth=0) {
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
                  'completionsubmit' => 1,
                  'completionscoredisabled' => 1,
                  'completionstatusrequired' => 4,
                  'closedate' => $closedate,
                  'opendate' => $opendate,
                  'visibleoncoursepage' => $stealth == 1 ? 0 : 1
                ));
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
                $questiondata['length'] = $question["max_responses"];
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
                            'max_responses' => new external_value(PARAM_INT, 'courseid instance id', VALUE_OPTIONAL),
                            'options' => new external_value(PARAM_RAW, 'course short name', VALUE_OPTIONAL)
                        )
                    )
                ),
                'stealth' => new external_value(PARAM_INT, 'full name', VALUE_OPTIONAL)
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
                             $restricted_by_activity_id, $pages, $stealth, $questions) {
        $generator = new testing_data_generator();
        $generator = $generator->get_plugin_generator('mod_quiz');
        $cm = self::add_quiz_coursemodule($courseid, $sectionid, $quiz_name, $quiz_intro, $qperpage, $attempts, 
                                            $timeopen, $timeclose, $duration_hours, $duration_min, $restricted_by_activity_id, $stealth);
        $quiz = new stdClass();
        $quiz->id = $cm->instance;
        $quiz->questionsperpage = $cm->questionsperpage;
        $quiz->coursemodule = $cm->coursemodule;
        $questiontext = "";
        $page = 0;
        $pages = explode("@@", $pages);
        foreach($questions as $key => $question){
            $questiontype = "";
            $questiontext = $question["text"];
            switch($question["tipo"]){
                case 3:
                    $questiontype = "multichoice";
                    break;
                case 4:
                    $questiontype = "multichoice"; //dropdown
                    $questiontext = $question["text"] + " {1:MCS:" + $question["answers"] + "}";
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
                    $ans_name = explode("$$", $answer)[0];
                    $ans_fraction = explode("$$", $answer)[1];
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
    
    public static function add_quiz_coursemodule($courseid, $sectionid, $quizname, $intro, $questionsperpage, $attempts, 
                                    $timeopen, $timeclose, $duration_hours, $duration_min, $restricted_by_activity_id, $stealth){
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
        if($stealth == 1){
            $quiz->visibleoncoursepage = 0;
        }
        $moduleinfo = add_moduleinfo($quiz, $course);
        return $moduleinfo;
    }

    public static function create_question($quizid, $qname, $questiontype, $questiontext=0){
        global $DB,$CFG;
        $context = context_module::instance($quizid);
        // Create a question in the default category.
        $contexts = new question_edit_contexts($context); //core_question\local\bank\question_edit_contexts($context); // question_edit_contexts($context);
        $cat = question_make_default_categories($contexts->all());

        $contextid = context_module::instance($quizid);
        $cat = question_get_default_category($contextid->id);
        $qname =  htmlentities(html_entity_decode($qname, ENT_QUOTES, 'UTF-8'));
        $questiontext =  html_entity_decode($questiontext, ENT_QUOTES, 'UTF-8');
        $question = new stdClass();
        $question->generalfeedback = "";
        $question->name = strlen($qname) > 0 ? substr($qname, 0, 40) : " - ";
        $question->category = $cat->id;
        $question->timecreated = time();
        $question->timemodified = time();
        $question->qtype = $questiontype;
        $question->questiontext = strlen($questiontext) > 0 ? $questiontext : " - ";
        $question->questiontextformat = 1;
        $question->createdby = 2;
        $question->modifiedby = 2;
        $question->id = $DB->insert_record('question', $question);
        // Create a bank entry for each question imported.
        $question = $DB->get_record('question', array('id' => $question->id));
        $questionbankentry = new \stdClass();
        $questionbankentry->questioncategoryid = $cat->id;
        $questionbankentry->idnumber = $question->idnumber ?? null;
        $questionbankentry->ownerid = $question->createdby;
        $questionbankentry->id = $DB->insert_record('question_bank_entries', $questionbankentry);
        // Create a version for each question imported.
        $questionversion = new \stdClass();
        $questionversion->questionbankentryid = $questionbankentry->id;
        $questionversion->questionid = $question->id;
        $questionversion->version = 1;
        $questionversion->status = \core_question\local\bank\question_version_status::QUESTION_STATUS_READY;
        $questionversion->id = $DB->insert_record('question_versions', $questionversion);
        
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
        $DB->insert_record('question_answers', $answer);  
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
        global $DB;
        quiz_add_quiz_question($questionid, $quiz, $page);
        $question = $DB->get_record('question', array('id' => $questionid));
        $questionbankentry = new \stdClass();
        $questionbankentry->questioncategoryid = $question->category;
        $questionbankentry->idnumber = $question->idnumber ?? null;
        $questionbankentry->ownerid = $question->createdby;
        $questionbankentry->id = $DB->insert_record('question_bank_entries', $questionbankentry);

        
    }

    
    function quiz_add_quiz_question($questionid, $quiz, $page = 0, $maxmark = null) {
        global $DB;
    
        // Make sue the question is not of the "random" type.
        $questiontype = $DB->get_field('question', 'qtype', array('id' => $questionid));
        if ($questiontype == 'random') {
            throw new coding_exception(
                    'Adding "random" questions via quiz_add_quiz_question() is deprecated. Please use quiz_add_random_questions().'
            );
        }
    
        $trans = $DB->start_delegated_transaction();
        $slots = $DB->get_records('quiz_slots', array('quizid' => $quiz->id),
                'slot', 'questionid, slot, page, id');
        if (array_key_exists($questionid, $slots)) {
            $trans->allow_commit();
            return false;
        }
    
        $maxpage = 1;
        $numonlastpage = 0;
        foreach ($slots as $slot) {
            if ($slot->page > $maxpage) {
                $maxpage = $slot->page;
                $numonlastpage = 1;
            } else {
                $numonlastpage += 1;
            }
        }
    
        // Add the new question instance.
        $slot = new stdClass();
        $slot->quizid = $quiz->id;
        $slot->questionid = $questionid;
    
        if ($maxmark !== null) {
            $slot->maxmark = $maxmark;
        } else {
            $slot->maxmark = $DB->get_field('question', 'defaultmark', array('id' => $questionid));
        }
    
        if (is_int($page) && $page >= 1) {
            // Adding on a given page.
            $lastslotbefore = 0;
            foreach (array_reverse($slots) as $otherslot) {
                if ($otherslot->page > $page) {
                    $DB->set_field('quiz_slots', 'slot', $otherslot->slot + 1, array('id' => $otherslot->id));
                } else {
                    $lastslotbefore = $otherslot->slot;
                    break;
                }
            }
            $slot->slot = $lastslotbefore + 1;
            $slot->page = min($page, $maxpage + 1);
    
            quiz_update_section_firstslots($quiz->id, 1, max($lastslotbefore, 1));
    
        } else {
            $lastslot = end($slots);
            if ($lastslot) {
                $slot->slot = $lastslot->slot + 1;
            } else {
                $slot->slot = 1;
            }
            if ($quiz->questionsperpage && $numonlastpage >= $quiz->questionsperpage) {
                $slot->page = $maxpage + 1;
            } else {
                $slot->page = $maxpage;
            }
        }
    
        $DB->insert_record('quiz_slots', $slot);

        $question->id = $DB->insert_record('question', $question);
        // Create a bank entry for each question imported.
        $questionbankentry = new \stdClass();
        $questionbankentry->questioncategoryid = $question->category;
        $questionbankentry->idnumber = $question->idnumber ?? null;
        $questionbankentry->ownerid = $question->createdby;
        $questionbankentry->id = $DB->insert_record('question_bank_entries', $questionbankentry);
        // Create a version for each question imported.
        $questionversion = new \stdClass();
        $questionversion->questionbankentryid = $questionbankentry->id;
        $questionversion->questionid = $question->id;
        $questionversion->version = 1;
        $questionversion->status = \core_question\local\bank\question_version_status::QUESTION_STATUS_READY;
        $questionversion->id = $DB->insert_record('question_versions', $questionversion);

        $trans->allow_commit();
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
                'restricted_by_activity_id' => new external_value(PARAM_INT, 'section id'),
                'pages' => new external_value(PARAM_RAW, 'pages'),
                'stealth' => new external_value(PARAM_INT, 'section id'),
                'questions' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'name' => new external_value(PARAM_RAW, 'full name'),
                            'text' => new external_value(PARAM_RAW, 'full name'),
                            'tipo' => new external_value(PARAM_INT, 'full name'),
                            'single' => new external_value(PARAM_INT, 'course short name'),
                            'answers' => new external_value(PARAM_RAW, 'answers')
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
        if(sizeof($courseactivities) > 0){            
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
        }        
    
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

        /**
     * Describes the parameters for insert_scorm_tracks.
     *
     * @return external_function_parameters
     * @since Moodle 3.0
     */
    public static function insert_scorm_tracks_parameters() {
        return new external_function_parameters(
            array(
                'scoid' => new external_value(PARAM_INT, 'SCO id'),
                'attempt' => new external_value(PARAM_INT, 'attempt number'),
                'userid' => new external_value(PARAM_INT, 'userid'),
                'time' => new external_value(PARAM_INT, 'time'),
                'tracks' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'element' => new external_value(PARAM_RAW, 'element name'),
                            'value' => new external_value(PARAM_RAW, 'element value')
                        )
                    )
                ),
            )
        );
    }

    /**
     * Saves a SCORM tracking record.
     * It will overwrite any existing tracking data for this attempt.
     * Validation should be performed before running the function to ensure the user will not lose any existing attempt data.
     *
     * @param int $scoid the SCO id
     * @param string $attempt the attempt number
     * @param array $tracks the track records to be stored
     * @return array warnings and the scoes data
     * @throws moodle_exception
     * @since Moodle 3.0
     */
    public static function insert_scorm_tracks($scoid, $attempt, $userid, $time, $tracks) {
        global $USER, $DB;

        $params = self::validate_parameters(self::insert_scorm_tracks_parameters(),
                                            array('scoid' => $scoid, 'attempt' => $attempt, 
                                            'userid' => $userid, 'time' => $time, 'tracks' => $tracks));

        $trackids = array();
        $warnings = array();

        $sco = scorm_get_sco($params['scoid'], SCO_ONLY);
        if (!$sco) {
            throw new moodle_exception('cannotfindsco', 'scorm');
        }

        $scorm = $DB->get_record('scorm', array('id' => $sco->scorm), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('scorm', $scorm->id);

        $context = context_module::instance($cm->id);
        self::validate_context($context);

        // Check settings / permissions to view the SCORM.
        require_capability('mod/scorm:savetrack', $context);

        // Check settings / permissions to view the SCORM.
        scorm_require_available($scorm);

        foreach ($params['tracks'] as $track) {
            $element = $track['element'];
            $value = $track['value'];
            $trackid = self::scorm_insert_track($userid, $scorm->id, $sco->id, $time, $params['attempt'], $element, $value,
                                            $scorm->forcecompleted);

            if ($trackid) {
                $trackids[] = $trackid;
            } else {
                $warnings[] = array(
                    'item' => 'scorm',
                    'itemid' => $scorm->id,
                    'warningcode' => 1,
                    'message' => 'Element: ' . $element . ' was not saved'
                );
            }
        }

        $result = array();
        $result['trackids'] = $trackids;
        $result['warnings'] = $warnings;
        return $result;
    }

    /**
     * Describes the insert_scorm_tracks return value.
     *
     * @return external_single_structure
     * @since Moodle 3.0
     */
    public static function insert_scorm_tracks_returns() {

        return new external_single_structure(
            array(
                'trackids' => new external_multiple_structure(new external_value(PARAM_INT, 'track id')),
                'warnings' => new external_warnings(),
            )
        );
    }

    static function scorm_insert_track($userid, $scormid, $scoid, $time, $attempt, $element, $value, $forcecompleted=false, $trackdata = null) {
        global $DB, $CFG;
    
        $id = null;
    
        if ($forcecompleted) {
            // TODO - this could be broadened to encompass SCORM 2004 in future.
            if (($element == 'cmi.core.lesson_status') && ($value == 'incomplete')) {
                if ($track = $DB->get_record_select('scorm_scoes_track',
                                                    'userid=? AND scormid=? AND scoid=? AND attempt=? '.
                                                    'AND element=\'cmi.core.score.raw\'',
                                                    array($userid, $scormid, $scoid, $attempt))) {
                    $value = 'completed';
                }
            }
            if ($element == 'cmi.core.score.raw') {
                if ($tracktest = $DB->get_record_select('scorm_scoes_track',
                                                        'userid=? AND scormid=? AND scoid=? AND attempt=? '.
                                                        'AND element=\'cmi.core.lesson_status\'',
                                                        array($userid, $scormid, $scoid, $attempt))) {
                    if ($tracktest->value == "incomplete") {
                        $tracktest->value = "completed";
                        $DB->update_record('scorm_scoes_track', $tracktest);
                    }
                }
            }
            if (($element == 'cmi.success_status') && ($value == 'passed' || $value == 'failed')) {
                if ($DB->get_record('scorm_scoes_data', array('scoid' => $scoid, 'name' => 'objectivesetbycontent'))) {
                    $objectiveprogressstatus = true;
                    $objectivesatisfiedstatus = false;
                    if ($value == 'passed') {
                        $objectivesatisfiedstatus = true;
                    }
    
                    if ($track = $DB->get_record('scorm_scoes_track', array('userid' => $userid,
                                                                            'scormid' => $scormid,
                                                                            'scoid' => $scoid,
                                                                            'attempt' => $attempt,
                                                                            'element' => 'objectiveprogressstatus'))) {
                        $track->value = $objectiveprogressstatus;
                        $track->timemodified = $time;
                        $DB->update_record('scorm_scoes_track', $track);
                        $id = $track->id;
                    } else {
                        $track = new stdClass();
                        $track->userid = $userid;
                        $track->scormid = $scormid;
                        $track->scoid = $scoid;
                        $track->attempt = $attempt;
                        $track->element = 'objectiveprogressstatus';
                        $track->value = $objectiveprogressstatus;
                        $track->timemodified = $time;
                        $id = $DB->insert_record('scorm_scoes_track', $track);
                    }
                    if ($objectivesatisfiedstatus) {
                        if ($track = $DB->get_record('scorm_scoes_track', array('userid' => $userid,
                                                                                'scormid' => $scormid,
                                                                                'scoid' => $scoid,
                                                                                'attempt' => $attempt,
                                                                                'element' => 'objectivesatisfiedstatus'))) {
                            $track->value = $objectivesatisfiedstatus;
                            $track->timemodified = $time;
                            $DB->update_record('scorm_scoes_track', $track);
                            $id = $track->id;
                        } else {
                            $track = new stdClass();
                            $track->userid = $userid;
                            $track->scormid = $scormid;
                            $track->scoid = $scoid;
                            $track->attempt = $attempt;
                            $track->element = 'objectivesatisfiedstatus';
                            $track->value = $objectivesatisfiedstatus;
                            $track->timemodified = $time;
                            $id = $DB->insert_record('scorm_scoes_track', $track);
                        }
                    }
                }
            }
    
        }
    
        $track = null;
        if ($trackdata !== null) {
            if (isset($trackdata[$element])) {
                $track = $trackdata[$element];
            }
        } else {
            $track = $DB->get_record('scorm_scoes_track', array('userid' => $userid,
                                                                'scormid' => $scormid,
                                                                'scoid' => $scoid,
                                                                'attempt' => $attempt,
                                                                'element' => $element));
        }
        if ($track) {
            if ($element != 'x.start.time' ) { // Don't update x.start.time - keep the original value.
                if ($track->value != $value) {
                    $track->value = $value;
                    $track->timemodified = $time;
                    $DB->update_record('scorm_scoes_track', $track);
                }
                $id = $track->id;
            }
        } else {
            $track = new stdClass();
            $track->userid = $userid;
            $track->scormid = $scormid;
            $track->scoid = $scoid;
            $track->attempt = $attempt;
            $track->element = $element;
            $track->value = $value;
            $track->timemodified = $time;
            $id = $DB->insert_record('scorm_scoes_track', $track);
            $track->id = $id;
        }
    
        // Trigger updating grades based on a given set of SCORM CMI elements.
        $scorm = false;
        if (in_array($element, array('cmi.core.score.raw', 'cmi.score.raw')) ||
            (in_array($element, array('cmi.completion_status', 'cmi.core.lesson_status', 'cmi.success_status'))
             && in_array($track->value, array('completed', 'passed')))) {
            $scorm = $DB->get_record('scorm', array('id' => $scormid));
            include_once($CFG->dirroot.'/mod/scorm/lib.php');
            scorm_update_grades($scorm, $userid);
        }
    
        // Trigger CMI element events.
        if (in_array($element, array('cmi.core.score.raw', 'cmi.score.raw')) ||
            (in_array($element, array('cmi.completion_status', 'cmi.core.lesson_status', 'cmi.success_status'))
            && in_array($track->value, array('completed', 'failed', 'passed')))) {
            if (!$scorm) {
                $scorm = $DB->get_record('scorm', array('id' => $scormid));
            }
            $cm = get_coursemodule_from_instance('scorm', $scormid);
            $data = array(
                'other' => array('attemptid' => $attempt, 'cmielement' => $element, 'cmivalue' => $track->value),
                'objectid' => $scorm->id,
                'context' => context_module::instance($cm->id),
                'relateduserid' => $userid
            );
            if (in_array($element, array('cmi.core.score.raw', 'cmi.score.raw'))) {
                // Create score submitted event.
                $event = \mod_scorm\event\scoreraw_submitted::create($data);
            } else {
                // Create status submitted event.
                $event = \mod_scorm\event\status_submitted::create($data);
            }
            // Fix the missing track keys when the SCORM track record already exists, see $trackdata in datamodel.php.
            // There, for performances reasons, columns are limited to: element, id, value, timemodified.
            // Missing fields are: userid, scormid, scoid, attempt.
            $track->userid = $userid;
            $track->scormid = $scormid;
            $track->scoid = $scoid;
            $track->attempt = $attempt;
            // Trigger submitted event.
            $event->add_record_snapshot('scorm_scoes_track', $track);
            $event->add_record_snapshot('course_modules', $cm);
            $event->add_record_snapshot('scorm', $scorm);
            $event->trigger();
        }
    
        return $id;
    }

    function tag_course($courseid, $tagid){
        global $DB; 
        $contextid = context_course::instance($courseid);
        $track = new stdClass();
        $track->tagid = $tagid;
        $track->component = 'core';
        $track->itemtype = 'course';
        $track->itemid = $courseid;
        $track->contextid = $contextid->id;
        $track->timecreated = time();
        $track->timemodified = time();
        $DB->insert_record('tag_instance', $track);
        $ret["id"] = $courseid;
        return $ret;
    }
    
    public static function tag_course_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'courseid instance id'),
                'tagid' => new external_value(PARAM_TEXT, 'courseid instance id')
            )
        );
    }

    public static function tag_course_returns() {
        return new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'id of the source'),
                ));
    }

    function add_wiki($courseid, $name, $desc, $content, $userid){
        global $DB;
        $course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);
        $wiki = new stdClass();
        $wiki->course    = $courseid;
        $wiki->name      = $name;
        $wiki->section      = 0;
        $wiki->module   = 22;
        $wiki->modulename = 'wiki';
        $wiki->visible = 1;
        $wiki->intro = $desc;
        $wiki->wikimode = "collaborative";
        $wiki->firstpagetitle = $name;
        $wiki->defaultformat = "html";
        $wiki->forceformat = 0;
        $moduleinfo = add_moduleinfo($wiki, $course);
        $wiki->id = $moduleinfo->instance;
        $record = array(
            'title' => $name,
            'wikiid' => $wiki->id,
            'subwikiid' => 0,
            'group' => null,
            'userid' => null,
            'content' => $content,
            'format' => $wiki->defaultformat
        );    
        self::create_page($wiki, $record, $userid);
        $ret = array("id" => $wiki->id);
        return $ret;
    }
    
    function add_subwiki($courseid, $wikiid, $title, $userid, $time=0, $content){
        $wiki = new stdClass();
        $wiki->id = $wikiid;
        $record = array(
            'title' => $title,
            'wikiid' => $wiki->id,
            'subwikiid' => 0,
            'group' => null,
            'userid' => null,
            'content' => $content,
            'format' => "html"
        );  
        
        $subwikiid = self::create_page($wiki, $record, $userid, $time);
        /*$cm = get_coursemodule_from_instance('wiki', $wiki->id, $courseid);
        $my_files = explode(";", $files);
        foreach($my_files as $file){
            self::create_file_from_pathname($cm->id, 'mod_wiki', $subwikiid, $file, 'attachments');

        } */
        return array("id" => $subwikiid, "title" => $title);  
    }

    function add_sub_wiki_history($wikiid, $subwikiid, $title, $history=[]){
        $wiki = new stdClass();
        $wiki->id = $wikiid;
        $record = array(
            'wikiid' => $wiki->id,
            'subwikiid' => $subwikiid,
            'title' => $title,
            'group' => null,
            'userid' => null,
            'format' => "html"
        );  
        $record['subwikiid'] = self::get_subwiki($wiki, $record['subwikiid'], $record['group'], $record['userid']);
        $wikipage = wiki_get_page_by_title($record['subwikiid'], $record['title']);
        foreach($history as $version){
            self::wiki_save_page($wikipage, $version['content'], $version['userid'], $version['time']);
        }
        return array("id" => $subwikiid);  
    }
    
    static function create_page($wiki, $record, $userid, $time=0) {
        $record['subwikiid'] = self::get_subwiki($wiki, $record['subwikiid'], $record['group'], $record['userid']);
        $wikipage = wiki_get_page_by_title($record['subwikiid'], $record['title']);
        if (!$wikipage) {
            $pageid = wiki_create_page($record['subwikiid'], $record['title'], $record['format'], $userid);
            $wikipage = wiki_get_page($pageid);
        }    
        self::wiki_save_page($wikipage, $record['content'], $userid, $time);
        
        return $record['subwikiid'];
    }
    
    static function wiki_save_page($wikipage, $newcontent, $userid, $time=0) {
        global $DB;
        if($time == 0)
            $time = time();
    
        $wiki = wiki_get_wiki_from_pageid($wikipage->id);
        $cm = get_coursemodule_from_instance('wiki', $wiki->id);
        $context = context_module::instance($cm->id);
    
        if (has_capability('mod/wiki:editpage', $context)) {
            $version = wiki_get_current_version($wikipage->id);
    
            $version->content = $newcontent;
            $version->userid = $userid;
            $version->version++;
            $version->timecreated = $time;
            $version->id = $DB->insert_record('wiki_versions', $version);
    
            $wikipage->timemodified = $version->timecreated;
            $wikipage->userid = $userid;
            $return = wiki_refresh_cachedcontent($wikipage, $newcontent);
            $event = \mod_wiki\event\page_updated::create(
                    array(
                        'context' => $context,
                        'objectid' => $wikipage->id,
                        'relateduserid' => $userid,
                        'other' => array(
                            'newcontent' => $newcontent
                            )
                        ));
            $event->add_record_snapshot('wiki', $wiki);
            $event->add_record_snapshot('wiki_pages', $wikipage);
            $event->add_record_snapshot('wiki_versions', $version);
            $event->trigger();
            return $return;
        } else {
            return false;
        }
    }
    
    static function get_subwiki($wiki, $subwikiid = null, $group = null, $userid = null) {
        global $USER, $DB;
    
        if ($subwikiid) {
            $params = ['id' => $subwikiid, 'wikiid' => $wiki->id];
            if ($group !== null) {
                $params['group'] = $group;
            }
            if ($userid !== null) {
                $params['userid'] = $userid;
            }
            return $DB->get_field('wiki_subwikis', 'id', $params, MUST_EXIST);
        }
    
        if ($userid === null) {
            $userid = ($wiki->wikimode == 'individual') ? $USER->id : 0;
        }
        if ($group === null) {
            $group = 0;
        }
        if ($subwiki = wiki_get_subwiki_by_group($wiki->id, $group, $userid)) {
            return $subwiki->id;
        } else {
            return wiki_add_subwiki($wiki->id, $group, $userid);
        }
    }   
    
    public static function add_wiki_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'courseid instance id'),
                'name' => new external_value(PARAM_RAW, 'courseid instance id'),
                'desc' => new external_value(PARAM_RAW, 'courseid instance id'),
                'content' => new external_value(PARAM_RAW, 'courseid instance id'),
                'userid' => new external_value(PARAM_RAW, 'courseid instance id')
            )
        );
    }

    public static function add_wiki_returns() {
        return new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'id of the source'),
                ));
    }

    public static function add_subwiki_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'courseid instance id'),
                'wikiid' => new external_value(PARAM_INT, 'courseid instance id'),                
                'title' => new external_value(PARAM_RAW, 'courseid instance id'),
                'userid' => new external_value(PARAM_INT, 'courseid instance id'),
                'time' => new external_value(PARAM_INT, 'courseid instance id'),
                'content' => new external_value(PARAM_RAW, 'courseid instance id', VALUE_OPTIONAL),
            )
        );
    }

    public static function add_subwiki_returns() {
        return new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'id of the source'),
                    'title' => new external_value(PARAM_RAW, 'id of the source')
                ));
    }

    public static function add_sub_wiki_history_parameters() {
        return new external_function_parameters(
            array(
                'wikiid' => new external_value(PARAM_INT, 'courseid instance id'),                
                'subwikiid' => new external_value(PARAM_INT, 'courseid instance id'),
                'title' => new external_value(PARAM_RAW, 'courseid instance id'),
                'history' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'content' => new external_value(PARAM_RAW, 'full name', VALUE_OPTIONAL),
                            'time' => new external_value(PARAM_INT, 'full name', VALUE_OPTIONAL),
                            'userid' => new external_value(PARAM_INT, 'full name', VALUE_OPTIONAL)
                        )
                    ), 'courseid instance id', VALUE_OPTIONAL
                ),
            )
        );
    }

    public static function add_sub_wiki_history_returns() {
        return new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'id of the source')
                ));
    }

    public function update_attempt_questions($attemptuniqueid, $questionname){
        global $DB;
        $questionssql = "SELECT q.id
                        FROM {question} q
                        JOIN {question_categories} qg on qg.id = q.category
                        JOIN {context} con on con.id = qg.contextid
                        JOIN {course_modules} cm on  cm.id = con.instanceid                        
                        JOIN {quiz} quiz on quiz.id = cm.instance
                        JOIN {quiz_attempts}  qa on qa.quiz = quiz.id
                        WHERE " . $DB->sql_compare_text('questiontext') . " = '$questionname'
                        AND qa.uniqueid = $attemptuniqueid";
        $questions = $DB->get_records_sql($questionssql);
        foreach($questions as $questionid => $question){
            $question_attempt = $DB->get_record('question_attempts', array('questionusageid' => $attemptuniqueid, 'slot' => 1), '*', MUST_EXIST);
            $question_answers = "SELECT id, answer, fraction
                        FROM {question_answers}
                        WHERE question = $questionid";
            $answers = $DB->get_records_sql($question_answers);
            $summary = [];
            $ids = [];
            foreach($answers as $answer){
                $summary[] = $answer->answer;
                $ids[] = $answer->id;
                if(intval($answer->fraction) == 1)
                    $rightanswer = $answer->answer;
            }
            $question_attempt->questionid = $questionid;
            $question_attempt->questionsummary = $questionname . ":" . implode(";", $summary);
            $question_attempt->rightanswer = $rightanswer;
            $DB->update_record('question_attempts', $question_attempt);

            // Update question attempt step data
            $question_attempt_step_sql = "SELECT sd.*
                        FROM {question_attempt_steps} s 
                        JOIN {question_attempt_step_data} sd on s.questionattemptid = sd.attemptstepid
                        WHERE s.questionattemptid = $question_attempt->id";
            $question_attempt_step = $DB->get_record_sql($question_attempt_step_sql);
            $question_attempt_step->value = $ids;
            $DB->update_record('question_attempt_step_data', $question_attempt_step);
            break;
        }
        return array("id" => $attemptuniqueid);        
    }

    public static function generate_questionnaire_responses_parameters() {
        return new external_function_parameters(
            array(
                'questionnaireid' => new external_value(PARAM_INT, 'attempt unique id'),
                'userid' => new external_value(PARAM_INT, 'attempt unique id'),
                'time' => new external_value(PARAM_INT, 'attempt unique id'),
                'questions' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'name' => new external_value(PARAM_RAW, 'full name', VALUE_OPTIONAL),
                            'values' => new external_value(PARAM_RAW, 'full name', VALUE_OPTIONAL),
                            'position' => new external_value(PARAM_INT, 'full name', VALUE_OPTIONAL)
                        )
                    ), 'question responses', VALUE_OPTIONAL
                )
            )
        );
    }

    public static function generate_questionnaire_responses($questionnaireid, $userid, $time, $questions){
        global $DB;
        $questionnaire = $DB->get_record('questionnaire', array('id' => $questionnaireid));
        $responses = [];
        foreach($questions as $question){
            $sql = ' SELECT * 
                      FROM {questionnaire_question}
                      WHERE surveyid = ? AND position = ?  AND ' . $DB->sql_compare_text('content') . ' = ?';
            $values = [$questionnaireid, $question["position"], $question["name"]];
            foreach ($DB->get_records_sql($sql, $values) as $instance) {
                $quest = $instance;
                break;
            }
            //$quest = $DB->get_record('questionnaire_question', array('content' => $DB->sql_compare_text($question["name"])));            
            switch($quest->type_id){
                case "8": // rate
                    $options = explode(";;;", $question["values"]);
                    $opts = [];
                    foreach($options as $option){
                        $name = explode("@@@", $option)[0];
                        $val = explode("@@@", $option)[1];
                        $opts[] = new question_response_rank($name, intval($val - 1));
                    }
                    $responses[] = new question_response($quest->id, $opts);
                    break;
                case "3": // essay
                    $responses[] = new question_response($quest->id, $question["values"]);
                    break;
                case "10": // numeric
                    $responses[] = new question_response($quest->id, $question["values"]);
                    break;
                case "4": // radio
                case "6": // drop
                    $options = $question["values"];
                    $responses[] = new question_response($quest->id, $options);
                    break;
                case "5": // check 
                    $options = explode(";;;", $question["values"]);
                    $responses[] = new question_response($quest->id, $options);
                    break;
            }
        }
        self::create_response(['questionnaireid' => $questionnaire->id, 'userid' => $userid, "submitted" => $time], $responses);
        //self::generate_response($questionnaire, $questions2, $userid, $time);
        return array("id" => $questionnaireid);
    }

    /**
     * @param questionnaire $questionnaire
     * @param \mod_questionnaire\question\question[] $questions
     * @param $userid
     * @param $complete
     * @return stdClass
     * @throws coding_exception
     */
    public static function generate_response($questionnaire, $questions, $userid, $time) {
        $time = time();
        $responses = [];
        foreach ($questions as $question) {
            switch ($question->type_id) {
                case QUESTEXT :
                    $responses[] = new question_response($question->id, 'Test answer');
                    break;
                case QUESESSAY :
                    $resptext = '<h1>Some header text</h1><p>Some paragraph text</p>';
                    $responses[] = new question_response($question->id, $resptext);
                    break;
                case QUESNUMERIC :
                    $responses[] = new question_response($question->id, 83);
                    break;
                /*case QUESRADIO :
                case QUESDROP :
                    $optidx = count($choices) - 1;
                    $responses[] = new question_response($question->id, $choices[$optidx]);
                    break;
                case QUESCHECK :
                    $answers = [];
                    for ($a = 0; $a < count($choices) - 1; $a++) {
                        $optidx = count($choices) - 1;
                        $answers[] = $choices[$optidx]->content;
                    }
                    $answers = array_unique($answers);
                    $responses[] = new question_response($question->id, $answers);
                    break;*/
                case QUESRATE :
                    $choices = array_values($question->choices);
                    //$qclassname = '\\mod_questionnaire\\question\\rate';
                    //new $qclassname($question->id, $qdata, $context, ['type_id' => 8]);
                    $answers = [];
                    for ($a = 0; $a < count($choices); $a++) {
                        $answers[] = new question_response_rank($choices[$a], 2);
                    }
                    $responses[] = new question_response($question->id, $answers);
                    break;
            }
        }
        return self::create_response(['questionnaireid' => $questionnaire->id, 'userid' => $userid, "time" => $time], $responses);
    }
    /**
     * Create response to questionnaire.
     *
     * @param array|stdClass $record
     * @param array $questionresponses
     * @param boolean $complete Whether the response is complete or not.
     * @return stdClass the discussion object
     */
    public static function create_response($record = null, $questionresponses) {
        global $DB;
        $record = (array)$record;
        $record['complete'] = 'y';

        // Add the response.
        $record['id'] = $DB->insert_record('questionnaire_response', $record);
        $responseid = $record['id'];

        foreach ($questionresponses as $questionresponse) {
            if (!$questionresponse instanceof question_response) {
                throw new coding_exception('Question responses must have an instance of question_response'.
                    var_export($questionresponse, true));
            }
            self::add_response_choice($questionresponse, $responseid);
        }
        return $record;
    }

    static function add_response_choice($questionresponse, $responseid) {
        global $DB;

        $question = $DB->get_record('questionnaire_question', ['id' => $questionresponse->questionid]);
        $qtype = intval($question->type_id);

        if (is_array($questionresponse->response)) {
            foreach ($questionresponse->response as $choice) {
                $newresponse = clone($questionresponse);
                $newresponse->response = $choice;
                self::add_response_choice($newresponse, $responseid);
            }
            return;
        }

        if ($qtype === 0 || $qtype === 4 || $qtype === 6 || $qtype === 5 || $qtype === 8) {
            if (is_int($questionresponse->response)) {
                $choiceid = $questionresponse->response;
            } else {
                if ($qtype === 8) {
                    if (!$questionresponse->response instanceof question_response_rank) {
                        throw new coding_exception('Question response for ranked choice should be of type question_response_rank');
                    }
                    $choiceval = $questionresponse->response->choice;
                } else {
                    if (!is_object($questionresponse->response)) {
                        $choiceval = $questionresponse->response;
                    } else {
                        if ($questionresponse->response->content.'' === '') {
                            throw new coding_exception('Question response cannot be null for question type '.$qtype);
                        }
                        $choiceval = $questionresponse->response->content;
                    }
                }
                // Lookup the choice id.
                $comptext = $DB->sql_compare_text('content');
                $select = 'WHERE question_id = ? AND ('.$comptext.' = ? OR '.$comptext.' = ?)';

                $params = [intval($question->id), $choiceval,  "!other=" . $choiceval];
                $rs = $DB->get_records_sql("SELECT * FROM {questionnaire_quest_choice} $select", $params, 0, 1);

                $choice = reset($rs);
                if (!$choice) {
                    var_dump("SELECT * FROM {questionnaire_quest_choice} $select");
                    throw new coding_exception('Could not find choice for "'.$choiceval.
                        '" (question_id = '.$question->id.')', var_export($choiceval, true));
                }
                $choiceid = $choice->id;
            }
            if ($qtype == 8) {
                $DB->insert_record('questionnaire_response_rank', [
                        'response_id' => $responseid,
                        'question_id' => $questionresponse->questionid,
                        'choice_id' => $choiceid,
                        'rankvalue' => $questionresponse->response->rankvalue
                    ]
                );
            } else {
                if ($qtype === 0 || $qtype === 4 || $qtype === 6) {
                    $instable = 'questionnaire_resp_single';
                } else if ($qtype === 5) {
                    $instable = 'questionnaire_resp_multiple';
                }
                $DB->insert_record($instable, [
                        'response_id' => $responseid,
                        'question_id' => $questionresponse->questionid,
                        'choice_id' => $choiceid
                    ]
                );
            }
        } else {
            $DB->insert_record('questionnaire_response_text', [
                    'response_id' => $responseid,
                    'question_id' => $questionresponse->questionid,
                    'response' => $questionresponse->response
                ]
            );
        }
    }


    public static function generate_questionnaire_responses_returns() {
        return new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'id of the source'),
                ));
    }

}
