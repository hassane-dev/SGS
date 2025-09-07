<?php

// Initialize internationalization (i18n)
require_once __DIR__ . '/../src/core/bootstrap_i18n.php';

// Require necessary files
require_once __DIR__ . '/../src/core/Router.php';
require_once __DIR__ . '/../src/core/Auth.php';

// Basic autoloader for our classes
spl_autoload_register(function ($class_name) {
    $path = __DIR__ . '/../src/' . str_replace('\\', '/', $class_name) . '.php';
    if (file_exists($path)) {
        require_once $path;
    }
});

// --- Routing ---
$router = new Router();

// Register routes
$router->register('/', 'HomeController', 'index');
$router->register('/login', 'AuthController', 'login');
$router->register('/logout', 'AuthController', 'logout');

// Settings
$router->register('/settings', 'SettingsController', 'index');

// Lycees CRUD
$router->register('/lycees', 'LyceeController', 'index');
$router->register('/lycees/create', 'LyceeController', 'create');
$router->register('/lycees/store', 'LyceeController', 'store');
$router->register('/lycees/edit', 'LyceeController', 'edit');
$router->register('/lycees/update', 'LyceeController', 'update');
$router->register('/lycees/destroy', 'LyceeController', 'destroy');

// Cycles CRUD
$router->register('/cycles', 'CycleController', 'index');
$router->register('/cycles/create', 'CycleController', 'create');
$router->register('/cycles/store', 'CycleController', 'store');
$router->register('/cycles/edit', 'CycleController', 'edit');
$router->register('/cycles/update', 'CycleController', 'update');
$router->register('/cycles/destroy', 'CycleController', 'destroy');

// Classes CRUD
$router->register('/classes', 'ClasseController', 'index');
$router->register('/classes/create', 'ClasseController', 'create');
$router->register('/classes/store', 'ClasseController', 'store');
$router->register('/classes/edit', 'ClasseController', 'edit');
$router->register('/classes/update', 'ClasseController', 'update');
$router->register('/classes/destroy', 'ClasseController', 'destroy');

// Matieres CRUD
$router->register('/matieres', 'MatiereController', 'index');
$router->register('/matieres/create', 'MatiereController', 'create');
$router->register('/matieres/store', 'MatiereController', 'store');
$router->register('/matieres/edit', 'MatiereController', 'edit');
$router->register('/matieres/update', 'MatiereController', 'update');
$router->register('/matieres/destroy', 'MatiereController', 'destroy');
$router->register('/matieres/assign', 'MatiereController', 'assign'); // GET to show form, POST to update

// Users CRUD
$router->register('/users', 'UserController', 'index');
$router->register('/users/create', 'UserController', 'create');
$router->register('/users/store', 'UserController', 'store');
$router->register('/users/edit', 'UserController', 'edit');
$router->register('/users/update', 'UserController', 'update');
$router->register('/users/destroy', 'UserController', 'destroy');

// Eleves CRUD
$router->register('/eleves', 'EleveController', 'index');
$router->register('/eleves/create', 'EleveController', 'create');
$router->register('/eleves/store', 'EleveController', 'store');
$router->register('/eleves/edit', 'EleveController', 'edit');
$router->register('/eleves/details', 'EleveController', 'details');
$router->register('/eleves/update', 'EleveController', 'update');
$router->register('/eleves/destroy', 'EleveController', 'destroy');

// Inscriptions
$router->register('/inscriptions/show', 'InscriptionController', 'showForm');
$router->register('/inscriptions/enroll', 'InscriptionController', 'enroll');

// Notes
$router->register('/notes', 'NoteController', 'index');
$router->register('/notes/enter', 'NoteController', 'enter');
$router->register('/notes/save', 'NoteController', 'save');

// Paiements
$router->register('/paiements', 'PaiementController', 'index');
$router->register('/paiements/create', 'PaiementController', 'create');
$router->register('/paiements/store', 'PaiementController', 'store');

// Bulletins
$router->register('/bulletin/show', 'BulletinController', 'show');

// Boutique
$router->register('/boutique/articles', 'BoutiqueArticleController', 'index');
$router->register('/boutique/articles/create', 'BoutiqueArticleController', 'create');
$router->register('/boutique/articles/store', 'BoutiqueArticleController', 'store');
$router->register('/boutique/articles/edit', 'BoutiqueArticleController', 'edit');
$router->register('/boutique/articles/update', 'BoutiqueArticleController', 'update');
$router->register('/boutique/articles/destroy', 'BoutiqueArticleController', 'destroy');
$router->register('/boutique/achats', 'BoutiqueAchatController', 'index');
$router->register('/boutique/achats/create', 'BoutiqueAchatController', 'create');
$router->register('/boutique/achats/store', 'BoutiqueAchatController', 'store');

// Tests d'Entrée
$router->register('/tests_entree', 'TestEntreeController', 'index');
$router->register('/tests_entree/create', 'TestEntreeController', 'create');
$router->register('/tests_entree/store', 'TestEntreeController', 'store');
$router->register('/tests_entree/destroy', 'TestEntreeController', 'destroy');

// Roles & Permissions
$router->register('/roles', 'RoleController', 'index');
$router->register('/roles/create', 'RoleController', 'create');
$router->register('/roles/store', 'RoleController', 'store');
$router->register('/roles/edit', 'RoleController', 'edit');
$router->register('/roles/update', 'RoleController', 'update');
$router->register('/roles/destroy', 'RoleController', 'destroy');

// Licences
$router->register('/licences', 'LicenceController', 'index');
$router->register('/licences/create', 'LicenceController', 'create');
$router->register('/licences/store', 'LicenceController', 'store');
$router->register('/licences/edit', 'LicenceController', 'edit');
$router->register('/licences/update', 'LicenceController', 'update');
$router->register('/licences/destroy', 'LicenceController', 'destroy');


// Get the requested URI
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// A basic way to handle sub-directory installations if needed.
// If the app is at http://localhost/myapp/, we want to route based on the path after /myapp/
// This is a simplification. A real app would use a configurable base path.
$base_path = ''; // Assume running in root for now
$uri = str_replace($base_path, '', $uri);
if ($uri === '') {
    $uri = '/';
}


// Dispatch the router
try {
    $router->dispatch($uri);
} catch (Exception $e) {
    // Basic error handling
    echo "An error occurred: " . $e->getMessage();
}

?>
