<?php
/**
 * WC Loyalty Account Integration
 *
 * Handles integration with WooCommerce My Account area.
 */

if (!defined('WPINC')) {
    die;
}

/**
 * WC_Loyalty_Account Class
 */
class WC_Loyalty_Account {
    
    /**
     * Constructor.
     */
public function __construct() {
    // Add endpoints early
    add_action('init', array($this, 'add_endpoints'), 10);
    
    // Add My Account menu item
    add_filter('woocommerce_account_menu_items', array($this, 'add_account_menu_items'));
    
    // Add content to My Account endpoint
    add_action('woocommerce_account_loyalty-points_endpoint', array($this, 'loyalty_points_content'));
    
    // Add points info to My Account dashboard
    add_action('woocommerce_account_dashboard', array($this, 'account_dashboard_widget'));
}
    
    /**
     * Add endpoints.
     */
   public function add_endpoints() {
    // First add the endpoint
    add_rewrite_endpoint('loyalty-points', EP_ROOT | EP_PAGES);
    
    // Only flush on specific circumstances to avoid performance issues
    if (get_option('wc_loyalty_flush_needed', 'yes') === 'yes') {
        flush_rewrite_rules();
        update_option('wc_loyalty_flush_needed', 'no');
    }
}
    
    /**
     * Add menu items to My Account.
     *
     * @param array $items Account menu items
     * @return array Modified account menu items
     */
    public function add_account_menu_items($items) {
        // Insert loyalty points before logout
        $logout_item = false;
        
        if (isset($items['customer-logout'])) {
            $logout_item = $items['customer-logout'];
            unset($items['customer-logout']);
        }
        
        $items['loyalty-points'] = __('Loyalty Points', 'wc-loyalty-gamification');
        
        if ($logout_item) {
            $items['customer-logout'] = $logout_item;
        }
        
        return $items;
    }
    
    /**
     * Display points history in My Account.
     */
    public function loyalty_points_content() {
        $user_id = get_current_user_id();
        
        // Get user points data
        $points = WC_Loyalty()->points->get_user_points($user_id);
        $points_history = WC_Loyalty()->points->get_points_history($user_id);
        $claimed_rewards = WC_Loyalty()->rewards->get_rewards_claimed($user_id);
        $reward_tiers = unserialize(get_option('wc_loyalty_reward_tiers'));
        
        // Load template
        include WC_LOYALTY_PLUGIN_DIR . 'templates/account/loyalty-points.php';
    }
    
    /**
     * Display loyalty widget on My Account dashboard.
     */
    public function account_dashboard_widget() {
        $user_id = get_current_user_id();
        $user_points = WC_Loyalty()->points->get_user_points($user_id);
        $reward_tiers = unserialize(get_option('wc_loyalty_reward_tiers'));
        $next_tier = WC_Loyalty()->rewards->get_next_reward_tier($user_points, $reward_tiers);
        
        // Load template
        include WC_LOYALTY_PLUGIN_DIR . 'templates/account/dashboard-widget.php';
    }
    
    /**
     * Get available free products for a user.
     *
     * @param int $user_id User ID
     * @return array Available free products
     */
    public function get_available_free_products($user_id) {
        global $wpdb;
        
        // Get predefined free products
        $table_free_products = $wpdb->prefix . 'wc_loyalty_free_products';
        $free_products = $wpdb->get_results("SELECT * FROM $table_free_products");
        $products = array();
        
        foreach ($free_products as $product_entry) {
            $product = wc_get_product($product_entry->product_id);
            if ($product && $product->is_in_stock()) {
                $products[] = array(
                    'id' => $product->get_id(),
                    'name' => $product->get_name(),
                    'image' => $product->get_image(),
                    'url' => $product->get_permalink()
                );
            }
        }
        
        // Get user's wishlist if applicable (using YITH WooCommerce Wishlist)
        if (function_exists('YITH_WCWL')) {
            $wishlist_items = YITH_WCWL()->get_wishlist_items(array('user_id' => $user_id));
            foreach ($wishlist_items as $item) {
                $product = wc_get_product($item['prod_id']);
                if ($product && $product->is_in_stock()) {
                    $products[] = array(
                        'id' => $product->get_id(),
                        'name' => $product->get_name(),
                        'image' => $product->get_image(),
                        'url' => $product->get_permalink(),
                        'wishlist' => true
                    );
                }
            }
        }
        
        return $products;
    }
    
    /**
     * Check if user can claim free product.
     *
     * @param int $user_id User ID
     * @return bool|int False if cannot claim, tier level if can claim
     */
    public function can_claim_free_product($user_id) {
        $points = WC_Loyalty()->points->get_user_points($user_id);
        $claimed_rewards = WC_Loyalty()->rewards->get_rewards_claimed($user_id);
        $reward_tiers = unserialize(get_option('wc_loyalty_reward_tiers'));
        
        foreach ($reward_tiers as $tier => $reward) {
            if ($reward['type'] == 'free_product' && $points >= $tier && !isset($claimed_rewards[$tier])) {
                return $tier;
            }
        }
        
        return false;
    }
}