<?php

// Skip cart page for manual orders

/**
 * HBF_CheckoutFlowHelper
 */
add_filter( 'wp_head', 'hfood_skip_cart_redirect_checkout' );
function hfood_skip_cart_redirect_checkout( $url ) {
	global $post;

	if(is_user_logged_in()){
		$user = wp_get_current_user();

		if($post->post_name == 'cart' && (in_array('administrator', $user->roles) || is_old_admin())){
			if(\WC()->cart->get_cart_contents_count() == 0){
				?>
				<script>
                    window.location = '/manual-orders';
				</script>
				<?php
			}
			?>
			<script>
                window.location = '<?= wc_get_checkout_url(); ?>';
			</script>
			<?php
		}
	}
}



/**
 * HBF_ManualOrderItem
 */
add_action('admin_bar_menu', 'manual_order_item_menu', 500);
function manual_order_item_menu($admin_bar) {
    global $current_user;

    if (is_array($current_user->roles) && (in_array('administrator', $current_user->roles) || in_array('view_extra_fields', $current_user->allcaps) || is_old_admin())) {
        $admin_bar->add_menu(array(
            'id' => 'manual-order-custom',
            'title' => 'Manual Orders',
            'href'  => site_url('/manual-orders'),
            'parent' => 'top-secondary',
        ));
    }
}


/**
 * HBF_UpdateShippingMethod
 */
function update_shipping_method() {
	if (isset($_POST['shipping_method']) && !empty($_POST['shipping_method'])) {
		$chosen_methods = WC()->session->get('chosen_shipping_methods');
		$chosen_methods[0] = wc_clean($_POST['shipping_method']);
		WC()->session->set('chosen_shipping_methods', $chosen_methods);

		// Recalculate the cart totals
		WC()->cart->calculate_totals();

		// Return the updated totals
		echo json_encode(array(
			'success' => true,
			'message' => 'Shipping method updated successfully.',
			'subtotal' => WC()->cart->get_cart_subtotal(),
			'total' => WC()->cart->get_total()
		));
	} else {
		echo json_encode(array(
			'success' => false,
			'message' => 'Invalid or missing shipping method.'
		));
	}
	wp_die(); 
}

add_action('wp_ajax_update_shipping_method', 'update_shipping_method');
add_action('wp_ajax_nopriv_update_shipping_method', 'update_shipping_method');

/**
 * HBF_CheckoutFlowHelper
 */
add_filter('woocommerce_get_stock_html', 'custom_remove_in_stock_text', 10, 2);
function custom_remove_in_stock_text( $html, $product ) {
	if ( $product->is_in_stock() ) {
		return '';
	}
	return $html;
}

//User Search

/**
 * HBF_SearchUsersForManualOrders
 */
add_action('wp_ajax_search_users_for_manual_orders', 'search_users_for_manual_orders');
add_action('wp_ajax_nopriv_search_users_for_manual_orders', 'search_users_for_manual_orders');
function search_users_for_manual_orders() {
    // Check if a search term is set
    if (!isset($_POST['search_term'])) {
        echo json_encode(array('success' => false, 'message' => 'No search term provided.'));
        wp_die();
    }

    $search_term = sanitize_text_field($_POST['search_term']);

    // Query users based on the search term
    $users_by_email_and_name = get_users(array(
        'search' => '*' . esc_attr($search_term) . '*',
        'search_columns' => array('user_login', 'user_nicename', 'user_email'),
    ));

    // Query users by billing phone number
    $users_by_billing_phone = get_users(array(
        'meta_query' => array(
            array(
                'key' => 'billing_phone',
                'value' => $search_term,
                'compare' => 'LIKE'
            )
        )
    ));

    // Query users by shipping phone number
    $users_by_shipping_phone = get_users(array(
        'meta_key' => 'shipping_phone',
        'meta_value' => '%' . $search_term . '%',  // Add wildcards
        'meta_compare' => 'LIKE',
    ));

    // Query users by billing company name
    $users_by_shipping_company = get_users(array(
        'meta_query' => array(
            array(
                'key' => 'shipping_company',
                'value' => $search_term,
                'compare' => 'LIKE'
            )
        )
    ));

    // Merge the results from all queries and remove duplicates
    $users = array_unique(array_merge($users_by_email_and_name, $users_by_billing_phone, $users_by_shipping_phone, $users_by_shipping_company), SORT_REGULAR);

    // Prepare the response
    $response = array();
    foreach ($users as $user) {
        $user_meta = get_user_meta($user->ID);
        $response[] = array(
            'id' => $user->ID,
            'name' => $user->display_name,
            'email' => $user->user_email,
            // Add the billing and shipping phone to the response
            'billing_phone' => !empty($user_meta['billing_phone']) ? $user_meta['billing_phone'][0] : '',
			'company' => !empty($user_meta['shipping_company']) ? $user_meta['shipping_company'][0] : 'No company',
            'shipping_phone' => !empty($user_meta['shipping_phone']) ? $user_meta['shipping_phone'][0] : '',
        );
    }

    // Return the results as a JSON response
    echo json_encode(array('success' => true, 'users' => $response));
    wp_die();
}

