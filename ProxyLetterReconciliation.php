<?php
namespace Stanford\LetterProject;

/** @var \Stanford\LetterProject\LetterProject $module */

use \REDCap as REDCap;
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

$image_url = $module->getUrl("images/stanford-healthcre.png",true,true );

// HANDLE AJAX POST REQUESTS
if (!empty($_POST['action'])) {
    $action = $_POST['action'];


    if ($action == 'saveResponse') {
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
        }catch (Exception $e) {
            $result = array("result" => "fail","message" => $e->getMessage());
        }


        //todo: check send status

        $result = array("result" => "success","message" => "Your emails were sent to these addresses: \n".implode("\n",$emails));


    }

    if ($action == 'downloadPDF') {
        $record = $_POST['record_id'];

        $letter_url = $module->getUrl("GetLetter.php", false,true);
        $redirect_url = $letter_url."&action=D&id=".$record;

        //redirect($redirect_url);

        $result = array("result" => "success", "url" => $redirect_url);

    }

    if ($action == 'printPDF') {
        $record = $_POST['record_id'];

        $letter_url = $module->getUrl("GetLetter.php", false,true);
        $redirect_url = $letter_url."&action=P&id=".$record;

        //redirect($redirect_url);

        $result = array("result" => "success", "url" => $redirect_url);

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


    $module->emDebug($verify_data, "VERIFY DATA for $code with event $first_event_id and participant id $participant_id ".$module->getProjectSetting('hash'));

    $v_email_code = $verify_data[$participant_id][$first_event_id][$module->getProjectSetting('hash')];

    if (!($email_code === $v_email_code)) {
        $_SESSION['msg'] = "Your code does not match the response expected from your link. Please use the link from your email.";
        break;
    }

    $record = $participant_id;
    $name = $verify_data[$participant_id][$first_event_id]['name'];
    $doctor_name = $verify_data[$participant_id][$first_event_id]['ltr_doctor_name'];

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
 * Reorganize REDCap getData result to be of foramte
 *   [question number] [event_name]
 * @param $record
 * @return array
 */
function organizeResponses($record) {
    global $module;

    //getData('array', $record) return
    $responses = $module->getResponseData($record);
    //$module->emLog($responses, "RESPONSES");
    $responses = $responses[$record];

    $first_event = $module->getProjectSetting('first-event');
    $last_event = $module->getProjectSetting('final-event');
    $questions = $module->getProjectSetting('questions');

    $reorganized = array();


    //foreach (LetterProject::$config['events'] as $event => $label) {
    foreach ($module->getProjectSetting('events') as $event_id) {

        $event_name = \REDCap::getEventNames(true, false, $event_id);
        $event_array[$event_name] = $event_id; //REDCap::getEventIdFromUniqueEvent($event);
    }
     $event_array = array(\REDCap::getEventNames(true, false, $first_event)=>$first_event) + $event_array;
    //$first_event_array[\REDCap::getEventNames(true, false, $first_event)]=$first_event;
    //array_unshift($event_array, $first_event_array);
    $event_array[\REDCap::getEventNames(true, false, $last_event)] = $last_event;
    //$module->emDebug($event_array, "EVENT ARRAY");

    //iterate over the question list from the config
    foreach ($questions as $num => $question) {

        $re = '/^(?<prefix>q\d*)_(?<part1>\w*)_*(?<part2>\w*)/m';
        preg_match_all($re, $question, $matches, PREG_SET_ORDER, 0);

        $prefix = $matches[0]['prefix'];
        $part1 =  $matches[0]['part1'];
        $part2 =  $matches[0]['part2'];

        //$module->emLog("PREFIX IS ".$prefix);

        foreach ($event_array as $event_name => $event_id) {
            //original and final arm have question prepended with 'q': i.e. q1, q2
            //proxy forms has it prepended with 'p_q': i.e. p_q1, p_q2, etc
            //$prefix = (($event_name == 'original_arm_1') || ($event_name == 'final_arm_1')) ? 'q' : 'p_q';

            if ($question == 'q2') {
                //Question 2 is actually 4 questions that should be displayed together
                $prefix = $question.'_milestone';
                $reorganized[$question][$event_name] = array($responses[$event_id][$prefix . '_1'],
                    $responses[$event_id][$prefix . '_2'],
                    $responses[$event_id][$prefix . '_3'],
                    $responses[$event_id][$prefix . '_4'],);

            } elseif ($question == 'q5') {
                //Question 5 is actually multiple questions that should be displayed together

                $q_types = array('name_decision', 'relationship_decision', 'address_decision', 'city_decision', 'phone_decision');

                $decision_maker_array = array();
                for ($j=1; $j<4; $j++) {
                    foreach ($q_types as $k => $v) {
                        $prefix = $question . '_' . $v;
                        $decision_maker_array[$j][$v] = $responses[$event_id][$prefix . '_' . $j];
                    }
                }
                $reorganized[$question][$event_name] = $decision_maker_array;
            } elseif (($prefix == 'q7') || ($prefix == 'q8')) {
                $reorganized[$question][$event_name]  = array('part1' => $responses[$event_id][$prefix . '_'. $part1],
                     'part2' => $responses[$event_id][$prefix . '_' . $part1 . '_inst']);
            } elseif ($question == 'q9') {
                $reorganized[$question][$event_name] = $responses[$event_id][$question];
                $reorganized[$question][$event_name]['q9_99_other'] = $responses[$event_id]['q9_99_other'];
            } elseif ($question == 'q13') {
                $reorganized[$question][$event_name]['q13'] = $responses[$event_id][$question];
                $reorganized[$question][$event_name]['q13_donate_following'] = $responses[$event_id]['q13_donate_following'];

            } else {
                $reorganized[$question][$event_name] = $responses[$event_id][$question];
            }

        }
    }

    //$module->emLog($reorganized, "REORGANIZED");

    return $reorganized;
}

function renderTabs() {
    global $module;

    $tabs = array();
    $index = 2;

    $questions = $module->getProjectSetting('questions'); //LetterProject::$config['questions'];
    $module->emLog($questions, "QUESTIONS");

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
    global $module;

    $questions = $module->getProjectSetting('questions'); //LetterProject::$config['questions'];
    $responses = organizeResponses($record);
    //$module->emDebug($responses);

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
    //LetterProject::$config['first_event']
    );
    $results = json_decode($q,true);
    $proxies = current($results);

    $module->emDebug($proxies,"PROXY");

    global $Proj;
    $metadata = $Proj->metadata;

    $divs = array();
    $divs[] = "
                <div id='home' class='tab-pane active in'>
                    <!--p>Home</p-->
                    <div class=\"jumbotron text-center\">
                    <div id='welcome'>
                        <h1>WHAT MATTERS MOST</h1>
                        <hr>
                        <p<>We want to provide you with the best care possible. To do this, we need to understand what matters most to you.</p>
                        <p<>We will make every effort to respect and honor your wishes and choices.</p>
                    </div>
                    </div>";
    $divs[] = renderNavButtons(false, true, false);
    $divs[] .="</div>";


    foreach ($questions as $num => $question_num ) {
        $meta_label = $metadata[$question_num]['element_label'];

        $str = "<div id='" . $question_num . "' class='tab-pane fade in'>
                    <br>";
        $str .= "<div class=\"jumbotron\"><p>".$meta_label."</p></div>";
        $str .= renderAnswers($question_num, $responses[$question_num]['original_arm_1'],
            $responses[$question_num]['proxy_1_arm_1'],
            $responses[$question_num]['proxy_2_arm_1'],
            $responses[$question_num]['proxy_3_arm_1'],
            $responses[$question_num]['final_arm_1'],
            $proxies
        );
        $str .= "</div>";
        $divs[] = $str;
    }
    $divs[] = "<div id='" . "print_page" . "' class='tab-pane fade in'>". getPDFPage($proxies) ."</div>";

    print implode("", $divs);
}


