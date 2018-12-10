<?php
/**
 * Created by PhpStorm.
 * User: jael
 * Date: 1/9/18
 * Time: 8:51 AM
 */

namespace Stanford\LetterProject;


use \REDCap as REDCap;
require("RestCallRequest.php");
//define('API_URL','https://redcap.stanford.edu/api/');
define('API_URL','http://127.0.0.1/api/');

class LetterProject extends \ExternalModules\AbstractExternalModule
{

    public static $config;


    function hook_survey_complete($project_id, $record = NULL, $instrument, $event_id, $group_id, $survey_hash, $response_id, $repeat_instance)
    {
        $this->emDebug("Starting Hook Survey Complete", $instrument);

        //if this is the final event,
        if ($event_id == $this->getProjectSetting('final-event') &&
            ($instrument == $this->getProjectSetting('witness-survey'))) {

            $this->emDebug("Final EVENT: this is the right instrument: $instrument and event: $event_id");
            $event_name = REDCap::getEventNames(true, false, $event_id);

            $get_fields = array($this->getProjectSetting('hash-url'),
                $this->getProjectSetting('code-field'));
            $this->emDebug($get_fields);
            //lookup the hash for the record id and redirect to the print page.
            $q = REDCap::getData('array', $record, $get_fields, $this->getProjectSetting('first-event'));
            $hash_url = $q[$record][$this->getProjectSetting('first-event')][$this->getProjectSetting('hash-url')];
            $email_code = $q[$record][$this->getProjectSetting('first-event')][$this->getProjectSetting('code-field')];


            $this->emDebug($q, $hash_url, "HASH_URL");
            ?>
            <style>
                #pagecontainer {
                    display: none;
                }
            </style>
            <form id="survey_complete" method="POST" action="<?php echo $hash_url ?>">
                <input type="hidden" name="code" value="<?php echo $email_code ?>"/>
                <input type="hidden" name="login" value="1"/>
                <input type="hidden" name="print_page" value="1"/>

            </form>
            <script>
                $('#survey_complete').submit();
                $(document).ready(function () {
                    $('.nav-tabs a:last').tab('show');
                });
            </script>
            <?php

        }

    }

    function hook_save_record($project_id, $record = NULL, $instrument, $event_id, $group_id = NULL, $survey_hash = NULL, $response_id = NULL, $repeat_instance = 1)
    {
        //if this is the original letter in , then save it to the final letter
        if ($event_id = $this->getProjectSetting('first-event')) {

            if ($instrument == $this->getProjectSetting('starting-survey')) {

                //set the Hash in the first form
                $this->emDebug("FIRST EVENT: STARTING SURVEY  SET HASH : $instrument and event: $event_id");
                $this->setHash($project_id, $record, $instrument);

            } else {

                $this->emDebug("FIRST EVENT: COPYING OVER SURVEY: $instrument and event: $event_id");
                $this->copyToFinal($project_id, $record, $instrument, $event_id);

            }

        }

    }

    /**
     *
     * Only copies if the form is not completed.
     * @param $project_id
     * @param $record
     * @param $instrument
     * @param $event_id
     */
    public function copyToFinal($project_id, $record, $instrument, $event_id)
    {
        $form_complete_field = $instrument . "_complete";

        //get data from final instrument
        //if final form is incomplete, move over data from the first form.
        $q = REDCap::getData(
            'json',
            $record,
            array($form_complete_field),
            $this->getProjectSetting('final-event'));
        $results = json_decode($q, true);
        $result = current($results);

        //if _complete = 0 and first survey = 2 then copy over data
        if ($result[$form_complete_field] == 0) {
            //incomplete form, transfer over the
            //get the data from the first event survey instrument
            $survey_fields = REDCap::getFieldNames($instrument);

            //get data from the first survey
            $f_q = REDCap::getData(
                'json',
                $record,
                $survey_fields,
                $this->getProjectSetting('first-event'));
            $f_results = json_decode($f_q, true);
            $f_result = current($f_results);
            //$this->emLog($f_result, "Current FIRST Record");

            if ($f_result[$form_complete_field] == 2) {
                $f_result[REDCap::getRecordIdField()] = $record;
                $redcap_event_name = REDCap::getEventNames(true, false, $this->getProjectSetting('final-event'));

                $f_result['redcap_event_name'] = $redcap_event_name;

                //first form is complete, do the migration
                $q = REDCap::saveData('json', json_encode(array($f_result)));
                //$this->emDebug($f_result, "xxCurrent FIRST Record", $q);
            }

        }
    }

    public function setHash($project_id, $record, $instrument)
    {
        $start_survey = $this->getProjectSetting('starting-survey');
        $this->emLog("Starting survey is " . $start_survey);

        // Set Record Hash and Hash Url
        if ($instrument == $start_survey) {
            // Check if hash exists
            $q = REDCap::getData(
                'json',
                $record,
                array(
                    REDCap::getRecordIdField(),
                    $this->getProjectSetting('hash'),
                    $this->getProjectSetting('hash-url')
                ),
                $this->getProjectSetting('first-event')
            //LetterProject::$config['first_event']
            );
            $results = json_decode($q, true);
            $result = current($results);

            $this->emLog($result, "DEBUG", "Current Record");

            $hash = isset($result[$this->getProjectSetting('hash')]) ? $result[$this->getProjectSetting('hash')] : '';
            $hash_url = isset($result[$this->getProjectSetting('hash-url')]) ? $result[$this->getProjectSetting('hash-url')] : '';
            $this->emDebug($hash, "Current Hash");
            $this->emDebug($hash_url, "Current Hash Url");
            if (empty($hash)) {
                // Generate a unique hash for this project
                $new_hash = generateRandomHash(8, false, TRUE, false);
                $api_url = $this->getUrl('ProxyLetterReconciliation.php', true, true);
                $new_hash_url = $api_url . "&e=" . $new_hash;
                $this->emDebug($new_hash_url, "New Hash ()");

                // Save it to the record (both as hash and hash_url for piping)
                $result[$this->getProjectSetting('hash')] = $new_hash;
                $result[$this->getProjectSetting('hash-url')] = $new_hash_url;
                $response = REDCap::saveData('json', json_encode(array($result)));
                $this->emDebug($record, ": Set unique Hash Url to $new_hash_url with result " . json_encode($response));
            } else {
                $this->emDebug($hash, $record . " has an existing hash url");
            }
        } else {
            $this->emDebug("No Match", "DEBUG");
        }
    }

    public function getResponseData($id, $select = null, $event = null)
    {
        //       $this->emLog($id, "ID");
//        $this->emLog($event, "EVENT");
        // get  responses for this ID and event
        $q = REDCap::getData('array', $id, $select, $event);
        //$q = REDCap::getData('array', $id);

        //$results = json_decode($q, true);

        //$this->emLog($q, "RESULT");
        if (count($q) == 0) {
            $this->emError($q, "Unable to get responses for $id in event $event", "ERROR");
            return false;
        }

        //$this->emLog($q, "DEBUG", "RESPONSE RESULT");
        //$responses =  $results[0];
        return $q;

    }


    public function getFileData($record) {
        $this->emDebug("THIS IS EDOC_PATH". EDOC_PATH);
        $this->emDebug("THIS IS EDOC_PATH". $edoc_path);

        # Get current file fields (we don't want to include file fields that were deleted from the dictionary)
        $file_fields = array();
        foreach (REDCap::getFieldNames() as $field) {
            if (REDCap::getFieldType($field) == 'file') $file_fields[] = $field;
        }
        if (!empty($file_fields)) {
            $this->emDebug("No fields of type 'field' in this project.");
        }
        $this->emDebug($file_fields);

        # Get doc_id's data for file fields
        $docs = array();
        $file_data = REDCap::getData('array',$record,$file_fields);
        foreach ($file_data as $id=>$record) {
            foreach($record as $event=>$fields) {
                foreach ($fields as $field_name=>$doc_id) {
                    if (!empty($doc_id)) {
                        $docs[$doc_id] = array('pid'=>$project_id, 'record'=>$id, 'field_name'=>$field_name);
                        if (REDCap::isLongitudinal()) {
                            $docs[$doc_id]['event_id'] = $event;
                            $docs[$doc_id]['event_name'] = REDCap::getEventNames(false,false,$event);
                            $docs[$doc_id]['unique_event_name'] = REDCap::getEventNames(true,false,$event);
                        }
                    }
                }
            }
        }

        $this->emDebug($file_data);
        $this->emDebug($docs);

        return $docs;
    }

    public function uploadFileData($record, $docs, $event) {
        $temp_file = "../../temp/copy_file.tmp";
        file_put_contents($temp_file, ' foo bar');

        //iterate throught the efiles for this record and upload them into the same field name for the passed in event
        foreach ($docs as $docnum => $deets) {


            $data = array();
            //check if the right record
            if ($deets['record'] != $record) {
                $this->emDebug("Wrong RECORD!". $deets['record'] . ' vs ' . $record);
                continue;
            }

            $event_name = REDCap::getEventNames(true, false, $event);
            $this->emDebug($deets,"moving ".$deets['field_name'] . " from ". $deets['unique_event_name'] . " to " . $event_name);

            $data = array(
                'token' => $this->getProjectSetting('api-token'),
                'content' => 'file',
                'action' => 'export',
                'record' => $record,
                'field' => $deets['field_name'],
                'event' => $deets['unique_event_name'],
                'returnFormat' => 'json'
            );

            $request = new RestCallRequest(API_URL, 'POST', $data);
            $request->execute();
            $request_info = $request->getResponseInfo();
            $this->emDebug($request_info);

        $content_type = $request_info['content_type'];
        //print "<pre>content_type:" . print_r($content_type,true) . "</pre>";
        $content_type = explode(";", $content_type);
        $mime = $content_type[0];
        $name_raw = $content_type[1];
        $re = "/.*\\\"(.*)\\\"/";
        preg_match($re, $name_raw, $matches);
        //print "<pre>Matches: " . print_r($matches,true). "</pre>";
        $name_original = isset($matches[1]) ? $matches[1] : "file";

        file_put_contents($temp_file, $request->getResponseBody());
        $this->emDebug($name_original, $mime);

        $curlFile = (function_exists('curl_file_create') ? curl_file_create($temp_file, $mime,  $name_original) : "@$temp_file");

$fields = array(
    'token' => $this->getProjectSetting('api-token'),
    'content' => 'file',
    'action' => 'import',
    'record' => $record,
    'field' => $deets['field_name'],
    'event' => $event_name,
    'file' => $curlFile,
    'returnFormat' => 'json'
);

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, API_URL);
curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // Set to TRUE for production use
curl_setopt($ch, CURLOPT_VERBOSE, 0);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_AUTOREFERER, true);
curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);

