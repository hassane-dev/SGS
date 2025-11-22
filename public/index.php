<?php


// Initialize internationalization (i18n)
require_once __DIR__ . '/../src/core/bootstrap_i18n.php';

// --- First Time Setup Check ---
// A more robust check is to see if any school has been created.
// If the lycees table is empty, we assume it's a fresh install.
require_once __DIR__ . '/../src/models/Lycee.php';
$lycees = Lycee::findAll();
$uri = strtok($_SERVER['REQUEST_URI'], '?');

if (empty($lycees)) {
    // If no school exists, we must run the setup process.
    // We only allow access to the setup routes.
    if (strpos($uri, '/setup') !== 0) {
        header('Location: /setup');
        exit();
    }
} else {
    // If schools exist, the setup is complete.
    // Block any further access to the setup routes.
    if (strpos($uri, '/setup') === 0) {
        header('Location: /login');
        exit();
    }
}

// --- Auto-seed the database if needed ---
// This runs only if the setup is complete but the default data is missing.
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/models/Role.php';
try {
    $roles = Role::findAll();
    if (empty($roles)) {
        // The roles table is empty, so we need to seed the database.
        $db = Database::getInstance();
        $sql = file_get_contents(__DIR__ . '/../db/seeds.sql');
        if ($sql) {
            $db->exec($sql);
        }
    }
} catch (Exception $e) {
    // If tables don't exist yet (e.g., before setup), this will fail.
    // We can safely ignore this error here.
}


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
$router->register('/settings/change-language', 'SettingsController', 'changeLanguage');
$router->register('/settings/change-lycee', 'SettingsController', 'changeLycee');
$router->register('/settings/change-year', 'SettingsController', 'changeYear');

// Notifications
$router->register('/notifications/all', 'NotificationController', 'index');
$router->register('/notifications/mark-as-read', 'NotificationController', 'markAsRead');
$router->register('/notifications/mark-all-as-read', 'NotificationController', 'markAllAsRead');

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
$router->register('/classes/show', 'ClasseController', 'show');
$router->register('/classes/create', 'ClasseController', 'create');
$router->register('/classes/store', 'ClasseController', 'store');
$router->register('/classes/edit', 'ClasseController', 'edit');
$router->register('/classes/update', 'ClasseController', 'update');
$router->register('/classes/destroy', 'ClasseController', 'destroy');
$router->register('/classes/assignMatiere', 'ClasseController', 'assignMatiere');
$router->register('/classes/removeMatiere', 'ClasseController', 'removeMatiere');
$router->register('/classes/assignEnseignant', 'ClasseController', 'assignEnseignant');
$router->register('/classes/unassignEnseignant', 'ClasseController', 'unassignEnseignant');

// API routes for dynamic dropdowns
$router->register('/classes/get-niveaux', 'ClasseController', 'getNiveauxForCycle');
$router->register('/classes/get-series', 'ClasseController', 'getSeriesForNiveau');
$router->register('/classes/get-numeros', 'ClasseController', 'getNumerosForClasse');

// Matieres CRUD
$router->register('/matieres', 'MatiereController', 'index');
$router->register('/matieres/create', 'MatiereController', 'create');
$router->register('/matieres/store', 'MatiereController', 'store');
$router->register('/matieres/edit', 'MatiereController', 'edit');
$router->register('/matieres/update', 'MatiereController', 'update');
$router->register('/matieres/destroy', 'MatiereController', 'destroy');

// Sequences CRUD
$router->register('/sequences', 'SequenceController', 'index');
$router->register('/sequences/create', 'SequenceController', 'create');
$router->register('/sequences/store', 'SequenceController', 'store');
$router->register('/sequences/edit', 'SequenceController', 'edit');
$router->register('/sequences/update', 'SequenceController', 'update');
$router->register('/sequences/destroy', 'SequenceController', 'destroy');

// Evaluation Settings
$router->register('/evaluations/settings', 'ParametresEvaluationController', 'index');
$router->register('/evaluations/settings/save', 'ParametresEvaluationController', 'save');

// Users CRUD
$router->register('/users', 'UserController', 'index');
$router->register('/users/create', 'UserController', 'create');
$router->register('/users/store', 'UserController', 'store');
$router->register('/users/view', 'UserController', 'view');
$router->register('/users/edit', 'UserController', 'edit');
$router->register('/users/update', 'UserController', 'update');
$router->register('/users/destroy', 'UserController', 'destroy');
$router->register('/profile', 'UserController', 'profile');
$router->register('/profile/update-password', 'UserController', 'updatePassword');

// Eleves CRUD
$router->register('/eleves', 'EleveController', 'index');
$router->register('/eleves/create', 'EleveController', 'create');
$router->register('/eleves/store', 'EleveController', 'store');
$router->register('/eleves/edit', 'EleveController', 'edit');
$router->register('/eleves/details', 'EleveController', 'details');
$router->register('/eleves/update', 'EleveController', 'update');
$router->register('/eleves/destroy', 'EleveController', 'destroy');
$router->register('/eleves/assign-class', 'EleveController', 'assignClass');
$router->register('/eleves/process-assignment', 'EleveController', 'processAssignment');

