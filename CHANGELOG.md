## [1.1.29] - 2019-09-23
### Added
- PI support for PSD2 and SCA

### Fixed
- Stop the order for try to being captured if txstateid empty
- 0.00 cost products breaks PayPal
- Fix Multi Currency Authenticate invoice using Base Currency amount

## [1.1.28] - 2019-08-07
### Added
- Setting to set max tokens per customer

### Changed
- Hide Add New Card when reached max tokens

### Fixed
- Label and Checkbox from first token being shown when press add new card
- Send 000 post code when field is left empty for Ireland or Hong Kong (SERVER and FORM)
- PI always sending 000 post code for Ireland and Hong Kong even if the customer entered a post code
- Module breaks Sales -> Order when the payment additional information is serialized
- Multi Currency refunds using Base Currency amount (FORM, SERVER, PayPal)

## [1.1.27] - 2019-06-19
### Fixed
- Module breaks Sales -> Order
- Server defer orders not being cancelled on SagePay
- PI always selected as default payment method on the checkout

## [1.1.26] - 2019-05-08
### Added
- Explanation message to order view
- Add waiting for score and test fraud flags
- Add CardHolder Name field to PI without DropIn

### Changed
- Update README.md to use url sagepaysuite.gitlab.ebizmarts.com for composer config.

### Fixed
- PI DropIn MOTO problem with multiple storeviews
- Invoice and Refund problem with multi currency site and base currency
- Class for 2.1 is not compatible with PHP 5.6
- Basket Sage50 doesn't send space character

### Removed
- PHP restrictions on module for M2.1
- Remove cc images from the Pi form

## [1.1.25] - 2019-03-26
### Changed
- On Hold status stop auto-invoice

### Fixed
- Defer invoice problem with Multi-Store setup
- Repeat problem with Multi-Store setup
- Redirect to empty cart fix

### Removed
- Remove FORM MOTO
 
## [1.1.24] - 2019-02-05
### Changed
- 3D secure iframe alignment on mobile devices.

### Security
- Encrypt callback URL.

## [1.1.23] - 2019-01-07
### Added
- Invoice confirmation email for Authorise and capture
- Show verification results in payment layout at order details
### Changed
- Server low profile smaller modal window

### Fixed
- Refund problem on multi-currency sites 
- PI without DropIn problem when you enter a wrong CVN
- Problem with refunds on multi-sites using two vendors
- Exception thrown when open Fraud report
- Basket XML constraint fix
- Magento's sign appearing when click fraud cell

## [1.1.22] - 2018-10-17
### Changed
- Update translation file strings en_GB.csv 
- Enforce fields length according to Sage Pay rules on Pi integration

### Removed
- Disable Multishipping payment methods because they dont work

### Fixed
- Problems with PayPal basket and special characters
 
## [1.1.21] - 2018-10-01
### Changed
 - Read module version from composer file
 - Improve error message when transaction fails (SERVER)

### Fixed
 - Repeat deferred invoice error
 - Problem when there is no shipping method. Validate quote befor submit.
 - Orders made with PI DropIn MOTO add +1 on the VendorTxCode
 - Second credit card is not being saved on Server
 - This credit card type is not allowed for this payment method on PI no DropIn
 - Auto-invoice not working
 - Quote not found when STATUS: NOTAUTHED on SERVER
 
## [1.1.20] - 2018-08-22
### Changed
- Uninstall database mechanism
- Terms & Condition server side validation (only for logged in customers)

### Fixed
- Checkout missing request to payment-information
- Unable to continue checkout if button "Load secure credit card form" button is pressed before editing the billing address
- Unable to find quote
- FORM email confirmation adds &CardHolder next to the shipping phone number

## [1.1.19] - 2018-08-06
### Fixed

- Rounding Issue, order amount mismatch by 1p.
- Repeat Defered orders with wrong status.
- Pi Incorrect payment actions.
- Token breaks checkout.
- MOTO Tax issue.
- Sync from API problem with Multi Store setup..
- Undefined property: stdclass::$status.
- Token is saved without asking the customer.
- PayPal sort order not being saved.
- Hong Kong optional zipcode.
- BankAuthCode and TxAuthNo is not saved on the DB

