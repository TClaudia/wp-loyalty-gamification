<?php
/**
 * WC Loyalty Admin Settings
 *
 * Handles admin settings and configuration.
 */

if (!defined('WPINC')) {
    die;
}

/**
 * WC_Loyalty_Admin Class
 */
class WC_Loyalty_Admin {

    /**
     * Add admin scripts and styles.
     *
     * @param string $hook Current admin page
     */
    public function admin_scripts($hook) {
        if ('woocommerce_page_wc-loyalty-settings' !== $hook) {
            return;
        }
        
        // Enqueue WooCommerce product search
        wp_enqueue_script('wc-enhanced-select');
        wp_enqueue_style('woocommerce_admin_styles');
        
        // Enqueue admin styles
        wp_enqueue_style(
            'wc-loyalty-admin-style',
            WC_LOYALTY_PLUGIN_URL . 'assets/css/admin-style.css',
            array(),
            WC_LOYALTY_VERSION
        );
        
        // Enqueue admin script with proper dependencies
        wp_enqueue_script(
            'wc-loyalty-admin-script',
            WC_LOYALTY_PLUGIN_URL . 'assets/js/admin-script.js',
            array('jquery', 'wp-util', 'wc-enhanced-select', 'wp-data'), // Add wp-data dependency
            WC_LOYALTY_VERSION,
            true
        );
    }
    
    /**
     * Constructor.
     */
    public function __construct() {
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
        
        // Add admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        
        // Process admin actions
        add_action('admin_init', array($this, 'process_admin_actions'));
        
        // Save reward tiers
        add_action('admin_init', array($this, 'save_reward_tiers'));
    }
    
    /**
     * Add admin menu.
     */
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            __('Loyalty Program', 'wc-loyalty-gamification'),
            __('Loyalty Program', 'wc-loyalty-gamification'),
            'manage_woocommerce',
            'wc-loyalty-settings',
            array($this, 'display_settings_page')
        );
    }
    
    /**
     * Register settings.
     */
    public function register_settings() {
        register_setting('wc_loyalty_settings', 'wc_loyalty_points_per_euro');
        register_setting('wc_loyalty_settings', 'wc_loyalty_points_for_review');
        register_setting('wc_loyalty_settings', 'wc_loyalty_reward_tiers');
    }
    
    /**
     * Display settings page.
     */
    public function display_settings_page() {
        include WC_LOYALTY_PLUGIN_DIR . 'templates/admin/settings.php';
    }
    
    /**
     * Process actions from admin settings page.
     */
    public function process_admin_actions() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'wc-loyalty-settings') {
            return;
        }
        
        // Add free product
        if (isset($_POST['action']) && $_POST['action'] === 'add_product' && isset($_POST['product_id'])) {
            check_admin_referer('add_free_product');
            
            global $wpdb;
            $table_name = $wpdb->prefix . 'wc_loyalty_free_products';
            $product_id = intval($_POST['product_id']);
            
            // Check if product exists and is valid
            $product = wc_get_product($product_id);
            if ($product) {
                $wpdb->insert(
                    $table_name,
                    array('product_id' => $product_id),
                    array('%d')
                );
                
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-success is-dismissible"><p>' . __('Product added to free products list.', 'wc-loyalty-gamification') . '</p></div>';
                });
            }
        }
        
        // Remove free product
        if (isset($_GET['action']) && $_GET['action'] === 'remove_product' && isset($_GET['product_id'])) {
            check_admin_referer('remove_free_product');
            
            global $wpdb;
            $table_name = $wpdb->prefix . 'wc_loyalty_free_products';
            $product_id = intval($_GET['product_id']);
            
            $wpdb->delete(
                $table_name,
                array('id' => $product_id),
                array('%d')
            );
            
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Product removed from free products list.', 'wc-loyalty-gamification') . '</p></div>';
            });
        }
    }
    
    /**
     * Save serialized reward tiers.
     */
    public function save_reward_tiers() {
        if (isset($_POST['wc_loyalty_reward_tiers'])) {
            $tiers_json = stripslashes($_POST['wc_loyalty_reward_tiers']);
            $tiers = json_decode($tiers_json, true);
            
            if (is_array($tiers)) {
                update_option('wc_loyalty_reward_tiers', serialize($tiers));
            }
        }
    }
    
    /**
     * Get all free products.
     *
     * @return array Products
     */
    public function get_free_products() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wc_loyalty_free_products';
        return $wpdb->get_results("SELECT * FROM $table_name");
    }
}