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
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/action/set-payment-information'
    ],
    function ($, Component, storage, url, customer, placeOrderAction, fullScreenLoader, modal, additionalValidators, urlBuilder, quote, customerData, setPaymentInformation) {
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
                dropInInstance: null
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
            sagepaySetForm: function () {
                var self = this;
                if (document.getElementById('sagepaysuitepi').checked) {
                    this.selectPaymentMethod();
                }
                self.defaultStateForm();
            },
            isOneStepCheckout: function () {
                return ($('#iosc-summary').length > 0);
            },
            selectPaymentMethod: function () {
                var self = this;
                if (self.isOneStepCheckout()) { //OneStepCheckout, populate cc fields on radio check.
                    self.preparePayment();
                }
                return this._super();
            },
            getRemoteJsName: function () {
                var self = this;
                var jsName = 'sagepayjs_';
                if (self.dropInEnabled()) {
                    jsName = jsName + 'dropin_';
                }
                return jsName;
            },
            getConfiguredMode: function () {
                return window.checkoutConfig.payment.ebizmarts_sagepaysuitepi.mode;
            },
            getPostCartsUrl: function () {
                var serviceUrl;
                if (!customer.isLoggedIn()) {
                    serviceUrl = urlBuilder.createUrl('/guest-carts/:cartId/billing-address', {
                        cartId: quote.getQuoteId()
                    });
                } else {
                    serviceUrl = urlBuilder.createUrl('/carts/mine/billing-address', {});
                }
                return serviceUrl;
            },
            createMerchantSessionKey: function () {
                var self = this;
                storage.get(urlBuilder.createUrl('/sagepay/pi-msk', {})).done(
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
            },
            savePaymentInfo: function () {
                var self = this;

                $.when(
                    setPaymentInformation(this.messageContainer, self.getData())
                ).done(
                    function (response) {
                        if (response === true) {
                            self.createMerchantSessionKey();
                        } else {
                            self.showPaymentError("Unable to save payment info.");
                        }
                    }
                ).fail(
                    function (response) {
                        if (response.responseJSON) {
                            self.showPaymentError(response.responseJSON.message);
                        } else {
                            self.showPaymentError("Unable to save payment info.");
                        }
                    }
                );
            },
            preparePayment: function () {
                var self = this;

                self.destroyInstanceSagePay();

                self.resetPaymentErrors();

                //validations
                if (!this.validate() || !additionalValidators.validate()) {
                    return false;
                }

                fullScreenLoader.startLoader();

                var serviceUrl = self.getPostCartsUrl();

                var payload = {
                    cartId: quote.getQuoteId(),
                    address: quote.billingAddress()
                };

                requirejs([self.getRemoteJsName() + self.getConfiguredMode()], function () {
                    storage.post(
                        serviceUrl,
                        JSON.stringify(payload)
                    ).done(
                        function () {
                            self.savePaymentInfo();
                        }
                    ).fail(
                        function (response) {
                            self.showPaymentError("Unable to save billing address.");
                        }
                    );
                });

                return false;
            },
            tokenisationAuthenticationFailed: function (tokenisationResult) {
                return tokenisationResult.error.errorCode === 1002;
            },
            tokenise: function () {
                var self = this;
                if (self.dropInInstance !== null) {
                    self.dropInInstance.tokenise();
                }
            },
            destroyInstanceSagePay: function () {
                var self = this;
                if (!self.dropInEnabled()) {
                    return;
                }

                if (self.dropInInstance !== null) {
                    self.dropInInstance.destroy();
                    self.dropInInstance = null;
                }

                self.isPlaceOrderActionAllowed(true);
                $("#submit_dropin_payment").css("display", "none");
            },
            defaultStateForm: function () {
                $("#payment-iframe").css("display", "none");
                $("#sp-container").css("display", "none");
                $("#load-dropin-form-button").css("display", "block");
                $("#submit_dropin_payment").css("display", "none");
            },
            isPlaceOrderActionAllowed: function (allowedParam) {
                var self = this;
                if (typeof allowedParam  === 'undefined') {
                    return quote.billingAddress() != null;
                }
                return allowedParam;
            },
            sagepayTokeniseCard: function (merchant_session_key) {

                var self = this;

                if (self.dropInEnabled()) {
                    if (merchant_session_key) {
                        self.isPlaceOrderActionAllowed(false);
                        self.merchantSessionKey = merchant_session_key;
                        self.dropInInstance = sagepayCheckout({
                                merchantSessionKey: merchant_session_key,
                                onTokenise: function (tokenisationResult) {
                                    if (tokenisationResult.success) {
                                        self.cardIdentifier     = tokenisationResult.cardIdentifier;
                                        self.creditCardType     = "";
                                        self.creditCardExpYear  = 0;
                                        self.creditCardExpMonth = 0;
                                        self.creditCardLast4    = 0;
                                        try {
                                            self.placeTransaction();
                                        } catch (err) {
                                            console.log(err);
                                            self.showPaymentError("Unable to initialize Sage Pay payment method, please use another payment method.");
                                        }
                                    } else {
                                        //Check if it is "Authentication failed"
                                        if (self.tokenisationAuthenticationFailed(tokenisationResult)) {
                                            self.destroyInstanceSagePay();
                                            self.resetPaymentErrors();
                                        } else {
                                            self.showPaymentError('Tokenisation failed', tokenisationResult.error.errorMessage);
                                        }
                                    }
                                }
                            });
                        self.dropInInstance.form();
                        fullScreenLoader.stopLoader();

                        $("#payment-iframe").css("display", "block");
                        $("#sp-container").css("display", "block");
                        $("#submit_dropin_payment").css("display", "block");
                        $("#load-dropin-form-button").css("display", "none");
                        $("#billing-address-same-as-shipping-sagepaysuitepi").change(function () {
                             self.defaultStateForm();
                        });
                    }
                } else {
                    if (merchant_session_key) {
                        //create token form
                        var token_form = document.getElementById(self.getCode() + '-token-form');
                        token_form.elements[0].setAttribute('value', merchant_session_key);
                        token_form.elements[1].setAttribute('value', document.getElementById(self.getCode() + '_cardholder').value);
                        token_form.elements[2].setAttribute('value', document.getElementById(self.getCode() + '_cc_number').value);
                        var expiration = document.getElementById(self.getCode() + '_expiration').value;
                        expiration = expiration.length === 1 ? "0" + expiration : expiration;
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
                                    }
                                } else {
                                    var errorMessage = "Unable to initialize Sage Pay payment method, please use another payment method.";
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
                            alert("Unable to initialize Sage Pay payment method, please use another payment method.");
                        }
                    }
                }
            },
            getPlaceTransactionUrl: function () {
                var serviceUrl = null;
                if (customer.isLoggedIn()) {
                    serviceUrl = urlBuilder.createUrl('/sagepay/pi', {});
                } else {
                    serviceUrl = urlBuilder.createUrl('/sagepay-guest/pi', {});
                }
                return serviceUrl;
            }, placeTransaction: function () {
                var self = this;

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

                var serviceUrl = self.getPlaceTransactionUrl();
                storage.post(
                    serviceUrl,
                    JSON.stringify(payload)
                ).done(
                    function (response) {

                        if (self.dropInEnabled()) {
                            fullScreenLoader.stopLoader();
                        }

                        if (response.success) {

                            if (response.status === "Ok") {

                                /**
                                 * transaction authenticated, redirect to success
                                 */
                                customerData.invalidate(['cart']);

                                if (!customer.isLoggedIn()) {
                                    customerData.invalidate(['checkout-data']);
                                }

                                window.location.replace(url.build('checkout/onepage/success/'));
                            } else if (response.status === "3DAuth") {


                                customerData.invalidate(['cart']);

                                if (!customer.isLoggedIn()) {
                                    customerData.invalidate(['checkout-data']);
                                }

                                /**
                                 * 3D secure authentication required
                                 */

                                //add transactionId param to callback
                                callbackUrl += "?transactionId=" + response.transaction_id +
                                    "&orderId=" + response.order_id +
                                    "&quoteId=" + response.quote_id;

                                //Build 3D form.
                                var form3D = document.getElementById(self.getCode() + '-3Dsecure-form');
                                form3D.setAttribute('action', response.acs_url);
                                form3D.elements[0].setAttribute('value', response.par_eq);
                                form3D.elements[1].setAttribute('value', callbackUrl);
                                form3D.elements[2].setAttribute('value', response.transaction_id);

                                if (!self.sagePayIsMobile()) {
                                    self.open3DModal();
                                    form3D.setAttribute('target', self.getCode() + '-3Dsecure-iframe');
                                }

                                form3D.submit();

                                if (!self.dropInEnabled()) {
                                    fullScreenLoader.stopLoader();
                                }
                            } else {
                                self.showPaymentError("Invalid Sage Pay response, please use another payment method.");
                            }
                        } else {
                            self.showPaymentError(response.error_message);
                            if (self.dropInEnabled()) {
                                self.destroyInstanceSagePay();
                            }
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
            sagePayIsMobile: function () {
            return (navigator.userAgent.match(/BlackBerry/i) ||
                navigator.userAgent.match(/webOS/i) ||
                navigator.userAgent.match(/Android/i) ||
                navigator.userAgent.match(/iPhone/i) ||
                navigator.userAgent.match(/iPod/i) ||
                navigator.userAgent.match(/iPad/i));
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
                self.defaultStateForm();
            },
            resetPaymentErrors: function () {
                var span = document.getElementById('sagepaysuitepi-payment-errors');

                if (null !== span) {
                    span.style.display = "none";
                }
            }
        });
    }
);
