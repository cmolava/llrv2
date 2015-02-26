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

require_once 'CRM/Report/Form.php';
require_once 'CRM/Event/PseudoConstant.php';
require_once 'CRM/Core/OptionGroup.php';


 

class CRM_Report_Form_Event_needmailingone extends CRM_Report_Form {
 
    function preProcess( ) {
        parent::preProcess( );
        parent::preProcess( );
    }
 
    function __construct( ) {
        $this->_columns = array( );
    parent::__construct( );
    }
 
    function postProcess( ) {
    $this->beginPostProcess( );
 
    // the query parts
    $mySelect =     "SELECT Count(civicrm_event.title) AS 'count', civicrm_event.title ";
    $myFrom =   "FROM civicrm_participant INNER JOIN civicrm_event ON civicrm_participant.event_id = civicrm_event.id INNER JOIN civicrm_value_particpant_administrative_info_53 ON civicrm_participant.id = civicrm_value_particpant_administrative_info_53.entity_id";
    $myWhere =      " WHERE civicrm_value_particpant_administrative_info_53.first_mailing_sent_260 = '0' AND civicrm_event.is_active = '1' AND civicrm_event.event_type_id = '1' AND civicrm_participant.status_id = '1'";
	$myOrderBy =    "GROUP BY civicrm_participant.event_id ORDER BY count DESC";
 
    //concatenate the query parts to get the full query
    $myQuery = $mySelect . $myFrom 	. $myWhere . $myOrderBy;
 
    // set the columns to be displayed
    $this->_columnHeaders =
             array( 'count' => array( 'title' => 'number' ),
                       'title'  => array( 'title' => 'Event' ),
                       );

 
    // fetch the query results
    $this->buildRows ( $myQuery, $rows );
    $this->doTemplateAssignment( $rows );
    $this->endPostProcess( $rows );
	    }
}

