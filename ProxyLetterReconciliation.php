<?php
namespace Stanford\LetterProject;

/** @var \Stanford\LetterProject\LetterProject $module */

use \REDCap as REDCap;
use DateTime;
use Stanford\SurveyDashboard\Participant;
include_once "LetterPDF.php";
use Stanford\LetterProject\LetterPDF;
use Exception;

// Start the session
session_start();

//HARD CODING IT FOR TEST
//$record = 5;
//$name = "Zaphod Beeblebrox ";
//$doctor_name = 'Doctor Who';

$image_url = $module->getUrl('images/stanford-healthcre.png',true,true );

// HANDLE AJAX POST REQUESTS
if (!empty($_POST['action'])) {
    $action = $_POST['action'];
    $record = $_POST['record_id'];

    if ($action == 'saveResponse') {
        //save the timestamp
        $module->saveTimestamp($module->getProjectId(), $record, $module->getProjectSetting('first-event'));

        //persist current set of responses
        $event_id = $module->getProjectSetting('final-event');
        $event_name = REDCap::getEventNames(true,false, $event_id);

        $data = array(
            //REDCap::getRecordIdField() => $_POST['record_id'],
            //'redcap_event_name' => LetterProject::$config['final_event']
            'redcap_event_name' =>$event_name
        );

        $data = array_merge($data, $_POST);
        unset($data['action']);

        //$module->emDEbug($data, "DATA from12REQUEST");

        //overwrite to unselect the checkbox selections
        $q = REDCap::saveData('json', json_encode(array($data)), overwrite);
        if (count($q['errors']) > 0) {
            $msg = "Error saving response for ".$data['record_id']." in ". $module->getProjectSetting('final-event');
            $module->emError($data, $q, $msg, "ERROR");

            $result = array("result" => "error", "message" => $msg);
        } else {
            $result = array("result" => "success");
        }
    }


    if ($action == 'emailPDF') {
        $record = $_POST['record_id'];

        $emails = $_POST['data'];

        try {
            $q = sendEmailPDF($record, $emails);
            //todo: check send status
            $module->emDebug("EMAILING PDF", $q);
            $result = array("result" => "success", "message" => "Your emails were sent to these addresses: \n" . implode("\n", $emails));
        }
        catch (Exception $e) {
            $result = array("result" => "fail", "message" => $e->getMessage());
        }
    }

    if ($action == 'downloadPDF') {
        $record = $_POST['record_id'];

        $letter_url = $module->getUrl("GetLetter.php", true,true);
        $redirect_url = $letter_url."&action=D&id=".$record;

        //redirect($redirect_url);

        $result = array("result" => "success", "url" => $redirect_url);

    }

    if ($action == 'printPDF') {
        $record = $_POST['record_id'];

        $letter_url = $module->getUrl("GetLetter.php", true,true);
        $redirect_url = $letter_url."&action=P&id=".$record;

        //redirect($redirect_url);

        $result = array("result" => "success", "url" => $redirect_url);

    }

    if ($action == 'saveNewEmail') {
        $record = $_POST['record_id'];
        $event = $_POST['event'];
        $email = $_POST['data'];

        $status = $module->saveNewEmail($record, $email, $event);
            //todo: check send status
        $module->emDebug("Saving MEW EMAIL ", $q);
        if ($status) {
            $result = array("result" => "success", "message" => "A proxy invitation email was sent to  $email");
        } else {
            $result = array("result" => "fail", "message" => "There was an error attempting to save the email.");
        }
    }

    if ($action == 'checkWitnessForm') {
        //get surveyURL for final witness form
        $event_id = $module->getProjectSetting('final-event');
        $record = $_POST['record_id'];
        $instrument = $module->getProjectSetting('witness-survey');

        // Get the survey link for this record-instrument-event
        $survey_link = REDCap::getSurveyLink($record, $instrument, $event_id);

        $module->emDebug($survey_link);
    }

    header('Content-Type: application/json');
    print json_encode($result);
    exit();

}

// HANDLE A LOGIN
while (isset($_POST['login'])) {
    $email_code = $_GET['e'];
    $code = $_POST['code'];
    $print_age = $_POST['print_page'];

    if (empty($email_code)) {
        $_SESSION['msg'] = "Please use the link from your email.";
        break;
    }

    if (empty($code)) {
        $_SESSION['msg'] = "You must supply a valid login code";
        break;
    }

   //Lookup the code and email_code
    $participant_id = $module->lookupByCode($code);

    if ($participant_id == false) {
        $_SESSION['msg'] = "The supplied login code was not valid.<br><br>If you forgot your code, please contact "
        . $module->getProjectSetting('project-admin-email') .".";
        break;
    }

    //verify that email_code matches
    //$first_event = LetterProject::$config['first_event'];
    $first_event_id = $module->getProjectSetting('first-event');
    $verify_data = $module->getResponseData($participant_id, array('name','ltr_doctor_name',$module->getProjectSetting('hash')), $first_event_id);
    //$event_id = REDCap::getEventIdFromUniqueEvent($first_event);

    //$module->emDebug($verify_data, "VERIFY DATA for $code with event $first_event_id and participant id $participant_id ".$module->getProjectSetting('hash'));

    $v_email_code = $verify_data[$participant_id][$first_event_id][$module->getProjectSetting('hash')];


    if (!($email_code === $v_email_code)) {
        $module->emDebug("$email_code does not match $v_email_code");
        $_SESSION['msg'] = "Your code does not match the response expected from your link. Please use the link from your email.";
        break;
    }

    $record = $participant_id;
    $name = $verify_data[$participant_id][$first_event_id]['name'];
    $doctor_name = $verify_data[$participant_id][$first_event_id]['ltr_doctor_name'];

    //check if the letter has been witnessed and signed
    $witness_done = $module->getWitnessPageStatus($record, $first_event_id);
    if ($witness_done === false) {
        $module->emDebug("WITNESS NOT YET DONE");
    } else {
        $module->emDebug("WITNESS COMPLETED");
    }

    //get surveyURL for final witness form
    $event_id = $module->getProjectSetting('final-event');
    $instrument = $module->getProjectSetting('witness-survey');
    $survey_link = REDCap::getSurveyLink($record, $instrument, $event_id);

    $module->emDebug($survey_link);



    //display the tabbed questions
    include("pages/reconciliation.php");
    // Redirect to the reconciliation
    //header('location: ' . $link);

    if (isset($_POST['print_page'])) {
        //we're coming in after the signature witness so go to the print page
        ?>
        <script>
        $(document).ready(function() {
            $('.nav-tabs a:last').tab('show');
        });
        </script>
        <?php
    }
    exit();

}

include "pages/login.php";

/**
 * Reorganize REDCap getData result to be of format
 *   [question number] [event_name]
 * @param $record
 * @return array
 */
