<?php

require_once 'UK_Direct_Debit/Form/Main.php';
require_once 'CRM/Core/Payment.php';
include("paycentre_includes.php");

/* @todo Calculate Collection Date
 * @todo Need to Store the SUN somewhere
 * @todo
 *
 *
 */


class uk_co_vedaconsulting_payment_paycentredd extends CRM_Core_Payment {

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
   * @static
   */
  static protected $_mode = null;

  /**
   * Constructor
   *
   * @param string $mode the mode of operation: live or test
   * @param $paymentProcessor
   */
  function __construct( $mode, &$paymentProcessor ) {
    $this->_mode             = $mode;
    $this->_paymentProcessor = $paymentProcessor;
    $this->_processorName    = ts('Paycentre Direct Debit Processor');
  }

  /**
   * singleton function used to manage this object
   *
   * @param string $mode the mode of operation: live or test
   * @param $paymentProcessor
   * @return object
   * @static
   *
   */
  static function &singleton( $mode, &$paymentProcessor ) {
      $processorName = $paymentProcessor['name'];
      if (self::$_singleton[$processorName] === null ) {
          self::$_singleton[$processorName] = new self( $mode, $paymentProcessor );
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

    /* TO DO
     * Add check to ensure password is also set
     * Also the URL's for api site
     */

    if (!empty($error)) {
      return implode('<p>', $error);
    }
    else {
      return NULL;
    }
  }

  function rand_str( $len )
  {
    // The alphabet the random string consists of
    $abc = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    // The default length the random key should have
    $defaultLength = 3;

    // Ensure $len is a valid number
    // Should be less than or equal to strlen( $abc ) but at least $defaultLength
    $len = max( min( intval( $len ), strlen( $abc )), $defaultLength );

    // Return snippet of random string as random string
    return substr( str_shuffle( $abc ), 0, $len );
  }

  function getUserEmail(&$params) {
    // Set email
    if (!empty($params['email-Primary'])) {
      $useremail = $params['email-Primary'];
    } else {
      $useremail = $params['email-5'];
    }
    return $useremail;
  }

  protected function getCRMVersion() {
    $crmversion = explode('.', ereg_replace('[^0-9\.]','', CRM_Utils_System::version()));
    return floatval($crmversion[0] . '.' . $crmversion[1]);
  }

  /*
   * From the selected collection day determine when the actual collection start date could be
   * For direct debit we need to allow 10 working days prior to collection for cooling off
   * We also may need to send them a letter etc
   *
   */
  function getCollectionStartDate(&$params) {

    $preferredCollectionDay = $params['preferred_collection_day'];

    $d = UK_Direct_Debit_Form_Main::firstCollectionDate($preferredCollectionDay, null);

    return $d;
  }

  /*
   * Determine the frequency based on the recurring params if set
   * Should check the [frequency_unit] and if set use that
   * If not set then default to D
   */
  function getCollectionFrequency(&$params) {
    return 'M';
  }
  
  function preparePostArray($fields, $self = null) {

    /*
     * TO DO
     * Promotion - Need to get the page ID
     */

    $useremail = self::getUserEmail($fields);
    $collectionDate = self::getCollectionStartDate($fields);
    CRM_Core_Error::debug_log_message('preparePostArray collectionDate='.$collectionDate->format('Y-m-d H:i:s'));
    $collectionFrequency = self::getCollectionFrequency($fields);
 
    $companyName = UK_Direct_Debit_Form_Main::getCompanyName();
    $SUN = UK_Direct_Debit_Form_Main::getSUN();

    $amount = 0;
    $serviceUserId = null;

    if ( isset($fields['amount']) ) {
        // Set amount in pence if not already set that way.
        $amount = $fields['amount'];
        if (is_int($amount)) {
            $amount = $amount * 100;
        }
    }

    if ( isset($self->_paymentProcessor['signature']) ) {
        $serviceUserId = $self->_paymentProcessor['signature'];
    }
    
    if (isset($fields['contactID'])) {
        /* Direct Transfer Record should now be created with the external identifier, not the PC Ref */
        // Check if the contact exists
        require_once 'CRM/Contact/DAO/Contact.php';
        $ddContact = new CRM_Contact_DAO_Contact();
        $ddContact->id = $fields['contactID'];
        $ddContact->find();
        if (!$ddContact->fetch()) {
            throw new CRM_Finance_BAO_Import_ValidateException(
                    "Can't find contact for this id");
        }

        // PS 16-1-2013
        // Amended as the payer ref should be the external identifier
        // Not the database id
        // $payerReference = $fields['contactID'];
        $payerReference = $ddContact->external_identifier;
    }
    else {
        $payerReference = 'CIVICRMEXT';
    }    
    
    // Construct params list to send to Smart Debit ...
    $directDebitParams = array(
//      'variable_ddi[service_user][pslid]' => $self->_paymentProcessor['signature'],
      'variable_ddi[service_user][pslid]' => $serviceUserId,
      'variable_ddi[reference_number]'    => $fields['ddi_reference'],
      'variable_ddi[payer_reference]'     => $payerReference,
      'variable_ddi[first_name]'          => $fields['billing_first_name'],
      'variable_ddi[last_name]'           => $fields['billing_last_name'],
      'variable_ddi[address_1]'           => $fields['billing_street_address-5'],
      'variable_ddi[town]'                => $fields['billing_city-5'],
      'variable_ddi[postcode]'            => $fields['billing_postal_code-5'],
      'variable_ddi[country]'             => $fields['billing_country_id-5'], //*** $params['billing_country-5']
      'variable_ddi[account_name]'        => $fields['account_holder'],
      'variable_ddi[sort_code]'           => $fields['bank_identification_number'],
      'variable_ddi[account_number]'      => $fields['bank_account_number'],
      'variable_ddi[regular_amount]'      => $amount,
      'variable_ddi[first_amount]'        => $amount,
      'variable_ddi[default_amount]'      => $amount,
      'variable_ddi[start_date]'          => $collectionDate->format("Y-m-d"),
//      'variable_ddi[promotion]'           => $fields['page_id'], //*** contributionPageID
      'variable_ddi[email_address]'       => $useremail,
      'variable_ddi[company_name]'        => $companyName,
      'variable_ddi[frequency_type]'      => $collectionFrequency,
    );
    return $directDebitParams;
  }

  function record_debtor_reference($ddi_reference, $debtor_reference) {

    $additionalDetails1 = 'Debtor Reference = '. $debtor_reference;
    
    $sql  = " UPDATE civicrm_direct_debit SET ";
    $sql .= "  additional_details1 = %0 ";
    $sql .= " WHERE ddi_reference = %1 ";

    CRM_Core_DAO::executeQuery($sql, array(
        array((string)$additionalDetails1, 'String'),
        array((string)$ddi_reference,      'String'),
    ));
    
  }    

  function record_payment_plan_reference($ddi_reference, $payment_plan_reference) {

    $additionalDetails2 = 'Payment Plan Reference = '. $payment_plan_reference;
    
    $sql  = " UPDATE civicrm_direct_debit SET ";
    $sql .= "  additional_details2 = %0 ";
    $sql .= " WHERE ddi_reference = %1 ";

    CRM_Core_DAO::executeQuery($sql, array(
        array((string)$additionalDetails2, 'String'),
        array((string)$ddi_reference,      'String'),
    ));
    
  } 
  
  function validatePayment($fields, $files, $self) {
    
    $validateParams = $fields;

    /* First thing to do is check if the DD has already been submitted */
    if (UK_Direct_Debit_Form_Main::isDDSubmissionComplete($fields['ddi_reference'])) {
        $response[] = "PreviouslySubmitted";
        return self::invalid($response, $validateParams);
    }
    
    $directDebitParams = self::preparePostArray($validateParams, $self);  

/*     
print("<br><br>fields="); print_r($fields);
print("<br><br>files="); print_r($files);
print("<br><br>self="); print_r($self);
print("<br><br>directDebitParams="); print_r($directDebitParams);
die;*/ 

    // Get the API Username and Password
    $username = $self->_paymentProcessor['user_name'];
    $password = $self->_paymentProcessor['password'];
    $wsdl     = $self->_paymentProcessor['url_api'];
    $ApplicationID = $self->_paymentProcessor['signature'];

    // Send payment POST to the target URL
    $response = requestPost($wsdl, $validateParams, $username, $password, $ApplicationID);     

//Array ( [Status] => OK [iban] => GB84LOYD30997800912720 ) 

    $direct_debit_response = array();
    $direct_debit_response['data_type']                = 'recurring';
    $direct_debit_response['entity_type']              = 'contribution_recur';
    $direct_debit_response['first_collection_date']    = $directDebitParams['variable_ddi[start_date]'];
    $direct_debit_response['preferred_collection_day'] = $fields['preferred_collection_day'];
    $direct_debit_response['confirmation_method']      = $fields['confirmation_method'];
    $direct_debit_response['ddi_reference']            = $fields['ddi_reference'];
    $direct_debit_response['payment_plan_id']          = $fields['ddi_reference']. '-001';
    $direct_debit_response['response_status']          = $response['Status'];
    $direct_debit_response['response_raw']             = null;
    $direct_debit_response['entity_id']                = null;
    $direct_debit_response['bank_name']                = $fields['bank_name'];
    $direct_debit_response['branch']                   = null;
    $direct_debit_response['address1']                 = null;
    $direct_debit_response['address2']                 = null;
    $direct_debit_response['address3']                 = null;
    $direct_debit_response['address4']                 = null;
    $direct_debit_response['town']                     = null;
    $direct_debit_response['county']                   = null;
    $direct_debit_response['postcode']                 = null;
          
    if (!empty($response['error'])) {
        $direct_debit_response['response_raw']  = $response['error'];
    }

    // Take action based upon the response status
    switch ($response["Status"]) {
        case 'OK':
            
            if (!empty($response['iban'])) {
                    
                $direct_debit_response['entity_id']  = isset($fields['entity_id']) ? $fields['entity_id'] : 0;
                $direct_debit_response['iban']       = $response['iban'];            
  
                UK_Direct_Debit_Form_Main::record_response($direct_debit_response);            
           
                return self::validate_succeed($direct_debit_response, $fields);
                
            } 
            
        default:

            $_SESSION['contribution_attempt'] = 'failed';
            
            UK_Direct_Debit_Form_Main::record_response($direct_debit_response);            
            
            return self::invalid($response, $validateParams);
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
    $validateParams = $params;
    
    // PS 01022013
    // Added code to get the email address for the contect and add to the array
    $validateParams['email'] = self::getUserEmail($params);
    
    // Get the API Username and Password
    $username = $this->_paymentProcessor['user_name'];
    $password = $this->_paymentProcessor['password'];
    $wsdl     = $this->_paymentProcessor['url_api'];
    $ApplicationID = $this->_paymentProcessor['signature'];
    
    $directDebitParams = self::preparePostArray($validateParams);

    // Send payment POST to the target URL
//    $request_path   = 'api/ddi/variable/create';

    $response = requestPost($wsdl, $validateParams, $username, $password, $ApplicationID);
   
    $debtorResponse = insertEntityDebtor($wsdl, $validateParams, $username, $password, $ApplicationID);
    
    self::record_debtor_reference($validateParams['ddi_reference'], $debtorResponse['debtor_reference_number']);
   
    $paycentreResponses = array();
    $paycentreResponses['iban'] = $response['iban'];    
    $paycentreResponses['debtor_reference_number'] = $debtorResponse['debtor_reference_number'];
    $validateParams['payer_reference'] = $directDebitParams['variable_ddi[payer_reference]'];   
    $validateParams['payment_plan_id'] = $validateParams['ddi_reference']. '-001';
    $validateParams['start_date'] = $directDebitParams['variable_ddi[start_date]'];
  
    $paymentPlanResponse = insertEntityPaymentPlan($wsdl, $validateParams, $username, $password, $ApplicationID, $paycentreResponses);  
    
    self::record_payment_plan_reference($validateParams['ddi_reference'], $paymentPlanResponse['payment_plan_reference']);
    
    $status = array();

    /* Used to debug responses, not needed on live site
    $status[] = ts("Status = ". $response['Status']);
    $status[] = ts("Reference Number (IBAN) = ". $response['iban'] );
    $status[] = ts("Debtor Reference = ". $debtorResponse['debtor_reference_number']);
    $status[] = ts("Payment Plan Reference = ". $paymentPlanResponse['payment_plan_reference']);

    $status = @implode( '<br/>', $status );
    CRM_Core_Session::setStatus( $status );
     * 
     */

    // Take action based upon the response status
    switch ($response["Status"]) {
        case 'OK':
            return self::succeed($response, $validateParams);

        default:
            $_SESSION['contribution_attempt'] = 'failed';
            return self::invalid($response, $validateParams);
    }
    
  }

  /**
   * SagePay payment has succeeded
   * @param $response
   * @return array
   */
  private function validate_succeed($response, &$params) {
      
    // Clear any old error messages from stack  
    drupal_get_messages();
    
    $response['trxn_id'] = $response['iban'];

    return true;
  }

  /**
   * SagePay payment has succeeded
   * @param $response
   * @return array
   */
  private function succeed($response, &$params) {

    // Clear any old error messages from stack  
    drupal_get_messages();
    
    $response['trxn_id'] = $params['payment_plan_id'];

    UK_Direct_Debit_Form_Main::completeDirectDebitSetup($response, $params);

    return $response;
  }
  /**
   * SagePay payment has failed
   * @param $response
   * @param $params
   * @return array
   */
  private function invalid($response, $params) {
 //     print_r($response); die;
    $msg = "Unfortunately, it seems the details provided are invalid:";
    $msg .= "<ul>";
    foreach( $response as $error):
        $msg .= "<li>";
        $msg .= errorMessage($error);
        $msg .= "</li>";
    endforeach;
    $msg .= "</ul>";
   //       print($msg); die;
    drupal_set_message($msg,'invalid', false);

    watchdog('CiviCRM DD Invalid', $_SESSION["rawresponse"]);
    //self::createFailedContribution($response, $params);
    return CRM_Core_Error::createAPIError($msg, $response);
  }
  /**
   * SagePay payment has returned a status we do not understand
   * @param $response
   * @param $params
   * @return array
   */
  private function error($response, $params) {
    $msg = "Unfortunately, it seems there was a problem with your direct debit details – please double check your billing address and card details and try again";
    drupal_set_message($msg, 'error', false);
    watchdog('error', 'error');
    //self::createFailedContribution($response, $params);
    return CRM_Core_Error::createAPIError($msg, $response);
  }
  /**
   * SagePay payment has failed
   * @param $response
   * @param $params
   * @return array
   */
  private function rejected($response, $params) {
    $msg = "Unfortunately, it seems the authorisation was a rejected – please double check your billing address and card details and try again.";
    drupal_set_message($msg, 'rejected', false);
    watchdog('rejected', 'rejected');
    return CRM_Core_Error::createAPIError($msg, $response);
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

  function buildForm(&$form) {
          
    UK_Direct_Debit_Form_Main::buildDirectDebit($form );

    $form->addFormRule(array('uk_co_vedaconsulting_payment_paycentredd', 'validatePayment'), $form); 

    if (self::getCRMVersion() >= 4.2) {

        CRM_Core_Region::instance('billing-block')->update('default', array(
            'disabled' => TRUE,
          ));

        CRM_Core_Region::instance('billing-block')->add(array(
            'template' => 'CRM/Core/MyPayPalBlock.tpl',
            'weight'   => -1,
          ));
    }
  }


}

