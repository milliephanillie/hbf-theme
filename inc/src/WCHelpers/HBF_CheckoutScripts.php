<?php
namespace Harrison\WCHelpers;

class HBF_CheckoutScripts {
    public static function init_hooks() {
        add_filter('wp_head', ['self', 'hfood_skip_cart_redirect_checkout']);
        add_action('wp_footer', ['self', 'hide_subscription_price_for_guests_js']);
    }
    
    public static function hide_subscription_price_for_guests_js() {
        if (!is_user_logged_in()) {
            ?>
            <script type="text/javascript">
                jQuery(document).ready(function($) {
                    $('.price').each(function() {
                        var text = $(this).text();
                        var newText = text.split('—')[0];
                        $(this).text(newText);
                    });
                });
            </script>
            <?php
        }
    }

    public static function hfood_skip_cart_redirect_checkout( $url ) {
        global $post;

        if(is_user_logged_in()){
            $user = wp_get_current_user();

            if($post->post_name == 'cart' && HBF_User::is_admin_or_old_admin()){
                if(\WC()->cart->get_cart_contents_count() == 0){
                    ?>
                    <script>
                        window.location = '/manual-orders';
                    </script>
                    <?php
                }
                ?>
                <script>
                    window.location = '<?= wc_get_checkout_url(); ?>';
                </script>
                <?php
            }
        }
    }
}