function organizeResponses($record) {
    global $module;

    $responses = $module->getResponseData($record);
    $responses = $responses[$record];

    $questions = $module->getProjectSetting('questions');

    $reorganized = array();
    $event_array = array();
    //create an event array of all the events in this project
    $event_list = array(
        $module->getProjectSetting('first-event'),
        $module->getProjectSetting('proxy-1-event'),
        $module->getProjectSetting('proxy-2-event'),
        $module->getProjectSetting('proxy-3-event'),
        $module->getProjectSetting('final-event')
    );

    foreach ($event_list as $event_id) {
        $event_name = \REDCap::getEventNames(true, false, $event_id);
        $event_array[$event_name] = $event_id; //REDCap::getEventIdFromUniqueEvent($event);
    }
    $module->emDebug($event_array, "EVENT ARRAY TEST");


    //iterate over the question list from the config
    foreach ($questions as $num => $question) {

        $re = '/^(?<prefix>q\d*)_(?<part1>\w*)_*(?<part2>\w*)/m';
        preg_match_all($re, $question, $matches, PREG_SET_ORDER, 0);

        $prefix = $matches[0]['prefix'];
        $part1 =  $matches[0]['part1'];
        $part2 =  $matches[0]['part2'];

        //$module->emLog("question is $question and   PREFIX IS ".$prefix . "part 1 is $part1 and part2 is $part2");

        foreach ($event_array as $event_name => $event_id) {
            //original and final arm have question prepended with 'q': i.e. q1, q2
            //proxy forms has it prepended with 'p_q': i.e. p_q1, p_q2, etc
            $proxy_prefix = (($event_name == 'original_arm_1') || ($event_name == 'final_arm_1')) ? '' : 'p_';

            if ($question == 'q2') {
                //Question 2 is actually 4 questions that should be displayed together
                $prefix = $question.'_milestone';
                $reorganized[$question][$event_name] = array($responses[$event_id][$proxy_prefix.$prefix . '_1'],
                    $responses[$event_id][$proxy_prefix.$prefix . '_2'],
                    $responses[$event_id][$proxy_prefix.$prefix . '_3'],
                    $responses[$event_id][$proxy_prefix.$prefix . '_4'],);

            } elseif ($question == 'q5') {
                //Question 5 is actually multiple questions that should be displayed together

                $q_types = array('name_decision', 'relationship_decision', 'address_decision', 'city_decision', 'phone_decision');

                $decision_maker_array = array();
                for ($j=1; $j<4; $j++) {
                    foreach ($q_types as $k => $v) {
                        $prefix = $question . '_' . $v;
                        $decision_maker_array[$j][$v] = $responses[$event_id][$proxy_prefix.$prefix . '_' . $j];
                    }
                }
                $reorganized[$question][$event_name] = $decision_maker_array;
            } elseif (($prefix == 'q7') || ($prefix == 'q8')) {
                $reorganized[$question][$event_name]  = array('part1' => $responses[$event_id][$proxy_prefix.$prefix . '_'. $part1],
                     'part2' => $responses[$event_id][$prefix . '_' . $part1 . '_inst']);
            } elseif ($question == 'q9') {
                $reorganized[$question][$event_name] = $responses[$event_id][$proxy_prefix.$question];
                $reorganized[$question][$event_name]['q9_99_other'] = $responses[$event_id][$proxy_prefix.'q9_99_other'];
            } elseif ($question == 'q13') {
                $reorganized[$question][$event_name]['q13'] = $responses[$event_id][$proxy_prefix.$question];
                $reorganized[$question][$event_name]['q13_donate_following'] = $responses[$event_id][$proxy_prefix.'q13_donate_following'];

            } else {
                $reorganized[$question][$event_name] = $responses[$event_id][$proxy_prefix.$question];
            }

        }
    }
    return $reorganized;
}

function renderTabs() {
    global $module;

    $tabs = array();
    $index = 2;

    $questions = $module->getProjectSetting('questions'); //LetterProject::$config['questions'];

    foreach ($questions as $num => $key) {

        $tabs[] = "<li class='nav-item'>".
            "<a class='nav-link' id='tab-{$key}' data-toggle='tab' href='#{$key}' role='tab' aria-controls='{$key}' aria-selected=''".
            "'>" . strtoupper($key) . "</a></li>";

        //        $tabs[] = "<li><a data-toggle='tab' id='tab-{$key}' data-index='{" . $index++ .
        //            "' data-key='{$key}' href='#{$key}' title='" . $key .
        //            "'>" . strtoupper($key) . "</a></li>";
    }
    //LetterProject::log(implode($tabs), "TABS");
    print implode("", $tabs);
}

function renderTabDivs($record) {
    global $module, $Proj;

    $questions = $module->getProjectSetting('questions'); //LetterProject::$config['questions'];
    $responses = organizeResponses($record);
    //$module->emDebug($responses);  exit;

    $maker_data = getDecisionMakerData($record);

    $q = REDCap::getData(
        'json',
        $record,
        array(
            REDCap::getRecordIdField(),
            $module->getProjectSetting('code-field'),
            $module->getProjectSetting('proxy-1-field'),
            $module->getProjectSetting('proxy-2-field'),
            $module->getProjectSetting('proxy-3-field')),
        $module->getProjectSetting('first-event')
    );
    $results = json_decode($q,true);
    $proxies = current($results);

    $divs = array();

    /**
    $divs[] = "
                <div id='home' class='tab-pane active in'>
                    <div class=\"jumbotron text-center\">
                        <div id='welcome'>
                            <h1>WHAT MATTERS MOST</h1>
                            <p<>We want to provide you with the best care possible. To do this, we need to understand what matters most to you.</p>
                            <p<>We will make every effort to respect and honor your wishes and choices.</p>
                        </div>
                    </div>";
     */
    $divs[] = "<div id='" . "home" . "' class='tab-pane active in'>". getDecisionMakerPage($maker_data);  // ."</div>";
    $divs[] = renderNavButtons(false, true, false);
    $divs[] .="</div>";

    $metadata = $Proj->metadata;
    $n = 1;
    foreach ($questions as $num => $question_num ) {
        $meta_label         = $metadata[$question_num]['element_label'];
        $field_type         = $metadata[$question_num]['element_type'];
        $question_count     = count($questions);
        $str  = "<div id='" . $question_num . "' class='tab-pane fade in'>";
            $str .= "<div class=\"jumbotron questions\">";
                $str .= "<h2>Care Choice Question</h2>";
                $str .= "<div class='metalabel'>";
                $str .= "<div class='page_count'>Question: $n of $question_count</div>";
                $str .= $meta_label;
                $str .= "<blockquote><h5>Your Response:</h5>".formatPrintAnswers($question_num, $field_type, $responses[$question_num]['original_arm_1'])."</blockquote>";
                $str .= "</div>";
            $str .= "</div>";
            $str .= 
                renderAnswers(  $question_num,
                                $responses[$question_num]['original_arm_1'],
                                $responses[$question_num]['proxy_1_arm_1'],
                                $responses[$question_num]['proxy_2_arm_1'],
                                $responses[$question_num]['proxy_3_arm_1'],
                                $responses[$question_num]['final_arm_1'],
                                $proxies
                );
        $str .= "</div>";
        $divs[] = $str;
        $n++;
    }
    $divs[] = "<div id='" . "print_page" . "' class='tab-pane fade in'>". getPDFPage($proxies) ."</div>";

    print implode("", $divs);
}



