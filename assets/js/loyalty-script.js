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
                
                // Manually bind copy buttons again
                setTimeout(function(){
                    WCLoyalty.bindCopyButtons();
                }, 500);
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

            // Make sure the canvas doesn't block clicks
            $('.wc-loyalty-progress-circle canvas').css({
                'pointer-events': 'none',
                'position': 'absolute'
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
            $(document).on('click', '.claim-free-product', function(e) {
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
        
        // Bind copy buttons directly
        bindCopyButtons: function() {
            $('.wc-loyalty-copy-code').each(function() {
                var $button = $(this);
                
                $button.off('click').on('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    var couponCode = $button.data('code');
                    var originalText = $button.text();
                    
                    // Simple copy method that works in most browsers
                    WCLoyalty.simpleCopyToClipboard(couponCode);
                    
                    // Update button text
                    $button.text('Copied!');
                    
                    // Show notification
                    WCLoyalty.showNotification('Coupon code copied to clipboard!', 'success');
                    
                    // Reset button text after a delay
                    setTimeout(function() {
                        $button.text(originalText);
                    }, 2000);
                });
            });
        },
        
        // A very simple copy method that works in most browsers
        simpleCopyToClipboard: function(text) {
            // Create textarea
            var textarea = document.createElement('textarea');
            textarea.value = text;
            
            // Make it not visible
            textarea.style.position = 'absolute';
            textarea.style.left = '-9999px';
            textarea.style.top = '0';
            textarea.setAttribute('readonly', '');
            
            // Add to body, select, copy, remove
            document.body.appendChild(textarea);
            textarea.select();
            textarea.setSelectionRange(0, 99999);
            document.execCommand('copy');
            document.body.removeChild(textarea);
        },
        
        // Initialize coupon functionality
        initCoupons: function() {
            // Direct binding of copy buttons
            this.bindCopyButtons();
            
            // Handle apply loyalty coupon
            $(document).off('click', '.apply-loyalty-coupon');
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
                            
                            // Refresh the page
                            setTimeout(function() {
                                window.location.reload();
                            }, 1500);
                        } else {
                            // Show error message
                            WCLoyalty.showNotification(response.data.message || 'Failed to apply coupon', 'error');
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
        
        // Rebind copy buttons after page load
        setTimeout(function(){
            WCLoyalty.bindCopyButtons();
        }, 500);
    });

})(jQuery);