<?php
namespace Harrison\AJAX;

use Harrison\Includes\HBF_User;

class HBF_CheckUserStatus {
    public function __construct() {
        add_action('wp_ajax_check_user_status', [$this, 'check_user_status_ajax']);
        add_action('wp_ajax_nopriv_check_user_status', [$this, 'check_user_status_ajax']);
    }

    public function check_user_status_ajax() {
        $current_user = wp_get_current_user();
        $old_user = HBF_User::get_old_user(); // Assuming get_old_user() is defined and returns the old user data
        $is_switched_user = $old_user && ($current_user->ID != $old_user->ID);

        // Check if the original user (before the switch) had 'view_extra_fields' capability
        $has_extra_fields_view = HBF_User::has_extra_fields_view();

        wp_send_json(array(
            'isAdmin' => $has_extra_fields_view,
            'currentUsername' => $current_user->display_name,
            'isSwitchedUser' => $is_switched_user
        ));
    }
}