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

        // Add cart coupon handling script
if (is_cart() || is_checkout()) {
    wp_enqueue_script(
        'wc-loyalty-cart-coupon',
        WC_LOYALTY_PLUGIN_URL . 'assets/js/cart-cupon.js',
        array('jquery'),
        WC_LOYALTY_VERSION,
        true
    );
    
    // Make sure to localize the script with AJAX data
    wp_localize_script('wc-loyalty-cart-coupon', 'wcLoyaltyData', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('wc_loyalty_nonce')
    ));
}
        
        // Add dashicons for the star icon
        wp_enqueue_style('dashicons');
        
        // Pass data to JS - safely check if WC_Loyalty is fully initialized
        $user_id = get_current_user_id();
        $user_points = 0;
        $next_tier = null;
        
        if (function_exists('WC_Loyalty') && WC_Loyalty() && WC_Loyalty()->points) {
            $user_points = WC_Loyalty()->points->get_user_points($user_id);
            
            if (WC_Loyalty()->rewards) {
                $reward_tiers = unserialize(get_option('wc_loyalty_reward_tiers', 'a:0:{}'));
                $next_tier = WC_Loyalty()->rewards->get_next_reward_tier($user_points, $reward_tiers);
            }
        }
        
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
        
        // Safely check if WC_Loyalty components are fully initialized
        if (!function_exists('WC_Loyalty') || !WC_Loyalty() || !WC_Loyalty()->points || !WC_Loyalty()->rewards) {
            return;
        }
        
        $total_points = WC_Loyalty()->points->get_user_points($user_id);
        $display_points = WC_Loyalty()->points->get_user_display_points($user_id);
        $cycle_level = WC_Loyalty()->points->get_user_cycle_level($user_id);
        $reward_tiers = unserialize(get_option('wc_loyalty_reward_tiers', 'a:0:{}'));
        $claimed_rewards = WC_Loyalty()->rewards->get_rewards_claimed($user_id);
        
        // Get next tier based on total points
        $next_tier = WC_Loyalty()->rewards->get_next_reward_tier($total_points, $reward_tiers);
        
        // Calculate progress percentage based on display points (0-2000 cycle)
        $progress = ($display_points / 2000) * 100;
        
        // Limit to 100%
        $progress = min($progress, 100);
        
        // Get user coupons and notifications
        $user_coupons = WC_Loyalty()->rewards->get_user_coupons($user_id);
        $user_notifications = WC_Loyalty()->rewards->get_user_notifications($user_id);
        
        // Load template
        include WC_LOYALTY_PLUGIN_DIR . 'templates/loyalty-interface.php';
    }

/**
 * Fixed version of the add_comment_author_badge function
 * Replace this in your class-wc-loyalty-frontend.php file
 */

/**
 * Add tier badge to comment author.
 */
public function add_comment_author_badge($author, $comment_id, $comment) {
    // Only add badge for logged-in users
    if (!$comment->user_id) {
        return $author;
    }
    
    // Safely check if WC_Loyalty components are fully initialized
    if (!function_exists('WC_Loyalty') || !WC_Loyalty() || !WC_Loyalty()->points) {
        return $author;
    }
    
    $tier_key = WC_Loyalty()->points->get_user_tier($comment->user_id);
    $tier_data = WC_Loyalty()->points->get_user_tier_data($comment->user_id);
    
    if (empty($tier_data)) {
        return $author;
    }
    
    // Create a badge that appears AFTER the author name without extra formatting
    $badge = sprintf(
        ' <span class="wc-loyalty-comment-badge" style="background-color: %s">%s</span>',
        esc_attr($tier_data['color']),
        esc_html($tier_data['name'])
    );
    
    // Remove any existing badge first to avoid duplicates
    $author = preg_replace('/<span class="wc-loyalty-comment-badge".*?<\/span>/', '', $author);
    
    // Return author name with badge, making sure we don't add extra formatting
    return $author . $badge;
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

        // Add comment author badge
       add_filter('comment_author', array($this, 'add_comment_badge_clean'), 99, 2);
    }


    /**
 * New method to add badge to comment author name
 * This uses a better hook point with fewer parameters
 */
public function add_comment_badge_clean($author, $comment_id) {
    // Get the comment object
    $comment = get_comment($comment_id);
    
    // Only add badge for logged-in users with a user_id
    if (!$comment || !$comment->user_id) {
        return $author;
    }
    
    // Safely check if WC_Loyalty components are initialized
    if (!function_exists('WC_Loyalty') || !WC_Loyalty() || !WC_Loyalty()->points) {
        return $author;
    }
    
    $tier_key = WC_Loyalty()->points->get_user_tier($comment->user_id);
    $tier_data = WC_Loyalty()->points->get_user_tier_data($comment->user_id);
    
    if (empty($tier_data)) {
        return $author;
    }
    
    // Create a clean badge with appropriate spacing
    $badge = sprintf(
        ' <span class="wc-loyalty-comment-badge" style="display:inline-block; margin-left:5px; padding:2px 6px; border-radius:3px; font-size:11px; background-color:%s; color:#fff;">%s</span>',
        esc_attr($tier_data['color']),
        esc_html($tier_data['name'])
    );
    
    // Return a clean version with just the author name and badge
    return wp_kses_post($author) . $badge;
}
}