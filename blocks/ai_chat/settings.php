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
 * Plugin settings
 *
 * @package    block_ai_chat
 * @copyright  2024 Phat Duy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig && $ADMIN->fulltree) {

    require_once($CFG->dirroot .'/blocks/ai_chat/lib.php');

    
    global $PAGE;
    $PAGE->requires->js_call_amd('block_ai_chat/settings', 'init');
    
    $settings->add(new admin_setting_configtext(
        'block_ai_chat/apikey',
        'API Key',
        'The API Key for your AI account or Azure API key',
        '',
        PARAM_TEXT
    ));


//    $settings->add(new admin_setting_configcheckbox(
//        'block_ai_chat/allowinstancesettings',
//        get_string('allowinstancesettings', 'block_ai_chat'),
//        get_string('allowinstancesettingsdesc', 'block_ai_chat'),
//        0
//    ));

}