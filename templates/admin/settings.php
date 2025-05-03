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
    <a href="#free-products" class="nav-tab"><?php esc_html_e('Free Products', 'wc-loyalty-gamification'); ?></a>
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
                        ?>
                        <div class="wc-loyalty-reward-tier">
                            <input type="number" class="wc-loyalty-reward-tier-points" value="<?php echo esc_attr($tier); ?>" placeholder="<?php esc_attr_e('Points', 'wc-loyalty-gamification'); ?>" min="1" step="1" />
                            
                            <select class="wc-loyalty-reward-tier-type">
                                <option value="discount" <?php selected($reward['type'], 'discount'); ?>><?php esc_html_e('Discount', 'wc-loyalty-gamification'); ?></option>
                                <option value="free_shipping" <?php selected($reward['type'], 'free_shipping'); ?>><?php esc_html_e('Free Shipping', 'wc-loyalty-gamification'); ?></option>
                                <option value="free_product" <?php selected($reward['type'], 'free_product'); ?>><?php esc_html_e('Free Product', 'wc-loyalty-gamification'); ?></option>
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
        
        <!-- Free Products Tab -->
        <div id="free-products" class="wc-loyalty-tab-content">
            <div class="wc-loyalty-free-products">
                <h2><?php esc_html_e('Free Products', 'wc-loyalty-gamification'); ?></h2>
                
                <p><?php esc_html_e('Select products that can be chosen as free rewards when customers reach the required points.', 'wc-loyalty-gamification'); ?></p>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Product', 'wc-loyalty-gamification'); ?></th>
                            <th class="wc-loyalty-product-actions"><?php esc_html_e('Actions', 'wc-loyalty-gamification'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $free_products = WC_Loyalty()->admin->get_free_products();
                        
                        if (!empty($free_products)) {
                            foreach ($free_products as $product_entry) {
                                $product = wc_get_product($product_entry->product_id);
                                if ($product) {
                                    ?>
                                    <tr>
                                        <td><?php echo esc_html($product->get_name()); ?></td>
                                        <td class="wc-loyalty-product-actions">
                                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=wc-loyalty-settings&action=remove_product&product_id=' . $product_entry->id), 'remove_free_product'); ?>" class="button button-secondary"><?php esc_html_e('Remove', 'wc-loyalty-gamification'); ?></a>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            }
                        } else {
                            ?>
                            <tr>
                                <td colspan="2"><?php esc_html_e('No free products added yet.', 'wc-loyalty-gamification'); ?></td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
                
                <div class="wc-loyalty-add-product-form">
                    <h3><?php esc_html_e('Add Product', 'wc-loyalty-gamification'); ?></h3>
                    
                    <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=wc-loyalty-settings')); ?>">
                        <?php wp_nonce_field('add_free_product'); ?>
                        <input type="hidden" name="action" value="add_product" />
                        
                        <select name="product_id" class="wc-product-search" data-placeholder="<?php esc_attr_e('Search for a product...', 'wc-loyalty-gamification'); ?>" data-action="woocommerce_json_search_products" data-exclude=""></select>
                        
                        <?php submit_button(__('Add Product', 'wc-loyalty-gamification'), 'primary', 'add_free_product', false); ?>
                    </form>
                </div>
            </div>
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
            <option value="free_product"><?php esc_html_e('Free Product', 'wc-loyalty-gamification'); ?></option>
        </select>
        
        <input type="number" class="wc-loyalty-reward-tier-value" value="" placeholder="<?php esc_attr_e('Value', 'wc-loyalty-gamification'); ?>" min="1" step="1" />
        
        <div class="wc-loyalty-reward-tier-actions">
            <button type="button" class="button wc-loyalty-remove-tier"><?php esc_html_e('Remove', 'wc-loyalty-gamification'); ?></button>
        </div>
    </div>
</div>
</script>