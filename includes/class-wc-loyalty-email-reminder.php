<?php
/**
 * WooCommerce Loyalty Gamification - Daily Email Reminder System
 * 
 * Adaugă un sistem pentru trimiterea automată de email-uri zilnice 
 * pentru a încuraja utilizatorii să își revendice punctele zilnice.
 */

if (!defined('WPINC')) {
    die;
}

/**
 * Clasa WC_Loyalty_Email_Reminder
 * Gestionează trimiterea email-urilor de reamintire zilnică
 */
class WC_Loyalty_Email_Reminder {
    
    /**
     * Constructor.
     */
    public function __construct() {
        // Adaugă cron event pentru trimiterea zilnică
        add_action('init', array($this, 'schedule_daily_emails'));
        
        // Callback pentru evenimentul cron
        add_action('wc_loyalty_send_daily_reminder', array($this, 'send_daily_emails'));
        
        // Adaugă setări în interfața de administrare
        add_action('admin_init', array($this, 'register_email_settings'));
        
        // Adaugă un tab pentru setări email
        add_filter('wc_loyalty_settings_tabs', array($this, 'add_email_settings_tab'));
        
        // Adaugă setări pentru tab-ul de email
        add_action('wc_loyalty_settings_tab_content_emails', array($this, 'display_email_settings'));
        
        // Filtrează conținutul email-ului pentru a adăuga link-uri personalizate
        add_filter('wc_loyalty_daily_email_content', array($this, 'process_email_template'), 10, 2);
    }
    
    /**
     * Programează evenimentul cron dacă nu există
     */
    public function schedule_daily_emails() {
        if (!wp_next_scheduled('wc_loyalty_send_daily_reminder')) {
            // Programează trimiterea la ora 10:00 în fiecare zi
            wp_schedule_event(strtotime('10:00:00'), 'daily', 'wc_loyalty_send_daily_reminder');
        }
    }
    
    /**
     * Înregistrează setările pentru email-uri
     */
    public function register_email_settings() {
        register_setting('wc_loyalty_settings', 'wc_loyalty_email_enabled');
        register_setting('wc_loyalty_settings', 'wc_loyalty_email_subject');
        register_setting('wc_loyalty_settings', 'wc_loyalty_email_template');
        register_setting('wc_loyalty_settings', 'wc_loyalty_email_from_name');
        register_setting('wc_loyalty_settings', 'wc_loyalty_email_from_email');
        register_setting('wc_loyalty_settings', 'wc_loyalty_email_send_time');
    }
    
    /**
     * Adaugă tab-ul pentru setări email
     */
    public function add_email_settings_tab($tabs) {
        $tabs['emails'] = __('Email Reminders', 'wc-loyalty-gamification');
        return $tabs;
    }
    
