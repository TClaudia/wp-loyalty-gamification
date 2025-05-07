<?php
/**
 * WC Loyalty Rewards Management
 *
 * Handles all rewards-related functionality.
 */

if (!defined('WPINC')) {
    die;
}

/**
 * WC_Loyalty_Rewards Class
 */
class WC_Loyalty_Rewards {
    
    /**
     * Constructor.
     */
/**
 * Constructor cu cod de reparare recompense
 */
public function __construct() {
    // Cod de reparare - Verifică și restaurează recompensele implicite
    $reward_tiers = unserialize(get_option('wc_loyalty_reward_tiers', 'a:0:{}'));
    
    // Verifică dacă reward_tiers este gol sau invalid
    if (empty($reward_tiers) || !is_array($reward_tiers) || count($reward_tiers) < 3) {
        // Setează valorile implicite pentru toate nivelurile, inclusiv 2000
        $default_reward_tiers = array(
            500 => array('type' => 'discount', 'value' => 20),
            1000 => array('type' => 'discount', 'value' => 40),
            1500 => array('type' => 'free_shipping', 'value' => true),
            2000 => array('type' => 'discount', 'value' => 60, 'max_order' => 400)
        );
        
        // Salvează valorile implicite în baza de date
        update_option('wc_loyalty_reward_tiers', serialize($default_reward_tiers));
        
        // Adaugă o notă în log pentru depanare
        error_log('WC Loyalty: Nivelurile de recompensă au fost restaurate la valorile implicite.');
    }
    
    // Continuă cu restul constructorului original
    // Check reward eligibility after points update
    add_action('wc_loyalty_points_updated', array($this, 'check_reward_eligibility'), 10, 2);
    
    // Special handler for when user reaches exactly 2000 points
    add_action('wc_loyalty_reached_2000_points', array($this, 'handle_premium_discount_eligibility'));
    
    // Apply free shipping if earned
    add_filter('woocommerce_package_rates', array($this, 'apply_free_shipping'), 100, 2);
    
    // Add notification for upcoming reward
    add_action('woocommerce_before_single_product', array($this, 'product_reward_notice'));
    
    // Mark coupon as used when applied
    add_action('woocommerce_applied_coupon', array($this, 'handle_applied_coupon'));
    
    // Add discount label to cart and checkout
    add_filter('woocommerce_cart_totals_coupon_label', array($this, 'custom_coupon_label'), 10, 2);
    
    // Add message about free shipping from loyalty program
    add_action('woocommerce_before_shipping_calculator', array($this, 'display_loyalty_shipping_message'));
}
    
    /**
     * Display free shipping message if applied
     */
    public function display_loyalty_shipping_message() {
        if (!is_user_logged_in()) {
            return;
        }
        
        $user_id = get_current_user_id();
        $free_shipping = get_user_meta($user_id, '_wc_loyalty_free_shipping', true);
        
        if ($free_shipping == 'yes') {
            echo '<div class="wc-loyalty-shipping-message">';
            echo __('Free shipping applied from your loyalty program rewards!', 'wc-loyalty-gamification');
            echo '</div>';
        }
    }
    
    /**
     * Custom label for loyalty coupons
     */
    public function custom_coupon_label($label, $coupon) {
        $coupon_id = $coupon->get_id();
        $is_loyalty_coupon = get_post_meta($coupon_id, '_wc_loyalty_coupon', true);
        
        if ($is_loyalty_coupon === 'yes') {
            return $label . ' <span class="loyalty-coupon-label">(' . __('Loyalty Reward', 'wc-loyalty-gamification') . ')</span>';
        }
        
        return $label;
    }
    
    /**
     * Special handler for 2000 points - premium discount reward
     * 
     * @param int $user_id User ID
     */
    public function handle_premium_discount_eligibility($user_id) {
        // Make sure we have the 2000 points tier
        $reward_tiers = unserialize(get_option('wc_loyalty_reward_tiers'));
        
        // Create or update the 2000 tier for 60% discount
        $reward_tiers[2000] = array('type' => 'discount', 'value' => 60, 'max_order' => 400);
        update_option('wc_loyalty_reward_tiers', serialize($reward_tiers));
        
        // Generate 60% discount coupon with 400 lei limit
        $coupon_code = $this->generate_discount_coupon($user_id, 60, 400);
        
        if ($coupon_code) {
            // Store coupon code
            $this->store_user_coupon(
                $user_id, 
                $coupon_code, 
                60, // 60% discount
                '+30 days', 
                2000, // tier
                'discount' // coupon type
            );
            
            // Store notification
            $this->store_user_notification(
                $user_id, 
                'discount', 
                __('You\'ve earned a 60% discount for orders up to 400 lei! Use the coupon code at checkout.', 'wc-loyalty-gamification')
            );
            
            // Mark reward as available for the current cycle
            $cycle_level = WC_Loyalty()->points->get_user_cycle_level($user_id);
            $discount_key = 'discount60_available_cycle_' . $cycle_level;
            update_user_meta($user_id, $discount_key, 'yes');
        }
    }
    
