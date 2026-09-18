<?php

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/models/FinancialStatusService.php';
require_once __DIR__ . '/../src/models/CompteFinancier.php';
require_once __DIR__ . '/../src/models/ExerciceFinancier.php';
require_once __DIR__ . '/../src/models/SessionCaisse.php';
require_once __DIR__ . '/../src/models/TreasuryService.php';
require_once __DIR__ . '/../src/controllers/SessionCaisseController.php';
require_once __DIR__ . '/../src/controllers/PaiementController.php';
require_once __DIR__ . '/../src/services/ComptabiliteService.php';
require_once __DIR__ . '/../db/migrations/20240115_05_create_comptabilite_generale.php';

@session_start();

$dbFile = '/tmp/test_session_caisse_noduplication.sqlite';
if (file_exists($dbFile)) {
    unlink($dbFile);
}

$pdo = new PDO('sqlite:' . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Initialize schema
$pdo->exec("CREATE TABLE IF NOT EXISTS param_lycee (id INTEGER PRIMARY KEY, nom_lycee TEXT, type_lycee TEXT)");
$pdo->exec("CREATE TABLE IF NOT EXISTS utilisateurs (id_user INTEGER PRIMARY KEY, nom TEXT, prenom TEXT, email TEXT, statut TEXT)");
$pdo->exec("CREATE TABLE IF NOT EXISTS comptes_financiers (id INTEGER PRIMARY KEY AUTOINCREMENT, lycee_id INTEGER, nom_compte TEXT, type_compte TEXT, solde_courant DECIMAL(15,2), devise TEXT DEFAULT 'FCFA', responsable_id INTEGER, statut TEXT DEFAULT 'actif', est_coffre INTEGER DEFAULT 0, compte_comptable_id INTEGER, compte_comptable_numero TEXT)");
$pdo->exec("CREATE TABLE IF NOT EXISTS sessions_caisse (id INTEGER PRIMARY KEY AUTOINCREMENT, lycee_id INTEGER, user_id INTEGER, compte_id INTEGER, date_ouverture DATETIME, date_fermeture DATETIME, solde_ouverture DECIMAL(15,2), solde_theorique DECIMAL(15,2), solde_reel DECIMAL(15,2), ecart DECIMAL(15,2), justificatif_ecart TEXT, montant_remis DECIMAL(15,2), fonds_caisse_conserve DECIMAL(15,2), statut TEXT, valide_par INTEGER, valide_le DATETIME, is_active INTEGER GENERATED ALWAYS AS (CASE WHEN statut IN ('ouverte', 'fermee_a_valider') THEN 1 ELSE NULL END) VIRTUAL)");
$pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS uk_session_compte_active ON sessions_caisse(compte_id, is_active)");
$pdo->exec("CREATE TABLE IF NOT EXISTS mouvements_tresorerie (id INTEGER PRIMARY KEY AUTOINCREMENT, lycee_id INTEGER, compte_id INTEGER, session_caisse_id INTEGER, exercice_financier_id INTEGER, transfert_id INTEGER, type_mouvement TEXT, montant DECIMAL(15,2), mode_paiement TEXT, reference_transaction TEXT, source_type TEXT, source_id INTEGER, evenement_type TEXT, motif TEXT, user_id INTEGER, date_mouvement DATETIME DEFAULT CURRENT_TIMESTAMP, is_aggregate_data INTEGER DEFAULT 0, date_reconstruite INTEGER DEFAULT 0, is_historical_migration INTEGER DEFAULT 0, mode_paiement_reconstruit INTEGER DEFAULT 0)");
$pdo->exec("CREATE TABLE IF NOT EXISTS regularisations_ecarts (id INTEGER PRIMARY KEY AUTOINCREMENT, lycee_id INTEGER, session_caisse_id INTEGER, montant DECIMAL(15,2), type_ecart TEXT, motif TEXT, constate_par INTEGER, approuve_par INTEGER, reference_audit TEXT, date_regularisation DATETIME DEFAULT CURRENT_TIMESTAMP)");
$pdo->exec("CREATE TABLE IF NOT EXISTS exercices_financiers (id INTEGER PRIMARY KEY AUTOINCREMENT, lycee_id INTEGER, libelle TEXT, date_debut DATE, date_fin DATE, est_actif INTEGER DEFAULT 1, cloture INTEGER DEFAULT 0)");

$pdo->exec("INSERT INTO param_lycee (id, nom_lycee, type_lycee) VALUES (1, 'Lycée Test Audit', 'Lycee')");
$pdo->exec("INSERT INTO utilisateurs (id_user, nom, prenom, email, statut) VALUES (1, 'Admin', 'Super', 'admin@test.com', 'actif')");
$pdo->exec("INSERT INTO utilisateurs (id_user, nom, prenom, email, statut) VALUES (2, 'Caissier', 'Jean', 'caissier@test.com', 'actif')");
$pdo->exec("INSERT INTO exercices_financiers (id, lycee_id, libelle, date_debut, date_fin, est_actif) VALUES (1, 1, 'Exercice 2026', '2026-01-01', '2026-12-31', 1)");

$pdo->exec("INSERT INTO comptes_financiers (id, lycee_id, nom_compte, type_compte, solde_courant, statut, est_coffre, compte_comptable_numero) VALUES (1, 1, 'Caisse Guichet', 'caisse', 10000.00, 'actif', 0, '571000')");
$pdo->exec("INSERT INTO comptes_financiers (id, lycee_id, nom_compte, type_compte, solde_courant, statut, est_coffre, compte_comptable_numero) VALUES (4, 1, 'Coffre Fort Central', 'caisse', 0.00, 'actif', 1, '571100')");

// Apply General Accounting Migration
migrate_05($pdo);

Database::setInstance($pdo);
ComptabiliteService::seedDefaultChartOfAccounts();
ComptabiliteService::seedDefaultJournalsForLycee(1);
ComptabiliteService::seedDefaultSchemas();

function assert_check($condition, $msg) {
    if ($condition) {
        echo "  [PASS] " . $msg . "\n";
    } else {
        echo "  [FAIL] " . $msg . "\n";
        exit(1);
    }
}

echo "=========================================================================\n";
echo "📊 TEST SUITE : VALIDATION NON-DUPLICATION DE CAISSE ET RECONCILIATION UI\n";
echo "=========================================================================\n";

// Test 1: Opening balance creates NO movement in mouvements_tresorerie
echo "\nTest 1: Ouverture de session de caisse...\n";
$sessId = SessionCaisse::ouvrir([
    'lycee_id' => 1,
    'user_id' => 2,
    'compte_id' => 1,
    'solde_ouverture' => 10000.00
]);
$mvtCountOpening = (int)$pdo->query("SELECT COUNT(*) FROM mouvements_tresorerie")->fetchColumn();
assert_check($mvtCountOpening === 0, "Aucun mouvement de trésorerie artificiel créé lors de l'ouverture.");

// Test 2: Operational collection of 50,000 FCFA
echo "\nTest 2: Encaissement d'inscription de 50 000 FCFA...\n";
TreasuryService::registerMovement([
    'lycee_id' => 1,
    'compte_id' => 1,
    'session_caisse_id' => $sessId,
    'type_mouvement' => 'entree',
    'montant' => 50000.00,
    'mode_paiement' => 'Espèces',
    'source_type' => 'inscriptions',
    'source_id' => 100,
    'evenement_type' => 'encaissement',
    'motif' => 'Encaissement Inscription Test',
    'user_id' => 2
]);

ComptabiliteService::genererEcritureAutomatique(
    'inscription',
    50000.00,
    1,
    'REC-100',
    2,
    'inscriptions',
    100,
    date('Y-m-d')
);

$caisse = CompteFinancier::findById(1);
assert_check((float)$caisse['solde_courant'] === 60000.00, "Solde courant de caisse exact après encaissement (60 000 FCFA).");

// Test 3: Cashier closure submission (no movements created)
echo "\nTest 3: Soumission de clôture par le caissier (solde_reel = 60000 FCFA)...\n";
SessionCaisse::cloturer($sessId, 60000.00, '', 60000.00, 0.00);
$mvtCountClosure = (int)$pdo->query("SELECT COUNT(*) FROM mouvements_tresorerie")->fetchColumn();
assert_check($mvtCountClosure === 1, "La soumission de clôture ne crée aucun mouvement de trésorerie prématuré.");

// Test 4: Admin approval & vault transfer
echo "\nTest 4: Approbation administrative par le chef comptable...\n";
SessionCaisse::approuver($sessId, 1, "Validation conforme sans écart");

$caisseAfter = CompteFinancier::findById(1);
$coffreAfter = CompteFinancier::findById(4);
assert_check((float)$caisseAfter['solde_courant'] === 0.00, "Solde final de caisse après remise au coffre = 0 FCFA.");
assert_check((float)$coffreAfter['solde_courant'] === 60000.00, "Solde final du coffre après réception remise = 60 000 FCFA.");

// Test 5: Post-validation UI reconciliation in SessionCaisseController::show() logic
echo "\nTest 5: Vérification de la non-duplication et réconciliation d'affichage post-validation...\n";
$stmt_mvt_all = $pdo->prepare("SELECT * FROM mouvements_tresorerie WHERE session_caisse_id = :session_id AND lycee_id = :lycee_id ORDER BY date_mouvement DESC");
$stmt_mvt_all->execute(['session_id' => $sessId, 'lycee_id' => 1]);
$allMvts = $stmt_mvt_all->fetchAll(PDO::FETCH_ASSOC);

$operationalMovements = [];
$transferMovements = [];
$totalEntreesOps = 0.00;
$totalSortiesOps = 0.00;

foreach ($allMvts as $m) {
    if (in_array($m['evenement_type'], ['remise_coffre_sortie', 'remise_coffre_entree'])) {
        $transferMovements[] = $m;
    } else {
        $operationalMovements[] = $m;
        if ((int)$m['compte_id'] === 1) {
            if ($m['type_mouvement'] === 'entree') {
                $totalEntreesOps += (float)$m['montant'];
            } else {
                $totalSortiesOps += (float)$m['montant'];
            }
        }
    }
}

assert_check(count($operationalMovements) === 1, "Les mouvements opérationnels sont exactement au nombre de 1 (l'encaissement).");
assert_check(count($transferMovements) === 2, "Les mouvements de transfert interne (Caisse->Coffre) sont isolés dans une section séparée (2 mouvements).");
assert_check($totalEntreesOps === 50000.00, "Total entrées opérationnelles exact = 50 000 FCFA (pas de duplication à 110 000 FCFA !).");

$soldeTheoriqueOps = 10000.00 + $totalEntreesOps - $totalSortiesOps;
assert_check($soldeTheoriqueOps === 60000.00, "Solde théorique opérationnel exact = 60 000 FCFA.");

// Test 6: Revenue / Products accounting isolation
echo "\nTest 6: Isolation du compte de produits (Classe 7 - Recettes de scolarité)...\n";
$totalProduits = (float)$pdo->query("SELECT SUM(credit) - SUM(debit) FROM ecritures_comptables e JOIN comptes_comptables c ON e.compte_comptable_id = c.id WHERE c.classe = 7")->fetchColumn();
assert_check($totalProduits === 50000.00, "Le total des recettes réelles (Classe 7) est de 50 000 FCFA et n'a pas été affecté par le transfert inter-comptes.");

// Test 7: Double validation protection
echo "\nTest 7: Vérification du blocage de double validation concourante...\n";
try {
    SessionCaisse::approuver($sessId, 1, "Tentative répétée");
    echo "  [FAIL] La double validation n'a pas été bloquée !\n";
    exit(1);
} catch (Exception $e) {
    assert_check(strpos($e->getMessage(), "Seule une session") !== false, "Double validation bloquée avec succès par le verrou de statut.");
}

echo "\n=========================================================================\n";
echo "🏆 TOUS LES TESTS DE NON-DUPLICATION ET RECONCILIATION DE CAISSE PASSENT !\n";
echo "=========================================================================\n";

Database::setInstance(null);
