<?php
/**
 * Funcții ajutătoare pentru trimiterea email-urilor
 */

// Previne accesul direct
if (!defined('WPINC')) {
    die;
}

/**
 * Configurează PHPMailer pentru trimiterea corectă a email-urilor
 */
function wc_loyalty_configure_phpmailer($phpmailer) {
    // Verifică dacă avem constante SMTP definite
    if (defined('SMTP_HOST') && !empty(SMTP_HOST)) {
        // Configurează pentru a folosi SMTP
        $phpmailer->isSMTP();
        $phpmailer->Host = SMTP_HOST;
        
        // Setări de autentificare
        if (defined('SMTP_AUTH') && SMTP_AUTH) {
            $phpmailer->SMTPAuth = true;
            
            if (defined('SMTP_USER') && defined('SMTP_PASS')) {
                $phpmailer->Username = SMTP_USER;
                $phpmailer->Password = SMTP_PASS;
            }
        }
        
        // Setări de securitate și port
        if (defined('SMTP_SECURE')) {
            $phpmailer->SMTPSecure = SMTP_SECURE;
        }
        
        if (defined('SMTP_PORT')) {
            $phpmailer->Port = SMTP_PORT;
        }
        
        // Informații expeditor
        if (defined('SMTP_FROM') && filter_var(SMTP_FROM, FILTER_VALIDATE_EMAIL)) {
            $phpmailer->From = SMTP_FROM;
        }
        
        if (defined('SMTP_NAME')) {
            $phpmailer->FromName = SMTP_NAME;
        }
        
        // Debugging
        if (defined('SMTP_DEBUG')) {
            $phpmailer->SMTPDebug = SMTP_DEBUG;
        }
        
        // Setări suplimentare
        $phpmailer->Timeout = 30; // Timeout mai mare pentru conexiuni lente
        $phpmailer->SMTPKeepAlive = true; // Menține conexiunea vie pentru trimiteri multiple
    }
    
    return $phpmailer;
}

/**
 * Funcție helper pentru trimiterea email-urilor cu debugging
 */
function wc_loyalty_send_email($to, $subject, $message, $headers = array(), $attachments = array()) {
    // Asigură-te că mesajul nu este gol
    if (empty($message)) {
        error_log('WC Loyalty: Conținutul email-ului este gol');
        return false;
    }
    
    // Asigură-te că avem header pentru HTML
    $has_html_header = false;
    
    if (is_array($headers)) {
        foreach ($headers as $header) {
            if (stripos($header, 'content-type: text/html') !== false) {
                $has_html_header = true;
                break;
            }
        }
        
        if (!$has_html_header) {
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
        }
    } else if (is_string($headers)) {
        if (stripos($headers, 'content-type: text/html') === false) {
            $headers .= "\r\nContent-Type: text/html; charset=UTF-8";
        }
    } else {
        $headers = array('Content-Type: text/html; charset=UTF-8');
    }
    
    // Verifică email-ul destinatarului
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        error_log('WC Loyalty: Adresa email destinatar invalidă: ' . $to);
        return false;
    }
    
    // Hook pentru configurarea PHPMailer
    add_action('phpmailer_init', 'wc_loyalty_configure_phpmailer');
    
    // Încearcă să trimită email-ul
    try {
        $result = wp_mail($to, $subject, $message, $headers, $attachments);
        
        // Elimină hook-ul pentru a nu afecta alte email-uri
        remove_action('phpmailer_init', 'wc_loyalty_configure_phpmailer');
        
        if (!$result) {
            error_log('WC Loyalty: Trimiterea email-ului a eșuat pentru ' . $to);
        }
        
        return $result;
    } catch (Exception $e) {
        error_log('WC Loyalty: Excepție la trimiterea email-ului: ' . $e->getMessage());
        remove_action('phpmailer_init', 'wc_loyalty_configure_phpmailer');
        return false;
    }
}