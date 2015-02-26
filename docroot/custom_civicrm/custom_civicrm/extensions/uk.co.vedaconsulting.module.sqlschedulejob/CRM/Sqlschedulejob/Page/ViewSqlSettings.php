<?php

require_once 'CRM/Core/Page.php';

class CRM_Sqlschedulejob_Page_ViewSqlSettings extends CRM_Core_Page {
  static function get_renewal_Setting_page(){
    $aRows             = array();
    $tableName         = CUSTOM_SQL_SCHEDULE_SETTING_TABLENAME;
    $sqlColumnName     = CUSTOM_SQL_SCHEDULE_SETTING_SQL_COLUMNNAME;
    $weightColumnName  = CUSTOM_SQL_SCHEDULE_SETTING_WEIGHT_TABLENAME;
    $sql=<<<SQL
    SELECT * 
    FROM {$tableName}
    ORDER BY {$weightColumnName} ASC
SQL;
    $dao = CRM_Core_DAO::executeQuery($sql);
    while($dao ->fetch()){
        
        $editURL = CRM_Utils_System::url('civicrm/admin/viewsqlschedules/add', 'reset=1&id=');
        $delURL  = CRM_Utils_System::url('civicrm/admin/viewsqlschedules', 'action=delete&id=');
        $aRows[] = array(
            'id'    => $dao->id,
            'sql'   => $dao->$sqlColumnName,
            'weight'=> $dao->$weightColumnName,
            'edit'  => sprintf("<a href='{$editURL}%d'>%s</a>", $dao->id, 'Edit' ),
            'delete'=> sprintf("<a href='{$delURL}%d' onclick=\"return confirm('Are you sure you want to delete this SQL?')\">%s</a>", $dao->id, 'Delete'),
        );
    }
    
    return $aRows;
  }
  function run() {
    $tableName      = CUSTOM_SQL_SCHEDULE_SETTING_TABLENAME;
    if(isset($_GET['action']) && $_GET['action'] == 'delete'){
        $deleteId = $_GET['id'];
        if(!empty( $deleteId )){
            $sql= "DELETE FROM {$tableName} WHERE id=%1";
            CRM_Core_DAO::executeQuery($sql, array( 1 => array($deleteId, 'Integer')));
        }
        $refreshURL = CRM_Utils_System::url('civicrm/admin/viewsqlschedules', 'reset=1');
        $status = "SQL has been deleted Successfully";
        CRM_Core_Session::setStatus($status, 'SQL Deleted', 'alert');
				CRM_Utils_System::redirect($refreshURL);
    }
    $settingsData   = self::get_renewal_Setting_page();
    $redirectURL    = CRM_Utils_System::url('civicrm/admin/viewsqlschedules/add','reset=1', true);
    $this->assign('settingsData', $settingsData);
    $this->assign('redirectURL', $redirectURL);
    parent::run();
  }
}