/**
 * HBF_OrderData
 */
add_action('wp_ajax_fetch_previous_orders', 'fetch_previous_orders');
add_action('wp_ajax_nopriv_fetch_previous_orders', 'fetch_previous_orders');
function fetch_previous_orders() {
	if (!isset($_POST['user_id'])) {
		echo json_encode(array('success' => false, 'message' => 'No user ID provided.'));
		wp_die();
	}
	
    // Check if the user has 'manual_ordering' or 'view_extra_fields' capability
    if (!current_user_can('manual_ordering') && !current_user_can('view_extra_fields')) {
        echo json_encode(array('success' => false, 'message' => 'Permission denied.'));
        wp_die();
    }

	if (!function_exists('wc_get_orders')) {
		echo json_encode(array('success' => false, 'message' => 'WooCommerce not active.'));
		wp_die();
	}

	$user_id = intval($_POST['user_id']);

	// Fetch the previous orders for the user
	$orders = wc_get_orders(array('customer_id' => $user_id, 'limit' => -1));

	$response = array();
	foreach ($orders as $order) {
		$response[] = array(
			'order_id' => $order->get_id(),
			'order_date' => $order->get_date_created()->date('Y-m-d H:i:s'),
			'order_total' => $order->get_total(),
		);
	}

	if (empty($response)) {
		echo json_encode(array('success' => false, 'message' => 'No orders found for this user.'));
		wp_die();
	}

	echo json_encode(array('success' => true, 'orders' => $response));
	wp_die();
}

/**
 * HBF_OrderData
 */
add_action('wp_ajax_fetch_previous_orders_for_credits', 'fetch_previous_orders_for_credits');
add_action('wp_ajax_nopriv_fetch_previous_orders_for_credits', 'fetch_previous_orders_for_credits');
function fetch_previous_orders_for_credits() {
    if (!isset($_POST['user_id'])) {
        echo json_encode(array('success' => false, 'message' => 'No user ID provided.'));
        wp_die();
    }
    
    // Check if the user has 'manual_ordering' or 'view_extra_fields' capability
    if (!current_user_can('manual_ordering') && !current_user_can('view_extra_fields')) {
        echo json_encode(array('success' => false, 'message' => 'Permission denied.'));
        wp_die();
    }

    if (!function_exists('wc_get_orders')) {
        echo json_encode(array('success' => false, 'message' => 'WooCommerce not active.'));
        wp_die();
    }

    $user_id = intval($_POST['user_id']);
    $orders = wc_get_orders(array('customer_id' => $user_id, 'limit' => -1));

    $response = array();
    foreach ($orders as $order) {
        $is_fully_refunded = $order->has_status('refunded');
        $total_refunded = $order->get_total_refunded();
        $order_total = $order->get_total();
        $is_partially_refunded = $total_refunded > 0 && $total_refunded < $order_total;

        $response[] = array(
            'order_id' => $order->get_id(),
            'order_date' => $order->get_date_created()->date('Y-m-d H:i:s'),
            'order_total' => $order_total,
            'is_refunded' => $is_fully_refunded,
            'is_partially_refunded' => $is_partially_refunded
        );
    }

    if (empty($response)) {
        echo json_encode(array('success' => false, 'message' => 'No orders found for this user.'));
        wp_die();
    }

    echo json_encode(array('success' => true, 'orders' => $response));
    wp_die();
}

/**
 * AJAX/HBF_Cart
 */
add_action('wp_ajax_populate_cart_with_order', 'populate_cart_with_order');
add_action('wp_ajax_nopriv_populate_cart_with_order', 'populate_cart_with_order');
function populate_cart_with_order() {
	if (!isset($_POST['order_id'])) {
		echo json_encode(array('success' => false, 'message' => 'No order ID provided.'));
		wp_die();
	}

	$order_id = intval($_POST['order_id']);
	$order = wc_get_order($order_id);

	if (!$order) {
		echo json_encode(array('success' => false, 'message' => 'Invalid order ID.'));
		wp_die();
	}

	// Get the user ID associated with the order
	$user_id = isset($_POST['user_id']) ? $_POST['user_id'] : $order->get_user_id();

	// Switch to the user associated with the order
	if (function_exists('switch_to_user') && $user_id) {
		switch_to_user($user_id, true);

	}

	// Empty the current cart
	WC()->cart->empty_cart();

	// Add order items to the cart using the original quantity from the order
	foreach ($order->get_items() as $item) {
		$product_id = $item->get_product_id();
		$quantity = $item->get_quantity();

		if(isset($item['variation_id'])){
			WC()->cart->add_to_cart($product_id, $quantity, $item['variation_id']);
		}else{
			WC()->cart->add_to_cart($product_id, $quantity);
		}

	}

	//WC()->session->set_customer_session_cookie( true );

	WC()->cart->calculate_shipping();

	echo json_encode(array('success' => true));
	wp_die();
}

