<?php
namespace Harrison\Shortcodes;

use Harrison\Shortcodes\HBF_Shortcodes;

class HBF_CustomCartTotalShortcode extends HBF_Shortcodes {
    protected function set_sc_settings() {
        $this->sc_settings = [
            'name' => 'custom_cart_total',
            'handle' => null,
            'permission_callback' => null
        ];
    }
    
    public function render_shortcode($atts, $content = NULL) {
		if ( class_exists( 'WooCommerce' ) && \WC()->cart ) {
			$output = '<table class="custom-cart-total">';
		
			// Subtotal
			$output .= '<tr class="cart-subtotal">';
			$output .= '<td class="label">' . __( 'Subtotal:', 'woocommerce' ) . '</td>';
			$output .= '<td class="value" id="custom-cart-subtotal-value">' . \WC()->cart->get_cart_subtotal() . '</td>';
			$output .= '</tr>';
		
			// Shipping
			if ( \WC()->customer->get_shipping_country() ) {
				$packages = \WC()->cart->get_shipping_packages();
				$shipping_methods = \WC()->shipping->calculate_shipping_for_package($packages[0]);
				if ( isset($shipping_methods['rates']) && !empty($shipping_methods['rates']) ) {
					$output .= '<tr class="cart-shipping">';
					$output .= '<td class="label">' . __('Shipping:', 'woocommerce') . '</td>';
					$output .= '<td class="value">';
					foreach ( $shipping_methods['rates'] as $rate_id => $rate ) {
						$output .= esc_html( $rate->label ) . ': ' . wc_price( $rate->cost ) . ' <input type="radio" name="shipping_method" value="' . esc_attr( $rate_id ) . '"><br>';
					}
					$output .= '</td>';
					$output .= '</tr>';
				} else {
					$output .= '<tr class="cart-shipping">';
					$output .= '<td class="label">' . __('Shipping:', 'woocommerce') . '</td>';
					$output .= '<td class="value">' . \WC()->cart->get_cart_shipping_total() . '</td>';
					$output .= '</tr>';
				}
			}
		
			// Taxes
			if ( wc_tax_enabled() && ! \WC()->cart->display_prices_including_tax() ) {
				$tax_totals = \WC()->cart->get_tax_totals();
				foreach ( $tax_totals as $code => $tax ) {
					$output .= '<tr class="tax-rate tax-rate-' . sanitize_title( $code ) . '">';
					$output .= '<td class="label">' . esc_html( $tax->label ) . ':</td>';
					$output .= '<td class="value">' . wp_kses_post( $tax->formatted_amount ) . '</td>';
					$output .= '</tr>';
				}
			}
		
			// Fees
			$fees = \WC()->cart->get_fees();
			foreach ( $fees as $fee ) {
				$output .= '<tr class="cart-fee">';
				$output .= '<td class="label">' . esc_html( $fee->name ) . ':</td>';
				$output .= '<td class="value">' . wc_price( $fee->amount ) . '</td>';
				$output .= '</tr>';
			}
		
			// Total
			$output .= '<tr class="cart-total">';
			$output .= '<td class="label">' . __('Total:', 'woocommerce') . '</td>';
			$output .= '<td class="value" id="custom-cart-total-value">' . \WC()->cart->get_total() . '</td>';
			$output .= '</tr>';
		
			$output .= '</table>';
		} else {
			$output = '<table class="custom-cart-total"><tr><td colspan="2">' . __('Cart is empty.', 'woocommerce') . '</td></tr></table>';
		}
		
		echo $output;
    }
}