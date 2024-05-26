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
 * API endpoint for retrieving GPT completion
 *
 * @package    block_ai_chat
 * @copyright  2024 Phat Duy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use \block_ai_chat\completion;

require_once('../../../config.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot . '/blocks/ai_chat/lib.php');

global $DB, $PAGE;
global $USER;

// if (get_config('block_ai_chat', 'restrictusage') !== "0") {
//     require_login();
// }

# URL LOCAL
$url_root = "http://localhost:8000";

# URL HOST
#$url_root = "https://moodle-va.onrender.com";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    //header("Location: $CFG->wwwroot");
    $body = json_decode(file_get_contents('php://input'), true);
    $message = clean_param($body['message'], PARAM_NOTAGS);
    // $history = clean_param_array($body['history'], PARAM_NOTAGS, true);
    $block_id = clean_param($body['blockId'], PARAM_INT, true);
    // $thread_id = clean_param($body['threadId'], PARAM_NOTAGS, true);
    // // So that we're not leaking info to the client like API key, the block makes an API request including its ID
    // // Then we can look up that specific block to pull out its config data
    
    $instance_record = $DB->get_record('block_instances', ['blockname' => 'ai_chat', 'id' => $block_id], '*');
    $instance = block_instance('ai_chat', $instance_record);
    error_log('USER id: '.$USER->id, 0);
    
    if (!$instance) {
        error_log("THIS IS INVALID BLOCK", 0);
        print_error('invalidblockinstance', 'error', $id);
    }


    // notice here
    $course_id = -1;
    $context = context::instance_by_id($instance_record->parentcontextid);
    if ($context->contextlevel == CONTEXT_COURSE) {
        $course = get_course($context->instanceid);
        $PAGE->set_course($course);
        $course_id = $course->id;
        error_log('COUUSE: '.$course->id,0);
    } else {
        $PAGE->set_context($context);
        //error_log($PAGE, 0);
    }

    // $block_settings = [];
    // $setting_names = [
    //     'sourceoftruth', 
    //     'prompt',
    //     'instructions',
    //     'username', 
    //     'assistantname', 
    //     'apikey', 
    //     'model', 
    //     'temperature', 
    //     'maxlength', 
    //     'topp', 
    //     'frequency', 
    //     'presence',
    //     'assistant'
    // ];

    $curlbody = [
        "content" => $message,
        "chatId" => $USER->id,
        "role"=> 1,
        "courseId"=> $course_id
    ];

    $curl = curl_init($url_root."/api/v1/chat");
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ',
        'Content-Type: application/json',
    ]);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($curlbody));

    $response = curl_exec($curl);
    $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($http_status === 422) {
        // Handle 422 error
        // You can get more details about the error from the response
        echo "422 Error: Invalid data or missing parameters";
    } else {
        // Handle successful response
        $response = json_decode($response);
        // Do something with the response
       
    }
    
    // Use var_dump to inspect the contents of $response

    $message_res = null;
    if (property_exists($response, 'error')) {
        $message_res = 'ERROR: ' . $response->error->message;
        error_log("RESPONSE err: ".$message_res,0);
    } else {
        $message_res = $response->message;
        error_log("RESPONSE suc: ".$message_res,0);
    }
    // Use var_dump to inspect the contents of $message
    error_log($message_res, 0);

    $final_response= [
        "id" => property_exists($response, 'id') ? $response->id : 'error',
        "message" => $message_res
    ];
    // Format the markdown of each completion message into HTML.
    $final_response["message"] = format_text($final_response["message"], FORMAT_MARKDOWN, );
    $final_response = json_encode($final_response);
    error_log($final_response, 0);
    echo $final_response;
}
else if($_SERVER['REQUEST_METHOD'] === 'DELETE'){
    # get the param from url
    error_log('delete is call', 0);
    $block_id = required_param('block_id', PARAM_NOTAGS);
    $curl = curl_init($url_root."/api/v1/chat/".$USER->id);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($curl);
    $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    error_log('delete success',0);
}