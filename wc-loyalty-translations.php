<?php
/**
 * WooCommerce Loyalty Gamification - Romanian Translations
 */

// Asigură-te că este apelat doar de WordPress
if (!defined('ABSPATH')) {
    exit;
}

// Funcție pentru încărcarea traducerilor
function wc_loyalty_load_romanian_translations() {
    // Verifică dacă limba site-ului este română
    if (get_locale() == 'ro_RO') {
        add_filter('gettext', 'wc_loyalty_translate_strings', 10, 3);
    }
}
add_action('init', 'wc_loyalty_load_romanian_translations');

// Funcție pentru traducerea textelor
function wc_loyalty_translate_strings($translated_text, $text, $domain) {
    // Aplică traducerile doar pentru domeniul plugin-ului nostru
    if ($domain != 'wc-loyalty-gamification') {
        return $translated_text;
    }

    // Array cu traducerile
    $translations = array(
        // Texte generale
        'Loyalty Program' => 'Program de Fidelitate',
        'Your Loyalty Points' => 'Punctele Tale de Fidelitate',
        'See Your Points' => 'Vezi Punctele Tale',
        'points' => 'puncte',
        'Current Points' => 'Puncte Curente',
        'You need %s more points to reach your next reward!' => 'Mai ai nevoie de %s puncte pentru următoarea recompensă!',
        'Congratulations! You\'ve reached 2000 points! Check your coupons for a 60% discount code.' => 'Felicitări! Ai acumulat 2000 de puncte! Verifică-ți cupoanele pentru un cod de reducere de 60%.',
        'You need %s more points to reach 2000 and earn a premium 60%% discount!' => 'Mai ai nevoie de %s puncte pentru a ajunge la 2000 și a primi o reducere premium de 60%%!',
        'Cycle Level: %d' => 'Nivel de Ciclu: %d',
        'Total Points: %d' => 'Total Puncte: %d',

        // Cupoane
        'Your Coupons' => 'Cupoanele Tale',
        '%d%% OFF' => '%d%% REDUCERE',
        'Premium Reward' => 'Recompensă Premium',
        'Copy' => 'Copiază',
        'Valid for orders up to 400 lei' => 'Valid pentru comenzi de până la 400 lei',
        'Expired' => 'Expirat',
        'Used' => 'Utilizat',
        'Valid until %s' => 'Valid până la %s',
        'Coupon code copied to clipboard!' => 'Cod de cupon copiat în clipboard!',
        'Copied!' => 'Copiat!',

        // Notificări
        'Notifications' => 'Notificări',
        'You\'ve earned a 60% discount coupon for orders up to 400 lei! Use the coupon code at checkout.' => 'Ai câștigat un cupon de reducere de 60% pentru comenzi de până la 400 lei! Utilizează codul de cupon la finalizarea comenzii.',

        // Recompense
        'Rewards' => 'Recompense',
        '%d%% Discount (up to 400 lei)' => '%d%% Reducere (până la 400 lei)',
        '%d%% Discount' => '%d%% Reducere',
        'Free Shipping' => 'Transport Gratuit',
        'Claimed' => 'Revendicat',
        'View Coupon' => 'Vezi Cupon',
        'No rewards available yet.' => 'Nu există recompense disponibile încă.',

        // Istoric
        'View Points History' => 'Vezi Istoricul Punctelor',
        'Points History' => 'Istoricul Punctelor',
        'No points history yet.' => 'Nu există încă istoric de puncte.',
        'Date' => 'Data',
        'Points' => 'Puncte',
        'Description' => 'Descriere',
        'Claimed Rewards' => 'Recompense Revendicate',
        'No rewards claimed yet.' => 'Nu există încă recompense revendicate.',
        'Points Level' => 'Nivel de Puncte',
        'Reward' => 'Recompensă',
        'Order #%s - %d points for €%.2f spent' => 'Comanda #%s - %d puncte pentru €%.2f cheltuiți',
        'Review for product #%s - %d points' => 'Recenzie pentru produsul #%s - %d puncte',

        // Pagina de setări admin
        'Loyalty Program Settings' => 'Setări Program de Fidelitate',
        'General Settings' => 'Setări Generale',
        'Reward Tiers' => 'Niveluri de Recompensă',
        'Membership Tiers' => 'Niveluri de Membru',
        'Points per Euro' => 'Puncte per Euro',
        'Number of points awarded per euro spent on orders' => 'Numărul de puncte acordate pentru fiecare euro cheltuit pe comenzi',
        'Points for Product Review' => 'Puncte pentru Recenzie Produs',
        'Number of points awarded for product reviews (customer must have purchased the product)' => 'Numărul de puncte acordate pentru recenzii de produse (clientul trebuie să fi cumpărat produsul)',
        'Premium Discount Max Order Value' => 'Valoarea Maximă a Comenzii pentru Reducerea Premium',
        'Maximum order value (in lei) for the premium 60% discount awarded at 2000 points' => 'Valoarea maximă a comenzii (în lei) pentru reducerea premium de 60% acordată la 2000 de puncte',
        'Premium Reward (2000 Points)' => 'Recompensă Premium (2000 de Puncte)',
        'When users reach 2000 points, they will automatically receive a 60% discount coupon valid for orders up to the maximum value set in General Settings.' => 'Când utilizatorii ajung la 2000 de puncte, vor primi automat un cupon de reducere de 60% valid pentru comenzi până la valoarea maximă setată în Setări Generale.',
        'Add Reward Tier' => 'Adaugă Nivel de Recompensă',
        'Add Membership Tier' => 'Adaugă Nivel de Membru',
        'Key (e.g. bronze)' => 'Cheie (ex. bronz)',
        'Display Name' => 'Nume Afișat',
        'Min Points' => 'Puncte Minime',
        'Color' => 'Culoare',
        'Tier benefits description' => 'Descrierea beneficiilor nivelului',
        'Remove' => 'Elimină',
        'Discount' => 'Reducere',
        'Points' => 'Puncte',
        'Value' => 'Valoare',

        // Mesaje coș/checkout
        'Your Loyalty Coupons' => 'Cupoanele Tale de Fidelitate',
        'Applied' => 'Aplicat',
        'Apply' => 'Aplică',
        'Applied from Loyalty Program' => 'Aplicat din Programul de Fidelitate',
        'Free shipping applied from your loyalty program rewards!' => 'Transport gratuit aplicat din recompensele programului de fidelitate!',
        'You must be logged in to apply coupons.' => 'Trebuie să fii autentificat pentru a aplica cupoane.',
        'Security check failed.' => 'Verificarea de securitate a eșuat.',
        'Coupon code is missing.' => 'Codul de cupon lipsește.',
        'Coupon applied successfully!' => 'Cupon aplicat cu succes!',
        'Failed to apply coupon. Please try again.' => 'Aplicarea cuponului a eșuat. Te rugăm să încerci din nou.',
        'This coupon is not valid or has already been used.' => 'Acest cupon nu este valid sau a fost deja utilizat.',
        'Invalid coupon code.' => 'Cod de cupon invalid.',
        'Coupon successfully applied!' => 'Cupon aplicat cu succes!',
        'Applying...' => 'Se aplică...',

        // Mesaje thank you
        'You\'ll earn %d loyalty points when this order is completed. <a href="%s">View your points</a>' => 'Vei câștiga %d puncte de fidelitate când această comandă va fi finalizată. <a href="%s">Vezi punctele tale</a>',
        '%d loyalty points awarded to customer.' => '%d puncte de fidelitate acordate clientului.',
        'Loyalty coupon %s was used and marked as claimed.' => 'Cuponul de fidelitate %s a fost utilizat și marcat ca revendicat.',

        // Nivel de membru
        'Welcome to our loyalty program!' => 'Bine ai venit în programul nostru de fidelitate!',
        'Enjoy special birthday offers and early sale access.' => 'Bucură-te de oferte speciale de ziua ta și acces timpuriu la reduceri.',
        'Exclusive promotions and priority customer service.' => 'Promoții exclusive și serviciu clienți prioritar.',
        'VIP service and exclusive product access.' => 'Serviciu VIP și acces exclusiv la produse.',
        'Next Tier: %s' => 'Următorul Nivel: %s',
        'You need %d more points to reach %s level' => 'Mai ai nevoie de %d puncte pentru a ajunge la nivelul %s',
        'Your Member Benefits' => 'Beneficiile Tale de Membru',

        // Mesaje email
        'Congratulations!' => 'Felicitări!',
        'You\'ve earned a %d%% discount through our loyalty program!' => 'Ai câștigat o reducere de %d%% prin programul nostru de fidelitate!',
        'As a valued customer, we\'re excited to reward you with this special discount on your next purchase.' => 'În calitate de client valoros, suntem încântați să te răsplătim cu această reducere specială la următoarea ta achiziție.',
        'Your Coupon Code:' => 'Codul tău de Cupon:',
        'Valid until: %s' => 'Valid până la: %s',
        'Simply enter this code at checkout to claim your discount.' => 'Introdu simplu acest cod la finalizarea comenzii pentru a beneficia de reducere.',
        'Shop Now' => 'Cumpără Acum',
        'Thank you for being a loyal customer!' => 'Îți mulțumim că ești un client fidel!',
        'My Account' => 'Contul Meu',
        'You\'ve earned free shipping through our loyalty program!' => 'Ai câștigat transport gratuit prin programul nostru de fidelitate!',
        'As a valued customer, we\'re excited to reward you with free shipping on your next order.' => 'În calitate de client valoros, suntem încântați să te răsplătim cu transport gratuit pentru următoarea comandă.',
        'Your Reward:' => 'Recompensa Ta:',
        'FREE SHIPPING' => 'TRANSPORT GRATUIT',
        'Your free shipping has been automatically applied to your account.' => 'Transportul gratuit a fost aplicat automat contului tău.',
        'This reward will be automatically applied to your next order at checkout. No code needed!' => 'Această recompensă va fi aplicată automat la următoarea comandă la finalizarea comenzii. Nu este necesar niciun cod!',
        'You\'ve earned a free product through our loyalty program!' => 'Ai câștigat un produs gratuit prin programul nostru de fidelitate!',
        'As a valued customer, we\'re excited to reward you with a free product of your choice.' => 'În calitate de client valoros, suntem încântați să te răsplătim cu un produs gratuit la alegerea ta.',
        'FREE PRODUCT' => 'PRODUS GRATUIT',
        'Click the button below to choose your free product.' => 'Apasă butonul de mai jos pentru a alege produsul gratuit.',
        'You can select from our curated list of products or choose from your wishlist items.' => 'Poți selecta din lista noastră de produse sau poți alege din articolele din lista ta de dorințe.',
        'Claim Your Free Product' => 'Revendică Produsul Gratuit',

        // Interfața mini-cupoane
        'Coupon code copied to clipboard!' => 'Cod de cupon copiat în clipboard!',

        // Plugin notices
        'WooCommerce Loyalty Gamification requires WooCommerce to be installed and activated.' => 'WooCommerce Loyalty Gamification necesită ca WooCommerce să fie instalat și activat.',
        'Product added to free products list.' => 'Produs adăugat la lista de produse gratuite.',
        'Product removed from free products list.' => 'Produs eliminat din lista de produse gratuite.',
'You have already checked in today. Come back tomorrow!' => 'Ai verificat deja astăzi. Revino mâine!',
'Failed to save check-in data. Please try again.' => 'Salvarea datelor de verificare a eșuat. Te rugăm să încerci din nou.',
'Daily check-in - Day %d streak - %d points' => 'Verificare zilnică - Ziua %d consecutivă - %d puncte',
'Check-in successful! You earned %d points.' => 'Verificare reușită! Ai câștigat %d puncte.',
'Congratulations! You reached a %d-day streak and earned a bonus of %d points!' => 'Felicitări! Ai atins o serie de %d zile și ai câștigat un bonus de %d puncte!',
'Day Streak' => 'Zile Consecutive',
'%d days until %d-day milestone!' => '%d zile până la obiectivul de %d zile!',
'You\'ve checked in today! %d points earned.' => 'Ai verificat astăzi! %d puncte câștigate.',
'Next check-in available: %s' => 'Următoarea verificare disponibilă: %s',
'points available' => 'puncte disponibile',
'Milestone bonus: +%d points!' => 'Bonus pentru obiectiv: +%d puncte!',
'Check in now to keep your streak!' => 'Verifică acum pentru a-ți menține seria!',
'Check In Now' => 'Verifică Acum',
'Milestone Rewards' => 'Recompense pentru Obiective',
'days' => 'zile',
'bonus points' => 'puncte bonus',
'Daily Check-in' => 'Verificare Zilnică',
'Base Check-in Points' => 'Puncte de Bază pentru Verificare',
'Base number of points awarded for each daily check-in' => 'Numărul de bază de puncte acordate pentru fiecare verificare zilnică',
'Streak Multiplier' => 'Multiplicator pentru Serie',
'Additional points multiplier per day of streak (e.g., 0.1 = +10% points per streak day)' => 'Multiplicator de puncte suplimentare pe zi de serie (de ex., 0.1 = +10% puncte per zi de serie)',
'Define special milestone days and bonus points awarded when users reach these streak milestones' => 'Definește zilele speciale de obiectiv și punctele bonus acordate când utilizatorii ating aceste obiective',
'Add Milestone' => 'Adaugă Obiectiv',
'Days' => 'Zile',
'Bonus Points' => 'Puncte Bonus',
'Check-in System' => 'Sistem de Verificare'

    );

    // Verifică dacă textul există în lista noastră de traduceri
    if (isset($translations[$text])) {
        return $translations[$text];
    }

    // Returnează textul original dacă nu există traducere
    return $translated_text;
}