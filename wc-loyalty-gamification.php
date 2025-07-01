<?php
/**
 * Plugin Name: WooCommerce Loyalty Gamification Version 11
 * Description: A loyalty gamification system for WooCommerce with points, progress bar, and rewards.
 * Version: 1.1.0
 * Author: Claudia Tun
 * WC requires at least: 5.0.0
 * WC tested up to: 7.5.0
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('WC_LOYALTY_VERSION', '1.1.0');
define('WC_LOYALTY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WC_LOYALTY_PLUGIN_URL', plugin_dir_url(__FILE__));

// Compatibility with HPOS
add_action('before_woocommerce_init', function() {
    if (class_exists('Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables',
            __FILE__,
            true
        );
    }
});

// Ensure WooCommerce is active
if (!function_exists('is_plugin_active')) {
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

if (!is_plugin_active('woocommerce/woocommerce.php')) {
    add_action('admin_notices', function() {
        ?>
        <div class="notice notice-error">
            <p><?php _e('WooCommerce Loyalty Gamification requires WooCommerce to be installed and activated.', 'wc-loyalty-gamification'); ?></p>
        </div>
        <?php
    });
    return;
}

// Enhanced file handling and security
class WC_Loyalty_Asset_Manager {
    /**
     * Ensure proper file permissions and security
     */
    public static function setup_plugin_assets() {
        $plugin_dir = WC_LOYALTY_PLUGIN_DIR;
        $asset_dirs = array(
            'assets/css',
            'assets/js',
            'assets/img'
        );

        // Create asset directories if they don't exist
        foreach ($asset_dirs as $dir) {
            $full_path = $plugin_dir . $dir;
            if (!is_dir($full_path)) {
                wp_mkdir_p($full_path);
            }
        }

        // Set secure .htaccess in assets directory
        self::create_htaccess();

        // Ensure file permissions
        self::set_file_permissions();
    }

    /**
     * Create .htaccess for additional security
     */
    private static function create_htaccess() {
        $htaccess_path = WC_LOYALTY_PLUGIN_DIR . 'assets/.htaccess';
        $htaccess_content = "# Deny direct access
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} -f
    RewriteRule ^(css|js)/ - [L]
</IfModule>

# Block direct script execution
<FilesMatch \".(php|php3|php4|php5|phtml)$\">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# Prevent directory listing
Options -Indexes";

        // Write .htaccess only if it doesn't exist
        if (!file_exists($htaccess_path)) {
            file_put_contents($htaccess_path, $htaccess_content);
        }
    }

    /**
     * Set secure file permissions
     */
    private static function set_file_permissions() {
        $asset_dirs = array(
            'assets/css',
            'assets/js'
        );

        foreach ($asset_dirs as $dir) {
            $full_path = WC_LOYALTY_PLUGIN_DIR . $dir;
            
            // Ensure directory exists and is readable
            if (is_dir($full_path)) {
                @chmod($full_path, 0755);

                // Set permissions for files
                $files = glob($full_path . '/*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        @chmod($file, 0644);
                    }
                }
            }
        }
    }
    
    /**
     * Enqueue plugin scripts and styles with proper dependencies
     */
    public static function enqueue_assets() {
        // Only enqueue for logged-in users
        if (!is_user_logged_in()) {
            return;
        }
        
        // Ensure jQuery is loaded
        wp_enqueue_script('jquery');
        
        // Enqueue CSS
        wp_enqueue_style(
            'wc-loyalty-styles', 
            WC_LOYALTY_PLUGIN_URL . 'assets/css/loyalty-style.css', 
            array(), 
            WC_LOYALTY_VERSION
        );
        
        // Enqueue Circle Progress first (it's a dependency)
        wp_enqueue_script(
            'wc-circle-progress', 
            WC_LOYALTY_PLUGIN_URL . 'assets/js/circle-progress.min.js', 
            array('jquery'), 
            '1.2.2', 
            true
        );

        // Enqueue main script with proper dependencies
        wp_enqueue_script(
            'wc-loyalty-script', 
            WC_LOYALTY_PLUGIN_URL . 'assets/js/loyalty-script.js', 
            array('jquery', 'wc-circle-progress'), 
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
        }
        
        // Enqueue daily check-in script last
        wp_enqueue_script(
            'wc-loyalty-daily',
            WC_LOYALTY_PLUGIN_URL . 'assets/js/daily-checkin.js',
            array('jquery', 'wc-circle-progress', 'wc-loyalty-script'),
            WC_LOYALTY_VERSION,
            true
        );
        
        // Pass necessary data to all scripts
        $data = array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'userPoints' => 0,
            'nextTier' => null,
            'nonce' => wp_create_nonce('wc_loyalty_nonce')
        );
        
        // Add user data if available
        if (function_exists('WC_Loyalty') && WC_Loyalty()->points) {
            $user_id = get_current_user_id();
            $data['userPoints'] = WC_Loyalty()->points->get_user_points($user_id);
            
            if (WC_Loyalty()->rewards) {
                $data['nextTier'] = WC_Loyalty()->rewards->get_next_reward_tier(
                    WC_Loyalty()->points->get_user_points($user_id)
                );
            }
        }
        
        // Localize data for all scripts
        wp_localize_script('wc-loyalty-script', 'wcLoyaltyData', $data);
        
        // Ensure cart coupon script has the data too
        if (is_cart() || is_checkout()) {
            wp_localize_script('wc-loyalty-cart-coupon', 'wcLoyaltyData', $data);
        }
    }
}