## [1.1.18] - 2018-04-06
### Added
  - Fraud flags on sales orders grid.

### Changed
  - Human friendly report api error on admin config.
  
### Fixed
  - Unique Constraint Violation cancelling orders.
  - Form failure StatusDetail inconsistent causes undefined offset.
  - Call to a member function getSagepaysuiteFraudCheck() on boolean.
  - Call to a member function getBillingAddress() on null.
  - Minify exception via xml causes problem with tinymce.
  - Invalid card on Drop-in the load secure from button disappears.
  - Invalid parameter type when using SOAP API.
  - Japanese currency issue.
  - SagePaySuite breaks Swagger when enabled.
  - Fix bad column name on sagepaysuite_token table.
  
## [1.1.17] - 2018-01-30
### Fixed
  - Fix bad class import on PiRequestManagement.

## [1.1.16] - 2018-01-15
### Changed
  - Split database support out of the box.
### Fixed
  - Parent page already initialised Direct Drop-in.
  - Failed MOTO orders send confirmation email.
  - There was an error with Sage Pay transaction : Notice: Undefined variable: result.
  - Quote id repeated if order is canceled by customer SERVER.
  - Money taken for auto cancelled order.
  
## [1.1.15] - 2017-11-06
### Fixed
  - Fix different vendornames per installation.
  - Direct MOTO Double confirmation email.
  
## [1.1.14] - 2017-09-27
### Fixed
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
  
## [1.1.13] - 2017-07-12
### Changed
  - Partial refunds after partial invoices.
### Fixed
  - Email error when placing 2 different orders on PI.
  - Can't create 2 credit memos for a transaction using PI.
  - Cart still contains items after purchase.
  - DroPin config per store view not working in frontend.
  - Extensions are not reporting support for all required PHP versions in the composer.json.
  - Different billing address button enabled before update the address.
  - Verifypeer set to true by default.

## [1.1.12] - 2017-05-05
### Fixed
  - Fix test.param is not a funcion on PI MOTO transactions.

## [1.1.11] - 2017-05-04
### Changed
  - Change wording on configuration settings.
### Fixed  
  - Order status for Deferred and Authenticate transactions. Now the initial status is Pending Payment, then it moves to Pending and when the invoice is created it moves to processing.
  - Fix error when creating an invoice "Notice: Undefined property: \Ebizmarts\SagePaySuite\Model\Payment::$_config"
  - Fix for duplicate customer address when checking out as logged in customer.
  - Duplicate payment on failed orders, happens rarely but now those payments are voided when the defect occurs.
  - Error on backend (MOTO) orders with multiple currencies. MultiStore MOTO Payments.
  - Fix postcode error when postcode is not required for the country.
  - Fix for "Notice: Object of class Magento\Framework\ObjectManager\ObjectManager could not be converted to ..." when Magento is in production mode and using Form.
  
## [1.1.10] - 2017-02-07
### Added
  - PI requests migrated to WEBAPIs, this fixes issues on frontend orders with custom options.
### Changed
  - A lot of refactoring, removing duplicate code.
  - DropIn checkout (SAQ-A) for frontend and backend orders.
### Fixed  
  - additional_information fraud rules object currupting the row.
  - quoteIdMaskFactory is declared too many times fix.
  - Undefined property: stdClass::$code fix.
  - Division by zero fix on basket.
  
## [1.1.9] - 2016-12-21
### Added
   - PI void using instructions/void API.
   - PI refund using own API.
   - Add index on sagepaysuite_token table.
### Fixed
   - Validation is failed. PI transactions go through even if Magento JS validation fails.
   - Uncaught TypeError: Unable to process binding if: hasSsCardType
   - PI on admin lets you enter cc number with spaces.
   - Magento minification minifies PI external files and 404s.
   - Fraud on order view Not enough information. Undefined property: stdClass::$fraudscreenrecommendation.
   - PI integration customer email not sent.

## [1.1.8] - 2016-10-28
### Added
   - Enable disable form and pi on moto, different config.
   - Add CardHolder to FORM requests for ReD validation.
   - Add index on sagepaysuite_token table.
