define(
    [
        'Magento_Checkout/js/view/payment/default',
        'jquery',
        'ko',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/action/redirect-on-success',
        window.checkoutConfig.payment.bnplpartners_factoring004magento.isModalProd
          ? 'bnpl-kz-modal-prod'
          : 'bnpl-kz-modal-dev',
    ],
    function (Component, $, ko, fullScreenLoader, redirectOnSuccessAction, BnplKzApi) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'BnplPartners_Factoring004Magento/payment/factoring004',
                redirectAfterPlaceOrder: false,
                _redirectLink: '',
            },

            initialize () {
                this._super();
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

            logoUrl () {
                return window.checkoutConfig.payment.bnplpartners_factoring004magento.logoUrl;
            },

            description () {
                return window.checkoutConfig.payment.bnplpartners_factoring004magento.description;
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
        });
    }
);