/**
 * Function to manually flush rewrite rules when needed
 */
function wc_loyalty_manual_flush_rules() {
    // Check if we need to flush
    if (get_option('wc_loyalty_flush_needed', 'yes') === 'yes') {
        // Force add the loyalty endpoints first
        add_rewrite_endpoint('loyalty-points', EP_ROOT | EP_PAGES);
        add_rewrite_endpoint('loyalty-rewards', EP_ROOT | EP_PAGES);
        
        // Then flush rewrite rules
        flush_rewrite_rules();
        
        // Update option to prevent frequent flushing
        update_option('wc_loyalty_flush_needed', 'no');
    }
}

// Register activation hook - FIXED VERSION
register_activation_hook(__FILE__, 'wc_loyalty_activate');

function wc_loyalty_activate() {
    try {
        // Ensure asset directories and permissions are set
        WC_Loyalty_Asset_Manager::setup_plugin_assets();

        // Load required files for activation
        $install_file = WC_LOYALTY_PLUGIN_DIR . 'includes/class-wc-loyalty-install.php';
        if (file_exists($install_file)) {
            require_once $install_file;
            WC_Loyalty_Install::activate();
        }
        
        // Force update templates to make sure they are in the right format
        $fix_file = WC_LOYALTY_PLUGIN_DIR . 'includes/fix-loyalty-templates.php';
        if (file_exists($fix_file)) {
            require_once $fix_file;
            if (function_exists('wc_loyalty_force_update_templates')) {
                wc_loyalty_force_update_templates();
            }
        }

        // Set up cron event if class is available
        if (!wp_next_scheduled('wc_loyalty_send_daily_reminder')) {
            wp_schedule_event(strtotime('10:00:00'), 'daily', 'wc_loyalty_send_daily_reminder');
        }
        
    } catch (Exception $e) {
        // Log the error instead of causing a fatal error
        error_log('WC Loyalty Activation Error: ' . $e->getMessage());
        
        // Add admin notice for the error
        add_action('admin_notices', function() use ($e) {
            ?>
            <div class="notice notice-error">
                <p><?php printf('WC Loyalty Activation Error: %s', esc_html($e->getMessage())); ?></p>
            </div>
            <?php
        });
    }
}

// Register deactivation hook
register_deactivation_hook(__FILE__, 'wc_loyalty_deactivate');

function wc_loyalty_deactivate() {
    $install_file = WC_LOYALTY_PLUGIN_DIR . 'includes/class-wc-loyalty-install.php';
    if (file_exists($install_file)) {
        require_once $install_file;
        WC_Loyalty_Install::deactivate();
    }
}

// Modify the main plugin loading
add_action('plugins_loaded', 'wc_loyalty_init_plugin', 30);

/**
 * Initialize the plugin
 */
