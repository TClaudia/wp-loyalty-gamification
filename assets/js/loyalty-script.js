/**
 * WooCommerce Loyalty Gamification - Main Frontend Script
 */
(function($) {
    'use strict';

    // Loyalty Program functionality
    var WCLoyalty = {
        // Initialize the functionality
        init: function() {
            this.initModal();
            this.initCircleProgress();
            this.initClaimProduct();
            this.initCoupons();
        },

        // Initialize modal functionality
        initModal: function() {
            var modal = $('#wc-loyalty-modal');
            var button = $('#wc-loyalty-toggle-btn');
            var closeBtn = $('.wc-loyalty-close');
            
            // Open modal when clicking the button
            button.on('click', function(e) {
                e.preventDefault();
                modal.fadeIn(300);
                WCLoyalty.initCircleProgress();
            });
            
            // Close modal when clicking the close button
            closeBtn.on('click', function() {
                modal.fadeOut(300);
            });
            
            // Close modal when clicking outside
            $(window).on('click', function(event) {
                if (event.target === modal[0]) {
                    modal.fadeOut(300);
                }
            });

            // Prevent propagation of click events inside modal content
            $('.wc-loyalty-modal-content').on('click', function(e) {
                e.stopPropagation();
            });
        },

        // Initialize circle progress
        initCircleProgress: function() {
            var progressCircle = $('.wc-loyalty-progress-circle');
            if (progressCircle.length === 0) return;
            
            var progress = progressCircle.data('progress');
            
            progressCircle.circleProgress({
                value: progress / 100,
                size: 180,
                thickness: 15,
                fill: {
                    gradient: ["#7952b3", "#fd7e14"]
                },
                emptyFill: "#f0f0f0",
                animation: {
                    duration: 1200
                },
                lineCap: 'round'
            });

            // Add animation effect to points counter
            var pointsCount = $('.wc-loyalty-points-count');
            var targetPoints = parseInt(pointsCount.text(), 10);
            
            $({countNum: 0}).animate({countNum: targetPoints}, {
                duration: 1000,
                easing: 'swing',
                step: function() {
                    pointsCount.text(Math.floor(this.countNum));
                },
                complete: function() {
                    pointsCount.text(targetPoints);
                }
            });
        },

        // Initialize claim product functionality
        initClaimProduct: function() {
            $('.claim-free-product').on('click', function(e) {
                e.preventDefault();
                
                var $button = $(this);
                var productId = $button.data('product-id');
                
                $button.prop('disabled', true).text('Processing...');
                
                $.ajax({
                    type: 'POST',
                    url: wcLoyaltyData.ajaxurl,
                    data: {
                        action: 'claim_loyalty_reward',
                        nonce: wcLoyaltyData.nonce,
                        reward_type: 'free_product',
                        product_id: productId
                    },
                    success: function(response) {
                        if (response.success) {
                            // Show success message
                            WCLoyalty.showNotification(response.data.message, 'success');
                            
                            // Redirect if provided
                            if (response.data.redirect) {
                                setTimeout(function() {
                                    window.location.href = response.data.redirect;
                                }, 1500);
                            } else {
                                $button.text('Claimed!').addClass('claimed');
                                
                                // Refresh page after a delay
                                setTimeout(function() {
                                    window.location.reload();
                                }, 2000);
                            }
                        } else {
                            // Show error message
                            WCLoyalty.showNotification(response.data.message, 'error');
                            $button.prop('disabled', false).text('Claim This');
                        }
                    },
                    error: function() {
                        // Show error message
                        WCLoyalty.showNotification('An error occurred. Please try again.', 'error');
                        $button.prop('disabled', false).text('Claim This');
                    }
                });
            });
        },
        
        // Initialize coupon functionality
        initCoupons: function() {
            // Handle copy coupon code
            $(document).on('click', '.wc-loyalty-copy-code', function(e) {
                e.preventDefault();
                
                var couponCode = $(this).data('code');
                var $button = $(this);
                
                // Create a temporary input element
                var $temp = $("<input>");
                $("body").append($temp);
                $temp.val(couponCode).select();
                
                // Copy the text
                document.execCommand("copy");
                $temp.remove();
                
                // Change button text to indicate copied
                var originalText = $button.text();
                $button.text('Copied!');
                
                // Reset button text after a delay
                setTimeout(function() {
                    $button.text(originalText);
                }, 2000);
                
                // Show notification
                WCLoyalty.showNotification('Coupon code copied to clipboard!', 'success');
            });
            
            // Handle apply loyalty coupon
            $(document).on('click', '.apply-loyalty-coupon', function(e) {
                e.preventDefault();
                
                var $button = $(this);
                var couponCode = $button.data('coupon');
                
                // Disable button and show loading state
                $button.prop('disabled', true).text('Applying...');
                
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
                            // Show success message
                            WCLoyalty.showNotification(response.data.message, 'success');
                            
                            // Refresh the cart
                            $('body').trigger('update_checkout');
                            $('body').trigger('wc_update_cart');
                            
                            // Reload the page for a complete refresh
                            setTimeout(function() {
                                window.location.reload();
                            }, 1000);
                        } else {
                            // Show error message
                            WCLoyalty.showNotification(response.data.message, 'error');
                            $button.prop('disabled', false).text('Apply');
                        }
                    },
                    error: function() {
                        // Show error message
                        WCLoyalty.showNotification('An error occurred. Please try again.', 'error');
                        $button.prop('disabled', false).text('Apply');
                    }
                });
            });
        },

        // Show notification
        showNotification: function(message, type) {
            // Remove any existing notifications
            $('.wc-loyalty-notification-popup').remove();
            
            // Create notification element
            var notification = $('<div class="wc-loyalty-notification-popup wc-loyalty-notification-' + type + '">' + message + '</div>');
            
            // Append to body
            $('body').append(notification);
            
            // Show notification
            setTimeout(function() {
                notification.addClass('show');
            }, 10);
            
            // Hide notification after a delay
            setTimeout(function() {
                notification.removeClass('show');
                setTimeout(function() {
                    notification.remove();
                }, 300);
            }, 4000);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        WCLoyalty.init();
    });

})(jQuery);