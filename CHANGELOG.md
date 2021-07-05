## [1.2.40] - 2021-07-07
### Fixed
- Module not recovering cart when PI 3D fails.
- Fraud check failing after Opayo update

## [1.2.39] - 2021-05-26
### Added
- Debug Mode setting
- Prevent customer personal data from logging setting
- Show 3rdMan score and score breakdown on order details
### Fixed
- 0.01 difference when you try to invoice PI Defer orders
- Invoice created successfully in Magento when transaction was aborted
- PI Authorize and Capture orders not being invoiced
- Recover cart message appearing in product page after successful order with PI and 3D

## [1.2.27.1] - 2021-05-12
### Fixed
- PI with 3D redirecting to cart after checkout

## [1.2.27] - 2021-02-01
### Fixed
- PI repeat with 3Dv2
- Recover cart when session is lost
- Fraud not being retrieved for non default sotres in multi-store setup
- Verification result not showing
- Browser Ipv6 error on PI

## [1.2.26] - 2020-11-24
### Fixed
- 3Dv1 not working with Protocol 4.00 for PI
- PI refund problem with Multi-Store sites
- Duplicated Callbacks received for FORM

## [1.2.25] - 2020-10-27	
### Changed
- Server cancel payment redirection to checkout shipping method
- Added new Order Details fields names in block

### Fixed
- Fix duplicate 3D callback and duplicate response for threeDSubmit
- CSP Whitelisting file
- Add routes to webRestrctions.xml to avoid payment failures when Magento's EE restrictions is enabled

## [1.2.24] - 2020-07-08	
### Changed	
- Sage Pay text and logo changed to Opayo	

### Fixed	
- Adapt 3Dv2 to latest updates	
- Duplicated address problem	
- 3D, Address, Postcode and CV2 flags not showing up on the order grid	
- Recover Cart problem when multiple items with same configurable parent	
- Order cancelled when same increment id on different store views	
- Duplicated PI Callbacks received cancel the order	
- Server not recovering cart when cancel the transaction	
- Add form validation in PI WITHOUT Form

## [1.2.23] - 2020-04-13
### Fixed
- Fix PI not working with virtual product and guest checkout
- Problem with js calls not being sequential and causing errors on checkout with PI
- Amount is not an integer when trying to make a refund

## [1.2.22] - 2020-03-18
### Changed
- Store SecurityKey in Database when SyncFromApi
- Enhance cart recovery to avoid orders cancelling when customer goes to checkout/cart

### Fixed
- Round amount when populating amount and currency to avoid 0.01 difference
- Order not available error with FORM

## [1.2.21] - 2020-02-12
### Added
- Compatibility with Magento 2.2.11

### Changed
- Look transaction by vendorTxCode if not VPSTxId when SyncFromApi

### Fixed
- Problem with basket format when using Sage50
- Error while trying to cancel SERVER Authenticate order

## [1.2.20] - 2019-12-19
### Fixed
- Items being canceled when order take more than 15 minutes
- Guest order being created with "Guest" as customer name
- Pi not loading when there are terms and conditions

## [1.2.19] - 2019-11-26
### Added
- Show Fraud information on order grid (3D, Post Code, Address, CV2)

### Changed
- New PI endpoint

### Fixed
- Order failing if using special characters on order id prefix
- Fraud flag showing no flag when 3rd Man and there's no Fraud Rule

### Security
- Encrypt PI callback URL

## [1.2.18] - 2019-10-28
### Added
- Compatibility with Magento 2.2.10
- Setting to open 3D verification in new window for PI

### Changed
- Sanitize Post Code on PI
- Remove spaces from paRes
- Remove "Load secure credit card form" PI button

### Fixed
- Frontend using Default Config values instead of Store values on Frontend
- Multiple 3D responses problem

## [1.2.17] - 2019-09-18
### Added
- PI support for PSD2 and SCA
- Payment Failed Emails implementation for PI

### Fixed
- Stop the order for try to being captured if txstateid empty
- 0.00 cost products breaks PayPal
- Fix Multi Currency Authenticate invoice using Base Currency amount

## [1.2.16] - 2019-08-07
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

## [1.2.15] - 2019-06-19
### Added
- PI DropIn compatibility with OneStepCheckout

### Fixed
- Module breaks Sales -> Order
- Server defer orders not being cancelled on SagePay
- Problem with submit payment button PI
- PI always selected as default payment method on the checkout

## [1.2.14] - 2019-05-08
### Added
- Explanation message to order view
- Add waiting for score and test fraud flags
- Add CardHolder Name field to PI without DropIn

### Changed
- Update README.md to use url sagepaysuite.gitlab.ebizmarts.com for composer config.

