/**
 * WooCommerce Loyalty Gamification - Admin Core Script îmbunătățit
 * Păstrează aceeași funcționalitate, cu design îmbunătățit
 */
(function($) {
    'use strict';
    
    // Inițializarea taburilor și interactivității
    var LoyaltyAdmin = {
        // Inițializează componentele
        init: function() {
            this.initTabs();
            this.initRewardTiers();
            this.initMembershipTiers();
            this.initMilestoneRewards();
            this.initColorPickers();
            this.setupTooltips();
        },
        
        // Inițializare tab-uri
        initTabs: function() {
            var tabs = $('.wc-loyalty-settings-tabs');
            var tabContents = $('.wc-loyalty-tab-content');
            
            tabs.on('click', 'a.nav-tab', function(e) {
                e.preventDefault();
                
                var target = $(this).attr('href');
                
                // Actualizează tab-urile
                tabs.find('a.nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                
                // Actualizează conținutul
                tabContents.hide();
                $(target).show();
                
                // Salvează tab-ul activ în localStorage
                if (typeof(Storage) !== "undefined") {
                    try {
                        localStorage.setItem('wc_loyalty_active_tab', target);
                    } catch (e) {
                        console.warn('Nu s-a putut salva starea tab-ului în localStorage');
                    }
                }
            });
            
            // Încarcă tab-ul activ din localStorage
            if (typeof(Storage) !== "undefined") {
                try {
                    var activeTab = localStorage.getItem('wc_loyalty_active_tab');
                    
                    if (activeTab && $(activeTab).length) {
                        tabs.find('a[href="' + activeTab + '"]').trigger('click');
                    } else {
                        // Setează implicit primul tab
                        tabs.find('a.nav-tab').first().trigger('click');
                    }
                } catch (e) {
                    // Setează implicit primul tab dacă localStorage eșuează
                    tabs.find('a.nav-tab').first().trigger('click');
                }
            } else {
                // Setează implicit primul tab dacă localStorage nu este suportat
                tabs.find('a.nav-tab').first().trigger('click');
            }
        },
        
        // Inițializare niveluri de recompensă
        initRewardTiers: function() {
            var self = this;
            var tiersContainer = $('#wc-loyalty-reward-tiers');
            
            // Adaugă nivel button
            $('#wc-loyalty-add-tier').on('click', function(e) {
                e.preventDefault();
                self.addRewardTier();
            });
            
            // Șterge nivel button (eveniment delegat)
            tiersContainer.on('click', '.wc-loyalty-remove-tier', function(e) {
                e.preventDefault();
                $(this).closest('.wc-loyalty-reward-tier').fadeOut(300, function() {
                    $(this).remove();
                });
            });
            
            // Gestionează schimbarea tipului de recompensă
            tiersContainer.on('change', '.wc-loyalty-reward-tier-type', function() {
                var valueField = $(this).closest('.wc-loyalty-reward-tier').find('.wc-loyalty-reward-tier-value');
                
                if ($(this).val() === 'discount') {
                    valueField.attr('placeholder', 'Valoare reducere %').show();
                } else if ($(this).val() === 'free_shipping') {
                    valueField.val('').hide();
                }
            });
            
            // Transmiterea recompenselor la submiterea formularului
            $('form#wc-loyalty-settings-form').on('submit', function() {
                var rewardTiers = {};
                
                // Adaugă tier-ul premium de 2000 de puncte
                rewardTiers[2000] = {
                    type: 'discount',
                    value: 60,
                    max_order: parseInt($('input[name="wc_loyalty_premium_discount_max"]').val(), 10) || 400
                };
                
                // Adaugă toate celelalte niveluri
                $('.wc-loyalty-reward-tier').each(function() {
                    var tier = $(this).find('.wc-loyalty-reward-tier-points').val();
                    var type = $(this).find('.wc-loyalty-reward-tier-type').val();
                    var value = $(this).find('.wc-loyalty-reward-tier-value').val();
                    
                    if (tier && type && tier != 2000) { // Ignoră intrările manuale pentru tier-ul de 2000 de puncte
                        var tierData = {
                            type: type
                        };
                        
                        if (type === 'discount' && value) {
                            tierData.value = parseInt(value, 10);
                        } else {
                            tierData.value = true;
                        }
                        
                        rewardTiers[tier] = tierData;
                    }
                });
                
                // Adaugă ca un câmp ascuns
                $('<input>').attr({
                    type: 'hidden',
                    name: 'wc_loyalty_reward_tiers',
                    value: JSON.stringify(rewardTiers)
                }).appendTo(this);
            });
        },
        
        // Inițializare niveluri de membru
        initMembershipTiers: function() {
            var self = this;
            var tiersContainer = $('#wc-loyalty-tiers');
            
            // Adaugă nivel button
            $('#wc-loyalty-add-tier-membership').on('click', function(e) {
                e.preventDefault();
                self.addMembershipTier();
            });
            
            // Șterge nivel button (eveniment delegat)
            tiersContainer.on('click', '.wc-loyalty-remove-tier', function(e) {
                e.preventDefault();
                $(this).closest('.wc-loyalty-tier').fadeOut(300, function() {
                    $(this).remove();
                });
            });
            
            // Actualizează previzualizarea culorii când se schimbă culoarea
            tiersContainer.on('input', '.wc-loyalty-tier-color', function() {
                var color = $(this).val();
                $(this).closest('.wc-loyalty-tier-header').find('.wc-loyalty-tier-color-preview').css('background-color', color);
            });
            
            // Transmiterea nivelurilor de membru la submiterea formularului
            $('form#wc-loyalty-settings-form').on('submit', function() {
                var tierData = {};
                
                $('.wc-loyalty-tier').each(function() {
                    var tierKey = $(this).find('.wc-loyalty-tier-key').val();
                    var tierName = $(this).find('.wc-loyalty-tier-name').val();
                    var minPoints = parseInt($(this).find('.wc-loyalty-tier-min-points').val(), 10);
                    var tierColor = $(this).find('.wc-loyalty-tier-color').val();
                    var tierPerks = $(this).find('.wc-loyalty-tier-perks-text').val();
                    
                    if (tierKey && tierName && !isNaN(minPoints)) {
                        tierData[tierKey] = {
                            name: tierName,
                            min_points: minPoints,
                            color: tierColor,
                            perks: tierPerks
                        };
                    }
                });
                
                // Adaugă ca un câmp ascuns
                $('<input>').attr({
                    type: 'hidden',
                    name: 'wc_loyalty_tiers',
                    value: JSON.stringify(tierData)
                }).appendTo(this);
            });
        },
        
        // Adaugă un nou nivel de recompensă
        addRewardTier: function() {
            var template = `
                <div class="wc-loyalty-reward-tier">
                    <input type="number" class="wc-loyalty-reward-tier-points" value="" placeholder="Puncte" min="1" step="1" />
                    <select class="wc-loyalty-reward-tier-type">
                        <option value="discount">Reducere</option>
                        <option value="free_shipping">Transport Gratuit</option>
                    </select>
                    <input type="number" class="wc-loyalty-reward-tier-value" value="" placeholder="Valoare" min="1" step="1" />
                    <div class="wc-loyalty-reward-tier-actions">
                        <button type="button" class="button wc-loyalty-remove-tier">Elimină</button>
                    </div>
                </div>
            `;
            
            $('#wc-loyalty-reward-tiers').append(template);
        },
        
        // Adaugă un nou nivel de membru
        addMembershipTier: function() {
            var tierTemplate = `
                <div class="wc-loyalty-tier">
                    <div class="wc-loyalty-tier-header">
                        <div class="wc-loyalty-tier-color-preview" style="background-color: #cccccc"></div>
                        <input type="text" class="wc-loyalty-tier-key" value="" placeholder="Cheie (ex. bronz)" />
                        <input type="text" class="wc-loyalty-tier-name" value="" placeholder="Nume Afișat" />
                        <input type="number" class="wc-loyalty-tier-min-points" value="0" placeholder="Puncte Minime" min="0" step="1" />
                        <input type="text" class="wc-loyalty-tier-color" value="#cccccc" placeholder="Culoare" />
                        <div class="wc-loyalty-tier-actions">
                            <button type="button" class="button wc-loyalty-remove-tier">Elimină</button>
                        </div>
                    </div>
                    <div class="wc-loyalty-tier-perks-entry">
                        <textarea class="wc-loyalty-tier-perks-text" placeholder="Descrierea beneficiilor nivelului"></textarea>
                    </div>
                </div>
            `;
            
            $('#wc-loyalty-tiers').append(tierTemplate);
        },
        
        // Inițializarea recompenselor pentru obiective
        initMilestoneRewards: function() {
            var self = this;
            var milestonesContainer = $('#wc-loyalty-milestone-rewards');
            
            // Adaugă obiectiv button
            $('#wc-loyalty-add-milestone').on('click', function(e) {
                e.preventDefault();
                self.addMilestoneReward();
            });
            
            // Șterge obiectiv button (eveniment delegat)
            milestonesContainer.on('click', '.wc-loyalty-remove-milestone', function(e) {
                e.preventDefault();
                $(this).closest('.wc-loyalty-milestone-reward').fadeOut(300, function() {
                    $(this).remove();
                    self.updateMilestonesJson();
                });
            });
            
            // Actualizează JSON când se schimbă valorile obiectivelor
            milestonesContainer.on('change', '.wc-loyalty-milestone-days, .wc-loyalty-milestone-bonus', function() {
                self.updateMilestonesJson();
            });
            
            // Gestionarea formularului de submit
            $('form#wc-loyalty-settings-form').on('submit', function() {
                self.updateMilestonesJson();
            });
        },
        
        // Adaugă o nouă recompensă pentru obiectiv
        addMilestoneReward: function() {
            var template = $('<div class="wc-loyalty-milestone-reward"></div>');
            
            template.append('<input type="number" class="wc-loyalty-milestone-days" value="" placeholder="Zile" min="1" step="1" />');
            template.append('<input type="number" class="wc-loyalty-milestone-bonus" value="" placeholder="Puncte Bonus" min="1" step="1" />');
            template.append('<button type="button" class="button wc-loyalty-remove-milestone">Elimină</button>');
            
            $('#wc-loyalty-milestone-rewards').append(template);
            this.updateMilestonesJson();
        },
        
        // Actualizează câmpul JSON hidden cu datele milestone
        updateMilestonesJson: function() {
            var milestones = {};
            
            $('.wc-loyalty-milestone-reward').each(function() {
                var days = $(this).find('.wc-loyalty-milestone-days').val();
                var bonus = $(this).find('.wc-loyalty-milestone-bonus').val();
                
                if (days && bonus) {
                    milestones[days] = parseInt(bonus, 10);
                }
            });
            
            $('#wc_loyalty_milestone_rewards_json').val(JSON.stringify(milestones));
        },
        
        // Inițializare color pickers
        initColorPickers: function() {
            // Verifică dacă există wp.wpColorPicker
            if (typeof wp !== 'undefined' && wp.wpColorPicker) {
                $('.wc-loyalty-tier-color').wpColorPicker({
                    change: function(event, ui) {
                        // Actualizează previzualizarea culorii când se schimbă valoarea
                        var color = ui.color.toString();
                        $(this).closest('.wc-loyalty-tier-header').find('.wc-loyalty-tier-color-preview').css('background-color', color);
                    }
                });
            }
        },
        
        // Inițializare tooltipuri
        setupTooltips: function() {
            // Adaugă tooltipuri pentru elementele cu clasa .wc-loyalty-tooltip
            $('.wc-loyalty-tooltip').hover(
                function() {
                    $(this).find('.tooltip-content').addClass('active');
                },
                function() {
                    $(this).find('.tooltip-content').removeClass('active');
                }
            );
        }
    };
    
    // Inițializează când documentul este gata
    $(function() {
        // Inițializează cu o mică întârziere pentru a evita conflictele
        setTimeout(function() {
            LoyaltyAdmin.init();
        }, 100);
    });
    
})(jQuery);