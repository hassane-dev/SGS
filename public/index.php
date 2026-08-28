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
            if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
                $sql = preg_replace('/ON DUPLICATE KEY UPDATE[^;]+;/i', ';', $sql);
            }
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

// Series CRUD
$router->register('/series', 'SerieController', 'index');
$router->register('/series/store', 'SerieController', 'store');
$router->register('/series/update', 'SerieController', 'update');
$router->register('/series/destroy', 'SerieController', 'destroy');

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
$router->register('/classes/updateParams', 'ClasseController', 'updateParams');


// API routes for dynamic dropdowns
$router->register('/classes/get-niveaux', 'ClasseController', 'getNiveauxForCycle');
$router->register('/classes/get-series', 'ClasseController', 'getSeriesForNiveau');
$router->register('/classes/get-numeros', 'ClasseController', 'getNumerosForClasse');
$router->register('/classes/find-id', 'ClasseController', 'findClassId');

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
$router->register('/evaluations/settings/create', 'ParametresEvaluationController', 'create');
$router->register('/evaluations/settings/store', 'ParametresEvaluationController', 'store');
$router->register('/evaluations/settings/delete', 'ParametresEvaluationController', 'delete');
$router->register('/evaluations/settings/legacy', 'ParametresEvaluationController', 'legacySettings');
$router->register('/evaluations/settings/save-legacy', 'ParametresEvaluationController', 'saveLegacy');
$router->register('/evaluations/deblocage', 'DeblocageController', 'index');
$router->register('/evaluations/deblocage/create', 'DeblocageController', 'create');
$router->register('/evaluations/deblocage/store', 'DeblocageController', 'store');
$router->register('/evaluations/deblocage/delete', 'DeblocageController', 'delete');

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
$router->register('/profile/update-settings', 'UserController', 'updateSettings');
$router->register('/profile/update-photo', 'UserController', 'updatePhoto');

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
$router->register('/eleves/parametres-financiers', 'EleveController', 'parametresFinanciers');
$router->register('/eleves/parametres-financiers/update', 'EleveController', 'updateParametresFinanciers');

// Inscriptions / Reinscriptions redirects to student creation flow
$router->register('/inscriptions', 'EleveController', 'create');
$router->register('/reinscriptions', 'EleveController', 'archives');
$router->register('/reinscription', 'EleveController', 'archives');

// Exercices et Périodes Comptables
$router->register('/comptabilite/exercices', 'ExerciceFinancierController', 'index');
$router->register('/comptabilite/exercices/create', 'ExerciceFinancierController', 'create');
$router->register('/comptabilite/exercices/store', 'ExerciceFinancierController', 'store');
$router->register('/comptabilite/exercices/{id}/activate', 'ExerciceFinancierController', 'activate');
$router->register('/comptabilite/exercices/{id}/close', 'ExerciceFinancierController', 'close');

$router->register('/comptabilite/periodes', 'ComptabilitePeriodeController', 'index');
$router->register('/comptabilite/periodes/create', 'ComptabilitePeriodeController', 'create');
$router->register('/comptabilite/periodes/store', 'ComptabilitePeriodeController', 'store');
$router->register('/comptabilite/periodes/generate', 'ComptabilitePeriodeController', 'generate');
$router->register('/comptabilite/periodes/{id}/cloture', 'ComptabilitePeriodeController', 'close');
$router->register('/comptabilite/periodes/{id}/reouvrir', 'ComptabilitePeriodeController', 'reopen');

