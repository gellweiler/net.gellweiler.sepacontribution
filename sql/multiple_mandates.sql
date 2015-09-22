-- Remove unique key on civicrm_sdd_mandate
-- to allow multiple mandates with the same id.
ALTER TABLE `civicrm_sdd_mandate` DROP KEY reference;
ALTER TABLE `civicrm_sdd_mandate` ADD INDEX reference(reference);
