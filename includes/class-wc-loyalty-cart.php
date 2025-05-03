<?php
/**
 * WC Loyalty Cart Integration
 *
 * Handles cart integration for loyalty coupons.
 */

if (!defined('WPINC')) {
    die;
}

/**
 * WC_Loyalty_Cart Class
 */
class WC_Loyalty_Cart {
    
    /**
     * Constructor.
     */
    public function __construct() {
        // Display loyalty coupons in the cart
        add_action('woocommerce_before_cart_table', array($this, 'display_loyalty_coupons'));
        
        // Display loyalty coupons on the checkout page
        add_action('woocommerce_before_checkout_form', array($this, 'display_loyalty_coupons'));
        
        // AJAX handler for applying coupons
        add_action('wp_ajax_apply_loyalty_coupon', array($this, 'apply_loyalty_coupon_ajax'));
    }
    
    /**
     * Display available loyalty coupons in the cart.
     */
    public function display_loyalty_coupons() {
        // Only show for logged-in users
        if (!is_user_logged_in()) {
            return;
        }
        
        $user_id = get_current_user_id();
        $user_coupons = WC_Loyalty()->rewards->get_user_coupons($user_id);
        
        // Filter out used and expired coupons
        $active_coupons = array_filter($user_coupons, function($coupon) {
            return !$coupon['is_used'] && strtotime($coupon['expires']) > time();
        });
        
        if (empty($active_coupons)) {
            return;
        }
        
        // Get currently applied coupons
        $applied_coupons = WC()->cart->get_applied_coupons();
        
        ?>
        <div class="wc-loyalty-cart-coupons">
            <h3><?php esc_html_e('Your Loyalty Coupons', 'wc-loyalty-gamification'); ?></h3>
            <div class="wc-loyalty-cart-coupon-list">
                <?php foreach ($active_coupons as $coupon) : 
                    $is_applied = in_array($coupon['code'], $applied_coupons);
                ?>
                    <div class="wc-loyalty-cart-coupon <?php echo $is_applied ? 'applied' : ''; ?>">
                        <div class="wc-loyalty-cart-coupon-info">
                            <span class="wc-loyalty-cart-coupon-discount">
                                <?php printf(esc_html__('%d%% OFF', 'wc-loyalty-gamification'), $coupon['discount']); ?>
                            </span>
                            <span class="wc-loyalty-cart-coupon-code"><?php echo esc_html($coupon['code']); ?></span>
                        </div>
                        
                        <?php if ($is_applied) : ?>
                            <span class="wc-loyalty-coupon-applied"><?php esc_html_e('Applied', 'wc-loyalty-gamification'); ?></span>
                        <?php else : ?>
                            <button type="button" class="button apply-loyalty-coupon" data-coupon="<?php echo esc_attr($coupon['code']); ?>"><?php esc_html_e('Apply', 'wc-loyalty-gamification'); ?></button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * AJAX handler for applying loyalty coupons.
     */
    public function apply_loyalty_coupon_ajax() {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in to apply coupons.', 'wc-loyalty-gamification')
            ));
            return;
        }

        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wc_loyalty_nonce')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'wc-loyalty-gamification')
            ));
            return;
        }
        
        // Check if coupon code is provided
        $coupon_code = isset($_POST['coupon_code']) ? sanitize_text_field($_POST['coupon_code']) : '';
        
        if (empty($coupon_code)) {
            wp_send_json_error(array(
                'message' => __('Coupon code is missing.', 'wc-loyalty-gamification')
            ));
            return;
        }
        
        // Apply coupon to cart
        $result = WC()->cart->apply_coupon($coupon_code);
        
        if ($result) {
            // Mark the coupon as used in user meta
            if (method_exists(WC_Loyalty()->rewards, 'mark_coupon_as_used')) {
                WC_Loyalty()->rewards->mark_coupon_as_used($coupon_code, get_current_user_id());
            }
            
            wp_send_json_success(array(
                'message' => __('Coupon applied successfully!', 'wc-loyalty-gamification')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to apply coupon. Please try again.', 'wc-loyalty-gamification')
            ));
        }
    }
}