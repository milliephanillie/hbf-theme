<?php


add_shortcode('load_user', function(){
	global $current_user;
	if (!in_array('administrator', $current_user->roles) && !user_can($current_user->ID, 'view_extra_fields') && !is_old_admin()) {
        return;
    }
	ob_start();
	?>

	<!-- HTML for the User Search and Load Previous Order functionality -->


	<script type="text/javascript">

	</script>

	<?php
	return ob_get_clean();

});

// User Switching
add_action('wp_ajax_switch_to_selected_user', 'switch_to_selected_user');
function switch_to_selected_user() {

	if (!isset($_POST['user_id'])) {
		echo json_encode(array('success' => false, 'message' => 'No user ID provided.'));
		wp_die();
	}

	$user_id = intval($_POST['user_id']);
	$current_user_id = intval($_POST['current_user_id']);
    $current_user = get_user_by('ID', $current_user_id);

  	// Restrict access for switching to higher user level
	if ($current_user && !in_array('administrator', $current_user->roles)) {
    $user = get_user_by('ID', $user_id);
    if (!in_array('subscriber', $user->roles) &&
        !in_array('distributor', $user->roles) &&
        !in_array('customer', $user->roles) &&
        !in_array('international', $user->roles) &&
        !in_array('export', $user->roles)) {
        echo json_encode(array('success' => false, 'message' => 'Sorry, you do not have the required permissions to switch to a user with a higher access level. Please contact your administrator if you believe this is in error.'));
        wp_die();
    }
}

	// Use the User Switching plugin's function to switch user
	if (function_exists('switch_to_user')) {
		if (switch_to_user($user_id, true)) {
			// Update the shipping address in the cart session
			$shipping_address = array(
				'first_name' => get_user_meta($user_id, 'shipping_first_name', true),
				'last_name'  => get_user_meta($user_id, 'shipping_last_name', true),
				'company'    => get_user_meta($user_id, 'shipping_company', true),
				'address_1'  => get_user_meta($user_id, 'shipping_address_1', true),
				'address_2'  => get_user_meta($user_id, 'shipping_address_2', true),
				'city'       => get_user_meta($user_id, 'shipping_city', true),
				'state'      => get_user_meta($user_id, 'shipping_state', true),
				'postcode'   => get_user_meta($user_id, 'shipping_postcode', true),
				'country'    => get_user_meta($user_id, 'shipping_country', true),
			);
			WC()->customer->set_shipping_location(
				$shipping_address['country'],
				$shipping_address['state'],
				$shipping_address['postcode'],
				$shipping_address['city']
			);

			WC()->cart->empty_cart();

			// Force WooCommerce to recalculate shipping
			WC()->shipping->reset_shipping();
			WC()->cart->calculate_shipping();

			echo json_encode(array('success' => true, 'message' => 'Switched user successfully.'));
		} else {
			echo json_encode(array('success' => false, 'message' => 'Failed to switch user.'));
		}
	} else {
		echo json_encode(array('success' => false, 'message' => 'User Switching function not available.'));
	}

	wp_die();
}



