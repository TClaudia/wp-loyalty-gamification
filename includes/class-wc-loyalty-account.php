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
        
        // Add My Account menu items
        add_filter('woocommerce_account_menu_items', array($this, 'add_account_menu_items'));
        
        // Add content to My Account endpoints
        add_action('woocommerce_account_loyalty-points_endpoint', array($this, 'loyalty_points_content'));
        add_action('woocommerce_account_loyalty-rewards_endpoint', array($this, 'loyalty_rewards_content'));
        
        // Add points info to My Account dashboard
        add_action('woocommerce_account_dashboard', array($this, 'account_dashboard_widget'));
        
        // Force flush rewrite rules on plugin activation
        register_activation_hook(WC_LOYALTY_PLUGIN_DIR . 'wc-loyalty-gamification.php', 'flush_rewrite_rules');
    }

<<<<<<< HEAD
    /**
     * Add endpoints.
     */
=======
>>>>>>> 815d2df76d4f986c861a1c2a5831e3bb6472e936
    public function add_endpoints() {
        add_rewrite_endpoint('loyalty-points', EP_ROOT | EP_PAGES);
        add_rewrite_endpoint('loyalty-rewards', EP_ROOT | EP_PAGES);
        
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
        // Insert our custom endpoints before logout
        $logout_item = false;
        
        if (isset($items['customer-logout'])) {
            $logout_item = $items['customer-logout'];
            unset($items['customer-logout']);
        }
        
        $items['loyalty-points'] = __('Loyalty Points', 'wc-loyalty-gamification');
        $items['loyalty-rewards'] = __('My Rewards', 'wc-loyalty-gamification');
        
        if ($logout_item) {
            $items['customer-logout'] = $logout_item;
        }
        
        return $items;
    }
    
    /**
<<<<<<< HEAD
     * Display rewards page in My Account.
=======
     * Display rewards content in My Account.
>>>>>>> 815d2df76d4f986c861a1c2a5831e3bb6472e936
     */
    public function loyalty_rewards_content() {
        $user_id = get_current_user_id();
        
        // Get user rewards data
        $user_coupons = WC_Loyalty()->rewards->get_user_coupons($user_id);
<<<<<<< HEAD
        if (!is_array($user_coupons)) {
            $user_coupons = array();
        }
        
        $claimed_rewards = WC_Loyalty()->rewards->get_rewards_claimed($user_id);
        if (!is_array($claimed_rewards)) {
            $claimed_rewards = array();
        }
        
        $reward_tiers = unserialize(get_option('wc_loyalty_reward_tiers', 'a:0:{}'));
        if (!is_array($reward_tiers)) {
            $reward_tiers = array();
        }
        
        $free_product_tier = $this->can_claim_free_product($user_id);
        $points = WC_Loyalty()->points->get_user_points($user_id);
=======
        $claimed_rewards = WC_Loyalty()->rewards->get_rewards_claimed($user_id);
        $reward_tiers = unserialize(get_option('wc_loyalty_reward_tiers'));
>>>>>>> 815d2df76d4f986c861a1c2a5831e3bb6472e936
        
        // Load template
        include WC_LOYALTY_PLUGIN_DIR . 'templates/account/loyalty-rewards.php';
    }
    
    /**
     * Display loyalty widget on My Account dashboard.
     */
    public function account_dashboard_widget() {
        $user_id = get_current_user_id();
        $user_points = WC_Loyalty()->points->get_user_points($user_id);
        $reward_tiers = unserialize(get_option('wc_loyalty_reward_tiers', 'a:0:{}'));
        
        if (!is_array($reward_tiers)) {
            $reward_tiers = array();
        }
        
        $next_tier = WC_Loyalty()->rewards->get_next_reward_tier($user_points, $reward_tiers);
        
        // Load template
        include WC_LOYALTY_PLUGIN_DIR . 'templates/account/dashboard-widget.php';
    }
    
    /**
     * Display points history in My Account.
     */
    public function loyalty_points_content() {
        $user_id = get_current_user_id();
        
<<<<<<< HEAD
        // Get predefined free products
        $table_free_products = $wpdb->prefix . 'wc_loyalty_free_products';
        
        // Check if table exists
        $table_exists = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table_free_products
        )) === $table_free_products;
        
        if (!$table_exists) {
            error_log("WC Loyalty: Free products table does not exist");
            return array();
        }
        
        $free_products = $wpdb->get_results("SELECT * FROM $table_free_products");
        $products = array();
        
        if (is_array($free_products)) {
            foreach ($free_products as $product_entry) {
                if (!isset($product_entry->product_id)) {
                    continue;
                }
                
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
        }
        
        // Get user's wishlist if applicable (using YITH WooCommerce Wishlist)
        if (function_exists('YITH_WCWL')) {
            try {
                $wishlist_items = YITH_WCWL()->get_wishlist_items(array('user_id' => $user_id));
                
                if (is_array($wishlist_items)) {
                    foreach ($wishlist_items as $item) {
                        if (!isset($item['prod_id'])) {
                            continue;
                        }
                        
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
            } catch (Exception $e) {
                error_log("WC Loyalty: Error getting wishlist items: " . $e->getMessage());
                // Continue without wishlist items
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
=======
        // Get user data
>>>>>>> 815d2df76d4f986c861a1c2a5831e3bb6472e936
        $points = WC_Loyalty()->points->get_user_points($user_id);
        $points_history = WC_Loyalty()->points->get_points_history($user_id);
        $claimed_rewards = WC_Loyalty()->rewards->get_rewards_claimed($user_id);
<<<<<<< HEAD
        
        if (!is_array($claimed_rewards)) {
            $claimed_rewards = array();
        }
        
        $reward_tiers = unserialize(get_option('wc_loyalty_reward_tiers', 'a:0:{}'));
        
        if (!is_array($reward_tiers)) {
            $reward_tiers = array();
        }
        
        foreach ($reward_tiers as $tier => $reward) {
            if (isset($reward['type']) && $reward['type'] == 'free_product' && $points >= $tier && !isset($claimed_rewards[$tier])) {
                return $tier;
            }
        }
=======
        $reward_tiers = unserialize(get_option('wc_loyalty_reward_tiers'));
        $user_coupons = WC_Loyalty()->rewards->get_user_coupons($user_id);
        
        // Get tier data
        $tier_key = WC_Loyalty()->points->get_user_tier($user_id);
        $tier_data = WC_Loyalty()->points->get_user_tier_data($user_id);
        $next_tier_data = WC_Loyalty()->points->get_next_tier_data($user_id);
>>>>>>> 815d2df76d4f986c861a1c2a5831e3bb6472e936
        
        // Load template
        include WC_LOYALTY_PLUGIN_DIR . 'templates/account/loyalty-points.php';
    }
<<<<<<< HEAD

    /**
     * Display points history in My Account.
     */
    public function loyalty_points_content() {
        $user_id = get_current_user_id();
        
        // Get user data with proper validation
        $points = WC_Loyalty()->points->get_user_points($user_id);
        $points_history = WC_Loyalty()->points->get_points_history($user_id);
        
        // Ensure points_history is an array
        if (!is_array($points_history)) {
            $points_history = array();
        }
        
        $claimed_rewards = WC_Loyalty()->rewards->get_rewards_claimed($user_id);
        
        // Ensure claimed_rewards is an array
        if (!is_array($claimed_rewards)) {
            $claimed_rewards = array();
        }
        
        $reward_tiers = unserialize(get_option('wc_loyalty_reward_tiers', 'a:0:{}'));
        
        // Ensure reward_tiers is an array
        if (!is_array($reward_tiers)) {
            $reward_tiers = array();
        }
        
        $user_coupons = WC_Loyalty()->rewards->get_user_coupons($user_id);
        
        // Ensure user_coupons is an array
        if (!is_array($user_coupons)) {
            $user_coupons = array();
        }
        
        // Load template with properly validated data
        include WC_LOYALTY_PLUGIN_DIR . 'templates/account/loyalty-points.php';
    }
=======
>>>>>>> 815d2df76d4f986c861a1c2a5831e3bb6472e936
}