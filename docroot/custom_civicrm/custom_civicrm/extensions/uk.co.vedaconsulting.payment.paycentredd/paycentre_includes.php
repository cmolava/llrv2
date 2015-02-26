<?php
/**************************************************************************************************
* Paycentre PHP Kit Includes File
***************************************************************************************************

***************************************************************************************************
* Change history
* ==============
*
* 10/08/2012 - Parvez Saleh - Created
*
***************************************************************************************************
* Description
* ===========
*
* Functions to allow communication with Paycentre REST API's
***************************************************************************************************/

ob_start();
//session_start();

/**************************************************************************************************
* Useful functions for all pages in this kit
**************************************************************************************************/

// New function to encode the XML strings
function xmlentities($string) {
    return str_replace(array("&", "<", ">", "\"", "'"),
        array("&amp;", "&lt;", "&gt;", "&quot;", "&apos;"), $string);
}


//Function to redirect browser
function redirect($url)
{
   if (!headers_sent())
        header('Location: '.$url);
   else
   {
        echo '<script type="text/javascript">';
        echo 'window.location.href="'.$url.'";';
        echo '</script>';
        echo '<noscript>';
        echo '<meta http-equiv="refresh" content="0;url='.$url.'" />';
        echo '</noscript>';
   }
}

/* Base 64 Encoding function **
** PHP does it natively but just for consistency and ease of maintenance, let's declare our own function **/

function base64Encode($plain) {
  // Initialise output variable
  $output = "";

  // Do encoding
  $output = base64_encode($plain);

  // Return the result
  return $output;
}

/* Base 64 decoding function **
** PHP does it natively but just for consistency and ease of maintenance, let's declare our own function **/

function base64Decode($scrambled) {
  // Initialise output variable
  $output = "";

  // Fix plus to space conversion issue
  $scrambled = str_replace(" ","+",$scrambled);

  // Do encoding
  $output = base64_decode($scrambled);

  // Return the result
  return $output;
}

// Function to check validity of email address entered in form fields
function is_valid_email($email) {
  $result = TRUE;
  if(!eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$", $email)) {
    $result = FALSE;
  }
  return $result;
}

function errorMessage ($errorCode) {

    switch($errorCode) {
        case 'BadCountryCode':
            return 'Unrecognisable Country';
        case 'BadCheckDigit':
            return 'BadCheckDigit';
        case 'BadAccountNumberFormat':
            return 'Account Number not recognised';
        case 'BadIbanChecksum':
            return 'IBAN number is invalid';
        case 'BadDbanChacksum':
            return 'DBAN Number is invalid';
       case 'BadBankCode':
            return 'Bank not recognised';
       case 'BadBranchCode':
            return 'Branch code not recognised';
       case 'BadAccountNumber':
            return 'Account number is invalid';
       case 'BadSortcode':
            return 'Sort code is invalid';
       case 'BadCharacter':
            return 'Invalid characters';
       case 'BadLength':
            return 'Pleae correct the sort code or account number';
       case 'BadNumberOfValues':
            return 'Error in data';
       case 'NotSupported':
            return 'Not Supported';
       case 'BankLookupFailed':
            return 'Bank cannot be located';
       case 'CustomAccountValidationFailed':
            return 'Your account has not been recognised';
       case 'NoBic':
            return 'BIC Number is invalid';
       case 'PreviouslySubmitted':
            return 'Your Direct Debit Request has been processed, please check your email for confirmation.';
        default:
            return 'Error occurred while processing your request. Please contact Customer Services.';
    }

}


