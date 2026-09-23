<?php

// Test suite for Phase 3 Targeted Validation (A1, A3, A5)
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

$dbFile = '/tmp/test_phase_3_targeted.sqlite';

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
$pdo->exec("INSERT INTO comptes_financiers (id, lycee_id, nom_compte, type_compte, solde_courant, statut, est_coffre, compte_comptable_numero) VALUES (10, 1, 'Caisse Guichet A', 'caisse', 10000.00, 'actif', 0, '571000')");
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
echo "  TEST SUITE : PHASE 3 TARGETED VALIDATION (A1, A3, A5)\n";
echo "=========================================================\n\n";

$_SESSION['user_id'] = 2;
$_SESSION['lycee_id'] = 1;

// Test 1: An open session can be closed
echo "Test 1: Clôture d'une session 'ouverte'...\n";
$sessId = SessionCaisse::ouvrir([
    'lycee_id' => 1,
    'user_id' => 2,
    'compte_id' => 10,
    'solde_ouverture' => 10000.00
]);

$ecart = SessionCaisse::cloturer($sessId, 10000.00, '', 10000.00, 0.00);
$sessAfter = SessionCaisse::findById($sessId);

assert_test($sessAfter['statut'] === 'fermee_a_valider', "La session ouverte a bien été fermée (statut = 'fermee_a_valider').");
assert_test((float)$ecart === 0.0, "Calcul d'écart conforme (0.00 FCFA).");

// Test 2: A session already 'fermee_a_valider' cannot be re-closed
echo "\nTest 2: Tentative de re-clôture d'une session 'fermee_a_valider'...\n";
$rejected = false;
try {
    SessionCaisse::cloturer($sessId, 12000.00, 'Tentative frauduleuse', 12000.00, 0.00);
} catch (Exception $e) {
    if (strpos($e->getMessage(), "ouverte") !== false || strpos($e->getMessage(), "ouvert") !== false) {
        $rejected = true;
    }
}
assert_test($rejected, "Re-clôture refusée au niveau modèle sur session 'fermee_a_valider'.");

// Test 3: A session already 'fermee_validee' cannot be re-closed
echo "\nTest 3: Tentative de re-clôture d'une session 'fermee_validee'...\n";
SessionCaisse::approuver($sessId, 1, "Validation par administrateur");
$sessValidee = SessionCaisse::findById($sessId);
assert_test($sessValidee['statut'] === 'fermee_validee', "La session est bien validée ('fermee_validee').");

$rejectedVal = false;
try {
    SessionCaisse::cloturer($sessId, 15000.00, 'Tentative post-validation', 15000.00, 0.00);
} catch (Exception $e) {
    if (strpos($e->getMessage(), "ouverte") !== false || strpos($e->getMessage(), "ouvert") !== false) {
        $rejectedVal = true;
    }
}
assert_test($rejectedVal, "Re-clôture refusée au niveau modèle sur session 'fermee_validee'.");

// Test 4: Account name (nom_compte) is properly returned in findActiveByUser()
echo "\nTest 4: Vérification de 'nom_compte' dans findActiveByUser()...\n";
$sessId2 = SessionCaisse::ouvrir([
    'lycee_id' => 1,
    'user_id' => 2,
    'compte_id' => 10,
    'solde_ouverture' => 0.00
]);
$activeSess = SessionCaisse::findActiveByUser(2, 1);
assert_test(!empty($activeSess['nom_compte']) && $activeSess['nom_compte'] === 'Caisse Guichet A', "Le champ 'nom_compte' est correctement renseigné ('Caisse Guichet A').");

// Test 5: Verify button label conditioning on edit permission
echo "\nTest 5: Vérification du libellé selon la permission edit...\n";
Auth::setSessionContext(['permissions' => ['sessions_caisse' => ['view']]]); // Read-only
$labelReadOnly = Auth::can('edit', 'sessions_caisse') ? "Consulter & Fermer" : "Consulter la session";
assert_test($labelReadOnly === "Consulter la session", "Un utilisateur en lecture seule obtient le libellé 'Consulter la session'.");

Auth::setSessionContext(['permissions' => ['sessions_caisse' => ['view', 'edit']]]); // Edit rights
$labelEdit = Auth::can('edit', 'sessions_caisse') ? "Consulter & Fermer" : "Consulter la session";
assert_test($labelEdit === "Consulter & Fermer", "Un caissier avec droit edit obtient le libellé 'Consulter & Fermer'.");

// Test 6: Verify discrepancy and vault transfer mechanics remain intact
echo "\nTest 6: Intégrité des calculs d'écart et de remise au coffre lors de l'approbation...\n";
// Close session 2 with discrepancy of +2000 and vault transfer 2000
SessionCaisse::cloturer($sessId2, 2000.00, "Surplus trouvé", 2000.00, 0.00);
$countMvtBefore = $pdo->query("SELECT COUNT(*) FROM mouvements_tresorerie")->fetchColumn();
SessionCaisse::approuver($sessId2, 1, "Approbation avec surplus");
$countMvtAfter = $pdo->query("SELECT COUNT(*) FROM mouvements_tresorerie")->fetchColumn();

// 1 mvt correction + 1 mvt remise sortie + 1 mvt remise entrée = 3 movements added
assert_test((int)$countMvtAfter === (int)$countMvtBefore + 3, "Régularisation d'écart (+2000) et double mouvement de remise au coffre générés sans altération.");

echo "\n=========================================================\n";
echo "  TOUS LES TESTS DE VALIDATION CIBLÉS ONT RÉUSSI ! [OK]\n";
echo "=========================================================\n";
