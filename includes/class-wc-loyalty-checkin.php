<?php
/**
 * WC Loyalty Check-in System
 *
 * Handles daily check-in functionality.
 */

if (!defined('WPINC')) {
    die;
}

/**
 * WC_Loyalty_Checkin Class
 */
class WC_Loyalty_Checkin {
    
    /**
     * Constructor.
     */
    public function __construct() {
        // Add AJAX handlers
        add_action('wp_ajax_wc_loyalty_daily_checkin', array($this, 'process_daily_checkin'));
        
        // Add settings for checkin points
        add_action('admin_init', array($this, 'register_checkin_settings'));
        
        // Add check-in widget to the modal
        add_action('wc_loyalty_modal_after_points', array($this, 'render_checkin_widget'));
    }
    
    /**
     * Register check-in settings.
     */
    public function register_checkin_settings() {
        register_setting('wc_loyalty_settings', 'wc_loyalty_base_checkin_points');
        register_setting('wc_loyalty_settings', 'wc_loyalty_streak_multiplier');
        register_setting('wc_loyalty_settings', 'wc_loyalty_milestone_rewards', array(
            'sanitize_callback' => array($this, 'sanitize_milestone_rewards'),
        ));
    }
    
    /**
     * Sanitize milestone rewards.
     *
     * @param string $input JSON string of milestone rewards
     * @return string Sanitized JSON string
     */
    public function sanitize_milestone_rewards($input) {
        if (empty($input)) {
            return json_encode(array(
                '7' => 50,   // 7-day streak: 50 bonus points
                '30' => 200, // 30-day streak: 200 bonus points
                '90' => 500, // 90-day streak: 500 bonus points
                '365' => 2000 // 365-day streak: 2000 bonus points
            ));
        }
        
        return $input;
    }
    
