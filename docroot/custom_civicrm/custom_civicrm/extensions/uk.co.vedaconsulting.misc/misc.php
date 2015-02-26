<?php

require_once 'misc.civix.php';

/**
 * Implementation of hook_civicrm_config
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function misc_civicrm_config(&$config) {
  _misc_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function misc_civicrm_xmlMenu(&$files) {
  _misc_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function misc_civicrm_install() {
  return _misc_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function misc_civicrm_uninstall() {
  return _misc_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function misc_civicrm_enable() {
  return _misc_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function misc_civicrm_disable() {
  return _misc_civix_civicrm_disable();
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
function misc_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _misc_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function misc_civicrm_managed(&$entities) {
  return _misc_civix_civicrm_managed($entities);
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
function misc_civicrm_caseTypes(&$caseTypes) {
  _misc_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implementation of hook_civicrm_alterSettingsFolders
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function misc_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _misc_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

// KJ This code was transfered here from civicrm_contribution_default drupal module and couldn't reproduce the usage of the following code that is why commenting it out
// Removed civicrm_contribution_default drupal module
/*
function misc_civicrm_buildForm($formName, &$form) {
  switch($formName) {
    case 'CRM_Contribute_Form_Contribution_Main':
      // Fill in contribution amount if present in the querystring.
      if (is_numeric($_GET['amount_other'])) {
        $defaults = array(
          'amount_other' => $_GET['amount_other'],
        );
        $form->setDefaults($defaults);
      };

    break;
  }
}
 * END
 */