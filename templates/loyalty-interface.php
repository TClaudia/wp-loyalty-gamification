<?php
/**
 * Loyalty Interface Template
 *
 * This template displays the loyalty program interface in the frontend.
 *
 * @package WC_Loyalty_Gamification
 */

defined('ABSPATH') || exit;
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
        
        <div class="wc-loyalty-points-display">
            <div class="wc-loyalty-progress-circle" data-progress="<?php echo esc_attr($progress); ?>">
                <div class="wc-loyalty-points-count"><?php echo esc_html($user_points); ?></div>
            </div>
            
            <?php if ($next_tier) : ?>
                <div class="wc-loyalty-points-next">
                    <?php 
                    printf(
                        esc_html__('You need %s more points to reach your next reward!', 'wc-loyalty-gamification'),
                        '<strong>' . esc_html($next_tier - $user_points) . '</strong>'
                    ); 
                    ?>
                </div>
            <?php else : ?>
                <div class="wc-loyalty-points-next">
                    <?php esc_html_e('Congratulations! You\'ve reached all reward tiers!', 'wc-loyalty-gamification'); ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="wc-loyalty-rewards-list">
            <h3><?php esc_html_e('Rewards', 'wc-loyalty-gamification'); ?></h3>
            
            <?php if (!empty($reward_tiers)) : ?>
                <ul>
                    <?php foreach ($reward_tiers as $tier => $reward) : 
                        $is_achieved = $user_points >= $tier;
                        $is_claimed = isset($claimed_rewards[$tier]);
                        $class = $is_achieved ? 'achieved' : '';
                        $class .= $is_claimed ? ' claimed' : '';
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