    /**
     * Check reward eligibility.
     *
     * @param int $user_id User ID
     * @param int $points Current points
     */
    public function check_reward_eligibility($user_id, $points) {
        $reward_tiers = unserialize(get_option('wc_loyalty_reward_tiers', 'a:0:{}'));
        
        // Safety check - ensure reward_tiers is an array
        if (!is_array($reward_tiers)) {
            $reward_tiers = array();
        }
        
        $claimed_rewards = $this->get_rewards_claimed($user_id);
        
        // Safety check - ensure claimed_rewards is an array
        if (!is_array($claimed_rewards)) {
            $claimed_rewards = array();
        }
        
        foreach ($reward_tiers as $tier => $reward) {
            // If user has enough points and hasn't claimed this reward yet
            if ($points >= $tier && !isset($claimed_rewards[$tier])) {
                $this->process_reward($user_id, $tier, $reward);
                
                // Mark reward as claimed
                $claimed_rewards[$tier] = current_time('mysql');
                $this->update_rewards_claimed($user_id, $claimed_rewards);
                
                // Log this to help with debugging
                error_log("Reward tier $tier claimed by user $user_id");
            }
        }
    }
    
    /**
     * Process reward.
     *
     * @param int $user_id User ID
     * @param int $tier Points tier
     * @param array $reward Reward data
     */
    private function process_reward($user_id, $tier, $reward) {
        $user = get_user_by('id', $user_id);
        
        if (!$user) {
            error_log("Cannot process reward: User ID $user_id not found");
            return;
        }
        
        // Check if user already has an active coupon for this tier
        $user_coupons = $this->get_user_coupons($user_id);
        $tier_already_processed = false;
        
        if (is_array($user_coupons)) {
            foreach ($user_coupons as $coupon) {
                if (isset($coupon['tier']) && $coupon['tier'] == $tier) {
                    $tier_already_processed = true;
                    break;
                }
            }
        }
        
        // Only proceed if this tier hasn't been processed yet
        if (!$tier_already_processed) {
            if (!isset($reward['type'])) {
                error_log("Reward type not specified for tier $tier");
                return;
            }
            
            switch ($reward['type']) {
                case 'discount':
                    // Get discount value with fallback
                    $discount_value = isset($reward['value']) ? intval($reward['value']) : 10;
                    
                    // Generate coupon code
                    $coupon_code = $this->generate_discount_coupon($user_id, $discount_value);
                    
                    if ($coupon_code) {
                        // Store the coupon code with tier information
                        $this->store_user_coupon($user_id, $coupon_code, $discount_value, '+30 days', $tier);
                    }
                    break;
                    
                case 'free_shipping':
                    // Enable free shipping
                    update_user_meta($user_id, '_wc_loyalty_free_shipping', 'yes');
                    
                    // Store notification
                    $this->store_user_notification(
                        $user_id, 
                        'free_shipping', 
                        __('You\'ve earned free shipping on your next order!', 'wc-loyalty-gamification')
                    );
                    break;
                    
                case 'free_product':
                    // Store notification
                    $this->store_user_notification(
                        $user_id, 
                        'free_product', 
                        __('You\'ve earned a free product! See below to claim it.', 'wc-loyalty-gamification')
                    );
                    break;
                    
                default:
                    error_log("Unknown reward type: {$reward['type']} for tier $tier");
                    break;
            }
        }
    }

