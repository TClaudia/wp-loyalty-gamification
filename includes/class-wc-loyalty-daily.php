<?php
/**
 * WC Loyalty Daily Check-in - Versiune minimalistă
 * Adaugă și Milestone Rewards în contul utilizatorului
 */

if (!defined('WPINC')) {
    die;
}

/**
 * WC_Loyalty_Daily Class - Versiune minimalistă
 */
class WC_Loyalty_Daily {
    
    /**
     * Constructor.
     */
    public function __construct() {
        // Adaugă setările pentru daily check-in
        add_action('admin_init', array($this, 'add_daily_settings'));
        
        // Adaugă setările pentru milestone rewards
        add_action('admin_init', array($this, 'add_milestone_settings'));
        
        // AJAX handler pentru claim daily points
        add_action('wp_ajax_claim_daily_points', array($this, 'claim_daily_points'));
        
        // AJAX handler pentru claim milestone reward
        add_action('wp_ajax_claim_milestone_reward', array($this, 'claim_milestone_reward'));
        
        // Adaugă butonul minimalist lângă bara de progres
        add_action('wc_loyalty_after_points_display', array($this, 'display_minimalist_button'));
        
        // Adaugă milestone rewards în pagina My Account
        add_action('woocommerce_account_loyalty-points_endpoint', array($this, 'display_milestone_rewards'), 10);
        
        // Adaugă stiluri CSS inline
        add_action('wp_head', array($this, 'add_inline_styles'));
    }
    
    /**
     * Adaugă setări pentru daily check-in
     */
    public function add_daily_settings() {
        // Register settings
        register_setting('wc_loyalty_settings', 'wc_loyalty_daily_points');
        register_setting('wc_loyalty_settings', 'wc_loyalty_max_streak');
        register_setting('wc_loyalty_settings', 'wc_loyalty_streak_bonus');
        
        // Add settings field
        add_settings_field(
            'wc_loyalty_daily_points',
            __('Points for Daily Check-in', 'wc-loyalty-gamification'),
            array($this, 'daily_points_callback'),
            'wc_loyalty_settings',
            'wc_loyalty_general_section'
        );
    }
    
    /**
     * Adaugă setări pentru milestone rewards
     */
    public function add_milestone_settings() {
        // Adaugă setările pentru milestones
        register_setting('wc_loyalty_settings', 'wc_loyalty_milestones');
        
        // Adaugă câmpul de setări
        add_settings_field(
            'wc_loyalty_milestones',
            __('Milestone Rewards', 'wc-loyalty-gamification'),
            array($this, 'milestones_callback'),
            'wc_loyalty_settings',
            'wc_loyalty_general_section'
        );
    }
    
    /**
     * Callback pentru setările daily points
     */
    public function daily_points_callback() {
        $daily_points = get_option('wc_loyalty_daily_points', 5);
        $max_streak = get_option('wc_loyalty_max_streak', 5);
        $streak_bonus = get_option('wc_loyalty_streak_bonus', 10);
        
        echo '<div style="margin-bottom:10px;">';
        echo '<input type="number" name="wc_loyalty_daily_points" value="' . esc_attr($daily_points) . '" min="1" step="1" style="width:70px;" />';
        echo '<p class="description">' . __('Number of points awarded for daily check-in', 'wc-loyalty-gamification') . '</p>';
        echo '</div>';
        
        echo '<div style="margin-bottom:10px;">';
        echo '<label>' . __('Maximum Streak Days:', 'wc-loyalty-gamification') . '</label> ';
        echo '<input type="number" name="wc_loyalty_max_streak" value="' . esc_attr($max_streak) . '" min="1" step="1" style="width:70px;" />';
        echo '<p class="description">' . __('Maximum number of consecutive days for streak bonus', 'wc-loyalty-gamification') . '</p>';
        echo '</div>';
        
        echo '<div>';
        echo '<label>' . __('Streak Bonus Points:', 'wc-loyalty-gamification') . '</label> ';
        echo '<input type="number" name="wc_loyalty_streak_bonus" value="' . esc_attr($streak_bonus) . '" min="0" step="1" style="width:70px;" />';
        echo '<p class="description">' . __('Bonus points awarded when the maximum streak is reached', 'wc-loyalty-gamification') . '</p>';
        echo '</div>';
    }
    