// Gestion des Dépenses (Phase 3)
$router->register('/depenses', 'DepenseController', 'index');
$router->register('/depenses/create', 'DepenseController', 'create');
$router->register('/depenses/store', 'DepenseController', 'store');
$router->register('/depenses/validate/{id}', 'DepenseController', 'validate');
$router->register('/depenses/vote/{id}', 'DepenseController', 'vote');
$router->register('/depenses/pay/{id}', 'DepenseController', 'pay');
$router->register('/depenses/process-payment/{id}', 'DepenseController', 'processPayment');
$router->register('/depenses/cancel/{id}', 'DepenseController', 'cancel');
$router->register('/depenses/validation', 'DepenseController', 'validation');
$router->register('/depenses/payments', 'DepenseController', 'payments');
$router->register('/depenses/history', 'DepenseController', 'history');
$router->register('/depenses/categories', 'DepenseController', 'categories');
$router->register('/depenses/categories/store', 'DepenseController', 'storeCategory');
$router->register('/depenses/categories/update', 'DepenseController', 'updateCategory');
$router->register('/depenses/categories/delete', 'DepenseController', 'deleteCategory');
$router->register('/depenses/centres-couts', 'DepenseController', 'centresCouts');
$router->register('/depenses/centres-couts/store', 'DepenseController', 'storeCentreCout');
$router->register('/depenses/centres-couts/update', 'DepenseController', 'updateCentreCout');
$router->register('/depenses/centres-couts/delete', 'DepenseController', 'deleteCentreCout');
$router->register('/depenses/beneficiaires', 'DepenseController', 'beneficiaires');
$router->register('/depenses/beneficiaires/store', 'DepenseController', 'storeBeneficiaire');
$router->register('/depenses/beneficiaires/update', 'DepenseController', 'updateBeneficiaire');
$router->register('/depenses/beneficiaires/delete', 'DepenseController', 'deleteBeneficiaire');

// Gestion Budgétaire (Phase 4)
$router->register('/budgets', 'BudgetController', 'index');
$router->register('/budgets/create', 'BudgetController', 'create');
$router->register('/budgets/store', 'BudgetController', 'store');
$router->register('/budgets/show/{id}', 'BudgetController', 'show');
$router->register('/budgets/lines/store', 'BudgetController', 'storeLine');
$router->register('/budgets/lines/delete', 'BudgetController', 'deleteLine');
$router->register('/budgets/submit/{id}', 'BudgetController', 'submit');
$router->register('/budgets/approve/{id}', 'BudgetController', 'approve');
$router->register('/budgets/close/{id}', 'BudgetController', 'close');
$router->register('/budgets/adjustment', 'BudgetController', 'adjustment');
$router->register('/budgets/adjustment/store', 'BudgetController', 'processAdjustment');
$router->register('/budgets/rebuild/{id}', 'BudgetController', 'rebuild');
$router->register('/budgets/report/{id}', 'BudgetController', 'report');
$router->register('/budgets/report', 'BudgetController', 'reportGlobal');
$router->register('/budgets/engagements', 'BudgetController', 'engagements');

// Gestion de la Trésorerie (Phase 2.1 & 2.2)
$router->register('/treasury/sessions', 'SessionCaisseController', 'index');
$router->register('/treasury/sessions/open', 'SessionCaisseController', 'open');
$router->register('/treasury/sessions/show/{id}', 'SessionCaisseController', 'show');
$router->register('/treasury/sessions/close', 'SessionCaisseController', 'close');
$router->register('/treasury/sessions/approve', 'SessionCaisseController', 'approve');

// Comptes Financiers (Phase 1)
$router->register('/comptes-financiers', 'CompteFinancierController', 'index');
$router->register('/comptes-financiers/create', 'CompteFinancierController', 'create');
$router->register('/comptes-financiers/store', 'CompteFinancierController', 'store');
$router->register('/comptes-financiers/edit/{id}', 'CompteFinancierController', 'edit');
$router->register('/comptes-financiers/update', 'CompteFinancierController', 'update');
$router->register('/comptes-financiers/destroy/{id}', 'CompteFinancierController', 'destroy');

// Référentiel Comptes Comptables
$router->register('/comptes-comptables', 'CompteComptableController', 'index');
$router->register('/comptes-comptables/create', 'CompteComptableController', 'create');
$router->register('/comptes-comptables/store', 'CompteComptableController', 'store');
$router->register('/comptes-comptables/edit/{id}', 'CompteComptableController', 'edit');
$router->register('/comptes-comptables/update', 'CompteComptableController', 'update');
$router->register('/comptes-comptables/toggle/{id}', 'CompteComptableController', 'toggleActive');
$router->register('/comptes-comptables/destroy/{id}', 'CompteComptableController', 'destroy');

