<?php

Class CRM_Trackmailing_Utils {
  function get_loggedIn_User() {
    $session    = &CRM_Core_Session::singleton( );
    $contactID  = $session->get('userID'        );
    return $contactID;
  }
  
}
?>
