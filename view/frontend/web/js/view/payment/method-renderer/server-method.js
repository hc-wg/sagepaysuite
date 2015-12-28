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

        $(document).ready(function () {
            var serverConfig = window.checkoutConfig.payment.ebizmarts_sagepaysuiteserver;
            if(serverConfig && !serverConfig.licensed){
                $("#payment .step-title").after('<div class="message error" style="margin-top: 5px;border: 1px solid red;">WARNING: Your Sage Pay Suite license is invalid.</div>');
            }
        });

        return Component.extend({
            defaults: {
                template: 'Ebizmarts_SagePaySuite/payment/server-form'
            },
            getCode: function () {
                return 'sagepaysuiteserver';
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

                        var serviceUrl = url.build('sagepaysuite/server/serverRequest');

                        //send server post request
                        storage.get(serviceUrl).done(
                            function (response) {

                                if (response.success) {

                                    $('#sagepaysuiteserver-actions-toolbar').css('display','none');
                                    $('#payment_form_sagepaysuiteserver .payment-method-note').css('display','none');

                                    $('#sagepaysuiteserver_embed_iframe_container').html("<iframe class='main-iframe' src='" +
                                        response.response.data.NextURL + "'></iframe>");

                                    fullScreenLoader.stopLoader();

                                } else {
                                    self.showPaymentError(response.error_message);
                                }
                            }
                        ).fail(
                            function (response) {
                                self.showPaymentError("Unable to submit to Sage Pay. Please try another payment option.");
                            }
                        );

                    }
                ).fail(
                    function (response) {
                        self.showPaymentError("Unable to save payment method.");
                    }
                );
            },
            showPaymentError: function(message){

                var span = document.getElementById(this.getCode() + '-payment-errors');

                span.innerHTML = message;
                span.style.display="block";

                $('#sagepaysuiteserver-actions-toolbar').css('display','block');
                $('#payment_form_sagepaysuiteserver .payment-method-note').css('display','block');

                fullScreenLoader.stopLoader();
            },
            resetPaymentErrors: function(){
                $('#sagepaysuiteserver-actions-toolbar').css('display','block');
                $('#payment_form_sagepaysuiteserver .payment-method-note').css('display','block');

                var span = document.getElementById(this.getCode() + '-payment-errors');
                span.style.display="none";

            }
        });
    }
);
