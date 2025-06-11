<?php
/**
 * WooCommerce Loyalty Gamification - Romanian Translations
 * Fișier de traducere complet pentru toate elementele din interfața utilizatorului
 */

// Asigură-te că este apelat doar de WordPress
if (!defined('ABSPATH')) {
    exit;
}

// Funcție pentru debugging
function wc_loyalty_debug($message) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[WC Loyalty Translations] ' . $message);
    }
}

// Funcție pentru încărcarea traducerilor - cu prioritate mare
function wc_loyalty_load_romanian_translations() {
    // Verifică dacă limba site-ului este română (acceptă mai multe formate)
    $locale = get_locale();
    wc_loyalty_debug('Checking locale: ' . $locale);
    
    if ($locale == 'ro_RO' || $locale == 'ro' || strpos($locale, 'ro') === 0) {
        wc_loyalty_debug('Enabling Romanian translations');
        
        // Înregistrează toate tipurile de filtre pentru a prinde toate textele
        add_filter('gettext', 'wc_loyalty_translate_strings', 10, 3);
        add_filter('gettext_with_context', 'wc_loyalty_translate_strings_with_context', 10, 4);
        add_filter('ngettext', 'wc_loyalty_translate_plural_strings', 10, 5);
        
        // În plus, înregistrăm filtrul pentru output buffer ca ultimă soluție
        if (!is_admin()) {
            ob_start('wc_loyalty_translate_output_buffer');
        }
    }
}

// Folosim o prioritate mare pentru a ne asigura că se execută după ce toate textele sunt înregistrate
add_action('init', 'wc_loyalty_load_romanian_translations', 999);

// Traducem și output-ul final
function wc_loyalty_translate_output_buffer($buffer) {
    // Lista de texte de înlocuit direct în HTML (pentru cazurile dificile)
    $replacements = array(
        '>Your Discount Coupons<' => '>Cupoanele tale de reducere<',
        '>Points History<' => '>Istoricul punctelor<',
        '>Claimed Rewards<' => '>Recompense revendicate<',
        '>Data<' => '>Data<',
        '>Puncte<' => '>Puncte<',
        '>Descriere<' => '>Descriere<',
        '>Date<' => '>Data<',
        '>Points<' => '>Puncte<',
        '>Description<' => '>Descriere<',
        '>Reward<' => '>Recompensă<',
        '>Free Shipping<' => '>Transport Gratuit<',
        '>20% OFF<' => '>20% REDUCERE<',
        '>40% OFF<' => '>40% REDUCERE<',
        '>Copy<' => '>Copiază<',
        'Valid until' => 'Valabil până la',
        'Istoricul punctelor' => 'Istoricul punctelor',
        'Daily check-in - Day 2' => 'Verificare zilnică - Ziua 2',
        'Daily check-in - Day 1 streak - 5 points' => 'Verificare zilnică - Ziua 1 consecutivă - 5 puncte',
        'Daily check-in - Day 1' => 'Verificare zilnică - Ziua 1',
        'Order #25 - 840 points for €840.00 spent' => 'Comanda #25 - 840 puncte pentru €840.00 cheltuiți',
    );
    
    return str_replace(array_keys($replacements), array_values($replacements), $buffer);
}

// Traducere pentru stringuri cu context
function wc_loyalty_translate_strings_with_context($translated_text, $text, $context, $domain) {
    // Folosim aceeași funcție ca pentru traducerile normale
    return wc_loyalty_translate_strings($translated_text, $text, $domain);
}

// Traducere pentru stringuri plurale
function wc_loyalty_translate_plural_strings($translated_text, $single, $plural, $number, $domain) {
    // Traducem forma singulară sau plurală în funcție de număr
    $text = $number == 1 ? $single : $plural;
    return wc_loyalty_translate_strings($translated_text, $text, $domain);
}

// Funcție pentru traducerea directă a unui text (poate fi folosită în template-uri)
function wc_loyalty_translate_direct($text) {
    return wc_loyalty_translate_strings($text, $text, 'wc-loyalty-gamification');
}

