<?php
/*
Addon Name: Authorize.net ARB gateway
Description: The Payment gateway for Authorize.net ARB.
Author: Barry (Incsub)
Author URI: http://premium.wpmudev.org
Gateway ID: authorizenetarb
*/

class AuthorizeNetARB extends AuthorizeNetRequest
{

    const LIVE_URL = "https://api.authorize.net/xml/v1/request.api";
    const SANDBOX_URL = "https://apitest.authorize.net/xml/v1/request.api";

    private $_request_type;
    private $_request_payload;

    /**
     * Optional. Used if the merchant wants to set a reference ID.
     *
     * @param string $refId
     */
    public function setRefId($refId)
    {
        $this->_request_payload = ($refId ? "<refId>$refId</refId>" : "");
    }

    /**
     * Create an ARB subscription
     *
     * @param AuthorizeNet_Subscription $subscription
     *
     * @return AuthorizeNetARB_Response
     */
    public function createSubscription(AuthorizeNet_Subscription $subscription)
    {
        $this->_request_type = "CreateSubscriptionRequest";
        $this->_request_payload .= $subscription->getXml();
        return $this->_sendRequest();
    }

    /**
     * Update an ARB subscription
     *
     * @param int                       $subscriptionId
     * @param AuthorizeNet_Subscription $subscription
     *
     * @return AuthorizeNetARB_Response
     */
    public function updateSubscription($subscriptionId, AuthorizeNet_Subscription $subscription)
    {
        $this->_request_type = "UpdateSubscriptionRequest";
        $this->_request_payload .= "<subscriptionId>$subscriptionId</subscriptionId>";
        $this->_request_payload .= $subscription->getXml();
        return $this->_sendRequest();
    }

    /**
     * Get status of a subscription
     *
     * @param int $subscriptionId
     *
     * @return AuthorizeNetARB_Response
     */
    public function getSubscriptionStatus($subscriptionId)
    {
        $this->_request_type = "GetSubscriptionStatusRequest";
        $this->_request_payload .= "<subscriptionId>$subscriptionId</subscriptionId>";
        return $this->_sendRequest();
    }

    /**
     * Cancel a subscription
     *
     * @param int $subscriptionId
     *
     * @return AuthorizeNetARB_Response
     */
    public function cancelSubscription($subscriptionId)
    {
        $this->_request_type = "CancelSubscriptionRequest";
        $this->_request_payload .= "<subscriptionId>$subscriptionId</subscriptionId>";
        return $this->_sendRequest();
    }

     /**
     *
     *
     * @param string $response
     *
     * @return AuthorizeNetARB_Response
     */
    protected function _handleResponse($response)
    {
        return new AuthorizeNetARB_Response($response);
    }

    /**
     * @return string
     */
    protected function _getPostUrl()
    {
        return ($this->_sandbox ? self::SANDBOX_URL : self::LIVE_URL);
    }

    /**
     * Prepare the XML document for posting.
     */
    protected function _setPostString()
    {
        $this->_post_string =<<<XML
<?xml version="1.0" encoding="utf-8"?>
<ARB{$this->_request_type} xmlns= "AnetApi/xml/v1/schema/AnetApiSchema.xsd">
    <merchantAuthentication>
        <name>{$this->_api_login}</name>
        <transactionKey>{$this->_transaction_key}</transactionKey>
    </merchantAuthentication>
    {$this->_request_payload}
</ARB{$this->_request_type}>
XML;
    }

}


/**
 * A class to parse a response from the ARB XML API.
 *
 * @package    AuthorizeNet
 * @subpackage AuthorizeNetARB
 */
class AuthorizeNetARB_Response extends AuthorizeNetXMLResponse
{

    /**
     * @return int
     */
    public function getSubscriptionId()
    {
        return $this->_getElementContents("subscriptionId");
    }

    /**
     * @return string
     */
    public function getSubscriptionStatus()
    {
        return $this->_getElementContents("Status");
    }

}

/*
* This class passes through the actual requests to Authorize.net
*/

