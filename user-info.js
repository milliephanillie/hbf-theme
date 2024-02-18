jQuery(document).ready(function($) {
    // Clear form fields on page load if they exist
    if ($(".billing-info-form").length > 0) {
        $(".billing-info-form")[0].reset();
    }
    if ($(".shipping-info-form").length > 0) {
        $(".shipping-info-form")[0].reset();
    }
});

    // User search functionality
    $("#shortcode-user-search").on("input", function() {
        var searchTerm = $(this).val();
        if (searchTerm.length >= 3) {
            $.ajax({
                url: php_vars.adminAjaxUrl,
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
                            dropdownContent += "<div class=\"shortcode-user-result\" data-user-id=\"" + user.id + "\">" + user.name + " (" + user.email + ")</div>";
                        });
                        $("#shortcode-user-search-results").html(dropdownContent).show();
                    } else {
                        $("#shortcode-user-search-results").hide();
                    }
                }
            });
        }
    });

    // Hide dropdown when clicking outside of the search field and dropdown
    $(document).on("click", function(event) {
        if (!$(event.target).closest("#shortcode-user-search, #shortcode-user-search-results").length) {
            $("#shortcode-user-search-results").hide();
        }
    });

    // Handle user selection from the dropdown
    $(document).on("click", ".shortcode-user-result", function() {
        var selectedUserId = $(this).data("user-id");
        console.log("Selected User ID:", selectedUserId);
        var selectedUserName = $(this).text().split(" (")[0];
        var selectedUserEmail = $(this).text().split(" (")[1].replace(")", "");
        $("#shortcode-user-search").data("selected-user-id", selectedUserId);
        $("#shortcode-user-search").data("selected-user-name", selectedUserName);
        $("#shortcode-user-search").data("selected-user-email", selectedUserEmail);
        $("#shortcode-user-search-results").empty();
        $("#loadPreviousOrder").prop("disabled", false); // Enable the "Load a Previous Order" button

        // Clear the search bar
        setTimeout(function() {
            $("#shortcode-user-search").val("");
        }, 1500);

        // Set the value of the hidden input field
        $("#selected_user_id").val(selectedUserId);

        // Populate the Billing and Shipping Information forms with the selected user data
        $.ajax({
            url: php_vars.adminAjaxUrl,
            type: "POST",
            data: {
                action: "get_user_billing_shipping_info",
                user_id: selectedUserId
            },
            success: function(response) {
                var data = JSON.parse(response);
                if (data.success) {
                    // Populate Billing Information form
                    for (var key in data.billing_info) {
                        $("#" + key + "_billing").val(data.billing_info[key]);
                    }

                    // Populate Shipping Information form
                    for (var key in data.shipping_info) {
                        $("#" + key + "_shipping").val(data.shipping_info[key]);
                    }
                } else {
                    alert("Failed to load user information.");
                }
            },
            error: function() {
                alert("Failed to load user information.");
            }
        });
    });

    // Delete user button click handler
    $("#delete-user-button").on("click", function() {
        var selectedUserId = $("#user-search").data("selected-user-id");
        var selectedUserName = $("#user-search").data("selected-user-name");
        var selectedUserEmail = $("#user-search").data("selected-user-email");
        if (!selectedUserId) {
            alert("Please select a user to delete.");
            return;
        }

        // Check user role and capabilities
        $.ajax({
            url: php_vars.adminAjaxUrl,
            type: "POST",
            data: {
                action: "check_user_role_and_capabilities",
                user_id: selectedUserId
            },
            success: function(response) {
                var data = JSON.parse(response);
                if (data.success) {
                    if (data.excluded) {
                        alert("You are not allowed to delete a user with the role of " + data.userRole + ".");
                        return;
                    }

                    var confirmation = confirm("Are you sure you want to delete the customer " + selectedUserName + " (" + selectedUserEmail + ")? This action cannot be undone.");
                    if (confirmation) {
                        var code = Math.random().toString(36).substring(2, 7).toUpperCase();
                        var userCode = prompt("Enter the following code to confirm deletion: " + code);
                        if (userCode === null) {
                            return;
                        }
                        if (userCode.toUpperCase() === code) {
                            $.ajax({
                                url: php_vars.adminAjaxUrl,
                                type: "POST",
                                data: {
                                    action: "delete_user",
                                    user_id: selectedUserId,
                                    nonce: php_vars.delete_user_nonce
                                },
                                success: function(response) {
                                    var data = JSON.parse(response);
                                    if (data.success) {
                                        alert("Customer deleted successfully.");
                                        location.reload();
                                    } else {
                                        alert("Failed to delete customer.");
                                    }
                                },
                                error: function(xhr, status, error) {
                                    console.log(xhr.responseText);
                                    alert("Failed to delete user.");
                                }
                            });
                        } else {
                            alert("Incorrect code. Customer not deleted.");
                        }
                    }
                } else {
                    alert("Failed to check user role and capabilities.");
                }
            },
            error: function() {
                alert("Failed to check user role and capabilities.");
            }
        });
    });

    // Handle "Ship to a different address?" checkbox
    $("#ship-to-different-address-checkbox").on("change", function() {
        if ($(this).is(":checked")) {
            $("#shipping-fields").slideDown();
        } else {
            $("#shipping-fields").slideUp();
        }
    });

    // Prevent form submission on enter key press for billing and shipping forms
    $(".form-container form input").on("keydown", function(e) {
        if (e.key === "Enter" && (e.target.closest(".billing-info-form") || e.target.closest(".shipping-info-form"))) {
            e.preventDefault();
        }
    });

    // Handle click event on the "Load a Previous Order" button
    $("#loadPreviousOrder").off("click").on("click", function() {
        var selectedUserId = $("#shortcode-user-search").data("selected-user-id");
        console.log("Fetching orders for User ID:", selectedUserId);

        // Make an AJAX request to fetch the previous orders
        $.ajax({
            url: php_vars.adminAjaxUrl,
            type: "POST",
            data: {
                action: "fetch_previous_orders",
                user_id: selectedUserId
            },
            dataType: "json",
            success: function(response) {
                console.log(response);
                if (response.success && response.orders.length > 0) {
                    var ordersTableBody = $("#ordersTable tbody");
                    ordersTableBody.empty(); // Clear any existing rows
                    response.orders.forEach(function(order) {
                        var orderRow = "<tr>" +
                            "<td>" + order.order_id + "</td>" +
                            "<td>" + order.order_date + "</td>" +
                            "<td>" + order.order_total + "</td>" +
                            "</tr>";
                        ordersTableBody.append(orderRow);
                    });
                    $("#previousOrders").show(); // Show the orders table
                    $("#ordersTable thead").show(); // Show the table header
                } else {
                    alert("No orders found or an error occurred.");
                }
            },
            error: function() {
                alert("An error occurred while fetching the orders.");
            }
        });
    });

    // Handle order row click to show order details
    $(document).off("click", "#ordersTable tbody tr").on("click", "#ordersTable tbody tr", function() {
        var orderId = $(this).find("td:first").text();
        console.log("Fetching details for Order ID:", orderId);
        var detailsRow = $(this).next(".order-details-row");

        // Check if we are already fetching details for this order
        if ($(this).hasClass("loading-details")) {
            return; // If so, do nothing
        }

        // Mark the row as loading
        $(this).addClass("loading-details");

        if (detailsRow.length === 0) {
            var templateHtml = $("#orderDetailsRowTemplate").html();
            detailsRow = $(templateHtml);
            $(this).after(detailsRow);
            detailsRow.addClass("order-details-row");
            fetchOrderDetails(orderId, detailsRow, function() {
                // Remove the loading mark
                $(this).removeClass("loading-details");
            });
        } else {
            detailsRow.toggle();
            // Remove the loading mark
            $(this).removeClass("loading-details");
        }
    });

    // Handle the "Add to Order" button click within the order details dropdown
    $(document).on("click", ".add-to-manual-order-button", function() {
        var orderId = $(this).data("order-id");
        var itemCount = $(this).data("item-count");
        var userId = $(this).data("user-id");
        var userName = $(this).data("user-name"); // Retrieve the username

        var userConfirmed = confirm("Add " + itemCount + " items to cart and continue as " + userName + "?");
        if (userConfirmed) {
            populateCartWithOrder(orderId, userId);
        }
    });

    // Function to populate the cart with the selected order
    function populateCartWithOrder(orderId, userId) {
        $.ajax({
            url: php_vars.adminAjaxUrl,
            type: "POST",
            data: {
                action: "populate_cart_with_order",
                order_id: orderId,
                user_id: userId
            },
            success: function(response) {
                var data = JSON.parse(response);
                if (data.success) {
                    // Switch to the user associated with the order
                    // var userId = $("#shortcode-user-search").data("selected-user-id");
                    window.location.href = "/checkout";
                } else {
                    alert("Error populating cart: " + data.message);
                }
                // switchToUser(userId, function() {window.location.href = "/checkout";});
            }
        });
    }

    function switchToUser(userId, callback) {
        $.ajax({
            url: php_vars.adminAjaxUrl,
            type: "POST",
            data: {
                action: "switch_to_selected_user",
                user_id: userId
            },
            success: function(response) {
                var data = JSON.parse(response);
                if (data.success) {
                    callback();
                } else {
                    alert("Error switching user: " + data.message);
                }
            },
            error: function() {
                alert("An error occurred while switching the user.");
            }
        });
    }

    // Function to fetch order details and populate the dropdown with a callback for loading state
    function fetchOrderDetails(orderId, detailsRow, callback) {
        $.ajax({
            url: php_vars.adminAjaxUrl,
            type: "POST",
            data: {
                action: "fetch_order_details",
                order_id: orderId
            },
            success: function(response) {
                var data = JSON.parse(response);
                if (data.success) {
                    var dropdownContent = "<ul>";
                    var productTotal = 0;
                    var refundedTotal = 0;
                    data.order_items.forEach(function(item) {
                        var itemTotal = parseFloat(item.price) * parseInt(item.quantity);
                        productTotal += itemTotal;
                        var refundedAmount = item.refunded_amount ? parseFloat(item.refunded_amount) : 0;
                        refundedTotal += refundedAmount;
                        var refundedText = refundedAmount > 0 ? " <span class='refunded-item'>(REFUNDED " + item.refunded_quantity + " item" + (item.refunded_quantity > 1 ? "s" : "") + " for $" + refundedAmount.toFixed(2) + " total)</span>" : "";
                        dropdownContent += "<li>" + item.name + " - $" + item.price + " x " + item.quantity + refundedText + "</li>";
                    });
                    dropdownContent += "</ul>";
                    dropdownContent += "<div class=\"product-total\">Product Total w/o Additional Fees: $" + (productTotal - refundedTotal).toFixed(2) + "</div>";

                    // Get the user ID and username from the selected user
                    var selectedUserId = $("#shortcode-user-search").data("selected-user-id");
                    var selectedUserName = $("#shortcode-user-search").val(); // Get the username from the input value

                    // Add the "Add to Order" button to the dropdown content with the total item count, user ID, and username
                    dropdownContent += "<button class=\"add-to-manual-order-button\" data-order-id=\"" + orderId + "\" data-item-count=\"" + data.order_items.length + "\" data-user-id=\"" + selectedUserId + "\" data-user-name=\"" + selectedUserName + "\">Add to Order</button>";

                    detailsRow.find(".order-details-dropdown").html(dropdownContent);
                    detailsRow.show();
                }
                if (typeof callback === "function") callback(); // Call the callback function to remove the loading state
            },
            error: function() {
                alert("An error occurred while fetching the order details.");
                if (typeof callback === "function") callback(); // Call the callback function to remove the loading state
            }
        });
    };