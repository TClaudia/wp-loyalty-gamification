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

<<<<<<< HEAD
<div class="wc-loyalty-tier-summary">
    <?php
    $tier_key = WC_Loyalty()->points->get_user_tier($user_id);
    $tier_data = WC_Loyalty()->points->get_user_tier_data($user_id);
    $next_tier = WC_Loyalty()->points->get_next_tier_data($user_id);
    
    // Ensure tier_data has required keys
    if (!is_array($tier_data)) {
        $tier_data = array(
            'name' => 'Bronze',
            'color' => '#cd7f32',
            'perks' => __('Welcome to our loyalty program!', 'wc-loyalty-gamification')
        );
    } else {
        // Make sure required keys exist
        if (!isset($tier_data['name'])) $tier_data['name'] = 'Bronze';
        if (!isset($tier_data['color'])) $tier_data['color'] = '#cd7f32';
        if (!isset($tier_data['perks'])) $tier_data['perks'] = __('Welcome to our loyalty program!', 'wc-loyalty-gamification');
    }
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
    
    <?php if ($next_tier && is_array($next_tier)): ?>
    <div class="wc-loyalty-next-tier-progress">
        <h4>
            <?php 
            printf(
                esc_html__('Next Tier: %s', 'wc-loyalty-gamification'),
                esc_html(isset($next_tier['name']) ? $next_tier['name'] : '')
            ); 
            ?>
        </h4>
        <div class="wc-loyalty-progress-container">
            <?php 
            $min_points = isset($next_tier['min_points']) ? $next_tier['min_points'] : 0;
            $points_needed = $min_points - $points;
            $percentage = ($min_points > 0) ? min(100, ($points / $min_points) * 100) : 0;
            $next_tier_color = isset($next_tier['color']) ? $next_tier['color'] : '#7952b3';
            ?>
            <div class="wc-loyalty-progress-bar">
                <div class="wc-loyalty-progress-fill" style="width: <?php echo esc_attr($percentage); ?>%; background-color: <?php echo esc_attr($next_tier_color); ?>"></div>
            </div>
            <div class="wc-loyalty-progress-text">
                <?php 
                printf(
                    esc_html__('You need %d more points to reach %s level', 'wc-loyalty-gamification'),
                    $points_needed,
                    esc_html(isset($next_tier['name']) ? $next_tier['name'] : '')
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
            
            <?php 
            $next_reward_tier = WC_Loyalty()->rewards->get_next_reward_tier($points);
            if ($next_reward_tier): 
            ?>
                <div class="wc-loyalty-next-reward">
                    <?php 
                    printf(
                        esc_html__('You need %d more points to earn your next reward!', 'wc-loyalty-gamification'),
                        $next_reward_tier - $points
                    ); 
                    ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php
    // Check if user can claim a free product
    $free_product_tier = WC_Loyalty()->account->can_claim_free_product($user_id);
    
    if ($free_product_tier) : 
        // Get available products
        $available_products = WC_Loyalty()->account->get_available_free_products($user_id);
    ?>
        <div class="wc-loyalty-free-products-grid" id="claim-free-product">
            <h2><?php esc_html_e('Claim Your Free Product', 'wc-loyalty-gamification'); ?></h2>
            
            <p><?php esc_html_e('Congratulations! You have enough points to claim a free product. Choose from one of the following:', 'wc-loyalty-gamification'); ?></p>
            
            <?php if (!empty($available_products) && is_array($available_products)) : ?>
                <?php
                // Group products by wishlist/regular
                $wishlist_products = array_filter($available_products, function($product) {
                    return isset($product['wishlist']) && $product['wishlist'];
                });
                
                $regular_products = array_filter($available_products, function($product) {
                    return !isset($product['wishlist']) || !$product['wishlist'];
                });
                ?>
                
                <?php if (!empty($wishlist_products)) : ?>
                    <div class="wc-loyalty-product-section">
                        <h4><?php esc_html_e('From Your Wishlist', 'wc-loyalty-gamification'); ?></h4>
                        
                        <div class="wc-loyalty-products-list">
                            <?php foreach ($wishlist_products as $product) : ?>
                                <div class="wc-loyalty-product-item">
                                    <div class="wc-loyalty-product-image">
                                        <?php echo isset($product['image']) ? $product['image'] : ''; ?>
                                        <div class="wc-loyalty-product-wishlist-badge">
                                            <?php esc_html_e('Wishlist', 'wc-loyalty-gamification'); ?>
                                        </div>
                                    </div>
                                    <div class="wc-loyalty-product-info">
                                        <h5 class="wc-loyalty-product-name"><?php echo isset($product['name']) ? esc_html($product['name']) : ''; ?></h5>
                                        <a href="#" class="claim-free-product" data-product-id="<?php echo isset($product['id']) ? esc_attr($product['id']) : ''; ?>">
                                            <?php esc_html_e('Claim This', 'wc-loyalty-gamification'); ?>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($regular_products)) : ?>
                    <div class="wc-loyalty-product-section">
                        <h4><?php esc_html_e('Available Products', 'wc-loyalty-gamification'); ?></h4>
                        
                        <div class="wc-loyalty-products-list">
                            <?php foreach ($regular_products as $product) : ?>
                                <div class="wc-loyalty-product-item">
                                    <div class="wc-loyalty-product-image">
                                        <?php echo isset($product['image']) ? $product['image'] : ''; ?>
                                    </div>
                                    <div class="wc-loyalty-product-info">
                                        <h5 class="wc-loyalty-product-name"><?php echo isset($product['name']) ? esc_html($product['name']) : ''; ?></h5>
                                        <a href="#" class="claim-free-product" data-product-id="<?php echo isset($product['id']) ? esc_attr($product['id']) : ''; ?>">
                                            <?php esc_html_e('Claim This', 'wc-loyalty-gamification'); ?>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php else : ?>
                <div class="woocommerce-info">
                    <?php esc_html_e('No products are currently available for redemption. Please check back later.', 'wc-loyalty-gamification'); ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <div class="wc-loyalty-points-history">
        <h3><?php esc_html_e('Points History', 'wc-loyalty-gamification'); ?></h3>
        
        <?php if (empty($points_history) || !is_array($points_history)) : ?>
