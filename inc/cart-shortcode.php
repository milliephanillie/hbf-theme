<?php
add_shortcode( 'custom_cart_total', 'custom_cart_total_shortcode' );
function custom_cart_total_shortcode() {
	ob_start();

	?>
	<table class="custom-cart-total">
		<?php
		if ( class_exists( 'WooCommerce' ) && WC()->cart ) {
			echo '<tr class="cart-subtotal">';
			echo '<td class="label">' . __( 'Subtotal:', 'woocommerce' ) . '</td>';
			echo '<td class="value" id="custom-cart-subtotal-value">' . WC()->cart->get_cart_subtotal() . '</td>';
			echo '</tr>';

			// Display shipping options if an address is passed
			if ( WC()->customer->get_shipping_country() ) {
				$packages = WC()->cart->get_shipping_packages();
				$shipping_methods = WC()->shipping->calculate_shipping_for_package($packages[0]);
				if ( isset($shipping_methods['rates']) && !empty($shipping_methods['rates']) ) {
					echo '<tr class="cart-shipping">';
					echo '<td class="label">' . __('Shipping:', 'woocommerce') . '</td>';
					echo '<td class="value">';
					foreach ( $shipping_methods['rates'] as $rate_id => $rate ) {
						echo esc_html( $rate->label ) . ': ' . wc_price( $rate->cost ) . ' <input type="radio" name="shipping_method" value="' . esc_attr( $rate_id ) . '"><br>';
					}
					echo '</td>';
					echo '</tr>';
				} else {
					echo '<tr class="cart-shipping">';
					echo '<td class="label">' . __('Shipping:', 'woocommerce') . '</td>';
					echo '<td class="value">' . WC()->cart->get_cart_shipping_total() . '</td>';
					echo '</tr>';
				}
			}

			// Display tax total
			if ( wc_tax_enabled() && ! WC()->cart->display_prices_including_tax() ) {
				$tax_totals = WC()->cart->get_tax_totals();
				foreach ( $tax_totals as $code => $tax ) {
					echo '<tr class="tax-rate tax-rate-' . sanitize_title( $code ) . '">';
					echo '<td class="label">' . esc_html( $tax->label ) . ':</td>';
					echo '<td class="value">' . wp_kses_post( $tax->formatted_amount ) . '</td>';
					echo '</tr>';
				}
			}

			// Display fees
			$fees = WC()->cart->get_fees();
			foreach ( $fees as $fee ) {
				echo '<tr class="cart-fee">';
				echo '<td class="label">' . esc_html( $fee->name ) . ':</td>';
				echo '<td class="value">' . wc_price( $fee->amount ) . '</td>';
				echo '</tr>';
			}

			// Display total
			echo '<tr class="cart-total">';
			echo '<td class="label">' . __('Total:', 'woocommerce') . '</td>';
			echo '<td class="value" id="custom-cart-total-value">' . WC()->cart->get_total() . '</td>';
			echo '</tr>';

		} else {
			echo '<tr><td colspan="2">' . __('Cart is empty.', 'woocommerce') . '</td></tr>';
		}
		?>
	</table>
	<?php
	return ob_get_clean();
}

