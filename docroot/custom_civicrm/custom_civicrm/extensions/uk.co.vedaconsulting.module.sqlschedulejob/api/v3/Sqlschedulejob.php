<?php

/**
 * Added by Mathavan@vedaconsulting.co.uk
 * New Custom API to execute the SQL stored in table civicrm_membership_renewal_sql_setting
 * @param type $params
 * @return string
 */
function civicrm_api3_sqlschedulejob_run() {
    $sqlColumnName      = CUSTOM_SQL_SCHEDULE_SETTING_SQL_COLUMNNAME;
    $aAllSettingsSqls   = _get_all_schedule_settings_sql_from_table();
    foreach( $aAllSettingsSqls as $settingSQLs ){
        $tempsplit = explode(";", $settingSQLs[$sqlColumnName]);
        foreach( $tempsplit as $splitedSQL ){
          $aSqls[] = $splitedSQL;
        }
    }
    
    #EXECUTE ALL SQLS
    foreach($aSqls as $sql){
      try{
        checkNullvalues($sql);
        CRM_Core_DAO::executeQuery($sql);
      }catch(Exception $e){
        CRM_Core_Error::debug_log_message($e->getMessage());
      }
    }
    
//    $DBParams   = $GLOBALS['_DB_DATAOBJECT']['CONNECTIONS'];
//    $oDBValues  = array_values($DBParams);
//    $DBValues   = $oDBValues[0]->dsn;
//    mysql_connect($DBValues['hostspec'],  $DBValues['username'], $DBValues['password'], true, 65536) or die("cannot connect");
//    mysql_select_db($DBValues['database']) or die("cannot use database");
//    $sToExecute = implode('; ', $aSqls);

//    if(!empty($sToExecute)){
//        die(mysql_error());
//        CRM_Core_Session::setStatus("Success");
//    }else{
//        CRM_Core_Session::setStatus("No SQL Settings can be stored.. Please Redirect to <baseURL>/civicrm/sql_schedule_settings");
//    }
    
    return ;
}

function checkNullvalues($str){
  if(empty($str)){
    throw new CRM_Core_Exception("No SQL String found, Null String.");
  }
  
  return true;
}

function _get_all_schedule_settings_sql_from_table(){
    #table name details
    $tableName         = CUSTOM_SQL_SCHEDULE_SETTING_TABLENAME;
    $weightColumnName  = CUSTOM_SQL_SCHEDULE_SETTING_WEIGHT_TABLENAME;
    $aRows             = array();
    $sql = "SELECT * FROM {$tableName} ORDER BY {$weightColumnName}";
    $dao = CRM_Core_DAO::executeQuery($sql);
    while($dao->fetch()){
        $aRows[] = $dao->toArray();
    }
    return $aRows;
}