    /**
     * Process daily check-in.
     */
    public function process_daily_checkin() {
        // Verify nonce
        check_ajax_referer('wc_loyalty_nonce', 'nonce');
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in to check in.', 'wc-loyalty-gamification')
            ));
            return;
        }
        
        $user_id = get_current_user_id();
        $today = current_time('Y-m-d');
        
        // Check if user already checked in today - IMPROVED CHECK
        if ($this->has_checked_in_today($user_id)) {
            wp_send_json_error(array(
                'message' => __('You have already checked in today. Come back tomorrow!', 'wc-loyalty-gamification')
            ));
            return;
        }
        
        // Process check-in and calculate streak
        $streak_info = $this->calculate_streak($user_id);
        $streak_count = $streak_info['streak_count'];
        
        // Get points configuration from settings - CONFIGURABLE POINTS
        $base_points = intval(get_option('wc_loyalty_base_checkin_points', 5));
        $streak_multiplier = floatval(get_option('wc_loyalty_streak_multiplier', 0.1));
        
        // Calculate points with streak bonus
        $streak_bonus = floor($base_points * $streak_multiplier * ($streak_count - 1));
        $points_earned = $base_points + $streak_bonus;
        
        // Check for milestone rewards
        $milestone_rewards = json_decode(get_option('wc_loyalty_milestone_rewards', '{}'), true);
        if (empty($milestone_rewards) || !is_array($milestone_rewards)) {
            $milestone_rewards = array(
                '7' => 50,   // 7-day streak: 50 bonus points
                '30' => 200, // 30-day streak: 200 bonus points
                '90' => 500, // 90-day streak: 500 bonus points
                '365' => 2000 // 365-day streak: 2000 bonus points
            );
        }
        
        $milestone_bonus = 0;
        $milestone_reached = '';
        
        if (isset($milestone_rewards[$streak_count])) {
            $milestone_bonus = intval($milestone_rewards[$streak_count]);
            $milestone_reached = $streak_count;
            $points_earned += $milestone_bonus;
        }
        
        // Save check-in data
        $saved = $this->save_checkin($user_id, $streak_count, $points_earned);
        
        if (!$saved) {
            wp_send_json_error(array(
                'message' => __('Failed to save check-in data. Please try again.', 'wc-loyalty-gamification')
            ));
            return;
        }
        
        // Add points to user account
        WC_Loyalty()->points->add_points($user_id, $points_earned, sprintf(
            __('Daily check-in - Day %d streak - %d points', 'wc-loyalty-gamification'),
            $streak_count,
            $points_earned
        ));
        
        // Prepare response
        $response = array(
            'success' => true,
            'message' => sprintf(
                __('Check-in successful! You earned %d points.', 'wc-loyalty-gamification'),
                $points_earned
            ),
            'streak_count' => $streak_count,
            'points_earned' => $points_earned,
            'next_checkin' => date('Y-m-d', strtotime('tomorrow')),
        );
        
        if ($milestone_reached) {
            $response['milestone_reached'] = $milestone_reached;
            $response['milestone_bonus'] = $milestone_bonus;
            $response['message'] = sprintf(
                __('Congratulations! You reached a %d-day streak and earned a bonus of %d points!', 'wc-loyalty-gamification'),
                $milestone_reached,
                $milestone_bonus
            );
        }
        
        wp_send_json_success($response);
    }
    
    /**
     * Check if user has already checked in today.
     *
     * @param int $user_id User ID
     * @return bool True if already checked in
     */
    public function has_checked_in_today($user_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wc_loyalty_checkins';
        $today = current_time('Y-m-d');
        
        // Use direct database query for better performance
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND check_date = %s",
            $user_id,
            $today
        ));
        
        return (int)$count > 0;
    }
    
    /**
     * Calculate current streak.
     *
     * @param int $user_id User ID
     * @return array Streak information
     */
    public function calculate_streak($user_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wc_loyalty_checkins';
        $yesterday = date('Y-m-d', strtotime('yesterday', current_time('timestamp')));
        
        // Check if there was a check-in yesterday
        $last_checkin = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d AND check_date = %s",
            $user_id,
            $yesterday
        ));
        
        if ($last_checkin) {
            // Continue streak
            return array(
                'streak_count' => $last_checkin->streak_count + 1,
                'is_continued' => true
            );
        } else {
            // Check if there was a check-in within the last 7 days (grace period)
            $grace_period_start = date('Y-m-d', strtotime('-7 days', current_time('timestamp')));
            $last_checkin_within_grace = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE user_id = %d AND check_date >= %s ORDER BY check_date DESC LIMIT 1",
                $user_id,
                $grace_period_start
            ));
            
            if ($last_checkin_within_grace) {
                // Maintain streak with grace period
                return array(
                    'streak_count' => $last_checkin_within_grace->streak_count + 1,
                    'is_continued' => true,
                    'had_grace_period' => true
                );
            }
        }
        
        // Start new streak
        return array(
            'streak_count' => 1,
            'is_continued' => false
        );
    }
    
    /**
     * Save check-in data.
     *
     * @param int $user_id User ID
     * @param int $streak_count Streak count
     * @param int $points_earned Points earned
     * @return bool Success or failure
     */
    public function save_checkin($user_id, $streak_count, $points_earned) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wc_loyalty_checkins';
        $today = current_time('Y-m-d');
        
        // Make sure user hasn't already checked in (safety check)
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE user_id = %d AND check_date = %s",
            $user_id,
            $today
        ));
        
        if ($existing) {
            // User already checked in today
            return false;
        }
        
        return $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'check_date' => $today,
                'streak_count' => $streak_count,
                'points_earned' => $points_earned,
                'update_date' => current_time('mysql')
            ),
            array('%d', '%s', '%d', '%d', '%s')
        );
    }
    
    /**
     * Get user's current streak information.
     *
     * @param int $user_id User ID
     * @return array Streak information
     */
    public function get_user_streak_info($user_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'wc_loyalty_checkins';
        $today = current_time('Y-m-d');
        
        // Check if user checked in today
        $today_checkin = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d AND check_date = %s",
            $user_id,
            $today
        ));
        
        if ($today_checkin) {
            return array(
                'streak_count' => $today_checkin->streak_count,
                'has_checked_in_today' => true,
                'next_checkin' => date('Y-m-d', strtotime('tomorrow')),
                'points_earned_today' => $today_checkin->points_earned
            );
        }
        
        // Get last check-in
        $last_checkin = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d ORDER BY check_date DESC LIMIT 1",
            $user_id
        ));
        
        if ($last_checkin) {
            $yesterday = date('Y-m-d', strtotime('yesterday', current_time('timestamp')));
            $grace_period_start = date('Y-m-d', strtotime('-7 days', current_time('timestamp')));
            
            // Check if streak is at risk
            $streak_status = 'active';
            if ($last_checkin->check_date < $grace_period_start) {
                $streak_status = 'broken';
            } elseif ($last_checkin->check_date < $yesterday) {
                $streak_status = 'at_risk';
            }
            
            return array(
                'streak_count' => ($streak_status == 'broken') ? 0 : $last_checkin->streak_count,
                'has_checked_in_today' => false,
                'last_checkin_date' => $last_checkin->check_date,
                'streak_status' => $streak_status,
                'next_checkin' => $today
            );
        }
        
        // No check-ins yet
        return array(
            'streak_count' => 0,
            'has_checked_in_today' => false,
            'streak_status' => 'new',
            'next_checkin' => $today
        );
    }
    
    /**
     * Render check-in widget in loyalty modal.
     */
    public function render_checkin_widget() {
        if (!is_user_logged_in()) {
            return;
        }
        
        $user_id = get_current_user_id();
        $streak_info = $this->get_user_streak_info($user_id);
        
        // Get points configuration from settings
        $base_points = intval(get_option('wc_loyalty_base_checkin_points', 5));
        $streak_multiplier = floatval(get_option('wc_loyalty_streak_multiplier', 0.1));
        
        // Validate milestone rewards first
        $milestone_rewards_json = get_option('wc_loyalty_milestone_rewards', '{}');
        if (empty($milestone_rewards_json) || $milestone_rewards_json === '{}') {
            $milestone_rewards = array(
                '7' => 50,
                '30' => 200,
                '90' => 500,
                '365' => 2000
            );
        } else {
            $milestone_rewards = json_decode($milestone_rewards_json, true);
            if (!is_array($milestone_rewards)) {
                $milestone_rewards = array(
                    '7' => 50,
                    '30' => 200,
                    '90' => 500,
                    '365' => 2000
                );
            }
        }
        
        // Get next milestone
        $next_milestone = null;
        $next_milestone_days = 0;
        
        if ($streak_info['streak_count'] > 0) {
            foreach ($milestone_rewards as $days => $bonus) {
                if ($days > $streak_info['streak_count']) {
                    if ($next_milestone === null || $days < $next_milestone) {
                        $next_milestone = $days;
                        $next_milestone_days = $days - $streak_info['streak_count'];
                    }
                }
            }
        }
        
        // Calculate today's potential points
        $potential_points = $base_points;
        if ($streak_info['streak_count'] > 0) {
            $potential_points += floor($base_points * $streak_multiplier * $streak_info['streak_count']);
        }
        
        // Check if today would reach a milestone
        $potential_milestone = false;
        $potential_milestone_bonus = 0;
        
        if (isset($milestone_rewards[$streak_info['streak_count'] + 1])) {
            $potential_milestone = true;
            $potential_milestone_bonus = $milestone_rewards[$streak_info['streak_count'] + 1];
            $potential_points += $potential_milestone_bonus;
        }
        
        include WC_LOYALTY_PLUGIN_DIR . 'templates/check-in-widget.php';
    }
}