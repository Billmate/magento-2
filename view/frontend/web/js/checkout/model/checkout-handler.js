define([
    'jquery',
    'Billmate_NwtBillmateCheckout/js/checkout/model/checkout-handler/address-handler',
    'Magento_Checkout/js/action/select-billing-address',
    'Magento_Checkout/js/model/address-converter',
    'mage/url',
    'Magento_Ui/js/modal/alert',
], function(
    $,
    addressHandler,
    selectBillingAddress,
    addressConverter,
    mageurl,
    magealert
) {
    'use strict';

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
            'address_selected': addressHandler(),
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
                this._invalidated = handler(iframeEventData);
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