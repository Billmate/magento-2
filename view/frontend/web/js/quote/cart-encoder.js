define(function () {
    'use strict';

    /**
     * Provides a base64 encoding of relevant cart contents, useful for comparing contents of cart objects
     * 
     * @param {Object} cartData Expects 'cart-data' section from 'Magento_Customer/js/customer-data'
     */
    return function (cartData) {
        let data = {
            currency: cartData.totals.quote_currency_code,
            shipping_method: cartData.shippingMethodCode,
            address_country: cartData.address.countryId,
            subtotal: cartData.totals.base_subtotal_incl_tax,
            total: cartData.totals.base_grand_total,
            items: []
        };

        const items = cartData.totals.items;
        items.forEach(function (item) {
            data.items[item.id] = item.base_row_total_incl_tax;
        });

        return window.btoa(JSON.stringify(data));
    }
})