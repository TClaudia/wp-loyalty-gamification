<?php
/**
 * My Account Loyalty Rewards Template
 *
 * This template displays the loyalty rewards page in My Account.
 *
 * @package WC_Loyalty_Gamification
 */

// Direct access prevention
if (!defined('ABSPATH')) {
    exit;
}

// Get user coupons
$user_coupons = WC_Loyalty()->rewards->get_user_coupons($user_id);
$points = WC_Loyalty()->points->get_user_points($user_id);
?>

<div class="wc-loyalty-rewards-page">
    <!-- COUPONS SECTION FIRST -->
    <div class="wc-loyalty-coupons-section">
        <h2><?php esc_html_e('Your Discount Coupons', 'wc-loyalty-gamification'); ?></h2>
        
        <?php if (!empty($user_coupons)): ?>
            <div class="wc-loyalty-coupons-grid">
                <?php foreach ($user_coupons as $index => $coupon): 
                    $coupon_expired = strtotime($coupon['expires']) < time();
                    $coupon_class = $coupon['is_used'] ? 'used' : ($coupon_expired ? 'expired' : 'active');
                    $is_premium = isset($coupon['tier']) && $coupon['tier'] === 2000;
                ?>
                    <div class="wc-loyalty-coupon <?php echo esc_attr($coupon_class); ?> <?php echo $is_premium ? 'premium-coupon' : ''; ?>" <?php if ($coupon['is_used'] || $coupon_expired) echo 'data-status="' . ($coupon['is_used'] ? 'USED' : 'EXPIRED') . '"'; ?>>
                        <div class="wc-loyalty-coupon-discount">
                            <?php printf(esc_html__('%d%% OFF', 'wc-loyalty-gamification'), $coupon['discount']); ?>
                            <?php if ($is_premium): ?>
                                <span class="premium-label"><?php esc_html_e('Premium Reward', 'wc-loyalty-gamification'); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="wc-loyalty-coupon-code">
                            <?php echo esc_html($coupon['code']); ?>
                            <button class="wc-loyalty-copy-code" data-code="<?php echo esc_attr($coupon['code']); ?>"><?php esc_html_e('Copy', 'wc-loyalty-gamification'); ?></button>
                        </div>
                        <?php if ($is_premium): ?>
                            <div class="wc-loyalty-coupon-info">
                                <?php 
                                printf(
                                    esc_html__('Valid for orders up to %s lei', 'wc-loyalty-gamification'),
                                    wc_loyalty_get_premium_discount_max() 
                                ); 
                                ?>
                            </div>
                        <?php endif; ?>
                        <div class="wc-loyalty-coupon-expiry">
                            <?php if ($coupon_expired): ?>
                                <?php esc_html_e('Expired', 'wc-loyalty-gamification'); ?>
                            <?php elseif ($coupon['is_used']): ?>
                                <?php esc_html_e('Used', 'wc-loyalty-gamification'); ?>
                            <?php else: ?>
                                <?php printf(esc_html__('Valid until %s', 'wc-loyalty-gamification'), date_i18n(get_option('date_format'), strtotime($coupon['expires']))); ?>
                            <?php endif; ?>
                        </div>
                        <div class="wc-loyalty-coupon-instructions">
                            <?php esc_html_e('Add products to your cart and enter this code at checkout.', 'wc-loyalty-gamification'); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="woocommerce-info">
                <?php esc_html_e('You don\'t have any discount coupons yet. Earn more points to receive discount rewards!', 'wc-loyalty-gamification'); ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- TIER SUMMARY -->
    <div class="wc-loyalty-tier-summary">
        <?php
        $tier_key = WC_Loyalty()->points->get_user_tier($user_id);
        $tier_data = WC_Loyalty()->points->get_user_tier_data($user_id);
        $next_tier = WC_Loyalty()->points->get_next_tier_data($user_id);
        ?>
        <div class="wc-loyalty-current-tier" style="border-color: <?php echo esc_attr($tier_data['color']); ?>">
            <div class="wc-loyalty-tier-badge" style="background-color: <?php echo esc_attr($tier_data['color']); ?>">
                <?php echo esc_html($tier_data['name']); ?>
            </div>
            <div class="wc-loyalty-tier-perks">
                <h4><?php esc_html_e('Your Member Benefits', 'wc-loyalty-gamification'); ?></h4>
                <p><?php echo esc_html($tier_data['perks']); ?></p>
            </div>
        </div>
        
        <?php if ($next_tier): ?>
        <div class="wc-loyalty-next-tier-progress">
            <h4>
                <?php 
                printf(
                    esc_html__('Next Tier: %s', 'wc-loyalty-gamification'),
                    esc_html($next_tier['name'])
                ); 
                ?>
            </h4>
            <div class="wc-loyalty-progress-container">
                <?php 
                $points_needed = $next_tier['min_points'] - $points;
                $percentage = min(100, ($points / $next_tier['min_points']) * 100);
                ?>
                <div class="wc-loyalty-progress-bar">
                    <div class="wc-loyalty-progress-fill" style="width: <?php echo esc_attr($percentage); ?>%; background-color: <?php echo esc_attr($next_tier['color']); ?>"></div>
                </div>
                <div class="wc-loyalty-progress-text">
                    <?php 
                    printf(
                        esc_html__('You need %d more points to reach %s level', 'wc-loyalty-gamification'),
                        $points_needed,
                        esc_html($next_tier['name'])
                    ); 
                    ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- POINTS SUMMARY -->
    <div class="wc-loyalty-points-summary">
        <div class="wc-loyalty-points-summary-value"><?php echo esc_html($points); ?></div>
        <div class="wc-loyalty-points-summary-label">
            <?php esc_html_e('Current Points', 'wc-loyalty-gamification'); ?>
            
            <?php if ($next_reward = WC_Loyalty()->rewards->get_next_reward_tier($points)): ?>
                <div class="wc-loyalty-next-reward">
                    <?php 
                    printf(
                        esc_html__('You need %d more points to earn your next reward!', 'wc-loyalty-gamification'),
                        $next_reward - $points
                    ); 
                    ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- CLAIMED REWARDS -->
    <div class="wc-loyalty-claimed-rewards">
        <h3><?php esc_html_e('Claimed Rewards', 'wc-loyalty-gamification'); ?></h3>
        
        <?php if (empty($claimed_rewards)): ?>
            <div class="woocommerce-info">
                <?php esc_html_e('No rewards claimed yet.', 'wc-loyalty-gamification'); ?>
            </div>
        <?php else: ?>
            <table class="wc-loyalty-rewards-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Date', 'wc-loyalty-gamification'); ?></th>
                        <th><?php esc_html_e('Points Level', 'wc-loyalty-gamification'); ?></th>
                        <th><?php esc_html_e('Reward', 'wc-loyalty-gamification'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    foreach (array_reverse($claimed_rewards, true) as $tier => $date): 
                        $reward = isset($reward_tiers[$tier]) ? $reward_tiers[$tier] : null;
                        if (!$reward) continue;
                    ?>
                        <tr>
                            <td>
                                <?php 
                                echo esc_html(
                                    date_i18n(
                                        get_option('date_format') . ' ' . get_option('time_format'), 
                                        strtotime($date)
                                    )
                                ); 
                                ?>
                            </td>
                            <td><?php echo esc_html($tier); ?></td>
                            <td>
                                <?php
                                switch ($reward['type']):
                                    case 'discount':
                                        if ($tier == 2000):
                                            printf(
                                                esc_html__('%d%% Discount (max %s lei)', 'wc-loyalty-gamification'),
                                                esc_html($reward['value']),
                                                wc_loyalty_get_premium_discount_max()
                                            );
                                        else:
                                            printf(
                                                esc_html__('%d%% Discount', 'wc-loyalty-gamification'),
                                                esc_html($reward['value'])
                                            );
                                        endif;
                                        break;
                                    case 'free_shipping':
                                        esc_html_e('Free Shipping', 'wc-loyalty-gamification');
                                        break;
                                endswitch;
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- POINTS HISTORY LINK -->
    <div class="wc-loyalty-points-link">
        <a href="<?php echo esc_url(wc_get_account_endpoint_url('loyalty-points')); ?>">
            <?php esc_html_e('View Points History', 'wc-loyalty-gamification'); ?>
        </a>
    </div>
</div>