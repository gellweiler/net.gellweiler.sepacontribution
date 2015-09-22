<?php

require_once 'sepacontribution.civix.php';
require_once 'sepa_mandate_from_contribution.php';
require_once 'sepa_payment_processors.php';

/**
 * Remove errors set by sepa module that prevent
 * changing the type of contriubtions to and from
 * sepa types.
 */
function sepacontribution_civicrm_validateForm(
    $formName,
    &$fields,
    &$files,
    &$form,
    &$errors
) {
  if ($formName == 'CRM_Contribute_Form_Contribution') {
    // Remove errors set by sepa module that
    // concern the payment instrument.
    if (isset($errors['payment_instrument_id'])) {
      unset($errors['payment_instrument_id']);
    }

    // Look if this is a sepa contribution.
    if (in_array(
        $fields['payment_instrument_id'],
        array_keys(sepacontribution_get_sepa_payment_instruments())
    )) {
      // Check sepa fields for errors.
      $sepa_fields = sepacontribution_extract_sepa_fields($fields);


      foreach ($sepa_fields as $field) {
        extract($field);

        switch ($column_name) {
          case 'iban':
            if (empty($value)) {
              $errors[$field['field_key']]
                = 'IBAN is required for sepa transactions';
            } else if ($msg = CRM_Sepa_Logic_Verification::verifyIBAN($value)) {
              $errors[$field['field_key']] = $msg;
            }
            break;

          case 'bic':
            if (empty($value)) {
              $errors[$field['field_key']]
                = 'BIC is required for sepa transactions';
            } else if ($msg = CRM_Sepa_Logic_Verification::verifyBIC($value)) {
              $errors[$field['field_key']] = $msg;
            }
            break;

          case 'reference':
            if (empty($value)) {
              $errors[$field['field_key']]
                = 'A sepa mandate reference is required for sepa transactions';
            }
            break;
        }
      }
    }
  }
}

/**
 * Extracts sepa fields from an array of fields.
 *
 * @param $fields
 *  Array of fields from a form hook.
 *
 * @return array
 *  All sepa custom fields. 
 */
function sepacontribution_extract_sepa_fields($fields) {
  $result = array();

  // Extract custom ids and map them to the field keys.
  $custom_ids = array();

  foreach (array_keys($fields) as $field) {
    if (preg_match('/^custom_(\d+)/', $field, $matches) === 1) {
      // Get custom field id.
      $custom_ids[$matches[1]] = $field;
    }
  }

  if (!empty($custom_ids)) {
    // Get columns of custom fields that are sepa custom fields.
    $custom_info = civicrm_api3('CustomField', 'get', array(
      'sequential' => 1,
      'return' => 'id,column_name',
      'id' => array('IN' => array_keys($custom_ids),
      'option_group_id' => sepacontribution_get_sepa_group_id(),
    )));

    foreach ($custom_info['values'] as $field) {
      $field_key = $custom_ids[$field['id']];
      $result[] = array(
        'column_name' => $field['column_name'],
        'field_key' => $field_key,
        'value' => $fields[$field_key],
      );
    }
  }

  return $result;
}

/**
 * Get the id of the custom sepa group.
 */
function sepacontribution_get_sepa_group_id() {
  // Find sepa custom field group.
  $sepa_group = civicrm_api3('CustomGroup', 'getsingle', array(
     'sequential' => 1,
     'return' => "id",
     'table_name' => "civicrm_sepa_custom",
  ));
  if (!is_array($sepa_group) || empty($sepa_group['id'])) {
    throw new Exception('Could not find custom sepa group.');
  }
  
  return $sepa_group['id'];
}

/**
 * Implementation of hook_civicrm_config
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function sepacontribution_civicrm_config(&$config) {
  _sepacontribution_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function sepacontribution_civicrm_xmlMenu(&$files) {
  _sepacontribution_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function sepacontribution_civicrm_install() {
  _sepacontribution_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function sepacontribution_civicrm_uninstall() {
  _sepacontribution_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function sepacontribution_civicrm_enable() {
  _sepacontribution_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function sepacontribution_civicrm_disable() {
  _sepacontribution_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function sepacontribution_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _sepacontribution_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function sepacontribution_civicrm_managed(&$entities) {
  _sepacontribution_civix_civicrm_managed($entities);
}

/**
 * Implementation of hook_civicrm_caseTypes
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function sepacontribution_civicrm_caseTypes(&$caseTypes) {
  _sepacontribution_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implementation of hook_civicrm_alterSettingsFolders
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function sepacontribution_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _sepacontribution_civix_civicrm_alterSettingsFolders($metaDataFolders);
}
