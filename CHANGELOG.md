## [1.4.40] - 2021-07-07
### Fixed
- Module not recovering cart when PI 3D fails.
- Fraud check failing after Opayo update
- The "ResultInterface" class doesn't exist and the namespace must be specified.

## [1.4.39] - 2021-05-26
### Added
- Compatibility with Magento 2.4.2-p1
- Debug Mode setting
- Prevent customer personal data from logging setting
- Show 3rdMan score and score breakdown on order details
### Changed
- Ask customers if want to save the credit card when they already have tokens
### Fixed
- 0.01 difference when you try to invoice PI Defer orders
- DropIn form not appearing after deleting all tokens on checkout
- The service interface name "Ebizmarts\SagePaySuite\Model\Token\Get" is invalid.
- Invoice created successfully in Magento when transaction was aborted
- PI Authorize and Capture orders not being invoiced
- Recover cart message appearing in product page after successful order with PI and 3D

## [1.4.5.1] - 2021-05-12
### Fixed
- PI with 3D redirecting to cart after checkout

## [1.4.5] - 2021-02-02
### Fixed
- Composer installation problem when requiring Magento vault

## [1.4.4] - 2021-02-01
### Changed
- Added token with vault usage on PI.

### Fixed
- PI repeat with 3Dv2
- Recover cart when session is lost
- Fraud not being retrieved for non default sotres in multi-store setup
- Verification result not showing
- Browser Ipv6 error on PI

## [1.4.3] - 2020-11-24
### Fixed
- 3Dv1 not working with Protocol 4.00 for PI
- PI refund problem with Multi-Store sites
- Duplicated Callbacks received for FORM

## [1.4.2] - 2020-10-27
### Fixed
- Fix duplicate 3D callback and duplicate response for threeDSubmit
- Typo in RecoverCarts.php

## [1.4.1] - 2020-10-06
### Changed
- Server cancel payment redirection to checkout shipping method form

### Fixed
- Added new Order Details fields names in block
- CSP Whitelisting file
- Restriction file added
- PayPal response decrypt issue with PHP7.4
- PayPal POST data fix
- Array key exists fix for PHP7.4
- Fixed unnecesary function calls in restoreCart and Tests
- Quote totals lost on cancel 1200

## [1.4.0] - 2020-08-03
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

[1.4.40]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/1.4.40
[1.4.39]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/1.4.39
[1.4.5.1]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/1.4.5.1
[1.4.5]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/1.4.5
[1.4.4]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/1.4.4
[1.4.3]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/1.4.3
[1.4.2]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/1.4.2
[1.4.1]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/1.4.1
[1.4.0]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/1.4.0
