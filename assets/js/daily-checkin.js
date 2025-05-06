/**
 * Daily Check-in Functionality - Direct Fix Version
 * This version avoids using circleProgress directly
 */
(function($) {
    'use strict';

    // Daily Check-in functionality
    var WCLoyaltyDaily = {
        // Initialize the functionality
        init: function() {
            this.initDailyButton();
            this.initDailyModal();
            this.initAccountButton();
        },

        // Initialize daily claim button
        initDailyButton: function() {
            const self = this;
            const claimBtn = $('.wc-loyalty-daily-claim');
            
            if (claimBtn.length === 0) return;
            
            claimBtn.on('click', function(e) {
                e.preventDefault();
                
                // If button is disabled, show message and return
                if ($(this).hasClass('disabled')) {
                    self.showNotification('You have already claimed your daily points today!', 'info');
                    return;
                }
                
                // Show daily modal
                self.showDailyModal();
            });
        },

        // Initialize daily modal
        initDailyModal: function() {
            const self = this;
            const modal = $('.wc-loyalty-daily-modal');
            const closeBtn = $('.wc-loyalty-daily-close');
            
            if (modal.length === 0) return;
            
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
            
            // Handle claim button in modal
            $('.wc-loyalty-daily-button').on('click', function(e) {
                e.preventDefault();
                
                // If button is already claimed, just close modal
                if ($(this).hasClass('claimed') || $(this).hasClass('disabled')) {
                    modal.fadeOut(300);
                    return;
                }
                
                // Make AJAX request to claim points
                self.claimDailyPoints($(this));
            });
        },
        
        // Initialize account streak button
        initAccountButton: function() {
            const self = this;
            const accountBtn = $('.wc-loyalty-account-streak-button');
            
            if (accountBtn.length === 0) return;
            
            accountBtn.on('click', function(e) {
                e.preventDefault();
                
                // If button is disabled, just return
                if ($(this).prop('disabled') || $(this).hasClass('claimed')) {
                    return;
                }
                
                // Make AJAX request to claim points
                self.claimDailyPoints($(this));
            });
        },
        
        // Show the daily modal
        showDailyModal: function() {
            $('.wc-loyalty-daily-modal').fadeIn(300);
        },
        
        // Claim daily points via AJAX
        claimDailyPoints: function(button) {
            const self = this;
            
            // Disable button and show loading state
            button.prop('disabled', true);
            const originalText = button.text();
            button.text('Claiming...');
            
            // Make AJAX request
            $.ajax({
                type: 'POST',
                url: window.wcLoyaltyData ? window.wcLoyaltyData.ajaxurl : (window.ajaxurl || '/wp-admin/admin-ajax.php'),
                data: {
                    action: 'claim_daily_points',
                    nonce: window.wcLoyaltyData ? window.wcLoyaltyData.nonce : ''
                },
                success: function(response) {
                    if (response.success) {
                        // Update button
                        button.addClass('claimed');
                        button.text('Claimed!');
                        
                        // Update all related buttons
                        $('.wc-loyalty-daily-claim, .wc-loyalty-account-streak-button, .wc-loyalty-daily-button')
                            .addClass('claimed disabled')
                            .prop('disabled', true)
                            .text('Claimed Today');
                        
                        // Show notification
                        self.showNotification(response.data.message, 'success');
                        
                        // Update points display - WITHOUT using circleProgress
                        self.updatePointsDisplaySimple(response.data.points);
                        
                        // Update streak count
                        if (response.data.streak) {
                            $('.wc-loyalty-streak-count').text('Day ' + response.data.streak + ' Streak');
                            $('.wc-loyalty-account-streak-count').text(response.data.streak);
                            $('.wc-loyalty-streak-badge').text(response.data.streak);
                        }
                        
                        // Close modal after a delay
                        setTimeout(function() {
                            $('.wc-loyalty-daily-modal').fadeOut(300);
                        }, 2000);
                    } else {
                        // Show error message
                        self.showNotification(response.data.message || 'Failed to claim points', 'error');
                        
                        // Reset button
                        button.prop('disabled', false);
                        button.text(originalText);
                    }
                },
                error: function() {
                    // Show error message
                    self.showNotification('An error occurred. Please try again.', 'error');
                    
                    // Reset button
                    button.prop('disabled', false);
                    button.text(originalText);
                }
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
        },
        
        // Simplified points display update that doesn't rely on circleProgress
        updatePointsDisplaySimple: function(points) {
            if (points) {
                // Just update the points count text
                $('.wc-loyalty-points-count').text(points);
                
                // Optional: Update progress visually without using circleProgress
                try {
                    // Calculate percentage
                    var percentage = Math.min((points / 2000) * 100, 100) + '%';
                    
                    // Find canvas element if it exists
                    var canvas = $('.wc-loyalty-progress-circle canvas');
                    if (canvas.length) {
                        // Add a CSS class to indicate progress
                        canvas.css('background', 'linear-gradient(to right, #7952b3 ' + percentage + ', #f0f0f0 ' + percentage + ')');
                    }
                } catch(e) {
                    console.log('Simple progress update failed, but points were updated');
                }
            }
        }
    };

    // Initialize when document is fully loaded
    $(window).on('load', function() {
        // Delay initialization to ensure DOM is fully ready
        setTimeout(function() {
            WCLoyaltyDaily.init();
        }, 500);
    });

})(jQuery);