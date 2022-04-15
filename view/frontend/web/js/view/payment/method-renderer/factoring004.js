define(
    [
        'Magento_Checkout/js/view/payment/default',
        'jquery',
        'ko',
    ],
    function (Component, $, ko) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'BnplPartners_Factoring004Magento/payment/factoring004',
            },

            termsChecked: ko.observable(false),

            agreementUrl () {
                return window.checkoutConfig.payment.bnplpartners_factoring004magento.agreementUrl;
            },
        });
    }
);