$output = curl_exec($ch);

curl_close($ch);

            $this->emDebug($output);


            $save_data = array(
              REDCap::getRecordIdField() => $record,
                'redcap_event_name'      => $event_name,
                $deets['field_name']     => 'foo'
            );
        }


    }



    public function sendEmail($to, $from, $subject, $msg, $attachment)
    {
        global $module;

        $module->emDebug("Send Email 5: in SendEmail ");
        //boundary
        $semi_rand = md5(time());
        $mime_boundary = "==Multipart_Boundary_x{$semi_rand}x";


        //headers for attachment
        //header for sender info
        $headers = "From: "." <".$from.">";
        $headers .= "\nMIME-Version: 1.0\n" . "Content-Type: multipart/mixed;\n" . " boundary=\"{$mime_boundary}\"";

        $module->emDebug("Send Email 6: Created header ");
        //multipart boundary
        $message = "--{$mime_boundary}\n" . "Content-Type: text/html; charset=\"UTF-8\"\n" .
            "Content-Transfer-Encoding: 7bit\n\n" . $msg . "\n\n";
        $message .= "--{$mime_boundary}\n";
        $message .= $attachment;
        $message .= "--{$mime_boundary}--";

        $module->emDebug("Send Email 7: Created multipart ");

        if (!mail($to, $subject, $message, $headers)) {
            $module->emDebug("Email NOT sent");
            return false;
        }
        $module->emDebug("Send Email 7: Email sent");
        return true;
    }


    /**
     * Read the current config from a single key-value pair in the external module settings table
     */
    function getConfigAsString()
    {
        $string_config = $this->getProjectSetting($this->PREFIX . '-config');
        // SurveyDashboard::log($string_config);
        return $string_config;
    }

    function setConfigAsString($string_config)
    {
        $this->setProjectSetting($this->PREFIX . '-config', $string_config);
    }


    public function lookupByCode($code)
    {
        $event_id = $this->getProjectSetting('first-event');
        $event = REDCap::getEventNames(true, false, $event_id);
        $code_field = $this->getProjectSetting('code-field');

        $filter = "[" . $event . "][" . $code_field . "] = '$code'";
        $get_fields = array(REDCap::getRecordIdField());
        $this->emDebug( "FILTER:".$filter);

        //passing in project_id causes getData to fail
        $q = REDCap::getData('json', NULL, $get_fields, $event,
            NULL, FALSE, FALSE, FALSE, $filter);
        $results = json_decode($q, true);

        if (count($results) > 1) {
            // There is a duplicate record in the table
            $participants = array();
            foreach ($results as $result) $participants[] = $result[REDCap::getRecordIdField()];
            $msg = "Warning: more than 1 participant is using the login code $code: " . implode(", ", $participants) . "\n" .
                "When they log in they are using the 'first' record so you should be able to delete the second instance after confirming there is no assessment data";
            REDCap::email($this->getProjectSetting('project-admin-email'), $this->getProjectSetting('project-from-email'), $this->getProjectSetting('project-title'), $msg);
        }

        // If the code does not match return false
        if (count($results) == 0) {
            return false;
        }

        // Take the first match (in case there are more than 1)
        $result = $results[0];
        return $result[REDCap::getRecordIdField()];
    }



