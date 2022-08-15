define(
    [
        'Magento_Checkout/js/view/payment/default',
        'jquery',
        'ko',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/totals',
        'BnplPartners_Factoring004Magento/js/view/payment/schedule/factoring004',
    ],
    function (Component, $, ko, fullScreenLoader, totals, Factoring004Payment) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'BnplPartners_Factoring004Magento/payment/factoring004',
                redirectAfterPlaceOrder: false,
                _redirectLink: '',
                schedule: '',
            },

            termsChecked: ko.observable(false),

            initialize () {
                this._super();
                this.schedule = this._renderSchedule();
            },

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

            logoUrl () {
                return window.checkoutConfig.payment.bnplpartners_factoring004magento.logoUrl;
            },

            description () {
                return window.checkoutConfig.payment.bnplpartners_factoring004magento.description;
            },

            _renderSchedule () {
                const elem = document.createElement('div');
                const schedule = new Factoring004Payment.PaymentSchedule({
                    elemId: 'payment-factoring004-schedule',
                    totalAmount: totals.totals().grand_total,
                });

                schedule.renderTo(elem);

                return elem.innerHTML;
            }
        });
    }
);
