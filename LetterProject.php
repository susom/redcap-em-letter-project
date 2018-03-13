<?php
/**
 * Created by PhpStorm.
 * User: jael
 * Date: 1/9/18
 * Time: 8:51 AM
 */

namespace Stanford\LetterProject;

use phpDocumentor\Reflection\Types\Null_;
use \REDCap as REDCap;

class LetterProject extends \ExternalModules\AbstractExternalModule {
    public static $config;

    function __construct() {
        parent::__construct();
        //self::$config['log_file'] = '/tmp/letter_project.log';

        // Load the config if in project context
        if (isset($_GET['pid'])) {
            $json_config = $this::getConfigAsString();
            self::$config = json_decode($json_config,true);
        }
    }

    function hook_survey_complete($project_id, $record = NULL, $instrument, $event_id, $group_id, $survey_hash, $response_id, $repeat_instance) {
//        self::debug("Starting Hook Survey Complete", $instrument);
//        $this->setHash($project_id, $record, $instrument);
//
//        exit();
    }

    function hook_save_record($project_id, $record = NULL, $instrument, $event_id, $group_id = NULL, $survey_hash = NULL, $response_id = NULL, $repeat_instance = 1) {
        self::debug("Starting Save Record", $instrument);
        $this->setHash($project_id, $record, $instrument);
    }

    public function setHash($project_id, $record, $instrument) {
        // Set Record Hash and Hash Url
        if ($instrument == LetterProject::$config['starting_survey']) {
            // Check if hash exists
            $q = REDCap::getData(
                'json',
                $record,
                array(
                    REDCap::getRecordIdField(),
                    LetterProject::$config['hash'],
                    LetterProject::$config['hash_url']
                ),
                LetterProject::$config['first_event']
            );
            $results = json_decode($q,true);
            $result = current($results);

            //Plugin::log($result,"DEBUG", "Current Record");

            $hash = isset($result[LetterProject::$config['hash']]) ? $result[LetterProject::$config['hash']] : '';
            $hash_url = isset($result[LetterProject::$config['hash_url']]) ? $result[LetterProject::$config['hash_url']] : '';
            self::debug($hash,"Current Hash");
            self::debug($hash_url,"Current Hash Url");
            if (empty($hash)) {
                // Generate a unique hash for this project
                $new_hash = generateRandomHash(8, false, TRUE, false);
                $api_url  = $this->getUrl('ProxyLetterReconciliation.php', true, true);
                $new_hash_url = $api_url . "&e=" . $new_hash;
                self::debug($new_hash_url,"New Hash ($i)");

                // Save it to the record (both as hash and hash_url for piping)
                $result[LetterProject::$config['hash']] = $new_hash;
                $result[LetterProject::$config['hash_url']] = $new_hash_url;
                $response = REDCap::saveData('json', json_encode(array($result)));
                self::debug($record ,": Set unique Hash Url to $new_hash_url with result " . json_encode($response));
            } else {
                self::debug($hash, $record. " has an existing hash url" );
            }
        } else {
            self::debug("No Match","DEBUG");
        }
    }

    public static function getResponseData($id, $select=null, $event=null ) {
 //       self::log($id, "ID");
//        self::log($event, "EVENT");
        // get  responses for this ID and event
        $q = REDCap::getData('array', $id, $select, $event);
       //$q = REDCap::getData('array', $id);

        //$results = json_decode($q, true);

        //self::log($q, "RESULT");
        if (count($q) == 0) {
            self::log($q, "Unable to get responses for $id in event $event", "ERROR");
            return false;
        }

        //self::log($q, "DEBUG", "RESPONSE RESULT");
        //$responses =  $results[0];
        return $q;

    }