// Funcția principală pentru traducerea textelor
function wc_loyalty_translate_strings($translated_text, $text, $domain) {
    // Pentru debugging - înregistrează textele și domeniile care conțin cuvinte cheie
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $debug_terms = array('coupon', 'point', 'discount', 'reward', 'loyalty', 'shipping', 'check-in', 'streak');
        $should_log = false;
        
        foreach ($debug_terms as $term) {
            if (stripos($text, $term) !== false) {
                $should_log = true;
                break;
            }
        }
        
        if ($should_log) {
            wc_loyalty_debug("Checking text: '$text', Domain: '$domain', Current translation: '$translated_text'");
        }
    }
    
    // Acceptă mai multe domenii posibile pentru flexibilitate
    // Trebuie să acceptăm și domeniul gol sau fals pentru unele cazuri speciale
    $accepted_domains = array('wc-loyalty-gamification', 'wc-loyalty', 'woocommerce-loyalty', '', false);
    $domain_accepted = false;
    
    // Verifică exact sau parțial numele domeniului
    foreach ($accepted_domains as $accepted) {
        if ($domain === $accepted || ($accepted !== '' && $accepted !== false && strpos($domain, $accepted) !== false)) {
            $domain_accepted = true;
            break;
        }
    }
    
    // Încercăm traducerea formularelor HTML care pot să nu aibă un domeniu
    if (!$domain_accepted) {
        // Cuvinte cheie din formular care pot indica că este vorba de plugin-ul nostru
        $form_keywords = array('loyalty', 'points', 'coupon', 'reward', 'check-in', 'streak');
        foreach ($form_keywords as $keyword) {
            if (stripos($text, $keyword) !== false) {
                $domain_accepted = true;
                break;
            }
        }
    }
    
    // Optimizare - pentru performanță verificăm întâi dacă domeniul este acceptat
    // și dacă nu este, returnăm direct traducerea curentă
    if (!$domain_accepted) {
        // Tentativă de traducere universală pentru orice text care există în dicționarul nostru
        // chiar dacă domeniul nu se potrivește
        $translations = wc_loyalty_get_translations();
        if (isset($translations[$text])) {
            wc_loyalty_debug("Found universal translation for '$text' in any domain");
            return $translations[$text];
        }
        return $translated_text;
    }
    
    // Obținem toate traducerile
    $translations = wc_loyalty_get_translations();
    
    // Verifică dacă textul există exact în lista de traduceri
    if (isset($translations[$text])) {
        wc_loyalty_debug("Translating: '$text' -> '{$translations[$text]}'");
        return $translations[$text];
    }
    
    // Verifică dacă textul conține formatări (pentru %s, %d etc.)
    foreach ($translations as $pattern => $translation) {
        // Verifică dacă modelul conține formatori (% urmat de o literă)
        if (strpos($pattern, '%') !== false && preg_match('/%[sdf]/', $pattern)) {
            // Creează o expresie regulată din model
            $regex_pattern = str_replace(
                array('%s', '%d', '%f', '%%'), 
                array('(.*?)', '(\d+)', '([0-9.]+)', '%'), 
                preg_quote($pattern, '/')
            );
            
            // Verifică dacă textul se potrivește cu modelul
            if (preg_match('/^' . $regex_pattern . '$/s', $text, $matches)) {
                array_shift($matches); // Elimină prima potrivire (întregul text)
                
                // Înlocuiește formatorii cu valorile potrivite
                $result = $translation;
                foreach ($matches as $i => $match) {
                    $pos = strpos($result, '%');
                    if ($pos !== false) {
                        $format_char = substr($result, $pos + 1, 1);
                        $result = substr_replace($result, $match, $pos, 2);
                    }
                }
                
                wc_loyalty_debug("Format translation: '$text' -> '$result'");
                return $result;
            }
        }
    }
    
    // Verificăm dacă textul este unul dintre textele statice specifice din exemplul tău
    $specific_translations = array(
        'Istoricul punctelor' => 'Istoricul punctelor',
        'Data' => 'Data',
        'Puncte' => 'Puncte',
        'Descriere' => 'Descriere',
        'Daily check-in - Day 2' => 'Verificare zilnică - Ziua 2',
        'Daily check-in - Day 1 streak - 5 points' => 'Verificare zilnică - Ziua 1 consecutivă - 5 puncte',
        'Daily check-in - Day 1' => 'Verificare zilnică - Ziua 1',
        'Order #25 - 840 points for €840.00 spent' => 'Comanda #25 - 840 puncte pentru €840.00 cheltuiți',
        'points' => 'puncte',
        'for' => 'pentru',
        'spent' => 'cheltuiți',
        'streak' => 'consecutivă',
    );
    
    if (isset($specific_translations[$text])) {
        wc_loyalty_debug("Specific translation: '$text' -> '{$specific_translations[$text]}'");
        return $specific_translations[$text];
    }
    
    // Verifică texte în stil tabelar care pot să nu fie prinse în dicționarul normal
    if (preg_match('/^mai \d+, \d{4} \d+:\d+ [ap]m$/', $text)) {
        // Transformă formatul datei "mai 10, 2025 2:48 pm" în "10 mai, 2025 2:48 pm"
        $result = preg_replace('/^mai (\d+), (\d{4}) (\d+:\d+ [ap]m)$/', '$1 mai, $2 $3', $text);
        wc_loyalty_debug("Date format translation: '$text' -> '$result'");
        return $result;
    }
    
    // Returnează textul original dacă nu am găsit o traducere
    return $translated_text;
}

