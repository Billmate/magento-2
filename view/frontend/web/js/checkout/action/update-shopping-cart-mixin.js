define([
        'jquery',
        'uiRegistry',
        'Billmate_NwtBillmateCheckout/js/checkout/action/reload-checkout'
    ],
    function($, uiRegistry, reloadTotals) {
        'use strict';

        /**
         * @param {Object} item 
         */
        const setNewSubtotals = function (item) {
            const component = uiRegistry.get('billmate-checkout-itemid-' + item.item_id + '-subtotal')
            let newRowTotal = item.row_total + item.weee_tax_applied_row_amount;
            let newRowTotalInclTax = item.row_total_incl_tax + item.weee_tax_applied_row_amount;

            component.row_total(component.formatPrice(newRowTotal));
            component.row_total_incl_tax(component.formatPrice(newRowTotalInclTax));
        }

        const updateShoppingCartMixin = {
            submitForm: function () {

                if(!$('body').hasClass('billmate-checkout-index')) {
                    this.element
                        .off('submit', this.onSubmit)
                        .on('submit', function () {
                            $(document.body).trigger('processStart');
                        })
                        .submit();
                }

                let requestData = $(this.element).serialize() + '&billmate=1';
                $.ajax({
                    url: $(this.element).context.action,
                    data: requestData,
                    type: 'post',
                    context: this,
    
                    /** @inheritdoc */
                    beforeSend: function () {
                        $(document.body).trigger('processStart');
                    },
    
                    /** @inheritdoc */
                    complete: function () {
                        $(document.body).trigger('processStop');
                    }
                })
                .done(function (response) {
                    reloadTotals(response);
                    let items = response.items;
                    Object.values(items).forEach(function (item) {
                        setNewSubtotals(item);
                    });
                });
            },
        };

        return function(targetWidget) {
            $.widget('mage.updateShoppingCart', targetWidget, updateShoppingCartMixin);
            return $.mage.updateShoppingCart;
        }
    }
)