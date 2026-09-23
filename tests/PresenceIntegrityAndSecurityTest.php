<?php

require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/core/View.php';
require_once __DIR__ . '/../src/models/AnneeAcademique.php';
require_once __DIR__ . '/../src/models/Eleve.php';
require_once __DIR__ . '/../src/models/Presence.php';
require_once __DIR__ . '/../src/models/Classe.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/services/AuthorizationScopeService.php';
require_once __DIR__ . '/../src/core/CsrfService.php';
require_once __DIR__ . '/../src/controllers/PresenceController.php';
require_once __DIR__ . '/../db/migrations/20240115_28_fix_presences_integrity_and_security.php';

function assert_presence($condition, $message) {
    if ($condition) {
        echo " [PASS] $message\n";
    } else {
        echo " [FAIL] $message\n";
        throw new Exception("Test failed: $message");
    }
}

// Polyfill function _() if gettext is missing
if (!function_exists('_')) {
    function _($string) {
        return $string;
    }
}

echo "=== DÉMARRAGE SUITE DE TEST: PRÉSENCES INTÉGRITÉ, SÉCURITÉ & MULTI-TENANT ===\n";

$dbFile = '/tmp/test_presences_integrity.sqlite';
if (file_exists($dbFile)) {
    unlink($dbFile);
}

