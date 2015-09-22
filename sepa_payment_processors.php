<?php

/**
 * Get an array of payment instruments that contain sepa in their name.
 * Use the id as key and an detailed array as value.
 */
function sepacontribution_get_sepa_payment_instruments($reset = false) {
  static $result;
  if (!isset($result) || $reset) {
    $result = array();

    // Find option group for payment_instrument ids.
    $option_group = civicrm_api3('OptionGroup', 'getsingle', array(
      'sequential' => 1,
      'return' => "id",
      'name' => "payment_instrument",
    ));

    if (!is_array($option_group) || empty($option_group['id'])) {
      throw new Exception('Could not find OptionGroup for payment processors.');
    }

    $option_values = civicrm_api3('OptionValue', 'get', array(
      'sequential' => 1,
      'option_group_id' => $option_group['id'],
      'label' => array('LIKE' => "%Sepa%"),
    ));

    if (!is_array($option_values) || empty($option_values['values'])) {
      throw new Exception('Could not find any sepa payment processors.');
    }

    foreach($option_values['values'] as $payment_instrument) {
      $result[$payment_instrument['value']] = $payment_instrument;
    }
  }

  return $result;
}

