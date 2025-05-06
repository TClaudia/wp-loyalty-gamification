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
     * Display rewards content in My Account.
     */
    public function loyalty_rewards_content() {
        $user_id = get_current_user_id();
        
        // Get user rewards data
        $user_coupons = WC_Loyalty()->rewards->get_user_coupons($user_id);
        $claimed_rewards = WC_Loyalty()->rewards->get_rewards_claimed($user_id);
        $reward_tiers = unserialize(get_option('wc_loyalty_reward_tiers'));
        
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
        
        // Get user data
        $points = WC_Loyalty()->points->get_user_points($user_id);
        $points_history = WC_Loyalty()->points->get_points_history($user_id);
        $claimed_rewards = WC_Loyalty()->rewards->get_rewards_claimed($user_id);
        $reward_tiers = unserialize(get_option('wc_loyalty_reward_tiers'));
        $user_coupons = WC_Loyalty()->rewards->get_user_coupons($user_id);
        
        // Get tier data
        $tier_key = WC_Loyalty()->points->get_user_tier($user_id);
        $tier_data = WC_Loyalty()->points->get_user_tier_data($user_id);
        $next_tier_data = WC_Loyalty()->points->get_next_tier_data($user_id);
        
        // Load template
        include WC_LOYALTY_PLUGIN_DIR . 'templates/account/loyalty-points.php';
    }
}