function wc_loyalty_init_plugin() {
    // Include required files
    $include_files = array(
        'includes/class-wc-loyalty-install.php',
        'includes/class-wc-loyalty-points.php',
        'includes/class-wc-loyalty-rewards.php',
        'includes/class-wc-loyalty-frontend.php',
        'includes/class-wc-loyalty-admin.php',
        'includes/class-wc-loyalty-account.php',
        'includes/class-wc-loyalty-ajax.php',
        'includes/class-wc-loyalty-cart.php',
        'includes/class-wc-loyalty-daily.php',
        'includes/class-wc-loyalty-email-reminder.php',
        'includes/email-functions.php'
    );

    foreach ($include_files as $file) {
        $full_path = WC_LOYALTY_PLUGIN_DIR . $file;
        if (file_exists($full_path)) {
            require_once $full_path;
        }
    }
    
    // Enqueue assets
    add_action('wp_enqueue_scripts', array('WC_Loyalty_Asset_Manager', 'enqueue_assets'));

    // Initialize the main plugin class
    WC_Loyalty_Gamification::instance();
}

/**
 * Main plugin class with Singleton pattern
 */
class WC_Loyalty_Gamification {
    /**
     * Points manager instance.
     *
     * @var WC_Loyalty_Points
     */
    public $points;

    /**
     * Rewards manager instance.
     *
     * @var WC_Loyalty_Rewards
     */
    public $rewards;

    /**
     * Frontend display instance.
     *
     * @var WC_Loyalty_Frontend
     */
    public $frontend;

    /**
     * Admin settings instance.
     *
     * @var WC_Loyalty_Admin
     */
    public $admin;

    /**
     * Account integration instance.
     *
     * @var WC_Loyalty_Account
     */
    public $account;

    /**
     * Ajax handler instance.
     *
     * @var WC_Loyalty_Ajax
     */
    public $ajax;

    /**
     * Cart integration instance.
     *
     * @var WC_Loyalty_Cart
     */
    public $cart;

    /**
     * Daily check-in instance.
     *
     * @var WC_Loyalty_Daily
     */
    public $daily;

    /**
     * Email reminder instance.
     *
     * @var WC_Loyalty_Email_Reminder
     */
    public $email_reminder;

    /**
     * The single instance of the class.
     *
     * @var WC_Loyalty_Gamification
     */
    protected static $instance = null;

    /**
     * Main WC_Loyalty_Gamification Instance.
     *
     * Ensures only one instance of WC_Loyalty_Gamification is loaded or can be loaded.
     *
     * @return WC_Loyalty_Gamification - Main instance.
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Prevent cloning of the instance
     */
    private function __clone() {}

    /**
     * Prevent unserialize of the instance
     * 
     * Changed to public visibility as per PHP requirements
     */
    public function __wakeup() {
        // Prevent unserializing of the instance
        _doing_it_wrong(__METHOD__, 'Unserializing instances of this class is not allowed.', WC_LOYALTY_VERSION);
    }

    /**
     * Constructor.
     */
    public function __construct() {
        // Prevent direct instantiation
        if (self::$instance !== null) {
            return;
        }

        $this->init_hooks();
    }

    /**
     * Initialize hooks.
     */
    private function init_hooks() {
        // Load text domain
        add_action('init', array($this, 'load_textdomain'));
        
        // Wait until WordPress is fully loaded before initializing components
        add_action('wp_loaded', array($this, 'init_components'), 20);
    }

