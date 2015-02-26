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

require_once 'CRM/Contact/Form/Search/Interface.php';

class CRM_Contact_Form_Search_Custom_EventAttendance
   implements CRM_Contact_Form_Search_Interface {
    
    protected $_formValues;
    
    function __construct( &$formValues ) {     
        $this->_formValues = $formValues;
        
        /**
         * Define the columns for search result rows
         */
        $this->_columns = array( ts('Contact Id')   => 'contact_id'  ,
                                 ts('Name'      )   => 'sort_name',
                                 ts('Event Count') => 'event_count');
    }
    
    function buildForm( &$form ) {
        /**
         * You can define a custom title for the search form
         */
        $this->setTitle('Find Sports Events Attendees within range of days');
                
        /**
         * Define the search form fields here
         */
        /* Not working with contact template so commented out
         * Have hard coded for the searches to work
         */
        /*
        $event_type = CRM_Core_OptionGroup::values( 'event_type', false );        
        foreach($event_type as $eventId => $eventName) {
            $form->addElement('checkbox', "event_type_id[$eventId]", 'Event Type', $eventName);
        }
        */
        
        $form->add( 'text',
            'min_days',
            ts( 'From (number of days)' ) );
        $form->addRule( 'min_days', ts( 'Please enter a valid number of days (numbers).' ), 'integer' );

        $form->add( 'text',
                    'max_days',
                    ts( '...to ' ) );
        $form->addRule( 'max_days', ts( 'Please enter a valid number of days (numbers).' ), 'integer' );

        /**
        $form->addDate('start_date', ts('Payments Date From'), false, array( 'formatType' => 'custom' ) );
        $form->addDate('end_date', ts('...through'), false, array( 'formatType' => 'custom' ) );
        **/
        
        /**
         * If you are using the sample template, this array tells the template fields to render
         * for the search form.
         */
        $form->assign( 'elements', array('min_days', 'max_days') );
    }
    
    /**
     * Define the smarty template used to layout the search form and results listings.
     */
    function templateFile( ) {
       return 'CRM/Contact/Form/Search/Custom.tpl';
    }
    
    /**
     * Construct the search query
     */       
    function all( $offset = 0, $rowcount = 0, $sort = null,
                  $includeContactIDs = false ) {
        // SELECT clause must include contact_id as an alias for civicrm_contact.id if you are going to use "tasks" like export etc.
        $select  = " DISTINCT civicrm_contact.id as contact_id,
        civicrm_contact.sort_name as sort_name,
        count(civicrm_participant.id) AS event_count";
        
        /*
        "civicrm_participant.event_id as event_id,
        COUNT(civicrm_participant.id) as participant_count,
        GROUP_CONCAT(DISTINCT(civicrm_event.title)) as event_name,
        civicrm_event.event_type_id as event_type_id,
        civicrm_option_value.label as event_type,
        IF(civicrm_contribution.payment_instrument_id <>0 , 'Yes', 'No') as payment_instrument_id,
        SUM(civicrm_contribution.total_amount) as payment_amount,
        format(sum(if(civicrm_contribution.payment_instrument_id <>0,(civicrm_contribution.total_amount *.034) +.45,0)),2) as fee,
        format(sum(civicrm_contribution.total_amount - (if(civicrm_contribution.payment_instrument_id <>0,(civicrm_contribution.total_amount *.034) +.45,0))),2) as net_payment";
        */
        
        $from  = $this->from();
        
        $where = $this->where();
        
        $having = $this->having( );
        if ( $having ) {
            $having = " HAVING $having ";
        }
        
        $sql = "
        SELECT $select
        FROM   $from
        WHERE  $where
        GROUP BY civicrm_contact.id
        $having
        ";
        // Define ORDER BY for query in $sort, with default value
        if ( ! empty( $sort ) ) {
            if ( is_string( $sort ) ) {
                $sql .= " ORDER BY $sort ";
            } else {
                $sql .= " ORDER BY " . trim( $sort->orderBy() );
            }
        } else {
            $sql .= "ORDER BY civicrm_contact.id desc";
        }
        
        if ( $rowcount > 0 && $offset >= 0 ) {
            $sql .= " LIMIT $offset, $rowcount ";
        }
        
        // Uncomment the next line to see the actual SQL generated:
        //CRM_Core_Error::debug('sql',$sql); exit();  
        return $sql;
    }
    
    function from( ) {
        return "
        civicrm_participant
        left join civicrm_event on
        civicrm_participant.event_id = civicrm_event.id
        left join civicrm_contact 
        on civicrm_contact.id = civicrm_participant.contact_id
        left join civicrm_option_value on
        ( civicrm_option_value.value = civicrm_event.event_type_id AND civicrm_option_value.option_group_id = 14)" ;
        
    }
    
    /*
     * WHERE clause is an array built from any required JOINS plus conditional filters based on search criteria field values
     *
     */
    function where( $includeContactIDs = false ) {
        $clauses = array( );
        
        // Need to work out which statuses we are interested in
        //$clauses[] = "civicrm_participant.status_id in ( 1 )";
        $clauses[] = "civicrm_participant.is_test = 0";
        
        if ( $includeContactIDs ) {
            $contactIDs = array( );
            foreach ( $this->_formValues as $id => $value ) {
                if ( $value &&
                     substr( $id, 0, CRM_Core_Form::CB_PREFIX_LEN ) == CRM_Core_Form::CB_PREFIX ) {
                    $contactIDs[] = substr( $id, CRM_Core_Form::CB_PREFIX_LEN );
                }
            }
            
            if ( ! empty( $contactIDs ) ) {
                $contactIDs = implode( ', ', $contactIDs );
                $clauses[] = "contact.id IN ( $contactIDs )";
            }
        }
        $minDays = CRM_Utils_Array::value( 'min_days', $this->_formValues  );
        if ( $minDays ) {
            $clauses[] = "DATEDIFF(CURDATE(),civicrm_event.start_date) >= $minDays";
        }

        $maxDays = CRM_Utils_Array::value( 'max_days', $this->_formValues );
        if ( $maxDays ) {
            $clauses[] = "DATEDIFF(CURDATE(),civicrm_event.start_date) <= $maxDays";
        }
        
        /**
        if ( ! empty($this->_formValues['event_type_id'] ) ) {
            $event_type_ids = implode(',', array_keys($this->_formValues['event_type_id']));
            $clauses[] = "civicrm_event.event_type_id IN ( $event_type_ids )";
        }
         * 
         */
        /**
         * Hard Coded as the form isn't showing the event type check box
         * No time to find out why, should be fixed in future
         */
        $clauses[] = "civicrm_event.event_type_id IN ( 1,2,3,4,7 )";
        
        return implode( ' AND ', $clauses );
    }
    
    
    /* This function does a query to get totals for some of the search result columns and returns a totals array. */   
    function summary( ) {
        return null;
    }
    
    function having( $includeContactIDs = false ) {
        return null;
    }
    
    /* 
     * Functions below generally don't need to be modified
     */
    function count( ) {
        $sql = $this->all( );
        
        $dao = CRM_Core_DAO::executeQuery( $sql,
                                           CRM_Core_DAO::$_nullArray );
        return $dao->N;
    }
    
    function contactIDs( $offset = 0, $rowcount = 0, $sort = null) { 
        return $this->all( $offset, $rowcount, $sort );
    }
    
    function &columns( ) {
        return $this->_columns;
    }
    
    function setTitle( $title ) {
        if ( $title ) {
            CRM_Utils_System::setTitle( $title );
        } else {
            CRM_Utils_System::setTitle(ts('Search'));
        }
    }
    
}

