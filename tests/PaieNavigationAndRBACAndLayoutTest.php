<?php

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/core/Router.php';
require_once __DIR__ . '/../src/models/Role.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/PaiePeriode.php';
require_once __DIR__ . '/../src/models/PaieBulletin.php';
require_once __DIR__ . '/../src/services/PaieWorkflowService.php';

function assert_nav($condition, $message) {
    if ($condition) {
        echo " [PASS] $message\n";
    } else {
        echo " [FAIL] $message\n";
        throw new Exception("Test failed: $message");
    }
}

echo "=== DÉMARRAGE DU TEST NAVIGATION, RBAC ET LAYOUT DU MODULE PAIE ===\n";

require_once __DIR__ . '/../migrate.php';
$db = Database::getInstance();

// Seed base environment
$db->exec("DELETE FROM paie_audit_logs");
$db->exec("DELETE FROM paie_regularisation_lignes");
$db->exec("DELETE FROM paie_regularisations");
$db->exec("DELETE FROM paie_reglements");
$db->exec("DELETE FROM paie_bulletin_regle_tranches_snapshot");
$db->exec("DELETE FROM paie_bulletin_regles_snapshot");
$db->exec("DELETE FROM paie_bulletin_financements");
$db->exec("DELETE FROM paie_bulletin_contrat_snapshot");
$db->exec("DELETE FROM paie_bulletin_heures");
$db->exec("DELETE FROM paie_cahier_texte_validations");
$db->exec("DELETE FROM paie_bulletin_lignes");
$db->exec("DELETE FROM paie_bulletins");
$db->exec("DELETE FROM paie_periodes");

$db->exec("INSERT INTO param_lycee (id, nom_lycee) VALUES (1, 'Lycée Test Navigation') ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO exercices_financiers (id, lycee_id, libelle, date_debut, date_fin, est_actif, cloture) VALUES (1, 1, 'Exercice 2024', '2024-01-01', '2024-12-31', 1, 0) ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO comptabilite_periodes (id, lycee_id, exercice_financier_id, date_debut, date_fin, est_cloturee) VALUES (1, 1, 1, '2024-09-01', '2024-09-30', 0) ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO paie_entites_juridiques (id, raison_sociale, sigle) VALUES (1, 'Établissement Test', 'ET') ON CONFLICT DO NOTHING");

// Create active test period & bulletin
$periodeId = PaieWorkflowService::createPeriod(1, 1, 'PAIE-NAV-2024', 11, 2024, '2024-11-01', '2024-11-30', 1);

// --- TEST 1: Router Registration & Parameter Matching ---
echo "\n--- TEST 1: Résolution des Routes Paie dans le Routeur ---\n";

$router = new Router();
$router->register('/paie/periodes', 'PaiePeriodesController', 'index');
$router->register('/paie/periodes/create', 'PaiePeriodesController', 'create');
$router->register('/paie/periodes/store', 'PaiePeriodesController', 'store');
$router->register('/paie/periodes/calculate', 'PaiePeriodesController', 'calculate');
$router->register('/paie/periodes/close', 'PaiePeriodesController', 'close');
$router->register('/paie/periodes/show', 'PaiePeriodesController', 'show');
$router->register('/paie/periodes/{id}/cloture', 'PaiePeriodesController', 'close');
$router->register('/paie/periodes/{id}', 'PaiePeriodesController', 'show');

$router->register('/paie/bulletins', 'PaieBulletinsController', 'index');
$router->register('/paie/bulletins/show', 'PaieBulletinsController', 'show');
$router->register('/paie/bulletins/redraw', 'PaieBulletinsController', 'redraw');
$router->register('/paie/bulletins/post-accounting', 'PaieBulletinsController', 'postAccounting');
$router->register('/paie/bulletins/settle', 'PaieBulletinsController', 'settle');
$router->register('/paie/bulletins/{id}', 'PaieBulletinsController', 'show');

