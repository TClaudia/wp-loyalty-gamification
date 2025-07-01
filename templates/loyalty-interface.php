<?php
/**
 * Loyalty Interface Template - FIXED VERSION
 */

defined('ABSPATH') || exit;

$user_id = get_current_user_id();
$user_coupons = WC_Loyalty()->rewards->get_user_coupons($user_id);
$user_notifications = WC_Loyalty()->rewards->get_user_notifications($user_id);

// Get correct point values
$total_points = WC_Loyalty()->points->get_user_points($user_id);
$display_points = WC_Loyalty()->points->get_user_display_points($user_id);
$cycle_level = WC_Loyalty()->points->get_user_cycle_level($user_id);
$reward_tiers = unserialize(get_option('wc_loyalty_reward_tiers', 'a:0:{}'));
$claimed_rewards = WC_Loyalty()->rewards->get_rewards_claimed($user_id);
$next_tier = WC_Loyalty()->rewards->get_next_reward_tier($display_points, $reward_tiers);
?>

<!-- Loyalty Button - Now Floating Higher -->
<div class="wc-loyalty-button floating">
    <button id="wc-loyalty-toggle-btn"><?php esc_html_e('See Your Points', 'wc-loyalty-gamification'); ?></button>
</div>

<!-- Loyalty Modal -->
<div id="wc-loyalty-modal" class="wc-loyalty-modal">
    <div class="wc-loyalty-modal-content">
        <span class="wc-loyalty-close">&times;</span>
        
        <h2><?php esc_html_e('Your Loyalty Points', 'wc-loyalty-gamification'); ?></h2>
        
        <?php
        // Calculate percentage progress to 2000 points
        $progress = ($display_points / 2000) * 100;
        
        // Limit to 100%
        $progress = min($progress, 100);
        ?>
        
        <div class="wc-loyalty-points-display">
            <div class="wc-loyalty-progress-circle" data-progress="<?php echo esc_attr($progress); ?>">
                <div class="wc-loyalty-points-count"><?php echo esc_html($display_points); ?></div>
                
                <!-- ADAUGĂ HOOK-URILE PENTRU DAILY CHECK-IN AICI: -->
                <?php
                // Hook pentru butonul de daily check-in - FOARTE IMPORTANT!
                do_action('wc_loyalty_after_points_display');
                ?>
            </div>
            
            <?php if ($cycle_level > 0) : ?>
                <div class="wc-loyalty-cycle-level">
                    <?php printf(esc_html__('Cycle Level: %d', 'wc-loyalty-gamification'), $cycle_level); ?>
                    <span class="wc-loyalty-total-points"><?php printf(esc_html__('Total Points: %d', 'wc-loyalty-gamification'), $total_points); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($display_points == 2000) : ?>
                <div class="wc-loyalty-points-next">
                    <?php esc_html_e('Congratulations! You\'ve reached 2000 points! Check your coupons for a 60% discount code.', 'wc-loyalty-gamification'); ?>
                </div>
            <?php elseif ($next_tier) : ?>
                <div class="wc-loyalty-points-next">
                    <?php 
                    $points_needed = $next_tier - $display_points;
                    printf(
                        esc_html__('You need %s more points to reach your next reward!', 'wc-loyalty-gamification'),
                        '<strong>' . esc_html($points_needed) . '</strong>'
                    ); 
                    ?>
                </div>
            <?php else : ?>
                <div class="wc-loyalty-points-next">
                    <?php 
                    $points_to_premium = 2000 - $display_points;
                    printf(
                        esc_html__('You need %s more points to reach 2000 and earn a premium 60%% discount!', 'wc-loyalty-gamification'),
                        '<strong>' . esc_html($points_to_premium) . '</strong>'
                    ); 
                    ?>
                </div>
            <?php endif; ?>
        </div>
        
        <?php
        // Hook pentru conținut suplimentar după afișarea punctelor - FOARTE IMPORTANT!
        do_action('wc_loyalty_modal_after_points');
        ?>

        <?php
        // Filter out used and expired coupons
        if (!empty($user_coupons)) : 
            $active_coupons = array_filter($user_coupons, function($coupon) {
                $coupon_expired = strtotime($coupon['expires']) < time();
                return !$coupon['is_used'] && !$coupon_expired;
            });
            
            if (!empty($active_coupons)) : ?>
                <div class="wc-loyalty-coupons-list">
                    <h3><?php esc_html_e('Your Coupons', 'wc-loyalty-gamification'); ?></h3>
                    
                    <div class="mini-coupons-container">
                        <?php foreach ($active_coupons as $index => $coupon) : 
                            $is_premium = isset($coupon['tier']) && $coupon['tier'] === 2000;
                        ?>
                            <div class="mini-coupon <?php echo $is_premium ? 'premium' : ''; ?>">
                                <div class="mini-coupon-info">
                                    <?php printf(esc_html__('%d%%', 'wc-loyalty-gamification'), $coupon['discount']); ?>
                                </div>
                                <button class="mini-copy-btn" data-code="<?php echo esc_attr($coupon['code']); ?>">
                                    <?php esc_html_e('Copy', 'wc-loyalty-gamification'); ?>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <div class="wc-loyalty-rewards-list">
            <h3><?php esc_html_e('Rewards', 'wc-loyalty-gamification'); ?></h3>
            
            <?php if (!empty($reward_tiers)) : ?>
                <ul>
                    <?php foreach ($reward_tiers as $tier => $reward) : 
                        $is_achieved = $display_points >= $tier;
                        $is_claimed = isset($claimed_rewards[$tier]);
                        $class = $is_achieved ? 'achieved' : '';
                        $class .= $is_claimed ? ' claimed' : '';
                        
                        // Highlight the premium discount tier at 2000 points
                        if ($tier == 2000 && $reward['type'] == 'discount' && $display_points == 2000 && !$is_claimed) {
                            $class .= ' highlight-reward';
                        }
                    ?>
                        <li class="<?php echo esc_attr($class); ?>">
                            <span class="tier-points"><?php echo esc_html($tier); ?> <?php esc_html_e('points', 'wc-loyalty-gamification'); ?></span>
                            
                            <span class="tier-reward">
                                <?php
                                switch ($reward['type']) {
                                    case 'discount':
                                        if ($tier == 2000) {
                                            printf(
                                                esc_html__('%d%% Discount (up to 400 lei)', 'wc-loyalty-gamification'),
                                                esc_html($reward['value'])
                                            );
                                        } else {
                                            printf(
                                                esc_html__('%d%% Discount', 'wc-loyalty-gamification'),
                                                esc_html($reward['value'])
                                            );
                                        }
                                        break;
                                    case 'free_shipping':
                                        esc_html_e('Free Shipping', 'wc-loyalty-gamification');
                                        break;
                                }
                                ?>
                            </span>
                            
                            <?php if ($is_claimed) : ?>
                                <span class="claimed-label"><?php esc_html_e('Claimed', 'wc-loyalty-gamification'); ?></span>
                            <?php elseif ($tier == 2000 && $reward['type'] == 'discount' && $display_points == 2000) : ?>
                                <a href="<?php echo esc_url(wc_get_account_endpoint_url('loyalty-points')); ?>" class="claim-now-label">
                                    <?php esc_html_e('View Coupon', 'wc-loyalty-gamification'); ?>
                                </a>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p><?php esc_html_e('No rewards available yet.', 'wc-loyalty-gamification'); ?></p>
            <?php endif; ?>
        </div>
        
        <div class="wc-loyalty-history-link">
            <a href="<?php echo esc_url(wc_get_account_endpoint_url('loyalty-points')); ?>">
                <?php esc_html_e('View Points History', 'wc-loyalty-gamification'); ?>
            </a>
        </div>
    </div>