function getSoapheader($username, $password, $ApplicationID) {

    $soap_header  = "<SOAP-ENV:Header>\n";
    $soap_header .= "<ns1:OperatorInfo>\n";
    $soap_header .= "<ns1:ApplicationID>". $ApplicationID ."</ns1:ApplicationID>\n";
    $soap_header .= "<ns1:OperatorID>". $username ."</ns1:OperatorID>\n";
    $soap_header .= "<ns1:OperatorPassword>". $password ."</ns1:OperatorPassword>\n";
    $soap_header .= "<ns1:HASH>string</ns1:HASH>\n";
    $soap_header .= "</ns1:OperatorInfo>\n";
    $soap_header .= "</SOAP-ENV:Header>\n";

    return $soap_header;

}

/*************************************************************
  Send a post request with cURL
    $url = URL to send request to
    $data = POST data to send (in URL encoded Key=value pairs)
*************************************************************/
function requestPost($url, $data, $username, $password, $ApplicationID){
    // Set a one-minute timeout for this script
    set_time_limit(160);

    $sortcode      = $data['bank_identification_number'];
    $accountNumber = $data['bank_account_number'];

    // Construct post string
    $xml_encoding = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

    $soapAction   = "SOAPAction: \"http://directdebit.com/pcws.V1.0.0.1/ExecuteAction\"";

    $soapEnvelope = "<SOAP-ENV:Envelope xmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:ns1=\"http://directdebit.com/pcws.V1.0.0.1\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:s=\"http://www.w3.org/2001/XMLSchema\" >\n";

    $soap_header  = getSoapheader($username, $password, $ApplicationID);

    $soap_request  = "  <SOAP-ENV:Body>\n";
    $soap_request .= "    <ns1:ExecuteAction>\n";
    $soap_request .= "      <ns1:request xsi:type=\"ns1:ValidateSCANRequest\">\n";
    $soap_request .= "        <ns1:SortCode>". $sortcode ."</ns1:SortCode>\n";
    $soap_request .= "        <ns1:AccountNumber>". $accountNumber ."</ns1:AccountNumber>\n";
    $soap_request .= "      </ns1:request>\n";
    $soap_request .= "    </ns1:ExecuteAction>\n";
    $soap_request .= "  </SOAP-ENV:Body>\n";
    $soap_request .= "</SOAP-ENV:Envelope>\n";

    $soapBody      = $soap_request;

    $soapRequest = $xml_encoding.$soapEnvelope.$soap_header.$soapBody;

    $HTTPHeader = array(
        "Content-type: text/xml;charset=\"utf-8\"",
        "Accept: text/xml",
        "Cache-Control: no-cache",
        "Pragma: no-cache",
        $soapAction,
        "Content-length: ".strlen($soapRequest),
    );

    $session = curl_init();
    curl_setopt($session, CURLOPT_URL, $url );
    curl_setopt($session, CURLOPT_CONNECTTIMEOUT, 100);
    curl_setopt($session, CURLOPT_TIMEOUT,        100);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true );
    curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($session, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($session, CURLOPT_POST,           true );
    curl_setopt($session, CURLOPT_POSTFIELDS,     $soapRequest);
    curl_setopt($session, CURLOPT_HTTPHEADER,     $HTTPHeader);

    // Send payment POST to the target URL
    $output = curl_exec($session);
    $header = curl_getinfo( $session );

    //SimpleXML doesn't recognise SOAP tag prefixes so remove them all
    $xml_string = preg_replace('|<soap:|','<soap', $output);
    $xml_string = preg_replace('|</soap:|','</soap',$xml_string);

    //Load SimpleXML friendly XML into a SimpleXML object
    $xml = simplexml_load_string($xml_string);

    //Store the raw response for later as it's useful to see for integration and understanding
    $_SESSION["rawresponse"] = $output;

    require_once('CRM/Core/Error.php');
    CRM_Core_Error::debug_log_message('Validate Response');
    CRM_Core_Error::debug_log_message($_SESSION["rawresponse"]);

    if(curl_errno($session)) {
        $resultsArray = json_decode(json_encode((array) $xml),1);
        $resultsArray["Status"] = "FAIL";
        $resultsArray['StatusDetail'] = curl_error($session);
    }
    else {
        $resultsArray = array();
        $responseStatus = (string)$xml->soapBody->ExecuteActionResponse->ExecuteActionResult->Result;
        if ($responseStatus == "OK") {
           $resultsArray["Status"] = "OK";
           $resultsArray['iban'] = (string)$xml->soapBody->ExecuteActionResponse->ExecuteActionResult->IBAN;
        } else {
           $resultsArray['Status'] = (string)$xml->soapBody->ExecuteActionResponse->ExecuteActionResult->Result;
        }
    }

    // Return the output
    return $resultsArray;

} // END function requestPost()

