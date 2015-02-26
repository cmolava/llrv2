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

class CRM_Contact_Form_Search_Custom_CashContributions
   implements CRM_Contact_Form_Search_Interface {

    protected $_formValues;

    function __construct( &$formValues ) {
        $this->_formValues = $formValues;

        /**
         * Define the columns for search result rows
         */
        $this->_columns = array( ts('Contact Id')   => 'contact_id'  ,
                                 ts('Name'      )   => 'sort_name',
                                 ts('Donation Count') => 'donation_count',
                                 ts('Donation Days') => 'donation_days' );
    }

    function buildForm( &$form ) {
        /**
         * You can define a custom title for the search form
         */
        $this->setTitle('Find Contributors using day range for FIRST contribution');

        require_once 'CRM/Contribute/PseudoConstant.php';
        $tag = array('' => ts('- any payment instrument -')) +
            CRM_Contribute_PseudoConstant::paymentInstrument( );
        
        $form->addElement('select', 'payment_instrument', ts('Payment Instrument'), $tag);

        /**
         * Define the search form fields here
         */
        $form->add( 'text',
                    'min_amount',
                    ts( 'Aggregate Total Between $' ) );
        $form->addRule( 'min_amount', ts( 'Please enter a valid amount (numbers and decimal point only).' ), 'money' );

        $form->add( 'text',
                    'max_amount',
                    ts( '...and $' ) );
        $form->addRule( 'max_amount', ts( 'Please enter a valid amount (numbers and decimal point only).' ), 'money' );

        $form->add( 'text',
                    'min_days',
                    ts( 'From (FIRST Contribution number of days)' ) );
        $form->addRule( 'min_days', ts( 'Please enter a valid number of days (numbers).' ), 'integer' );

        $form->add( 'text',
                    'max_days',
                    ts( '...to ' ) );
        $form->addRule( 'max_days', ts( 'Please enter a valid number of days (numbers).' ), 'integer' );

        $form->addDate( 'start_date', ts('Contribution Date From'), false, array( 'formatType' => 'custom') );
        $form->addDate( 'end_date', ts('...through'), false, array( 'formatType' => 'custom') );

        $tag = array('' => ts('- any tag -')) +
        CRM_Core_PseudoConstant::tag( );
        $form->addElement('select', 'tag', ts('Tagged'), $tag);

        /**
         * If you are using the sample template, this array tells the template fields to render
         * for the search form.
         */
        $form->assign( 'elements', array( 'hon_contribs', 'mem_contribs', 'fmnf_contribs', 'tf_contribs', 'min_amount', 'max_amount', 'min_days', 'max_days', 'payment_instrument', 'start_date', 'end_date', 'tag') );
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
                  $includeContactIDs = false, $onlyIDs = false ) {

        // SELECT clause must include contact_id as an alias for civicrm_contact.id
        if ( $onlyIDs ) {
            $select  = "DISTINCT contact_a.id as contact_id";
        } else {
            $select  = "
DISTINCT contact_a.id as contact_id,
contact_a.sort_name as sort_name,
DATEDIFF(CURDATE(),MIN(contrib.receive_date)) AS donation_days,
count(contrib.id) AS donation_count
";
        }
        $from  = $this->from( );

        $FMNFContribs = CRM_Utils_Array::value( 'fmnf_contribs',
                                          $this->_formValues );
        if ( $FMNFContribs ) {
            $from .= "         
        , civicrm_contact contact_fmnf";
        }

        $TFContribs = CRM_Utils_Array::value( 'tf_contribs',
                                          $this->_formValues );
        if ( $TFContribs ) {
            $from .= "         
        , civicrm_contact contact_c";
        }

        $tag = CRM_Utils_Array::value( 'tag',
                                       $this->_formValues );
        if ( $tag ) {
            $from .= "         
        , civicrm_entity_tag ";
        }
        
        $where = $this->where( $includeContactIDs );

        $having = $this->having( );
        if ( $having ) {
            $having = " HAVING $having ";
        }

        $sql = "
SELECT $select
FROM   $from
WHERE  $where
GROUP BY contact_a.id
$having
";
        //for only contact ids ignore order.
        if ( !$onlyIDs ) {
            // Define ORDER BY for query in $sort, with default value
            if ( ! empty( $sort ) ) {
                if ( is_string( $sort ) ) {
                    $sql .= " ORDER BY $sort ";
                } else {
                    $sql .= " ORDER BY " . trim( $sort->orderBy() );
                }
            } else {
                $sql .= "ORDER BY donation_days desc";
            }
        }

        if ( $rowcount > 0 && $offset >= 0 ) {
            $sql .= " LIMIT $offset, $rowcount ";
        }
        return $sql;
    }

    function from( ) {
        return "
civicrm_contribution AS contrib,
civicrm_contact AS contact_a
";
    }

     /*
      * WHERE clause is an array built from any required JOINS plus conditional filters based on search criteria field values
      *
      */
    function where( $includeContactIDs = false ) {
        $clauses = array( );

        $clauses[] = "contrib.contact_id = contact_a.id";
        $clauses[] = "contrib.is_test = 0";
        $clauses[] = "contrib.financial_type_id not in (1471, 1472, 2, 1473, 1478, 1487)";
        
        $honContribs = CRM_Utils_Array::value( 'hon_contribs',
                                          $this->_formValues );

        $paymentInstrument = CRM_Utils_Array::value( 'payment_instrument',
                                          $this->_formValues );
        
        if ( $paymentInstrument ) {        
            $clauses[] = "contrib.payment_instrument_id = $paymentInstrument ";
        }

        $startDate = CRM_Utils_Date::processDate( $this->_formValues['start_date'] );
        if ( $startDate ) {
            $clauses[] = "contrib.receive_date >= $startDate";
        }

        $endDate = CRM_Utils_Date::processDate( $this->_formValues['end_date'] );
        if ( $endDate ) {
            $clauses[] = "contrib.receive_date <= $endDate";
        }

       $tag = CRM_Utils_Array::value( 'tag', $this->_formValues );
        if ( $tag ) {
            $clauses[] = "civicrm_entity_tag.tag_id = $tag";
            $clauses[] = "civicrm_entity_tag.entity_table = 'civicrm_contact' ";
            $clauses[] = "contact_a.id = civicrm_entity_tag.entity_id";
        }
        
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
                $clauses[] = "contact_a.id IN ( $contactIDs )";
            }
        }

        return implode( ' AND ', $clauses );
    }

    function having( $includeContactIDs = false ) {
        $clauses = array( );
        
        $min = CRM_Utils_Array::value( 'min_amount', $this->_formValues );
        if ( $min ) {
            $min = CRM_Utils_Rule::cleanMoney( $min );
            $clauses[] = "sum(contrib.total_amount) >= $min";
        }

        $max = CRM_Utils_Array::value( 'max_amount', $this->_formValues );
        if ( $max ) {
            $max = CRM_Utils_Rule::cleanMoney( $max );
            $clauses[] = "sum(contrib.total_amount) <= $max";
        }
        
        $minDays = CRM_Utils_Array::value( 'min_days', $this->_formValues  );
        if ( $minDays ) {
            $clauses[] = "DATEDIFF(CURDATE(),MAX(contrib.receive_date)) >= $minDays";
        }

        $maxDays = CRM_Utils_Array::value( 'max_days', $this->_formValues );
        if ( $maxDays ) {
            $clauses[] = "DATEDIFF(CURDATE(),MAX(contrib.receive_date)) <= $maxDays";
        }
        

        return implode( ' AND ', $clauses );
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
        return $this->all( $offset, $rowcount, $sort, false, true );
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

   function summary( ) {
       return null;
   }

}


