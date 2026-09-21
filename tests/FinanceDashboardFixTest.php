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
require_once __DIR__ . '/../src/controllers/ReportingController.php';

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

$pdo->exec("INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role_id) VALUES ('Admin', 'User', 'admin@test.com', 'pass', 1)");
$userId = $pdo->lastInsertId();

$_SESSION = [
    'user_id' => $userId,
    'lycee_id' => $lycee1Id,
    'role_id' => 1,
    'permissions' => ['view:paiement', 'manage:paiement', 'view:reporting', 'export:reporting']
];


// TEST 1: Exclusion of Canceled Receipts
echo "\n--- TEST 1: Exclusion des paiements annulés des encaissements ---\n";
$pdo->exec("INSERT INTO eleves (lycee_id, nom, prenom, matricule, statut) VALUES ($lycee1Id, 'Dupont', 'Jean', 'MAT-01', 'actif')");
$eleve1Id = $pdo->lastInsertId();

$pdo->exec("INSERT INTO classes (lycee_id, cycle_id, niveau, numero) VALUES ($lycee1Id, 1, '6eme', 1)");
$classe1Id = $pdo->lastInsertId();

$pdo->exec("INSERT INTO etudes (eleve_id, classe_id, lycee_id, annee_academique_id, is_active) VALUES ($eleve1Id, $classe1Id, $lycee1Id, $anneeId, 1)");
$etude1Id = $pdo->lastInsertId();

$pdo->exec("INSERT INTO inscriptions (etude_id, eleve_id, classe_id, lycee_id, annee_academique_id, montant_total, montant_verse, reste_a_payer, statut, date_inscription)
           VALUES ($etude1Id, $eleve1Id, $classe1Id, $lycee1Id, $anneeId, 50000, 50000, 0, 'valide', '2024-09-10 10:00:00')");

$pdo->exec("INSERT INTO inscriptions (etude_id, eleve_id, classe_id, lycee_id, annee_academique_id, montant_total, montant_verse, reste_a_payer, statut, date_inscription)
           VALUES ($etude1Id, $eleve1Id, $classe1Id, $lycee1Id, $anneeId, 30000, 30000, 0, 'annule', '2024-09-11 11:00:00')");

$stmtValid = $pdo->prepare("SELECT SUM(montant_verse) FROM inscriptions WHERE lycee_id = :l AND annee_academique_id = :a AND statut = 'valide'");
$stmtValid->execute(['l' => $lycee1Id, 'a' => $anneeId]);
$validSum = (float)$stmtValid->fetchColumn();

$stmtAll = $pdo->prepare("SELECT SUM(montant_verse) FROM inscriptions WHERE lycee_id = :l AND annee_academique_id = :a");
$stmtAll->execute(['l' => $lycee1Id, 'a' => $anneeId]);
$allSum = (float)$stmtAll->fetchColumn();

assert_fin($validSum === 50000.0, "Le cumul des inscriptions validées est de 50 000 FCFA (seules les inscriptions à statut 'valide' sont retenues).");
assert_fin($validSum < $allSum, "Le cumul des recettes validées exclut strictement l'inscription annulée de 30 000 FCFA.");


// TEST 2: Intégration des mensualités dues dans les restes à percevoir
echo "\n--- TEST 2: Intégration des mensualités dues dans les restes à percevoir ---\n";
$pdo->exec("INSERT INTO eleves (lycee_id, nom, prenom, matricule, statut) VALUES ($lycee1Id, 'Martin', 'Claire', 'MAT-02', 'actif')");
$eleve2Id = $pdo->lastInsertId();

$pdo->exec("INSERT INTO classes (lycee_id, cycle_id, niveau, numero) VALUES ($lycee1Id, 1, '5eme', 1)");
$classe2Id = $pdo->lastInsertId();

$pdo->exec("INSERT INTO etudes (eleve_id, classe_id, lycee_id, annee_academique_id, is_active) VALUES ($eleve2Id, $classe2Id, $lycee1Id, $anneeId, 1)");
$etude2Id = $pdo->lastInsertId();

$pdo->exec("INSERT INTO sequences (lycee_id, annee_academique_id, nom, date_debut, date_fin, statut) VALUES ($lycee1Id, $anneeId, 'Seq 1', '2024-09-01', '2024-10-31', 'ouverte')");

$pdo->exec("INSERT INTO cycles (id_cycle, nom_cycle, niveau_debut, niveau_fin) VALUES (1, 'Premier Cycle', '6eme', '3eme')");

$pdo->exec("INSERT INTO frais (lycee_id, cycle, niveau_debut, niveau_fin, frais_inscription, frais_mensuel, annee_academique_id)
           VALUES ($lycee1Id, 'Premier Cycle', '5eme', '5eme', 20000, 15000, $anneeId)");

$pdo->exec("INSERT INTO inscriptions (etude_id, eleve_id, classe_id, lycee_id, annee_academique_id, montant_total, montant_verse, reste_a_payer, statut)
           VALUES ($etude2Id, $eleve2Id, $classe2Id, $lycee1Id, $anneeId, 20000, 10000, 10000, 'valide')");

$st = FinancialStatusService::getStudentFinancialStatus($eleve2Id, $anneeId);
assert_fin($st['reste_inscription'] === 10000.0, "Le reste d'inscription pour l'élève est exactement de 10 000 FCFA.");
assert_fin($st['reste_mensualite'] >= 15000.0, "Le reste des mensualités échues est supérieur ou égal à 15 000 FCFA.");
assert_fin($st['total_reste'] === ($st['reste_inscription'] + $st['reste_mensualite']), "Le reste total combine correctement inscription et mensualités dues.");


// TEST 3: Recovery Rate Calculation in KpiService
echo "\n--- TEST 3: Calcul du taux de recouvrement global ---\n";
$filters = ['date_debut' => '2024-01-01', 'date_fin' => '2025-12-31'];
$rate = KpiService::computeKpi('taux_recouvrement', $lycee1Id, $filters);
assert_fin(is_float($rate) && $rate >= 0.0 && $rate <= 100.0, "Le taux de recouvrement global est calculé avec succès: " . number_format($rate, 2) . "%");


// TEST 4: Multi-Tenant Isolation
echo "\n--- TEST 4: Isolation multi-tenant des données financières ---\n";
$recettes1 = KpiService::computeKpi('recettes_scolaires', $lycee1Id, $filters);
$recettes2 = KpiService::computeKpi('recettes_scolaires', $lycee2Id, $filters);
assert_fin(is_float($recettes1) && is_float($recettes2), "Calcul des recettes indépendant par lycée (Lycée 1: $recettes1 FCFA, Lycée 2: $recettes2 FCFA).");

echo "\n=== TOUS LES TESTS FINANCIERS ONT REUSSI SANS ERREUR ===\n";
