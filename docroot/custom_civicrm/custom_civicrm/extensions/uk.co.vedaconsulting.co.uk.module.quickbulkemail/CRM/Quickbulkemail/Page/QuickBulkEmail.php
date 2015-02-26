<?php

require_once 'CRM/Core/Page.php';

/**
 * @madav
 * Page to View list of Quick bulk mailing 
 */
class CRM_Quickbulkemail_Page_QuickBulkEmail extends CRM_Core_Page {
  
  /*
   * function to get contact display name by $cid
   * return display name 
   */
    function get_contact_name( $cid ){
      if(empty($cid)){
        return "Null";
      }
      $params = array(
        'version' => 3,
        'sequential' => 1,
        'id'  =>  $cid
      );
      $aContact = civicrm_api('Contact', 'get', $params);
      if( $aContact['is_error'] != 1 && $aContact['count']!= 0 ){
       
      /*  $name = sprintf( "<a href='civicrm/contact/view?reset=1&cid=%d'>%s</a>"
                                      , $cid
                                      , $aContact['values'][0]['display_name']
                                      );
       
       */
        $name =  $aContact['values'][0]['display_name'];
      }
      return $name; 
    }
    
    /**
     * function to get the list of all mailing 
     * return scheduled, draft and complete mailings
     * @return string
     */
    function get_all_mailings(){
      
      #api return count 25 as default.
      #set max count in rowCount as 500
      $params = array(
        'version' => 3,
        'sequential' => 1,
        'rowCount' => 500 
      );
      
      /*$sql = "Select count(*) as count From civicrm_mailing";
      $mailingCount = CRM_Core_DAO::singleValueQuery($sql);
      if($mailingCount > 25){
        $params['rowCount'] = $mailingCount;
      }*/
      $aMailings = civicrm_api('Mailing', 'get', $params);
      if( $aMailings['is_error'] == 1 ){
         watchdog( WATCHDOG_DEBUG
              , "Calling get_all_mailings():\n" .
                '$aMailings:'   . print_r( $aMailings, true ) . "\n"
              );
        return;
      }
      foreach ( $aMailings['values'] as $key => $mailing ){
        $pMailingJob = array(
                  'version' => 3,
                  'sequential' => 1,
                  'mailing_id' => $mailing['id']
                  );
      $aMailingJob = civicrm_api('MailingJob', 'get', $pMailingJob);
      if( $aMailingJob['is_error'] != 1 && $aMailingJob['count']!= 0 ){
        $status = $aMailingJob['values'][0]['status'];
        $stDate = array_search('start_date', $aMailingJob['values'][0]);
        $endDate = array_search('end_date', $aMailingJob['values'][0]);
        $not_scheduled = false;
          if($stDate){
            $start_date = CRM_Utils_Date::customFormat($stDate);
          }else{
             $start_date = 'NULL';
          }
          if($endDate){
            $end_date = CRM_Utils_Date::customFormat($endDate);
          }else{
             $end_date = 'NULL';
          }
      }else{
        $status = "Draft";
        $start_date = $end_date = 'NULL';
        $not_scheduled = true;
      }
    
      //get change the action link based on the status
      if($not_scheduled){
        $actionUrl = CRM_Utils_System::url( 'civicrm/quickbulkemail', 'mid='.$mailing['id'].'&continue=true&reset=1');
        $actionName = 'Edit';
      }else{
        $actionUrl = CRM_Utils_System::url( 'civicrm/quickbulkemail', 'mid='.$mailing['id'].'&reset=1');
        $actionName = 'Re-Use';
      }
      
      //get scheduled_id and contact name
      if(!empty( $mailing['scheduled_id'] )){
        $scheduled_id   = $mailing['scheduled_id'];
        $scheduled_name = self::get_contact_name( $mailing['scheduled_id'] );
      }else{
        $scheduled_id   = 'NULL';
        $scheduled_name = 'NULL';
      }
      
      $action = "<span><a href='".$actionUrl."'>".$actionName."</a></span>" ;
      $rows[] = array( 'id'           => $mailing['id']
                      ,'name'         => $mailing['name']
                      ,'status'       => $status
                      ,'created_date' => CRM_Utils_Date::customFormat($mailing['created_date'])
                      ,'scheduled'    => CRM_Utils_Date::customFormat($mailing['scheduled_date'])
                      ,'start'        => $start_date
                      ,'end'          => $end_date
                      ,'created_by'   => self::get_contact_name( $mailing['created_id'] )
                      ,'scheduled_by' => $scheduled_name
                      ,'created_id'   => $mailing['created_id']
                      ,'scheduled_id' => $scheduled_id
                      ,'action'       => $action
                ); 
      }
      return $rows;
    }
    function run(){
      #@madav
      #assigning table column headers in array to use both thead and tfoot.
      $columnHeaders = array( "Mailing Name", "Status", "Created By", "Sent BY", "Scheduled", "Started", "Completed", "Action" );
      $this->assign('columnHeaders', $columnHeaders); 
      
      #getting list of mailings.
      $rows =  self::get_all_mailings();
      $this->assign('rows', $rows); 
     
      parent::run();
    }
  }