</div>

<!-- CSS și JavaScript rămân la fel ca în versiunea anterioară -->
<style>
/* Daily Check-in button minimalist - ADAUGĂ ACEST CSS */
.wc-loyalty-check-in-button {
    position: relative;
    bottom: 100%;
    right: 100%;
    background-color:rgb(255, 255, 255);
    color: white;
    width: 0px;
    height: 0px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    cursor: pointer;
    border: none;
}



.wc-loyalty-check-in-button.disabled {
    background-color: #aaa;
    cursor: not-allowed;
}

/* Notificări pentru daily check-in */
.wc-loyalty-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 10px 15px;
    background-color: white;
    border-left: 4px solid #7952b3;
    border-radius: 4px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    z-index: 9999;
    transition: all 0.3s ease;
    transform: translateX(110%);
}

.wc-loyalty-notification.show {
    transform: translateX(0);
}

.wc-loyalty-notification.success {
    border-left-color: #28a745;
}

.wc-loyalty-notification.error {
    border-left-color: #dc3545;
}

.wc-loyalty-notification.info {
    border-left-color: #17a2b8;
}

/* Restul CSS-ului pentru cupoane rămâne la fel... */
.mini-loyalty-coupons {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 9999;
}

.mini-coupon-toggle {
    background-color: #7952b3;
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
}