    /**
     * Load text domain for translations.
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'wc-loyalty-gamification',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
    }

    /**
     * Initialize plugin components.
     */
    public function init_components() {
        // Ensure required files exist before initializing components
        $required_files = [
            'includes/class-wc-loyalty-points.php' => 'WC_Loyalty_Points',
            'includes/class-wc-loyalty-rewards.php' => 'WC_Loyalty_Rewards',
            'includes/class-wc-loyalty-frontend.php' => 'WC_Loyalty_Frontend',
            'includes/class-wc-loyalty-admin.php' => 'WC_Loyalty_Admin',
            'includes/class-wc-loyalty-account.php' => 'WC_Loyalty_Account',
            'includes/class-wc-loyalty-ajax.php' => 'WC_Loyalty_Ajax',
            'includes/class-wc-loyalty-cart.php' => 'WC_Loyalty_Cart',
            'includes/class-wc-loyalty-daily.php' => 'WC_Loyalty_Daily',
            'includes/class-wc-loyalty-email-reminder.php' => 'WC_Loyalty_Email_Reminder',
        ];
        
        // Initialize components only if the file exists
        foreach ($required_files as $file => $class) {
            $full_path = WC_LOYALTY_PLUGIN_DIR . $file;
            if (file_exists($full_path)) {
                require_once $full_path;
                $property = strtolower(str_replace('WC_Loyalty_', '', $class));
                if (class_exists($class)) {
                    $this->$property = new $class();
                } else {
                    error_log("Class $class not found in $full_path");
                }
            } else {
                error_log("Required file not found: $full_path");
            }
        }

        // Initialize check-in system if file exists
        $checkin_file = WC_LOYALTY_PLUGIN_DIR . 'includes/class-wc-loyalty-checkin.php';
        if (file_exists($checkin_file)) {
            require_once $checkin_file;
            if (class_exists('WC_Loyalty_Checkin')) {
                $this->checkin = new WC_Loyalty_Checkin();
            }
        }
    }
}

// Remove the separate function, use direct class method
function WC_Loyalty() {
    return WC_Loyalty_Gamification::instance();
}

// Add the manual flush rules action
add_action('wp_loaded', 'wc_loyalty_manual_flush_rules', 30);

/**
 * Function to force flush rewrite rules
 */
function wc_loyalty_force_flush() {
    // This will force adding our endpoints
    add_rewrite_endpoint('loyalty-points', EP_ROOT | EP_PAGES);
    add_rewrite_endpoint('loyalty-rewards', EP_ROOT | EP_PAGES);
    
    // Force flush rewrite rules
    flush_rewrite_rules();
    
    // Set a flag in the database to indicate we've done this
    update_option('wc_loyalty_flushed_at', time());
}

// Add a temporary rewrite flush - remove after permalinks start working
add_action('init', 'wc_loyalty_force_flush', 999);

/**
 * Add this function to a page to manually trigger rule flushing
 * You can create a temporary admin page that calls this
 */
function wc_loyalty_debug_permalinks() {
    global $wp_rewrite;
    
    echo '<h2>WC Loyalty Debug</h2>';
    echo '<p>Flushing rewrite rules...</p>';
    
    // Add our endpoints
    add_rewrite_endpoint('loyalty-points', EP_ROOT | EP_PAGES);
    add_rewrite_endpoint('loyalty-rewards', EP_ROOT | EP_PAGES);
    
    // Flush rules
    $wp_rewrite->flush_rules(true);
    
    echo '<p>Done. Rewrite rules flushed.</p>';
    
    // Show current rules for debugging
    echo '<h3>Current Rewrite Rules:</h3>';
    echo '<pre>';
    print_r($wp_rewrite->rewrite_rules());
    echo '</pre>';
    
    // Show endpoints
    echo '<h3>Current Endpoints:</h3>';
    echo '<pre>';
    print_r($wp_rewrite->endpoints);
    echo '</pre>';
}

// Optionally create a temporary admin page to run the debug function
function wc_loyalty_add_debug_page() {
    // Only for admin users
    if (!current_user_can('manage_options')) return;
    
    add_submenu_page(
        'woocommerce',
        'Loyalty Debug',
        'Loyalty Debug',
        'manage_options',
        'wc-loyalty-debug',
        'wc_loyalty_debug_permalinks'
    );
}
// Enable debug page
add_action('admin_menu', 'wc_loyalty_add_debug_page', 99);

// Utility function to get premium discount value
function wc_loyalty_get_premium_discount_max() {
    return get_option('wc_loyalty_premium_discount_max', 400);
}

// Add custom text to cart for loyalty discounts
add_filter('woocommerce_cart_totals_coupon_html', 'wc_loyalty_coupon_cart_description', 10, 3);
function wc_loyalty_coupon_cart_description($coupon_html, $coupon, $discount_amount_html) {
    // Check if this is a loyalty coupon
    $is_loyalty_coupon = get_post_meta($coupon->get_id(), '_wc_loyalty_coupon', true);
    
    if ($is_loyalty_coupon === 'yes') {
        // Add loyalty program text
        $coupon_html .= '<p class="loyalty-discount-note">' . __('Applied from Loyalty Program', 'wc-loyalty-gamification') . '</p>';
    }
    
    return $coupon_html;
}

