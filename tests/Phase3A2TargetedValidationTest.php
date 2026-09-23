<?php

// Test suite for Phase 3 A2 Targeted Validation
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/models/FinancialStatusService.php';
require_once __DIR__ . '/../src/models/CompteFinancier.php';
require_once __DIR__ . '/../src/models/ExerciceFinancier.php';
require_once __DIR__ . '/../src/models/SessionCaisse.php';
require_once __DIR__ . '/../src/models/TreasuryService.php';
require_once __DIR__ . '/../src/controllers/SessionCaisseController.php';
require_once __DIR__ . '/../src/services/ComptabiliteService.php';
require_once __DIR__ . '/../db/migrations/20240115_05_create_comptabilite_generale.php';

@session_start();

$dbFile = '/tmp/test_phase_3_a2_targeted.sqlite';

if (file_exists($dbFile)) {
    unlink($dbFile);
}

$pdo = new PDO('sqlite:' . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

Database::setInstance($pdo);

// Create required tables
$pdo->exec("CREATE TABLE IF NOT EXISTS param_lycee (id INTEGER PRIMARY KEY AUTOINCREMENT, nom VARCHAR(100));");
$pdo->exec("CREATE TABLE IF NOT EXISTS utilisateurs (id_user INTEGER PRIMARY KEY AUTOINCREMENT, nom VARCHAR(100), prenom VARCHAR(100));");
$pdo->exec("CREATE TABLE IF NOT EXISTS comptes_financiers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    lycee_id INTEGER,
    nom_compte VARCHAR(150),
    type_compte VARCHAR(50),
    solde_courant DECIMAL(15,2) DEFAULT 0,
    devise VARCHAR(10) DEFAULT 'FCFA',
    responsable_id INTEGER,
    statut VARCHAR(20) DEFAULT 'actif',
    est_coffre TINYINT DEFAULT 0,
    compte_comptable_id INTEGER,
    compte_comptable_numero VARCHAR(50)
);");

$pdo->exec("CREATE TABLE IF NOT EXISTS sessions_caisse (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    lycee_id INTEGER,
    user_id INTEGER,
    compte_id INTEGER,
    date_ouverture DATETIME,
    date_fermeture DATETIME,
    solde_ouverture DECIMAL(15,2) DEFAULT 0,
    solde_theorique DECIMAL(15,2) DEFAULT 0,
    solde_reel DECIMAL(15,2),
    ecart DECIMAL(15,2) DEFAULT 0,
    justificatif_ecart TEXT,
    statut VARCHAR(30) DEFAULT 'ouverte',
    valide_par INTEGER,
    valide_le DATETIME,
    montant_remis DECIMAL(15,2) DEFAULT NULL,
    fonds_caisse_conserve DECIMAL(15,2) DEFAULT NULL
);");

$pdo->exec("CREATE TABLE IF NOT EXISTS mouvements_tresorerie (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    lycee_id INTEGER,
    compte_id INTEGER,
    session_caisse_id INTEGER,
    exercice_financier_id INTEGER,
    transfert_id INTEGER,
    type_mouvement VARCHAR(20),
    montant DECIMAL(15,2),
    mode_paiement VARCHAR(50),
    reference_transaction VARCHAR(150),
    source_type VARCHAR(100),
    source_id INTEGER,
    evenement_type VARCHAR(50),
    motif VARCHAR(255),
    date_mouvement DATETIME DEFAULT CURRENT_TIMESTAMP,
    user_id INTEGER,
    is_aggregate_data TINYINT DEFAULT 0,
    date_reconstruite TINYINT DEFAULT 0,
    is_historical_migration TINYINT DEFAULT 0,
    mode_paiement_reconstruit TINYINT DEFAULT 0
);");

$pdo->exec("CREATE TABLE IF NOT EXISTS regularisations_ecarts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    lycee_id INTEGER,
    session_caisse_id INTEGER,
    montant DECIMAL(15,2),
    type_ecart VARCHAR(20),
    motif TEXT,
    constate_par INTEGER,
    approuve_par INTEGER,
    date_constat DATETIME DEFAULT CURRENT_TIMESTAMP,
    reference_audit VARCHAR(100)
);");

$pdo->exec("CREATE TABLE IF NOT EXISTS exercices_financiers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    lycee_id INTEGER,
    libelle VARCHAR(100),
    date_debut DATE,
    date_fin DATE,
    est_actif TINYINT DEFAULT 1,
    cloture TINYINT DEFAULT 0,
    type_exercice VARCHAR(50) DEFAULT 'normal'
);");

migrate_05($pdo);

// Seed basic data
$pdo->exec("INSERT INTO schemas_comptables (evenement, compte_debit_numero, compte_credit_numero, libelle_modele, journal_code) VALUES
('remise_coffre', '571100', '571000', 'Remise de caisse au coffre', 'TRESO'),
('ecart_positif', '571000', '758000', 'Surplus de caisse', 'TRESO'),
('ecart_negatif', '658000', '571000', 'Manquant de caisse', 'TRESO');");

$pdo->exec("INSERT INTO comptes_comptables (numero, libelle, classe, nature, autoriser_ecriture) VALUES
('571000', 'Caisse Centrale', 5, 'actif', 1),
('571100', 'Coffre Fort Central', 5, 'actif', 1),
('758000', 'Surplus de caisse', 7, 'produit', 1),
('658000', 'Manquant de caisse', 6, 'charge', 1);");

$pdo->exec("INSERT INTO param_lycee (id, nom) VALUES (1, 'Lycée Excellence')");
$pdo->exec("INSERT INTO journaux_comptables (lycee_id, code, libelle, type_journal, actif) VALUES (1, 'TRESO', 'Journal Trésorerie', 'tresorerie', 1)");
$pdo->exec("INSERT INTO utilisateurs (id_user, nom, prenom) VALUES (1, 'Admin', 'Chef'), (2, 'Caissier', 'Jean')");
$pdo->exec("INSERT INTO exercices_financiers (id, lycee_id, libelle, date_debut, date_fin, est_actif) VALUES (1, 1, 'Exercice 2024', '2024-01-01', '2024-12-31', 1)");
$pdo->exec("INSERT INTO comptes_financiers (id, lycee_id, nom_compte, type_compte, solde_courant, statut, est_coffre, compte_comptable_numero) VALUES (10, 1, 'Caisse Guichet A', 'caisse', 15000.00, 'actif', 0, '571000')");
$pdo->exec("INSERT INTO comptes_financiers (id, lycee_id, nom_compte, type_compte, solde_courant, statut, est_coffre, compte_comptable_numero) VALUES (20, 1, 'Coffre Fort Central', 'caisse', 500000.00, 'actif', 1, '571100')");

function assert_test($condition, $message) {
    if ($condition) {
        echo "  [PASS] " . $message . "\n";
    } else {
        echo "  [FAIL] " . $message . "\n";
        exit(1);
    }
}

echo "=========================================================\n";
echo "  TEST SUITE : PHASE 3 A2 TARGETED VALIDATION\n";
echo "=========================================================\n\n";

$_SESSION['user_id'] = 2;
$_SESSION['lycee_id'] = 1;

// TEST A: Interactive cash payment without an open session
echo "Test A: Tentative de paiement espèces sans session de caisse ouverte...\n";
$countSessBefore = $pdo->query("SELECT COUNT(*) FROM sessions_caisse")->fetchColumn();
$countMvtBefore = $pdo->query("SELECT COUNT(*) FROM mouvements_tresorerie")->fetchColumn();

$rejectedA = false;
$msgA = '';
try {
    TreasuryService::registerMovement([
        'lycee_id' => 1,
        'compte_id' => 10,
        'type_mouvement' => 'entree',
        'montant' => 25000.00,
        'mode_paiement' => 'Espèces',
        'source_type' => 'inscriptions',
        'source_id' => 101,
        'evenement_type' => 'encaissement',
        'motif' => 'Règlement inscription élève A',
        'user_id' => 2
    ]);
} catch (Exception $e) {
    $rejectedA = true;
    $msgA = $e->getMessage();
}

$countSessAfter = $pdo->query("SELECT COUNT(*) FROM sessions_caisse")->fetchColumn();
$countMvtAfter = $pdo->query("SELECT COUNT(*) FROM mouvements_tresorerie")->fetchColumn();

assert_test($rejectedA, "Opération rejetée car aucune session de caisse n'est ouverte.");
assert_test(strpos($msgA, "Veuillez ouvrir votre session de caisse journalière") !== false, "Message d'erreur métier explicite : '$msgA'.");
assert_test((int)$countSessAfter === (int)$countSessBefore, "Aucune session de caisse n'a été créée implicitement.");
assert_test((int)$countMvtAfter === (int)$countMvtBefore, "Aucun mouvement de trésorerie n'a été créé.");


// TEST B: Interactive cash payment WITH an open session
echo "\nTest B: Paiement espèces AVEC une session de caisse ouverte...\n";
$sessIdB = SessionCaisse::ouvrir([
    'lycee_id' => 1,
    'user_id' => 2,
    'compte_id' => 10,
    'solde_ouverture' => 15000.00
]);

$mvtIdB = TreasuryService::registerMovement([
    'lycee_id' => 1,
    'compte_id' => 10,
    'session_caisse_id' => $sessIdB,
    'type_mouvement' => 'entree',
    'montant' => 25000.00,
    'mode_paiement' => 'Espèces',
    'source_type' => 'inscriptions',
    'source_id' => 101,
    'evenement_type' => 'encaissement',
    'motif' => 'Règlement inscription élève B',
    'user_id' => 2
]);

$countSessAfterB = $pdo->query("SELECT COUNT(*) FROM sessions_caisse")->fetchColumn();
$mvtRowB = $pdo->query("SELECT * FROM mouvements_tresorerie WHERE id = " . (int)$mvtIdB)->fetch(PDO::FETCH_ASSOC);

assert_test(!empty($mvtIdB), "Mouvement de trésorerie enregistré avec succès.");
assert_test((int)$mvtRowB['session_caisse_id'] === (int)$sessIdB, "Le mouvement est rattaché à la session ouverte N° $sessIdB.");
assert_test((int)$countSessAfterB === 1, "Aucune session doublon créée lors de l'enregistrement.");


// TEST C: Operational movement on 'fermee_a_valider' session
echo "\nTest C: Tentative d'enregistrement d'un mouvement sur session 'fermee_a_valider'...\n";
SessionCaisse::cloturer($sessIdB, 40000.00, '', 40000.00, 0.00); // Statut -> fermee_a_valider
$sessRowC = SessionCaisse::findById($sessIdB);
assert_test($sessRowC['statut'] === 'fermee_a_valider', "La session est bien à l'état 'fermee_a_valider'.");

$rejectedC = false;
$countMvtBeforeC = $pdo->query("SELECT COUNT(*) FROM mouvements_tresorerie")->fetchColumn();

try {
    TreasuryService::registerMovement([
        'lycee_id' => 1,
        'compte_id' => 10,
        'session_caisse_id' => $sessIdB,
        'type_mouvement' => 'entree',
        'montant' => 10000.00,
        'mode_paiement' => 'Espèces',
        'source_type' => 'mensualites',
        'source_id' => 201,
        'evenement_type' => 'encaissement',
        'motif' => 'Règlement mensuel tardif',
        'user_id' => 2
    ]);
} catch (Exception $e) {
    if (strpos($e->getMessage(), "plus ouverte") !== false || strpos($e->getMessage(), "Veuillez ouvrir") !== false) {
        $rejectedC = true;
    }
}

$countMvtAfterC = $pdo->query("SELECT COUNT(*) FROM mouvements_tresorerie")->fetchColumn();
$sessRowCAfter = SessionCaisse::findById($sessIdB);

assert_test($rejectedC, "Mouvement refusé sur session 'fermee_a_valider'.");
assert_test((int)$countMvtAfterC === (int)$countMvtBeforeC, "Aucun nouveau mouvement créé.");
assert_test($sessRowCAfter['statut'] === 'fermee_a_valider', "Le statut de la session 'fermee_a_valider' est inchangé.");


// TEST D: Operational movement on 'fermee_validee' session
echo "\nTest D: Tentative d'enregistrement d'un mouvement sur session 'fermee_validee'...\n";
SessionCaisse::approuver($sessIdB, 1, "Approbation session B");
$sessRowD = SessionCaisse::findById($sessIdB);
assert_test($sessRowD['statut'] === 'fermee_validee', "La session est bien à l'état 'fermee_validee'.");

$rejectedD = false;
try {
    TreasuryService::registerMovement([
        'lycee_id' => 1,
        'compte_id' => 10,
        'session_caisse_id' => $sessIdB,
        'type_mouvement' => 'entree',
        'montant' => 5000.00,
        'mode_paiement' => 'Espèces',
        'source_type' => 'mensualites',
        'source_id' => 202,
        'evenement_type' => 'encaissement',
        'motif' => 'Règlement mensuel post-validation',
        'user_id' => 2
    ]);
} catch (Exception $e) {
    if (strpos($e->getMessage(), "plus ouverte") !== false || strpos($e->getMessage(), "Veuillez ouvrir") !== false) {
        $rejectedD = true;
    }
}

assert_test($rejectedD, "Mouvement refusé sur session 'fermee_validee'.");


// TEST E: Verification that old automatic creation (ghost session) no longer occurs
echo "\nTest E: Vérification qu'aucune session 'fantôme' (solde_ouverture = solde_courant) n'est créée...\n";
// Currently no open session exists on compte 10
$activeSessE = SessionCaisse::findOpenByCompte(10);
assert_test($activeSessE === null, "Aucune session ouverte n'existe actuellement sur le compte 10.");

$rejectedE = false;
try {
    TreasuryService::registerMovement([
        'lycee_id' => 1,
        'compte_id' => 10,
        'type_mouvement' => 'entree',
        'montant' => 1000.00,
        'mode_paiement' => 'Espèces',
        'source_type' => 'inscriptions',
        'source_id' => 103,
        'evenement_type' => 'encaissement',
        'motif' => 'Essai sans session',
        'user_id' => 2
    ]);
} catch (Exception $e) {
    $rejectedE = true;
}

$sessionsCountE = $pdo->query("SELECT COUNT(*) FROM sessions_caisse WHERE solde_ouverture = 15000.00 AND date_ouverture > '2024-01-01'")->fetchColumn();
assert_test($rejectedE, "L'opération sans session a été rejetée.");
assert_test((int)$sessionsCountE === 1, "Aucune session fantôme n'a été créée automatiquement.");


// TEST F: Technical / Historical flows compatibility (is_historical_migration = true)
echo "\nTest F: Compatibilité des flux techniques/historiques (is_historical_migration = 1)...\n";
$mvtIdF = TreasuryService::registerMovement([
    'lycee_id' => 1,
    'compte_id' => 10,
    'type_mouvement' => 'entree',
    'montant' => 100000.00,
    'mode_paiement' => 'Espèces',
    'source_type' => 'migration_historique',
    'source_id' => 999,
    'evenement_type' => 'encaissement',
    'motif' => 'Reprise de solde historique',
    'user_id' => 1,
    'is_historical_migration' => 1
]);

assert_test(!empty($mvtIdF), "La migration historique sans session a été exécutée avec succès (flux technique préservé).");


echo "\n=========================================================\n";
echo "  TOUS LES TESTS A2 (A, B, C, D, E, F) ONT RÉUSSI ! [OK]\n";
echo "=========================================================\n";
