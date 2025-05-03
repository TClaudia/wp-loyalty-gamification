<?php
/**
 * Loyalty Interface Template
 *
 * This template displays the loyalty program interface in the frontend.
 *
 * @package WC_Loyalty_Gamification
 */

defined('ABSPATH') || exit;

$user_id = get_current_user_id();
$user_coupons = WC_Loyalty()->rewards->get_user_coupons($user_id);
$user_notifications = WC_Loyalty()->rewards->get_user_notifications($user_id);

// IMPORTANT: Always use the points functions
$total_points = WC_Loyalty()->points->get_user_points($user_id);
$display_points = WC_Loyalty()->points->get_user_display_points($user_id);
$cycle_level = WC_Loyalty()->points->get_user_cycle_level($user_id);
$reward_tiers = unserialize(get_option('wc_loyalty_reward_tiers', 'a:0:{}'));
$claimed_rewards = WC_Loyalty()->rewards->get_rewards_claimed($user_id);

// Get next tier based on display points (not total points)
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
        // ALWAYS calculate progress toward 2000 points, not next tier
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
                <div class="wc-loyalty-points-next wc-loyalty-free-product-alert">
                    <?php esc_html_e('Congratulations! You\'ve reached 2000 points and can claim a free product!', 'wc-loyalty-gamification'); ?>
                    <a href="<?php echo esc_url(wc_get_account_endpoint_url('loyalty-rewards') . '#claim-free-product'); ?>">
                        <?php esc_html_e('Claim Now', 'wc-loyalty-gamification'); ?>
                    </a>
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
                    $points_to_free_product = 2000 - $display_points;
                    printf(
                        esc_html__('You need %s more points to reach 2000 and earn a free product!', 'wc-loyalty-gamification'),
                        '<strong>' . esc_html($points_to_free_product) . '</strong>'
                    ); 
                    ?>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($user_coupons)) : ?>
            <div class="wc-loyalty-coupons-list">
                <h3><?php esc_html_e('Your Discount Coupons', 'wc-loyalty-gamification'); ?></h3>
                
                <?php foreach ($user_coupons as $index => $coupon) : 
                    $coupon_expired = strtotime($coupon['expires']) < time();
                    $coupon_class = $coupon['is_used'] ? 'used' : ($coupon_expired ? 'expired' : 'active');
                ?>
                    <div class="wc-loyalty-coupon <?php echo esc_attr($coupon_class); ?>">
                        <div class="wc-loyalty-coupon-discount">
                            <?php printf(esc_html__('%d%% OFF', 'wc-loyalty-gamification'), $coupon['discount']); ?>
                        </div>
                        <div class="wc-loyalty-coupon-code">
                            <?php echo esc_html($coupon['code']); ?>
                            <button class="wc-loyalty-copy-code" data-code="<?php echo esc_attr($coupon['code']); ?>"><?php esc_html_e('Copy', 'wc-loyalty-gamification'); ?></button>
                        </div>
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
                        
                        // Highlight the free product tier at 2000 points
                        if ($tier == 2000 && $reward['type'] == 'free_product' && $display_points == 2000 && !$is_claimed) {
                            $class .= ' highlight-reward';
                        }
                    ?>
                        <li class="<?php echo esc_attr($class); ?>">
                            <span class="tier-points"><?php echo esc_html($tier); ?> <?php esc_html_e('points', 'wc-loyalty-gamification'); ?></span>
                            
                            <span class="tier-reward">
                                <?php
                                switch ($reward['type']) {
                                    case 'discount':
                                        printf(
                                            esc_html__('%d%% Discount', 'wc-loyalty-gamification'),
                                            esc_html($reward['value'])
                                        );
                                        break;
                                    case 'free_shipping':
                                        esc_html_e('Free Shipping', 'wc-loyalty-gamification');
                                        break;
                                    case 'free_product':
                                        esc_html_e('Free Product', 'wc-loyalty-gamification');
                                        break;
                                }
                                ?>
                            </span>
                            
                            <?php if ($is_claimed) : ?>
                                <span class="claimed-label"><?php esc_html_e('Claimed', 'wc-loyalty-gamification'); ?></span>
                            <?php elseif ($tier == 2000 && $reward['type'] == 'free_product' && $display_points == 2000) : ?>
                                <a href="<?php echo esc_url(wc_get_account_endpoint_url('loyalty-rewards') . '#claim-free-product'); ?>" class="claim-now-label">
                                    <?php esc_html_e('Claim Now', 'wc-loyalty-gamification'); ?>
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

<!-- Notifications Container -->
<div class="wc-loyalty-notifications-container"></div>