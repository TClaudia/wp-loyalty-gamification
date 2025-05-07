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
    }
    
    /**
     * Display available loyalty coupons in the cart.
     */
    public function display_loyalty_coupons() {
        // Only show for logged-in users and only on cart or checkout pages
        if (!is_user_logged_in() || (!is_cart() && !is_checkout())) {
            return;
        }
        
        $user_id = get_current_user_id();
        
        // Check if WC_Loyalty is properly initialized before using it
        if (!function_exists('WC_Loyalty') || !WC_Loyalty() || !isset(WC_Loyalty()->rewards)) {
            return; // Exit if WC_Loyalty or rewards component is not available
        }
        
        // Check if the get_user_coupons method exists
        if (!method_exists(WC_Loyalty()->rewards, 'get_user_coupons')) {
            return;
        }
        
        $user_coupons = WC_Loyalty()->rewards->get_user_coupons($user_id);
        
        // Filter out used and expired coupons
        $active_coupons = array();
        if (is_array($user_coupons)) {
            foreach ($user_coupons as $coupon) {
                if (isset($coupon['is_used']) && isset($coupon['expires']) && 
                    !$coupon['is_used'] && strtotime($coupon['expires']) > time()) {
                    $active_coupons[] = $coupon;
                }
            }
        }
        
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
                    $is_premium = isset($coupon['tier']) && $coupon['tier'] == 2000;
                ?>
                    <div class="wc-loyalty-cart-coupon <?php echo $is_applied ? 'applied' : ''; ?> <?php echo $is_premium ? 'premium' : ''; ?>">
                        <div class="wc-loyalty-cart-coupon-info">
                            <span class="wc-loyalty-cart-coupon-discount">
                                <?php printf(esc_html__('%d%% OFF', 'wc-loyalty-gamification'), $coupon['discount']); ?>
                                <?php if ($is_premium): ?>
                                    <span class="premium-label"><?php esc_html_e('Premium', 'wc-loyalty-gamification'); ?></span>
                                <?php endif; ?>
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
}