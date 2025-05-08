<?php
/**
 * Template nou pentru pagina de setări admin - WooCommerce Loyalty Gamification
 * Reține toată funcționalitatea, dar îmbunătățește designul
 */
defined('ABSPATH') || exit;
?>

<div class="wrap wc-loyalty-settings-page">
    <h1><?php esc_html_e('Setări Program de Fidelitate', 'wc-loyalty-gamification'); ?></h1>
    
    <?php if (isset($_GET['paypal_notice']) && $_GET['paypal_notice'] == '1'): ?>
    <div class="wc-loyalty-paypal-promo">
        <img src="<?php echo esc_url(WC_LOYALTY_PLUGIN_URL); ?>assets/images/paypal-logo.png" alt="PayPal">
        <span><?php esc_html_e('PayPal Payments is almost ready. To get started, connect your PayPal account with the Account Setup page.', 'wc-loyalty-gamification'); ?></span>
        <a href="#" class="button button-secondary" style="margin-left: 15px;"><?php esc_html_e('Account Setup', 'wc-loyalty-gamification'); ?></a>
    </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['programmer_activ']) && $_GET['programmer_activ'] == '1'): ?>
    <!-- Programatorul de actualizări -->
    <div class="wc-loyalty-update-scheduler">
        <div class="wc-loyalty-card-header">
            <h3><?php esc_html_e('Programare de actualizări', 'wc-loyalty-gamification'); ?></h3>
            <div>
                <?php if (isset($active_update) && $active_update): ?>
                    <span class="wc-loyalty-status active"><?php esc_html_e('Activ', 'wc-loyalty-gamification'); ?></span>
                <?php else: ?>
                    <span class="wc-loyalty-status inactive"><?php esc_html_e('Inactiv', 'wc-loyalty-gamification'); ?></span>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="scheduler-grid">
            <input type="text" id="update_scheduler_date" name="update_scheduler_date" value="<?php echo esc_attr(date('Y-m-d')); ?>" class="wc-loyalty-input-md" />
            <select id="update_scheduler_action" name="update_scheduler_action" class="wc-loyalty-input-md">
                <option value=""><?php esc_html_e('Alegeți documentul...', 'wc-loyalty-gamification'); ?></option>
            </select>
            <a href="#" class="button"><?php esc_html_e('Setări detaliate »', 'wc-loyalty-gamification'); ?></a>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Tab-uri pentru secțiuni -->
    <div class="wc-loyalty-settings-tabs nav-tab-wrapper">
        <a href="#general-settings" class="nav-tab nav-tab-active"><?php esc_html_e('Setări Generale', 'wc-loyalty-gamification'); ?></a>
        <a href="#reward-tiers" class="nav-tab"><?php esc_html_e('Niveluri de Recompensă', 'wc-loyalty-gamification'); ?></a>
        <a href="#membership-tiers" class="nav-tab"><?php esc_html_e('Niveluri de Membru', 'wc-loyalty-gamification'); ?></a>
        <a href="#checkin-settings" class="nav-tab"><?php esc_html_e('Sistem de Verificare', 'wc-loyalty-gamification'); ?></a>
    </div>
    
    <form method="post" action="options.php" id="wc-loyalty-settings-form">
        <?php settings_fields('wc_loyalty_settings'); ?>
        
        <!-- Setări Generale Tab -->
        <div id="general-settings" class="wc-loyalty-tab-content">
            <div class="wc-loyalty-settings-columns">
                <div class="wc-loyalty-config-card">
                    <h3><?php esc_html_e('Configurare Puncte', 'wc-loyalty-gamification'); ?></h3>
                    
                    <div class="wc-loyalty-form-grid">
                        <label for="wc_loyalty_points_per_euro"><?php esc_html_e('Puncte per Euro', 'wc-loyalty-gamification'); ?></label>
                        <div>
                            <input type="number" id="wc_loyalty_points_per_euro" name="wc_loyalty_points_per_euro" 
                                   value="<?php echo esc_attr(get_option('wc_loyalty_points_per_euro', 1)); ?>" min="1" step="1" class="wc-loyalty-input-sm" />
                            <p class="description"><?php esc_html_e('Numărul de puncte acordate per euro cheltuit pe comenzi', 'wc-loyalty-gamification'); ?></p>
                        </div>
                    </div>
                    
                    <div class="wc-loyalty-form-grid">
                        <label for="wc_loyalty_points_for_review"><?php esc_html_e('Puncte pentru Recenzie Produs', 'wc-loyalty-gamification'); ?></label>
                        <div>
                            <input type="number" id="wc_loyalty_points_for_review" name="wc_loyalty_points_for_review" 
                                   value="<?php echo esc_attr(get_option('wc_loyalty_points_for_review', 50)); ?>" min="0" step="1" class="wc-loyalty-input-sm" />
                            <p class="description"><?php esc_html_e('Numărul de puncte acordate pentru recenzii de produse (clientul trebuie să fi cumpărat produsul)', 'wc-loyalty-gamification'); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="wc-loyalty-config-card">
                    <h3><?php esc_html_e('Configurare Discount Premium', 'wc-loyalty-gamification'); ?></h3>
                    
                    <div class="wc-loyalty-form-grid">
                        <label for="wc_loyalty_premium_discount_max"><?php esc_html_e('Valoarea Maximă a Comenzii pentru Reducerea Premium', 'wc-loyalty-gamification'); ?></label>
                        <div>
                            <input type="number" id="wc_loyalty_premium_discount_max" name="wc_loyalty_premium_discount_max" 
                                   value="<?php echo esc_attr(get_option('wc_loyalty_premium_discount_max', 400)); ?>" min="0" step="1" class="wc-loyalty-input-sm" />
                            <p class="description"><?php esc_html_e('Valoarea maximă a comenzii (în lei) pentru reducerea premium de 60% acordată la 2000 de puncte', 'wc-loyalty-gamification'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php submit_button(); ?>
        </div>
        
        <!-- Niveluri de Recompensă Tab -->
        <div id="reward-tiers" class="wc-loyalty-tab-content" style="display: none;">
            <div class="wc-loyalty-reward-tiers" id="wc-loyalty-reward-tiers">
                <h3><?php esc_html_e('Niveluri de Recompensă Personalizate', 'wc-loyalty-gamification'); ?></h3>
                
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
                            <input type="number" class="wc-loyalty-reward-tier-points" value="<?php echo esc_attr($tier); ?>" placeholder="<?php esc_attr_e('Puncte', 'wc-loyalty-gamification'); ?>" min="1" step="1" />
                            
                            <select class="wc-loyalty-reward-tier-type">
                                <option value="discount" <?php selected($reward['type'], 'discount'); ?>><?php esc_html_e('Reducere', 'wc-loyalty-gamification'); ?></option>
                                <option value="free_shipping" <?php selected($reward['type'], 'free_shipping'); ?>><?php esc_html_e('Transport Gratuit', 'wc-loyalty-gamification'); ?></option>
                            </select>
                            
                            <input type="number" class="wc-loyalty-reward-tier-value" value="<?php echo esc_attr($value); ?>" placeholder="<?php esc_attr_e('Valoare', 'wc-loyalty-gamification'); ?>" min="1" step="1" <?php if (!$show_value) echo 'style="display:none;"'; ?> />
                            
                            <div class="wc-loyalty-reward-tier-actions">
                                <button type="button" class="button wc-loyalty-remove-tier"><?php esc_html_e('Elimină', 'wc-loyalty-gamification'); ?></button>
                            </div>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
            
            <button type="button" id="wc-loyalty-add-tier" class="button wc-loyalty-add-tier"><?php esc_html_e('Adaugă Nivel de Recompensă', 'wc-loyalty-gamification'); ?></button>
            
            <div class="wc-loyalty-premium-tier">
                <h3><?php esc_html_e('Recompensă Premium (2000 Puncte)', 'wc-loyalty-gamification'); ?></h3>
                <p><?php esc_html_e('Când utilizatorii ajung la 2000 puncte, vor primi automat un cupon de reducere de 60% valid pentru comenzi până la valoarea maximă setată în Setări Generale.', 'wc-loyalty-gamification'); ?></p>
            </div>
            
            <?php submit_button(); ?>
        </div>
        
        <!-- Niveluri de Membru Tab -->
        <div id="membership-tiers" class="wc-loyalty-tab-content" style="display: none;">
            <div class="wc-loyalty-tiers" id="wc-loyalty-tiers">
                <h3><?php esc_html_e('Niveluri de Membru', 'wc-loyalty-gamification'); ?></h3>
                
                <?php
                $tiers = unserialize(get_option('wc_loyalty_tiers', 'a:0:{}'));
                
                if (!empty($tiers)) {
                    foreach ($tiers as $tier_key => $tier_data) {
                        ?>
                        <div class="wc-loyalty-tier">
                            <div class="wc-loyalty-tier-header">
                                <div class="wc-loyalty-tier-color-preview" style="background-color: <?php echo esc_attr($tier_data['color']); ?>"></div>
                                <input type="text" class="wc-loyalty-tier-key" value="<?php echo esc_attr($tier_key); ?>" placeholder="<?php esc_attr_e('Cheie (ex. bronz)', 'wc-loyalty-gamification'); ?>" />
                                <input type="text" class="wc-loyalty-tier-name" value="<?php echo esc_attr($tier_data['name']); ?>" placeholder="<?php esc_attr_e('Nume Afișat', 'wc-loyalty-gamification'); ?>" />
                                <input type="number" class="wc-loyalty-tier-min-points" value="<?php echo esc_attr($tier_data['min_points']); ?>" placeholder="<?php esc_attr_e('Puncte Minime', 'wc-loyalty-gamification'); ?>" min="0" step="1" />
                                <input type="text" class="wc-loyalty-tier-color" value="<?php echo esc_attr($tier_data['color']); ?>" placeholder="<?php esc_attr_e('Culoare', 'wc-loyalty-gamification'); ?>" />
                                <div class="wc-loyalty-tier-actions">
                                    <button type="button" class="button wc-loyalty-remove-tier"><?php esc_html_e('Elimină', 'wc-loyalty-gamification'); ?></button>
                                </div>
                            </div>
                            <div class="wc-loyalty-tier-perks-entry">
                                <textarea class="wc-loyalty-tier-perks-text" placeholder="<?php esc_attr_e('Descrierea beneficiilor nivelului', 'wc-loyalty-gamification'); ?>"><?php echo esc_textarea($tier_data['perks']); ?></textarea>
                            </div>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
            
            <button type="button" id="wc-loyalty-add-tier-membership" class="button wc-loyalty-add-tier"><?php esc_html_e('Adaugă Nivel de Membru', 'wc-loyalty-gamification'); ?></button>
            
            <?php submit_button(); ?>
        </div>
        
        <!-- Sistem de Verificare Tab -->
        <div id="checkin-settings" class="wc-loyalty-tab-content" style="display: none;">
            <div class="wc-loyalty-settings-columns">
                <div class="wc-loyalty-config-card">
                    <h3><?php esc_html_e('Setări Verificare Zilnică', 'wc-loyalty-gamification'); ?></h3>
                    
                    <div class="wc-loyalty-form-grid">
                        <label for="wc_loyalty_base_checkin_points"><?php esc_html_e('Puncte de Bază pentru Verificare', 'wc-loyalty-gamification'); ?></label>
                        <div>
                            <input type="number" id="wc_loyalty_base_checkin_points" name="wc_loyalty_base_checkin_points" 
                                   value="<?php echo esc_attr(get_option('wc_loyalty_base_checkin_points', 5)); ?>" min="1" step="1" class="wc-loyalty-input-sm" />
                            <p class="description"><?php esc_html_e('Numărul de bază de puncte acordate pentru fiecare verificare zilnică', 'wc-loyalty-gamification'); ?></p>
                        </div>
                    </div>
                    
                    <div class="wc-loyalty-form-grid">
                        <label for="wc_loyalty_streak_multiplier"><?php esc_html_e('Multiplicator pentru Serie', 'wc-loyalty-gamification'); ?></label>
                        <div>
                            <input type="number" id="wc_loyalty_streak_multiplier" name="wc_loyalty_streak_multiplier" 
                                   value="<?php echo esc_attr(get_option('wc_loyalty_streak_multiplier', 0.1)); ?>" min="0" max="1" step="0.1" class="wc-loyalty-input-sm" />
                            <p class="description"><?php esc_html_e('Multiplicator de puncte suplimentare pe zi de serie (de ex., 0.1 = +10% puncte per zi de serie)', 'wc-loyalty-gamification'); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="wc-loyalty-config-card">
                    <h3><?php esc_html_e('Recompense pentru Obiective', 'wc-loyalty-gamification'); ?></h3>
                    <p class="description"><?php esc_html_e('Definește zilele speciale de obiectiv și punctele bonus acordate când utilizatorii ating aceste obiective', 'wc-loyalty-gamification'); ?></p>
                    
                    <div id="wc-loyalty-milestone-rewards">
                        <?php
                        $milestone_rewards = json_decode(get_option('wc_loyalty_milestone_rewards', '{}'), true);
                        
                        if (empty($milestone_rewards)) {
                            $milestone_rewards = array(
                                '7' => 50,   // 7-day streak: 50 bonus points
                                '30' => 200, // 30-day streak: 200 bonus points
                                '90' => 500, // 90-day streak: 500 bonus points
                                '365' => 2000 // 365-day streak: 2000 bonus points
                            );
                        }
                        
                        foreach ($milestone_rewards as $days => $bonus) {
                            ?>
                            <div class="wc-loyalty-milestone-reward">
                                <input type="number" class="wc-loyalty-milestone-days" value="<?php echo esc_attr($days); ?>" placeholder="<?php esc_attr_e('Zile', 'wc-loyalty-gamification'); ?>" min="1" step="1" />
                                <input type="number" class="wc-loyalty-milestone-bonus" value="<?php echo esc_attr($bonus); ?>" placeholder="<?php esc_attr_e('Puncte Bonus', 'wc-loyalty-gamification'); ?>" min="1" step="1" />
                                <button type="button" class="button wc-loyalty-remove-milestone"><?php esc_html_e('Elimină', 'wc-loyalty-gamification'); ?></button>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                    
                    <button type="button" id="wc-loyalty-add-milestone" class="button wc-loyalty-add-tier"><?php esc_html_e('Adaugă Obiectiv', 'wc-loyalty-gamification'); ?></button>
                    
                    <input type="hidden" name="wc_loyalty_milestone_rewards" id="wc_loyalty_milestone_rewards_json" value='<?php echo esc_attr(json_encode($milestone_rewards)); ?>' />
                </div>
            </div>
                
            <?php submit_button(); ?>
        </div>
    </form>
    
    <!-- Template pentru niveluri de recompensă noi -->
    <div id="wc-loyalty-reward-tier-template" style="display:none;">
        <div class="wc-loyalty-reward-tier">
            <input type="number" class="wc-loyalty-reward-tier-points" value="" placeholder="<?php esc_attr_e('Puncte', 'wc-loyalty-gamification'); ?>" min="1" step="1" />
            
            <select class="wc-loyalty-reward-tier-type">
                <option value="discount"><?php esc_html_e('Reducere', 'wc-loyalty-gamification'); ?></option>
                <option value="free_shipping"><?php esc_html_e('Transport Gratuit', 'wc-loyalty-gamification'); ?></option>
            </select>
            
            <input type="number" class="wc-loyalty-reward-tier-value" value="" placeholder="<?php esc_attr_e('Valoare', 'wc-loyalty-gamification'); ?>" min="1" step="1" />
            
            <div class="wc-loyalty-reward-tier-actions">
                <button type="button" class="button wc-loyalty-remove-tier"><?php esc_html_e('Elimină', 'wc-loyalty-gamification'); ?></button>
            </div>
        </div>
    </div>
    
    <!-- Template pentru niveluri de membru noi -->
    <div id="wc-loyalty-membership-tier-template" style="display:none;">
        <div class="wc-loyalty-tier">
            <div class="wc-loyalty-tier-header">
                <div class="wc-loyalty-tier-color-preview" style="background-color: #cccccc"></div>
                <input type="text" class="wc-loyalty-tier-key" value="" placeholder="<?php esc_attr_e('Cheie (ex. bronz)', 'wc-loyalty-gamification'); ?>" />
                <input type="text" class="wc-loyalty-tier-name" value="" placeholder="<?php esc_attr_e('Nume Afișat', 'wc-loyalty-gamification'); ?>" />
                <input type="number" class="wc-loyalty-tier-min-points" value="0" placeholder="<?php esc_attr_e('Puncte Minime', 'wc-loyalty-gamification'); ?>" min="0" step="1" />
                <input type="text" class="wc-loyalty-tier-color" value="#cccccc" placeholder="<?php esc_attr_e('Culoare', 'wc-loyalty-gamification'); ?>" />
                <div class="wc-loyalty-tier-actions">
                    <button type="button" class="button wc-loyalty-remove-tier"><?php esc_html_e('Elimină', 'wc-loyalty-gamification'); ?></button>
                </div>
            </div>
            <div class="wc-loyalty-tier-perks-entry">
                <textarea class="wc-loyalty-tier-perks-text" placeholder="<?php esc_attr_e('Descrierea beneficiilor nivelului', 'wc-loyalty-gamification'); ?>"></textarea>
            </div>
        </div>
    </div>
</div>