### Fixed
- PI DropIn MOTO problem with multiple storeviews
- Invoice and Refund problem with multi currency site and base currency
- Basket Sage50 doesn't send space character

### Removed
- PHP restrictions on module for M2.1
- Remove cc images from the Pi form

## [1.2.13] - 2019-03-26
### Changed
- On Hold status stop auto-invoice

### Fixed
- Redirect to empty cart fix
- Multi-Currency invoice use base currency amount
- Defer invoice problem with Multi-Store setup
- Repeat problem with Multi-Store setup

## [1.2.12] - 2019-02-05
### Changed
- 3D secure iframe alignment on mobile devices.

### Security
- Encrypt callback URL.

## [1.2.11] - 2019-01-07
### Added
- Invoice confirmation email for Authorise and capture
- Show verification results in payment layout at order details

### Changed
- Server low profile smaller modal window

### Fixed
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

### Removed
- Disable Multishipping payment methods because they dont work

### Fixed
- Problems with PayPal basket and special characters

## [1.2.9] - 2018-10-01
### Added
- PI Defer partial invoice

### Changed
- Read module version from composer file
- Improve error message when transaction fails (SERVER)

### Fixed
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

### Fixed
- Checkout missing request to payment-information
- Unable to continue checkout if button "Load secure credit card form" button is pressed before editing the billing address
- Unable to find quote
- FORM email confirmation adds &CardHolder next to the shipping phone number

## [1.2.7] - 2018-08-06
### Added
- 2.2.5 compatibility.

### Changed
- Hong Kong optional zipcode.

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
- BankAuthCode and TxAuthNo is not saved on the DB.

## [1.2.6] - 2018-04-06
### Fixed
- Form failure StatusDetail inconsistent causes undefined offset.
- Unique Constraint Violation cancelling orders on Form and Server integrations.

## [1.2.5] - 2018-03-22
### Added
- Fraud flags on sales orders grid.

### Changed
- Improve error message when reporting password is incorrect.

### Fixed
- Unserialize use helper objects.
- Minify javascript exception via xml causes problem with tinymce, using plugin now.
- Invalid card on Drop-in, the load secure from button disappears.
- Call to a member function getSagepaysuiteFraudCheck on boolean. Sync from api on backend.
- Call to a member function getBillingAddress on null. Specific countries option with Pi.

## [1.2.4] - 2018-03-01
### Changed
- Improve admin message error when Reporting API signature fails.
- Update Sage Pay Direct label to Sage Pay Pi on admin config page.

### Fixed
- Swagger generation failing because of missing parameter "quote" on webapi.xml.
- Non-decimal currencies (eg: JPY) sending wrong amount to Sage Pay Pi and failing on Server/Form.

## [1.2.3] - 2018-02-13
### Fixed
- Concrete class parameter breaks SOAP API.
- Upgrade schema vendorname column not defined.

## [1.2.2] - 2018-01-30
### Fixed
- Fix bad class import on PiRequestManagement.
  
## [1.2.1] - 2018-01-16
### Changed
- Split database support out of the box.
- Updated en_GB.csv translation file.

### Fixed
- Parent page already initialised Direct Drop-in.
- Failed MOTO orders send confirmation email.
- There was an error with Sage Pay transaction : Notice: Undefined variable: result.
- Quote id repeated if order is canceled by customer SERVER.
- Money taken for auto cancelled order.

## [1.2.0] - 2017-09-28
### Added
- First release with Magento 2.2.0 compatibility.

[1.2.40]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/1.2.40
[1.2.39]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/1.2.39
[1.2.27.1]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/1.2.27.1
[1.2.27]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/1.2.27
[1.2.26]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/1.2.26
[1.2.25]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/1.2.25
[1.2.24]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/1.2.24
[1.2.23]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/1.2.23
[1.2.22]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/1.2.22
[1.2.21]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/1.2.21
[1.2.20]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/1.2.20
[1.2.19]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/1.2.19
[1.2.18]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/1.2.18
[1.2.17]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/1.2.17
[1.2.16]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/1.2.16
[1.2.15]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/1.2.15
[1.2.14]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/1.2.14
[1.2.13]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/1.2.13
[1.2.12]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/1.2.12
[1.2.11]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/1.2.11
[1.2.10]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/1.2.10
[1.2.9]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/1.2.9
[1.2.8]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/1.2.8
[1.2.7]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/1.2.7
[1.2.6]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/1.2.6
[1.2.5]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/1.2.5
[1.2.4]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/1.2.4
[1.2.3]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/1.2.3
[1.2.2]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/v1.2.2
[1.2.1]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/v1.2.1
[1.2.0]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/v1.2.0