// Obsolete inscription routes, the flow now starts from EleveController
// $router->register('/inscriptions/show-form', 'InscriptionController', 'showForm');
// $router->register('/inscriptions/enroll', 'InscriptionController', 'enroll');

// Frais (Fee management)
$router->register('/frais', 'FraisController', 'index');
$router->register('/frais/store', 'FraisController', 'store');

// Comptable (Accountant) flow
$router->register('/comptable/pending', 'ComptableController', 'listPending');
$router->register('/comptable/validate-form', 'ComptableController', 'showValidationForm');
$router->register('/comptable/validate', 'ComptableController', 'processValidation');

// Mensualites (Monthly Payments)
$router->register('/mensualites/show-form', 'MensualiteController', 'showForm');
$router->register('/mensualites/store', 'MensualiteController', 'store');

// Recus (Receipts)
$router->register('/recu/inscription', 'RecuController', 'showInscriptionRecu');

// Evaluations (Grades)
$router->register('/evaluations/select_class', 'EvaluationController', 'selectClass');
$router->register('/evaluations/select_evaluation', 'EvaluationController', 'selectEvaluation');
$router->register('/evaluations/form', 'EvaluationController', 'showForm');
$router->register('/evaluations/save', 'EvaluationController', 'save');

// Paiements
// Obsolete payment routes, replaced by Inscription and Mensualite flows
// $router->register('/paiements', 'PaiementController', 'index');
// $router->register('/paiements/create', 'PaiementController', 'create');
// $router->register('/paiements/store', 'PaiementController', 'store');

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

// Card Template
$router->register('/modele-carte/edit', 'ModeleCarteController', 'edit');
$router->register('/carte/generer', 'CarteController', 'generer');

// Setup
$router->register('/setup', 'SetupController', 'index');
$router->register('/setup/choice', 'SetupController', 'processChoice');
$router->register('/setup/finish', 'SetupController', 'finish');

// Academic Years
$router->register('/annees-academiques', 'AnneeAcademiqueController', 'index');
$router->register('/annees-academiques/create', 'AnneeAcademiqueController', 'create');
$router->register('/annees-academiques/store', 'AnneeAcademiqueController', 'store');
$router->register('/annees-academiques/edit', 'AnneeAcademiqueController', 'edit');
$router->register('/annees-academiques/update', 'AnneeAcademiqueController', 'update');
$router->register('/annees-academiques/destroy', 'AnneeAcademiqueController', 'destroy');
$router->register('/annees-academiques/activate', 'AnneeAcademiqueController', 'activate');

// Timetable
$router->register('/emploi-du-temps', 'EmploiDuTempsController', 'index');
$router->register('/emploi-du-temps/create', 'EmploiDuTempsController', 'create');
$router->register('/contrats', 'TypeContratController', 'index');
$router->register('/contrats/create', 'TypeContratController', 'create');
$router->register('/contrats/store', 'TypeContratController', 'store');
$router->register('/contrats/edit', 'TypeContratController', 'edit');
$router->register('/contrats/update', 'TypeContratController', 'update');
$router->register('/contrats/destroy', 'TypeContratController', 'destroy');
$router->register('/emploi-du-temps/store', 'EmploiDuTempsController', 'store');
$router->register('/emploi-du-temps/destroy', 'EmploiDuTempsController', 'destroy');

// Bulletins (Report Cards)
$router->register('/bulletins', 'BulletinController', 'index');
$router->register('/bulletins/class_results', 'BulletinController', 'showClassResults');
$router->register('/bulletins/student', 'BulletinController', 'showStudentBulletin');
$router->register('/bulletins/appreciation/save', 'BulletinController', 'saveAppreciation');

// Bulletin Template Editor
$router->register('/modele-bulletin/edit', 'ModeleBulletinController', 'edit');
$router->register('/modele-bulletin/save', 'ModeleBulletinController', 'save');

// School Parameters
$router->register('/param-lycee/edit', 'ParamLyceeController', 'edit');
$router->register('/param-lycee/update', 'ParamLyceeController', 'update');
$router->register('/param-devoir/edit', 'ParamDevoirController', 'edit');
$router->register('/param-devoir/update', 'ParamDevoirController', 'update');
$router->register('/param-composition/edit', 'ParamCompositionController', 'edit');
$router->register('/param-composition/update', 'ParamCompositionController', 'update');

// Salaires
$router->register('/salaires', 'SalaireController', 'index');
$router->register('/salaires/create', 'SalaireController', 'create');
$router->register('/salaires/store', 'SalaireController', 'store');
$router->register('/salaires/fiche', 'SalaireController', 'genererFiche');

// Cahier de Texte
$router->register('/cahier-texte', 'CahierTexteController', 'index');
$router->register('/cahier-texte/create', 'CahierTexteController', 'create');
$router->register('/cahier-texte/store', 'CahierTexteController', 'store');
$router->register('/cahier-texte/destroy', 'CahierTexteController', 'destroy');


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
