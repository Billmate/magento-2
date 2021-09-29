define([
    'Magento_Checkout/js/view/cart/shipping-rates',
    'Magento_Checkout/js/model/checkout-data-resolver',
    'Billmate_NwtBillmateCheckout/js/checkout/action/save-shipping-method',
    'Magento_Checkout/js/model/shipping-save-processor/default'
], function(Component, checkoutDataResolver, saveShippingMethod, processor) {
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
            saveShippingMethod(methodData, processor);
            return true;
        }
    });
});