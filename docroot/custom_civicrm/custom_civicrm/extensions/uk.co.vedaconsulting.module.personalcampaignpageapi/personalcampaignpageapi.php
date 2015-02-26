<?php

require_once 'personalcampaignpageapi.civix.php';

/**
 * Implementation of hook_civicrm_config
 */
function personalcampaignpageapi_civicrm_config(&$config) {
  _personalcampaignpageapi_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 */
function personalcampaignpageapi_civicrm_xmlMenu(&$files) {
  _personalcampaignpageapi_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 */
function personalcampaignpageapi_civicrm_install() {
   $sql = "CREATE TABLE IF NOT EXISTS civicrm_pcp_campaign
          (
          id int primary key AUTO_INCREMENT,
          pcp_id  int,
          campaign_id  int,
          drupal_node_id int
          )";
  CRM_Core_DAO::executeQuery($sql);
  return _personalcampaignpageapi_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 */
function personalcampaignpageapi_civicrm_uninstall() {
  return _personalcampaignpageapi_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 */
function personalcampaignpageapi_civicrm_enable() {
  return _personalcampaignpageapi_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function personalcampaignpageapi_civicrm_disable() {
  return _personalcampaignpageapi_civix_civicrm_disable();
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
function personalcampaignpageapi_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _personalcampaignpageapi_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 */
function personalcampaignpageapi_civicrm_managed(&$entities) {
  return _personalcampaignpageapi_civix_civicrm_managed($entities);
}