### Changed
   - Remove reference to legacy code Mage::logException.
   - Redirect to Sage Pay on server integration when on mobile.
   - Validate moto order when using pi before submitting to sagepay.
   - Sage Pay Logo loading via HTTPS everywhere now.
   - Sage Pay PI does not show a progress indicator once the place order button is pressed.
   - Don't show "My Saved Credit Cards" link on My Account if not enabled.
   - Specific ACL on admin controllers.
   - Many performance and standards compliance improvements.
### Fixed
   - BasketXML fixes specially for PayPal.
   - Fixed many issues with frontend orders, changed requests to webapis.
   - Fix logo disappearing on checkout.
   - Fix moto order stuck in pending_payment status.
   - Fix cancelled orders in pi frontend when 3D secure is not Authenticated.
   
## [1.1.7] - 2016-08-18
### Changed
   - Coding standards for Magento Marketplace.
### Fixed
   - Basket display issue, decimal places.
   - MOTO customer create account for PI integration fixed.

## [1.1.6.0] - 2016-07-12
### Changed
   - Change PI wording for Direct.
   
### Fixed   
   - Order with custom option=file with SERVER integration was not working.
   - MOTO fixes.

## [1.1.5.2] - 2016-06-28
### Fixed
   - Billing address not updated from checkout.

## [1.1.5] - 2016-05-09
### Added
   - License and Reporting credentials validated in config.
### Fixed
   - Compilation error with fraud helper in version 2.0.4.
   - Filename of fraud grid in admin with lowercase letter.

## [1.1.4] - 2016-04-01
### Added
   - Tokens Report in backend.
   - Fraud Report in backend.
   - Fraud score automations.
   - Unit-testing coverage of 80%.
   - Basket in all requests, XML and Sage50 compatible.
   - Currency configuration options.
   - Transaction details can now be synced from Sage Pay API from backend.
   - REPEAT MOTO integration.
   - FORM MOTO integration.
   - Euro Payments now supported with SERVER integration.
   - Paypal "processing payment" page.
   - SERVER nice and shinny "slide" modal mode.
   - Translations backbone.
   - SERVER VPS hash validation.
### Changed
   - Max tokens per customer limitation (3).
   - Recover quote when end user clicks on back button after order was pre saved.
### Fixed
   - Various fixes to meet magento2 coding standarts.

## [1.1.2] - 2016-02-01
### Added
   - PayPal integration (frontend).
   - Cancel Pending payments CRON.
   - Fraud report CRON.
   - Token list in frontend customer area.
   - Unit tests additions.
### Fixed
   - Virtual products state address error.

## [1.1.0] - 2016-01-15
### Added
   - SERVER integration (frontend)
   - PI integration (backend)
   - Token integration for SERVER
   - 3D Secure for all integrations
   - Auth & Capture, Defer and Authentication payment actions for all integrations

## [1.0.6] - 2015-12-15
### Added
   - FORM integration (frontend)
   - PI integration (frontend)
   - Online Refunds
   
[1.1.28]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/1.1.28
[1.1.27]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/1.1.27
[1.1.26]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/1.1.26
[1.1.25]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/1.1.25
[1.1.24]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/1.1.24
[1.1.23]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/1.1.23
[1.1.22]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/1.1.22
[1.1.21]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/1.1.21
[1.1.20]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/1.1.20
[1.1.19]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/1.1.19
[1.1.18]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/1.1.18
[1.1.17]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/v1.1.17
[1.1.16]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/v1.1.16
[1.1.15]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/1.1.15
[1.1.14]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/v1.1.14
[1.1.13]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/v1.1.13
[1.1.12]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/v1.1.12
[1.1.11]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/v1.1.11
[1.1.10]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/v1.1.10
[1.1.9]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/1.1.9
[1.1.8]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/v1.1.8
[1.1.7]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/v1.1.7
[1.1.6.0]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/v1.1.6.0
[1.1.5.2]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/v1.1.5.2
[1.1.5]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/v1.1.5
[1.1.4]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/v1.1.4
[1.1.2]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/v1.1.2
[1.1.0]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/v1.1.0
[1.0.6]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/v1.0.6
