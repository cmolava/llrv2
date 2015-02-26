<?php

/**
 * RunAddressFormat.Create API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
 */

/**
 * A PHP cron script to format all the addresses in the database. Currently
 * it only does geocoding if the geocode values are not set. At a later
 * stage we will also handle USPS address cleanup and other formatting
 * issues
 *
 */

define( 'THROTTLE_REQUESTS', 0 );

//function _civicrm_api3_run_address_format_create_spec(&$spec) {
//  $spec['magicword']['api.required'] = 1;
//}

/**
 * RunAddressFormat.Create API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_run_address_format_create($params) {

    try {
        session_start( );                               

        require_once '../civicrm.config.php'; 
        require_once 'CRM/Core/Config.php'; 

        $config = CRM_Core_Config::singleton(); 

        require_once 'Console/Getopt.php';
        $shortOptions = "n:p:s:e:k:g:parse";
        $longOptions  = array( 'name=', 'pass=', 'key=', 'start=', 'end=', 'geocoding=', 'parse=' );

        $getopt  = new Console_Getopt( );
        $args = $getopt->readPHPArgv( );

        array_shift( $args );
        list( $valid, $dontCare ) = $getopt->getopt2( $args, $shortOptions, $longOptions );

        $vars = array(
                      'start'     => 's',
                      'end'       => 'e',
                      'name'      => 'n',
                      'pass'      => 'p',
                      'key'       => 'k',
                      'geocoding' => 'g',
                      'parse'     => 'ap' );

        foreach ( $vars as $var => $short ) {
            $$var = null;
            foreach ( $valid as $v ) {
                if ( $v[0] == $short || $v[0] == "--$var" ) {
                    $$var = $v[1];
                    break;
                }
            }
            if ( ! $$var ) {
                $$var = CRM_Utils_Array::value( $var, $_REQUEST );
            }
            $_REQUEST[$var] = $$var;
        }

        // this does not return on failure
        // require_once 'CRM/Utils/System.php';
        CRM_Utils_System::authenticateScript( true, $name, $pass );

        //log the execution of script
        CRM_Core_Error::debug_log_message( 'UpdateAddress.php' );

        // load bootstrap to call hooks
        require_once 'CRM/Utils/System.php';
        CRM_Utils_System::loadBootStrap(  );

        // do check for geocoding.
        //$processGeocode = false;
        $processGeocode = true;
        $parseStreetAddress = false;

        // we have an exclusive lock - run the mail queue
        processContacts( $config, $processGeocode, $parseStreetAddress, $start, $end );

    }
    catch (Exception $e) {
        throw new API_Exception($e->getMessage(), $e->getCode());    
    }

    return civicrm_api3_create_success($values = 1, $params = array(), $entity = NULL, $action = NULL); 

}

function processContacts( &$config, $processGeocode, $parseStreetAddress, $start = null, $end = null, $addressLimit = 3000) 
{
    // build where clause.
    $clause = array( '( c.id = a.contact_id )' );
    if ( $start ) {
        $clause[] = "( c.id >= $start )";
    }
    if ( $end ) {
        $clause[] = "( c.id <= $end )";
    }
    if ( $processGeocode ) {
        //$clause[] = '( a.geo_code_1 is null OR a.geo_code_1 = 0 )';
        //$clause[] = '( a.geo_code_2 is null OR a.geo_code_2 = 0 )';
        //$clause[] = '( a.country_id is not null )';
        //$clause[] = '( (d.id IS NULL) or (d.postal_code != a.postal_code) )';
        $clause[] = '( a.postal_code IS NOT NULL)';
    }
    $whereClause = implode( ' AND ', $clause );
    
    $query = "
SELECT     c.id,
           a.id as address_id,
           a.street_address,
           a.city,
           a.postal_code
FROM       civicrm_contact  c
INNER JOIN civicrm_address a ON a.contact_id = c.id
WHERE      {$whereClause}
AND NOT EXISTS (SELECT 1 FROM civicrm_address_geocoding_result d WHERE a.id = d.address_id AND UPPER(a.postal_code) = UPPER(d.postal_code))
  ORDER BY a.id
  LIMIT 0, {$addressLimit}
";

//s.name as state,
//o.name as country
//LEFT  JOIN civicrm_country        o ON a.country_id = o.id
//LEFT  JOIN civicrm_state_province s ON a.state_province_id = s.id

    $totalGeocoded = $totalAddresses = $totalAddressParsed = 0;
//print($query);
    $dao =& CRM_Core_DAO::executeQuery( $query, CRM_Core_DAO::$_nullArray );
    
    //if ( $processGeocode ) {
    //    require_once( str_replace('_', DIRECTORY_SEPARATOR, $config->geocodeMethod ) . '.php' );
    //}
    
    require_once 'CRM/Core/DAO/Address.php';
    require_once 'CRM/Core/BAO/Address.php';
    
    $unparseableContactAddress = array( );
    while ( $dao->fetch( ) ) {
        $totalAddresses++;
        /*$params = array( 'street_address'    => $dao->street_address,
                         'postal_code'       => $dao->postal_code,
                         'city'              => $dao->city,
                         'state_province'    => $dao->state,
                         'country'           => $dao->country );*/
        $params = array( 'postal_code'       => $dao->postal_code );                         
        $addressParams = array( );
        
        // process geocode.
        if ( $processGeocode ) {
            // loop through the address removing more information
            // so we can get some geocode for a partial address
            // i.e. city -> state -> country
            
            $maxTries = 5;
            do {
                if ( defined( 'THROTTLE_REQUESTS' ) &&
                     THROTTLE_REQUESTS ) {
                    usleep( 50000 );
                }
                
                //eval( $config->geocodeMethod . '::format( $params, true );' );
                parseGeoCodeXml( &$params );
                //print_r ($params);exit;
                array_shift( $params );
                $maxTries--;
            } while ( ( ! isset( $params['geo_code_1'] ) ) &&
                      ( $maxTries > 1 ) );
            
            if ( isset( $params['geo_code_1'] ) ) {
                $totalGeocoded++;
                $addressParams['geo_code_1'] = $params['geo_code_1'];
                $addressParams['geo_code_2'] = $params['geo_code_2'];
            }
        }
        
        // finally update address object.
        if ( !empty( $addressParams ) ) {
            $address = new CRM_Core_DAO_Address( );
            $address->id = $dao->address_id;
            $address->copyValues( $addressParams );
            $address->save( );
            $address->free( );
        }
        
        // Log the geocoding
        if ( $processGeocode ) {
            
            $logging_sql  = " INSERT INTO civicrm_address_geocoding_result SET ";
            $logging_sql .= " address_id   = %0, ";
            $logging_sql .= " geocoding_date = NOW(), ";
            $logging_sql .= " geocode_1    = %1, ";
            $logging_sql .= " geocode_2    = %2, ";
            $logging_sql .= " postal_code    = %3 ";

            $geo_code_1 = 'NOTFOUND';
            $geo_code_2 = 'NOTFOUND';
            if ( isset( $addressParams['geo_code_1'] ) ) {
                $geo_code_1 = $addressParams['geo_code_1'];
                $geo_code_2 = $addressParams['geo_code_2'];            
            }
             
            $logging_params = array(array($dao->address_id,   'Int'),
                                    array($geo_code_1, 'String'),
                                    array($geo_code_2, 'String'),
                                    array($dao->postal_code, 'String'),
                                   );

            $ret = CRM_Core_DAO::executeQuery($logging_sql, $logging_params);

//            echo ts( "Addresses Geocoded : $logging_sql" );
//            print_r($logging_params);
//            print_r($ret);          
        }        
