<?php
// tests/test_phase_4.php

define('TEST_MODE', true);
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/models/FinancialStatusService.php';
require_once __DIR__ . '/../src/models/CompteFinancier.php';
require_once __DIR__ . '/../src/models/ExerciceFinancier.php';
require_once __DIR__ . '/../src/models/SessionCaisse.php';
require_once __DIR__ . '/../src/models/TreasuryService.php';
require_once __DIR__ . '/../src/models/Eleve.php';
require_once __DIR__ . '/../src/models/Etude.php';
require_once __DIR__ . '/../src/models/Inscription.php';
require_once __DIR__ . '/../src/models/Mensualite.php';
require_once __DIR__ . '/../src/models/ParamGeneral.php';
require_once __DIR__ . '/../src/models/ParamLycee.php';
require_once __DIR__ . '/../src/models/Frais.php';
require_once __DIR__ . '/../src/models/Cycle.php';
require_once __DIR__ . '/../src/models/Classe.php';
require_once __DIR__ . '/../src/models/EtatFinancierEleve.php';
require_once __DIR__ . '/../src/models/PolitiqueFinanciere.php';
require_once __DIR__ . '/../src/controllers/SessionCaisseController.php';
require_once __DIR__ . '/../src/controllers/PaiementController.php';
require_once __DIR__ . '/../src/controllers/DepenseController.php';

// Load Phase 4 classes
require_once __DIR__ . '/../src/models/Budget.php';
require_once __DIR__ . '/../src/models/BudgetLigne.php';
require_once __DIR__ . '/../src/models/BudgetAjustement.php';
require_once __DIR__ . '/../src/models/BudgetEngagement.php';
require_once __DIR__ . '/../src/models/BudgetHistorique.php';
require_once __DIR__ . '/../src/services/BudgetService.php';
require_once __DIR__ . '/../src/services/BudgetWorkflowService.php';
require_once __DIR__ . '/../src/services/BudgetControlService.php';
require_once __DIR__ . '/../src/services/BudgetAdjustmentService.php';
require_once __DIR__ . '/../src/services/BudgetReportingService.php';

@session_start();