.mini-coupon-toggle:hover {
    background-color: #5e3d8f;
    transform: translateY(-2px);
}

.mini-coupon-toggle svg {
    width: 20px;
    height: 20px;
}

.mini-coupon-panel {
    position: absolute;
    bottom: 50px;
    right: 0;
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    padding: 8px;
    width: 130px;
    display: none;
}

.mini-coupon-panel.visible {
    display: block;
    animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(5px); }
    to { opacity: 1; transform: translateY(0); }
}

.mini-coupon-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 6px;
    margin-bottom: 4px;
    border-radius: 4px;
}

.mini-coupon-item:last-child {
    margin-bottom: 0;
}

.mini-coupon-item.discount-20 {
    background-color: #f0e7ff;
}

.mini-coupon-item.discount-40 {
    background-color: #e1d3fa;
}

.mini-coupon-item.discount-60 {
    background-color: #fff3d9;
}

.mini-coupon-discount {
    font-weight: 600;
    font-size: 12px;
    color: #333;
    display: flex;
    align-items: center;
}

.mini-premium-badge {
    display: inline-block;
    margin-left: 2px;
    background-color: #f59e0b;
    color: white;
    font-size: 8px;
    padding: 1px 3px;
    border-radius: 2px;
    vertical-align: top;
}

.mini-copy-btn {
    background-color: #fd7e14;
    color: white;
    border: none;
    padding: 2px 6px;
    border-radius: 2px;
    font-size: 10px;
    cursor: pointer;
    transition: background-color 0.2s;
}

.mini-copy-btn:hover {
    background-color: #ea580c;
}

.mini-copy-btn.copied {
    background-color: #10b981;
}
</style>

<!-- Restul conținutului rămâne la fel... -->
<div class="mini-loyalty-coupons">
    <button class="mini-coupon-toggle" id="mini-coupon-toggle">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="6" width="20" height="12" rx="2"/>
            <path d="M22 10c-1.5 0-2 .5-2 2s.5 2 2 2M2 10c1.5 0 2 .5 2 2s-.5 2-2 2"/>
        </svg>
    </button>
    
    <div class="mini-coupon-panel" id="mini-coupon-panel">
        <?php 
        if (!empty($user_coupons)) {
            foreach ($user_coupons as $coupon) : 
                $is_premium = isset($coupon['tier']) && $coupon['tier'] === 2000;
                $coupon_expired = strtotime($coupon['expires']) < time();
                $coupon_usable = !$coupon['is_used'] && !$coupon_expired;
                
                // Skip expired or used coupons
                if (!$coupon_usable) continue;
                
                // Set class based on discount percentage
                $discount_class = 'discount-' . $coupon['discount'];
            ?>
                <div class="mini-coupon-item <?php echo esc_attr($discount_class); ?>">
                    <div class="mini-coupon-discount">
                        <?php echo esc_html($coupon['discount']); ?>%
                        <?php if ($is_premium) : ?>
                            <span class="mini-premium-badge">P</span>
                        <?php endif; ?>
                    </div>
                    <button class="mini-copy-btn" data-code="<?php echo esc_attr($coupon['code']); ?>">
                        <?php esc_html_e('Copy', 'wc-loyalty-gamification'); ?>
                    </button>
                </div>
            <?php 
            endforeach;
        }
        ?>
    </div>
</div>

