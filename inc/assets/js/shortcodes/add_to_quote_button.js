jQuery(document).ready(function($) {
    $("#add-to-quote-btn").on("click", function() {
        var nonce = $(this).data("nonce");
        var customerInfo = ' . json_encode(WC()->customer->get_billing()) . ';

        // Prepare the order data
        var orderData = {
            action: "create_quote_from_order",
            nonce: nonce,
            customer_info: customerInfo
            // Add any additional order data needed here
        };

        // Perform the AJAX request
        $.ajax({
            url: window.php_vars.adminAjaxUrl,
            type: "POST",
            data: orderData,
            success: function(response) {
                if (response.success) {
                    alert("Quote created successfully.");
                    // Implement redirect if needed
                } else {
                    alert(response.data.message || "Error creating quote.");
                }
            },
            error: function(error) {
                console.log(error);
                alert("Error creating quote.");
            }
        });
    });
});