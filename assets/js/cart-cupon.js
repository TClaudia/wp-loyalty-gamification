/**
 * Fixed version - Properly handle coupon application with better error handling
 */
(function($) {
    'use strict';
    
    // Executăm codul după ce documentul este gata
    $(document).ready(function() {
        // Verificăm dacă jQuery este disponibil
        if (typeof $ !== 'function') {
            console.error('jQuery is not available. Coupon application may not work properly.');
            return;
        }
        
        // Gestionăm click-ul pe butonul de aplicare cupon
        $(document).on('click', '.apply-loyalty-coupon', function(e) {
            e.preventDefault();
            e.stopPropagation(); // Prevenim propagarea evenimentului
            
            var $button = $(this);
            var couponCode = $button.data('coupon');
            
            // Verificăm dacă avem un cod de cupon valid
            if (!couponCode) {
                alert('Invalid coupon code');
                return;
            }
            
            // Verificăm dacă avem datele AJAX necesare
            if (typeof wcLoyaltyData === 'undefined' || !wcLoyaltyData.ajaxurl) {
                alert('Error: Missing configuration data');
                return;
            }
            
            // Afișăm starea de procesare
            $button.prop('disabled', true);
            var originalText = $button.text();
            $button.text('Applying...');
            
            // Facem cererea AJAX pentru aplicarea cuponului
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
                        // Afișăm mesajul de succes
                        if (response.data && response.data.message) {
                            alert(response.data.message);
                        } else {
                            alert('Coupon applied successfully!');
                        }
                        
                        // Reîncărcăm pagina pentru a reflecta modificările
                        window.location.reload();
                    } else {
                        // Afișăm mesajul de eroare
                        var errorMsg = (response && response.data && response.data.message) ? 
                                      response.data.message : 'Failed to apply coupon';
                        alert(errorMsg);
                        $button.prop('disabled', false);
                        $button.text(originalText);
                    }
                },
                error: function(xhr, status, error) {
                    // Afișăm mesajul de eroare detaliat pentru debugging
                    console.error('AJAX Error:', status, error);
                    alert('An error occurred. Please try again.');
                    $button.prop('disabled', false);
                    $button.text(originalText);
                },
                // Adăugăm timeout pentru a preveni cereri blocate
                timeout: 15000
            });
        });
    });
})(jQuery);