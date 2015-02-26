<?php

require_once 'CRM/Core/Form.php';
require_once 'CRM/Core/Session.php';
require_once 'CRM/Core/PseudoConstant.php';

class CRM_DirectDebit_Form_Confirm extends CRM_Core_Form {
  const QUEUE_NAME = 'sm-pull';
  const END_URL    = 'civicrm/directdebit/syncsd/confirm';
  const END_PARAMS = 'state=done';
  const BATCH_COUNT = 10;
  const SD_SETTING_GROUP = 'SmartDebit Preferences';

  public $auddisDate = NULL;

  public function preProcess() {
    $status = 0;
    $state = CRM_Utils_Request::retrieve('state', 'String', CRM_Core_DAO::$_nullObject, FALSE, 'tmp', 'GET');
    if ($state == 'done') {
      $status = 1;

      $ids  = CRM_Core_BAO_Setting::getItem(CRM_DirectDebit_Form_Confirm::SD_SETTING_GROUP, 'result_ids');
      $rejectedids  = CRM_Core_BAO_Setting::getItem(CRM_DirectDebit_Form_Confirm::SD_SETTING_GROUP, 'rejected_ids');
      $this->assign('ids', $ids);
      $this->assign('rejectedids', $rejectedids);
      $this->assign('totalValidContribution', count($ids));
      $this->assign('totalRejectedContribution', count($rejectedids));


    }
    $this->assign('status', $status);
  }

  public function buildQuickForm() {

    $auddisDates = CRM_Utils_Request::retrieve('auddisDates', 'String', $this, false, '', 'GET');
    $this->add('hidden', 'auddisDate', serialize($auddisDates));
    $redirectUrlBack = CRM_Utils_System::url('civicrm/directdebit/syncsd', 'reset=1');

    $this->addButtons(array(
              array(
                'type' => 'submit',
                'name' => ts('Confirm Sync'),
                'isDefault' => TRUE,
                ),
              array(
                'type' => 'cancel',
                'js' => array('onclick' => "location.href='{$redirectUrlBack}'; return false;"),
                'name' => ts('Cancel'),
              )
            )
    );
  }

  public function postProcess() {

    $params     = $this->controller->exportValues();
    $auddisDates = unserialize($params['auddisDate']);
    $financialTypeID    = CRM_Core_BAO_Setting::getItem('UK Direct Debit', 'financial_type');

    // Check financialType is set in the civicrm_setting table
    if(empty($financialTypeID)) {
      CRM_Core_Session::setStatus(ts('Make sure Financial Type is set in UK Direct Debit setting'), Error, 'error');
      return FALSE;
    }

    $runner = self::getRunner($auddisDates);
    if ($runner) {
      // Create activity for the sync just finished with the auddis date
      foreach ($auddisDates as $auddisDate) {

        $params = array(
          'version' => 3,
          'sequential' => 1,
          'activity_type_id' => 6,
          'subject' => $auddisDate,
          'details' => 'Sync had been processed already for this date '.$auddisDate,
        );
        $result = civicrm_api('Activity', 'create', $params);
      }
      // Run Everything in the Queue via the Web.
      $runner->runAllViaWeb();
    } else {
      CRM_Core_Session::setStatus(ts('Nothing to pull. Make sure smart debit settings are correctly configured in the payment processor setting page'));
    }
  }

