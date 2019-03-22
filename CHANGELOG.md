## [1.3.3] - 2019-03-25
### Changed
- On Hold status stop auto-invoice

### Fixed
- Conflict problems on db_schema
- Redirect to empty cart fix
- Multi-Currency invoice use base currency amount
- Defer invoice problem with Multi-Store setup
- Repeat problem with Multi-Store setup

## [1.3.2] - 2019-02-05
### Changed
- 3D secure iframe alignment on mobile devices
- last_trans_id field on table sales_order_payment truncated to 32, causing error on callbacks

### Security
- Encrypt callback URL

## [1.3.1] - 2019-01-07
### Added
- Invoice confirmation email for Authorise and capture

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

## [1.3.0] - 2018-12-04
### Fixed
- Magento not running schema updates. Switching to Schema patches
- New CSRF checks rejecting callbacks
