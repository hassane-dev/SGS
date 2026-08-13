<?php
// tests/test_phase_9_reporting.php

define('TEST_MODE', true);
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/models/Lycee.php';
require_once __DIR__ . '/../src/models/AnneeAcademique.php';
require_once __DIR__ . '/../src/services/KpiService.php';
require_once __DIR__ . '/../src/services/ReportingService.php';
require_once __DIR__ . '/../src/services/ForecastService.php';
require_once __DIR__ . '/../src/controllers/ReportingController.php';

@session_start();

$dbFile = '/tmp/test_phase_9.sqlite';

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

    // Copy real database to test db file
    copy(__DIR__ . '/../database.sqlite', $dbFile);

    // Connect to test db and override Database singleton
    $pdo = new PDO("sqlite:$dbFile");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    Database::setInstance($pdo);

    // Let's truncate or clean the dynamic analytical tables to ensure a clean slate
    $db = Database::getInstance();
    $db->exec("DELETE FROM reporting_snapshots");
    $db->exec("DELETE FROM reporting_kpi_seuils");
    $db->exec("DELETE FROM reporting_previsions_config");
    $db->exec("DELETE FROM reporting_audit_logs");
}

try {
    echo "=========================================================================\n";
    echo "🧪 SUITE AUTOMATISÉE DE TESTS D'INTÉGRATION ET D'AUDIT : PHASE 9\n";
    echo "=========================================================================\n";

    setup_db();
    $db = Database::getInstance();

    // Clean other standard tables to avoid overlaps or duplicate keys, but preserve schemas
    $db->exec("DELETE FROM param_lycee");
    $db->exec("DELETE FROM annees_academiques");
    $db->exec("DELETE FROM exercices_financiers");
    $db->exec("DELETE FROM comptes_financiers");
    $db->exec("DELETE FROM comptes_comptables");
    $db->exec("DELETE FROM ecritures_comptables");
    $db->exec("DELETE FROM pieces_comptables");
    $db->exec("DELETE FROM mouvements_tresorerie");
    $db->exec("DELETE FROM roles");
    $db->exec("DELETE FROM role_permissions");

    // Seed test data
    // 1. Two Lycées
    $db->exec("INSERT INTO param_lycee (id, nom_lycee, sigle) VALUES (1, 'Lycée de l Excellence', 'L_EXC')");
    $db->exec("INSERT INTO param_lycee (id, nom_lycee, sigle) VALUES (2, 'Collège de la Trinité', 'C_TRI')");

    // 2. Academic & financial years
    $db->exec("INSERT INTO annees_academiques (id, libelle, date_debut, date_fin, est_active, cloturee) VALUES (1, '2024-2025', '2024-09-01', '2025-06-30', 1, 0)");
    $db->exec("INSERT INTO exercices_financiers (id, lycee_id, libelle, date_debut, date_fin, est_actif) VALUES (1, 1, 'Ex 2024', '2024-01-01', '2024-12-31', 1)");
    $db->exec("INSERT INTO exercices_financiers (id, lycee_id, libelle, date_debut, date_fin, est_actif) VALUES (2, 2, 'Ex 2024-B', '2024-01-01', '2024-12-31', 1)");

    // 3. Accounts
    $db->exec("INSERT INTO comptes_financiers (id, lycee_id, nom_compte, type_compte, solde_courant, statut) VALUES (10, 1, 'Caisse Centrale', 'caisse', 500000.00, 'actif')");
    $db->exec("INSERT INTO comptes_financiers (id, lycee_id, nom_compte, type_compte, solde_courant, statut) VALUES (20, 2, 'Banque Populaire', 'banque', 1200000.00, 'actif')");

    // 4. Accounts general ledger
    $db->exec("INSERT INTO comptes_comptables (id, numero, libelle, classe, nature) VALUES (1001, '521100', 'Caisse principale', 5, 'debit')");
    $db->exec("INSERT INTO comptes_comptables (id, numero, libelle, classe, nature) VALUES (1002, '706100', 'Ventes de prestations', 7, 'credit')");
    $db->exec("INSERT INTO comptes_comptables (id, numero, libelle, classe, nature) VALUES (1003, '601100', 'Achats de fournitures', 6, 'debit')");
    $db->exec("INSERT INTO comptes_comptables (id, numero, libelle, classe, nature) VALUES (1004, '401100', 'Fournisseurs d exploitation', 4, 'credit')");

    // 5. Seed standard comptable role 7 & associate its permissions
    $db->exec("INSERT INTO roles (id_role, nom_role, lycee_id) VALUES (7, 'comptable', 1)");
    $db->exec("
        INSERT INTO role_permissions (role_id, permission_id)
        SELECT 7, id_permission FROM permissions
        WHERE resource = 'reporting' AND action IN ('view', 'dashboard', 'kpis', 'export')
    ");

    echo "\n--- [SCÉNARIO 1 : CALCULS DES KPI CANONIQUES & FORMULES] ---\n";
    // Test computed liquidites_totales for Lycee 1
    $liq1 = KpiService::computeKpi('liquidites_totales', 1);
    assert_test($liq1 === 500000.00, "Le KPI Liquidités Totales pour le Lycée 1 est exact (500 000 FCFA).");

    $liq2 = KpiService::computeKpi('liquidites_totales', 2);
    assert_test($liq2 === 1200000.00, "Le KPI Liquidités Totales pour le Lycée 2 est exact (1 200 000 FCFA).");

    // Test list definition catalog
    $defs = KpiService::getDefinitions();
    assert_test(isset($defs['liquidites_totales']), "Le catalogue de définitions contient 'liquidites_totales'.");
    assert_test($defs['liquidites_totales']['categorie'] === 'trésorerie', "La catégorie de 'liquidites_totales' est bien 'trésorerie'.");
    assert_test($defs['resultat']['categorie'] === 'comptabilité', "La catégorie de 'resultat' est bien 'comptabilité'.");

    echo "\n--- [SCÉNARIO 2 : COMPTABILITÉ DOUBLE-ENTRÉE & RAPPROCHEMENTS] ---\n";
    // Seed some general ledger pieces & ecritures for Lycee 1
    // Piece 1: Receipt of 300 000 (Debit 521100, Credit 706100)
    $db->exec("INSERT INTO pieces_comptables (id, lycee_id, exercice_financier_id, journal_id, numero_piece, libelle_piece, date_piece, statut, cree_par) VALUES (1, 1, 1, 1, 'P-001', 'Recette Scolaire', '2024-05-15', 'valide', 1)");
    $db->exec("INSERT INTO ecritures_comptables (piece_comptable_id, exercice_comptable_id, compte_comptable_id, debit, credit, libelle_ligne) VALUES (1, 1, 1001, 300000.00, 0.00, 'Encaissement scolarité')");
    $db->exec("INSERT INTO ecritures_comptables (piece_comptable_id, exercice_comptable_id, compte_comptable_id, debit, credit, libelle_ligne) VALUES (1, 1, 1002, 0.00, 300000.00, 'Encaissement scolarité')");

    // Let's assert double-entry balance (Debit = Credit)
    $recons = KpiService::validateReconciliations(1);
    assert_test($recons['R5']['statut'] === 'OK', "R5 : Équilibre de la Balance Comptable Débit = Crédit validé !");
    assert_test($recons['R4']['statut'] === 'OK', "R4 : Produits - Charges = Résultat (300 000 FCFA) validé !");

    // Let's introduce an imbalance in Piece 2 (not balanced debit/credit)
    $db->exec("INSERT INTO pieces_comptables (id, lycee_id, exercice_financier_id, journal_id, numero_piece, libelle_piece, date_piece, statut, cree_par) VALUES (2, 1, 1, 1, 'P-002', 'Piece Desequilibree', '2024-05-16', 'valide', 1)");
    $db->exec("INSERT INTO ecritures_comptables (piece_comptable_id, exercice_comptable_id, compte_comptable_id, debit, credit, libelle_ligne) VALUES (2, 1, 1001, 50000.00, 0.00, 'Ligne déséquilibrée')");

    $recons_imbalanced = KpiService::validateReconciliations(1);
    assert_test($recons_imbalanced['R5']['statut'] === 'ECART', "R5 détecte correctement un écart de balance débit/crédit.");
    assert_test($recons_imbalanced['R5']['ecart'] === 50000.00, "Le montant de l'écart détecté (50 000 FCFA) est rigoureusement exact.");

    // Clean up Piece 2 to keep db clean
    $db->exec("DELETE FROM ecritures_comptables WHERE piece_comptable_id = 2");
    $db->exec("DELETE FROM pieces_comptables WHERE id = 2");

    echo "\n--- [SCÉNARIO 3 : SECURITY MULTI-LYCÉE ISOLATION & IDOR PROTECTION] ---\n";
    // Mock standard user logged in Lycee 1
    $_SESSION['user'] = [
        'id' => 45,
        'nom' => 'Kone',
        'prenom' => 'Abdoulaye',
        'role_id' => 7, // Standard Comptable
        'lycee_id' => 1,
        'permissions' => [] // Will be dynamic reloaded from test DB
    ];

    // Instantiate ReportingController
    $controller = new ReportingController();

    // Verify resolving Lycee ID defaults to session lycee_id
    // Calling protected/private methods can be simulated by triggering actions and asserting output status
    // Standard User on Lycee 1 trying to access Lycee 2 should trigger a 403
    $_GET['lycee_id'] = 2;
    ob_start();
    $controller->dashboard();
    $html = ob_get_clean();
    assert_test(strpos($html, 'Accès Refusé') !== false, "L'accès à un autre lycée (ID 2) par un utilisateur standard du Lycée 1 est strictement bloqué (Anti-IDOR).");

    // Restoring safe GET parameters
    $_GET['lycee_id'] = 1;

    echo "\n--- [SCÉNARIO 4 : PRÉVISIONS FINANCIÈRES & HORIZON] ---\n";
    // Try forecasting with no monthly history points (should reject cleanly)
    $fc = ForecastService::predict(1, 'moving_average', 3);
    assert_test($fc['statut_qualite'] === 'INSUFFISANT', "ForecastService rejette proprement les prévisions si l'historique est insuffisant (< 3 mois).");

    // Seed 4 months of monthly historical cash flow movements for Lycee 1
    $db->exec("INSERT INTO mouvements_tresorerie (lycee_id, compte_id, exercice_financier_id, type_mouvement, montant, mode_paiement, source_type, source_id, evenement_type, motif, date_mouvement, user_id) VALUES (1, 10, 1, 'entree', 100000.00, 'especes', 'scolarite', 101, 'encaissement', 'scolarite M1', '2024-01-10 10:00:00', 1)");
    $db->exec("INSERT INTO mouvements_tresorerie (lycee_id, compte_id, exercice_financier_id, type_mouvement, montant, mode_paiement, source_type, source_id, evenement_type, motif, date_mouvement, user_id) VALUES (1, 10, 1, 'entree', 150000.00, 'especes', 'scolarite', 102, 'encaissement', 'scolarite M2', '2024-02-10 10:00:00', 1)");
    $db->exec("INSERT INTO mouvements_tresorerie (lycee_id, compte_id, exercice_financier_id, type_mouvement, montant, mode_paiement, source_type, source_id, evenement_type, motif, date_mouvement, user_id) VALUES (1, 10, 1, 'entree', 200000.00, 'especes', 'scolarite', 103, 'encaissement', 'scolarite M3', '2024-03-10 10:00:00', 1)");
    $db->exec("INSERT INTO mouvements_tresorerie (lycee_id, compte_id, exercice_financier_id, type_mouvement, montant, mode_paiement, source_type, source_id, evenement_type, motif, date_mouvement, user_id) VALUES (1, 10, 1, 'entree', 250000.00, 'especes', 'scolarite', 104, 'encaissement', 'scolarite M4', '2024-04-10 10:00:00', 1)");

    // Test forecast execution with 4 months of history
    $fc_ok = ForecastService::predict(1, 'moving_average', 3, 'central');
    assert_test($fc_ok['statut_qualite'] === 'BON', "ForecastService calcule avec succès la projection lorsque l'historique est suffisant.");
    assert_test($fc_ok['valeur_prevue'] > 0, "La valeur de projection prévue est positive et cohérente.");
    assert_test(isset($fc_ok['scenarios']['prudent']), "Le scénario prudent est correctement modélisé.");
    assert_test($fc_ok['scenarios']['prudent'] < $fc_ok['scenarios']['central'], "Le scénario prudent est inférieur au scénario central comme attendu.");

    echo "\n--- [SCÉNARIO 5 : IDEMPOTENCE DES SNAPSHOTS & SEUILS] ---\n";
    // Check snapshots generation
    $snap_ok = ReportingService::generateSnapshot(1, 1, null, '2024-05-31', 'mensuelle', 'liquidites_totales', 500000.00);
    assert_test($snap_ok === true, "Génération d'un snapshot initial pour 'liquidites_totales'.");

    // Generating the exact same snapshot with updated value should perform an UPDATE (Idempotency)
    $snap_update = ReportingService::generateSnapshot(1, 1, null, '2024-05-31', 'mensuelle', 'liquidites_totales', 550000.00);
    assert_test($snap_update === true, "La mise à jour d'un snapshot existant (Idempotence) s'est déroulée avec succès.");

    // Check database count
    $stmt_cnt = $db->query("SELECT COUNT(*) FROM reporting_snapshots WHERE lycee_id = 1 AND kpi_code = 'liquidites_totales' AND date_snapshot = '2024-05-31'");
    assert_test((int)$stmt_cnt->fetchColumn() === 1, "La contrainte d'unicité a bien empêché la création de doublons.");

    // Check thresholds saving
    $th_ok = ReportingService::saveThreshold(1, 'liquidites_totales', 100000.00, 200000.00, 500000.00, 50000.00, 'croissant');
    assert_test($th_ok === true, "Enregistrement des seuils d'alerte pour le KPI 'liquidites_totales'.");

    // Evaluate visual status based on saved thresholds
    $status_evaluated = ReportingService::evaluateKpiValue(1, 'liquidites_totales', 35000.00);
    assert_test($status_evaluated === 'danger', "L'évaluation du KPI (35 000 < Seuil Danger 50 000) retourne correctement la classe visuelle 'danger'.");

    echo "\n--- [SCÉNARIO 6 : SENSIBLE OPERATION AUDIT JOURNALISATION] ---\n";
    // Log sensitive comparison
    $audit_ok = ReportingService::logAudit(45, 1, 'comparaison_multi_lycee', "Analyse comparative transversale lancée.");
    assert_test($audit_ok === true, "Journalisation de l'opération sensible d'audit.");

    $audit_count = $db->query("SELECT COUNT(*) FROM reporting_audit_logs WHERE lycee_id = 1 AND operation = 'comparaison_multi_lycee'")->fetchColumn();
    assert_test((int)$audit_count === 1, "L'opération sensible est enregistrée de façon immuable dans 'reporting_audit_logs'.");

    echo "\n=========================================================================\n";
    echo "🏆 TOUS LES TESTS D'INTÉGRATION ET DE CONFORMITÉ ONT RÉUSSI (100% GREEN) !\n";
    echo "=========================================================================\n";

} catch (Exception $e) {
    echo "\n❌ UN TEST A ÉCHOUÉ : " . $e->getMessage() . "\n";
    echo "FILE: " . $e->getFile() . " on line " . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
    echo "=========================================================================\n";
    exit(1);
}
?>
