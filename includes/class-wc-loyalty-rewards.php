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
    public function __construct() {
        // Check reward eligibility after points update
        add_action('wc_loyalty_points_updated', array($this, 'check_reward_eligibility'), 10, 2);
        
        // Apply free shipping if user has earned it
        add_filter('woocommerce_package_rates', array($this, 'apply_free_shipping'), 100, 2);
        
        // Add notification for upcoming reward
        add_action('woocommerce_before_single_product', array($this, 'product_reward_notice'));
    }
    
    /**
     * Check reward eligibility.
     *
     * @param int $user_id User ID
     * @param int $points Current points
     */
    public function check_reward_eligibility($user_id, $points) {
        $reward_tiers = unserialize(get_option('wc_loyalty_reward_tiers'));
        $claimed_rewards = $this->get_rewards_claimed($user_id);
        
        foreach ($reward_tiers as $tier => $reward) {
            // If user has enough points and hasn't claimed this reward yet
            if ($points >= $tier && !isset($claimed_rewards[$tier])) {
                $this->process_reward($user_id, $tier, $reward);
                
                // Mark reward as claimed
                $claimed_rewards[$tier] = current_time('mysql');
                $this->update_rewards_claimed($user_id, $claimed_rewards);
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
        
        switch ($reward['type']) {
            case 'discount':
                // Generate coupon code
                $coupon_code = $this->generate_discount_coupon($user_id, $reward['value']);
                
                // Send email with coupon code
                $this->send_reward_email($user->user_email, 'discount', array(
                    'discount' => $reward['value'],
                    'coupon_code' => $coupon_code
                ));
                break;
                
            case 'free_shipping':
                // Enable free shipping for next order
                update_user_meta($user_id, '_wc_loyalty_free_shipping', 'yes');
                
                // Send email notification
                $this->send_reward_email($user->user_email, 'free_shipping');
                break;
                
            case 'free_product':
                // Send email with link to claim free product
                $this->send_reward_email($user->user_email, 'free_product');
                break;
        }
    }
    
    /**
     * Generate discount coupon.
     *
     * @param int $user_id User ID
     * @param int $discount_value Discount percentage
     * @return string Coupon code
     */
    private function generate_discount_coupon($user_id, $discount_value) {
        $user = get_user_by('id', $user_id);
        $coupon_code = 'LOYALTY' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
        
        $coupon = array(
            'post_title' => $coupon_code,
            'post_content' => '',
            'post_status' => 'publish',
            'post_author' => 1,
            'post_type' => 'shop_coupon'
        );
        
        $coupon_id = wp_insert_post($coupon);
        
        if ($coupon_id) {
            // Set coupon data
            update_post_meta($coupon_id, 'discount_type', 'percent');
            update_post_meta($coupon_id, 'coupon_amount', $discount_value);
            update_post_meta($coupon_id, 'individual_use', 'yes');
            update_post_meta($coupon_id, 'usage_limit', '1');
            update_post_meta($coupon_id, 'expiry_date', date('Y-m-d', strtotime('+30 days')));
            update_post_meta($coupon_id, 'apply_before_tax', 'yes');
            update_post_meta($coupon_id, 'free_shipping', 'no');
            update_post_meta($coupon_id, 'customer_email', array($user->user_email));
        }
        
        return $coupon_code;
    }
    
    /**
     * Send reward email.
     *
     * @param string $email Customer email
     * @param string $reward_type Type of reward
     * @param array $data Additional data
     */
    public function send_reward_email($email, $reward_type, $data = array()) {
        $subject = '';
        $message = '';
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        // Get email template
        ob_start();
        include WC_LOYALTY_PLUGIN_DIR . 'templates/emails/reward-' . $reward_type . '.php';
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
                
            case 'free_product':
                $subject = __('You\'ve earned a free product!', 'wc-loyalty-gamification');
                break;
        }
        
        wp_mail($email, $subject, $message, $headers);
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
                $placeholders['{discount_amount}'] = $data['discount'];
                $placeholders['{coupon_code}'] = $data['coupon_code'];
                $placeholders['{expiry_date}'] = date_i18n(get_option('date_format'), strtotime('+30 days'));
                break;
                
            case 'free_shipping':
                // No additional placeholders
                break;
                
            case 'free_product':
                $placeholders['{free_product_url}'] = wc_get_account_endpoint_url('loyalty-points') . '#claim-free-product';
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
        
        return $rewards ? unserialize($rewards) : array();
    }
    
    /**
     * Update rewards claimed.
     *
     * @param int $user_id User ID
     * @param array $rewards Claimed rewards
     * @return bool Success or failure
     */
    public function update_rewards_claimed($user_id, $rewards) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wc_loyalty_points';
        return $wpdb->update(
            $table_name,
            array('rewards_claimed' => serialize($rewards)),
            array('user_id' => $user_id),
            array('%s'),
            array('%d')
        );
    }
    
    /**
     * Get next reward tier.
     *
     * @param int $current_points Current points
     * @param array $reward_tiers Available reward tiers
     * @return int|null Next tier or null if no tiers available
     */
    public function get_next_reward_tier($current_points, $reward_tiers = null) {
        if ($reward_tiers === null) {
            $reward_tiers = unserialize(get_option('wc_loyalty_reward_tiers'));
        }
        
        $next_tier = null;
        $next_points = PHP_INT_MAX;
        
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
        if (!is_user_logged_in()) {
            return $rates;
        }
        
        $user_id = get_current_user_id();
        $free_shipping = get_user_meta($user_id, '_wc_loyalty_free_shipping', true);
        
        if ($free_shipping == 'yes') {
            foreach ($rates as $rate_id => $rate) {
                if ($rate->method_id == 'flat_rate') {
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
        $points = WC_Loyalty()->points->get_user_points($user_id);
        $reward_tiers = unserialize(get_option('wc_loyalty_reward_tiers'));
        $next_tier = $this->get_next_reward_tier($points, $reward_tiers);
        
        if ($next_tier) {
            $points_needed = $next_tier - $points;
            $reward_type = '';
            
            switch ($reward_tiers[$next_tier]['type']) {
                case 'discount':
                    $reward_type = sprintf(__('%d%% discount', 'wc-loyalty-gamification'), $reward_tiers[$next_tier]['value']);
                    break;
                case 'free_shipping':
                    $reward_type = __('free shipping', 'wc-loyalty-gamification');
                    break;
                case 'free_product':
                    $reward_type = __('a free product', 'wc-loyalty-gamification');
                    break;
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
} // Added the missing closing brace for the class