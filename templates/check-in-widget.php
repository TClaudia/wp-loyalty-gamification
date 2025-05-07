<?php
/**
 * Minimalist Daily Check-in Template
 * Replace this with the content in templates/check-in-widget.php
 */

defined('ABSPATH') || exit;
?>

<div class="wc-loyalty-daily-check">
    <?php if ($streak_info['has_checked_in_today']): ?>
        <!-- Checked in today - show minimal confirmation -->
        <button class="wc-loyalty-daily-claim disabled" disabled>
            <?php esc_html_e('Claimed Today', 'wc-loyalty-gamification'); ?>
        </button>
    <?php else: ?>
        <!-- Not checked in yet - show claim button -->
        <button class="wc-loyalty-daily-claim" id="wc-loyalty-daily-btn">
            <?php 
            printf(
                esc_html__('+%d pts', 'wc-loyalty-gamification'),
                $potential_points
            ); 
            ?>
        </button>
    <?php endif; ?>
</div>

<!-- Add this CSS to your loyalty-style.css file -->
<style>
/* Minimalist Daily Check-in Styles */
.wc-loyalty-daily-check {
    position: relative;
    margin-top: 20px;
    text-align: center;
}

.wc-loyalty-daily-claim {
    position: absolute;
    bottom: -15px;
    left: 50%;
    transform: translateX(-50%);
    background-color: var(--loyalty-secondary);
    color: var(--loyalty-white);
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
    transition: var(--loyalty-transition);
    white-space: nowrap;
}

.wc-loyalty-daily-claim:hover {
    background-color: var(--loyalty-secondary-dark);
    transform: translateX(-50%) translateY(-2px);
}

.wc-loyalty-daily-claim.disabled {
    background-color: #ccc;
    cursor: not-allowed;
}

/* Simple Streak Badge */
.wc-loyalty-streak-badge {
    position: absolute;
    top: -10px;
    right: -10px;
    background-color: var(--loyalty-primary);
    color: var(--loyalty-white);
    font-size: 10px;
    font-weight: 700;
    padding: 3px 6px;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Modal styles remain, but made more minimal */
.wc-loyalty-daily-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 99999;
    align-items: center;
    justify-content: center;
}

.wc-loyalty-daily-modal-content {
    background-color: var(--loyalty-white);
    padding: 25px;
    border-radius: var(--loyalty-border-radius);
    max-width: 350px;
    position: relative;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
    text-align: center;
}

.wc-loyalty-daily-close {
    position: absolute;
    top: 10px;
    right: 10px;
    font-size: 18px;
    color: #999;
    cursor: pointer;
}

.wc-loyalty-daily-title {
    font-size: 18px;
    font-weight: 700;
    color: var(--loyalty-primary);
    margin-bottom: 15px;
}

.wc-loyalty-daily-message {
    margin-bottom: 20px;
    font-size: 14px;
    color: #666;
}

.wc-loyalty-streak-count {
    display: inline-block;
    margin: 10px 0 20px;
    background-color: #f8f9fa;
    padding: 5px 15px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 14px;
    color: var(--loyalty-primary);
}

.wc-loyalty-daily-button {
    background-color: var(--loyalty-primary);
    color: white;
    border: none;
    padding: 8px 20px;
    border-radius: var(--loyalty-border-radius);
    font-weight: 600;
    cursor: pointer;
    transition: var(--loyalty-transition);
}

.wc-loyalty-daily-button:hover {
    background-color: var(--loyalty-primary-dark);
}
</style>

