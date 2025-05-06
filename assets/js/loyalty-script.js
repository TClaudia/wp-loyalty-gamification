/**
 * WooCommerce Loyalty Gamification - Main Frontend Script
 */
(function($) {
    'use strict';

    // Check if jQuery is properly loaded
    if (typeof $ !== 'function') {
        console.error('jQuery is not available. Loyalty script will not function properly.');
        return;
    }

    // Loyalty Program functionality
    var WCLoyalty = {
        // Initialize the functionality
        init: function() {
            this.initModal();
            
            // Try initializing circle progress, but don't fail if it doesn't work
            try {
                this.initCircleProgress();
            } catch (e) {
                console.log('Circle Progress initialization failed:', e);
                // Use a simple fallback for progress display
                this.initSimpleProgress();
            }
            
            this.initCoupons();
            this.initNotifications();
            this.initCheckin();
        },

        // Initialize modal functionality
        initModal: function() {
            var modal = $('#wc-loyalty-modal');
            var button = $('#wc-loyalty-toggle-btn');
            var closeBtn = $('.wc-loyalty-close');
            
            if (!modal.length || !button.length) return;
            
            // Open modal when clicking the button
            button.on('click', function(e) {
                e.preventDefault();
                modal.fadeIn(300);
                
                // Try circle progress, fall back if it fails
                try {
                    WCLoyalty.initCircleProgress();
                } catch (e) {
                    console.log('Circle Progress initialization failed:', e);
                    WCLoyalty.initSimpleProgress();
                }
                
                // Manually bind copy buttons again
                setTimeout(function(){
                    WCLoyalty.bindCopyButtons();
                }, 500);
            });
            
            // Close modal when clicking the close button
            if (closeBtn.length) {
                closeBtn.on('click', function() {
                    modal.fadeOut(300);
                });
            }
            
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

        // Simple progress display as a fallback
        initSimpleProgress: function() {
            var progressCircle = $('.wc-loyalty-progress-circle');
            if (progressCircle.length === 0) return;
            
            var progress = progressCircle.data('progress') || 0;
            
            // Check if we already created a simple progress display
            if (progressCircle.find('.simple-progress-container').length) return;
            
            // Create simple progress bar
            var simpleProgress = $('<div class="simple-progress-fill"></div>').css({
                'width': progress + '%',
                'height': '10px',
                'background-color': '#7952b3',
                'border-radius': '5px'
            });
            
            var progressContainer = $('<div class="simple-progress-container"></div>').css({
                'width': '100%',
                'height': '10px',
                'background-color': '#f0f0f0',
                'border-radius': '5px',
                'margin-top': '10px',
                'overflow': 'hidden'
            });
            
            progressContainer.append(simpleProgress);
            progressCircle.append(progressContainer);
            
            // Add animation effect to points counter
            var pointsCount = $('.wc-loyalty-points-count');
            if (pointsCount.length) {
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
            }
        },

        // Initialize circle progress
        initCircleProgress: function() {
            var progressCircle = $('.wc-loyalty-progress-circle');
            if (progressCircle.length === 0) return;
            
            // Check if jQuery and the circleProgress plugin are available
            if (typeof $.fn === 'undefined' || typeof $.fn.circleProgress !== 'function') {
                console.log('Circle Progress plugin not available, using fallback');
                this.initSimpleProgress();
                return;
            }
            
            var progress = progressCircle.data('progress') || 0;
            
            try {
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
                if (pointsCount.length) {
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
                }
            } catch (e) {
                console.error('Error initializing circle progress:', e);
                this.initSimpleProgress();
            }
        },
        
        // Initialize coupon functionality
        initCoupons: function() {
            // Bind apply coupon button in cart
            $(document).on('click', '.apply-loyalty-coupon', function(e) {
                e.preventDefault();
                
                var $button = $(this);
                var couponCode = $button.data('coupon');
                
                // Verify we have the data we need
                if (!couponCode || typeof wcLoyaltyData === 'undefined' || !wcLoyaltyData.ajaxurl) {
                    WCLoyalty.showNotification('Error: Missing coupon data', 'error');
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
                            WCLoyalty.showNotification(response.data && response.data.message ? response.data.message : 'Coupon applied successfully!', 'success');
                            
                            // Reload the page to reflect changes
                            window.location.reload();
                        } else {
                            // Show error message
                            WCLoyalty.showNotification(response && response.data && response.data.message ? response.data.message : 'Failed to apply coupon', 'error');
                            $button.prop('disabled', false);
                            $button.text('Apply');
                        }
                    },
                    error: function(xhr, status, error) {
                        // Show error message with details for debugging
                        console.error('AJAX Error:', status, error);
                        WCLoyalty.showNotification('An error occurred. Please try again.', 'error');
                        $button.prop('disabled', false);
                        $button.text('Apply');
                    }
                });
            });
            
            // Bind copy buttons
            this.bindCopyButtons();
        },
        
        // Bind copy buttons directly
        bindCopyButtons: function() {
            // Handle standard coupon buttons
            $('.wc-loyalty-copy-code').each(function() {
                var $button = $(this);

                $button.off('click').on('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    var couponCode = $button.data('code');
                    if (!couponCode) return;
                    
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

            // Handle minimalist coupon buttons
            var miniCopyButtons = $('.mini-copy-btn');
            miniCopyButtons.each(function() {
                var $button = $(this);
                
                $button.off('click').on('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    var couponCode = $button.data('code');
                    if (!couponCode) return;
                    
                    var originalText = $button.text();
                    
                    // Copy to clipboard
                    WCLoyalty.simpleCopyToClipboard(couponCode);
                    
                    // Visual feedback
                    $button.text('Copied!');
                    $button.addClass('copied');
                    
                    // Add animation to the parent coupon
                    $button.parent().addClass('copy-animation');
                    
                    // Reset after delay
                    setTimeout(function() {
                        $button.text(originalText);
                        $button.removeClass('copied');
                        $button.parent().removeClass('copy-animation');
                    }, 1500);
                    
                    // Show notification
                    WCLoyalty.showNotification('Coupon code copied to clipboard!', 'success');
                });
            });
        },
        
        // A simple copy method that works in most browsers
        simpleCopyToClipboard: function(text) {
            try {
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
                
                return true;
            } catch (e) {
                console.error('Copy to clipboard failed:', e);
                return false;
            }
        },
        
        // Initialize notifications
        initNotifications: function() {
            // Add notification handlers if needed
            // Currently handled by showNotification
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
        },
        
        // Initialize check-in functionality
        initCheckin: function() {
            var self = this;
            var checkinBtn = $('#wc-loyalty-checkin-btn');
            
            if (checkinBtn.length) {
                checkinBtn.on('click', function(e) {
                    e.preventDefault();
                    
                    if (typeof wcLoyaltyData === 'undefined' || !wcLoyaltyData.ajaxurl) {
                        self.showNotification('Error: Missing AJAX configuration', 'error');
                        return;
                    }
                    
                    // Change button state
                    $(this).prop('disabled', true);
                    $(this).text('Checking in...');
                    
                    // Make AJAX request
                    $.ajax({
                        type: 'POST',
                        url: wcLoyaltyData.ajaxurl,
                        data: {
                            action: 'wc_loyalty_daily_checkin',
                            nonce: wcLoyaltyData.nonce
                        },
                        success: function(response) {
                            if (response && response.success) {
                                // Show success notification
                                self.showNotification(response.data && response.data.message ? response.data.message : 'Check-in successful!', 'success');
                                
                                // Update the display - reload the modal content for simplicity
                                setTimeout(function() {
                                    location.reload();
                                }, 1500);
                            } else {
                                // Show error message
                                self.showNotification(response && response.data && response.data.message ? response.data.message : 'Failed to check in. Please try again.', 'error');
                                checkinBtn.prop('disabled', false);
                                checkinBtn.text('Check In Now');
                            }
                        },
                        error: function(xhr, status, error) {
                            // Show error message with details
                            console.error('AJAX Error:', status, error);
                            self.showNotification('An error occurred. Please try again.', 'error');
                            checkinBtn.prop('disabled', false);
                            checkinBtn.text('Check In Now');
                        }
                    });
                });
            }
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        // Debug info
        console.log('jQuery version:', $.fn.jquery);
        console.log('circleProgress available:', typeof $.fn.circleProgress === 'function');
        
        // Initialize with a small delay to make sure all other scripts are loaded
        setTimeout(function() {
            try {
                WCLoyalty.init();
            } catch (e) {
                console.error('Error initializing WCLoyalty:', e);
            }
        }, 500);
        
        // Rebind copy buttons after page load
        setTimeout(function(){
            try {
                WCLoyalty.bindCopyButtons();
            } catch (e) {
                console.error('Error binding copy buttons:', e);
            }
        }, 1000);
    });

})(jQuery);