// Mark coupon as used when order is completed
add_action('woocommerce_order_status_completed', 'wc_loyalty_mark_coupon_used_on_complete', 10, 1);
function wc_loyalty_mark_coupon_used_on_complete($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) return;
    
    $user_id = $order->get_user_id();
    if ($user_id <= 0) return;
    
    $applied_coupons = $order->get_coupon_codes();
    if (empty($applied_coupons)) return;
    
    foreach ($applied_coupons as $coupon_code) {
        $coupon = new WC_Coupon($coupon_code);
        
        // Skip invalid coupons
        if (!$coupon || !$coupon->get_id()) continue;
        
        $is_loyalty_coupon = get_post_meta($coupon->get_id(), '_wc_loyalty_coupon', true);
        
        if ($is_loyalty_coupon === 'yes') {
            // Make sure WC_Loyalty and rewards component exist
            if (function_exists('WC_Loyalty') && WC_Loyalty()->rewards) {
                WC_Loyalty()->rewards->mark_coupon_as_used($coupon_code, $user_id);
                
                // Add order note
                $order->add_order_note(
                    sprintf(__('Loyalty coupon %s was used and marked as claimed.', 'wc-loyalty-gamification'), 
                    $coupon_code)
                );
            }
        }
    }
}

add_action('woocommerce_applied_coupon', 'wc_loyalty_handle_applied_coupon');

/**
 * Log debug information to help troubleshoot coupon issues
 * 
 * @param string $message The message to log
 * @param mixed $data Optional data to include in the log
 */
function wc_loyalty_debug_log($message, $data = null) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $log_message = '[WC Loyalty] ' . $message;
        
        if ($data !== null) {
            $log_message .= ': ' . (is_array($data) || is_object($data) ? print_r($data, true) : $data);
        }
        
        error_log($log_message);
    }
}

/**
 * Handle applied coupon through WooCommerce hooks
 * 
 * @param string $coupon_code The coupon code that was applied
 */
function wc_loyalty_handle_applied_coupon($coupon_code) {
    if (!is_user_logged_in()) {
        return;
    }
    
    $user_id = get_current_user_id();
    
    // Only process if WC_Loyalty is available
    if (!function_exists('WC_Loyalty') || !WC_Loyalty()->rewards) {
        return;
    }
    
    // Check if this is a loyalty coupon - find direct method to check
    $user_coupons = WC_Loyalty()->rewards->get_user_coupons($user_id);
    
    if (is_array($user_coupons)) {
        foreach ($user_coupons as $coupon) {
            if (isset($coupon['code']) && $coupon['code'] === $coupon_code) {
                // Use a direct approach to mark coupon as used
                $user_coupons_updated = $user_coupons;
                
                foreach ($user_coupons_updated as $key => $c) {
                    if ($c['code'] === $coupon_code) {
                        $user_coupons_updated[$key]['is_used'] = true;
                    }
                }
                
                // Update user coupons metadata
                update_user_meta($user_id, '_wc_loyalty_coupons', $user_coupons_updated);
                break;
            }
        }
    }
}

// Load translations for Romanian
$translations_file = WC_LOYALTY_PLUGIN_DIR . 'wc-loyalty-translations.php';
if (file_exists($translations_file)) {
    include_once($translations_file);
}

/**
 * Sistem îmbunătățit pentru marcarea și ascunderea cupoanelor utilizate
 * Adaugă în wc-loyalty-gamification.php sau într-un fișier separat
 */

// 1. Hook îmbunătățit pentru detectarea utilizării cupoanelor
add_action('woocommerce_order_status_processing', 'wc_loyalty_mark_coupons_used_immediately', 10, 2);
add_action('woocommerce_order_status_completed', 'wc_loyalty_mark_coupons_used_immediately', 10, 2);
add_action('woocommerce_payment_complete', 'wc_loyalty_mark_coupons_used_on_payment');

