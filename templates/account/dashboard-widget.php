<?php
/**
 * Dashboard Widget Template
 *
 * This template displays the loyalty program widget in the My Account dashboard.
 *
 * @package WC_Loyalty_Gamification
 */

defined('ABSPATH') || exit;
?>

<div class="wc-loyalty-dashboard-widget">
    <h4><?php esc_html_e('Loyalty Program', 'wc-loyalty-gamification'); ?></h4>
    
    <p>
        <?php 
        printf(
            esc_html__('You currently have %s in our loyalty program.', 'wc-loyalty-gamification'),
            '<span class="points-count">' . esc_html($user_points) . ' ' . esc_html__('points', 'wc-loyalty-gamification') . '</span>'
        ); 
        ?>
    </p>
    
    <?php if ($next_tier) : ?>
        <p>
            <?php 
            printf(
                esc_html__('Earn %d more points to reach your next reward!', 'wc-loyalty-gamification'),
                $next_tier - $user_points
            ); 
            ?>
        </p>
    <?php else : ?>
        <p><?php esc_html_e('Congratulations! You\'ve reached all reward tiers.', 'wc-loyalty-gamification'); ?></p>
    <?php endif; ?>
    
    <p>
        <a href="<?php echo esc_url(wc_get_account_endpoint_url('loyalty-points')); ?>">
            <?php esc_html_e('View Details', 'wc-loyalty-gamification'); ?>
        </a>
    </p>
</div>