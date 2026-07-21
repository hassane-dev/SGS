<?php

$test_keys = [
    'Navigation' => [
        'Tableau de Bord',
        'Accueil'
    ],
    'Actions' => [
        'Enregistrer',
        'Annuler',
        'Modifier',
        'Supprimer'
    ],
    'Formulaires' => [
        'Nom & Prénom',
        'Date de Naissance',
        'Classe',
        'Libellé'
    ],
    'Messages Flash' => [
        'Paramètres mis à jour avec succès.',
        'Aucune année académique active.'
    ],
    'Comptabilité' => [
        "Reçu de Paiement",
        'Montant Versé',
        'Reliquat sur inscription :'
    ]
];

function run_gettext_test($lang, $categories) {
    // Set up gettext environment
    $domain = 'messages';
    $locale_dir = __DIR__ . '/locale';

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

    bindtextdomain($domain, $locale_dir);
    bind_textdomain_codeset($domain, 'UTF-8');
    textdomain($domain);

    echo "=== Language: $lang (locale: " . setlocale(LC_ALL, 0) . ") ===\n";
    foreach ($categories as $cat => $keys) {
        echo "  [$cat]\n";
        foreach ($keys as $key) {
            $translated = gettext($key);
            echo "    - Original: \"$key\"\n";
            echo "      Translated: \"$translated\"\n";
        }
    }
    echo "\n";
}

run_gettext_test('fr_FR', $test_keys);
run_gettext_test('en_US', $test_keys);
run_gettext_test('ar', $test_keys);
