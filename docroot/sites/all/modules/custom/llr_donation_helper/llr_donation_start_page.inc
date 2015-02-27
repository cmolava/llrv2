<?php
/**
 * @file llr_donation_helper_page.inc
 *
 * Donation start  page functionality.
 *
 */

/**
 * Page callback for donation start
 */
function llr_donation_donate_start_page() {
  drupal_set_title('Thank you! Your donation will save lives');
  return drupal_get_form('llr_donation_donate_start_form');
}

/**
 * Form builder callback for donation start page
 */
function llr_donation_donate_start_form($form_state) {
  $path = drupal_get_path('module', 'llr_donation_helper');
  $settings = variable_get('llr_donation', llr_donation_default_settings());
  
  foreach( $settings['donation_amounts'] as $amount ) {
    if ( $amount['monthly'] ) {
      $monthly_donate_options[(string)$amount['value']] = '£' . $amount['value'];
    }
    if ( $amount['single'] ) {
      $single_donate_options[(string)$amount['value']] = '£' . $amount['value'];
    }
  }
  $form = array(
    '#submit' => array(
      'llr_donation_donate_start_form_submit',
    ),
    '#validate' => array(
      'llr_donation_donate_start_form_validate',
    ),
    '#attached' => array(
      'js' => array($path . '/js/llr_donation.js'),
      'css' => array($path . '/css/llr_donation.css'),
    ),
    '#attributes' => array('class' => array('donate-start')),
    'donate_options' => array(
      '#title' => t('Donate options'),
      '#type' => 'radios',
      '#options' => array(
        'monthly' => t('Monthly donation'),
        'single' => t('One-off donation'),
        'pay_in' => t('Pay in'),
      ),
      '#required' => TRUE,
      '#default_value' => 'monthly',
    ),
    'donate_amount_options_monthly' => array(
      '#type' => 'radios',
      '#title' => t('Monthly amount'),
      '#options' => $monthly_donate_options,
      '#required' => FALSE,
      '#attributes' => array('class' => array('hide-title')),
    ),
    'donate_amount_options_single' => array(
      '#title' => t('I would like to donate'),
      '#type' => 'radios',
      '#options' => $single_donate_options,
      '#attributes' => array('class' => array('hide-title')),
      '#required' => FALSE,
    ),
    //Pay in has same payment options as single
    'donate_amount_options_pay_in' => array(
      '#title' => t('I would like to pay-in'),
      '#type' => 'radios',
      '#options' => $single_donate_options,
      '#attributes' => array('class' => array('hide-title')),
      '#required' => FALSE,
    ),
    'donate_amount' => array(
      '#title' =>t('Amount'),
      '#type' => 'textfield',
      '#placeholder' => t('Other amount'),
      '#attributes' => array('class' => array('hide-title')),
      '#field_prefix'  => '£',
      '#required' => TRUE,
      
    ),
    'donate_reason' => array(
      '#title' => t('Why are you donating today?'),
      '#type' => 'select',
      '#required' => TRUE,
      '#options' =>  array(
          'general' => t('To beat blood cancer'),
          'in_mem' => t('In memory of a loved one'),
          'support_fundraising' => t('To support a fundraiser'),
          'tribute_fund' => t('To give to a tribute fund'),
        ),
    ),
    'submit' => array(
      '#type' => 'submit',
      '#value' => 'Next',
    ),
  ); 

  //Set default values from previous submission
  $prev_values = llr_webform_retrieve_values('donate_start_form');
  foreach ( $prev_values as $k => $v ) {
    $form[$k]['#default_value'] = $v;
  }
  
  //Provide js with settings relating to donation amounts
  drupal_add_js(array('LLRDonate' => $settings['donation_amounts']), 'setting');
  return $form;
}

/**
 * Validation callback for donation start page form
 */
function llr_donation_donate_start_form_validate(&$form, &$form_state) {
}

/**
 * Submit callback for donation start  form
 */
function llr_donation_donate_start_form_submit(&$form, &$form_state) {
  $values = $form_state['values'];
  $destination = '';
  //Route to a named donate webform
  switch  ( $values['donate_options']  ) {
    case 'monthly' :
      $dest_form = 'webform_donate_monthly';
      break;
    case 'single' :
    case 'pay_in' :
      $dest_form = 'webform_donate_single';
      break;
  }
  if ( $dest_form ) {
    $nid = llr_webform_get_id_by_name($dest_form);
    $destination =  $nid ? 'node/' . $nid : $_GET['q'];
    llr_webform_store_values($dest_form, $values);
    drupal_goto($destination);
  }
}
