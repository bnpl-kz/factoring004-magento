define(
    [
        'Magento_Checkout/js/view/payment/default',
        'jquery',
        'ko',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/totals',
        'Magento_Checkout/js/model/quote',
        'Magento_Catalog/js/price-utils',
        'BnplPartners_Factoring004Magento/js/view/payment/schedule/factoring004',
    ],
    function (Component, $, ko, fullScreenLoader, totals, quote, priceUtils, Factoring004Payment) {
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

            minAmount () {
                return window.checkoutConfig.payment.bnplpartners_factoring004magento.minAmount;
            },

            maxAmount () {
                return window.checkoutConfig.payment.bnplpartners_factoring004magento.maxAmount;
            },

            minAmountFormatted () {
                return priceUtils.formatPrice(this.minAmount(), quote.getPriceFormat());
            },

            maxAmountFormatted () {
                return priceUtils.formatPrice(this.maxAmount(), quote.getPriceFormat());
            },

            isDisabled () {
                return this.amount() < this.minAmount() || this.amount() > this.maxAmount();
            },

            amount () {
                return totals.totals().grand_total;
            },

            amountNotEnoughFormatted () {
                return priceUtils.formatPrice(Math.round(this.minAmount() - this.amount()), quote.getPriceFormat());
            },

            amountExceededFormatted () {
                return priceUtils.formatPrice(Math.round(this.amount() - this.maxAmount()), quote.getPriceFormat());
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
