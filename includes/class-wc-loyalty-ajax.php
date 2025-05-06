<?php
/**
 * WC Loyalty AJAX Handler
 *
 * Handles AJAX requests for the loyalty program.
 */

if (!defined('WPINC')) {
    die;
}

/**
 * WC_Loyalty_Ajax Class
 */
class WC_Loyalty_Ajax {
    
    /**
     * Constructor.
     */
    public function __construct() {
        // AJAX handlers for logged-in users
        add_action('wp_ajax_apply_loyalty_coupon', array($this, 'apply_loyalty_coupon'));
    }
    
    /**
     * AJAX handler for applying coupons directly.
     */
    public function apply_loyalty_coupon() {
        // Verify nonce
        check_ajax_referer('wc_loyalty_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $coupon_code = isset($_POST['coupon_code']) ? sanitize_text_field($_POST['coupon_code']) : '';
        
        // Validate coupon code
        if (!$coupon_code) {
            wp_send_json_error(array(
                'message' => __('Invalid coupon code.', 'wc-loyalty-gamification')
            ));
            return;
        }
        
        // Check if this is a valid loyalty coupon for this user
        $is_valid_coupon = false;
        $user_coupons = WC_Loyalty()->rewards->get_user_coupons($user_id);
        
        foreach ($user_coupons as $coupon) {
            if ($coupon['code'] === $coupon_code && !$coupon['is_used']) {
                $is_valid_coupon = true;
                break;
            }
        }
        
        if (!$is_valid_coupon) {
            wp_send_json_error(array(
                'message' => __('This coupon is not valid or has already been used.', 'wc-loyalty-gamification')
            ));
            return;
        }
        
        // Apply the coupon
        if (WC()->cart->apply_coupon($coupon_code)) {
            // Success
            wp_send_json_success(array(
                'message' => __('Coupon successfully applied!', 'wc-loyalty-gamification'),
            ));
        } else {
            // Failed to apply
            wp_send_json_error(array(
                'message' => __('Failed to apply coupon. Please try again.', 'wc-loyalty-gamification')
            ));
        }
    }
}

// The wc_loyalty_coupon_cart_description function is already defined in the main plugin file,
// so it's not needed here. The wc_loyalty_mark_coupon_used_on_complete function is also
// already defined in the main file.