// Gestion des Achats & Fournisseurs (Phase 7)
$router->register('/achats/fournisseurs', 'AchatController', 'listFournisseurs');
$router->register('/achats/fournisseurs/create', 'AchatController', 'createFournisseur');
$router->register('/achats/fournisseurs/show', 'AchatController', 'showFournisseur');
$router->register('/achats/fournisseurs/edit', 'AchatController', 'editFournisseur');
$router->register('/achats/fournisseurs/toggle', 'AchatController', 'toggleFournisseur');
$router->register('/achats/fournisseurs/delete', 'AchatController', 'deleteFournisseur');
$router->register('/achats/categories', 'AchatController', 'listCategories');
$router->register('/achats/categories/create', 'AchatController', 'createCategory');
$router->register('/achats/categories/edit', 'AchatController', 'editCategory');
$router->register('/achats/categories/delete', 'AchatController', 'deleteCategory');
$router->register('/achats/articles', 'AchatController', 'listArticles');
$router->register('/achats/articles/create', 'AchatController', 'createArticle');
$router->register('/achats/articles/edit', 'AchatController', 'editArticle');
$router->register('/achats/articles/delete', 'AchatController', 'deleteArticle');
$router->register('/achats/demandes', 'AchatController', 'listDemandes');
$router->register('/achats/demandes/create', 'AchatController', 'createDemande');
$router->register('/achats/demandes/approve', 'AchatController', 'approveDemande');
$router->register('/achats/commandes', 'AchatController', 'listCommandes');
$router->register('/achats/commandes/create', 'AchatController', 'createCommande');
$router->register('/achats/commandes/show', 'AchatController', 'showCommande');
$router->register('/achats/receptions', 'AchatController', 'listReceptions');
$router->register('/achats/receptions/create', 'AchatController', 'createReception');
$router->register('/achats/receptions/show', 'AchatController', 'showReception');
$router->register('/achats/factures', 'AchatController', 'listFactures');
$router->register('/achats/factures/rapprochement', 'AchatController', 'matchFacture');
$router->register('/achats/factures/pay', 'AchatController', 'payFacture');
$router->register('/achats/avoirs/create', 'AchatController', 'createAvoir');

// Politique Financière, Contrôle, Journal & Rapports (Phase 1 Integration)
$router->register('/finance/policy/edit', 'FinancePolicyController', 'edit');
$router->register('/finance/policy/update', 'FinancePolicyController', 'update');
$router->register('/finance/policy', 'FinancePolicyController', 'edit');
$router->register('/finance/control', 'ControleFinancierController', 'index');
$router->register('/journal', 'JournalComptableController', 'index');
$router->register('/grand-livre', 'JournalComptableController', 'grandLivre');
$router->register('/balance', 'JournalComptableController', 'balance');
$router->register('/reports/financial', 'RapportFinancierController', 'index');

// Phase 9 - Reporting Décisionnel SGS
$router->register('/reporting', 'ReportingController', 'dashboard');
$router->register('/reporting/kpis', 'ReportingController', 'kpis');
$router->register('/reporting/comparaison', 'ReportingController', 'comparaison');
$router->register('/reporting/analyse', 'ReportingController', 'analyse');
$router->register('/reporting/previsions', 'ReportingController', 'previsions');
$router->register('/reporting/export', 'ReportingController', 'export');
$router->register('/reporting/threshold/save', 'ReportingController', 'save_threshold');
$router->register('/reporting/snapshot/save', 'ReportingController', 'generate_snapshot_manually');

// Frais (Fee management)
$router->register('/frais', 'FraisController', 'index');
$router->register('/frais/create', 'FraisController', 'create');
$router->register('/frais/store', 'FraisController', 'store');
$router->register('/frais/get-niveaux', 'FraisController', 'getNiveaux');
$router->register('/frais/get-series', 'FraisController', 'getSeries');

// Comptable / Paiements (Unifié)
$router->register('/paiements', 'PaiementController', 'index');
$router->register('/paiements/pending', 'PaiementController', 'listPending');
$router->register('/paiements/show/{eleveId}', 'PaiementController', 'show');
$router->register('/paiements/regulariser-inscription/{eleveId}', 'PaiementController', 'regulariserInscription');
$router->register('/paiements/process-payment/{eleveId}', 'PaiementController', 'processPayment');
$router->register('/paiements/restes', 'PaiementController', 'restes');
$router->register('/paiements/restes/class/{classeId}', 'PaiementController', 'classRestes');
$router->register('/paiements/regler/{eleveId}', 'PaiementController', 'regler');
$router->register('/paiements/historique', 'PaiementController', 'historique');
$router->register('/paiements/recus', 'PaiementController', 'recus');
$router->register('/paiements/rapports', 'PaiementController', 'rapports');
$router->register('/paiements/controle', 'PaiementController', 'controle');
$router->register('/paiements/journal', 'PaiementController', 'journal');
$router->register('/paiements/annuler-recu', 'PaiementController', 'annulerRecu');
$router->register('/paiements/rembourser', 'PaiementController', 'rembourser');
$router->register('/annees-academiques/toggle-cloture', 'AnneeAcademiqueController', 'toggleCloture');

