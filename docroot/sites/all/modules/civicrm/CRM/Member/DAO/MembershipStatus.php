<?php
/*
+--------------------------------------------------------------------+
| CiviCRM version 4.5                                                |
+--------------------------------------------------------------------+
| Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 *
 * Generated from xml/schema/CRM/Member/MembershipStatus.xml
 * DO NOT EDIT.  Generated by CRM_Core_CodeGen
 */
require_once 'CRM/Core/DAO.php';
require_once 'CRM/Utils/Type.php';
class CRM_Member_DAO_MembershipStatus extends CRM_Core_DAO
{
  /**
   * static instance to hold the table name
   *
   * @var string
   * @static
   */
  static $_tableName = 'civicrm_membership_status';
  /**
   * static instance to hold the field values
   *
   * @var array
   * @static
   */
  static $_fields = null;
  /**
   * static instance to hold the keys used in $_fields for each field.
   *
   * @var array
   * @static
   */
  static $_fieldKeys = null;
  /**
   * static instance to hold the FK relationships
   *
   * @var string
   * @static
   */
  static $_links = null;
  /**
   * static instance to hold the values that can
   * be imported
   *
   * @var array
   * @static
   */
  static $_import = null;
  /**
   * static instance to hold the values that can
   * be exported
   *
   * @var array
   * @static
   */
  static $_export = null;
  /**
   * static value to see if we should log any modifications to
   * this table in the civicrm_log table
   *
   * @var boolean
   * @static
   */
  static $_log = true;
  /**
   * Membership Id
   *
   * @var int unsigned
   */
  public $id;
  /**
   * Name for Membership Status
   *
   * @var string
   */
  public $name;
  /**
   * Label for Membership Status
   *
   * @var string
   */
  public $label;
  /**
   * Event when this status starts.
   *
   * @var string
   */
  public $start_event;
  /**
   * Unit used for adjusting from start_event.
   *
   * @var string
   */
  public $start_event_adjust_unit;
  /**
   * Status range begins this many units from start_event.
   *
   * @var int
   */
  public $start_event_adjust_interval;
  /**
   * Event after which this status ends.
   *
   * @var string
   */
  public $end_event;
  /**
   * Unit used for adjusting from the ending event.
   *
   * @var string
   */
  public $end_event_adjust_unit;
  /**
   * Status range ends this many units from end_event.
   *
   * @var int
   */
  public $end_event_adjust_interval;
  /**
   * Does this status aggregate to current members (e.g. New, Renewed, Grace might all be TRUE... while Unrenewed, Lapsed, Inactive would be FALSE).
   *
   * @var boolean
   */
  public $is_current_member;
  /**
   * Is this status for admin/manual assignment only.
   *
   * @var boolean
   */
  public $is_admin;
  /**
   *
   * @var int
   */
  public $weight;
  /**
   * Assign this status to a membership record if no other status match is found.
   *
   * @var boolean
   */
  public $is_default;
  /**
   * Is this membership_status enabled.
   *
   * @var boolean
   */
  public $is_active;
  /**
   * Is this membership_status reserved.
   *
   * @var boolean
   */
  public $is_reserved;
  /**
   * class constructor
   *
   * @access public
   * @return civicrm_membership_status
   */
  function __construct()
  {
    $this->__table = 'civicrm_membership_status';
    parent::__construct();
  }
  /**
   * returns all the column names of this table
   *
   * @access public
   * @return array
   */
  static function &fields()
  {
    if (!(self::$_fields)) {
      self::$_fields = array(
        'id' => array(
          'name' => 'id',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Membership Status ID') ,
          'required' => true,
        ) ,
        'membership_status' => array(
          'name' => 'name',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Membership Status') ,
          'maxlength' => 128,
          'size' => CRM_Utils_Type::HUGE,
          'import' => true,
          'where' => 'civicrm_membership_status.name',
          'headerPattern' => '',
          'dataPattern' => '',
          'export' => true,
        ) ,
        'label' => array(
          'name' => 'label',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Label') ,
          'maxlength' => 128,
          'size' => CRM_Utils_Type::HUGE,
        ) ,
        'start_event' => array(
          'name' => 'start_event',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Start Event') ,
          'maxlength' => 12,
          'size' => CRM_Utils_Type::TWELVE,
          'html' => array(
            'type' => 'Select',
          ) ,
          'pseudoconstant' => array(
            'callback' => 'CRM_Core_SelectValues::eventDate',
          )
        ) ,
        'start_event_adjust_unit' => array(
          'name' => 'start_event_adjust_unit',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('Start Event Adjust Unit') ,
          'maxlength' => 8,
          'size' => CRM_Utils_Type::EIGHT,
          'html' => array(
            'type' => 'Select',
          ) ,
          'pseudoconstant' => array(
            'callback' => 'CRM_Core_SelectValues::unitList',
          )
        ) ,
        'start_event_adjust_interval' => array(
          'name' => 'start_event_adjust_interval',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Start Event Adjust Interval') ,
        ) ,
        'end_event' => array(
          'name' => 'end_event',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('End Event') ,
          'maxlength' => 12,
          'size' => CRM_Utils_Type::TWELVE,
          'html' => array(
            'type' => 'Select',
          ) ,
          'pseudoconstant' => array(
            'callback' => 'CRM_Core_SelectValues::eventDate',
          )
        ) ,
        'end_event_adjust_unit' => array(
          'name' => 'end_event_adjust_unit',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => ts('End Event Adjust Unit') ,
          'maxlength' => 8,
          'size' => CRM_Utils_Type::EIGHT,
          'html' => array(
            'type' => 'Select',
          ) ,
          'pseudoconstant' => array(
            'callback' => 'CRM_Core_SelectValues::unitList',
          )
        ) ,
        'end_event_adjust_interval' => array(
          'name' => 'end_event_adjust_interval',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('End Event Adjust Interval') ,
        ) ,
        'is_current_member' => array(
          'name' => 'is_current_member',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => ts('Current Membership?') ,
        ) ,
        'is_admin' => array(
          'name' => 'is_admin',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => ts('Admin Assigned Only?') ,
        ) ,
        'weight' => array(
          'name' => 'weight',
          'type' => CRM_Utils_Type::T_INT,
          'title' => ts('Weight') ,
        ) ,
        'is_default' => array(
          'name' => 'is_default',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => ts('Default Status?') ,
        ) ,
        'is_active' => array(
          'name' => 'is_active',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => ts('Is Active') ,
          'default' => '1',
        ) ,
        'is_reserved' => array(
          'name' => 'is_reserved',
          'type' => CRM_Utils_Type::T_BOOLEAN,
          'title' => ts('Is Reserved') ,
        ) ,
      );
    }
    return self::$_fields;
  }
  /**
   * Returns an array containing, for each field, the arary key used for that
   * field in self::$_fields.
   *
   * @access public
   * @return array
   */
  static function &fieldKeys()
  {
    if (!(self::$_fieldKeys)) {
      self::$_fieldKeys = array(
        'id' => 'id',
        'name' => 'membership_status',
        'label' => 'label',
        'start_event' => 'start_event',
        'start_event_adjust_unit' => 'start_event_adjust_unit',
        'start_event_adjust_interval' => 'start_event_adjust_interval',
        'end_event' => 'end_event',
        'end_event_adjust_unit' => 'end_event_adjust_unit',
        'end_event_adjust_interval' => 'end_event_adjust_interval',
        'is_current_member' => 'is_current_member',
        'is_admin' => 'is_admin',
        'weight' => 'weight',
        'is_default' => 'is_default',
        'is_active' => 'is_active',
        'is_reserved' => 'is_reserved',
      );
    }
    return self::$_fieldKeys;
  }
  /**
   * returns the names of this table
   *
   * @access public
   * @static
   * @return string
   */
  static function getTableName()
  {
    return CRM_Core_DAO::getLocaleTableName(self::$_tableName);
  }
  /**
   * returns if this table needs to be logged
   *
   * @access public
   * @return boolean
   */
  function getLog()
  {
    return self::$_log;
  }
  /**
   * returns the list of fields that can be imported
   *
   * @access public
   * return array
   * @static
   */
  static function &import($prefix = false)
  {
    if (!(self::$_import)) {
      self::$_import = array();
      $fields = self::fields();
      foreach($fields as $name => $field) {
        if (CRM_Utils_Array::value('import', $field)) {
          if ($prefix) {
            self::$_import['membership_status'] = & $fields[$name];
          } else {
            self::$_import[$name] = & $fields[$name];
          }
        }
      }
    }
    return self::$_import;
  }
  /**
   * returns the list of fields that can be exported
   *
   * @access public
   * return array
   * @static
   */
  static function &export($prefix = false)
  {
    if (!(self::$_export)) {
      self::$_export = array();
      $fields = self::fields();
      foreach($fields as $name => $field) {
        if (CRM_Utils_Array::value('export', $field)) {
          if ($prefix) {
            self::$_export['membership_status'] = & $fields[$name];
          } else {
            self::$_export[$name] = & $fields[$name];
          }
        }
      }
    }
    return self::$_export;
  }
}
