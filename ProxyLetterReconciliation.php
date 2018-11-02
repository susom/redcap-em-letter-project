<?php
namespace Stanford\LetterProject;

/** @var \Stanford\LetterProject\LetterProject $module */

use \REDCap as REDCap;
use Stanford\SurveyDashboard\Participant;

// Start the session
session_start();

//HARD CODING IT FOR TEST
//$record = 5;
//$name = "Zaphod Beeblebrox ";
//$doctor_name = 'Doctor Who';

$image_url = $module->getUrl("images/stanford_med.png",true,true );

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

        $module->emDEbug($data, "DATA from12REQUEST");

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

    header('Content-Type: application/json');
    print json_encode($result);
    exit();

}

// HANDLE A LOGIN
while (isset($_POST['login'])) {
    $email_code = $_GET['e'];
    $code = $_POST['code'];

    $module->emLog("$code is $code and email is $email_code");

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


    $module->emLog($verify_data, "VERIFY DATA for $code with event $first_event_id and participant id $participant_id ".$module->getProjectSetting('hash'));


    $v_email_code = $verify_data[$participant_id][$first_event_id][$module->getProjectSetting('hash')];
    $module->emLog("V_EMAIL_CODE : ".$v_email_code . " vs " . $email_code);
    if (!($email_code === $v_email_code)) {
        $_SESSION['msg'] = "Your code does not match the response expected from your link. Please use the link from your email.";
        break;
    }

    $record = $participant_id;
    $name = $verify_data[$participant_id][$first_event_id]['name'];
    $doctor_name = $verify_data[$participant_id][$first_event_id]['ltr_doctor_name'];


    //display the tabbed questions
    include("pages/reconciliation.php");
    // Redirect to the reconciliation
    //header('location: ' . $link);
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
        $module->emLog("$event_name is the name for this id $event_id");

    }
     $event_array = array(\REDCap::getEventNames(true, false, $first_event)=>$first_event) + $event_array;
    //$first_event_array[\REDCap::getEventNames(true, false, $first_event)]=$first_event;
    //array_unshift($event_array, $first_event_array);
    $event_array[\REDCap::getEventNames(true, false, $last_event)] = $last_event;
    $module->emLog($event_array, "EVENT ARRAY");

    //iterate over the question list from the config
    foreach ($questions as $num => $question) {

        $re = '/^(?<prefix>q\d*)_(?<part1>\w*)_*(?<part2>\w*)/m';
        preg_match_all($re, $question, $matches, PREG_SET_ORDER, 0);

        $prefix = $matches[0]['prefix'];
        $part1 =  $matches[0]['part1'];
        $part2 =  $matches[0]['part2'];

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
            } elseif ($prefix == 'q7') {
                $reorganized[$question][$event_name]  = array('part1' => $responses[$event_id][$prefix . '_'. $part1],
                     'part2' => $responses[$event_id][$prefix . '_' . $part1 . '_inst']);
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

        $tabs[] = "<li><a data-toggle='tab' id='tab-{$key}' data-index='{" . $index++ .
            "' data-key='{$key}' href='#{$key}' title='" . $key .
            "'>" . strtoupper($key) . "</a></li>";
    }
    //LetterProject::log(implode($tabs), "TABS");
    print implode("", $tabs);
}

