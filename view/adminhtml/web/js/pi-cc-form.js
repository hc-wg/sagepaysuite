/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

/*jshint jquery:true*/

define([
    "jquery",
    'mage/url',
    "jquery/ui",
    'mage/translate'
], function ($, url, ui, $t) {
    "use strict";

    /**
     * Disable card server validation in admin
     */
    if (typeof order !== 'undefined') {
        order.addExcludedPaymentMethod('sagepaysuitepi');
    }

    $.widget('mage.sagepaysuitepiCcForm', {
        options: {
            code: "sagepaysuitepi"
        },
        creditCardType: '',
        creditCardExpYear: '',
        creditCardExpMonth: '',
        creditCardLast4: '',
        merchantSessionKey: '',
        cardIdentifier: '',

        prepare: function (event, method) {
            if (method === this.options.code) {
                this.preparePayment();
            }
        },
        preparePayment: function () {
            $('#edit_form').off('submitOrder').on('submitOrder', this.submitAdminOrder.bind(this));
            $('#edit_form').off('changePaymentData').on('changePaymentData', this.changePaymentData.bind(this));
        },
        changePaymentData: function () {
        },
        fieldObserver: function () {
        },
        submitAdminOrder: function () {

            $('#edit_form').validate().form();
            $('#edit_form').trigger('afterValidate.beforeSubmit');
            $('body').trigger('processStop');

            // validate parent form
            if ($('#edit_form').validate().errorList.length) {
                return false;
            }

            var self = this;
            self.resetPaymentErrors();

            var serviceUrl = this.options.url.generateMerchantKey;

            require(['sagepayjs_' + this.options.mode], function () {
                $.ajax({
                    url: serviceUrl,
                    data: {form_key: window.FORM_KEY},
                    type: 'POST',
                    showLoader: true
                }).done(function (response) {
                    if (response.success) {
                        self.sagepayTokeniseCard(response.merchant_session_key);
                    } else {
                        self.showPaymentError(response.error_message ? response.error_message : response.message);
                    }
                });
            });

            return false;
        },
        sagepayTokeniseCard: function (merchant_session_key) {

            var self = this;

            if (merchant_session_key) {
                var token_form = document.getElementById(self.getCode() + '-token-form');

                if (!token_form) {
                    token_form = document.createElement("form");
                    token_form.setAttribute('id',self.getCode() + '-token-form');
                    token_form.setAttribute('method',"post");
                    token_form.setAttribute('action',"/payment");
                    token_form.setAttribute('style',"display:none;");
                    document.getElementsByTagName('body')[0].appendChild(token_form);

                    var input_merchant_key = document.createElement("input");
                    input_merchant_key.setAttribute('type',"hidden");
                    input_merchant_key.setAttribute('data-sagepay',"merchantSessionKey");
                    token_form.appendChild(input_merchant_key);
                    input_merchant_key.setAttribute('value',merchant_session_key);

                    var input_cc_owner = document.createElement("input");
                    input_cc_owner.setAttribute('type',"text");
                    input_cc_owner.setAttribute('data-sagepay',"cardholderName");
                    token_form.appendChild(input_cc_owner);
                    input_cc_owner.setAttribute('value',"Owner");

                    var input_cc_number = document.createElement("input");
                    input_cc_number.setAttribute('type',"text");
                    input_cc_number.setAttribute('data-sagepay',"cardNumber");
                    token_form.appendChild(input_cc_number);
                    input_cc_number.setAttribute('value',document.getElementById(self.getCode() + "_cc_number").value);

                    var input_cc_exp = document.createElement("input");
                    input_cc_exp.setAttribute('type',"text");
                    input_cc_exp.setAttribute('data-sagepay',"expiryDate");
                    token_form.appendChild(input_cc_exp);
                    var expiration = document.getElementById(self.getCode() + "_expiration").value
                    expiration = expiration.length == 1 ? "0" + expiration : expiration;
                    expiration += document.getElementById(self.getCode() + "_expiration_yr").value.substring(2,4);
                    input_cc_exp.setAttribute('value',expiration);

                    var input_cc_cvc = document.createElement("input");
                    input_cc_cvc.setAttribute('type',"text");
                    input_cc_cvc.setAttribute('data-sagepay',"securityCode");
                    token_form.appendChild(input_cc_cvc);
                    input_cc_cvc.setAttribute('value',document.getElementById(self.getCode() + "_cc_cid").value);
                } else {
                    //update token form
                    var token_form = document.getElementById(self.getCode() + '-token-form');
                    token_form.elements[0].setAttribute('value', merchant_session_key);
                    token_form.elements[1].setAttribute('value', "Owner");
                    token_form.elements[2].setAttribute('value', document.getElementById(self.getCode() + '_cc_number').value);
                    var expiration = document.getElementById(self.getCode() + '_expiration').value;
                    expiration = expiration.length == 1 ? "0" + expiration : expiration;
                    expiration += document.getElementById(self.getCode() + '_expiration_yr').value.substring(2, 4);
                    token_form.elements[3].setAttribute('value', expiration);
                    token_form.elements[4].setAttribute('value', document.getElementById(self.getCode() + '_cc_cid').value);
                }

                try {
                    //request token
                    Sagepay.tokeniseCardDetails(token_form, function (status, response) {
                    
                        if (status === 201) {
                            self.creditCardType = self.parseCCType(response.cardType);
                            self.creditCardExpYear = document.getElementById(self.getCode() + '_expiration_yr').value;
                            self.creditCardExpMonth = document.getElementById(self.getCode() + '_expiration').value;
                            self.creditCardLast4 = document.getElementById(self.getCode() + '_cc_number').value.slice(-4);
                            self.merchantSessionKey = merchant_session_key;
                            self.cardIdentifier = response.cardIdentifier;

                            try {
                                self.placeTansactionRequest();
                            } catch (err) {
                                console.log(err);
                                alert("Unable to initialize Sage Pay payment method, please refresh the page and try again.");
                            }
                        } else {
                            var errorMessages = "";

                            var errorsCount = response.responseJSON.errors.length;
                            for (var i = 0; i < errorsCount; i++) {
                                errorMessages += "<br />" + response.responseJSON.errors[i].clientMessage;
                            }

                            self.showPaymentError(errorMessages);
                        }
                    });
                } catch (err) {
                    console.log(err);
                    //errorProcessor.process(err);
                    alert("Unable to initialize Sage Pay payment method, please refresh the page and try again.");
                }
            }
        },
        placeTansactionRequest: function () {

            var self = this;

            var serviceUrl = this.options.url.request;

            var formData = jQuery("#edit_form").serialize();
            formData += "&merchant_session_key=" + self.merchantSessionKey;
            formData += "&card_identifier=" + self.cardIdentifier;
            formData += "&card_type=" + self.creditCardType;
            formData += "&card_exp_month=" + self.creditCardExpMonth;
            formData += "&card_exp_year=" + self.creditCardExpYear;
            formData += "&card_last4=" + self.creditCardLast4;

            $.ajax({
                url: serviceUrl,
                data: formData,
                type: 'POST',
                showLoader: true
            }).done(function (response) {

                if (response.success == true) {
                    //redirect to success
                    window.location.href = response.response.redirect;
                } else {
                    self.showPaymentError(response.error_message ? response.error_message : "Invalid Sage Pay response, please use another payment method.");
                }
                console.log(response);
            });
        },
        parseCCType: function (cctype) {
            switch (cctype) {
                case 'Visa':
                    return "VI";
                    break;
                case 'MasterCard':
                    return "MC";
                    break;
                case 'Maestro':
                    return "MI";
                    break;
                case 'AmericanExpress':
                    return "AE";
                    break;
                case 'Diners':
                    return "DN";
                    break;
                case 'JCB':
                    return "JCB";
                    break;
                default:
                    return cctype;
                    break;
            }
        },
        getCode: function () {
            return this.options.code;
        },
        showPaymentError: function (message) {

            var span = document.getElementById(this.getCode() + '-payment-errors');

            span.innerHTML = message;
            span.style.display = "block";

            $('#edit_form').trigger('processStop');
            $('body').trigger('processStop');
        },
        resetPaymentErrors: function () {
            var span = document.getElementById(this.getCode() + '-payment-errors');
            span.style.display = "none";

        },
        _create: function () {
            $('#edit_form').on('changePaymentMethod', this.prepare.bind(this));
            $('#edit_form').on('changePaymentData', this.changePaymentData.bind(this));
            $('#edit_form').trigger(
                'changePaymentMethod',
                [
                    $('#edit_form').find(':radio[name="payment[method]"]:checked').val()
                ]
            );
        }
    });

    return $.mage.sagepaysuitepiCcForm;
});