    /**
     * Store user coupon for frontend display
     * 
     * @param int $user_id User ID
     * @param string $coupon_code Coupon code
     * @param int $discount_value Discount percentage
     * @param string $expiry Expiry period (e.g. '+30 days')
     * @param int|null $tier Tier level
     */
    private function store_user_coupon($user_id, $coupon_code, $discount_value, $expiry = '+30 days', $tier = null) {
        $user_coupons = get_user_meta($user_id, '_wc_loyalty_coupons', true);
        
        if (!is_array($user_coupons)) {
            $user_coupons = array();
        }
        
        // Add the new coupon
        $user_coupons[] = array(
            'code' => $coupon_code,
            'discount' => $discount_value,
            'created' => current_time('mysql'),
            'expires' => date('Y-m-d H:i:s', strtotime($expiry)),
            'is_used' => false,
            'tier' => $tier  // Store tier information
        );
        
        // Save the updated coupons
        update_user_meta($user_id, '_wc_loyalty_coupons', $user_coupons);
    }

    /**
     * Store user notification for frontend display
     * 
     * @param int $user_id User ID
     * @param string $type Notification type
     * @param string $message Notification message
     */
    private function store_user_notification($user_id, $type, $message) {
        $notifications = get_user_meta($user_id, '_wc_loyalty_notifications', true);
        
        if (!is_array($notifications)) {
            $notifications = array();
        }
        
        // Add the new notification
        $notifications[] = array(
            'type' => $type,
            'message' => $message,
            'created' => current_time('mysql'),
            'is_read' => false
        );
        
        // Save the updated notifications
        update_user_meta($user_id, '_wc_loyalty_notifications', $notifications);
    }

    /**
     * Get user coupons.
     *
     * @param int $user_id User ID
     * @return array Active coupons
     */
    public function get_user_coupons($user_id) {
        $coupons = get_user_meta($user_id, '_wc_loyalty_coupons', true);
        return is_array($coupons) ? $coupons : array();
    }

    /**
     * Get user notifications.
     *
     * @param int $user_id User ID
     * @return array Notifications
     */
    public function get_user_notifications($user_id) {
        $notifications = get_user_meta($user_id, '_wc_loyalty_notifications', true);
        return is_array($notifications) ? $notifications : array();
    }
    /**
 * Generate discount coupon.
 *
 * @param int $user_id User ID
 * @param int $discount_value Discount percentage
 * @param int $max_order Maximum order value (0 for no limit)
 * @return string|false Coupon code or false on failure
 */
private function generate_discount_coupon($user_id, $discount_value, $max_order = 0) {
    $user = get_user_by('id', $user_id);
    
    if (!$user) {
        error_log("Cannot generate discount coupon: User ID $user_id not found");
        return false;
    }
    
    // Generate a unique coupon code
    $coupon_code = 'LOYALTY' . strtoupper(substr(md5(uniqid(mt_rand(), true) . $user_id . time()), 0, 8));
    
    // Create coupon post
    $coupon = array(
        'post_title' => $coupon_code,
        'post_content' => '',
        'post_status' => 'publish',
        'post_author' => 1,
        'post_type' => 'shop_coupon'
    );
    
    $coupon_id = wp_insert_post($coupon);
    
    if ($coupon_id && !is_wp_error($coupon_id)) {
        // Set coupon data
        update_post_meta($coupon_id, 'discount_type', 'percent');
        update_post_meta($coupon_id, 'coupon_amount', $discount_value);
        update_post_meta($coupon_id, 'individual_use', 'yes');
        update_post_meta($coupon_id, 'usage_limit', '1');
        update_post_meta($coupon_id, 'usage_limit_per_user', '1');
        update_post_meta($coupon_id, 'expiry_date', date('Y-m-d', strtotime('+30 days')));
        update_post_meta($coupon_id, 'apply_before_tax', 'yes');
        update_post_meta($coupon_id, 'free_shipping', 'no');
        update_post_meta($coupon_id, '_wc_loyalty_coupon', 'yes'); // Mark as loyalty coupon
        update_post_meta($coupon_id, 'customer_email', array($user->user_email));
        
        // Set maximum spend if specified
        if ($max_order > 0) {
            update_post_meta($coupon_id, 'maximum_amount', $max_order);
        }
        
        return $coupon_code;
    }
    
    error_log("Failed to create WooCommerce coupon for user ID $user_id");
    return false;
}
    
