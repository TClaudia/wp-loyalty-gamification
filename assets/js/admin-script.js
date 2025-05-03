/**
 * WooCommerce Loyalty Gamification - Admin Script
 * This script avoids using WordPress data stores
 */
(function($) {
    'use strict';

    // Admin Functionality
    var WCLoyaltyAdmin = {
        // Initialize the functionality
        init: function() {
            this.initRewardTiers();
            this.initMembershipTiers();
            // Don't initialize tabs here - they're handled in admin-core.js
        },

        // Initialize reward tiers functionality
        initRewardTiers: function() {
            var self = this;
            var tiersContainer = $('#wc-loyalty-reward-tiers');
            
            // Add tier button
            $('#wc-loyalty-add-tier').on('click', function(e) {
                e.preventDefault();
                self.addRewardTier();
            });
            
            // Remove tier button (delegated event)
            tiersContainer.on('click', '.wc-loyalty-remove-tier', function(e) {
                e.preventDefault();
                $(this).closest('.wc-loyalty-reward-tier').fadeOut(300, function() {
                    $(this).remove();
                });
            });
            
            // Handle reward type change
            tiersContainer.on('change', '.wc-loyalty-reward-tier-type', function() {
                var valueField = $(this).closest('.wc-loyalty-reward-tier').find('.wc-loyalty-reward-tier-value');
                
                if ($(this).val() === 'discount') {
                    valueField.attr('placeholder', 'Discount %').show();
                } else if ($(this).val() === 'free_shipping') {
                    valueField.val('').hide();
                }
            });
            
            // Form submission - collect reward tiers
            $('form#wc-loyalty-settings-form').on('submit', function() {
                var rewardTiers = {};
                
                // Add the 2000 points premium tier
                rewardTiers[2000] = {
                    type: 'discount',
                    value: 60,
                    max_order: parseInt($('input[name="wc_loyalty_premium_discount_max"]').val(), 10) || 400
                };
                
                // Add all other tiers
                $('.wc-loyalty-reward-tier').each(function() {
                    var tier = $(this).find('.wc-loyalty-reward-tier-points').val();
                    var type = $(this).find('.wc-loyalty-reward-tier-type').val();
                    var value = $(this).find('.wc-loyalty-reward-tier-value').val();
                    
                    if (tier && type && tier != 2000) { // Skip manual entries for 2000 points tier
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
                
                // Add as hidden field
                $('<input>').attr({
                    type: 'hidden',
                    name: 'wc_loyalty_reward_tiers',
                    value: JSON.stringify(rewardTiers)
                }).appendTo(this);
            });
        },

       initMembershipTiers: function() {
            var self = this;
            var tiersContainer = $('#wc-loyalty-tiers');
            
            // Add tier button
            $('#wc-loyalty-add-tier-membership').on('click', function(e) {
                e.preventDefault();
                self.addMembershipTier();
            });
            
            // Remove tier button (delegated event)
            tiersContainer.on('click', '.wc-loyalty-remove-tier', function(e) {
                e.preventDefault();
                $(this).closest('.wc-loyalty-tier').fadeOut(300, function() {
                    $(this).remove();
                });
            });
            
            // Update color preview when color changes
            tiersContainer.on('input', '.wc-loyalty-tier-color', function() {
                var color = $(this).val();
                $(this).closest('.wc-loyalty-tier-header').find('.wc-loyalty-tier-color-preview').css('background-color', color);
            });
            
            // Form submission - collect tier data
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
                
                // Add as hidden field
                $('<input>').attr({
                    type: 'hidden',
                    name: 'wc_loyalty_tiers',
                    value: JSON.stringify(tierData)
                }).appendTo(this);
            });
        },

        // Add a new membership tier
        addMembershipTier: function() {
            var tierTemplate = `
                <div class="wc-loyalty-tier">
                    <div class="wc-loyalty-tier-header">
                        <div class="wc-loyalty-tier-color-preview" style="background-color: #cccccc"></div>
                        <input type="text" class="wc-loyalty-tier-key" value="" placeholder="Key (e.g. bronze)" />
                        <input type="text" class="wc-loyalty-tier-name" value="" placeholder="Display Name" />
                        <input type="number" class="wc-loyalty-tier-min-points" value="0" placeholder="Min Points" min="0" step="1" />
                        <input type="text" class="wc-loyalty-tier-color" value="#cccccc" placeholder="Color" />
                        <div class="wc-loyalty-tier-actions">
                            <button type="button" class="button wc-loyalty-remove-tier">Remove</button>
                        </div>
                    </div>
                    <div class="wc-loyalty-tier-perks-entry">
                        <textarea class="wc-loyalty-tier-perks-text" placeholder="Tier benefits description"></textarea>
                    </div>
                </div>
            `;
            
            $('#wc-loyalty-tiers').append(tierTemplate);
        },

        // Add a new reward tier without using wp.template
        addRewardTier: function() {
            // Use jQuery clone instead of template system
            var template = $('#wc-loyalty-reward-tier-template').children().first().clone();
            $('#wc-loyalty-reward-tiers').append(template);
        }
    };

    // Initialize when document is ready
    $(function() {
        // Initialize with a small delay to avoid conflicts
        setTimeout(function() {
            WCLoyaltyAdmin.init();
        }, 100);
    });

})(jQuery);