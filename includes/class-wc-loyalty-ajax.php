<?php
/**
 * Fixed version of the AJAX handler for applying coupons
 * Replace the existing function in class-wc-loyalty-ajax.php
 */

 class WC_Loyalty_Ajax {
    
    /**
     * Constructor.
     */
    public function __construct() {
        // AJAX handlers for logged-in users
        add_action('wp_ajax_apply_loyalty_coupon', array($this, 'apply_loyalty_coupon'));
    }
    

public function apply_loyalty_coupon() {
    // Start output buffering to catch any warnings
    ob_start();

    try {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wc_loyalty_nonce')) {
            ob_end_clean(); // Clean output buffer before sending response
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'wc-loyalty-gamification')
            ));
            return;
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            ob_end_clean(); // Clean output buffer before sending response
            wp_send_json_error(array(
                'message' => __('You must be logged in to apply coupons.', 'wc-loyalty-gamification')
            ));
            return;
        }
        
        $user_id = get_current_user_id();
        $coupon_code = isset($_POST['coupon_code']) ? sanitize_text_field($_POST['coupon_code']) : '';
        
        // Validate coupon code
        if (empty($coupon_code)) {
            ob_end_clean(); // Clean output buffer before sending response
            wp_send_json_error(array(
                'message' => __('Invalid coupon code.', 'wc-loyalty-gamification')
            ));
            return;
        }
        
        // Check if WooCommerce is active and cart is available
        if (!function_exists('WC') || !WC() || !isset(WC()->cart)) {
            ob_end_clean(); // Clean output buffer before sending response
            wp_send_json_error(array(
                'message' => __('WooCommerce cart is not available. Please refresh the page and try again.', 'wc-loyalty-gamification')
            ));
            return;
        }
        
        // Check if this is a valid loyalty coupon for this user
        $is_valid_coupon = false;
        
        // Make sure rewards component is available
        if (!method_exists(WC_Loyalty(), 'rewards') || !method_exists(WC_Loyalty()->rewards, 'get_user_coupons')) {
            ob_end_clean(); // Clean output buffer before sending response
            wp_send_json_error(array(
                'message' => __('Rewards system is not available. Please contact support.', 'wc-loyalty-gamification')
            ));
            return;
        }
        
        $user_coupons = WC_Loyalty()->rewards->get_user_coupons($user_id);
        
        if (!is_array($user_coupons)) {
            $user_coupons = array(); // Ensure it's an array even if empty
        }
        
        foreach ($user_coupons as $coupon) {
            if (isset($coupon['code']) && $coupon['code'] === $coupon_code && 
                isset($coupon['is_used']) && !$coupon['is_used']) {
                $is_valid_coupon = true;
                break;
            }
        }
        
        if (!$is_valid_coupon) {
            ob_end_clean(); // Clean output buffer before sending response
            wp_send_json_error(array(
                'message' => __('This coupon is not valid or has already been used.', 'wc-loyalty-gamification')
            ));
            return;
        }
        
        // Clear any existing notices before we apply the coupon
        wc_clear_notices();
        
        // Apply the coupon with error checking
        $result = WC()->cart->apply_coupon($coupon_code);
        
        // Since WC 3.0+, we check notices to see if it worked
        $notices = wc_get_notices('error');
        
        // Clean output buffer before sending response
        ob_end_clean();
        
        if (empty($notices)) {
            // Mark the coupon as used immediately
            $user_coupons = WC_Loyalty()->rewards->get_user_coupons($user_id);
            $updated_coupons = array();
            
            foreach ($user_coupons as $coupon) {
                if (isset($coupon['code']) && $coupon['code'] === $coupon_code) {
                    $coupon['is_used'] = true;
                }
                $updated_coupons[] = $coupon;
            }
            
            // Save the updated coupons
            update_user_meta($user_id, '_wc_loyalty_coupons', $updated_coupons);
            
            // Success response
            wp_send_json_success(array(
                'message' => __('Coupon successfully applied!', 'wc-loyalty-gamification'),
                'coupon_code' => $coupon_code
            ));
        } else {
            // Failed to apply with specific error
            $error_message = !empty($notices) && is_array($notices) && isset($notices[0]['notice']) ? 
                            $notices[0]['notice'] : 
                            __('Failed to apply coupon. Please try again.', 'wc-loyalty-gamification');
            
            wp_send_json_error(array(
                'message' => $error_message
            ));
        }
    } catch (Exception $e) {
        // Log the error for debugging
        error_log('WC Loyalty coupon error: ' . $e->getMessage());
        
        // Clean output buffer before sending error response
        ob_end_clean();
        
        wp_send_json_error(array(
            'message' => __('An unexpected error occurred. Please try again later.', 'wc-loyalty-gamification')
        ));
    }

    // If we reach here, ensure buffer is cleaned in case something went wrong
    ob_end_clean();
    exit;
}
}