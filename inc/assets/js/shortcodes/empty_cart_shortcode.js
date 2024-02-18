jQuery(document).ready(function($) {
    // Empty cart button click handler
    $('#emptyCart').click(function() {
        $.ajax({
            url: window.php_vars.ajaxurl,
            type: 'POST',
            data: {
                action: 'empty_cart'
            },
            success: function(response) {
                var data = JSON.parse(response);
                if (data.success) {
                    var userConfirmed = confirm('Cart emptied successfully! Click OK to refresh the page.');
                    if (userConfirmed) {
                        location.reload();  // Refresh the page
                    }
                } else {
                    alert('Error emptying cart: ' + data.message);
                }
            }
        });
    });

    // Enable/disable the EMPTY CART button based on the cart status
    function checkCartStatus() {
        $.ajax({
            url: window.php_vars.ajaxurl,
            type: 'POST',
            data: {
                action: 'check_cart_status'
            },
            success: function(response) {
                var data = JSON.parse(response);
                $('#emptyCart').prop('disabled', !data.cart_has_items); // Enable/disable the button based on cart status
            }
        });
    }
    checkCartStatus();
});