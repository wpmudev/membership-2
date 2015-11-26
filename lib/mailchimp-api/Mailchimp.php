<?php

require_once 'Mailchimp/Folders.php';
require_once 'Mailchimp/Templates.php';
require_once 'Mailchimp/Users.php';
require_once 'Mailchimp/Helper.php';
require_once 'Mailchimp/Mobile.php';
require_once 'Mailchimp/Conversations.php';
require_once 'Mailchimp/Ecomm.php';
require_once 'Mailchimp/Neapolitan.php';
require_once 'Mailchimp/Lists.php';
require_once 'Mailchimp/Campaigns.php';
require_once 'Mailchimp/Vip.php';
require_once 'Mailchimp/Reports.php';
require_once 'Mailchimp/Gallery.php';
require_once 'Mailchimp/Goal.php';
require_once 'Mailchimp/Exceptions.php';

class M2_Mailchimp {

    public $apikey;
    public $ch;
    public $root  = 'https://api.mailchimp.com/2.0';
    public $debug = false;

    public static $error_map = array(
        "ValidationError" => "M2_Mailchimp_ValidationError",
        "ServerError_MethodUnknown" => "M2_Mailchimp_ServerError_MethodUnknown",
        "ServerError_InvalidParameters" => "M2_Mailchimp_ServerError_InvalidParameters",
        "Unknown_Exception" => "M2_Mailchimp_Unknown_Exception",
        "Request_TimedOut" => "M2_Mailchimp_Request_TimedOut",
        "Zend_Uri_Exception" => "M2_Mailchimp_Zend_Uri_Exception",
        "PDOException" => "M2_Mailchimp_PDOException",
        "Avesta_Db_Exception" => "M2_Mailchimp_Avesta_Db_Exception",
        "XML_RPC2_Exception" => "M2_Mailchimp_XML_RPC2_Exception",
        "XML_RPC2_FaultException" => "M2_Mailchimp_XML_RPC2_FaultException",
        "Too_Many_Connections" => "M2_Mailchimp_Too_Many_Connections",
        "Parse_Exception" => "M2_Mailchimp_Parse_Exception",
        "User_Unknown" => "M2_Mailchimp_User_Unknown",
        "User_Disabled" => "M2_Mailchimp_User_Disabled",
        "User_DoesNotExist" => "M2_Mailchimp_User_DoesNotExist",
        "User_NotApproved" => "M2_Mailchimp_User_NotApproved",
        "Invalid_ApiKey" => "M2_Mailchimp_Invalid_ApiKey",
        "User_UnderMaintenance" => "M2_Mailchimp_User_UnderMaintenance",
        "Invalid_AppKey" => "M2_Mailchimp_Invalid_AppKey",
        "Invalid_IP" => "M2_Mailchimp_Invalid_IP",
        "User_DoesExist" => "M2_Mailchimp_User_DoesExist",
        "User_InvalidRole" => "M2_Mailchimp_User_InvalidRole",
        "User_InvalidAction" => "M2_Mailchimp_User_InvalidAction",
        "User_MissingEmail" => "M2_Mailchimp_User_MissingEmail",
        "User_CannotSendCampaign" => "M2_Mailchimp_User_CannotSendCampaign",
        "User_MissingModuleOutbox" => "M2_Mailchimp_User_MissingModuleOutbox",
        "User_ModuleAlreadyPurchased" => "M2_Mailchimp_User_ModuleAlreadyPurchased",
        "User_ModuleNotPurchased" => "M2_Mailchimp_User_ModuleNotPurchased",
        "User_NotEnoughCredit" => "M2_Mailchimp_User_NotEnoughCredit",
        "MC_InvalidPayment" => "M2_Mailchimp_MC_InvalidPayment",
        "List_DoesNotExist" => "M2_Mailchimp_List_DoesNotExist",
        "List_InvalidInterestFieldType" => "M2_Mailchimp_List_InvalidInterestFieldType",
        "List_InvalidOption" => "M2_Mailchimp_List_InvalidOption",
        "List_InvalidUnsubMember" => "M2_Mailchimp_List_InvalidUnsubMember",
        "List_InvalidBounceMember" => "M2_Mailchimp_List_InvalidBounceMember",
        "List_AlreadySubscribed" => "M2_Mailchimp_List_AlreadySubscribed",
        "List_NotSubscribed" => "M2_Mailchimp_List_NotSubscribed",
        "List_InvalidImport" => "M2_Mailchimp_List_InvalidImport",
        "MC_PastedList_Duplicate" => "M2_Mailchimp_MC_PastedList_Duplicate",
        "MC_PastedList_InvalidImport" => "M2_Mailchimp_MC_PastedList_InvalidImport",
        "Email_AlreadySubscribed" => "M2_Mailchimp_Email_AlreadySubscribed",
        "Email_AlreadyUnsubscribed" => "M2_Mailchimp_Email_AlreadyUnsubscribed",
        "Email_NotExists" => "M2_Mailchimp_Email_NotExists",
        "Email_NotSubscribed" => "M2_Mailchimp_Email_NotSubscribed",
        "List_MergeFieldRequired" => "M2_Mailchimp_List_MergeFieldRequired",
        "List_CannotRemoveEmailMerge" => "M2_Mailchimp_List_CannotRemoveEmailMerge",
        "List_Merge_InvalidMergeID" => "M2_Mailchimp_List_Merge_InvalidMergeID",
        "List_TooManyMergeFields" => "M2_Mailchimp_List_TooManyMergeFields",
        "List_InvalidMergeField" => "M2_Mailchimp_List_InvalidMergeField",
        "List_InvalidInterestGroup" => "M2_Mailchimp_List_InvalidInterestGroup",
        "List_TooManyInterestGroups" => "M2_Mailchimp_List_TooManyInterestGroups",
        "Campaign_DoesNotExist" => "M2_Mailchimp_Campaign_DoesNotExist",
        "Campaign_StatsNotAvailable" => "M2_Mailchimp_Campaign_StatsNotAvailable",
        "Campaign_InvalidAbsplit" => "M2_Mailchimp_Campaign_InvalidAbsplit",
        "Campaign_InvalidContent" => "M2_Mailchimp_Campaign_InvalidContent",
        "Campaign_InvalidOption" => "M2_Mailchimp_Campaign_InvalidOption",
        "Campaign_InvalidStatus" => "M2_Mailchimp_Campaign_InvalidStatus",
        "Campaign_NotSaved" => "M2_Mailchimp_Campaign_NotSaved",
        "Campaign_InvalidSegment" => "M2_Mailchimp_Campaign_InvalidSegment",
        "Campaign_InvalidRss" => "M2_Mailchimp_Campaign_InvalidRss",
        "Campaign_InvalidAuto" => "M2_Mailchimp_Campaign_InvalidAuto",
        "MC_ContentImport_InvalidArchive" => "M2_Mailchimp_MC_ContentImport_InvalidArchive",
        "Campaign_BounceMissing" => "M2_Mailchimp_Campaign_BounceMissing",
        "Campaign_InvalidTemplate" => "M2_Mailchimp_Campaign_InvalidTemplate",
        "Invalid_EcommOrder" => "M2_Mailchimp_Invalid_EcommOrder",
        "Absplit_UnknownError" => "M2_Mailchimp_Absplit_UnknownError",
        "Absplit_UnknownSplitTest" => "M2_Mailchimp_Absplit_UnknownSplitTest",
        "Absplit_UnknownTestType" => "M2_Mailchimp_Absplit_UnknownTestType",
        "Absplit_UnknownWaitUnit" => "M2_Mailchimp_Absplit_UnknownWaitUnit",
        "Absplit_UnknownWinnerType" => "M2_Mailchimp_Absplit_UnknownWinnerType",
        "Absplit_WinnerNotSelected" => "M2_Mailchimp_Absplit_WinnerNotSelected",
        "Invalid_Analytics" => "M2_Mailchimp_Invalid_Analytics",
        "Invalid_DateTime" => "M2_Mailchimp_Invalid_DateTime",
        "Invalid_Email" => "M2_Mailchimp_Invalid_Email",
        "Invalid_SendType" => "M2_Mailchimp_Invalid_SendType",
        "Invalid_Template" => "M2_Mailchimp_Invalid_Template",
        "Invalid_TrackingOptions" => "M2_Mailchimp_Invalid_TrackingOptions",
        "Invalid_Options" => "M2_Mailchimp_Invalid_Options",
        "Invalid_Folder" => "M2_Mailchimp_Invalid_Folder",
        "Invalid_URL" => "M2_Mailchimp_Invalid_URL",
        "Module_Unknown" => "M2_Mailchimp_Module_Unknown",
        "MonthlyPlan_Unknown" => "M2_Mailchimp_MonthlyPlan_Unknown",
        "Order_TypeUnknown" => "M2_Mailchimp_Order_TypeUnknown",
        "Invalid_PagingLimit" => "M2_Mailchimp_Invalid_PagingLimit",
        "Invalid_PagingStart" => "M2_Mailchimp_Invalid_PagingStart",
        "Max_Size_Reached" => "M2_Mailchimp_Max_Size_Reached",
        "MC_SearchException" => "M2_Mailchimp_MC_SearchException",
        "Goal_SaveFailed" => "M2_Mailchimp_Goal_SaveFailed",
        "Conversation_DoesNotExist" => "M2_Mailchimp_Conversation_DoesNotExist",
        "Conversation_ReplySaveFailed" => "M2_Mailchimp_Conversation_ReplySaveFailed",
        "File_Not_Found_Exception" => "M2_Mailchimp_File_Not_Found_Exception",
        "Folder_Not_Found_Exception" => "M2_Mailchimp_Folder_Not_Found_Exception",
        "Folder_Exists_Exception" => "M2_Mailchimp_Folder_Exists_Exception"
    );

