<?php

/**
 * Define Donation admin form.
 */
function llr_donation_admin_form($form, &$form_state) {
  $settings = variable_get('llr_donation', llr_donation_default_settings());
  //We save a single variable called llr_donation as a nested associative array;
  $form['#tree'] = TRUE;

  //This is a wrapper to key by llr_donation
  $don = array('#type' => 'container');

  //Minimum number of donation amount options
  $min_amt_opts = 3;
  
  //Check for item ajax button click and perform alterations on settings
  llr_donation_admin_form_ajax_rebuild($settings, $form_state);
  $donation_amounts = $settings['donation_amounts'];
  
  $don['donation_amounts']  = array(
    '#type' => 'fieldset',
    '#title' => 'Donation Amounts',
    '#description' => 'Configure amounts to appear as donation options',
    '#collapsible' => TRUE,
   // '#collapsed' => TRUE,
    //To let callback know what to return, we give the ajax wrapper an
    //id matching the element key (with underscore/hyphen replacement
    '#prefix' => '<div id="donation-amounts">',
    '#suffix' => '</div>',
  );
  foreach ( $donation_amounts as $idx => $amt ) {
    $item = array();
    $item['#type'] = 'fieldset';
    $item['#title'] = '£' . $amt['value'];
    $item['#collapsible'] = TRUE;
    //Expand if a new item else collapse
    $item['#collapsed'] = (bool) $amt['value'];
    $item['value'] = array(
      '#title' => 'Amount',
      '#type' => 'textfield',
      '#element_validate' => 'element_validate_number',
      '#required' => TRUE,
      '#field_prefix' => '£',
      '#size' => 7,
      '#default_value' => $amt['value'],
    );
    $item['fact'] = array(
      '#title' => 'Fact',
      '#description' => 'A description of what may be achieved with a donation of this amount',
      '#type' => 'textarea',
      '#default_value' => $amt['fact'],
    );
    $item['single'] = array(
      '#title' => 'Appears in single (one-off) donation form',
      '#type' => 'checkbox',
      '#default_value' => $amt['single'],
    );
    $item['monthly'] = array(
      '#title' => 'Appears in monthly donation form',
      '#type' => 'checkbox',
      '#default_value' => $amt['monthly'],
    );
    if ( $idx > $min_amt_opts - 1 ) {
      $item['remove_amount'] = array(
        '#type' => 'button',
        '#value' => 'Remove this amount',
        '#name' => 'remove_amount_' . $idx,
        '#ajax' => array(
          'callback' => 'llr_donation_ajax_callback',
          'wrapper' => 'donation-amounts',
        ),
        //Don't validate the form when removing items
        '#submit' => array(),
        '#limit_validation_errors' => array(),
      );
    }
    $item['#weight'] = $idx;
    $don['donation_amounts'][$idx] = $item;
  }
  $don['donation_amounts']['add_amount'] = array(
      '#type' => 'button',
      '#value' => 'Add an amount',
      '#name' => 'add_amount',
      '#weight' => $idx + 1,
      '#ajax' => array(
        'callback' => 'llr_donation_ajax_callback',
        'wrapper' => 'donation-amounts',
      ),
      //Don't validate or submit the form when adding blank items
      '#submit' => array(),
      '#limit_validation_errors' => array(),
  );
  $don['nudge_desc']['#markup'] = '<div><h3>Nudges</h3>' 
    . '<p>The donation journey includes nudges to encourage the donor to increase their donation to reach '
    . 'a particular target </p> <p> </p></div>';
  $don['nudges_button_label'] = array(
    '#type' => 'textfield',
    '#title' => 'Button label for nudges',
    '#description' => '{shortfall} will be replaced by the amount to be added to the current amount to reach the target.',
    '#default_value' => $settings['nudges_button_label'],
    '#required' => TRUE,
  );
  $don['nudges_accept_msg'] = array(
    '#type' => 'textarea',
    '#title' => 'Nudge accepted message',
    '#description' => 'A message to display if the donor accepts the nudge.',
    '#default_value' => $settings['nudges_accept_msg'],
    '#required' => TRUE,
  );
  $don['nudges_single'] = llr_donation_nudge_form_section('single', $settings);
  $don['nudges_monthly'] = llr_donation_nudge_form_section('monthly', $settings);
  $don['titles_single'] = llr_donation_titles_form_section('single', $settings);
  $don['titles_monthly'] = llr_donation_titles_form_section('monthly', $settings);
  $form['llr_donation'] = $don;
  return system_settings_form($form);
}

/**
 * Default ajax callback for the donation settings form.
 *
 * Determines which part of the form is to be returned based
 * on the convention that the wrapper id  matches the element
 * key.
 */
function llr_donation_ajax_callback($form, &$form_state) {
  $key = $form_state['triggering_element']['#ajax']['wrapper'];
  $ret = $form['llr_donation'];
  if ( $key ) {
    $key = str_replace('-', '_', $key);
    $ret = isset($ret[$key]) ?  $ret[$key] : $ret;
  }    
  return $ret;
}

/**
 * Helper function for the form building function.
 *
 * Check for ajax triggers and alter the settings array accordingly
 * The settings array is used to construct the form.
 */
