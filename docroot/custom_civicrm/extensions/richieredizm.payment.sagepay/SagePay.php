<?php
 
require_once 'CRM/Core/Payment.php';
include("includes.php");
 
class richieredizm_payment_sagepay extends CRM_Core_Payment {
  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   * @static
   */
  static private $_singleton = null;
 
  /**
   * mode of operation: live or test
   *
   * @var object
   */
   protected $_mode = null;
 
  /**
   * Constructor
   *
   * @param string $mode the mode of operation: live or test
   * @param $paymentProcessor
   */
  function __construct( $mode, &$paymentProcessor ) {
    $this->_mode             = $mode;
    $this->_paymentProcessor = $paymentProcessor;
    $this->_processorName    = ts('LLR SagePay Processor');
  }
 
  /**
   * singleton function used to manage this object
   *
   * @param string $mode the mode of operation: live or test
   * @param $paymentProcessor
   * @param $paymentForm
   * @param $force
   * @return object
   * @static
   *
   */
  static function &singleton( $mode, &$paymentProcessor, &$paymentForm=NULL, $force=false ) {
      $processorName = $paymentProcessor['name'];
      if (self::$_singleton[$processorName] === null ) {
          self::$_singleton[$processorName] = new richieredizm_payment_sagepay( $mode, $paymentProcessor );
      }
      return self::$_singleton[$processorName];
  }
 
  /**
   * This function checks to see if we have the right config values
   *
   * @return string the error message if any
   * @public
   */
  function checkConfig( ) {
    $config = CRM_Core_Config::singleton();
 
    $error = array();
 
    if (empty($this->_paymentProcessor['user_name'])) {
      $error[] = ts('The "Bill To ID" is not set in the Administer CiviCRM Payment Processor.');
    }
 
    if (!empty($error)) {
      return implode('<p>', $error);
    }
    else {
      return NULL;
    }
  }
 