  static function getRunner($auddisDates = NULL) {
    // Setup the Queue
    $queue = CRM_Queue_Service::singleton()->create(array(
      'name'  => self::QUEUE_NAME,
      'type'  => 'Sql',
      'reset' => TRUE,
    ));

    // List of auddis files
    $auddisArray      = CRM_DirectDebit_Form_SyncSd::getSmartDebitAuddis();
    if($auddisDates) {
    // Find the relevant auddis file
      foreach ($auddisDates as $auddisDate) {
        $auddisDetails  = CRM_DirectDebit_Form_Auddis::getRightAuddisFile($auddisArray, $auddisDate);
        $auddisFiles[] = CRM_DirectDebit_Form_SyncSd::getSmartDebitAuddis($auddisDetails['uri']);
      }
    }

    $selectQuery = "SELECT `transaction_id` as trxn_id, `receive_date` as receive_date FROM `veda_civicrm_smartdebit_import`";
    $dao = CRM_Core_DAO::executeQuery($selectQuery);
    $traIds = array();
    while($dao->fetch()) {
      $traIds[] = $dao->trxn_id;
      $receiveDate  = $dao->receive_date;
    }

    $count  = count($traIds);

    // Set the Number of Rounds
    $rounds = ceil($count/self::BATCH_COUNT);
    // Setup a Task in the Queue
    $i = 0;
    while ($i < $rounds) {
      $start   = $i * self::BATCH_COUNT;
      $contactsarray  = array_slice($traIds, $start, self::BATCH_COUNT, TRUE);
      $counter = ($rounds > 1) ? ($start + self::BATCH_COUNT) : $count;
      $task    = new CRM_Queue_Task(
        array('CRM_DirectDebit_Form_Confirm', 'syncSmartDebitRecords'),
        array(array($contactsarray), array($auddisDetails), array($auddisFiles), $auddisDate),
        "Pulling smart debit - Contacts {$counter} of {$count}"
      );

      // Add the Task to the Queu
      $queue->createItem($task);
      $i++;
    }

    if (!empty($traIds)) {
      // Setup the Runner
      $runner = new CRM_Queue_Runner(array(
        'title' => ts('Import From Smart Debit'),
        'queue' => $queue,
        'errorMode'=> CRM_Queue_Runner::ERROR_ABORT,
        'onEndUrl' => CRM_Utils_System::url(self::END_URL, self::END_PARAMS, TRUE, NULL, FALSE),
      ));

      // Reset the counter when sync starts
      $query1 = "UPDATE civicrm_setting SET value = NULL WHERE name = 'result_ids'";
      $query2 = "UPDATE civicrm_setting SET value = NULL WHERE name = 'rejected_ids'";

      CRM_Core_DAO::executeQuery($query1);
      CRM_Core_DAO::executeQuery($query2);

      // Add contributions for rejected payments with the status of 'failed'
      $ids = array();
      foreach ($auddisFiles as $auddisFile) {
        foreach ($auddisFile as $key => $value) {

          $sql = "
            SELECT ctrc.id contribution_recur_id ,ctrc.contact_id , cont.display_name ,ctrc.start_date , ctrc.amount, ctrc.trxn_id , ctrc.frequency_unit, ctrc.payment_instrument_id
            FROM civicrm_contribution_recur ctrc
            INNER JOIN civicrm_contact cont ON (ctrc.contact_id = cont.id)
            WHERE ctrc.trxn_id = %1";

          $params = array( 1 => array( $value['reference'], 'String' ) );
          $dao = CRM_Core_DAO::executeQuery( $sql, $params);
          
          $selectQuery  = "SELECT customer_id as customer_id, contact as account_name, info as transaction_ref, `receive_date` as receive_date, amount as amount FROM `veda_civicrm_smartdebit_import` WHERE `transaction_id` = '{$value['reference']}'";
          $daoSelect    = CRM_Core_DAO::executeQuery($selectQuery);
          $daoSelect->fetch();

          $smartdebitParams = array(
            'account_name' => $daoSelect->account_name,
            'transaction_ref' => $daoSelect->transaction_ref,
            'amount' => $daoSelect->amount,
            'reference_number' => $smartDebitRecord,
            'debit_date' => $daoSelect->receive_date,
            'customer_id' => $daoSelect->customer_id,
          );

          $financialTypeID    = CRM_Core_BAO_Setting::getItem('UK Direct Debit', 'financial_type');
          // RS: Commenting below line, as we save the financial type ID in civicrm_setting table
          // $financialTypeID  = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialType', $financialType, 'id', 'name');

          if ($dao->fetch()) {
            $contributeParams =
            array(
              'version'                => 3,
              'contact_id'             => $dao->contact_id,
              'contribution_recur_id'  => $dao->contribution_recur_id,
              'total_amount'           => $dao->amount,
              'invoice_id'             => md5(uniqid(rand(), TRUE )),
              'trxn_id'                => $value['reference'].'/'.CRM_Utils_Date::processDate($receiveDate),
              'financial_type_id'      => $financialTypeID,
              'payment_instrument_id'  => $dao->payment_instrument_id,
              'contribution_status_id' => 4,
              'source'                 => 'Smart Debit Import',
              'receive_date'           => $value['effective-date'],
            );

            // Allow params to be modified via hook
            CRM_DirectDebit_Utils_Hook::alterSmartDebitContributionParams( $contributeParams, $smartdebitParams );

            $contributeResult = civicrm_api('Contribution', 'create', $contributeParams);

            if(!$contributeResult['is_error']) {
              $contributionID   = $contributeResult['id'];
              // get contact display name to display in result screen
              $contactParams = array('version' => 3, 'id' => $contributeResult['values'][$contributionID]['contact_id']);
              $contactResult = civicrm_api('Contact', 'getsingle', $contactParams);

              $ids[$contributionID]= array(   'cid' => $contributeResult['values'][$contributionID]['contact_id']
                                            , 'id' => $contributionID
                                            , 'display_name' => $contactResult['display_name']
                                            );

              // Allow auddis rejected contribution to be handled by hook
              CRM_DirectDebit_Utils_Hook::handleAuddisRejectedContribution( $contributionID );
            }
          }
        }
      }

      CRM_Core_BAO_Setting::setItem($ids,
        CRM_DirectDebit_Form_Confirm::SD_SETTING_GROUP, 'rejected_ids'
      );
      return $runner;
    }
    return FALSE;
  }

