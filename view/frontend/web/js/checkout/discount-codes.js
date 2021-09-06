define([
    'jquery',
    'Billmate_NwtBillmateCheckout/js/checkout/action/reload-totals'
], function ($, reloadTotals) {
    'use strict';

    const _submitHandler = function (event) {
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
        .done(function (response) {
            reloadTotals(response);
            if(!response.errors) {
                $(this.options.applyButton).closest('div').toggle();
                this.couponCode.attr('disabled', 'disabled');
                $(this.options.cancelButton).closest('div').toggle();
            }
        }.bind(this));

        if (event.type === 'submit') {
            return false;
        }
    };

    $.widget('billmate.checkoutDiscountCode', {
        options: {
        },

        /** @inheritdoc */
        _create: function () {
            this.couponCode = $(this.options.couponCodeSelector);
            this.removeCoupon = $(this.options.removeCouponSelector);

            $(this.options.applyButton).on('click', _submitHandler.bind(this));
            $(this.element).on('submit', _submitHandler.bind(this));

            $(this.options.cancelButton).on('click', function () {
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
                .done(function (response) {
                    reloadTotals(response);
                    if(!response.errors) {
                        $(this.options.applyButton).closest('div').toggle();
                        this.couponCode.removeAttr('disabled');
                        $(this.options.cancelButton).closest('div').toggle();
                    }
                }.bind(this));
            }.bind(this));
        },
    });

    return $.billmate.checkoutDiscountCode;
});