function renderTabDivs($record) {
    global $module;

    $questions = $module->getProjectSetting('questions'); //LetterProject::$config['questions'];
    $responses = organizeResponses($record);

    global $Proj;
    $metadata = $Proj->metadata;

    $divs = array();
    $divs[] = "
                <div id='home' class='tab-pane active in'>
                    <!--p>Home</p-->
                    <div class=\"jumbotron text-center\">
                    <div id='home_line_chart'>this is the home page. Perhaps some instructions here?</div>
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
                $responses[$question_num]['final_arm_1']);
        $str .= "<div id='" . $question_num . "_table'></div>
                </div>";

        $divs[] = $str;
    }
    print implode("", $divs);
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
    $disabled = $editable ? "" : "disabled";

    $q = '';

    //helper fields for labeling
    $q_label = $question_num . "_" . $proxy_num;  //i.e. q7_proxy_1
    $final_label = $question_num . "_final";  //i.e. q7_final

    switch ($field_type) {
        case "textarea":
            //handle the textarea formats
            $q .= "<textarea $readonly class=\"form-control\" id=\"$q_label\" name=\"$q_label\" rows=\"5\">$response</textarea>";
            break;
        case "descriptive":
            //handle the descriptive formats
            //Q2 is an odd ball case where there is 4 text boxes after the descriptive
            //let's just check just in case more get added
            if ($question_num == "q2") {
                //$module->emLog($response, "RESPONSE FOR $proxy_num");

                //put each field in a separate text field
                $i=1;
                foreach ($response as $proxy_name) {
                    $q .= "<div class=\"form-control\">";
                    $q .= "$i.  <input name=" . $q_label . "_" . $i . " $readonly value=\"$proxy_name\" class=\"form-check-input\" type=\"text\"" . "/></div>";
                    $i++;
                }
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


                    $q .= "<div class=\"form-control\">";
                    $q .= '<div class="col-md-2">';
                    $q .= '<h5>Decision Maker</h5>';
                    $q .= '<h4>#1</h4>';
                    $q .= '<h4>#2</h4>';
                    $q .= '<h4>#3</h4>';
                    $q .= '</div>';

                    $q .= '<div class="col-md-2">';
                    $q .= '<label class="control-label" for="date">Name</label>';
                    $q .= '<input class="form-control form-check-input" id="'.$q_label.'_name_decision_1'.'" name="'.$q_label.'_name_decision_1'.'"  value="'.$response[1]['name_decision'].'" type="text">';
                    $q .= '<input class="form-control form-check-input" id="'.$q_label.'_name_decision_2'.'" name="'.$q_label.'_name_decision_2'.'"  value="'.$response[2]['name_decision'].'" type="text">';
                    $q .= '<input class="form-control form-check-input" id="'.$q_label.'_name_decision_3'.'" name="'.$q_label.'_name_decision_3'.'"  value="'.$response[3]['name_decision'].'" type="text">';
                    $q .= '</div>';


                    $q .= '<div class="col-md-2">';
                    $q .= '<label class="control-label" for="date">Relationship</label>';
                    $q .= '<input class="form-control form-check-input" id="'.$q_label.'_relationship_decision_1'.'" name="'.$q_label.'_relationship_decision_1'.'"  value="'.$response[1]['relationship_decision'].'" type="text">';
                    $q .= '<input class="form-control form-check-input" id="'.$q_label.'_relationship_decision_2'.'" name="'.$q_label.'_relationship_decision_2'.'"  value="'.$response[2]['relationship_decision'].'" type="text">';
                    $q .= '<input class="form-control form-check-input" id="'.$q_label.'_relationship_decision_3'.'" name="'.$q_label.'_relationship_decision_3'.'"  value="'.$response[3]['relationship_decision'].'" type="text">';
                    $q .= '</div>';

                    $q .= '<div class="col-md-2">';
                    $q .= '<label class="control-label" for="date">Address</label>';
                    $q .= '<input class="form-control form-check-input" id="'.$q_label.'_address_decision_1'.'" name="'.$q_label.'_address_decision_1'.'"  value="'.$response[1]['address_decision'].'" type="text">';
                    $q .= '<input class="form-control form-check-input" id="'.$q_label.'_address_decision_2'.'" name="'.$q_label.'_address_decision_2'.'"  value="'.$response[2]['address_decision'].'" type="text">';
                    $q .= '<input class="form-control form-check-input" id="'.$q_label.'_address_decision_3'.'" name="'.$q_label.'_address_decision_3'.'"  value="'.$response[3]['address_decision'].'" type="text">';
                    $q .= '</div>';


                    $q .= '<div class="col-md-2">';
                    $q .= '<label class="control-label" for="date">City, State, Zip Code</label>';
                    $q .= '<input class="form-control form-check-input" id="'.$q_label.'_city_decision_1'.'" name="'.$q_label.'_city_decision_1'.'"  value="'.$response[1]['city_decision'].'" type="text">';
                    $q .= '<input class="form-control form-check-input" id="'.$q_label.'_city_decision_2'.'" name="'.$q_label.'_city_decision_2'.'"  value="'.$response[2]['city_decision'].'" type="text">';
                    $q .= '<input class="form-control form-check-input" id="'.$q_label.'_city_decision_3'.'" name="'.$q_label.'_city_decision_3'.'"  value="'.$response[3]['city_decision'].'" type="text">';
                    $q .= '</div>';


                    $q .= '<div class="col-md-2">';
                    $q .= '<label class="control-label" for="date">Phone Numbers</label>';
                    $q .= '<input class="form-control form-check-input" id="'.$q_label.'_phone_decision_1'.'" name="'.$q_label.'_phone_decision_1'.'"  value="'.$response[1]['phone_decision'].'" type="text">';
                    $q .= '<input class="form-control form-check-input" id="'.$q_label.'_phone_decision_2'.'" name="'.$q_label.'_phone_decision_2'.'"  value="'.$response[2]['phone_decision'].'" type="text">';
                    $q .= '<input class="form-control form-check-input" id="'.$q_label.'_phone_decision_3'.'" name="'.$q_label.'_phone_decision_3'.'"  value="'.$response[3]['phone_decision'].'" type="text">';
                    $q .= '</div>';

                    $q .= '</div>';
                    $q .= '<div></div>';


                } else {
                    $i=1;
                    foreach ($response as $proxy_name=>$detail) {
                        $q .= '  <div class="border row">';
                        $q .=  '<div class=" border col-xs-2 col-sm-2 col-lg-2" >'. $i. ': '. $detail['name_decision'].'</div>';
                        $q .= ' <div class="col-xs-2 col-sm-2 col-lg-2" >'. $detail['relationship_decision'].'</div>';
                        $q .= ' <div class="col-xs-4 col-sm-4 col-lg-4" > '. $detail['address_decision'].'</div>';
                        $q .= '<div class="col-xs-2 col-sm-2 col-lg-2" > '. $detail['city_decision'].'</div>';
                        $q .= ' <div class="col-xs-2 col-sm-2 col-lg-2" > '. $detail['phone_decision'].'</div>';
                        $q .= ' </div>';
                        $i++;
                    }
                }
            }

            break;
        case "checkbox":
            //handle the checkbox formats

            //get the choice enumeration from the metadata
            $coded = getFieldChoiceList($question_num);

            $i=1;
            foreach ($coded as $code => $proxy_num) {
                $q .= "<div class=\"form-control\">";

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
                $part1 =  $response['part1'];
                $part2 =  $response['part2'];

                $q .= '<div class="col-md-2">';
                $i=1;

                foreach ($coded as $code => $proxy_num) {
//                $r .= "<div class=\"btn-group\" data-toggle=\"buttons\">";
//                $r .= "<label class=\"btn btn-primary\">";
//                $r .="<input type=\"radio\" name=\"options\" id=\"option1\"> Option 1";
//                $r .= "</label>";
//                $r .= "<input name=" . $q_label . " value=" . $i . " $disabled class=\"form-check-input\" ";
//
//                $r .="/>$proxy_num</div>";

                $q .= "<div class=\"form-control\">";
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
                $q .= '<div class="col-md-10">';
                $q .= "<textarea $readonly class=\"form-control\" id=\"$q_label\" name=\"$q_label\" rows=\"5\">$part2</textarea>";
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

                $i = 1;

                foreach ($coded as $code => $proxy_num) {
//                $r .= "<div class=\"btn-group\" data-toggle=\"buttons\">";
//                $r .= "<label class=\"btn btn-primary\">";
//                $r .="<input type=\"radio\" name=\"options\" id=\"option1\"> Option 1";
//                $r .= "</label>";
//                $r .= "<input name=" . $q_label . " value=" . $i . " $disabled class=\"form-check-input\" ";
//
//                $r .="/>$proxy_num</div>";

                    $q .= "<div class=\"form-control\">";
                    $q .= "<input name=" . $q_label . " value=" . $i . " $disabled class=\"form-check-input\" ";
                    $i++;

                    $q .= " type=\"radio\"";
                    if ((isset($response)) && ($response == $code)) {
                        //LetterProject::log($response ."_". $code, "IN YESNO/RADIO");
                        $q .= " checked = checked";
                    }
                    $q .= "/>$proxy_num</div>";
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


            $i=1;
            foreach ($coded as $code => $proxy_num) {
                $q .= "<div class=\"form-control\">";
                $q .= "<input name=" . $q_label . " value=" . $i . " $disabled class=\"form-check-input\" ";

                //yesno decrements since 1=yes, 0=no
                $i--;

                $q .= " type=\"radio\"";
                if ((isset($response)) && ($response == $code) ) {
                    //LetterProject::log($response ."_". $code, "IN YESNO/RADIO");
                    $q .= " checked = checked";
                }
                $q .="/>$proxy_num</div>";
            }
            break;
        default:
            //what is default?  treat like text area
            $q .= "<textarea $readonly class=\"form-control\" id=\"$q_label\" name=\"$q_label\" rows=\"5\">$response</textarea>";
            break;
            break;

    }
    return $q;
}


function renderNavButtons($previous,$next, $submit) {
    $str = '<div class="input-group mb-3 group-end">';
    if ($previous) {
        $str .= '<a class="btn btn-primary btnPrevious">Previous</a>';
    }
    if ($next) {
        $str .= '<a class="btn btn-primary btnNext">Next</a>';
    }
    if ($submit) {
        $str .= '<a class="btn btn-primary btnSubmit">Submit</a>';
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
function renderAnswers($question_num, $orig, $p1, $p2, $p3, $final) {
    global $module;
    //what is last question field
    $questions = $module->getProjectSetting('questions');
    $last_question = end(array_values($questions));


    $fields = array('original'=>$orig, 'proxy_1'=>$p1,'proxy_2'=>$p2, 'proxy_3'=>$p3);
    global $Proj;
    $metadata = $Proj->metadata;
    $field_type = $metadata[$question_num]['element_type'];
    //LetterProject::log($field_type, "===============FIELD_TYPE for $question_num");


    $q = "<div class=\"row\">
            <div class=\"col-lg-12\">";

    foreach ($fields as $label => $response) {
        //$module->emLog("Question num: $question_num / LABEL : $label / RESPONSE : ", $response);

        if (! isset($response)) {
            continue;
        }


        //helper fields for labeling
        $q_label = $question_num . "_" . $label;  //i.e. q7_proxy_1
        $final_label = $question_num . "_final";  //i.e. q7_final

        $q .= "<div class=\"input-group\">
               <span class=\"input-group-addon\">" . strtoupper($label) . "</span>";

        $q .= formatInputFields($question_num, $label, $field_type, $response, false);

        $q .= "</div>";
    }

    $q .= "<br><h4>Final Version</h4>";

  	$q .= "<div class=\"form-group\">";

    $q .= formatInputFields($question_num, 'final', $field_type, $final, true);
    //$q .="<textarea class=\"form-control\" id=\"$final_label\" name=\"$final_label\" rows=\"5\">$final</textarea>";
	$q.="</div>";

    //$q.="<div class=\"form-group\"> <!-- Submit button !-->";
	//$q .="<button class=\"btn btn-primary \" name=\"submit\" type=\"submit\">Submit</button>";
	//$q .="</div>";

    if ($question_num == $last_question) {
        $q .= renderNavButtons(true, false, true);
    } else {
        $q .= renderNavButtons(true, true, false);
    }

	$q .= "</div>";
    $q .= "</div>";

    return $q;


}

?>

<script>


</script>