require([
    'Magento_Customer/js/customer-data'
], function (customerData) {
    let sections = ['cart'];
    customerData.reload(sections, true);
});