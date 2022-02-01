define([
    'jquery',
    'Billmate_NwtBillmateCheckout/js/checkout/model/checkout-handler/address-handler',
    'Magento_Checkout/js/action/select-billing-address',
    'Magento_Checkout/js/action/select-shipping-address',
    'Magento_Checkout/js/model/address-converter',
    'Magento_Checkout/js/model/quote',
    'mage/url',
    'Magento_Ui/js/modal/alert',
], function(
    $,
    addressHandler,
    selectBillingAddress,
    selectShippingAddress,
    addressConverter,
    quote,
    mageurl,
    magealert
) {
    'use strict';

    /**
     * Handle purchase_initialized event
     * 
     * @param {Object} data 
     */
    const _handlePurchaseInitialized = function (data) {
        window.dispatchEvent(new Event('disableCartAutoUpdate'));
        $(this.options.purchaseInitializedHideTarget).hide();
        $.ajax({
            method: 'POST',
            url: mageurl.build('billmate/checkout/purchaseInitialized'),
            data: {form_key: $.mage.cookies.get('form_key')},
            dataType: 'json'
        }).done(function (response) {
            if (!response.success) {
                const message = response.message ?? this.options.defaultErrorMessage;
                magealert({content: message});
                return;
            }
            this._postMessage('purchase_complete');
        }.bind(this))
        .fail(function (fail) {
            magealert({content: this.options.defaultErrorMessage});
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

    /**
     * Handle checkout_success event
     * 
     * @param {Object} data 
     */
    const _handleCheckoutSuccess = function (data) {
        if (this._checkoutComplete) {
            return;
        }

        $.ajax({
            method: 'POST',
            url: mageurl.build('billmate/checkout/successEvent'),
            data: {form_key: $.mage.cookies.get('form_key')},
            dataType: 'json'
        }).done(function (response) {
            if (!response.success) {
                const message = response.message ?? this.options.defaultErrorMessage;
                magealert({content: message});
                return;
            }
            this.checkoutComplete = true;
            location.href = mageurl.build('billmate/checkout/success');
        }.bind(this))
        .fail(function () {
            magealert({content: this.options.defaultErrorMessage});
        }.bind(this));
    }

    $.widget('billmate.checkoutHandler', {
        _isLocked: false,
        _invalidated: true,
        _checkoutComplete: false,
        _eventHandlers: {
            'address_selected': addressHandler(),
            'purchase_initialized': _handlePurchaseInitialized,
            'content_height': _handleContentHeight,
            'checkout_success': _handleCheckoutSuccess,
        },
        _create: function () {
            this._super();
            quote.paymentMethod({method: this.options.methodCode, title: this.options.methodTitle});
            quote.shippingMethod(window.checkoutConfig.selectedShippingMethod);
            window.addEventListener('message', this._handleMessage.bind(this));
            window.addEventListener('billmateLock', this.lock.bind(this));
            window.addEventListener('billmateUpdate', this._handleBillmateUpdateEvent.bind(this));
            this._postMessage('update'); // To capture pre-loaded address if present
            selectBillingAddress(
                addressConverter.formAddressDataToQuoteAddress(window.checkoutConfig.billingAddressFromData)
            );
            selectShippingAddress(
                addressConverter.formAddressDataToQuoteAddress(window.checkoutConfig.shippingAddressFromData)
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

        /**
         * Sends an updateCheckout request to the Billmate API
         */
        _updateCheckoutBackend: function () {
            return $.ajax(mageurl.build('billmate/checkout/updateCheckout'), {
                dataType: 'json',
                cache: false,
                method: 'GET'
            })
            .done(function (data) {
                if (!data.success) {
                    magealert({content: this.options.defaultErrorMessage});
                    return;
                }
                this._invalidated = true;
                this.update();
            }.bind(this))
            .fail(function (fail) {
                magealert({
                    content: $.mage.__('Sorry, we lost connection to Billmate. Reloading the page now.'),
                    actions: {
                        always: function () {
                            location.reload();
                        }
                    }
                });
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