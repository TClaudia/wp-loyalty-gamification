/**
 * Fixed version - Properly handle coupon application with better error handling
 */
(function($) {
    'use strict';
    
    // Execute code when document is ready
    $(document).ready(function() {
        // Check if jQuery is available
        if (typeof $ !== 'function') {
            console.error('jQuery is not available. Coupon application may not work properly.');
            return;
        }
        
        // Handle click on apply coupon button
        $(document).on('click', '.apply-loyalty-coupon', function(e) {
            e.preventDefault();
            e.stopPropagation(); // Prevent event propagation
            
            var $button = $(this);
            var couponCode = $button.data('coupon');
            
            // Check if we have a valid coupon code
            if (!couponCode) {
                alert('Invalid coupon code');
                return;
            }
            
            // Check if we have the necessary AJAX data
            if (typeof wcLoyaltyData === 'undefined' || !wcLoyaltyData.ajaxurl) {
                alert('Error: Missing configuration data');
                return;
            }
            
            // Show processing state
            $button.prop('disabled', true);
            var originalText = $button.text();
            $button.text('Applying...');
            
            // Make the AJAX request to apply the coupon
            $.ajax({
                type: 'POST',
                url: wcLoyaltyData.ajaxurl,
                data: {
                    action: 'apply_loyalty_coupon',
                    nonce: wcLoyaltyData.nonce,
                    coupon_code: couponCode
                },
                success: function(response) {
                    try {
                        // Handle response based on its type
                        if (typeof response === 'string') {
                            // Try to parse JSON string
                            response = JSON.parse(response);
                        }
                        
                        if (response && response.success) {
                            // Show success message
                            alert(response.data && response.data.message ? 
                                 response.data.message : 'Coupon applied successfully!');
                            
                            // Reload the page to reflect changes
                            window.location.reload();
                        } else {
                            // Show error message
                            var errorMsg = (response && response.data && response.data.message) ? 
                                          response.data.message : 'Failed to apply coupon';
                            alert(errorMsg);
                            $button.prop('disabled', false);
                            $button.text(originalText);
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                        console.log('Raw response:', response);
                        alert('Error applying coupon. Please try again.');
                        $button.prop('disabled', false);
                        $button.text(originalText);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                    // Log response text for debugging
                    if (xhr && xhr.responseText) {
                        console.log('Response text:', xhr.responseText);
                    }
                    alert('An error occurred. Please try again.');
                    $button.prop('disabled', false);
                    $button.text(originalText);
                }
            });
        });
    });
})(jQuery);