function getPDFPage($proxy) {
    global $module;
    $module->emDebug($proxy);

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
        case "textarea":
            //handle the textarea formats
            $q .= "<textarea $readonly class=\"form-control $grey\" id=\"$q_label\" name=\"$q_label\" rows=\"5\">$response</textarea><br>";
            break;
        case "descriptive":
            //handle the descriptive formats
            //Q2 is an odd ball case where there is 4 text boxes after the descriptive
            //let's just check just in case more get added
            if ($question_num == "q2") {
                //$module->emLog($response, "RESPONSE FOR $proxy_num");

                //put each field in a separate text field
                $i=1;
                $q .= "<div class='container'>";
                foreach ($response as $proxy_name) {
                    $name = $question_num . "_" . $i;
                    //$q .= "<div class='text-group {$grey}'>";
                    //$q .= "<label for='$name'>".$i."</label>";
                    $q .= "<input name=" .$name . " $readonly value=\"$proxy_name\"  type=\"text\"" . "/>";
                    //$q .= "</div>";
                    $i++;
                }
                $q .= "</div>";
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
                    $q .= '<td><input class="tcell" id="'.$q_label.'_name_decision_1'.'" name="'.$q_label.'_name_decision_1'.'"  value="'.$response[1]['name_decision'].'" type="text"></td>';
                    $q .= '<td><input class="tcell" id="'.$q_label.'_relationship_decision_1'.'" name="'.$q_label.'_relationship_decision_1'.'"  value="'.$response[1]['relationship_decision'].'" type="text"></td>';
                    $q .= '<td><input class="tcell" id="'.$q_label.'_address_decision_1'.'" name="'.$q_label.'_address_decision_1'.'"  value="'.$response[1]['address_decision'].'" type="text"></td>';
                    $q .= '<td><input class="tcell" id="'.$q_label.'_city_decision_1'.'" name="'.$q_label.'_city_decision_1'.'"  value="'.$response[1]['city_decision'].'" type="text"></td>';
                    $q .= '<td><input class="tcell" id="'.$q_label.'_phone_decision_1'.'" name="'.$q_label.'_phone_decision_1'.'"  value="'.$response[1]['phone_decision'].'" type="text"></td>';
                    $q .= '</tr>';
                    $q .= '<tr>';
                    $q .= '<td>2</td>';
                    $q .= '<td><input class="tcell" id="'.$q_label.'_name_decision_2'.'" name="'.$q_label.'_name_decision_2'.'"  value="'.$response[2]['name_decision'].'" type="text"></td>';
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
                $q .= "<div class=\"form-control $grey\">";

                //$q .= "<input name=".$q_label."_".$code." $disabled class=\"form-check-input\" ";
                //$q .= "<input name=" . $q_label . " value=" . $i . " $disabled class=\"form-check-input\" ";
                //in the end, chose to represent each checkbox option as a separate field. easier to overwrite
                //blanks during the save
                $q .= "<input name=" . $q_label . "_" . $i . " $disabled class=\"form-check-input\" ";

                $i++;

                $q .= " type=\"checkbox\"";
                if ($response[$code]) {
                    $q .= " checked = checked";
                }
                $q .="/>$proxy_num</div>";
            }
            if ($question_num == "q9") {
                $response_other = $response['q9_99_other'];

                $q .= "<textarea $readonly class=\"form-control $grey\" id=\"$q_label\" name=\"$q_label\" rows=\"5\">$response_other</textarea>";
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

                $q .= '<div class="greyed">';
                $i = 1;

                foreach ($coded as $code => $proxy_num) {
//                $r .= "<div class=\"btn-group\" data-toggle=\"buttons\">";
//                $r .= "<label class=\"btn btn-primary\">";
//                $r .="<input type=\"radio\" name=\"options\" id=\"option1\"> Option 1";
//                $r .= "</label>";
//                $r .= "<input name=" . $q_label . " value=" . $i . " $disabled class=\"form-check-input\" ";
//
//                $r .="/>$proxy_num</div>";

                    $q .= "<div class=\"form-control $grey\">";
                    $q .= "<input name=" . $q_label . " value=" . $i . " $disabled class=\"form-check-input\" ";
                    $i++;

                    $q .= " type=\"radio\"";
                    if ((isset($part1)) && ($part1 == $code)) {
                        //LetterProject::log($response ."_". $code, "IN YESNO/RADIO");
                        $q .= " checked = checked";
                    }
                    $q .= "/>$proxy_num</div>";
                }
                $q .= '</div>';
                $q .= '<div class="">';
                $q .= "<textarea $readonly class=\"form-control $grey\" id=\"$q_label\" name=\"$q_label\" rows=\"5\">$part2</textarea>";
                $q .= '</div>';
            } elseif ($prefix == "q8") {
                $module->emLog("2PREFIX IS SDF".$prefix);
                    $question_num = $prefix;
                    $part1 =  $response['part1'];
                    $part2 =  $response['part2'];

                    $q .= '<div class="">';
                    $i=1;

                    foreach ($coded as $code => $proxy_num) {
//                $r .= "<div class=\"btn-group\" data-toggle=\"buttons\">";
//                $r .= "<label class=\"btn btn-primary\">";
//                $r .="<input type=\"radio\" name=\"options\" id=\"option1\"> Option 1";
//                $r .= "</label>";
//                $r .= "<input name=" . $q_label . " value=" . $i . " $disabled class=\"form-check-input\" ";
//
//                $r .="/>$proxy_num</div>";

                        $q .= "<div class=\"form-control $grey\">";
                        $q .= "<input name=" . $q_label . " value=" . $i . " $disabled class=\"form-check-input\" ";
                        $i++;

                        $q .= " type=\"radio\"";
                        if ((isset($part1)) && ($part1 == $code) ) {
                            //LetterProject::log($response ."_". $code, "IN YESNO/RADIO");
                            $q .= " checked = checked";
                        }
                        $q .="/>$proxy_num</div>";
                    }
                    $q .= '</div>';
                    $q .= '<div class="$grey">';
                    $q .= "<textarea $readonly class=\"form-control $grey\" id=\"$q_label\" name=\"$q_label\" rows=\"5\">$part2</textarea>";
                    $q .= '</div>';

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
//                $r .= "<div class=\"btn-group\" data-toggle=\"buttons\">";
//                $r .= "<label class=\"btn btn-primary\">";
//                $r .="<input type=\"radio\" name=\"options\" id=\"option1\"> Option 1";
//                $r .= "</label>";
//                $r .= "<input name=" . $q_label . " value=" . $i . " $disabled class=\"form-check-input\" ";
//
//                $r .="/>$proxy_num</div>";

                    $q .= "<div class=\"form-control $grey\">";
                    $q .= "<input name=" . $q_label . " value=" . $i . " $disabled class=\"form-check-input\" ";
                    $i++;

                    $q .= " type=\"radio\"";
                    if ((isset($response)) && ($response == $code)) {
                        //LetterProject::log($response ."_". $code, "IN YESNO/RADIO");
                        $q .= " checked = checked";
                    }
                    $q .= "/>$proxy_num</div>";
                }

                if ($question_num == "q13") {
                    $q .= "<textarea $readonly class=\"form-control $grey\" id=\"$q_label\" name=\"$q_label\" rows=\"5\">$q13_other</textarea>";
                }

            }

            break;
        case "yesno":
            //handle the yesno formats

            /**
             * <div class="custom-control custom-radio">
            <input type="radio" id="customRadio1" name="customRadio" class="custom-control-input">
            <label class="custom-control-label" for="customRadio1">Boots</label>
            </div>
            <div class="custom-control custom-radio">
            <input type="radio" id="customRadio2" name="customRadio" class="custom-control-input">
            <label class="custom-control-label" for="customRadio2">Shoes</label>
            </div>
             *
             */


            //get the choice enumeration from the metadata
            $coded = getFieldChoiceList($question_num);

            $re = '/^(?<prefix>q\d*)_(?<part1>\w*)_*(?<part2>\w*)/m';
            preg_match_all($re, $question_num, $matches, PREG_SET_ORDER, 0);

            $prefix = $matches[0]['prefix'];

            if ($prefix == "q8") {
                $question_num = $prefix;
                $part1 =  $response['part1'];
                    $part2 =  $response['part2'];

                    $q .= '<div class="">';
                    $i=1;

                    foreach ($coded as $code => $proxy_num) {
//                $r .= "<div class=\"btn-group\" data-toggle=\"buttons\">";
//                $r .= "<label class=\"btn btn-primary\">";
//                $r .="<input type=\"radio\" name=\"options\" id=\"option1\"> Option 1";
//                $r .= "</label>";
//                $r .= "<input name=" . $q_label . " value=" . $i . " $disabled class=\"form-check-input\" ";
//
//                $r .="/>$proxy_num</div>";

                        $q .= "<div class=\"form-control $grey\">";
                        $q .= "<input name=" . $q_label . " value=" . $i . " $disabled class=\"form-check-input\" ";
                        $i--;

                        $q .= " type=\"radio\"";
                        if ((isset($part1)) && ($part1 == $code) ) {
                            //LetterProject::log($response ."_". $code, "IN YESNO/RADIO");
                            $q .= " checked = checked";
                        }
                        $q .="/>$proxy_num</div>";
                    }
                    $inst_label = $q_label."_inst";
                    $q .= '</div>';
                    $q .= '<div class="">';
                    $q .= "<textarea $readonly class=\"form-control $grey\" id=\"$inst_label\" name=\"$inst_label\" rows=\"5\">$part2</textarea>";
                    $q .= '</div>';
            } else {

                $i = 1;
                foreach ($coded as $code => $proxy_num) {
                    $q .= "<div class=\"form-control $grey\">";
                    $q .= "<input name=" . $q_label . " value=" . $i . " $disabled class=\"form-check-input\" ";

                    //yesno decrements since 1=yes, 0=no
                    $i--;

                    $q .= " type=\"radio\"";
                    if ((isset($response)) && ($response == $code)) {
                        //LetterProject::log($response ."_". $code, "IN YESNO/RADIO");
                        $q .= " checked = checked";
                    }
                    $q .= "/>$proxy_num</div>";
                }
            }
            break;
        default:
            //what is default?  treat like text area
            $q .= "<textarea $readonly class=\"form-control $grey\" id=\"$q_label\" name=\"$q_label\" rows=\"5\">$response</textarea>";
            break;
            break;

    }
    return $q;
}