    /**
     * Callback pentru setările de milestone
     */
    public function milestones_callback() {
        // Obține setările salvate sau folosește valorile implicite
        $milestones = get_option('wc_loyalty_milestones', array(
            '7' => 50,
            '30' => 200,
            '90' => 500,
            '365' => 2000
        ));
        
        echo '<div class="wc-loyalty-milestones-settings">';
        echo '<p>' . __('Set bonus points for different milestone periods:', 'wc-loyalty-gamification') . '</p>';
        
        echo '<div class="wc-loyalty-milestone-entries" style="display:grid; grid-template-columns: repeat(2, 1fr); gap:10px;">';
        
        // Milestone pentru 7 zile
        echo '<div class="wc-loyalty-milestone-entry">';
        echo '<label>' . __('7 days:', 'wc-loyalty-gamification') . '</label> ';
        echo '<input type="number" name="wc_loyalty_milestones[7]" value="' . esc_attr($milestones['7']) . '" min="0" step="1" style="width:70px;" />';
        echo '</div>';
        
        // Milestone pentru 30 zile
        echo '<div class="wc-loyalty-milestone-entry">';
        echo '<label>' . __('30 days:', 'wc-loyalty-gamification') . '</label> ';
        echo '<input type="number" name="wc_loyalty_milestones[30]" value="' . esc_attr($milestones['30']) . '" min="0" step="1" style="width:70px;" />';
        echo '</div>';
        
        // Milestone pentru 90 zile
        echo '<div class="wc-loyalty-milestone-entry">';
        echo '<label>' . __('90 days:', 'wc-loyalty-gamification') . '</label> ';
        echo '<input type="number" name="wc_loyalty_milestones[90]" value="' . esc_attr($milestones['90']) . '" min="0" step="1" style="width:70px;" />';
        echo '</div>';
        
        // Milestone pentru 365 zile
        echo '<div class="wc-loyalty-milestone-entry">';
        echo '<label>' . __('365 days:', 'wc-loyalty-gamification') . '</label> ';
        echo '<input type="number" name="wc_loyalty_milestones[365]" value="' . esc_attr($milestones['365']) . '" min="0" step="1" style="width:70px;" />';
        echo '</div>';
        
        echo '</div>'; // .wc-loyalty-milestone-entries
        
        echo '<p class="description">' . __('Bonus points awarded when user reaches the specified milestone.', 'wc-loyalty-gamification') . '</p>';
        echo '</div>'; // .wc-loyalty-milestones-settings
    }
    
