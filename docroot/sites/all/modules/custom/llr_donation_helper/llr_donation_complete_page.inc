<?php
/**
 * @file llr_donation_complete_page
 *
 */

/**
 * Page callback for donation complete page
 */
function llr_donation_donate_complete_page() {
  //Get donation values
  $values = llr_webform_retrieve_values('donate_complete');
  kpr($values);
  //Clean up cache
  llr_webform_clear_values('donate_start_form');
  
  return 'thanks so much for your donation';
}

