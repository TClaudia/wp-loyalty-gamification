<?php
/**
 * Loyalty Interface Template
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

<!-- Loyalty Button -->
<div class="wc-loyalty-button">
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
        
        <?php if (!empty($user_coupons)) : ?>
            <div class="wc-loyalty-coupons-list">
                <h3><?php esc_html_e('Your Coupons', 'wc-loyalty-gamification'); ?></h3>
                
                <?php foreach ($user_coupons as $index => $coupon) : 
                    $coupon_expired = strtotime($coupon['expires']) < time();
                    $coupon_class = $coupon['is_used'] ? 'used' : ($coupon_expired ? 'expired' : 'active');
                    $is_premium = isset($coupon['tier']) && $coupon['tier'] === 2000;
                ?>
                    <div class="wc-loyalty-coupon <?php echo esc_attr($coupon_class); ?> <?php echo $is_premium ? 'premium-coupon' : ''; ?>">
                        <div class="wc-loyalty-coupon-discount">
                            <?php printf(esc_html__('%d%% OFF', 'wc-loyalty-gamification'), $coupon['discount']); ?>
                            <?php if ($is_premium) : ?>
                                <span class="premium-label"><?php esc_html_e('Premium Reward', 'wc-loyalty-gamification'); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="wc-loyalty-coupon-code">
                            <?php echo esc_html($coupon['code']); ?>
                            <button class="wc-loyalty-copy-code" data-code="<?php echo esc_attr($coupon['code']); ?>"><?php esc_html_e('Copy', 'wc-loyalty-gamification'); ?></button>
                        </div>
                        <?php if ($is_premium) : ?>
                            <div class="wc-loyalty-coupon-info">
                                <?php esc_html_e('Valid for orders up to 400 lei', 'wc-loyalty-gamification'); ?>
                            </div>
                        <?php endif; ?>
                        <div class="wc-loyalty-coupon-expiry">
                            <?php if ($coupon_expired) : ?>
                                <?php esc_html_e('Expired', 'wc-loyalty-gamification'); ?>
                            <?php elseif ($coupon['is_used']) : ?>
                                <?php esc_html_e('Used', 'wc-loyalty-gamification'); ?>
                            <?php else : ?>
                                <?php printf(esc_html__('Valid until %s', 'wc-loyalty-gamification'), date_i18n(get_option('date_format'), strtotime($coupon['expires']))); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        
        <?php if (!empty($user_notifications)) : ?>
            <div class="wc-loyalty-notifications">
                <h3><?php esc_html_e('Notifications', 'wc-loyalty-gamification'); ?></h3>
                
                <?php foreach ($user_notifications as $index => $notification) : ?>
                    <div class="wc-loyalty-notification <?php echo esc_attr($notification['type']); ?>">
                        <?php echo esc_html($notification['message']); ?>
                    </div>
                <?php endforeach; ?>
            </div>
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

<!-- Compact Floating Coupon Interface -->
<style>
/* Compact Floating Coupon Interface Styles */
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

/* Different background colors for each coupon */
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

<div class="mini-loyalty-coupons">
    <button class="mini-coupon-toggle" id="mini-coupon-toggle">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="6" width="20" height="12" rx="2"/>
            <path d="M22 10c-1.5 0-2 .5-2 2s.5 2 2 2M2 10c1.5 0 2 .5 2 2s-.5 2-2 2"/>
        </svg>
    </button>
    
    <div class="mini-coupon-panel" id="mini-coupon-panel">
        <?php foreach ($user_coupons as $coupon) : 
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
        <?php endforeach; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize main modal functionality
    const modal = document.getElementById('wc-loyalty-modal');
    const toggleBtn = document.getElementById('wc-loyalty-toggle-btn');
    const closeBtn = document.querySelector('.wc-loyalty-close');
    
    // Open modal when clicking the button
    toggleBtn.addEventListener('click', function(e) {
        e.preventDefault();
        modal.style.display = 'block';
        
        // Initialize circle progress
        initCircleProgress();
        
        // Manually bind copy buttons in main modal
        bindMainCopyButtons();
    });
    
    // Close modal when clicking the close button
    closeBtn.addEventListener('click', function() {
        modal.style.display = 'none';
    });
    
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
    
    // Initialize circle progress
    function initCircleProgress() {
        var progressCircle = document.querySelector('.wc-loyalty-progress-circle');
        if (!progressCircle) return;
        
        var progress = progressCircle.getAttribute('data-progress');
        
        if (typeof $.fn.circleProgress === 'function') {
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
        }
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
});
</script>

<!-- Notifications Container -->
<div class="wc-loyalty-notifications-container"></div>