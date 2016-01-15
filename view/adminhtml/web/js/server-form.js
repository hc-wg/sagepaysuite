/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

/*jshint jquery:true*/

define([
    "jquery",
    'mage/storage',
    'mage/url',
    "jquery/ui"
], function ($, storage, url) {
    "use strict";

    $.widget('mage.sagepaysuiteServerForm', {
        options: {
            code: "sagepaysuiteserver"
        },

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
        submitAdminOrder: function () {

            var self = this;
            self.resetPaymentErrors();

            var serviceUrl = sagepaysuiteserver_config.url.request;

            jQuery.ajax( {
                url: serviceUrl,
                data: {form_key: window.FORM_KEY},
                type: 'POST'
            }).done(function(response) {
                if(response.success == true){
                    window.location.href = response.response.data.NextURL;
                }else{
                    self.showPaymentError(response.error_message);
                }
                console.log(response);
            });

            return false;
        },
        getCode: function(){
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

    return $.mage.sagepaysuiteServerForm;
});