<?php
namespace Harrison\Includes;

/**
 * Class Depends on user switching plugin
 *
 * @see https://wordpress.org/plugins/user-switching/
 */
class HBF_User {
    public static function is_old_admin() {
        $flag_old_user = false;

        $old_user = self::get_old_user();

        if($old_user && (in_array('administrator', $old_user->roles) || user_can($old_user->ID, 'view_extra_fields'))){
            $flag_old_user = true;
        }

        return $flag_old_user;
    }

    public static function get_old_user() {
        $cookie = '';
        if(function_exists('user_switching_get_olduser_cookie')){
            $cookie = user_switching_get_olduser_cookie();
        }

        if ( ! empty( $cookie ) ) {
            $old_user_id = false;
            if(function_exists('wp_validate_auth_cookie')){
                $old_user_id = wp_validate_auth_cookie( $cookie, 'logged_in' );
            }

            if ( $old_user_id ) {
                return get_userdata( $old_user_id );
            }
        }

        return false;
    }

    public static function is_admin_or_can_view_extra_fields() {
        $user = wp_get_current_user();

        return in_array('administrator', $user->roles) || self::is_old_admin() || user_can($user->ID, 'view_extra_fields');
    }

    public static function is_hbf_admin() {
        return self::is_admin_or_can_view_extra_fields();
    }

    public static function can_view_walkin() {
        $user = wp_get_current_user();

        return in_array('administrator', $user->roles) || in_array('shop-manager', $user->roles) || in_array('shipping-manager', $user->roles) || self::is_old_admin() || user_can($user->ID, 'view_extra_fields');
    }

    public static function user_can_order_internationally() {
        $user = wp_get_current_user();

        return !empty(array_intersect($user->roles, array('administrator', 'shop-manager', 'shipping-manager'))) || self::is_old_admin() || user_can($user->ID, 'view_extra_fields');
    }

    public static function has_extra_fields_view() {
        $old_user = self::get_old_user();

        return $old_user ? user_can($old_user->ID, 'view_extra_fields') : current_user_can('view_extra_fields');
    }
}