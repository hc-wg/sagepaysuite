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
        'Magento_Checkout/js/model/full-screen-loader'
    ],
    function ($, Component, storage, url, urlBuilder, customer, quote, fullScreenLoader) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Ebizmarts_SagePaySuite/payment/form-form'
            },
            getCode: function () {
                return 'sagepaysuiteform';
            },
            /** Init observable variables */
            initObservable: function () {
                return this;
            },
            /** Returns payment information data */
            getData: function() {
                return $.extend(true, this._super(), {'additional_data': null});
            },
            preparePayment: function(){

                fullScreenLoader.startLoader();

                var self = this;
                self.resetPaymentErrors();

                var serviceUrl,
                    payload,
                    paymentData = quote.paymentMethod();

                /**
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

                        var serviceUrl = url.build('sagepaysuite/form/formRequest');

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

                    }
                ).fail(
                    function (response) {
                        self.showPaymentError("Unable to save payment method.");
                    }
                );

                return false;

            },
            showPaymentError: function(message){

                var span = document.getElementById(this.getCode() + '-payment-errors');

                span.innerHTML = message;
                span.style.display="block";

                fullScreenLoader.stopLoader();
            },
            resetPaymentErrors: function(){
                var span = document.getElementById(this.getCode() + '-payment-errors');
                span.style.display="none";

            }
        });
    }
);
