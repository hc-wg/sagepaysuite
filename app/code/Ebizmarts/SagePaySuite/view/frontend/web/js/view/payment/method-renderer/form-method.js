/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Ebizmarts_SagePaySuite/js/action/set-payment-method'
    ],
    function ($, Component, setPaymentMethodAction) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Ebizmarts_SagePaySuite/payment/form-checkout'
            },
            /** Init observable variables */
            initObservable: function () {
//                this._super()
//                    .observe('billingAgreement');
                return this;
            },
            /** Returns payment information data */
            getData: function() {
                var parent = this._super(),
                    additionalData = null;
                return $.extend(true, parent, {'additional_data': additionalData});
            },
            /** Redirect to sagepay form */
            continueToSagePayForm: function() {
                this.selectPaymentMethod();
                setPaymentMethodAction();
                return false;
            }
        });
    }
);
