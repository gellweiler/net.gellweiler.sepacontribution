# SEPA in standard contributions for CiviCRM

The [SEPA-Module](https://civicrm.org/make-it-happen/sepa-direct-debit-integration?q=make-it-happen/sepa-direct-debit-integration) uses an own button to record new SEPA-Contributions, existing contributions can't be changed into SEPA-Contributions and vice versa. Further the SEPA-Module expects one [SDD Mandate](http://www.europeanpaymentscouncil.eu/index.cfm/sepa-direct-debit/the-sdd-mandate/) per contribution or recurring contribution – if you are only interested in collecting regular membership fees this will work, but if you need to collect multiple singe contributions with the same mandate this is a big restriction.

This module works around these restrictions without the need to patch any of the original SEPA module's code. However this does not guaranty that this module will work with future versions of the SEPA-Module. **It is very important, that you install & activate this module after the SEPA-Module.**, because this module needs to be called after the SEPA-Module to unset errors set by it.

This module is a hackish solution, **backup your CiviCRM DB before installing this module and know what you're doing**. This module messes with the DB to work. That being sad, we've used in production over the last half year and it worked so far. 

###### Sponsor
This module was developed for [BIVA e.V.](http://www.biva.de).
