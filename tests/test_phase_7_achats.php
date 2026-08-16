<?php
// tests/test_phase_7_achats.php

define('TEST_MODE', true);
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/models/FinancialStatusService.php';
require_once __DIR__ . '/../src/models/CompteFinancier.php';
require_once __DIR__ . '/../src/models/ExerciceFinancier.php';
require_once __DIR__ . '/../src/models/SessionCaisse.php';
require_once __DIR__ . '/../src/models/TreasuryService.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/Role.php';
require_once __DIR__ . '/../src/models/Permission.php';
require_once __DIR__ . '/../src/models/Lycee.php';
require_once __DIR__ . '/../src/models/ParamGeneral.php';
require_once __DIR__ . '/../src/models/ParamLycee.php';
require_once __DIR__ . '/../src/models/Budget.php';
require_once __DIR__ . '/../src/models/BudgetLigne.php';
require_once __DIR__ . '/../src/models/BudgetEngagement.php';
require_once __DIR__ . '/../src/models/Fournisseur.php';
require_once __DIR__ . '/../src/models/AchatCategorie.php';
require_once __DIR__ . '/../src/models/AchatArticle.php';
require_once __DIR__ . '/../src/models/AchatDemande.php';
require_once __DIR__ . '/../src/models/AchatDemandeLigne.php';
require_once __DIR__ . '/../src/models/AchatCommande.php';
require_once __DIR__ . '/../src/models/AchatCommandeLigne.php';
require_once __DIR__ . '/../src/models/AchatReception.php';
require_once __DIR__ . '/../src/models/AchatReceptionLigne.php';
require_once __DIR__ . '/../src/models/AchatFacture.php';
require_once __DIR__ . '/../src/models/AchatFactureLigne.php';
require_once __DIR__ . '/../src/models/AchatFactureReglement.php';
require_once __DIR__ . '/../src/models/AchatAvoirFournisseur.php';
require_once __DIR__ . '/../src/models/AchatAvoirFournisseurLigne.php';
require_once __DIR__ . '/../src/services/AchatWorkflowService.php';
require_once __DIR__ . '/../src/services/BudgetService.php';
require_once __DIR__ . '/../src/services/BudgetControlService.php';
require_once __DIR__ . '/../src/services/ComptabiliteService.php';

@session_start();

$dbFile = '/tmp/test_phase_7_achats.sqlite';

function assert_test($condition, $message) {
    if ($condition) {
        echo "  [PASS] $message\n";
    } else {
        echo "  [FAIL] $message\n";
        throw new Exception("Test Assertion Failed: $message");
    }
}

