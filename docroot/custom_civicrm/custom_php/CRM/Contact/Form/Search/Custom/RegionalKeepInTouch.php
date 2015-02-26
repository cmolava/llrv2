<?php
require_once 'CRM/Contact/Form/Search/Interface.php';

class CRM_Contact_Form_Search_Custom_RegionalKeepInTouch
  implements CRM_Contact_Form_Search_Interface {

  //
  protected $_formValues;

  /**
   * Class constructor
   */
  function __construct(&$formValues) {
    $this->_formValues = $formValues;

    // Columns for search results
    // ts('display text') => 'column selected'
    $this->_columns = array(
      ts('Contact Id') => 'contact_id',
      ts('Name') => 'FullName',
      ts('Activity Id') => 'ActivityID',
      ts('Source') => 'Source',
      ts('Info') => 'Info',
      ts('Region Id') => 'RegionID',
    );
  }

  /**
   * Create search form
   */
  function buildForm(&$form) {
     $this->setTitle('Contacts that would like to keep in touch with the organisation, by region');

    // Search form fields
    //
    // TODO: This should be a select1 control with a numeric value? Ideally 
    // yes, but Owen says this is not essential for him to achieve what he 
    // wants
    $form->add(
      'text',
      'region',
      ts('Region Id')
    );
    
    $form->addRule(
      'region',
      ts('Numeric value only'),
      'numeric'
    );
    
    // When using the sample template, array tells the template fields to render for the search form
    $form->assign(
      'elements',
      array(
        'region',
      )
    );
  }

  /**
   * Template usedfor search form and results
   */
  function templateFile() {
    // TODO: Custom.tpl is from the example file the search is based upon but
    // docs mention Sample.tpl, which doesn't exist...
    return 'CRM/Contact/Form/Search/Custom.tpl';
  }

  /**
   * Construct the full SQL query
   */       
  function all($offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE, $onlyIDs = false) {
    // AS: This appears to be related to/used when creating 'smart groups'. Important to note
    // that when creating smart group, the table alias for 'civicrm_contact' needs to be 'contact_a'.
    // Currently, I've no idea why this is!
    if ( $onlyIDs ) {
      $select  = "DISTINCT contact_a.id as contact_id";
    } else {
      // SELECT columns the same for both parts of UNIONed query
      // Note: 'contact_id' is named as such because the template file expects it be so
      $select = "
contact_a.id AS contact_id,
contact_a.display_name AS FullName,
civicrm_activity.id AS ActivityID,
civicrm_value_correspondence_in_type_65.type__correspondence_in__285 AS Source,
civicrm_value_correspondence_in__keep_in_touch_72.keep_in_touch__information__307 AS Info,
civicrm_value_donor_tax_type_4.regional_121 AS RegionID
";
    }

    $from = $this->from();
    $where_union_top = $this->where($includeContactIDs);
    $where_union_bottom = $this->where_union_botttom($includeContactIDs);

    $sql = "
SELECT $select
FROM   $from
WHERE  $where_union_top
UNION
SELECT $select
FROM   $from
WHERE  $where_union_bottom
";

    // AS: Disabled as was causing problems when creating a 'smart group',
    // probably caused by UNION SQL syntax. Ordering is not important.
    // Statement ORDER BY for query in $sort, with default value
    /*
    if (!empty($sort)) {
      if (is_string($sort)) {
        $sql .= " ORDER BY $sort";
      } 
      else {
        $sql .= " ORDER BY " . trim($sort->orderBy());
      }
    } 
    else {
      $sql .= "ORDER BY ActivityID DESC";
    }
    */

    if ($rowcount > 0 && $offset >= 0) {
      $sql .= " LIMIT $offset, $rowcount ";
    }

    // Debug SQL statement...
    //CRM_Core_Error::debug('sql', $sql);
    //exit();
    
    return $sql;
  }
  
  /**
   * Statement FROM clause, same for both parts of UNION
   */
  function from() {
    return "
civicrm_activity,
civicrm_activity_target,
civicrm_contact AS contact_a,
civicrm_value_correspondence_in_type_65,
civicrm_value_correspondence_in__keep_in_touch_72,
civicrm_value_donor_tax_type_4
";
  }

  /**
   * WHERE clause used in top half of UNIONed queries
   *
   * An array built from any required JOINS plus conditional filters 
   * based on search criteria field values
   */
  function where($includeContactIDs = FALSE) {
    $clauses = array();
    
    // 1st half of UNIONed query
    $clauses[] = 'civicrm_activity.activity_type_id = 65';
    $clauses[] = 'civicrm_activity_target.activity_id = civicrm_activity.id ';
    $clauses[] = 'civicrm_activity_target.target_contact_id = contact_a.id';
    $clauses[] = 'civicrm_activity.id = civicrm_value_correspondence_in_type_65.entity_id';
    $clauses[] = 'civicrm_value_correspondence_in_type_65.type__correspondence_in__285 = "Keep in touch card"';
    $clauses[] = 'civicrm_activity.id = civicrm_value_correspondence_in__keep_in_touch_72.entity_id ';
    $clauses[] = 'civicrm_value_correspondence_in__keep_in_touch_72.keep_in_touch__information__307 NOT LIKE "%Disease%"';
    $clauses[] = 'civicrm_value_correspondence_in__keep_in_touch_72.keep_in_touch__information__307 LIKE "%Volunteering%" ';
    $clauses[] = 'civicrm_value_correspondence_in__keep_in_touch_72.contacted_by_fundraising_373 = 0';
    $clauses[] = 'contact_a.id = civicrm_value_donor_tax_type_4.entity_id';
    
    // TODO: type of form control restricts input to be numeric, so there's some kind of sanitisation
    // but are there further requirements (eg. getInteger() or something equivalent)?
    $region = trim($this->_formValues['region']);
    if ($region) {
      $clauses[] = "civicrm_value_donor_tax_type_4.regional_121 = $region";
    }

    return implode(' AND ', $clauses);
  }

  /**
   * WHERE clause for bottom half of UNIONed queries
   */
  function where_union_botttom($includeContactIDs = FALSE) {
    $clauses = array();

    // 2nd half of UNIONed query
    $clauses[] = 'civicrm_activity.activity_type_id = 65';
    $clauses[] = 'civicrm_activity_target.activity_id = civicrm_activity.id';
    $clauses[] = 'civicrm_activity_target.target_contact_id = contact_a.id';
    $clauses[] = 'civicrm_activity.id = civicrm_value_correspondence_in_type_65.entity_id';
    $clauses[] = 'civicrm_value_correspondence_in_type_65.type__correspondence_in__285 = "Keep in touch card"';
    $clauses[] = 'civicrm_activity.id = civicrm_value_correspondence_in__keep_in_touch_72.entity_id';
    $clauses[] = 'civicrm_value_correspondence_in__keep_in_touch_72.keep_in_touch__information__307 LIKE "%Disease%"';
    $clauses[] = 'civicrm_value_correspondence_in__keep_in_touch_72.patient_enquiry_complete_372 = 1';
    $clauses[] = '(
      civicrm_value_correspondence_in__keep_in_touch_72.keep_in_touch__information__307 LIKE "%Volunteering%"
      OR 
      civicrm_value_correspondence_in__keep_in_touch_72.keep_in_touch__information__307 LIKE "%Fund%"
    )';
    $clauses[] = 'civicrm_value_correspondence_in__keep_in_touch_72.contacted_by_fundraising_373 = 0';
    $clauses[] = 'contact_a.id = civicrm_value_donor_tax_type_4.entity_id';
    
    $region = trim($this->_formValues['region']);
    if ($region) {
      $clauses[] = "civicrm_value_donor_tax_type_4.regional_121 = $region";
    }

    return implode(' AND ', $clauses);
  }

  /**
   * Functions below generally don't need to be modified
   */

  function count() {
    $sql = $this->all();
    $dao = CRM_Core_DAO::executeQuery($sql, CRM_Core_DAO::$_nullArray);

    return $dao->N;
  }
       
  function contactIDs($offset = 0, $rowcount = 0, $sort = NULL) { 
    return $this->all( $offset, $rowcount, $sort, FALSE, TRUE);
  }
       
  function &columns() {
    return $this->_columns;
  }

  function setTitle($title) {
    if ($title) {
      CRM_Utils_System::setTitle($title);
    } 
    else {
      CRM_Utils_System::setTitle(ts('Search'));
    }
  }

  function summary() {
    return NULL;
  }
}

