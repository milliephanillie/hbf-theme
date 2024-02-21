<?php
namespace Harrison\WCHelpers;

use Harrison\Includes\HBF_User;

class HBF_CheckoutHelper {
    const ASSET_VERSION = '1.0.0';

    public function __construct() {        
        add_action('woocommerce_order_status_changed', [$this, 'check_order_status_and_send_tracking_info'], 10, 4);
        //Adding here as this hook is generated in check_order_status_and_send_tracking_info
        add_action('send_tracking_info_to_customer', [$this, 'send_tracking_info_on_complete']);
    }

    

    

    

    
}
