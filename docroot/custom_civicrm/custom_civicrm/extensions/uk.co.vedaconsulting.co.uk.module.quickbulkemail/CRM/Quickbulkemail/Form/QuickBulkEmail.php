<?php

require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Quickbulkemail_Form_QuickBulkEmail extends CRM_Core_Form {
  
   /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    $this->_mailingID = CRM_Utils_Request::retrieve('mid', 'Integer', $this, FALSE, NULL);
  }
  
  /**
   * This function sets the default values for the form.
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return None
   */
  function setDefaultValues() {
    // to continue the unscheduled or draft mailing
    $continue = $this->_continue = CRM_Utils_Request::retrieve('continue', 'String', $this, FALSE, NULL);
    $mailingID = CRM_Utils_Request::retrieve('mid', 'Integer', $this, FALSE, NULL);
    $defaults = array();
    if ($this->_mailingID) {
      // check that the user has permission to access mailing id
      CRM_Mailing_BAO_Mailing::checkPermission($this->_mailingID);

      $mailing = new CRM_Mailing_DAO_Mailing();
      $mailing->id = $this->_mailingID;
      $mailing->addSelect('name', 'campaign_id');
      $mailing->find(TRUE);

      $defaults['name'] = $mailing->name;
      if (!$continue) {
        $defaults['name'] = ts('Copy of %1', array(1 => $mailing->name));
      }
      else {
        // CRM-7590, reuse same mailing ID if we are continuing
        $this->set('mailing_id', $this->_mailingID);
      }

      $defaults['campaign_id'] = $mailing->campaign_id;
      $defaults['dedupe_email'] = $mailing->dedupe_email;

      $dao = new CRM_Mailing_DAO_Group();

      $mailingGroups = array(
        'civicrm_group' => array( ),
        'civicrm_mailing' => array( )
      );
      $dao->mailing_id = $this->_mailingID;
      $dao->find();
      while ($dao->fetch()) {
        // account for multi-lingual
        // CRM-11431
        $entityTable = 'civicrm_group';
        if (substr($dao->entity_table, 0, 15) == 'civicrm_mailing') {
          $entityTable = 'civicrm_mailing';
        }
        $mailingGroups[$entityTable][$dao->group_type][] = $dao->entity_id;
      }

      $defaults['includeGroups'] = $mailingGroups['civicrm_group']['Include'];
      $defaults['excludeGroups'] = CRM_Utils_Array::value('Exclude', $mailingGroups['civicrm_group']);

      if (!empty($mailingGroups['civicrm_mailing'])) {
        $defaults['includeMailings'] = CRM_Utils_Array::value('Include', $mailingGroups['civicrm_mailing']);
        $defaults['excludeMailings'] = CRM_Utils_Array::value('Exclude', $mailingGroups['civicrm_mailing']);
      }
    } else {
      $defaults['url_tracking'] = TRUE;
      $defaults['open_tracking'] = TRUE;
    }

    //set default message body
    $reuseMailing = FALSE;
    if ($mailingID) {
      $reuseMailing = TRUE;
    }
    else {
      $mailingID = $this->_mailingID;
    }

    $count = $this->get('count');
    $this->assign('count', $count);

    $this->set('skipTextFile', FALSE);
    $this->set('skipHtmlFile', FALSE);
    $htmlMessage = NULL;
    if ($mailingID) {
      $dao = new CRM_Mailing_DAO_Mailing();
      $dao->id = $mailingID;
      $dao->find(TRUE);
      $dao->storeValues($dao, $defaults);

      //we don't want to retrieve template details once it is
      //set in session
      $templateId = $this->get('template');
      $this->assign('templateSelected', $templateId ? $templateId : 0);
      if (isset($defaults['msg_template_id']) && !$templateId) {
        $defaults['template'] = $defaults['msg_template_id'];
        $messageTemplate = new CRM_Core_DAO_MessageTemplate();
        $messageTemplate->id = $defaults['msg_template_id'];
        $messageTemplate->selectAdd();
        $messageTemplate->selectAdd('msg_text, msg_html');
        $messageTemplate->find(TRUE);

        $defaults['text_message'] = $messageTemplate->msg_text;
        $htmlMessage = $messageTemplate->msg_html;
      }

      if (isset($defaults['body_text'])) {
        $defaults['text_message'] = $defaults['body_text'];
        $this->set('textFile', $defaults['body_text']);
        $this->set('skipTextFile', TRUE);
      }

      if (isset($defaults['body_html'])) {
        $htmlMessage = $defaults['body_html'];
        $this->set('htmlFile', $defaults['body_html']);
        $this->set('skipHtmlFile', TRUE);
      }

      //set default from email address.
      if (CRM_Utils_Array::value('from_name', $defaults) && CRM_Utils_Array::value('from_email', $defaults)) {
        $defaults['from_email_address'] = array_search('"' . $defaults['from_name'] . '" <' . $defaults['from_email'] . '>',
          CRM_Core_OptionGroup::values('from_email_address')
        );
      }
      else {
        //get the default from email address.
        $defaultAddress = CRM_Core_OptionGroup::values('from_email_address', NULL, NULL, NULL, ' AND is_default = 1');
        foreach ($defaultAddress as $id => $value) {
          $defaults['from_email_address'] = $id;
        }
      }

      if (CRM_Utils_Array::value('replyto_email', $defaults)) {
        $replyToEmail = CRM_Core_OptionGroup::values('from_email_address');
        foreach ($replyToEmail as $value) {
          if (strstr($value, $defaults['replyto_email'])) {
            $replyToEmailAddress = $value;
            break;
          }
        }
        $replyToEmailAddress = explode('<', $replyToEmailAddress);
        $replyToEmailAddress = $replyToEmailAddress[0] . '<' . $replyToEmailAddress[1];
        $this->replytoAddress = $defaults['reply_to_address'] = array_search($replyToEmailAddress, $replyToEmail);
      }
    }
    /*
    //set default from email address.
    if (CRM_Utils_Array::value('from_name', $defaults) && CRM_Utils_Array::value('from_email', $defaults)) {
      $defaults['from_email_address'] = array_search('"' . $defaults['from_name'] . '" <' . $defaults['from_email'] . '>',
        CRM_Core_OptionGroup::values('from_email_address')
      );
    }
    else {
      //get the default from email address.
      $defaultAddress = CRM_Core_OptionGroup::values('from_email_address', NULL, NULL, NULL, ' AND is_default = 1');
      foreach ($defaultAddress as $id => $value) {
        $defaults['from_email_address'] = $id;
      }
    }

    if (CRM_Utils_Array::value('replyto_email', $defaults)) {
      $replyToEmail = CRM_Core_OptionGroup::values('from_email_address');
      foreach ($replyToEmail as $value) {
        if (strstr($value, $defaults['replyto_email'])) {
          $replyToEmailAddress = $value;
          break;
        }
      }
      $replyToEmailAddress = explode('<', $replyToEmailAddress);
      $replyToEmailAddress = $replyToEmailAddress[0] . '<' . $replyToEmailAddress[1];
      $this->replytoAddress = $defaults['reply_to_address'] = array_search($replyToEmailAddress, $replyToEmail);
    }
    */

    //fix for CRM-2873
    if (!$reuseMailing) {
      $textFilePath = $this->get('textFilePath');
      if ($textFilePath &&
        file_exists($textFilePath)
      ) {
        $defaults['text_message'] = file_get_contents($textFilePath);
        if (strlen($defaults['text_message']) > 0) {
          $this->set('skipTextFile', TRUE);
        }
      }

      $htmlFilePath = $this->get('htmlFilePath');
      if ($htmlFilePath &&
        file_exists($htmlFilePath)
      ) {
        $defaults['html_message'] = file_get_contents($htmlFilePath);
        if (strlen($defaults['html_message']) > 0) {
          $htmlMessage = $defaults['html_message'];
          $this->set('skipHtmlFile', TRUE);
        }
      }
    }

    if ($this->get('html_message')) {
      $htmlMessage = $this->get('html_message');
    }

    $htmlMessage = str_replace(array("\n", "\r"), ' ', $htmlMessage);
    $htmlMessage = str_replace("'", "\'", $htmlMessage);
    $this->assign('message_html', $htmlMessage);

    $defaults['upload_type'] = 1;
    if (isset($defaults['body_html'])) {
      $defaults['html_message'] = $defaults['body_html'];
    }
    if(!empty( $defaults['html_message'] )){
      $this->assign( 'reuse_message_template', $defaults['html_message'] );
    }

    //CRM-4678 setdefault to default component when composing new mailing.
    if (!$reuseMailing) {
      $componentFields = array(
        'header_id' => 'Header',
        'footer_id' => 'Footer',
      );
      foreach ($componentFields as $componentVar => $componentType) {
        $defaults[$componentVar] = CRM_Mailing_PseudoConstant::defaultComponent($componentType, '');
      }
    }
    //end
    
    return $defaults;
  }  
  
  function buildQuickForm() {
    
    $config = CRM_Core_Config::singleton();
    $session = CRM_Core_Session::singleton();
      
    // add form elements
    $this->add('text', 'name', ts('Name Your Mailing'),
      CRM_Core_DAO::getAttribute('CRM_Mailing_DAO_Mailing', 'name'),
      TRUE
    );
	
    //get the mailing groups.
    $groups = CRM_Core_PseudoConstant::group('Mailing');
	
    $this->add(
      'select', // field type
      'includeGroups', // field name
      'Select Group', // field label
      array('' => '- select -') + $groups, // list of options
      true // is required
    );
	
    // Add campaign 
    // Start
    $mailingId = CRM_Utils_Request::retrieve('mid', 'Integer', $this, FALSE, NULL);
    $campaignId = NULL;
    if ($mailingId) {
      $campaignId = CRM_Core_DAO::getFieldValue('CRM_Mailing_DAO_Mailing', $mailingId, 'campaign_id');
    }
    CRM_Campaign_BAO_Campaign::addCampaign($this, $campaignId);
    // End
	
    // Add email subject and and template elements
    // Start
    $this->add('text', 'subject', ts('Subject'), 'size=50 maxlength=254', TRUE);
    CRM_Mailing_BAO_Mailing::commonCompose($this);
    // End
	
    // Advanced options - Tracking options
    // Start
    $this->addElement('checkbox', 'override_verp', ts('Track Replies?'));

    $defaults['override_verp'] = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
      'track_civimail_replies', NULL, FALSE
    );
    
    $this->add('checkbox', 'url_tracking', ts('Track Click-throughs?'));
    $defaults['url_tracking'] = TRUE;

    $this->add('checkbox', 'open_tracking', ts('Track Opens?'));
    //$this->add('checkbox', 'open_tracking', ts('Track Opens?'), '', array('value' => '1'), array('checked' => 'checked'));
    $defaults['open_tracking'] = TRUE;
    
    $this->add('checkbox', 'forward_replies', ts('Forward Replies?'));
    $defaults['forward_replies'] = FALSE;
    
    $this->add('checkbox', 'auto_responder', ts('Auto-respond to Replies?'));
    $defaults['auto_responder'] = FALSE;
    
    $this->add('select', 'reply_id', ts('Auto-responder'),
      CRM_Mailing_PseudoConstant::component('Reply'), TRUE
    );
    // End
    
    // From email address and reply to options
    // Start
    $options = array();
    // this seems so hacky, not sure what we are doing here and why. Need to investigate and fix
    $session->getVars($options,
      "CRM_Mailing_Controller_Send_{$this->controller->_key}"
    );
    
    $fromEmailAddress = CRM_Core_OptionGroup::values('from_email_address');
    if (empty($fromEmailAddress)) {
      //redirect user to enter from email address.
      $url = CRM_Utils_System::url('civicrm/admin/options/from_email_address', 'group=from_email_address&action=add&reset=1');
      $status = ts("There is no valid from email address present. You can add here <a href='%1'>Add From Email Address.</a>", array(1 => $url));
      $session->setStatus($status);
    }
    else {
      foreach ($fromEmailAddress as $key => $email) {
        $fromEmailAddress[$key] = htmlspecialchars($fromEmailAddress[$key]);
      }
    }
    
    
    $this->add('select', 'from_email_address',
      ts('From Email Address'), array(
        '' => '- select -') + $fromEmailAddress, TRUE
    );
    
    //echo "<pre>";print_r ($config);echo "</pre>";
    //Added code to add custom field as Reply-To on form when it is enabled from Mailer settings
    if (isset($config->replyTo) && !empty($config->replyTo) &&
      ! CRM_Utils_Array::value( 'override_verp', $options ) ) {
      $this->add('select', 'reply_to_address', ts('Reply-To'),
        array('' => '- select -') + $fromEmailAddress
      );
    }
    elseif (CRM_Utils_Array::value('override_verp', $options)) {
      $trackReplies = TRUE;
      $this->assign('trackReplies', $trackReplies);
    }
    
    // Mailing Header and footer
    // Start
    $this->add('select', 'header_id', ts('Mailing Header'),
      array('' => ts('- none -')) + CRM_Mailing_PseudoConstant::component('Header')
    );
    $this->add('select', 'footer_id', ts('Mailing Footer'),
      array('' => ts('- none -')) + CRM_Mailing_PseudoConstant::component('Footer')
    );
    // End
    
    #@madav getting default header na footer id to tpl
    #start
    $this->assign('headerId', key(CRM_Mailing_PseudoConstant::component('Header')));
    $this->assign('footerId', key(CRM_Mailing_PseudoConstant::component('Footer')));
    #end
    
    // Exclude from groups, Innclude/Exclude mailings
    // Start
    $outG = &$this->addElement('advmultiselect', 'excludeGroups',
      ts('Exclude Group(s)') . ' ',
      $groups,
      array(
        'size' => 5,
        'style' => 'width:240px',
        'class' => 'advmultiselect',
      )
    );
    $outG->setButtonAttributes('add', array('value' => ts('Add >>')));
    $outG->setButtonAttributes('remove', array('value' => ts('<< Remove')));
    
    $mailings = CRM_Mailing_PseudoConstant::completed();
    if (!$mailings) {
      $mailings = array();
    }
    
    $inM = &$this->addElement('advmultiselect', 'includeMailings',
      ts('INCLUDE Recipients of These Mailing(s)') . ' ',
      $mailings,
      array(
        'size' => 5,
        'style' => 'width:240px',
        'class' => 'advmultiselect',
      )
    );
    $outM = &$this->addElement('advmultiselect', 'excludeMailings',
      ts('EXCLUDE Recipients of These Mailing(s)') . ' ',
      $mailings,
      array(
        'size' => 5,
        'style' => 'width:240px',
        'class' => 'advmultiselect',
      )
    );

    $inM->setButtonAttributes('add', array('value' => ts('Add >>')));
    $outM->setButtonAttributes('add', array('value' => ts('Add >>')));
    $inM->setButtonAttributes('remove', array('value' => ts('<< Remove')));
    $outM->setButtonAttributes('remove', array('value' => ts('<< Remove')));
    
    $this->assign('mailingCount', count($mailings));
    // End
    
    $this->addFormRule(array('CRM_Quickbulkemail_Form_QuickBulkEmail', 'formRule'));
    
    // Schedule or send
    // Start
    //$this->addDateTime('start_date', ts('Schedule Mailing'), FALSE, array('formatType' => 'mailing'));
    //$this->addElement('checkbox', 'now', ts('Send Immediately'));
    // End
    
    $buttons = array(
      array('type' => 'next',
        'name' => ts('Schedule & Send >>'),
        'spacing' => '&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;',
        'isDefault' => TRUE,
      ),
      array(
        'type' => 'submit',
        'name' => ts('Save & Continue Later'),
      ),
    );
    $this->addButtons($buttons);

    // export form elements
    // $this->assign('elementNames', $this->getRenderableElementNames());
    parent::buildQuickForm();
  }
  
  /**
   * global validation rules for the form
   *
   * @param array $fields posted values of the form
   *
   * @return array list of errors to be posted back to the form
   * @static
   * @access public
   */
  static
  function formRule($fields) {
    $errors = array();
    
    // Validate include/exclude groups, mailings
    // Start
    if (isset($fields['includeGroups']) &&
      isset($fields['excludeGroups']) &&
      is_array($fields['excludeGroups'])
    ) {
      $checkGroups = array();
      $checkGroups = in_array($fields['includeGroups'], $fields['excludeGroups']);
      if (!empty($checkGroups)) {
        $errors['excludeGroups'] = ts('Cannot have same groups in Include Group(s) and Exclude Group(s).');
      }
    }

    if (isset($fields['includeMailings']) &&
      is_array($fields['includeMailings']) &&
      isset($fields['excludeMailings']) &&
      is_array($fields['excludeMailings'])
    ) {
      $checkMailings = array();
      $checkMailings = array_intersect($fields['includeMailings'], $fields['excludeMailings']);
      if (!empty($checkMailings)) {
        $errors['excludeMailings'] = ts('Cannot have same mail in Include mailing(s) and Exclude mailing(s).');
      }
    }

    if (!empty($fields['search_id']) &&
      empty($fields['group_id'])
    ) {
      $errors['group_id'] = ts('You must select a group to filter on');
    }

    if (empty($fields['search_id']) &&
      !empty($fields['group_id'])
    ) {
      $errors['search_id'] = ts('You must select a search to filter');
    }
    // End
    
    // Validate message template html/text
    // Start
    
    $errors = array();
    $template = CRM_Core_Smarty::singleton();


    if (isset($fields['html_message'])) {
      $htmlMessage = str_replace(array("\n", "\r"), ' ', $fields['html_message']);
      $htmlMessage = str_replace("'", "\'", $htmlMessage);
      $template->assign('htmlContent', $htmlMessage);
    }
    
    $domain = CRM_Core_BAO_Domain::getDomain();

    $session = CRM_Core_Session::singleton();
    $values = array('contact_id' => $session->get('userID'),
      'version' => 3,
    );
    require_once 'api/api.php';
    $contact = civicrm_api('contact', 'get', $values);

    //CRM-4524
    $contact = reset($contact['values']);

    $verp = array_flip(array('optOut', 'reply', 'unsubscribe', 'resubscribe', 'owner'));
    foreach ($verp as $key => $value) {
      $verp[$key]++;
    }

    $urls = array_flip(array('forward', 'optOutUrl', 'unsubscribeUrl', 'resubscribeUrl'));
    foreach ($urls as $key => $value) {
      $urls[$key]++;
    }


    // set $header and $footer
    foreach (array(
      'header', 'footer') as $part) {
      $$part = array();
      if ($fields["{$part}_id"]) {
        //echo "found<p>";
        $component = new CRM_Mailing_BAO_Component();
        $component->id = $fields["{$part}_id"];
        $component->find(TRUE);
        ${$part}['textFile'] = $component->body_text;
        ${$part}['htmlFile'] = $component->body_html;
        $component->free();
      }
      else {
        ${$part}['htmlFile'] = ${$part}['textFile'] = '';
      }
    }
    
    if (!CRM_Utils_Array::value('text_message', $fields) && !CRM_Utils_Array::value('html_message', $fields)) {
        $errors['html_message'] = ts('Please provide either a Text or HTML formatted message - or both.');
    }
    
    foreach (array(
      'text', 'html') as $file) {


      $str  = $fields[$file . '_message'];
      $str  = ($file == 'html') ? str_replace('%7B', '{', str_replace('%7D', '}', $str)) : $str;
      $name = $file . ' message';
      

      /* append header/footer */

      $str = $header[$file . 'File'] . $str . $footer[$file . 'File'];

      $dataErrors = array();
      
      /* First look for missing tokens */

      $err = CRM_Utils_Token::requiredTokens($str);
      if ($err !== TRUE) {
        foreach ($err as $token => $desc) {
          $dataErrors[] = '<li>' . ts('This message is missing a required token - {%1}: %2',
            array(1 => $token, 2 => $desc)
          ) . '</li>';
        }
      }

      /* Do a full token replacement on a dummy verp, the current
             * contact and domain, and the first organization. */


      // here we make a dummy mailing object so that we
      // can retrieve the tokens that we need to replace
      // so that we do get an invalid token error
      // this is qute hacky and I hope that there might
      // be a suggestion from someone on how to
      // make it a bit more elegant

      $dummy_mail        = new CRM_Mailing_BAO_Mailing();
      $mess              = "body_{$file}";
      $dummy_mail->$mess = $str;
      $tokens            = $dummy_mail->getTokens();

      $str = CRM_Utils_Token::replaceSubscribeInviteTokens($str);
      $str = CRM_Utils_Token::replaceDomainTokens($str, $domain, NULL, $tokens[$file]);
      $str = CRM_Utils_Token::replaceMailingTokens($str, $mailing, NULL, $tokens[$file]);
      $str = CRM_Utils_Token::replaceOrgTokens($str, $org);
      $str = CRM_Utils_Token::replaceActionTokens($str, $verp, $urls, NULL, $tokens[$file]);
      $str = CRM_Utils_Token::replaceContactTokens($str, $contact, NULL, $tokens[$file]);

      $unmatched = CRM_Utils_Token::unmatchedTokens($str);

      if (!empty($unmatched) && 0) {
        foreach ($unmatched as $token) {
          $dataErrors[] = '<li>' . ts('Invalid token code') . ' {' . $token . '}</li>';
        }
      }
      if (!empty($dataErrors)) {
        $errors[$file . '_message'] = ts('The following errors were detected in %1:', array(
          1 => $name)) . ' <ul>' . implode('', $dataErrors) . '</ul><br /><a href="' . CRM_Utils_System::docURL2('Sample CiviMail Messages', TRUE, NULL, NULL, NULL, "wiki") . '" target="_blank">' . ts('More information on required tokens...') . '</a>';
      }
    }
    
    // End

    return empty($errors) ? TRUE : $errors;
  }

  function postProcess() {
    
    if (!$this->get('mailing_id')) {
      // Create mailing
      $this->createMailing();
    }
    
    $mid = $this->get('mailing_id');
    
    $status = ts("You can schedule this mailing to be sent starting at a specific date and time, OR you can request that it be sent as soon as possible by checking 'Send Immediately'");
    CRM_Core_Session::setStatus($status);
    $url = CRM_Utils_System::url('civicrm/quickbulkemailschedule', "mid=$mid&reset=1");
    $actionName = $this->controller->_actionName; 
    if( array_search('submit', $actionName)){
      $url = CRM_Utils_System::url('civicrm/view/quickbulkemail');
    }
   
    return $this->controller->setDestination($url);
    
  }

  /*
   * Function to create the mailing based on the posted params
   */
  function createMailing() {
      
    // Create the mailing and update the group/receipients
    $this->updateGroupandRecipients();
    
    // Update tracking options
    $this->updateTrackingOptions();
    
    // Update the templates and upload options
    $this->updateTemplateOptions();
  }

  /**
   * Function to update group and receipients and create the mailing   
   */
  function updateGroupandRecipients() {
    
    $values = $this->controller->exportValues($this->_name);
    $groups = array();
    $ids = array();
    foreach (array(
      'name', 'group_id', 'search_id', 'search_args', 'campaign_id', 'dedupe_email') as $n) {
      if (CRM_Utils_Array::value($n, $values)) {
        $params[$n] = $values[$n];
      }
    }
    
    $qf_Group_submit = $this->controller->exportValue($this->_name, '_qf_Group_submit');
    $this->set('name', $params['name']);
    
    $inGroups    = $values['includeGroups'];
    $outGroups   = $values['excludeGroups'];
    $inMailings  = $values['includeMailings'];
    $outMailings = $values['excludeMailings'];

    $groups['include'][] = $values['includeGroups'];
    
    if (is_array($outGroups)) {
      foreach ($outGroups as $key => $id) {
        if ($id) {
          $groups['exclude'][] = $id;
        }
      }
    }

    $mailings = array();
    if (is_array($inMailings)) {
      foreach ($inMailings as $key => $id) {
        if ($id) {
          $mailings['include'][] = $id;
        }
      }
    }
    if (is_array($outMailings)) {
      foreach ($outMailings as $key => $id) {
        if ($id) {
          $mailings['exclude'][] = $id;
        }
      }
    }

    $session            = CRM_Core_Session::singleton();
    $params['groups']   = $groups;
    $params['mailings'] = $mailings;
    if( $this->_continue ){
      $params['id'] =   $ids['id']   = $this->_mailingID;
    }
    
    // new mailing, so lets set the created_id
    $session = CRM_Core_Session::singleton();
    $params['created_id'] = $session->get('userID');
    $params['created_date'] = date('YmdHis');
    
    $mailing = CRM_Mailing_BAO_Mailing::create($params, $ids);
    
    $this->set('mailing_id', $mailing->id);

    $dedupeEmail = FALSE;
    if (isset($params['dedupe_email'])) {
      $dedupeEmail = $params['dedupe_email'];
    }

    // also compute the recipients and store them in the mailing recipients table
    CRM_Mailing_BAO_Mailing::getRecipients($mailing->id,
      $mailing->id,
      NULL,
      NULL,
      TRUE,
      $dedupeEmail
    );

    $count = CRM_Mailing_BAO_Recipients::mailingSize($mailing->id);
    $this->set('count', $count);
    $this->assign('count', $count);
    $this->set('groups', $groups);
    $this->set('mailings', $mailings);
  }
  
  /*
   * Function to update tracking options to the mailing
   */
  function updateTrackingOptions() {
      // Tracking and reply options
    $params = $ids = array();
    $uploadParams = array('reply_id', 'unsubscribe_id', 'optout_id', 'resubscribe_id');
    $uploadParamsBoolean = array('override_verp', 'forward_replies', 'url_tracking', 'open_tracking', 'auto_responder');

    $qf_Settings_submit = $this->controller->exportValue($this->_name, '_qf_Settings_submit');

    foreach ($uploadParams as $key) {
      $params[$key] = $this->controller->exportvalue($this->_name, $key);
      $this->set($key, $this->controller->exportvalue($this->_name, $key));
    }

    foreach ($uploadParamsBoolean as $key) {
      if ($this->controller->exportvalue($this->_name, $key)) {
        $params[$key] = TRUE;
      }
      else {
        $params[$key] = FALSE;
      }
      $this->set($key, $this->controller->exportvalue($this->_name, $key));
    }

    $params['visibility'] = $this->controller->exportvalue($this->_name, 'visibility');

    // override_verp must be flipped, as in 3.2 we reverted
    // its meaning to ‘should CiviMail manage replies?’ – i.e.,
    // ‘should it *not* override Reply-To: with VERP-ed address?’
    $params['override_verp'] = !$params['override_verp'];

    $ids['mailing_id'] = $this->get('mailing_id');

    // update mailing
    CRM_Mailing_BAO_Mailing::create($params, $ids);
  }
  
  /*
   * Function to update template options
   */
  
  function updateTemplateOptions() {
      // Update the templates and upload options
    $params       = $ids = array();
    $uploadParams = array('header_id', 'footer_id', 'subject', 'from_name', 'from_email');
    $fileType     = array('textFile', 'htmlFile');

    $formValues = $this->controller->exportValues($this->_name);
    foreach ($uploadParams as $key) {
      if (CRM_Utils_Array::value($key, $formValues) ||
        in_array($key, array('header_id', 'footer_id'))
      ) {
        $params[$key] = $formValues[$key];
        $this->set($key, $formValues[$key]);
      }
    }

    
    $text_message = $formValues['text_message'];
    $params['body_text'] = $text_message;
    $this->set('textFile', $params['body_text']);
    $this->set('text_message', $params['body_text']);
    $html_message = $formValues['html_message'];

    // dojo editor does some html conversion when tokens are
    // inserted as links. Hence token replacement fails.
    // this is hack to revert html conversion for { to %7B and
    // } to %7D by dojo editor
    $html_message = str_replace('%7B', '{', str_replace('%7D', '}', $html_message));

    $params['body_html'] = $html_message;
    $this->set('htmlFile', $params['body_html']);
    $this->set('html_message', $params['body_html']);
    

    $params['name'] = $this->get('name');
    

    $session = CRM_Core_Session::singleton();
    $params['contact_id'] = $session->get('userID');
    $composeFields = array(
      'template', 'saveTemplate',
      'updateTemplate', 'saveTemplateName',
    );
    
    CRM_Core_BAO_File::formatAttachment($formValues,
      $params,
      'civicrm_mailing',
      $this->get('mailing_id')
    );
    $ids['mailing_id'] = $this->get('mailing_id');
    
    //set msg_template_id 
    $params['msg_template_id'] = CRM_Utils_Array::value('template', $formValues);
    $this->set('template', $params['msg_template_id']);
	
    //handle mailing from name & address.
    $fromEmailAddress = CRM_Utils_Array::value($formValues['from_email_address'],
      CRM_Core_PseudoConstant::fromEmailAddress('from_email_address')
    );

    //get the from email address
    $params['from_email'] = CRM_Utils_Mail::pluckEmailFromHeader($fromEmailAddress);

    //get the from Name
    $params['from_name'] = CRM_Utils_Array::value(1, explode('"', $fromEmailAddress));

    //Add Reply-To to headers
    if (CRM_Utils_Array::value('reply_to_address', $formValues)) {
      $replyToEmail = CRM_Core_PseudoConstant::fromEmailAddress('from_email_address');
      $params['replyto_email'] = CRM_Utils_Array::value($formValues['reply_to_address'], $replyToEmail);
    }
    
    /* Build the mailing object */

     CRM_Mailing_BAO_Mailing::create($params, $ids);
  }
}