    public static function getVerificationData($id) {
        self::log($id, "Getting verification data ID");
        $event = LetterProject::$config['first_event'];
        self::log($event, "EVENT");

        $verify_data =
        // get  responses for this ID and event
        $q = REDCap::getData('array', $id);
        //$q = REDCap::getData('array', $id);

        //$results = json_decode($q, true);

        //self::log($q, "RESULT");
        if (count($q) == 0) {
            self::log($q, "Unable to get responses for $id in event $event", "ERROR");
            return false;
        }

        //self::log($q, "DEBUG", "RESPONSE RESULT");
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

    /**
     * A Logging Function for debugging, etc...
     * @param string $obj
     * @param string $type
     * @param null $detail
     */
    public static function log($obj = "Here", $detail = null, $type = "INFO") {
        self::writeLog($obj, $detail, $type);
    }

    public static function debug($obj = "Here", $detail = null, $type = "DEBUG") {
        self::writeLog($obj, $detail, $type);
    }

    public static function error($obj = "Here", $detail = null, $type = "ERROR") {
        self::writeLog($obj, $detail, $type);
        //TODO: BUBBLE UP ERRORS FOR REVIEW!
    }

    public static function writeLog($obj, $detail, $type) {
        $plugin_log_file = self::$config['log_file'];
        //$plugin_log_file = '/tmp/letter_project.log';

        // Get calling file using php backtrace to help label where the log entry is coming from
        $bt = debug_backtrace();
        $calling_file = $bt[1]['file'];
        $calling_line = $bt[1]['line'];
        $calling_function = $bt[3]['function'];
        if (empty($calling_function)) $calling_function = $bt[2]['function'];
        if (empty($calling_function)) $calling_function = $bt[1]['function'];
        // if (empty($calling_function)) $calling_function = $bt[0]['function'];

        // Convert arrays/objects into string for logging
        if (is_array($obj)) {
            $msg = "(array): " . print_r($obj,true);
        } elseif (is_object($obj)) {
            $msg = "(object): " . print_r($obj,true);
        } elseif (is_string($obj) || is_numeric($obj)) {
            $msg = $obj;
        } elseif (is_bool($obj)) {
            $msg = "(boolean): " . ($obj ? "true" : "false");
        } else {
            $msg = "(unknown): " . print_r($obj,true);
        }

        // Prepend prefix
        if ($detail) $msg = "[$detail] " . $msg;

        // Build log row
        $output = array(
            date( 'Y-m-d H:i:s' ),
            empty($project_id) ? "-" : $project_id,
            basename($calling_file, '.php'),
            $calling_line,
            $calling_function,
            $type,
            $msg
        );

        // Output to plugin log if defined, else use error_log
        if (!empty($plugin_log_file)) {
            file_put_contents(
                $plugin_log_file,
                implode("\t",$output) . "\n",
                FILE_APPEND
            );
        }
        if (!file_exists($plugin_log_file)) {
            // Output to error log
            error_log(implode("\t",$output));
        }
    }

    public static function lookupByCode($code) {
        $event = LetterProject::$config['first_event'];
        $code_field = LetterProject::$config['code_field'];

        $filter = "[".$event. "][" . $code_field . "] = '$code'";
        $get_fields = array(REDCap::getRecordIdField());
        //LetterProject ::log($filter, "FILTER");

        //passing in project_id causes getData to fail
        $q = REDCap::getData('json', NULL, $get_fields, LetterProject::$config['first_event'],
            NULL, FALSE, FALSE, FALSE, $filter);
        $results = json_decode($q, true);

        if(count($results) > 1) {
            // There is a duplicate record in the table
            $participants = array();
            foreach ($results as $result) $participants[] = $result[REDCap::getRecordIdField()];
            $msg = "Warning: more than 1 participant is using the login code $code: " . implode(", ", $participants) . "\n" .
                "When they log in they are using the 'first' record so you should be able to delete the second instance after confirming there is no assessment data";
            REDCap::email(LetterProject::$config['project_admin_email'], LetterProject::$config['project_from_email'],LetterProject::$config['project_title'], $msg);
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
}