/**
 * AJAX/HBF_Cart
 */
add_action('wp_ajax_empty_cart', 'empty_cart');
add_action('wp_ajax_nopriv_empty_cart', 'empty_cart');
function empty_cart() {
	WC()->cart->empty_cart();
	echo json_encode(array('success' => true));
	wp_die();
}

/**
 * AJAX/HBF_Cart
 */
add_action('wp_ajax_check_cart_status', 'check_cart_status');
add_action('wp_ajax_nopriv_check_cart_status', 'check_cart_status');
function check_cart_status() {
	$cart_has_items = !WC()->cart->is_empty();
	echo json_encode(array('cart_has_items' => $cart_has_items));
	wp_die();
}

/**
 * AJAX/HBF_OrderData
 */
add_action('wp_ajax_fetch_order_details', 'fetch_order_details');
add_action('wp_ajax_nopriv_fetch_order_details', 'fetch_order_details');
function fetch_order_details() {
    if (!isset($_POST['order_id'])) {
        echo json_encode(array('success' => false, 'message' => 'No order ID provided.'));
        wp_die();
    }

    $order_id = intval($_POST['order_id']);
    $order = wc_get_order($order_id);

    if (!$order) {
        echo json_encode(array('success' => false, 'message' => 'Invalid order ID.'));
        wp_die();
    }

    $order_items = array();
    $item_count = 0; // Initialize item count
    $product_total = 0; // Initialize product total

    foreach ($order->get_items() as $item_id => $item) {
        $product_id = $item->get_product_id();
        $product = wc_get_product($product_id);
        $product_name = $item->get_name();
        $item_price = wc_format_decimal($order->get_item_total($item, false, false), 2); // Get item total excluding taxes and discounts
        $item_quantity = $item->get_quantity();

        $order_items[] = array(
            'name' => $product_name,
            'price' => $item_price,
            'quantity' => $item_quantity
        );

        $item_count += $item_quantity; // Update item count
        $product_total += $item_price * $item_quantity; // Update product total
    }

    $user_id = $order->get_user_id(); // Get the user ID associated with the order

    echo json_encode(array(
        'success' => true,
        'order_items' => $order_items,
        'item_count' => $item_count, // Return item count
        'product_total' => $product_total, // Return product total
        'user_id' => $user_id // Return user ID
    ));
    wp_die();
}

/**
 * HBF_OrderData
 */
add_action('wp_ajax_fetch_order_details_for_credit', 'fetch_order_details_for_credit');
add_action('wp_ajax_nopriv_fetch_order_details_for_credit', 'fetch_order_details_for_credit');
function fetch_order_details_for_credit() {
    if (!isset($_POST['order_id'])) {
        echo json_encode(array('success' => false, 'message' => 'No order ID provided.'));
        wp_die();
    }

    $order_id = intval($_POST['order_id']);
    $order = wc_get_order($order_id);

    if (!$order || $order->has_status('refunded')) {
        echo json_encode(array('success' => false, 'message' => 'Order is fully refunded or invalid.'));
        wp_die();
    }

    // Fetch credited items meta
    $credited_items = get_post_meta($order_id, 'credited_items', true) ?: [];

    $order_items = array();
    foreach ($order->get_items() as $item_id => $item) {
        $refunded_quantity = $order->get_qty_refunded_for_item($item_id);
        $remaining_quantity = $item->get_quantity() - abs($refunded_quantity);

        if ($remaining_quantity <= 0) {
            continue; // Skip fully refunded items
        }

        $product_id = $item->get_product_id();
        $variation_id = $item->get_variation_id();
        $product = wc_get_product($variation_id ? $variation_id : $product_id);

        $product_name = $product->get_name();
        if ($variation_id) {
            $product_name .= ' - ' . implode(', ', $product->get_variation_attributes());
        }

        $line_total = $item->get_total(); // Line total excluding tax
        $line_tax = $item->get_total_tax(); // Total tax for the line

        // Construct a unique key for the product or variation
        $item_key = $variation_id ? $product_id . '_' . $variation_id : $product_id;

        // Fetch credited quantity using the unique key
        $credited_qty = isset($credited_items[$item_key]) ? absint($credited_items[$item_key]) : 0;

        $order_items[] = array(
            'product_id' => $product_id,
            'variation_id' => $variation_id,
            'name' => $product_name,
            'price' => wc_format_decimal($line_total / $item->get_quantity(), 2),
            'tax' => wc_format_decimal($line_tax, 2),
            'quantity' => $remaining_quantity,
            'line_total' => wc_format_decimal($line_total + $line_tax, 2),
            'credited_qty' => $credited_qty
        );
    }

    error_log('Credit Module: Order Items - ' . print_r($order_items, true));

    echo json_encode(array(
        'success' => true,
        'order_items' => $order_items
    ));
    wp_die();
}


