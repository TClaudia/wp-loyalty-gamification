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
        add_action('wp_ajax_claim_loyalty_reward', array($this, 'claim_loyalty_reward'));
    }
    
    /**
     * AJAX handler for claiming rewards.
     */
    public function claim_loyalty_reward() {
        // Verify nonce
        check_ajax_referer('wc_loyalty_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $reward_type = isset($_POST['reward_type']) ? sanitize_text_field($_POST['reward_type']) : '';
        
        // Handle free product claim
        if ($reward_type === 'free_product') {
            $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
            $free_product_tier = WC_Loyalty()->account->can_claim_free_product($user_id);
            
            if (!$free_product_tier) {
                wp_send_json_error(array(
                    'message' => __('You are not eligible for a free product at this time.', 'wc-loyalty-gamification')
                ));
                return;
            }
            
            if ($product_id > 0) {
                $product = wc_get_product($product_id);
                
                if (!$product || !$product->is_in_stock()) {
                    wp_send_json_error(array(
                        'message' => __('This product is not available.', 'wc-loyalty-gamification')
                    ));
                    return;
                }
                
                // Mark reward as claimed
                $claimed_rewards = WC_Loyalty()->rewards->get_rewards_claimed($user_id);
                $claimed_rewards[$free_product_tier] = current_time('mysql');
                WC_Loyalty()->rewards->update_rewards_claimed($user_id, $claimed_rewards);
                
                // Add product to cart with 100% discount
                $cart_item_key = WC()->cart->add_to_cart($product_id, 1);
                
                if ($cart_item_key) {
                    // Apply 100% discount to this item
                    // In a real implementation, we'd use a cart hook to apply the discount
                    // For demonstration, we'll use a session variable to flag this item
                    WC()->session->set('loyalty_free_product_' . $cart_item_key, true);
                    
                    // Add note about free product redemption
                    WC_Loyalty()->points->add_points($user_id, 0, sprintf(
                        __('Redeemed free product: %s', 'wc-loyalty-gamification'),
                        $product->get_name()
                    ));
                    
                    wp_send_json_success(array(
                        'message' => __('Free product added to cart! Proceed to checkout to complete your order.', 'wc-loyalty-gamification'),
                        'redirect' => wc_get_cart_url()
                    ));
                } else {
                    wp_send_json_error(array(
                        'message' => __('Could not add product to cart. Please try again.', 'wc-loyalty-gamification')
                    ));
                }
            } else {
                wp_send_json_error(array(
                    'message' => __('Invalid product selected.', 'wc-loyalty-gamification')
                ));
            }
        } else {
            wp_send_json_error(array(
                'message' => __('Invalid reward type.', 'wc-loyalty-gamification')
            ));
        }
    }
}

// Apply 100% discount to free product in cart
add_action('woocommerce_before_calculate_totals', 'wc_loyalty_apply_free_product_discount', 10, 1);
function wc_loyalty_apply_free_product_discount($cart) {
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }
    
    // Only calculate once
    if (did_action('woocommerce_before_calculate_totals') > 1) {
        return;
    }
    
    // Ensure we have a session
    if (!WC()->session) {
        return;
    }
    
    // Check each cart item
    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        if (WC()->session->get('loyalty_free_product_' . $cart_item_key)) {
            // Apply 100% discount by setting price to 0
            $cart_item['data']->set_price(0);
            
            // Add text to indicate this is a free loyalty reward
            if (!isset($cart_item['loyalty_free_product_text_added'])) {
                add_filter('woocommerce_cart_item_name', function($name, $cart_item, $cart_item_key_arg) use ($cart_item_key) {
                    if ($cart_item_key_arg === $cart_item_key) {
                        return $name . ' <span class="loyalty-free-product-label">' . __('(Loyalty Reward)', 'wc-loyalty-gamification') . '</span>';
                    }
                    return $name;
                }, 10, 3);
                $cart_item['loyalty_free_product_text_added'] = true;
            }
        }
    }
}

// Add note to order when it contains a free product
add_action('woocommerce_checkout_create_order', 'wc_loyalty_add_free_product_order_note', 10, 1);
function wc_loyalty_add_free_product_order_note($order) {
    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        if (WC()->session->get('loyalty_free_product_' . $cart_item_key)) {
            $product = $cart_item['data'];
            $order->add_order_note(sprintf(
                __('This order contains a free product (%s) redeemed through the loyalty program.', 'wc-loyalty-gamification'),
                $product->get_name()
            ));
            
            // Clear the session flag
            WC()->session->set('loyalty_free_product_' . $cart_item_key, null);
        }
    }
}