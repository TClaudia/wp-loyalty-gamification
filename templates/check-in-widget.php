<?php
/**
 * Check-in Widget Template
 */

defined('ABSPATH') || exit;
?>

<div class="wc-loyalty-checkin-section">
    <h3><?php esc_html_e('Daily Check-in', 'wc-loyalty-gamification'); ?></h3>
    
    <div class="wc-loyalty-checkin-container">
        <div class="wc-loyalty-streak-display">
            <div class="wc-loyalty-streak-count">
                <span class="streak-number"><?php echo esc_html($streak_info['streak_count']); ?></span>
                <span class="streak-label"><?php esc_html_e('Day Streak', 'wc-loyalty-gamification'); ?></span>
            </div>
            
            <?php if ($streak_info['streak_count'] > 0): ?>
                <div class="wc-loyalty-streak-progress">
                    <?php if ($next_milestone): ?>
                        <div class="wc-loyalty-milestone-progress">
                            <div class="milestone-progress-text">
                                <?php 
                                printf(
                                    esc_html__('%d days until %d-day milestone!', 'wc-loyalty-gamification'),
                                    $next_milestone_days,
                                    $next_milestone
                                ); 
                                ?>
                            </div>
                            <div class="milestone-progress-bar">
                                <div class="milestone-progress-fill" style="width: <?php echo esc_attr(($streak_info['streak_count'] % $next_milestone) / $next_milestone * 100); ?>%"></div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="wc-loyalty-checkin-status">
            <?php if ($streak_info['has_checked_in_today']): ?>
                <div class="wc-loyalty-checkin-complete">
                    <div class="checkin-icon-complete">✓</div>
                    <div class="checkin-message">
                        <?php 
                        printf(
                            esc_html__('You\'ve checked in today! %d points earned.', 'wc-loyalty-gamification'),
                            $streak_info['points_earned_today']
                        ); 
                        ?>
                    </div>
                    <div class="checkin-next">
                        <?php 
                        printf(
                            esc_html__('Next check-in available: %s', 'wc-loyalty-gamification'),
                            date_i18n(get_option('date_format'), strtotime($streak_info['next_checkin']))
                        ); 
                        ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="wc-loyalty-checkin-available">
                    <div class="checkin-points-available">
                        <span class="points-amount"><?php echo esc_html($potential_points); ?></span>
                        <span class="points-label"><?php esc_html_e('points available', 'wc-loyalty-gamification'); ?></span>
                    </div>
                    
                    <?php if ($potential_milestone): ?>
                        <div class="checkin-milestone-alert">
                            <?php 
                            printf(
                                esc_html__('Milestone bonus: +%d points!', 'wc-loyalty-gamification'),
                                $potential_milestone_bonus
                            ); 
                            ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($streak_info['streak_status'] == 'at_risk'): ?>
                        <div class="checkin-streak-alert">
                            <?php esc_html_e('Check in now to keep your streak!', 'wc-loyalty-gamification'); ?>
                        </div>
                    <?php endif; ?>
                    
                    <button type="button" id="wc-loyalty-checkin-btn" class="wc-loyalty-checkin-button">
                        <?php esc_html_e('Check In Now', 'wc-loyalty-gamification'); ?>
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="wc-loyalty-milestone-list">
        <h4><?php esc_html_e('Milestone Rewards', 'wc-loyalty-gamification'); ?></h4>
        <ul>
            <?php foreach ($milestone_rewards as $days => $bonus): ?>
                <li class="<?php echo ($streak_info['streak_count'] >= $days) ? 'achieved' : ''; ?>">
                    <span class="milestone-days"><?php echo esc_html($days); ?> <?php esc_html_e('days', 'wc-loyalty-gamification'); ?></span>
                    <span class="milestone-bonus">+<?php echo esc_html($bonus); ?> <?php esc_html_e('bonus points', 'wc-loyalty-gamification'); ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>