/**
 * Marchează cupoanele ca utilizate imediat după plată sau procesare
 */
function wc_loyalty_mark_coupons_used_immediately($order_id, $order = null) {
    if (!$order) {
        $order = wc_get_order($order_id);
    }
    
    if (!$order) return;
    
    $user_id = $order->get_user_id();
    if ($user_id <= 0) return;
    
    // Obține cupoanele aplicate pe comandă
    $applied_coupons = $order->get_coupon_codes();
    if (empty($applied_coupons)) return;
    
    wc_loyalty_debug_log("Processing coupons for order #$order_id: " . implode(', ', $applied_coupons));
    
    foreach ($applied_coupons as $coupon_code) {
        // Verifică dacă este un cupon de loialitate
        if (wc_loyalty_is_loyalty_coupon($coupon_code)) {
            wc_loyalty_mark_specific_coupon_used($user_id, $coupon_code, $order_id);
        }
    }
}

/**
 * Marchează cupoanele la finalizarea plății
 */
function wc_loyalty_mark_coupons_used_on_payment($order_id) {
    wc_loyalty_mark_coupons_used_immediately($order_id);
}

/**
 * Verifică dacă un cupon este un cupon de loialitate
 */
function wc_loyalty_is_loyalty_coupon($coupon_code) {
    // Metoda 1: Verifică prin WC_Coupon
    $coupon = new WC_Coupon($coupon_code);
    if ($coupon && $coupon->get_id()) {
        $is_loyalty = get_post_meta($coupon->get_id(), '_wc_loyalty_coupon', true);
        if ($is_loyalty === 'yes') {
            return true;
        }
    }
    
    // Metoda 2: Verifică prin prefixul cuponului
    if (strpos($coupon_code, 'LOYALTY') === 0) {
        return true;
    }
    
    // Metoda 3: Verifică în cupoanele utilizatorului
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        if (function_exists('WC_Loyalty') && WC_Loyalty()->rewards) {
            $user_coupons = WC_Loyalty()->rewards->get_all_user_coupons($user_id);
            if (is_array($user_coupons)) {
                foreach ($user_coupons as $coupon) {
                    if (isset($coupon['code']) && $coupon['code'] === $coupon_code) {
                        return true;
                    }
                }
            }
        }
    }
    
    return false;
}

/**
 * Marchează un cupon specific ca utilizat
 */
function wc_loyalty_mark_specific_coupon_used($user_id, $coupon_code, $order_id = null) {
    // Verifică dacă WC_Loyalty este disponibil
    if (!function_exists('WC_Loyalty') || !WC_Loyalty()->rewards) {
        wc_loyalty_debug_log("WC_Loyalty not available for marking coupon: $coupon_code");
        return false;
    }
    
    // Obține cupoanele utilizatorului
    $user_coupons = WC_Loyalty()->rewards->get_user_coupons($user_id);
    if (!is_array($user_coupons)) {
        wc_loyalty_debug_log("No user coupons found for user $user_id");
        return false;
    }
    
    $coupon_found = false;
    $updated_coupons = $user_coupons;
    
    // Marchează cuponul ca utilizat
    foreach ($updated_coupons as $key => $coupon) {
        if (isset($coupon['code']) && $coupon['code'] === $coupon_code) {
            $updated_coupons[$key]['is_used'] = true;
            $updated_coupons[$key]['used_date'] = current_time('mysql');
            if ($order_id) {
                $updated_coupons[$key]['used_order_id'] = $order_id;
            }
            $coupon_found = true;
            wc_loyalty_debug_log("Marked coupon $coupon_code as used for user $user_id");
            break;
        }
    }
    
    if ($coupon_found) {
        // Salvează cupoanele actualizate
        update_user_meta($user_id, '_wc_loyalty_coupons', $updated_coupons);
        
        // Declanșează hook pentru alte funcționalități
        do_action('wc_loyalty_coupon_marked_used', $user_id, $coupon_code, $order_id);
        
        return true;
    }
    
    wc_loyalty_debug_log("Coupon $coupon_code not found in user $user_id coupons");
    return false;
}

