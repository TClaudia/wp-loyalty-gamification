<?php
/**
 * My Account Loyalty Points Template
 *
 * This template displays the loyalty points page in My Account.
 *
 * @package WC_Loyalty_Gamification
 */

// Direct access prevention
if (!defined('ABSPATH')) {
    exit;
}
?>

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

<div class="wc-loyalty-points-page">
    <div class="wc-loyalty-points-summary">
        <div class="wc-loyalty-points-summary-value"><?php echo esc_html($points); ?></div>
        <div class="wc-loyalty-points-summary-label">
            <?php esc_html_e('Current Points', 'wc-loyalty-gamification'); ?>
            
            <?php if ($next_reward = WC_Loyalty()->rewards->get_next_reward_tier($points)): ?>
                <div class="wc-loyalty-next-reward">
                    <?php 
                    if ($next_reward == 2000) {
                        // Special handling for premium tier
                        printf(
                            esc_html__('You need %d more points to earn a 60%% discount for orders up to %s lei!', 'wc-loyalty-gamification'),
                            $next_reward - $points,
                            wc_loyalty_get_premium_discount_max()
                        );
                    } else {
                        printf(
                            esc_html__('You need %d more points to earn your next reward!', 'wc-loyalty-gamification'),
                            $next_reward - $points
                        );
                    }
                    ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Premium Discount Alert if exactly 2000 points -->
    <?php if (WC_Loyalty()->points->get_user_display_points($user_id) == 2000): ?>
        <div class="wc-loyalty-premium-alert">
            <p>
                <?php 
                printf(
                    esc_html__('Congratulations! You\'ve reached 2000 points and earned a 60%% discount for orders up to %s lei. Check your rewards page to see your discount coupon.', 'wc-loyalty-gamification'),
                    wc_loyalty_get_premium_discount_max()
                ); 
                ?>
                <a href="<?php echo esc_url(wc_get_account_endpoint_url('loyalty-rewards')); ?>" class="button"><?php esc_html_e('View My Rewards', 'wc-loyalty-gamification'); ?></a>
            </p>
        </div>
    <?php endif; ?>
    
    <!-- Points History -->
    <div class="wc-loyalty-points-history">
        <h3><?php esc_html_e('Points History', 'wc-loyalty-gamification'); ?></h3>
        
        <?php if (empty($points_history)): ?>
            <div class="woocommerce-info">
                <?php esc_html_e('No points history yet.', 'wc-loyalty-gamification'); ?>
            </div>
        <?php else: ?>
            <table class="wc-loyalty-history-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Date', 'wc-loyalty-gamification'); ?></th>
                        <th><?php esc_html_e('Points', 'wc-loyalty-gamification'); ?></th>
                        <th><?php esc_html_e('Description', 'wc-loyalty-gamification'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_reverse($points_history) as $entry): ?>
                        <tr>
                            <td>
                                <?php 
                                echo esc_html(
                                    date_i18n(
                                        get_option('date_format') . ' ' . get_option('time_format'), 
                                        strtotime($entry['date'])
                                    )
                                ); 
                                ?>
                            </td>
                            <td class="<?php echo $entry['points'] > 0 ? 'points-positive' : 'points-negative'; ?>">
                                <?php echo $entry['points'] > 0 ? '+' . esc_html($entry['points']) : esc_html($entry['points']); ?>
                            </td>
                            <td><?php echo esc_html($entry['description']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <!-- Claimed Rewards -->
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
    
    <!-- Link to Rewards Page -->
    <div class="wc-loyalty-rewards-link">
        <a href="<?php echo esc_url(wc_get_account_endpoint_url('loyalty-rewards')); ?>">
            <?php esc_html_e('View My Rewards', 'wc-loyalty-gamification'); ?>
        </a>
    </div>
</div>