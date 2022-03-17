define([
    'Magento_Checkout/js/action/select-shipping-method',
    'Magento_Checkout/js/checkout-data',
    'Magento_Checkout/js/model/cart/estimate-service'
], function (
    selectShippingMethodAction,
    checkoutData
) {
    'use strict';

    return function (methodData, processor) {
        selectShippingMethodAction(methodData);
        checkoutData.setSelectedShippingRate(methodData['carrier_code'] + '_' + methodData['method_code']);
        window.dispatchEvent(new Event('billmateLock'));
        processor.saveShippingInformation().then(function () {
            window.dispatchEvent(new Event('billmateUpdate'));
        });
    }
})