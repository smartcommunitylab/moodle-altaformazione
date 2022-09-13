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
 * Reports ablock external services
 *
 * @package     local_edwiserreports
 * @copyright   2019 wisdmlabs <support@wisdmlabs.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = array(
    'local_modcustomfields_add_resource' => array(
        'classname' => 'local_modcustomfields_external',
        'methodname' => 'add_resource',
        'classpath' => '',
        'description' => 'Create resource activiy',
        'type' => 'write',
        'ajax' => true,
    ),
    'local_modcustomfields_create_questionnaire' => array(
        'classname' => 'local_modcustomfields_external',
        'methodname' => 'create_questionnaire',
        'classpath' => '',
        'description' => 'Create questionnaire module',
        'type' => 'write',
        'ajax' => true,
    ),
    'local_modcustomfields_create_quiz' => array(
        'classname' => 'local_modcustomfields_external',
        'methodname' => 'create_quiz',
        'classpath' => '',
        'description' => 'Create quiz activity',
        'type' => 'write',
        'ajax' => true,
    ),
    'local_modcustomfields_create_quiz_random' => array(
        'classname' => 'local_modcustomfields_external',
        'methodname' => 'create_quiz_random',
        'classpath' => '',
        'description' => 'Create random quiz activity',
        'type' => 'write',
        'ajax' => true,
    ),
    'local_modcustomfields_create_question_bank' => array(
        'classname' => 'local_modcustomfields_external',
        'methodname' => 'create_question_bank',
        'classpath' => '',
        'description' => 'Create question bank',
        'type' => 'write',
        'ajax' => true,
    ),
    'local_modcustomfields_create_progress_block' => array(
        'classname' => 'local_modcustomfields_external',
        'methodname' => 'create_progress_block',
        'classpath' => '',
        'description' => 'Add block progress',
        'type' => 'write',
        'ajax' => true,
    ),
    'local_modcustomfields_update_activity_dependency' => array(
        'classname' => 'local_modcustomfields_external',
        'methodname' => 'update_activity_dependency',
        'classpath' => '',
        'description' => 'Update activity dependency',
        'type' => 'write',
        'ajax' => true,
    ),
    'local_modcustomfields_insert_scorm_tracks' => array(
        'classname' => 'local_modcustomfields_external',
        'methodname' => 'insert_scorm_tracks',
        'classpath' => '',
        'description' => 'insert_scorm_tracks',
        'type' => 'write',
        'ajax' => true,
    ),
    'local_modcustomfields_tag_course' => array(
        'classname' => 'local_modcustomfields_external',
        'methodname' => 'tag_course',
        'classpath' => '',
        'description' => 'tag_course',
        'type' => 'write',
        'ajax' => true,
    ),
    'local_modcustomfields_add_wiki' => array(
        'classname' => 'local_modcustomfields_external',
        'methodname' => 'add_wiki',
        'classpath' => '',
        'description' => 'add_wiki',
        'type' => 'write',
        'ajax' => true,
    ),
    'local_modcustomfields_add_subwiki' => array(
        'classname' => 'local_modcustomfields_external',
        'methodname' => 'add_subwiki',
        'classpath' => '',
        'description' => 'add_subwiki',
        'type' => 'write',
        'ajax' => true,
    ),
    'local_modcustomfields_add_sub_wiki_history' => array(
        'classname' => 'local_modcustomfields_external',
        'methodname' => 'add_sub_wiki_history',
        'classpath' => '',
        'description' => 'add_sub_wiki_history',
        'type' => 'write',
        'ajax' => true,
    ),
    'local_modcustomfields_generate_questionnaire_responses' => array(
        'classname' => 'local_modcustomfields_external',
        'methodname' => 'generate_questionnaire_responses',
        'classpath' => '',
        'description' => 'generate_questionnaire_responses',
        'type' => 'write',
        'ajax' => true,
    )
    
);
