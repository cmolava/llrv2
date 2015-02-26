<?php

require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Quickbulkemail_Form_QuickBulkEmailSchedule extends CRM_Core_Form {
  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    if (CRM_Mailing_Info::workflowEnabled() &&
      !CRM_Core_Permission::check('schedule mailings')
    ) {
      $url = CRM_Utils_System::url('civicrm/mailing/browse/unscheduled', 'reset=1&scheduled=false');
      CRM_Utils_System::redirect($url);
    }
    
    $this->_mailingID = $this->get('mailing_id');
    $this->_scheduleFormOnly = FALSE;
    if (!$this->_mailingID) {
      $this->_mailingID = CRM_Utils_Request::retrieve('mid', 'Integer', $this, TRUE);
      $this->_scheduleFormOnly = TRUE;
    }
    if (empty($this->_mailingID)) {
        $url = CRM_Utils_System::url('civicrm/quickbulkemail', 'reset=1');
        CRM_Utils_System::redirect($url); 
    }
        
    
  }
  
  function buildQuickForm() {

    // Test Mailing
    // Start
    $this->add('text', 'test_email', ts('Send to This Address'));
    $this->add('select',
      'test_group',
      ts('Send to This Group'),
      array('' => ts('- none -')) + CRM_Core_PseudoConstant::group('Mailing')
    );
    $this->add('submit', 'sendtest', ts('Send a Test Mailing'));
    $this->addFormRule(array('CRM_Quickbulkemail_Form_QuickBulkEmailSchedule', 'testMail'), $this);
    // End
    
    // Schedule or send
    // Start
    $this->addDateTime('start_date', ts('Schedule Mailing'), FALSE, array('formatType' => 'mailing'));
    $this->addElement('checkbox', 'now', ts('Send Immediately'));
    // End
    
    //$this->addFormRule(array('CRM_Quickbulkemail_Form_QuickBulkEmailSchedule', 'formRule'), $this);
    
    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => ts('Submit'),
        'isDefault' => TRUE,
      ),
    ));

    // export form elements
    // $this->assign('elementNames', $this->getRenderableElementNames());
    parent::buildQuickForm();
  }
  
  /**
   * Form rule to validate the date selector and/or if we should deliver
   * immediately.
   *
   * Warning: if you make changes here, be sure to also make them in
   * Retry.php
   *
   * @param array $params     The form values
   *
   * @return boolean          True if either we deliver immediately, or the
   *                          date is properly set.
   * @static
   */
  public static function formRule($params, $files, $self) {
    if (!empty($params['_qf_Schedule_submit'])) {
      //when user perform mailing from search context
      //redirect it to search result CRM-3711.
      $ssID = $self->get('ssID');
      if ($ssID && $self->_searchBasedMailing) {
        if ($self->_action == CRM_Core_Action::BASIC) {
          $fragment = 'search';
        }
        elseif ($self->_action == CRM_Core_Action::PROFILE) {
          $fragment = 'search/builder';
        }
        elseif ($self->_action == CRM_Core_Action::ADVANCED) {
          $fragment = 'search/advanced';
        }
        else {
          $fragment = 'search/custom';
        }

        $draftURL = CRM_Utils_System::url('civicrm/mailing/browse/unscheduled', 'scheduled=false&reset=1');
        $status = ts("Your mailing has been saved. You can continue later by clicking the 'Continue' action to resume working on it.<br /> From <a href='%1'>Draft and Unscheduled Mailings</a>.", array(1 => $draftURL));
        CRM_Core_Session::setStatus($status);

        //replace user context to search.
        $context = $self->get('context');
        if (!CRM_Contact_Form_Search::isSearchContext($context)) {
          $context = 'search';
        }

        $urlParams = "force=1&reset=1&ssID={$ssID}&context={$context}";
        $qfKey = CRM_Utils_Request::retrieve('qfKey', 'String', $this);
        if (CRM_Utils_Rule::qfKey($qfKey)) {
          $urlParams .= "&qfKey=$qfKey";
        }
        $url = CRM_Utils_System::url('civicrm/contact/' . $fragment, "force=1&reset=1&ssID={$ssID}");
        CRM_Utils_System::redirect($url);
      }
      else {
        CRM_Core_Session::setStatus(ts("Your mailing has been saved. Click the 'Continue' action to resume working on it."));
        $url = CRM_Utils_System::url('civicrm/mailing/browse/unscheduled', 'scheduled=false&reset=1');
        CRM_Utils_System::redirect($url);
      }
    }
    
    if ($params['_qf_QuickBulkEmailSchedule_submit'] == 'Submit') {
      if (isset($params['now']) || CRM_Utils_Array::value('_qf_Schedule_back', $params) == '<< Previous') {
        return TRUE;
      }

      if (CRM_Utils_Date::format(CRM_Utils_Date::processDate($params['start_date'],
            $params['start_date_time']
          )) < CRM_Utils_Date::format(date('YmdHi00'))) {
        return array(
          'start_date' =>
          ts('Start date cannot be earlier than the current time.'),
        );
      }
      $error = TRUE;
    }
    return $error;
  }

  /**
   * Form rule to send out a test mailing.
   *
   * @param array $params     Array of the form values
   * @param array $files      Any files posted to the form
   * @param array $self       an current this object
   *
   * @return boolean          true on succesful SMTP handoff
   * @access public
   */
  static
  function &testMail($testParams, $files, $self) {
    
    if (isset($testParams['_qf_QuickBulkEmailSchedule_submit']) && $testParams['_qf_QuickBulkEmailSchedule_submit'] == 'Submit') {
      if (isset($testParams['now'])) {
        $error = TRUE;  
        return $error;
      }

      if (CRM_Utils_Date::format(CRM_Utils_Date::processDate($testParams['start_date'],
            $testParams['start_date_time']
          )) < CRM_Utils_Date::format(date('YmdHi00'))) {
        $errorArray = array(
          'start_date' =>
          ts('Start date cannot be earlier than the current time.'),
        );
        
        return $errorArray; 
      }
    }
    
    else {
      $error = NULL;

      $urlString = 'civicrm/quickbulkemailschedule';
      $urlParams = "mid=".$self->_mailingID."&reset=1";

      $ssID = $self->get('ssID');
      if ($ssID && $self->_searchBasedMailing) {
        if ($self->_action == CRM_Core_Action::BASIC) {
          $fragment = 'search';
        }
        elseif ($self->_action == CRM_Core_Action::PROFILE) {
          $fragment = 'search/builder';
        }
        elseif ($self->_action == CRM_Core_Action::ADVANCED) {
          $fragment = 'search/advanced';
        }
        else {
          $fragment = 'search/custom';
        }
        $urlString = 'civicrm/contact/' . $fragment;
      }
      $emails = NULL;
      if (CRM_Utils_Array::value('sendtest', $testParams)) {
        if (!($testParams['test_group'] || $testParams['test_email'])) {
          CRM_Core_Session::setStatus(ts('You did not provide an email address or select a group.'), ts('Test not sent.'), 'error');
          $error = TRUE;
        }

        if ($testParams['test_email']) {
          $emailAdd = explode(',', $testParams['test_email']);
          foreach ($emailAdd as $key => $value) {
            $email = trim($value);
            $testParams['emails'][] = $email;
            $emails .= $emails ? ",'$email'" : "'$email'";
            if (!CRM_Utils_Rule::email($email)) {
              CRM_Core_Session::setStatus(ts('Please enter a valid email addresses.'), ts('Test not sent.'), 'error');
              $error = TRUE;
            }
          }
        }

        if ($error) {
          $url = CRM_Utils_System::url($urlString, $urlParams);
          CRM_Utils_System::redirect($url);
          return $error;
        }
      }

      if (CRM_Utils_Array::value('_qf_Test_submit', $testParams)) {
        //when user perform mailing from search context
        //redirect it to search result CRM-3711.
        if ($ssID && $self->_searchBasedMailing) {
          $draftURL = CRM_Utils_System::url('civicrm/mailing/browse/unscheduled', 'scheduled=false&reset=1');
          $status = ts("You can continue later by clicking the 'Continue' action to resume working on it.<br />From <a href='%1'>Draft and Unscheduled Mailings</a>.", array(1 => $draftURL));

          //replace user context to search.
          $context = $self->get('context');
          if (!CRM_Contact_Form_Search::isSearchContext($context)) {
            $context = 'search';
          }
          $urlParams = "force=1&reset=1&ssID={$ssID}&context={$context}&qfKey={$testParams['qfKey']}";
          $url = CRM_Utils_System::url($urlString, $urlParams);
        }
        else {
          $status = ts("Click the 'Continue' action to resume working on it.");
          $url = CRM_Utils_System::url('civicrm/mailing/browse/unscheduled', 'scheduled=false&reset=1');
        }
        CRM_Core_Session::setStatus($status, ts('Mailing Saved'), 'success');
        CRM_Utils_System::redirect($url);
      }

      if (CRM_Mailing_Info::workflowEnabled()) {
        if (!CRM_Core_Permission::check('schedule mailings') &&
          CRM_Core_Permission::check('create mailings')
        ) {
          $url = CRM_Utils_System::url('civicrm/mailing/browse/unscheduled', 'scheduled=false&reset=1');
          CRM_Utils_System::redirect($url);
        }
      }

      if (CRM_Utils_Array::value('_qf_Test_next', $testParams) &&
        $self->get('count') <= 0) {
        return array(
          '_qf_default' =>
          ts("You can not schedule or send this mailing because there are currently no recipients selected. Click 'Previous' to return to the Select Recipients step, OR click 'Save & Continue Later'."),
        );
      }

      if (CRM_Utils_Array::value('_qf_Import_refresh', $_POST) ||
        CRM_Utils_Array::value('_qf_Test_next', $testParams) ||
        !CRM_Utils_Array::value('sendtest', $testParams)
      ) {
        $error = TRUE;
        return $error;
      }

      $job             = new CRM_Mailing_BAO_Job();
      $job->mailing_id = $self->_mailingID;
      $job->is_test    = TRUE;
      $job->save();
      $newEmails = NULL;
      $session = CRM_Core_Session::singleton();
      if (!empty($testParams['emails'])) {
        $query = "
SELECT     e.id, e.contact_id, e.email
FROM       civicrm_email e
INNER JOIN civicrm_contact c ON e.contact_id = c.id
WHERE      e.email IN ($emails)
AND        e.on_hold = 0
AND        c.is_opt_out = 0
AND        c.do_not_email = 0
AND        c.is_deceased = 0
GROUP BY   e.id
ORDER BY   e.is_bulkmail DESC, e.is_primary DESC
";

        $dao = CRM_Core_DAO::executeQuery($query);
        $emailDetail = array();
        // fetch contact_id and email id for all existing emails
        while ($dao->fetch()) {
          $emailDetail[$dao->email] = array(
            'contact_id' => $dao->contact_id,
            'email_id' => $dao->id,
          );
        }

        $dao->free();
        foreach ($testParams['emails'] as $key => $email) {
          $email = trim($email);
          $contactId = $emailId = NULL;
          if (array_key_exists($email, $emailDetail)) {
            $emailId = $emailDetail[$email]['email_id'];
            $contactId = $emailDetail[$email]['contact_id'];
          }

          if (!$contactId) {
            //create new contact.
            $params = array(
              'contact_type' => 'Individual',
              'email' => array(
                1 => array('email' => $email,
                  'is_primary' => 1,
                  'location_type_id' => 1,
                )),
            );
            $contact   = CRM_Contact_BAO_Contact::create($params);
            $emailId   = $contact->email[0]->id;
            $contactId = $contact->id;
            $contact->free();
          }
          $params = array(
            'job_id' => $job->id,
            'email_id' => $emailId,
            'contact_id' => $contactId,
          );
          CRM_Mailing_Event_BAO_Queue::create($params);
        }
      }

      $testParams['job_id'] = $job->id;
      $isComplete = FALSE;
      while (!$isComplete) {
        $isComplete = CRM_Mailing_BAO_Job::runJobs($testParams);
      }

      if (CRM_Utils_Array::value('sendtest', $testParams)) {
        $status = NULL;
        if (CRM_Mailing_Info::workflowEnabled()) {
          if ((
              CRM_Core_Permission::check('schedule mailings') &&
              CRM_Core_Permission::check('create mailings')
            ) ||
            CRM_Core_Permission::check('access CiviMail')
          ) {
            $status = ts("Click 'Submit' when you are ready to Schedule or Send your live mailing.");
          }
        }
        else {
          $status = ts("Click 'Submit' when you are ready to Schedule or Send your live mailing.");
        }

        if ($status) {
          CRM_Core_Session::setStatus($status, ts('Test message sent'), 'success');
        }
        // stop redirecting to test mail url
        $url = CRM_Utils_System::url($urlString, $urlParams);
        CRM_Utils_System::redirect($url);
      }
    }
    $error = TRUE;
    return TRUE;
  }
  
  function postProcess() {
    $params = array();

    $params['mailing_id'] = $ids['mailing_id'] = $this->_mailingID;

    if (empty($params['mailing_id'])) {
      CRM_Core_Error::fatal(ts('Could not find a mailing id'));
    }

    foreach (array(
      'now', 'start_date', 'start_date_time') as $parameter) {
      $params[$parameter] = $this->controller->exportValue($this->_name,
        $parameter
      );
    }

    $mailing = new CRM_Mailing_BAO_Mailing();
    $mailing->id = $ids['mailing_id'];
    if ($mailing->find(TRUE)) {
      $job             = new CRM_Mailing_BAO_Job();
      $job->mailing_id = $mailing->id;
      $job->is_test    = 0;
      if ($job->find(TRUE)) {
        CRM_Core_Error::fatal(ts('A job for this mailing already exists'));
      }

      if (empty($mailing->is_template)) {
        $job->status = 'Scheduled';
        if ($params['now']) {
          $job->scheduled_date = date('YmdHis');
        }
        else {
          $job->scheduled_date = CRM_Utils_Date::processDate($params['start_date'] . ' ' . $params['start_date_time']);
        }
        $job->save();
      }

      // set approval details if workflow is not enabled
      if (!CRM_Mailing_Info::workflowEnabled()) {
        $session = CRM_Core_Session::singleton();
        $mailing->approver_id = $session->get('userID');
        $mailing->approval_date = date('YmdHis');
        $mailing->approval_status_id = 1;
      }
      else {
        // reset them in case this mailing was rejected
        $mailing->approver_id = 'null';
        $mailing->approval_date = 'null';
        $mailing->approval_status_id = 'null';
      }

      if ($mailing->approval_date) {
        $mailing->approval_date = CRM_Utils_Date::isoToMysql($mailing->approval_date);
      }

      // also set the scheduled_id
      $session = CRM_Core_Session::singleton();
      $mailing->scheduled_id = $session->get('userID');
      $mailing->scheduled_date = date('YmdHis');
      $mailing->created_date = CRM_Utils_Date::isoToMysql($mailing->created_date);
      $mailing->save();
    }
    
    $status = ts("Your mailing has been saved.");
    CRM_Core_Session::setStatus($status);
    $url = CRM_Utils_System::url('civicrm/view/quickbulkemail');
    return $this->controller->setDestination($url);
  }
}
