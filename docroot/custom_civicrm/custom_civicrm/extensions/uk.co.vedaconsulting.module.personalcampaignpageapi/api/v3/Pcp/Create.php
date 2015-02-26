<?php

/**
 * An example API call
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
require_once 'CRM/PCP/BAO/PCP.php';
function civicrm_api3_Pcp_create($params) {
     $result['is_error'] = 0;
     if(!$params['contact_id'] 
          || !$params['page_id']
          || !$params['title']
          || !$params['intro_text']
          || !$params['goal_amount']
         ){
              $result['error_code'] = "mandatory missing";
              $result['entity'] = "PCP";
              $result['action'] = "create";
              $result['is_error'] = 1;
              $result['error_message'] = "Mandatory(s) Field contact_id, page_id, title, intro_text, goal_amount";
              return $result;
     }
     if(!$params['campaign_id'] 
          || !$params['drupal_node_id']
         ){
              $result['error_code'] = "mandatory missing";
              $result['entity'] = "PCP";
              $result['action'] = "create";
              $result['is_error'] = 1;
              $result['error_message'] = "Mandatory(s) Field drupal_node_id, campaign_id";
              return $result;
     }
     if(!array_key_exists('status_id', $params)){
       $params['status_id'] = '1';
     }
     if(!array_key_exists('page_type', $params)){
       $params['page_type'] = 'contribute';
     }
     if(!array_key_exists('is_thermometer', $params)){
       $params['is_thermometer'] = '1';
     }
     if(!array_key_exists('is_honor_roll', $params)){
       $params['is_honor_roll'] = '1';
     }
     if(!array_key_exists('currency', $params)){
       $params['currency'] = 'GBP';
     }
     if(!array_key_exists('is_active', $params)){
       $params['is_active'] = '1';
     }
     if(!array_key_exists('donate_link_text', $params)){
       $params['donate_link_text'] = 'Donation';
     }
     $result['version'] = 3;
     $result['count']   = 0;
     $result['values']  = array();
     $add = (array) CRM_PCP_BAO_PCP::add($params, false);
     $result['count']   = count($add['id']);
     if(!empty( $add['id'])
             && !empty($params['campaign_id'])
             && !empty($params['drupal_node_id'])
             ){
       
       _civicrm_api3_insert_pcpCampaign($add['id'], $params['campaign_id'], $params['drupal_node_id']);
     }
     $result['values'][] = array(
         'id'               => $add['id'],
         'contact_id'       => $add['contact_id'],
         'status_id'        => $add['status_id'],
         'title'            => $add['title'],
         'intro_text'       => $add['intro_text'],
         'page_text'        => $add['page_text'],
         'donate_link_text' => $add['donate_link_text'],
         'page_id'          => $add['page_id'],
         'page_type'        => $add['page_type'],
         'pcp_block_id'     => $add['pcp_block_id'],
         'is_thermometer'   => $add['is_thermometer'],
         'is_honor_roll'    => $add['is_honor_roll'],
         'goal_amount'      => $add['goal_amount'],
         'currency'         => $add['currency'],
         'currency'         => $add['currency'],
         'campaign_id'      => $params['campaign_id'],
         'drupal_node_id'   => $params['drupal_node_id'],
         'is_active'        => $add['is_active'],
     );
     
     return $result;
}
function _civicrm_api3_insert_pcpCampaign($pcp_id, $campaign_id, $drupal_node_id) {
  $insert = "INSERT INTO civicrm_pcp_campaign
                    ( `pcp_id`
                     , `campaign_id`
                     , `drupal_node_id`
                     ) VALUES 
                     (  %1
                      , %2
                      , %3
                      )                      
                      ";
  $params = array(
      1 => array( $pcp_id, 'Integer'),
      2 => array( $campaign_id, 'Integer'),
      3 => array( $drupal_node_id, 'Integer'),
  );
   CRM_Core_DAO::executeQuery($insert, $params);
}

