jQuery(document).ready(function($) {
    //Clear form fields on page load
    $(".billing-info-form")[0].reset();
    // $(".shipping-info-form")[0].reset();
    if ($('body').hasClass('custom-user-view')) {
        var storedData = localStorage.getItem('customerCreditInfo');
        if (storedData) {
            var creditInfo = JSON.parse(storedData);
            showCreditModal(creditInfo.customerName, creditInfo.creditBalance);
        }
    }
});

// User search functionality
$("#shortcode-user-search").on("input", function() {
    var searchTerm = $(this).val();
    if (searchTerm.length >= 3) {
        $.ajax({
            url: window.php_vars.ajaxurl,
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
                        var company = user.company ? user.company : 'No company';
                        var phone = user.billing_phone ? user.billing_phone : 'No phone';
                        dropdownContent += "<div class=\"shortcode-user-result\" data-user-id=\"" + user.id + "\">" + user.name + " (" + user.email + ") - Phone: " + phone + " - Company: " + company + "</div>";
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
    var selectedUserName = $(this).text().split(" (")[0];
    var selectedUserEmail = $(this).text().split(" (")[1].replace(")", "");
    $("#shortcode-user-search").data("selected-user-id", selectedUserId);
    $("#shortcode-user-search").data("selected-user-name", selectedUserName);
    $("#shortcode-user-search").data("selected-user-email", selectedUserEmail);
    $("#shortcode-user-search-results").empty();
    $("#loadPreviousOrder").prop("disabled", false);  // Enable the "Load a Previous Order" button

    // Clear the search bar
    setTimeout(function() {
        $("#shortcode-user-search").val("");
    }, 1500);

    // Set the value of the hidden input field
    $("#selected_user_id").val(selectedUserId);

    // Populate the Billing and Shipping Information forms with the selected user data
    $.ajax({
        url: window.php_vars.ajaxurl,
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

// Flag to track the visibility of the previous orders dropdown
var isPreviousOrdersVisible = false;

// Handle click event on the "Load a Previous Order" button
$("#loadPreviousOrder").off("click").on("click", function() {
    var selectedUserId = $("#shortcode-user-search").data("selected-user-id");
    console.log("Fetching orders for User ID:", selectedUserId);

    // Toggle the visibility of the previous orders dropdown with a sliding animation
    var previousOrders = $("#previousOrders");
    var loadOrderButton = $(this); // Reference to the button

    if (previousOrders.is(":visible")) {
        previousOrders.slideUp(function() { // Slide up if visible
            loadOrderButton.text("Load a Previous Order"); // Reset the button text after slide up
            loadOrderButton.removeClass("orders-loaded"); // Remove the class when orders are not visible
        });
    } else {
        // Make an AJAX request to fetch the previous orders if not currently visible
        $.ajax({
            url: window.php_vars.ajaxurl,
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
                    previousOrders.slideDown(function() { // Slide down to show the orders table
                        loadOrderButton.text("Orders Loaded: " + response.orders.length); // Update the button text after slide down
                        loadOrderButton.addClass("orders-loaded"); // Add the class when orders are visible
                    });
                    $("#ordersTable thead").show(); // Ensure the table header is shown
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
$(document).off("click", "#ordersTable tbody tr").on("click", "#ordersTable tbody tr", function(e) {
    // Ignore clicks on the order details row
    if ($(e.target).closest(".order-details-row").length) {
        return;
    }

    var orderId = $(this).find("td:first").text();
    var detailsRow = $(this).next(".order-details-row");

    // Check if the details row already exists
    if (detailsRow.length > 0) {
        // If it exists, just toggle it with animation
        detailsRow.stop().slideToggle(1000, function() {
// If after toggling, the details row
            // is not visible, remove it from the DOM
            if (!$(this).is(":visible")) {
                $(this).remove();
            }
        });
    } else {
        // If it does not exist, create it and fetch the details
        $(this).addClass("loading-details");
        var templateHtml = $("#orderDetailsRowTemplate").html();
        detailsRow = $(templateHtml);
        // Insert the details row directly after the clicked row
        $(this).after(detailsRow);
        detailsRow.addClass("order-details-row");
        fetchOrderDetails(orderId, detailsRow, function() {
            $(this).removeClass("loading-details");
            // Slide down with animation
            detailsRow.slideDown(400);
        });
    }
});

// Function to show the credit modal with customer details
function showCreditModal(customerName, creditBalance) {
    $('#creditApplicationModal .customer-name').text('Customer: ' + customerName);
    $('#creditApplicationModal .credit-balance').text('Credit Balance: ' + creditBalance);
    $('#creditApplicationModal').show();
}

// Store credit information in localStorage
function storeCreditInfoInLocalStorage(customerName, creditBalance) {
    var creditInfo = {
        customerName: customerName,
        creditBalance: creditBalance
    };
    localStorage.setItem('customerCreditInfo', JSON.stringify(creditInfo));
}

// Get Customer Credit Info
function fetchCustomerCreditInfo(userId, callback) {
    console.log("Calling fetchCustomerCreditInfo with userId:", userId);
    $.ajax({
        url: window.php_vars.ajaxurl,
        type: 'POST',
        data: {
            action: 'get_customer_credit_info',
            userId: userId
        },
        success: function(response) {
            var data = JSON.parse(response);
            if (data.success) {
                // Save to localStorage
                localStorage.setItem('customerCreditInfo', JSON.stringify({
                    customerName: data.customerName,
                    creditBalance: data.creditBalance,
                    userId: userId
                }));

                // Call callback if provided
                if (callback) callback(data.customerName, data.creditBalance);

                $('#creditApplicationModal').removeClass('custom-cloaking');
            } else {
                console.error("Failed to fetch credit info:", data.message);
                alert("Failed to fetch credit info: " + data.message);
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error("AJAX Error:", textStatus, errorThrown);
            alert("Error fetching customer credit info.");
        }
    });
}

// Bind click event to the "Switch to User" button
$('#switchToUser').click(function() {
    var selectedUserId = $('#shortcode-user-search').data('selected-user-id');
    console.log("Selected User ID:", selectedUserId);
    var currentUser = $(this).data('current-user');

    if (!selectedUserId) {
        alert('Please select a user first.');
        return;
    }

    // Store selectedUserId in sessionStorage
    sessionStorage.setItem('selectedUserId', selectedUserId);

    // AJAX call to switch user
    $.ajax({
        url: window.php_vars.ajaxurl,
        type: 'POST',
        data: {
            action: 'switch_to_selected_user',
            user_id: selectedUserId,
            current_user_id: currentUser,
            security: window.php_vars.switch_to_selected_user_nonce,
        },
        beforeSend: function() {
            console.log("Sending AJAX request with user_id:", selectedUserId);
        },
        success: function(response) {
            var data = JSON.parse(response);
            if (data.success) {
                alert('Switched to customer successfully.');

                // Retrieve selectedUserId from sessionStorage
                var userId = sessionStorage.getItem('selectedUserId');

                fetchCustomerCreditInfo(selectedUserId, function(customerName, creditBalance) {
                    showCreditModal(customerName, creditBalance);
                });
                location.reload();
            } else {
                alert('Error switching to customer: ' + data.message);
            }
        },
        error: function() {
            alert('An error occurred while switching to the customer.');
        }
    });
});

// Handle the "Add to Order" button click within the order details dropdown
$(document).on("click", ".add-to-manual-order-button", function() {
    var orderId = $(this).data("order-id");
    var itemCount = $(this).data("item-count");
    var userId = $(this).data("user-id");

    // Retrieve the username from the #shortcode-user-search input's data attribute
    var userName = $("#shortcode-user-search").data("selected-user-name");

    var userConfirmed = confirm("Add " + itemCount + " items to cart and continue as " + userName + "?");
    if (userConfirmed) {
        populateCartWithOrder(orderId, userId);
        // Retrieve and update the credit balance
        fetchCustomerCreditInfo(userId, function(customerName, creditBalance) {
            // Update the sessionStorage with the new credit info
            sessionStorage.setItem('customerCreditInfo', JSON.stringify({ customerName: customerName, creditBalance: creditBalance }));

            // Update the credit modal with the new information
            $('#creditApplicationModal .customer-name').text('Customer: ' + customerName);
            $('#creditApplicationModal .credit-balance').text('Credit Balance: $' + creditBalance);
        });
    }
});

// Function to populate the cart with the selected order
function populateCartWithOrder(orderId, userId) {
    // Show loading indicator
    var $loadingIndicator = $('#loading-indicator');
    $loadingIndicator.show();

    $.ajax({
        url: window.php_vars.ajaxurl,
        type: "POST",
        dataType: "json",
        cache: false,
        data: {
            action: "populate_cart_with_order",
            order_id: orderId,
            user_id: userId
        },
        context: this,
        success: function(data) {
            // Hide loading indicator
            $loadingIndicator.hide();

            if (data.success) {
                // Redirect to the checkout page
                window.location.href = "/checkout";
            } else {
                // Show an error message to the user
                alert("Error populating cart: " + data.message);
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            // Hide loading indicator
            $loadingIndicator.hide();

            // Handle low-level network errors
            alert("An error occurred while processing your request. Please try again.");
        }
    });
}
