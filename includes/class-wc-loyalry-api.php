<?php
class WC_Loyalty_Api {
    /**
     * Constructor
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    /**
     * Înregistrează rutele API
     */
    public function register_routes() {
        register_rest_route('wc-loyalty/v1', '/points', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_points'),
            'permission_callback' => array($this, 'check_permission')
        ));
        
        register_rest_route('wc-loyalty/v1', '/coupons', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_coupons'),
            'permission_callback' => array($this, 'check_permission')
        ));
        
        register_rest_route('wc-loyalty/v1', '/free-products', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_free_products'),
            'permission_callback' => array($this, 'check_permission')
        ));
    }
    
    /**
     * Verifică permisiunea
     */
    public function check_permission() {
        return is_user_logged_in();
    }
    
    /**
     * Obține punctele utilizatorului
     */
    public function get_points() {
        $user_id = get_current_user_id();
        
        return array(
            'display_points' => WC_Loyalty()->points->get_user_display_points($user_id),
            'total_points' => WC_Loyalty()->points->get_user_points($user_id),
            'cycle_level' => WC_Loyalty()->points->get_user_cycle_level($user_id),
            'progress' => (WC_Loyalty()->points->get_user_display_points($user_id) / 2000) * 100
        );
    }
    
    /**
     * Obține cupoanele utilizatorului
     */
    public function get_coupons() {
        $user_id = get_current_user_id();
        return WC_Loyalty()->rewards->get_user_coupons($user_id);
    }
    
    /**
     * Obține produsele gratuite disponibile
     */
    public function get_free_products() {
        return WC_Loyalty()->get_free_products();
    }
}

// Inițializează API-ul
new WC_Loyalty_Api();