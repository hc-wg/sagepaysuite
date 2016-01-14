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
            if (serverConfig) {

                if (!serverConfig.licensed) {
                    $("#payment .step-title").after('<div class="message error" style="margin-top: 5px;border: 1px solid red;">WARNING: Your Sage Pay Suite license is invalid.</div>');
                }
            }
        });

        return Component.extend({
            defaults: {
                template: 'Ebizmarts_SagePaySuite/payment/server-form',
                use_token: false,
                save_token: false
            },
            getCode: function () {
                return 'sagepaysuiteserver';
            },
            /** Returns payment information data */
            getData: function () {
                return $.extend(true, this._super(), {'additional_data': null});
            },
            preparePayment: function () {

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

                        var serviceUrl = url.build('sagepaysuite/server/request');
                        var save_token = self.save_token && !self.use_token;
                        var token = null;
                        if (self.use_token) {
                            var tokens = window.checkoutConfig.payment.ebizmarts_sagepaysuiteserver.tokens;
                            for (var i = 0; i < tokens.length; i++) {
                                if ($('#' + self.getCode() + '-token-' + tokens[i].id).prop("checked") == true) {
                                    token = tokens[i].token;
                                    break;
                                }
                            }
                            if (token == null) {
                                self.showPaymentError("Please select the card to be used form the list");
                                return;
                            }
                        }

                        //send server post request
                        storage.post(serviceUrl,
                            JSON.stringify({
                                save_token: save_token,
                                token: token
                            })).done(
                            function (response) {

                                if (response.success) {

                                    $('#sagepaysuiteserver-actions-toolbar').css('display', 'none');
                                    $('#payment_form_sagepaysuiteserver .payment-method-note').css('display', 'none');
                                    $('#' + self.getCode() + '-tokens').css('display', 'none');


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
            showPaymentError: function (message) {

                var span = document.getElementById(this.getCode() + '-payment-errors');

                span.innerHTML = message;
                span.style.display = "block";

                $('#sagepaysuiteserver-actions-toolbar').css('display', 'block');
                $('#payment_form_sagepaysuiteserver .payment-method-note').css('display', 'block');

                fullScreenLoader.stopLoader();
            },
            resetPaymentErrors: function () {
                $('#sagepaysuiteserver-actions-toolbar').css('display', 'block');
                $('#payment_form_sagepaysuiteserver .payment-method-note').css('display', 'block');

                var span = document.getElementById(this.getCode() + '-payment-errors');
                span.style.display = "none";

            },
            addNewCard: function () {
                this.use_token = false;
                $('#' + this.getCode() + '-tokens .token-list').hide();
                $('#' + this.getCode() + '-tokens .add-new-card-link').hide();
                $('#' + this.getCode() + '-tokens .using-new-card-message').show();
                $('#' + this.getCode() + '-tokens .use-saved-card-link').show();

            },
            useSavedTokens: function () {
                this.use_token = true;
                $('#' + this.getCode() + '-tokens .token-list').show();
                $('#' + this.getCode() + '-tokens .use-saved-card-link').hide();
                $('#' + this.getCode() + '-tokens .using-new-card-message').hide();
                $('#' + this.getCode() + '-tokens .add-new-card-link').show();

            },
            deleteToken: function (id) {

            },
            customerHasTokens: function () {
                if (window.checkoutConfig.payment.ebizmarts_sagepaysuiteserver) {
                    if (window.checkoutConfig.payment.ebizmarts_sagepaysuiteserver.token_enabled &&
                        window.checkoutConfig.payment.ebizmarts_sagepaysuiteserver.token_enabled == true) {
                        this.save_token = true;
                        if (window.checkoutConfig.payment.ebizmarts_sagepaysuiteserver.tokens &&
                            window.checkoutConfig.payment.ebizmarts_sagepaysuiteserver.tokens.length > 0) {
                            this.use_token = true;
                            return true;
                        } else {
                            this.use_token = false;
                            return false;
                        }
                    } else {
                        this.save_token = false;
                        this.use_token = false;
                        return false;
                    }
                }
                return false;
            },
            getCustomerTokens: function () {
                return window.checkoutConfig.payment.ebizmarts_sagepaysuiteserver.tokens;
            },
            getIcons: function (type) {
                switch (type) {
                    case 'VISA':
                    case 'DELTA':
                    case 'UKE':
                        return window.checkoutConfig.payment.ccform.icons["VI"].url;
                        break;
                    case 'MC':
                    case 'MCDEBIT':
                        return window.checkoutConfig.payment.ccform.icons["MC"].url;
                        break;
                    case 'MAESTRO':
                        return window.checkoutConfig.payment.ccform.icons["MD"].url;
                        break;
                    case 'AMEX':
                        return window.checkoutConfig.payment.ccform.icons["AE"].url;
                        break;
                    case 'DC':
                        return window.checkoutConfig.payment.ccform.icons["DC"].url;
                        break;
                    case 'JCB':
                        return window.checkoutConfig.payment.ccform.icons["JCB"].url;
                        break;
                    default:
                        return "";
                        break;
                }
            }
        });
    }
);
