/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*jshint jquery:true*/
define([
    "jquery",
    "sagepayjs",
    'mage/storage',
    'mage/url',
    "jquery/ui"
], function ($, sagepayjs, storage, url) {
    "use strict";

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
        changePaymentData: function(){
            console.log("changePaymentData");
        },
        fieldObserver: function(){
            console.log("fieldObserver");
        },
        disableValidation: function(){
            var method = this.getCode();
            var self = this;
            if ($('payment_form_'+ method)){
                this.paymentMethod = method;
                var form = 'payment_form_'+method;
                [form + '_before', form, form + '_after'].each(function(el) {
                    var block = $(el);
                    if (block) {
                        block.select('input', 'select', 'textarea').each(function(field) {
                            if (!el.include('_before') && !el.include('_after') && !field.bindChange) {
                                field.observe('change', self.fieldObserver.bind(this))
                            }
                        },this);
                    }
                },this);
            }
        },
        submitAdminOrder: function () {

            //var url = order.loadBaseUrl;

            var self = this;
            self.resetPaymentErrors();

            var serviceUrl = url.build('/sagepaysuite/pi/generateMerchantKey');

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

            //var ccNumber = $("#braintree_cc_number").val(),
            //    ccExprYr = $("#braintree_expiration_yr").val(),
            //    ccExprMo = $("#braintree_expiration").val(),
            //    self = this;
            //if (self.options.useCvv) {
            //    var cvv = $('#braintree_cc_cid').val();
            //}
            //
            //if (ccNumber) {
            //    this.enableDisableFields(true);
            //    var braintreeClient = new braintree.api.Client({clientToken: this.options.clientToken}),
            //        braintreeObj = {
            //            number: ccNumber,
            //            expirationMonth: ccExprMo,
            //            expirationYear: ccExprYr,
            //            };
            //    if (self.options.useCvv) {
            //        braintreeObj.cvv = cvv;
            //    }
            //    braintreeClient.tokenizeCard(
            //        braintreeObj,
            //        function (err, nonce) {
            //            if (!err) {
            //                $('#braintree_nonce').val(nonce);
            //                if (self.options.isFraudDetectionEnabled) {
            //                    $('#braintree_device_id').val($('#device_data').val());
            //                }
            //                order._realSubmit();
            //            } else {
            //                //TODO: handle error case
            //            }
            //        }
            //    );
            //} else {
            //    if (self.options.isFraudDetectionEnabled) {
            //        $('#braintree_device_id').val($('#device_data').val());
            //    }
            //    order._realSubmit();
            //}
        },
        sagepayTokeniseCard: function (merchant_session_key) {

            var self = this;

            if (merchant_session_key) {

                var token_form = document.getElementById(self.getCode() + '-token-form');

                if(!token_form){
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
                    input_cc_owner.setAttribute('value',"");

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

                }else {

                    //update token form
                    var token_form = document.getElementById(self.getCode() + '-token-form');
                    token_form.elements[0].setAttribute('value', merchant_session_key);
                    token_form.elements[1].setAttribute('value', "");
                    token_form.elements[2].setAttribute('value', document.getElementById(self.getCode() + '_cc_number').value);
                    var expiration = document.getElementById(self.getCode() + '_expiration').value;
                    expiration = expiration.length == 1 ? "0" + expiration : expiration;
                    expiration += document.getElementById(self.getCode() + '_expiration_yr').value.substring(2, 4);
                    token_form.elements[3].setAttribute('value', expiration);
                    token_form.elements[4].setAttribute('value', document.getElementById(self.getCode() + '_cc_cid').value);
                }

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
        getCode: function(){
            return this.options.code;
        },
        showPaymentError: function (message) {

            var span = document.getElementById(this.getCode() + '-payment-errors');

            span.innerHTML = message;
            span.style.display = "block";
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
            this.disableValidation();
        }
    });

    return $.mage.sagepaysuitepiCcForm;
});