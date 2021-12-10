define([
    'Magento_Checkout/js/view/cart/shipping-rates',
    'Magento_Checkout/js/model/checkout-data-resolver',
    'Magento_Checkout/js/action/select-shipping-method',
    'Magento_Checkout/js/model/shipping-save-processor/default',
    'Magento_Checkout/js/checkout-data',
    'Magento_Checkout/js/model/cart/estimate-service',
], function(Component, checkoutDataResolver, selectShippingMethodAction, shippingSaveProcessor, checkoutData) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Billmate_NwtBillmateCheckout/checkout/shipping-rates'
        },
        initObservable: function () {
            this._super();
            checkoutDataResolver.resolveEstimationAddress();
            return this;
        },

        /**
         * Set shipping method, extended to also set in backend
         * @param {String} methodData
         * @returns bool
         */
         selectShippingMethod: function (methodData) {
            selectShippingMethodAction(methodData);
            checkoutData.setSelectedShippingRate(methodData['carrier_code'] + '_' + methodData['method_code']);
            window.dispatchEvent(new Event('billmateLock'));
            shippingSaveProcessor.saveShippingInformation().then(function () {
                window.dispatchEvent(new Event('billmateUpdate'));
            });
            return true;
        }
    });
});