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
        'sagepayjs',
        'Magento_Checkout/js/model/full-screen-loader'

    ],
    function ($, Component, storage, url, customer, placeOrderAction, sagepayjs, fullScreenLoader) {
        'use strict';


        $(document).ready(function () {
            var piConfig = window.checkoutConfig.payment.ebizmarts_sagepaysuitepi;
            if(piConfig && !piConfig.licensed){
                $("#payment .step-title").after('<div class="message error" style="margin-top: 5px;border: 1px solid red;">WARNING: Your Sage Pay Suite license is invalid.</div>');
            }
        });

        return Component.extend({
            placeOrderHandler: null,
            validateHandler: null,
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
            isActive: function () {
                return true;
            },
            preparePayment: function () {
                var self = this;
                self.resetPaymentErrors();

                fullScreenLoader.startLoader();

                var serviceUrl = url.build('sagepaysuite/pi/generateMerchantKey');

                //generate merchant session key
                storage.get(serviceUrl).done(
                    function (response) {

                        if (response.success) {
                            self.sagepayTokeniseCard(response.merchant_session_key);
                        } else {
                            self.showPaymentError(response.error_message);
                        }
                    }
                ).fail(
                    function (response) {
                        self.showPaymentError("Unable to create merchant session key.");
                    }
                );
                return false;
            },

            sagepayTokeniseCard: function (merchant_session_key) {

                var self = this;

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
                        //console.log(token_form);

                        //request token
                        Sagepay.tokeniseCardDetails(token_form, function (status, response) {
                            //console.log(status, response);

                            if (status === 201) {
                                self.creditCardType = response.cardType;
                                self.creditCardExpYear = document.getElementById(self.getCode() + '_expiration_yr').value;
                                self.creditCardExpMonth = document.getElementById(self.getCode() + '_expiration').value;
                                self.creditCardLast4 = document.getElementById(self.getCode() + '_cc_number').value.slice(-4);
                                self.merchantSessionKey = merchant_session_key;
                                self.cardIdentifier = response.cardIdentifier;

                                try {

                                    self.placeOrder();

                                } catch (err) {
                                    console.log(err);
                                    alert("Unable to initialize Sage Pay payment method, please refresh the page and try again.");
                                }

                            } else {
                                self.showPaymentError(response.error.message);
                            }
                        });
                    } catch (err) {
                        console.log(err);
                        //errorProcessor.process(err);
                        alert("Unable to initialize Sage Pay payment method, please refresh the page and try again.");
                    }
                }
            },

            /**
             * @override
             */
            getData: function () {
                return {
                    'method': this.getCode(),
                    'additional_data': {
                        'cc_last4': this.creditCardLast4,
                        'merchant_session_Key': this.merchantSessionKey,
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
                        function(order_id,response, extra){
                            console.log("success");
                            window.location.replace(url.build('checkout/onepage/success/'));
                        }
                    ).fail(
                        function(response){
                            self.isPlaceOrderActionAllowed(true);

                            var error_message = "Unable to capture payment. Please refresh the page and try again.";
                            if(response && response.responseJSON && response.responseJSON.message){
                                error_message = response.responseJSON.message;
                            }
                            self.showPaymentError(error_message);
                        }
                    );
                    return true;
                }
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