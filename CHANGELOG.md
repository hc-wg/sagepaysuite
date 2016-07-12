1.1.6.0 (12/07/2016)
=============
**This release requires Magento 2.1.0 at least.**
* Improvements
    * Change PI wording for Direct.
* Bugfixes
    * Order with custom option=file with SERVER integration was not working.
    * MOTO fixes.

1.1.5.2 (28/06/2016)
=============
* Bugfixes
    * Billing address not updated from checkout.

1.1.5 (09/05/2016)
=============
* New Features
    * License and Reporting credentials validated in config.
* Bugfixes
    * Compilation error with fraud helper in version 2.0.4.
    * Filename of fraud grid in admin with lowercase letter.

1.1.4 (01/04/2016)
=============
* New Features
    * Tokens Report in backend.
    * Fraud Report in backend.
    * Fraud score automations.
    * Unit-testing coverage of 80%.
    * Basket in all requests, XML and Sage50 compatible.
    * Currency configuration options.
    * Transaction details can now be synced from Sage Pay API from backend.
    * REPEAT MOTO integration.
    * FORM MOTO integration.
    * Euro Payments now supported with SERVER integration.
* Improvements
    * Max tokens per customer limitation (3).
    * Paypal "processing payment" page.
    * SERVER nice and shinny "slide" modal mode.
    * Translations backbone.
    * SERVER VPS hash validation.
    * Recover quote when end user clicks on back button after order was pre saved.
* Bugfixes
    * Various fixes to meet magento2 coding standarts.

1.1.2 (01/02/2016)
=============
* New Features
    * PayPal integration (frontend).
    * Cancel Pening payments CRON.
    * Fraud report CRON.
    * Token list in frontend customer area.
    * Unit tests additions.
* Bugfixes
    * Virtual products state address error.

1.1.0 (15/01/2016)
=============
* New Features
    * SERVER integration (frontend)
    * PI integration (backend)
    * Token integration for SERVER
    * 3D Secure for all integrations
    * Auth & Capture, Defer and Authentication payment actions for all integrations

1.0.6 (15/12/2015)
=============
* New Features
    * FORM integration (frontend)
    * PI integration (frontend)
    * Online Refunds
