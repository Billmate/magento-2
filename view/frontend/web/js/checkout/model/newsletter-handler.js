define([
    'jquery',
    'mage/url'
], function(
    $,
    mageurl
) {
    $.widget('billmate.newsletterHandler', {
        _create: function () {
            this._super();
            this._on(this.element, {
                'change': function () {
                    $.ajax({
                        method: 'POST',
                        url: this.element.action,
                        data: $(this.element).serialize(),
                        dataType: 'json'                    
                    })
                }.bind(this)
            })
        }
    });

    return $.billmate.newsletterHandler;
});