function getDecisionMakerPage($maker_data) {
    global $module;
    //$module->emDebug($maker_data);

//    $str = "<div class=\"jumbotron text-center\">
//                    <div id='pdf_page_one'>this is the print page. Perhaps some PDF here?</div>
//                    </div>";

    $str =
        '
        <div class="card-deck mb-3 text-center">
        
          <div class="card mb-4 box-shadow">
          <div class="card-body">
            <button type="button" id="btn-print-pdf" class="btn btn-lg btn-block btn-primary">View My Letter</button>
            <ul class="list-unstyled mt-3 mb-4">
              <li>A new tab will open to display the letter and present a menu to print to your printer</li>
            </ul>
          </div>
        </div>
        
        <div>
        
</div>
        
        </div>';

    /**

    $maker_data = array();
    $maker_data[1] = array("one", "two", "three");
    $maker_data[2] = array("one2", "two", "three");
    $maker_data[3] = array("one3", "two", "three");

$maker_data = getDecisionMakerData($record);
*/
    //make table with the decisiion makers
    $table =  "<div class='decision_maker'><table id='decision_maker'>";

    //TODO Check first if notarized

    $table .= "<tr><th>My Decision Makers</th><th>Email</th><th>Status</th><th>Send PDF of my letter</th></tr>";
    foreach ($maker_data as $k => $v) {
        $table .= "<tr>";
        foreach ($v as $row => $element) {
            $table .= "<td>$element</td>";
        }
        $table .= "</tr>";

    }
    $table .="</table>";

    $table .="</div>";


    return $str.$table;
}

function getDecisionMakerData($record_id) {
    global $module;

    $email_field = $module->getProjectSetting('code-field');
    $proxy_1_name_field = $module->getProjectSetting('proxy-1-name-field');
    $proxy_2_name_field = $module->getProjectSetting('proxy-2-name-field');
    $proxy_3_name_field = $module->getProjectSetting('proxy-3-name-field');
    $proxy_1_field      = $module->getProjectSetting('proxy-1-field');
    $proxy_2_field      = $module->getProjectSetting('proxy-2-field');
    $proxy_3_field      = $module->getProjectSetting('proxy-3-field');
    $date_last_reconciled =  $module->getProjectSetting('date-last-reconciled');

    $params = array(
            'project_id' => $module->getProjectId(),
            'return_format' => 'json',
            'events' => array($module->getProjectSetting('first-event')),
            'fields' => array(
                $email_field, //$module->getProjectSetting('code-field'),
                $proxy_1_field, //$module->getProjectSetting('proxy-1-field'),
                $proxy_2_field, //$module->getProjectSetting('proxy-2-field'),
                $proxy_3_field, //$module->getProjectSetting('proxy-3-field')
                $proxy_1_name_field,
                $proxy_2_name_field,
                $proxy_3_name_field,
                $date_last_reconciled
            ),
            'records' => $record_id
    );
    $data = REDCap::getData($params);

        //$q = \REDCap::getData($module->getProjectId(), 'json',  array($record_id), null, $module->getProjectSetting('final-event'));
    $final_data = json_decode($data, true);
    //$module->emDebug($params,$module->getProjectId(),$module->getProjectSetting('final-event'), $final_data, $record_id, "FINAL DATA");

        //
    //$module->emDebug($params,$final_data); exit;
    $final_data = current($final_data);

    $send_data[$email_field]['email'] = $final_data[$email_field];
    $send_data[$proxy_1_field]['email'] = $final_data[$proxy_1_field];
    $send_data[$proxy_2_field]['email'] = $final_data[$proxy_2_field];
    $send_data[$proxy_3_field]['email'] = $final_data[$proxy_3_field];
    $send_data[$proxy_1_field]['name'] = $final_data[$proxy_1_name_field];
    $send_data[$proxy_2_field]['name'] = $final_data[$proxy_2_name_field];
    $send_data[$proxy_3_field]['name'] = $final_data[$proxy_3_name_field];

    //now get the completion status
    $main_status = getCompletionStatus($module->getProjectSetting('first-event'), $record_id);
    $event_1_status = getCompletionStatus($module->getProjectSetting('proxy-1-event'), $record_id, true);
    $event_2_status = getCompletionStatus($module->getProjectSetting('proxy-2-event'), $record_id, true);
    $event_3_status = getCompletionStatus($module->getProjectSetting('proxy-3-event'), $record_id, true);


    $completion_field = $module->getProjectSetting('letter-survey').'_complete';
    $timestamp_field = $module->getProjectSetting('letter-survey').'_timestamp';
    $proxy_completion_field = $module->getProjectSetting('proxy-survey').'_complete';
    $proxy_timestamp_field = $module->getProjectSetting('proxy-survey').'_timestamp';

    $send_data[$email_field]['status'] = $main_status[$completion_field];
    $send_data[$proxy_1_field]['status'] = $event_1_status[$proxy_completion_field];
    $send_data[$proxy_2_field]['status'] = $event_2_status[$proxy_completion_field];
    $send_data[$proxy_3_field]['status'] = $event_3_status[$proxy_completion_field];
    $send_data[$email_field]['timestamp'] = $main_status[$timestamp_field];
    $send_data[$proxy_1_field]['timestamp'] = $event_1_status[$proxy_timestamp_field];
    $send_data[$proxy_2_field]['timestamp'] = $event_2_status[$proxy_timestamp_field];
    $send_data[$proxy_3_field]['timestamp'] = $event_3_status[$proxy_timestamp_field];

    //$module->emDebug($send_data); exit;

    //rearrange the data by proxy event
    $return_data = array();
    foreach ($send_data as $event => $row) {
        $return_data[] = getSurveyStatus($record_id, $row, $final_data[$date_last_reconciled], $event);
    }

    //$module->emDebug($return_data); exit;
    return $return_data;

}

/**
 *
 * Possible Survey Status
 * Waiting for response  =>  not completed
 * Completed      => survey_complete == 2  AND ( survey_timestamp > last_reconciled_timestamp )
 * Pending review => survey_complete==2 AND ( survey_timestamp < last_reconciled_timestamp )
 * Send proxy invitation button  => No decision maker email
 * Just trigger save of the form to trigger the autonotify?
 *
 * @param $row  :
 *
 */
function getSurveyStatus($record_id, $row, $last_timestamp_str, $event) {
    global $module;

    //$module->emDebug($last_timestamp_str, $row);
    $email = $row['email'];
    $status = $row['status'];
    $name   = $row['name'];

    $ts_string = $row['timestamp'];
    $ts = new DateTime($ts_string);
    $last_ts = new DateTime($last_timestamp_str);

    //$module->emDebug($ts, $last_ts);

    //default for email

    if ($status == 2) {
        if ($ts > $last_ts) {
            //finished survey was completed before last reconciled
            $module->emDebug('Pending' . $ts->getTimestamp() . ' is less than '. $last_ts->getTimestamp());
            $completion_status = 'Pending Review';

        } else {

            $module->emDebug('COMPLETED TS : ' . $ts->getTimestamp() . ' is greater than '. $last_ts->getTimestamp());
            $completion_status = 'Completed';
        }
        $button_html = '<button id="'. $email .'" class="btn btn-lg btn-block btn-primary send-pdf" data-id="'. $record_id .'" data-email="'. $email .'" name="send">SEND to '.$email.'</button>';
    } else {
        //if there is an email, then waiting
        if (!empty($email)) {
            $completion_status = 'Waiting for response';
            $button_html = '<button id="'. $email .'" class="btn btn-lg btn-block btn-primary send-pdf" data-id="'. $record_id .'" data-email="'. $email .'" name="send">SEND to '.$email.'</button>';
        } else {
            //create a text field for
            $completion_status = '<button id="'. $email .'" class="btn btn-lg btn-block btn-primary send-invite" data-id="'. $record_id .'" data-event="'. $event .'" name="send">SEND invitation to proxy</button>';
            //$email ='Enter another proxy:<br> <input type="text" name="fname">';
            $email =' <input class="proxy_email_input" type="text" id="proxy_email_'.$event.'" placeholder="Enter proxy\'s email.">';
                $foo = '<div>
                <input type="text" id="proxy" placeholder="Enter another proxy email."/>
            </div>';
            //$button_html = '<button id="email_holder" class="btn btn-lg btn-block btn-primary send-invite"  data-id="'. $record_id .'" data-event="'. $event .'"  name="send-invite">SEND invitation to new proxy</button>';
            $button_html = '';

        }
    }


    //button
    //Sequence here determines column sequence in the table
    return array($name, $email, $completion_status, $button_html);

}

