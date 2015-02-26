<?php
require_once 'CRM/Contact/Form/Search/Interface.php';

class CRM_Contact_Form_Search_Custom_ChequeSearch
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
      ts('Batch Id') => 'batch_id',
      ts('Name') => 'name',
      ts('Amount') => 'amount',
      ts('Type') => 'payment_method',
      ts('Expected posting date') => 'expected_posting_date',
      ts('Banking/received date') => 'banking_date',
    );
  }

  /**
   * Create search form
   */
  function buildForm(&$form) {
     $this->setTitle('Cheque search');

    // Search form fields and validation rules
    // TODO: Work out how to add some plain HTML so you can add instructions
    // unless the idea is that you add this to a custom template instead of 
    // as a markup type control to the form?)
    
    // Earliest date to search from
    $form->add(
      'text',
      'cheque_arrival_date_start',
      ts('Earliest date cheque arrived (YYYY-MM-DD)'),
      '',
      TRUE /* required-ness */
    );
    
    $form->addRule(
      'cheque_arrival_date_start',
      ts('Required date format is: YYYY-MM-DD'),
      'date'
    );
    
    // Latest date to search until
    $form->add(
      'text',
      'cheque_arrival_date_end',
      ts('Latest date cheque arrived (YYYY-MM-DD)'),
      '',
      TRUE /* required-ness */
    );
    
    $form->addRule(
      'cheque_arrival_date_end',
      ts('Required date format is: YYYY-MM-DD'),
      'date'
    );
    
    // Name on cheque (optional)
    $form->add(
      'text',
      'name_on_cheque',
      ts('Name on cheque (eg. smith)')
    );

    $form->addRule(
      'name_on_cheque',
      ts('Alphanumeric characters, spaces, hyphens and ampersands only'),
      'regex',
      '/^[0-9a-zA-Z\s\-&]+$/'
    );

    // Amount cheque was for (optional)
    $form->add(
      'text',
      'amount',
      ts('Amount (eg. 10.50)')
    );

    $form->addRule(
      'amount',
      ts('Enter a valid amount (numbers and decimal point only eg. 10.50).'),
      'money'
    );
    
    // Add fields to form
    $form->assign(
      'elements',
      array(
        'cheque_arrival_date_start',
        'cheque_arrival_date_end',
        'name_on_cheque',
        'amount',
      )
    );
  }

  /**
   * Template used for search form and results
   */
  function templateFile() {
    return 'CRM/Contact/Form/Search/ChequeSearch.tpl';
  }

  /**
   * Construct the full SQL query
   */       
  function all($offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE, $onlyIDs = false) {
    $select = "
cbad.batch_id,
cbad.name, 
cbad.amount, 
cbd.expected_posting_date, 
cbd.banking_date,
civi_ov.name AS payment_method
";

    $from = $this->from();
    $where = $this->where($includeContactIDs);    

    $sql = "
SELECT $select
FROM   $from
WHERE  $where
";

    // Statement ORDER BY for query in $sort, with default value
    if (!empty($sort)) {
      if (is_string($sort)) {
        $sql .= " ORDER BY $sort";
      } 
      else {
        $sql .= " ORDER BY " . trim($sort->orderBy());
      }
    } 
    else {
      $sql .= "ORDER BY cbd.banking_date DESC";
    }

    if ($rowcount > 0 && $offset >= 0) {
      $sql .= " LIMIT $offset, $rowcount ";
    }

    // Debug SQL statement...
    // CRM_Core_Error::debug('sql', $sql);
    // exit();
    
    return $sql;
  }
  
  /**
   * Statement FROM clause, same for both parts of UNION
   */
  function from() {
    return "
mtl_civicrm_batch_allocation_details cbad,
mtl_civicrm_batch_details cbd,
civicrm_option_group civi_og,
civicrm_option_value civi_ov
";
  }

  /**
   * WHERE clause
   *
   */
  function where($includeContactIDs = FALSE) {
    $clauses = array();

    $clauses[] = "cbad.batch_id = cbd.entity_id";
    $clauses[] = "cbad.payment_instrument_id = civi_ov.value";
    $clauses[] = "civi_og.id = civi_ov.option_group_id";
    
    // Dates are required fileds so will have values to use in clause[]
    // $cheque_arrival_date_start = CRM_Utils_Date::processDate($this->_formValues['cheque_arrival_date_start']);
    $cheque_arrival_date_start = trim($this->_formValues['cheque_arrival_date_start']);
    $cheque_arrival_date_end = trim($this->_formValues['cheque_arrival_date_end']);
    
    $clauses[] = "cbd.banking_date BETWEEN CAST('$cheque_arrival_date_start' AS DATE) AND CAST('$cheque_arrival_date_end' AS DATE)";
  
    $name_on_cheque = trim($this->_formValues['name_on_cheque']);
    if ($name_on_cheque) {
      $clauses[] = "cbad.name LIKE '%$name_on_cheque%'";
    }

    $amount = trim($this->_formValues['amount']);
    if ($amount) {
      $clauses[] = "cbad.amount = $amount";
    }

    $clauses[] = "civi_og.name = 'payment_instrument'";
    // 2 = 'Cheque' 4 = 'Cash' etc in civicrm_option_value. Value could
    // come from form control, see queries at EOF.
    $clauses[] = "civi_ov.value = 2";
    
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
Full queries (not exhaustive of field combinations), for clarity:

// Select cheques where name, amount and dates are provided
SELECT cbad.batch_id, cbad.name, cbad.amount, cbd.expected_posting_date, cbd.banking_date, civi_ov.label
FROM mtl_civicrm_batch_allocation_details cbad 
JOIN mtl_civicrm_batch_details cbd ON cbad.batch_id = cbd.entity_id
JOIN civicrm_option_value civi_ov ON cbad.payment_instrument_id = civi_ov.value
JOIN civicrm_option_group civi_og ON civi_og.id = civi_ov.option_group_id
WHERE cbad.name LIKE '%Mr S J Tutton%'
AND cbad.amount = '65.00'
AND cbd.banking_date BETWEEN CAST('2011-12-07' AS DATE) AND CAST('2011-12-07' AS DATE)
AND civi_og.name = 'payment_instrument'
AND civi_ov.value = 2;

// Select cheques where only dates are provided
SELECT cbad.batch_id, cbad.name, cbad.amount, cbd.expected_posting_date, cbd.banking_date, civi_ov.label
FROM mtl_civicrm_batch_allocation_details cbad 
JOIN mtl_civicrm_batch_details cbd ON cbad.batch_id = cbd.entity_id
JOIN civicrm_option_value civi_ov ON cbad.payment_instrument_id = civi_ov.value
JOIN civicrm_option_group civi_og ON civi_og.id = civi_ov.option_group_id
WHERE cbd.banking_date BETWEEN CAST('2011-12-01' AS DATE) AND CAST('2011-12-08' AS DATE)
AND civi_og.name = 'payment_instrument'
AND civi_ov.value = 2;

// Get all payment instruments (for form control)
SELECT civi_ov.name, civi_ov.value
FROM civicrm_option_value civi_ov JOIN civicrm_option_group civi_og ON civi_og.id = civi_ov.option_group_id
WHERE civi_og.name = 'payment_instrument'
ORDER BY civi_ov.label;
*/