/**
 * HBF_UserBillingInfo
 */
function fetch_billing_info() {
	// Verify nonce
	if (!isset($_POST['_ajax_nonce']) || !wp_verify_nonce($_POST['_ajax_nonce'], 'fetch_billing_info_nonce')) {
		wp_send_json_error('Nonce verification failed.');
		return;
	}

	// Check for necessary parameters
	if (!isset($_POST['user_id'])) {
		wp_send_json_error('User ID not provided.');
		return;
	}

	$user_id = intval($_POST['user_id']);
	$billing_info = get_user_meta($user_id, 'billing', true);
	wp_send_json_success($billing_info);
}
add_action('wp_ajax_fetch_billing_info', 'fetch_billing_info');

/**
 * HBF_ManualOrderItem
 */
add_action('admin_bar_menu', 'add_switching_info_to_admin_bar', 100);
function add_switching_info_to_admin_bar($admin_bar) {
	// Check if the current user has the 'manual_ordering' capability
	if (current_user_can('manual_ordering')) {
		$original_user = false;

		// First, attempt to get the original user from the transient
		$current_user_id = get_current_user_id();
		$original_user_id = get_transient('original_user_' . $current_user_id);
		if ($original_user_id) {
			$original_user = get_userdata($original_user_id);
		}

		// If transient method fails, fall back to the User Switching plugin's method
		if (!$original_user && class_exists('user_switching') && method_exists('user_switching', 'get_original_user')) {
			$original_user = user_switching::get_original_user();
		}

		$current_user_data = get_userdata(get_current_user_id());
		$current_username = $current_user_data->user_login;

		$menu_class = $original_user ? 'switched-user' : '';

		$admin_bar->add_menu(array(
			'id' => 'currently-acting-as',
			'title' => 'Currently Acting As: ' . $current_username,
			'parent' => 'top-secondary',
			'meta' => array('class' => $menu_class)
		));
	}
}


// Add Custom Fields to WooCommerce Checkout
// function custom_checkout_fields( $fields ) {
// 	if ( current_user_can( 'view_extra_fields' ) ) {
// 		// Add Order Type field
// 		$fields['order']['order_type'] = array(
// 			'label'     => __('Order Type', 'woocommerce'),
// 			'required'  => false,
// 			'class'     => array('form-row-wide', 'order-type-field'),
// 			'clear'     => true,
// 			'type'      => 'select',
// 			'options'   => array(
// 				'national' => 'National',
// 				'international' => 'International'
// 			),
// 			'default'   => 'national'
// 		);

// 		$fields['order']['order_shipping_custom'] = array(
// 			'label'     => __('Shipping', 'woocommerce'),
// 			'placeholder'   => _x('$', 'placeholder', 'woocommerce'),
// 			'required'  => false,
// 			'class'     => array('form-row-first', 'custom-field'),
// 			'clear'     => false
// 		);

// 		$fields['order']['order_admin_fee'] = array(
// 			'label'     => __('Admin Fee', 'woocommerce'),
// 			'placeholder'   => _x('$', 'placeholder', 'woocommerce'),
// 			'required'  => false,
// 			'class'     => array('form-row-first', 'custom-field'),
// 			'clear'     => false
// 		);

// 		$fields['order']['order_pallet_fee'] = array(
// 			'label'     => __('Pallet Fee', 'woocommerce'),
// 			'placeholder'   => _x('$', 'placeholder', 'woocommerce'),
// 			'required'  => false,
// 			'class'     => array('form-row-first', 'custom-field'),
// 			'clear'     => false
// 		);

// 		$fields['order']['order_misc_fee'] = array(
// 			'label'     => __('Miscellaneous Fee', 'woocommerce'),
// 			'placeholder'   => _x('$', 'placeholder', 'woocommerce'),
// 			'required'  => false,
// 			'class'     => array('form-row-first', 'custom-field'),
// 			'clear'     => false
// 		);

