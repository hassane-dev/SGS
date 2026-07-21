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

// Normalization map to map short codes, free-text database parameters, or inputs to canonical locales
$lang_map = [
    'fr' => 'fr_FR',
    'fr_FR' => 'fr_FR',
    'Francais' => 'fr_FR',
    'Français' => 'fr_FR',
    'en' => 'en_US',
    'en_US' => 'en_US',
    'Anglais' => 'en_US',
    'English' => 'en_US',
    'ar' => 'ar',
    'Arabe' => 'ar',
    'Arabic' => 'ar'
];

// Normalize school default language if retrieved
if ($school_lang) {
    $school_lang = $lang_map[$school_lang] ?? $default_lang;
}

// 2. Check for language change in URL and update session
if (isset($_GET['lang'])) {
    $requested_lang = $_GET['lang'];
    $normalized_lang = $lang_map[$requested_lang] ?? $lang_map[explode('_', $requested_lang)[0]] ?? $default_lang;
    if (isset($supported_languages[$normalized_lang])) {
        $_SESSION['lang'] = $normalized_lang;

        // Persist preference for authenticated users
        if (Auth::check()) {
            $user_id = Auth::getUserId();
            if ($user_id) {
                require_once __DIR__ . '/../models/ParametreUtilisateur.php';
                $user_settings = ParametreUtilisateur::findByUserId($user_id);
                $user_settings->langue_preferee = $normalized_lang;
                if (!$user_settings->lycee_id) {
                    $user_settings->lycee_id = Auth::getLyceeId();
                }
                $user_settings->save();
            }
        }
    }
    // Redirect to the same page without the lang parameter to have a clean URL
    $redirect_url = strtok($_SERVER['REQUEST_URI'], '?');
    header("Location: " . $redirect_url);
    exit();
}

// 3. Determine the language to use
$lang = $_SESSION['lang'] ?? $school_lang ?? $default_lang;

// Settle on normalized key
$lang = $lang_map[$lang] ?? $lang_map[explode('_', $lang)[0]] ?? $default_lang;
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
putenv('LANG=' . $lang);
putenv('LANGUAGE=' . $lang);

if ($lang === 'ar') {
    setlocale(LC_ALL, 'ar_SA.utf8', 'ar_EG.utf8', 'ar_SA.UTF-8', 'ar_EG.UTF-8', 'ar.utf8', 'ar');
} elseif ($lang === 'en_US') {
    setlocale(LC_ALL, 'en_US.utf8', 'en_US.UTF-8', 'en_US', 'english');
} else {
    setlocale(LC_ALL, $lang . '.utf8', $lang . '.UTF-8', $lang, 'french');
}

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
