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
 * Block class
 *
 * @package    block_ai_chat
 * @copyright  2023 Bryce Yoder <me@bryceyoder.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->dirroot . '/webservice/lib.php');

class block_ai_chat extends block_base
{
    public function init()
    {
//         $this->title = get_string('ai_chat', 'block_ai_chat');
        $this->title = "Moodle Virtual Assistant";
    }

    public function has_config()
    {
        return true;
    }

    function applicable_formats()
    {
        return array('all' => true);
    }

    public function specialization()
    {
        if (!empty($this->config->title)) {
            $this->title = $this->config->title;
        }
    }
    public function get_history($blockId)
    {
        global $USER;
        $curl = new \curl();

        $response = $curl->get("http://localhost:8000/api/v1/chat?chatid=" . $USER->id);

        if ($response) {
            error_log("res st: " . $response->status, 0);
            $token = optional_param('token',  0, PARAM_TEXT);
            $token = s(\core\session\manager::get_login_token());
            error_log("token: " . $token, 0);
            if (property_exists($response, 'error')) {
                $message = 'ERROR: ' . $response->error->message;
            } else {
                // Decode the JSON response
                $response = json_decode($response);

                // Check if the response is valid JSON
                if ($response !== null) {
                    // Return the decoded response
                    return $response;
                } else {
                    // Invalid JSON response
                    return [];
                }
            }
        } else {
            return [];
        }
    }
    public function get_content()
    {



        global $USER;

        if ($this->content !== null) {
            return $this->content;
        }

        // Send data to front end
        // $persistconvo = get_config('block_ai_chat', 'persistconvo');

        // if (!empty($this->config)) {
        //     $persistconvo = (property_exists($this->config, 'persistconvo') && get_config('block_ai_chat', 'allowinstancesettings')) ? $this->config->persistconvo : $persistconvo;
        // }
        //$history = $this->get_history($this->instance->id);
        $history = $this->get_history($USER->id);
        foreach ($history as $h) {
            // Access the properties of the $h object and concatenate them into a string
            $message = "History: ";
            foreach ($h as $key => $value) {
                $message .= "{$key}: {$value}, ";
            }
            // Remove the trailing comma and space
            $message = rtrim($message, ", ");
            // Log the message
            error_log($message, 0);
        }
        // -----------------------------
        $this->page->requires->js_call_amd('block_ai_chat/lib', 'init', [[
            'blockId' => $this->instance->id,
            //'blockId' => $blockId->id,
            // 'api_type' => get_config('block_ai_chat', 'type') ? get_config('block_ai_chat', 'type') : 'chat',
            // 'persistConvo' => $persistconvo
            'history' => $history
        ]]);
        // error_log("Persistconvo: ".$persistconvo, 0);
        // Determine if name labels should be shown.
        $showlabelscss = '';
        if (!empty($this->config) && !$this->config->showlabels) {
            $showlabelscss = '
                .ai_message:before {
                    display: none;
                }
                .ai_message {
                    margin-bottom: 0.5rem;
                }
            ';
        }

        // First, fetch the global settings for these (and the defaults if not set)
        // $assistantname = get_config('block_ai_chat', 'assistantname') ? get_config('block_ai_chat', 'assistantname') : get_string('defaultassistantname', 'block_ai_chat');
        // $username = get_config('block_ai_chat', 'username') ? get_config('block_ai_chat', 'username') : get_string('defaultusername', 'block_ai_chat');

        // // Then, override with local settings if available
        // if (!empty($this->config)) {
        //     $assistantname = (property_exists($this->config, 'assistantname') && $this->config->assistantname) ? $this->config->assistantname : $assistantname;
        //     $username = (property_exists($this->config, 'username') && $this->config->username) ? $this->config->username : $username;

        // }
        // $assistantname = format_string($assistantname, true, ['context' => $this->context]);
        // $username = format_string($username, true, ['context' => $this->context]);


        $assistantname = "Moodi";
        $username = "User";
        error_log('user name is: ' . $username, 0);
        $this->content = new stdClass;
        $this->content->text = '
            <script>
                var assistantName = "' . $assistantname . '";
                var userName = "' . $username . '";
            </script>

            <style>
                ' . $showlabelscss . '
                .ai_message.user:before {
                    content: "' . $username . '";
                }
                .ai_message.bot:before {
                    content: "' . $assistantname . '";
                }
            </style>

            <div id="ai_chat_log" role="log"></div>
        ';

        $arrow_img = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path fill="white" d="M438.6 278.6c12.5-12.5 12.5-32.8 0-45.3l-160-160c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L338.8 224 32 224c-17.7 0-32 14.3-32 32s14.3 32 32 32l306.7 0L233.4 393.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0l160-160z"/></svg>';
        $refresh_img = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.5.1 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M105.1 202.6c7.7-21.8 20.2-42.3 37.8-59.8c62.5-62.5 163.8-62.5 226.3 0L386.3 160H352c-17.7 0-32 14.3-32 32s14.3 32 32 32H463.5c0 0 0 0 0 0h.4c17.7 0 32-14.3 32-32V80c0-17.7-14.3-32-32-32s-32 14.3-32 32v35.2L414.4 97.6c-87.5-87.5-229.3-87.5-316.8 0C73.2 122 55.6 150.7 44.8 181.4c-5.9 16.7 2.9 34.9 19.5 40.8s34.9-2.9 40.8-19.5zM39 289.3c-5 1.5-9.8 4.2-13.7 8.2c-4 4-6.7 8.8-8.1 14c-.3 1.2-.6 2.5-.8 3.8c-.3 1.7-.4 3.4-.4 5.1V432c0 17.7 14.3 32 32 32s32-14.3 32-32V396.9l17.6 17.5 0 0c87.5 87.4 229.3 87.4 316.7 0c24.4-24.4 42.1-53.1 52.9-83.7c5.9-16.7-2.9-34.9-19.5-40.8s-34.9 2.9-40.8 19.5c-7.7 21.8-20.2 42.3-37.8 59.8c-62.5 62.5-163.8 62.5-226.3 0l-.1-.1L125.6 352H160c17.7 0 32-14.3 32-32s-14.3-32-32-32H48.4c-1.6 0-3.2 .1-4.8 .3s-3.1 .5-4.6 1z"/></svg>';

        $this->content->footer =  '
            <div id="control_bar">
                <div id="input_bar">
                    <input id="ai_input" placeholder="' . 'Ask a question...' . '" type="text" name="message" />
                    <button title="Submit" id="go">' . $arrow_img . '</button>
                </div>
                <button title="New chat" id="refresh">' . $refresh_img . '</button>
            </div>';

        return $this->content;
    }
}
