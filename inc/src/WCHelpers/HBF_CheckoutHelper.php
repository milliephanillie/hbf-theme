<?php
namespace Harrison\WCHelpers;

use Harrison\Includes\HBF_User;

class HBF_CheckoutHelper {
    const ASSET_VERSION = '1.0.0';

    public function __construct() {
        add_action('woocommerce_before_calculate_totals', [$this, 'set_custom_total']);

        add_action('woocommerce_before_cart', [$this, 'recalculate_shipping_when_cart_updated']);

        add_action('woocommerce_before_checkout_form', [$this, 'check_for_pobox_in_shipping'], 10, 0);
        add_action('woocommerce_before_checkout_form', [$this, 'custom_back_button_for_manual_orders'], 10);

        add_action('woocommerce_cart_calculate_fees', [$this, 'custom_fee_for_sample_kit'], 20);

        add_action('woocommerce_checkout_order_processed', [$this, 'custom_payment_checkout_handler'], 10, 3);

        add_action('woocommerce_checkout_update_order_meta', [$this, 'copy_billing_to_shipping_if_empty']);
        add_action('woocommerce_checkout_update_order_meta', [$this, 'save_custom_order_notes']);
        add_action('woocommerce_checkout_update_order_meta', [$this, 'save_shipping_notes']);

        add_filter('woocommerce_is_purchasable', [$this, 'limit_product_purchase_once_per_customer'], 10, 2);
        add_filter('woocommerce_variation_is_purchasable', [$this, 'limit_product_purchase_once_per_customer'], 10, 2);

        add_filter('woocommerce_order_is_editable', [$this, 'make_net15_orders_editable'], 9999, 2);

        add_action('woocommerce_order_status_changed', [$this, 'check_order_status_and_send_tracking_info'], 10, 4);
        //Adding here as this hook is generated in check_order_status_and_send_tracking_info
        add_action('send_tracking_info_to_customer', [$this, 'send_tracking_info_on_complete']);

        add_filter('woocommerce_package_rates', [$this, 'custom_shipping_cost'], 10, 2);
        add_filter('woocommerce_package_rates', [$this, 'filter_shipping_methods_based_on_user_role'], 10, 2);
        add_filter('woocommerce_package_rates', [$this, 'filter_shipping_methods'], 10, 2);
        add_filter('woocommerce_package_rates', [$this, 'disable_shipping_methods_for_pobox'], 10, 2);

        add_action('woocommerce_payment_complete', [$this, 'handle_partial_payment_complete']);
    }

    public function limit_product_purchase_once_per_customer($purchasable, $product) {
        $limited_product_id = 1580;

        if ($product->get_id() == $limited_product_id) {
            // Check if the current user is logged in
            if (is_user_logged_in()) {
                // Get the current user's ID
                $current_user_id = get_current_user_id();

                // Check if the user has already bought the product
                if (wc_customer_bought_product('', $current_user_id, $limited_product_id)) {
                    // User has already bought the product, so it's not purchasable
                    $purchasable = false;
                }
        } else {
            // User is not logged in, so the product can't be purchased (as we can't track their purchase history)
            $purchasable = false;
        }
        }

        return $purchasable;
    }

