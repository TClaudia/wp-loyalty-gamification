<?php
/**
 * My Account Loyalty Points Template - Minimalist Design
 *
 * This template displays the loyalty points page in My Account with a minimalist design.
 *
 * @package WC_Loyalty_Gamification
 */

// Direct access prevention
if (!defined('ABSPATH')) {
    exit;
}

?>

<style>
/* Custom minimalist styling */
.min-loyalty-container {
    background-color: white;
    padding: 1rem;
    max-width: 48rem;
    margin: 0 auto;
    font-size: 0.875rem;
}

.loyalty-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.member-tier {
    border-left: 4px solid #7952b3;
    padding-left: 0.5rem;
}

.member-tier h3 {
    font-size: 1rem;
    font-weight: 600;
    color: #5e3d8f;
    margin: 0;
}

.member-tier p {
    font-size: 0.75rem;
    color: #666;
    margin: 0;
}

.points-display {
    display: flex;
    align-items: baseline;
}

.points-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #7952b3;
    margin-right: 0.5rem;
}

.points-label {
    font-size: 0.75rem;
    color: #666;
}

.section-heading {
    font-size: 0.875rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 0.5rem;
    border-bottom: 1px solid #eee;
    padding-bottom: 0.25rem;
}

.coupons-grid {
    display: grid;
    grid-template-columns: repeat(1, 1fr);
    gap: 0.5rem;
    margin-bottom: 1.5rem;
}

@media (min-width: 640px) {
    .coupons-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

.coupon-card {
    border: 1px solid #e5e7eb;
    border-radius: 0.25rem;
    padding: 0.5rem;
    background-color: #f9fafb;
    font-size: 0.75rem;
}

.coupon-card.premium {
    background-color: #fffbeb;
}

.coupon-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.25rem;
}

.coupon-discount {
    font-weight: 700;
}

.coupon-premium-badge {
    margin-left: 0.25rem;
    background-color: #f59e0b;
    color: white;
    padding: 0 0.25rem;
    border-radius: 9999px;
    font-size: 0.75rem;
}

.coupon-code {
    background-color: rgba(229, 231, 235, 0.5);
    padding: 0.25rem;
    border-radius: 0.25rem;
    font-family: monospace;
    font-size: 0.75rem;
    margin-bottom: 0.25rem;
}

.premium .coupon-code {
    background-color: rgba(255, 255, 255, 0.6);
}

.coupon-expiry, .coupon-condition {
    font-size: 0.75rem;
    color: #666;
}

.coupon-premium-condition {
    font-size: 0.75rem;
    color: #92400e;
}

.copy-btn {
    background-color: #f97316;
    color: white;
    font-size: 0.75rem;
    padding: 0.125rem 0.25rem;
    border-radius: 0.25rem;
    border: none;
    cursor: pointer;
}

.copy-btn:hover {
    background-color: #ea580c;
}

.points-table {
    width: 100%;
    font-size: 0.75rem;
    margin-bottom: 1rem;
}

.points-table th {
    background-color: #f9fafb;
    text-align: left;
    padding: 0.25rem;
}

.points-table td {
    padding: 0.25rem;
    border-bottom: 1px solid #f3f4f6;
}

.points-positive {
    color: #10b981;
    font-weight: 500;
}

.date-cell {
    color: #666;
}

.description-cell {
    color: #333;
}
</style>

<?php
// Display daily streak status at the top of the page
do_action('wc_loyalty_display_account_streak');
?>
<div class="min-loyalty-container">
    <!-- Member Status and Points in one row -->
    <div class="loyalty-header">
        <div class="member-tier">
            <h3><?php echo esc_html($tier_data['name']); ?></h3>
            <p><?php echo esc_html($tier_data['perks']); ?></p>
        </div>
        
        <div class="points-display">
            <div class="points-value"><?php echo esc_html($points); ?></div>
            <div class="points-label"><?php esc_html_e('Current Points', 'wc-loyalty-gamification'); ?></div>
        </div>
    </div>
    <?php
// In templates/account/loyalty-points.php, replace the coupons grid section with this:

// Filter active coupons (not used and not expired)
$active_coupons = array_filter($user_coupons, function($coupon) {
    $coupon_expired = strtotime($coupon['expires']) < time();
    return !$coupon['is_used'] && !$coupon_expired;
});
?>

