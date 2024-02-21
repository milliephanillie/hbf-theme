<?php
namespace Harrison\Includes;

class HBF_OrderStatusChanged {
    public static function init_hooks() {
        add_action('woocommerce_order_status_changed', [$this, 'check_order_status_and_send_tracking_info'], 10, 4);

        add_action('send_tracking_info_to_customer', [$this, 'send_tracking_info_on_complete']);
    }

    public static function check_order_status_and_send_tracking_info($order_id, $from_status, $to_status, $order) {
        if ('completed' === $to_status) {
            wp_schedule_single_event(time() + 60, 'send_tracking_info_to_customer', array($order_id));
        }
    }

    public function static send_tracking_info_on_complete($order_id)
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

}