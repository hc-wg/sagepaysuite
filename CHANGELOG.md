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

[1.4.3]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/1.4.3
[1.4.2]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/1.4.2
[1.4.1]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/1.4.1
[1.4.0]: https://github.com/ebizmarts/magento2-sage-pay-suite/releases/tag/1.4.0