    function send_tracking_info_on_complete($order_id)
    {
        $order = wc_get_order($order_id);
        $order_date = $order->get_date_created();
        $cutoff_date = new DateTime('2024-01-28');

        if ($order_date < $cutoff_date) {
            return;
        }

        $notes = wc_get_order_notes(array('order_id' => $order_id));
        $tracking_number = null;
        foreach ($notes as $note) {
            if (strpos($note->content, 'UPS Tracking Number:') !== false) {
                preg_match('/UPS Tracking Number: (\w+)/', $note->content, $matches);
                if (isset($matches[1])) {
                    $tracking_number = $matches[1];
                    break;
                }
            }
        }

        if (!$tracking_number) {
            return;
        }

        $store_address = WC()->countries->get_base_address();
        $store_city = WC()->countries->get_base_city();
        $store_postcode = WC()->countries->get_base_postcode();
        $store_country = WC()->countries->get_base_country();
        $full_store_address = $store_address . ', ' . $store_city . ', ' . $store_postcode . ', ' . $store_country;

        $shipping_address = $order->get_formatted_shipping_address();

        $order_items = '';
        foreach ($order->get_items() as $item_id => $item) {
            $product_name = $item->get_name();
            $quantity = $item->get_quantity();
            $order_items .= $product_name . ' x ' . $quantity . '<br>';
        }

        $style = '
        body { background-color: #f7f7f7; color: #3c3c3c; }
        .email-container { background-color: #ffffff; margin: 0 auto; padding: 20px; width: 600px; }
        .email-header { color: #ffffff; padding: 10px; text-align: center; }
        .email-body { padding: 20px; }
        .email-footer { font-size: 12px; text-align: center; padding: 10px; }
        a { color: #004c7e; text-decoration: none; }
    ';

        $email_content = '
        <html>
        <head>
            <style>' . $style . '</style>
        </head>
        <body>
            <div class="email-container">
                <div class="email-header">
                    <img src="https://www.harrisonsbirdfoods.com/wp-content/uploads/2024/01/HBD-logo-main.png" alt="Header Image" style="background-color:transparent;">
                </div>
                <div class="email-body">
                    <p>Hello, your order has been shipped. Here are the details:</p>
                    <p><strong>Harrison\'s Bird Foods</strong><br>' . $full_store_address . '</p>
                    <p><strong>Shipping Address:</strong><br>' . $shipping_address . '</p>
                    <p><strong>Order Details:</strong><br>' . $order_items . '</p>
                    <p><strong>Tracking Number:</strong> ' . $tracking_number . '<br>
                    <a href="https://www.ups.com/track?tracknum=' . $tracking_number . '">Track your shipment</a></p>
                </div>
                <div class="email-footer">
                    ' . get_bloginfo('name') . ' &mdash; Copyright © 2024
                </div>
            </div>
        </body>
        </html>
    ';

        $headers = array('Content-Type: text/html; charset=UTF-8');
        wp_mail($order->get_billing_email(), 'Your Order Has Been Shipped', $email_content, $headers);
    }

    public function check_order_status_and_send_tracking_info($order_id, $from_status, $to_status, $order) {
        if ('completed' === $to_status) {
            wp_schedule_single_event(time() + 60, 'send_tracking_info_to_customer', array($order_id));
        }
    }

    private function is_pobox($address)
    {
        $address = strtolower(str_replace(array('.', ',', ' '), '', $address));
        return (strpos($address, 'pobox') !== false || preg_match('/box.*po/i', $address) || preg_match('/po.*box/i', $address));
    }

    public function disable_shipping_methods_for_pobox($rates, $package) {
        if (HBF_User::is_hbf_admin()) {
            return $rates;
        }

        $shipping_address = $package['destination']['address'];
        if ($this->is_pobox($shipping_address)) {
            return array();
        }
        return $rates;
    }

    public function check_for_pobox_in_shipping()
    {
        if (!HBF_User::is_hbf_admin()) {
            wp_enqueue_script('check-for-pobox-in-shipping', HBF_PLUGIN_URL . 'assets/js/check_for_pobox_in_shipping.js', null, self::ASSET_VERSION, true);
        }
    }

    public function user_can_bypass_pobox_check()
    {
        $user = wp_get_current_user();
        return in_array('administrator', $user->roles) || is_old_admin() || user_can($user->ID, 'view_extra_fields');
    }

    public function filter_shipping_methods($rates, $package) {
        $can_view_walk_in = HBF_User::can_view_walkin();

        if (!$can_view_walk_in) {
            foreach ($rates as $rate_id => $rate) {
                if ('free_shipping' === $rate->method_id) {
                    unset($rates[$rate_id]);
                }
            }
        }

        return $rates;
    }

    public function make_net15_orders_editable($allow_edit, $order) {
        if ($order->get_status() === 'net15-pending') {
            $allow_edit = true; // Set to true if order status is 'net15-pending'
        }
        return $allow_edit; // Return the modified or original state of editability
    }

    public function recalculate_shipping_when_cart_updated() {
        \WC()->cart->calculate_totals();
    }

    public function custom_fee_for_sample_kit($cart) {
        if (is_admin() && !defined('DOING_AJAX')) return;

        $sample_kit_id = 1580; // Sample Kit Product ID
        $sample_kit_in_cart = false;

        foreach ($cart->get_cart_contents() as $key => $item) {
            if ($item['product_id'] == $sample_kit_id) {
                $sample_kit_in_cart = true;
                unset($cart->cart_contents[$key]['data']->needs_shipping);
                break;
            }
        }

        if ($sample_kit_in_cart) {
            $cart->add_fee('Sample Kit Shipping Addendum', 5);
        }
    }

    public function filter_shipping_methods_based_on_user_role($rates, $package) {
        $user = wp_get_current_user();

        $allowed_roles = array('administrator', 'shop_manager', 'shipping_manager');

        $is_allowed = array_intersect($allowed_roles, $user->roles) || is_old_admin() || user_can($user->ID, 'view_extra_fields');

        if (!$is_allowed) {
            foreach ($rates as $rate_id => $rate) {
                if (strpos($rate_id, 'usps') !== false) {
                    unset($rates[$rate_id]);
                }
            }
        }

        return $rates;
    }

    function checkout_country_restrict_js()
    {
        // Nonce for verification and security
        $nonce = wp_create_nonce('country_restrict');
        ?>
        <script type="text/javascript">
            jQuery(function ($) {
                // Function to clear the customer session
                function clearCustomerSession() {
                    $.ajax({
                        url: wc_checkout_params.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'clear_customer_session',
                            security: '<?php echo $nonce; ?>'
                        },
                        success: function (response) {
                            if (response === 'success') {
                                console.log('Session cleared.');
                            }
                        }
                    });
                }

                // Function to check the country and specific states and act accordingly
                function checkCountryAndStateRestrictions(country, state) {
                    // List of restricted states
                    var restrictedStates = ['HI', 'PR', 'AK']; // Hawaii, Puerto Rico, Alaska

                    // If country is not 'US' or if it is 'US' and state is in the restricted list, show the alert and redirect
                    if (country && (country !== 'US' || (country === 'US' && restrictedStates.includes(state)))) {
                        var location = country === 'US' ? 'State: ' + state : 'Country: ' + country;
                        var message = "Online ordering is currently not available for your location. If you would like to place an order with us, please call 1-800-346-0269 to speak with a customer service representative. We apologize for the inconvenience and appreciate your business.\n\nDetected Location: " + location;
                        alert(message);
                        clearCustomerSession();
                        window.location.href = "<?php echo home_url(); ?>"; // Redirect to home page
                    }
                }

                // Set up event listeners for changes to the country and state fields
                $('#billing_country, #shipping_country, #billing_state, #shipping_state').change(function () {
                    var selectedCountry = $('#billing_country').val() || $('#shipping_country').val();
                    var selectedState = $('#billing_state').val() || $('#shipping_state').val();
                    checkCountryAndStateRestrictions(selectedCountry, selectedState);
                });

                // Check immediately if the country and state are pre-selected (for returning customers)
                var currentCountry = $('#billing_country').val() || $('#shipping_country').val();
                var currentState = $('#billing_state').val() || $('#shipping_state').val();
                checkCountryAndStateRestrictions(currentCountry, currentState);
            });
        </script>
        <?php
    }

    public function custom_shipping_cost($rates, $package)
    {
        $weight_limit = 6;
        $addendum_percentage = 30;

        $package_weight = 0;
        foreach ($package['contents'] as $item_id => $values) {
            $product = $values['data'];
            $package_weight += $product->get_weight() * $values['quantity'];
        }

        if ($package_weight >= $weight_limit) {
            foreach ($rates as $rate_key => $rate) {
                if (strpos($rate->label, 'UPS') !== false) {
                    $old_cost = $rate->cost;

                    $new_cost = $old_cost + ($old_cost * ($addendum_percentage / 100));
                    $rates[$rate_key]->cost = $new_cost;
                }
            }
        }

        return $rates;
    }

    public function copy_billing_to_shipping_if_empty( $order_id ) {
        if ( ! $order_id ) {
            return;
        }

        $order = wc_get_order( $order_id );

        if ( empty( $order->get_shipping_address_1() ) ) {
            $billing_address_1 = $order->get_billing_address_1();
            $billing_address_2 = $order->get_billing_address_2();
            $billing_city = $order->get_billing_city();
            $billing_state = $order->get_billing_state();
            $billing_postcode = $order->get_billing_postcode();
            $billing_country = $order->get_billing_country();

            $order->set_shipping_address_1( $billing_address_1 );
            $order->set_shipping_address_2( $billing_address_2 );
            $order->set_shipping_city( $billing_city );
            $order->set_shipping_state( $billing_state );
            $order->set_shipping_postcode( $billing_postcode );
            $order->set_shipping_country( $billing_country );

            $order->save();
        }
    }

    public function set_custom_total($cart_object) {
        if (\WC()->session->get('partial_payment_order_id') && \WC()->session->get('partial_payment_amount')) {
            $payment_amount = \WC()->session->get('partial_payment_amount');
            foreach ($cart_object->get_cart() as $hash => $value) {
                $value['data']->set_price($payment_amount);
            }
        }
    }

    public function custom_back_button_for_manual_orders() {
        $user = wp_get_current_user();
        if ( in_array('administrator', $user->roles) || is_old_admin() || user_can($user->ID, 'view_extra_fields') ) {
            echo '<a href="/manual-orders" class="custom-back-button"><i class="fa fa-arrow-left"></i> BACK</a>';
        }
    }

    public function save_shipping_notes( $order_id ) {
        $shipping_notes = \WC()->session->get('shipping_notes');
        if (!empty($shipping_notes)) {
            update_post_meta($order_id, '_shipping_notes', $shipping_notes);
            \WC()->session->__unset('shipping_notes');
        }
    }

    public function custom_payment_checkout_handler($order_id, $posted_data, $order) {
        $original_order_id = isset($_GET['original_order_id']) ? $_GET['original_order_id'] : null;
        $payment_product_id = 4781;

        foreach ($order->get_items() as $item) {
            if ($item->get_product_id() == $payment_product_id) {
                if ($original_order_id) {
                    $original_order = wc_get_order($original_order_id);
                    if ($original_order) {
                        $order->update_status('cancelled', __('Payment applied to original order.', 'woocommerce'));
                    }
                    break;
                }
            }
        }
    }

    public function save_custom_order_notes( $order_id ) {
        if ( isset($_POST['custom_order_notes']) && !empty($_POST['custom_order_notes']) ) {
            $order = wc_get_order( $order_id );
            $order->add_order_note( sanitize_textarea_field($_POST['custom_order_notes']) );
        }
    }

    public function handle_partial_payment_complete($order_id) {
        $partial_order_id = \WC()->session->get('partial_payment_order_id');
        if ($partial_order_id && $partial_order_id == $order_id) {
            $order = wc_get_order($order_id);
            $payment_amount = \WC()->session->get('partial_payment_amount');

            \WC()->session->set('partial_payment_order_id', null);
            \WC()->session->set('partial_payment_amount', null);
        }
    }
}
