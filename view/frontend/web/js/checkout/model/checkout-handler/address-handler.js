define([
    'jquery',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/address-converter',
    'Magento_Checkout/js/model/shipping-save-processor/default',
    'Magento_Checkout/js/action/set-billing-address',
    'Magento_Customer/js/model/customer',
], function (
    $,
    quote,
    addressConverter,
    shippingSaveProcessor,
    setBillingAddressAction,
    customerModel
) {
    'use strict';

    return function () {

        /**
         * Format address from billmate iframe before handing it over to Magento
         * 
         * @param {Object} address
         * @returns {Object}
         */
        const _formatAddress =  function (address) {
            let newAddressClone = Object.assign({}, address);
            newAddressClone.street = [address.street, address.street2];
            newAddressClone.postcode = address.zip;
            newAddressClone.telephone = address.phone;
            newAddressClone.countryId = address.country;
            return addressConverter.formAddressDataToQuoteAddress(newAddressClone);
        };

        /**
         * Compare two addresses
         * 
         * @param {Object} newAddress
         * @param {Object} currentAddress
         * @returns {boolean} True if data differs
         */
        const _addressHasChanged = function (newAddress, currentAddress) {
            if (typeof currentAddress !== 'object' ||
                $.isEmptyObject(currentAddress) ||
                typeof currentAddress.street !== 'object') {
                return true;
            }

            // Normalize if first address is not a Magento formatted address
            let normalizedAddress = newAddress;
            if (typeof normalizedAddress.getType !== 'function') {
                normalizedAddress = _formatAddress(newAddress);
            }

            const checks = [
                normalizedAddress.email === currentAddress.email,
                normalizedAddress.firstname === currentAddress.firstname,
                normalizedAddress.lastname === currentAddress.lastname,
                normalizedAddress.postcode === currentAddress.postcode,
                normalizedAddress.street[0] === currentAddress.street[0],
                normalizedAddress.street[1] === currentAddress.street[1],
                normalizedAddress.countryId === currentAddress.countryId,
                normalizedAddress.telephone === currentAddress.telephone,
            ];
            return !checks.every(Boolean);
        };

        /**
         * Handle data from address_selected event
         * Save the provided address data as quote billing and shipping address, as appropriate
         * 
         * @param {Object} data
         * @returns {Boolean}
         */
        const handleAddressSelected = function (data) {
            let action = 0;
            const customer = data.Customer;

            // Address state checks

            /**
             * Billing address form was updated
             */
            const billingAddressChanged = (
                typeof data.billingAddress === 'object' &&
                _addressHasChanged(data.billingAddress, quote.billingAddress())
            );

            /**
             * Billing address was loaded after customer entered their pno
             */
            const billingAddressFromPno = (
                typeof data.billingAddress !== 'object' && // Ignored if data from form exists
                typeof customer === 'object' &&
                typeof customer.Billing === 'object' &&
                _addressHasChanged(customer.Billing, quote.billingAddress())
            );

            /**
             * Customer entered a shipping address
             */
            const shippingAddressEntered = (
                typeof data.shippingAddress === 'object'
            );

            /**
             * Shipping address from pno exists
             */
            const shippingAddressPnoExists = (
                typeof customer === 'object' &&
                typeof customer.Shipping === 'object' &&
                !!customer.Shipping.zip
            );

            /**
             * Shipping address was loaded after customer entered their pno
             */
            const shippingAddressFromPno = (
                typeof data.shippingAddress !== 'object' && // Ignored if data from form exists
                shippingAddressPnoExists &&
                _addressHasChanged(customer.Shipping, quote.shippingAddress())
            );

            /**
             * Shipping address form was updated
             */
            const shippingAddressChanged = (
                typeof data.shippingAddress === 'object' &&
                !$.isEmptyObject(data.shippingAddress) &&
                _addressHasChanged(data.shippingAddress, quote.shippingAddress())
            );

            /**
             * Shipping address was removed
             * In this case we must set shipping address to be equal to the billing address
             */
            const shippingAddressRemoved = (
                // When separate shipping address is un-selected, data.shippingAddress will be an empty object
                typeof data.shippingAddress === 'object' &&
                $.isEmptyObject(data.shippingAddress) &&
                _addressHasChanged(quote.billingAddress(), quote.shippingAddress())
            );

            const billingUpdate = (billingAddressChanged || billingAddressFromPno);
            const shippingUpdate = (shippingAddressChanged || shippingAddressFromPno);

            /**
             * Bitwise Actions map:
             * 1: Set quote shipping address equal to quote billing address
             * 2: Update quote billing address from data
             * 4: Update quote shipping address from data
             */
            action |= (billingUpdate) ? 2 : 0;
            action |= ((billingUpdate && !(shippingAddressEntered || shippingAddressPnoExists)) || shippingAddressRemoved) ? 1 : 0;
            action |= (shippingUpdate) ? 4 : 0;

            if ((action & 4) > 0) {
                const newShippingAddress = (shippingAddressPnoExists) ? customer.Shipping : data.shippingAddress;
                quote.shippingAddress(_formatAddress(newShippingAddress));
            }

            if ((action & 2) > 0) {
                const newBillingAddress = (billingAddressFromPno) ? customer.Billing : data.billingAddress;
                quote.billingAddress(_formatAddress(newBillingAddress));
            }

            if ((action & 1) > 0) {
                quote.shippingAddress(quote.billingAddress());
            }

            // Save to backend if any actions were done
            if (action > 0) {
                if (quote.isVirtual()) {
                    setBillingAddressAction();
                } else {
                    shippingSaveProcessor.saveShippingInformation();
                }

                if (!customerModel.isLoggedIn()) {
                    quote.guestEmail = quote.billingAddress().email;
                }
            }

            return action > 0;
        }

        return handleAddressSelected;
    }
});