/*************************************************************
  Send a post request with cURL
    $url = URL to send request to
    $data = POST data to send (in URL encoded Key=value pairs)
*************************************************************/
function insertEntityDebtor($url, $data, $username, $password, $ApplicationID){
    // Set a one-minute timeout for this script
    set_time_limit(160);

    $firstname           = $data['first_name'];
    $lastname            = $data['last_name'];
    $reference           = $data['ddi_reference'];
    $ddiScore            = 'Passed';
    $validation          = 'true';
    $created             = date('Y-m-d\TH:i:s'); //'2012-10-08T10:31:18Z';
    $lastChanged         = date('Y-m-d\TH:i:s'); //'2012-10-08T10:31:18Z';
    $dateOfBirth         = null; //'2072-10-08T10:31:15Z';
    $status              = 'Active';
    $creationMethod      = 'Manual';
    $addressLine1        = $data['billing_street_address-5'];
    $addressLine2        = '';
    $addressLine3        = '';
    $city                = $data['billing_city-5'];
    $county              = $data['billing_state_province_id-5'];
    $country             = $data['billing_country_id-5'];
    $postcode            = $data['billing_postal_code-5'];
//    $companyName         = $data['company_name'];
    $workNumber          = null; //'12345678';
    $faxNumber           = null; //'12345678';
    $email               = $data['email'];
    $isCreditCardAddress = 'false';
    $isLetterAddress     = 'true';

    // Construct post string
    $xml_encoding = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

    $soapAction   = "SOAPAction: \"http://directdebit.com/pcws.V1.0.0.1/InsertEntity\"";

    $soapEnvelope = "<SOAP-ENV:Envelope xmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:ns1=\"http://directdebit.com/pcws.V1.0.0.1\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:s=\"http://www.w3.org/2001/XMLSchema\" >\n";

    $soap_header  = getSoapheader($username, $password, $ApplicationID);

    $soap_request  = " <SOAP-ENV:Body>\n";
    $soap_request .= "    <ns1:InsertEntity>\n";
    $soap_request .= "      <ns1:entity xsi:type=\"ns1:Debtor\">\n";
    $soap_request .= "        <ns1:ID>00000000-0000-0000-0000-000000000000</ns1:ID>\n";
    $soap_request .= "        <ns1:ApplicationID>". $ApplicationID ."</ns1:ApplicationID>\n";
    $soap_request .= "        <ns1:PreferredCommunicationMethod>Email</ns1:PreferredCommunicationMethod>\n";
    $soap_request .= "        <ns1:FirstName>". xmlentities($firstname) ."</ns1:FirstName>\n";
    $soap_request .= "        <ns1:LastName>". xmlentities($lastname) ."</ns1:LastName>\n";
    $soap_request .= "        <ns1:Reference>". xmlentities($reference) ."</ns1:Reference>\n";
    $soap_request .= "        <ns1:DDiDScore>". $ddiScore ."</ns1:DDiDScore>\n";
    $soap_request .= "        <ns1:Validation>". $validation ."</ns1:Validation>\n";
    $soap_request .= "        <ns1:Created>". $created ."</ns1:Created>\n";
    $soap_request .= "        <ns1:LastChanged>". $lastChanged ."</ns1:LastChanged>\n";
 //   $soap_request .= "        <ns1:DateofBirth>". $dateOfBirth ."</ns1:DateofBirth>\n";
    $soap_request .= "        <ns1:Status>". $status ."</ns1:Status>\n";
    $soap_request .= "        <ns1:CreationMethod>". $creationMethod ."</ns1:CreationMethod>\n";
    $soap_request .= "        <ns1:MainAddress>\n";
    $soap_request .= "          <ns1:AddressLine1>". xmlentities($addressLine1) ."</ns1:AddressLine1>\n";
 //   $soap_request .= "          <ns1:AddressLine2>". $addressLine2 ."</ns1:AddressLine2>\n";
 //   $soap_request .= "          <ns1:AddressLine3>". $addressLine3 ."</ns1:AddressLine3>\n";
    $soap_request .= "          <ns1:City>". xmlentities($city) ."</ns1:City>\n";
 //   $soap_request .= "          <ns1:CompanyName>". $companyName ."</ns1:CompanyName>\n";
    $soap_request .= "          <ns1:Country>". $country ."</ns1:Country>\n";
    $soap_request .= "          <ns1:County>". $county ."</ns1:County>\n";
    $soap_request .= "          <ns1:Email>". $email ."</ns1:Email>\n";
    $soap_request .= "          <ns1:PostCode>". $postcode ."</ns1:PostCode>\n";
 //   $soap_request .= "          <ns1:WorkNumber>". $workNumber ."</ns1:WorkNumber>\n";
 //   $soap_request .= "          <ns1:FaxNumber>". $faxNumber ."</ns1:FaxNumber>\n";
    $soap_request .= "          <ns1:IsCreditCardAddress>". $isCreditCardAddress ."</ns1:IsCreditCardAddress>\n";
    $soap_request .= "          <ns1:IsLetterAddress>". $isLetterAddress ."</ns1:IsLetterAddress>\n";
    $soap_request .= "        </ns1:MainAddress>\n";
    $soap_request .= "      </ns1:entity>\n";
    $soap_request .= "    </ns1:InsertEntity>\n";
    $soap_request .= "  </SOAP-ENV:Body>\n";
    $soap_request .= "</SOAP-ENV:Envelope>\n";

    $soapBody      = $soap_request;

    $soapRequest = $xml_encoding.$soapEnvelope.$soap_header.$soapBody;

    $HTTPHeader = array(
        "Content-type: text/xml;charset=\"utf-8\"",
        "Accept: text/xml",
        "Cache-Control: no-cache",
        "Pragma: no-cache",
        $soapAction,
        "Content-length: ".strlen($soapRequest),
    );

    $session = curl_init();
    curl_setopt($session, CURLOPT_URL, $url );
    curl_setopt($session, CURLOPT_CONNECTTIMEOUT, 100);
    curl_setopt($session, CURLOPT_TIMEOUT,        100);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true );
    curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($session, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($session, CURLOPT_POST,           true );
    curl_setopt($session, CURLOPT_POSTFIELDS,     $soapRequest);
    curl_setopt($session, CURLOPT_HTTPHEADER,     $HTTPHeader);

    // Send payment POST to the target URL
    $output = curl_exec($session);
    $header = curl_getinfo( $session );

    //SimpleXML doesn't recognise SOAP tag prefixes so remove them all
    $xml_string = preg_replace('|<soap:|','<soap', $output);
    $xml_string = preg_replace('|</soap:|','</soap',$xml_string);
//print('<br>xml_string=<br>');
//    print($xml_string);
    //Load SimpleXML friendly XML into a SimpleXML object
    $xml = simplexml_load_string($xml_string);

    //Store the raw response for later as it's useful to see for integration and understanding
    $_SESSION["rawresponse"] = $output;

    require_once('CRM/Core/Error.php');
    CRM_Core_Error::debug_log_message('Debtor Request');
    CRM_Core_Error::debug_log_message($soap_request);    
    CRM_Core_Error::debug_log_message('Debtor Response');
    CRM_Core_Error::debug_log_message($_SESSION["rawresponse"]);

    watchdog('CiviCRM DD Debtor Response', $_SESSION["rawresponse"]);

// print('<br>InsertEntityResult=<br>');
//    print((string)$xml->soapBody->InsertEntityResponse->InsertEntityResult);

    /* TODO We should be checking the response to ensure the Debtor is created as we want? */

    if(curl_errno($session)) {
        $resultsArray = json_decode(json_encode((array) $xml),1);
        $resultsArray["Status"] = "FAIL";
        $resultsArray['StatusDetail'] = curl_error($session);
    }
    else {
        $resultsArray = array();
        $resultsArray['Status'] = 'OK';
        $resultsArray['debtor_reference_number'] = (string)$xml->soapBody->InsertEntityResponse->InsertEntityResult;
    }

//echo "output=".$output;
//print_r($resultsArray);
//die;

    // Return the output
    return $resultsArray;

} // END function insertEntityDebtor()