/**
 * AJAX handler pentru marcarea manuală a cuponului ca utilizat
 */
add_action('wp_ajax_wc_loyalty_mark_coupon_used', 'wc_loyalty_ajax_mark_coupon_used');

function wc_loyalty_ajax_mark_coupon_used() {
    // Verificări de securitate
    if (!wp_verify_nonce($_POST['nonce'], 'wc_loyalty_nonce')) {
        wp_send_json_error('Security check failed');
        return;
    }
    
    if (!is_user_logged_in()) {
        wp_send_json_error('User not logged in');
        return;
    }
    
    $coupon_code = sanitize_text_field($_POST['coupon_code']);
    $user_id = get_current_user_id();
    
    if (empty($coupon_code)) {
        wp_send_json_error('Invalid coupon code');
        return;
    }
    
    // Marchează cuponul ca utilizat
    $success = wc_loyalty_mark_specific_coupon_used($user_id, $coupon_code);
    
    if ($success) {
        wp_send_json_success(array(
            'message' => __('Cuponul a fost marcat ca utilizat', 'wc-loyalty-gamification'),
            'coupon_code' => $coupon_code
        ));
    } else {
        wp_send_json_error('Failed to mark coupon as used');
    }
}

/**
 * Funcție pentru filtrarea cupoanelor active (nu utilizate și nu expirate)
 */
function wc_loyalty_get_active_user_coupons($user_id, $include_used = false) {
    if (!function_exists('WC_Loyalty') || !WC_Loyalty()->rewards) {
        return array();
    }
    
    $all_coupons = WC_Loyalty()->rewards->get_user_coupons($user_id);
    if (!is_array($all_coupons)) {
        return array();
    }
    
    $active_coupons = array();
    $current_time = time();
    
    foreach ($all_coupons as $coupon) {
        // Verifică dacă cuponul este utilizat
        $is_used = isset($coupon['is_used']) && $coupon['is_used'];
        
        // Verifică dacă cuponul este expirat
        $is_expired = false;
        if (isset($coupon['expires'])) {
            $expiry_time = strtotime($coupon['expires']);
            $is_expired = ($expiry_time && $expiry_time < $current_time);
        }
        
        // Include cuponul dacă:
        // - Nu este utilizat și nu este expirat, SAU
        // - include_used este true și vrem să vedem toate cupoanele
        if ((!$is_used && !$is_expired) || $include_used) {
            // Adaugă proprietăți suplimentare pentru afișare
            $coupon['is_expired'] = $is_expired;
            $coupon['is_active'] = !$is_used && !$is_expired;
            $active_coupons[] = $coupon;
        }
    }
    
    return $active_coupons;
}

/**
 * Înlocuiește funcția get_user_coupons din clasa WC_Loyalty_Rewards
 * pentru a returna doar cupoanele active în mod implicit
 */
add_filter('wc_loyalty_filter_user_coupons', 'wc_loyalty_filter_active_coupons', 10, 2);

function wc_loyalty_filter_active_coupons($coupons, $user_id) {
    return wc_loyalty_get_active_user_coupons($user_id, false);
}

/**
 * Adaugă JavaScript pentru gestionarea cupoanelor în frontend
 */
add_action('wp_footer', 'wc_loyalty_coupon_management_script');

