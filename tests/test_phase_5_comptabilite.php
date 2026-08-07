<?php
// tests/test_phase_5_comptabilite.php

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

$dbFile = '/tmp/test_phase_5_comptabilite.sqlite';

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
        statut VARCHAR(50) DEFAULT 'actif'
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
        is_historical_migration BOOLEAN NOT NULL DEFAULT FALSE,
        mode_paiement_reconstruit BOOLEAN NOT NULL DEFAULT FALSE,
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

    // Load Phase 3 dynamic tables migrations
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
echo "📊 PROTOCOLE DE VALIDATION DE LA PHASE 5 (COMPTABILITÉ GÉNÉRALE)\n";
echo "=========================================================================\n";

$pdo = setup_db();

try {
    // 1. Initialise the default chart of accounts and journal configurations
    ComptabiliteService::seedDefaultChartOfAccounts();
    ComptabiliteService::seedDefaultJournalsForLycee(1);
    ComptabiliteService::seedDefaultSchemas();

    $stmt_c = $pdo->query("SELECT COUNT(*) FROM comptes_comptables");
    $accounts_count = (int)$stmt_c->fetchColumn();
    assert_test($accounts_count > 0, "Plan comptable OHADA de référence initialisé ($accounts_count comptes insérés).");

    $stmt_j = $pdo->query("SELECT COUNT(*) FROM journaux_comptables WHERE lycee_id = 1");
    $journals_count = (int)$stmt_j->fetchColumn();
    assert_test($journals_count === 6, "6 Journaux comptables auxiliaires initialisés (JC, JB, JR, JD, JS, JO).");

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
            'paiement' => ['view', 'manage', 'annuler_recu', 'rembourser_eleve'],
            'sessions_caisse' => ['view', 'create', 'edit', 'validate'],
            'journal' => ['view', 'export'],
            'grand_livre' => ['view', 'export'],
            'balance' => ['view', 'export'],
            'tresorerie_reports' => ['view'],
            'finance_reports' => ['view'],
            'depense' => ['create', 'validate', 'pay', 'cancel', 'view']
        ]
    ];

    // Seed reference data
    $pdo->exec("INSERT INTO param_lycee (id, nom_lycee, type_lycee) VALUES (1, 'Lycée d''Excellence', 'prive')");
    $pdo->exec("INSERT INTO param_general (id, lycee_id, modalite_paiement) VALUES (1, 1, 'Espèces,Chèque,Virement')");
    $pdo->exec("INSERT INTO annees_academiques (id, libelle, date_debut, date_fin, est_active, cloturee) VALUES (1, '2024-2025', '2024-01-01', '2024-12-31', 1, 0)");
    $pdo->exec("INSERT INTO exercices_financiers (id, lycee_id, libelle, date_debut, date_fin, est_actif, cloture) VALUES (1, 1, 'Exercice 2024', '2024-01-01', '2024-12-31', 1, 0)");
    $pdo->exec("INSERT INTO depense_categories (id, lycee_id, nom_categorie, modifiable, statut) VALUES (1, 1, 'Fournitures de bureau', 1, 'actif')");
    $pdo->exec("INSERT INTO depense_beneficiaires (id, lycee_id, nom_beneficiaire, type, statut) VALUES (1, 1, 'Librairie Centrale', 'externe', 'actif')");
    $pdo->exec("INSERT INTO comptes_financiers (id, lycee_id, nom_compte, type_compte, solde_courant, statut) VALUES (1, 1, 'Caisse Centrale', 'caisse', 100000.00, 'actif')");
    $pdo->exec("INSERT INTO cycles (id_cycle, nom_cycle) VALUES (1, 'CEG')");
    $pdo->exec("INSERT INTO classes (id_classe, niveau, serie, numero, cycle_id, lycee_id) VALUES (1, '6ème', '', 1, 1, 1)");
    $pdo->exec("INSERT INTO frais (id_frais, lycee_id, frais_inscription, frais_mensuel, annee_academique_id, cycle) VALUES (1, 1, 50000.00, 10000.00, 1, 'CEG')");
    $pdo->exec("INSERT INTO sequences (id, lycee_id, annee_academique_id, nom, type, date_debut, date_fin) VALUES (1, 1, 1, 'Trimestre 1', 'trimestrielle', '2024-09-01', '2024-12-31')");
    $pdo->exec("INSERT INTO utilisateurs (id_user, nom, prenom, email, role_id, lycee_id) VALUES (1, 'Comptable', 'Principal', 'comptable@school.com', 9, 1)");
    $pdo->exec("INSERT INTO utilisateurs (id_user, nom, prenom, email, role_id, lycee_id) VALUES (2, 'Caissier', 'Auxiliaire', 'caissier@school.com', 10, 1)");

    echo "\n--- [TEST 1 : SÉCURITÉ ET EQUILIBRE DES ÉCRITURES COMPTABLES] ---\n";
    // Check locked closed period block
    $pdo->exec("INSERT INTO comptabilite_periodes (lycee_id, exercice_financier_id, date_debut, date_fin, est_cloturee) VALUES (1, 1, '2024-01-01', '2024-01-31', 1)");
    try {
        ComptabiliteService::enregistrerPiece(1, 'Test Piece', '2024-01-15', null, null, [], 1);
        assert_test(false, "L'écriture aurait dû être bloquée car la période de la date est clôturée.");
    } catch (Exception $e) {
        assert_test(strpos($e->getMessage(), "clôturée") !== false, "Blocage d'écriture en période close validé !");
    }

    // Check arithmetical balance Debit = Credit
    $lignes_desequilibrees = [
        ['compte_numero' => '571000', 'debit' => 100.00],
        ['compte_numero' => '701100', 'credit' => 90.00] // desequilibre de 10
    ];
    try {
        ComptabiliteService::enregistrerPiece(1, 'Test Piece', '2024-02-15', null, null, $lignes_desequilibrees, 1);
        assert_test(false, "L'écriture déséquilibrée aurait dû être bloquée.");
    } catch (Exception $e) {
        assert_test(strpos($e->getMessage(), "Déséquilibre") !== false, "Blocage d'écriture déséquilibrée validé !");
    }

    // Check perfect double entry
    $lignes_equilibrees = [
        ['compte_numero' => '571000', 'debit' => 100.00],
        ['compte_numero' => '701100', 'credit' => 100.00]
    ];
    $pieceId = ComptabiliteService::enregistrerPiece(1, 'Test Piece Valide', '2024-02-15', 'inscriptions', 999, $lignes_equilibrees, 1);
    assert_test($pieceId > 0, "Écriture équilibrée enregistrée avec succès. ID Pièce: $pieceId");

    // Check unique sequence number generation
    $stmt_p = $pdo->prepare("SELECT numero_piece FROM pieces_comptables WHERE id = :id");
    $stmt_p->execute(['id' => $pieceId]);
    $numPiece = $stmt_p->fetchColumn();
    assert_test($numPiece === 'JC-2024-000001', "Le numéro de pièce généré est séquentiel et formaté : $numPiece");

    // Check duplicate prevention (Idempotency)
    try {
        ComptabiliteService::enregistrerPiece(1, 'Test Piece Valide Doublon', '2024-02-15', 'inscriptions', 999, $lignes_equilibrees, 1);
        assert_test(false, "L'idempotence de la transaction n'a pas été respectée.");
    } catch (Exception $e) {
        assert_test(strpos($e->getMessage(), "déjà") !== false, "Prévention et blocage des doublons validés avec succès !");
    }

    echo "\n--- [TEST 2 : CONTREPASSATION COMPTABLE (ANNULATION)] ---\n";
    $contrePieceId = ComptabiliteService::contrepasserPiece($pieceId, 1, "Erreur de saisie");
    assert_test($contrePieceId > 0, "Contrepassation effectuée. ID Contre-pièce: $contrePieceId");

    // Check that original status is changed to 'contrepasse'
    $stmt_orig = $pdo->prepare("SELECT statut FROM pieces_comptables WHERE id = :id");
    $stmt_orig->execute(['id' => $pieceId]);
    $statut_orig = $stmt_orig->fetchColumn();
    assert_test($statut_orig === 'contrepasse', "Statut de la pièce d'origine mis à jour à 'contrepasse'.");

    // Verify counter-balanced lines
    $stmt_c_lines = $pdo->prepare("SELECT SUM(debit) as total_debit, SUM(credit) as total_credit FROM ecritures_comptables WHERE piece_comptable_id = :id");
    $stmt_c_lines->execute(['id' => $contrePieceId]);
    $inv_sums = $stmt_c_lines->fetch(PDO::FETCH_ASSOC);
    assert_test((float)$inv_sums['total_debit'] === 100.00 && (float)$inv_sums['total_credit'] === 100.00, "La pièce de contrepassation est parfaitement équilibrée.");

    echo "\n--- [TEST 3 : ENCAISSEMENT D'INSCRIPTIONS ET EXTRAPOLATION COMPTABLE] ---\n";
    // Setup cash session
    $pdo->exec("INSERT INTO sessions_caisse (id, lycee_id, user_id, compte_id, date_ouverture, solde_ouverture, statut) VALUES (1, 1, 2, 1, '2024-09-01 08:00:00', 100000.00, 'ouverte')");
    $pdo->exec("INSERT INTO eleves (id_eleve, lycee_id, nom, prenom, statut, identifiant_public) VALUES (1, 1, 'Zadi', 'Jean', 'en_attente_paiement', '15092024-0001E')");
    $pdo->exec("INSERT INTO etudes (id_etude, eleve_id, classe_id, lycee_id, annee_academique_id, status) VALUES (1, 1, 1, 1, 1, 'en_attente_paiement')");

    // Process payment in PaiementController
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = [
        'montant_inscription' => 50000.00,
        'mode_paiement' => 'Espèces',
        'reference_transaction' => 'RECU-001'
    ];
    $_SESSION['user']['id'] = 2; // Caissier Auxiliaire has active session

    $controller = new PaiementController();
    $controller->processPayment(1);

    if (!empty($_SESSION['error_message'])) {
        echo "  [INFO] Error message from Controller: " . $_SESSION['error_message'] . "\n";
    }

    // Verify automatically generated accounting entries
    $stmt_pc_ins = $pdo->prepare("SELECT * FROM pieces_comptables WHERE source_table = 'inscriptions' AND source_id = 1");
    $stmt_pc_ins->execute();
    $piece_ins = $stmt_pc_ins->fetch(PDO::FETCH_ASSOC);
    assert_test($piece_ins !== false, "Une pièce comptable a été automatiquement générée pour l'inscription.");
    $expected_year_prefix = 'JC-' . date('Y') . '-000002';
    assert_test($piece_ins['numero_piece'] === $expected_year_prefix, "Numérotation transactionnelle respectée : " . $piece_ins['numero_piece']);

    $stmt_lines = $pdo->prepare("
        SELECT e.*, c.numero as compte_numero
        FROM ecritures_comptables e
        JOIN comptes_comptables c ON e.compte_comptable_id = c.id
        WHERE e.piece_comptable_id = :id
    ");
    $stmt_lines->execute(['id' => $piece_ins['id']]);
    $lines_ins = $stmt_lines->fetchAll(PDO::FETCH_ASSOC);

    assert_test(count($lines_ins) === 2, "La pièce comptable contient exactement 2 lignes d'écriture.");
    assert_test($lines_ins[0]['compte_numero'] === '571000' && (float)$lines_ins[0]['debit'] === 50000.00, "Débit correct sur le compte de Caisse (571000).");
    assert_test($lines_ins[1]['compte_numero'] === '701100' && (float)$lines_ins[1]['credit'] === 50000.00, "Crédit correct sur le compte de Droits d'inscription (701100).");

    echo "\n--- [TEST 4 : REPRISE ET RECONSTRUCTION HISTORIQUE DE TOUTE LA COMPTABILITÉ] ---\n";
    // We wipe the general ledger but keep the operational student payments intact
    $pdo->exec("DELETE FROM ecritures_comptables");
    $pdo->exec("DELETE FROM pieces_comptables");
    $pdo->exec("DELETE FROM pieces_comptables_sequences");

    $result = ComptabiliteService::reconstruireEcrituresHistoriques(1, 1, 1);
    assert_test($result['reconstructed'] > 0, "Reprise historique globale réussie ! (" . $result['reconstructed'] . " pièces recréées).");

    // Check that the reconstructed piece matches the exact inscription amount
    $stmt_recon = $pdo->prepare("SELECT SUM(debit) as total FROM ecritures_comptables");
    $stmt_recon->execute();
    $total_debits = (float)$stmt_recon->fetchColumn();
    assert_test($total_debits === 50000.00, "Montant total reconstruit correct ($total_debits FCFA).");

    echo "\n--- [TEST 5 : EXTRACTION ANALYTIQUE, COMPTE DE RÉSULTAT ET BALANCE 6 COLONNES] ---\n";
    // Query balance
    $balance_data = BalanceService::getBalanceData(1);
    assert_test(count($balance_data['lignes']) === 2, "La balance contient exactement 2 comptes mouvementés.");
    assert_test($balance_data['totaux']['final_debit'] === 50000.00 && $balance_data['totaux']['final_credit'] === 50000.00, "Équilibre parfait de la balance vérifié (Débit = Crédit = 50 000 FCFA).");

    // Query result sheet (Produits - Charges)
    $result_sheet = ReportingFinancierService::getCompteResultat(1);
    assert_test((float)$result_sheet['total_produits'] === 50000.00, "Total des produits mesuré : 50 000 FCFA.");
    assert_test((float)$result_sheet['resultat_net'] === 50000.00, "Résultat excédentaire de l'exercice calculé à 50 000 FCFA.");

    echo "\n--- [TEST 6 : PERFORMANCES COMPTABLES (TEST DE CHARGE : 1000 PIÈCES / 2000 ÉCRITURES)] ---\n";
    $time_start = microtime(true);

    // Seed 1000 balanced pieces
    $pdo->beginTransaction();
    for ($i = 1; $i <= 1000; $i++) {
        $l = [
            ['compte_numero' => '571000', 'debit' => 1000.00],
            ['compte_numero' => '701100', 'credit' => 1000.00]
        ];
        // We explicitly use a unique source to simulate separate daily cash entries
        ComptabiliteService::enregistrerPiece(1, "Test Charge $i", '2024-03-01', 'simulations', $i, $l, 1);
    }
    $pdo->commit();

    $time_end = microtime(true);
    $execution_time = ($time_end - $time_start);
    assert_test($execution_time < 1.5, "Insertion rapide de 1000 pièces en partie double effectuée en " . round($execution_time, 4) . " secondes.");

    // Query Balance on the large dataset
    $time_balance_start = microtime(true);
    $large_balance = BalanceService::getBalanceData(1);
    $time_balance_end = microtime(true);
    $balance_duration = $time_balance_end - $time_balance_start;
    assert_test($balance_duration < 0.2, "Balance générale de charge extraite en " . round($balance_duration, 4) . " secondes.");
    assert_test((float)$large_balance['totaux']['final_debit'] === 1050000.00, "Solde final cumulé de charge exact (1 050 000 FCFA).");

    echo "\n🏆 TOUS LES SCÉNARIOS DE TEST DE LA PHASE 5 ONT RÉUSSI AVEC SUCCÈS ! (ZÉRO DÉFAUT)\n";
    echo "=========================================================================\n";

} catch (Exception $e) {
    echo "\n❌ UN TEST A ÉCHOUÉ : " . $e->getMessage() . "\n";
    echo "FILE: " . $e->getFile() . " on line " . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
    echo "=========================================================================\n";
    exit(1);
}
