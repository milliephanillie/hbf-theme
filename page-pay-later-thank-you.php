<?php
/* Template Name: Pay Later Thank You Page */

$current_user = wp_get_current_user();
$old_user = get_old_user(); 

$is_switched_user = $old_user && ($current_user->ID != $old_user->ID);
$has_capability = $old_user ? user_can($old_user->ID, 'view_extra_fields') : user_can($current_user->ID, 'view_extra_fields');

if (!$has_capability) {
    wp_die('Sorry, you are not allowed to access this page.', 'Restricted Access', array('response' => 403));
}

$current_user_id = get_current_user_id(); // Get the current user ID
$current_order_user_id = 0; // Initialize with a default value

// Check if order ID is present in the URL
if (isset($_GET['order_id']) && !empty($_GET['order_id'])) {
    $order_id = sanitize_text_field($_GET['order_id']);
    $order = wc_get_order($order_id);

    if ($order) {
        $current_order_user_id = $order->get_user_id(); // Get the user ID associated with the order.

        // Switch back to the original user if needed
        $original_user_id = WC()->session->get('switched_from_user_id');
        if ($original_user_id && function_exists('switch_to_user')) {
            switch_to_user($original_user_id);
            WC()->session->set('switched_from_user_id', null);
        }

        // Check if the current user is not the order user and switch if necessary.
        if ($current_user_id != $current_order_user_id && function_exists('switch_to_user')) {
            switch_to_user($current_order_user_id);
            // Update session to remember the original user.
            WC()->session->set('switched_from_user_id', $current_user_id);
        }
    }
}

if ( WC()->cart ) {
    WC()->cart->empty_cart();
}

get_header();

