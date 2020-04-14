<?php

namespace Stanford\LetterProject;


use REDCap;
use Files;

class LetterProject extends \ExternalModules\AbstractExternalModule
{

    public static $config;



    function redcap_survey_page_top($project_id, $record, $instrument, $event_id, $group_id, $survey_hash, $response_id, $repeat_instance)
    {
        //request from VJ to enlarge resize font in survey top right corner
         //$this->emDebug("Starting redcap_survey_page_top", $instrument);

        ?>

        <style>

            #changeFont {
                font-size: 20px;
            }

            .increaseFont img {
                width: 40px;
            }

            .decreaseFont img {
                width: 40px;
            }

        </style>
        <?php

    }


    function redcap_survey_complete($project_id, $record = NULL, $instrument, $event_id, $group_id, $survey_hash, $response_id, $repeat_instance)
    {
        $this->emDebug("Starting Hook Survey Complete", $instrument);

        //if this is the final event, then assume we are coming in from the reconciliation form. redirect to portal.
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

            //$this->emDebug($q, $hash_url, $email_code,"REDIRECT to HASH_URL");

            ?>
            <style>
                #pagecontainer {
                    display: none;
                }
            </style>
            <form id="survey_complete" method="POST" action="<?php echo $hash_url ?>">
                <input type="hidden" name="code" value="<?php echo $email_code ?>"/>
                <input type="hidden" name="login" value="1"/>
                <input type="hidden" name="home" value="1"/>
            </form>
            <script>
                $('#survey_complete').submit();
                $(document).ready(function () {
                    $('.nav-tabs a[href="#home"]').tab('show');
                });
            </script>
            <?php
        }

    }

    function redcap_save_record($project_id, $record = NULL, $instrument, $event_id, $group_id = NULL, $survey_hash = NULL, $response_id = NULL, $repeat_instance = 1)
    {
        //if this is the original letter in , then save it to the final letter
        if ($event_id = $this->getProjectSetting('first-event')) {
            switch ($instrument) {
                case  $this->getProjectSetting('starting-survey'):  //Public survey - starting point
                    $this->emDebug("FIRST EVENT: STARTING SURVEY  SET HASH : $instrument and event: $event_id");
                    $this->setHash($project_id, $record, $instrument);
                    break;
                case $this->getProjectSetting('email-survey'): //DECISION MAKER pages
                    //march update: removing this complexity. just have three options
                    //if this is the email survey, iterate through and copy over the teh first three selected to the proxy fields.
                    //$this->emDebug("FIRST EVENT: STARTING SURVEY  SET HASH : $instrument and event: $event_id");
                    //$this->setProxyEmails($project_id, $record, $instrument);

                    //new change: if decision makers are added in the reconciliation page then need to update the list of proxies
                    $this->emDebug("FIRST EVENT: COPYING OVER SURVEY: $instrument and event: $event_id");
                    $this->copyToFinal($project_id, $record, $instrument, $event_id);
                    break;

                case $this->getProjectSetting('letter-survey'):
                    $this->emDebug("FIRST EVENT: COPYING OVER SURVEY: $instrument and event: $event_id");
                    $this->copyToFinal($project_id, $record, $instrument, $event_id);
                    break;
                case $this->getProjectSetting('witness-survey'):

                    $this->emDebug("FIRST EVENT: COPYING OVER SURVEY: $instrument and event: $event_id");
                    $this->copyToFinal($project_id, $record, $instrument, $event_id);


                    //get the edoc for these files
                    //get the signature fields
                    # Get current file fields (we don't want to include file fields that were deleted from the dictionary)

                //TODO: confirm that signature fields are now copied over with a regular get and saveData??
                    //just hardcoding the signature fields.
                    $file_fields  = array('patient_signature', 'adult_signature', 'witness1_signature',
                        'witness2_signature','declaration_signature', 'specialwitness_signature');
                    $sig_status = $this->copyOverSigFields($project_id, $record, $file_fields, $event_id);
            }
        }
    }

    /**
     * Copy over the signature field from the passed in event
     *
     * @param $project_id
     * @param $record
     * @param $file_fields
     * @param $event_id
     * @return bool|\mysqli_result
     */
    public function copyOverSigFields($project_id, $record, $file_fields, $event_id) {
        $final_event = $this->getProjectSetting('final-event');

        $sig_status = true;

        # Get doc_ids data for file fields
        $docs = array();

        $params = array(
            'return_format'=>'array',
            'fields'=>$file_fields,
            'records'=>array($record),
            'events'=>$event_id);
        $file_data = REDCap::getData($params);

        $sig_fields = $file_data[$record][$event_id];

        $values = array();
        foreach ($sig_fields as $field_name => $doc_id) {

            if (!empty($doc_id)) {

                //check if already exists;
                $check_sql = sprintf("select count(*) from redcap_data where project_id = '%s' and " .
                    "event_id = '%s' and record = '%s' and field_name = '%s'",
                    prep($project_id),
                    prep($final_event),
                    prep($record),
                    prep($field_name));

                //$this->emDebug("SQL is " . $check_sql);
                $q = db_result(db_query($check_sql),0);
                //$this->emDebug("SQL result is " . $q);

                //INSERT ignore INTO redcap_data (project_id, event_id,record,field_name,value) VALUES (186, 1095,13,'patient_signature', 805);
                if ($q == 0) {
                    //no existing signature, so update signature over to the final event
                    $values[] = sprintf("('%s', '%s','%s', '%s','%s')",
                        prep($project_id),
                        prep($final_event),
                        prep($record),
                        prep($field_name),
                        prep($doc_id));
                }
            }
        }
        $value_str = implode(',', $values);

        if (!empty($values)) {
            $insert_sql = "INSERT INTO redcap_data (project_id, event_id,record,field_name,value) VALUES  " . $value_str . ';';
            $sig_status = db_query($insert_sql);
        }

        return $sig_status;
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

    /**
     * An email was entered in the reconciliation tool Add the email as proxy_email
     *
     * @param $record
     * @param $email
     * @param $email_event
     * @return bool
     */
    public function saveNewEmail($record, $email, $email_event)
    {
        $this->emDebug($record, $email, $email_event);
        //figure out which event to save the new email
        $event = '';
        switch ($email_event) {
            case 'email_proxy_1':
                $event = $this->getProjectSetting('proxy-1-event');
                $email_field = $this->getProjectSetting('proxy-1-field');
                break;
            case 'email_proxy_2':
                $event = $this->getProjectSetting('proxy-2-event');
                $email_field = $this->getProjectSetting('proxy-2-field');
                break;
            case 'email_proxy_3':
                $event = $this->getProjectSetting('proxy-3-event');
                $email_field = $this->getProjectSetting('proxy-3-field');
                break;
        }

        $data = array(
            REDCap::getRecordIdField() => $record,
            'redcap_event_name' => REDCap::getEventNames(true,false, $this->getProjectSetting('final-event')),
            $email_field        => $email
            //feb update: remove the checkboxes
            //"send_".$email_field."___1" => '1'  //checkbox for email proxy field
        );
        //$this->emDebug($event . " from " . $email_event, $data);
        $q= REDCap::saveData('json', json_encode(array($data)));

        if (count($q['errors']) > 0) {
            $this->emError($q, "Error saving proxy emails", "ERROR");
            REDCap::logEvent(
                "Unable to add new proxy email. ",  //action
                "Error while adding new proxy email $email for $event with this error".$q['errors'],
                NULL, //sql optional
                $record //record optional
            );

            return false;
        }

        return true;
    }


    /**
     * Allowing up to six slot, but can only take three proxy.
     * So copy over the first three to the three proxy slots
     *
     * @param $project_id
     * @param $record
     * @param $instrument
     * @return bool
     */
    public function setProxyEmails($project_id, $record, $instrument) {
        //get the data for the selected emails
         $q = REDCap::getData(
                'json',
                $record,
                array(
                    REDCap::getRecordIdField(),
                    'email_decision_maker_1',
                    'email_decision_maker_2',
                    'email_decision_maker_3',
                    'email_decision_maker_4',
                    'email_decision_maker_5',
                    'email_decision_maker_6',
                    'send_decision_maker_1',
                    'send_decision_maker_2',
                    'send_decision_maker_3',
                    'send_decision_maker_4',
                    'send_decision_maker_5',
                    'send_decision_maker_6'
                ),
                $this->getProjectSetting('first-event')
            //LetterProject::$config['first_event']
         );
        $results = json_decode($q, true);
        $result = current($results);

        $this->emDebug($result);

        $data = array(
            REDCap::getRecordIdField() => $record,
            'redcap_event_name' => REDCap::getEventNames(true,false,$this->getProjectSetting('first-event')),
        );


        $j=1;
        for ($i=1; $i<7; $i++) {
            //check if the send is set
            $set_status = $result["send_decision_maker_".$i."___1"];
            $this->emDebug($i,  "send_decision_maker_".$i."___1", $set_status);

                if ($set_status==1) {
                    if ($j < 4) {
                        $data["email_proxy_" . $j] = $result["email_decision_maker_" . $i];
                        $data["send_email_proxy_" . $j."___1"] = $result["send_decision_maker_" . $i."___1"];
                    } else {

                        REDCap::logEvent(
                            "Only 3 proxies allowed to be selected.",  //action
                            "Unable to send email to more than 3 proxies. Not sending email to {$i}th selected at ".
                            $result["email_decision_maker_" . $i],
                            NULL, //sql optional
                            $record //record optional
                        );
                    }
                    $j++;
                }
        }
        //$this->emDebug($results, $data);
        $q= REDCap::saveData('json', json_encode(array($data)), 'overwrite');

        if (count($q['errors']) > 0) {
            $this->emError($q, "Error saving proxy emails", "ERROR");
            return false;
        }

    }

    public function saveTimestamp($project_id, $record, $event_id) {
        $event_name = REDCap::getEventNames(true, false, $event_id);

        $timestamp = date('Y-m-d H:i:s');

        $data = array(
            REDCap::getRecordIdField() => $record,
            'redcap_event_name' => $event_name,
            $this->getProjectSetting('date-last-reconciled') => $timestamp
        );

        $q = REDCap::saveData('json', json_encode(array($data)));

        if (!empty($q['errors'])) {
            $msg = "Error saving timestamp for record $record - ask administrator to review logs: " . $q['errors'];
            $this->emError($msg);
            REDCap::logEvent(
                "Error saving reconciliation timestamp.",  //action
                $msg,
                NULL, //sql optional
                $record //record optional
            );

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

            $hash = isset($result[$this->getProjectSetting('hash')]) ? $result[$this->getProjectSetting('hash')] : '';
            $hash_url = isset($result[$this->getProjectSetting('hash-url')]) ? $result[$this->getProjectSetting('hash-url')] : '';

            if (empty($hash)) {
                // Generate a unique hash for this project
                $new_hash = generateRandomHash(8, false, TRUE, false);
                $api_url = $this->getUrl('ProxyLetterReconciliation.php', true, true);
                $new_hash_url = $api_url . "&e=" . $new_hash;

                // Save it to the record (both as hash and hash_url for piping)
                $result[$this->getProjectSetting('hash')] = $new_hash;
                $result[$this->getProjectSetting('hash-url')] = $new_hash_url;
                $response = REDCap::saveData('json', json_encode(array($result)));
                $this->emDebug($record, ": Set unique Hash Url to $new_hash_url with result " . json_encode($response));
            } else {
                $this->emDebug($hash, $record . " has an existing hash url");
            }
        } else {
            $this->emDebug("No Match");
        }
    }

    public function getResponseData($id, $select = null, $event = null)
    {
        // get  responses for this ID and event
        $q = REDCap::getData('array', $id, $select, $event);

        if (count($q) == 0) {
            $this->emError($q, "Unable to get responses for $id in event $event", "ERROR");
            return false;
        }
        return $q;
    }

    /**
     * Check various variables in the Witness and Signature page to determine whether the Letter is ready to
     * be shared.
     * checking with VJ for definitive list. In the meantime, using
     *
     *
     */
    public function getWitnessPageStatus($record, $event) {
        //get all the fields needed to be completed for a witness form to be deemed complete
        $required_fields = $this->getProjectSetting('witness_ready_fields'); //LetterProject::$config['questions'];

        $all_populated = true;

        $check_fields = array();
        foreach ($required_fields as $num => $key) {
            $check_fields[] = $key;
        }

        $params = array(
            'return_format' => 'json',
            'records'       => array($record),
            'events'        => $event,
            'fields'        => $check_fields
        );

        $q = REDCap::getData($params);
        $records = json_decode($q, true);

        //get the data and just make sure that there is value for both.
        foreach (current($records) as $k => $v) {

            if (empty($v)) {
                $all_populated = false;
                $this->emDebug("$k is not set, not yet ready to print PDF");
                break;
            }
        }

        return $all_populated;

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


    function setupLetter($record_id)
    {
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

        //
        $final_data = current($final_data);

        $pdf->CustomHeaderText =$final_data['ltr_name'];

        // ---------------------------------------------------------

        // set font
        //$pdf->SetFont('arial', '', 12);

        // add a page
        $pdf->AddPage();

        //create html for page 1
        $html = $pdf->makeHTMLPage1($record_id, $final_data);
        $pdf->writeHTML($html, true, false, true, false, '');

        //create html for page 2
        $pdf->AddPage();
        $html = $pdf->makeHTMLPage2($record_id, $final_data);
        $pdf->writeHTML($html, true, false, true, false, '');

        //Question 6
        $q6 = $final_data['q6'];
        $pdf->RadioButton('health_decisions', 5, array(), array(), '1', ($q6 == 1));
        $pdf->Cell(35, 5, 'Starting right now');
        $pdf->Ln(6);
        $pdf->RadioButton('health_decisions', 5, array(), array(), '2', $q6 == 2);
        $pdf->Cell(35, 5, 'When I am not able to make decisions by myself');
        $pdf->Ln(6);

        //create html for page 3
        $pdf->AddPage();
        $pdf = $pdf->makeHTMLPage3($record_id, $final_data, $pdf);

        //sometimes the table is longer, so split tables into two pages
        $pdf->AddPage();
        $pdf = $pdf->makeHTMLPage3Part2($record_id, $final_data, $pdf);

        //create html for page 4
        $pdf->AddPage();
        $pdf = $pdf->makeHTMLPage4($record_id, $final_data, $pdf);

        //copy over signatures for page 5
        $patient_sigfile_path = Files::copyEdocToTemp($final_data['patient_signature'], true);
        $adult_sigfile_path = Files::copyEdocToTemp($final_data['adult_signature'], true);

        //create html for page 5
        $pdf->AddPage();
        $html5 = $pdf->makeHTMLPage5($record_id, $final_data, $patient_sigfile_path, $adult_sigfile_path);
        $pdf->writeHTML($html5, true, false, true, false, '');

        //unlink the files
        unlink($patient_sigfile_path);
        unlink($adult_sigfile_path);

        //copy over signatures for page 6
        $witness1_sigfile_path = Files::copyEdocToTemp($final_data['witness1_signature'], true);
        $witness2_sigfile_path = Files::copyEdocToTemp($final_data['witness2_signature'], true);


        //create html for page 6n

        $pdf->AddPage();
        $html6 = $pdf->makeHTMLPage6($record_id, $final_data, $witness1_sigfile_path, $witness2_sigfile_path);
        $pdf->writeHTML($html6, true, false, true, false, '');

        //unlink the files
        unlink($witness1_sigfile_path);
        unlink($witness2_sigfile_path);


        $declaration_sigfile_path = Files::copyEdocToTemp($final_data['declaration_signature'], true);
        $specialwitness_sigfile_path = Files::copyEdocToTemp($final_data['specialwitness_signature'], true);

        //create html for page 7
        $pdf->AddPage();
        $html7 = $pdf->makeHTMLPage7($record_id, $final_data, $declaration_sigfile_path, $specialwitness_sigfile_path);
        $pdf->writeHTML($html7, true, false, true, false, '');

        unlink($declaration_sigfile_path);
        unlink($specialwitness_sigfile_path);

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

    public function dumpResource($name) {
        $file =  $this->getModulePath() . $name;
        if (file_exists($file)) {
            $contents = file_get_contents($file);
            echo $contents;
        } else {
            $this->emError("Unable to find $file");
        }
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