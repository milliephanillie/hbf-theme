jQuery(document).ready(function($) {
	// Define original_order_id 
    var urlParams = new URLSearchParams(window.location.search);
    var original_order_id = urlParams.get('original_order_id');
    
	$('#payLaterButton').on('click', function() {
        if (!confirm("Are you sure you want to proceed with Pay Later?")) {
            return; // Stop if the user cancels the action
        }

        var nonce = $(this).data('nonce');
        $.ajax({
        // existing AJAX setup...
        success: function(response) {
            if (response.success) {
                // Redirect using a constructed URL
                var orderID = response.data.order_id;
                window.location.href = '/pay-later-thank-you/?order_id=' + orderID;
            } else {
                // Handle error
                alert(response.data.message || 'Error processing Pay Later order.');
            }
        },
        error: function(error) {
            console.log(error);
            alert('Error processing Pay Later order.');
        }
    });
});
// Handler for applying payment
    $('#apply-payment').on('click', function() {
        var amount = $('#payment-amount').val();
        var method = $('#payment-method').val();
        var order_id = new URLSearchParams(window.location.search).get('order_id');

        $.ajax({
            url: window.php_vars.adminAjaxUrl,
            type: 'POST',
            data: {
                action: 'apply_payment_to_order',
                order_id: order_id,
                amount: amount,
                method: method
            },
            success: function(response) {
                if (response.success) {
                    redirectToCheckoutWithOrderId();
                } else {
                    alert(response.data.message || 'Error applying payment.');
                }
            },
            error: function(error) {
                alert('Error applying payment.');
            }
        });
    });

    function redirectToCheckoutWithOrderId() {
        if (original_order_id) {
            window.location.href = '/checkout/?original_order_id=' + original_order_id;
        } else {
            alert("Order ID not found.");
        }
    }

    // Using the Checkout to apply a payment to an existing order
    $('form.checkout').on('submit', function(e) {
        var payment_amount = $('#payment-amount').val();

        if (original_order_id && payment_amount) {
            e.preventDefault(); // Prevent the default form submission

            $.ajax({
                url: window.php_vars.adminAjaxUrl,
                type: 'POST',
                data: {
                    action: 'apply_partial_payment_to_order',
                    original_order_id: original_order_id,
                    payment_amount: payment_amount
                },
                success: function(response) {
                    if (response.success) {
                        alert('Payment applied successfully');
                        window.location.href = '/order-confirmation?order_id=' + original_order_id;
                    } else {
                        alert(response.data.message || 'Error applying payment');
                    }
                },
                error: function(error) {
                    alert('Error applying payment');
                }
            });
        }
    });
	
    // Handler for updating billing information
    $('#update-billing').on('click', function() {
        var order_id = new URLSearchParams(window.location.search).get('order_id');
        var billingData = {
            first_name: $('#billing-first-name').val(),
            last_name: $('#billing-last-name').val(),
            email: $('#billing-email').val(),
            phone: $('#billing-phone').val()
            // Add other billing fields here if needed
        };

        $.ajax({
            url: window.php_vars.adminAjaxUrl,
            type: 'POST',
            data: {
                action: 'update_billing_information',
                order_id: order_id,
                billing_data: billingData
            },
            success: function(response) {
                // Handle success
                if (response.success) {
                    alert('Billing information updated successfully.');
                } else {
                    alert(response.data.message || 'Error updating billing information.');
                }
            },
            error: function(error) {
                // Handle error
                alert('Error updating billing information.');
            }
        });
    });
	// Handler for Go to Payment button
	$('.go-to-payment-page').on('click', function(e) {
		e.preventDefault();
		var orderId = $(this).data('order-id');
		var userId = $(this).data('user-id'); 

		$.ajax({
			url: window.php_vars.adminAjaxUrl,
			type: 'POST',
			data: {
				action: 'handle_payment_redirect',
				order_id: orderId,
				user_id: userId,
				_ajax_nonce: php_vars.switch_nonce 
			},
			success: function(response) {
				if (response.success) {
					window.location.href = response.checkout_url;
				} else {
					alert('Could not proceed to payment: ' + response.message);
				}
			},
			error: function(jqXHR, textStatus, errorThrown) {
				alert('AJAX Error: ' + errorThrown);
			}
		});
	});
	
    // Handler for sending invoice
    $('#send-invoice').on('click', function() {
        var emailHBD = $('#email-invoice-hbd').is(':checked');
        var emailCustomer = $('#email-invoice-customer').is(':checked');
        var order_id = new URLSearchParams(window.location.search).get('order_id');

        $.ajax({
            url: window.php_vars.adminAjaxUrl,
            type: 'POST',
            data: {
                action: 'send_invoice_email',
                order_id: order_id,
                email_hbd: emailHBD,
                email_customer: emailCustomer
            },
            success: function(response) {
                // Handle success
                if (response.success) {
                    alert('Invoice sent successfully.');
                } else {
                    alert(response.data.message || 'Error sending invoice.');
                }
            },
            error: function(error) {
                // Handle error
                alert('Error sending invoice.');
            }
        });
// Handler for updating custom fee
$('#update-fees').on('click', function() {
    var orderId = $(this).data('order-id');
    var adminFee = $('#admin-fee').val();
    var palletFee = $('#pallet-fee').val();
    var miscFee = $('#misc-fee').val();

    $.ajax({
        url: window.php_vars.adminAjaxUrl,
        type: 'POST',
        data: {
            action: 'update_custom_fees',
            order_id: orderId,
            admin_fee: adminFee,
            pallet_fee: palletFee,
            misc_fee: miscFee
        },
        success: function(response) {
            if (response.success) {
                // Update the page to reflect the changes
                alert('Fees updated successfully.');
            } else {
                alert(response.data.message || 'Error updating fees.');
            }
        },
        error: function(error) {
            alert('Error updating fees.');
        }
    });
});
// Handler for updating custom shipping
$('#update-custom-shipping').on('click', function() {
    var order_id = $(this).data('order-id');
    var customShipping = $('#custom-shipping').val();

    $.ajax({
        url: window.php_vars.adminAjaxUrl,
        type: 'POST',
        data: {
            action: 'update_custom_shipping',
            order_id: order_id,
            custom_shipping: customShipping
        },
        success: function(response) {
            if (response.success) {
                alert('Custom shipping updated successfully.');
                // Update the page content or refresh the page to show new shipping
            } else {
                alert(response.data.message || 'Error updating custom shipping.');
            }
        },
        error: function(error) {
            console.log(error);
            alert('Error updating custom shipping.');
        }
    });
});
		});
	});