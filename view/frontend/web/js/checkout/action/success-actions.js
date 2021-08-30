define(
    ['Magento_Customer/js/customer-data'],
    function (customerData) {
        let sections = ['cart'];
        customerData.invalidate(['cart']);
        customerData.reload(sections, true);
    }
);