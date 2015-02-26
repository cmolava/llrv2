<?php

require_once 'CRM/Admin/Form/Setting.php';
require_once 'CRM/Core/BAO/CustomField.php';

class CRM_Admin_Form_Setting_TrackingMailingSettingsForm extends CRM_Admin_Form_Setting {

  public function buildQuickForm() {
    CRM_Utils_System::setTitle( ts( 'Tracking Mailing Settings' ) );

    $aCustomFields = CRM_Core_BAO_CustomField::getFields();
    $aCf = array();
    foreach ( $aCustomFields as $k => $v ) {
      $aCf[$k] = $v['label'];
    }

    $this->addElement( 'text'
                     , 'unsubscribe_redirect_url'
                     , ts( 'URL to redirect to when user want\'s to unsubscribe.' )
                     );

    parent::buildQuickForm();
  }
}