define([
        'jquery',
        'ko',
        'underscore',
        'uiRegistry',
        'mage/url',
        'Magento_Ui/js/modal/alert',
        'Magento_Ui/js/modal/confirm',
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/cart/totals-processor/default',
        'Billmate_NwtBillmateCheckout/js/quote/cart-encoder',
        'Billmate_NwtBillmateCheckout/js/checkout/view/item/subtotal',
        'Billmate_NwtBillmateCheckout/js/checkout/action/reload-totals',
    ],
    function(
        $,
        ko,
        _,
        uiRegistry,
        mageurl,
        magealert,
        confirm,
        customerData,
        quote,
        totalsDefaultProvider,
        cartEncoder,
        subtotalViewModel,
        reloadTotals
    ) {
        'use strict';

        /**
         * Update cart item subtotal view model with new totals
         * 
         * @param {Object} item 
         */
        const _setNewSubtotals = function (item) {
            const component = uiRegistry.get('billmate-checkout-itemid-' + item.item_id + '-subtotal')
            if (!component) {
                return;
            }

            const newRowTotal = item.row_total + item.weee_tax_applied_row_amount;
            const newRowTotalInclTax = item.row_total_incl_tax + item.weee_tax_applied_row_amount;

            component.row_total(component.formatPrice(newRowTotal));
            component.row_total_incl_tax(component.formatPrice(newRowTotalInclTax));
        }

        const updateShoppingCartMixin = {
            options: {
                removerSelector: '.action-delete',
                tableSelector: '#shopping-cart-table',
                itemSubtotalSelector: '.subtotal > span',
                qtyAdjustSelector: '.control.qty'
            },

            _privateContentVersion: null,
            _encodedCart: null,
            _disableAutoUpdate: false,
            _create: function () {
                this._super();
                if (!this._isBillmateCheckout()) {
                    return this;
                }

                window.addEventListener('submitCart', function () {
                    this._disableAutoUpdate = true;
                    this.element.submit();
                }.bind(this));

                window.addEventListener('disableCartAutoUpdate', function () {
                    this._disableAutoUpdate = true;
                }.bind(this));

                window.addEventListener('enableCartAutoUpdate', function () {
                    this._disableAutoUpdate = false;
                }.bind(this));

                window.addEventListener('updatePrivateContentVersion', function () {
                    this._setNewPrivateContentVersion();
                }.bind(this));

                this._bindEventsToElements();
                this._setNewPrivateContentVersion();
                this._encodedCart = cartEncoder(customerData.get('cart-data')());

                // A watcher that detects changes to the cart from elsewhere, such as a second tab
                setInterval(function () {
                    const cookiePrivateContentVersion = $.mage.cookies.get('private_content_version');
                    if (cookiePrivateContentVersion === this._privateContentVersion || this._disableAutoUpdate) {
                        return;
                    }

                    this._privateContentVersion = cookiePrivateContentVersion;

                    customerData.reload(['cart']);
                    this._disableAutoUpdate = true;

                    // This updates cart cache
                    totalsDefaultProvider.estimateTotals(quote.shippingAddress()).then(function () {
                        const newEncodedCart = cartEncoder(customerData.get('cart-data')());
                        if (this._encodedCart === newEncodedCart) {
                            this._disableAutoUpdate = false;
                            return;
                        }

                        magealert({
                            content: 'Cart was updated in another tab/window. Reloading checkout now.',
                            actions: {
                                always: function () {
                                    location.reload();
                                }
                            }
                        });
                    }.bind(this));

                }.bind(this), 2000);
            },

            /**
             * Makes the update cart form submit into an ajax request
             */
            submitForm: function () {

                // Original widget function
                if(!this._isBillmateCheckout()) {
                    this._super();
                    return true;
                }

                let requestData = $(this.element).serialize() + '&billmate=1';
                $.ajax({
                    url: $(this.element).context.action,
                    data: requestData,
                    method: 'POST',
                    dataType: 'json',
                    context: this,

                    beforeSend: function () {
                        $(document.body).trigger('processStart');
                        this._disableAutoUpdate = true;
                    }
                })
                .done(function (response) {
                    reloadTotals(response);
                    let items = response.items;
                    Object.values(items).forEach(function (item) {
                        _setNewSubtotals(item);
                    });
                    this._setNewPrivateContentVersion();
                })
                .always(function () {
                    $(document.body).trigger('processStop');
                    this._disableAutoUpdate = false;
                });
            },

            /**
             * Handle removal of item in cart, making it an ajax request
             * 
             * @param {Object} event 
             */
            _removeHandler: function (event) {
                event.preventDefault();
                confirm({
                    content: $.mage.__(
                        'Are you sure you want to remove %1 from the cart?'
                    ).replace('%1', $(event.target).attr('data-item-name')),
                    buttons: [{
                        text: $.mage.__('No'),
                        class: 'action-secondary action-dismiss',
                        click: function (event) {
                            this.closeModal(event);
                        }
                    }, {
                        text: $.mage.__('Yes'),
                        class: 'action-primary action-accept',
                        click: function (event) {
                            this.closeModal(event, true);
                        }
                    }],
                    actions: {
                        confirm: function () {
                            $.ajax(mageurl.build('checkout/cart/delete'), {
                                dataType: 'json',
                                method: 'post',
                                context: this,
                                data: {
                                    id: $(event.target).attr('data-item-id'),
                                    form_key: $.mage.cookies.get('form_key'),
                                    billmate: 1
                                },
                                beforeSend: function () {
                                    $(document.body).trigger('processStart');
                                    this._disableAutoUpdate = true;
                                }
                            })
                            .done(function (response) {
                                reloadTotals(response);
                                // We receive new html for all cart items
                                this._setNewPrivateContentVersion();
                                this._updateCartItems(response.carthtml);
                            })
                            .fail(function (fail) {
                                magealert({content: $.mage.__('Failed to remove item')});
                            })
                            .always(function () {
                                $(document.body).trigger('processStop');
                                this._disableAutoUpdate = false;
                            });
                        }.bind(this)
                    }
                });
            },

            /**
             * Replace current cart items html
             * 
             * @param {String} html new cart items html
             */
            _updateCartItems: function (html) {
                const newCartPlaceholder = $('<div class="cart-placeholder" />').html(html).toggle();
                $(this.options.tableSelector).find('tbody').remove();
                $(this.options.tableSelector).append(newCartPlaceholder.find('tbody'));

                // Re-bind element-bound events since we've replaced the html elements
                this._bindEventsToElements();

                $(this.options.tableSelector).find('tbody').each(function (index, elem) {
                    // We must also reapply knockout binding to price display, and reinitialize widget on qty adjustment buttons
                    ko.applyBindings(
                        subtotalViewModel({}),
                        _.first($(elem).find(this.options.itemSubtotalSelector))
                    );

                    _.first($(elem).find(this.options.qtyAdjustSelector).qtyAdjust());

                }.bind(this));
            },

            /**
             * Setup element-bound event handlers
             */
            _bindEventsToElements: function () {
                if (!this._isBillmateCheckout()) {
                    return;
                }

                this._on($(this.element.find(this.options.removerSelector)), {
                    'click': this._removeHandler
                });
            },

            /**
             * @returns {Boolean}
             */
            _isBillmateCheckout: function () {
                return $('body').hasClass('billmate-checkout-index');
            },

            _setNewPrivateContentVersion() {
                this._privateContentVersion = $.mage.cookies.get('private_content_version');
            }
        };

        return function(targetWidget) {
            $.widget('mage.updateShoppingCart', targetWidget, updateShoppingCartMixin);
            return $.mage.updateShoppingCart;
        }
    }
)