/**
 * Returnează toate traducerile pentru plugin
 * Separat în funcție pentru a putea fi extins sau modificat ușor
 */
function wc_loyalty_get_translations() {
    // Arrayul cu toate traducerile
    return array(
        // Titluri și elemente principale
        'Istoricul punctelor' => 'Istoricul punctelor',
        'Your Discount Coupons' => 'Cupoanele tale de reducere',
        'Points History' => 'Istoricul punctelor',
        'Claimed Rewards' => 'Recompense revendicate',
        'Your Loyalty Points' => 'Punctele Tale de Fidelitate',
        'See Your Points' => 'Vezi Punctele Tale',
        'Loyalty Program' => 'Program de Fidelitate',
        
        // Headers de tabele
        'Data' => 'Data',
        'Puncte' => 'Puncte',
        'Descriere' => 'Descriere',
        'Date' => 'Data',
        'Points' => 'Puncte',
        'Description' => 'Descriere',
        
        // Cupoane și procente
        '20% OFF' => '20% REDUCERE',
        '40% OFF' => '40% REDUCERE',
        '%d%% OFF' => '%d%% REDUCERE',
        'LOYALTYF6681EEF' => 'LOYALTYF6681EEF',
        'LOYALTY914DCCC6' => 'LOYALTY914DCCC6',
        'Valid until june 5, 2025' => 'Valabil până la 5 iunie, 2025',
        'Valid until %s' => 'Valabil până la %s',
        'Copy' => 'Copiază',
        'Premium' => 'Premium',
        'Premium Reward' => 'Recompensă Premium',
        'Claimed' => 'Revendicat',
        'View Coupon' => 'Vezi Cupon',
        
        // Recompense
        'Rewards' => 'Recompense',
        'Free Shipping' => 'Transport Gratuit',
        '%d%% Discount' => '%d%% Reducere',
        '%d%% Discount (up to 400 lei)' => '%d%% Reducere (până la 400 lei)',
        '%d%% Discount (max %s lei)' => '%d%% Reducere (maxim %s lei)',
        'Valid for orders up to %s lei' => 'Valid pentru comenzi de până la %s lei',
        'Valid for orders up to 400 lei' => 'Valid pentru comenzi de până la 400 lei',
        
        // Coloane tabele și date
        'Reward' => 'Recompensă',
        'mai 10, 2025 2:48 pm' => '10 mai, 2025 2:48 pm',
        'mai 7, 2025 12:39 pm' => '7 mai, 2025 12:39 pm',
        'mai 7, 2025 12:38 pm' => '7 mai, 2025 12:38 pm',
        'mai 6, 2025 1:36 pm' => '6 mai, 2025 1:36 pm',
        'mai 6, 2025 1:08 pm' => '6 mai, 2025 1:08 pm',
        'mai 6, 2025 12:57 pm' => '6 mai, 2025 12:57 pm',
        'mai 6, 2025 11:53 am' => '6 mai, 2025 11:53 am',
        
        // Descrieri puncte și comenzi
        'Comanda #35 - 145 puncte pentru €145.00 cheltuiți' => 'Comanda #35 - 145 puncte pentru €145.00 cheltuiți',
        'Verificare zilnică - Ziua 2 consecutivă - 5 puncte' => 'Verificare zilnică - Ziua 2 consecutivă - 5 puncte',
        'Daily check-in - Day 2' => 'Verificare zilnică - Ziua 2',
        'Comanda #28 - 327 puncte pentru €327.00 cheltuiți' => 'Comanda #28 - 327 puncte pentru €327.00 cheltuiți',
        'Order #25 - 840 points for €840.00 spent' => 'Comanda #25 - 840 puncte pentru €840.00 cheltuiți',
        'Daily check-in - Day 1 streak - 5 points' => 'Verificare zilnică - Ziua 1 consecutivă - 5 puncte',
        'Daily check-in - Day 1' => 'Verificare zilnică - Ziua 1',
        'Order #%s - %d points for €%.2f spent' => 'Comanda #%s - %d puncte pentru €%.2f cheltuiți',
        'Review for product #%s - %d points' => 'Recenzie pentru produsul #%s - %d puncte',
        
        // Mesaje pentru utilizator
        'You need %s more points to reach your next reward!' => 'Mai ai nevoie de %s puncte pentru următoarea recompensă!',
        'Congratulations! You\'ve reached 2000 points! Check your coupons for a 60% discount code.' => 'Felicitări! Ai acumulat 2000 de puncte! Verifică-ți cupoanele pentru un cod de reducere de 60%.',
        'You need %s more points to reach 2000 and earn a premium 60%% discount!' => 'Mai ai nevoie de %s puncte pentru a ajunge la 2000 și a primi o reducere premium de 60%%!',
        'You\'ve earned a 60% discount coupon for orders up to 400 lei! Use the coupon code at checkout.' => 'Ai câștigat un cupon de reducere de 60% pentru comenzi de până la 400 lei! Utilizează codul de cupon la finalizarea comenzii.',
        'You\'ve earned free shipping on your next order!' => 'Ai câștigat transport gratuit pentru următoarea comandă!',
        'You\'ve earned a free product! See below to claim it.' => 'Ai câștigat un produs gratuit! Vezi mai jos pentru a-l revendica.',
        'No rewards available yet.' => 'Nu există recompense disponibile încă.',
        
        // Istoric și recompense
        'No points history yet.' => 'Nu există încă istoric de puncte.',
        'No rewards claimed yet.' => 'Nu există încă recompense revendicate.',
        'View Points History' => 'Vezi Istoricul Punctelor',
        'Current Points' => 'Puncte Curente',
        'Transport Gratuit' => 'Transport Gratuit',
        '40% Reducere' => '40% Reducere',
        '20% Reducere' => '20% Reducere',
        '40% Discount' => '40% Reducere',
        '20% Discount' => '20% Reducere',
        
        // Checkout și coș
        'Your Loyalty Coupons' => 'Cupoanele Tale de Fidelitate',
        'Applied' => 'Aplicat',
        'Apply' => 'Aplică',
        'Applied from Loyalty Program' => 'Aplicat din Programul de Fidelitate',
        'Free shipping applied from your loyalty program rewards!' => 'Transport gratuit aplicat din recompensele programului de fidelitate!',
        'You\'ll earn %d loyalty points when this order is completed. <a href="%s">View your points</a>' => 'Vei câștiga %d puncte de fidelitate când această comandă va fi finalizată. <a href="%s">Vezi punctele tale</a>',
        
        // Mesaje erori și confirmare
        'Invalid coupon code.' => 'Cod de cupon invalid.',
        'Coupon applied successfully!' => 'Cupon aplicat cu succes!',
        'Failed to apply coupon. Please try again.' => 'Aplicarea cuponului a eșuat. Te rugăm să încerci din nou.',
        'An error occurred. Please try again.' => 'A apărut o eroare. Te rugăm să încerci din nou.',
        'You must be logged in to apply coupons.' => 'Trebuie să fii autentificat pentru a aplica cupoane.',
        'Security check failed.' => 'Verificarea de securitate a eșuat.',
        'This coupon is not valid or has already been used.' => 'Acest cupon nu este valid sau a fost deja utilizat.',
        
        // Daily check-in
        'Daily Check-in' => 'Verificare Zilnică',
        'Check in daily to earn points and build your streak!' => 'Verifică zilnic pentru a câștiga puncte și a-ți construi seria!',
        'Day %d Streak' => 'Ziua %d Consecutivă',
        'Day' => 'Ziua',
        'Streak' => 'Serie',
        'Claim %d Points' => 'Revendică %d Puncte',
        'You have already claimed your daily points today!' => 'Ai revendicat deja punctele tale zilnice astăzi!',
        'Check-in successful! You earned %d points.' => 'Verificare reușită! Ai câștigat %d puncte.',
        'You earned %d points for checking in today!' => 'Ai câștigat %d puncte pentru verificarea de azi!',
        'Bonus! You reached a %d day streak and earned %d extra points!' => 'Bonus! Ai atins o serie de %d zile și ai câștigat %d puncte suplimentare!',
        'Claiming...' => 'Se revendică...',
        'Claimed!' => 'Revendicat!',
        'Claimed Today' => 'Revendicat Astăzi',
        'Daily check-in - Day %d' => 'Verificare zilnică - Ziua %d',
        'Daily check-in - Day %d streak - %d points' => 'Verificare zilnică - Ziua %d consecutivă - %d puncte',
        
        // Valori și cuvinte individuale
        'points' => 'puncte',
        'Points' => 'Puncte',
        'Discount' => 'Reducere',
        'discount' => 'reducere',
        'Coupon' => 'Cupon',
        'coupon' => 'cupon',
        'shipping' => 'transport',
        'Shipping' => 'Transport',
        'reward' => 'recompensă',
        'Reward' => 'Recompensă',
        'rewards' => 'recompense',
        'Rewards' => 'Recompense',
        'history' => 'istoric',
        'History' => 'Istoric',
        'claimed' => 'revendicat',
        'Claimed' => 'Revendicat',
        'valid' => 'valabil',
        'Valid' => 'Valabil',
        'until' => 'până la',
        'copy' => 'copiază',
        '+%d' => '+%d',
        '+145' => '+145',
        '+5' => '+5',
        '+327' => '+327',
        '+840' => '+840',
        '1500' => '1500',
        '1000' => '1000',
        '500' => '500',
        '145' => '145',
        '327' => '327',
        '840' => '840',
        '5' => '5',
        
        // Elemente direct din exemplul tău
        'Verificare zilnică - Ziua 2' => 'Verificare zilnică - Ziua 2',
        'Verificare zilnică - Ziua 1 consecutivă - 5 puncte' => 'Verificare zilnică - Ziua 1 consecutivă - 5 puncte',
        'Verificare zilnică - Ziua 1' => 'Verificare zilnică - Ziua 1',
        'spent' => 'cheltuiți',
        'for' => 'pentru',
        'streak' => 'consecutivă',
    );
}

// Această funcție va forța reîncărcarea traducerilor - poate fi apelată manual dacă este necesar
function wc_loyalty_force_translations_refresh() {
    // Elimină orice filtre existente
    remove_filter('gettext', 'wc_loyalty_translate_strings');
    remove_filter('gettext_with_context', 'wc_loyalty_translate_strings_with_context');
    remove_filter('ngettext', 'wc_loyalty_translate_plural_strings');
    
    // Reîncarcă traducerile
    wc_loyalty_load_romanian_translations();
    
    wc_loyalty_debug('Traducerile au fost reîncărcate forțat');
    
    // Indică faptul că traducerile au fost reîncărcate
    return true;
}

// Adaugă opțiune pentru a forța reîncărcarea traducerilor
if (defined('WP_DEBUG') && WP_DEBUG) {
    add_action('init', 'wc_loyalty_force_translations_refresh', 9999);
}