    /**
     * Adaugă stiluri CSS inline
     */
    public function add_inline_styles() {
        // Doar pentru utilizatorii autentificați
        if (!is_user_logged_in()) {
            return;
        }
        
        ?>
        <style type="text/css">
        /* Daily Check-in buton minimalist */
        .wc-loyalty-check-in-button {
            position: absolute;
            bottom: -12px;
            right: -12px;
            background-color: #7952b3;
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
            z-index: 100;
        }
        
        .wc-loyalty-check-in-button:hover {
            background-color: #5e3d8f;
            transform: scale(1.1);
        }
        
        .wc-loyalty-check-in-button.disabled {
            background-color: #aaa;
            cursor: not-allowed;
        }
        
        /* Stiluri pentru Milestone Rewards */
        .wc-loyalty-milestone-rewards {
            margin: 20px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        
        .wc-loyalty-milestone-rewards h3 {
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 18px;
            color: #333;
        }
        
        .wc-loyalty-milestone-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        
        @media (min-width: 768px) {
            .wc-loyalty-milestone-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }
        
        .wc-loyalty-milestone-item {
            padding: 10px;
            background-color: white;
            border-radius: 4px;
            border-left: 3px solid #ddd;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .wc-loyalty-milestone-item.achieved {
            border-left-color: #7952b3;
        }
        
        .wc-loyalty-milestone-item.claimed {
            opacity: 0.7;
        }
        
        .wc-loyalty-milestone-days {
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        
        .wc-loyalty-milestone-points {
            color: #7952b3;
            font-weight: 700;
        }
        
        /* Notificări */
        .wc-loyalty-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 15px;
            background-color: white;
            border-left: 4px solid #7952b3;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            z-index: 9999;
            transition: all 0.3s ease;
            transform: translateX(110%);
        }
        
        .wc-loyalty-notification.show {
            transform: translateX(0);
        }
        
        .wc-loyalty-notification.success {
            border-left-color: #28a745;
        }
        
        .wc-loyalty-notification.error {
            border-left-color: #dc3545;
        }
        </style>
        <?php
    }
    
    /**
     * Afișează butonul minimalist pentru Daily Check-in
     */
public function display_minimalist_button() {
    if (!is_user_logged_in()) {
        return;
    }
    
    // Nu afișa Milestone Rewards aici, doar butonul Daily Check-in
    $user_id = get_current_user_id();
    $last_claim = get_user_meta($user_id, '_wc_loyalty_last_daily_claim', true);
    $today = date('Y-m-d');
    $claimed_today = ($last_claim === $today);
    
    // Adaugă butonul minimalist
    $disabled_class = $claimed_today ? 'disabled' : '';
    
    echo '<button class="wc-loyalty-check-in-button ' . esc_attr($disabled_class) . '" id="wc-loyalty-check-in" ' . ($claimed_today ? 'disabled' : '') . '>';
    echo '+';
    echo '</button>';
    
    // Adaugă script pentru funcționalitate
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('#wc-loyalty-check-in').on('click', function(e) {
            e.preventDefault();
            
            if ($(this).hasClass('disabled')) {
                showNotification('You have already claimed your daily points today!', 'info');
                return;
            }
            
            // Disable button to prevent multiple clicks
            $(this).prop('disabled', true);
            
            // Send AJAX request to claim points
            $.ajax({
                type: 'POST',
                url: wcLoyaltyData.ajaxurl,
                data: {
                    action: 'claim_daily_points',
                    nonce: wcLoyaltyData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        showNotification(response.data.message, 'success');
                        
                        // Update button
                        $('#wc-loyalty-check-in').addClass('disabled');
                        
                        // Update points display
                        if (response.data.points) {
                            $('.wc-loyalty-points-count').text(response.data.points);
                        }
                    } else {
                        // Show error message
                        showNotification(response.data.message || 'Failed to claim points', 'error');
                        $('#wc-loyalty-check-in').prop('disabled', false);
                    }
                },
                error: function() {
                    // Show error message
                    showNotification('An error occurred. Please try again.', 'error');
                    $('#wc-loyalty-check-in').prop('disabled', false);
                }
            });
        });
        
        // Helper function to show notifications
        function showNotification(message, type) {
            // Remove any existing notifications
            $('.wc-loyalty-notification').remove();
            
            // Create notification element
            var notification = $('<div class="wc-loyalty-notification ' + type + '">' + message + '</div>');
            
            // Append to body
            $('body').append(notification);
            
            // Show notification
            setTimeout(function() {
                notification.addClass('show');
            }, 10);
            
            // Hide notification after a delay
            setTimeout(function() {
                notification.removeClass('show');
                setTimeout(function() {
                    notification.remove();
                }, 300);
            }, 4000);
        }
    });
    </script>
    <?php
}
    
    /**
     * AJAX handler pentru claim daily points
     */
    public function claim_daily_points() {
        // Verifică dacă utilizatorul este autentificat
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in to claim points.', 'wc-loyalty-gamification')
            ));
            return;
        }
        
        // Verifică nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wc_loyalty_nonce')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'wc-loyalty-gamification')
            ));
            return;
        }
        
        $user_id = get_current_user_id();
        
        // Verifică dacă a revendicat deja azi
        $last_claim = get_user_meta($user_id, '_wc_loyalty_last_daily_claim', true);
        $today = date('Y-m-d');
        
        if ($last_claim === $today) {
            wp_send_json_error(array(
                'message' => __('You have already claimed your daily points today!', 'wc-loyalty-gamification')
            ));
            return;
        }
        
        // Obține valoarea punctelor
        $daily_points = get_option('wc_loyalty_daily_points', 5);
        $max_streak = get_option('wc_loyalty_max_streak', 5);
        $streak_bonus = get_option('wc_loyalty_streak_bonus', 10);
        $points_to_award = $daily_points;
        
        // Verifică streak
        $streak = get_user_meta($user_id, '_wc_loyalty_streak', true);
        $streak = intval($streak);
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        // Dacă ultima revendicare a fost ieri, incrementează streak
        if ($last_claim === $yesterday) {
            $streak++;
        } else {
            // Resetează streak dacă lanțul a fost întrerupt
            $streak = 1;
        }
        
        // Verifică dacă s-a atins streak-ul maxim
        $message = sprintf(__('You earned %d points for checking in today!', 'wc-loyalty-gamification'), $daily_points);
        
        if ($streak >= $max_streak) {
            // Acordă bonus pentru streak
            $points_to_award += $streak_bonus;
            
            // Actualizează mesajul
            $message = sprintf(
                __('Bonus! You reached a %d day streak and earned %d extra points!', 'wc-loyalty-gamification'),
                $max_streak,
                $streak_bonus
            );
            
            // Resetează streak după acordarea bonusului
            $streak = 0;
        }
        
        // Adaugă puncte
        WC_Loyalty()->points->add_points($user_id, $points_to_award, sprintf(
            __('Daily check-in - Day %d', 'wc-loyalty-gamification'),
            $streak
        ));
        
        // Actualizează metadatele utilizatorului
        update_user_meta($user_id, '_wc_loyalty_last_daily_claim', $today);
        update_user_meta($user_id, '_wc_loyalty_streak', $streak);
        
        // Obține punctele actualizate pentru răspuns
        $current_points = WC_Loyalty()->points->get_user_display_points($user_id);
        
        // Trimite răspunsul de succes
        wp_send_json_success(array(
            'message' => $message,
            'points' => $current_points,
            'streak' => $streak
        ));
    }
    
    /**
     * Afișează Milestone Rewards în contul utilizatorului
     */
    public function display_milestone_rewards() {
        if (!is_user_logged_in()) {
            return;
        }
        
        $user_id = get_current_user_id();
        
        // Obține data înregistrării utilizatorului
        $user_data = get_userdata($user_id);
        $registration_date = $user_data->user_registered;
        
        // Calculează zilele de la înregistrare
        $registration_time = strtotime($registration_date);
        $current_time = current_time('timestamp');
        $days_registered = floor(($current_time - $registration_time) / (60 * 60 * 24));
        
        // Obține milestone rewards
        $milestones = get_option('wc_loyalty_milestones', array(
            '7' => 50,
            '30' => 200,
            '90' => 500,
            '365' => 2000
        ));
        
        // Sortează milestone-urile după numărul de zile
        // Fix for line 497 in class-wc-loyalty-daily.php
if (is_array($milestones)) {
    ksort($milestones);
} else {
    // Handle the case where $milestones is not an array
    $milestones = array(); // Default to empty array
    // Or properly decode/convert the string to an array if possible
}
        
        ?>
        <div class="wc-loyalty-milestone-rewards">
            <h3><?php esc_html_e('Milestone Rewards', 'wc-loyalty-gamification'); ?></h3>
            
            <div class="wc-loyalty-milestone-grid">
                <?php foreach ($milestones as $days => $points) : 
                    $achieved = $days_registered >= $days;
                    $claimed = get_user_meta($user_id, '_wc_loyalty_milestone_' . $days, true) === 'claimed';
                    $classes = array('wc-loyalty-milestone-item');
                    if ($achieved) $classes[] = 'achieved';
                    if ($claimed) $classes[] = 'claimed';
                ?>
                    <div class="<?php echo esc_attr(implode(' ', $classes)); ?>">
                        <div class="wc-loyalty-milestone-days"><?php echo esc_html($days); ?> <?php esc_html_e('days', 'wc-loyalty-gamification'); ?></div>
                        <div class="wc-loyalty-milestone-points">+<?php echo esc_html($points); ?> <?php esc_html_e('bonus points', 'wc-loyalty-gamification'); ?></div>
                        
                        <?php if ($achieved && !$claimed) : ?>
                            <button class="wc-loyalty-claim-milestone" data-days="<?php echo esc_attr($days); ?>" data-points="<?php echo esc_attr($points); ?>">
                                <?php esc_html_e('Claim', 'wc-loyalty-gamification'); ?>
                            </button>
                        <?php elseif ($claimed) : ?>
                            <span class="wc-loyalty-milestone-claimed"><?php esc_html_e('Claimed', 'wc-loyalty-gamification'); ?></span>
                        <?php else : ?>
                            <span class="wc-loyalty-milestone-locked"><?php esc_html_e('Locked', 'wc-loyalty-gamification'); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Handler pentru butonul de claim milestone
            $('.wc-loyalty-claim-milestone').on('click', function() {
                var button = $(this);
                var days = button.data('days');
                var points = button.data('points');
                
                // Dezactivează butonul pentru a preveni click-uri multiple
                button.prop('disabled', true).text('...');
                
                // Trimite cererea AJAX
                $.ajax({
                    url: wcLoyaltyData.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'claim_milestone_reward',
                        days: days,
                        points: points,
                        nonce: wcLoyaltyData.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            // Actualizează UI-ul
                            button.parent().addClass('claimed');
                            button.replaceWith('<span class="wc-loyalty-milestone-claimed">Claimed</span>');
                            
                            // Arată un mesaj de succes
                            showNotification(response.data.message, 'success');
                        } else {
                            // Arată un mesaj de eroare
                            showNotification(response.data.message || 'An error occurred', 'error');
                            button.prop('disabled', false).text('Claim');
                        }
                    },
                    error: function() {
                        // Arată un mesaj de eroare generic
                        showNotification('An error occurred. Please try again.', 'error');
                        button.prop('disabled', false).text('Claim');
                    }
                });
            });
            
            // Helper function to show notifications
            function showNotification(message, type) {
                // Remove any existing notifications
                $('.wc-loyalty-notification').remove();
                
                // Create notification element
                var notification = $('<div class="wc-loyalty-notification ' + type + '">' + message + '</div>');
                
                // Append to body
                $('body').append(notification);
                
                // Show notification
                setTimeout(function() {
                    notification.addClass('show');
                }, 10);
                
                // Hide notification after a delay
                setTimeout(function() {
                    notification.removeClass('show');
                    setTimeout(function() {
                        notification.remove();
                    }, 300);
                }, 4000);
            }
        });
        </script>
        <?php
    }
    
    /**
     * AJAX handler pentru milestone rewards
     */
    public function claim_milestone_reward() {
        // Verifică dacă utilizatorul este autentificat
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in to claim rewards.', 'wc-loyalty-gamification')
            ));
            return;
        }
        
        // Verifică nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wc_loyalty_nonce')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'wc-loyalty-gamification')
            ));
            return;
        }
        
        // Obține parametrii
        $days = isset($_POST['days']) ? intval($_POST['days']) : 0;
        $points = isset($_POST['points']) ? intval($_POST['points']) : 0;
        
        if ($days <= 0 || $points <= 0) {
            wp_send_json_error(array(
                'message' => __('Invalid milestone data.', 'wc-loyalty-gamification')
            ));
            return;
        }
        
        $user_id = get_current_user_id();
        
        // Verifică dacă milestone-ul a fost deja revendicat
        $claimed = get_user_meta($user_id, '_wc_loyalty_milestone_' . $days, true) === 'claimed';
        if ($claimed) {
            wp_send_json_error(array(
                'message' => __('You have already claimed this milestone reward.', 'wc-loyalty-gamification')
            ));
            return;
        }
        
        // Verifică dacă utilizatorul a atins milestone-ul
        $user_data = get_userdata($user_id);
        $registration_date = $user_data->user_registered;
        $registration_time = strtotime($registration_date);
        $current_time = current_time('timestamp');
        $days_registered = floor(($current_time - $registration_time) / (60 * 60 * 24));
        
        if ($days_registered < $days) {
            wp_send_json_error(array(
                'message' => __('You have not reached this milestone yet.', 'wc-loyalty-gamification')
            ));
            return;
        }
        
        // Adaugă punctele
        WC_Loyalty()->points->add_points($user_id, $points, sprintf(
            __('Milestone reward for %d days of membership', 'wc-loyalty-gamification'),
            $days
        ));
        
        // Marchează milestone-ul ca revendicat
        update_user_meta($user_id, '_wc_loyalty_milestone_' . $days, 'claimed');
        
        // Trimite răspunsul de succes
        wp_send_json_success(array(
            'message' => sprintf(
                __('Congratulations! You have been awarded %d bonus points.', 'wc-loyalty-gamification'),
                $points
            )
        ));
    }
}

// Inițializează clasa
function wc_loyalty_init_daily_minimalist() {
    // Înlocuiește vechea instanță cu noua instanță minimalistă
    return new WC_Loyalty_Daily();
}

// Utilizează acest filtru pentru a înlocui vechea instanță
add_filter('wc_loyalty_daily_instance', 'wc_loyalty_init_daily_minimalist');