/*
Full query, for clarity:

SELECT contact_a.id AS contact_id,                                                    // Included in all custom queries
contact_a.display_name AS FullName,                                                  // Mr Fred Bloggs
civicrm_activity.id AS ActivityID,                                                         // Id of some activity
civicrm_value_correspondence_in_type_65.type__correspondence_in__285 AS Source,            // Letter/Social Media/Phone etc.
civicrm_value_correspondence_in__keep_in_touch_72.keep_in_touch__information__307 AS Info, // Disease Information/Social Events/Volunteering/Opportunities etc.
civicrm_value_donor_tax_type_4.regional_121 AS RegionID                                    // 2/3/4...
FROM
civicrm_activity,
civicrm_activity_target,
civicrm_contact AS contact_a,
civicrm_value_correspondence_in_type_65,
civicrm_value_correspondence_in__keep_in_touch_72,
civicrm_value_donor_tax_type_4
WHERE
civicrm_activity.activity_type_id = 65 
AND civicrm_activity_target.activity_id = civicrm_activity.id 
AND civicrm_activity_target.target_contact_id = contact_a.id 
AND civicrm_activity.id = civicrm_value_correspondence_in_type_65.entity_id 
AND civicrm_value_correspondence_in_type_65.type__correspondence_in__285 = 'Keep in touch card' 
AND civicrm_activity.id = civicrm_value_correspondence_in__keep_in_touch_72.entity_id 
AND civicrm_value_correspondence_in__keep_in_touch_72.keep_in_touch__information__307 NOT LIKE '%Disease%' 
AND civicrm_value_correspondence_in__keep_in_touch_72.keep_in_touch__information__307 LIKE '%Volunteering%' 
AND civicrm_value_correspondence_in__keep_in_touch_72.contacted_by_fundraising_373 = 0 
AND contact_a.id = civicrm_value_donor_tax_type_4.entity_id 
AND civicrm_value_donor_tax_type_4.regional_121 = 5
UNION
SELECT contact_a.id AS contact_id,
contact_a.display_name AS FullName,
civicrm_activity.id AS ActivityID,
civicrm_value_correspondence_in_type_65.type__correspondence_in__285 AS Source,
civicrm_value_correspondence_in__keep_in_touch_72.keep_in_touch__information__307 AS Info,
civicrm_value_donor_tax_type_4.regional_121 AS RegionID
FROM
civicrm_activity,
civicrm_activity_target,
civicrm_contact AS contact_a,
civicrm_value_correspondence_in_type_65,
civicrm_value_correspondence_in__keep_in_touch_72,
civicrm_value_donor_tax_type_4
WHERE
civicrm_activity.activity_type_id = 65 
AND civicrm_activity_target.activity_id = civicrm_activity.id 
AND civicrm_activity_target.target_contact_id = contact_a.id 
AND civicrm_activity.id = civicrm_value_correspondence_in_type_65.entity_id 
AND civicrm_value_correspondence_in_type_65.type__correspondence_in__285 = 'Keep in touch card' 
AND civicrm_activity.id = civicrm_value_correspondence_in__keep_in_touch_72.entity_id 
AND civicrm_value_correspondence_in__keep_in_touch_72.keep_in_touch__information__307 LIKE '%Disease%' 
AND civicrm_value_correspondence_in__keep_in_touch_72.patient_enquiry_complete_372 = 1 
AND
(
  civicrm_value_correspondence_in__keep_in_touch_72.keep_in_touch__information__307 LIKE '%Volunteering%' 
  OR 
  civicrm_value_correspondence_in__keep_in_touch_72.keep_in_touch__information__307 LIKE '%Fund%'
) 
AND civicrm_value_correspondence_in__keep_in_touch_72.contacted_by_fundraising_373 = 0 
AND contact_a.id = civicrm_value_donor_tax_type_4.entity_id 
AND civicrm_value_donor_tax_type_4.regional_121 = 5;
*/