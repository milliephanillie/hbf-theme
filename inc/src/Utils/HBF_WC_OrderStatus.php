<?php
namespace Harrison\Utils;

class HBF_WC_OrderStatus {
    public function __construct() {
        add_filter('wc_order_statuses', [$this, 'add_custom_order_status_to_wc']);
        add_filter('wc_order_statuses', [$this, 'add_partial_refund_to_order_statuses']);
    }

    public function add_custom_order_status_to_wc($order_statuses) {
        $new_order_statuses = array();

        foreach ($order_statuses as $key => $status) {
            $new_order_statuses[$key] = $status;
            if ('wc-processing' === $key) {
                $new_order_statuses['wc-net15-pending'] = 'NET15 Pending';
            }
        }
        return $new_order_statuses;
    }

    public function add_partial_refund_to_order_statuses($order_statuses) {
        $new_order_statuses = array();
        foreach ($order_statuses as $key => $status) {
            $new_order_statuses[$key] = $status;
            if ('wc-processing' === $key) {
                $new_order_statuses['wc-partial-refund'] = 'Partial Refund';
            }
        }
        return $new_order_statuses;
    }
}