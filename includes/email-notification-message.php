<?php
/**
 * WooCommerce Loyalty Gamification - Notificări pentru pagina My Account
 * 
 * Adaugă notificări pentru mesajele legate de revendicarea punctelor zilnice
 * prin email.
 */

if (!defined('WPINC')) {
    die;
}

// Adaugă hook pentru afișarea notificărilor pe pagina My Account
add_action('woocommerce_account_content', 'wc_loyalty_display_account_notifications', 5);

/**
 * Afișează notificări pe pagina My Account
 */
function wc_loyalty_display_account_notifications() {
    // Verifică dacă avem un mesaj de afișat
    if (!isset($_GET['loyalty_message'])) {
        return;
    }
    
    $message = sanitize_text_field($_GET['loyalty_message']);
    $points = isset($_GET['points']) ? intval($_GET['points']) : 0;
    
    // Afișează mesajul corespunzător
    switch ($message) {
        case 'claimed_success':
            wc_add_notice(
                sprintf(
                    __('Felicitări! Ai revendicat cu succes %d puncte de fidelitate prin link-ul din email.', 'wc-loyalty-gamification'),
                    $points
                ),
                'success'
            );
            break;
            
        case 'already_claimed':
            wc_add_notice(
                __('Ai revendicat deja punctele tale zilnice astăzi. Revino mâine pentru mai multe puncte!', 'wc-loyalty-gamification'),
                'notice'
            );
            break;
            
        case 'invalid_token':
            wc_add_notice(
                __('Link-ul de revendicare a expirat sau este invalid. Te rugăm să folosești un link mai recent sau să revendici punctele direct de pe site.', 'wc-loyalty-gamification'),
                'error'
            );
            break;
    }
}
