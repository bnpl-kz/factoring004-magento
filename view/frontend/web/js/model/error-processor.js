/**
 * @api
 */
define([
    'mage/url',
    'Magento_Ui/js/model/messageList',
    'mage/translate'
], function (url, globalMessageList, $t) {
    'use strict';

    return {
        /**
         * @param {Object} response
         * @param {Object} messageContainer
         */
        process: function (response, messageContainer) {
            var error;

            messageContainer = messageContainer || globalMessageList;

            if (response.status == 401) { //eslint-disable-line eqeqeq
                window.location.replace(url.build('customer/account/login/'));
            } else {
                try {
                    error = JSON.parse(response.responseText);
                } catch (exception) {
                    error = {
                        message: $t('Something went wrong with your request. Please try again later.'),
                        errors: [],
                    };
                }

                for (const item of error.errors) {
                    if (item.message === 'bnplpartners_factoring004magento') {
                        window.location.replace(url.build('factoring004/error'))
                        return;
                    }
                }

                messageContainer.addErrorMessage(error);
            }
        }
    };
});
