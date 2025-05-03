<?php
/**
 * Plugin Name: WooCommerce Loyalty Gamification GIT 
 * Description: A loyalty gamification system for WooCommerce with points, progress bar, and rewards.
 * Version: 1.0.1
 * Author: Claudia Tun
 * WC requires at least: 5.0.0
 * WC tested up to: 7.5.0
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('WC_LOYALTY_VERSION', '1.0.1');
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
     * Enqueue plugin scripts and styles with version control
     */
    public static function enqueue_assets() {
        // Enqueue CSS
        wp_enqueue_style(
            'wc-loyalty-styles', 
            WC_LOYALTY_PLUGIN_URL . 'assets/css/loyalty-style.css', 
            array(), 
            WC_LOYALTY_VERSION
        );

        // Enqueue JS
        wp_enqueue_script(
            'wc-loyalty-script', 
            WC_LOYALTY_PLUGIN_URL . 'assets/js/loyalty-script.js', 
            array('jquery'), 
            WC_LOYALTY_VERSION, 
            true
        );

        // Enqueue Circle Progress
        wp_enqueue_script(
            'wc-circle-progress', 
            WC_LOYALTY_PLUGIN_URL . 'assets/js/circle-progress.min.js', 
            array('jquery'), 
            '1.2.2', 
            true
        );
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
        
        // Log that we flushed the rules
        error_log('WC Loyalty: Rewrite rules flushed.');
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
        'includes/class-wc-loyalty-cart.php'
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
        // Initialize components in the correct dependency order
        $this->points = new WC_Loyalty_Points();
        $this->rewards = new WC_Loyalty_Rewards();
        $this->frontend = new WC_Loyalty_Frontend();
        $this->admin = new WC_Loyalty_Admin();
        $this->account = new WC_Loyalty_Account();
        $this->ajax = new WC_Loyalty_Ajax();
        $this->cart = new WC_Loyalty_Cart();
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

/**
 * Cart integration instance.
 *
 * @var WC_Loyalty_Cart
 */
public $cart;