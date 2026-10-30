<?php

// Test suite for Phase 2: Financial User ID Security & Validation
define('TEST_MODE', true);

require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/Role.php';
require_once __DIR__ . '/../src/models/Budget.php';
require_once __DIR__ . '/../src/models/BudgetLigne.php';
require_once __DIR__ . '/../src/models/BudgetEngagement.php';
require_once __DIR__ . '/../src/models/BudgetHistorique.php';
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
require_once __DIR__ . '/../src/services/ComptabiliteService.php';
require_once __DIR__ . '/../src/services/BudgetService.php';
require_once __DIR__ . '/../src/services/AchatWorkflowService.php';

function run_phase2_tests() {
    echo "=====================================================\n";
    echo "Running Test Suite: Phase 2 Financial User ID Fix\n";
    echo "=====================================================\n";

    $db = Database::getInstance();

    try {
        // Clean old test rows
        $db->exec("DELETE FROM budget_historique WHERE details LIKE '%Ligne P2 Test%' OR details LIKE '%source test_source%'");
        $db->exec("DELETE FROM budget_engagements WHERE source_type = 'test_source'");
        $db->exec("DELETE FROM achat_facture_lignes WHERE facture_id IN (SELECT id FROM achat_factures WHERE reference_facture LIKE 'FAC-P2-%')");
        $db->exec("DELETE FROM achat_factures WHERE reference_facture LIKE 'FAC-P2-%'");
        $db->exec("DELETE FROM achat_reception_lignes WHERE reception_id IN (SELECT id FROM achat_receptions WHERE numero_reception = 'REC-P2-01')");
        $db->exec("DELETE FROM achat_receptions WHERE numero_reception = 'REC-P2-01'");
        $db->exec("DELETE FROM achat_commande_lignes WHERE commande_id IN (SELECT id FROM achat_commandes WHERE numero_commande = 'CMD-P2-01')");
        $db->exec("DELETE FROM achat_commandes WHERE numero_commande = 'CMD-P2-01'");
        $db->exec("DELETE FROM achat_demande_lignes WHERE demande_id IN (SELECT id FROM achat_demandes WHERE justification = 'Demande P2')");
        $db->exec("DELETE FROM achat_demandes WHERE justification = 'Demande P2'");
        $db->exec("DELETE FROM budget_lignes WHERE budget_id IN (SELECT id FROM budgets WHERE libelle = 'Test Budget P2')");
        $db->exec("DELETE FROM budgets WHERE libelle = 'Test Budget P2'");
        $db->exec("DELETE FROM achat_articles WHERE reference = 'REF-P2'");
        $db->exec("DELETE FROM achat_categories WHERE libelle = 'Cat P2'");
        $db->exec("DELETE FROM fournisseurs WHERE code_fournisseur = 'F-P2'");

        // Create Accounting Accounts 601000 and 401000
        $db->exec("INSERT OR IGNORE INTO comptes_comptables (numero, libelle, classe, nature, actif)
                   VALUES ('601000', 'Achats de fournitures', 6, 'charge', 1)");
        $db->exec("INSERT OR IGNORE INTO comptes_comptables (numero, libelle, classe, nature, actif)
                   VALUES ('401000', 'Fournisseurs', 4, 'tiers', 1)");

        // --- Setup Test Data ---
        $passHash = password_hash('pass', PASSWORD_DEFAULT);
        $db->exec("DELETE FROM utilisateurs WHERE id_user IN (25, 42)");

        // Create User 25
        $stmtU25 = $db->prepare("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, email, mot_de_passe, role_id, actif, auth_version)
                   VALUES (25, 1, 'User25', 'Financial', 'user25@test.com', :pass, 1, 1, 1)");
        $stmtU25->execute(['pass' => $passHash]);

        // Create User 42
        $stmtU42 = $db->prepare("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, email, mot_de_passe, role_id, actif, auth_version)
                   VALUES (42, 1, 'User42', 'Financial', 'user42@test.com', :pass, 1, 1, 1)");
        $stmtU42->execute(['pass' => $passHash]);

        // Create Financial Exercise
        $db->exec("INSERT OR IGNORE INTO exercices_financiers (id, lycee_id, libelle, date_debut, date_fin, est_actif, cloture)
                   VALUES (99, 1, 'Ex P2 2025', '2025-01-01', '2025-12-31', 1, 0)");

        // Create Budget & Line
        $budgetId = Budget::create([
            'lycee_id' => 1, 'exercice_financier_id' => 99, 'libelle' => 'Test Budget P2',
            'statut' => 'actif', 'cree_par' => 25
        ]);

        // Create Vendor & Article Category
        $fournisseurId = Fournisseur::create([
            'lycee_id' => 1, 'raison_sociale' => 'Fournisseur P2', 'code_fournisseur' => 'F-P2',
            'compte_comptable_tiers' => '401000', 'actif' => 1
        ]);

        $catId = AchatCategorie::create([
            'libelle' => 'Cat P2', 'compte_comptable_charge' => '601000'
        ]);

        $ligneId = BudgetLigne::create([
            'budget_id' => $budgetId, 'categorie_id' => $catId, 'allocation_initiale' => 500000
        ]);

        $articleId = AchatArticle::create([
            'lycee_id' => 1, 'categorie_id' => $catId, 'code_article' => 'ART-P2', 'reference' => 'REF-P2',
            'libelle' => 'Article P2', 'unite_mesure' => 'Unité', 'prix_unitaire_estime' => 1000, 'actif' => 1
        ]);

        // Create Purchase Demande, Commande, Reception
        $demandeId = AchatWorkflowService::createDemande(1, 25, 'Demande P2', [
            ['article_id' => $articleId, 'quantite' => 10, 'prix_unitaire_estime' => 1000, 'budget_ligne_id' => $ligneId]
        ]);

        AchatDemande::updateStatus($demandeId, 'approuvee', 25, 'Approuve P2');

        $commandeId = AchatWorkflowService::createCommande(1, $demandeId, $fournisseurId, 'CMD-P2-01', 25, [
            ['demande_ligne_id' => null, 'article_id' => $articleId, 'quantite_commandee' => 10, 'prix_unitaire_negocie' => 1000]
        ]);

        $cmdLignes = AchatCommandeLigne::findByCommande($commandeId);
        $cmdLigneId = $cmdLignes[0]['id'];

        $receptionId = AchatWorkflowService::receiveCommande(1, $commandeId, 'REC-P2-01', 25, [
            ['commande_ligne_id' => $cmdLigneId, 'quantite_receptionnee' => 10]
        ]);

        $recLignes = AchatReceptionLigne::findByReception($receptionId);
        $recLigneId = $recLignes[0]['id'];

        // ----------------------------------------------------
        // TEST 1: User connected with ID 25 is recorded in budget_historique (NOT ID 1)
        // ----------------------------------------------------
        echo "  [Test 1] BudgetService::reserve with User 25...\n";
        Auth::logout();
        Auth::login('user25@test.com', 'pass');

        $countBefore1 = (int)$db->query("SELECT COUNT(*) FROM budget_historique WHERE execute_par = 25")->fetchColumn();
        $engagementId = BudgetService::reserve(0, 50000, $ligneId, 'test_source', 101);

        $lastHist = $db->query("SELECT * FROM budget_historique ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);

        if ($lastHist && (int)$lastHist['execute_par'] === 25 && (int)$lastHist['execute_par'] !== 1) {
            echo "    PASS: Budget reservation logged execute_par = 25 (not user 1).\n";
        } else {
            throw new Exception("FAIL: Budget reservation logged wrong user ID: " . ($lastHist['execute_par'] ?? 'null'));
        }

        // ----------------------------------------------------
        // TEST 2: User connected with ID 42 is recorded in pieces_comptables (NOT ID 1)
        // ----------------------------------------------------
        echo "  [Test 2] AchatWorkflowService::createFacture with User 42...\n";
        Auth::logout();
        Auth::login('user42@test.com', 'pass');

        $factureId = AchatWorkflowService::createFacture(
            1, $fournisseurId, $commandeId, $receptionId, 'FAC-P2-42', date('Y-m-d'), date('Y-m-d'),
            [['reception_ligne_id' => $recLigneId, 'quantite_facturee' => 10, 'prix_unitaire_facture' => 1000]]
        );

        $facture = AchatFacture::findById($factureId);
        $pieceId = $facture['piece_comptable_id'];
        $piece = $db->query("SELECT * FROM pieces_comptables WHERE id = " . (int)$pieceId)->fetch(PDO::FETCH_ASSOC);

        if ($piece && (int)$piece['cree_par'] === 42 && (int)$piece['cree_par'] !== 1) {
            echo "    PASS: Invoice accounting voucher logged cree_par = 42 (not user 1).\n";
        } else {
            throw new Exception("FAIL: Invoice accounting voucher logged wrong cree_par: " . ($piece['cree_par'] ?? 'null'));
        }

        // ----------------------------------------------------
        // TEST 3 & 4: Unauthenticated or invalid user ID throws exception
        // ----------------------------------------------------
        echo "  [Test 3 & 4] Refusal on unauthenticated / invalid user ID...\n";
        Auth::logout(); // Clear session

        $unauthBudgetRefused = false;
        try {
            BudgetService::reserve(null, 10000, $ligneId, 'test_unauth', 102);
        } catch (Exception $e) {
            if (str_contains($e->getMessage(), 'utilisateur authentifié')) {
                $unauthBudgetRefused = true;
            }
        }

        $unauthFactureRefused = false;
        try {
            AchatWorkflowService::createFacture(
                1, $fournisseurId, $commandeId, $receptionId, 'FAC-P2-UNAUTH', date('Y-m-d'), date('Y-m-d'),
                [['reception_ligne_id' => $recLigneId, 'quantite_facturee' => 10, 'prix_unitaire_facture' => 1000]]
            );
        } catch (Exception $e) {
            if (str_contains($e->getMessage(), 'utilisateur authentifié')) {
                $unauthFactureRefused = true;
            }
        }

        if ($unauthBudgetRefused && $unauthFactureRefused) {
            echo "    PASS: Unauthenticated calls cleanly refused with clear exceptions.\n";
        } else {
            throw new Exception("FAIL: Unauthenticated call was not properly refused!");
        }

        // ----------------------------------------------------
        // TEST 5 & 6: Zero records created & zero open transactions after refusal
        // ----------------------------------------------------
        echo "  [Test 5 & 6] No orphan records & transactions closed after refusal...\n";
        $unauthFactureCount = (int)$db->query("SELECT COUNT(*) FROM achat_factures WHERE reference_facture = 'FAC-P2-UNAUTH'")->fetchColumn();
        $inTx = $db->inTransaction();

        if ($unauthFactureCount === 0 && !$inTx) {
            echo "    PASS: Zero orphan records created and no transaction left open after refusal.\n";
        } else {
            throw new Exception("FAIL: Orphan records created or broken transaction state!");
        }

        // ----------------------------------------------------
        // TEST 7: Verification that no '?? 1' fallback exists in source files
        // ----------------------------------------------------
        echo "  [Test 7] Verification of code source files...\n";
        $bsCode = file_get_contents(__DIR__ . '/../src/services/BudgetService.php');
        $awsCode = file_get_contents(__DIR__ . '/../src/services/AchatWorkflowService.php');

        if (!str_contains($bsCode, 'id_user\'] ?? 1') && !str_contains($awsCode, 'id_user\'] ?? 1')) {
            echo "    PASS: Verified complete removal of '?? 1' fallback from both target services.\n";
        } else {
            throw new Exception("FAIL: Fallback '?? 1' still present in source code!");
        }

        echo "\n=====================================================\n";
        echo " ALL PHASE 2 TEST SCENARIOS PASSED SUCCESSFULLY!\n";
        echo "=====================================================\n";

    } finally {
        Auth::logout();
    }
}

run_phase2_tests();
?>