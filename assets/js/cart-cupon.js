jQuery(document).ready(function($) {
    // Handle apply loyalty coupon button click
    $(document).on('click', '.apply-loyalty-coupon', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var couponCode = $button.data('coupon');
        
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
                if (response.success) {
                    // Show success message as alert
                    alert(response.data.message);
                    
                    // Reload the page to reflect changes
                    window.location.reload();
                } else {
                    // Show error message
                    alert(response.data.message || 'Failed to apply coupon');
                    $button.prop('disabled', false);
                    $button.text('Apply');
                }
            },
            error: function() {
                // Show error message
                alert('An error occurred. Please try again.');
                $button.prop('disabled', false);
                $button.text('Apply');
            }
        });
    });
});