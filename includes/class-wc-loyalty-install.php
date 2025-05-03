<?php
/**
 * WC Loyalty Installation Handler
 *
 * Handles installation and setup of WooCommerce Loyalty Gamification plugin.
 */

if (!defined('WPINC')) {
    die;
}

/**
 * WC_Loyalty_Install Class
 */
class WC_Loyalty_Install {
    
    /**
     * Plugin activation.
     */
    public static function activate() {
        // Create database tables
        self::create_tables();
        
        // Set default options
        self::set_default_options();
        
        // Create necessary directories and files
        self::create_files();

        update_option('wc_loyalty_flush_needed', 'yes');
        
        // Flush rewrite rules
        add_action('init', function() {
            add_rewrite_endpoint('loyalty-points', EP_ROOT | EP_PAGES);
            add_rewrite_endpoint('loyalty-rewards', EP_ROOT | EP_PAGES);
            flush_rewrite_rules();
        }, 20);
    }
    
    /**
     * Plugin deactivation.
     */
    public static function deactivate() {
        flush_rewrite_rules();
    }
    
    /**
     * Create database tables.
     */
    private static function create_tables() {
        global $wpdb;
        
        $wpdb->hide_errors();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Create points table
        $table_name = $wpdb->prefix . 'wc_loyalty_points';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            points int(11) NOT NULL DEFAULT 0,
            points_history longtext,
            rewards_claimed longtext,
            update_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY user_id (user_id)
        ) $charset_collate;";
        
        dbDelta($sql);
    }
    
    /**
     * Set default options.
     */
    private static function set_default_options() {
        // Points settings
        add_option('wc_loyalty_points_per_euro', 1);
        add_option('wc_loyalty_points_for_review', 50);
        add_option('wc_loyalty_premium_discount_max', 400);
            
        // Membership tiers
        add_option('wc_loyalty_tiers', serialize(array(
            'bronze' => array(
                'name' => 'Bronze',
                'min_points' => 0,
                'color' => '#cd7f32',
                'perks' => 'Welcome to our loyalty program!'
            ),
            'silver' => array(
                'name' => 'Silver',
                'min_points' => 500,
                'color' => '#c0c0c0',
                'perks' => 'Enjoy special birthday offers and early sale access.'
            ),
            'gold' => array(
                'name' => 'Gold',
                'min_points' => 1000,
                'color' => '#ffd700',
                'perks' => 'Exclusive promotions and priority customer service.'
            ),
            'platinum' => array(
                'name' => 'Platinum', 
                'min_points' => 2000,
                'color' => '#e5e4e2',
                'perks' => 'VIP service and exclusive product access.'
            )
        )));
    
        // Reward tiers with premium discount at 2000
        add_option('wc_loyalty_reward_tiers', serialize(array(
            500 => array('type' => 'discount', 'value' => 20),
            1000 => array('type' => 'discount', 'value' => 40),
            1500 => array('type' => 'free_shipping', 'value' => true),
            2000 => array('type' => 'discount', 'value' => 60, 'max_order' => 400)
        )));
    }
    
    /**
     * Create necessary files and directories.
     */
    private static function create_files() {
        // Array of directories to create
        $directories = array(
            WC_LOYALTY_PLUGIN_DIR . 'assets',
            WC_LOYALTY_PLUGIN_DIR . 'assets/css',
            WC_LOYALTY_PLUGIN_DIR . 'assets/js',
            WC_LOYALTY_PLUGIN_DIR . 'assets/images',
            WC_LOYALTY_PLUGIN_DIR . 'includes',
            WC_LOYALTY_PLUGIN_DIR . 'templates',
            WC_LOYALTY_PLUGIN_DIR . 'languages'
        );
        
        // Create directories if they don't exist
        foreach ($directories as $directory) {
            if (!file_exists($directory)) {
                wp_mkdir_p($directory);
            }
        }
        
        // Create or update .htaccess file to protect uploads directory
        $htaccess_file = WC_LOYALTY_PLUGIN_DIR . '.htaccess';
        if (!file_exists($htaccess_file)) {
            $htaccess_content = "# Apache 2.4 and above
<IfModule mod_authz_core.c>
    Require all denied
</IfModule>

# Apache 2.3 and below
<IfModule !mod_authz_core.c>
    Order deny,allow
    Deny from all
</IfModule>
";
            @file_put_contents($htaccess_file, $htaccess_content);
        }
        
        // Create index.php files to prevent directory listing
        $index_file = WC_LOYALTY_PLUGIN_DIR . 'index.php';
        if (!file_exists($index_file)) {
            $index_content = "<?php\n// Silence is golden.";
            @file_put_contents($index_file, $index_content);
            
            // Place index.php in each subdirectory
            foreach ($directories as $directory) {
                $subdir_index = trailingslashit($directory) . 'index.php';
                if (!file_exists($subdir_index)) {
                    @file_put_contents($subdir_index, $index_content);
                }
            }
        }
    }
}