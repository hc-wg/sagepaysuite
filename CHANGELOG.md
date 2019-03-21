## [1.2.13] - 2019-03-25
### Added
- On Hold status stop auto-invoice

### Changed
- Redirect to empty cart fix
- Multi-Currency invoice use base currency amount
- Defer invoice problem with Multi-Store setup
- Repeat problem with Multi-Store setup

## [1.2.12] - 2019-02-05
### Changed
- Encrypt callback URL.
- 3D secure iframe alignment on mobile devices.

## [1.2.11] - 2019-01-07
### Added
- Invoice confirmation email for Authorise and capture
- Show verification results in payment layout at order details
- Server low profile smaller modal window

### Changed
- Cancel or Void a Defer order without invoice
- Refund problem on multi-currency sites
- PI without DropIn problem when you enter a wrong CVN
- Problem with refunds on multi-sites using two vendors
- Exception thrown when open Fraud report
- Basket XML constraint fix
- Magento's sign appearing when click fraud cell

## [1.2.10] - 2018-10-16
### Changed
- Update translation file strings en_GB.csv 
- Enforce fields length according to Sage Pay rules on Pi integration 
- Disable Multishipping payment methods because they dont work
- Problems with PayPal basket and special characters

## [1.2.9] - 2018-10-01
### Added
- PI Defer partial invoice
- Read module version from composer file

### Changed
- Improve error message when transaction fails (SERVER)
- Quote not found when STATUS: NOTAUTHED on SERVER
- Repeat deferred invoice error
- Problem when there is no shipping method. Validate quote befor submit.
- Orders made with PI DropIn MOTO add +1 on the VendorTxCode
- Delay fraud check to avoid no fraud information result
- Fraud check command failure
- Auto-invoice not working
- This credit card type is not allowed for this payment method on PI no DropIn
- Second credit card is not being saved on Server

## [1.2.8] - 2018-08-22
### Added
- Uninstall database mechanism
- Terms & Condition server side validation

### Changed
- Checkout missing request to payment-information
- Unable to continue checkout if button "Load secure credit card form" button is pressed before editing the billing address
- Unable to find quote
- FORM email confirmation adds &CardHolder next to the shipping phone number

## [1.2.7] - 2018-08-06
### Added
- 2.2.5 compatibility.

### Changed
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
- BankAuthCode and TxAuthNo is not saved on the DB.

## [1.2.6] - 2018-04-06
### Changed
- Form failure StatusDetail inconsistent causes undefined offset.
- Unique Constraint Violation cancelling orders on Form and Server integrations.

## [1.2.5] - 2018-03-22
### Added
- Fraud flags on sales orders grid.

### Changed
- Improve error message when reporting password is incorrect.
- Unserialize use helper objects.
- Minify javascript exception via xml causes problem with tinymce, using plugin now.
- Invalid card on Drop-in, the load secure from button disappears.
- Call to a member function getSagepaysuiteFraudCheck on boolean. Sync from api on backend.
- Call to a member function getBillingAddress on null. Specific countries option with Pi.

## [1.2.4] - 2018-03-01
### Changed
- Swagger generation failing because of missing parameter "quote" on webapi.xml.
- Non-decimal currencies (eg: JPY) sending wrong amount to Sage Pay Pi and failing on Server/Form.
- Improve admin message error when Reporting API signature fails.
- Update Sage Pay Direct label to Sage Pay Pi on admin config page.

## [1.2.3] - 2018-02-13
### Changed
- Concrete class parameter breaks SOAP API.
- Upgrade schema vendorname column not defined.

## [1.2.2] - 2018-01-30
### Changed
- Fix bad class import on PiRequestManagement.
  
## [1.2.1] - 2018-01-16
### Changed
- Parent page already initialised Direct Drop-in.
- Failed MOTO orders send confirmation email.
- There was an error with Sage Pay transaction : Notice: Undefined variable: result.
- Quote id repeated if order is canceled by customer SERVER.
- Money taken for auto cancelled order.
- Updated en_GB.csv translation file.
- Split database support out of the box.

## [1.2.0] - 2017-09-28
### Added
- First release with Magento 2.2.0 compatibility.