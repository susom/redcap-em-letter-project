<?php
/**
 * Created by PhpStorm.
 * User: jael
 * Date: 1/9/18
 * Time: 8:51 AM
 */

namespace Stanford\LetterProject;

use \REDCap as REDCap;

class LetterProject extends \ExternalModules\AbstractExternalModule {
    public static $config;

//
//    function __construct() {
//        parent::__construct();
//        //self::$config['log_file'] = '/tmp/letter_project.log';
//
//        // Load the config if in project context
//        if (isset($_GET['pid'])) {
//            $json_config = $this->getConfigAsString();
//            self::$config = json_decode($json_config,true);
//        }
//    }

    function hook_survey_complete($project_id, $record = NULL, $instrument, $event_id, $group_id, $survey_hash, $response_id, $repeat_instance) {
        $this->emDebug("Starting Hook Survey Complete", $instrument);
//        $this->setHash($project_id, $record, $instrument);
//
//        exit();
    }

    function hook_save_record($project_id, $record = NULL, $instrument, $event_id, $group_id = NULL, $survey_hash = NULL, $response_id = NULL, $repeat_instance = 1) {
        //if this is the original letter in , then save it to the final letter
        if ($event_id = $this->getProjectSetting('first-event')) {
            $this->emLog("FIRST EVENT: this is the $instrument and $event_id vs ".
                $this->getProjectSetting('first-event'));

        }


        //if this is the original letter in , then save it to the final letter
        if ($event_id == $this->getProjectSetting('first-event') &&
            ($instrument == $this->getProjectSetting('letter-survey'))) {

            $this->emLog("FIRST EVENT: this is the right instrument: $instrument and event: $event_id");
            $this->copyToFinal($project_id, $record, $instrument, $event_id);

        }


        $this->emDebug("Starting Save Record", $instrument);
        $this->setHash($project_id, $record, $instrument);
    }

    public function copyToFinal($project_id, $record, $instrument, $event_id) {
        $form_complete_field = $instrument."_complete";

        //get data from final instrument
        //if final form is incomplete, move over data from the first form.
        $q = REDCap::getData(
            'json',
            $record,
            array($form_complete_field),
            $this->getProjectSetting('final-event'));
        $results = json_decode($q, true);
        $result = current($results);
        $this->emLog($result, "Current FINAL Record");


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
                $redcap_event_name = REDCap::getEventNames(true, false,$this->getProjectSetting('final-event'));

                $f_result['redcap_event_name'] = $redcap_event_name;
                $save_data= array_merge($id_array,$f_result );



                //first form is complete, do the migration
                $q = REDCap::saveData('json', json_encode(array($f_result)));
                $this->emLog($f_result, "xxCurrent FIRST Record", $q);
            }

        }
    }

    public function setHash($project_id, $record, $instrument) {
        $start_survey = $this->getProjectSetting('starting-survey');
        $this->emLog("Starting survey is ".$start_survey);

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
            $results = json_decode($q,true);
            $result = current($results);

            $this->emLog($result,"DEBUG", "Current Record");

            $hash = isset($result[$this->getProjectSetting('hash')]) ? $result[$this->getProjectSetting('hash')] : '';
            $hash_url = isset($result[$this->getProjectSetting('hash-url')]) ? $result[$this->getProjectSetting('hash-url')] : '';
            $this->emDebug($hash,"Current Hash");
            $this->emDebug($hash_url,"Current Hash Url");
            if (empty($hash)) {
                // Generate a unique hash for this project
                $new_hash = generateRandomHash(8, false, TRUE, false);
                $api_url  = $this->getUrl('ProxyLetterReconciliation.php', true, true);
                $new_hash_url = $api_url . "&e=" . $new_hash;
                $this->emDebug($new_hash_url,"New Hash ()");

                // Save it to the record (both as hash and hash_url for piping)
                $result[$this->getProjectSetting('hash')] = $new_hash;
                $result[$this->getProjectSetting('hash-url')] = $new_hash_url;
                $response = REDCap::saveData('json', json_encode(array($result)));
                $this->emDebug($record ,": Set unique Hash Url to $new_hash_url with result " . json_encode($response));
            } else {
                $this->emDebug($hash, $record. " has an existing hash url" );
            }
        } else {
            $this->emDebug("No Match","DEBUG");
        }
    }

    public function getResponseData($id, $select=null, $event=null ) {
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


    /**
     * Read the current config from a single key-value pair in the external module settings table
     */
    function getConfigAsString() {
        $string_config = $this->getProjectSetting($this->PREFIX . '-config');
        // SurveyDashboard::log($string_config);
        return $string_config;
    }

    function setConfigAsString($string_config) {
        $this->setProjectSetting($this->PREFIX . '-config', $string_config);
    }


    public function lookupByCode($code) {
        $event_id = $this->getProjectSetting('first-event');
        $event = REDCap::getEventNames(true, false, $event_id);
        $code_field = $this->getProjectSetting('code-field');

        $filter = "[".$event. "][" . $code_field . "] = '$code'";
        $get_fields = array(REDCap::getRecordIdField());
        $this->emLog($filter, "FILTER");

        //passing in project_id causes getData to fail
        $q = REDCap::getData('json', NULL, $get_fields, $event,
            NULL, FALSE, FALSE, FALSE, $filter);
        $results = json_decode($q, true);

        if(count($results) > 1) {
            // There is a duplicate record in the table
            $participants = array();
            foreach ($results as $result) $participants[] = $result[REDCap::getRecordIdField()];
            $msg = "Warning: more than 1 participant is using the login code $code: " . implode(", ", $participants) . "\n" .
                "When they log in they are using the 'first' record so you should be able to delete the second instance after confirming there is no assessment data";
            REDCap::email($this->getProjectSetting('project-admin-email'), $this->getProjectSetting('project-from-email'),$this->getProjectSetting('project-title'), $msg);
        }

        // If the code does not match return false
        if(count($results) == 0) {
            return false;
        }

        // Take the first match (in case there are more than 1)
        $result = $results[0];
        return $result[REDCap::getRecordIdField()];
    }


    public static function getSessionMessage() {
        if (isset($_SESSION['msg']) && !empty($_SESSION['msg'])) {
            $html = "
            <div id='session-message' class='alert alert-info text-center fade in' data-dismiss='alert'>
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
    function emLog() {
        $emLogger = \ExternalModules\ExternalModules::getModuleInstance('em_logger');
        $emLogger->emLog($this->PREFIX, func_get_args(), "INFO");
    }

    function emDebug() {
        // Check if debug enabled
        if ( $this->getSystemSetting('enable-system-debug-logging') || ( !empty($_GET['pid']) && $this->getProjectSetting('enable-project-debug-logging'))) {
            $emLogger = \ExternalModules\ExternalModules::getModuleInstance('em_logger');
            $emLogger->emLog($this->PREFIX, func_get_args(), "DEBUG");
        }
    }

    function emError() {
        $emLogger = \ExternalModules\ExternalModules::getModuleInstance('em_logger');
        $emLogger->emLog($this->PREFIX, func_get_args(), "ERROR");
    }
}