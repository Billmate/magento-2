define([
    'jquery',
    'Billmate_NwtBillmateCheckout/js/checkout/action/reload-checkout'
], function ($, reloadTotals) {
    'use strict';

    $.widget('mage.billmateCheckoutDiscountCode', {
        options: {
        },

        /** @inheritdoc */
        _create: function () {
            this.couponCode = $(this.options.couponCodeSelector);
            this.removeCoupon = $(this.options.removeCouponSelector);

            $(this.options.applyButton).on('click', $.proxy(function () {
                this.couponCode.attr('data-validate', '{required:true}');
                this.removeCoupon.attr('value', '0');
                $(this.element).validation();

                let requestData = $(this.element).serialize();
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
                .done($.proxy(function (response) {
                    reloadTotals(response);
                    if(!response.errors) {
                        $(this.options.applyButton).closest('div').toggle();
                        this.couponCode.attr('disabled', 'disabled');
                        $(this.options.cancelButton).closest('div').toggle();
                    }
                }, this));
            }, this));

            $(this.options.cancelButton).on('click', $.proxy(function () {
                this.couponCode.removeAttr('data-validate');
                this.removeCoupon.attr('value', '1');

                let requestData = $(this.element).serialize();
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
                .done($.proxy(function (response) {
                    reloadTotals(response);
                    if(!response.errors) {
                        $(this.options.applyButton).closest('div').toggle();
                        this.couponCode.removeAttr('disabled');
                        $(this.options.cancelButton).closest('div').toggle();
                    }
                }, this));
            }, this));
        }
    });

    return $.mage.billmateCheckoutDiscountCode;
});