<!-- Coupons in compact grid -->
<h2 class="section-heading"><?php esc_html_e('Your Discount Coupons', 'wc-loyalty-gamification'); ?></h2>

<?php if (!empty($active_coupons)) : ?>
    <div class="coupons-grid">
        <?php foreach ($active_coupons as $index => $coupon): 
            $is_premium = isset($coupon['tier']) && $coupon['tier'] === 2000;
        ?>
            <div class="coupon-card <?php echo $is_premium ? 'premium' : ''; ?>">
                <div class="coupon-header">
                    <div class="coupon-discount">
                        <?php printf(esc_html__('%d%% OFF', 'wc-loyalty-gamification'), $coupon['discount']); ?>
                        <?php if ($is_premium): ?>
                            <span class="coupon-premium-badge"><?php esc_html_e('Premium', 'wc-loyalty-gamification'); ?></span>
                        <?php endif; ?>
                    </div>
                    <button class="copy-btn wc-loyalty-copy-code" data-code="<?php echo esc_attr($coupon['code']); ?>"><?php esc_html_e('Copy', 'wc-loyalty-gamification'); ?></button>
                </div>
                <div class="coupon-code"><?php echo esc_html($coupon['code']); ?></div>
                <?php if ($is_premium): ?>
                    <div class="coupon-premium-condition">
                        <?php 
                        printf(
                            esc_html__('Valid for orders up to %s lei', 'wc-loyalty-gamification'),
                            wc_loyalty_get_premium_discount_max() 
                        ); 
                        ?>
                    </div>
                <?php endif; ?>
                <div class="coupon-expiry">
                    <?php printf(esc_html__('Valid until %s', 'wc-loyalty-gamification'), date_i18n(get_option('date_format'), strtotime($coupon['expires']))); ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else : ?>
    <div class="woocommerce-info">
        <?php esc_html_e('You don\'t have any active discount coupons. Earn more points to receive discount rewards!', 'wc-loyalty-gamification'); ?>
    </div>
<?php endif; ?>


    <!-- Points History - Compact -->
    <h2 class="section-heading"><?php esc_html_e('Points History', 'wc-loyalty-gamification'); ?></h2>
    <div>
        <?php if (empty($points_history)): ?>
            <div class="woocommerce-info">
                <?php esc_html_e('No points history yet.', 'wc-loyalty-gamification'); ?>
            </div>
        <?php else: ?>
            <table class="points-table">
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
                            <td class="date-cell">
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
                            <td class="description-cell"><?php echo esc_html($entry['description']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Claimed Rewards - Compact -->
    <h2 class="section-heading"><?php esc_html_e('Claimed Rewards', 'wc-loyalty-gamification'); ?></h2>
    <div>
        <?php if (empty($claimed_rewards)): ?>
            <div class="woocommerce-info">
                <?php esc_html_e('No rewards claimed yet.', 'wc-loyalty-gamification'); ?>
            </div>
        <?php else: ?>
            <table class="points-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Date', 'wc-loyalty-gamification'); ?></th>
                        <th><?php esc_html_e('Points', 'wc-loyalty-gamification'); ?></th>
                        <th><?php esc_html_e('Reward', 'wc-loyalty-gamification'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    foreach (array_reverse($claimed_rewards, true) as $tier => $date): 
                        $reward = isset($reward_tiers[$tier]) ? $reward_tiers[$tier] : null;
                        if (!$reward || !is_array($reward) || !isset($reward['type'])) continue;
                    ?>
                        <tr>
                            <td class="date-cell">
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
</div>

<script>
// Script to handle coupon code copying
document.addEventListener('DOMContentLoaded', function() {
    const copyButtons = document.querySelectorAll('.wc-loyalty-copy-code');
    
    copyButtons.forEach(button => {
        button.addEventListener('click', function() {
            const couponCode = this.getAttribute('data-code');
            const originalText = this.textContent;
            
            // Create a temporary textarea element to copy from
            const textarea = document.createElement('textarea');
            textarea.value = couponCode;
            textarea.setAttribute('readonly', '');
            textarea.style.position = 'absolute';
            textarea.style.left = '-9999px';
            document.body.appendChild(textarea);
            
            // Select and copy the text
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            
            // Update button text
            this.textContent = 'Copied!';
            
            // Reset button text after a delay
            setTimeout(() => {
                this.textContent = originalText;
            }, 2000);
        });
    });
});