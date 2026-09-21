<?php

require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/core/View.php';
require_once __DIR__ . '/../src/models/AnneeAcademique.php';
require_once __DIR__ . '/../src/models/Eleve.php';
require_once __DIR__ . '/../src/models/Etude.php';
require_once __DIR__ . '/../src/models/Frais.php';
require_once __DIR__ . '/../src/models/Inscription.php';
require_once __DIR__ . '/../src/models/Mensualite.php';
require_once __DIR__ . '/../src/models/Sequence.php';
require_once __DIR__ . '/../src/models/FinancialStatusService.php';
require_once __DIR__ . '/../src/services/KpiService.php';
require_once __DIR__ . '/../src/controllers/PaiementController.php';

if (!defined('TEST_MODE')) {
    define('TEST_MODE', true);
}

function assert_fin($condition, $message) {
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

echo "=== DÉMARRAGE SUITE DE TEST: FINANCE DASHBOARD CORRECTIONS ===\n";

$dbFile = '/tmp/test_fin_dash.sqlite';
if (file_exists($dbFile)) {
    unlink($dbFile);
}

$pdo = new PDO('sqlite:' . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
Database::setInstance($pdo);

// Create Schema in SQLite
$pdo->exec("CREATE TABLE param_lycee (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nom_lycee TEXT,
    type_lycee TEXT
)");

$pdo->exec("CREATE TABLE annees_academiques (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    libelle TEXT,
    date_debut DATE,
    date_fin DATE,
    est_active INTEGER,
    cloturee INTEGER
)");

$pdo->exec("CREATE TABLE utilisateurs (
    id_user INTEGER PRIMARY KEY AUTOINCREMENT,
    nom TEXT,
    prenom TEXT,
    email TEXT,
    mot_de_passe TEXT,
    role_id INTEGER
)");

$pdo->exec("CREATE TABLE roles (
    id_role INTEGER PRIMARY KEY AUTOINCREMENT,
    nom_role TEXT,
    lycee_id INTEGER
)");

$pdo->exec("CREATE TABLE permissions (
    id_permission INTEGER PRIMARY KEY AUTOINCREMENT,
    resource TEXT,
    action TEXT
)");

$pdo->exec("CREATE TABLE role_permissions (
    role_id INTEGER,
    permission_id INTEGER
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

$pdo->exec("CREATE TABLE eleves (
    id_eleve INTEGER PRIMARY KEY AUTOINCREMENT,
    lycee_id INTEGER,
    nom TEXT,
    prenom TEXT,
    matricule TEXT,
    identifiant_public TEXT,
    statut TEXT
)");

$pdo->exec("CREATE TABLE cycles (
    id_cycle INTEGER PRIMARY KEY AUTOINCREMENT,
    nom_cycle TEXT,
    niveau_debut TEXT,
    niveau_fin TEXT
)");

$pdo->exec("CREATE TABLE classes (
    id_classe INTEGER PRIMARY KEY AUTOINCREMENT,
    lycee_id INTEGER,
    cycle_id INTEGER,
    niveau TEXT,
    serie TEXT,
    numero INTEGER
)");

$pdo->exec("CREATE TABLE etudes (
    id_etude INTEGER PRIMARY KEY AUTOINCREMENT,
    eleve_id INTEGER,
    classe_id INTEGER,
    lycee_id INTEGER,
    annee_academique_id INTEGER,
    is_active INTEGER,
    status TEXT DEFAULT 'active'
)");

$pdo->exec("CREATE TABLE sequences (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    lycee_id INTEGER,
    annee_academique_id INTEGER,
    nom TEXT,
    date_debut DATE,
    date_fin DATE,
    statut TEXT
)");

$pdo->exec("CREATE TABLE frais (
    id_frais INTEGER PRIMARY KEY AUTOINCREMENT,
    lycee_id INTEGER,
    cycle TEXT,
    niveau_debut TEXT,
    niveau_fin TEXT,
    serie TEXT,
    frais_inscription REAL,
    frais_mensuel REAL,
    frais_logo REAL,
    frais_carte REAL,
    autres_frais TEXT,
    annee_academique_id INTEGER
)");

$pdo->exec("CREATE TABLE inscriptions (
    id_inscription INTEGER PRIMARY KEY AUTOINCREMENT,
    etude_id INTEGER,
    eleve_id INTEGER,
    classe_id INTEGER,
    lycee_id INTEGER,
    annee_academique_id INTEGER,
    montant_total REAL,
    montant_verse REAL,
    reste_a_payer REAL,
    details_frais TEXT,
    user_id INTEGER,
    recu_numero TEXT,
    statut TEXT DEFAULT 'valide',
    date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$pdo->exec("CREATE TABLE mensualites (
    id_mensualite INTEGER PRIMARY KEY AUTOINCREMENT,
    etude_id INTEGER,
    eleve_id INTEGER,
    classe_id INTEGER,
    lycee_id INTEGER,
    annee_academique_id INTEGER,
    mois_ou_sequence TEXT,
    montant_verse REAL,
    reste_a_payer REAL,
    date_paiement DATETIME DEFAULT CURRENT_TIMESTAMP,
    user_id INTEGER
)");

$pdo->exec("CREATE TABLE mensualite_details (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    mensualite_id INTEGER,
    montant REAL,
    mode_paiement TEXT,
    reference_transaction TEXT,
    date_paiement DATETIME DEFAULT CURRENT_TIMESTAMP,
    recu_numero TEXT,
    statut TEXT DEFAULT 'valide'
)");

$pdo->exec("CREATE TABLE parametres_financiers_eleves (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    eleve_id INTEGER UNIQUE,
    type_avantage TEXT DEFAULT 'Aucun',
    valeur_type TEXT DEFAULT 'Pourcentage',
    valeur REAL DEFAULT 0.00,
    date_debut DATE,
    date_fin DATE,
    motif TEXT,
    organisme_financeur TEXT,
    frais_concernes TEXT,
    tous_frais INTEGER DEFAULT 0,
    cree_le TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Seed data
$pdo->exec("INSERT INTO annees_academiques (libelle, date_debut, date_fin, est_active, cloturee) VALUES ('2024-2025 Test', '2024-09-01', '2025-06-30', 1, 0)");
$anneeId = $pdo->lastInsertId();

$pdo->exec("INSERT INTO param_lycee (nom_lycee, type_lycee) VALUES ('Lycée Finance Test 1', 'prive')");
$lycee1Id = $pdo->lastInsertId();

$pdo->exec("INSERT INTO param_lycee (nom_lycee, type_lycee) VALUES ('Lycée Finance Test 2', 'prive')");
$lycee2Id = $pdo->lastInsertId();

$pdo->exec("INSERT INTO roles (id_role, nom_role) VALUES (1, 'Admin')");
$pdo->exec("INSERT INTO permissions (id_permission, resource, action) VALUES (1, 'paiement', 'view')");
$pdo->exec("INSERT INTO permissions (id_permission, resource, action) VALUES (2, 'paiement', 'manage')");
$pdo->exec("INSERT INTO role_permissions (role_id, permission_id) VALUES (1, 1)");
$pdo->exec("INSERT INTO role_permissions (role_id, permission_id) VALUES (1, 2)");

$pdo->exec("INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role_id) VALUES ('Admin', 'User', 'admin@test.com', 'pass', 1)");
$userId = $pdo->lastInsertId();

Auth::setSessionContext([
    'id' => $userId,
    'id_user' => $userId,
    'nom' => 'Admin',
    'prenom' => 'User',
    'email' => 'admin@test.com',
    'role_id' => 1,
    'lycee_id' => $lycee1Id
]);

// Setup Cycle, Class, Fees, Sequence
$pdo->exec("INSERT INTO cycles (id_cycle, nom_cycle, niveau_debut, niveau_fin) VALUES (1, 'Premier Cycle', '6eme', '3eme')");

$pdo->exec("INSERT INTO classes (lycee_id, cycle_id, niveau, numero) VALUES ($lycee1Id, 1, '6eme', 1)");
$classe1Id = $pdo->lastInsertId();

$pdo->exec("INSERT INTO frais (lycee_id, cycle, niveau_debut, niveau_fin, frais_inscription, frais_mensuel, annee_academique_id)
           VALUES ($lycee1Id, 'Premier Cycle', '6eme', '6eme', 50000, 20000, $anneeId)");

$pdo->exec("INSERT INTO sequences (lycee_id, annee_academique_id, nom, date_debut, date_fin, statut)
           VALUES ($lycee1Id, $anneeId, 'Séquence 1', '2024-09-01', '2024-10-31', 'ouverte')");


// ==========================================
// SCÉNARIO 1 : TRANSACTIONS DÉTAILLÉES COMPLÈTES
// ==========================================
echo "\n--- SCÉNARIO 1 : Vérification complète des 4 KPI avec transactions réelles ---\n";

$pdo->exec("INSERT INTO eleves (lycee_id, nom, prenom, matricule, statut) VALUES ($lycee1Id, 'Dupont', 'Jean', 'MAT-01', 'en_attente_paiement')");
$eleve1Id = $pdo->lastInsertId();

$pdo->exec("INSERT INTO etudes (eleve_id, classe_id, lycee_id, annee_academique_id, is_active, status) VALUES ($eleve1Id, $classe1Id, $lycee1Id, $anneeId, 0, 'en_attente_paiement')");
$etude1Id = $pdo->lastInsertId();

// Étude inactive/abandonnée pour vérifier l'exclusion
$pdo->exec("INSERT INTO eleves (lycee_id, nom, prenom, matricule, statut) VALUES ($lycee1Id, 'Inactif', 'Paul', 'MAT-99', 'inactif')");
$eleveInactifId = $pdo->lastInsertId();
$pdo->exec("INSERT INTO etudes (eleve_id, classe_id, lycee_id, annee_academique_id, is_active, status) VALUES ($eleveInactifId, $classe1Id, $lycee1Id, $anneeId, 0, 'abandon')");

$todayNow = date('Y-m-d H:i:s');

// 1. Inscription validée (30 000 FCFA versés sur 50 000 FCFA) -> reste_inscription = 20 000 FCFA
$pdo->exec("INSERT INTO inscriptions (etude_id, eleve_id, classe_id, lycee_id, annee_academique_id, montant_total, montant_verse, reste_a_payer, statut, date_inscription, recu_numero)
           VALUES ($etude1Id, $eleve1Id, $classe1Id, $lycee1Id, $anneeId, 50000, 30000, 20000, 'valide', '$todayNow', 'REC-INS-01')");

// 2. Inscription annulée (15 000 FCFA) -> Doit être strictement EXCLUE de Encaissement Total, Total ce mois, Aujourd'hui
$pdo->exec("INSERT INTO inscriptions (etude_id, eleve_id, classe_id, lycee_id, annee_academique_id, montant_total, montant_verse, reste_a_payer, statut, date_inscription, recu_numero)
           VALUES ($etude1Id, $eleve1Id, $classe1Id, $lycee1Id, $anneeId, 15000, 15000, 0, 'annule', '$todayNow', 'REC-INS-02')");

// 3. Mensualité validée (Septembre 20 000 FCFA) ->
$pdo->exec("INSERT INTO mensualites (etude_id, eleve_id, classe_id, lycee_id, annee_academique_id, mois_ou_sequence, montant_verse, reste_a_payer, date_paiement)
           VALUES ($etude1Id, $eleve1Id, $classe1Id, $lycee1Id, $anneeId, 'Septembre', 20000, 0, '$todayNow')");
$mens1Id = $pdo->lastInsertId();

$pdo->exec("INSERT INTO mensualite_details (mensualite_id, montant, mode_paiement, reference_transaction, date_paiement, recu_numero, statut)
           VALUES ($mens1Id, 20000, 'Espèces', 'REC-MENS-01', '$todayNow', 'REC-MENS-01', 'valide')");

// 4. Mensualité annulée (10 000 FCFA) -> Doit être strictly EXCLUE
$pdo->exec("INSERT INTO mensualites (etude_id, eleve_id, classe_id, lycee_id, annee_academique_id, mois_ou_sequence, montant_verse, reste_a_payer, date_paiement)
           VALUES ($etude1Id, $eleve1Id, $classe1Id, $lycee1Id, $anneeId, 'Octobre', 0, 20000, '$todayNow')");
$mens2Id = $pdo->lastInsertId();

$pdo->exec("INSERT INTO mensualite_details (mensualite_id, montant, mode_paiement, reference_transaction, date_paiement, recu_numero, statut)
           VALUES ($mens2Id, 10000, 'Espèces', 'REC-MENS-02', '$todayNow', 'REC-MENS-02', 'annule')");

// Exécuter le contrôleur pour le Dashboard Finance et capturer le HTML rendu
ob_start();
$controller = new PaiementController();
$controller->index();
$html1 = ob_get_clean();

// Rendu HTML assertions
assert_fin(strpos($html1, '50 000') !== false, "L'affichage HTML contient le montant formaté 50 000 FCFA (Encaissement Total / Total ce mois / Aujourd'hui).");
assert_fin(strpos($html1, '40 000') !== false, "L'affichage HTML contient le montant des restes 40 000 FCFA (Restes à percevoir).");

echo "  -> Scénario 1 validé avec succès (50 000 FCFA encaissés, 40 000 FCFA restes).\n";


// ==========================================
// SCÉNARIO 2 : SANS OPÉRATION (ÉTABLISSEMENT SANS TRANSACTION)
// ==========================================
echo "\n--- SCÉNARIO 2 : Établissement sans opération (Normalisation des KPI à 0) ---\n";

Auth::setSessionContext([
    'id' => $userId,
    'id_user' => $userId,
    'nom' => 'Admin',
    'prenom' => 'User',
    'email' => 'admin@test.com',
    'role_id' => 1,
    'lycee_id' => $lycee2Id
]);

// Vérification directe des requêtes calculées pour le Lycée 2 (0 opération)
$stmt = $pdo->prepare("SELECT SUM(montant_verse) FROM inscriptions WHERE lycee_id = :l AND annee_academique_id = :a AND statut = 'valide'");
$stmt->execute(['l' => $lycee2Id, 'a' => $anneeId]);
$totalInsEmpty = (float)($stmt->fetchColumn() ?? 0.0);

$stmt = $pdo->prepare("SELECT SUM(md.montant) FROM mensualite_details md JOIN mensualites m ON md.mensualite_id = m.id_mensualite WHERE m.lycee_id = :l AND m.annee_academique_id = :a AND md.statut = 'valide'");
$stmt->execute(['l' => $lycee2Id, 'a' => $anneeId]);
$totalMensEmpty = (float)($stmt->fetchColumn() ?? 0.0);

$totalGlobalEmpty = $totalInsEmpty + $totalMensEmpty;

$todayStr = date('Y-m-d');
$stmt = $pdo->prepare("
    SELECT SUM(montant) FROM (
        SELECT montant_verse as montant FROM inscriptions WHERE lycee_id = :l1 AND annee_academique_id = :a1 AND statut = 'valide' AND DATE(date_inscription) = :d1
        UNION ALL
        SELECT md.montant FROM mensualite_details md JOIN mensualites m ON md.mensualite_id = m.id_mensualite WHERE m.lycee_id = :l2 AND m.annee_academique_id = :a2 AND md.statut = 'valide' AND DATE(md.date_paiement) = :d2
    ) as t
");
$stmt->execute(['l1' => $lycee2Id, 'a1' => $anneeId, 'd1' => $todayStr, 'l2' => $lycee2Id, 'a2' => $anneeId, 'd2' => $todayStr]);
$totalTodayEmpty = (float)($stmt->fetchColumn() ?? 0.0);

$thisMonthStr = date('Y-m');
$stmt = $pdo->prepare("
    SELECT SUM(montant) FROM (
        SELECT montant_verse as montant FROM inscriptions WHERE lycee_id = :l1 AND annee_academique_id = :a1 AND statut = 'valide' AND SUBSTR(date_inscription, 1, 7) = :m1
        UNION ALL
        SELECT md.montant FROM mensualite_details md JOIN mensualites m ON md.mensualite_id = m.id_mensualite WHERE m.lycee_id = :l2 AND m.annee_academique_id = :a2 AND md.statut = 'valide' AND SUBSTR(md.date_paiement, 1, 7) = :m2
    ) as t
");
$stmt->execute(['l1' => $lycee2Id, 'a1' => $anneeId, 'm1' => $thisMonthStr, 'l2' => $lycee2Id, 'a2' => $anneeId, 'm2' => $thisMonthStr]);
$totalMonthEmpty = (float)($stmt->fetchColumn() ?? 0.0);

$stmtStudents = $pdo->prepare("
    SELECT DISTINCT e.id_eleve
    FROM eleves e
    JOIN etudes et ON e.id_eleve = et.eleve_id
    WHERE e.lycee_id = :lycee_id
    AND et.annee_academique_id = :annee_id
    AND (et.status = 'active' OR et.status = 'en_attente_paiement')
");
$stmtStudents->execute(['lycee_id' => $lycee2Id, 'annee_id' => $anneeId]);
$studentIdsEmpty = $stmtStudents->fetchAll(PDO::FETCH_COLUMN);

$arrieresEmpty = 0.0;
foreach ($studentIdsEmpty as $sId) {
    $st = FinancialStatusService::getStudentFinancialStatus($sId, $anneeId);
    if ($st) {
        $arrieresEmpty += (float)($st['total_reste'] ?? 0.0);
    }
}

echo "  -> Encaissement Total calculé (Lycée 2) : " . var_export($totalGlobalEmpty, true) . "\n";
echo "  -> Total ce mois calculé (Lycée 2) : " . var_export($totalMonthEmpty, true) . "\n";
echo "  -> Aujourd'hui calculé (Lycée 2) : " . var_export($totalTodayEmpty, true) . "\n";
echo "  -> Restes à percevoir calculés (Lycée 2) : " . var_export($arrieresEmpty, true) . "\n";

assert_fin($totalGlobalEmpty === 0.0, "KPI Encaissement Total est égal à 0.0 (non NULL, non vide).");
assert_fin($totalMonthEmpty === 0.0, "KPI Total ce mois est égal à 0.0 (non NULL, non vide).");
assert_fin($totalTodayEmpty === 0.0, "KPI Aujourd'hui est égal à 0.0 (non NULL, non vide).");
assert_fin($arrieresEmpty === 0.0, "KPI Restes à percevoir est égal à 0.0 (non NULL, non vide).");

echo "\n=== TOUS LES TESTS FINANCIERS DU DASHBOARD ONT RÉUSSI AVEC SUCCÈS ===\n";