// 		// Add Update Total button
// 		$fields['order']['update_total_button'] = array(
// 			'type' => 'button',
// 			'class' => array('button', 'update-total-button'),
// 			'label' => __('Update Total', 'woocommerce'),
// 			'attributes' => array(
// 				'disabled' => 'disabled',  // The button will be disabled initially
// 				'onclick' => 'updateCartTotals()'  // JavaScript function to be called when the button is clicked
// 			)
// 		);
// 	}
// 	return $fields;
// }
//add_filter( 'woocommerce_checkout_fields' , 'custom_checkout_fields' );

/**
 * HBF_CartCaculateFees
 */
add_action( 'woocommerce_cart_calculate_fees', 'custom_checkout_fee' );
function custom_checkout_fee() {
    if ( current_user_can( 'view_extra_fields' ) ) {
        $shipping = WC()->session->get('shipping-amount') ?? 0;
        $admin_fee = isset( $_POST['order_admin_fee'] ) ? floatval($_POST['order_admin_fee']) : 0;
        $pallet_fee = isset( $_POST['order_pallet_fee'] ) ? floatval($_POST['order_pallet_fee']) : 0;
        $misc_fee = isset( $_POST['order_misc_fee'] ) ? floatval($_POST['order_misc_fee']) : 0;

        $total_fees = $admin_fee + $pallet_fee + $misc_fee;

        if ($total_fees != 0 || $shipping != 0 || $admin_fee != 0 || $pallet_fee != 0 || $misc_fee != 0) {
            WC()->cart->add_fee('Additional Fees', $total_fees);
        }


		$order_type = isset( $_POST['order_type'] ) ? $_POST['order_type'] : 'national';
		if ( $order_type == 'international' ) {
			$total_cost = WC()->cart->cart_contents_total;
			$fee = $total_cost * 0.03;  // 3% fee
			WC()->cart->add_fee( '3% Credit Card Convenience Fee', $fee );

			// Set Free Shipping
			$shipping_packages = WC()->cart->get_shipping_packages();
			if ( is_array( $shipping_packages ) ) {
				foreach ( $shipping_packages as $package_id => $package ) {
					if ( isset( $package['rates'] ) && is_array( $package['rates'] ) ) {
						foreach ( $package['rates'] as $rate ) {
							if ( 'free_shipping:10' === $rate->method_id ) {
								WC()->session->set( 'chosen_shipping_methods', array( $package_id => $rate->id ) );
								break 2;
							}
						}
					}
				}
			}
		}
	}
}

/**
 * HBF_AdminOrderHelper
 */
add_action( 'woocommerce_admin_order_data_after_billing_address', 'display_custom_fields_in_admin_order', 10, 1 );
function display_custom_fields_in_admin_order( $order ) {
	echo '<p><strong>' . __('Order Type') . ':</strong> ' . get_post_meta( $order->get_id(), '_order_type', true ) . '</p>';
	echo '<div style="clear:both;"></div>';  // Clear the float to ensure the next fields line up 2x2
	echo '<p><strong>' . __('Shipping') . ':</strong> ' . get_post_meta( $order->get_id(), '_order_shipping_custom', true ) . '</p>';
	echo '<p><strong>' . __('Admin Fee') . ':</strong> ' . get_post_meta( $order->get_id(), '_order_admin_fee', true ) . '</p>';
	echo '<p><strong>' . __('Pallet Fee') . ':</strong> ' . get_post_meta( $order->get_id(), '_order_pallet_fee', true ) . '</p>';
	echo '<p><strong>' . __('Miscellaneous Fee') . ':</strong> ' . get_post_meta( $order->get_id(), '_order_misc_fee', true ) . '</p>';
}


/**
 * HBF_User
 */
function get_old_user() {
	$cookie = '';
	if(function_exists('user_switching_get_olduser_cookie')){
		$cookie = user_switching_get_olduser_cookie();
	}

	if ( ! empty( $cookie ) ) {
		$old_user_id = false;
		if(function_exists('wp_validate_auth_cookie')){
			$old_user_id = wp_validate_auth_cookie( $cookie, 'logged_in' );
		}

		if ( $old_user_id ) {
			return get_userdata( $old_user_id );
		}
	}
	return false;
}

function is_old_admin(){
	$flag_old_user = false;
	if(function_exists('get_old_user')){
		$old_user = get_old_user();
		if($old_user && (in_array('administrator', $old_user->roles) || user_can($old_user->ID, 'view_extra_fields'))){
			$flag_old_user = true;

		}
	}

	return $flag_old_user;
}

/**
 * HBF_CheckoutFlowHelper
 */
