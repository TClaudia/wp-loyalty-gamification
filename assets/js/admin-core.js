/**
 * WooCommerce Loyalty Gamification - Admin Core Script
 * This script avoids using WordPress data stores
 */
(function($) {
    'use strict';
    
    window.wcLoyaltyCore = {
        // Initialize tabs
        initTabs: function() {
            var tabs = $('.wc-loyalty-settings-tabs');
            var tabContents = $('.wc-loyalty-tab-content');
            
            tabs.on('click', 'a.nav-tab', function(e) {
                e.preventDefault();
                
                var target = $(this).attr('href');
                
                // Update tabs
                tabs.find('a.nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');
                
                // Update content
                tabContents.hide();
                $(target).show();
                
                // Store active tab in localStorage
                if (typeof(Storage) !== "undefined") {
                    try {
                        localStorage.setItem('wc_loyalty_active_tab', target);
                    } catch (e) {
                        console.warn('Could not save tab state to localStorage');
                    }
                }
            });
            
            // Load active tab from localStorage
            if (typeof(Storage) !== "undefined") {
                try {
                    var activeTab = localStorage.getItem('wc_loyalty_active_tab');
                    
                    if (activeTab && $(activeTab).length) {
                        tabs.find('a[href="' + activeTab + '"]').trigger('click');
                    } else {
                        // Default to first tab
                        tabs.find('a.nav-tab').first().trigger('click');
                    }
                } catch (e) {
                    // Default to first tab if localStorage fails
                    tabs.find('a.nav-tab').first().trigger('click');
                }
            } else {
                // Default to first tab if localStorage not supported
                tabs.find('a.nav-tab').first().trigger('click');
            }
        }
    };
    
    $(document).ready(function() {
        window.wcLoyaltyCore.initTabs();
    });
    
})(jQuery);