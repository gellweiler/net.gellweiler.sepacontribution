<?php
require_once 'sepa_payment_processors.php';

/**
 * Implementation of hook_civicrm_pre().
 */
function sepacontribution_civicrm_pre(
  $op, //operation
  $objectName,
  $objectId,
  &$objectRef
) {
  if ($objectName == 'Contribution' && ($op == 'edit' || $op == 'create')) {
    $obj = SepaMandateFromContribution::get();
    $obj->preContributionSet($op, $objectName, $objectIdm, $objectRef);
  }
}

/**
 * Implementation of hook_civicrm_post().
 */
function sepacontribution_civicrm_post(
  $op, //operation
  $objectName,
  $objectId,
  &$objectRef
) {
  if ($objectName == 'Contribution' && ($op == 'edit' || $op == 'create')) {
    $obj = SepaMandateFromContribution::get();
    $obj->postContributionSet($op, $objectName, $objectIdm, $objectRef);
  }
}

/**
 * Creates/Updates a sepa mandate from the custom sepa fields
 * when creating a new contribution.
 */
class SepaMandateFromContribution
{
  /**
   * Array of values to pass through to civicrm api.
   */
  protected $values = array(
    'entity_table' => 'civicrm_contribution',
    'creditor_id' => 1, // Biva e.V.
  );


  /**
   * Flag that signals if this contribution
   * is a sepa contribution.
   */
  protected $is_sepa_contribution = false;

  /**
   * Get/Create a singleton for this class.
   */
  public static function get($objectId) {
    static $instance;

    if (!isset($instance)) {
      $instance = new self($objectId);
    }

    return $instance;
  }

  /**
   * Object should be constructed through ::get().
   */
  private function __construct() {}

  /**
   * Invoked by hook_civicrm_pre().
   * 
   * Extracts values from contribution before writing them to db
   * and remove custom sepa values from object,
   * so that they don't get automatically saved.
   */
  public function preContributionSet(
    $op, //operation
    $objectName,
    $objectId,
    &$objectRef
  ) {
    // List of keys that should be unset in custom array after 
    // extacting values.
    $keys_to_unset = array();

    // Get values from custom Sepa fields.
    foreach($objectRef['custom'] as $field_id => $field_set) {
      foreach($field_set as $field) {
        if ($field['table_name'] == 'civicrm_sepa_custom') {
          if (!empty($field['value'])) {
            $this->values[$field['column_name']] = $field['value'];
          }

          $keys_to_unset[] = $field_id;
        }
      }
    }

    // Remove custom sepa values from contribution.
    foreach($keys_to_unset as $key) {
      unset($objectRef['custom'][$key]);
    }

    // Check if this is a sepa contribution.
    if (in_array(
      $objectRef['payment_instrument_id'],
      array_keys(sepacontribution_get_sepa_payment_instruments()))
    ) {
      $this->is_sepa_contribution = true;
    }

    if ($this->is_sepa_contribution) {
      // Get the contact id and add it to the values.
      $this->values['contact_id'] = $objectRef['contact_id'];

      // Get value of sepa instrument and use it's name as status.
      $payment_instruments = sepacontribution_get_sepa_payment_instruments();
      $this->values['status'] = $status =
        $payment_instruments[$objectRef['payment_instrument_id']]['name'];
      $this->values['type'] = $status == 'RCUR' ? 'RCUR' : 'OOFF';
    }
  }

  /**
   * Invoked by hook_civicrm_post().
   *
   * Extracts contribition id after writing contribution to db and
   * then creates/updates a sepa mandate from custom sepa values or deletes
   * existing sepa mandates if this contribution is not a sepa contribution.
   */
  public function postContributionSet(
    $op, //operation
    $objectName,
    $objectId,
    &$objectRef
  ) {
    // Add contribution id to values.
    $this->values['entity_id'] = $objectRef->id;

    if ($this->is_sepa_contribution) {
      $this->createSepaMandate();
    } else {
      $this->deleteSepaMandate();

      // Delete references to this contribution in sepa dashboard.
      CRM_Core_DAO::executeQuery(
"
DELETE FROM civicrm_sdd_contribution_txgroup
WHERE contribution_id=$objectRef->id
"
      );
    }
  }

  /**
   * Gets existing mandates that are connected with this contribution.
   */
  private function getMandates() {
      // Look for mandates with  entity_id of this contribution.
      $query = array_intersect_key(
        $this->values,
        array('entity_id' => '', 'entity_table' => '')
      );

      return civicrm_api3('SepaMandate', 'get', $query);
  }

  /**
   * Deletes sepa mandates for this contribution.
   */
  private function deleteSepaMandate() {
    $mandates = $this->getMandates();
    if ($mandates['count'] > 0) {
      foreach ($mandates['values'] as $mandate) {
        civicrm_api3('SepaMandate', 'delete', array('id' => $mandate['id']));
      }
    }
  }

  /**
   * Create sepa mandat from values if the custom 
   * SEPA fields are filled out.
   */
  private function createSepaMandate() {
    $mandate = $this->getMandates();
    if ($mandate['count'] > 0) {
      $set = reset($mandate['values']);
      $this->values['id'] = $set['id'];
    }

    civicrm_api3('SepaMandate', 'create', $this->values, true);
  }
}