<!-- JavaScript rămâne la fel ca în versiunea anterioară -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Codul JavaScript rămâne exact la fel...
    // [JavaScript code remains the same as previous version]
    
    // jQuery safety check - crucial fix
    if (typeof jQuery === 'undefined') {
        console.error('jQuery is not loaded. Loyalty functionality may not work properly.');
        return;
    }
    
    // Use an IIFE to create a local jQuery instance
    (function($) {
        // Initialize main modal functionality
        const modal = document.getElementById('wc-loyalty-modal');
        const toggleBtn = document.getElementById('wc-loyalty-toggle-btn');
        const closeBtn = document.querySelector('.wc-loyalty-close');
        
        // Open modal when clicking the button
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function(e) {
                e.preventDefault();
                modal.style.display = 'block';
                
                // Safe circle progress initialization
                safeInitCircleProgress();
                
                // Manually bind copy buttons in main modal
                bindMainCopyButtons();
            });
        }
        
        // Close modal when clicking the close button
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                modal.style.display = 'none';
            });
        }
        
        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
        
        // Safe initialization of circle progress with error handling
        function safeInitCircleProgress() {
            try {
                var progressCircle = document.querySelector('.wc-loyalty-progress-circle');
                if (!progressCircle) return;
                
                var progress = progressCircle.getAttribute('data-progress');
                
                // Only use circleProgress if jQuery and the plugin are properly loaded
                if ($ && $.fn && typeof $.fn.circleProgress === 'function') {
                    $(progressCircle).circleProgress({
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
                } else {
                    // Fallback for when circleProgress isn't available
                    console.log('Circle progress plugin not available, using fallback');
                    createSimpleProgressBar(progressCircle, progress);
                }
            } catch (e) {
                console.error('Error initializing circle progress:', e);
                
                // Find the progress circle again in case of scope issues
                var progressCircle = document.querySelector('.wc-loyalty-progress-circle');
                if (progressCircle) {
                    createSimpleProgressBar(progressCircle, progressCircle.getAttribute('data-progress') || 0);
                }
            }
        }
        
        // Simple alternative progress display
        function createSimpleProgressBar(container, percentage) {
            // Clear any existing content
            container.innerHTML = '';
            
            // Get the points count element or create it
            var pointsCount = container.querySelector('.wc-loyalty-points-count');
            if (!pointsCount) {
                pointsCount = document.createElement('div');
                pointsCount.className = 'wc-loyalty-points-count';
                pointsCount.textContent = container.getAttribute('data-points') || '0';
                container.appendChild(pointsCount);
            }
            
            // Create simple progress bar
            var barContainer = document.createElement('div');
            barContainer.style.width = '100%';
            barContainer.style.height = '10px';
            barContainer.style.backgroundColor = '#f0f0f0';
            barContainer.style.borderRadius = '5px';
            barContainer.style.marginTop = '10px';
            barContainer.style.overflow = 'hidden';
            barContainer.style.position = 'absolute';
            barContainer.style.bottom = '20px';
            barContainer.style.left = '0';
            
            var progressBar = document.createElement('div');
            progressBar.style.width = percentage + '%';
            progressBar.style.height = '100%';
            progressBar.style.backgroundColor = '#7952b3';
            progressBar.style.borderRadius = '5px';
            
            barContainer.appendChild(progressBar);
            container.appendChild(barContainer);
        }
        
        // Bind copy buttons in main modal
        function bindMainCopyButtons() {
            const copyButtons = document.querySelectorAll('.wc-loyalty-copy-code');
            
            copyButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const couponCode = this.getAttribute('data-code');
                    const originalText = this.textContent;
                    
                    // Copy to clipboard
                    copyToClipboard(couponCode);
                    
                    // Update button text
                    this.textContent = 'Copied!';
                    
                    // Reset button text after a delay
                    setTimeout(() => {
                        this.textContent = originalText;
                    }, 2000);
                });
            });
        }
        
        // Mini coupon panel toggle
        const miniToggleButton = document.getElementById('mini-coupon-toggle');
        const miniCouponPanel = document.getElementById('mini-coupon-panel');
        
        if (miniToggleButton && miniCouponPanel) {
            miniToggleButton.addEventListener('click', function() {
                miniCouponPanel.classList.toggle('visible');
            });
            
            // Close panel when clicking outside
            document.addEventListener('click', function(event) {
                if (!miniCouponPanel.contains(event.target) && event.target !== miniToggleButton) {
                    miniCouponPanel.classList.remove('visible');
                }
            });
            
            // Mini coupon copy functionality
            const miniCopyButtons = document.querySelectorAll('.mini-copy-btn');
            
            miniCopyButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.stopPropagation();
                    
                    const code = this.getAttribute('data-code');
                    const originalText = this.textContent;
                    
                    // Copy to clipboard
                    copyToClipboard(code);
                    
                    // Update button appearance
                    this.textContent = '✓';
                    this.classList.add('copied');
                    
                    // Reset after delay
                    setTimeout(() => {
                        this.textContent = originalText;
                        this.classList.remove('copied');
                    }, 2000);
                });
            });
        }
        
        // Helper function for copying to clipboard
        function copyToClipboard(text) {
            // Create a temporary textarea element
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'absolute';
            textarea.style.left = '-9999px';
            document.body.appendChild(textarea);
            
            // Select and copy text
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
        }
    })(jQuery); // Pass jQuery to the IIFE
});
</script>

<!-- Notifications Container -->
<div class="wc-loyalty-notifications-container"></div>
?>