    /**
     * Afișează setările pentru email-uri
     */
    public function display_email_settings() {
        $email_enabled = get_option('wc_loyalty_email_enabled', 'no');
        $email_subject = get_option('wc_loyalty_email_subject', __('Don\'t forget your daily loyalty points!', 'wc-loyalty-gamification'));
        $email_template = get_option('wc_loyalty_email_template', $this->get_default_template());
        $from_name = get_option('wc_loyalty_email_from_name', get_bloginfo('name'));
        $from_email = get_option('wc_loyalty_email_from_email', get_option('admin_email'));
        $send_time = get_option('wc_loyalty_email_send_time', '10:00');
        
        ?>
        <div class="wc-loyalty-config-card">
            <h3><?php esc_html_e('Email Reminder Settings', 'wc-loyalty-gamification'); ?></h3>
            
            <div class="wc-loyalty-form-grid">
                <label for="wc_loyalty_email_enabled"><?php esc_html_e('Enable Daily Emails', 'wc-loyalty-gamification'); ?></label>
                <div>
                    <select id="wc_loyalty_email_enabled" name="wc_loyalty_email_enabled" class="wc-loyalty-input-md">
                        <option value="yes" <?php selected($email_enabled, 'yes'); ?>><?php esc_html_e('Yes', 'wc-loyalty-gamification'); ?></option>
                        <option value="no" <?php selected($email_enabled, 'no'); ?>><?php esc_html_e('No', 'wc-loyalty-gamification'); ?></option>
                    </select>
                    <p class="description"><?php esc_html_e('Enable or disable daily reminder emails', 'wc-loyalty-gamification'); ?></p>
                </div>
            </div>
            
            <div class="wc-loyalty-form-grid">
                <label for="wc_loyalty_email_from_name"><?php esc_html_e('From Name', 'wc-loyalty-gamification'); ?></label>
                <div>
                    <input type="text" id="wc_loyalty_email_from_name" name="wc_loyalty_email_from_name" 
                           value="<?php echo esc_attr($from_name); ?>" class="wc-loyalty-input-lg" />
                </div>
            </div>
            
            <div class="wc-loyalty-form-grid">
                <label for="wc_loyalty_email_from_email"><?php esc_html_e('From Email', 'wc-loyalty-gamification'); ?></label>
                <div>
                    <input type="email" id="wc_loyalty_email_from_email" name="wc_loyalty_email_from_email" 
                           value="<?php echo esc_attr($from_email); ?>" class="wc-loyalty-input-lg" />
                </div>
            </div>
            
            <div class="wc-loyalty-form-grid">
                <label for="wc_loyalty_email_send_time"><?php esc_html_e('Send Time', 'wc-loyalty-gamification'); ?></label>
                <div>
                    <input type="time" id="wc_loyalty_email_send_time" name="wc_loyalty_email_send_time" 
                           value="<?php echo esc_attr($send_time); ?>" class="wc-loyalty-input-sm" />
                    <p class="description"><?php esc_html_e('Time of day to send the reminder emails (server time)', 'wc-loyalty-gamification'); ?></p>
                </div>
            </div>
            
            <div class="wc-loyalty-form-grid">
                <label for="wc_loyalty_email_subject"><?php esc_html_e('Email Subject', 'wc-loyalty-gamification'); ?></label>
                <div>
                    <input type="text" id="wc_loyalty_email_subject" name="wc_loyalty_email_subject" 
                           value="<?php echo esc_attr($email_subject); ?>" class="wc-loyalty-input-lg" />
                </div>
            </div>
            
            <div class="wc-loyalty-form-grid" style="display: block;">
                <label for="wc_loyalty_email_template"><?php esc_html_e('Email Template', 'wc-loyalty-gamification'); ?></label>
                <div>
                    <textarea id="wc_loyalty_email_template" name="wc_loyalty_email_template" 
                              rows="15" style="width: 100%;"><?php echo esc_textarea($email_template); ?></textarea>
                    <p class="description">
                        <?php esc_html_e('Available variables:', 'wc-loyalty-gamification'); ?>
                        <code>{customer_name}</code>, 
                        <code>{site_name}</code>, 
                        <code>{today_points}</code>, 
                        <code>{current_streak}</code>, 
                        <code>{points_total}</code>, 
                        <code>{login_url}</code>
                    </p>
                </div>
            </div>
            
            <div class="wc-loyalty-form-grid">
                <label></label>
                <div>
                    <button type="button" id="send-test-email" class="button button-secondary">
                        <?php esc_html_e('Send Test Email', 'wc-loyalty-gamification'); ?>
                    </button>
                    <span id="test-email-notice" style="display:none; margin-left: 10px; color: green;"></span>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#send-test-email').on('click', function() {
                var button = $(this);
                var originalText = button.text();
                
                button.prop('disabled', true).text('<?php esc_html_e('Sending...', 'wc-loyalty-gamification'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wc_loyalty_send_test_email',
                        nonce: '<?php echo wp_create_nonce('wc_loyalty_test_email'); ?>'
                    },
                    success: function(response) {
                        $('#test-email-notice').text(response.data.message).show();
                        setTimeout(function() {
                            $('#test-email-notice').fadeOut();
                        }, 5000);
                    },
                    error: function() {
                        $('#test-email-notice').text('<?php esc_html_e('Error sending test email', 'wc-loyalty-gamification'); ?>').show();
                    },
                    complete: function() {
                        button.prop('disabled', false).text(originalText);
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Returnează modelul implicit de email
     */
    private function get_default_template() {
        return '<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Daily Points Reminder</title>
    <style>
        body {
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            font-size: 16px;
            line-height: 1.6;
            color: #333333;
            background-color: #f7f7f7;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #7952b3;
            padding: 30px;
            text-align: center;
            color: white;
            border-top-left-radius: 5px;
            border-top-right-radius: 5px;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }
        .content {
            background-color: white;
            padding: 30px;
            border-bottom-left-radius: 5px;
            border-bottom-right-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .points-box {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border: 2px dashed #7952b3;
            margin: 20px 0;
            border-radius: 5px;
        }
        .points-value {
            font-size: 24px;
            font-weight: 700;
            color: #7952b3;
            margin: 10px 0;
        }
        .button {
            display: inline-block;
            background-color: #7952b3;
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            margin-top: 20px;
        }
        .button:hover {
            background-color: #5e3d8f;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #666666;
            font-size: 14px;
        }
        .streak-info {
            font-size: 16px;
            color: #5e3d8f;
            margin: 5px 0;
        }
        .reminder {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 12px 15px;
            margin: 15px 0;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Daily Points Reminder</h1>
        </div>
        <div class="content">
            <p>Hello {customer_name},</p>
            
            <p>Don\'t forget to visit {site_name} today to claim your daily loyalty points!</p>
            
            <div class="points-box">
                <p>Today you can earn:</p>
                <div class="points-value">{today_points} points</div>
                <p>Current streak: Day {current_streak}</p>
            </div>
            
            <p>You currently have a total of {points_total} points in our loyalty program.</p>
            
            <div class="reminder">
                <strong>Tip:</strong> Check in daily to keep your streak going and earn bonus points!
            </div>
            
            <p style="text-align: center;">
                <a href="{login_url}" class="button">Visit Site to Claim Points</a>
            </p>
            
            <p>Log in to your account and check in daily to keep building your points and unlock exclusive rewards!</p>
        </div>
        <div class="footer">
            <p>&copy; <?php echo date("Y"); ?> {site_name} | <a href="{login_url}">My Account</a></p>
            <p style="font-size: 12px; color: #999;">
                You are receiving this email because you are a member of our loyalty program.
                <br>If you wish to stop receiving these notifications, you can update your preferences in your account.
            </p>
        </div>
    </div>
</body>
</html>';
    }
    
    /**
     * Procesează modelul de email pentru a înlocui variabilele
     */
    public function process_email_template($content, $user_id) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return $content;
        }
        
        // Obține toate datele necesare
        $customer_name = $user->display_name;
        $site_name = get_bloginfo('name');
        $login_url = wc_get_page_permalink('myaccount');
        
        // Construim un link special către pagina contului
        $account_url = add_query_arg([
            'utm_source' => 'loyalty_email',
            'utm_medium' => 'email',
            'utm_campaign' => 'daily_points'
        ], $login_url);
        
        // Obține informații despre puncte și streak-uri
        $points_total = 0;
        $current_streak = 1;
        $today_points = get_option('wc_loyalty_base_checkin_points', 5);
        
        if (function_exists('WC_Loyalty') && WC_Loyalty()->points) {
            $points_total = WC_Loyalty()->points->get_user_points($user_id);
            
            // Încearcă să obținem informații despre streak
            $streak_info = get_user_meta($user_id, '_wc_loyalty_streak', true);
            if ($streak_info && is_numeric($streak_info)) {
                $current_streak = intval($streak_info) + 1; // Adună 1 pentru următoarea zi
            }
            
            // Calculează punctele potențiale pentru astăzi
            $streak_multiplier = get_option('wc_loyalty_streak_multiplier', 0.1);
            $today_points = $today_points + floor($today_points * $streak_multiplier * $current_streak);
            
            // Verifică dacă ziua de astăzi atinge un milestone
            $milestones = json_decode(get_option('wc_loyalty_milestone_rewards', '{}'), true);
            if (is_array($milestones) && isset($milestones[$current_streak])) {
                $today_points += intval($milestones[$current_streak]);
            }
        }
        
        // Înlocuiește variabilele din model
        $replacements = [
            '{customer_name}' => $customer_name,
            '{site_name}' => $site_name,
            '{today_points}' => $today_points,
            '{current_streak}' => $current_streak,
            '{points_total}' => $points_total,
            '{login_url}' => $account_url
        ];
        
        $content = str_replace(array_keys($replacements), array_values($replacements), $content);
        return $content;
    }
    /**
 * Trimite email-uri zilnice către toți utilizatorii care nu au verificat încă
 */

public function send_daily_emails() {
    if (get_option('wc_loyalty_email_enabled', 'no') !== 'yes') {
        return;
    }
    $users = get_users([
        'role__in' => ['customer', 'subscriber', 'administrator'],
        'fields' => ['ID', 'user_email', 'display_name']
    ]);
    $today = date('Y-m-d');
    $subject = get_option('wc_loyalty_email_subject', __(
        'Don\'t forget your daily loyalty points!', 'wc-loyalty-gamification'));
    $template = get_option('wc_loyalty_email_template', $this->get_default_template());
    $from_name = get_option('wc_loyalty_email_from_name', get_bloginfo('name'));
    $from_email = get_option('wc_loyalty_email_from_email', get_option('admin_email'));
    $headers = [
        'From: ' . $from_name . ' <' . $from_email . '>',
        'Content-Type: text/html; charset=UTF-8'
    ];
    $sent_count = 0;
    foreach ($users as $user) {
        $last_claim = get_user_meta($user->ID, '_wc_loyalty_last_daily_claim', true);
        if ($last_claim !== $today) {
            $content = apply_filters('wc_loyalty_daily_email_content', $template, $user->ID);
            if (function_exists('wc_loyalty_send_email')) {
                $success = wc_loyalty_send_email($user->user_email, $subject, $content, $headers);
            } else {
                $success = wp_mail($user->user_email, $subject, $content, $headers);
            }
            if ($success) {
                $sent_count++;
            }
            usleep(100000); 
        }
    }
    
    // Log pentru verificarea funcționării
    error_log(sprintf(
        'WC Loyalty Email Reminder: Sent %d emails on %s',
        $sent_count,
        date('Y-m-d H:i:s')
    ));
}
    
    /**
 * Trimite un email de test către administrator
 */
public function send_test_email() {
    // Verificare de permisiuni
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Permission denied', 'wc-loyalty-gamification')]);
        return;
    }
    
    // Verifică nonce-ul
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wc_loyalty_test_email')) {
        wp_send_json_error(['message' => __('Security check failed.', 'wc-loyalty-gamification')]);
        return;
    }
    
    $user_id = get_current_user_id();
    $user = wp_get_current_user();
    
    // Obține setările de email
    $subject = get_option('wc_loyalty_email_subject', __('Don\'t forget your daily loyalty points!', 'wc-loyalty-gamification'));
    $template = get_option('wc_loyalty_email_template', $this->get_default_template());
    $from_name = get_option('wc_loyalty_email_from_name', get_bloginfo('name'));
    $from_email = get_option('wc_loyalty_email_from_email', get_option('admin_email'));
    
    // Procesează conținutul email-ului
    $content = apply_filters('wc_loyalty_daily_email_content', $template, $user_id);
    
    // Setarea header-elor pentru email
    $headers = [
        'From: ' . $from_name . ' <' . $from_email . '>',
        'Content-Type: text/html; charset=UTF-8'
    ];
    
    // Folosește funcția îmbunătățită de trimitere email
    if (function_exists('wc_loyalty_send_email')) {
        $success = wc_loyalty_send_email($user->user_email, '[TEST] ' . $subject, $content, $headers);
    } else {
        // Fallback la wp_mail obișnuit
        $success = wp_mail($user->user_email, '[TEST] ' . $subject, $content, $headers);
    }
    
    if ($success) {
        wp_send_json_success(['message' => __('Test email sent successfully!', 'wc-loyalty-gamification')]);
    } else {
        wp_send_json_error(['message' => __('Failed to send test email', 'wc-loyalty-gamification')]);
    }
}
}
// Adaugă hook pentru AJAX
add_action('wp_ajax_wc_loyalty_send_test_email', array(new WC_Loyalty_Email_Reminder(), 'send_test_email'));