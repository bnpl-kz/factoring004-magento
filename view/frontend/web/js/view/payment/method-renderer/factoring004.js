define(
    [
        'Magento_Checkout/js/view/payment/default',
        'jquery',
        'ko',
        'Magento_Checkout/js/model/full-screen-loader',
    ],
    function (Component, $, ko, fullScreenLoader) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'BnplPartners_Factoring004Magento/payment/factoring004',
                redirectAfterPlaceOrder: false,
                _redirectLink: '',
            },

            termsChecked: ko.observable(false),

            afterPlaceOrder () {
                if (this._redirectLink) {
                    fullScreenLoader.startLoader();
                    window.location.replace(this._redirectLink);
                }
            },

            getPlaceOrderDeferredObject () {
                return this._super()
                    .done((data, type, jqXHR) => this._redirectLink = jqXHR.getResponseHeader('X-Location'));
            },

            agreementUrl () {
                return window.checkoutConfig.payment.bnplpartners_factoring004magento.agreementUrl;
            },
        });
    }
);
