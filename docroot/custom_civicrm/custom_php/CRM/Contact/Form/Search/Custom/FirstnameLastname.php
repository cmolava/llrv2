<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2011                                |
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
 * @copyright CiviCRM LLC (c) 2004-2011
 * $Id$
 *
 */

require_once 'CRM/Contact/Form/Search/Custom/Base.php';
require_once 'CRM/Contact/BAO/SavedSearch.php';

class CRM_Contact_Form_Search_Custom_firstnameLastname
   extends    CRM_Contact_Form_Search_Custom_Base
   implements CRM_Contact_Form_Search_Interface {

    protected $_formValues;

    protected $_tableName = null;

    protected $_where = ' (1) ';

    function __construct( &$formValues ) {
        $this->_formValues = $formValues;
        $this->_columns = array( ts('Contact Id')   => 'contact_id'  ,
                                 ts('Contact Type') => 'contact_type',
                                 ts('Name')         => 'sort_name',
                                 ts('First name')   => 'first_name',
                                 ts('Last Name')    => 'last_name');
        
        //define variables
        $this->_allSearch = false; 
        $this->_groups    = false;
        $this->_tags      = false;
        $this->_andOr      = $this->_formValues['andOr'];
                
    }

    function __destruct( ) {
        // mysql drops the tables when connectiomn is terminated
        // cannot drop tables here, since the search might be used
        // in other parts after the object is destroyed
    }
    
    function buildForm( &$form ) {

        // text for sort_name
        $form->add('text', 'first_name', ts('First Name'));

        // text for sort_name
        $form->add('text', 'last_name', ts('Last Name'));
                
        /**
         * if you are using the standard template, this array tells the template what elements
         * are part of the search criteria
         */
        $form->assign( 'elements', array( 'first_name', 'last_name' ) );
       
    }
    
    function all( $offset = 0, $rowcount = 0, $sort = null,
                  $includeContactIDs = false, $justIDs = false ) {
        if ( $justIDs ) {
            $selectClause = "DISTINCT(cico.id)  as contact_id";
        } else {
            $selectClause = "DISTINCT(cico.id)  as contact_id,
                         cico.contact_type as contact_type,
                         cico.sort_name    as sort_name,            
                         cico.first_name   as first_name,         
                         cico.last_name    as last_name";            
        }
        
        $from  = $this->from( );
        
        $where = $this->where( $includeContactIDs );
        
        $sql = " SELECT $selectClause $from WHERE  $where ";
        
        // Define ORDER BY for query in $sort, with default value
        if ( ! $justIDs ) {
            if ( ! empty( $sort ) ) {
                if ( is_string( $sort ) ) {
                    $sql .= " ORDER BY $sort ";
                } else {
                    $sql .= " ORDER BY " . trim( $sort->orderBy() );
                }
            } else {
                $sql .= " ORDER BY contact_id ASC";
            }
        }
        
        if ( $offset >= 0 && $rowcount > 0 ) {
            $sql .= " LIMIT $offset, $rowcount ";
        }
        
        return $sql;
        
    }
    
    function from( ) {
        

        $from = " FROM civicrm_contact cico";

        return $from;
    }

    function where( $includeContactIDs = false ) {
         
        $clauses = array( );

        // These are required filters for our query.
        $clauses[] = "cico.contact_type = 'Individual'";

        // These are conditional filters based on user input
        $firstName   = CRM_Utils_Array::value( 'first_name',
                                         $this->_formValues );
        if ( $firstName != null ) {
            if ( strpos( $name, '%' ) === false ) {
                $name = "%{$firstName}%";
            }
            $clauses[] = "cico.first_name LIKE '$firstName'";
        }

        // These are conditional filters based on user input
        $lastName   = CRM_Utils_Array::value( 'last_name',
                                         $this->_formValues );
        if ( $lastName != null ) {
            if ( strpos( $name, '%' ) === false ) {
                $name = "%{$lastName}%";
            }
            $clauses[] = "cico.last_name LIKE '$lastName'";
        }

        return implode( ' AND ', $clauses );

    }

    /* 
     * Functions below generally don't need to be modified
     */
    function count( ) {
        $sql = $this->all( );
           
        $dao = CRM_Core_DAO::executeQuery( $sql );
        return $dao->N;
    }
       
    function contactIDs( $offset = 0, $rowcount = 0, $sort = null) { 
        return $this->all( $offset, $rowcount, $sort, false, true );
    }

    function &columns( ) {
        return $this->_columns;
    }

    function summary( ) {
        return null;
    }

    function templateFile( ) {
        return 'CRM/Contact/Form/Search/Custom.tpl';
    }

}