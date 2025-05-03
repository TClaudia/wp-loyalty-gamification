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
 * Cart integration instance.
 *
 * @var WC_Loyalty_Cart
 */
public $cart;

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
        $this->cart = new WC_Loyalty_Cart();
        $this->frontend = new WC_Loyalty_Frontend();
        $this->admin = new WC_Loyalty_Admin();
        $this->account = new WC_Loyalty_Account();
        $this->ajax = new WC_Loyalty_Ajax();
    
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
 * Adaugă o pagină scurtă cu produsele gratuite disponibile
 */
function wc_loyalty_display_free_products_page() {
    if (!is_user_logged_in()) {
        return;
    }
    
    $free_products = WC_Loyalty()->get_free_products();
    
    if (empty($free_products)) {
        return;
    }
    
    // Adaugă shortcode pentru pagina de produse gratuite
    add_shortcode('wc_loyalty_free_products', function() use ($free_products) {
        $output = '<div class="wc-loyalty-free-products-page">';
        $output .= '<h2>' . __('Free Products Available with Loyalty Coupon', 'wc-loyalty-gamification') . '</h2>';
        $output .= '<p>' . __('When you reach 2000 loyalty points, you\'ll receive a coupon that can be used to get one of these products for free:', 'wc-loyalty-gamification') . '</p>';
        
        $output .= '<div class="wc-loyalty-free-products-grid">';
        
        foreach ($free_products as $product) {
            $output .= '<div class="wc-loyalty-free-product-item">';
            $output .= '<a href="' . esc_url($product['permalink']) . '">' . $product['image'] . '</a>';
            $output .= '<h3><a href="' . esc_url($product['permalink']) . '">' . esc_html($product['name']) . '</a></h3>';
            $output .= '<div class="wc-loyalty-free-product-price">' . wc_price($product['price']) . ' ' . __('or FREE with coupon', 'wc-loyalty-gamification') . '</div>';
            $output .= '</div>';
        }
        
        $output .= '</div>';
        $output .= '</div>';
        
        return $output;
    });
}

// Inițializează pagina de produse gratuite
add_action('init', 'wc_loyalty_display_free_products_page');

// Marchează cuponul ca utilizat după finalizarea comenzii
add_action('woocommerce_order_status_processing', function($order_id) {
    $order = wc_get_order($order_id);
    $coupons_used = $order->get_coupon_codes();
    
    foreach ($coupons_used as $coupon_code) {
        $coupon = new WC_Coupon($coupon_code);
        $is_free_product_coupon = get_post_meta($coupon->get_id(), '_wc_loyalty_free_product_coupon', true);
        
        if ($is_free_product_coupon === 'yes') {
            $user_id = get_post_meta($coupon->get_id(), '_wc_loyalty_user_id', true);
            
            if ($user_id) {
                // Marchează cuponul ca utilizat
                WC_Loyalty()->rewards->mark_coupon_as_used($coupon_code, $user_id);
                
                // Adaugă notă în comandă
                $order->add_order_note(__('Free product loyalty coupon used for this order.', 'wc-loyalty-gamification'));
                
                // Marchează recompensa ca fiind solicitată
                $cycle_level = WC_Loyalty()->points->get_user_cycle_level($user_id);
                $claimed_rewards = WC_Loyalty()->rewards->get_rewards_claimed($user_id);
                $claim_key = '2000_cycle_' . $cycle_level;
                
                if (!isset($claimed_rewards[$claim_key])) {
                    $claimed_rewards[$claim_key] = current_time('mysql');
                    WC_Loyalty()->rewards->update_rewards_claimed($user_id, $claimed_rewards);
                }
                
                // Adaugă notificare pentru utilizator
                WC_Loyalty()->rewards->store_user_notification(
                    $user_id, 
                    'success', 
                    __('You\'ve successfully claimed your free product!', 'wc-loyalty-gamification')
                );
            }
        }
    }
});

// Adaugă mesaj în coș pentru produsele eligibile
add_filter('woocommerce_get_price_html', function($price_html, $product) {
    if (!is_user_logged_in()) {
        return $price_html;
    }
    
    $user_id = get_current_user_id();
    $user_coupons = WC_Loyalty()->rewards->get_user_coupons($user_id);
    $has_free_product_coupon = false;
    
    foreach ($user_coupons as $coupon) {
        if (isset($coupon['type']) && $coupon['type'] === 'free_product' && !$coupon['is_used']) {
            $has_free_product_coupon = true;
            break;
        }
    }
    
    if ($has_free_product_coupon) {
        // Verifică dacă produsul este în lista de produse gratuite
        global $wpdb;
        $table_name = $wpdb->prefix . 'wc_loyalty_free_products';
        $free_products = $wpdb->get_col("SELECT product_id FROM $table_name");
        
        if (in_array($product->get_id(), $free_products)) {
            return $price_html . ' <span class="wc-loyalty-free-eligible">' . __('or FREE with your loyalty coupon!', 'wc-loyalty-gamification') . '</span>';
        }
    }
    
    return $price_html;
}, 10, 2);

// Adaugă notificare în pagina de produs pentru produsele eligibile
add_action('woocommerce_before_single_product_summary', function() {
    if (!is_user_logged_in()) {
        return;
    }
    
    global $product;
    $user_id = get_current_user_id();
    $user_coupons = WC_Loyalty()->rewards->get_user_coupons($user_id);
    $free_product_coupon = null;
    
    foreach ($user_coupons as $coupon) {
        if (isset($coupon['type']) && $coupon['type'] === 'free_product' && !$coupon['is_used']) {
            $free_product_coupon = $coupon;
            break;
        }
    }
    
    if ($free_product_coupon) {
        // Verifică dacă produsul este în lista de produse gratuite
        global $wpdb;
        $table_name = $wpdb->prefix . 'wc_loyalty_free_products';
        $free_products = $wpdb->get_col("SELECT product_id FROM $table_name");
        
        if (in_array($product->get_id(), $free_products)) {
            echo '<div class="wc-loyalty-free-product-notice">';
            echo '<p>' . esc_html__('This product is available FREE with your loyalty coupon!', 'wc-loyalty-gamification') . '</p>';
            echo '<p>' . sprintf(
                esc_html__('Use coupon code: %s at checkout to get this product for free.', 'wc-loyalty-gamification'),
                '<strong>' . esc_html($free_product_coupon['code']) . '</strong>'
            ) . '</p>';
            echo '<button class="wc-loyalty-copy-code" data-code="' . esc_attr($free_product_coupon['code']) . '">' . esc_html__('Copy Code', 'wc-loyalty-gamification') . '</button>';
            echo '</div>';
        }
    }
});

