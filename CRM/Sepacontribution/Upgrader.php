<?php

/**
 * Collection of upgrade steps
 */
class CRM_Sepacontribution_Upgrader extends CRM_Sepacontribution_Upgrader_Base {

  /**
   * Replace table for custom SEPA fields with view.
   * To redirect SEPA fields to sdd_mandate table.
   */
  public function enable() {
    // Execute DB hacks that redirect sepa custom fields
    // to the sdd_mandate table and ensure that this
    // modules hooks are run after the sepa extension's hooks.

    $this->executeSqlFile('sql/sepa_custom_fields.sql');
    $this->executeSqlFile('sql/multiple_mandates.sql');
  }
}
