define([
    'jquery',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/address-converter',
    'Magento_Checkout/js/checkout-data',
    'Magento_Checkout/js/model/shipping-save-processor/default',
    'Magento_Checkout/js/action/select-billing-address',
    'mage/url',
    'Magento_Ui/js/modal/alert',
], function(
    $,
    quote,
    addressConverter,
    checkoutData,
    shippingSaveProcessor,
    selectBillingAddress,
    mageurl,
    magealert
) {
    'use strict';

    /**
     * Format address from billmate iframe before handing it over to Magento
     * 
     * @param {Object} address
     * @returns {Object}
     */
    const _formatAddress = function (address) {
        let newAddressClone = Object.assign({}, address);
        newAddressClone.street = [address.street, address.street2];
        newAddressClone.postcode = address.zip;
        newAddressClone.telephone = address.phone;
        return addressConverter.formAddressDataToQuoteAddress(newAddressClone);
    }

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
            normalizedAddress.firstname === currentAddress.firstname,
            normalizedAddress.lastname === currentAddress.lastname,
            normalizedAddress.postcode === currentAddress.postcode,
            normalizedAddress.street[0] === currentAddress.street[0],
            normalizedAddress.street[1] === currentAddress.street[1],
            normalizedAddress.countryId === currentAddress.countryId,
            normalizedAddress.telephone === currentAddress.telephone,
        ];
        return !checks.every(Boolean);
    }

    /**
     * Handle address_selected event
     * 
     * @param {Object} data
     * @returns {Boolean}
     */
    const _handleAddressSelected = function (data) {
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

        if (action > 0) {
            shippingSaveProcessor.saveShippingInformation();
        }

        return action > 0;
    };

    const _handlePaymentMethodSelected = function (data) {
        const methodId = data.method;

        $.ajax({
            method: 'POST',
            url: mageurl.build('billmate/checkout/savePaymentMethod'),
            data: {methodId: methodId, form_key: $.mage.cookies.get('form_key')},
            dataType: 'json',
            beforeSend: function () {
                $(document.body).trigger('processStart');
            }
        }).done(function (response) {
            if (!response.success) {
                const message = response.message ?? this.options.genericErrorMessage;
                magealert({content: message});
            }
        }.bind(this))
        .fail(function (fail) {
            magealert({content: this.options.genericErrorMessage});
        }.bind(this))
        .always(function () {
            $(document.body).trigger('processStop');
        });
    }

    /**
     * Handle purchase_initialized event
     * 
     * @param {Object} data 
     */
    const _handlePurchaseInitialized = function (data) {
        $(this.options.purchaseInitializedHideTarget).toggle();
        $.ajax({
            method: 'POST',
            url: mageurl.build('billmate/checkout/purchaseInitialized'),
            data: {form_key: $.mage.cookies.get('form_key')},
            dataType: 'json'
        }).done(function (response) {
            if (!response.success) {
                const message = response.message ?? this.options.genericErrorMessage;
                magealert({content: message});
                $(this.options.purchaseInitializedHideTarget).toggle();
                return;
            }
            this._postMessage('purchase_complete');
        }.bind(this))
        .fail(function (fail) {
            magealert({content: this.options.genericErrorMessage});
            $(this.options.purchaseInitializedHideTarget).toggle();
        }.bind(this));
    }

    /**
     * Handle content_height event
     * 
     * @param {Object} data 
     */
    const _handleContentHeight = function (data) {
        var iframe = this.element;
        
        if (data && data > 0) {
            iframe.height(data + 'px');
        }
    }

    $.widget('billmate.checkoutHandler', {
        options: {
            genericErrorMessage: $.mage.__('Sorry, there has been an error processing your order. Please contact customer support.')
        },
        _isLocked: false,
        _invalidated: true,
        _eventHandlers: {
            'address_selected': _handleAddressSelected,
            'payment_method_selected': _handlePaymentMethodSelected,
            'purchase_initialized': _handlePurchaseInitialized,
            'content_height': _handleContentHeight
        },
        _create: function () {
            this._super();
            window.addEventListener('message', this._handleMessage.bind(this));
            window.addEventListener('billmateLock', this.lock.bind(this));
            window.addEventListener('billmateUpdate', this._handleBillmateUpdateEvent.bind(this));
            selectBillingAddress(
                addressConverter.formAddressDataToQuoteAddress(window.checkoutConfig.billingAddressFromData)
            );
        },

        /**
         * Handle message event
         * 
         * @param {Object} event 
         */
        _handleMessage: function (event) {
            const eventData = JSON.parse(event.data);
            const eventName = eventData.event;
            const iframeEventData = eventData.data;
            const handler = this._eventHandlers[eventName];
            if (typeof handler === 'function') {
                const runHandler = handler.bind(this);
                this._invalidated = runHandler(iframeEventData);
                this.update();
            }
        },

        /**
         * Handle event billmateUpdate
         */
        _handleBillmateUpdateEvent: function () {
            this._updateCheckoutBackend().then(function () {
                this.unlock();
            }.bind(this));
        },

        /**
         * Post message to the iframe
         * 
         * @param {String} message 
         */
        _postMessage: function (message) {
            this.element.context.contentWindow.postMessage(message, '*');
        },

        _updateCheckoutBackend: function () {
            return $.ajax(mageurl.build('billmate/checkout/updateCheckout'), {
                dataType: 'json',
                cache: false,
                method: 'GET'
            })
            .done(function (data) {
                this._invalidated = true;
                this.update();
            }.bind(this))
            .fail(function (fail) {
                magealert({content: $.mage.__('Sorry, we lost connection to Billmate. Reloading the page now.')});
            }.bind(this));
        },

        lock: function () {
            if (!this._isLocked) {
                this._postMessage('lock');
                this._isLocked = true;
            }
        },

        unlock: function () {
            if (this._isLocked) {
                this._postMessage('unlock');
                this._isLocked = false;
            }
        },

        update: function () {
            if(this._invalidated) {
                this._postMessage('update');
                this._invalidated = false;
            }
        }
    });
    return $.billmate.checkoutHandler;
})