/**
 * Check the completion status of the proxy letters to report to the portal.
 * Latest change: if proxy now uses a different form.
 * @param $event
 * @param $record
 * @return mixed|null
 */
function getCompletionStatus($event, $record, $proxy = false) {
    global $module;

    $completion_field = $module->getProjectSetting('letter-survey').'_complete';
    $timestamp_field = $module->getProjectSetting('letter-survey').'_timestamp';

    if ($proxy == true) {
        $completion_field = $module->getProjectSetting('proxy-survey').'_complete';
        $timestamp_field = $module->getProjectSetting('proxy-survey').'_timestamp';
    }

    $params = array(
        'project_id' => $module->getProjectId(),
        'return_format' => 'json',
        'events' => $event,
        'exportSurveyFields' => true,
        'fields' => array($completion_field, $timestamp_field),
        'records' => $record
    );
    $data = REDCap::getData($params);
    $final_data = json_decode($data, true);
    //$module->emDebug($event, $params,$final_data);
    if (!empty($final_data)) {
        return current($final_data);
    }
    return null;

}

function getPDFPage($proxy) {
    global $module;
    //$module->emDebug($proxy);

    //    $str = "<div class=\"jumbotron text-center\">
    //                    <div id='pdf_page_one'>this is the print page. Perhaps some PDF here?</div>
    //                    </div>";

    $str =
        '
        <div class="card-deck mb-3 text-center">
        
          <div class="card mb-4 box-shadow">
          <div class="card-body">
            <button type="button" id="btn-email-pdf" class="btn btn-lg btn-block btn-outline-primary">Send to these emails</button>
            <ul class="text-left list-unstyled mt-3 mb-4">
                <div class="form-check">
                <input type="checkbox" class="form-check-input" name="channel[]" id="checkbox" value="' . $proxy[$module->getProjectSetting("code-field")] . '">
                <label for="checkbox1" class="form-check-label">To you: ' . $proxy[$module->getProjectSetting("code-field")] . ' ?</label>
                </div>
                <div class="form-check">
                <input type="checkbox" class="form-check-input" name="channel[]" id="checkbox" value="' . $proxy[$module->getProjectSetting("proxy-1-field")] . '">
                <label for="checkbox1" class="form-check-label">To ' . $proxy[$module->getProjectSetting("proxy-1-field")] . '?</label>
                </div>
                <div class="form-check">
                <input type="checkbox" class="form-check-input" name="channel[]" id="checkbox" value="' . $proxy[$module->getProjectSetting("proxy-2-field")] . '">
                <label for="checkbox1" class="form-check-label">To ' . $proxy[$module->getProjectSetting("proxy-2-field")] . '?</label>
                </div>
                <div class="form-check">
                <input type="checkbox" class="form-check-input" name="channel[]" id="checkbox" value="' . $proxy[$module->getProjectSetting("proxy-3-field")] . '">
                <label for="checkbox1" class="form-check-label">To ' . $proxy[$module->getProjectSetting("proxy-3-field")] . '?</label>
                </div>
            </ul>
          </div>
          </div>
        
          <div class="card mb-4 box-shadow">
          <div class="card-body">
             <button type="button" id="btn-download-pdf" class="btn btn-lg btn-block btn-primary">Download</button>
             <ul class="list-unstyled mt-3 mb-4">
               <li>Download to your local drive</li>            
              </ul>
          </div>
        </div>
          
          <div class="card mb-4 box-shadow">
          <div class="card-body">
            <button type="button" id="btn-print-pdf" class="btn btn-lg btn-block btn-primary">Display and Print</button>
            <ul class="list-unstyled mt-3 mb-4">
              <li>A new tab will open to display the letter and present a menu to print to your printer</li>
            </ul>
          </div>
        </div>
        
        </div>';

    return $str;
}


/**
 * Helper method to get the choice list from the metadata.
 *
 * @param $question_num - field name in the project data dictionary
 * @return array - array in this format
 *           [coded_value] => [label]
 */
function getFieldChoiceList($question_num) {
    global $Proj;
    $metadata = $Proj->metadata;

    $field_enum = $metadata[$question_num]['element_enum'];
    //if enumeration is set, then convert it into an coded value array

    //separate on the \n
    $enum = explode("\\n", $field_enum);
    foreach ($enum as $value ) {
        $temp = explode(",", $value);
        $coded[trim($temp[0])] = $temp[1];
    }
    return $coded;
}

