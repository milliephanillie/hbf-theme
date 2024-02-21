var orderDetails; // Truly global variable
jQuery(document).ready(function($) {
    var currentOrderDetails; // Global variable to hold the current order details
	var refundedTaxTotal = 0; // Initialize refundedTaxTotal globally
	
	// Function to determine the refund status of the order
	function getRefundStatus(orderDetails) {
    let totalRefunded = 0;
    for (let itemId in orderDetails.refunded_items) {
    if (orderDetails.refunded_items.hasOwnProperty(itemId)) {
        let item = orderDetails.refunded_items[itemId];
        totalRefunded += parseFloat(item.refund_total);
    }
}

    // Check if there's at least one refunded item
    if (Object.keys(orderDetails.refunded_items).length > 0) {
        // If there are refunded items, check if the order is partially or fully refunded
        if (totalRefunded < Math.abs(orderDetails.total_order_amount)) {
            console.log('Order is partially refunded.');
            return '<span style="color: maroon;">PARTIALLY REFUNDED</span>';
        } else if (totalRefunded >= Math.abs(orderDetails.total_order_amount)) {
            console.log('Order is fully refunded.');
            return '<span>REFUNDED</span>';
        }
    } else {
        return '<span>NOT REFUNDED</span>';
    }

    // If none of the above conditions are met, log an error message
    return '<span>ERROR</span>';
}
	
    // Function to calculate the total amount for the refund
    function calculateTotalAmount(orderDetails) {
    var totalAmount = orderDetails.items.reduce(function(sum, item) {
        return sum + parseFloat(item.price);
    }, 0);

    // Add shipping and tax totals
    totalAmount += parseFloat(orderDetails.shipping_total || 0) + parseFloat(orderDetails.tax_total || 0);

    // Add custom fees
    totalAmount += parseFloat(orderDetails.shipping_amount || 0); // Custom shipping fee
    totalAmount += parseFloat(orderDetails.admin_fee || 0);       // Admin fee
    totalAmount += parseFloat(orderDetails.pallet_fee || 0);      // Pallet fee
    totalAmount += parseFloat(orderDetails.misc_fee || 0);        // Misc fee

    return totalAmount;
}

    // Event listener for checkbox changes (both product and shipping refund checkboxes)
	$(document).on('change', '.item-checkbox, #refund-shipping', function() {
		updateRefundTotal(currentOrderDetails);
	});
	
	// Event listener for checkbox changes on custom fees
	$(document).on('change', '#refund--shipping-amount, #refund-admin-fee, #refund-pallet-fee, #refund-misc-fee', function() {
    updateRefundTotal(currentOrderDetails);
	});

	// Function to update the refund total based on selected items and custom fees
	function updateRefundTotal(orderDetails) {
		var refundTotal = 0;
		var refundedTaxTotal = 0;
		var anyItemChecked = false;
		var anyCustomFeeChecked = false;

		$('.item-checkbox:checked').each(function() {
			anyItemChecked = true;
			var pricePerUnit = parseFloat($(this).data('price'));
			var taxPerUnit = parseFloat($(this).data('tax'));
			var qty = parseFloat($(this).closest('.order-item').find('.item-qty').val());
			var refundedQty = parseFloat($(this).closest('.order-item').find('.refunded-qty').val()) || 0;
			var adjustedQty = qty - refundedQty;

			var itemTotal = pricePerUnit * adjustedQty; // Calculate item total without tax
			var itemTaxAmount = taxPerUnit * adjustedQty; // Calculate tax amount for the item

			refundTotal += itemTotal + itemTaxAmount; // Include item total and tax in refundTotal
			refundedTaxTotal += itemTaxAmount; // Include tax amount in refundedTaxTotal
		});

		if ($('#refund-shipping').is(':checked') && anyItemChecked) {
			refundTotal += parseFloat(orderDetails.shipping_total);
			refundedTaxTotal += parseFloat(orderDetails.shipping_tax || 0);
		}

		// Check for custom fees
		if ($('#refund-shipping-amount').is(':checked')) {
			refundTotal += parseFloat(orderDetails.shipping_amount || 0);
			anyCustomFeeChecked = true;
		}
		if ($('#refund-admin-fee').is(':checked')) {
			refundTotal += parseFloat(orderDetails.admin_fee || 0);
			anyCustomFeeChecked = true;
		}
		if ($('#refund-pallet-fee').is(':checked')) {
			refundTotal += parseFloat(orderDetails.pallet_fee || 0);
			anyCustomFeeChecked = true;
		}
		if ($('#refund-misc-fee').is(':checked')) {
			refundTotal += parseFloat(orderDetails.misc_fee || 0);
			anyCustomFeeChecked = true;
		}

		// Update #refund-button text to display refundTotal including tax
		var shouldUpdateButton = anyItemChecked || anyCustomFeeChecked;
		$('#refund-button').text(shouldUpdateButton ? 'Refund Selected Items $' + refundTotal.toFixed(2) : 'Refund Selected Items $0.00');
		$('#refunded-tax').text('(Refunded Tax: $' + (shouldUpdateButton ? refundedTaxTotal.toFixed(2) : '0.00') + ')');
	}
    
    // Function to format phone numbers
    function formatPhoneNumber(phoneNumber) {
        var cleaned = ('' + phoneNumber).replace(/\D/g, '');
        var match = cleaned.match(/^(\d{3})(\d{3})(\d{4})$/);
        if (match) {
            return '(' + match[1] + ') ' + match[2] + '-' + match[3];
        }
        return phoneNumber;
    }


	// Event listener for order number input changes
     $(document).on('input', '#order-number', function() {
        // Re-enable the Submit button when the order number changes
        $('button[type="submit"]', '#refund-form').prop('disabled', false);
    });

    // The single submit event handler for the refund form
    $(document).on('submit', '#refund-form', function(e) {
        e.preventDefault();

        // Disable the Submit button after the order is loaded
        $('button[type="submit"]', this).prop('disabled', true);

        var orderNumber = $('#order-number').val();

	// Perform the AJAX call to get order details
		$.ajax({
			url: window.php_vars.adminAjaxUrl, // Use the localized 'ajaxurl' variable
			type: 'POST',
			data: {
				action: 'get_order_details',
				order_number: orderNumber,
				nonce: window.refundFormVars.nonce // Include the nonce for security
			},
			success: function(response) {
				orderDetails = JSON.parse(response);
				console.log(orderDetails);
				currentOrderDetails = orderDetails; // Set the global variable
				
				 // Update the display of custom fees based on refund flags
            	updateCustomFeesDisplay(orderDetails);

				// Calculate the total order amount and set it in the orderDetails
				orderDetails.total_order_amount = calculateTotalAmount(orderDetails);

				// Check if the order has already been refunded
				if (orderDetails.status === 'refunded') {
					alert('This order has already been refunded.');
					location.reload();
					return;
				}
				
				// Call updateRefundTotal to ensure refundedTaxTotal is up to date
				updateRefundTotal(currentOrderDetails);

				var remainingTax = parseFloat(orderDetails.tax_total) - refundedTaxTotal;
				if (remainingTax < 0) remainingTax = 0;

                // Update the tax field in the UI only
                $('.shipping-tax p:nth-child(2)').html('<strong>Tax (' + orderDetails.state + '):</strong> $' + remainingTax.toFixed(2) + '<span id="refunded-tax" style="color: darkred; font-size: smaller; margin-left: 10px;">(Refunded Tax: $' + refundedTaxTotal.toFixed(2) + ')</span>');

                // Update the tax field in the order details
                orderDetails.tax_total = remainingTax.toFixed(2);

				var orderDate = new Date(orderDetails.order_date);
				var currentDate = new Date();
				var timeDiff = currentDate - orderDate;
				var daysDiff = Math.floor(timeDiff / (1000 * 60 * 60 * 24));

				// Determine the refund status and create the HTML for it
				var refundStatusHtml = getRefundStatus(orderDetails);
				$('#refund-status').html(refundStatusHtml); // Update the refund status in the correct div

				if (daysDiff > 30) {
					if (!confirm('This order is ' + daysDiff + ' days old. Refunds cannot be processed after 30 days of the order date. Proceed anyway?')) {
						location.reload();
						return;
					}
				}

		var orderItemsHtml = '';
		orderItemsHtml = '<p><strong>Order Date:</strong> ' + orderDetails.order_date + '</p>'; // Display the order date
		var totalAmount = 0;
		console.log("Refunded Items: ", orderDetails.refunded_items);
		orderDetails.items.forEach(function(item) {
            var refundedItem = orderDetails.refunded_items[item.product_id] || orderDetails.refunded_items[item.variation_id];
            var remainingQty = item.qty - (refundedItem ? refundedItem.qty : 0);
            var pricePerUnit = item.price / item.qty;
            var taxPerUnit = item.tax / item.qty;

            orderItemsHtml += '<div class="order-item">';
            console.log('Tax per unit: ' + taxPerUnit);
            orderItemsHtml += '<input type="checkbox" class="item-checkbox" value="' + item.id + '" data-price="' + pricePerUnit + '" data-tax="' + taxPerUnit + '">';
            orderItemsHtml += '<span class="item-name">' + item.name + '</span>';
            orderItemsHtml += '<input type="number" class="item-qty" value="' + remainingQty + '" min="1" max="' + remainingQty + '">';
            orderItemsHtml += '<span class="item-price">' + pricePerUnit.toFixed(2) + '</span>';
            orderItemsHtml += '<span class="item-tax" style="display:none;">' + taxPerUnit.toFixed(2) + '</span>'; // Add tax per unit
            orderItemsHtml += '<input type="number" class="refunded-qty" value="' + (refundedItem ? refundedItem.qty : 0) + '" readonly style="display:none;">'; // Add refunded quantity
            orderItemsHtml += '</div>';

            totalAmount += parseFloat(item.price);
        });

		// Update the order items HTML	
		var orderItemsContainer = document.querySelector('.order-items-container');
		if (orderItemsContainer) {
			orderItemsContainer.innerHTML = orderItemsHtml;
		}
				
		// Event listener for product checkbox changes
        $(document).on('change', '.item-checkbox', function() {
            updateRefundTotal(currentOrderDetails);

            // Check if any product checkboxes are checked
            var anyProductChecked = $('.item-checkbox:checked').length > 0;

            // Automatically deselect 'Refund Shipping for Partial Refund' if no products are checked
            if (!anyProductChecked) {
                $('#refund-shipping').prop('checked', false);
            }

            // Enable or disable 'Refund Shipping for Partial Refund' based on product checkbox state
            $('#refund-shipping').prop('disabled', !anyProductChecked);
        });

        // Ensure 'Refund Shipping for Partial Refund' is disabled on page load if no products are checked
        $(document).ready(function() {
            var anyProductChecked = $('.item-checkbox:checked').length > 0;
            $('#refund-shipping').prop('disabled', !anyProductChecked);
        });
			
        // Insert the HTML into the DOM
        $('#order-items').html(orderItemsHtml);
        $('#order-details').show();
        $('#refund-button').show();
        $('#refund-entire-order-button').show(); 
				
		// Calculate the total refund amount including custom fees
		var totalRefundAmount = parseFloat(orderDetails.items_total || 0) + parseFloat(orderDetails.tax_total || 0) + parseFloat(orderDetails.shipping_total || 0) + parseFloat(orderDetails.shipping_amount || 0) + parseFloat(orderDetails.admin_fee || 0) + parseFloat(orderDetails.pallet_fee || 0) + parseFloat(orderDetails.misc_fee || 0);
		$('#refund-entire-order-button').text('Refund Entire Order $' + totalRefundAmount.toFixed(2));
					
							var refundedItemsHtml = '';
							var refundedItems = Array.isArray(orderDetails.refunded_items) ? orderDetails.refunded_items : [];
							refundedItems.forEach(function(item) {
								refundedItemsHtml += '<div class="refunded-item">';
								refundedItemsHtml += '<span class="item-name">' + item.name + '</span>';
								refundedItemsHtml += '<span class="item-qty">' + item.qty + '</span>';
								refundedItemsHtml += '<span class="item-price">' + item.refund_total.toFixed(2) + '</span>';
								refundedItemsHtml += '<span class="item-status">Refunded</span>';
								refundedItemsHtml += '</div>';
							});
							$('#order-items').after(refundedItemsHtml);

							var customerDetailsHtml = '<h3>Customer Details</h3>';
							customerDetailsHtml += '<p><strong>Name:</strong> ' + orderDetails.customer_name + '</p>';
							customerDetailsHtml += '<p><strong>Address:</strong> ' + orderDetails.customer_address + '</p>';
							customerDetailsHtml += '<p><strong>Phone:</strong> ' + formatPhoneNumber(orderDetails.customer_phone) + '</p>';
							customerDetailsHtml += '<p><strong>Payment Method:</strong> ' + orderDetails.payment_method + '</p>';
							if (orderDetails.card_type && orderDetails.card_last_four) {
								customerDetailsHtml += '<p><strong>Card:</strong> ' + orderDetails.card_type + ' ending in ' + orderDetails.card_last_four + '</p>';
							}
							$('#customer-details').html(customerDetailsHtml);
							$('#customer-details').show();

							var shippingRefundTaxHtml = '<div class="shipping-refund-tax">';

							// Clear previous order's shipping and tax information
							$('.shipping-tax').remove();
							$('.refund-shipping').remove();
				
							// Reset the "Refund Selected Items" button text after new order details are loaded
    						$('#refund-button').text('Refund Selected Items $0.00');

							var shippingAndTaxHtml = '<div class="shipping-tax">';
							shippingAndTaxHtml += '<p><strong>Shipping:</strong> ' + orderDetails.shipping_method + ' - $' + parseFloat(orderDetails.shipping_total).toFixed(2) + '</p>';
							shippingAndTaxHtml += '<p><strong>Tax (' + orderDetails.state + '):</strong> $' + parseFloat(orderDetails.tax_total).toFixed(2);
							shippingAndTaxHtml += '<span id="refunded-tax" style="color: darkred; font-size: smaller; margin-left: 10px;">(Refunded Tax: $0.00)</span></p>';  // Add refunded tax span
							shippingAndTaxHtml += '</div>';

							var refundShippingHtml = '<div class="refund-shipping" style="display: flex; align-items: center;">';
							refundShippingHtml += '<input type="checkbox" id="refund-shipping" value="' + orderDetails.shipping_total + '">';
							refundShippingHtml += '<label for="refund-shipping" style="margin-left: 5px;">Refund Shipping for Partial Refund</label>';
							refundShippingHtml += '</div>';

							// Custom Fees Display (without checkboxes)
var customFeesHtml = '<div class="custom-fees" style="margin-top: 10px;">';
customFeesHtml += 'Custom Shipping: $' + parseFloat(orderDetails.shipping_amount || 0).toFixed(2) + '<br>';
customFeesHtml += 'Admin Fee: $' + parseFloat(orderDetails.admin_fee || 0).toFixed(2) + '<br>';
customFeesHtml += 'Pallet Fee: $' + parseFloat(orderDetails.pallet_fee || 0).toFixed(2) + '<br>';
customFeesHtml += 'Misc Fee: $' + parseFloat(orderDetails.misc_fee || 0).toFixed(2);
customFeesHtml += '</div>';


							// Combine all parts
							shippingRefundTaxHtml += shippingAndTaxHtml;
							shippingRefundTaxHtml += refundShippingHtml;
							shippingRefundTaxHtml += customFeesHtml;
							shippingRefundTaxHtml += '</div>';


							$('#order-items').after(shippingRefundTaxHtml);	

							totalAmount += parseFloat(orderDetails.shipping_total || 0) + 
										  parseFloat(orderDetails.tax_total || 0) + 
										  parseFloat(orderDetails.shipping_amount || 0) + 
										  parseFloat(orderDetails.admin_fee || 0) + 
										  parseFloat(orderDetails.pallet_fee || 0) + 
										  parseFloat(orderDetails.misc_fee || 0);

							// Update the text of the 'Refund Entire Order' button
							$('#refund-entire-order-button').text('Refund Entire Order $' + totalAmount.toFixed(2));

							$(document).on('input', '.item-qty', function() {
								var maxQty = parseInt($(this).attr('max'));
								var currentQty = parseInt($(this).val());
								if (currentQty > maxQty) {
									$(this).val(maxQty);
								} else if (currentQty < 1) {
									$(this).val(1);
								}
								updateRefundTotal(currentOrderDetails);
							});

							$(document).on('change', '#refund-shipping', function() {
								updateRefundTotal(currentOrderDetails);
							});

							// Event listener for checkbox changes
							$(document).on('change', '.order-items-container .item-checkbox', function() {
								updateRefundTotal(currentOrderDetails);
							});

							// Event listener for quantity input changes
							$(document).on('change', '.order-items-container .item-qty', function() {
								updateRefundTotal(currentOrderDetails);
							});

							// Event listener for the refund shipping checkbox change
							$(document).on('change', '#refund-shipping', function() {
								updateRefundTotal(currentOrderDetails);
							});
						},
						error: function() {
							alert('Failed to fetch order details. Please try again.');
						},
					});
				});
	
	// Function to update the display of custom fees based on refund flags
	function updateCustomFeesDisplay(orderDetails) {
		// Check for custom shipping fee and update display and checkbox state
		if (orderDetails['shipping-amount-refunded']) {
			$('#refund-shipping-amount').prop('checked', false).prop('disabled', true);
			$('#refund-shipping-amount').next().text(' Refund Custom Shipping ($0.00)');
		}

		// Check for admin fee and update display and checkbox state
		if (orderDetails['admin-fee-refunded']) {
			$('#refund-admin-fee').prop('checked', false).prop('disabled', true);
			$('#refund-admin-fee').next().text(' Refund Admin Fee ($0.00)');
		}

		// Check for pallet fee and update display and checkbox state
		if (orderDetails['pallet-fee-refunded']) {
			$('#refund-pallet-fee').prop('checked', false).prop('disabled', true);
			$('#refund-pallet-fee').next().text(' Refund Pallet Fee ($0.00)');
		}

		// Check for misc fee and update display and checkbox state
		if (orderDetails['misc-fee-refunded']) {
			$('#refund-misc-fee').prop('checked', false).prop('disabled', true);
			$('#refund-misc-fee').next().text(' Refund Misc Fee ($0.00)');
		}
	}

	// Event handler for the refund button click
	$(document).on('click', '#refund-button', function() {
		var selectedItems = [];
		var totalRefundTax = 0; // Initialize a variable to hold the total tax for the refund
		var refundShipping = $('#refund-shipping').is(':checked'); // Determine if shipping should be refunded
		var totalCustomFees = 0; // Initialize a variable to hold the total custom fees for the refund
		var customFees = {};

    // Check if orderDetails is defined
    if (typeof orderDetails !== 'undefined') {
        var customFees = {
            shipping_amount: $('#refund-shipping-amount').is(':checked') ? parseFloat(orderDetails.shipping_amount || 0) : 0,
			admin_fee: $('#refund-admin-fee').is(':checked') ? parseFloat(orderDetails.admin_fee || 0) : 0,
			pallet_fee: $('#refund-pallet-fee').is(':checked') ? parseFloat(orderDetails.pallet_fee || 0) : 0,
			misc_fee: $('#refund-misc-fee').is(':checked') ? parseFloat(orderDetails.misc_fee || 0) : 0
        };
		
		// Log these values to the console for debugging
		console.log('Custom Fees being sent:', customFees);customFees
    }
		
		// Calculate the total amount for selected custom fees
		if ($('#refund-shipping-amount').is(':checked')) {
			totalCustomFees += parseFloat($('#refund-shipping-amount').data('amount') || 0);
		}
		if ($('#refund-admin-fee').is(':checked')) {
			totalCustomFees += parseFloat($('#refund-admin-fee').data('amount') || 0);
		}
		if ($('#refund-pallet-fee').is(':checked')) {
			totalCustomFees += parseFloat($('#refund-pallet-fee').data('amount') || 0);
		}
		if ($('#refund-misc-fee').is(':checked')) {
			totalCustomFees += parseFloat($('#refund-misc-fee').data('amount') || 0);
		}

		$('.item-checkbox:checked').each(function() {
			var itemId = $(this).val();
			var qty = $(this).closest('.order-item').find('.item-qty').val();
			var taxPerUnit = parseFloat($(this).data('tax')); // Get the tax per unit for the item
			var refundedQty = qty - (parseFloat($(this).closest('.order-item').find('.refunded-qty').val()) || 0); // Calculate the quantity to be refunded
			var taxAmount = taxPerUnit * refundedQty; // Calculate the tax amount for the refunded quantity

			totalRefundTax += taxAmount; // Add the tax amount to the total refund tax
			selectedItems.push({ id: itemId, qty: qty, tax: taxAmount }); // Include the tax amount for each item
		});

		var orderNumber = $('#order-number').val();
		var refundReason = $('#refund-reason').val();

		$.ajax({
			url: window.php_vars.adminAjaxUrl,
			type: 'POST',
			data: {
				action: 'process_refund',
				order_number: orderNumber,
				items: selectedItems.length > 0 ? selectedItems : [],
				reason: refundReason,
				tax: totalRefundTax,
				refund_shipping: refundShipping,
				custom_fees: customFees, // Send the custom fees as an object
				total_custom_fees: totalCustomFees // Optionally send the total custom fees if needed
			},
			success: function(response) {
				var result = JSON.parse(response);
				if (result.success) {
					alert('Refund processed successfully.');
					var orderNumber = $('#order-number').val();
        			showRefundModal(orderNumber);
				} else {
					alert('Failed to process refund. Please try again.');
				}
			},
			error: function() {
				alert('Failed to process refund. Please try again.');
			},
		});
	});
	
    // Event handler for the refund entire order button click
	$(document).on('click', '#refund-entire-order-button', function() {
    var orderNumber = $('#order-number').val();
    var refundReason = $('#refund-reason').val();

	// Set refundShipping to true for full refund
    var refundShipping = true;

    // Define custom fees based on orderDetails or default to 0
    var customFees = {
        shipping_amount: parseFloat(orderDetails.shipping_amount || 0),
        admin_fee: parseFloat(orderDetails.admin_fee || 0),
        pallet_fee: parseFloat(orderDetails.pallet_fee || 0),
        misc_fee: parseFloat(orderDetails.misc_fee || 0)
    };

    $.ajax({
        url: window.php_vars.adminAjaxUrl,
        type: 'POST',
        data: {
            action: 'process_refund',
            order_number: orderNumber,
            items: 'all',
            reason: refundReason,
            refund_shipping: refundShipping,
            custom_fees: customFees
			},
			success: function(response) {
				console.log(response); // Log the response for debugging
				var result;
				try {
					result = JSON.parse(response);
				} catch (e) {
					console.error('Error parsing JSON:', e);
					alert('Failed to process refund. Please try again.');
					return;
				}
				if (result.success) {
					alert('Refund processed successfully.');
					var orderNumber = $('#order-number').val();
        			showRefundModal(orderNumber);
				} else {
					alert('Failed to process refund. Please try again.');
				}
			},
			error: function(jqXHR, textStatus, errorThrown) {
				console.error('AJAX error:', textStatus, 'Details:', errorThrown);
				alert('Failed to process refund. Please try again.');
			}
		});
	});

	// Cancel Refund button click event
	$(document).on('click', '#cancel-refund-button', function() {
		if (confirm('Are you sure you want to cancel the refund?')) {
			// Re-enable the "Submit" button and the order number field
			$('#order-number').prop('disabled', false);
			$('button[type="submit"]', '#refund-form').prop('disabled', false);

			// Check if the form exists and is a form element before resetting
			var refundForm = $('#refund-form');
			if (refundForm.length && refundForm[0] instanceof HTMLFormElement) {
				refundForm[0].reset();
			}

			// Reload the page to reflect the changes
			location.reload();
		}
	});
});