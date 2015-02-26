<?php

require_once 'sqlschedulejob.civix.php';
define("CUSTOM_SQL_SCHEDULE_SETTING_TABLENAME", "civicrm_sql_schedule_setting");
define("CUSTOM_SQL_SCHEDULE_SETTING_SQL_COLUMNNAME", "schedule_sql");
define("CUSTOM_SQL_SCHEDULE_SETTING_WEIGHT_TABLENAME", "sql_weight");

/**
 * Implementation of hook_civicrm_config
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function sqlschedulejob_civicrm_config(&$config) {
  _sqlschedulejob_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function sqlschedulejob_civicrm_xmlMenu(&$files) {
  _sqlschedulejob_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function sqlschedulejob_civicrm_install() {
  return _sqlschedulejob_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function sqlschedulejob_civicrm_uninstall() {
  return _sqlschedulejob_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function sqlschedulejob_civicrm_enable() {
  return _sqlschedulejob_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function sqlschedulejob_civicrm_disable() {
  return _sqlschedulejob_civix_civicrm_disable();
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
function sqlschedulejob_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _sqlschedulejob_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function sqlschedulejob_civicrm_managed(&$entities) {
  return _sqlschedulejob_civix_civicrm_managed($entities);
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
function sqlschedulejob_civicrm_caseTypes(&$caseTypes) {
  _sqlschedulejob_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implementation of hook_civicrm_alterSettingsFolders
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function sqlschedulejob_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _sqlschedulejob_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

function sqlschedulejob_civicrm_navigationMenu( &$params ) {
    #Get the maximum key of $params
    $maxKey = max(array_keys($params)); 
		
    #set settings navigation Id 
    $navId  = $maxKey+1;
    
    #set navigation menu
		$parentId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Navigation', 'Administer', 'id', 'name');
    $params[$parentId]['child'][$navId] = array (
                          'attributes' => array (
                                            'label'      => 'Scheduled Job - SQLs',
                                            'name'       => 'Scheduled Job - SQLs',
                                            'url'        => 'civicrm/admin/viewsqlschedules?reset=1',
                                            'permission' => 'administer CiviCRM',
                                            'operator'   => null,
                                            'separator'  => 2,
                                            'parentID'   => $parentId,
                                            'navID'      => $navId,
                                            'active'     => 1
                                            )
                      );
}