define([
        'ko', 
        'Magento_Catalog/js/price-utils',
        'Magento_Checkout/js/model/quote'
    ], 
    function (
        ko,
        priceUtils,
        quote
    ) {
    'use strict';

    return function (config) {
        const formatPrice = function (amount) {
            return priceUtils.formatPrice(amount, quote.getPriceFormat());
        }
        return {
            'formatPrice': formatPrice,
            'row_total': ko.observable(formatPrice(config.row_total)),
            'row_total_incl_tax': ko.observable(formatPrice(config.row_total_incl_tax))
        }
    }
});