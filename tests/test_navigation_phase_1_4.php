<?php
// tests/test_navigation_phase_1_4.php

define('TEST_MODE', true);
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

echo "=========================================================================\n";
echo "📊 AUDIT D'INTÉGRATION ET DE NAVIGATION DES PHASES 1 À 4\n";
echo "=========================================================================\n";

$public_index_path = __DIR__ . '/../public/index.php';
if (!file_exists($public_index_path)) {
    echo "  [FAIL] public/index.php introuvable!\n";
    exit(1);
}

// 1. Lire et parser les routes dans public/index.php
$index_content = file_get_contents($public_index_path);
preg_match_all('/\$router->register\(\s*\'([^\']+)\'\s*,\s*\'([^\']+)\'\s*,\s*\'([^\']+)\'\s*\)/', $index_content, $matches, PREG_SET_ORDER);

echo "\n--- [VÉRIFICATION DU ROUTAGE ET DES CONTRÔLEURS] ---\n";
$registered_routes = [];
$errors = 0;

foreach ($matches as $match) {
    $route = $match[1];
    $controller = $match[2];
    $action = $match[3];

    $registered_routes[$route] = [
        'controller' => $controller,
        'action' => $action
    ];

    // Vérifier si le fichier du contrôleur existe
    $controller_file = __DIR__ . '/../src/controllers/' . $controller . '.php';
    if (!file_exists($controller_file)) {
        echo "  [FAIL] Route '$route': Contrôleur '$controller' introuvable dans src/controllers/!\n";
        $errors++;
        continue;
    }

    // Inclure le contrôleur et vérifier la méthode
    require_once $controller_file;
    if (!class_exists($controller)) {
        echo "  [FAIL] Route '$route': Classe '$controller' non définie dans le fichier!\n";
        $errors++;
        continue;
    }

    $ref = new ReflectionClass($controller);
    if (!$ref->hasMethod($action)) {
        echo "  [FAIL] Route '$route': Méthode '$action' manquante dans '$controller'!\n";
        $errors++;
    } else {
        echo "  [PASS] Route '$route' -> '$controller::$action' vérifiée.\n";
    }
}

// 2. Parser et vérifier la barre latérale (sidebar_able.php)
echo "\n--- [VÉRIFICATION DE LA BARRE LATÉRALE (SIDEBAR)] ---\n";
$sidebar_path = __DIR__ . '/../src/views/layouts/sidebar_able.php';
if (!file_exists($sidebar_path)) {
    echo "  [FAIL] sidebar_able.php introuvable!\n";
    $errors++;
} else {
    // Intercepter la déclaration de $navItems dans sidebar_able.php
    $sidebar_content = file_get_contents($sidebar_path);

    // Extraire tous les liens de type 'url' => '/...'
    preg_match_all('/\'url\'\s*=>\s*\'([^\']+)\'/', $sidebar_content, $url_matches);
    $urls = array_unique($url_matches[1]);

    foreach ($urls as $url) {
        if ($url === '/' || $url === '#') {
            echo "  [PASS] Sidebar URL '$url' (Accueil/Racine) valide d'office.\n";
            continue;
        }

        // Vérifier s'il y a un pattern de route qui matche cette URL
        $matched = false;
        foreach ($registered_routes as $route_pattern => $info) {
            // Remplacer les paramètres dynamiques comme {id} ou {eleveId} par une regex générique (\w+)
            $pattern = preg_replace('/\{(\w+)\}/', '(\w+)', $route_pattern);
            $pattern = '#^' . $pattern . '$#';

            // Retirer les éventuels paramètres de requête de l'URL pour la comparaison de routage
            $url_path = parse_url($url, PHP_URL_PATH);

            if (preg_match($pattern, $url_path)) {
                $matched = true;
                break;
            }
        }

        if ($matched) {
            echo "  [PASS] Sidebar URL '$url' matche une route enregistrée.\n";
        } else {
            echo "  [FAIL] Sidebar URL '$url' ne matche AUCUNE route enregistrée dans public/index.php (Risque d'erreur 404)!\n";
            $errors++;
        }
    }
}

// 3. Vérification des Vues de Comptabilité
echo "\n--- [VÉRIFICATION DES DOSSIERS DE VUES DE COMPTABILITÉ] ---\n";
$required_views = [
    'src/views/comptabilite/comptes_financiers/index.php',
    'src/views/comptabilite/comptes_financiers/create.php',
    'src/views/comptabilite/comptes_financiers/edit.php',
    'src/views/comptabilite/politique_financiere/edit.php',
    'src/views/comptabilite/controle_financier/index.php',
    'src/views/comptabilite/journal/index.php',
    'src/views/comptabilite/rapports/index.php',
    'src/views/errors/404.php',
    'src/views/errors/403.php'
];

foreach ($required_views as $v) {
    $path = __DIR__ . '/../' . $v;
    if (file_exists($path)) {
        echo "  [PASS] Vue requise existante : $v\n";
    } else {
        echo "  [FAIL] Vue requise ABSENTE : $v\n";
        $errors++;
    }
}

echo "\n=========================================================================\n";
if ($errors === 0) {
    echo "🏆 TOUS LES TESTS DE NAVIGATION ET D'INTÉGRATION ONT RÉUSSI !\n";
    echo "=========================================================================\n";
    exit(0);
} else {
    echo "❌ $errors ERREUR(S) DÉTECTÉE(S) LORS DE L'AUDIT DE NAVIGATION !\n";
    echo "=========================================================================\n";
    exit(1);
}
?>