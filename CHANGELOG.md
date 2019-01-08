1.3.1 (07/01/2019)
=============
**This release requires Magento 2.3.x.**

**Implemented features:**

- Invoice confirmation email for Authorise and capture [#607]

**Implemented enhancements:**

- Server low profile smaller modal window [#601]

 **Fixed bugs:**
- Cancel or Void a Defer order without invoice [#581]
- Refund problem on multi-currency sites [#578]
- PI without DropIn problem when you enter a wrong CVN [#585]
- Problem with refunds on multi-sites using two vendors [#591]
- Exception thrown when open Fraud report [#594]
- Basket XML constraint fix [#617]
- Magento's sign appearing when click fraud cell [#626]

1.3.0 (04/12/2018)
=============
**This release requires Magento 2.3.x.**

**Fixed bugs:**

- Magento not running schema updates. Switching to Schema patches. [#574]
- New CSRF checks rejecting callbacks [#566] [#568] [#567]