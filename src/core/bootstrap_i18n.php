<?php

// --- i18n / gettext setup ---
require_once __DIR__ . '/../models/ParamGeneral.php';
require_once __DIR__ . '/Auth.php';


if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Determine if we are in the setup process to avoid database calls
$uri = strtok($_SERVER['REQUEST_URI'], '?');
$is_setup_route = (strpos($uri, '/setup') === 0);

// Fetch school-specific language settings only if not in setup and user is logged in
$school_lang = null;
if (!$is_setup_route && Auth::check()) {
    try {
        $params = ParamGeneral::findByAuthenticatedUser();
        if ($params && !empty($params['langue_1'])) {
            $school_lang = $params['langue_1'];
        }
    } catch (PDOException $e) {
        // Gracefully handle cases where the database/table doesn't exist yet,
        // especially during the transition from setup to the main application.
        // We can log this if needed, but for now, we'll just fall back.
        $school_lang = null;
    }
}

// 1. Define supported languages
$supported_languages = [
    'fr_FR' => ['name' => 'Français', 'dir' => 'ltr'],
    'en_US' => ['name' => 'English', 'dir' => 'ltr'],
    'ar'    => ['name' => 'العربية', 'dir' => 'rtl']
];
$default_lang = 'fr_FR';

// 2. Check for language change in URL and update session
if (isset($_GET['lang']) && isset($supported_languages[$_GET['lang']])) {
    $_SESSION['lang'] = $_GET['lang'];
    // Redirect to the same page without the lang parameter to have a clean URL
    $redirect_url = strtok($_SERVER['REQUEST_URI'], '?');
    header("Location: " . $redirect_url);
    exit();
}

// 3. Determine the language to use
$lang = $_SESSION['lang'] ?? $school_lang ?? $default_lang;
if (!isset($supported_languages[$lang])) {
    $lang = $default_lang;
}

// Sync the determined language with the session for consistency across pages
$_SESSION['lang'] = $lang;

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
