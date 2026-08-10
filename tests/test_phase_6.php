<?php
// tests/test_phase_6.php

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

// Phase 5 services
require_once __DIR__ . '/../src/services/ComptabiliteService.php';
require_once __DIR__ . '/../src/services/JournalService.php';
require_once __DIR__ . '/../src/services/GrandLivreService.php';
require_once __DIR__ . '/../src/services/BalanceService.php';
require_once __DIR__ . '/../src/services/ReportingFinancierService.php';
require_once __DIR__ . '/../src/services/ExportComptableService.php';

@session_start();

$dbFile = '/tmp/test_phase_6_cadrage.sqlite';

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

    // Schema Base Tables
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
        lycee_id INTEGER,
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
        date_activation DATETIME,
        active_par INTEGER
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
        details_frais TEXT,
        user_id INTEGER,
        recu_numero VARCHAR(50),
        statut VARCHAR(50) DEFAULT 'valide',
        date_inscription DATETIME
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
        statut VARCHAR(50) DEFAULT 'valide',
        date_paiement DATETIME
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
        is_active TINYINT DEFAULT NULL,
        montant_remis DECIMAL(15,2) DEFAULT NULL,
        fonds_caisse_conserve DECIMAL(15,2) DEFAULT NULL,
        UNIQUE (compte_id, is_active)
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
        mode_paiement_reconstruit BOOLEAN DEFAULT FALSE,
        UNIQUE (compte_id, source_type, source_id, evenement_type)
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

    $pdo->exec("CREATE TABLE IF NOT EXISTS salaires (
        id_salaire INTEGER PRIMARY KEY AUTOINCREMENT,
        personnel_id INTEGER,
        montant DECIMAL(10, 2),
        mode_paiement VARCHAR(50),
        nb_heures_travaillees DECIMAL(5, 2),
        periode_mois INTEGER,
        periode_annee INTEGER,
        date_paiement DATE,
        etat_paiement VARCHAR(50) DEFAULT 'non_paye',
        lycee_id INTEGER,
        annee_id INTEGER
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS frais (
        id_frais INTEGER PRIMARY KEY AUTOINCREMENT,
        lycee_id INTEGER,
        frais_inscription DECIMAL(10, 2),
        frais_mensuel DECIMAL(10, 2),
        frais_logo DECIMAL(10, 2),
        frais_carte DECIMAL(10, 2),
        cycle VARCHAR(100),
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
        statut VARCHAR(50) DEFAULT 'ouverte',
        date_debut DATE,
        date_fin DATE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS politiques_financieres (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lycee_id INTEGER UNIQUE,
        activation_seuil_type VARCHAR(50) DEFAULT '100',
        activation_seuil_valeur DECIMAL(10,2) NULL,
        notes_seuil_mensualites INTEGER DEFAULT 0,
        bulletin_seuil_complet TINYINT(1) DEFAULT 1,
        active TINYINT(1) DEFAULT 1
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS parametres_financiers_eleves (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        eleve_id INTEGER UNIQUE,
        type_avantage VARCHAR(50) DEFAULT 'Aucun',
        valeur_type VARCHAR(50) DEFAULT 'Pourcentage',
        valeur DECIMAL(10,2) DEFAULT 0.00,
        tous_frais TINYINT(1) DEFAULT 0
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS etats_financiers_eleves (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        eleve_id INTEGER UNIQUE,
        inscription_statut VARCHAR(50) DEFAULT 'Non payée',
        mensualite_statut VARCHAR(50) DEFAULT 'À jour',
        notes_consultation VARCHAR(50) DEFAULT 'Interdite',
        bulletin_impression VARCHAR(50) DEFAULT 'Interdite'
    )");

    // Load migrations
    require_once __DIR__ . '/../db/migrations/20240115_01_create_referentiels_depenses.php';
    require_once __DIR__ . '/../db/migrations/20240115_02_create_depenses_principale.php';
    require_once __DIR__ . '/../db/migrations/20240115_03_create_historiques_et_logs_depenses.php';
    require_once __DIR__ . '/../db/migrations/20240115_04_create_budget_tables.php';
    require_once __DIR__ . '/../db/migrations/20240115_05_create_comptabilite_generale.php';

    migrate_01($pdo);
    migrate_02($pdo);
    migrate_03($pdo);
    migrate_04($pdo);
    migrate_05($pdo);

    Database::setInstance($pdo);
    return $pdo;
}

echo "=========================================================================\n";
echo "📊 PROTOCOLE DE VALIDATION DE LA PHASE 6 (REMISE DE CAISSE ET COFFRE)\n";
echo "=========================================================================\n";

$pdo = setup_db();

try {
    // 1. Initialise the default chart of accounts and journal configurations
    ComptabiliteService::seedDefaultChartOfAccounts();
    ComptabiliteService::seedDefaultJournalsForLycee(1);
    ComptabiliteService::seedDefaultSchemas();

    // Insert OHADA General Ledger accounts used for dynamic routing
    $pdo->exec("INSERT INTO comptes_comptables (numero, libelle, classe, nature, autoriser_ecriture) VALUES ('571100', 'Coffre Fort Central', 5, 'actif', 1)");
    $pdo->exec("INSERT INTO comptes_comptables (numero, libelle, classe, nature, autoriser_ecriture) VALUES ('571200', 'Caisse Opérationnelle A', 5, 'actif', 1)");

    // Inject mock user details & global roles
    $_SESSION['user'] = [
        'id' => 1,
        'nom' => 'Administrateur',
        'prenom' => 'Local',
        'email' => 'admin@school.com',
        'role_id' => 1,
        'role_name' => 'admin_local',
        'lycee_id' => 1,
        'permissions' => [
            'sessions_caisse' => ['view', 'create', 'edit', 'validate'],
            'comptes_financiers' => ['view', 'create', 'edit', 'manage']
        ]
    ];

    // Seed baseline reference data
    $pdo->exec("INSERT INTO param_lycee (id, nom_lycee, type_lycee) VALUES (1, 'Lycée de Cadrage', 'prive')");
    $pdo->exec("INSERT INTO param_general (id, lycee_id, modalite_paiement) VALUES (1, 1, 'Espèces')");
    $pdo->exec("INSERT INTO annees_academiques (id, libelle, date_debut, date_fin, est_active, cloturee) VALUES (1, '2024-2025', '2024-01-01', '2024-12-31', 1, 0)");
    $pdo->exec("INSERT INTO exercices_financiers (id, lycee_id, libelle, date_debut, date_fin, est_actif, cloture) VALUES (1, 1, 'Exercice 2024', '2024-01-01', '2024-12-31', 1, 0)");
    $pdo->exec("INSERT INTO utilisateurs (id_user, nom, prenom, email, role_id, lycee_id) VALUES (1, 'Chef', 'Comptable', 'chef@school.com', 9, 1)");
    $pdo->exec("INSERT INTO utilisateurs (id_user, nom, prenom, email, role_id, lycee_id) VALUES (2, 'Caissier', 'Auxiliaire', 'caissier@school.com', 10, 1)");

    echo "\n--- [TEST 1 : CRÉATION DU COFFRE UNIQUE ET DU COMPTE DE CAISSE] ---\n";
    // Create the unique Coffre account
    $coffreId = CompteFinancier::create([
        'lycee_id' => 1,
        'nom_compte' => 'Coffre-fort Central',
        'type_compte' => 'caisse',
        'solde_courant' => 100000.00,
        'est_coffre' => 1,
        'compte_comptable_numero' => '571100'
    ]);
    assert_test($coffreId > 0, "Coffre fort créé avec succès.");

    // Try to create another Coffre on same lycée -> should fail
    try {
        CompteFinancier::create([
            'lycee_id' => 1,
            'nom_compte' => 'Coffre-fort Auxiliaire',
            'type_compte' => 'caisse',
            'solde_courant' => 0.00,
            'est_coffre' => 1,
            'compte_comptable_numero' => '571102'
        ]);
        assert_test(false, "L'établissement n'aurait pas dû autoriser un second coffre.");
    } catch (Exception $e) {
        assert_test(strpos($e->getMessage(), "Un seul Coffre Principal") !== false, "Blocage réussi de la création d'un second coffre !");
    }

    // Create a regular caisse operational account
    $caisseId = CompteFinancier::create([
        'lycee_id' => 1,
        'nom_compte' => 'Tiroir Caisse A',
        'type_compte' => 'caisse',
        'solde_courant' => 0.00,
        'est_coffre' => 0,
        'compte_comptable_numero' => '571200'
    ]);
    assert_test($caisseId > 0, "Caisse opérationnelle créée avec succès.");


    echo "\n--- [TEST 2 : WORKFLOW CAS NOMINAL (REMISE TOTALE)] ---\n";
    // 1. Open Session 1
    $sessId1 = SessionCaisse::ouvrir([
        'lycee_id' => 1,
        'user_id' => 2,
        'compte_id' => $caisseId
    ]);
    $session1 = SessionCaisse::findById($sessId1);
    assert_test((float)$session1['solde_ouverture'] === 0.00, "Le solde d'ouverture initial de la session is de 0.00 FCFA.");

    // Simulate an encaissement using TreasuryService so solde_theorique is correctly updated!
    TreasuryService::registerMovement([
        'lycee_id' => 1,
        'compte_id' => $caisseId,
        'session_caisse_id' => $sessId1,
        'exercice_financier_id' => 1,
        'type_mouvement' => 'entree',
        'montant' => 45000.00,
        'mode_paiement' => 'Espèces',
        'source_type' => 'journal_comptable',
        'source_id' => 201,
        'evenement_type' => 'encaissement',
        'motif' => 'Encaissement Jean',
        'user_id' => 2
    ]);

    // 2. Clôturer Session 1 (remise totale : réel = 45 000, remis = 45 000, fonds conservés = 0)
    SessionCaisse::cloturer($sessId1, 45000.00, '', 45000.00, 0.00);

    $session1 = SessionCaisse::findById($sessId1);
    assert_test($session1['statut'] === 'fermee_a_valider', "Caisse fermée en attente de validation.");
    assert_test((float)$session1['montant_remis'] === 45000.00 && (float)$session1['fonds_caisse_conserve'] === 0.00, "Déclaration de remise : 45 000 FCFA à verser, 0 FCFA conservé.");

    // 3. Approuver Session 1 (valide_par = 1 (separation of duties))
    SessionCaisse::approuver($sessId1, 1, "Validation nominale");

    // Check balances after nominal remittance
    $caisse_solde = (float)CompteFinancier::findById($caisseId)['solde_courant'];
    $coffre_solde = (float)CompteFinancier::findById($coffreId)['solde_courant'];

    assert_test($caisse_solde === 0.00, "Le solde de la caisse opérationnelle est retombé à 0.00 FCFA.");
    assert_test($coffre_solde === 145000.00, "Le solde du Coffre Principal a été crédité et s'élève à 145 000.00 FCFA (100 000 initiaux + 45 000 remis).");

    // Check accounting piece generated
    $stmt_piece = $pdo->prepare("SELECT * FROM pieces_comptables WHERE source_table = 'sessions_caisse' AND source_id = :id");
    $stmt_piece->execute(['id' => $sessId1]);
    $piece = $stmt_piece->fetch(PDO::FETCH_ASSOC);
    assert_test($piece !== false, "Une pièce comptable a été automatiquement générée pour la remise.");

    $stmt_lines = $pdo->prepare("
        SELECT e.*, c.numero as compte_numero
        FROM ecritures_comptables e
        JOIN comptes_comptables c ON e.compte_comptable_id = c.id
        WHERE e.piece_comptable_id = :id
    ");
    $stmt_lines->execute(['id' => $piece['id']]);
    $lines = $stmt_lines->fetchAll(PDO::FETCH_ASSOC);
    assert_test(count($lines) === 2, "La pièce contient 2 lignes.");
    assert_test($lines[0]['compte_numero'] === '571100' && (float)$lines[0]['debit'] === 45000.00, "Ligne 1 Débit : Compte Coffre (571100) pour 45 000 FCFA.");
    assert_test($lines[1]['compte_numero'] === '571200' && (float)$lines[1]['credit'] === 45000.00, "Ligne 2 Crédit : Compte Caisse (571200) pour 45 000 FCFA.");


    echo "\n--- [TEST 3 : WORKFLOW FONDS CONSERVÉ (OUVERTURE N+1 ÉTANCHE)] ---\n";
    // 1. Open Session 2 on same caisse (Continuous session)
    $sessId2 = SessionCaisse::ouvrir([
        'lycee_id' => 1,
        'user_id' => 2,
        'compte_id' => $caisseId
    ]);
    $session2 = SessionCaisse::findById($sessId2);
    // As Session 1 conserved 0.00, opening balance must be strictly 0.00 (not accounts_financials solde which was 0 but double-checking)
    assert_test((float)$session2['solde_ouverture'] === 0.00, "Le solde d'ouverture de la session 2 is bien de 0.00 FCFA (étanchéité assurée).");

    // Simulate an encaissement using TreasuryService
    TreasuryService::registerMovement([
        'lycee_id' => 1,
        'compte_id' => $caisseId,
        'session_caisse_id' => $sessId2,
        'exercice_financier_id' => 1,
        'type_mouvement' => 'entree',
        'montant' => 35000.00,
        'mode_paiement' => 'Espèces',
        'source_type' => 'journal_comptable',
        'source_id' => 202,
        'evenement_type' => 'encaissement',
        'motif' => 'Encaissement Marie',
        'user_id' => 2
    ]);

    // 2. Clôturer Session 2 (répartition : réel = 35 000, remise = 25 000, fonds conservés = 10 000)
    SessionCaisse::cloturer($sessId2, 35000.00, '', 25000.00, 10000.00);

    // 3. Approuver Session 2
    SessionCaisse::approuver($sessId2, 1, "Validation avec fonds conservé");

    $caisse_solde = (float)CompteFinancier::findById($caisseId)['solde_courant'];
    $coffre_solde = (float)CompteFinancier::findById($coffreId)['solde_courant'];
    assert_test($caisse_solde === 10000.00, "Le solde restant de la caisse opérationnelle est de 10 000 FCFA (le fonds conservé).");
    assert_test($coffre_solde === 170000.00, "Le solde du Coffre Principal est passé à 170 000 FCFA (+ 25 000 de remise).");

    // 4. Open Session 3 on same caisse -> Solde ouverture must be strictly 10 000 FCFA
    $sessId3 = SessionCaisse::ouvrir([
        'lycee_id' => 1,
        'user_id' => 2,
        'compte_id' => $caisseId
    ]);
    $session3 = SessionCaisse::findById($sessId3);
    assert_test((float)$session3['solde_ouverture'] === 10000.00, "Le solde d'ouverture de la session 3 est de 10 000 FCFA, héritant parfaitement du fonds conservé de la session 2 !");


    echo "\n--- [TEST 4 : CONTRÔLE DE SÉCURITÉ DES INVARIANTS PHYSIQUES (REJETS)] ---\n";
    // Try to close Session 3 with invalid partition: reel = 15000, remise = 10000, conserve = 3000 (diff is 2000)
    try {
        SessionCaisse::cloturer($sessId3, 15000.00, '', 10000.00, 3000.00);
        assert_test(false, "La clôture avec partition incohérente aurait dû être rejetée.");
    } catch (Exception $e) {
        assert_test(strpos($e->getMessage(), "Incohérence physique") !== false, "La partition incohérente a été rejetée avec succès !");
    }

    // Try to close with negative values
    try {
        SessionCaisse::cloturer($sessId3, 10000.00, '', -1000.00, 11000.00);
        assert_test(false, "La clôture avec montant négatif aurait dû être rejetée.");
    } catch (Exception $e) {
        assert_test(strpos($e->getMessage(), "négatifs") !== false, "Le montant de remise négatif a été bloqué !");
    }


    echo "\n--- [TEST 5 : VALIDATION FAIL-FAST DES COMPTES SANS CODE COMPTABLE] ---\n";
    // Create a caisse without GL account linked
    $caisseSGLId = CompteFinancier::create([
        'lycee_id' => 1,
        'nom_compte' => 'Caisse Sans Code',
        'type_compte' => 'caisse',
        'solde_courant' => 0.00,
        'est_coffre' => 0,
        'compte_comptable_numero' => '' // Missing!
    ]);

    // Open, register entry, close and try to validate -> should fail-fast and rollback
    $sessIdFail = SessionCaisse::ouvrir([
        'lycee_id' => 1,
        'user_id' => 2,
        'compte_id' => $caisseSGLId
    ]);

    TreasuryService::registerMovement([
        'lycee_id' => 1,
        'compte_id' => $caisseSGLId,
        'session_caisse_id' => $sessIdFail,
        'exercice_financier_id' => 1,
        'type_mouvement' => 'entree',
        'montant' => 1000.00,
        'mode_paiement' => 'Espèces',
        'source_type' => 'journal_comptable',
        'source_id' => 203,
        'evenement_type' => 'encaissement',
        'motif' => 'Entree test',
        'user_id' => 2
    ]);

    SessionCaisse::cloturer($sessIdFail, 1000.00, '', 1000.00, 0.00);

    try {
        SessionCaisse::approuver($sessIdFail, 1, "Validation avec compte manquant");
        assert_test(false, "La validation aurait dû échouer de manière Fail-Fast.");
    } catch (Exception $e) {
        assert_test(strpos($e->getMessage(), "n'est pas configuré") !== false, "Validation bloquée proprement : " . $e->getMessage());
    }

    // Verify atomic rollback: no movements written to treasury or accounting for this session approve
    $stmt_m_fail = $pdo->prepare("SELECT COUNT(*) FROM mouvements_tresorerie WHERE session_caisse_id = :id AND evenement_type LIKE 'remise%'");
    $stmt_m_fail->execute(['id' => $sessIdFail]);
    $count_m_fail = (int)$stmt_m_fail->fetchColumn();
    assert_test($count_m_fail === 0, "Rollback OK : Aucun mouvement de remise de trésorerie n'a été conservé en base.");

    $stmt_p_fail = $pdo->prepare("SELECT COUNT(*) FROM pieces_comptables WHERE source_table = 'sessions_caisse' AND source_id = :id");
    $stmt_p_fail->execute(['id' => $sessIdFail]);
    $count_p_fail = (int)$stmt_p_fail->fetchColumn();
    assert_test($count_p_fail === 0, "Rollback OK : Aucune pièce comptable de remise n'a été générée.");


    echo "\n--- [TEST 6 : SÉPARATIONS DES TÂCHES ET SÉCURITÉ CONCURRENTE] ---\n";
    // Close Session 3 so that it is in 'fermee_a_valider' state and ready for approval checks
    SessionCaisse::cloturer($sessId3, 10000.00, '', 10000.00, 0.00);

    // Caissier (user 2) tries to approve his own session 3 -> should fail
    try {
        SessionCaisse::approuver($sessId3, 2, "Auto-validation");
        assert_test(false, "Le caissier a pu valider sa propre session.");
    } catch (Exception $e) {
        assert_test(strpos($e->getMessage(), "Séparation des tâches") !== false, "Rejet conforme de l'auto-validation !");
    }

    echo "\n--- [TEST 7 : SÉCURISATION CONTRE LES APPROBATIONS CONCURRENTES (DOUBLE CLIC / RETRY)] ---\n";
    // Clear sessions to bypass any active session gate block
    $pdo->exec("DELETE FROM sessions_caisse; DELETE FROM regularisations_ecarts; DELETE FROM mouvements_tresorerie;");

    // Create a new session specifically for concurrency test
    $sessIdConcat = SessionCaisse::ouvrir([
        'lycee_id' => 1,
        'user_id' => 2,
        'compte_id' => $caisseId
    ]);
    // Simulate dynamic encaissement of 15000 FCFA
    TreasuryService::registerMovement([
        'lycee_id' => 1,
        'compte_id' => $caisseId,
        'session_caisse_id' => $sessIdConcat,
        'exercice_financier_id' => 1,
        'type_mouvement' => 'entree',
        'montant' => 15000.00,
        'mode_paiement' => 'Espèces',
        'source_type' => 'journal_comptable',
        'source_id' => 301,
        'evenement_type' => 'encaissement',
        'motif' => 'Encaissement Concurrence',
        'user_id' => 2
    ]);
    SessionCaisse::cloturer($sessIdConcat, 15000.00, '', 15000.00, 0.00);

    // Call first approval (should succeed)
    SessionCaisse::approuver($sessIdConcat, 1, "Première approbation");

    // Call second concurrent approval on the same session ID (should be blocked and raise Exception)
    try {
        SessionCaisse::approuver($sessIdConcat, 1, "Deuxième approbation concurrente");
        assert_test(false, "La deuxième approbation n'a pas été rejetée.");
    } catch (Exception $e) {
        assert_test(strpos($e->getMessage(), "Seule une session à l'état 'En attente de validation'") !== false, "Deuxième approbation concurrente rejetée de manière conforme après acquisition du verrou : " . $e->getMessage());
    }

    // Verify database counts to ensure NO double remise or double postings occurred
    $stmt_m_count = $pdo->prepare("SELECT COUNT(*) FROM mouvements_tresorerie WHERE session_caisse_id = :id AND evenement_type IN ('remise_coffre_sortie', 'remise_coffre_entree')");
    $stmt_m_count->execute(['id' => $sessIdConcat]);
    $mvt_count = (int)$stmt_m_count->fetchColumn();
    assert_test($mvt_count === 2, "Une seule validation de flux de trésorerie effectuée (1 sortie caisse, 1 entrée coffre, mvt_count = 2).");

    $stmt_p_count = $pdo->prepare("SELECT COUNT(*) FROM pieces_comptables WHERE source_table = 'sessions_caisse' AND source_id = :id");
    $stmt_p_count->execute(['id' => $sessIdConcat]);
    $pcs_count = (int)$stmt_p_count->fetchColumn();
    assert_test($pcs_count === 1, "Une seule pièce comptable générale de transfert générée pour la remise.");

    echo "\n🏆 TOUS LES SCÉNARIOS DE TEST DE LA PHASE 6 ONT RÉUSSI AVEC SUCCÈS ! (ZÉRO DÉFAUT)\n";
    echo "=========================================================================\n";

} catch (Exception $e) {
    echo "\n❌ UN TEST A ÉCHOUÉ : " . $e->getMessage() . "\n";
    echo "FILE: " . $e->getFile() . " on line " . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
    echo "=========================================================================\n";
    exit(1);
}