if( !class_exists('AuthorizeNetRequest') ) {
	abstract class AuthorizeNetRequest
	{

	    protected $_api_login;
	    protected $_transaction_key;
	    protected $_post_string;
	    public $VERIFY_PEER = true; // Set to false if getting connection errors.
	    protected $_sandbox = true;
	    protected $_log_file = false;

	    /**
	     * Set the _post_string
	     */
	    abstract protected function _setPostString();

	    /**
	     * Handle the response string
	     */
	    abstract protected function _handleResponse($string);

	    /**
	     * Get the post url. We need this because until 5.3 you
	     * you could not access child constants in a parent class.
	     */
	    abstract protected function _getPostUrl();

	    /**
	     * Constructor.
	     *
	     * @param string $api_login_id       The Merchant's API Login ID.
	     * @param string $transaction_key The Merchant's Transaction Key.
	     */
	    public function __construct($api_login_id = false, $transaction_key = false)
	    {
	        $this->_api_login = ($api_login_id ? $api_login_id : (defined('AUTHORIZENET_API_LOGIN_ID') ? AUTHORIZENET_API_LOGIN_ID : ""));
	        $this->_transaction_key = ($transaction_key ? $transaction_key : (defined('AUTHORIZENET_TRANSACTION_KEY') ? AUTHORIZENET_TRANSACTION_KEY : ""));
	        $this->_sandbox = (defined('AUTHORIZENET_SANDBOX') ? AUTHORIZENET_SANDBOX : true);
	        $this->_log_file = (defined('AUTHORIZENET_LOG_FILE') ? AUTHORIZENET_LOG_FILE : false);
	    }

	    /**
	     * Alter the gateway url.
	     *
	     * @param bool $bool Use the Sandbox.
	     */
	    public function setSandbox($bool)
	    {
	        $this->_sandbox = $bool;
	    }

	    /**
	     * Set a log file.
	     *
	     * @param string $filepath Path to log file.
	     */
	    public function setLogFile($filepath)
	    {
	        $this->_log_file = $filepath;
	    }

	    /**
	     * Return the post string.
	     *
	     * @return string
	     */
	    public function getPostString()
	    {
	        return $this->_post_string;
	    }

	    /**
	     * Posts the request to AuthorizeNet & returns response.
	     *
	     * @return AuthorizeNetARB_Response The response.
	     */
	    protected function _sendRequest()
	    {
	        $this->_setPostString();
	        $post_url = $this->_getPostUrl();
	        $curl_request = curl_init($post_url);
	        curl_setopt($curl_request, CURLOPT_POSTFIELDS, $this->_post_string);
	        curl_setopt($curl_request, CURLOPT_HEADER, 0);
	        curl_setopt($curl_request, CURLOPT_TIMEOUT, 45);
	        curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, 1);
	        curl_setopt($curl_request, CURLOPT_SSL_VERIFYHOST, 2);
	        if ($this->VERIFY_PEER) {
	            curl_setopt($curl_request, CURLOPT_CAINFO, dirname(dirname(__FILE__)) . '/ssl/cert.pem');
	        } else {
	            curl_setopt($curl_request, CURLOPT_SSL_VERIFYPEER, false);
	        }

	        if (preg_match('/xml/',$post_url)) {
	            curl_setopt($curl_request, CURLOPT_HTTPHEADER, Array("Content-Type: text/xml"));
	        }

	        $response = curl_exec($curl_request);

	        if ($this->_log_file) {

	            if ($curl_error = curl_error($curl_request)) {
	                file_put_contents($this->_log_file, "----CURL ERROR----\n$curl_error\n\n", FILE_APPEND);
	            }
	            // Do not log requests that could contain CC info.
	            // file_put_contents($this->_log_file, "----Request----\n{$this->_post_string}\n", FILE_APPEND);

	            file_put_contents($this->_log_file, "----Response----\n$response\n\n", FILE_APPEND);
	        }
	        curl_close($curl_request);

	        return $this->_handleResponse($response);
	    }

	}
}