// Mensualités
$router->register('/mensualites', 'MensualiteController', 'index');
$router->register('/mensualites/class/{classeId}', 'MensualiteController', 'classDashboard');
$router->register('/mensualites/pay/{eleveId}', 'MensualiteController', 'pay');

// Recus (Receipts)
$router->register('/recu/inscription', 'RecuController', 'showInscriptionRecu');
$router->register('/recu/print', 'RecuController', 'print');

// Evaluations (Grades)
$router->register('/evaluations/select_class', 'EvaluationController', 'selectClass');
$router->register('/evaluations/select_evaluation', 'EvaluationController', 'selectEvaluation');
$router->register('/evaluations/form', 'EvaluationController', 'showForm');
$router->register('/evaluations/save', 'EvaluationController', 'save');
$router->register('/notes/saisir/{classe_id}/{matiere_id}', 'EvaluationController', 'directSaisie');



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
$router->register('/boutique/recu', 'BoutiqueAchatController', 'recu');

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

// Presence
$router->register('/presences/gerer/{classe_id}', 'PresenceController', 'gerer');
$router->register('/presences/store', 'PresenceController', 'store');

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
$router->register('/settings/politique-financiere', 'PolitiqueFinanciereController', 'edit');
$router->register('/settings/politique-financiere/update', 'PolitiqueFinanciereController', 'update');
$router->register('/param-general/edit', 'ParamGeneralController', 'edit');
$router->register('/param-general/update', 'ParamGeneralController', 'update');
$router->register('/param-devoir', 'ParamDevoirController', 'edit');
$router->register('/param-devoir/edit', 'ParamDevoirController', 'edit');
$router->register('/param-devoir/update', 'ParamDevoirController', 'update');
$router->register('/param-composition', 'ParamCompositionController', 'edit');
$router->register('/param-composition/edit', 'ParamCompositionController', 'edit');
$router->register('/param-composition/update', 'ParamCompositionController', 'update');

// Affectations Pédagogiques Routes
$router->register('/affectations-pedagogiques', 'AffectationPedagogiqueController', 'index');
$router->register('/affectations-pedagogiques/create', 'AffectationPedagogiqueController', 'create');
$router->register('/affectations-pedagogiques/store', 'AffectationPedagogiqueController', 'store');
$router->register('/affectations-pedagogiques/edit', 'AffectationPedagogiqueController', 'edit');
$router->register('/affectations-pedagogiques/update', 'AffectationPedagogiqueController', 'update');
$router->register('/affectations-pedagogiques/suspend', 'AffectationPedagogiqueController', 'suspend');
$router->register('/affectations-pedagogiques/terminate', 'AffectationPedagogiqueController', 'terminate');
$router->register('/affectations-pedagogiques/history', 'AffectationPedagogiqueController', 'history');
$router->register('/affectations-pedagogiques/get-niveaux', 'AffectationPedagogiqueController', 'getNiveaux');
$router->register('/affectations-pedagogiques/get-series', 'AffectationPedagogiqueController', 'getSeries');
$router->register('/affectations-pedagogiques/get-numeros', 'AffectationPedagogiqueController', 'getNumeros');
$router->register('/affectations-pedagogiques/get-classe-id', 'AffectationPedagogiqueController', 'getClasseId');
$router->register('/affectations-pedagogiques/get-matieres', 'AffectationPedagogiqueController', 'getMatieres');
$router->register('/affectations-pedagogiques/get-enseignants', 'AffectationPedagogiqueController', 'getEnseignants');
$router->register('/affectations-pedagogiques/reactivate', 'AffectationPedagogiqueController', 'reactivate');
$router->register('/affectations-pedagogiques/replace', 'AffectationPedagogiqueController', 'replace');

// DRH - Direction des Ressources Humaines
$router->register('/drh', 'PersonnelController', 'index');
$router->register('/drh/dashboard', 'PersonnelController', 'dashboard');
$router->register('/drh/show', 'PersonnelController', 'show');
$router->register('/drh/create', 'PersonnelController', 'create');
$router->register('/drh/store', 'PersonnelController', 'store');
$router->register('/drh/edit', 'PersonnelController', 'edit');
$router->register('/drh/update', 'PersonnelController', 'update');
$router->register('/drh/update-status', 'PersonnelController', 'updateStatus');
$router->register('/drh/export', 'PersonnelController', 'export');