function wc_loyalty_coupon_management_script() {
    if (!is_user_logged_in()) return;
    ?>
    <script>
    (function($) {
        'use strict';
        
        // Funcție pentru ascunderea cupoanelor utilizate
        function hideCouponElement(couponCode) {
            // Ascunde din modal
            $('.wc-loyalty-coupon').each(function() {
                var code = $(this).find('.wc-loyalty-coupon-code').text().trim();
                if (code === couponCode) {
                    $(this).fadeOut(300, function() {
                        $(this).remove();
                    });
                }
            });
            
            // Ascunde din mini coupons
            $('.mini-coupon').each(function() {
                var code = $(this).find('.mini-copy-btn').data('code');
                if (code === couponCode) {
                    $(this).fadeOut(300, function() {
                        $(this).remove();
                    });
                }
            });
            
            // Ascunde din cart coupons
            $('.wc-loyalty-cart-coupon').each(function() {
                var code = $(this).find('.wc-loyalty-cart-coupon-code').text().trim();
                if (code === couponCode) {
                    $(this).fadeOut(300, function() {
                        $(this).remove();
                    });
                }
            });
        }
        
        // Monitor pentru aplicarea cupoanelor în coș
        $(document).on('applied_coupon_in_checkout', function(event, couponCode) {
            if (couponCode && couponCode.indexOf('LOYALTY') === 0) {
                setTimeout(function() {
                    hideCouponElement(couponCode);
                }, 1000);
            }
        });
        
        // Monitor pentru evenimente WooCommerce
        $(document.body).on('applied_coupon', function(event, couponCode) {
            if (couponCode && couponCode.indexOf('LOYALTY') === 0) {
                setTimeout(function() {
                    hideCouponElement(couponCode);
                }, 1000);
            }
        });
        
        // Funcție pentru marcarea manuală a cuponului ca utilizat
        window.markCouponAsUsed = function(couponCode) {
            $.ajax({
                url: wcLoyaltyData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'wc_loyalty_mark_coupon_used',
                    coupon_code: couponCode,
                    nonce: wcLoyaltyData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        hideCouponElement(couponCode);
                        if (typeof showNotification === 'function') {
                            showNotification(response.data.message, 'success');
                        }
                    }
                }
            });
        };
        
    })(jQuery);
    </script>
    <?php
}

/**
 * Adaugă buton pentru marcarea manuală a cuponului ca utilizat (opțional)
 */
function wc_loyalty_add_mark_used_button($coupon) {
    if (isset($coupon['is_used']) && $coupon['is_used']) {
        return; // Nu afișa butonul pentru cupoanele deja utilizate
    }
    
    $coupon_code = esc_attr($coupon['code']);
    echo '<button type="button" class="wc-loyalty-mark-used-btn" onclick="markCouponAsUsed(\'' . $coupon_code . '\')" style="margin-left: 5px; font-size: 10px; padding: 2px 6px;">Marchează ca utilizat</button>';
}

/**
 * Hook pentru curățarea periodică a cupoanelor expirate
 */
add_action('wp', 'wc_loyalty_schedule_cleanup');

function wc_loyalty_schedule_cleanup() {
    if (!wp_next_scheduled('wc_loyalty_cleanup_expired_coupons')) {
        wp_schedule_event(time(), 'daily', 'wc_loyalty_cleanup_expired_coupons');
    }
}

add_action('wc_loyalty_cleanup_expired_coupons', 'wc_loyalty_cleanup_expired_coupons');

function wc_loyalty_cleanup_expired_coupons() {
    global $wpdb;
    
    // Obține toți utilizatorii cu cupoane
    $users_with_coupons = $wpdb->get_col("
        SELECT user_id 
        FROM {$wpdb->usermeta} 
        WHERE meta_key = '_wc_loyalty_coupons' 
        AND meta_value != ''
    ");
    
    $cleaned_count = 0;
    $current_time = time();
    
    foreach ($users_with_coupons as $user_id) {
        $user_coupons = get_user_meta($user_id, '_wc_loyalty_coupons', true);
        
        if (!is_array($user_coupons)) continue;
        
        $active_coupons = array();
        $had_expired = false;
        
        foreach ($user_coupons as $coupon) {
            $is_expired = false;
            if (isset($coupon['expires'])) {
                $expiry_time = strtotime($coupon['expires']);
                $is_expired = ($expiry_time && $expiry_time < $current_time);
            }
            
            // Păstrează doar cupoanele care nu sunt expirate sau care sunt utilizate (pentru istoric)
            if (!$is_expired || (isset($coupon['is_used']) && $coupon['is_used'])) {
                $active_coupons[] = $coupon;
            } else {
                $had_expired = true;
            }
        }
        
        // Actualizează doar dacă au fost cupoane expirate
        if ($had_expired && count($active_coupons) !== count($user_coupons)) {
            update_user_meta($user_id, '_wc_loyalty_coupons', $active_coupons);
            $cleaned_count++;
        }
    }
    
    if ($cleaned_count > 0) {
        wc_loyalty_debug_log("Cleaned expired coupons for $cleaned_count users");
    }
}
?>