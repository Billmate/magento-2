var config = {
    config: {
        mixins: {
            'Magento_Checkout/js/action/update-shopping-cart': {
                'Billmate_NwtBillmateCheckout/js/checkout/action/update-shopping-cart-mixin': true
            }
        }
    },
    paths: {
        slick: 'Billmate_NwtBillmateCheckout/js/lib/slick.min'
    },
    shim: {
        slick: {
            deps: ['jquery']
        }
    }
};