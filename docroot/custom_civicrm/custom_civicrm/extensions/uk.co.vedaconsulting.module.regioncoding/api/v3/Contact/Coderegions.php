<?php
// $Id$

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
 * APIv3 functions for registering/processing mailing events.
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Mailing
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * Files required for this package
 */

/**
 * Handle a create event.
 *
 * @param array $params
 * @return array API Success Array
 */
function civicrm_api3_contact_coderegions($params, $ids = array()) {
  
  try {
    
    $start = CRM_Utils_Array::value('start', $params, FALSE);
    $end = CRM_Utils_Array::value('start', $params, FALSE);
    
    // we have an exclusive lock - run the mail queue
    $processCountArray = processContacts( TRUE, $start, $end );
  }
  catch (Exception $e) {
      throw new API_Exception($e->getMessage(), $e->getCode());    
  }

  return civicrm_api3_create_success($processCountArray);  
}

function processContacts( $processRegion, $start = null, $end = null, $addressLimit = 1000) 
{
    // build where clause.
    $clause = array( '( c.id = a.contact_id )' );
    if ( $start ) {
        $clause[] = "( c.id >= $start )";
    }
    if ( $end ) {
        $clause[] = "( c.id <= $end )";
    }
    if ( $processRegion ) {
        $clause[] = '( a.postal_code IS NOT NULL)';
        $clause[] = '( a.is_primary = 1)';
    }
    $whereClause = implode( ' AND ', $clause );
    
    $query = "
SELECT     c.id,
           a.id as address_id,
           a.street_address,
           a.city,
           a.postal_code,
           a.contact_id as contact_id
FROM       civicrm_contact  c
INNER JOIN civicrm_address a ON a.contact_id = c.id
WHERE      {$whereClause}
AND NOT EXISTS (SELECT 1 FROM civicrm_address_region_result d WHERE a.id = d.address_id AND UPPER(a.postal_code) = UPPER(d.postal_code))
  ORDER BY a.id DESC
  LIMIT 0, {$addressLimit}
";

    $totalRegionalised = $totalAddresses = $totalAddressParsed = 0;

    $dao =& CRM_Core_DAO::executeQuery( $query, CRM_Core_DAO::$_nullArray );

    while ( $dao->fetch( ) ) {
        $totalAddresses++;

        // process geocode.
        if ( $processRegion ) {
            $postcodeRegion = setContactRegion( $dao->postal_code, $dao->contact_id );
            if ( !empty( $postcodeRegion ) ) {
                $totalRegionalised++;
            }            
        }
        
        // Log the geocoding
        if ( $processRegion ) {
            
            $logging_sql  = " INSERT INTO civicrm_address_region_result SET ";
            $logging_sql .= " address_id   = %0, ";
            $logging_sql .= " region_date = NOW(), ";
            $logging_sql .= " region    = %1, ";
            $logging_sql .= " postal_code    = %2 ";

            $region = 'NOTFOUND';
            if ( !empty( $postcodeRegion ) ) {
                $region = $postcodeRegion;
            }
             
            $logging_params = array(array($dao->address_id,   'Int'),
                                    array($region, 'String'),
                                    array($dao->postal_code, 'String'),
                                   );

            $ret = CRM_Core_DAO::executeQuery($logging_sql, $logging_params);
        }        
    }
    
    $returnArray = array();
    $returnArray['totalAddresses'] = $totalAddresses;
    $returnArray['totalRegionalised'] = $totalRegionalised;
    return $returnArray;
}

function setContactRegion($postcode, $contact_id) {
    /* First Get the region which matches the postcode
     * Then determine if the contact has an existing record in the regional table or not
     * Then we can update or insert into the table depending on whats happening
     */
    $region = getPostcodeRegion($postcode);
    $customRowID = '';

    $region_sql = "SELECT id, regional_121 FROM civicrm_value_donor_tax_type_4 WHERE entity_id = $contact_id";
    $region_dao = CRM_Core_DAO::executeQuery( $region_sql );

    while($region_dao->fetch()) {
      $customRowID = $region_dao->id;
    }

    if (empty($customRowID)) {
        $region_sql = "INSERT INTO civicrm_value_donor_tax_type_4 (entity_id, regional_121) VALUES ($contact_id, '$region')";
        $region_dao = CRM_Core_DAO::executeQuery( $region_sql );
    } else {
        $region_sql = "UPDATE civicrm_value_donor_tax_type_4 SET regional_121 = '$region' WHERE id = $customRowID";
        $region_dao = CRM_Core_DAO::executeQuery( $region_sql );
    }
    return $region;
} 

function getPostcodeRegion($postcode) {

    $x == 0;
    /* Setup default region, so if cant find using postcode then will use this value */
    $selectedRegion = '7';        

    for($i = 0; $i < strlen($postcode); $i++) {
        $postcodePortion = substr($postcode, 0, strlen($postcode)-$x);

        // See if we can find the region for the postcode
        // if we do then exit the loop
        // otherwise continue
        $region_sql = "SELECT region FROM veda_postcode_region_mapping WHERE postcode_part = %0";
        $region_params = array(array($postcodePortion, 'String'));            
        $region_dao = CRM_Core_DAO::executeQuery( $region_sql, $region_params);

        //$tempArray[''] = '-select-';
        while($region_dao->fetch()) {
          $selectedRegion = $region_dao->region;

          /* Break out of both loops - we've found the region for the postcode */
          break 2;
        }

        $x++;
    }

    return $selectedRegion;                      
}


