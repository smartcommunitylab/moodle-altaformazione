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
    )
);
