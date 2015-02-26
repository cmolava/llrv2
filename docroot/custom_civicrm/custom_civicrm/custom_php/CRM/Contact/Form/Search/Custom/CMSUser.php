<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */
class CRM_Contact_Form_Search_Custom_CMSUser extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

  protected $_query;

  function __construct(&$formValues) {
    parent::__construct($formValues);

    $this->normalize();
    $this->_columns = array(
      ts('') => 'contact_type',
      ts('Name') => 'sort_name',
      ts('Address') => 'street_address',
      ts('City') => 'city',
 //     ts('State') => 'state_province',
      ts('Postal') => 'postal_code',
      ts('Country') => 'country',
      ts('Email') => 'email',
      ts('Phone') => 'phone',
      ts('CiviCRM ID') => 'contact_id',
      ts('User ID') => 'uf_id',

    );

    $params = CRM_Contact_BAO_Query::convertFormValues($this->_formValues);
    $returnProperties = array();
    $returnProperties['contact_sub_type'] = 1;

    $addressOptions = CRM_Core_BAO_Setting::valueOptions(CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
      'address_options', TRUE, NULL, TRUE
    );

    foreach ($this->_columns as $name => $field) {
      if (in_array($field, array(
        'street_address', 'city', 'state_province', 'postal_code', 'country')) &&
        !CRM_Utils_Array::value($field, $addressOptions)
      ) {
        unset($this->_columns[$name]);
        continue;
      }
      $returnProperties[$field] = 1;
    }

    $fields1 = CRM_Contact_BAO_Contact::exportableFields('All', FALSE, TRUE, TRUE);
    $fields2 = CRM_Core_Component::getQueryFields();
    $uf_match_uf_id = array(
      'name' => 'uf_id',
      'type' => 1,
      'title' =>  'UID',
      'where' => 'civicrm_uf_match.uf_id',
      

      );
    $fields = array_merge($fields1, $fields2);
    $fields['uf_id'] = $uf_match_uf_id;
    $this->_query = new CRM_Contact_BAO_Query($params, $returnProperties, $fields,
      TRUE, FALSE, 1, FALSE, FALSE
    );
  //Remove uf_match_id from the query since civicrm_uf_match has no id column 
    unset($this->_query->_select['uf_match_id']);
    
    //It would be neater to add our join in our from() method but we need to do it 
    //earlier to avoid errors since columns referencing the table have already been added to the  query 
   $UF_join = empty($this->_formValues['cms_users_only']) ? 'LEFT' : 'INNER';
   $this->_query->_fromClause .= $UF_join . " JOIN civicrm_uf_match ON (contact_a.id = civicrm_uf_match.contact_id) ";
  }

  /**
   * normalize the form values to make it look similar to the advanced form values
   * this prevents a ton of work downstream and allows us to use the same code for
   * multiple purposes (queries, save/edit etc)
   *
   * @return void
   * @access private
   */
  function normalize() {
    $contactType = CRM_Utils_Array::value('contact_type', $this->_formValues);
    if ($contactType && !is_array($contactType)) {
      unset($this->_formValues['contact_type']);
      $this->_formValues['contact_type'][$contactType] = 1;
    }

    $group = CRM_Utils_Array::value('group', $this->_formValues);
    if ($group && !is_array($group)) {
      unset($this->_formValues['group']);
      $this->_formValues['group'][$group] = 1;
    }

    $tag = CRM_Utils_Array::value('tag', $this->_formValues);
    if ($tag && !is_array($tag)) {
      unset($this->_formValues['tag']);
      $this->_formValues['tag'][$tag] = 1;
    }

    return;
  }

  function buildForm(&$form) {
   // print_r($this->_query->_select);
   // print_r($this->_query->_fromClause);
    $contactTypes = array('' => ts('- any contact type -')) + CRM_Contact_BAO_ContactType::getSelectElements();
    $form->add('select', 'contact_type', ts('Find...'), $contactTypes);

    // add select for categories
    $tag = array('' => ts('- any tag -')) + CRM_Core_PseudoConstant::tag();
    $form->addElement('select', 'tag', ts('Tagged'), $tag);

    // text for sort_name
    $form->add('text', 'sort_name', ts('Name or email'));
    
    $form->add('text', 'postal_code', ts('Post Code'));
    $form->add('checkbox', 'cms_users_only', ts('Only include site users?'));

    $form->assign('elements', array('sort_name', 'contact_type', 'group', 'tag', 'postal_code', 'cms_users_only'));
  }

  function count() {
    return $this->_query->searchQuery(0, 0, NULL, TRUE);
  }

  function all(
    $offset = 0,
    $rowCount = 0,
    $sort = NULL,
    $includeContactIDs = TRUE,
    $justIDs = FALSE
  ) {
    return $this->_query->searchQuery(
      $offset,
      $rowCount,
      $sort,
      FALSE,
      $includeContactIDs,
      FALSE,
      $justIDs,
      TRUE
    );
  }
  
 function alterRow(&$row) {
   print_r($row);    
   if ( ! empty($row['uf_id']) ) {
      $row['uf_id'] = '<a href="' . CRM_Utils_System::url('user/'. $row['uf_id']) . '">' . $row['uf_id'] . '</a>';
   }
 }
 
 function from() {
    return $this->_query->_fromClause;
  }

 function where($includeContactIDs = FALSE) {
    if ($whereClause = $this->_query->whereClause()) {
      return $whereClause;
    }
    return ' (1) ';
  }

  function templateFile() {
    return 'CRM/Contact/Form/Search/Custom/Sample.tpl';
  }
}

