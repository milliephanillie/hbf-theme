jQuery(function($) {
    // Hide the second select2 container immediately
    $('.woocommerce-input-wrapper .select2-container:nth-child(2)').hide();

    // Prevent form submission on Enter key press
    $(window).keydown(function(event) {
        if (event.keyCode == 13) {
            event.preventDefault();
            return false;
        }
    });

    // Debounce function to limit the rate at which a function can fire.
    var debounce = function(func, delay) {
        var inDebounce;
        return function() {
            var context = this;
            var args = arguments;
            clearTimeout(inDebounce);
            inDebounce = setTimeout(function() {
                func.apply(context, args);
            }, delay);
        }
    };

    // Handle changes to the additional fee fields
    $('form.checkout').on('change', '#shipping-amount, #admin-fee, #pallet-fee, #misc-fee', function() {
        var feeType = $(this).attr('id');
        var feeValue = $(this).val().trim();

        feeValue = feeValue === '' ? 0 : parseFloat(feeValue);


        $.ajax({
            type: 'POST',
            url: window.php_vars.adminAjaxUrl,
            data: {
                'action': 'update_custom_fees',
                'fee_type': feeType,
                'fee_value': feeValue,
                'security': window.php_vars.update_session_nonce
            },
            success: function(response) {
                if (response.success) {
                    $('body').trigger('update_checkout');
                }
            },
        });
    });

    // Handle changes to other specified form fields
    $('form.checkout').on('change', 'select#chosen_order_type, #billing_postcode', debounce(function() {
        var val = $(this).val();
        var type = $(this).data('type');

        if (type === 'chosen_order_type' && val === 'national') {
            // Handle national selection without reloading the page
            // For example, reset fields or adjust the form
            // ...
            return; // Exit the function if 'national' is selected
        }

        $.ajax({
            type: 'POST',
            url: window.php_vars.ajax_url,
            data: {
                'action': 'woo_get_ajax_data',
                'type': type,
                'val': val,
                'security': window.php_vars.update_session_nonce // Use the new nonce here
            },
            success: function(result) {
                if (type === 'chosen_order_type') {
                    if (val === 'international') {
                        $('.manual-order-row:not(.order-type)').show();
                    } else {
                        $('.manual-order-row:not(.order-type)').hide();
                    }
                }
                // Trigger update_checkout only if necessary
                if (result.need_update) {
                    $('body').trigger('update_checkout');
                }
            },
            error: function(error) {
                alert('An error occurred. Please try again.'); // User-facing error message
            }
        });
    }, 250)); // 250ms debounce
});