// DRH Sub-resource controllers
$router->register('/drh/assignments/store', 'PersonnelAssignmentController', 'store');
$router->register('/drh/assignments/delete', 'PersonnelAssignmentController', 'delete');
$router->register('/drh/contracts/store', 'PersonnelContractController', 'store');
$router->register('/drh/contracts/cancel', 'PersonnelContractController', 'cancel');
$router->register('/drh/contracts/details', 'PersonnelContractController', 'details');
$router->register('/drh/documents/store', 'PersonnelDocumentController', 'store');
$router->register('/drh/documents/download', 'PersonnelDocumentController', 'download');
$router->register('/drh/documents/delete', 'PersonnelDocumentController', 'delete');

// Salaires (Legacy)
$router->register('/salaires', 'SalaireController', 'index');
$router->register('/salaires/create', 'SalaireController', 'create');
$router->register('/salaires/store', 'SalaireController', 'store');
$router->register('/salaires/fiche', 'SalaireController', 'genererFiche');

// Lot 2.1 - Moteur de Paie
$router->register('/paie/periodes', 'PaiePeriodesController', 'index');
$router->register('/paie/periodes/create', 'PaiePeriodesController', 'create');
$router->register('/paie/periodes/store', 'PaiePeriodesController', 'store');
$router->register('/paie/periodes/calculate', 'PaiePeriodesController', 'calculate');
$router->register('/paie/periodes/close', 'PaiePeriodesController', 'close');
$router->register('/paie/periodes/show', 'PaiePeriodesController', 'show');
$router->register('/paie/periodes/{id}/cloture', 'PaiePeriodesController', 'close');
$router->register('/paie/periodes/{id}', 'PaiePeriodesController', 'show');

$router->register('/paie/bulletins', 'PaieBulletinsController', 'index');
$router->register('/paie/bulletins/prepare', 'PaieBulletinsController', 'prepare');
$router->register('/paie/bulletins/preview', 'PaieBulletinsController', 'preview');
$router->register('/paie/bulletins/calculate', 'PaieBulletinsController', 'calculate');
$router->register('/paie/bulletins/generate-individual', 'PaieBulletinsController', 'generateIndividual');
$router->register('/paie/bulletins/show', 'PaieBulletinsController', 'show');
$router->register('/paie/bulletins/redraw', 'PaieBulletinsController', 'redraw');
$router->register('/paie/bulletins/post-accounting', 'PaieBulletinsController', 'postAccounting');
$router->register('/paie/bulletins/settle', 'PaieBulletinsController', 'settle');
$router->register('/paie/bulletins/{id}', 'PaieBulletinsController', 'show');

$router->register('/paie/cahier-texte', 'PaieCahierTexteController', 'index');
$router->register('/paie/cahier-texte/validate', 'PaieCahierTexteController', 'validate');
$router->register('/paie/cahier-texte/bulk-validate', 'PaieCahierTexteController', 'bulkValidate');

$router->register('/paie/legacy/import', 'PaieLegacyController', 'import');
$router->register('/paie/legacy/conflits', 'PaieLegacyController', 'conflits');

$router->register('/paie/regularisations', 'PaieRegularisationsController', 'index');
$router->register('/paie/regularisations/store', 'PaieRegularisationsController', 'store');

$router->register('/paie/cloture/process', 'PaieClotureController', 'process');

// Cahier de Texte
$router->register('/cahier-texte', 'CahierTexteController', 'index');
$router->register('/cahier-texte/create', 'CahierTexteController', 'create');
$router->register('/cahier-texte/store', 'CahierTexteController', 'store');
$router->register('/cahier-texte/edit', 'CahierTexteController', 'edit');
$router->register('/cahier-texte/update', 'CahierTexteController', 'update');
$router->register('/cahier-texte/destroy', 'CahierTexteController', 'destroy');
$router->register('/cahier-texte/{classe_id}/{matiere_id}', 'CahierTexteController', 'directCreate');


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
if ($uri !== '/' && substr($uri, -1) === '/') {
    $uri = rtrim($uri, '/');
}


// Dispatch the router
try {
    $router->dispatch($uri);
} catch (Exception $e) {
    // Basic error handling
    echo "An error occurred: " . $e->getMessage();
}

?>