  /**
   * Sets appropriate parameters and calls Sage Pay Direct Payment Processor Version 2.23
   *
   * @param array $params  name value pair of contribution data
   *
   * @return array $result
   * @access public
   *
   */
  function doDirectPayment(&$params) {
  //  CRM_Core_Error::debug_log_message();
   $params['credit_card_type'] = strtoupper( $params['credit_card_type'] );
    // Card Types accepted by SagePay (Ver 2.23): VISA, MC, DELTA, MAESTRO, UKE, AMEX, DC, JCB, LASER, PAYPAL
    switch ($params['credit_card_type'] )
    {
    case 'MASTERCARD':
      $creditCardType = 'MC';
      break;
    case 'VISA ELECTRON':
      $creditCardType = 'UKE';
      break;
    case 'DINERS':
      $creditCardType = 'DC';
      break;
    default:
      $creditCardType = $params['credit_card_type'];
    }
    // Set email
    if (! empty($params['email-Primary']) ) {
      $useremail = $params['email-Primary'];
    } if (! empty($params['email-5']) ) {
      $useremail = $params['email-5'];
    } else {
      $useremail = $params['email'];
    }
    //Invoice id is used by sagepay as the transaction id. CiviCRM API
    //doesn not generate one (this is fixed in v 4.6) so we generate 
    //one here if it has not been provided.
    if ( empty($params['invoiceID']) ) {
      $params['invoiceID'] = ! empty($params['invoice_id']) ? $params['invoice_id'] :  md5(uniqid(rand(), TRUE));
    }  
    $donateAmount = str_replace(',', '', $params['amount']);
    
    //Convert country id values to iso values if they have not been provided
    $country_keys = array('billing_country-5' => 'billing_country_id-5', 'country' => 'country_id');
    foreach ( $country_keys as  $isokey => $idkey ) {
      if (empty($params[$isokey]) && ! empty($params[$idkey]) ) {
        $params[$isokey] = $this->getCountryISO($params[$idkey]);
      }
    }
    // Construct params list to send to SagePay ...
    $sageParams = array(
      'Vendor'             => $this->_paymentProcessor['user_name'],
      'VPSProtocol'        => '2.23',
      'TxType'             => 'PAYMENT',
      'VendorTxCode'       => $params['invoiceID'],
      'Amount'             => sprintf("%.2f", $donateAmount),
      'Currency'           => $params['currencyID'],
      'Description'        => substr($params['description'], 0, 100),
      'CardHolder'         => $params['billing_last_name'] . ' ' . $params['billing_last_name'],
      'CardNumber'         => $params['credit_card_number'],
      'ExpiryDate'         => substr('0' . $params['credit_card_exp_date']['M'], -2) . substr($params['credit_card_exp_date']['Y'], -2), //Ensure 2 digit month
      'IssueNumber'        => '',
      'CV2'                => $params['cvv2'],
      'CardType'           => $creditCardType,
      'BillingSurname'     => $params['billing_last_name'],
      'BillingFirstnames'  => $params['billing_first_name'],
      'BillingAddress1'    => $params['billing_street_address-5'],
      'BillingCity'        => $params['billing_city-5'],
      'BillingPostCode'    => $params['billing_postal_code-5'],
      'BillingCountry'     => $params['billing_country-5'],

      //'BillingState'       => $params['billing_state_province-5'],

      'DeliverySurname'    => $params['billing_last_name'],
      'DeliveryFirstnames' => $params['billing_first_name'],
      'DeliveryAddress1'   => $params['street_address'],
      'DeliveryCity'       => $params['city'],
      'DeliveryPostcode'   => $params['postal_code'],
      'DeliveryCountry'    => $params['country'],  
      //'DeliveryState'      => $params['billing_state_province-5'],
      'CustomerEMail'      => $useremail,
      'Basket'             => '',
      'GiftAidPayment'     => 0,
      'ApplyAVSCV2'        => '',
      'Apply3DSecure'      => 0
    );

    $strlenBill = strlen($params['billing_state_province-5']);
  if($strlenBill <= 2) {
       $sageParams['BillingState'] = $params['billing_state_province-5'];
    $sageParams['DeliveryState'] = $params['billing_state_province-5'];
    }
    // Construct post string
    $post = '';
    foreach ($sageParams as $key => $value)
    $post .= ($key != 'Vendor' ? '&' : '') . $key . '=' . urlencode($value);
  
    // Send payment POST to the target URL
    $url      = $this->_paymentProcessor['url_site'];
    $response = requestPost($url, $post);

    // Workarounds for special cases
    if ($response["Status"] != 'OK') {
      if ( preg_match('/VendorTxCode has been used/i', $response["StatusDetail"]) ) {
        $errormsg = 'Your payment has already been submitted.
        Please check your email for confirmation. If you have any queries
        please contact us on info@beatingbloodcancers.org.uk';
        drupal_set_message($errormsg, 'error');
    if (is_numeric($params['contribution_campaign_id'])) {
      $finalURL = '/civicrm/contribute/transact?_qf_Main_display=true&qfKey='.$params['qfKey'];   
    } elseif (is_numeric($params['llr_eventId'])) {
        $finalURL = base_path() . 'civicrm/event/register?_qf_Register_display=true&&qfKey='.$params['qfKey'];
    }
    CRM_Utils_System::redirect( $finalURL );
      }       
    }
  if ($_SESSION['contribution_attempt']) { 
    unset($_SESSION['contribution_attempt']);
  }
    
    // Take action based upon the response status
  switch ($response["Status"]) {
          case 'OK':
        return self::succeed($response);
      case 'REJECTED':
        $_SESSION['contribution_attempt'] = 'failed';
        return self::rejected($response, $params);
      case 'INVALID':
        $_SESSION['contribution_attempt'] = 'failed';
        return self::invalid($response, $params);
      default:
        $_SESSION['contribution_attempt'] = 'failed';
        return self::error($response, $params);
  }

  }
  
  /**
   * Creates an error for a failed transaction.
   */
  private function createError($msg, $response) {
    //The civicrm api/v3/Contribution.php expects an array on error
    //(it uses is_error()
    //Whereas Contribute/Form/Contribution.php (as of v 4.5) expects an object of type CRM_Core_Error.
    //We will try to account for both callers.

    //Note: in  v4.6,  there are further changes - doPayment called rather than
    //doDirectPayment and  an exception is expected caught. 
    
    //Drupal-specific and horrid even within Drupal, but do not know any
    //better way of determining whether caller is api or not.
    $path = $_GET['q'];
    //A heavy assumption, if we are not on a civicrm page, we have been
    //called by civicrm_api.
    if ( 0 === strpos($path, 'civicrm/') && 0 !== strpos($path, 'civicrm/api') ) {
      $error = CRM_Core_Error::createError($msg, $response['Status'], 'Error');
        return $error; 
    }
    else {
      return array('error_message' => $msg, 'is_error' => 1);
    }
  }