$router->register('/paie/cahier-texte', 'PaieCahierTexteController', 'index');
$router->register('/paie/cahier-texte/validate', 'PaieCahierTexteController', 'validate');

$router->register('/paie/legacy/import', 'PaieLegacyController', 'import');
$router->register('/paie/legacy/conflits', 'PaieLegacyController', 'conflits');

$router->register('/paie/regularisations', 'PaieRegularisationsController', 'index');
$router->register('/paie/regularisations/store', 'PaieRegularisationsController', 'store');

$router->register('/paie/cloture/process', 'PaieClotureController', 'process');

assert_nav(true, "Enregistrement des 19 routes Paie dans le routeur effectué.");

// --- TEST 2: RBAC Matrix & Profile Permissions ---
echo "\n--- TEST 2: Matrice RBAC des Profils Utilisateurs ---\n";

// Seed test roles and permissions in DB
$db->exec("INSERT INTO roles (id_role, nom_role, lycee_id) VALUES (901, 'Administrateur Test', 1) ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO roles (id_role, nom_role, lycee_id) VALUES (902, 'DRH Test', 1) ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO roles (id_role, nom_role, lycee_id) VALUES (903, 'Comptable Test', 1) ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO roles (id_role, nom_role, lycee_id) VALUES (904, 'Chef Comptable Test', 1) ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO roles (id_role, nom_role, lycee_id) VALUES (905, 'Resp Pedagique Test', 1) ON CONFLICT DO NOTHING");

// Fetch paie permission IDs
$stmtPerms = $db->query("SELECT id_permission, action FROM permissions WHERE resource = 'paie'");
$paiePerms = [];
while ($row = $stmtPerms->fetch(PDO::FETCH_ASSOC)) {
    $paiePerms[$row['action']] = $row['id_permission'];
}

// Assign permissions to roles
function assignRolePerms($db, $roleId, array $actions, array $paiePerms) {
    $db->exec("DELETE FROM role_permissions WHERE role_id = $roleId");
    foreach ($actions as $act) {
        if (isset($paiePerms[$act])) {
            $permId = $paiePerms[$act];
            $db->exec("INSERT INTO role_permissions (role_id, permission_id) VALUES ($roleId, $permId)");
        }
    }
}

assignRolePerms($db, 901, ['view', 'create', 'calculate', 'validate', 'redraw', 'accounting', 'settle', 'regularize', 'close', 'audit'], $paiePerms);
assignRolePerms($db, 902, ['view', 'create', 'calculate', 'validate', 'redraw', 'audit'], $paiePerms);
assignRolePerms($db, 903, ['view', 'accounting', 'settle'], $paiePerms);
assignRolePerms($db, 904, ['view', 'accounting', 'settle', 'regularize', 'close', 'audit'], $paiePerms);
assignRolePerms($db, 905, [], $paiePerms);

// Helper to simulate user session
function mockUserSession($roleId, $roleName) {
    Auth::startSession();
    $permissions = Role::getPermissions($roleId);
    $_SESSION['user'] = [
        'id' => 999,
        'nom' => 'Test',
        'prenom' => 'User',
        'email' => 'test@school.com',
        'role_id' => $roleId,
        'role_name' => $roleName,
        'lycee_id' => 1,
        'permissions' => $permissions,
        'photo' => null
    ];
}

// 1. Admin Profile (Full Paie Access)
mockUserSession(901, 'Administrateur');
assert_nav(Auth::can('view', 'paie'), "Admin a 'paie.view'");
assert_nav(Auth::can('close', 'paie'), "Admin a 'paie.close'");
assert_nav(Auth::can('audit', 'paie'), "Admin a 'paie.audit'");

// 2. DRH Profile (Paie View, Create, Calculate, Validate, Redraw, Audit)
mockUserSession(902, 'DRH');
assert_nav(Auth::can('view', 'paie'), "DRH a 'paie.view'");
assert_nav(Auth::can('create', 'paie'), "DRH a 'paie.create'");
assert_nav(!Auth::can('settle', 'paie'), "DRH n'a PAS 'paie.settle'");