add_action( 'woocommerce_review_order_before_shipping', 'checkout_shipping_form_packing_addition', 20 );
function checkout_shipping_form_packing_addition( ) {
	$domain = 'woocommerce';

	$chosen   = WC()->session->get('chosen_order_type');
	$shipping_amount   = WC()->session->get('shipping-amount') ?? '';
	$admin_fee   = WC()->session->get('admin-fee') ?? '';
	$pallet_fee   = WC()->session->get('pallet-fee') ?? '';
	$misc_fee   = WC()->session->get('misc-fee') ?? '';


	if(!is_user_logged_in()){
		return;
	}

	$user = wp_get_current_user();

	if(!in_array('administrator',$user->roles) && !is_old_admin() && !user_can($user->ID, 'view_extra_fields')){
		return;
	}

	echo '<tr class="order-type manual-order-row"><th>' . __('Order Type', $domain) . '</th><td>';

	// Add a custom checkbox field
	woocommerce_form_field( 'chosen_order_type', array(
		'type'      => 'select',
		'class'     => array( 'form-row-wide type' ),
		'options'   => array(
			'national' =>  'National',
			'international' => 'International',
		),
		'required'  => false,
		'custom_attributes' => array('data-type' => 'chosen_order_type')
	), $chosen );

	echo '</td></tr>';

	echo '<tr class="shipping-amount manual-order-row"><th>' . __('Shipping Cost ($)', $domain) . '</th><td>';


	woocommerce_form_field( 'shipping-amount', array(
		'type'      => 'number',
		'class'     => array( 'form-row-wide shipping-amount fee-field' ),
		'required'  => false,
		'custom_attributes' => array('data-type' => 'shipping-amount', 'step' => '0.1', 'min' => '0')
	), $shipping_amount );

	echo '</td></tr>';

	echo '<tr class="admin-fee manual-order-row"><th>' . __('Admin Fee ($)', $domain) . '</th><td>';


	woocommerce_form_field( 'admin-fee', array(
		'type'      => 'number',
		'class'     => array( 'form-row-wide admin-fee fee-field' ),
		'required'  => false,
		'custom_attributes' => array('data-type' => 'admin-fee', 'step' => '0.1', 'min' => '0')
	), $admin_fee );

	echo '</td></tr>';

	echo '<tr class="admin-fee manual-order-row"><th>' . __('Pallet Fee ($)', $domain) . '</th><td>';


	woocommerce_form_field( 'pallet-fee', array(
		'type'      => 'number',
		'class'     => array( 'form-row-wide pallet-fee fee-field' ),
		'required'  => false,
		'custom_attributes' => array('data-type' => 'pallet-fee', 'step' => '0.1', 'min' => '0')
	), $pallet_fee );

	echo '</td></tr>';

	echo '<tr class="admin-fee manual-order-row"><th>' . __('Misc Fee ($)', $domain) . '</th><td>';


	woocommerce_form_field( 'misc-fee', array(
		'type'      => 'number',
		'class'     => array( 'form-row-wide misc-fee fee-field' ),
		'required'  => false,
		'custom_attributes' => array('data-type' => 'misc-fee', 'step' => '0.1', 'min' => '0')
	), $misc_fee );

	echo '</td></tr>';
}

