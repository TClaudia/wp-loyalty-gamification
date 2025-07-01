<?php
/**
 * Actualizări pentru class-wc-loyalty-cart.php
 * Îmbunătățește gestionarea cupoanelor în coș pentru a ascunde cele utilizate
 */

class WC_Loyalty_Cart_Enhanced {
    
    /**
     * Constructor.
     */
    public function __construct() {
        // Display loyalty coupons in the cart
        add_action('woocommerce_before_cart_table', array($this, 'display_loyalty_coupons'));
        
        // Display loyalty coupons on the checkout page
        add_action('woocommerce_before_checkout_form', array($this, 'display_loyalty_coupons'));
        
        // Hook pentru ascunderea cupoanelor după aplicare
        add_action('woocommerce_applied_coupon', array($this, 'handle_coupon_applied_in_cart'));
        
        // JavaScript pentru gestionarea în timp real
        add_action('wp_footer', array($this, 'add_cart_coupon_management_script'));
    }
    
    /**
     * Display available loyalty coupons in the cart (doar cele active).
     */
    public function display_loyalty_coupons() {
        // Only show for logged-in users and only on cart or checkout pages
        if (!is_user_logged_in() || (!is_cart() && !is_checkout())) {
            return;
        }
        
        $user_id = get_current_user_id();
        
        // Check if WC_Loyalty is properly initialized before using it
        if (!function_exists('WC_Loyalty') || !WC_Loyalty() || !isset(WC_Loyalty()->rewards)) {
            return; // Exit if WC_Loyalty or rewards component is not available
        }
        
        // Check if the get_user_coupons method exists
        if (!method_exists(WC_Loyalty()->rewards, 'get_user_coupons')) {
            return;
        }
        
        $user_coupons = WC_Loyalty()->rewards->get_user_coupons($user_id);
        
        // Filter out used and expired coupons
        $active_coupons = array();
        $current_time = time();
        
        if (is_array($user_coupons)) {
            foreach ($user_coupons as $coupon) {
                $is_used = isset($coupon['is_used']) && $coupon['is_used'];
                $is_expired = isset($coupon['expires']) && strtotime($coupon['expires']) < $current_time;
                
                // Include only active coupons (not used and not expired)
                if (!$is_used && !$is_expired) {
                    $active_coupons[] = $coupon;
                }
            }
        }
        
        if (empty($active_coupons)) {
            return;
        }
        
        // Get currently applied coupons
        $applied_coupons = WC()->cart->get_applied_coupons();
        
        ?>
        <div class="wc-loyalty-cart-coupons" id="wc-loyalty-cart-coupons">
            <h3><?php esc_html_e('Your Loyalty Coupons', 'wc-loyalty-gamification'); ?></h3>
            <div class="wc-loyalty-cart-coupon-list">
                <?php foreach ($active_coupons as $coupon) : 
                    $is_applied = in_array($coupon['code'], $applied_coupons);
                    $is_premium = isset($coupon['tier']) && $coupon['tier'] == 2000;
                ?>
                    <div class="wc-loyalty-cart-coupon <?php echo $is_applied ? 'applied' : ''; ?> <?php echo $is_premium ? 'premium' : ''; ?>" 
                         data-coupon-code="<?php echo esc_attr($coupon['code']); ?>">
                        <div class="wc-loyalty-cart-coupon-info">
                            <span class="wc-loyalty-cart-coupon-discount">
                                <?php printf(esc_html__('%d%% OFF', 'wc-loyalty-gamification'), $coupon['discount']); ?>
                                <?php if ($is_premium): ?>
                                    <span class="premium-label"><?php esc_html_e('Premium', 'wc-loyalty-gamification'); ?></span>
                                <?php endif; ?>
                            </span>
                            <span class="wc-loyalty-cart-coupon-code"><?php echo esc_html($coupon['code']); ?></span>
                            <?php if ($is_premium): ?>
                                <span class="wc-loyalty-cart-coupon-condition">
                                    <?php printf(esc_html__('Max %s lei', 'wc-loyalty-gamification'), wc_loyalty_get_premium_discount_max()); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($is_applied) : ?>
                            <span class="wc-loyalty-coupon-applied"><?php esc_html_e('Applied', 'wc-loyalty-gamification'); ?></span>
                        <?php else : ?>
                            <button type="button" class="button apply-loyalty-coupon" data-coupon="<?php echo esc_attr($coupon['code']); ?>">
                                <?php esc_html_e('Apply', 'wc-loyalty-gamification'); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Handle coupon applied in cart - marchează și ascunde cuponul
     */
    public function handle_coupon_applied_in_cart($coupon_code) {
        if (!is_user_logged_in()) {
            return;
        }
        
        // Verifică dacă este un cupon de loialitate
        if (!wc_loyalty_is_loyalty_coupon($coupon_code)) {
            return;
        }
        
        $user_id = get_current_user_id();
        
        // Marchează cuponul ca utilizat
        wc_loyalty_mark_specific_coupon_used($user_id, $coupon_code);
        
        // Adaugă JavaScript pentru ascunderea imediată
        add_action('wp_footer', function() use ($coupon_code) {
            ?>
            <script>
            jQuery(document).ready(function($) {
                // Ascunde cuponul din interface
                $('.wc-loyalty-cart-coupon[data-coupon-code="<?php echo esc_js($coupon_code); ?>"]').fadeOut(300);
                
                // Actualizează și alte interfețe
                $('.mini-coupon .mini-copy-btn[data-code="<?php echo esc_js($coupon_code); ?>"]').closest('.mini-coupon').fadeOut(300);
            });
            </script>
            <?php
        });
    }
    
    /**
     * Adaugă JavaScript pentru gestionarea cupoanelor în coș
     */
    public function add_cart_coupon_management_script() {
        if (!is_cart() && !is_checkout()) {
            return;
        }
        ?>
        <script>
        jQuery(document).ready(function($) {
            
            // Monitor pentru aplicarea cupoanelor
            $(document.body).on('applied_coupon', function(event, couponCode) {
                if (couponCode && couponCode.indexOf('LOYALTY') === 0) {
                    // Ascunde cuponul din listă
                    setTimeout(function() {
                        var $couponElement = $('.wc-loyalty-cart-coupon[data-coupon-code="' + couponCode + '"]');
                        if ($couponElement.length) {
                            $couponElement.addClass('applied');
                            $couponElement.find('.apply-loyalty-coupon').hide();
                            $couponElement.find('.wc-loyalty-cart-coupon-info').after('<span class="wc-loyalty-coupon-applied">Applied</span>');
                            
                            // Sau ascunde complet după un timp
                            setTimeout(function() {
                                $couponElement.fadeOut(300, function() {
                                    $(this).remove();
                                    
                                    // Verifică dacă mai sunt cupoane active
                                    if ($('.wc-loyalty-cart-coupon').length === 0) {
                                        $('#wc-loyalty-cart-coupons').fadeOut(300);
                                    }
                                });
                            }, 2000);
                        }
                    }, 500);
                }
            });
            
            // Monitor pentru eliminarea cupoanelor
            $(document.body).on('removed_coupon', function(event, couponCode) {
                if (couponCode && couponCode.indexOf('LOYALTY') === 0) {
                    // Nu afișa din nou cuponul eliminat - rămâne ascuns
                    console.log('Loyalty coupon removed: ' + couponCode);
                }
            });
            
            // Îmbunătățire pentru butonul Apply
            $('.apply-loyalty-coupon').on('click', function(e) {
                e.preventDefault();
                
                var $button = $(this);
                var couponCode = $button.data('coupon');
                var $couponElement = $button.closest('.wc-loyalty-cart-coupon');
                
                // Verifică din nou disponibilitatea
                if (!couponCode || typeof wcLoyaltyData === 'undefined') {
                    alert('Error: Missing configuration data');
                    return;
                }
                
                // Afișează starea de încărcare
                $button.prop('disabled', true);
                var originalText = $button.text();
                $button.text('Applying...');
                
                // Adaugă clasa de încărcare la elementul cupon
                $couponElement.addClass('applying');
                
                $.ajax({
                    type: 'POST',
                    url: wcLoyaltyData.ajaxurl,
                    data: {
                        action: 'apply_loyalty_coupon',
                        nonce: wcLoyaltyData.nonce,
                        coupon_code: couponCode
                    },
                    success: function(response) {
                        if (response && response.success) {
                            // Marchează ca aplicat
                            $couponElement.removeClass('applying').addClass('applied');
                            $button.hide();
                            $couponElement.find('.wc-loyalty-cart-coupon-info').after('<span class="wc-loyalty-coupon-applied">Applied</span>');
                            
                            // Reîncarcă pagina pentru a reflecta modificările
                            setTimeout(function() {
                                window.location.reload();
                            }, 1000);
                            
                        } else {
                            // Afișează eroarea
                            var errorMsg = (response && response.data && response.data.message) ? 
                                          response.data.message : 'Failed to apply coupon';
                            alert(errorMsg);
                            
                            // Resetează butonul
                            $couponElement.removeClass('applying');
                            $button.prop('disabled', false);
                            $button.text(originalText);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', status, error);
                        alert('An error occurred. Please try again.');
                        
                        // Resetează butonul
                        $couponElement.removeClass('applying');
                        $button.prop('disabled', false);
                        $button.text(originalText);
                    }
                });
            });
        });
        </script>
        
        <style>
        /* Stiluri pentru stările cupoanelor în coș */
        .wc-loyalty-cart-coupon.applying {
            opacity: 0.7;
            pointer-events: none;
        }
        
        .wc-loyalty-cart-coupon.applied {
            background: linear-gradient(135deg, #28a745, #20c997);
        }
        
        .wc-loyalty-cart-coupon.applied .premium-label {
            background-color: rgba(255, 255, 255, 0.3);
        }
        
        .wc-loyalty-coupon-applied {
            background-color: rgba(255, 255, 255, 0.2);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            color: white;
        }
        
        .wc-loyalty-cart-coupon-condition {
            font-size: 11px;
            opacity: 0.9;
            display: block;
            margin-top: 2px;
        }
        </style>
        <?php
    }
}

// Înlocuiește clasa originală
// În wc-loyalty-gamification.php, înlocuiește inițializarea:
// $this->cart = new WC_Loyalty_Cart();
// cu:
// $this->cart = new WC_Loyalty_Cart_Enhanced();

/**
 * Funcție pentru actualizarea în timp real a interfețelor
 * Adaugă în wc-loyalty-gamification.php
 */
add_action('woocommerce_applied_coupon', 'wc_loyalty_handle_realtime_coupon_application');

function wc_loyalty_handle_realtime_coupon_application($coupon_code) {
    if (!is_user_logged_in()) {
        return;
    }
    
    // Verifică dacă este un cupon de loialitate
    if (!wc_loyalty_is_loyalty_coupon($coupon_code)) {
        return;
    }
    
    $user_id = get_current_user_id();
    
    // Marchează cuponul ca utilizat
    $success = wc_loyalty_mark_specific_coupon_used($user_id, $coupon_code);
    
    if ($success) {
        // Setează o sesiune pentru a ști că trebuie să actualizăm interfețele
        if (!session_id()) {
            session_start();
        }
        $_SESSION['wc_loyalty_coupon_used'] = $coupon_code;
        
        // Log pentru debugging
        wc_loyalty_debug_log("Coupon $coupon_code marked as used for user $user_id in real-time");
    }
}

/**
 * Adaugă notificare în sesiune pentru cupoanele utilizate
 */
add_action('wp_footer', 'wc_loyalty_show_coupon_used_notification');

function wc_loyalty_show_coupon_used_notification() {
    if (!session_id()) {
        session_start();
    }
    
    if (isset($_SESSION['wc_loyalty_coupon_used'])) {
        $coupon_code = $_SESSION['wc_loyalty_coupon_used'];
        unset($_SESSION['wc_loyalty_coupon_used']);
        
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Afișează notificare
            if (typeof showNotification === 'function') {
                showNotification('Cuponul <?php echo esc_js($coupon_code); ?> a fost aplicat și marcat ca utilizat!', 'success');
            }
            
            // Actualizează toate interfețele pentru a ascunde cuponul
            setTimeout(function() {
                $('[data-coupon-code="<?php echo esc_js($coupon_code); ?>"]').fadeOut(300);
                $('.mini-copy-btn[data-code="<?php echo esc_js($coupon_code); ?>"]').closest('.mini-coupon').fadeOut(300);
            }, 1000);
        });
        </script>
        <?php
    }
}
?>