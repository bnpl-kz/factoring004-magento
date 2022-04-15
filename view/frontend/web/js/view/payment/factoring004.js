define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (Component, rendererList) {
        'use strict';
        rendererList.push(
            {
                type: 'bnplpartners_factoring004magento',
                component: 'BnplPartners_Factoring004Magento/js/view/payment/method-renderer/factoring004'
            }
        );

        Component.extend({});
    }
);
