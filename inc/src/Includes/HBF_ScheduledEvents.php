<?php

class HBF_ScheduledEvents {
    public function __construct() {
        add_action('wp', [$this, 'schedule_daily_sales_report']);
        add_action('send_daily_sales_report_hook', [$this, 'send_daily_sales_report_direct_query']);
    }

    public function schedule_daily_sales_report()
    {
        $hook_name = 'send_daily_sales_report_hook';
        /*
            // For testing: Ensure the event is not already scheduled before scheduling it again
            if (!wp_next_scheduled($hook_name)) {
                wp_clear_scheduled_hook($hook_name); // Clear existing hooks for testing purposes
                $time = time() + 60; // For immediate testing, schedule 1 minute after
                wp_schedule_event($time, 'minutely', $hook_name); // Use 'minutely' for testing
            }
        */
        // Uncomment the below section and comment out the above section after testing

        if (!wp_next_scheduled($hook_name)) {
            $time = strtotime('6:01:00'); // Sends at 12:01am
            wp_schedule_event($time, 'daily', $hook_name); // Use 'daily' for production
        }

    }

    function send_daily_sales_report_direct_query()
    {
        global $wpdb;

        $timezone = new DateTimeZone('America/Chicago');

        $start_date = new DateTime('yesterday midnight', $timezone);
        $end_date = new DateTime('today midnight -1 second', $timezone);

        $start_date_query = $start_date->format('Y-m-d H:i:s'); // Start of the previous day
        $end_date_query = $end_date->format('Y-m-d H:i:s'); // End of the previous day

        $query = $wpdb->prepare("
    SELECT
        COUNT(posts.ID) AS number_of_orders,
        SUM(meta_total.meta_value) AS gross_sales,
        COALESCE(SUM(meta_refunded.meta_value), 0) AS refunds,
        SUM(meta_total.meta_value - COALESCE(meta_refunded.meta_value, 0)) - COALESCE(SUM(meta_tax.meta_value), 0) - COALESCE(SUM(meta_shipping.meta_value), 0) AS net_sales,
        COALESCE(SUM(meta_tax.meta_value), 0) AS taxes,
        COALESCE(SUM(meta_shipping.meta_value), 0) AS shipping,
        SUM(meta_total.meta_value - COALESCE(meta_refunded.meta_value, 0)) AS total_sales
    FROM aImIof_posts AS posts
    INNER JOIN aImIof_postmeta AS meta_total ON posts.ID = meta_total.post_id AND meta_total.meta_key = '_order_total'
    LEFT JOIN aImIof_postmeta AS meta_tax ON posts.ID = meta_tax.post_id AND meta_tax.meta_key = '_order_tax'
    LEFT JOIN aImIof_postmeta AS meta_shipping ON posts.ID = meta_shipping.post_id AND meta_shipping.meta_key = '_order_shipping'
    LEFT JOIN aImIof_postmeta AS meta_refunded ON posts.ID = meta_refunded.post_id AND meta_refunded.meta_key = '_order_amount_refunded'
    WHERE posts.post_type = 'shop_order'
    AND posts.post_status IN ('wc-completed', 'wc-processing')
    AND posts.post_date >= %s
    AND posts.post_date < %s
    ", $start_date_query, $end_date_query);

        $report_data = $wpdb->get_row($query, ARRAY_A);

        if (!$report_data) {
            return;
        }

        $email_content = prepare_email_content($report_data, $start_date);
        $recipients = ['dana@harrisonsbirdfoods.com', 'mimi@wallingcpa.com.com', 'websites@navertise.net'];
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        wp_mail($recipients, 'Daily WooCommerce Report - ' . $start_date->format('Y-m-d'), $email_content, $headers);
    }

    function prepare_email_content($report_data, $start_date) {
        $style = 'body { background-color: #f7f7f7; color: #3c3c3c; } .email-container { background-color: #ffffff; margin: 0 auto; padding: 20px; width: 600px; } .email-header { color: #ffffff; padding: 10px; text-align: center; } .email-body { padding: 20px; } .email-footer { font-size: 12px; text-align: center; padding: 10px; }';
        $email_content = "<html><head><style>{$style}</style></head><body>";
        $email_content .= '<div class="email-container"><div class="email-header"><img src="https://www.harrisonsbirdfoods.com/wp-content/uploads/2024/01/HBD-logo-main.png" alt="Header Image" style="background-color:transparent;"></div><div class="email-body">';
        $email_content .= '<p>Daily Sales Report for ' . $start_date->format('Y-m-d') . ':</p>';

        foreach ($report_data as $key => $value) {
            $label = ucfirst(str_replace('_', ' ', $key));
            // Check if the value is for "number_of_orders" to avoid adding '$' sign
            if ($key == 'number_of_orders') {
                $formatted_value = number_format($value, 0); // No decimal places and no '$' sign for number of orders
            } else {
                $formatted_value = is_numeric($value) ? ('$' . number_format($value, 2)) : $value;
            }
            $email_content .= "<p><strong>{$label}:</strong> {$formatted_value}</p>";
        }

        $email_content .= '</div><div class="email-footer">' . get_bloginfo('name') . ' &mdash; Copyright © ' . date('Y') . '</div></div></body></html>';
        return $email_content;
    }
}