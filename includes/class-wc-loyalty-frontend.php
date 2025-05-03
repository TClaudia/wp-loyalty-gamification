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
    $total_points = WC_Loyalty()->points->get_user_points($user_id);
    $display_points = WC_Loyalty()->points->get_user_display_points($user_id);
    $cycle_level = WC_Loyalty()->points->get_user_cycle_level($user_id);
    $reward_tiers = unserialize(get_option('wc_loyalty_reward_tiers'));
    $claimed_rewards = WC_Loyalty()->rewards->get_rewards_claimed($user_id);
    
    // Get next tier based on total points
    $next_tier = WC_Loyalty()->rewards->get_next_reward_tier($total_points, $reward_tiers);
    
    // Calculate progress percentage based on display points (0-2000 cycle)
    $progress = ($display_points / 2000) * 100;
    
    // Limit to 100%
    $progress = min($progress, 100);
    
    // Load template
    include WC_LOYALTY_PLUGIN_DIR . 'templates/loyalty-interface.php';
}


    /**
 * Add tier badge to comment author.
 */
public function add_comment_author_badge($author, $comment_id, $comment) {
    if (!is_user_logged_in() || !$comment->user_id) {
        return $author;
    }
    
    $tier_key = WC_Loyalty()->points->get_user_tier($comment->user_id);
    $tier_data = WC_Loyalty()->points->get_user_tier_data($comment->user_id);
    
    if (empty($tier_data)) {
        return $author;
    }
    
    $badge = sprintf(
        '<span class="wc-loyalty-comment-badge" style="background-color: %s">%s</span>',
        esc_attr($tier_data['color']),
        esc_html($tier_data['name'])
    );
    
    return $author . ' ' . $badge;
}

// Add this to the WC_Loyalty_Frontend class constructor
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
    add_filter('get_comment_author', array($this, 'add_comment_author_badge'), 10, 3);
    
    // Add loyalty coupons to cart
    add_action('woocommerce_before_cart_table', array($this, 'display_loyalty_coupons_in_cart'));
    add_action('woocommerce_before_checkout_form', array($this, 'display_loyalty_coupons_in_cart'));
    
    // Add AJAX handler for applying coupons
    add_action('wp_ajax_apply_loyalty_coupon', array($this, 'apply_loyalty_coupon'));
}

/**
 * Display available loyalty coupons in the cart.
 */
public function display_loyalty_coupons_in_cart() {
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
public function apply_loyalty_coupon() {
    // Verify nonce
    check_ajax_referer('wc_loyalty_nonce', 'nonce');
    
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