function llr_donation_admin_form_ajax_rebuild(&$settings, &$form_state) {
  if (  empty($form_state['clicked_button']['#ajax']) ) {
    return;
  }
  //a blank nudge
  $new_nudge = array(
    'target_value' => 0,
    'threshold' => 0,
    'text' => '',
  );
  
  $button = $form_state['clicked_button'];
  
  //Get the key of the button element and parent item
  $op = array_pop($button['#array_parents']);
  $target_key = array_pop($button['#array_parents']);

  //Get a reference to the settings element we want to alter
  $target =& $settings;
  for ( $i = 1; $i < count($button['#array_parents']); $i++ ) {
    $k = $button['#array_parents'][$i];
    if ( ! empty( $target[$k]) ) {
      $target =& $target[$k];
    }
  }
  drupal_set_message("performing $op on $target_key"); 
  switch ( $op ) {
  case 'remove_amount' :
    unset($target[$target_key]); 
    $settings['donation_amounts'] = array_values($target);
    break;
  case 'add_amount' :
    $settings['donation_amounts'][] = array( 
      'value' => '',
      'fact' => '',
      'monthly' => FALSE,
      'single' => FALSE, 
    );
    break;
  case 'add_nudge_single' :
    $settings['nudges_single'][] = $new_nudge;
    break;
  case 'remove_nudge_single' :
    unset($target[$target_key]); 
    $settings['nudges_single'] = array_values($target);
    break;
  case 'add_nudge_monthly' :
    $settings['nudges_monthly'][] = $new_nudge;
    break;
  case 'remove_nudge_monthly' :
    unset($target[$target_key]); 
    $settings['nudges_monthly'] = array_values($target);
    break;
  }
}

/**
 * Helper function to build form section for titles settings
 */
function llr_donation_titles_form_section($type, $settings) {
  
  $items  = array(
    '#type' => 'fieldset',
    '#title' => "Page titles for $type donations",
    '#description' => "Configure page titles for $type donations.",
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );

  $t = $settings['titles_' . $type];

  $items['main'] = array(
    '#type' => 'textfield',
    '#title' => 'Main donation page',
    '#default_value' => $t['main'],
    '#description' => '{amount} will be replaced with the donation amount',
  );
  $items['thank_you'] = array(
    '#type' => 'textfield',
    '#title' => 'Thank You page',
    '#default_value' => $t['thank_you'],
    '#description' => '{amount} will be replaced with the donation amount',
  );
  return $items;
}

/**
 * Helper function to build the section of form
 * for nudge settings.
 * There are separate sections for single and monthly
 */
function llr_donation_nudge_form_section($type, $settings) {
  $nudge_items  = array(
    '#type' => 'fieldset',
    '#title' => "Nudges for $type donations",
    '#description' => "Configure nudges for $type donations.",
    '#collapsible' => TRUE,
    //To let callback know what to return, we give the ajax wrapper an
    //id matching the element key (with underscore/hyphen replacement
    '#prefix' => '<div id="nudges-' . $type . '">',
    '#suffix' => '</div>',
  );
  $sn = $settings['nudges_' . $type];
  foreach ( $sn as $idx => $nudge ) {
    $item = array(
      '#type' => 'fieldset',
      '#title' => '£' . $nudge['target_value'],
      '#collapsible' => TRUE,
    //Expand if a new item else collapse
      '#collapsed' => (bool) $nudge['target_value'],
    
    );
    $item['target_value'] = array(
      '#title' => 'Target amount',
      '#type' => 'textfield',
      '#element_validate' => 'element_validate_number',
      '#required' => TRUE,
      '#field_prefix' => '£',
      '#size' => 7,
      '#default_value' => $nudge['target_value'],
    );
    $item['threshold'] = array(
      '#title' => 'Nearness threshold',
      '#description' => 'The nudge is only displayed if the current donation plus the threshold is greater or equal to the target.',
      '#type' => 'textfield',
      '#element_validate' => 'element_validate_number',
      '#required' => TRUE,
      '#field_prefix' => '£',
      '#size' => 7,
      '#default_value' => $nudge['threshold'],
    );
    $item['text'] = array(
      '#title' => 'Text',
      '#description' =>  "A message to encourage the donor to accept the nudge. 
          The token {shortfall} will insert the difference in value between the current amount
          and the target.",
      '#type' => 'textarea',
      '#required' => TRUE,
      '#default_value' => $nudge['text'],
    );
    $item['remove_nudge_' . $type] = array(
      '#type' => 'button',
      '#value' => 'Remove this nudge',
      '#name' => 'remove_nudge_' . $type . '_' . $idx,
      '#ajax' => array(
        'callback' => 'llr_donation_ajax_callback',
        'wrapper' => 'nudges-' . $type,
      ),
      //Don't validate the form when removing items
      '#submit' => array(),
      '#limit_validation_errors' => array(),
    );
   $nudge_items[$idx] = $item;
  } 
 $nudge_items['add_nudge_' . $type] = array(
      '#type' => 'button',
      '#value' => 'Add a new nudge',
      '#name' => 'add_nudge_' . $type,
      '#weight' => $idx + 1,
      '#ajax' => array(
        'callback' => 'llr_donation_ajax_callback',
        'wrapper' => 'nudges-' . $type,
      ),
      //Don't validate the form when adding blank items
      '#submit' => array(),
      '#limit_validation_errors' => array(),
  );
  return $nudge_items;
}