  /**
   * SagePay payment has succeeded
   * @param $response
   * @return array
   */
  private function succeed($response) {
    $response['trxn_id'] = $response['VPSTxId'];
    return $response;
  }
  /**
   * SagePay payment has failed
   * @param $response
   * @param $params
   * @return array
   */
  private function invalid($response, $params) {
    $msg = "Unfortunately, it seems the details provided are invalid – please double check your billing address and card details and try again.";  
    //drupal_set_message($msg,'error');
    self::createFailedContribution($response, $params);
    return $this->createError($msg, $response);
    //return CRM_Core_Error::createAPIError( $msg);
  }

  /**
   * SagePay payment has returned a status we do not understand
   * @param $response
   * @param $params
   * @return array
   */
  private function error($response, $params) {
    $msg = "Unfortunately, it seems there was a problem with your credit card details – please double check your billing address and card details and try again";
  //  drupal_set_message($msg, 'error');
    watchdog('SagePay', $response["StatusDetail"], $response, WATCHDOG_ERROR);
    self::createFailedContribution($response, $params);
    return $this->createError($msg, $response);
  }
  
  /**
   * SagePay payment has failed
   * @param $response
   * @param $params
   * @return array
   */
  private function rejected($response, $params) {
    $msg = "Unfortunately, it seems the authorisation was  rejected – please double check your billing address and card details and try again.";
    self::createFailedContribution($response, $params);
    return $this->createError($msg, $response);
  }
  /**
   * Create a contribution record for CC transactions that fail.
   *
   * @param $response
   * @param $params
   */
  private function createFailedContribution(&$response, &$params) {
    // Set value to 0 so that CRM/Event/Registration/Confirm->postProcess()
    // does not later also create a Contribution and Transaction
    $response['amount'] = 0;

    // Retrieve or create a Contact object
    require_once 'api/api.php';
    $defaults = $params;
    $defaults['version'] = 3;
    $defaults['contact_type'] = 'Individual';
    if ($params['contact_id']) {
      $contact = civicrm_api('Contact', 'Get', array('id' => $params['contact_id'], 'version' => 3));
    } else {
      $contact = civicrm_api('Contact', 'Create', $defaults);
      $params['contact_id'] = $contact['id'];
    }
  
    $contribution_values = array(
      'contact_id' => $contact['id'],
      'contribution_status_id' => 4,
      'cancel_reason' => $response['StatusDetail'],
      'cancel_date' => CRM_Utils_Date::getToday(),
      'version' => 3,
    );

    // Add event data if this is an event payment
    if ($this->_paymentForm && $this->_paymentForm->_values['event']) {
      $contribution_values['financial_type_id'] = $this->_paymentForm->_values['event']['financial_type_id'];
      $contribution_values['campaign_id'] = $this->_paymentForm->_values['event']['campaign_id'];
      $contribution_values['source'] = $this->_paymentForm->_values['event']['title'];
    }

    // Create the contribution. We don't need to do anything with it, but it's here for inspection if required.
    $contribution = civicrm_api('Contribution', 'Create', $contribution_values);
  
  }

  /**
   * Helper method, gets country iso code from id
   */
  private function getCountryISO($country_id) {
    static $countries = array();
    if ( !empty( $country_id && ! isset($countries[$country_id])) ) {
        $sql    = "SELECT iso_code FROM civicrm_country WHERE id = %1";
        $params = array( 1 => array( $country_id , 'Integer' ) );
        $dao    = CRM_Core_DAO::executeQuery( $sql, $params );
        $dao->fetch();
        $countries[$country_id] = $dao->iso_code;
    }
    return $countries[$country_id]; 
  }


  /**
   * Sets appropriate parameters for checking out to UCM Payment Collection
   *
   * @param array $params  name value pair of contribution datat
   * @param $component
   * @access public
   *
   */
  function doTransferCheckout( &$params, $component ) {
    CRM_Core_Error::fatal(ts('This function is not implemented'));
  }
}