    public function __construct($apikey=null, $opts=array()) {
        if (!$apikey) {
            $apikey = getenv('MAILCHIMP_APIKEY');
        }

        if (!$apikey) {
            $apikey = $this->readConfigs();
        }

        if (!$apikey) {
            throw new M2_Mailchimp_Error('You must provide a MailChimp API key');
        }

        $this->apikey = $apikey;
        $dc           = "us1";

        if (strstr($this->apikey, "-")){
            list($key, $dc) = explode("-", $this->apikey, 2);
            if (!$dc) {
                $dc = "us1";
            }
        }

        $this->root = str_replace('https://api', 'https://' . $dc . '.api', $this->root);
        $this->root = rtrim($this->root, '/') . '/';

        if (!isset($opts['timeout']) || !is_int($opts['timeout'])){
            $opts['timeout'] = 600;
        }
        if (isset($opts['debug'])){
            $this->debug = true;
        }


        $this->ch = curl_init();

        if ( isset($opts['CURLOPT_FOLLOWLOCATION'] ) && $opts['CURLOPT_FOLLOWLOCATION'] === true) {
            curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
        }

        curl_setopt($this->ch, CURLOPT_USERAGENT, 'MailChimp-PHP/2.0.5');
        curl_setopt($this->ch, CURLOPT_POST, true);
        curl_setopt($this->ch, CURLOPT_HEADER, false);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, $opts['timeout']);


