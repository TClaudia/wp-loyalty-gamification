<?php
/**
 * WC Loyalty Points Management
 *
 * Handles all points-related functionality.
 */

if (!defined('WPINC')) {
    die;
}

/**
 * WC_Loyalty_Points Class
 */
class WC_Loyalty_Points {
    
    /**
     * Constructor.
     */
    public function __construct() {
        // Add points for purchases
        add_action('woocommerce_order_status_completed', array($this, 'add_points_for_purchase'));
        
        // Add points for product reviews
        add_action('comment_post', array($this, 'add_points_for_review'), 10, 3);
        
        // Refresh points when order status changes
        add_action('woocommerce_order_status_changed', array($this, 'refresh_points'), 10, 4);
        
        // Add notification about points on Thank You page
        add_action('woocommerce_thankyou', array($this, 'thankyou_message'), 10, 1);
    }
    
    /**
     * Add points for purchase.
     *
     * @param int $order_id Order ID
     */
    public function add_points_for_purchase($order_id) {
        // Avoid processing the same order multiple times
        $already_processed = get_post_meta($order_id, '_loyalty_points_awarded', true);
        if ($already_processed) {
            error_log("POINTS DEBUG: Order $order_id already processed for points");
            return;
        }
        
        $order = wc_get_order($order_id);
        $user_id = $order->get_user_id();
        
        // Only proceed if this is a registered user
        if ($user_id === 0) {
            return;
        }
        
        // Calculate points (points per euro spent)
        $order_total = $order->get_total();
        $points_per_euro = get_option('wc_loyalty_points_per_euro', 1);
        $points_earned = floor($order_total * $points_per_euro);
        
        error_log("POINTS DEBUG: Order $order_id - Total: $order_total, Points earned: $points_earned");
        
        // Add points to user account
        $this->add_points($user_id, $points_earned, sprintf(
            __('Order #%s - %d points for €%.2f spent', 'wc-loyalty-gamification'),
            $order_id,
            $points_earned,
            $order_total
        ));
        
        // Mark order as processed
        update_post_meta($order_id, '_loyalty_points_awarded', 'yes');
        
        // Add order note
        $order->add_order_note(sprintf(
            __('%d loyalty points awarded to customer.', 'wc-loyalty-gamification'),
            $points_earned
        ));
    }
    
    /**
     * Add points for product review.
     *
     * @param int $comment_ID Comment ID
     * @param int $comment_approved Approval status
     * @param array $comment_data Comment data
     */
    public function add_points_for_review($comment_ID, $comment_approved, $comment_data) {
        // Only proceed if this is an approved product review
        if ($comment_approved !== 1 || $comment_data['comment_type'] !== 'review') {
            return;
        }
        
        // Get user ID and product ID
        $user_id = $comment_data['user_id'];
        $product_id = $comment_data['comment_post_ID'];
        
        // Only proceed if user is logged in
        if ($user_id === 0) {
            return;
        }
        
        // Check if the user has purchased this product
        if (!$this->user_has_purchased_product($user_id, $product_id)) {
            return;
        }
        
        // Check if the user has already been awarded points for reviewing this product
        if ($this->has_review_points($user_id, $product_id)) {
            return;
        }
        
        // Add points for the review
        $points_for_review = get_option('wc_loyalty_points_for_review', 50);
        $this->add_points($user_id, $points_for_review, sprintf(
            __('Review for product #%s - %d points', 'wc-loyalty-gamification'),
            $product_id,
            $points_for_review
        ));
    }
    
