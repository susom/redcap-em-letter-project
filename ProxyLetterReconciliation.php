<?php
namespace Stanford\LetterProject;

/** @var \Stanford\LetterProject\LetterProject $module */

use \REDCap as REDCap;

// Start the session
session_start();

//HARD CODING IT FOR TEST
//$record = 5;
//$name = "Zaphod Beeblebrox ";
//$doctor_name = 'Doctor Who';

// HANDLE AJAX POST REQUESTS
if (!empty($_POST['action'])) {
    $action = $_POST['action'];


    if ($action == 'saveResponse') {
        //persist current set of responses

        $data = array(
            //REDCap::getRecordIdField() => $_POST['record_id'],
            'redcap_event_name' => LetterProject::$config['final_event']
        );

        $data = array_merge($data, $_POST);
        unset($data['action']);

        //LetterProject::log($data, "DATA from12REQUEST");

        //overwrite to unselect the checkbox selections
        $q = REDCap::saveData('json', json_encode(array($data)), overwrite);
        if (count($q['errors']) > 0) {
            $msg = "Error saving response for ".$data['record_id']." in ".LetterProject::$config['final_event'];
            $module->emError($q, $msg, "ERROR");

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

//    echo "<pre>".print_r($_POST)."</pre>";
//    echo "<pre>".print_r($_GET)."</pre>";

    if (empty($email_code)) {
        $_SESSION['msg'] = "Please use the link from your email.";
        break;
    }

    if (empty($code)) {
        $_SESSION['msg'] = "You must supply a valid login code";
        break;
    }

   //Lookup the code and email_code
    $participant_id = LetterProject::lookupByCode($code);

    if ($participant_id == false) {
        $_SESSION['msg'] = "The supplied login code was not valid.<br><br>If you forgot your code, please contact "
        . LetterProject::$config['project_admin_email'] .".";
        break;
    }

    //verify that email_code matches
    $first_event = LetterProject::$config['first_event'];
    $verify_data = LetterProject::getResponseData($participant_id, array('name','ltr_doctor_name',LetterProject::$config['hash']), $first_event);
    $event_id = REDCap::getEventIdFromUniqueEvent($first_event);


    $v_email_code = $verify_data[$participant_id][$event_id][LetterProject::$config['hash']];
    if (!($email_code === $v_email_code)) {
        $_SESSION['msg'] = "Your code does not match the response expected from your link. Please use the link from your email.";
        break;
    }

    $record = $participant_id;
    $name = $verify_data[$participant_id][$event_id]['name'];
    $doctor_name = $verify_data[$participant_id][$event_id]['ltr_doctor_name'];


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
    //getData('array', $record) return
    $responses = LetterProject::getResponseData($record);
    $responses = $responses[$record];
    $reorganized = array();

    foreach (LetterProject::$config['events'] as $event => $label) {
        $event_array[$event] = REDCap::getEventIdFromUniqueEvent($event);
    }

    for ($i = 1; $i <= 10; $i++) {
        $question = 'q'.$i;

        foreach ($event_array as $event_name => $event_id) {
            //original and final arm have question prepended with 'q': i.e. q1, q2
            //proxy forms has it prepended with 'p_q': i.e. p_q1, p_q2, etc
            $prefix = (($event_name == 'original_arm_1') || ($event_name == 'final_arm_1'))? 'q' : 'p_q';

            //Question 5 is actually 3 questions that should be displayed together
            if ($i == 5) {
                $reorganized[$question][$event_name] = array($responses[$event_id][$prefix . $i.'_1'],
                    $responses[$event_id][$prefix . $i.'_2'],
                    $responses[$event_id][$prefix . $i.'_3']);
            } else {
                $reorganized[$question][$event_name] = $responses[$event_id][$prefix . $i];
            }
        }
    }
    //LetterProject::log($reorganized, "REORGANIZED"); exit;

    return $reorganized;
}



function renderTabs() {
    $tabs = array();
    $index = 2;

    $questions = LetterProject::$config['questions'];

    foreach ($questions as $key => $as) {

        $tabs[] = "<li><a data-toggle='tab' id='tab-{$key}' data-index='{" . $index++ .
            "' data-key='{$key}' href='#{$key}' title='" . $key .
            "'>" . strtoupper($key) . "</a></li>";
    }
    //LetterProject::log(implode($tabs), "TABS");
    print implode("", $tabs);
}

function renderTabDivs($record) {

    $questions = LetterProject::$config['questions'];
    $responses = organizeResponses($record);

    global $Proj;
    $metadata = $Proj->metadata;

    $divs = array();
    $divs[] = "
                <div id='home' class='tab-pane active in'>
                    <!--p>Home</p-->
                    <div id='home_line_chart'>this is the home page. Perhaps some instructions here?</div>
                </div>";

    foreach ($questions as $question_num => $val) {
        $meta_label = $metadata[$question_num]['element_label'];

        $divs[] = "<div id='" . $question_num . "' class='tab-pane fade in'>
                    <br><p>".$meta_label."</p>".
            renderAnswers($question_num, $responses[$question_num]['original_arm_1'],
                $responses[$question_num]['proxy_1_arm_1'],
                $responses[$question_num]['proxy_2_arm_1'],
                $responses[$question_num]['proxy_3_arm_1'],
                $responses[$question_num]['final_arm_1']).
            "<div id='" . $question_num . "_table'></div>
                </div>";
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

            //Q5 is an odd ball case where there is 5 text boxes after the descriptive
            //let's just check just in case more get added
            if ($question_num == "q5") {
                //LetterProject::log($response, "RESPONSE FOR $proxy_num");

                //put each field in a separate text field
                $i=1;
                foreach ($response as $proxy_name) {
                    $q .= "<div class=\"form-control\">";
                    $q .= "$i.  <input name=" . $q_label . "_" . $i . " $readonly value=\"$proxy_name\" class=\"form-check-input\" type=\"text\"" . "/></div>";
                    $i++;
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

            $i=1;
            foreach ($coded as $code => $proxy_num) {
                $q .= "<div class=\"form-control\">";
                $q .= "<input name=" . $q_label . " value=" . $i . " $disabled class=\"form-check-input\" ";
                $i++;

                $q .= " type=\"radio\"";
                if ((isset($response)) && ($response == $code) ) {
                    //LetterProject::log($response ."_". $code, "IN YESNO/RADIO");
                    $q .= " checked = checked";
                }
                $q .="/>$proxy_num</div>";
            }
            break;
        case "yesno":
            //handle the yesno formats

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
    $fields = array('original'=>$orig, 'proxy_1'=>$p1,'proxy_2'=>$p2, 'proxy_3'=>$p3);
    global $Proj;
    $metadata = $Proj->metadata;
    $field_type = $metadata[$question_num]['element_type'];
    //LetterProject::log($field_type, "===============FIELD_TYPE for $question_num");


    $q = "<div class=\"row\">
            <div class=\"col-lg-6\">";

    foreach ($fields as $label => $response) {
//        LetterProject::log($response, "RESPONSE");

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

    $q .= "<br>
  	<div class=\"form-group\"> 
		<label class=\"control-label \" for=\"final\">Final Version</label> ";
    $q .= formatInputFields($question_num, 'final', $field_type, $final, true);
    //$q .="<textarea class=\"form-control\" id=\"$final_label\" name=\"$final_label\" rows=\"5\">$final</textarea>";
	$q.="</div>
		<div class=\"form-group\"> <!-- Submit button !-->
		<button class=\"btn btn-primary \" name=\"submit\" type=\"submit\">Submit</button>
	</div>
  </div>
</div>";

    return $q;


}

?>

<script>


</script>