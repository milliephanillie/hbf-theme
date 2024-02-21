jQuery(document).ready(function($) {

    // User search functionality
    $("#shortcode-user-search-2").on("input", function() {
        var searchTerm = $(this).val();
        if (searchTerm.length >= 3) {
            $.ajax({
                url: window.php_vars.adminAjaxUrl,
                type: "POST",
                data: {
                    action: "search_users_for_manual_orders",
                    search_term: searchTerm
                },
                success: function(response) {
                    var data = JSON.parse(response);

                    if (data.success && data.users.length > 0) {
                        var dropdownContent = "";
                        data.users.forEach(function(user) {
                            var phone = user.billing_phone ? user.billing_phone : 'No phone';
                            dropdownContent += "<div class=\"shortcode-user-2-result\" data-user-id=\"" + user.id + "\">" + user.name + " (" + user.email + ") - Phone: " + phone + "</div>";
                        });
                        $("#shortcode-user-search-2-results").html(dropdownContent).show();
                    } else {
                        $("#shortcode-user-search-2-results").hide();
                    }
                }
            });
        }
    });

    // Hide dropdown when clicking outside of the search field and dropdown
    $(document).on("click", function(event) {
        if (!$(event.target).closest("#shortcode-user-search-2, #shortcode-user-search-2-results").length) {
            $("#shortcode-user-search-2-results").hide();
        }
    });

    // Handle user selection from the dropdown
    $(document).on("click", ".shortcode-user-2-result", function() {
        var selectedUserId = $(this).data("user-id");
        var selectedUserName = $(this).text().split(" (")[0];
        var selectedUserEmail = $(this).text().split(" (")[1].replace(")", "");
        $("#shortcode-user-search-2").data("selected-user-id", selectedUserId);
        $("#shortcode-user-search-2").data("selected-user-name", selectedUserName);
        $("#shortcode-user-search-2").data("selected-user-email", selectedUserEmail);
        $("#shortcode-user-search-2-results").empty();
        $("#loadPreviousOrder2").prop("disabled", false);  // Enable the "Load a Previous Order" button

        // Clear the search bar
        setTimeout(function() {
            $("#shortcode-user-search-2").val("");
        }, 1500);

        // Set the value of the hidden input field
        $("#selected_user_id2").val(selectedUserId);

        $.ajax({
            url: window.php_vars.adminAjaxUrl,
            type: "POST",
            data: {
                action: "get_customer_info", // Ensure this AJAX action is properly defined on the server side
                user_id: selectedUserId
            },
            success: function(response) {
                var data = JSON.parse(response);
                if (data.success) {
                    var customerInfoHtml = '<strong>CUSTOMER INFORMATION</strong><br>' +
                        '<strong>Name:</strong> ' + data.first_name + ' ' + data.last_name + '<br>' +
                        '<strong>Address:</strong> ' + data.address + '<br>' +
                        '<strong>Phone:</strong> ' + data.phone + '<br>' +
                        '<strong>Email:</strong> ' + data.email;

                    // Adding credit balance if available
                    if (data.credit_balance !== undefined) {
                        customerInfoHtml += '<br><strong>Credit Balance:</strong> $' + data.credit_balance;
                    }

                    $('#selected-customer-info').html(customerInfoHtml);
                }
            },
            error: function() {
                $('#selected-customer-info').html("Failed to load customer information.");
            }
        });
    });

    // Flag to track the visibility of the previous orders dropdown
    var isPreviousOrdersVisible = false;

    // Handle click event on the "Load a Previous Order" button
    $("#loadPreviousOrder2").off("click").on("click", function() {
        var selectedUserId = $("#shortcode-user-search-2").data("selected-user-id");
        console.log("Fetching orders for User ID:", selectedUserId);

        var previousOrders = $("#previousOrdersCredits");
        var loadOrderButton = $(this); // Reference to the button

        if (previousOrders.is(":visible")) {
            previousOrders.slideUp(function() {
                loadOrderButton.text("Load a Previous Order");
                loadOrderButton.removeClass("orders-loaded");
            });
        } else {
            $.ajax({
                url: window.php_vars.adminAjaxUrl,
                type: "POST",
                data: {
                    action: "fetch_previous_orders_for_credits",
                    user_id: selectedUserId
                },
                dataType: "json",
                success: function(response) {
                    console.log(response);
                    if (response.success && response.orders.length > 0) {
                        var ordersTableBody = $("#ordersTableCredits tbody");
                        ordersTableBody.empty(); // Clear any existing rows
                        response.orders.forEach(function(order) {
                            var orderRowClass = order.is_refunded ? " class='refunded-order'" : (order.is_partially_refunded ? " class='partially-refunded-order'" : "");
                            var orderRowText = order.is_refunded ? "<s>" + order.order_total + "</s> REFUNDED" : (order.is_partially_refunded ? order.order_total + " PARTIAL" : order.order_total);
                            var orderRow = "<tr" + orderRowClass + ">" +
                                "<td>" + order.order_id + "</td>" +
                                "<td>" + order.order_date + "</td>" +
                                "<td>" + orderRowText + "</td>" +
                                "</tr>";
                            ordersTableBody.append(orderRow);
                        });
                        previousOrders.slideDown(function() {
                            loadOrderButton.text("Orders Loaded: " + response.orders.length);
                            loadOrderButton.addClass("orders-loaded");
                        });
                        $("#ordersTableCredits thead").show();
                    } else if(response.success && response.orders.length <= 0) {
                        alert("No orders found.");
                    } else {
                        alert("No orders found or an error occurred.");
                    }
                },
                error: function() {
                    alert("An error occurred while fetching the orders.");
                }
            });
        }
    });

    // Handle order row click to show order details with animation
    $(document).off("click", "#ordersTableCredits tbody tr").on("click", "#ordersTableCredits tbody tr", function(e) {
        // Ignore clicks on the order details row
        if ($(e.target).closest(".order-details-row").length) {
            return;
        }

        // Check if the order is refunded
        if ($(this).hasClass("refunded-order")) {
            alert("This order has already been fully refunded.");
            return; // Exit the function
        }

        var orderId = $(this).find("td:first").text();
        console.log("Selected Order ID:", orderId);
        $("#shortcode-user-search-2").data("selected-order-id", orderId);
        var detailsRow = $(this).next(".order-details-row");

        // Check if the details row already exists
        if (detailsRow.length > 0) {
            // If it exists, just toggle it with animation
            detailsRow.stop().slideToggle(1000, function() {
                // If after toggling, the details row is not visible, remove it from the DOM
                if (!$(this).is(":visible")) {
                    $(this).remove();
                }
            });
        } else {
            // If it does not exist, create it and fetch the details
            $(this).addClass("loading-details");
            var templateHtml = $("#orderDetailsRowTemplate2").html();
            detailsRow = $(templateHtml);
            // Insert the details row directly after the clicked row
            $(this).after(detailsRow);
            detailsRow.addClass("order-details-row");
            fetchOrderDetailsForCredit(orderId, detailsRow, function() {
                $(this).removeClass("loading-details");
                // Slide down with animation
                detailsRow.slideDown(400);
            });
        }
    });

    function fetchOrderDetailsForCredit(orderId, detailsRow, callback) {
        $.ajax({
            url: window.php_vars.adminAjaxUrl,
            type: "POST",
            data: {
                action: "fetch_order_details_for_credit",
                order_id: orderId
            },
            success: function(response) {
                console.log("Received AJAX response for order details", response);
                var data = JSON.parse(response);

                // Log the received order items data
                console.log("Credit Module: Received Order Items Data", data.order_items);

                if (data.success) {
                    var dropdownContent = '<table class="credit-table">';

                    // Add table headings
                    dropdownContent += `
                        <thead>
                            <tr>
                                <th>Product ID</th>
                                <th>Item Qty</th>
                                <th>Item Cost</th>
                                <th>Tax</th>
                                <th>Line Total</th>
                            </tr>
                        </thead>
                        <tbody>`;

                    data.order_items.forEach(function(item) {
                        console.log('Item:', item.product_id, item.variation_id, item.credited_qty, item.quantity); // Debugging line

                        var creditedQty = item.credited_qty || 0; // Get credited quantity
                        var availableQty = item.quantity - creditedQty; // Calculate available quantity
                        var creditedQtyDisplay = creditedQty > 0 ? ' <span style="color: red;">(-' + creditedQty + ')</span>' : '';  // Display credited quantity
                        var lineTotal = availableQty * item.price + availableQty * (item.tax / item.quantity);

                        var productId = item.product_id;
                        var variationId = item.variation_id || '';

                        var itemId = item.variation_id && item.variation_id !== "0" ? `${item.product_id}_${item.variation_id}` : item.product_id;

                        dropdownContent += `
                            <tr data-item-id="${itemId}">
                                <td>${item.name}</td>
                                <td>${availableQty}${creditedQtyDisplay}</td> <!-- Display available quantity and credited quantity -->
                                <td>$${parseFloat(item.price).toFixed(2)}</td>
                                <td>$${parseFloat(item.tax).toFixed(2)}</td>
                                <td>$${parseFloat(lineTotal).toFixed(2)}</td> <!-- Ensure lineTotal is correctly calculated and formatted -->
                            </tr>
                            <tr class="credit-row">
                                <td style="font-weight:700;">Credit:</td>
                                <td><input type="number" class="credit-qty" value="0" min="0" max="${availableQty}"></td>
                                <td class="credit-cost">$0.00</td>
                                <td class="credit-tax">$0.00</td>
                                <td class="credit-line-total">$0.00</td>
                            </tr>`;
                    });
                    dropdownContent += '</table>';
                    dropdownContent += "<button id='apply-credit-btn'>Credit Customer: $<span id='total-credit-amount'>0.00</span></button>";

                    detailsRow.find(".order-details-dropdown").html(dropdownContent);
                    calculateCredits(); // Call to calculate initial totals
                    detailsRow.show();
                } else {
                    console.error("Error: ", data.error); // Log any errors
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("AJAX Error: ", textStatus, errorThrown); // Log AJAX errors
                alert("An error occurred while fetching the order details.");
            }
        }).always(function() {
            if (typeof callback === "function") callback(); // Always remove the loading state
        });
    }

    $(document).on("click", "#apply-credit-btn", function() {
        var totalCreditAmount = $('#total-credit-amount').text();
        var confirmCredit = confirm(`Confirm crediting customer: $${totalCreditAmount}`);
        if (!confirmCredit) {
            return; // Exit if the user does not confirm
        }

        // Prepare data for AJAX call
        var creditData = [];
        var selectedUserId = $("#shortcode-user-search-2").data("selected-user-id");
        var selectedOrderId = $("#shortcode-user-search-2").data("selected-order-id");

        $('.credit-table .credit-row').each(function() {
            var qty = $(this).find('.credit-qty').val();
            if (qty > 0) {
                var itemData = $(this).prev().data('item-id');
                var itemId = (typeof itemData === 'string') ? itemData.split('_') : [itemData];
                var productId = itemId[0];
                var variationId = itemId.length > 1 ? itemId[1] : ''; // If variationId is undefined or not present, use an empty string
                var productName = $(this).prev().find('td:first').text();
                var creditCost = $(this).find('.credit-cost').text().slice(1); // Remove $ sign
                var creditTax = $(this).find('.credit-tax').text().slice(1); // Remove $ sign
                creditData.push({
                    product_id: productId,
                    variation_id: variationId,
                    product_name: productName,
                    quantity: qty,
                    cost: creditCost,
                    tax: creditTax
                });
            }
        });

        console.log("Credit Data:", creditData);
        console.log("User ID:", selectedUserId);
        console.log("Order ID:", selectedOrderId);
        console.log("Credit Module: Prepared Credit Data for Submission", creditData);

        $.ajax({
            url: window.php_vars.adminAjaxUrl,
            type: "POST",
            data: {
                action: "apply_credit_to_customer_account",
                user_id: selectedUserId,
                order_id: selectedOrderId,
                credit_data: creditData
            },
            success: function(response) {
                var data = JSON.parse(response);
                if (data.success) {
                    alert("Credits applied successfully.");
                    // Trigger the modal display
                    $("#postCreditModal").show();
                } else {
                    alert("Failed to apply credits: " + data.message);
                }
            },
            error: function() {
                alert("Error applying credits.");
            }
        });
    });

    // Calculate and display credit totals
    function calculateCredits() {
        var totalCredit = 0;
        $('.credit-table .credit-row').each(function() {
            var availableQty = parseInt($(this).prev().find('td:nth-child(2)').text());
            if (availableQty > 0) {                var qty = parseInt($(this).find('.credit-qty').val());
                var price = parseFloat($(this).prev().find('td:nth-child(3)').text().slice(1));
                var tax = parseFloat($(this).prev().find('td:nth-child(4)').text().slice(1)) / parseFloat($(this).prev().find('td:nth-child(2)').text());
                var creditCost = qty * price;
                var creditTax = qty * tax;

                $(this).find('.credit-cost').text('$' + creditCost.toFixed(2));
                $(this).find('.credit-tax').text('$' + creditTax.toFixed(2));
                var lineTotal = creditCost + creditTax;
                $(this).find('.credit-line-total').text('$' + lineTotal.toFixed(2));

                totalCredit += lineTotal;
            }
        });

        // Check if totalCredit is a number. If not, set it to 0
        if (isNaN(totalCredit)) {
            totalCredit = 0;
        }
        console.log("Calculated total credit", totalCredit);
        $('#total-credit-amount').text(totalCredit.toFixed(2));
    }

    $(document).on('change', '.credit-qty', function() {
        calculateCredits();
    });

    // Debugging: Log when the function is triggered
    console.log('Credit calculation triggered');

    // Handle form submission
    $("#creditApplicationForm").on("submit", function(e) {
        e.preventDefault();
        var selectedItems = [];
        $('.credit-table .credit-row').each(function() {
            var qty = $(this).find('.credit-qty').val();
            if (qty > 0) {
                var productName = $(this).prev().find('td:first').text();
                var creditCost = $(this).find('.credit-cost').text().slice(1); // Remove $ sign
                var creditTax = $(this).find('.credit-tax').text().slice(1); // Remove $ sign
                selectedItems.push({
                    product_name: productName,
                    quantity: qty,
                    cost: creditCost,
                    tax: creditTax
                });
            }
        });

        $.ajax({
            url: window.php_vars.adminAjaxUrl,
            type: "POST",
            data: {
                action: "apply_credit_to_customer_account",
                user_id: $("#shortcode-user-search-2").data("selected-user-id"),
                order_id: $("#shortcode-user-search-2").data("selected-order-id"),
                credit_data: selectedItems
            },
            success: function(response) {
                var data = JSON.parse(response);
                if (data.success) {
                    alert("Credits applied successfully.");
                } else {
                    alert("Failed to apply credits: " + data.message);
                }
            },
            error: function() {
                alert("Error applying credits.");
            }
        });
    });
});