// jQuery - Ajax script
add_action( 'wp_footer', 'checkout_order_type_script' );
function checkout_order_type_script() {
    // Only checkout page
    $user = wp_get_current_user();
    if ( is_checkout() && ! is_wc_endpoint_url() && (in_array('administrator', $user->roles) || is_old_admin() || user_can($user->ID, 'view_extra_fields'))) :
    ?>
    <script type="text/javascript">
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
                url: wc_checkout_params.ajax_url,
                data: {
                    'action': 'update_custom_fees',
                    'fee_type': feeType,
                    'fee_value': feeValue,
                    'security': php_vars.update_session_nonce
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
                url: wc_checkout_params.ajax_url,
                data: {
                    'action': 'woo_get_ajax_data',
                    'type': type,
                    'val': val,
                    'security': php_vars.update_session_nonce // Use the new nonce here
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
    </script>
    <?php
    endif;
}

// Php Ajax (Receiving request and saving to WC session)
add_action( 'wp_ajax_woo_get_ajax_data', 'woo_get_ajax_data' );
add_action( 'wp_ajax_nopriv_woo_get_ajax_data', 'woo_get_ajax_data' );
function woo_get_ajax_data() {
    // Check the nonce named 'update_session_nonce' that we created and passed in the localized script
    check_ajax_referer('update_session_nonce', 'security');

    if ( isset($_POST['type']) && isset($_POST['val']) ) {
        $type = sanitize_key( $_POST['type'] );
        $val = sanitize_text_field( $_POST['val'] ); // Sanitize the value

        // Validate the value if needed, for example, ensure it's a number if it's supposed to be
        if ( 'shipping-amount' === $type || 'admin-fee' === $type || 'pallet-fee' === $type || 'misc-fee' === $type ) {
            $val = floatval( $val );
        }

        WC()->session->set($type, $val);

        // Determine if we need to update the checkout
        $need_update = true; // You can add conditions here to decide when to update the checkout

        wp_send_json_success( array('value' => $val, 'need_update' => $need_update) );
    } else {
        wp_send_json_error('Invalid data received.');
    }

    wp_die(); 
}

// Add a custom dynamic packaging fee
add_action( 'woocommerce_cart_calculate_fees', 'add_packaging_fee', 20, 1 );
function add_packaging_fee( $cart ) {
	if ( is_admin() && ! defined( 'DOING_AJAX' ) )
		return;

	global $woocommerce;

	$order_type = WC()->session->get( 'chosen_order_type' );
	$shipping = floatval(WC()->session->get( 'shipping-amount' ));
	$admin = floatval(WC()->session->get( 'admin-fee' ));
	$pallet = floatval(WC()->session->get( 'pallet-fee' ));
	$misc = floatval(WC()->session->get( 'misc-fee' ));

	if($order_type === 'national'){

		// var_dump($cart->fees_api);
		return;

	}

	if(!empty($shipping)){
		$cart->add_fee( 'Shipping', $shipping );
	}

	if(!empty($admin)){
		$cart->add_fee( 'Admin Fee', $admin );
	}

	if(!empty($pallet)){
		$cart->add_fee( 'Pallet Fee', $pallet );
	}

	if(!empty($misc)){
		$cart->add_fee( 'Misc Fee', $misc );
	}

	if($order_type == 'international'){
		$fees = $shipping + $misc + $admin + $pallet;
		$cart->add_fee( 'Credit card convenience fee (3%)', ($cart->cart_contents_total + $fees) * 0.03);
	}

	return;
}

add_filter( 'woocommerce_package_rates', 'define_default_shipping_method', 100, 2 );
function define_default_shipping_method( $rates, $package ) {

	$order_type = WC()->session->get( 'chosen_order_type' );
	$shipping = floatval(WC()->session->get( 'shipping-amount' ));
	//  exit($order_type);



	if($order_type === 'international'){
		foreach( $rates as $rate_id => $rate_val ) {
			if($rate_id == 'free_shipping:10'){
				continue;
			}
			unset( $rates[ $rate_id ] );
		}
	}

	return $rates;

}

// Function to skip cart page based on custom capability
/*function hfood_skip_cart_redirect_checkout() {
	if ( is_user_logged_in() && is_page( 'cart' ) && current_user_can( 'skip_cart_page' ) ) {
		global $post;
		$post_slug = $post->post_name;

		if ( $post_slug == 'manual-orders' ) {
			wp_redirect( wc_get_checkout_url() );
			exit;
		}
	}
}
add_action( 'template_redirect', 'hfood_skip_cart_redirect_checkout' );*/


// Quick fix for simple pricing after removing prices in CSS for subscriptions

add_filter( 'body_class', function ( $classes ) {
	if ( ! is_admin() && is_product() ) {
		global $post;
		$product = wc_get_product( $post->ID );
		$cssclass = 'hide_pricing_'.$product->get_type();
		return array_merge( $classes, array( $cssclass ) );
	}
	else{
		return $classes;
	}
});

// Remove subscription pricing for guests
add_action( 'woocommerce_before_add_to_cart_button', 'hide_subscription_pricing_for_guests', 10 );

function hide_subscription_pricing_for_guests() {
	if ( ! is_user_logged_in() ) {
		echo '<style>.wcsatt-options-product, .wcsatt-options-wrapper { display: none; }</style>';
		echo '<p style="padding: 4rem 0; font-size: 22px;"><a href="' . esc_url( wc_get_page_permalink( 'myaccount' ) ) . '" style="font-weight: bold; color: red;">Register or Login</a> to Subscribe and Save.</p>';

	}
}

add_filter('woocommerce_get_price_html', 'hide_subscription_price_shop_page', 10, 2);
function hide_subscription_price_shop_page($price, $product) {
	if (!is_user_logged_in()) {
		if (is_product()) {
			return $price; // Show regular price on product page for guests
		} else {
			return preg_replace('/— or (.*) \/ month/', '', $price); // Hide subscription price on other pages for guests
		}
	}
	return $price; // Show price for logged-in users and everywhere else
}

add_action('wp_footer', 'hide_subscription_price_for_guests_js');

function hide_subscription_price_for_guests_js() {
	if (!is_user_logged_in()) {
		?>
		<script type="text/javascript">
            jQuery(document).ready(function($) {
                $('.price').each(function() {
                    var text = $(this).text();
                    var newText = text.split('—')[0];
                    $(this).text(newText);
                });
            });
		</script>
		<?php
	}
}

//Adding the cart fragment after billing address
add_action('woocommerce_checkout_after_customer_details', function(){
	if(!is_checkout()){
		return;
	}
	?>
	<br>
	<style>
        .cart_item .product-name{
            width: 50%;
        }
        .cart_item .product-name a.remove{
            float: right;
        }
        .product-name .quantity{
            display: inline-block !important;
        }
	</style>
	<script>
        jQuery(document).ready(function($) {
			// Listen for the update_checkout event
			$('body').on('update_checkout', function() {
				// Temporarily disable the default WooCommerce scroll-to behavior
				var defaultScrollTo = $.fn.scrollTo;
				$.fn.scrollTo = function(target, duration, options) {
					// Check if the target is part of the checkout form
					if ($(target).closest('form.checkout').length) {
						// If so, prevent the default scrollTo behavior
						return;
					} else {
						// Otherwise, use the default scrollTo behavior
						return defaultScrollTo.apply(this, arguments);
					}
				};

				// After a short delay, restore the default scrollTo function
				setTimeout(function() {
					$.fn.scrollTo = defaultScrollTo;
				}, 1000); // Delay in milliseconds
			});
		});
	</script>
	<?php
	//echo do_shortcode('[woocommerce_cart]');
});


add_filter( 'show_admin_bar', 'hf_hide_admin_bar');
function hf_hide_admin_bar(){
	$user = wp_get_current_user();

	if(function_exists('get_old_user')){
		$old_user = get_old_user();
		if($old_user && (in_array('administrator', $old_user->roles) || user_can($old_user->ID, 'view_extra_fields'))){
			return true;

		}
	}

	if(in_array('administrator', $user->roles) || user_can($user->ID,'view_extra_fields') ){
		return true;
	}
	return false;
}

// Thank You Page
add_action( 'woocommerce_thankyou', function( $order_id ) {
    if ( is_old_admin() ) {
        $old_user = get_old_user();
        // Generate the invoice print URL
        $printUrl = generate_invoice_print_url($order_id);
		
        // Get the order object
        $order = wc_get_order($order_id);

        // Check if the order status is 'On Hold' and manually display the 'View Invoice' link if necessary
        if ($order && $order->has_status('on-hold')) {
            echo '<a href="' . esc_url($printUrl) . '" class="wc_pip_view_invoice" style="display: none;">View Invoice</a>';
        }
        ?>
        <div class="modal-sw">
            <p style="text-align: center"><strong>Order is complete.</strong></p>
            <p>To print the invoice, click the "print" option below. To switch back to <?php echo $old_user->first_name . ' ' . $old_user->last_name; ?> and continue taking orders, click "done."</p>
            <div style="text-align: center; margin-top: 20px;">
                <button id="printInvoice" style="margin-right: 10px;" data-print-url="<?php echo $printUrl; ?>">Print</button>
                <button id="completeProcess">Done</button>
            </div>
        </div>
        <script>
			jQuery(function($) {
				$('#printInvoice').on('click', function() {
					// Find the 'View Invoice' button URL
					var invoiceUrl = $('.wc_pip_view_invoice').attr('href');
					if (invoiceUrl) {
						// Open the invoice URL in a new tab
						window.open(invoiceUrl, '_blank');
					}
				});

				$('#completeProcess').on('click', function() {
					// Disable the button after the first click
        			$(this).prop('disabled', true).text('Processing...');
					
					var href = $('#user_switching_switch_on a').attr('href');
					var two_href = href.split('redirect_to=');
					if (two_href[0]) {
						window.location = two_href[0] + 'redirect_to=' + '<?php echo site_url() . '/manual-orders' ?>';
					}
				});
			});
		</script>
        <style>
            .modal-sw {
                position: fixed;
                top: 40%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: rgba(255, 255, 255, 0.9); /* Added transparency */
                z-index: 90000;
                padding: 30px;
                border: 1px solid;
                box-shadow: 0px 0px 10px rgba(0,0,0,0.5);
            }
            #printInvoice, #completeProcess {
                padding: 10px 20px;
                font-size: 16px;
                cursor: pointer;
            }
        </style>
        <?php
    }
});

// Print Invoices
function generate_invoice_print_url($order_id) {
    $order_id = intval($order_id);

    // Generate the nonce for the print action
    $print_nonce = wp_create_nonce('print-invoice');

    // Construct the print URL
    return home_url("/checkout/order-received/{$order_id}/?wc_pip_action=print&wc_pip_document=invoice&order_id={$order_id}&_wpnonce={$print_nonce}");
}