    /**
     * Add points to user account.
     *
     * @param int $user_id User ID
     * @param int $points Points to add
     * @param string $description Points description
     * @return bool Success or failure
     */
    public function add_points($user_id, $points, $description = '') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wc_loyalty_points';
        $user_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE user_id = %d",
            $user_id
        ));
        
        // New history entry
        $history_entry = array(
            'date' => current_time('mysql'),
            'points' => $points,
            'description' => $description
        );
        
        // If user exists, update their points
        if ($user_exists) {
            // Get current points and history
            $current_points = $this->get_user_points($user_id);
            $points_history = $this->get_points_history($user_id);
            
            // Calculate new points total
            $new_points = $current_points + $points;
            if ($new_points < 0) {
                $new_points = 0; // Don't allow negative points
            }
            
            // Add history entry
            if (!empty($points_history)) {
                $points_history[] = $history_entry;
            } else {
                $points_history = array($history_entry);
            }
            
            // Check if user reached 2000 points
            $before_cycle = floor($current_points / 2000);
            $after_cycle = floor($new_points / 2000);
            
            // If cycle increased, trigger special event
            if ($after_cycle > $before_cycle) {
                // Store the cycle level
                update_user_meta($user_id, '_wc_loyalty_cycle_level', $after_cycle);
                
                // Trigger action for reaching 2000 points
                do_action('wc_loyalty_reached_2000_points', $user_id);
            }
            
            // Update database
            $updated = $wpdb->update(
                $table_name,
                array(
                    'points' => $new_points,
                    'points_history' => serialize($points_history),
                    'update_date' => current_time('mysql')
                ),
                array('user_id' => $user_id),
                array('%d', '%s', '%s'),
                array('%d')
            );
            
            if ($updated) {
                // Trigger action for points update
                do_action('wc_loyalty_points_updated', $user_id, $new_points);
            }
            
            return $updated;
        } else {
            // User doesn't exist, create a new record
            $inserted = $wpdb->insert(
                $table_name,
                array(
                    'user_id' => $user_id,
                    'points' => max(0, $points), // Don't allow negative points for new users
                    'points_history' => serialize(array($history_entry)),
                    'update_date' => current_time('mysql')
                ),
                array('%d', '%d', '%s', '%s')
            );
            
            if ($inserted) {
                // Trigger action for points update
                do_action('wc_loyalty_points_updated', $user_id, max(0, $points));
            }
            
            return $inserted;
        }
    }
    
    /**
     * Deduct points from user account.
     *
     * @param int $user_id User ID
     * @param int $points Points to deduct
     * @param string $description Points description
     * @return bool Success or failure
     */
    public function deduct_points($user_id, $points, $description = '') {
        return $this->add_points($user_id, -$points, $description);
    }
    
    /**
     * Get user points.
     *
     * @param int $user_id User ID
     * @return int Points amount
     */
    public function get_user_points($user_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wc_loyalty_points';
        $points = $wpdb->get_var($wpdb->prepare(
            "SELECT points FROM $table_name WHERE user_id = %d",
            $user_id
        ));
        
        return $points ? intval($points) : 0;
    }
    
    /**
     * Get points history.
     *
     * @param int $user_id User ID
     * @return array Points history
     */
    public function get_points_history($user_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wc_loyalty_points';
        $history = $wpdb->get_var($wpdb->prepare(
            "SELECT points_history FROM $table_name WHERE user_id = %d",
            $user_id
        ));
        
        return $history ? unserialize($history) : array();
    }
    
    /**
     * Check if user has purchased a specific product.
     *
     * @param int $user_id User ID
     * @param int $product_id Product ID
     * @return bool True if user has purchased product
     */
    public function user_has_purchased_product($user_id, $product_id) {
        global $wpdb;
        
        $customer_orders = $wpdb->get_col($wpdb->prepare("
            SELECT order_items.order_id
            FROM {$wpdb->prefix}woocommerce_order_items as order_items
            LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
            LEFT JOIN {$wpdb->posts} AS posts ON order_items.order_id = posts.ID
            WHERE posts.post_type = 'shop_order'
            AND posts.post_status IN ('wc-completed')
            AND order_items.order_item_type = 'line_item'
            AND order_item_meta.meta_key = '_product_id'
            AND order_item_meta.meta_value = %d
            AND posts.post_author = %d
        ", $product_id, $user_id));
        
        return !empty($customer_orders);
    }
    
    /**
     * Check if user already received points for a product review.
     *
     * @param int $user_id User ID
     * @param int $product_id Product ID
     * @return bool True if user already received points
     */
    public function has_review_points($user_id, $product_id) {
        $points_history = $this->get_points_history($user_id);
        
        foreach ($points_history as $entry) {
            if (strpos($entry['description'], "Review for product #{$product_id}") !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Refresh points when order status changes.
     *
     * @param int $order_id Order ID
     * @param string $from_status Previous status
     * @param string $to_status New status
     * @param WC_Order $order Order object
     */
    public function refresh_points($order_id, $from_status, $to_status, $order) {
        if ($to_status === 'completed') {
            // Check if points were already awarded for this order
            $already_processed = get_post_meta($order_id, '_loyalty_points_awarded', true);
            if (!$already_processed) {
                $this->add_points_for_purchase($order_id);
            } else {
                error_log("POINTS DEBUG: Skipping refresh_points for order $order_id - already processed");
            }
        }
    }
    
    /**
     * Display message about earned points on thank you page.
     *
     * @param int $order_id Order ID
     */
    public function thankyou_message($order_id) {
        $order = wc_get_order($order_id);
        $user_id = $order->get_user_id();
        
        // Only for registered users
        if ($user_id === 0) {
            return;
        }
        
        $points_per_euro = get_option('wc_loyalty_points_per_euro', 1);
        $order_total = $order->get_total();
        $points_earned = floor($order_total * $points_per_euro);
        
        echo '<div class="wc-loyalty-thankyou-message">';
        printf(
            __('You\'ll earn %d loyalty points when this order is completed. <a href="%s">View your points</a>', 'wc-loyalty-gamification'),
            $points_earned,
            wc_get_account_endpoint_url('loyalty-points')
        );
        echo '</div>';
    }
    
    /**
     * Get user loyalty tier.
     *
     * @param int $user_id User ID
     * @return string Tier key
     */
    public function get_user_tier($user_id) {
        $points = $this->get_user_points($user_id);
        $tiers = unserialize(get_option('wc_loyalty_tiers', 'a:0:{}'));
        
        // Default to lowest tier
        $current_tier = array_key_first($tiers);
        
        foreach ($tiers as $tier_key => $tier_data) {
            if ($points >= $tier_data['min_points']) {
                $current_tier = $tier_key;
            } else {
                break;
            }
        }
        
        return $current_tier;
    }
    
    /**
     * Get user tier data.
     *
     * @param int $user_id User ID
     * @return array Tier data
     */
    public function get_user_tier_data($user_id) {
        $tier_key = $this->get_user_tier($user_id);
        $tiers = unserialize(get_option('wc_loyalty_tiers', 'a:0:{}'));
        
        return isset($tiers[$tier_key]) ? $tiers[$tier_key] : array();
    }
    
    /**
     * Get next tier data.
     *
     * @param int $user_id User ID
     * @return array|null Next tier data or null if at highest tier
     */
    public function get_next_tier_data($user_id) {
        $current_tier = $this->get_user_tier($user_id);
        $tiers = unserialize(get_option('wc_loyalty_tiers', 'a:0:{}'));
        $tier_keys = array_keys($tiers);
        
        $current_index = array_search($current_tier, $tier_keys);
        
        // Check if there's a next tier
        if ($current_index !== false && isset($tier_keys[$current_index + 1])) {
            $next_tier_key = $tier_keys[$current_index + 1];
            return $tiers[$next_tier_key];
        }
        
        return null;
    }
    
    /**
     * Get user display points (with cycling at 2000).
     *
     * @param int $user_id User ID
     * @return int Display points (0-2000)
     */
    public function get_user_display_points($user_id) {
        $points = $this->get_user_points($user_id);
        
        // If points exceeds 2000, return the remainder
        if ($points >= 2000) {
            return $points % 2000;
        }
        
        return $points;
    }
    
    /**
     * Get user cycle level (how many times they've reached 2000 points).
     *
     * @param int $user_id User ID
     * @return int Cycle level
     */
    public function get_user_cycle_level($user_id) {
        // Use the stored cycle level for better accuracy
        $cycle_level = get_user_meta($user_id, '_wc_loyalty_cycle_level', true);
        
        // If no stored level, calculate it (for backward compatibility)
        if (empty($cycle_level)) {
            $points = $this->get_total_historical_points($user_id);
            $cycle_level = floor($points / 2000);
        }
        
        return intval($cycle_level);
    }
    
    /**
     * Get total historical points earned (including points that have been reset).
     *
     * @param int $user_id User ID
     * @return int Total historical points
     */
    public function get_total_historical_points($user_id) {
        $history = $this->get_points_history($user_id);
        $total = 0;
        
        foreach ($history as $entry) {
            if ($entry['points'] > 0) {
                $total += $entry['points'];
            }
        }
        
        return $total;
    }
}