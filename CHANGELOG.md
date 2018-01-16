1.2.1 (16/01/2018)
=============
**This release requires Magento 2.2.x.**
- Bug fixes
  - Parent page already initialised Direct Drop-in.
  - Failed MOTO orders send confirmation email.
  - There was an error with Sage Pay transaction : Notice: Undefined variable: result.
  - Quote id repeated if order is canceled by customer SERVER.
  - Money taken for auto cancelled order.
- Improvements
  - Split database support out of the box.
  - Updated en_GB.csv translation file.

1.2.0 (28/09/2017)
=============
**This release requires Magento 2.2.0.**
- First release with Magento 2.2.0 compatibility.

1.1.14 (27/09/2017)
=============
**This release requires Magento 2.1.x.**
- Bug fixes
  - Fix FORM transactions not cancelling when in pending_payment state and customer leaves the payment pages.
  - Fix error where if a wrong CVC is entered in PI DropIn you cannot retry.
  - Fix MOTO pricing problem.
  - Fix SERVER integration VendorTxCode null value.
  - Fix Transaction not Found error with DropIn.
  - Fix multiple requests on MOTO orders when changing shipping method.
  - Show correct error in cart instead of Something went wrong: Invalid Sage Pay Response.
  - Clear mini-cart after paypal order.
  - Fix MOTO customer already exists error but payment is taken anyway.
  - Fix paypal callback using wrong total.
  - Fix currency:base problem in frontend.
  - Fix conflict with credit card form dates when other cc payment methods are enabled on frontend.
  
1.1.13 (12/07/2017)
=============
**This release requires Magento 2.1.x.**
- Bug fixes
  - Partial refunds after partial invoices.
  - Email error when placing 2 different orders on PI.
  - Can't create 2 credit memos for a transaction using PI.
  - Cart still contains items after purchase.
  - DroPin config per store view not working in frontend.
  - Extensions are not reporting support for all required PHP versions in the composer.json.
  - Different billing address button enabled before update the address.
  - Verifypeer set to true by default.

1.1.12 (05/05/2017)
=============
**This release requires Magento 2.1.x.**
- Bug fixes
  - Fix test.param is not a funcion on PI MOTO transactions.

1.1.11 (04/05/2017)
=============
**This release requires Magento 2.1.x.**
- Improvements
  - Change wording on configuration settings.
- Bug fixes
  - Order status for Deferred and Authenticate transactions. Now the initial status is Pending Payment, then it moves to Pending and when the invoice is created it moves to processing.
  - Fix error when creating an invoice "Notice: Undefined property: \Ebizmarts\SagePaySuite\Model\Payment::$_config"
  - Fix for duplicate customer address when checking out as logged in customer.
  - Duplicate payment on failed orders, happens rarely but now those payments are voided when the defect occurs.
  - Error on backend (MOTO) orders with multiple currencies. MultiStore MOTO Payments.
  - Fix postcode error when postcode is not required for the country.
  - Fix for "Notice: Object of class Magento\Framework\ObjectManager\ObjectManager could not be converted to ..." when Magento is in production mode and using Form.
  
1.1.10 (07/02/2017)
=============
**This release requires Magento 2.1.x.**
- Improvements
  - PI requests migrated to WEBAPIs, this fixes issues on frontend orders with custom options.
  - A lot of refactoring, removing duplicate code.
- Bug fixes
  - additional_information fraud rules object currupting the row.
  - quoteIdMaskFactory is declared too many times fix.
  - Undefined property: stdClass::$code fix.
  - Division by zero fix on basket.
- New features
  - DropIn checkout (SAQ-A) for frontend and backend orders.

1.1.9 (21/12/2016)
=============
**This release requires Magento 2.1.x.**
* Improvements
    * PI void using instructions/void API.
    * PI refund using own API.
    * Add index on sagepaysuite_token table.
* Bug fixes
    * Validation is failed. PI transactions go through even if Magento JS validation fails.
    * Uncaught TypeError: Unable to process binding if: hasSsCardType
    * PI on admin lets you enter cc number with spaces.
    * Magento minification minifies PI external files and 404s.
    * Fraud on order view Not enough information. Undefined property: stdClass::$fraudscreenrecommendation.
    * PI integration customer email not sent.

1.1.8 (28/10/2016)
=============
**This release requires Magento 2.1.x.**
* Improvements
    * Enable disable form and pi on moto, different config.
    * Add CardHolder to FORM requests for ReD validation.
    * Add index on sagepaysuite_token table.
* Bug fixes
    * Remove reference to legacy code Mage::logException.
    * Redirect to Sage Pay on server integration when on mobile.
    * Validate moto order when using pi before submitting to sagepay.
    * Sage Pay Logo loading via HTTPS everywhere now.
    * Sage Pay PI does not show a progress indicator once the place order button is pressed.
    * Don't show "My Saved Credit Cards" link on My Account if not enabled.
    * BasketXML fixes specially for PayPal.
    * Fixed many issues with frontend orders, changed requests to webapis.
    * Fix logo disappearing on checkout.
    * Fix moto order stuck in pending_payment status.
    * Fix cancelled orders in pi frontend when 3D secure is not Authenticated.
    * Specific ACL on admin controllers.
    * Many performance and standards compliance improvements.

1.1.7 (18/08/2016)
=============
**This release requires Magento 2.1.x.**
* Improvements
    * Coding standards for Magento Marketplace.
* Bug fixes
    * Basket display issue, decimal places.
    * MOTO customer create account for PI integration fixed.

1.1.6.0 (12/07/2016)
=============
**This release requires Magento 2.1.x.**
* Improvements
    * Change PI wording for Direct.
* Bug fixes
    * Order with custom option=file with SERVER integration was not working.
    * MOTO fixes.

1.1.5.2 (28/06/2016)
=============
* Bug fixes
    * Billing address not updated from checkout.

1.1.5 (09/05/2016)
=============
* New Features
    * License and Reporting credentials validated in config.
* Bug fixes
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
* Bug fixes
    * Various fixes to meet magento2 coding standarts.

1.1.2 (01/02/2016)
=============
* New Features
    * PayPal integration (frontend).
    * Cancel Pening payments CRON.
    * Fraud report CRON.
    * Token list in frontend customer area.
    * Unit tests additions.
* Bug fixes
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