function renderNavButtons($previous,$next, $submit, $print_page = null) {
    $str = '<div class="mb-3 group-end">';
    if ($previous) {
        $str .= '<a class="btn btn-primary btnPrevious">Previous</a>';
    }
    if ($next) {
        $str .= '<a class="btn btn-primary btnNext">Next</a>';
    }
    if ($submit) {
        $str .= '<a class="btn btn-primary btnWitness">Review Witness and Signature Form</a>';
    }
    if ($print_page) {
        $str .= '<div><a class="btn btn-primary btnPrint">Go to Print Page</a></div>';
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
 *1
 * @return string - HTML string
 */
function renderAnswers($question_num, $orig, $p1, $p2, $p3, $final, $proxy) {
    global $module;
    //what is last question field
    $questions = $module->getProjectSetting('questions');
    $last_question = end(array_values($questions));

    $fields = array(
        'What you said'=>$orig,
        'What '.$proxy[$module->getProjectSetting("proxy-1-field")].' said'=>$p1,
        'What '.$proxy[$module->getProjectSetting("proxy-2-field")].' said'=>$p2,
        'What '.$proxy[$module->getProjectSetting("proxy-3-field")].' said'=>$p3);
    global $Proj;
    $metadata = $Proj->metadata;
    $field_type = $metadata[$question_num]['element_type'];
    //LetterProject::log($field_type, "===============FIELD_TYPE for $question_num");


    $q = "<div class=\"col-lg-12\">";

    foreach ($fields as $label => $response) {
        //$module->emLog("Question num: $question_num / LABEL : $label / RESPONSE : ", $response);

        if (! isset($response)) {
            continue;
        }


        //helper fields for labeling
        $q_label = $question_num . "_" . $label;  //i.e. q7_proxy_1
        $final_label = $question_num . "_final";  //i.e. q7_final

        $q .= "<div class=\"input-group\">";
        $q .= '<div class="input-group-prepend">';
        $q .= '<div class="input-group-text">' . $label . '</div>';

        $q .= "<div class=\"form-group greyed\">";
        $q .= formatInputFields($question_num, $label, $field_type, $response, false);
        $q .= "</div>";
        $q .= "</div>";
        $q .= "</div>";
    }

    $q .= "<br><h4>Final Version</h4>";

    $q .= "<div class=\"form-group\">";

    $q .= formatInputFields($question_num, 'final', $field_type, $final, true);

    $q.="</div>";


    if ($question_num == $last_question) {
        $q .= renderNavButtons(true, false, true, true);
    } else {
        $q .= renderNavButtons(true, true, false);
    }

    $q .= "</div>";

    return $q;


}

function setupLetterPDFGenerator($record_id) {
     global $module;

    set_time_limit(0);

    //$pdf = new LetterPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT,true, 'UTF-8', false);

    $pdf = new LetterPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, false, 'ISO-8859-1', false);

    // Set document information dictionary in unicode mode
    $pdf->SetDocInfoUnicode(true);

    $module->emDebug("LOGO", PDF_HEADER_LOGO);
    $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, 'Stanford What-Matters-Most Letter Directive',null,  array(150,43,40));

    // set header and footer fonts
    $pdf->setHeaderFont(Array('times', '', 14));
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    $pdf->SetFont('arial', '', 12);
    $pdf->SetAutoPageBreak(TRUE,PDF_MARGIN_BOTTOM);

    // set image scale factor
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

    // ---------------------------------------------------------
    //if record ID is set, get Data for that record

    // Use alternative passing of parameters as an associate array
    $params = array(
        'project_id'=>$module->getProjectId(),
        'return_format' =>'json',
        //'exportSurveyFields'=>true,
        //'fields'=>array('dob','record_id'),
        'events'=>array( $module->getProjectSetting('final-event')),
        'records'=>array($record_id));
    $data = REDCap::getData($params);

    //$q = \REDCap::getData($module->getProjectId(), 'json',  array($record_id), null, $module->getProjectSetting('final-event'));
    $final_data = json_decode($data, true);
    //$module->emDebug($params,$module->getProjectId(),$module->getProjectSetting('final-event'), $final_data, $record_id, "FINAL DATA");

    // ---------------------------------------------------------

    // set font
    //$pdf->SetFont('arial', '', 12);

    // add a page
    $pdf->AddPage();

    //create html for page 1
    $html = $pdf->makeHTMLPage1($record_id, current($final_data));
    $pdf->writeHTML($html, true, false, true, false, '');

    //create html for page 2
    $html = $pdf->makeHTMLPage2($record_id);
    $pdf->writeHTML($html, true, false, true, false, '');

    return $pdf;
}

function sendEmailPDF($record_id, $emails) {
    global $module;


    //1. generate the PDF file
    if (isset($record_id)) {
        $pdf = setupLetterPDFGenerator($record_id);
    } else {
        throw new Exception("Email was not sent as the record field is undefined.");
    }

    //2. TODO: setup the name to be Letter with timestamp
    $letter_attachment = $pdf->Output('LetterProject.pdf', 'E');

    //3. foreach email
    if (isset($emails)) {

        $status = array();
        foreach ($emails as $email) {
            $to = $email;
            $from = 'noreply@stanford.edu';
            $subject = "Stanford Letter Project";
            $msg = 'Attached please find a copy of your letter.<br><br>
               --Stanford Letter Project';
            $status[] = $module->sendEmail($to, $from, $subject, $msg, $letter_attachment);
        }

    } else {
        throw new Exception("Email was not sent as the email field is undefined.");
    }


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