function formatPrintAnswers($question_num, $field_type, $response) {
    global $module;

    $q = '';
    switch ($field_type) {
        case "descriptive":
            //handle the descriptive formats
            //Q2 is an odd ball case where there is 4 text boxes after the descriptive
            //let's just check just in case more get added
            if ($question_num == "q2") {
                //$module->emLog($response, "RESPONSE FOR $proxy_num");
                $q .= "<ul class='list_answers'>";
                foreach ($response as $resp_item) {
                    if(empty($resp_item)){
                        continue;
                    }
                    $q .= "<li><span>$resp_item</span></li>";
                }
                $q .= "</ul>";
            }

            //Q5 is an odd ball case where there is 5 text boxes after the descriptive
            //let's just check just in case more get added
            if ($question_num == "q5") {
                //$module->emLog($response, "RESPONSE FOR $proxy_num");
                $q .= '<div class="ttable">';
                $q .= '<div class="ttable-row thead">';
                    $q .= '<div class="ttable-cell"></div>';
                    $q .= '<div class="ttable-cell">Name:</div>';
                    $q .= '<div class="ttable-cell">Relationship:</div>';
                    $q .= '<div class="ttable-cell">Address:</div>';
                    $q .= '<div class="ttable-cell">City, State, Zip:</div>';
                    $q .= '<div class="ttable-cell">Phone:</div>';
                $q .= '</div>';
                $q .= '<div class="ttable-row">';
                $q .= '<div class="ttable-cell proxy_num">Proxy 1</div>';
                $q .= '<div class="ttable-cell"><b>Name:</b> '.$response[1]['name_decision'].'</div>';
                $q .= '<div class="ttable-cell"><b>Relationship:</b> '.$response[1]['relationship_decision'].'</div>';
                $q .= '<div class="ttable-cell"><b>Address:</b> '.$response[1]['address_decision'].'</div>';
                $q .= '<div class="ttable-cell"><b>City, State, Zip:</b> '.$response[1]['city_decision'].'</div>';
                $q .= '<div class="ttable-cell"><b>Phone:</b> '.$response[1]['phone_decision'].'</div>';
                $q .= '</div>';
                $q .= '<div class="ttable-row">';
                $q .= '<div class="ttable-cell proxy_num">Proxy 2</div>';
                $q .= '<div class="ttable-cell"><b>Name:</b> '.$response[2]['name_decision'].'</div>';
                $q .= '<div class="ttable-cell"><b>Relationship:</b> '.$response[2]['relationship_decision'].'</div>';
                $q .= '<div class="ttable-cell"><b>Address:</b> '.$response[2]['address_decision'].'</div>';
                $q .= '<div class="ttable-cell"><b>City, State, Zip:</b> '.$response[2]['city_decision'].'</div>';
                $q .= '<div class="ttable-cell"><b>Phone:</b> '.$response[2]['phone_decision'].'</div>';
                $q .= '</div>';
                $q .= '<div class="ttable-row">';
                $q .= '<div class="ttable-cell proxy_num">Proxy 3</div>';
                $q .= '<div class="ttable-cell"><b>Name:</b> '.$response[3]['name_decision'].'</div>';
                $q .= '<div class="ttable-cell"><b>Relationship:</b> '.$response[3]['relationship_decision'].'</div>';
                $q .= '<div class="ttable-cell"><b>Address:</b> '.$response[3]['address_decision'].'</div>';
                $q .= '<div class="ttable-cell"><b>City, State, Zip:</b> '.$response[3]['city_decision'].'</div>';
                $q .= '<div class="ttable-cell"><b>Phone:</b> '.$response[3]['phone_decision'].'</div>';
                $q .= '</div>';
                $q .= '</div>';
            }

            break;
        case "checkbox":
            //handle the checkbox formats

            //get the choice enumeration from the metadata
            $coded = getFieldChoiceList($question_num);

            $i=1;
            foreach ($coded as $code => $proxy_num) {
                $q .= "<label class='disabled'>";
                $q .= "<input name='" . $q_label . "_" . $i . "' disabled  type=\"checkbox\"";
                if ($response[$code]) {
                    $q .= " checked = checked";
                }
                $q .="/>$proxy_num</label>";
                $i++;
            }
            if ($question_num == "q9") {
                $response_other = $response['q9_99_other'];
                $q .= "<p class='text_answer other'>$response_other</p>";
            }
            break;
        case "radio":
            //handle the radio formats
            //get the choice enumeration from the metadata
            $coded  = getFieldChoiceList($question_num);
            $re     = '/^(?<prefix>q\d*)_(?<part1>\w*)_*(?<part2>\w*)/m';
            preg_match_all($re, $question_num, $matches, PREG_SET_ORDER, 0);
            $prefix = $matches[0]['prefix'];

            if ($prefix == "q7") {
                $question_num = $prefix;
                $part1 = $response['part1'];
                $part2 = $response['part2'];

                $q .= '<form>';
                $i = 1;
                foreach ($coded as $code => $proxy_num) {
                    $q .= "<label class='disabled'>";
                    $q .= "<input name='" . $q_label . "' value=" . $i . " disabled type=\"radio\"";
                    if ((isset($part1)) && ($part1 == $code)) {
                        $q .= " checked='checked'";
                    }
                    $q .= "/> $proxy_num";
                    $q .= "</label>";
                    $i++;
                }
                $q .= '</form>';
                $q .= "<p class='text_answer other'>$part2</p>";
            } elseif ($prefix == "q8") {
                $module->emLog("2PREFIX IS SDF".$prefix);
                $question_num = $prefix;
                $part1 =  $response['part1'];
                $part2 =  $response['part2'];

                $q .= '<form>';
                $i=1;
                foreach ($coded as $code => $proxy_num) {
                    $q .= "<label class='disabled'>";
                    $q .= "<input name='" . $q_label . "' value=" . $i . " disabled type=\"radio\"";
                    if ((isset($part1)) && ($part1 == $code) ) {
                        $q .= " checked='checked'";
                    }
                    $q .= "/> $proxy_num";
                    $q .= "</label>";
                    $i++;
                }
                $q .= '</form>';
                $q .= "<p class='text_answer other'>$part2</p>";
            } else {
                if ($question_num == "q13") {
                    $q13_other  = $response['q13_donate_following'];
                    $response   = $response['q13'];
                }
                $q .= '<form>';
                $i = 1;
                foreach ($coded as $code => $proxy_num) {
                    $q .= "<label class='disabled'>";
                    $q .= "<input name='" . $q_label . "' value=" . $i . " disabled type=\"radio\"";
                    if ((isset($response)) && ($response == $code)) {
                        $q .= " checked='checked'";
                    }
                    $q .= "/> $proxy_num";
                    $q .= "</label>";
                    if ($question_num == "q13" && $i == 2) {
                        $q .= "<p class='text_answer other q13'>$q13_other</p>";
                    }
                    $i++;
                }
                $q .= '</form>';
            }
            break;
        case "yesno":
            //handle the yesno formats
            //get the choice enumeration from the metadata
            $coded  = getFieldChoiceList($question_num);
            $re     = '/^(?<prefix>q\d*)_(?<part1>\w*)_*(?<part2>\w*)/m';
            preg_match_all($re, $question_num, $matches, PREG_SET_ORDER, 0);

            $prefix = $matches[0]['prefix'];
            $q .= '<form>';
            if ($prefix == "q8") {
                $question_num = $prefix;
                $part1 =  $response['part1'];
                $part2 =  $response['part2'];
                $i=1;
                foreach ($coded as $code => $proxy_num) {
                    $q .= "<label class='disabled'>";
                    $q .= "<input name='" . $q_label . " value=" . $i . "' disabled type=\"radio\"";
                    if ((isset($part1)) && ($part1 == $code) ) {
                        $q .= " checked='checked'";
                    }
                    $q .=" /> $proxy_num";
                    $q .= "</label>";
                    $i--;
                }
                $inst_label = $q_label."_inst";
                $q .= "<p class='text_answer other'>$part2</p>";
            } else {
                $i = 1;
                foreach ($coded as $code => $proxy_num) {
                    $q .= "<label class='disabled'>";
                    $q .= "<input name='" . $q_label . " value=" . $i . "' disabled type=\"radio\"";
                    if ((isset($response)) && ($response == $code)) {
                        $q .= " checked='checked'";
                    }
                    $q .= "/> $proxy_num";
                    $q .= "</label>";
                    //yesno decrements since 1=yes, 0=no
                    $i--;
                }
            }
            $q .= '</form>';
            break;
        default:
            //default  is textarea
            $q .= "<p class='text_answer'>$response</p>";
            break;
    }

    return $q;
}

