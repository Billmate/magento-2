define([
    'jquery',
    'Magento_Customer/js/customer-data',
    'Magento_Checkout/js/model/checkout-data-resolver'
], function ($, customerData, checkoutDataResolver) {
    'use strict';

    return function (response) {
        if (response.errors) {
            let errorMessages = [];
            $.each(response.errors, function (error) {
                errorMessages.push({type: 'error', text: error})
            });
            customerData.set('messages', {'messages': errorMessages});
        }
        window.dispatchEvent(new Event('billmateLock'));
        customerData.reload(['cart'], true).then(function () {
            checkoutDataResolver.resolveEstimationAddress();
            window.dispatchEvent(new Event('billmateUpdate'));
        });
    }
})