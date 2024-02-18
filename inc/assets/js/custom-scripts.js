jQuery(document).ready(function($) {
    // Check if we just switched the user
    if (sessionStorage.getItem('userSwitched') === 'true') {
        // Clear the flag
        sessionStorage.removeItem('userSwitched');

        // Redirect to /checkout
        window.location.href = '/checkout';
        return;  // Exit early to prevent the rest of the code from running
    }
					
		//Done Button for Credited Orders
		$(document).on('click', '#doneButton', function() {
			location.reload(); // Refresh the page
		});
	
		// Print Button for Credited Order Page
		$(document).on('click', '#printOrderButton', function() {
			var selectedOrderId = $("#shortcode-user-search-2").data("selected-order-id");
			var selectedUserId = $("#shortcode-user-search-2").data("selected-user-id");

			$.ajax({
				url: php_vars.ajaxurl,
				type: "POST",
				data: {
					action: "ajax_generate_print_page_content",
					order_id: selectedOrderId,
					user_id: selectedUserId
				},
				success: function(response) {
					var data = JSON.parse(response);
					if (data.pdfUrl) {
						window.open(data.pdfUrl, '_blank');
					} else {
						alert("Error generating PDF.");
					}
				},
				error: function() {
					alert("Error in AJAX request.");
				}
			});
		});
	
	//Reset Credit Balance
	$(document).on('click', '#resetCreditBalanceButton', function() {
    var userId = $("#shortcode-user-search-2").data("selected-user-id");
    if (!userId) {
        alert('User ID not found.');
        return;
    }

    if (confirm('Are you sure you want to reset the credit balance for this user?')) {
        // AJAX request to reset credit balance
        $.ajax({
            url: php_vars.ajaxurl,
            type: 'POST',
            data: {
                action: 'reset_credit_balance',
                user_id: userId
            },
            success: function(response) {
                var data = JSON.parse(response);
                if (data.success) {
                    alert('Credit balance reset successfully.');
                    // Refresh the page or update the display
                } else {
                    alert('Failed to reset credit balance: ' + data.message);
                }
            },
            error: function() {
                alert('Error occurred while resetting credit balance.');
            }
        });
    }
});
	
    // Hide Pricing for Specific Class
    if ($('body').hasClass('hide_pricing_simple')) {
        $('.price').each(function() {
            var text = $(this).text();
            var newText = text.split('—')[0];
            $(this).text(newText);
        });
    }

    // Hide Subscription Pricing for Guests
    if (!php_vars.is_user_logged_in) {
        $('.wcsatt-options-product').hide();
        $('.wcsatt-options-product').after('<p><a href="' + php_vars.myaccount_url + '">Register</a> to see subscription pricing.</p>');
    }
	
	// Save Shipping Notes
	$(document).on('blur', '#shipping_notes', function() {
    	var notes = $(this).val();
		console.log('Sending shipping notes:', notes); // Log the notes
    	console.log('Nonce:', wc_checkout_params.update_order_review_nonce); // Log the nonce

    // Save to session instead of directly to order meta
    $.ajax({
        url: wc_checkout_params.ajax_url,
        type: 'POST',
        data: {
            action: 'save_shipping_notes_to_session',
            shipping_notes: notes,
			security: wc_checkout_params.update_order_review_nonce
        },
        success: function(response) {
            console.log('Shipping notes saved to session:', response);
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.log('Error saving shipping notes to session:', textStatus, errorThrown);
        }
    });
});
	
    // Debugging: Log available shipping methods for logged-in users
    if (php_vars.is_user_logged_in) {
        console.log("Logged-in user's shipping methods:", $('input[name="shipping_method"]').map(function() {
            return $(this).val();
        }).get());
    }
	
	// Set default value for Order Type dropdown if it's empty
    var orderTypeDropdown = $('select[name="order_type"]');
    if (!orderTypeDropdown.val()) {
        orderTypeDropdown.val('national');
    }

    // Dynamic Update of Cart Total
    $('.custom-field input').on('input', function() {
        var field_data = {
            action: 'update_cart_totals',
            field_values: {}
        };

        $('.custom-field input').each(function() {
            var field_name = $(this).attr('name');
            var field_value = $(this).val();
            field_data.field_values[field_name] = field_value;
        });

        $.post(php_vars.ajaxurl, field_data, function(response) {
            $('.custom-cart-total').replaceWith(response);
        });
    });

    // When a Shipping Option is Selected
    $(document).on('change', 'input[name="shipping_method"]', function() {
        var selectedMethod = $(this).val();
 
        // New $.ajax structure
        $.ajax({
            url: php_vars.ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'update_shipping_method',
                shipping_method: selectedMethod
            },
            success: function(data) {
                console.log("Response received:", data);
                console.log("Type of received data:", typeof data);

                if (data && data.success) {
                    var subtotalText = $(data.subtotal).text();
                    var totalText = $(data.total).text();
                    console.log("Subtotal (text):", subtotalText);
                    console.log("Total (text):", totalText);

                    $('#custom-cart-subtotal-value').text(subtotalText);
                    $('#custom-cart-total-value').text(totalText);
                } else {
                    console.error("Unexpected data format in response");
                }
            },
            error: function() {
                console.error("Error in AJAX call");
            }
        });
    });
	
	/* removing this code - leave for now -G
	// New AJAX functionality for Split Order button
    $('#split-order-btn').on('click', function() {
		// Confirmation dialog
		if (!confirm("Are you sure you want to split this order?")) {
			return; // Stop the function if the user clicks 'Cancel'
		}
		
    var nonce = $(this).data('nonce');
    $.ajax({
        url: php_vars.adminAjaxUrl,
        type: 'POST',
        data: {
            action: 'process_split_order',
            nonce: nonce
        },
        success: function(response) {
            if (response.success) {
				// Custom success message
                alert("Order has been prepared to split. To proceed with processing, please visit the 'Orders' screen in WooCommerce.");
				
                var href = $('#user_switching_switch_on a').attr('href');
                var two_href = href ? href.split('redirect_to=') : '';
                if (two_href[0]) {
                    window.location = two_href[0] + 'redirect_to=' + encodeURIComponent(php_vars.siteUrl + '/manual-orders');
                } else {
                    alert('Unable to switch back to the original user.');
                }
            } else {
                alert(response.data.message || 'Error processing order.');
            }
        },
        error: function(error) {
            console.log(error);
            alert('Error processing order.');
        }
    });
});
*/
	// New code for updating the notification bar
    var notificationBar = $('#custom-notification-bar');

    function updateUserNotification() {
        $.ajax({
            url: php_vars.ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'check_user_status'
            },
            success: function(response) {
                console.log("update_user_notification")
                console.log("AJAX Response:", response); // For debugging
                if (response.isAdmin) {
                    var message = "Hello, " + response.currentUsername + "! No Order Has Been Started.";
                    if (response.isSwitchedUser) {
                        message = "Currently Ordering for " + response.currentUsername;
                    }
                    notificationBar.text(message).show();
                } else {
                    notificationBar.hide();
                }
            },
            error: function() {
                console.log("update_user_notification")
                console.error("Error fetching user status");
            }
        });
    }

    // Initial check
    updateUserNotification();

    // Listen for user switch event
    $(document).on('userSwitched', function() {
        updateUserNotification();
    });
	
	
	function updateCloakingDisplay() {
		$.ajax({
			url: php_vars.ajaxurl,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'check_user_status'
			},
			success: function(response) {
                console.log("update_cloacking_display")
				console.log("AJAX Response:", response); // Debugging line
				if (response.isAdmin && !response.isSwitchedUser) {
					console.log("Hiding .custom-cloaking elements"); // Debugging line
					$('.custom-cloaking').removeClass('visible');
				} else {
					console.log("Showing .custom-cloaking elements"); // Debugging line
					$('.custom-cloaking').addClass('visible');
				}
			},
			error: function() {
                console.log("update_cloacking_display")
				console.error("Error fetching user status");
			}
		});
	}

	// Call the function on page load and whenever needed
	$(document).ready(function() {
		updateCloakingDisplay();
	});

	// Listen for user switch event
	$(document).on('userSwitched', function() {
		updateCloakingDisplay();
	});

	function updateReverseCloakingDisplay() {
		$.ajax({
			url: php_vars.ajaxurl,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'check_user_status'
			},
			success: function(response) {
                console.log("update_reverse_cloaking_display")
				console.log("AJAX Response:", response); // Debugging line
				if (response.isAdmin && response.isSwitchedUser) {
					console.log("Hiding .custom-reverse-cloaking elements"); // Debugging line
					$('.custom-reverse-cloaking').addClass('hidden');
				} else {
					console.log("Showing .custom-reverse-cloaking elements"); // Debugging line
					$('.custom-reverse-cloaking').removeClass('hidden');
				}
			},
			error: function(error) {
                console.log("update_reverse_cloaking_display")
				console.error("Error fetching user status");
				console.error(error);
			}
		});
	}

	// Call the function on page load and whenever needed
	$(document).ready(function() {
		updateReverseCloakingDisplay();
	});

	// Listen for user switch event
	$(document).on('userSwitched', function() {
		updateReverseCloakingDisplay();
	});
});