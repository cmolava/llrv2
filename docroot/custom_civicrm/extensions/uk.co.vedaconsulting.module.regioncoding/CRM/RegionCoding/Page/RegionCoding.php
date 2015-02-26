<?php

require_once 'CRM/Core/Page.php';

class CRM_RegionCoding_Page_RegionCoding extends CRM_Core_Page {
  function run() {
    $params = array(
        'version' => '3',
        'sequential' => '1'
    );
   
    $codeRegions = civicrm_api('Contact', 'coderegions', $params);
    print_r($codeRegions);
    
    parent::run();
  }
}
