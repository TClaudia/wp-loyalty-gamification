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

// Register activation hook
register_activation_hook(__FILE__, 'wc_loyalty_activate');

function wc_loyalty_activate() {
    // Ensure asset directories and permissions are set
    WC_Loyalty_Asset_Manager::setup_plugin_assets();

    // Existing activation logic
    require_once WC_LOYALTY_PLUGIN_DIR . 'includes/class-wc-loyalty-install.php';
    WC_Loyalty_Install::activate();
    
    // Force update templates to make sure they are in the right format
    require_once WC_LOYALTY_PLUGIN_DIR . 'includes/fix-loyalty-templates.php';
    wc_loyalty_force_update_templates();

    // Adaugă clasa de email reminder
require_once WC_LOYALTY_PLUGIN_DIR . 'includes/class-wc-loyalty-email-reminder.php';
$this->email_reminder = new WC_Loyalty_Email_Reminder();
}

// Register deactivation hook
register_deactivation_hook(__FILE__, 'wc_loyalty_deactivate');

function wc_loyalty_deactivate() {
    require_once WC_LOYALTY_PLUGIN_DIR . 'includes/class-wc-loyalty-install.php';
    WC_Loyalty_Install::deactivate();
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
     * The single instance of the class.
     *
     * @var WC_Loyalty_Gamification
     */
    protected static $instance = null;

    /**
 * Email reminder instance.
 *
 * @var WC_Loyalty_Email_Reminder
 */
public $email_reminder;

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

     $email_reminder_file = WC_LOYALTY_PLUGIN_DIR . 'includes/class-wc-loyalty-email-reminder.php';
    if (file_exists($email_reminder_file)) {
        require_once $email_reminder_file;
        $this->email_reminder = new WC_Loyalty_Email_Reminder();
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
include_once(WC_LOYALTY_PLUGIN_DIR . 'wc-loyalty-translations.php');