=======
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

    <!-- Coupons in compact grid -->
    <h2 class="section-heading"><?php esc_html_e('Your Discount Coupons', 'wc-loyalty-gamification'); ?></h2>
    <div class="coupons-grid">
        <?php foreach ($user_coupons as $index => $coupon): 
            $coupon_expired = strtotime($coupon['expires']) < time();
            $coupon_class = $coupon['is_used'] ? 'used' : ($coupon_expired ? 'expired' : 'active');
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
                    <?php if ($coupon_expired): ?>
                        <?php esc_html_e('Expired', 'wc-loyalty-gamification'); ?>
                    <?php elseif ($coupon['is_used']): ?>
                        <?php esc_html_e('Used', 'wc-loyalty-gamification'); ?>
                    <?php else: ?>
                        <?php printf(esc_html__('Valid until %s', 'wc-loyalty-gamification'), date_i18n(get_option('date_format'), strtotime($coupon['expires']))); ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Points History - Compact -->
    <h2 class="section-heading"><?php esc_html_e('Points History', 'wc-loyalty-gamification'); ?></h2>
    <div>
        <?php if (empty($points_history)): ?>
>>>>>>> 815d2df76d4f986c861a1c2a5831e3bb6472e936
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
<<<<<<< HEAD
                    <?php foreach (array_reverse($points_history) as $entry) : 
                        // Ensure all required keys exist
                        if (!isset($entry['date'])) $entry['date'] = current_time('mysql');
                        if (!isset($entry['points'])) $entry['points'] = 0;
                        if (!isset($entry['description'])) $entry['description'] = '';
                    ?>
=======
                    <?php foreach (array_reverse($points_history) as $entry): ?>
>>>>>>> 815d2df76d4f986c861a1c2a5831e3bb6472e936
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
<<<<<<< HEAD
    
    <div class="wc-loyalty-claimed-rewards">
        <h3><?php esc_html_e('Claimed Rewards', 'wc-loyalty-gamification'); ?></h3>
        
        <?php if (empty($claimed_rewards) || !is_array($claimed_rewards)) : ?>
=======

    <!-- Claimed Rewards - Compact -->
    <h2 class="section-heading"><?php esc_html_e('Claimed Rewards', 'wc-loyalty-gamification'); ?></h2>
    <div>
        <?php if (empty($claimed_rewards)): ?>
>>>>>>> 815d2df76d4f986c861a1c2a5831e3bb6472e936
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
<<<<<<< HEAD
                    if (!isset($reward_tiers) || !is_array($reward_tiers)) {
                        $reward_tiers = array();
                    }
                    
                    foreach (array_reverse($claimed_rewards, true) as $tier => $date) : 
=======
                    foreach (array_reverse($claimed_rewards, true) as $tier => $date): 
>>>>>>> 815d2df76d4f986c861a1c2a5831e3bb6472e936
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
<<<<<<< HEAD
                                        $value = isset($reward['value']) ? $reward['value'] : 0;
                                        printf(
                                            esc_html__('%d%% Discount', 'wc-loyalty-gamification'),
                                            esc_html($value)
                                        );
=======
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
>>>>>>> 815d2df76d4f986c861a1c2a5831e3bb6472e936
                                        break;
                                    case 'free_shipping':
                                        esc_html_e('Free Shipping', 'wc-loyalty-gamification');
                                        break;
<<<<<<< HEAD
                                    case 'free_product':
                                        esc_html_e('Free Product', 'wc-loyalty-gamification');
                                        break;
                                    default:
                                        esc_html_e('Reward', 'wc-loyalty-gamification');
                                        break;
                                }
=======
                                endswitch;
>>>>>>> 815d2df76d4f986c861a1c2a5831e3bb6472e936
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
</script>