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
            var formConfig = window.checkoutConfig.payment.ebizmarts_sagepaysuiteform;
            if (formConfig && !formConfig.licensed) {
                $("#payment .step-title").after('<div class="message error" style="margin-top: 5px;border: 1px solid red;">WARNING: Your Sage Pay Suite license is invalid.</div>');
            }
        });

        return Component.extend({
            defaults: {
                template: 'Ebizmarts_SagePaySuite/payment/form-form'
            },
            getCode: function () {
                return 'sagepaysuiteform';
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
                    serviceUrl,
                    JSON.stringify(payload)
                ).done(
                    function () {
                    
                        var paymentData = {method:self.getCode()};

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
                            serviceUrl,
                            JSON.stringify(payload)
                        ).done(function () {

                                var serviceUrl = url.build('sagepaysuite/form/request');

                                //generate crypt and form data
                                storage.get(serviceUrl).done(
                                    function (response) {

                                        if (response.success) {
                                            //set form data and submit
                                            var form_form = document.getElementById(self.getCode() + '-form');
                                            form_form.setAttribute('action',response.redirect_url);
                                            form_form.elements[0].setAttribute('value', response.vps_protocol);
                                            form_form.elements[1].setAttribute('value', response.tx_type);
                                            form_form.elements[2].setAttribute('value', response.vendor);
                                            form_form.elements[3].setAttribute('value', response.crypt);

                                            form_form.submit();
                                        } else {
                                            self.showPaymentError(response.error_message);
                                        }
                                    }
                                ).fail(
                                    function (response) {
                                        self.showPaymentError("Unable to submit form to Sage Pay.");
                                    }
                                );
                            }).fail(
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
                span.style.display="block";

                fullScreenLoader.stopLoader();
            },
            resetPaymentErrors: function () {
                var span = document.getElementById(this.getCode() + '-payment-errors');
                span.style.display="none";

            }
        });
    }
);
