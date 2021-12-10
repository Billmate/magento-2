define([
    'jquery'
], function ($) {
    'use strict';

    $.widget('billmate.qtyAdjust', {
        options: {
            incrementSelector: '.input-number-increment',
            decrementSelector: '.input-number-decrement',
            inputSelector: '.input-text.qty'
        },

        _lastUpdateQty: null,
        _incrementElem: null,
        _decrementElem: null,
        _inputElem: null,
        _create: function () {
            this._incrementElem = $(this.element).find(this.options.incrementSelector);
            this._decrementElem = $(this.element).find(this.options.decrementSelector);
            this._inputElem = $(this.element).find(this.options.inputSelector);
            this._lastUpdateQty = this._inputElem.val(),
            this._bindClickEvent();
            this._bindBlurEvent();
        },

        _bindClickEvent: function () {
            this._on(this._incrementElem, {
                'click': function () {
                    let currentQty = this._inputElem.val();
                    let qty = parseInt(currentQty) + parseInt(1);
                    this._inputElem.val(qty);
                    this._dispatchSubmitEvent();
                }
            });

            this._on(this._decrementElem, {
                'click': function () {
                    let currentQty = this._inputElem.val();
                    if (currentQty > 1) {
                        let qty = parseInt(currentQty) - parseInt(1);
                        this._inputElem.val(qty);
                        this._dispatchSubmitEvent();
                    }
                }
            });
        },

        _bindBlurEvent: function () {
            this._on(this._inputElem, {
                'blur': function () {
                    if (this._inputElem.val() !== this._lastUpdateQty) {
                        this._dispatchSubmitEvent();
                    }
                }
            });
        },

        _dispatchSubmitEvent: function () {
            this._lastUpdateQty = this._inputElem.val();
            window.dispatchEvent(new Event('submitCart'));
        }
    });

    return $.billmate.qtyAdjust;
});
