<?php
require_once 'CRM/Contact/Form/Search/Interface.php';

class CRM_Contact_Form_Search_Custom_InMemoryTributeFundDonationByDays
  implements CRM_Contact_Form_Search_Interface {

  protected $_formValues;

  function __construct(&$formValues) {
    $this->_formValues = $formValues;

    // Columns for search results
    // @todo  contact_id | MIN(c.receive_date) AS earliest_received | campaign_id
    $this->_columns = array(
      ts('Contact Id') => 'contact_id',
      ts('Earliest received date') => 'earliest_received',
      ts('Campaign Id') => 'campaign_id',
    );
  }

  /**
   * Create search form
   */
  function buildForm(&$form) {
     $this->setTitle('In memory &amp; tribute fund donation by days search');

    // Search form field(s) and validation rules
    $form->add(
      'text',
      'interval',
      ts('No. of days. eg. 90'),
      '',
      TRUE /* required-ness */
    );

    $form->addRule(
      'interval',
      ts('Up to 3 digits'),
      'regex',
      '/^[0-9]{1,3}$/'
    );

    // Add fields to form
    $form->assign(
      'elements',
      array(
        'interval',
      )
    );
  }

  /**
   * Template file path used for search form and results
   */
  function templateFile() {
    return 'CRM/Contact/Form/Search/InMemoryTributeFundDonationByDays.tpl';
  }

  /**
   * Construct full SELECT query
   * 
   * Full example SQL that should be prodiced, for clarity:
   * 
   * SELECT c.contact_id, MIN(c.receive_date) AS earliest_received, c.campaign_id 
   * FROM civicrm_contribution c
   * GROUP BY c.contact_id 
   * HAVING MIN(c.receive_date) > DATE_SUB(CURDATE(), INTERVAL 90 DAY) 
   * AND (
   *   campaign_id = 78 
   *   OR 
   *   campaign_id = 2998 
   *   OR 
   *   campaign_id = 178
   * )
   * 
   * The hard-coded campaign_id values were specified by Owen. See Podio issue:
   * https://leukaemialymphomaresearch.podio.com/web-support/item/16489145
   */
  function all($offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE, $onlyIDs = false) {
    // SELECT columns
    $select = "c.contact_id, MIN(c.receive_date) AS earliest_received, c.campaign_id";

    // FROM tables and aliases
    $from = $this->from();

    // HAVING clause
    $having = $this->having();

    // Pull all the statement parts together
    $sql = 'SELECT ' . $select
      . ' FROM ' . $from
      . ' GROUP BY c.contact_id'
      . ' HAVING ' . $having;

    return $sql;
  }

  /**
   * SQL FROM clause
   */
  function from() {
    return "civicrm_contribution c";
  }

  /**
   * SQL WHERE clause
   */
  function where($includeContactIDs = FALSE) {
    return '';
  }

  /**
   * SQL HAVING clause
   * 
   * Documentation about this is rubbish. It says "you'll need to define a 
   * having clause", but the having clause is not a documented function from 
   * what little documentation I can find. So it looks like you don't *need* to 
   * define this, but it makes sense to go along with the way other parts of the
   * query are being generated.
   * 
   * http://wiki.civicrm.org/confluence/display/CRMDOC40/Creating+A+Custom+Search+Extension
   * http://civiapi.idealworld.org/api/civicrm/CRM--Contact--Form--Search--Interface.php/interface/CRM_Contact_Form_Search_Interface/
   */
  function having() {
    $having .= 'MIN(c.receive_date) > DATE_SUB(CURDATE(), INTERVAL ' 
      . CRM_Utils_Array::value('interval', $this->_formValues) . ' DAY)'
      . ' AND ('
      . '  campaign_id = 78'
      . '  OR'
      . '  campaign_id = 2998'
      . '  OR'
      . '  campaign_id = 178'
      . ')';

    return $having;
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