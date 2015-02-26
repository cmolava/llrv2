<?php

require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Sqlschedulejob_Form_SqlSettings extends CRM_Core_Form {
  function preProcess() {
      $this->editValues = NULL;
      if(isset($_GET['id']) && !empty($_GET['id'])){
          $this->editValues = $_GET['id'];
      }
      parent::preProcess();
  }
  function buildQuickForm() {
    #table Details
    $tableName         = CUSTOM_SQL_SCHEDULE_SETTING_TABLENAME;
    $sqlColumnName     = CUSTOM_SQL_SCHEDULE_SETTING_SQL_COLUMNNAME;
    $weightColumnName  = CUSTOM_SQL_SCHEDULE_SETTING_WEIGHT_TABLENAME;

    #form fields
    $this->addElement('text', 'existing_id', ts(''),  array('style' => 'display:none;' ));
    $this->add('textarea', 'sql_schedule', ts('SQL '), '',  true );
    $this->add('text', 'sql_schedule_weight', ts(' Weight '), array('class' => 'sql_schedule_weight' , 'size' => '5') );

    if($this->editValues){
        $sql = "SELECT * FROM {$tableName} WHERE id = %1";
        $dao = CRM_Core_DAO::executeQuery($sql, array( 1 => array($this->editValues , 'Integer')));
        if( $dao->fetch() ){
            $defaultValues["existing_id"] = $dao->id;
            $defaultValues["sql_schedule"] = $dao->$sqlColumnName;
            $defaultValues["sql_schedule_weight"] = $dao->$weightColumnName;
            $this->assign('existingWeight', $dao->$weightColumnName);
        }else{
            $defaultValues["existing_id"] = 0;
            $this->assign('existingWeight', NULL);
        }
        $this->setDefaults($defaultValues);
    }
    #submit button
    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => ts('Save'),
        'isDefault' => TRUE,
      ),
    ));
    parent::buildQuickForm();
  }//end build form

  function postProcess() {
    $values      = $this->_submitValues;
    $sRenewalSql = str_replace("\r\n", " ",$values['sql_schedule']);
    $sRenewalSql = str_replace("\"", " \\\"",$values['sql_schedule']);
    $iWeight     = $values['sql_schedule_weight'];
    $iId         = $values['existing_id'];
    #table name details
    $tableName         = CUSTOM_SQL_SCHEDULE_SETTING_TABLENAME;
    $sqlColumnName     = CUSTOM_SQL_SCHEDULE_SETTING_SQL_COLUMNNAME;
    $weightColumnName  = CUSTOM_SQL_SCHEDULE_SETTING_WEIGHT_TABLENAME;

    if(empty($iWeight)){
        $getMaxWeight       = "SELECT MAX({$weightColumnName})FROM $tableName";
        $getMaxWeightDAO    = CRM_Core_DAO::singleValueQuery($getMaxWeight);
        $iWeight            = $getMaxWeightDAO + 1;
    }
    if($iId == 0){

    $sql = <<<INSERT
        INSERT INTO `{$tableName}` ( {$sqlColumnName}, {$weightColumnName} ) VALUES ( "{$sRenewalSql}",  $iWeight )
INSERT;
			$status = "SQL has been added successfully";
			$action = 'SQL Added';
    } else {
    $sql = <<<UPDATE
        UPDATE `{$tableName}` SET {$sqlColumnName} =  "{$sRenewalSql}", {$weightColumnName} = {$iWeight}
        WHERE id = {$iId}
UPDATE;
			$status = "SQL has been updated successfully";
			$action = 'SQL Updated';
    }
    if( !empty($sRenewalSql) ){
        CRM_Core_DAO::executeQuery($sql);
    }

    #redirect after update
    CRM_Core_Session::setStatus($status, $action, 'alert');
    $url = CRM_Utils_System::url('civicrm/admin/viewsqlschedules' , 'reset=1');
    CRM_Utils_System::redirect($url);
    parent::postProcess();
  }//end post process
}