//die;
    }
    
    echo ts( "Addresses Evaluated: $totalAddresses\n" );
    if ( $processGeocode ) {
        echo ts( "Addresses Geocoded : $totalGeocoded\n" );        
    }
    if ( $parseStreetAddress ) {
        echo ts( "Street Address Parsed : $totalAddressParsed\n" );
        if ( $unparseableContactAddress ) {
            echo ts( "<br />\nFollowing is the list of contacts whose address is not parsed :<br />\n");
            foreach ( $unparseableContactAddress as $contactLink ) {
                echo ts("%1<br />\n", array( 1 => $contactLink ) );
            }
        }
    }
    
    return;
}
/**
*  function to get longitude and Latituse after passing poast code
*
*/
function parseGeoCodeXml( &$params ) {
  
  $address = array();
  $URL = "http://geocoding.vedaconsulting.co.uk/index.php/api/hello/postcode.xml/";
  $postcode = $params['postal_code'];
  if(empty($postcode)) {
    return $address;
  }
  $URL .= str_replace(' ' , '' ,  $postcode );
  $xml = simplexml_load_file($URL);
  
  if (is_object($xml)) {
      foreach($xml->children() as $child)  {
        //echo $child->getName(). ": " . $child . "<br />";
        $params['geo_code_2'] = (string) $child->latitude;
        $params['geo_code_1'] = (string) $child->longitude;   
      }
  }   
}