function setupLetter($record_id) {
    global $module;

    set_time_limit(0);

    //$pdf = new LetterPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT,true, 'UTF-8', false);

    $pdf = new LetterPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, false, 'ISO-8859-1', false);

    // Set document information dictionary in unicode mode
    $pdf->SetDocInfoUnicode(true);

    //$module->emDebug("LOGO", PDF_HEADER_LOGO);
    $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, 'Stanford What-Matters-Most Letter Directive', null, array(150, 43, 40));

    // set header and footer fonts
    $pdf->setHeaderFont(Array('times', '', 14));
    $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    $pdf->SetFont('arial', '', 12);
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

    // set image scale factor
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

    // ---------------------------------------------------------
    //if record ID is set, get Data for that record

    // Use alternative passing of parameters as an associate array
    $params = array(
        'project_id' => $module->getProjectId(),
        'return_format' => 'json',
        //'exportSurveyFields'=>true,
        //'fields'=>array('dob','record_id'),
        'events' => array($module->getProjectSetting('final-event')),
        'records' => array($record_id));
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
    $pdf->AddPage();
    $html = $pdf->makeHTMLPage2($record_id,current($final_data));
    $pdf->writeHTML($html, true, false, true, false, '');

    //Question 6
    $q6 = current($final_data)['q6'];
    $pdf->RadioButton('health_decisions', 5, array(), array(), '1', ($q6 == 1));
    $pdf->Cell(35, 5, 'Starting right now');
    $pdf->Ln(6);
    $pdf->RadioButton('health_decisions', 5, array(), array(), '2', $q6 == 2);
    $pdf->Cell(35, 5, 'When I am not able to make decisions by myself');
    $pdf->Ln(6);

    //create html for page 3
    $pdf->AddPage();
    $pdf = $pdf->makeHTMLPage3($record_id,current($final_data), $pdf);

    //create html for page 4
    $pdf->AddPage();
    $pdf = $pdf->makeHTMLPage4($record_id,current($final_data), $pdf);

    //create html for page 5
    $pdf->AddPage();
    $html5 = $pdf->makeHTMLPage5($record_id,current($final_data), $pdf);
    $pdf->writeHTML($html5, true, false, true, false, '');

    //create html for page 6n
    $pdf->AddPage();
    $html6 = $pdf->makeHTMLPage6($record_id,current($final_data), $pdf);
    $pdf->writeHTML($html6, true, false, true, false, '');


    //create html for page 7
    $pdf->AddPage();
    $html7 = $pdf->makeHTMLPage7($record_id,current($final_data), $pdf);
    $pdf->writeHTML($html7, true, false, true, false, '');

    return $pdf;
}

    public static function getSessionMessage()
    {
        if (isset($_SESSION['msg']) && !empty($_SESSION['msg'])) {
            $html = "
            <div id='session-message' class='alert alert-info text-center in' data-dismiss='alert'>
                <p><strong>" . $_SESSION['msg'] . "</strong></p>
            </div>
        ";
            unset($_SESSION['msg']);
        } else {
            $html = '';
        }
        return $html;
    }

    /**
     *
     * emLogging integration
     *
     */
    function emLog()
    {
        $emLogger = \ExternalModules\ExternalModules::getModuleInstance('em_logger');
        $emLogger->emLog($this->PREFIX, func_get_args(), "INFO");
    }

    function emDebug()
    {
        // Check if debug enabled
        if ($this->getSystemSetting('enable-system-debug-logging') || (!empty($_GET['pid']) && $this->getProjectSetting('enable-project-debug-logging'))) {
            $emLogger = \ExternalModules\ExternalModules::getModuleInstance('em_logger');
            $emLogger->emLog($this->PREFIX, func_get_args(), "DEBUG");
        }
    }

    function emError()
    {
        $emLogger = \ExternalModules\ExternalModules::getModuleInstance('em_logger');
        $emLogger->emLog($this->PREFIX, func_get_args(), "ERROR");
    }
}