define([
    'jquery'
], function ($) {
    'use strict';

    $.widget('nwt.qtyAdjust', {

        _create: function () {
            this._bindClickEvent();
        },

        _bindClickEvent: function () {
            // TODO: Add AJAX on quantity change
            
            $(this.element).on('click', function () {
                var inputId = $(this).data('product-id'),
                    input = $("#cart-" + inputId + "-qty"),
                    currentQty = input.val(),
                    dataSubmitUrl = input.data('cart-url-submit');
                    // dataRefreshUrl = input.data('cart-url-update'),
    
                if ($(this).hasClass('input-number-increment')) {
                    var qty = parseInt(currentQty) + parseInt(1);
    
                    $("#cart-" + inputId + "-qty").val(qty);
                } else {
                    if (currentQty > 1) {
                        var qty = parseInt(currentQty) - parseInt(1);
    
                        $("#cart-" + inputId + "-qty").val(qty); 
                    }
                }
            })
        }
    });

    return $.nwt.qtyAdjust;
});
