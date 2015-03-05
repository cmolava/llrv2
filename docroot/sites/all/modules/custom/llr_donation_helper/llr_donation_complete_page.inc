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
  $values = llr_webform_retrieve_values('llr_donate');
  //Clean up cache
  // llr_webform_clear_values('llr_donate');
  $contact_prefix = 'civicrm_1_contact_1_';
  $mail = $values['webform'][$contact_prefix . 'email_email'][0];
  $fname = $values['webform'][$contact_prefix . 'contact_first_name'][0];
  $lname = $values['webform'][$contact_prefix . 'contact_last_name'][0];

  $prepop = array(
    'name' => "$fname $lname",
    'mail' => $mail,
  );
  $prepop = llr_register_sanitize_values($prepop);
 kpr($prepop); 
  kpr($values);
//////// ///llrdebug remove true from here.
  if (true || user_is_anonymous() && ! llr_donation_mail_exists() ) {
    $content['register'] = llr_donation_keep_track($prepop);
  }
  $content['share'] = llr_donation_share(); 
  return $content;
}

 
/**
 * Returns a renderable array for the donation complete social share block.
 **/
function llr_donation_share($params=array()) {
  $content['#prefix'] = '<div class="donate-share-block">';
  $content['#suffix'] = '</div>';
  $content['subject']['#markup'] = '<h3>Share your support on social media</h3>';
  $content['register']['#markup'] = '<p> Did you know that..</p>';
  return $content;
}

/**
 * Returns a renderable array for the donation complete registration block.
 * 
 * @params $values: array of key value pairs with which to populate the
 * registration form.
 *  - mail
 *  - name
 * 
 * @return array
 * @see llr_register_registration_form
 */
function llr_donation_keep_track($values) {

  $content['#prefix'] = '<div class="keep-track-registration-block">';
  $content['#suffix'] = '</div>';
  $content['subject']['#markup'] = '<h3>Keep track of your support</h3>';
  $content['register']['#markup'] = '<p> Just Enter a password below and you will be able to track all the amazing things you do for Leukaemia &amp; Lymphoma Research.</p>';
  $content['form'] = drupal_get_form('llr_register_registration_form', $values);
  return $content;
}
