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
            this.initProductSearch();
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
                } else if ($(this).val() === 'free_shipping' || $(this).val() === 'free_product') {
                    valueField.val('').hide();
                }
            });
            
            // Form submission - collect reward tiers
            $('form#wc-loyalty-settings-form').on('submit', function() {
                var rewardTiers = {};
                
                $('.wc-loyalty-reward-tier').each(function() {
                    var tier = $(this).find('.wc-loyalty-reward-tier-points').val();
                    var type = $(this).find('.wc-loyalty-reward-tier-type').val();
                    var value = $(this).find('.wc-loyalty-reward-tier-value').val();
                    
                    if (tier && type) {
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

        // Add a new reward tier without using wp.template
      addRewardTier: function() {
    // Use jQuery clone instead of template system
    var template = $('#wc-loyalty-reward-tier-template').children().first().clone();
    $('#wc-loyalty-reward-tiers').append(template);
},

        // Initialize product search
        initProductSearch: function() {
            $('.wc-product-search').each(function() {
                var $select = $(this);
                
                if (!$select.data('select2') && typeof $select.select2 === 'function') {
                    try {
                        $select.select2({
                            ajax: {
                                url: ajaxurl,
                                dataType: 'json',
                                delay: 250,
                                data: function(params) {
                                    return {
                                        term: params.term,
                                        action: 'woocommerce_json_search_products',
                                        security: woocommerce_admin_meta_boxes.search_products_nonce
                                    };
                                },
                                processResults: function(data) {
                                    var terms = [];
                                    if (data) {
                                        $.each(data, function(id, text) {
                                            terms.push({id: id, text: text});
                                        });
                                    }
                                    return {results: terms};
                                },
                                cache: true
                            },
                            minimumInputLength: 3
                        });
                    } catch (e) {
                        console.error('Error initializing select2:', e);
                    }
                }
            });
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