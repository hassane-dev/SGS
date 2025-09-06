<?php

// --- i18n / gettext setup ---

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Set the default language
$default_lang = 'fr_FR';

// 2. Check for language change in URL and update session
if (isset($_GET['lang'])) {
    $_SESSION['lang'] = $_GET['lang'];
    // Redirect to the same page without the lang parameter to have a clean URL
    $redirect_url = strtok($_SERVER['REQUEST_URI'], '?');
    header("Location: " . $redirect_url);
    exit();
}

// 3. Determine the language to use
$lang = $_SESSION['lang'] ?? $default_lang;

// 4. Set up the gettext environment
// The domain should match the name of your .mo file (e.g., messages.mo)
$domain = 'messages';
$locale_dir = __DIR__ . '/../../locale';

// Set language environment variables
putenv('LC_ALL=' . $lang);
setlocale(LC_ALL, $lang . '.utf8', $lang);

// Bind the text domain
bindtextdomain($domain, $locale_dir);
bind_textdomain_codeset($domain, 'UTF-8');

// Choose the domain
textdomain($domain);

// 5. Create a shorthand function for translation
if (!function_exists('_')) {
    function _($text) {
        return gettext($text);
    }
}

?>
