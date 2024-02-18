<?php
/**
 * todo the refunded variable is not set
 */
namespace Harrison\AJAX;

use Harrison\Utils\HBF_PDFGenerator;

class HBF_GeneratePrintPageContent {
    public function __construct() {
        add_action('wp_ajax_ajax_generate_print_page_content', [$this, 'ajax_generate_print_page_content']);
        add_action('wp_ajax_nopriv_ajax_generate_print_page_content', [$this, 'ajax_generate_print_page_content']);
    }

    private function generate_print_page_content($order_id, $user_id) {
        $order = wc_get_order($order_id);
        $current_user = wp_get_current_user();

        // Retrieve credited items
        $credited_items = get_post_meta($order_id, 'credited_items', true);
        error_log('PDF Generation: Credited items for order #' . $order_id . ': ' . print_r($credited_items, true));

        if (!$credited_items) {
            $credited_items = [];
        }

        // Retrieve and validate the credit issued
        $credit_issued = get_post_meta($order_id, '_applied_credit', true);
        $credit_issued = is_numeric($credit_issued) ? floatval($credit_issued) : 0.0;

        // Calculate the adjusted total
        $adjusted_total = $order->get_total() - $credit_issued;

        // Calculate previous order total
        $previousOrderTotal = $adjusted_total + $credit_issued;

        // Log credit issued
        error_log('Credit Issued for Order #' . $order_id . ': ' . $credit_issued);

        // Current date for "Date Credit Issued"
        $current_date = date('M j, Y');

        // CSS styles for the tables
        $tableStyle = 'width: 100%; border-collapse: collapse;';
        $thStyle = 'padding: 5px; background-color: #004c7e; color: white; text-align: right; font-size: 14px;';
        $tdStyle = 'padding: 5px; border: 1px solid #ddd; text-align: right; font-size: 14px;';

        // Start building the HTML content
        $output = "<div class='print-page-content'>";

        // Header with logo and invoice information
        $output .= "<div style='display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;'>";
        $output .= "<div style='flex-basis: 50%;'>";
        $output .= "<img src='" . site_url() . "/wp-content/uploads/2023/07/harrisons-bird-foods-logo-h.png' alt='Company Logo' style='max-width: 200px;'/>";
        $output .= "</div>";
        $output .= "<div style='flex-basis: 50%; text-align: right;'>";
        $output .= "<h2 style='margin: 0; color: #004c7e;'>Credit Invoice</h2>";
        $output .= "<p>Order No: " . esc_html($order->get_order_number()) . "</p>";
        $output .= "<p>Date Credit Issued: " . esc_html($current_date) . "</p>"; // Current date
        $output .= "<p>Order Date: " . esc_html($order->get_date_created()->format('M j, Y')) . "</p>"; // Order date
        $output .= "<p>Issued by: " . esc_html($current_user->display_name) . "</p>";
        $output .= "</div>";
        $output .= "</div>";

        // Company Information
        $output .= "<div style='margin-bottom: 20px;'>";
        $output .= "<h3 style='margin-bottom: 5px;'>" . esc_html('Harrison\'s Bird Foods') . "</h3>";
        $output .= "<p>7108 Crossroads Blvd. Suite 325<br>Brentwood, TN 37027<br>615-221-9919 Office<br>800-346-0269 Toll Free<br>orders@harrisonsbirdfoods.com</p>";
        $output .= "</div>";

        // Customer Information
        $billing_address = $order->get_formatted_billing_address();
        $output .= "<div style='margin-bottom: 20px;'>";
        $output .= "<h3>Bill to:</h3>";
        $output .= "<p>" . $billing_address . "</p>";
        $output .= "</div>";

        // Order items table for non-credited items
        $output .= "<h2>Order Items</h2>";
        $output .= "<table style='" . $tableStyle . "'>";
        $output .= "<tr><th style='" . $thStyle . "'>Product</th><th style='" . $thStyle . "'>Item Qty</th><th style='" . $thStyle . "'>Item Cost</th><th style='" . $thStyle . "'>Total Tax</th><th style='" . $thStyle . "'>Line Total</th></tr>";

        foreach ($order->get_items() as $item_id => $item) {
            $product_id = $item->get_product_id();
            $variation_id = $item->get_variation_id();
            $item_key = $variation_id ? $product_id . '_' . $variation_id : (string) $product_id;

            // Retrieve the credited quantity for this item
            $credited_qty = isset($credited_items[$item_key]) ? $credited_items[$item_key] : 0;
            $qty_to_display = $item->get_quantity() - $credited_qty;


            // Log the details of each item being processed
            error_log("PDF Generation: Processing item - ID: $item_key, Qty: $qty_to_display, Credited Qty: $credited_qty");

            if ($qty_to_display > 0) {
                error_log("PDF Generation: Processing item - ID: $item_key, Original Qty: " . $item->get_quantity() . ", Credited Qty: $credited_qty, Display Qty: $qty_to_display");
                $output .= "<tr><td style='" . $tdStyle . "'>" . esc_html($item->get_name()) . "</td><td style='" . $tdStyle . "'>" . esc_html($qty_to_display) . "</td><td style='" . $tdStyle . "'>" . wc_price($item->get_subtotal()) . "</td><td style='" . $tdStyle . "'>" . wc_price($item->get_subtotal_tax()) . "</td><td style='" . $tdStyle . "'>" . wc_price($item->get_total() + $item->get_total_tax()) . "</td></tr>";
            }
        }
        $output .= "</table>";

        // Initialize credit_issued to zero
        $credit_issued = 0.0;

        // Credited Items table
        if ($credited_items && is_array($credited_items) && count($credited_items) > 0) {
            error_log('PDF Generation: Credited Items - ' . print_r($credited_items, true));
            $output .= "<h2>Credited Items</h2>";
            $output .= "<table style='" . $tableStyle . "'>";
            $output .= "<tr><th style='" . $thStyle . "'>Product</th><th style='" . $thStyle . "'>Credited Qty</th><th style='" . $thStyle . "'>Credited Cost</th><th style='" . $thStyle . "'>Credited Tax</th><th style='" . $thStyle . "'>Credited Total</th></tr>";
            foreach ($credited_items as $item_key => $qty_credited) {
                list($product_id, $variation_id) = explode('_', $item_key . '_');
                $item = null;

                foreach ($order->get_items() as $order_item) {
                    if ($order_item->get_product_id() == $product_id && ($variation_id == 0 || $order_item->get_variation_id() == $variation_id)) {
                        $item = $order_item;
                        break; // Item found
                    }
                }

                if ($item) {
                    $output .= "<tr><td style='" . $tdStyle . "'>" . esc_html($item->get_name()) . "</td><td style='" . $tdStyle . "'>" . esc_html($qty_credited) . "</td><td style='" . $tdStyle . "'>" . wc_price($qty_credited * $item->get_subtotal() / $item->get_quantity()) . "</td><td style='" . $tdStyle . "'>" . wc_price($qty_credited * $item->get_subtotal_tax() / $item->get_quantity()) . "</td><td style='" . $tdStyle . "'>" . wc_price($qty_credited * ($item->get_total() + $item->get_total_tax()) / $item->get_quantity()) . "</td></tr>";
                } else {
                    // Log error or handle cases where the item is not found
                    error_log("PDF Generation: No item found for Product ID: $product_id, Variation ID: $variation_id");
                }
                // Calculate credit issued for each item
                $credit_issued += $qty_credited * ($item->get_total() + $item->get_total_tax()) / $item->get_quantity();
            }
            $output .= "</table>";
        }

        // Calculate previous order total
        $previousOrderTotal = $adjusted_total + $credit_issued;

        // Get additional order details
        $items_subtotal = $order->get_subtotal();
        $shipping_total = $order->get_shipping_total();
        $tax_total = $order->get_total_tax();
        $order_total = $order->get_total();
        $paid = $order->get_total() - $order->get_total_refunded();
        $net_payment = $paid - $refunded;

        // Order Totals Table
        $output .= "<table style='width: 50%; float: right; border-collapse: collapse; margin-top: 20px;'>";
        $output .= "<tr><th style='" . $thStyle . "'>Description</th><th style='" . $thStyle . "'>Amount</th></tr>";
        $output .= "<tr><td style='" . $tdStyle . "'>Items Subtotal:</td><td style='" . $tdStyle . "'>" . wc_price($items_subtotal) . "</td></tr>";
        $output .= "<tr><td style='" . $tdStyle . "'>Shipping:</td><td style='" . $tdStyle . "'>" . wc_price($shipping_total) . "</td></tr>";
        $output .= "<tr><td style='" . $tdStyle . "'>Tax:</td><td style='" . $tdStyle . "'>" . wc_price($tax_total) . "</td></tr>";
        $output .= "<tr><td style='" . $tdStyle . "'>Previous Order Total:</td><td style='" . $tdStyle . "'>" . wc_price($previousOrderTotal) . "</td></tr>";
        $output .= "<tr><td style='" . $tdStyle . "'>Adjusted Order Total:</td><td style='" . $tdStyle . "'>" . wc_price($adjusted_total) . "</td></tr>";
        $output .= "</table>";

        // Credit Issued and Adjusted Order Total
        $credit_balance = get_user_meta($user_id, 'credit_balance', true);
        $output .= "<h3>Credit Issued: " . wc_price($credit_issued) . "</h3>";
        $output .= "<h3>Credit Balance: " . wc_price($credit_balance) . "</h3>";

        // Footer
        $output .= "<div style='text-align: center; margin-top: 30px;'>";
        $output .= "<p>Thank you for shopping with us.</p>";
        $output .= "</div>";

        $output .= "</div>";

        $output .= "</div>"; // Close the print-page-content div

        error_log('PDF Generation: Final output for order #' . $order_id . ': ' . $output);
        return $output;
    }

    public function ajax_generate_print_page_content() {
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

        $htmlContent = $this->generate_print_page_content($order_id, $user_id);
        $pdfUrl = HBF_PDFGenerator::generate_pdf($htmlContent, "order-details-{$order_id}.pdf", $order_id);

        echo json_encode(array('pdfUrl' => $pdfUrl));
        wp_die();
    }
}