<!-- Minimalist Daily Check-in Modal -->
<div class="wc-loyalty-daily-modal" id="wc-loyalty-daily-modal">
    <div class="wc-loyalty-daily-modal-content">
        <span class="wc-loyalty-daily-close">&times;</span>
        
        <div class="wc-loyalty-daily-title">
            <?php esc_html_e('Daily Check-in', 'wc-loyalty-gamification'); ?>
        </div>
        
        <div class="wc-loyalty-daily-message">
            <?php esc_html_e('Check in daily to earn points and build your streak!', 'wc-loyalty-gamification'); ?>
        </div>
        
        <div class="wc-loyalty-streak-count">
            <?php 
            printf(
                esc_html__('Day %d Streak', 'wc-loyalty-gamification'),
                $streak_info['streak_count']
            ); 
            ?>
        </div>
        
        <button class="wc-loyalty-daily-button" id="wc-loyalty-daily-claim-btn">
            <?php 
            printf(
                esc_html__('Claim %d Points', 'wc-loyalty-gamification'),
                $potential_points
            ); 
            ?>
        </button>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Daily check-in button click
    $('#wc-loyalty-daily-btn').on('click', function(e) {
        e.preventDefault();
        
        // Show modal
        $('#wc-loyalty-daily-modal').fadeIn(300).css('display', 'flex');
    });
    
    // Close modal when clicking close button
    $('.wc-loyalty-daily-close').on('click', function() {
        $('#wc-loyalty-daily-modal').fadeOut(300);
    });
    
    // Close modal when clicking outside
    $(window).on('click', function(event) {
        if (event.target == $('#wc-loyalty-daily-modal')[0]) {
            $('#wc-loyalty-daily-modal').fadeOut(300);
        }
    });
    
    // Handle claim button in modal
    $('#wc-loyalty-daily-claim-btn').on('click', function(e) {
        e.preventDefault();
        
        // Disable button and show loading state
        $(this).prop('disabled', true);
        $(this).text('<?php esc_html_e('Claiming...', 'wc-loyalty-gamification'); ?>');
        
        // Make AJAX request
        $.ajax({
            type: 'POST',
            url: wcLoyaltyData.ajaxurl,
            data: {
                action: 'claim_daily_points',
                nonce: wcLoyaltyData.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Update button text
                    $('#wc-loyalty-daily-claim-btn').text('<?php esc_html_e('Claimed!', 'wc-loyalty-gamification'); ?>');
                    
                    // Update all related buttons
                    $('.wc-loyalty-daily-claim').addClass('disabled').prop('disabled', true).text('<?php esc_html_e('Claimed Today', 'wc-loyalty-gamification'); ?>');
                    
                    // Show notification
                    showNotification(response.data.message, 'success');
                    
                    // Update points display
                    if (response.data.points) {
                        $('.wc-loyalty-points-count').text(response.data.points);
                    }
                    
                    // Update streak count if available
                    if (response.data.streak) {
                        $('.wc-loyalty-streak-count').text('<?php esc_html_e('Day', 'wc-loyalty-gamification'); ?> ' + response.data.streak + ' <?php esc_html_e('Streak', 'wc-loyalty-gamification'); ?>');
                        $('.wc-loyalty-streak-badge').text(response.data.streak);
                    }
                    
                    // Close modal after a delay
                    setTimeout(function() {
                        $('#wc-loyalty-daily-modal').fadeOut(300);
                    }, 2000);
                } else {
                    // Show error and reset button
                    showNotification(response.data.message || '<?php esc_html_e('Failed to claim points', 'wc-loyalty-gamification'); ?>', 'error');
                    $('#wc-loyalty-daily-claim-btn').prop('disabled', false).text('<?php esc_html_e('Try Again', 'wc-loyalty-gamification'); ?>');
                }
            },
            error: function() {
                // Show error and reset button
                showNotification('<?php esc_html_e('An error occurred. Please try again.', 'wc-loyalty-gamification'); ?>', 'error');
                $('#wc-loyalty-daily-claim-btn').prop('disabled', false).text('<?php esc_html_e('Try Again', 'wc-loyalty-gamification'); ?>');
            }
        });
    });
    
    // Notification function
    function showNotification(message, type) {
        // Remove any existing notifications
        $('.wc-loyalty-notification-popup').remove();
        
        // Create notification
        var notification = $('<div class="wc-loyalty-notification-popup wc-loyalty-notification-' + type + '">' + message + '</div>');
        
        // Add to body
        $('body').append(notification);
        
        // Show notification
        setTimeout(function() {
            notification.addClass('show');
        }, 10);
        
        // Hide after delay
        setTimeout(function() {
            notification.removeClass('show');
            setTimeout(function() {
                notification.remove();
            }, 300);
        }, 4000);
    }
});
</script>