  static function syncSmartDebitRecords(CRM_Queue_TaskContext $ctx, $contactsarray, $auddisDetails, $auddisFile, $auddisDate ) {

    $contactsarray  = array_shift($contactsarray);
    $auddisDetails  = array_shift($auddisDetails);
    $auddisFile     = array_shift($auddisFile);

    $ids = array();

    foreach ($contactsarray as $key => $smartDebitRecord) {

      $sql = "
        SELECT ctrc.id contribution_recur_id ,ctrc.contact_id , cont.display_name ,ctrc.start_date , ctrc.amount, ctrc.trxn_id , ctrc.frequency_unit, ctrc.payment_instrument_id, ctrc.campaign_id
        FROM civicrm_contribution_recur ctrc
        INNER JOIN civicrm_contact cont ON (ctrc.contact_id = cont.id)
        WHERE ctrc.trxn_id = %1";

      $params = array( 1 => array( $smartDebitRecord, 'String' ) );
      $dao = CRM_Core_DAO::executeQuery( $sql, $params);

      $selectQuery  = "SELECT customer_id as customer_id, contact as account_name, info as transaction_ref, `receive_date` as receive_date, amount as amount FROM `veda_civicrm_smartdebit_import` WHERE `transaction_id` = '{$smartDebitRecord}'";
      $daoSelect    = CRM_Core_DAO::executeQuery($selectQuery);
      $daoSelect->fetch();
      
      $smartdebitParams = array(
        'account_name' => $daoSelect->account_name,
        'transaction_ref' => $daoSelect->transaction_ref,
        'amount' => $daoSelect->amount,
        'reference_number' => $smartDebitRecord,
        'debit_date' => $daoSelect->receive_date,
        'customer_id' => $daoSelect->customer_id,
      );

      $financialTypeID    = CRM_Core_BAO_Setting::getItem('UK Direct Debit', 'financial_type');
      // RS: Commenting below line, as we save the financial type ID in civicrm_setting table
      //$financialTypeID  = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialType', $financialType, 'id', 'name');

      // Smart debit charge file has dates in UK format
      // UK dates (eg. 27/05/1990) won't work with strotime, even with timezone properly set.
      // However, if you just replace "/" with "-" it will work fine.
      $receiveDate = date('Y-m-d', strtotime(str_replace('/', '-', $daoSelect->receive_date)));

      if ($dao->fetch()) {
        $contributeParams =
        array(
          'version'                => 3,
          'contact_id'             => $dao->contact_id,
          'contribution_recur_id'  => $dao->contribution_recur_id,
          'total_amount'           => $dao->amount,
          'invoice_id'             => md5(uniqid(rand(), TRUE )),
          'trxn_id'                => $smartDebitRecord.'/'.CRM_Utils_Date::processDate($receiveDate),
          'financial_type_id'      => $financialTypeID,
          'payment_instrument_id'  => $dao->payment_instrument_id,
          'contribution_status_id' => 1,
          'source'                 => 'Smart Debit Import',
          'receive_date'           => CRM_Utils_Date::processDate($receiveDate),
          'campaign_id'            => $dao->campaign_id,
        );
        

        // Allow params to be modified via hook
        CRM_DirectDebit_Utils_Hook::alterSmartDebitContributionParams( $contributeParams, $smartdebitParams);

        $contributeResult = civicrm_api('Contribution', 'create', $contributeParams);

        if(!$contributeResult['is_error']) {

          $contributionID   = $contributeResult['id'];
          $contriReurID     = $contributeResult['values'][$contributionID]['contribution_recur_id'];
          //KJ
          // Check column exists first before getting the membership id from recurring table
          $columnExists     = CRM_Core_DAO::checkFieldExists('civicrm_contribution_recur', 'membership_id');
          if($columnExists) {
              $membershipQuery  = "SELECT `membership_id` FROM `civicrm_contribution_recur` WHERE `id` = %1";
              $membershipID   = CRM_Core_DAO::singleValueQuery($membershipQuery, array( 1 => array( $contriReurID, 'Int' ) ) );
          }
          if (!empty($membershipID)) {

            $getMembership  = civicrm_api("Membership"
                                      ,"get"
                                      , array ('version'       => '3'
                                              ,'membership_id' => $membershipID
                                              )
                                      );

            $membershipEndDate   = $getMembership['values'][$membershipID]['end_date'];

            $contributionReceiveDate = $contributeResult['values'][$contributionID]['receive_date'];

            $contributionReceiveDateString = date("Ymd", strtotime($contributionReceiveDate));
            $membershipEndDateString = date("Ymd", strtotime($membershipEndDate));

            $contributionRecurring = civicrm_api("ContributionRecur"
                                                ,"get"
                                                , array ('version' => '3'
                                                        ,'id'      => $contriReurID
                                                        )
                                                );

            $frequencyUnit = $contributionRecurring['values'][$contriReurID]['frequency_unit'];

            if (!is_null($frequencyUnit)) {
              $membershipEndDateString = date("Y-m-d",strtotime(date("Y-m-d", strtotime($membershipEndDate)) . " +1 $frequencyUnit"));

              $membershipParams = array ( 'version'       => '3'
                                         , 'membership_id' => $membershipID
                                         , 'id'            => $membershipID
                                         , 'end_date'      => $membershipEndDateString
                                        );

              // Set a flag to be sent to hook, so that membership renewal can be skipped
              $membershipParams['renew'] = 1;

              // Allow membership update params to be modified via hook
              CRM_DirectDebit_Utils_Hook::handleSmartDebitMembershipRenewal( $membershipParams );

              // Membership renewal may be skipped in hook by setting 'renew' = 0
              if ($membershipParams['renew'] == 1 ) {

                // remove the renew kay from params array, which need to be passed to API
                unset($membershipParams['renew']);

                $updatedMember = civicrm_api("Membership"
                                              ,"create"
                                              , $membershipParams
                                              );
              }
            }
          }

          // get contact display name to display in result screen
          $contactParams = array('version' => 3, 'id' => $contributeResult['values'][$contributionID]['contact_id']);
          $contactResult = civicrm_api('Contact', 'getsingle', $contactParams);

          $ids[$contributionID]= array(   'cid' => $contributeResult['values'][$contributionID]['contact_id']
                                        , 'id' => $contributionID
                                        , 'display_name' => $contactResult['display_name']
                                        );
        }
      }
       else {
        // The following hook is to find the contact to match from other ways
        $params = array('transaction_id' => $smartDebitRecord);
        CRM_DirectDebit_Utils_Hook::findSmartDebitContact( $params );
        $paymentIns = CRM_Core_BAO_Setting::getItem('UK Direct Debit', 'payment_instrument_id');
        if(!empty($params['contact_id'])) {
          $contributeParams =
            array(
              'version'                => 3,
              'contact_id'             => $params['contact_id'],
              'total_amount'           => $daoSelect->amount,
              'invoice_id'             => md5(uniqid(rand(), TRUE )),
              'trxn_id'                => $smartDebitRecord.'/'.CRM_Utils_Date::processDate($receiveDate),
              'financial_type_id'      => $financialTypeID,
              'payment_instrument_id'  => $paymentIns,
              'contribution_status_id' => 1,
              'source'                 => 'Smart Debit Import',
              'receive_date'           => CRM_Utils_Date::processDate($receiveDate),
            );

        // Allow params to be modified via hook
        CRM_DirectDebit_Utils_Hook::alterSmartDebitContributionParams( $contributeParams, $smartdebitParams );

        $contributeResult = civicrm_api('Contribution', 'create', $contributeParams);
        $contributionID   = $contributeResult['id'];
         // get contact display name to display in result screen
        $contactParams = array('version' => 3, 'id' => $contributeResult['values'][$contributionID]['contact_id']);
        $contactResult = civicrm_api('Contact', 'getsingle', $contactParams);

        $ids[$contributionID]= array(   'cid' => $contributeResult['values'][$contributionID]['contact_id']
                                      , 'id' => $contributionID
                                      , 'display_name' => $contactResult['display_name']
                                      );
        }
      }
    }

    $prevResults      = CRM_Core_BAO_Setting::getItem(CRM_DirectDebit_Form_Confirm::SD_SETTING_GROUP, 'result_ids');

    if($prevResults) {
      $compositeResults = array_merge($prevResults, $ids);
      CRM_Core_BAO_Setting::setItem($compositeResults,
        CRM_DirectDebit_Form_Confirm::SD_SETTING_GROUP, 'result_ids'
      );
    }
    else {
      CRM_Core_BAO_Setting::setItem($ids,
        CRM_DirectDebit_Form_Confirm::SD_SETTING_GROUP, 'result_ids'
      );
    }


    return CRM_Queue_Task::TASK_SUCCESS;
  }



}
