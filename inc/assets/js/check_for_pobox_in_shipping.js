jQuery(document).ready(function($) {
    function checkAddressForPOBox(address) {
        return (address.match(/p\.?o\.?\s?box/i) !== null);
    }

    function showAlertIfPOBox() {
        var shippingAddress = $("#shipping_address_1").val();
        var billingAddress = $("#billing_address_1").val();

        if (checkAddressForPOBox(shippingAddress) || checkAddressForPOBox(billingAddress)) {
            alert("Sorry, we do not ship to PO BOX addresses. Please update the form with a valid street address. Alternatively, if you need to use a PO Box, please call us toll free at 1-800-346-0269 and we will be happy to assist you.");
        }
    }
    $("#shipping_address_1, #billing_address_1").on("change", function() {
        showAlertIfPOBox();
    });

    // Initial check on page load
    showAlertIfPOBox();
});