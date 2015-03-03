<?php

require_once 'api/v3/Contact.php';
function civicrm_api3_vcontact_get($apiRequest) {
  $input = $apiRequest[''];
  //MV : check the api cal from custom contact ref / REST
  $customReference = False;
  if(empty($input) && isset($apiRequest['sort_name'])){
    $input = $apiRequest['sort_name'];
    $customReference = TRUE;
  }
  $contactList = array();
  // DS FIXME: 
  // 1. This api is only called for REST contact ref fields (core contact autocomplete fields)
  // 2. Custom contact ref field calls still go through CRM_Contact_Page_AJAX::contactReference() 
  // and only search on sort-name. We might want to replace contact-get api
  // there with this API.
  // 3. We need to consider offset and limit params we get
  //  [offset] => 0
  //  [rowCount] => 10
  //  [version] => 3
  //  [return.sort_name] => 1
  //  [return.email] => 1
  //  [return.phone] => 1
  //  [return.street_address] => 1
  //  [return.city] => 1
  //  [return.state_province] => 1
  //  [return.country] => 1
  //  [sort_name] => 5163
  //  [sort] => sort_name
  //  [check_permissions] => 0
  $selectFields  = array('sort_name', 'email', 'phone', 'street_address', 'city', 'postal_code', 'external_identifier');
  $selectClause  = implode(', ', $selectFields);
  if (strlen($input) >= 2) {
    $query = "SELECT cc.id as id, CONCAT_WS( ' :: ', {$selectClause}) as data, {$selectClause} 
      FROM civicrm_contact cc 
      LEFT JOIN civicrm_email eml ON ( cc.id = eml.contact_id AND eml.is_primary = 1 ) 
      LEFT JOIN civicrm_phone phe ON ( cc.id = phe.contact_id AND phe.is_primary = 1 ) 
      LEFT JOIN civicrm_address sts ON ( cc.id = sts.contact_id AND sts.is_primary = 1) 
      WHERE sts.postal_code LIKE '$input%'
      AND cc.is_deleted = 0
      LIMIT 0,15
      UNION ALL
      SELECT cc.id as id, CONCAT_WS( ' :: ', {$selectClause}) as data, {$selectClause}
      FROM civicrm_contact cc 
      LEFT JOIN civicrm_email eml ON ( cc.id = eml.contact_id AND eml.is_primary = 1 ) 
      LEFT JOIN civicrm_phone phe ON ( cc.id = phe.contact_id AND phe.is_primary = 1 ) 
      LEFT JOIN civicrm_address sts ON ( cc.id = sts.contact_id AND sts.is_primary = 1) 
      WHERE cc.external_identifier = '$input'
      AND cc.is_deleted = 0
      LIMIT 0,15
      UNION ALL
      SELECT cc.id as id, CONCAT_WS( ' :: ', {$selectClause}) as data, {$selectClause}   
      FROM civicrm_contact cc 
      LEFT JOIN civicrm_email eml ON ( cc.id = eml.contact_id AND eml.is_primary = 1 ) 
      LEFT JOIN civicrm_phone phe ON ( cc.id = phe.contact_id AND phe.is_primary = 1 ) 
      LEFT JOIN civicrm_address sts ON ( cc.id = sts.contact_id AND sts.is_primary = 1) 
      WHERE cc.display_name like '%$input%'
      AND cc.is_deleted = 0
      LIMIT 0,15"; 
    $dao = CRM_Core_DAO::executeQuery($query);
    $count = 0;
    while ($dao->fetch()) {
      $contactList[$count] =
        array(
          'data'      => $dao->data,
          'id'        => $dao->id,
        );
        foreach ($selectFields as $fields) {
           $contactList[$count][$fields] = $dao->$fields;
        }
        $count++ ;
    } 
  }
  return civicrm_api3_create_success($contactList, array(), 'vcontact', 'getlist');
}

function _civicrm_api3_vcontact_getlist_params(&$request) {
  return _civicrm_api3_contact_getlist_params($request);
}

function _civicrm_api3_vcontact_getlist_output($result, $request) {
  $request['label_field'] = "data";
  return _civicrm_api3_contact_getlist_output($result, $request);
}
