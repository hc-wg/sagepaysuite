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
        'mage/storage',
        'mage/url',
        'Magento_Checkout/js/model/url-builder',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/payment/additional-validators'
    ],
    function ($, Component, storage, url, urlBuilder, customer, quote, fullScreenLoader, additionalValidators) {
        'use strict';

        $(document).ready(function () {
            var paypalConfig = window.checkoutConfig.payment.ebizmarts_sagepaysuitepaypal;
            if (paypalConfig && !paypalConfig.licensed) {
                $("#payment .step-title").after('<div class="message error" style="margin-top: 5px;border: 1px solid red;">WARNING: Your Sage Pay Suite license is invalid.</div>');
            }
        });

        return Component.extend({
            defaults: {
                template: 'Ebizmarts_SagePaySuite/payment/paypal-form'
            },
            getCode: function () {
                return 'sagepaysuitepaypal';
            },
            /** Returns payment information data */
            getData: function () {
                return $.extend(true, this._super(), {'additional_data': null});
            },
            preparePayment: function () {

                var self = this;
                self.resetPaymentErrors();

                //validations
                if (!this.validate() || !additionalValidators.validate()) {
                    return false;
                }

                fullScreenLoader.startLoader();

                /**
                 * Save billing address
                 * Checkout for guest and registered customer.
                 */
                var serviceUrl,
                    payload;
                if (!customer.isLoggedIn()) {
                    serviceUrl = urlBuilder.createUrl('/guest-carts/:cartId/billing-address', {
                        cartId: quote.getQuoteId()
                    });
                    payload = {
                        cartId: quote.getQuoteId(),
                        address: quote.billingAddress()
                    };
                } else {
                    serviceUrl = urlBuilder.createUrl('/carts/mine/billing-address', {});
                    payload = {
                        cartId: quote.getQuoteId(),
                        address: quote.billingAddress()
                    };
                }

                return storage.post(
                    serviceUrl, JSON.stringify(payload)
                ).done(
                    function () {
                        var paymentData = {method: self.getCode()};

                        /**
                         * Set payment method
                         * Checkout for guest and registered customer.
                         */
                        if (!customer.isLoggedIn()) {
                            serviceUrl = urlBuilder.createUrl('/guest-carts/:cartId/selected-payment-method', {
                                cartId: quote.getQuoteId()
                            });
                            payload = {
                                cartId: quote.getQuoteId(),
                                method: paymentData
                            };
                        } else {
                            serviceUrl = urlBuilder.createUrl('/carts/mine/selected-payment-method', {});
                            payload = {
                                cartId: quote.getQuoteId(),
                                method: paymentData
                            };
                        }
                        return storage.put(
                            serviceUrl, JSON.stringify(payload)
                        ).done(
                            function () {

                                var serviceUrl = url.build('sagepaysuite/paypal/request');

                                //generate crypt and form data
                                storage.get(serviceUrl).done(
                                    function (response) {

                                        if (response.success) {
                                            if (response.response.data.PayPalRedirectURL) {
                                                window.location.href = response.response.data.PayPalRedirectURL;
                                            } else {
                                                self.showPaymentError("Invalid response from PayPal, please try another payment method");
                                            }
                                        } else {
                                            self.showPaymentError(response.error_message);
                                        }
                                    }
                                ).fail(
                                    function (response) {
                                        self.showPaymentError("Unable to submit form to PayPal.");
                                    }
                                );
                            }
                        ).fail(
                            function (response) {
                                self.showPaymentError("Unable to save payment method.");
                            }
                        );
                    }
                ).fail(
                    function (response) {
                        self.showPaymentError("Unable to save billing address.");
                    }
                );
            },
            showPaymentError: function (message) {

                var span = document.getElementById(this.getCode() + '-payment-errors');

                span.innerHTML = message;
                span.style.display = "block";

                fullScreenLoader.stopLoader();
            },
            resetPaymentErrors: function () {
                var span = document.getElementById(this.getCode() + '-payment-errors');
                span.style.display = "none";

            }
        });
    }
);
