<?php
/**
 * Admin Settings Template
 *
 * This template displays the admin settings page.
 *
 * @package WC_Loyalty_Gamification
 */

defined('ABSPATH') || exit;
?>

<div class="wrap wc-loyalty-settings-page">
    <h1><?php esc_html_e('Loyalty Program Settings', 'wc-loyalty-gamification'); ?></h1>
    
   <div class="wc-loyalty-settings-tabs nav-tab-wrapper">
    <a href="#general-settings" class="nav-tab"><?php esc_html_e('General Settings', 'wc-loyalty-gamification'); ?></a>
    <a href="#reward-tiers" class="nav-tab"><?php esc_html_e('Reward Tiers', 'wc-loyalty-gamification'); ?></a>
    <a href="#membership-tiers" class="nav-tab"><?php esc_html_e('Membership Tiers', 'wc-loyalty-gamification'); ?></a>
</div>
    
    <form method="post" action="options.php" id="wc-loyalty-settings-form">
        <?php settings_fields('wc_loyalty_settings'); ?>
        
        <!-- General Settings Tab -->
        <div id="general-settings" class="wc-loyalty-tab-content">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Points per Euro', 'wc-loyalty-gamification'); ?></th>
                    <td>
                        <input type="number" name="wc_loyalty_points_per_euro" value="<?php echo esc_attr(get_option('wc_loyalty_points_per_euro', 1)); ?>" min="1" step="1" />
                        <p class="description"><?php esc_html_e('Number of points awarded per euro spent on orders', 'wc-loyalty-gamification'); ?></p>
                    </td>
                </tr>
                
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Points for Product Review', 'wc-loyalty-gamification'); ?></th>
                    <td>
                        <input type="number" name="wc_loyalty_points_for_review" value="<?php echo esc_attr(get_option('wc_loyalty_points_for_review', 50)); ?>" min="0" step="1" />
                        <p class="description"><?php esc_html_e('Number of points awarded for product reviews (customer must have purchased the product)', 'wc-loyalty-gamification'); ?></p>
                    </td>
                </tr>
                
                <tr valign="top">
                    <th scope="row"><?php esc_html_e('Premium Discount Max Order Value', 'wc-loyalty-gamification'); ?></th>
                    <td>
                        <input type="number" name="wc_loyalty_premium_discount_max" value="<?php echo esc_attr(get_option('wc_loyalty_premium_discount_max', 400)); ?>" min="0" step="1" />
                        <p class="description"><?php esc_html_e('Maximum order value (in lei) for the premium 60% discount awarded at 2000 points', 'wc-loyalty-gamification'); ?></p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(); ?>
        </div>
        
        <!-- Reward Tiers Tab -->
        <div id="reward-tiers" class="wc-loyalty-tab-content">
            <div class="wc-loyalty-reward-tiers" id="wc-loyalty-reward-tiers">
                <?php
                $reward_tiers = unserialize(get_option('wc_loyalty_reward_tiers', 'a:0:{}'));
                
                if (!empty($reward_tiers)) {
                    foreach ($reward_tiers as $tier => $reward) {
                        $show_value = $reward['type'] === 'discount' ? true : false;
                        $value = isset($reward['value']) && is_numeric($reward['value']) ? $reward['value'] : '';
                        
                        // Skip the 2000 points tier as it's managed separately
                        if ($tier == 2000) {
                            continue;
                        }
                        ?>
                        <div class="wc-loyalty-reward-tier">
                            <input type="number" class="wc-loyalty-reward-tier-points" value="<?php echo esc_attr($tier); ?>" placeholder="<?php esc_attr_e('Points', 'wc-loyalty-gamification'); ?>" min="1" step="1" />
                            
                            <select class="wc-loyalty-reward-tier-type">
                                <option value="discount" <?php selected($reward['type'], 'discount'); ?>><?php esc_html_e('Discount', 'wc-loyalty-gamification'); ?></option>
                                <option value="free_shipping" <?php selected($reward['type'], 'free_shipping'); ?>><?php esc_html_e('Free Shipping', 'wc-loyalty-gamification'); ?></option>
                            </select>
                            
                            <input type="number" class="wc-loyalty-reward-tier-value" value="<?php echo esc_attr($value); ?>" placeholder="<?php esc_attr_e('Value', 'wc-loyalty-gamification'); ?>" min="1" step="1" <?php if (!$show_value) echo 'style="display:none;"'; ?> />
                            
                            <div class="wc-loyalty-reward-tier-actions">
                                <button type="button" class="button wc-loyalty-remove-tier"><?php esc_html_e('Remove', 'wc-loyalty-gamification'); ?></button>
                            </div>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
            
            <div class="wc-loyalty-premium-tier">
                <h3><?php esc_html_e('Premium Reward (2000 Points)', 'wc-loyalty-gamification'); ?></h3>
                <p><?php esc_html_e('When users reach 2000 points, they will automatically receive a 60% discount coupon valid for orders up to the maximum value set in General Settings.', 'wc-loyalty-gamification'); ?></p>
            </div>
            
            <button type="button" id="wc-loyalty-add-tier" class="button wc-loyalty-add-tier"><?php esc_html_e('Add Reward Tier', 'wc-loyalty-gamification'); ?></button>
            
            <?php submit_button(); ?>
        </div>

        <!-- Membership Tiers Tab -->
        <div id="membership-tiers" class="wc-loyalty-tab-content">
            <div class="wc-loyalty-tiers" id="wc-loyalty-tiers">
                <?php
                $tiers = unserialize(get_option('wc_loyalty_tiers', 'a:0:{}'));
                
                if (!empty($tiers)) {
                    foreach ($tiers as $tier_key => $tier_data) {
                        ?>
                        <div class="wc-loyalty-tier">
                            <div class="wc-loyalty-tier-header">
                                <div class="wc-loyalty-tier-color-preview" style="background-color: <?php echo esc_attr($tier_data['color']); ?>"></div>
                                <input type="text" class="wc-loyalty-tier-key" value="<?php echo esc_attr($tier_key); ?>" placeholder="<?php esc_attr_e('Key (e.g. bronze)', 'wc-loyalty-gamification'); ?>" />
                                <input type="text" class="wc-loyalty-tier-name" value="<?php echo esc_attr($tier_data['name']); ?>" placeholder="<?php esc_attr_e('Display Name', 'wc-loyalty-gamification'); ?>" />
                                <input type="number" class="wc-loyalty-tier-min-points" value="<?php echo esc_attr($tier_data['min_points']); ?>" placeholder="<?php esc_attr_e('Min Points', 'wc-loyalty-gamification'); ?>" min="0" step="1" />
                                <input type="text" class="wc-loyalty-tier-color" value="<?php echo esc_attr($tier_data['color']); ?>" placeholder="<?php esc_attr_e('Color', 'wc-loyalty-gamification'); ?>" />
                                <div class="wc-loyalty-tier-actions">
                                    <button type="button" class="button wc-loyalty-remove-tier"><?php esc_html_e('Remove', 'wc-loyalty-gamification'); ?></button>
                                </div>
                            </div>
                            <div class="wc-loyalty-tier-perks-entry">
                                <textarea class="wc-loyalty-tier-perks-text" placeholder="<?php esc_attr_e('Tier benefits description', 'wc-loyalty-gamification'); ?>"><?php echo esc_textarea($tier_data['perks']); ?></textarea>
                            </div>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
            
            <button type="button" id="wc-loyalty-add-tier-membership" class="button wc-loyalty-add-tier"><?php esc_html_e('Add Membership Tier', 'wc-loyalty-gamification'); ?></button>
            
            <?php submit_button(); ?>
        </div>
    </form>
</div>

<!-- Template for new reward tier -->
<script type="text/html" id="tmpl-wc-loyalty-reward-tier-template">
    <<!-- Template for new reward tier (avoid using tmpl- prefix) -->
<div id="wc-loyalty-reward-tier-template" style="display:none;">
    <div class="wc-loyalty-reward-tier">
        <input type="number" class="wc-loyalty-reward-tier-points" value="" placeholder="<?php esc_attr_e('Points', 'wc-loyalty-gamification'); ?>" min="1" step="1" />
        
        <select class="wc-loyalty-reward-tier-type">
            <option value="discount"><?php esc_html_e('Discount', 'wc-loyalty-gamification'); ?></option>
            <option value="free_shipping"><?php esc_html_e('Free Shipping', 'wc-loyalty-gamification'); ?></option>
        </select>
        
        <input type="number" class="wc-loyalty-reward-tier-value" value="" placeholder="<?php esc_attr_e('Value', 'wc-loyalty-gamification'); ?>" min="1" step="1" />
        
        <div class="wc-loyalty-reward-tier-actions">
            <button type="button" class="button wc-loyalty-remove-tier"><?php esc_html_e('Remove', 'wc-loyalty-gamification'); ?></button>
        </div>
    </div>
</div>
</script>