if (isset($_GET['order_id']) && !empty($_GET['order_id'])) {
    $order_id = sanitize_text_field($_GET['order_id']);
    $order = wc_get_order($order_id);

    if ($order) {
        // Inline styles
        echo "<style>
        /* Global container and section styles */
        .order-details-container { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; display: flex; justify-content: space-between; max-width: 1200px; margin: 20px auto; gap: 20px; }
        .column { background-color: #f5f5f5; padding: 20px; box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1); }
        .section { margin-bottom: 20px; }
        .section-title { color: #004c7e; font-size: 24px; margin-bottom: 15px; }
        .info-line { margin: 0; font-size: 16px; }
        
        /* List styles */
        .items-list { list-style: none; padding: 0; margin: 0; }
        .items-list li { background-color: #fff; padding: 10px; border-bottom: 1px solid #eee; }
        .item-name { font-weight: bold; }
        .item-details { font-size: 0.9em; }
        
        /* Button styles */
        .button { background-color: #004c7e; color: white; padding: 10px 15px; border: none; cursor: pointer; display: block; width: 100%; margin: 0 auto 10px; text-align: center; }
        .button:hover { background-color: #00395a; }
        
        /* Form container and field styles */
		.form-container { display: flex; justify-content: space-between; padding-top: 20px; }
		.form-container > div { width: calc(50% - 10px); }
		.form-container > div + div { margin-left: 20px; }
		.form-field { margin-bottom: 20px; }
		.form-field label { display: block; margin-bottom: 5px; }

		/* Uniform input styles */
		input.input-text,
		input[type='email'],
		input[type='tel'],
		input[type='number'],
		select, 
		textarea {
			height: 45px;
			width: 100%;
			padding: 10px;
			border: 1px solid #ccc;
			box-sizing: border-box;
			font-size: 16px;
			background-color: #fff;
			color: #4e4e4e;
		}

		/* Hide required asterisks - using attribute selector for increased specificity */
		label .required {
			display: none !important;
		}

		/* Spacing beneath headings */
		.form-container > div h3 {
			margin-bottom: 20px;
		}
		
		.billing-shipping-forms {
		display: none;
		}

        /* Checkbox styles */
        .woocommerce form .form-row .input-checkbox { margin-left: 0; }
        
        /* Toggle display for forms */
        .billing-shipping-forms { display: none; }
        
        /* Save button styles */
        .savecustinfo-button { 
            width: 100%; 
            padding: 10px; 
            background-color: #0073aa; 
            color: #ffffff; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            display: block; 
            margin-top: 10px; 
            height: 40px; 
        }
        .savecustinfo-button:hover { background-color: #005177; }
        .savecustinfo-button:disabled { background-color: #e1e1e1; color: #999; cursor: not-allowed; }
        
        /* Shipping fields styles */
        #shipping-fields { display: none; }
        
        /* Spacing beneath headings */
        .form-container > div h3 { margin-bottom: 20px; }
		
		/* New row for payment and fees */
        .payment-fees-container {  display: flex; justify-content: space-between; max-width: 1200px; margin: 20px auto; gap: 20px; }
        .payment-fees-column { background-color: #f5f5f5; padding: 20px; box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1); width: calc(50% - 10px); }
    </style>";

        // Order Details
        echo "<div class='order-details-container'>";
        // Left Column
        echo "<div class='column'>";
        echo "<div class='section'>";
        echo "<h1 class='section-title'>Order Details (Order ID: " . esc_html($order_id) . ")</h1>";

        // Customer Information
        echo "<div class='section'>";
        echo "<h3>Customer Information:</h3>";
        echo "<p class='info-line'>" . esc_html($order->get_formatted_billing_address()) . "</p>";
        echo "</div>";

        // Order Items
        echo "<div class='section'>";
        echo "<h3>Items:</h3>";
        echo "<ul class='items-list'>";
        foreach ($order->get_items() as $item_id => $item) {
            echo "<li><span class='item-name'>" . esc_html($item->get_name()) . "</span> <span class='item-details'>Qty: " . esc_html($item->get_quantity()) . " | Total: " . wc_price($item->get_total()) . "</span></li>";
        }
        echo "</ul>";
        echo "</div>";
		
		echo "</div>";
        echo "</div>"; // Close left column

        // Right Column for Billing and Shipping Forms
        echo "<div class='column'>";
        echo "<div class='section'>";
        echo "<button id='billing-shipping-info-button' class='button'>Billing & Shipping Information</button>";
        echo "<div class='billing-shipping-forms'>";
        echo display_billing_shipping_forms($order);
        echo "</div>"; // Close billing-shipping-forms
        echo "</div>"; // Close section
        echo "</div>"; // Close right column
        echo "</div>"; // Close order-details-container
		
		// Second row: Apply Payment and Custom Fees and Shipping
		echo "<div class='payment-fees-container'>";

		// Apply Payment Section
		echo "<div class='payment-fees-column'>";
		echo "<h3>Apply Payment:</h3>";
		echo "<form id='apply-payment-form' method='post' action='". esc_url( admin_url('admin-post.php') ) ."'>";
		echo "<p><label for='payment-amount'>Payment Amount:</label>";
		echo "<input type='number' id='payment-amount' name='payment_amount' placeholder='Enter payment amount' min='0' step='any' required /></p>";
		echo "<p>Please have your payment method ready. You will be redirected to a payment page to complete the transaction.</p>";
		echo "<input type='hidden' name='order_id' value='" . esc_attr($order_id) . "' />";
		echo "<input type='hidden' name='action' value='apply_partial_payment' />";
		wp_nonce_field('apply_partial_payment_nonce');
		echo "<button type='submit' class='go-to-payment-page, button'>Go To Payment</button>";
		echo "</form>";
		echo "</div>";

        // Custom Fees and Shipping Update
        echo "<div class='payment-fees-column'>";
        echo "<h3>Custom Fees and Shipping:</h3>";
        echo "<div><label for='admin-fee'>Admin Fee ($):</label><input type='number' id='admin-fee' min='0' step='0.01' placeholder='Enter admin fee amount' /></div>";
        echo "<div><label for='pallet-fee'>Pallet Fee ($):</label><input type='number' id='pallet-fee' min='0' step='0.01' placeholder='Enter pallet fee amount' /></div>";
        echo "<div><label for='misc-fee'>Misc Fee ($):</label><input type='number' id='misc-fee' min='0' step='0.01' placeholder='Enter miscellaneous fee amount' /></div>";
        echo "<button id='update-fees' data-order-id='" . esc_attr($order_id) . "' class='button'>Update Fees</button>";
        echo "</div>";
        echo "</div>"; // Close payment-fees-container
    } else {
        echo "<p>Order not found.</p>";
    }
} else {
    echo "<p>No order ID provided.</p>";
}

get_footer();

// Function to display Billing and Shipping forms for the order
function display_billing_shipping_forms($order) {
    $user_id = $order->get_user_id();
    $billing_fields = WC()->checkout->get_checkout_fields('billing');
    $shipping_fields = WC()->checkout->get_checkout_fields('shipping');
    ob_start();

    // Billing Information Form
    echo '<div class="form-container">';
    echo '<div>';
    echo '<h3>Billing Information:</h3>';
    echo '<form method="post" class="billing-info-form">';
    foreach ($billing_fields as $key => $field) {
        $field_value = get_user_meta($user_id, $key, true);
        woocommerce_form_field($key, $field, $field_value);
    }
    echo '<button type="submit" name="save_billing_info" class="savecustinfo-button">Save Billing Information</button>';
    echo '</form>';
    echo '</div>';

    // Shipping Information Form
    echo '<div>';
    echo '<form method="post" class="shipping-info-form">';
    echo '<h3> Modify Shipping Info:</h3>';
    foreach ($shipping_fields as $key => $field) {
        $field_value = get_user_meta($user_id, $key, true);
        woocommerce_form_field($key, $field, $field_value);
    }
    echo '<button type="submit" name="save_shipping_info" class="savecustinfo-button">Save Shipping Information</button>';
    echo '</form>';
    echo '</div>';
    echo '</div>'; // Close form-container

    return ob_get_clean();
}

// JavaScript for the page
echo
"<script>
    jQuery(document).ready(function($) {
        $('#billing-shipping-info-button').click(function() {
            $('.billing-shipping-forms').slideToggle();
        });
        $('#ship-to-different-address-checkbox').change(function() {
            if ($(this).is(':checked')) {
                $('#shipping-fields').slideDown();
            } else {
                $('#shipping-fields').slideUp();
            }
        });
    });
</script>";
?>