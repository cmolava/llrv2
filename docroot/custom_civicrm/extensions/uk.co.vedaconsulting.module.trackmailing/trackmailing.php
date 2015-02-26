<?php

require_once 'trackmailing.civix.php';

/**
 * Implementation of hook_civicrm_config
 */
function trackmailing_civicrm_config(&$config) {
  _trackmailing_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 */
function trackmailing_civicrm_xmlMenu(&$files) {
  _trackmailing_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 */
function trackmailing_civicrm_install() {
 $sql = "CREATE TABLE IF NOT EXISTS civicrm_custom_track_mailing
          (
          id int primary key AUTO_INCREMENT,
          schedule_reminder_id  int,
          mailing_id int,
          is_respect_optout tinyint(4) DEFAULT 0 COMMENT 'Should the bulk email flag of contact be respected?'
          )";
  CRM_Core_DAO::executeQuery($sql);
  return _trackmailing_civix_civicrm_install();
}


/**
 * Implementation of hook_civicrm_uninstall
 */
function trackmailing_civicrm_uninstall() {
  return _trackmailing_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 */
function trackmailing_civicrm_enable() {
   return _trackmailing_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function trackmailing_civicrm_disable() {
  $sql = "Select count(*) as count FROM civicrm_custom_track_mailing";
  $dao = CRM_Core_DAO::singleValueQuery($sql);
  if ($dao){
    $message = "Cannot disable this extensions. Some of the Mailing Jobs are attached to Schedule Reminders";
    die($message);
  }else{
    return _trackmailing_civix_civicrm_disable();
  }
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 */
function trackmailing_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _trackmailing_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 */
function trackmailing_civicrm_managed(&$entities) {
  return _trackmailing_civix_civicrm_managed($entities);
}

function trackmailing_civicrm_buildForm($formName, &$form) {
  if ($formName == "CRM_Admin_Form_ScheduleReminders"){
    //using sql to retrive all the mailing job and mailing id
    $getAllMailingJob = array();
    $query = "
SELECT cmj.id         as job_id,
       cmj.mailing_id as mid,
       cm.name        as name,
       cm.is_archived as is_archived,
       cm.sms_provider_id as is_sms
FROM `civicrm_mailing_job` as cmj
JOIN civicrm_mailing as cm ON ( cm.id = cmj.mailing_id)
ORDER BY cm.is_archived DESC, cm.name ASC";
    $dao = CRM_Core_DAO::executeQuery($query);
    while ( $dao->fetch() ){
      $getAllMailingJob[$dao->mid] = $dao->is_sms ? "{$dao->name} (SMS)" : $dao->name;
    }

    // build email type radio option
    $choice    = array();
    $attribute = array('id_suffix' => 'is_respect_optout');
    $choice[]  = $form->createElement('radio', NULL, '11', ts('Marketing'), '1', $attribute);
    $choice[]  = $form->createElement('radio', NULL, '11', ts('Transactional'), '0', $attribute);
    $form->addGroup($choice, 'is_respect_optout', ts('Email Type'));

    $form->addElement( 'checkbox', 'trackMail', ts( 'Track Mailing' ) );
    $form->addElement( 'select', 'mailing_job', ts( 'Mailing Job' ), $getAllMailingJob);
    
    // also assign to template
    $template =& CRM_Core_Smarty::singleton( );
    $beginHookFormElements = $template->get_template_vars( 'beginHookFormElements' );
    if ( ! $beginHookFormElements ) {
        $beginHookFormElements = array( );
    }
    $beginHookFormElements[] = 'is_respect_optout';
    $beginHookFormElements[] = 'trackMail';
    $beginHookFormElements[] = 'mailing_job';
    $form->assign( 'beginHookFormElements', $beginHookFormElements );

    // enable the checkbox
    $scheduleReminderID = $form->getVar( '_id' );
    if(!empty($scheduleReminderID)){

      //set the default values for existing schedule reminder
      $check = "SELECT *
                FROM civicrm_custom_track_mailing
                WHERE schedule_reminder_id = {$scheduleReminderID}
                ";
      $dao = CRM_Core_DAO::executeQuery($check);
      if ( $dao->fetch() ){
        $mid = $dao->mailing_id;
        $defaults['trackMail'] = '1';
        $defaults['mailing_job'] = $mid;
        $defaults['is_respect_optout'] = $dao->is_respect_optout;
        $form->setDefaults( $defaults );
        $url = CRM_Utils_System::url('civicrm/mailing/report', 'mid='.$mid.'&reset=1');
        echo "<a id='view_mailing_report' href=".$url.">&nbsp;View Mailing Report</a>";
      }
    } else {
      // defaults for new reminder
      $defaults['is_respect_optout'] = 1;
      $form->setDefaults( $defaults );
    }
  }
}

function trackmailing_civicrm_postProcess( $formName, &$form ) {
  if($formName == "CRM_Admin_Form_ScheduleReminders"){
      $submitValues       = $form->_submitValues;
      $trackMail          = $submitValues['trackMail'];
      $mailing_id         = $submitValues['mailing_job'];
      $title              = $submitValues['title'];
      $scheduleReminderID = $form->getVar( '_id' );
      if(!$scheduleReminderID && !empty($title)){
          $query  = "SELECT id
                FROM civicrm_action_schedule
                WHERE title = '{$title}'
                ";
          $scheduleReminderID = CRM_Core_DAO::singleValueQuery($query);
      }

      if($trackMail && !empty($scheduleReminderID)){
        $check = "SELECT id
                  FROM civicrm_custom_track_mailing
                  WHERE schedule_reminder_id = {$scheduleReminderID}
                  ";
        $dao = CRM_Core_DAO::singleValueQuery($check);
        if( !$dao ){
          $sql = "INSERT INTO civicrm_custom_track_mailing
                    (schedule_reminder_id, mailing_id, is_respect_optout) VALUES (%1, %2, %3)
                    ";
          $params = array( 
            1 => array( $scheduleReminderID, 'Integer' ), 
            2 => array( $mailing_id, 'Integer' ),
            3 => array( CRM_Utils_Array::value('is_respect_optout', $submitValues, 0), 'Integer' )
          );
        }else{
          $sql = "UPDATE `civicrm_custom_track_mailing`
                  SET    `mailing_id`=%1, is_respect_optout=%2
                  WHERE  `schedule_reminder_id`=%3";
          $params = array( 
            1 => array( $mailing_id, 'Integer' ), 
            2 => array( CRM_Utils_Array::value('is_respect_optout', $submitValues, 0), 'Integer' ),
            3 => array( $scheduleReminderID, 'Integer' )
          );
        }
        CRM_Core_DAO::executeQuery($sql, $params);
      }

  }
}

function trackmailing_get_tracking_mailing_info($schedual_id) {
  if ($schedual_id) {
    $query = "
    SELECT  t.*
      FROM  civicrm_custom_track_mailing t
INNER JOIN  civicrm_mailing m ON m.id = t.mailing_id
     WHERE  t.schedule_reminder_id = %1 LIMIT 1";
    $dao   = CRM_Core_DAO::executeQuery($query, array(1 => array($schedual_id, 'Integer')));
    if ($dao->fetch()) {
      return 
        array(
          'mailing_id'        => $dao->mailing_id,
          'is_respect_optout' => $dao->is_respect_optout, 
        );
    }
  }
  return array();
}

function trackmailing_is_tracking_mailing( $mailing_id ) {
  $sSql =<<<EOD
            SELECT t.mailing_id
            FROM   civicrm_custom_track_mailing t
            JOIN   civicrm_mailing m ON m.id = t.mailing_id
            WHERE  t.mailing_id    = %1
EOD;
  $aParams = array( 1 => array( $mailing_id, 'Integer' ) );

  return CRM_Core_DAO::singleValueQuery( $sSql, $aParams );
}

function trackmailing_add_to_mailing_recipients($mailing_id, $contact_id, $email_id = NULL, $phone_id = NULL) {
  if (!$email_id && !$phone_id) {
    CRM_Core_Error::debug_log_message("Required params to amend recipients missing.");
    return FALSE;
  }

  $params = array( 
    1 => array( $mailing_id, 'Integer' ),
    2 => array( $contact_id, 'Integer' ));
  $values = "%1, %2";

  $values .= $email_id ? ", %3" : ", NULL";
  if ($email_id) {
    $params[3] = array($email_id, 'Integer');
  }

  $values .= $phone_id ? ", %4" : ", NULL";
  if ($phone_id) {
    $params[4] = array($phone_id, 'Integer');
  }

  $sql = "INSERT INTO civicrm_mailing_recipients(mailing_id, contact_id, email_id, phone_id) VALUES ({$values});";
  $mailingRecipients = CRM_Core_DAO::executeQuery( $sql, $params );

  // add to the Mailing Queue, the Mailing Recipients.
  $sql ="
SELECT id
FROM   civicrm_mailing_job
WHERE  mailing_id = %1 AND job_type   = 'child'";
  $params      = array(1 => array($mailing_id, 'Integer'));
  $mailingJobs = CRM_Core_DAO::executeQuery($sql, $params);
  $newMailEventQueue = array();
  while( $mailingJobs->fetch() ) {
    $newMailEventQueue[] = 
      array(
        $mailingJobs->id, 
        $email_id ? $email_id : 'null',
        $contact_id,
        $phone_id ? $phone_id : 'null');
  }
  CRM_Core_Error::debug_log_message("calling CRM_Mailing_Event_BAO_Queue::bulkCreate:" . print_r($newMailEventQueue, true)  );

  if (!empty( $newMailEventQueue )) {
    CRM_Mailing_Event_BAO_Queue::bulkCreate( $newMailEventQueue );
  }
  CRM_Core_Error::debug_log_message("calling CRM_Mailing_Event_BAO_Queue::bulkCreate - DONE.");

  return $mailingRecipients;
}

function tracking_civicrm_set_mail_job_to_running( $mailing_id ) {

  // Set Parent and Child status to Running.
  $sSql =<<<EOD
            UPDATE civicrm_mailing_job
            SET    end_date   = null
            ,      status     = 'Running'
            WHERE  mailing_id = %1;
EOD;
  $aParams = array( 1 => array( $mailing_id, 'Integer' ) );
  CRM_Core_DAO::executeQuery( $sSql, $aParams );

  // Now set the Job Limit of the Child record to be the total rec of Recipient
  $iJobLimit   = CRM_Mailing_BAO_Recipients::mailingSize( $mailing_id );
CRM_Core_Error::debug_log_message("tracking_civicrm_set_mail_job_to_running - iJobLimit:" . print_r( $iJobLimit, true )  );
  $sSql        =<<<EOD
            UPDATE civicrm_mailing_job
            SET    job_limit  = %2
            WHERE  mailing_id = %1
            AND    job_type   = 'child'
EOD;
  $aParams = array( 1 => array( $mailing_id, 'Integer' )
                  , 2 => array( $iJobLimit , 'Integer' )
                  );
  CRM_Core_DAO::executeQuery( $sSql, $aParams );
  CRM_Core_Error::debug_log_message("tracking_civicrm_set_mail_job_to_running - DONE. "  );
}


function trackmailing_civicrm_alterMailParams( &$params, $context = NULL ) {
  if ( $params['groupName']  == 'Scheduled Reminder Sender' ) {
    $trackParams = trackmailing_get_tracking_mailing_info($params['schedule_id']);
    $iMailingId  = $trackParams['mailing_id'];
    $respectBulkFlag = $trackParams['is_respect_optout'];

    if ( empty( $iMailingId ) ) {
      CRM_Core_Error::debug_log_message( "trackmailing_civicrm_alterMailParams: Failed to locate Mailing ID with schedule_id:" . $params['schedule_id'] );
    } else {
      /*
       * We want want to abort the mail from being sent, set the boolean abortMailSend to true in the params array
       */
      $params['abortMailSend'] = true;

      // check if mailing is an sms-mailing
      $isSMSMailing = CRM_Core_DAO::getFieldValue('CRM_Mailing_DAO_Mailing', $iMailingId, 'sms_provider_id', 'id');

      /*
       * Instead add to civicrm_mailing_recipients for the mailing job and set the mailing job to scheduled
       */
      $email = $phone = array();
      if (!$isSMSMailing) {
        $email = civicrm_api("Email",
                 "getsingle",
                 array('version'   => '3',
                   'sequential' => '1',
                   'contact_id' => $params['contact_id'],
                   'is_primary' => '1'));
        if ($email && $respectBulkFlag) {
          $contact = new CRM_Contact_BAO_Contact();
          $contact->id = $params['contact_id'];
          $contact->find(TRUE);
          if ($contact->is_opt_out) {
            CRM_Core_Error::debug_log_message( "trackmailing_civicrm_alterMailParams: Skipped attaching contact to recipient list, inorder to respect bulk email flag setting in reminder - Contact: cid={$params['contact_id']}, email={$email['id']}.");
            return;
          }
        }
      } else {
        $phoneTypes = CRM_Core_OptionGroup::values('phone_type', TRUE, FALSE, FALSE, NULL, 'name');
        $phone = civicrm_api("Phone",
                 "getsingle",
                 array('version'    => '3',
                   'sequential' => '1',
                   'contact_id' => $params['contact_id'],
                   'do_not_sms' => 0,
                   'phone_type_id' => $phoneTypes['Mobile']));
        if (!CRM_Utils_Array::value('id', $phone)) {
          CRM_Core_Error::debug_log_message("trackmailing_civicrm_alterMailParams: Could not retrieve phone for mailing_id: {$iMailingId}, contact_id: {$params['contact_id']}");
        }
      }
      $oDao = trackmailing_add_to_mailing_recipients($iMailingId,
              $params['contact_id'],
              CRM_Utils_Array::value('id', $email),
              CRM_Utils_Array::value('id', $phone));
      if ( $oDao ) {
        /* Now set the  mailing job to scheduled */
        tracking_civicrm_set_mail_job_to_running( $iMailingId );
      } else {
        CRM_Core_Error::debug_log_message("trackmailing_civicrm_alterMailParams: Failed to insert into civicrm_mailing_recipients - mailing_id: $iMailingId, contact_id:" . $params['contact_id'] . ', email_id:' .$email['id'] );
      }
    }
  }
}

function trackmailing_civicrm_unsubscribeGroups( $op, $mailingId, $contactId, &$groups, &$baseGroups ) {
  if ( $op == 'unsubscribe' && trackmailing_is_tracking_mailing( $mailingId ) ) {
    $oConfig                 = CRM_Core_Config::singleton();
    $sUnsubscribeRedirectUrl = $oConfig->unsubscribe_redirect_url;
    if ( !empty( $sUnsubscribeRedirectUrl ) ) {
      CRM_Utils_System::redirect( $sUnsubscribeRedirectUrl );
    } else {
      CRM_Core_Error::statusBounce( 'Unsubscribe URL has not been set.' );
    }
    CRM_Utils_System::civiExit();
  }
}