/*************************************************************
  Send a post request with cURL
    $url = URL to send request to
    $data = POST data to send (in URL encoded Key=value pairs)
*************************************************************/
function insertEntityPaymentPlan($url, $data, $username, $password, $ApplicationID, $paycentreResponses){
    // Set a one-minute timeout for this script
    set_time_limit(160);
//print("Data = ");print_r($data); print("paycentreResponses = ");print_r($paycentreResponses); die;

    $debtorID           = $paycentreResponses['debtor_reference_number'];
    $origin             = 'Web';
    $status             = 'Active';
    $lastCollected      = '0001-01-01T00:00:00';
    $restrictedDate     = '0001-01-01T00:00:00';
    $bankAccountStatus  = 'Valid';
    $accountName        = $data['account_holder'];
    $IBAN               = $paycentreResponses['iban'] ;
//    $BIC                = '';
    $isAdhocs           = 'false';
    $reference          = $data['ddi_reference'];
    $collected          = '0';
    $extracted          = '0';
    $lastChanged        = '0001-01-01T00:00:00';
    $alternateReference = $data['payer_reference'];
    $recurrencePattern  = 'Monthly';     /* TODO Fix This */
    $monthlyPick1       = 'First';       /* TODO Fix This */
    $monthlyPick2       = 'Day';         /* TODO Fix This */
    $everyNth           = '1';
    $endPattern         = 'NoEnd';
    $regularAmount      = $data['amount'];
    $firstAmount        = '0';
    $lastAmount         = '0';
    $totalAmount        = '0.0';
    //$noOfDebits         = '10';     Not Used
    $amountType         = 'Regular';
    $startDateYear      = substr($data['start_date'] , 0, 4);
    $startDateMonth     = substr($data['start_date'] , 5, 2);
    $startDateDay       = substr($data['start_date'] , 8, 2);
    $startDateActualDate= $data['start_date']. 'T00:00:00';
    $comments           = 'CiviCRM Direct Debit Plan';
    $IBANDomesticCheck  = 'true';
    $monthOfYear        = 'None';
//    $weekDays           = '';
//    $daysOfMonth        = '';
    $type               = 'Account';

    // Construct post string
    $xml_encoding = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

    $soapAction   = "SOAPAction: \"http://directdebit.com/pcws.V1.0.0.1/InsertEntity\"";

    $soapEnvelope = "<SOAP-ENV:Envelope xmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\" xmlns:ns1=\"http://directdebit.com/pcws.V1.0.0.1\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:s=\"http://www.w3.org/2001/XMLSchema\" >\n";

    $soap_header  = getSoapheader($username, $password, $ApplicationID);

    $soap_request  = " <SOAP-ENV:Body>\n";
    $soap_request .= '    <ns1:InsertEntity xmlns="http://directdebit.com/pcws.V1.0.0.1">\n';
    $soap_request .= "      <ns1:entity xsi:type=\"ns1:PaymentPlan\">\n";
    $soap_request .= "        <ns1:ID>00000000-0000-0000-0000-000000000000</ns1:ID>\n";
    $soap_request .= "        <ns1:DebtorID>". $debtorID ."</ns1:DebtorID>\n";
    $soap_request .= "        <ns1:ApplicationID>". $ApplicationID ."</ns1:ApplicationID>\n";
    $soap_request .= "        <ns1:Origin>". $origin ."</ns1:Origin>\n";
    $soap_request .= "        <ns1:Status>". $status ."</ns1:Status>\n";
    $soap_request .= "        <ns1:LastCollected>". $lastCollected ."</ns1:LastCollected>\n";
    $soap_request .= "        <ns1:RestrictedDate>". $restrictedDate ."</ns1:RestrictedDate>\n";
    $soap_request .= "        <ns1:BankAccountStatus>". $bankAccountStatus ."</ns1:BankAccountStatus>\n";
    $soap_request .= "        <ns1:AccountName>". xmlentities($accountName) ."</ns1:AccountName>\n";
    $soap_request .= "        <ns1:IBAN>". $IBAN ."</ns1:IBAN>\n";
//    $soap_request .= "        <ns1:BIC>". $BIC ."</ns1:BIC>\n";
    $soap_request .= "        <ns1:IsAdhocs>". $isAdhocs ."</ns1:IsAdhocs>\n";
    $soap_request .= "        <ns1:Reference>". $reference ."</ns1:Reference>\n";
    $soap_request .= "        <ns1:Collected>". $collected ."</ns1:Collected>\n";
    $soap_request .= "        <ns1:Extracted>". $extracted ."</ns1:Extracted>\n";
    $soap_request .= "        <ns1:LastChanged>". $lastChanged ."</ns1:LastChanged>\n";
    $soap_request .= "        <ns1:AlternateReference>". $alternateReference ."</ns1:AlternateReference>\n";
    $soap_request .= "        <ns1:RecurrencePattern>". $recurrencePattern ."</ns1:RecurrencePattern>\n";
    $soap_request .= "        <ns1:MonthlyPick1>". $monthlyPick1 ."</ns1:MonthlyPick1>\n";
    $soap_request .= "        <ns1:MonthlyPick2>". $monthlyPick2 ."</ns1:MonthlyPick2>\n";
    $soap_request .= "        <ns1:EveryNth>". $everyNth ."</ns1:EveryNth>\n";
    $soap_request .= "        <ns1:EndPattern>". $endPattern ."</ns1:EndPattern>\n";
    $soap_request .= "        <ns1:RegularAmount>". $regularAmount ."</ns1:RegularAmount>\n";
    $soap_request .= "        <ns1:FirstAmount>". $firstAmount ."</ns1:FirstAmount>\n";
    $soap_request .= "        <ns1:LastAmount>". $lastAmount ."</ns1:LastAmount>\n";
    $soap_request .= "        <ns1:TotalAmount>". $totalAmount ."</ns1:TotalAmount>\n";
    //$soap_request .= "        <ns1:NoOfDebits>". $noOfDebits ."</ns1:NoOfDebits>\n";
    $soap_request .= "        <ns1:AmountType>". $amountType ."</ns1:AmountType>\n";
    $soap_request .= "        <ns1:StartDate>\n";
    $soap_request .= "        <ns1:Day>". $startDateDay ."</ns1:Day>\n";
    $soap_request .= "        <ns1:Month>". $startDateMonth ."</ns1:Month>\n";
    $soap_request .= "        <ns1:Year>". $startDateYear ."</ns1:Year>\n";
    $soap_request .= "        <ns1:ActualDate>". $startDateActualDate ."</ns1:ActualDate>\n";
    $soap_request .= "        </ns1:StartDate>\n";
    $soap_request .= "        <ns1:Comments>". xmlentities($comments) ."</ns1:Comments>\n";
    $soap_request .= "        <ns1:IBANDomesticCheck>". $IBANDomesticCheck ."</ns1:IBANDomesticCheck>\n";
    $soap_request .= "        <ns1:MonthOfYear>". $monthOfYear ."</ns1:MonthOfYear>\n";
    $soap_request .= "        <ns1:WeekDays>None</ns1:WeekDays>\n";
    // PS 16-01-2013
    // Amended as the Days of the Month should be the prefered collection day and not the 15 for everyone
    // $soap_request .= "        <ns1:DaysOfMonth>D15</ns1:DaysOfMonth>\n";
    $soap_request .= "        <ns1:DaysOfMonth>D".ltrim($startDateDay, "0")."</ns1:DaysOfMonth>\n";
    
    $soap_request .= "        <ns1:Type>". $type ."</ns1:Type>\n";
    $soap_request .= "      </ns1:entity>\n";
    $soap_request .= "    </ns1:InsertEntity>\n";
    $soap_request .= "  </SOAP-ENV:Body>\n";
    $soap_request .= "</SOAP-ENV:Envelope>\n";

    $soapBody      = $soap_request;

    $soapRequest = $xml_encoding.$soapEnvelope.$soap_header.$soapBody;

    $HTTPHeader = array(
        "Content-type: text/xml;charset=\"utf-8\"",
        "Accept: text/xml",
        "Cache-Control: no-cache",
        "Pragma: no-cache",
        $soapAction,
        "Content-length: ".strlen($soapRequest),
    );

    $session = curl_init();
    curl_setopt($session, CURLOPT_URL, $url );
    curl_setopt($session, CURLOPT_CONNECTTIMEOUT, 100);
    curl_setopt($session, CURLOPT_TIMEOUT,        100);
    curl_setopt($session, CURLOPT_RETURNTRANSFER, true );
    curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($session, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($session, CURLOPT_POST,           true );
    curl_setopt($session, CURLOPT_POSTFIELDS,     $soapRequest);
    curl_setopt($session, CURLOPT_HTTPHEADER,     $HTTPHeader);

    // Send payment POST to the target URL
    $output = curl_exec($session);
    $header = curl_getinfo( $session );

    //SimpleXML doesn't recognise SOAP tag prefixes so remove them all
    $xml_string = preg_replace('|<soap:|','<soap', $output);
    $xml_string = preg_replace('|</soap:|','</soap',$xml_string);
//print('<br>xml_string=<br>');
 //   print($xml_string);
    //Load SimpleXML friendly XML into a SimpleXML object
    $xml = simplexml_load_string($xml_string);

    //Store the raw response for later as it's useful to see for integration and understanding
    $_SESSION["rawresponse"] = $output;

    require_once('CRM/Core/Error.php');
    CRM_Core_Error::debug_log_message('Plan Request');
    CRM_Core_Error::debug_log_message($soap_request);
    CRM_Core_Error::debug_log_message('Plan Response');
    CRM_Core_Error::debug_log_message($_SESSION["rawresponse"]);

    watchdog('CiviCRM DD Payplan Response', $_SESSION["rawresponse"]);

 //print('<br>insertEntityPaymentPlan=<br>');
 //   print_r($xml);
 //   die;
//    print((string)$xml->soapBody->InsertEntityResponse->InsertEntityResult);

    if(curl_errno($session)) {
        $resultsArray = json_decode(json_encode((array) $xml),1);
        $resultsArray["Status"] = "FAIL";
        $resultsArray['StatusDetail'] = curl_error($session);
    }
    else {
        $resultsArray = array();
        $resultsArray['Status'] = 'OK';
        $resultsArray['payment_plan_reference'] = (string)$xml->soapBody->InsertEntityResponse->InsertEntityResult;

    }

//echo "output=".$output;
//print_r($resultsArray);
//die;

    // Return the output
    return $resultsArray;

} // END function insertEntityPaymentPlan()


?>