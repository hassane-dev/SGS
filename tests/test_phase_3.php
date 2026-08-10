<?php
// tests/test_phase_3.php

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

@session_start();

$dbFile = '/tmp/test_phase_3.sqlite';

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
echo "📊 PROTOCOLE DE VALIDATION DE LA PHASE 3 (GESTION DES DÉPENSES)\n";
echo "=========================================================================\n";

$pdo = setup_db();

// Load & Run all tests
require_once __DIR__ . '/unit/DepenseModelTest.php';
require_once __DIR__ . '/unit/DepensePieceTest.php';
require_once __DIR__ . '/unit/DepenseWorkflowTransitionsTest.php';
require_once __DIR__ . '/integration/DepenseWorkflowServiceTest.php';
require_once __DIR__ . '/integration/DepenseIdempotencyTest.php';
require_once __DIR__ . '/integration/DepenseConcurrencyTest.php';
require_once __DIR__ . '/integration/DepenseMultiLyceeTest.php';

try {
    test_depense_model($pdo);
    test_depense_piece_validation();
    test_workflow_transitions($pdo);
    test_workflow_service_integration($pdo);
    test_depense_idempotency($pdo);
    test_depense_concurrency($pdo);
    test_depense_multi_lycee_isolation($pdo);

    echo "\n🏆 TOUS LES TESTS DE LA PHASE 3 ONT RÉUSSI AVEC SUCCÈS !\n";
    echo "=========================================================================\n";
} catch (Exception $e) {
    echo "\n❌ UN TEST A ÉCHOUÉ : " . $e->getMessage() . "\n";
    echo "FILE: " . $e->getFile() . " on line " . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
    echo "=========================================================================\n";
    exit(1);
}
?>