    /**
     * Send reward email.
     *
     * @param string $email Customer email
     * @param string $reward_type Type of reward
     * @param array $data Additional data
     * @return bool Success or failure
     */
    public function send_reward_email($email, $reward_type, $data = array()) {
        $subject = '';
        $message = '';
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        // Get email template
        $template_path = WC_LOYALTY_PLUGIN_DIR . 'templates/emails/reward-' . $reward_type . '.php';
        
        if (!file_exists($template_path)) {
            return false;
        }
        
        ob_start();
        include $template_path;
        $message = ob_get_clean();
        
        // Replace placeholders
        $message = $this->replace_email_placeholders($message, $reward_type, $data);
        
        // Set subject based on reward type
        switch ($reward_type) {
            case 'discount':
                $subject = sprintf(__('You\'ve earned a %d%% discount!', 'wc-loyalty-gamification'), $data['discount']);
                break;
                
            case 'free_shipping':
                $subject = __('You\'ve earned free shipping!', 'wc-loyalty-gamification');
                break;
        }
        
        return wp_mail($email, $subject, $message, $headers);
    }
    
    /**
     * Replace email placeholders with actual data.
     *
     * @param string $content Email content
     * @param string $reward_type Type of reward
     * @param array $data Additional data
     * @return string Processed content
     */
    private function replace_email_placeholders($content, $reward_type, $data) {
        $placeholders = array(
            '{site_url}' => home_url(),
            '{site_name}' => get_bloginfo('name'),
            '{account_url}' => wc_get_account_endpoint_url('loyalty-points')
        );
        
        // Add reward-specific placeholders
        switch ($reward_type) {
            case 'discount':
                $placeholders['{discount_amount}'] = isset($data['discount']) ? intval($data['discount']) : 0;
                $placeholders['{coupon_code}'] = isset($data['coupon_code']) ? $data['coupon_code'] : '';
                $placeholders['{expiry_date}'] = date_i18n(get_option('date_format'), strtotime('+30 days'));
                break;
                
            case 'free_shipping':
                // No additional placeholders
                break;
        }
        
        return str_replace(array_keys($placeholders), array_values($placeholders), $content);
    }
    
    /**
     * Get rewards claimed.
     *
     * @param int $user_id User ID
     * @return array Claimed rewards
     */
    public function get_rewards_claimed($user_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wc_loyalty_points';
        $rewards = $wpdb->get_var($wpdb->prepare(
            "SELECT rewards_claimed FROM $table_name WHERE user_id = %d",
            $user_id
        ));
        
        if ($rewards === null) {
            return array();
        }
        
        $unserialized = @unserialize($rewards);
        return is_array($unserialized) ? $unserialized : array();
    }
    
