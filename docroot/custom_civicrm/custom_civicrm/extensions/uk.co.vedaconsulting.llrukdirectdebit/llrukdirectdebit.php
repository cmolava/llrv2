<?php

require_once 'llrukdirectdebit.civix.php';

/**
 * Implementation of hook_civicrm_config
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function llrukdirectdebit_civicrm_config(&$config) {
  _llrukdirectdebit_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function llrukdirectdebit_civicrm_xmlMenu(&$files) {
  _llrukdirectdebit_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function llrukdirectdebit_civicrm_install() {
  
  //KJ 28/01/2015 
  // Start
  // This is to insert financial type id into civicrm_setting table for 'UK Direct Debit' group
  // as current 'Ukdirectdebit' extension does not insert this. This is required for smart debit sync
  $extensionDir       = dirname( __FILE__ ) . DIRECTORY_SEPARATOR;
  $sDbScriptsDir      = $extensionDir .  'sql' .  DIRECTORY_SEPARATOR;
  CRM_Utils_File::sourceSQLFile(CIVICRM_DSN, sprintf( "%supdate.sql", $sDbScriptsDir ) );
  //End
  return _llrukdirectdebit_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function llrukdirectdebit_civicrm_uninstall() {
  return _llrukdirectdebit_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function llrukdirectdebit_civicrm_enable() {
  return _llrukdirectdebit_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function llrukdirectdebit_civicrm_disable() {
  return _llrukdirectdebit_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function llrukdirectdebit_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _llrukdirectdebit_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function llrukdirectdebit_civicrm_managed(&$entities) {
  return _llrukdirectdebit_civix_civicrm_managed($entities);
}

/**
 * Implementation of hook_civicrm_caseTypes
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function llrukdirectdebit_civicrm_caseTypes(&$caseTypes) {
  _llrukdirectdebit_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implementation of hook_civicrm_alterSettingsFolders
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function llrukdirectdebit_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _llrukdirectdebit_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

function llrukdirectdebit_civicrm_findSmartDebitContact(&$params) {
  // Find the contact from web refernce using 'like' in query from contribuiton recurring table
  $contact = llrukdirectdebit_getContactFromString($params['transaction_id']);
  // If no contact found, then find the ocntact direct transfer custom group 
  If(!$contact) {
    $details = llrukdirectdebit_getContact($params['transaction_id']);
    $contact = $details['contact_id'];;
  }
  if ($contact) {
    $params['contact_id'] = $contact;
    $params['message']    = "<font color='green'>Contact Found. Contact ID: ".$contact;
  }
}

function llrukdirectdebit_civicrm_alterSmartDebitContributionParams(&$contributeParams, $smartdebitParams ) {
  if(empty($contributeParams['campaign_id'])) {
  //First find the campaign_id from 'reference_number'
    $details = llrukdirectdebit_getCampaign($smartdebitParams['reference_number']);
    if (!empty($details)) {
      $contributeParams['campaign_id'] = $details['campaign_id'];
    }
    // if no campaign_id , then try to get campaign_id from 'customer_id'
    else {
      if($smartdebitParams['customer_id']) {
        $details = llrukdirectdebit_getCampaign($smartdebitParams['customer_id']);
      }
      if (!empty($details)) {
        $contributeParams['campaign_id'] = $details['campaign_id'];
      }
    }
  }
}

// This method find the contact from recurring table using 'like' in the query as some trxn_ids have extra addition in it
function llrukdirectdebit_getContactFromString($reference) {
  $query = "select contact_id from `civicrm_contribution_recur` where trxn_id like '{$reference}%'";
  return CRM_Core_DAO::singleValueQuery($query);
}

function llrukdirectdebit_getContact($reference) {
  
  $DirectTranferCutomGroup = llrukdirectdebit_getCustomGroup($reference);
  $sourceTypeColumnName = $DirectTranferCutomGroup['sourceTypeColumnName'];
  $sourceDonorIdentifier = $DirectTranferCutomGroup['sourceDonorIdentifier'];
  $sourceCampaign = $DirectTranferCutomGroup['sourceCampaign'];
  $customGroupTableName = $DirectTranferCutomGroup['tableName'];
  $query = "SELECT id, entity_id, $sourceCampaign AS campaign_id FROM $customGroupTableName WHERE $sourceTypeColumnName = %0 AND $sourceDonorIdentifier = %1";
  
  $cparams = array(
      array('DIRECTDEBIT', 'String'),
      array(trim($reference), 'String'),
  );
  $financialImportDAO = CRM_Core_DAO::executeQuery($query, $cparams);
  $resultArray = array();
  while ( $financialImportDAO->fetch() ) {
    $resultArray['contact_id'] = $financialImportDAO->entity_id;
  }
  return $resultArray;
}

// This method used for finding campaign id from custom group
function llrukdirectdebit_getCampaign($reference) {
  $DirectTranferCutomGroup = llrukdirectdebit_getCustomGroup($reference);
  $sourceTypeColumnName = $DirectTranferCutomGroup['sourceTypeColumnName'];
  $sourceDonorIdentifier = $DirectTranferCutomGroup['sourceDonorIdentifier'];
  $sourceCampaign = $DirectTranferCutomGroup['sourceCampaign'];
  $customGroupTableName = $DirectTranferCutomGroup['tableName'];
  $query = "SELECT id, entity_id, $sourceCampaign AS campaign_id FROM $customGroupTableName WHERE $sourceTypeColumnName = %0 AND $sourceDonorIdentifier = %1 AND $sourceCampaign IS NOT NULL";

  $cparams = array(
      array('DIRECTDEBIT', 'String'),
      array(trim($reference), 'String'),
  );
  $financialImportDAO = CRM_Core_DAO::executeQuery($query, $cparams);
  $resultArray = array();
  while ( $financialImportDAO->fetch() ) {
      $resultArray['campaign_id'] = $financialImportDAO->campaign_id;
  }
  return $resultArray;
}

function llrukdirectdebit_getCustomGroup($reference) {
   // First get the group id for the Financial Import Reference Group
  $custom_group_name = 'Direct_Transfers';
  $customGroupParams = array(
      'version' => 3,
      'sequential' => 1,
      'name' => $custom_group_name,
  );
  $custom_group_ret = civicrm_api('CustomGroup', 'GET', $customGroupParams);
  if ($custom_group_ret['is_error'] || $custom_group_ret['count'] == 0) {
      throw new CRM_Finance_BAO_Import_ValidateException(
              "Can't find custom group for Financial_Import_Reference",
              $excCode,
              $value);
  }
  $customGroupID = $custom_group_ret['id'];
  $customGroupTableName = $custom_group_ret['values'][0]['table_name'];

  // Now try and find a record with the reference passed
  $customGroupParams = array(
      'version' => 3,
      'sequential' => 1,
      'custom_group_id' => $customGroupID,
  );
  $custom_field_ret = civicrm_api ('CustomField','GET',$customGroupParams);
  foreach($custom_field_ret['values'] as $k => $field){
    $field_attributes[$field['name']] = $field;
  }
  $resultArray = array();
   
  $resultArray['sourceTypeColumnName'] = $field_attributes['Direct_Transfer_Type']['column_name'];
  $resultArray['sourceDonorIdentifier'] = $field_attributes['Reference_Number']['column_name'];
  //$sourceActivePledge = $field_attributes['Active_pledge']['column_name'];
  $resultArray['sourceCampaign'] = $field_attributes['Pledge_Campaign']['column_name'];
  $resultArray['tableName'] = $customGroupTableName;
  
  return $resultArray;
  
}