$pdo = new PDO('sqlite:' . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
Database::setInstance($pdo);

// Setup Schema in SQLite
$pdo->exec("CREATE TABLE param_lycee (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nom_lycee TEXT,
    actif INTEGER DEFAULT 1
)");

$pdo->exec("CREATE TABLE annees_academiques (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    libelle TEXT,
    date_debut DATE NULL,
    date_fin DATE NULL,
    est_active INTEGER DEFAULT 0
)");

$pdo->exec("CREATE TABLE cycles (
    id_cycle INTEGER PRIMARY KEY AUTOINCREMENT,
    nom_cycle TEXT
)");

$pdo->exec("CREATE TABLE classes (
    id_classe INTEGER PRIMARY KEY AUTOINCREMENT,
    lycee_id INTEGER,
    cycle_id INTEGER,
    niveau TEXT,
    serie TEXT,
    numero INTEGER
)");

$pdo->exec("CREATE TABLE eleves (
    id_eleve INTEGER PRIMARY KEY AUTOINCREMENT,
    lycee_id INTEGER,
    nom TEXT,
    prenom TEXT,
    identifiant_public TEXT,
    statut TEXT DEFAULT 'actif'
)");

$pdo->exec("CREATE TABLE etudes (
    id_etude INTEGER PRIMARY KEY AUTOINCREMENT,
    eleve_id INTEGER,
    classe_id INTEGER,
    lycee_id INTEGER,
    annee_academique_id INTEGER,
    is_active INTEGER DEFAULT 1,
    status TEXT DEFAULT 'active'
)");

$pdo->exec("CREATE TABLE matieres (
    id_matiere INTEGER PRIMARY KEY AUTOINCREMENT,
    nom_matiere TEXT,
    code TEXT,
    actif INTEGER DEFAULT 1
)");

$pdo->exec("CREATE TABLE utilisateurs (
    id_user INTEGER PRIMARY KEY AUTOINCREMENT,
    nom TEXT,
    prenom TEXT,
    role_id INTEGER,
    lycee_id INTEGER,
    actif INTEGER DEFAULT 1,
    auth_version INTEGER DEFAULT 1
)");

$pdo->exec("CREATE TABLE roles (
    id_role INTEGER PRIMARY KEY AUTOINCREMENT,
    nom_role TEXT
)");

$pdo->exec("CREATE TABLE role_permissions (
    role_id INTEGER,
    permission_id INTEGER
)");

$pdo->exec("CREATE TABLE permissions (
    id_permission INTEGER PRIMARY KEY AUTOINCREMENT,
    resource TEXT,
    action TEXT
)");

$pdo->exec("CREATE TABLE affectations_pedagogiques (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    enseignant_id INTEGER NOT NULL,
    classe_id INTEGER NOT NULL,
    matiere_id INTEGER NOT NULL,
    annee_academique_id INTEGER NOT NULL,
    date_debut DATE NULL,
    date_fin DATE NULL,
    statut TEXT DEFAULT 'actif'
)");

$pdo->exec("CREATE TABLE personnel_cycles_assignments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    personnel_id INTEGER NOT NULL,
    cycle_id INTEGER NOT NULL,
    date_debut DATE NOT NULL,
    date_fin DATE NULL,
    actif INTEGER DEFAULT 1
)");

$pdo->exec("CREATE TABLE notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    lycee_id INTEGER,
    role TEXT,
    message TEXT,
    type TEXT,
    lien TEXT,
    is_read INTEGER DEFAULT 0,
    est_lu INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    cree_le TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$pdo->exec("CREATE TABLE presences (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    eleve_id INTEGER NOT NULL,
    classe_id INTEGER NOT NULL,
    matiere_id INTEGER NULL,
    enseignant_id INTEGER NOT NULL,
    annee_academique_id INTEGER NOT NULL,
    lycee_id INTEGER NOT NULL,
    date_presence DATE NOT NULL,
    statut TEXT NOT NULL DEFAULT 'present',
    commentaire TEXT NULL,
    cree_le DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Seed initial data
$pdo->exec("INSERT INTO param_lycee (nom_lycee) VALUES ('Lycée A')");
$lyceeA = $pdo->lastInsertId();

$pdo->exec("INSERT INTO param_lycee (nom_lycee) VALUES ('Lycée B')");
$lyceeB = $pdo->lastInsertId();

$pdo->exec("INSERT INTO annees_academiques (libelle, est_active) VALUES ('2023-2024 Old', 0)");
$anneeOld = $pdo->lastInsertId();

$pdo->exec("INSERT INTO annees_academiques (libelle, est_active) VALUES ('2024-2025 Active', 1)");
$anneeActive = $pdo->lastInsertId();

$pdo->exec("INSERT INTO cycles (nom_cycle) VALUES ('Second Cycle')");
$cycleId = $pdo->lastInsertId();

$pdo->exec("INSERT INTO classes (lycee_id, cycle_id, niveau, numero) VALUES ($lyceeA, $cycleId, '2nde', 1)");
$classeA1 = $pdo->lastInsertId();

$pdo->exec("INSERT INTO classes (lycee_id, cycle_id, niveau, numero) VALUES ($lyceeA, $cycleId, '1ere', 1)");
$classeA2 = $pdo->lastInsertId();

$pdo->exec("INSERT INTO classes (lycee_id, cycle_id, niveau, numero) VALUES ($lyceeB, $cycleId, 'Tle', 1)");
$classeB1 = $pdo->lastInsertId();

$pdo->exec("INSERT INTO matieres (nom_matiere, code) VALUES ('Mathématiques', 'MATH')");
$matiere1 = $pdo->lastInsertId();

// Students
// Student 1: Active in 2024-2025 in Classe A1
$pdo->exec("INSERT INTO eleves (lycee_id, nom, prenom, identifiant_public, statut) VALUES ($lyceeA, 'Kouassi', 'Jean', 'MAT-001', 'actif')");
$eleve1 = $pdo->lastInsertId();
$pdo->exec("INSERT INTO etudes (eleve_id, classe_id, lycee_id, annee_academique_id, is_active, status) VALUES ($eleve1, $classeA1, $lyceeA, $anneeActive, 1, 'active')");

// Student 2: Active in 2024-2025 in Classe A1
$pdo->exec("INSERT INTO eleves (lycee_id, nom, prenom, identifiant_public, statut) VALUES ($lyceeA, 'Diallo', 'Awa', 'MAT-002', 'actif')");
$eleve2 = $pdo->lastInsertId();
$pdo->exec("INSERT INTO etudes (eleve_id, classe_id, lycee_id, annee_academique_id, is_active, status) VALUES ($eleve2, $classeA1, $lyceeA, $anneeActive, 1, 'active')");

// Student 3: Old student from 2023-2024 (should BE EXCLUDED from 2024-2025 roster)
$pdo->exec("INSERT INTO eleves (lycee_id, nom, prenom, identifiant_public, statut) VALUES ($lyceeA, 'Traore', 'Moussa', 'MAT-999', 'inactif')");
$eleveOld = $pdo->lastInsertId();
$pdo->exec("INSERT INTO etudes (eleve_id, classe_id, lycee_id, annee_academique_id, is_active, status) VALUES ($eleveOld, $classeA1, $lyceeA, $anneeOld, 1, 'quitte')");

// Roles & Permissions
$pdo->exec("INSERT INTO roles (nom_role) VALUES ('enseignant')");
$roleEns = $pdo->lastInsertId();

$pdo->exec("INSERT INTO permissions (resource, action) VALUES ('presence', 'manage')");
$permManage = $pdo->lastInsertId();
$pdo->exec("INSERT INTO permissions (resource, action) VALUES ('presence', 'view')");
$permView = $pdo->lastInsertId();

$pdo->exec("INSERT INTO role_permissions (role_id, permission_id) VALUES ($roleEns, $permManage)");
$pdo->exec("INSERT INTO role_permissions (role_id, permission_id) VALUES ($roleEns, $permView)");

// Users
// Teacher in Lycée A assigned ONLY to Classe A1
$pdo->exec("INSERT INTO utilisateurs (nom, prenom, role_id, lycee_id) VALUES ('Prof', 'A1', $roleEns, $lyceeA)");
$teacherA1 = $pdo->lastInsertId();

$pdo->exec("INSERT INTO affectations_pedagogiques (enseignant_id, classe_id, matiere_id, annee_academique_id, statut) VALUES ($teacherA1, $classeA1, $matiere1, $anneeActive, 'actif')");
$pdo->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, actif) VALUES ($teacherA1, $cycleId, '2020-01-01', 1)");

// Execute Migration 28
migrate_28($pdo);

// TEST 1: Roster Extraction (Phase 6 Alignment)
echo "\n--- TEST 1: Verification of Eleve::findActiveRosterForClass ---\n";
$roster = Eleve::findActiveRosterForClass($classeA1, $anneeActive, $lyceeA);
assert_presence(count($roster) === 2, "Seuls 2 élèves actifs de l'année 2024-2025 sont extraits pour l'appel.");
$rosterIds = array_column($roster, 'id_eleve');
assert_presence(in_array($eleve1, $rosterIds) && in_array($eleve2, $rosterIds), "Les élèves actifs Kouassi et Diallo sont présents dans la liste.");
assert_presence(!in_array($eleveOld, $rosterIds), "L'ancien élève Traore de l'année précédente est strictement exclu de la feuille d'appel.");

// TEST 2: Anti-Duplication via Presence::saveAll()
echo "\n--- TEST 2: Verification of Anti-Duplication on Attendance Submit ---\n";

$data1 = [
    'classe_id' => $classeA1,
    'date_presence' => '2024-09-23',
    'enseignant_id' => $teacherA1,
    'annee_academique_id' => $anneeActive,
    'lycee_id' => $lyceeA,
    'presences' => [
        $eleve1 => ['statut' => 'present', 'commentaire' => 'Premier appel'],
        $eleve2 => ['statut' => 'absent', 'commentaire' => 'Non justifié']
    ]
];

Presence::saveAll($data1);

$countStmt = $pdo->query("SELECT COUNT(*) FROM presences");
$totalRowsFirst = (int)$countStmt->fetchColumn();
assert_presence($totalRowsFirst === 2, "2 enregistrements de présence créés au premier appel.");

// Re-submit identical attendance sheet for the same date & class
$data2 = [
    'classe_id' => $classeA1,
    'date_presence' => '2024-09-23',
    'enseignant_id' => $teacherA1,
    'annee_academique_id' => $anneeActive,
    'lycee_id' => $lyceeA,
    'presences' => [
        $eleve1 => ['statut' => 'retard', 'commentaire' => 'Arrivé 10min plus tard'],
        $eleve2 => ['statut' => 'justifie', 'commentaire' => 'Motif médical']
    ]
];

Presence::saveAll($data2);

$countStmt2 = $pdo->query("SELECT COUNT(*) FROM presences");
$totalRowsSecond = (int)$countStmt2->fetchColumn();
assert_presence($totalRowsSecond === 2, "Aucun doublon créé lors de la ré-soumission de l'appel (total reste égal à 2).");

$p1 = Presence::findByClassAndDate($classeA1, '2024-09-23', null, $anneeActive, $lyceeA);
assert_presence(count($p1) === 2, "2 enregistrements retrouvés par findByClassAndDate.");
$pMap = [];
foreach ($p1 as $p) {
    $pMap[$p['eleve_id']] = $p;
}
assert_presence($pMap[$eleve1]['statut'] === 'retard', "Le statut de l'élève 1 a été correctement mis à jour en 'retard'.");
assert_presence($pMap[$eleve2]['statut'] === 'justifie', "Le statut de l'élève 2 a été correctement mis à jour en 'justifie'.");

// TEST 3: Multi-Tenant & Teacher Pedagogical Scope Protection (IDOR & Usurpation)
echo "\n--- TEST 3: Verification of Multi-Tenant IDOR and Teacher Scope Protection ---\n";

Auth::setSessionContext([
    'id' => $teacherA1,
    'id_user' => $teacherA1,
    'nom' => 'Prof',
    'prenom' => 'A1',
    'role_id' => $roleEns,
    'lycee_id' => $lyceeA
]);

$controller = new PresenceController();

// 3.1 Authorized Access: Teacher A1 accesses assigned Class A1
ob_start();
try {
    $controller->gerer($classeA1);
    $htmlAuthorized = ob_get_clean();
    assert_presence(strpos($htmlAuthorized, 'Gestion des Présences') !== false, "L'enseignant accède avec succès à sa classe attribuée (Classe A1).");
} catch (Exception $e) {
    ob_end_clean();
    assert_presence(false, "L'enseignant n'a pas pu accéder à sa classe attribuée : " . $e->getMessage());
}

// 3.2 Teacher Scope Violation: Teacher A1 attempts to access unassigned Class A2 in same school (Lycée A)
$classA2Denied = false;
ob_start();
try {
    $controller->gerer($classeAA2 ?? $classeA2);
    ob_end_clean();
} catch (Exception $e) {
    ob_end_clean();
}
if (http_response_code() === 403) {
    $classA2Denied = true;
}
assert_presence($classA2Denied, "Refus 403 Forbidden lorsqu'un enseignant tente d'accéder à une classe non attribuée du même lycée (Classe A2).");

// 3.3 Cross-Tenant IDOR Violation: Teacher A1 attempts to access Class B1 in another school (Lycée B)
http_response_code(200); // Reset response code
$classB1Denied = false;
ob_start();
try {
    $controller->gerer($classeB1);
    ob_end_clean();
} catch (Exception $e) {
    ob_end_clean();
}
if (http_response_code() === 403) {
    $classB1Denied = true;
}
assert_presence($classB1Denied, "Refus 403 Forbidden lors d'une tentative d'accès Cross-Tenant à une classe du Lycée B (Classe B1).");

echo "\n=== TOUS LES TESTS D'INTÉGRITÉ, DE SÉCURITÉ ET DE MULTI-TENANT ONT RÉUSSI AVEC SUCCÈS ===\n";