function formatInputFields($question_num, $proxy_num, $field_type, $response, $editable=false) {
    global $module;

    $readonly = $editable ? "" : "readonly";
    $grey = $editable ? "" : "greyed";
    $disabled = $editable ? "" : "disabled";

    $q = '';

    //helper fields for labeling
    $q_label = $question_num . "_" . $proxy_num;  //i.e. q7_proxy_1
    $final_label = $question_num . "_final";  //i.e. q7_final

    switch ($field_type) {
        case "descriptive":
            //handle the descriptive formats
            //Q2 is an odd ball case where there is 4 text boxes after the descriptive
            //let's just check just in case more get added
            if ($question_num == "q2") {
                //$module->emLog($response, "RESPONSE FOR $proxy_num");

                //put each field in a separate text field
                $i=1;
                $q .= "<ul class='list_answers'>";
                foreach ($response as $proxy_name) {
                    //$name = $question_num . "_" . $i;
                    //$q .= "<div class='text-group {$grey}'>";
                    //$q .= "<label for='$name'>".$i."</label>";
                    $q .= '<input id="'.$final_label.'_'.$i.'" name="' .$final_label .'_'.$i.'" value="'.$proxy_name.'" type="text" />';
                    //$q .= "</div>";
                    $i++;
                }
                $q .= "</ul>";
            }

            //Q5 is an odd ball case where there is 5 text boxes after the descriptive
            //let's just check just in case more get added
            if ($question_num == "q5") {
                //$module->emLog($response, "RESPONSE FOR $proxy_num");

                if ($proxy_num == 'final') {
                    //put each field in a separate text field
                    $i=1;
                        //$q .= "<p>Decision Maker #$proxy_name</p>";
                        //$q .= "<div class=\"form-control\">";
                        ///$q .= "Name: <input name=" . $q_label . "_" . $i . " $readonly value=\"".$detail['name_decision']."\" class=\"form-check-input\" type=\"text\"" . "/>";
                        //$q .= "Relationship: <input name=" . $q_label . "_" . $i . " $readonly value=\"".$detail['relationship_decision']."\" class=\"form-check-input\" type=\"text\"" . "/>";
                        //$q .= "</div>";
                    $q .= "<div class='tabular'>";
                    $q .= '<table class="tabular">';
                    $q .= '<thead>';
                    $q .= '<tr>';
                    $q .= '<th colspan="1"></th>';
                    $q .= '<th colspan="1">Name:</th>';
                    $q .= '<th colspan="1">Relationship:</th>';
                    $q .= '<th colspan="1">Address:</th>';
                    $q .= '<th colspan="1">City, State, Zip:</th>';
                    $q .= '<th colspan="1">Phone:</th>';
                    $q .= '</tr>';
                    $q .= '</thead>';
                    $q .= '<tr>';
                    $q .= '<td>1</td>';
                    $q .= '<td><input class="tcell" id="'.$final_label.'_name_decision_1'.'" name="'.$final_label.'_name_decision_1'.'"  value="'.$response[1]['name_decision'].'" type="text"></td>';
                    $q .= '<td><input class="tcell" id="'.$final_label.'_relationship_decision_1'.'" name="'.$final_label.'_relationship_decision_1'.'"  value="'.$response[1]['relationship_decision'].'" type="text"></td>';
                    $q .= '<td><input class="tcell" id="'.$final_label.'_address_decision_1'.'" name="'.$final_label.'_address_decision_1'.'"  value="'.$response[1]['address_decision'].'" type="text"></td>';
                    $q .= '<td><input class="tcell" id="'.$final_label.'_city_decision_1'.'" name="'.$final_label.'_city_decision_1'.'"  value="'.$response[1]['city_decision'].'" type="text"></td>';
                    $q .= '<td><input class="tcell" id="'.$final_label.'_phone_decision_1'.'" name="'.$final_label.'_phone_decision_1'.'"  value="'.$response[1]['phone_decision'].'" type="text"></td>';
                    $q .= '</tr>';
                    $q .= '<tr>';
                    $q .= '<td>2</td>';
                    $q .= '<td><input class="tcell" id="'.$final_label.'_name_decision_2'.'" name="'.$final_label.'_name_decision_2'.'"  value="'.$response[2]['name_decision'].'" type="text"></td>';
                    $q .= '<td><input class="tcell" id="'.$final_label.'_relationship_decision_2'.'" name="'.$final_label.'_relationship_decision_2'.'"  value="'.$response[2]['relationship_decision'].'" type="text"></td>';
                    $q .= '<td><input class="tcell" id="'.$final_label.'_address_decision_2'.'" name="'.$final_label.'_address_decision_2'.'"  value="'.$response[2]['address_decision'].'" type="text"></td>';
                    $q .= '<td><input class="tcell" id="'.$final_label.'_city_decision_2'.'" name="'.$final_label.'_city_decision_2'.'"  value="'.$response[2]['city_decision'].'" type="text"></td>';
                    $q .= '<td><input class="tcell" id="'.$final_label.'_phone_decision_2'.'" name="'.$final_label.'_phone_decision_2'.'"  value="'.$response[2]['phone_decision'].'" type="text"></td>';
                    $q .= '</tr>';
                    $q .= '<tr>';
                    $q .= '<td>3</td>';
                    $q .= '<td><input class="tcell" id="'.$final_label.'_name_decision_3'.'" name="'.$final_label.'_name_decision_3'.'"  value="'.$response[3]['name_decision'].'" type="text"></td>';
                    $q .= '<td><input class="tcell" id="'.$final_label.'_relationship_decision_3'.'" name="'.$final_label.'_relationship_decision_3'.'"  value="'.$response[3]['relationship_decision'].'" type="text"></td>';
                    $q .= '<td><input class="tcell" id="'.$final_label.'_address_decision_3'.'" name="'.$final_label.'_address_decision_3'.'"  value="'.$response[3]['address_decision'].'" type="text"></td>';
                    $q .= '<td><input class="tcell" id="'.$final_label.'_city_decision_3'.'" name="'.$final_label.'_city_decision_3'.'"  value="'.$response[3]['city_decision'].'" type="text"></td>';
                    $q .= '<td><input class="tcell" id="'.$final_label.'_phone_decision_3'.'" name="'.$final_label.'_phone_decision_3'.'"  value="'.$response[3]['phone_decision'].'" type="text"></td>';
                    $q .= '</tr>';
                    $q .= '</table>';
                    $q .= '</div>';


                } else {
                    $i=1;

                    $q .= "<div class='tabular'>";
                    $q .= '<table class="tabular">';
                    $q .= '<thead>';
                    $q .= '<tr>';
                    $q .= '<th colspan="1"></th>';
                    $q .= '<th colspan="1">Name:</th>';
                    $q .= '<th colspan="1">Relationship:</th>';
                    $q .= '<th colspan="1">Address:</th>';
                    $q .= '<th colspan="1">City, State, Zip:</th>';
                    $q .= '<th colspan="1">Phone:</th>';
                    $q .= '</tr>';
                    $q .= '</thead>';
                    $q .= '<tr>';
                    $q .= '<td>1</td>';
                    $q .= '<td><input class="tcell" readonly id="'.$q_label.'_name_decision_1'.'" name="'.$q_label.'_name_decision_1'.'"  value="'.$response[1]['name_decision'].'" type="text"></td>';
                    $q .= '<td><input class="tcell" readonly id="'.$q_label.'_relationship_decision_1'.'" name="'.$q_label.'_relationship_decision_1'.'"  value="'.$response[1]['relationship_decision'].'" type="text"></td>';
                    $q .= '<td><input class="tcell" readonly id="'.$q_label.'_address_decision_1'.'" name="'.$q_label.'_address_decision_1'.'"  value="'.$response[1]['address_decision'].'" type="text"></td>';
                    $q .= '<td><input class="tcell" readonly id="'.$q_label.'_city_decision_1'.'" name="'.$q_label.'_city_decision_1'.'"  value="'.$response[1]['city_decision'].'" type="text"></td>';
                    $q .= '<td><input class="tcell" readonly id="'.$q_label.'_phone_decision_1'.'" name="'.$q_label.'_phone_decision_1'.'"  value="'.$response[1]['phone_decision'].'" type="text"></td>';
                    $q .= '</tr>';
                    $q .= '<tr>';
                    $q .= '<td>2</td>';
                    $q .= '<td><input class="tcell" readonly id="'.$q_label.'_name_decision_2'.'" name="'.$q_label.'_name_decision_2'.'"  value="'.$response[2]['name_decision'].'" type="text"></td>';
                    $q .= '<td><input class="tcell" id="'.$q_label.'_relationship_decision_2'.'" name="'.$q_label.'_relationship_decision_2'.'"  value="'.$response[2]['relationship_decision'].'" type="text"></td>';
                    $q .= '<td><input class="tcell" id="'.$q_label.'_address_decision_2'.'" name="'.$q_label.'_address_decision_2'.'"  value="'.$response[2]['address_decision'].'" type="text"></td>';
                    $q .= '<td><input class="tcell" id="'.$q_label.'_city_decision_2'.'" name="'.$q_label.'_city_decision_2'.'"  value="'.$response[2]['city_decision'].'" type="text"></td>';
                    $q .= '<td><input class="tcell" id="'.$q_label.'_phone_decision_2'.'" name="'.$q_label.'_phone_decision_2'.'"  value="'.$response[2]['phone_decision'].'" type="text"></td>';
                    $q .= '</tr>';
                    $q .= '<tr>';
                    $q .= '<td>3</td>';
                    $q .= '<td><input class="tcell" id="'.$q_label.'_name_decision_3'.'" name="'.$q_label.'_name_decision_3'.'"  value="'.$response[3]['name_decision'].'" type="text"></td>';
                    $q .= '<td><input class="tcell" id="'.$q_label.'_relationship_decision_3'.'" name="'.$q_label.'_relationship_decision_3'.'"  value="'.$response[3]['relationship_decision'].'" type="text"></td>';
                    $q .= '<td><input class="tcell" id="'.$q_label.'_address_decision_3'.'" name="'.$q_label.'_address_decision_3'.'"  value="'.$response[3]['address_decision'].'" type="text"></td>';
                    $q .= '<td><input class="tcell" id="'.$q_label.'_city_decision_3'.'" name="'.$q_label.'_city_decision_3'.'"  value="'.$response[3]['city_decision'].'" type="text"></td>';
                    $q .= '<td><input class="tcell" id="'.$q_label.'_phone_decision_3'.'" name="'.$q_label.'_phone_decision_3'.'"  value="'.$response[3]['phone_decision'].'" type="text"></td>';
                    $q .= '</tr>';
                    $q .= '</table>';
                    $q .= '</div>';

                }
            }

            break;
        case "checkbox":
            //handle the checkbox formats
            //get the choice enumeration from the metadata
            $coded = getFieldChoiceList($question_num);

            $i=1;
            foreach ($coded as $code => $proxy_num) {
                $q .= "<label>";
                    $q .= "<input name=" . $q_label . "_" . $i . " $disabled type=\"checkbox\"";
                    if ($response[$code]) {
                        $q .= " checked = checked";
                    }
                    $q .="/> $proxy_num";
                $q .= "</label>";
                $i++;
            }
            if ($question_num == "q9") {
                $response_other = $response['q9_99_other'];
                $q .= "<textarea $readonly class=\"form-control $grey\" id=\"$q_label\" name=\"$q_label\" rows=\"3\">$response_other</textarea>";
            }
            break;
        case "radio":
            //handle the radio formats
            //get the choice enumeration from the metadata


            $coded = getFieldChoiceList($question_num);
            //$module->emDebug("==========================question num: $question_num", $coded, $q_label, $response);

            $re = '/^(?<prefix>q\d*)_(?<part1>\w*)_*(?<part2>\w*)/m';
            preg_match_all($re, $question_num, $matches, PREG_SET_ORDER, 0);

            $prefix = $matches[0]['prefix'];

            if ($prefix == "q7") {
                $question_num = $prefix;
                $part1 = $response['part1'];
                $part2 = $response['part2'];

                $q .= '<div>';
                $i = 1;

                foreach ($coded as $code => $proxy_num) {
                    $q .= "<label>";
                    $q .= "<input name=" . $q_label . " value=" . $i . " $disabled ";
                    $q .= " type=\"radio\"";
                    if ((isset($part1)) && ($part1 == $code)) {
                        //LetterProject::log($response ."_". $code, "IN YESNO/RADIO");
                        $q .= " checked = checked";
                    }
                    $q .= "/> $proxy_num";
                    $q .= "</label>";
                    $i++;
                }
                $q .= '</div>';
                $q .= "<textarea $readonly class=\"form-control $grey\" id=\"$q_label\" name=\"$q_label\" rows=\"3\">$part2</textarea>";
            } elseif ($prefix == "q8") {
                $module->emLog("2PREFIX IS SDF".$prefix);
                $question_num = $prefix;
                $part1 =  $response['part1'];
                $part2 =  $response['part2'];

                $q .= '<div>';
                $i=1;
                foreach ($coded as $code => $proxy_num) {
                    $q .= "<label>";
                    $q .= "<input name=" . $q_label . " value=" . $i . " $disabled ";
                    $q .= " type=\"radio\"";
                    if ((isset($part1)) && ($part1 == $code)) {
                        //LetterProject::log($response ."_". $code, "IN YESNO/RADIO");
                        $q .= " checked = checked";
                    }
                    $q .= "/> $proxy_num";
                    $q .= "</label>";
                    $i++;
                }
                $q .= '</div>';
                $q .= "<textarea $readonly class=\"form-control $grey\" id=\"$q_label\" name=\"$q_label\" rows=\"3\">$part2</textarea>";
            } else {

                /**
                 *
                 * <div class="btn-group" data-toggle="buttons">
                 * <label class="btn btn-primary">
                 * <input type="radio" name="options" id="option1"> Option 1
                 * </label>
                 * <label class="btn btn-primary">
                 * <input type="radio" name="options" id="option2"> Option 2
                 * </label>
                 * <label class="btn btn-primary">
                 * <input type="radio" name="options" id="option3"> Option 3
                 * </label>
                 * </div>
                 */

                if ($question_num == "q13") {
                    $q13_other = $response['q13_donate_following'];
                    $response = $response['q13'];
                }

                $i = 1;

                foreach ($coded as $code => $proxy_num) {
                    $q .= "<label>";
                    $q .= "<input name=" . $q_label . " value=" . $i . " $disabled ";
                    $q .= " type=\"radio\"";
                    if ((isset($response)) && ($response == $code)) {
                        //LetterProject::log($response ."_". $code, "IN YESNO/RADIO");
                        $q .= " checked = checked";
                    }
                    $q .= "/> $proxy_num";
                    $q .= "</label>";

                    if ($question_num == "q13" && $i == 2) {
                        $q .= "<textarea $readonly class=\"form-control $grey\" id=\"$q_label\" name=\"$q_label\" rows=\"3\">$q13_other</textarea>";
                    }
                    $i++;
                }
                if ($question_num == "q13") {
                }
            }

            break;
        case "yesno":
            //handle the yesno formats

            //get the choice enumeration from the metadata
            $coded = getFieldChoiceList($question_num);

            $re = '/^(?<prefix>q\d*)_(?<part1>\w*)_*(?<part2>\w*)/m';
            preg_match_all($re, $question_num, $matches, PREG_SET_ORDER, 0);

            $prefix = $matches[0]['prefix'];

            if ($prefix == "q8") {
                $question_num = $prefix;
                $part1 =  $response['part1'];
                    $part2 =  $response['part2'];
                    $q .= '<div>';
                    $i=1;
                    foreach ($coded as $code => $proxy_num) {
                        $q .= "<label>";
                        $q .= "<input name=" . $q_label . " value=" . $i . " $disabled ";
                        $q .= " type=\"radio\"";
                        if ((isset($part1)) && ($part1 == $code) ) {
                            $q .= " checked = checked";
                        }
                        $q .= "/> $proxy_num";
                        $q .= "</label>";
                        $i--;
                    }
                    $inst_label = $q_label."_inst";
                    $q .= '</div>';
                    $q .= "<textarea $readonly class=\"form-control $grey\" id=\"$inst_label\" name=\"$inst_label\" rows=\"3\">$part2</textarea>";
            } else {
                $i = 1;
                foreach ($coded as $code => $proxy_num) {
                    $q .= "<label>";
                    $q .= "<input name=" . $q_label . " value=" . $i . " $disabled type=\"radio\"";
                    if ((isset($response)) && ($response == $code)) {
                        $q .= " checked = checked";
                    }
                    $q .= "/> $proxy_num</label>";
                    $i--;
                }
            }
            break;
        default:
            //textarea
            $q .= "<textarea $readonly class=\"form-control $grey\" id=\"$q_label\" name=\"$q_label\" rows=\"4\">$response</textarea>";
            break;
    }
    return $q;
}