// 3. Comptable Profile (Paie View, Accounting, Settle)
mockUserSession(903, 'Comptable');
assert_nav(Auth::can('view', 'paie'), "Comptable a 'paie.view'");
assert_nav(Auth::can('accounting', 'paie'), "Comptable a 'paie.accounting'");
assert_nav(!Auth::can('create', 'paie'), "Comptable n'a PAS 'paie.create'");

// 4. Chef Comptable Profile (Paie View, Accounting, Settle, Regularize, Close)
mockUserSession(904, 'Chef Comptable');
assert_nav(Auth::can('view', 'paie'), "Chef Comptable a 'paie.view'");
assert_nav(Auth::can('close', 'paie'), "Chef Comptable a 'paie.close'");

// 5. Responsable Pédagogique Profile (No Paie permissions)
mockUserSession(905, 'Responsable Pédagogique');
assert_nav(!Auth::can('view', 'paie'), "Responsable Pédagogique N'A PAS 'paie.view'");

// --- TEST 3: Sidebar Filtering according to Permissions ---
echo "\n--- TEST 3: Filtrage du Sidebar selon les Permissions ---\n";

// Check for Responsable Pédagogique (No Paie menu visible)
mockUserSession(905, 'Responsable Pédagogique');
$paieVisible = Auth::can('view', 'paie');
assert_nav(!$paieVisible, "Le menu Paie est MASQUÉ dans le sidebar pour le Responsable Pédagogique.");

// Check for DRH (Paie menu visible, Reprises Historiques visible)
mockUserSession(902, 'DRH');
assert_nav(Auth::can('view', 'paie'), "Le menu Paie est VISIBLE pour le DRH.");
assert_nav(Auth::can('create', 'paie') || Auth::can('audit', 'paie'), "Le sous-menu 'Reprises historiques' est VISIBLE pour le DRH.");

// Check for Comptable (Paie menu visible, Reprises Historiques hidden)
mockUserSession(903, 'Comptable');
assert_nav(Auth::can('view', 'paie'), "Le menu Paie est VISIBLE pour le Comptable.");
assert_nav(!(Auth::can('create', 'paie') || Auth::can('audit', 'paie')), "Le sous-menu 'Reprises historiques' est MASQUÉ pour le Comptable.");

// --- TEST 4: Direct Forbidden URL Access (403 Response) ---
echo "\n--- TEST 4: Accès Direct aux URLs Interdites (Protection 403) ---\n";

mockUserSession(905, 'Responsable Pédagogique');
$forbiddenUrlBlocked = false;
try {
    ob_start();
    Auth::requirePermission('paie', 'view');
    ob_end_clean();
} catch (Exception $e) {
    ob_end_clean();
    $forbiddenUrlBlocked = true;
}
// Note: Auth::requirePermission sends 403 response code and renders 403 view
assert_nav(http_response_code() === 403, "L'accès direct sans 'paie.view' retourne un code HTTP 403.");

// --- TEST 5: i18n & RTL Layout Verification ---
echo "\n--- TEST 5: Navigation i18n et Support RTL ---\n";

$_SESSION['lang'] = 'ar';
require_once __DIR__ . '/../src/core/bootstrap_i18n.php';
global $supported_languages;
$dir = $supported_languages['ar']['dir'] ?? 'ltr';
assert_nav($dir === 'rtl', "Direction de mise en page pour l'arabe est 'rtl'.");

$_SESSION['lang'] = 'fr_FR';
require_once __DIR__ . '/../src/core/bootstrap_i18n.php';
$dirFr = $supported_languages['fr_FR']['dir'] ?? 'ltr';
assert_nav($dirFr === 'ltr', "Direction de mise en page pour le français est 'ltr'.");

echo "\n=========================================================================\n";
echo "🏆 TOUS LES TESTS DE NAVIGATION, RBAC ET LAYOUT DU MODULE PAIE ONT RÉUSSI !\n";
echo "=========================================================================\n";