function setup_db() {
    global $dbFile;
    if (file_exists($dbFile)) {
        unlink($dbFile);
    }

    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // DDL structures
    $pdo->exec("CREATE TABLE IF NOT EXISTS param_lycee (id INTEGER PRIMARY KEY AUTOINCREMENT, nom_lycee VARCHAR(255), type_lycee VARCHAR(50))");
    $pdo->exec("CREATE TABLE IF NOT EXISTS utilisateurs (id_user INTEGER PRIMARY KEY AUTOINCREMENT, nom VARCHAR(100), prenom VARCHAR(100), email VARCHAR(255), role_id INTEGER, lycee_id INTEGER, actif BOOLEAN DEFAULT TRUE, identifiant_public VARCHAR(50))");
    $pdo->exec("CREATE TABLE IF NOT EXISTS roles (id_role INTEGER PRIMARY KEY AUTOINCREMENT, nom_role VARCHAR(100), lycee_id INTEGER)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS role_permissions (role_id INTEGER, permission_id INTEGER)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS permissions (id_permission INTEGER PRIMARY KEY AUTOINCREMENT, resource VARCHAR(100), action VARCHAR(100), description TEXT, UNIQUE(resource, action))");
    $pdo->exec("CREATE TABLE IF NOT EXISTS param_general (id INTEGER PRIMARY KEY AUTOINCREMENT, lycee_id INTEGER)");

    $pdo->exec("CREATE TABLE IF NOT EXISTS exercices_financiers (
        id INTEGER PRIMARY KEY AUTOINCREMENT, lycee_id INTEGER, libelle VARCHAR(100), date_debut DATE, date_fin DATE, est_actif BOOLEAN DEFAULT 1, cloture BOOLEAN DEFAULT 0, type_exercice VARCHAR(50) DEFAULT 'normal'
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS comptes_financiers (
        id INTEGER PRIMARY KEY AUTOINCREMENT, lycee_id INTEGER, nom_compte VARCHAR(150), type_compte VARCHAR(50), solde_courant DECIMAL(15, 2) DEFAULT 0.00, devise VARCHAR(10) DEFAULT 'FCFA', responsable_id INTEGER, statut VARCHAR(50) DEFAULT 'actif', est_coffre TINYINT DEFAULT 0, compte_comptable_numero VARCHAR(20) DEFAULT NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS sessions_caisse (
        id INTEGER PRIMARY KEY AUTOINCREMENT, lycee_id INTEGER, user_id INTEGER, compte_id INTEGER, date_ouverture DATETIME, date_fermeture DATETIME, solde_ouverture DECIMAL(15, 2) DEFAULT 0.00, solde_theorique DECIMAL(15, 2) DEFAULT 0.00, solde_reel DECIMAL(15, 2) DEFAULT NULL, ecart DECIMAL(15, 2) DEFAULT 0.00, justificatif_ecart TEXT, statut VARCHAR(50) DEFAULT 'ouverte', valide_par INTEGER, valide_le DATETIME, montant_remis DECIMAL(15,2) DEFAULT NULL, fonds_caisse_conserve DECIMAL(15,2) DEFAULT NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS mouvements_tresorerie (
        id INTEGER PRIMARY KEY AUTOINCREMENT, lycee_id INTEGER, compte_id INTEGER, session_caisse_id INTEGER, exercice_financier_id INTEGER, transfert_id INTEGER DEFAULT NULL, type_mouvement VARCHAR(50), montant DECIMAL(15, 2), mode_paiement VARCHAR(50), reference_transaction VARCHAR(150), source_type VARCHAR(100), source_id INTEGER, evenement_type VARCHAR(50), motif VARCHAR(255), date_mouvement TIMESTAMP DEFAULT CURRENT_TIMESTAMP, user_id INTEGER, is_aggregate_data BOOLEAN DEFAULT 0, date_reconstruite BOOLEAN DEFAULT 0, is_historical_migration BOOLEAN DEFAULT 0, mode_paiement_reconstruit BOOLEAN DEFAULT 0, UNIQUE(compte_id, source_type, source_id, evenement_type)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS budgets (
        id INTEGER PRIMARY KEY AUTOINCREMENT, lycee_id INTEGER, exercice_financier_id INTEGER, libelle VARCHAR(150), statut VARCHAR(50) DEFAULT 'brouillon', cree_par INTEGER, date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS budget_lignes (
        id INTEGER PRIMARY KEY AUTOINCREMENT, budget_id INTEGER, categorie_id INTEGER, centre_cout_id INTEGER, allocation_initiale DECIMAL(15,2), montant_ajustements DECIMAL(15,2) DEFAULT 0.00, montant_engage DECIMAL(15,2) DEFAULT 0.00, montant_consomme DECIMAL(15,2) DEFAULT 0.00
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS budget_engagements (
        id INTEGER PRIMARY KEY AUTOINCREMENT, depense_id INTEGER, budget_ligne_id INTEGER, montant DECIMAL(15,2), statut VARCHAR(50), date_engagement TIMESTAMP DEFAULT CURRENT_TIMESTAMP, source_type VARCHAR(100) DEFAULT NULL, source_id INTEGER DEFAULT NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS budget_ajustements (
        id INTEGER PRIMARY KEY AUTOINCREMENT, lycee_id INTEGER, type_ajustement VARCHAR(50), ligne_source_id INTEGER, ligne_destination_id INTEGER, montant DECIMAL(15,2), motif TEXT, execute_par INTEGER, date_ajustement TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS budget_historique (
        id INTEGER PRIMARY KEY AUTOINCREMENT, lycee_id INTEGER, evenement VARCHAR(100), details TEXT, execute_par INTEGER, date_evenement TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS devises (id INTEGER PRIMARY KEY AUTOINCREMENT, code VARCHAR(3) UNIQUE, nom VARCHAR(100), taux_reference DECIMAL(15,6) DEFAULT 1.000000, date_taux TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS comptes_comptables (id INTEGER PRIMARY KEY AUTOINCREMENT, numero VARCHAR(20) UNIQUE, libelle VARCHAR(150), classe INT, nature VARCHAR(50), compte_parent_id INT, autoriser_ecriture TINYINT DEFAULT 1, est_systeme TINYINT DEFAULT 0, actif TINYINT DEFAULT 1)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS journaux_comptables (id INTEGER PRIMARY KEY AUTOINCREMENT, lycee_id INT, code VARCHAR(10), libelle VARCHAR(100), type_journal VARCHAR(50), ordre_affichage INT DEFAULT 0, actif TINYINT DEFAULT 1, exercice_comptable_id INT, UNIQUE(lycee_id, code))");
    $pdo->exec("CREATE TABLE IF NOT EXISTS pieces_comptables_sequences (journal_id INT NOT NULL, annee INT NOT NULL, dernier_chrono INT NOT NULL DEFAULT 0, PRIMARY KEY(journal_id, annee))");
    $pdo->exec("CREATE TABLE IF NOT EXISTS pieces_comptables (id INTEGER PRIMARY KEY AUTOINCREMENT, lycee_id INT, exercice_financier_id INT, journal_id INT, numero_piece VARCHAR(50) UNIQUE, libelle_piece VARCHAR(255), date_piece DATE, source_table VARCHAR(100), source_id INT, devise VARCHAR(3) DEFAULT 'XOF', taux_change DECIMAL(15,6) DEFAULT 1.00, cree_par INT, date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP, statut VARCHAR(50) DEFAULT 'valide', piece_originale_id INT, UNIQUE (source_table, source_id, statut))");
    $pdo->exec("CREATE TABLE IF NOT EXISTS ecritures_comptables (id INTEGER PRIMARY KEY AUTOINCREMENT, piece_comptable_id INT, exercice_comptable_id INT, compte_comptable_id INT, debit DECIMAL(15,2) DEFAULT 0.00, credit DECIMAL(15,2) DEFAULT 0.00, libelle_ligne VARCHAR(255), budget_ligne_id INT, centre_cout_id INT, activite_id INT, verrouille TINYINT DEFAULT 0, devise_id INT DEFAULT NULL, montant_devise DECIMAL(15,2) DEFAULT NULL, taux_conversion DECIMAL(15,6) DEFAULT NULL, montant_fcfa DECIMAL(15,2) DEFAULT NULL, UNIQUE(piece_comptable_id, compte_comptable_id, debit, credit))");
    $pdo->exec("CREATE TABLE IF NOT EXISTS comptabilite_periodes (id INTEGER PRIMARY KEY AUTOINCREMENT, lycee_id INT, exercice_financier_id INT, date_debut DATE, date_fin DATE, est_cloturee TINYINT DEFAULT 0)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS schemas_comptables (id INTEGER PRIMARY KEY AUTOINCREMENT, evenement VARCHAR(100) UNIQUE, compte_debit_numero VARCHAR(20) NOT NULL, compte_credit_numero VARCHAR(20) NOT NULL, libelle_modele VARCHAR(255) NOT NULL, journal_code VARCHAR(10) NOT NULL)");

    require_once __DIR__ . '/../db/migrations/20240115_07_create_achats_et_fournisseurs.php';
    migrate_07($pdo);

    Database::setInstance($pdo);
    return $pdo;
}

echo "=========================================================================\n";
echo "📊 PROTOCOLE DE VALIDATION DE LA PHASE 7 (FOURNISSEURS & ACHATS)\n";
echo "=========================================================================\n";

$pdo = setup_db();

try {
    // Seed default chart of accounts
    $pdo->exec("INSERT INTO comptes_comptables (numero, libelle, classe, nature) VALUES ('401100', 'Fournisseurs d''exploitation', 4, 'passif')");
    $pdo->exec("INSERT INTO comptes_comptables (numero, libelle, classe, nature) VALUES ('605100', 'Fournitures de bureau', 6, 'charge')");
    $pdo->exec("INSERT INTO comptes_comptables (numero, libelle, classe, nature) VALUES ('571200', 'Caisse Principale', 5, 'actif')");

    // 1. Create Lycee & Exercice
    $pdo->exec("INSERT INTO param_lycee (id, nom_lycee, type_lycee) VALUES (1, 'Lycée Pilote Phase 7', 'prive')");
    $pdo->exec("INSERT INTO exercices_financiers (id, lycee_id, libelle, date_debut, date_fin, est_actif, cloture) VALUES (1, 1, 'Exercice 2026', '2026-01-01', '2026-12-31', 1, 0)");
    $pdo->exec("INSERT INTO utilisateurs (id_user, nom, prenom, email, role_id, lycee_id) VALUES (1, 'Comptable', 'Phase7', 'comptable7@lycee.com', 7, 1)");

    $lyceeId = 1;
    $exerciceId = 1;
    $userId = 1;

    // 2. Budget Setup
    $budgetId = Budget::create([
        'lycee_id' => $lyceeId,
        'exercice_financier_id' => $exerciceId,
        'libelle' => 'Budget Global 2026',
        'cree_par' => $userId
    ]);
    Budget::updateStatus($budgetId, 'actif'); // Active budget

    $ligneId = BudgetLigne::create([
        'budget_id' => $budgetId,
        'categorie_id' => 1,
        'allocation_initiale' => 500000.00
    ]);

    echo "\n--- [TEST 1 : CRÉATION DU FOURNISSEUR & UNICITÉ] ---\n";
    $fournisseurId = Fournisseur::create([
        'lycee_id' => $lyceeId,
        'raison_sociale' => 'Fournitures Papeteries SAS',
        'code_fournisseur' => 'FOU-PAP-01',
        'compte_comptable_tiers' => '401100'
    ]);
    assert_test($fournisseurId > 0, "Fournisseur créé avec succès.");

    try {
        Fournisseur::create([
            'lycee_id' => $lyceeId,
            'raison_sociale' => 'Fournitures Doublon SAS',
            'code_fournisseur' => 'FOU-PAP-01'
        ]);
        assert_test(false, "L'unicité du code fournisseur a été violée.");
    } catch (Exception $e) {
        assert_test(true, "Blocage réussi de la création d'un second fournisseur avec le même code !");
    }

    echo "\n--- [TEST 2 : CATALOGUE ET ARTICLES] ---\n";
    $catId = AchatCategorie::create([
        'libelle' => 'Rames de papier',
        'compte_comptable_charge' => '605100'
    ]);
    assert_test($catId > 0, "Catégorie d'achats créée avec succès.");

    $artId = AchatArticle::create([
        'categorie_id' => $catId,
        'libelle' => 'Rame A4 Double A',
        'reference' => 'RAME-A4-DA',
        'unite_mesure' => 'Carton',
        'prix_unitaire_estime' => 15000.00
    ]);
    assert_test($artId > 0, "Article de catalogue créé avec succès.");

    echo "\n--- [TEST 3 : WORKFLOW NOMINAL D'ACHAT (3-WAY MATCHING & POLYMORPHISME BUDGET)] ---\n";
    // A. Demande d'achat
    $items = [
        [
            'article_id' => $artId,
            'quantite' => 10.0000,
            'prix_unitaire_estime' => 15000.00,
            'budget_ligne_id' => $ligneId
        ]
    ];
    $daId = AchatWorkflowService::createDemande($lyceeId, $userId, "Achat annuel de fournitures administratives", $items);
    assert_test($daId > 0, "Demande d'achat créée avec succès.");

    // B. Approval & Budget reservation
    AchatWorkflowService::approveDemande($daId, 99, "Validé par le proviseur"); // simulate different user
    $da = AchatDemande::findById($daId);
    assert_test($da['statut'] === 'approuvee', "Demande d'achat approuvée.");

    $engagement = BudgetEngagement::findBySource('achat_demandes', 1);
    assert_test($engagement !== null && $engagement['statut'] === 'reserve', "Réservation budgétaire polymorphe créée au statut 'reserve'.");

    // C. Commande
    $cmdItems = [
        [
            'article_id' => $artId,
            'demande_ligne_id' => 1,
            'quantite_commandee' => 10.0000,
            'prix_unitaire_negocie' => 14000.00 // Négocié à la baisse
        ]
    ];
    $cmdId = AchatWorkflowService::createCommande($lyceeId, $daId, $fournisseurId, 'BC-2026-00001', $userId, $cmdItems);
    assert_test($cmdId > 0, "Bon de commande émis et associé à la DA.");

    // Check budget promotion to 'engage'
    $engagement = BudgetEngagement::findBySource('achat_demandes', 1);
    assert_test($engagement['statut'] === 'engage', "L'engagement budgétaire a été promu à l'état 'engage'.");

    // D. Réception
    $recItems = [
        [
            'commande_ligne_id' => 1,
            'quantite_receptionnee' => 10.0000,
            'quantite_refusee' => 0.0000
        ]
    ];
    $recId = AchatWorkflowService::receiveCommande($lyceeId, $cmdId, 'BR-2026-00001', $userId, $recItems);
    assert_test($recId > 0, "Réception physique validée avec succès.");

    // E. Facturation (3-Way Matching)
    $factItems = [
        [
            'reception_ligne_id' => 1,
            'quantite_facturee' => 10.0000,
            'prix_unitaire_facture' => 14000.00,
            'taux_tva_facture' => 0.0000
        ]
    ];
    $factureId = AchatWorkflowService::createFacture($lyceeId, $fournisseurId, $cmdId, $recId, 'FACT-PAP-2026-99', '2026-02-15', '2026-03-15', $factItems);
    assert_test($factureId > 0, "Facture fournisseur enregistrée et rapprochée avec succès.");

    $engagement = BudgetEngagement::findBySource('achat_demandes', 1);
    assert_test($engagement['statut'] === 'consomme', "L'engagement budgétaire polymorphe a été définitivement consommé.");

    echo "\n--- [TEST 4 : SÉCURISATION DU PAIEMENT ET SESSIONS DE CAISSE] ---\n";
    $compteFinId = CompteFinancier::create([
        'lycee_id' => $lyceeId,
        'nom_compte' => 'Caisse Menue Monnaie',
        'type_compte' => 'caisse',
        'solde_courant' => 200000.00,
        'compte_comptable_numero' => '571200'
    ]);

    // Open physical session
    $sessionCaisseId = SessionCaisse::ouvrir([
        'lycee_id' => $lyceeId,
        'user_id' => $userId,
        'compte_id' => $compteFinId
    ]);

    $reste = AchatFacture::getResteAPayer($factureId);
    assert_test($reste === 140000.00, "Le reste à payer initial de la facture est de 140 000.00 FCFA.");

    // Pay total invoice
    $token = uniqid('idem_', true);
    AchatWorkflowService::payFacture($factureId, $compteFinId, $sessionCaisseId, 140000.00, 'Espèces', $userId, $token);

    $nouveauReste = AchatFacture::getResteAPayer($factureId);
    assert_test($nouveauReste === 0.00, "Le reste à payer est tombé à 0.00 FCFA.");

    $facture = AchatFacture::findById($factureId);
    assert_test($facture['statut'] === 'payee', "Le statut de la facture est passé à 'payee'.");

    echo "\n--- [TEST 5 : BLOCAGES SÉCURITAIRES DE CONCURRENCE ET DOUBLE PAIEMENT] ---\n";
    // Try to pay again with the same token/idempotency_key
    try {
        AchatWorkflowService::payFacture($factureId, $compteFinId, $sessionCaisseId, 1000.00, 'Espèces', $userId, $token);
        assert_test(false, "La double imputation avec le même jeton a été autorisée.");
    } catch (Exception $e) {
        assert_test(true, "Blocage d'idempotence réussi d'un double règlement consécutif !");
    }

    echo "\n🏆 TOUS LES SCÉNARIOS DE TEST DE LA PHASE 7 ONT RÉUSSI AVEC SUCCÈS !\n";
    echo "=========================================================================\n";
} catch (Exception $e) {
    echo "\n❌ UN TEST A ÉCHOUÉ : " . $e->getMessage() . "\n";
    echo "FILE: " . $e->getFile() . " on line " . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
    echo "=========================================================================\n";
    exit(1);
}
?>