$dbFile = '/tmp/test_phase_4.sqlite';

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

    // Schema
    $pdo->exec("CREATE TABLE IF NOT EXISTS param_lycee (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nom_lycee VARCHAR(255),
        type_lycee VARCHAR(50)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS param_general (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lycee_id INTEGER,
        modalite_paiement VARCHAR(255)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS annees_academiques (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        libelle VARCHAR(100),
        date_debut DATE,
        date_fin DATE,
        est_active BOOLEAN,
        cloturee BOOLEAN
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS classes (
        id_classe INTEGER PRIMARY KEY AUTOINCREMENT,
        niveau VARCHAR(50),
        serie VARCHAR(50),
        numero INTEGER,
        cycle_id INTEGER,
        lycee_id INTEGER
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS cycles (
        id_cycle INTEGER PRIMARY KEY AUTOINCREMENT,
        nom_cycle VARCHAR(100)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS eleves (
        id_eleve INTEGER PRIMARY KEY AUTOINCREMENT,
        nom VARCHAR(100),
        prenom VARCHAR(100),
        statut VARCHAR(50) DEFAULT 'en_attente',
        identifiant_public VARCHAR(50) UNIQUE DEFAULT NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS etudes (
        id_etude INTEGER PRIMARY KEY AUTOINCREMENT,
        eleve_id INTEGER,
        classe_id INTEGER,
        lycee_id INTEGER,
        annee_academique_id INTEGER,
        status VARCHAR(50) DEFAULT 'en_attente_paiement',
        is_active BOOLEAN,
        date_activation DATETIME
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS inscriptions (
        id_inscription INTEGER PRIMARY KEY AUTOINCREMENT,
        etude_id INTEGER,
        eleve_id INTEGER,
        classe_id INTEGER,
        lycee_id INTEGER,
        annee_academique_id INTEGER,
        montant_total DECIMAL(10, 2),
        montant_verse DECIMAL(10, 2),
        reste_a_payer DECIMAL(10, 2),
        recu_numero VARCHAR(50),
        statut VARCHAR(50) DEFAULT 'valide'
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS mensualites (
        id_mensualite INTEGER PRIMARY KEY AUTOINCREMENT,
        etude_id INTEGER,
        eleve_id INTEGER,
        classe_id INTEGER,
        lycee_id INTEGER,
        annee_academique_id INTEGER,
        mois_ou_sequence VARCHAR(50),
        montant_verse DECIMAL(10, 2),
        reste_a_payer DECIMAL(10, 2)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS mensualite_details (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        mensualite_id INTEGER,
        montant DECIMAL(10, 2),
        mode_paiement VARCHAR(50),
        recu_numero VARCHAR(50),
        statut VARCHAR(50) DEFAULT 'valide'
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS journal_comptable (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lycee_id INTEGER,
        eleve_id INTEGER,
        user_id INTEGER,
        annee_academique_id INTEGER,
        operation VARCHAR(100),
        montant DECIMAL(10, 2),
        mode_paiement VARCHAR(50),
        recu_numero VARCHAR(50),
        reference_origine VARCHAR(100)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS exercices_financiers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lycee_id INTEGER,
        libelle VARCHAR(100),
        date_debut DATE,
        date_fin DATE,
        est_actif BOOLEAN,
        cloture BOOLEAN,
        type_exercice VARCHAR(50) DEFAULT 'normal'
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS comptes_financiers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lycee_id INTEGER,
        nom_compte VARCHAR(150),
        type_compte VARCHAR(50),
        solde_courant DECIMAL(15, 2) DEFAULT 0.00,
        devise VARCHAR(10) DEFAULT 'FCFA',
        responsable_id INTEGER,
        statut VARCHAR(50) DEFAULT 'actif',
        est_coffre TINYINT DEFAULT 0,
        compte_comptable_numero VARCHAR(20) DEFAULT NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS sessions_caisse (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lycee_id INTEGER,
        user_id INTEGER,
        compte_id INTEGER,
        date_ouverture DATETIME,
        date_fermeture DATETIME,
        solde_ouverture DECIMAL(15, 2) DEFAULT 0.00,
        solde_theorique DECIMAL(15, 2) DEFAULT 0.00,
        solde_reel DECIMAL(15, 2) DEFAULT NULL,
        ecart DECIMAL(15, 2) DEFAULT 0.00,
        justificatif_ecart TEXT,
        statut VARCHAR(50) DEFAULT 'ouverte',
        valide_par INTEGER,
        valide_le DATETIME,
        montant_remis DECIMAL(15,2) DEFAULT NULL,
        fonds_caisse_conserve DECIMAL(15,2) DEFAULT NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS transferts_financiers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lycee_id INTEGER,
        compte_source_id INTEGER,
        compte_destination_id INTEGER,
        montant DECIMAL(15, 2),
        motif VARCHAR(255),
        statut VARCHAR(50) DEFAULT 'demande',
        demande_par INTEGER,
        autorise_par INTEGER,
        date_demande TIMESTAMP,
        date_execution DATETIME
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS mouvements_tresorerie (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lycee_id INTEGER,
        compte_id INTEGER,
        session_caisse_id INTEGER,
        exercice_financier_id INTEGER,
        transfert_id INTEGER,
        type_mouvement VARCHAR(50),
        montant DECIMAL(15, 2),
        mode_paiement VARCHAR(50),
        reference_transaction VARCHAR(150),
        source_type VARCHAR(100),
        source_id INTEGER,
        evenement_type VARCHAR(50),
        motif VARCHAR(255),
        date_mouvement TIMESTAMP,
        user_id INTEGER,
        is_aggregate_data BOOLEAN DEFAULT FALSE,
        date_reconstruite BOOLEAN DEFAULT FALSE,
        is_historical_migration BOOLEAN DEFAULT FALSE,
        mode_paiement_reconstruit BOOLEAN DEFAULT FALSE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS regularisations_ecarts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lycee_id INTEGER,
        session_caisse_id INTEGER,
        montant DECIMAL(15, 2),
        type_ecart VARCHAR(50),
        motif TEXT,
        constate_par INTEGER,
        approuve_par INTEGER,
        date_constat TIMESTAMP,
        reference_audit VARCHAR(100) UNIQUE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        lycee_id INTEGER,
        message TEXT,
        link VARCHAR(255),
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS frais (
        id_frais INTEGER PRIMARY KEY AUTOINCREMENT,
        lycee_id INTEGER,
        frais_inscription DECIMAL(10, 2),
        frais_mensuel DECIMAL(10, 2),
        annee_academique_id INTEGER
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS utilisateurs (
        id_user INTEGER PRIMARY KEY AUTOINCREMENT,
        nom VARCHAR(100),
        prenom VARCHAR(100),
        email VARCHAR(255),
        role_id INTEGER,
        lycee_id INTEGER,
        actif BOOLEAN DEFAULT TRUE,
        identifiant_public VARCHAR(50) UNIQUE DEFAULT NULL
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS roles (
        id_role INTEGER PRIMARY KEY AUTOINCREMENT,
        nom_role VARCHAR(100)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS sequences (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lycee_id INTEGER,
        annee_academique_id INTEGER,
        nom VARCHAR(255),
        type VARCHAR(50),
        statut VARCHAR(50) DEFAULT 'ouverte'
    )");

    // Load Phase 3 dynamic tables migrations
    require_once __DIR__ . '/../db/migrations/20240115_01_create_referentiels_depenses.php';
    require_once __DIR__ . '/../db/migrations/20240115_02_create_depenses_principale.php';
    require_once __DIR__ . '/../db/migrations/20240115_03_create_historiques_et_logs_depenses.php';
    require_once __DIR__ . '/../db/migrations/20240115_04_create_budget_tables.php';

    migrate_01($pdo);
    migrate_02($pdo);
    migrate_03($pdo);
    migrate_04($pdo);

    Database::setInstance($pdo);
    return $pdo;
}

echo "=========================================================================\n";
echo "📊 PROTOCOLE DE VALIDATION DE LA PHASE 4 (PILOTAGE BUDGÉTAIRE)\n";
echo "=========================================================================\n";

$pdo = setup_db();

try {
    // Inject mock user details & global roles
    $_SESSION['user'] = [
        'id_user' => 1,
        'lycee_id' => 1,
        'permissions' => [
            'budget' => ['view', 'create', 'update', 'activate', 'close', 'adjust', 'transfer', 'report', 'override'],
            'depense' => ['create', 'validate', 'reject', 'pay', 'cancel', 'view']
        ]
    ];

    // Seed basic reference data
    $pdo->exec("INSERT INTO param_lycee (id, nom_lycee, type_lycee) VALUES (1, 'Lycee Facil', 'public')");
    $pdo->exec("INSERT INTO annees_academiques (id, libelle, date_debut, date_fin, est_active, cloturee) VALUES (1, '2024-2025', '2024-01-01', '2024-12-31', 1, 0)");
    $pdo->exec("INSERT INTO exercices_financiers (id, lycee_id, libelle, date_debut, date_fin, est_actif, cloture) VALUES (1, 1, 'Exercice 2024', '2024-01-01', '2024-12-31', 1, 0)");
    $pdo->exec("INSERT INTO depense_categories (id, lycee_id, nom_categorie, modifiable, statut) VALUES (1, 1, 'Fournitures de Bureau', 1, 'actif')");
    $pdo->exec("INSERT INTO depense_beneficiaires (id, lycee_id, nom_beneficiaire, type, statut) VALUES (1, 1, 'Librairie Centrale', 'externe', 'actif')");
    $pdo->exec("INSERT INTO comptes_financiers (id, lycee_id, nom_compte, type_compte, solde_courant, statut) VALUES (1, 1, 'Caisse Centrale', 'caisse', 150000.00, 'actif')");

    echo "\n--- [SCÉNARIO 1 : CRÉATION DU BUDGET & LIGNES] ---\n";
    $budgetId = Budget::create([
        'lycee_id' => 1,
        'exercice_financier_id' => 1,
        'libelle' => 'Budget Annuel 2024',
        'cree_par' => 1
    ]);
    assert_test($budgetId > 0, "Le budget brouillon a été créé avec ID: $budgetId");

    $ligneId = BudgetService::createBudgetLine([
        'budget_id' => $budgetId,
        'categorie_id' => 1,
        'centre_cout_id' => null,
        'allocation_initiale' => 10000.00
    ]);
    assert_test($ligneId > 0, "Ligne budgétaire de 10 000 FCFA créée avec succès.");

    // Check availability
    $avail = BudgetControlService::checkAvailability($ligneId, 4000);
    assert_test($avail['disponible'] === false, "CheckAvailability doit retourner false car le budget n'est pas encore actif (statut actuel: brouillon).");

    echo "\n--- [SCÉNARIO 2 : WORKFLOW BUDGETAIRE (ACTIVATION)] ---\n";
    BudgetWorkflowService::submit($budgetId, 1);
    $b = Budget::findById($budgetId);
    assert_test($b['statut'] === 'soumis', "Le budget est à l'état 'soumis'.");

    BudgetWorkflowService::validateBudget($budgetId, 1);
    BudgetWorkflowService::activate($budgetId, 1);
    $b = Budget::findById($budgetId);
    assert_test($b['statut'] === 'actif', "Le budget est officiellement actif !");

    $avail = BudgetControlService::checkAvailability($ligneId, 4000);
    assert_test($avail['disponible'] === true, "La disponibilité est valide après activation.");
    assert_test($avail['solde_restant'] === 6000.00, "Le solde restant calculé après vérification est de 6000 FCFA.");

    echo "\n--- [SCÉNARIO 3 : INTÉGRATION WORKFLOW DÉPENSES (NOMINAL)] ---\n";
    // Create draft expense
    $depenseId = Depense::create([
        'lycee_id' => 1,
        'numero_piece' => 'DEP-001',
        'categorie_id' => 1,
        'centre_cout_id' => null,
        'beneficiaire_id' => 1,
        'montant' => 3000.00,
        'motif' => 'Achat ramettes de papier',
        'cree_par' => 1,
        'exercice_financier_id' => 1
    ]);

    // Submit draft: reserves credits
    DepenseWorkflowService::submitForApproval($depenseId, 1);
    $line = BudgetLigne::findById($ligneId);
    assert_test((float)$line['montant_engage'] === 3000.00, "Le montant engagé de la ligne budgétaire est bien de 3000 FCFA.");

    $eng = BudgetEngagement::findByDepense($depenseId);
    assert_test($eng['statut'] === 'reserve', "L'engagement budgétaire est créé à l'état 'reserve'.");

    // Approve: promotes engagement to 'engage'
    DepenseWorkflowService::approve($depenseId, 1);
    $eng = BudgetEngagement::findByDepense($depenseId);
    assert_test($eng['statut'] === 'engage', "L'engagement budgétaire est promu à l'état 'engage'.");

    // Pay: consumes engagement
    DepenseWorkflowService::pay($depenseId, 1, 1);
    $eng = BudgetEngagement::findByDepense($depenseId);
    assert_test($eng['statut'] === 'consomme', "L'engagement budgétaire est consommé à l'état 'consomme'.");

    $line = BudgetLigne::findById($ligneId);
    assert_test((float)$line['montant_engage'] === 0.00, "Le montant engagé redevient 0 FCFA.");
    assert_test((float)$line['montant_consomme'] === 3000.00, "Le montant consommé est mis à jour à 3000 FCFA.");

    // Cancel (contre-passation): restores budget consumption to 'annule'
    DepenseWorkflowService::cancel($depenseId, 1, "Erreur d'achat");
    $eng = BudgetEngagement::findByDepense($depenseId);
    assert_test($eng['statut'] === 'annule', "L'engagement budgétaire est restauré à l'état 'annule'.");

    $line = BudgetLigne::findById($ligneId);
    assert_test((float)$line['montant_consomme'] === 0.00, "Le montant consommé redevient 0 FCFA après contre-passation.");

    echo "\n--- [SCÉNARIO 4 : BLOCAGE DE DÉPASSEMENT STRICT & EXCEPTIONNEL] ---\n";
    // Create expense of 15 000 FCFA (Limit: 10 000 FCFA)
    $depenseExceedId = Depense::create([
        'lycee_id' => 1,
        'numero_piece' => 'DEP-002',
        'categorie_id' => 1,
        'centre_cout_id' => null,
        'beneficiaire_id' => 1,
        'montant' => 15000.00,
        'motif' => 'Achat d\'un vidéoprojecteur',
        'cree_par' => 1,
        'exercice_financier_id' => 1
    ]);

    // Test without override permission first
    $_SESSION['user']['permissions']['budget'] = ['view', 'create', 'update', 'activate']; // remove override
    try {
        DepenseWorkflowService::submitForApproval($depenseExceedId, 1);
        assert_test(false, "Aurait dû bloquer la soumission pour dépassement budgétaire.");
    } catch (Exception $e) {
        assert_test(strpos($e->getMessage(), "Blocage budgétaire") !== false, "Blocage strict de dépassement budgétaire confirmé !");
    }

    // Restore override and test exceptional override
    $_SESSION['user']['permissions']['budget'][] = 'override';
    DepenseWorkflowService::submitForApproval($depenseExceedId, 1);
    $engExceed = BudgetEngagement::findByDepense($depenseExceedId);
    assert_test($engExceed['statut'] === 'reserve', "L'engagement exceptionnel a été forcé avec succès.");

    echo "\n--- [SCÉNARIO 5 : AJUSTEMENTS ET DOTATIONS DE CRÉDITS] ---\n";
    // Restore all budget permissions for Scenario 5
    $_SESSION['user']['permissions']['budget'] = ['view', 'create', 'update', 'activate', 'close', 'adjust', 'transfer', 'report', 'override'];
    // Add additional emergency dotation of 10 000 FCFA to the line
    BudgetAdjustmentService::allocateExtra($ligneId, 10000.00, 1, "Urgence projecteur");
    $line = BudgetLigne::findById($ligneId);
    assert_test((float)$line['montant_ajustements'] === 10000.00, "La dotation d'urgence a été créditée de 10 000 FCFA.");

    // Verify reconstruction
    BudgetService::rebuildBudget($budgetId);
    $line = BudgetLigne::findById($ligneId);
    assert_test((float)$line['montant_ajustements'] === 10000.00, "La cohérence des ajustements est préservée après reconstruction.");

    echo "\n🏆 TOUS LES SCÉNARIOS DE LA PHASE 4 ONT ÉTÉ VALIDÉS AVEC SUCCÈS !\n";
    echo "=========================================================================\n";

} catch (Exception $e) {
    echo "\n❌ UN SCÉNARIO A ÉCHOUÉ : " . $e->getMessage() . "\n";
    echo "FILE: " . $e->getFile() . " on line " . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
    echo "=========================================================================\n";
    exit(1);
}
?>