function renderNavButtons($previous,$next, $submit, $print_page = null) {
    $str = '<div class="mb-3 group-end btn-footer">';
    if ($previous) {
        $str .= '<div class="btn btn-primary btnPrevious">Back</div>';
    }
    if ($next) {
        $str .= '<div class="btn btn-primary btnNext">Next</div>';
    }
    if ($submit) {
        $str .= '<div class="btn btn-primary btnWitness">Review Witness and Signature Form</div>';
    }
    if ($print_page) {
        $str .= '<div class="btn btn-primary btnPrint">Go to Print Page</div>';
    }
    $str .= '</div>';
    return $str;
}

/**
 * Renders for reconciling the 4 separate responses into a Final text box
 *
 *
 * @param $question_num  - question number
 * @param $orig  - Original response
 * @param $p1    - Response from proxy_1
 * @param $p2    - Response from proxy_2
 * @param $p3    - Response from proxy_3
 * @param $final - Final text, if one exists.
 * @param $proxy - array of emails of subject and proxies
 *
 * @return string - HTML string
 */
function renderAnswers($question_num, $orig, $p1, $p2, $p3, $final, $proxy) {
    global $module, $Proj;
    //what is last question field
    $questions = $module->getProjectSetting('questions');
    $last_question = end(array_values($questions));

    $proxy_1_field = $proxy[$module->getProjectSetting("proxy-1-field")];
    $proxy_2_field = $proxy[$module->getProjectSetting("proxy-2-field")];
    $proxy_3_field = $proxy[$module->getProjectSetting("proxy-3-field")];

    $fields_test = array(
        $proxy[$module->getProjectSetting("proxy-1-field")].'\'s <br>response'=>$p1,
        $proxy[$module->getProjectSetting("proxy-2-field")].'\'s <br>response'=>$p2,
        $proxy[$module->getProjectSetting("proxy-3-field")].'\'s <br>response'=>$p3);

    if (!empty($proxy_1_field)) {
        $fields[$proxy_1_field]=$p1;
    }
    if (!empty($proxy_2_field)) {
        $fields[$proxy_2_field]=$p2;
    }
    if (!empty($proxy_3_field)) {
        $fields[$proxy_3_field]=$p3;
    }

    //$module->emDebug($proxy, $fields,$proxy_1_field,$proxy_2_field,$proxy_3_field); exit;

    $metadata = $Proj->metadata;
    $field_type = $metadata[$question_num]['element_type'];
    //LetterProject::log($field_type, "===============FIELD_TYPE for $question_num");

    // $q = "<div class=\"col-lg-12\">";
    // $q .= "<blockquote><b>Your Response</b>: $orig</blockquote>";
    // $q .= "</div>";

    $q .= "<div class=\"col-lg-12 proxy_answers\">";

    $q .= "<h2>Your Proxy Responses</h2>";
    foreach ($fields as $label => $response) {
        //$module->emLog("Question num: $question_num / LABEL : $label / RESPONSE : ", $response);  exit;

        // if (!isset($response)) {
        //     continue;
        // }


        //helper fields for labeling
        $q_label = $question_num . "_" . $label;  //i.e. q7_proxy_1
        $final_label = $question_num . "_final";  //i.e. q7_final
        $q .= "<div class=\"col-lg-4 proxy_cards\">";
        $q .= "<div class='card'>";
            $q .= "<div class=\"card-body\">";
                $q .= '<h4 class="card-title">' . $label . '</h4>';
                $q .= "<div class=\"card-text\">";
                $q .= formatPrintAnswers($question_num, $field_type, $response);
                $q .= "</div>";
             $q .= "</div>";
        $q .= "</div>";
        $q .= "</div>";
    }
    $q .="</div>";

    $q .= "<div class=\"col-lg-12 final_answer\">";
    $q .= "<h2>Your Response : Final Edit</h2>";
        $q .= "<p class='instruction_text'>Please review your Proxies' responses and make any final edits to your original response here.</p>";
        $q .= "<div class=\"form-group\">";
            $q .= formatInputFields($question_num, 'final', $field_type, $final, true);
        $q .="</div>";

        if ($question_num == $last_question) {
            $q .= renderNavButtons(true, false, true, true);
        } else {
            $q .= renderNavButtons(true, true, false);
        }
    $q .= "</div>";

    return $q;
}