    /**
     * Update rewards claimed.
     *
     * @param int $user_id User ID
     * @param array $rewards Claimed rewards
     * @return bool Success or failure
     */
    public function update_rewards_claimed($user_id, $rewards) {
        if (!is_array($rewards)) {
            error_log("Cannot update rewards: rewards data is not an array for user $user_id");
            return false;
        }
        
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wc_loyalty_points';
        $serialized_rewards = serialize($rewards);
        
        // Check if user record exists first
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE user_id = %d",
            $user_id
        ));
        
        if ($exists) {
            return $wpdb->update(
                $table_name,
                array('rewards_claimed' => $serialized_rewards),
                array('user_id' => $user_id),
                array('%s'),
                array('%d')
            );
        } else {
            // Insert a new record if none exists
            return $wpdb->insert(
                $table_name,
                array(
                    'user_id' => $user_id,
                    'points' => 0,
                    'points_history' => serialize(array()),
                    'rewards_claimed' => $serialized_rewards,
                    'update_date' => current_time('mysql')
                ),
                array('%d', '%d', '%s', '%s', '%s')
            );
        }
    }
    
    /**
     * Get next reward tier.
     *
     * @param int $current_points Current points
     * @param array|null $reward_tiers Available reward tiers
     * @return int|null Next tier or null if no tiers available
     */
    public function get_next_reward_tier($current_points, $reward_tiers = null) {
        if ($reward_tiers === null) {
            $reward_tiers = unserialize(get_option('wc_loyalty_reward_tiers', 'a:0:{}'));
        }
        
        // Safety check - ensure reward_tiers is an array
        if (!is_array($reward_tiers)) {
            $reward_tiers = array();
        }
        
        $next_tier = null;
        $next_points = PHP_INT_MAX;
        
        // First check if we're close to 2000 (the special premium discount tier)
        if ($current_points < 2000 && !isset($reward_tiers[2000])) {
            // If no 2000 tier exists but we're below 2000, use that as the target
            $next_tier = 2000;
            $next_points = 2000;
        }
        
        foreach ($reward_tiers as $tier => $reward) {
            if ($tier > $current_points && $tier < $next_points) {
                $next_tier = $tier;
                $next_points = $tier;
            }
        }
        
        return $next_tier;
    }
    
    /**
     * Apply free shipping if user has earned it.
     *
     * @param array $rates Shipping rates
     * @param array $package Shipping package
     * @return array Modified shipping rates
     */
    public function apply_free_shipping($rates, $package) {
        if (!is_user_logged_in() || !is_array($rates)) {
            return $rates;
        }
        
        $user_id = get_current_user_id();
        $free_shipping = get_user_meta($user_id, '_wc_loyalty_free_shipping', true);
        
        if ($free_shipping === 'yes') {
            foreach ($rates as $rate_id => $rate) {
                if (is_object($rate) && isset($rate->method_id) && $rate->method_id === 'flat_rate') {
                    $rates[$rate_id]->cost = 0;
                    $rates[$rate_id]->label .= ' ' . __('(Loyalty Reward)', 'wc-loyalty-gamification');
                }
            }
            
            // Remove the free shipping flag after use
            delete_user_meta($user_id, '_wc_loyalty_free_shipping');
        }
        
        return $rates;
    }
    
    /**
     * Add notification for upcoming reward.
     */
    public function product_reward_notice() {
        if (!is_user_logged_in()) {
            return;
        }
        
        $user_id = get_current_user_id();
        $total_points = WC_Loyalty()->points->get_user_points($user_id);
        $display_points = WC_Loyalty()->points->get_user_display_points($user_id);
        $reward_tiers = unserialize(get_option('wc_loyalty_reward_tiers'));
        
        // Special case for exactly 2000 points - premium discount notification
        if ($display_points == 2000) {
            wc_print_notice(
                sprintf(
                    __('Congratulations! You\'ve reached 2000 points and earned a 60%% discount coupon for orders up to 400 lei! <a href="%s">View Your Coupons</a>', 'wc-loyalty-gamification'),
                    wc_get_account_endpoint_url('loyalty-rewards')
                ),
                'success'
            );
            return;
        }
        
        // Normal next tier calculation
        $next_tier = $this->get_next_reward_tier($display_points, $reward_tiers);
        
        if ($next_tier) {
            $points_needed = $next_tier - $display_points;
            $reward_type = '';
            
            if ($next_tier == 2000) {
                $reward_type = __('a 60% discount coupon', 'wc-loyalty-gamification');
            } else if (isset($reward_tiers[$next_tier])) {
                switch ($reward_tiers[$next_tier]['type']) {
                    case 'discount':
                        $reward_type = sprintf(__('%d%% discount', 'wc-loyalty-gamification'), $reward_tiers[$next_tier]['value']);
                        break;
                    case 'free_shipping':
                        $reward_type = __('free shipping', 'wc-loyalty-gamification');
                        break;
                }
            }
            
            wc_print_notice(
                sprintf(
                    __('You\'re only %d points away from earning %s with our loyalty program!', 'wc-loyalty-gamification'),
                    $points_needed,
                    $reward_type
                ),
                'notice'
            );
        }
    }

    /**
     * Update coupon status to "used".
     * 
     * @param string $coupon_code The coupon code that was applied
     * @param int $user_id The user ID
     */
    public function mark_coupon_as_used($coupon_code, $user_id) {
        $user_coupons = $this->get_user_coupons($user_id);
        $updated = false;
        
        foreach ($user_coupons as $key => $coupon) {
            if ($coupon['code'] === $coupon_code) {
                $user_coupons[$key]['is_used'] = true;
                $updated = true;
                break;
            }
        }
        
        if ($updated) {
            // Save updated coupons
            update_user_meta($user_id, '_wc_loyalty_coupons', $user_coupons);
        }
    }


    /**
 * Handle applied coupon
 *
 * @param string $coupon_code The coupon code
 */
public function handle_applied_coupon($coupon_code) {
    if (!is_user_logged_in()) {
        return;
    }
    
    $user_id = get_current_user_id();
    
    // Check if this is a loyalty coupon
    $user_coupons = $this->get_user_coupons($user_id);
    
    if (is_array($user_coupons)) {
        foreach ($user_coupons as $coupon) {
            if (isset($coupon['code']) && $coupon['code'] === $coupon_code && !$coupon['is_used']) {
                // Mark the coupon as used
                $this->mark_coupon_as_used($coupon_code, $user_id);
                break;
            }
        }
    }
}
}