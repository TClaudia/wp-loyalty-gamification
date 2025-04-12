<?php
/**
 * WC Loyalty Frontend Display
 *
 * Handles frontend display and user interface.
 */

if (!defined('WPINC')) {
    die;
}

/**
 * WC_Loyalty_Frontend Class
 */
class WC_Loyalty_Frontend {

    /**
     * Fix conflicts with the Botiga theme.
     */
    public function fix_botiga_conflicts() {
        // Make sure we're not removing Botiga's actions
        if (has_action('woocommerce_after_shop_loop_item', 'botiga_woocommerce_template_loop_add_to_cart')) {
            // Botiga's hook is registered, don't interfere with it
            return;
        }
        
        // If Botiga's hook isn't registered, add the default WooCommerce hook back
        add_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10);
    }

    /**
     * Constructor.
     */
    public function __construct() {
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Add loyalty interface to footer
        add_action('wp_footer', array($this, 'render_loyalty_interface'));

        // Register the fix_botiga_conflicts method
        add_action('wp', array($this, 'fix_botiga_conflicts'), 99);
    }
    
    /**
     * Enqueue scripts and styles.
     */
    public function enqueue_scripts() {
        // Only enqueue if user is logged in
        if (!is_user_logged_in()) {
            return;
        }
        
        // Enqueue styles
        wp_enqueue_style(
            'wc-loyalty-style',
            WC_LOYALTY_PLUGIN_URL . 'assets/css/loyalty-style.css',
            array(),
            WC_LOYALTY_VERSION
        );
        
        // Enqueue circle progress library
        wp_enqueue_script(
            'wc-loyalty-circle-progress',
            WC_LOYALTY_PLUGIN_URL . 'assets/js/circle-progress.min.js',
            array('jquery'),
            '1.2.2',
            true
        );
        
        // Enqueue main script
        wp_enqueue_script(
            'wc-loyalty-script',
            WC_LOYALTY_PLUGIN_URL . 'assets/js/loyalty-script.js',
            array('jquery', 'wc-loyalty-circle-progress'),
            WC_LOYALTY_VERSION,
            true
        );
        
        // Add dashicons for the star icon
        wp_enqueue_style('dashicons');
        
        // Pass data to JS
        $user_id = get_current_user_id();
        $user_points = WC_Loyalty()->points->get_user_points($user_id);
        $reward_tiers = unserialize(get_option('wc_loyalty_reward_tiers'));
        $next_tier = WC_Loyalty()->rewards->get_next_reward_tier($user_points, $reward_tiers);
        
        wp_localize_script('wc-loyalty-script', 'wcLoyaltyData', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'userPoints' => $user_points,
            'nextTier' => $next_tier,
            'nonce' => wp_create_nonce('wc_loyalty_nonce')
        ));
    }
    
    /**
     * Render loyalty interface on frontend.
     */
    public function render_loyalty_interface() {
        // Only show for logged-in users
        if (!is_user_logged_in()) {
            return;
        }
        
        $user_id = get_current_user_id();
        $user_points = WC_Loyalty()->points->get_user_points($user_id);
        $reward_tiers = unserialize(get_option('wc_loyalty_reward_tiers'));
        $claimed_rewards = WC_Loyalty()->rewards->get_rewards_claimed($user_id);
        $next_tier = WC_Loyalty()->rewards->get_next_reward_tier($user_points, $reward_tiers);
        
        // Calculate progress percentage
        $progress = 0;
        if ($next_tier !== null) {
            $progress = ($user_points / $next_tier) * 100;
        } elseif (!empty($reward_tiers)) {
            // If user has passed all tiers
            $highest_tier = max(array_keys($reward_tiers));
            $progress = ($user_points >= $highest_tier) ? 100 : ($user_points / $highest_tier) * 100;
        }
        
        // Limit to 100%
        $progress = min($progress, 100);
        
        // Load template
        include WC_LOYALTY_PLUGIN_DIR . 'templates/loyalty-interface.php';
    }
}