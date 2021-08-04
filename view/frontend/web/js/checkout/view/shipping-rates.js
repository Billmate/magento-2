define([
    'Magento_Checkout/js/model/checkout-data-resolver',
    'Magento_Checkout/js/model/cart/estimate-service',
    'Magento_Checkout/js/view/cart/shipping-rates'
], function(checkoutDataResolver, estimateService, Component) {
    'use strict';

    return Component.extend({
        initObservable: function () {
            this._super();
            checkoutDataResolver.resolveEstimationAddress();
            return this;
        }
    });
});