function sendEmailPDF($record_id, $emails) {
    global $module;

    $module->emDebug("Send Email 1 to ".$record_id, empty($record_id), $record_id == '');
    //1. generate the PDF file
    if (!empty($record_id)) {
        $pdf = $module->setupLetter($record_id);
    } else {
        $msg = "Email was not sent as the record field is undefined.";
        $module->emDebug($msg);
        throw new Exception($msg);
    }
    $module->emDebug("Send Email 2: got pdf");

    //2. TODO: setup the name to be Letter with timestamp
    $letter_attachment = $pdf->Output('LetterProject.pdf', 'E');

    $module->emDebug("Send Email 3: got Email attachment");

    //3. foreach email
    if (isset($emails)) {

        $status = array();
        foreach ($emails as $email) {
            $to = $email;
            $from = 'no-reply@stanford.edu';
            $subject = "Stanford Letter Project";
            $msg = 'Attached please find a copy of your letter.<br><br>
               --Stanford Letter Project<br><br>';
            $module->emDebug("Send Email 4: Sending Email to ".$email);
            $status[] = $module->sendEmail($to, $from, $subject, $msg, $letter_attachment);
        }


    } else {
        throw new Exception("Email was not sent as the email field is undefined.");
    }

    $module->emDebug("Send Email 8: Finished");

    //$module->emDebug("Letter ATtach", $letter_attachment, "ETTER ATTACHB");
    //TODO: check if all status is clear
    return true;
}

function downloadPDF($record_id) {
    global $module;

    //1. generate the PDF file
    if (isset($record_id)) {
        $pdf = setupLetterPDFGenerator($record_id);
    } else {
        throw new Exception("Record  ID WAS NOT SET was not sent as the record field is undefined.");
    }

    $module->emDebug("PDF");
    //2. Display??
    //$pdf->IncludeJS("print();");
    $foo = $pdf->Output('LetterProject.pdf', 'I');
    $module->emDebug($foo, "FOO");
    //return false;
}
?>