        $this->folders = new M2_Mailchimp_Folders($this);
        $this->templates = new M2_Mailchimp_Templates($this);
        $this->users = new M2_Mailchimp_Users($this);
        $this->helper = new M2_Mailchimp_Helper($this);
        $this->mobile = new M2_Mailchimp_Mobile($this);
        $this->conversations = new M2_Mailchimp_Conversations($this);
        $this->ecomm = new M2_Mailchimp_Ecomm($this);
        $this->neapolitan = new M2_Mailchimp_Neapolitan($this);
        $this->lists = new M2_Mailchimp_Lists($this);
        $this->campaigns = new M2_Mailchimp_Campaigns($this);
        $this->vip = new M2_Mailchimp_Vip($this);
        $this->reports = new M2_Mailchimp_Reports($this);
        $this->gallery = new M2_Mailchimp_Gallery($this);
        $this->goal = new M2_Mailchimp_Goal($this);
    }

    public function __destruct() {
        curl_close($this->ch);
    }

    public function call($url, $params) {
        $params['apikey'] = $this->apikey;

        $params = json_encode($params);
        $ch     = $this->ch;

        curl_setopt($ch, CURLOPT_URL, $this->root . $url . '.json');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_VERBOSE, $this->debug);

        $start = microtime(true);
        $this->log('Call to ' . $this->root . $url . '.json: ' . $params);
        if($this->debug) {
            $curl_buffer = fopen('php://memory', 'w+');
            curl_setopt($ch, CURLOPT_STDERR, $curl_buffer);
        }

        $response_body = curl_exec($ch);

        $info = curl_getinfo($ch);
        $time = microtime(true) - $start;
        if($this->debug) {
            rewind($curl_buffer);
            $this->log(stream_get_contents($curl_buffer));
            fclose($curl_buffer);
        }
        $this->log('Completed in ' . number_format($time * 1000, 2) . 'ms');
        $this->log('Got response: ' . $response_body);

        if(curl_error($ch)) {
            throw new M2_Mailchimp_HttpError("API call to $url failed: " . curl_error($ch));
        }
        $result = json_decode($response_body, true);

        if(floor($info['http_code'] / 100) >= 4) {
            throw $this->castError($result);
        }

        return $result;
    }

    public function readConfigs() {
        $paths = array('~/.mailchimp.key', '/etc/mailchimp.key');
        foreach($paths as $path) {
            if(file_exists($path)) {
                $apikey = trim(file_get_contents($path));
                if ($apikey) {
                    return $apikey;
                }
            }
        }
        return false;
    }

    public function castError($result) {
        if ($result['status'] !== 'error' || !$result['name']) {
            throw new M2_Mailchimp_Error('We received an unexpected error: ' . json_encode($result));
        }

        $class = (isset(self::$error_map[$result['name']])) ? self::$error_map[$result['name']] : 'M2_Mailchimp_Error';
        return new $class($result['error'], $result['code']);
    }

    public function log($msg) {
        if ($this->debug) {
            error_log($msg);
        }
    }
}


