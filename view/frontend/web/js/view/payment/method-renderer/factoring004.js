define(
    [
        'Magento_Checkout/js/view/payment/default',
        'jquery',
        'ko',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/totals',
        'Magento_Checkout/js/model/quote',
        'Magento_Catalog/js/price-utils',
        'Magento_Checkout/js/action/redirect-on-success',
        'BnplPartners_Factoring004Magento/js/view/payment/schedule/factoring004',
        window.checkoutConfig.payment.bnplpartners_factoring004magento.isModalProd
          ? 'bnpl-kz-modal-prod'
          : 'bnpl-kz-modal-dev',
    ],
    function (Component, $, ko, fullScreenLoader, totals, quote, priceUtils, redirectOnSuccessAction, Factoring004Payment, BnplKzApi) {
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
                this._bnplKzPaymentWidget = new BnplKzApi.CPO({
                    rootId: 'payment-factoring004-widget',
                    callbacks: {
                        onEnd: () => redirectOnSuccessAction.execute(),
                        onError: (err) => {
                            if (err.code === 'clientError') {
                                this._redirectToPayment();
                            }
                        },
                        onRestart: (redirectLink) => this._redirectLink = redirectLink,
                        onDeclined () {
                            fullScreenLoader.startLoader();
                            window.location.replace('/checkout/onepage/failure/');
                        },
                    },
                });
            },

            placeOrder (data, event) {
                if (this._redirectLink) {
                    this._openWidget();
                    return false;
                }

                return this._super(data, event);
            },

            afterPlaceOrder () {
                if (!this._redirectLink) return;

                if (this.isWidget()) {
                    this._openWidget();
                    return;
                }

               this._redirectToPayment();
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

            isWidget () {
                return window.checkoutConfig.payment.bnplpartners_factoring004magento.paymentGatewayType === 'modal';
            },

            _redirectToPayment () {
                fullScreenLoader.startLoader();
                window.location.replace(this._redirectLink);
            },

            _openWidget () {
                this._bnplKzPaymentWidget.render({
                    redirectLink: this._redirectLink,
                });
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
