-- Use a view to redirect requests to civicrm_sepa_custom to civicrm_sdd_mandate.
DROP TABLE IF EXISTS civicrm_sepa_custom;
DROP VIEW IF EXISTS civicrm_sepa_custom;
CREATE VIEW civicrm_sepa_custom AS SELECT * FROM civicrm_sdd_mandate;
