/**
 * Fixed version - Properly handle coupon application with better error handling
 */
jQuery(document).ready(function($) {
    // Handle apply loyalty coupon button click
    $(document).on('click', '.apply-loyalty-coupon', function(e) {
        e.preventDefault();
        e.stopPropagation(); // Prevent event bubbling
        
        var $button = $(this);
        var couponCode = $button.data('coupon');
        
        // Verify we have a valid coupon code
        if (!couponCode) {
            alert('Invalid coupon code');
            return;
        }
        
        // Show processing status
        $button.prop('disabled', true);
        $button.text('Applying...');
        
        // Make AJAX request to apply coupon
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
                    // Show success message
                    if (response.data && response.data.message) {
                        alert(response.data.message);
                    } else {
                        alert('Coupon applied successfully!');
                    }
                    
                    // Reload the page to reflect changes
                    window.location.reload();
                } else {
                    // Show error message
                    var errorMsg = (response && response.data && response.data.message) ? 
                                  response.data.message : 'Failed to apply coupon';
                    alert(errorMsg);
                    $button.prop('disabled', false);
                    $button.text('Apply');
                }
            },
            error: function(xhr, status, error) {
                // Show detailed error message for debugging
                console.error('AJAX Error:', status, error);
                alert('An error occurred. Please try again.');
                $button.prop('disabled', false);
                $button.text('Apply');
            }
        });
    });
});