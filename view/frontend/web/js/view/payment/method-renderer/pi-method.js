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
        'Magento_CheckoutAgreements/js/view/agreement-validation',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/model/url-builder',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/action/set-payment-information'
    ],
    function ($, Component, storage, url, customer, placeOrderAction, fullScreenLoader, modal, agreementValidation, additionalValidators, urlBuilder, quote, customerData, setPaymentInformation) {
        'use strict';

        $(document).ready(function () {
            var piConfig = window.checkoutConfig.payment.ebizmarts_sagepaysuitepi;
            if (piConfig && !piConfig.licensed) {
                $("#payment .step-title").after('<div class="message error" style="margin-top: 5px;border: 1px solid red;">WARNING: Your Opayo Suite license is invalid.</div>');
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
                dropInInstance: null,
                save_token: false,
                use_token: true,
                used_token_slots: 0
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
            threeDNewWindowEnabled: function () {
                return window.checkoutConfig.payment.ebizmarts_sagepaysuitepi.newWindow == 1;
            },
            scaEnabled: function () {
                return window.checkoutConfig.payment.ebizmarts_sagepaysuitepi.sca == 1;
            },
            isActive: function () {
                return true;
            },
            isTokenServiceEnabled: function () {
                return window.checkoutConfig.payment.ebizmarts_sagepaysuitepi.tokenEnabled == true;
            },
            sagepaySetForm: function () {
                var self = this;

                self.addShippingUpdateEvent();
                self.loadDropInForm();
                self.addBillingUpdateEvents();

                if (!self.dropInEnabled()) {
                    $('button.checkout').attr('disabled', 'disabled');
                }
            },
            observeCardChanges: function(itemId) {
                var self = this;
                var code = self.getCode();

                if (self.checkFormElementExistense(code)) {
                    var element = document.getElementById(itemId);

                    if (element.value == '') {
                        $(element).parents('.field').addClass('_error');
                        $('button.checkout').attr('disabled', 'disabled');
                    } else {
                        if (self.checkFieldFilled(code, itemId)) {
                            $(element).parents('.field').removeClass('_error');
                        }

                        if (self.checkFilledfFormFields(code)) {
                            $('button.checkout').removeAttr('disabled');
                        }
                    }
                }
            },
            checkFieldFilled: function(code, itemId) {
                var elementValue = document.getElementById(itemId).value;

                if (itemId == code + '_cardholder' || itemId == code + '_cc_number' || itemId == code + '_cc_cid') {
                    if (elementValue == '') {
                        return false;
                    }
                } else if (itemId == code + '_expiration') {
                    if (elementValue == 'Month') {
                        return false;
                    }
                } else if (itemId == code + '_expiration_yr') {
                    if (elementValue == 'Year') {
                        return false;
                    }
                }

                return true;
            },
            checkFilledfFormFields: function(code) {
                var cardHolder = document.getElementById(code + '_cardholder').value;
                var ccNumber = document.getElementById(code + '_cc_number').value;
                var expiration = document.getElementById(code + '_expiration').value;
                var expirationYr = document.getElementById(code + '_expiration_yr').value;
                var CID = document.getElementById(code + '_cc_cid').value;

                if (cardHolder == ''
                    || ccNumber == ''
                    || expiration == 'Month' || expiration == ''
                    || expirationYr == 'Year' || expirationYr == ''
                    || CID == '') {
                    return false;
                }

                return true;
            },
            checkFormElementExistense: function(code) {
                var cardHolderElement = document.getElementById(code + '_cardholder');
                var ccNumberElement = document.getElementById(code + '_cc_number');
                var expirationElement = document.getElementById(code + '_expiration');
                var expirationYrElement = document.getElementById(code + '_expiration_yr');
                var CIDElement = document.getElementById(code + '_cc_cid');

                if (cardHolderElement !== null
                    && ccNumberElement !== null
                    && expirationElement !== null
                    && expirationYrElement !== null
                    && CIDElement !== null) {
                    return true;
                }

                return false;
            },
            addShippingUpdateEvent: function () {
                var self = this;

                $(".button.action.continue.primary").on('click', function () {
                    self.resetPaymentErrors();
                    self.loadDropInForm();
                });
            },
            addBillingUpdateEvents: function () {
                var self = this;

                $("#billing-address-same-as-shipping-sagepaysuitepi").on('change', function () {
                    if ($("#billing-address-same-as-shipping-sagepaysuitepi").is(':checked')) {
                        self.resetPaymentErrors();
                        self.loadDropInForm();
                    }
                });

                $(".action.action-update").on('click', function () {
                    self.resetPaymentErrors();
                    self.loadDropInForm();
                });
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
                        self.showPaymentError("Unable to create Opayo merchant session key.");
                    }
                );
            },
            savePaymentInfo: function () {
                var self = this;

                var defer = $.Deferred();

                $.when(
                    setPaymentInformation(this.messageContainer, self.getData())
                ).done(
                    function (response) {
                        if (response === true) {
                            defer.resolve();
                        } else {
                            defer.reject();

                            self.showPaymentError("Unable to save payment info.");
                        }
                    }
                ).fail(
                    function (response) {
                        defer.reject();

                        if (response.responseJSON) {
                            self.showPaymentError(response.responseJSON.message);
                        } else {
                            self.showPaymentError("Unable to save payment info.");
                        }
                    }
                );

                return defer;
            },
            preparePayment: function () {
                var self = this;

                if (!self.dropInEnabled()) {
                    if (!additionalValidators.validate()) {
                        return false;
                    }
                }

                self.destroyInstanceSagePay();

                //validations
                if (!self.validate() || self.getCode() != self.isChecked()) {
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
                            if (!self.dropInEnabled()) {
                                self.savePaymentInfo().done(function () {
                                    self.createMerchantSessionKey();
                                });
                            } else {
                                self.createMerchantSessionKey();
                            }
                        }
                    ).fail(
                        function (response) {
                            self.showPaymentError("Unable to save billing address.");
                        }
                    );
                });

                return false;
            },
            loadDropInForm: function () {
                var self = this;

                if (document.getElementById('sagepaysuitepi').checked) {
                    self.selectPaymentMethod();
                }

                if (self.dropInEnabled() && quote.billingAddress() != null) {
                    if (!this.use_token) {
                        self.preparePayment();
                    }
                }
            },
            tokenisationAuthenticationFailed: function (tokenisationResult) {
                return tokenisationResult.error.errorCode === 1002;
            },
            tokenise: function () {
                var self = this;

                if (additionalValidators.validate()) {
                    self.savePaymentInfo().done(function () {
                        if (self.dropInInstance !== null) {
                            self.dropInInstance.tokenise();
                        }
                    })
                } else {
                    return false;
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
            isPlaceOrderActionAllowed: function (allowedParam) {
                if (typeof allowedParam === 'undefined') {
                    return quote.billingAddress() != null;
                }
                return allowedParam;
            },
            afterTokenise: function (tokenisationResult) {
                var self = this;
                var cardIdentifier = "";
                if (tokenisationResult.success) {
                    if (self.use_token) {
                        cardIdentifier = self.getSagePayToken(self.getSelectedToken());
                    } else {
                        cardIdentifier = tokenisationResult.cardIdentifier;
                    }
                    self.cardIdentifier = cardIdentifier;
                    self.creditCardType = "";
                    self.creditCardExpYear = 0;
                    self.creditCardExpMonth = 0;
                    self.creditCardLast4 = 0;
                    try {
                        self.placeTransaction();
                    } catch (err) {
                        console.log(err);
                        self.showPaymentError("Unable to initialize Opayo payment method, please use another payment method.");
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
            },
            sagepayTokeniseCard: function (merchant_session_key) {

                var self = this;

                if (self.dropInEnabled()) {
                    if (merchant_session_key) {
                        self.isPlaceOrderActionAllowed(false);
                        self.merchantSessionKey = merchant_session_key;

                        if (self.dropInInstance !== null) {
                            self.dropInInstance.destroy();
                            self.dropInInstance = null;
                        }

                        if (self.use_token) {
                            var selectedToken = self.getSelectedToken();
                            var sagePayToken = self.getSagePayToken(selectedToken);

                            self.dropInInstance = sagepayCheckout({
                                merchantSessionKey: merchant_session_key,
                                reusableCardIdentifier: sagePayToken,
                                onTokenise: function (tokenisationResult) { self.afterTokenise(tokenisationResult) }
                            });
                        } else {
                            self.dropInInstance = sagepayCheckout({
                                merchantSessionKey: merchant_session_key,
                                onTokenise: function (tokenisationResult) { self.afterTokenise(tokenisationResult) }
                            });
                        }

                        self.dropInInstance.form();
                        fullScreenLoader.stopLoader();

                        $("#payment-iframe").css("display", "block");
                        $("#sp-container").css("display", "block");
                        $("#submit_dropin_payment").css("display", "block");
                        $("#load-dropin-form-button").css("display", "none");
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
                                    self.creditCardType = response.cardType;
                                    self.creditCardExpYear = document.getElementById(self.getCode() + '_expiration_yr').value;
                                    self.creditCardExpMonth = document.getElementById(self.getCode() + '_expiration').value;
                                    self.creditCardLast4 = document.getElementById(self.getCode() + '_cc_number').value.slice(-4);
                                    self.merchantSessionKey = merchant_session_key;
                                    self.cardIdentifier = response.cardIdentifier;

                                    try {
                                        self.placeTransaction();
                                    } catch (err) {
                                        self.showPaymentError("Unable to initialize Opayo payment method, please use another payment method.");
                                    }
                                } else {
                                    var errorMessage = "Unable to initialize Opayo payment method, please use another payment method.";
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
                            alert("Unable to initialize Opayo payment method, please use another payment method.");
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

                var sagePayRequestData = {
                    "merchant_session_key": self.merchantSessionKey,
                    "card_identifier": self.cardIdentifier,
                    "cc_type": self.creditCardType,
                    "cc_exp_month": self.creditCardExpMonth,
                    "cc_exp_year": self.creditCardExpYear,
                    "cc_last_four": self.creditCardLast4,
                    "save_token": self.saveToken(),
                    "reusable_token": self.reusableToken()
                };

                $.extend(sagePayRequestData, self.scaParams());

                var payload = {
                    "cartId": quote.getQuoteId(),
                    "requestData": sagePayRequestData
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
                                customerData.invalidate(['checkout-data']);

                                window.location.replace(url.build('checkout/onepage/success/'));
                            } else if (response.status === "3DAuth") {

                                customerData.invalidate(['cart']);
                                customerData.invalidate(['checkout-data']);

                                /**
                                 * 3D secure authentication required
                                 */
                                if (self.scaEnabled()) {
                                    var form3Dv2 = document.getElementById(self.getCode() + '-3DsecureV2-form');
                                    form3Dv2.setAttribute('action', response.acs_url);
                                    form3Dv2.elements[0].setAttribute('value', response.creq);

                                    if (!self.sagePayIsMobile() && !self.threeDNewWindowEnabled()) {
                                        self.open3DModal();
                                        form3Dv2.setAttribute('target', self.getCode() + '-3Dsecure-iframe');
                                    }
                                    form3Dv2.submit();
                                } else {
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

                                    if (!self.sagePayIsMobile() && !self.threeDNewWindowEnabled()) {
                                        self.open3DModal();
                                        form3D.setAttribute('target', self.getCode() + '-3Dsecure-iframe');
                                    }

                                    form3D.submit();
                                }

                                if (!self.dropInEnabled()) {
                                    fullScreenLoader.stopLoader();
                                }
                            } else {
                                self.showPaymentError("Invalid Opayo response, please use another payment method.");
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
                        self.showPaymentError("Unable to capture Opayo transaction, please use another payment method.");
                    }
                );
            },

            /**
             * Create 3D modal
             */
            open3DModal: function () {
                this.modal = $('<iframe id="' + this.getCode() + '-3Dsecure-iframe" name="' + this.getCode() + '-3Dsecure-iframe"></iframe>').modal({
                    modalClass: 'sagepaysuite-modal',
                    title: "Opayo 3D Secure Authentication",
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
            scaParams: function () {
                return {
                    'javascript_enabled': 1,
                    'accept_headers': 'Accept headers.',
                    'language': navigator.language,
                    'user_agent': navigator.userAgent,
                    'java_enabled': navigator.javaEnabled() ? 1 : 0,
                    'color_depth': screen.colorDepth,
                    'screen_width': screen.width,
                    'screen_height': screen.height,
                    'timezone': (new Date()).getTimezoneOffset()
                }
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

                self.loadDropInForm();
            },
            resetPaymentErrors: function () {
                var span = document.getElementById('sagepaysuitepi-payment-errors');

                if (null !== span) {
                    span.style.display = "none";
                }
            },
            getRememberToken: function () {
                return (document.getElementById('piremembertoken') &&
                    document.getElementById('piremembertoken').checked == true);
            },
            saveToken: function () {
                var self = this;

                return (self.isTokenServiceEnabled() && self.getRememberToken());
            },
            reusableToken: function () {
                var self = this;

                return (self.isTokenServiceEnabled() && this.use_token);
            },
            getCustomerTokenCount: function () {
                var sagePayTokens = window.checkoutConfig.payment.ebizmarts_sagepaysuitepi.tokenCount;
                if (sagePayTokens.length > 0) {
                    this.useSavedTokens();
                }
                return sagePayTokens;
            },
            useSavedTokens: function () {
                this.use_token = true;
                this.destroyInstanceSagePay();
                document.getElementById('piremembertoken').checked = 0;
                $('#sagepay-pi-remembertoken-container').hide();

                $('#' + this.getCode() + '-tokens .token-list').show();
                $('#' + this.getCode() + '-tokens .add-new-card-link').show();
                $('#' + this.getCode() + '-tokens .use-saved-card-link').hide();
                $('#' + this.getCode() + '-tokens .using-new-card-message').hide();
            },
            customerHasTokens: function () {
                this.save_token = false;
                this.use_token = false;

                if (this.isTokenServiceEnabled()) {
                    this.save_token = true;
                    var customerTokens = this.getCustomerTokenCount();

                    if (customerTokens && customerTokens.length > 0) {
                        this.used_token_slots = customerTokens.length;
                        //this.checkMaxTokensPerCustomer();
                        this.use_token = true;
                    }
                }

                return this.use_token;
            },
            addNewCard: function () {
                this.use_token = false;
                this.loadDropInForm();
                document.getElementById('piremembertoken').checked = 1;
                $('#' + this.getCode() + '-tokens .token-list').hide();
                $('#' + this.getCode() + '-tokens .add-new-card-link').hide();
                $('#' + this.getCode() + '-tokens .using-new-card-message').show();
                $('#' + this.getCode() + '-tokens .use-saved-card-link').show();
            },
            getIcons: function (type) {
                switch (type) {
                    case 'VI':
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
            },
            selectToken: function () {
                var self = this;

                // TO DO:
                // Revisar si esta bien que se cheque aca o ver de que no se
                // ejecute el checked del boton
                if (this.isRadioChecked()) {
                    self.use_token = true;
                    self.loadDropInTokenForm();
                }
            },
            isRadioChecked: function () {
                var self = this;
                var customerTokens = this.getCustomerTokenCount();
                for (var i = 0; i < customerTokens.length; i++) {
                    if ($('#' + self.getCode() + '-token-' + customerTokens[i].id).prop("checked") == true) {
                        return true;
                    }
                }
                return false;
            },
            getSelectedToken: function () {
                var self = this;
                var customerTokens = this.getCustomerTokenCount();
                for (var i = 0; i < customerTokens.length; i++) {
                    if ($('#' + self.getCode() + '-token-' + customerTokens[i].id).prop("checked") == true) {
                        return customerTokens[i];
                    }
                }
                return false;
            },
            getSagePayToken: function (token) {
                var sagePayToken = "";
                var url = urlBuilder.createUrl('/sagepay/token/:tokenId/:customerId', {
                    tokenId: token.id,
                    customerId: token.customer_id
                });

                $.ajax({
                    url: '/' + url,
                    data: {},
                    type: 'GET',
                    dataType: 'json',
                    async: false,
                    showLoader: false
                }).done(function (data) {
                    sagePayToken = data.response;
                });

                return sagePayToken;
            },
            loadDropInTokenForm: function () {
                var self = this;

                self.selectPaymentMethod();

                if (self.dropInEnabled() && quote.billingAddress() != null && self.use_token) {
                    self.preparePayment();
                }
            },
            deleteToken: function (id) {
                var self = this;
                if (confirm("Are you sure you wish to delete this saved credit card token?")) {
                    var serviceUrl = url.build('sagepaysuite/token/delete');

                    //send token delete post
                    return storage.get(serviceUrl + "/token_id/" + id + "/checkout/1").done(
                        function (response) {

                            if (response.success && response.success == true) {
                                //check warning message
                                self.used_token_slots = self.used_token_slots - 1;
                                //TO DO: agregar checkeo de cantidad de tokens por customer
                                //self.checkMaxTokensPerCustomer();

                                //hide token row
                                $('#' + self.getCode() + '-token-' + id).prop("checked", false);
                                $('#' + self.getCode() + '-tokenrow-' + id).hide();

                                //delete from token list
                                var tokens = this.getCustomerTokens();
                                for (var i = 0; i < tokens.length; i++) {
                                    if (id == tokens[i].id) {
                                        tokens.splice(i, 1);
                                    }
                                }
                                if (tokens.length == 0) {
                                    $('#' + self.getCode() + '-tokens').hide();
                                    self.use_token = false;
                                }

                                //TO DO: destroy dropIn instance if payment already selected.
                            } else {
                                self.showPaymentError(response.error_message);
                            }
                        }
                    ).fail(
                        function (response) {
                            self.showPaymentError("Unable to delete credit card token.");
                        }
                    );
                }
            }
        });
    }
);
