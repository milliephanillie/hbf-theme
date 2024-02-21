jQuery(document).ready(function($) {
    // Search for users based on input
    $('#loadUser').on('input', function() {
        var searchTerm = $(this).val();
        if (searchTerm.length >= 3) {
            $.ajax({
                url: window.php_vars.adminAjaxUrl,
                type: 'POST',
                data: {
                    action: 'search_users_for_manual_orders',
                    search_term: searchTerm
                },
                success: function(response) {
                    var data = JSON.parse(response);
                    console.log(data);
                    if (data.success && data.users.length > 0) {
                        var dropdownContent = '';
                        data.users.forEach(function(user) {
                            console.log('User object:', user); // Log each user object to inspect its structure
                            var phone = user.billing_info && user.billing_info.billing_phone ? user.billing_info.billing_phone : 'No phone';
                            console.log('Phone:', phone); // Log the phone number
                            dropdownContent += '<div class="user-result" data-user-id="' + user.id + '">' + user.name + ' (' + user.email + ') - Phone: ' + phone + '</div>';
                        });
                        $('#userSearchResults').html(dropdownContent).show(); // Show the dropdown
                    } else {
                        $('#userSearchResults').hide(); // Hide the dropdown if no users are found
                    }
                }
            });
        }
    });

    // Handle user selection from the dropdown
    $(document).on('click', '.user-result', function() {
        var selectedUserId = $(this).data('user-id');
        var selectedUserName = $(this).text();
        $('#loadUser').val(selectedUserName).data('selected-user-id', selectedUserId);
        $('#userSearchResults').empty();
        $('#switchToUser').prop('disabled', false);  // Enable the "Switch to User" button
        $('#loadPreviousOrder').prop('disabled', false);  // Enable the "Load a Previous Order" button
    });

    // Empty cart button click handler
    $('#emptyCart').click(function() {
        $.ajax({
            url: window.php_vars.adminAjaxUrl,
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

    // Optionally, you can enable/disable the EMPTY CART button based on the cart status
    function checkCartStatus() {
        $.ajax({
            url: window.php_vars.adminAjaxUrl,
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

    // Call checkCartStatus on page load and whenever needed
    checkCartStatus();

    // Handle click event on the "Load a Previous Order" button
    $('#loadPreviousOrder').off('click').on('click', function() {
        var selectedUserId = $('#loadUser').data('selected-user-id');

        // Make an AJAX request to fetch the previous orders
        $.ajax({
            url: window.php_vars.adminAjaxUrl,
            type: 'POST',
            data: {
                action: 'fetch_previous_orders',
                user_id: selectedUserId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.orders.length > 0) {
                    var ordersTableBody = $('#ordersTable tbody');
                    ordersTableBody.empty();  // Clear any existing rows
                    response.orders.forEach(function(order) {
                        var orderRow = '<tr>' +
                            '<td>' + order.order_id + '</td>' +
                            '<td>' + order.order_date + '</td>' +
                            '<td>' + order.order_total + '</td>' +
                            '</tr>';
                        ordersTableBody.append(orderRow);
                    });
                    $('#previousOrders').show();  // Show the orders table
                    $('#ordersTable thead').show();  // Show the table header
                } else {
                    alert('No orders found or an error occurred.');
                }
            },
            error: function() {
                alert('An error occurred while fetching the orders.');
            }
        });
    });

    // Handle order row click to show order details
    $(document).off('click', '#ordersTable tbody tr').on('click', '#ordersTable tbody tr', function() {
        var orderId = $(this).find('td:first').text();
        var detailsRow = $(this).next('.order-details-row');
        if (detailsRow.length === 0) {
            var templateHtml = $('#orderDetailsRowTemplate').html();
            detailsRow = $(templateHtml);
            $(this).after(detailsRow);
            detailsRow.addClass('order-details-row').show();
            fetchOrderDetails(orderId, detailsRow);
        } else {
            detailsRow.toggle();
        }
    });

    // Handle the "Add to Order" button click within the order details dropdown
    $(document).on('click', '.add-to-manual-order-button', function() {
        var orderId = $(this).data('order-id');
        var itemCount = $(this).data('item-count');
        var userId = $(this).data('user-id');
        var userName = $(this).data('user-name'); // Retrieve the username

        var userConfirmed = confirm('Add ' + itemCount + ' items to cart and continue as ' + userName + '?');
        if (userConfirmed) {
            populateCartWithOrder(orderId, userId);
        }
    });

    // Function to populate the cart with the selected order
    function populateCartWithOrder(orderId, userId) {
        $.ajax({
            url: window.php_vars.adminAjaxUrl,
            type: 'POST',
            data: {
                action: 'populate_cart_with_order',
                order_id: orderId,
                user_id: userId
            },
            success: function(response) {
                var data = JSON.parse(response);
                if (data.success) {
                    // Switch to the user associated with the order
                    //  var userId = $('#loadUser').data('selected-user-id');
                    window.location.href = '/checkout';
                }
                else {
                    alert('Error populating cart: ' + data.message);
                }
                // switchToUser(userId, function() {window.location.href = '/checkout';});
            }
        });
    }

    function switchToUser(userId, callback) {
        $.ajax({
            url: window.php_vars.adminAjaxUrl,
            type: 'POST',
            data: {
                action: 'switch_to_selected_user',
                user_id: userId
            },
            success: function(response) {
                var data = JSON.parse(response);
                if (data.success) {
                    callback();
                } else {
                    alert('Error switching user: ' + data.message);
                }
            },
            error: function() {
                alert('An error occurred while switching the user.');
            }
        });
    }


    // Function to fetch order details and populate the dropdown
    function fetchOrderDetails(orderId, detailsRow) {
        $.ajax({
            url: window.php_vars.adminAjaxUrl,
            type: 'POST',
            data: {
                action: 'fetch_order_details',
                order_id: orderId
            },
            success: function(response) {
                var data = JSON.parse(response);
                if (data.success) {
                    var dropdownContent = '<ul>';
                    var productTotal = 0;
                    var totalItemCount = 0; // Initialize total item count
                    data.order_items.forEach(function(item) {
                        var itemTotal = parseFloat(item.price) * parseInt(item.quantity);
                        productTotal += itemTotal;
                        totalItemCount += parseInt(item.quantity); // Sum up the quantities
                        dropdownContent += '<li>' + item.name + ' - $' + item.price + ' x ' + item.quantity + '</li>';
                    });
                    dropdownContent += '</ul>';
                    dropdownContent += '<div class="product-total">Product Total w/o Additional Fees: $' + productTotal.toFixed(2) + '</div>';

                    // Get the user ID and username from the selected user
                    var selectedUserId = $('#loadUser').data('selected-user-id');
                    var selectedUserName = $('#loadUser').val(); // Get the username from the input value

                    // Add the "Add to Order" button to the dropdown content with the total item count, user ID, and username
                    dropdownContent += '<button class="add-to-manual-order-button" data-order-id="' + orderId + '" data-item-count="' + totalItemCount + '" data-user-id="' + selectedUserId + '" data-user-name="' + selectedUserName + '">Add to Order</button>';

                    detailsRow.find('.order-details-dropdown').html(dropdownContent);
                }
            }
        });
    }
});