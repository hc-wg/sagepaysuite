/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'Magento_Payment/js/view/payment/cc-form',
        'mage/storage',
        'mage/url',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Ui/js/modal/modal',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/model/url-builder',
        'Magento_Checkout/js/model/quote'

    ],
    function ($, Component, storage, url, customer, placeOrderAction, fullScreenLoader, modal, additionalValidators, urlBuilder, quote) {
        'use strict';

        $(document).ready(function () {
            var piConfig = window.checkoutConfig.payment.ebizmarts_sagepaysuitepi;
            if (piConfig && !piConfig.licensed) {
                $("#payment .step-title").after('<div class="message error" style="margin-top: 5px;border: 1px solid red;">WARNING: Your Sage Pay Suite license is invalid.</div>');
            }
        });

        return Component.extend({
            placeOrderHandler: null,
            validateHandler: null,
            modal: null,
            defaults: {
                template: 'Ebizmarts_SagePaySuite/payment/pi-form',
                creditCardType: '',
                creditCardExpYear: '',
                creditCardExpMonth: '',
                creditCardLast4: '',
                merchantSessionKey: '',
                cardIdentifier: '',
            },
            setPlaceOrderHandler: function (handler) {
                this.placeOrderHandler = handler;
            },
            setValidateHandler: function (handler) {
                this.validateHandler = handler;
            },
            getCode: function () {
                return 'sagepaysuitepi';
            },
            dropInEnabled: function () {
                return window.checkoutConfig.payment.ebizmarts_sagepaysuitepi.dropin == 1;
            },
            isActive: function () {
                return true;
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

                var jsName = 'sagepayjs_';
                if (self.dropInEnabled()) {
                    jsName = jsName + 'dropin_';
                }

                requirejs([jsName + window.checkoutConfig.payment.ebizmarts_sagepaysuitepi.mode], function () {
                    storage.post(
                        serviceUrl,
                        JSON.stringify(payload)
                    ).done(
                        function () {
                            serviceUrl = urlBuilder.createUrl('/sagepay/pi-msk', {});

                            //generate merchant session key
                            storage.get(serviceUrl).done(
                                function (response) {

                                    if (response.success) {
                                        self.sagepayTokeniseCard(response.response);
                                    } else {
                                        self.showPaymentError(response.error_message);
                                    }
                                }
                            ).fail(
                                function (response) {
                                    self.showPaymentError("Unable to create Sage Pay merchant session key.");
                                }
                            );
                        }
                    ).fail(
                        function (response) {
                            self.showPaymentError("Unable to save billing address.");
                        }
                    );
                });

                return false;
            },
            sagepayTokeniseCard: function (merchant_session_key) {

                var self = this;

                if (self.dropInEnabled()) {
                    if (merchant_session_key) {
                        self.isPlaceOrderActionAllowed(false);
                        self.merchantSessionKey = merchant_session_key;
                        sagepayCheckout({
                                merchantSessionKey: merchant_session_key,
                                onTokenise: function (tokenisationResult) {
                                    if (tokenisationResult.success) {
                                        self.cardIdentifier     = tokenisationResult.cardIdentifier;
                                        self.creditCardType     = "";
                                        self.creditCardExpYear  = "";
                                        self.creditCardExpMonth = "";
                                        self.creditCardLast4    = "";
                                        try {
                                            self.placeTransaction();
                                        } catch (err) {
                                            console.log(err);
                                            self.showPaymentError("Unable to initialize Sage Pay payment method, please use another payment method.");
                                        }
                                    } else {
                                        //console.error('Tokenisation failed', tokenisationResult.error.errorMessage);
                                    }
                                }
                            }
                        ).form();
                        fullScreenLoader.stopLoader();

                        document.getElementById('submit_dropin_payment').style.display = "block";
                    }
                }
                else {
                    if (merchant_session_key) {
                        //create token form
                        var token_form = document.getElementById(self.getCode() + '-token-form');
                        token_form.elements[0].setAttribute('value', merchant_session_key);
                        token_form.elements[1].setAttribute('value', "Owner");
                        token_form.elements[2].setAttribute('value', document.getElementById(self.getCode() + '_cc_number').value);
                        var expiration = document.getElementById(self.getCode() + '_expiration').value;
                        expiration = expiration.length == 1 ? "0" + expiration : expiration;
                        expiration += document.getElementById(self.getCode() + '_expiration_yr').value.substring(2, 4);
                        token_form.elements[3].setAttribute('value', expiration);
                        token_form.elements[4].setAttribute('value', document.getElementById(self.getCode() + '_cc_cid').value);

                        try {
                            //request token
                            Sagepay.tokeniseCardDetails(token_form, function (status, response) {

                                if (status === 201) {
                                    self.creditCardType     = response.cardType;
                                    self.creditCardExpYear  = document.getElementById(self.getCode() + '_expiration_yr').value;
                                    self.creditCardExpMonth = document.getElementById(self.getCode() + '_expiration').value;
                                    self.creditCardLast4    = document.getElementById(self.getCode() + '_cc_number').value.slice(-4);
                                    self.merchantSessionKey = merchant_session_key;
                                    self.cardIdentifier     = response.cardIdentifier;

                                    try {
                                        self.placeTransaction();
                                    } catch (err) {
                                        self.showPaymentError("Unable to initialize Sage Pay payment method, please use another payment method.");
                                        console.log(err);
                                    }
                                } else {
                                    var errorMessage = "Unable to initialize Sage Pay payment method, please use another payment method.";
                                    console.log(response);
                                    if (response.responseJSON) {
                                        response = response.responseJSON;
                                    }
                                    if (response && response.error && response.error.message) {
                                        errorMessage = response.error.message;
                                    } else if (response && response.errors && response.errors[0] && response.errors[0].clientMessage) {
                                        errorMessage = response.errors[0].clientMessage;
                                    }
                                    self.showPaymentError(errorMessage);
                                }
                            });
                        } catch (err) {
                            console.log(err);
                            //errorProcessor.process(err);
                            alert("Unable to initialize Sage Pay payment method, please use another payment method.");
                        }
                    }
                }
            },
            placeTransaction: function () {

                var self = this;

                var serviceUrl = null;
                if (customer.isLoggedIn()) {
                    serviceUrl = urlBuilder.createUrl('/sagepay/pi', {});
                } else {
                    serviceUrl = urlBuilder.createUrl('/sagepay-guest/pi', {});
                }

                var callbackUrl = url.build('sagepaysuite/pi/callback3D');

                var payload = {
                    "cartId": quote.getQuoteId(),
                    "requestData": {
                        "merchant_session_key": self.merchantSessionKey,
                        "card_identifier": self.cardIdentifier,
                        "cc_type": self.creditCardType,
                        "cc_exp_month": self.creditCardExpMonth,
                        "cc_exp_year": self.creditCardExpYear,
                        "cc_last_four": self.creditCardLast4
                    }
                };

                if (self.dropInEnabled()) {
                    fullScreenLoader.startLoader();
                }

                storage.post(
                    serviceUrl,
                    JSON.stringify(payload)
                ).done(
                    function (response) {

                        if (self.dropInEnabled()) {
                            fullScreenLoader.stopLoader();
                        }

                        if (response.success) {
                            if (response.status == "Ok") {

                                /**
                                 * transaction authenticated, redirect to success
                                 */

                                window.location.replace(url.build('checkout/onepage/success/'));
                            } else if (response.status == "3DAuth") {

                                /**
                                 * 3D secure authentication required
                                 */

                                    //add transactionId param to callback
                                callbackUrl += "?transactionId=" + response.transaction_id +
                                    "&orderId=" + response.order_id +
                                    "&quoteId=" + response.quote_id;

                                self.open3DModal();

                                var form3D = document.getElementById(self.getCode() + '-3Dsecure-form');
                                form3D.setAttribute('target', self.getCode() + '-3Dsecure-iframe');
                                form3D.setAttribute('action', response.acs_url);
                                form3D.elements[0].setAttribute('value', response.par_eq);
                                form3D.elements[1].setAttribute('value', callbackUrl);
                                form3D.elements[2].setAttribute('value', response.transaction_id);
                                form3D.submit();

                                if (!self.dropInEnabled()) {
                                    fullScreenLoader.stopLoader();
                                }
                            } else {
                                console.log(response);
                                self.showPaymentError("Invalid Sage Pay response, please use another payment method.");
                            }
                        } else {
                            self.showPaymentError(response.error_message);
                        }
                        }
                ).fail(
                    function (response) {
                        self.showPaymentError("Unable to capture Sage Pay transaction, please use another payment method.");
                    }
                );
            },

            /**
             * Create 3D modal
             */
            open3DModal: function () {
                this.modal = $('<iframe id="' + this.getCode() + '-3Dsecure-iframe" name="' + this.getCode() + '-3Dsecure-iframe"></iframe>').modal({
                    modalClass: 'sagepaysuite-modal',
                    title: "Sage Pay 3D Secure Authentication",
                    type: 'slide',
                    responsive: true,
                    clickableOverlay: false,
                    closeOnEscape: false,
                    buttons: []
                });
                this.modal.modal('openModal');
            },

            /**
             * @override
             */
            getData: function () {
                return {
                    'method': this.getCode(),
                    'additional_data': {
                        'cc_last4': this.creditCardLast4,
                        'merchant_session_key': this.merchantSessionKey,
                        'card_identifier': this.cardIdentifier,
                        'cc_type': this.creditCardType,
                        'cc_exp_year': this.creditCardExpYear,
                        'cc_exp_month': this.creditCardExpMonth
                    }
                };
            },

            /**
             * Place order.
             */
            placeOrder: function (data, event) {
                if (event) {
                    event.preventDefault();
                }
                var self = this,
                    placeOrder,
                    emailValidationResult = customer.isLoggedIn(),
                    loginFormSelector = 'form[data-role=email-with-possible-login]';
                if (!customer.isLoggedIn()) {
                    $(loginFormSelector).validation();
                    emailValidationResult = Boolean($(loginFormSelector + ' input[name=username]').valid());
                }
                if (emailValidationResult && this.validate()) {
                    this.isPlaceOrderActionAllowed(false);
                    placeOrder = placeOrderAction(this.getData(), false);

                    $.when(placeOrder).done(
                        function (order_id, response, extra) {
                            console.log("success");
                            window.location.replace(url.build('checkout/onepage/success/'));
                        }
                    ).fail(
                        function (response) {
                            self.isPlaceOrderActionAllowed(true);

                            var error_message = "Unable to capture payment. Please refresh the page and try again.";
                            if (response && response.responseJSON && response.responseJSON.message) {
                                error_message = response.responseJSON.message;
                            }
                            self.showPaymentError(error_message);
                        }
                    );
                    return true;
                }
                return false;
            },
            showPaymentError: function (message) {
                var self = this;

                var span = document.getElementById('sagepaysuitepi-payment-errors');

                span.innerHTML = message;
                span.style.display = "block";

                fullScreenLoader.stopLoader();
            },
            resetPaymentErrors: function () {
                var span = document.getElementById('sagepaysuitepi-payment-errors');

                if (null != span) {
                    span.style.display = "none";
                }
            }
        });
    }
);