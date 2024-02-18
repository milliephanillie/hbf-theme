<?php
namespace Harrison\AJAX;

use Harrison\Utils\HBF_PDFGenerator;
use Dompdf\Dompdf;

class HBF_GenerateRefundPDF {
    public function __construct()
    {
        add_action('wp_ajax_ajax_generate_refund_pdf', [$this, 'ajax_generate_refund_pdf']);
        add_action('wp_ajax_nopriv_ajax_generate_refund_pdf', [$this, 'ajax_generate_refund_pdf']);
    }

    //todo put html in template
    private function generate_refund_pdf_content($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return 'Order not found.';
        }

        // Retrieve the refund reason from the order meta
        $refund_reason = sanitize_textarea_field($_POST['reason']);
        $current_user = wp_get_current_user();
        $issuer_name = $current_user->user_firstname . ' ' . $current_user->user_lastname;

        // Initialize the output
        $output = "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: auto;'>";

        // Header with logo and invoice information
        $output .= "<div style='display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;'>";
        $output .= "<div style='flex-basis: 50%;'>";
        $output .= "<img src='" . site_url() . "/wp-content/uploads/2023/07/harrisons-bird-foods-logo-h.png' alt='Company Logo' style='max-width: 200px;'/>";
        $output .= "</div>";
        $output .= "<div style='flex-basis: 50%; text-align: right;'>";
        $output .= "<h2 style='margin: 0; color: #004c7e;'>Credit Invoice</h2>";
        $output .= "<p>Invoice No: " . esc_html($order->get_order_number()) . "</p>";
        $output .= "<p>Invoice Date: " . esc_html($order->get_date_created()->format('M j, Y')) . "</p>";
        $output .= "<p>Issued by: " . esc_html($issuer_name) . "</p>";
        $output .= "</div>";
        $output .= "</div>";

        // Company Information
        $output .= "<div style='margin-bottom: 20px;'>";
        $output .= "<h3 style='margin-bottom: 5px;'>" . esc_html('Harrison\'s Bird Foods') . "</h3>";
        $output .= "<p>7108 Crossroads Blvd. Suite 325<br>Brentwood, TN 37027<br>615-221-9919 Office<br>800-346-0269 Toll Free<br>orders@harrisonsbirdfoods.com</p>";
        $output .= "</div>";

        // Billing Information
        $output .= "<div style='margin-bottom: 20px;'>";
        $output .= "<h3>Bill to:</h3>";
        $output .= "<p>" . $order->get_formatted_billing_address() . "</p>";
        $output .= "</div>";

        // Refund Reason
        if (!empty($refund_reason)) {
            $output .= "<h3 style='color: #004c7e;'>Refund Reason:</h3>";
            $output .= "<p>{$refund_reason}</p>";
        }

        // Initialize variables for the refund totals
        $refunded_items_total = 0;
        $tax_refunded_total = 0;

        // Calculate refunded totals for items and taxes
        $refunds = $order->get_refunds();
        foreach ($refunds as $refund) {
            foreach ($refund->get_items() as $item_id => $item) {
                $refunded_items_total += abs($item->get_total());
                $tax_refunded_total += abs($item->get_total_tax());
            }
        }

        // Calculate the combined total of shipping and fees refunded
        $refund_total = abs($order->get_total_refunded());
        $shipping_and_fees_refunded_total = $refund_total - ($refunded_items_total + $tax_refunded_total);

        // Refunded Items Table
        $output .= "<table style='width: 100%; border-collapse: collapse; margin-top: 20px;'>";
        $output .= "<thead style='background-color: #004c7e; color: white;'>";
        $output .= "<tr><th style='border: 1px solid #ddd; padding: 8px;'>Item</th><th style='border: 1px solid #ddd; padding: 8px;'>Quantity</th><th style='border: 1px solid #ddd; padding: 8px;'>Refund Amount</th></tr>";
        $output .= "</thead>";
        $output .= "<tbody>";
        $refunds = $order->get_refunds();
        foreach ($refunds as $refund) {
            foreach ($refund->get_items() as $item_id => $item) {
                $output .= "<tr>";
                $output .= "<td style='border: 1px solid #ddd; padding: 8px;'>" . esc_html($item->get_name()) . "</td>";
                $output .= "<td style='border: 1px solid #ddd; padding: 8px;'>" . esc_html($item->get_quantity()) . "</td>";
                $output .= "<td style='border: 1px solid #ddd; padding: 8px;'>" . wc_price($item->get_total()) . "</td>";
                $output .= "</tr>";
            }
        }
        $output .= "</tbody>";
        $output .= "</table>";

        // Refund Notes
        $output .= "<div style='margin-top: 20px; text-align: left;'>";
        $refunds = $order->get_refunds();
        foreach ($refunds as $refund) {
            $refund_notes = $refund->get_refund_reason();
            if ($refund_notes) {
                $output .= "<p><strong>Note:</strong> " . esc_html($refund_notes) . "</p>";
            }
        }
        $output .= "</div>";
        $tdStyle = '';
        // Order Totals Table
        $output .= "<table style='width: 50%; float: right; border-collapse: collapse; margin-top: 20px;'>";
        $output .= "<tr><td style='" . $tdStyle . "text-align: right;'>Refunded items total:</td><td style='" . $tdStyle . "text-align: right;'>" . wc_price($refunded_items_total) . "</td></tr>";
        $output .= "<tr><td style='" . $tdStyle . "text-align: right;'>Tax Refunded Total:</td><td style='" . $tdStyle . "text-align: right;'>" . wc_price($tax_refunded_total) . "</td></tr>";
        $output .= "<tr><td style='" . $tdStyle . "text-align: right;'>Shipping & Fees Refunded Total:</td><td style='" . $tdStyle . "text-align: right;'>" . wc_price($shipping_and_fees_refunded_total) . "</td></tr>";
        $output .= "<tr><td style='" . $tdStyle . "text-align: right;'>Refund Total:</td><td style='" . $tdStyle . "text-align: right;'>" . wc_price($refund_total) . "</td></tr>";
        $output .= "</table>";

        // Footer
        $output .= "<div style='text-align: center; margin-top: 30px;'>";
        $output .= "<p>Thank you for shopping with us.</p>";
        $output .= "</div>";

        $output .= "</div>"; // Close the container div

        return $output;
    }

    public function ajax_generate_refund_pdf() {
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;

        $htmlContent = $this->generate_refund_pdf_content($order_id);
        $pdfUrl = HBF_PDFGenerator::generate_pdf($htmlContent, "refund-details-{$order_id}.pdf", $order_id);

        echo json_encode(array('pdfUrl' => $pdfUrl));
        wp_die();
    }
}