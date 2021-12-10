define(['ko'], function (ko) {
    'use strict';

    return function (config) {
        return {
            'couponCode': ko.observable(config.couponCode),
            'hasCouponCode': ko.computed(function () {
                return (typeof this.couponCode === 'string' && this.couponCode.length > 0)
            }, this)
        }
    }
});