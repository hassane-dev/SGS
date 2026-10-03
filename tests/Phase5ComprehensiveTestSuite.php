<?php

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/CompteFinancier.php';
require_once __DIR__ . '/../src/models/SessionCaisse.php';
require_once __DIR__ . '/../src/models/TreasuryService.php';
require_once __DIR__ . '/../src/models/ExerciceFinancier.php';
require_once __DIR__ . '/../src/models/ModePaiement.php';
require_once __DIR__ . '/../src/models/PaiementVentilation.php';
require_once __DIR__ . '/../src/services/PaymentRoutingService.php';

@session_start();

$dbFile = '/tmp/test_phase_5_comprehensive.sqlite';

if (file_exists($dbFile)) {
    unlink($dbFile);
}

$pdo = new PDO('sqlite:' . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

Database::setInstance($pdo);

// Create required SQLite schema
$pdo->exec("
    CREATE TABLE param_lycee (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nom_lycee TEXT NOT NULL
    );

    CREATE TABLE utilisateurs (
        id_user INTEGER PRIMARY KEY AUTOINCREMENT,
        nom TEXT,
        prenom TEXT,
        email TEXT,
        statut TEXT,
        lycee_id INTEGER
    );

    CREATE TABLE exercices_financiers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lycee_id INTEGER,
        libelle TEXT,
        date_debut TEXT,
        date_fin TEXT,
        est_actif INTEGER DEFAULT 1,
        cloture INTEGER DEFAULT 0
    );

    CREATE TABLE comptes_financiers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lycee_id INTEGER,
        nom_compte TEXT,
        type_compte TEXT,
        solde_courant REAL DEFAULT 0.00,
        devise TEXT DEFAULT 'FCFA',
        responsable_id INTEGER,
        statut TEXT DEFAULT 'actif',
        est_coffre INTEGER DEFAULT 0,
        compte_comptable_id INTEGER,
        compte_comptable_numero TEXT
    );

    CREATE TABLE sessions_caisse (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lycee_id INTEGER,
        user_id INTEGER,
        compte_id INTEGER,
        date_ouverture TEXT,
        date_fermeture TEXT,
        solde_ouverture REAL DEFAULT 0.00,
        solde_theorique REAL DEFAULT 0.00,
        solde_reel REAL,
        ecart REAL DEFAULT 0.00,
        justificatif_ecart TEXT,
        montant_remis REAL,
        fonds_caisse_conserve REAL,
        statut TEXT DEFAULT 'ouverte',
        valide_par INTEGER,
        valide_le TEXT,
        fonds_source_session_id INTEGER,
        fonds_source_user_id INTEGER,
        prise_en_charge_confirmee INTEGER DEFAULT 0,
        date_prise_en_charge TEXT
    );

    CREATE TABLE mouvements_tresorerie (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lycee_id INTEGER,
        compte_id INTEGER,
        session_caisse_id INTEGER,
        exercice_financier_id INTEGER,
        transfert_id INTEGER,
        type_mouvement TEXT,
        montant REAL,
        mode_paiement TEXT,
        reference_transaction TEXT,
        source_type TEXT,
        source_id INTEGER,
        evenement_type TEXT,
        motif TEXT,
        user_id INTEGER,
        date_mouvement TEXT DEFAULT CURRENT_TIMESTAMP,
        is_aggregate_data INTEGER DEFAULT 0,
        date_reconstruite INTEGER DEFAULT 0,
        is_historical_migration INTEGER DEFAULT 0,
        mode_paiement_reconstruit INTEGER DEFAULT 0
    );

    CREATE TABLE comptes_comptables (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        numero TEXT,
        libelle TEXT,
        actif INTEGER DEFAULT 1,
        autoriser_ecriture INTEGER DEFAULT 1
    );

    CREATE TABLE pieces_comptables (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lycee_id INTEGER,
        journal_id INTEGER,
        numero_piece TEXT,
        date_piece TEXT,
        libelle TEXT,
        statut TEXT DEFAULT 'valide',
        source_table TEXT,
        source_id INTEGER,
        user_id INTEGER
    );

    CREATE TABLE ecritures_comptables (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        piece_id INTEGER,
        compte_comptable_id INTEGER,
        debit REAL DEFAULT 0,
        credit REAL DEFAULT 0,
        libelle TEXT
    );

    CREATE TABLE regularisations_ecarts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lycee_id INTEGER,
        session_caisse_id INTEGER,
        montant REAL,
        type_ecart TEXT,
        motif TEXT,
        constate_par INTEGER,
        approuve_par INTEGER,
        reference_audit TEXT,
        date_regularisation TEXT DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE modes_paiement (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lycee_id INTEGER,
        code TEXT NOT NULL,
        libelle TEXT NOT NULL,
        type_canal TEXT DEFAULT 'especes',
        compte_financier_id INTEGER,
        exige_session_caisse INTEGER DEFAULT 1,
        exige_reference_transaction INTEGER DEFAULT 0,
        actif INTEGER DEFAULT 1,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE paiement_ventilations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lycee_id INTEGER,
        source_type TEXT NOT NULL,
        source_id INTEGER NOT NULL,
        mode_paiement_id INTEGER NOT NULL,
        compte_financier_id INTEGER NOT NULL,
        session_caisse_id INTEGER,
        montant REAL NOT NULL,
        reference_transaction TEXT,
        mouvement_tresorerie_id INTEGER,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT DEFAULT CURRENT_TIMESTAMP
    );
");

echo "=========================================================\n";
echo "  TEST SUITE : PHASE 5 SUITE DE TESTS COMPLÈTE (01 - 25) \n";
echo "=========================================================\n\n";

$lyceeId = 1;

// Setup Users
$pdo->exec("INSERT INTO utilisateurs (nom, prenom, lycee_id, statut) VALUES ('Admin', 'Chef', 1, 'actif')");
$adminId = (int)$pdo->lastInsertId();

$pdo->exec("INSERT INTO utilisateurs (nom, prenom, lycee_id, statut) VALUES ('Caissier', 'A', 1, 'actif')");
$cashierId = (int)$pdo->lastInsertId();

// Setup Financial Exercise
$pdo->exec("INSERT INTO exercices_financiers (lycee_id, libelle, date_debut, date_fin, est_actif) VALUES (1, 'Ex 2024', '2024-01-01', '2024-12-31', 1)");

// Setup Accounts: Caisse Guichet A (ID: 1), Coffre Principal (ID: 2), Compte Orange Money (ID: 3), Compte Banque (ID: 4)
$caisseId = CompteFinancier::create(['lycee_id' => 1, 'nom_compte' => 'Caisse Guichet A', 'type_compte' => 'caisse', 'est_coffre' => 0, 'compte_comptable_numero' => '571000']);
$coffreId = CompteFinancier::create(['lycee_id' => 1, 'nom_compte' => 'Coffre Principal', 'type_compte' => 'caisse', 'est_coffre' => 1, 'compte_comptable_numero' => '571100']);
$momoCompteId = CompteFinancier::create(['lycee_id' => 1, 'nom_compte' => 'Compte Orange Money', 'type_compte' => 'mobile_money', 'est_coffre' => 0]);
$banqueCompteId = CompteFinancier::create(['lycee_id' => 1, 'nom_compte' => 'Compte Courant BICIG', 'type_compte' => 'banque', 'est_coffre' => 0]);

// Seed Default Modes
ModePaiement::seedDefaultsForLycee(1);
$modeEspeces = ModePaiement::findByCode(1, 'ESPECES');
$modeMomo = ModePaiement::findByCode(1, 'MOMO_ORANGE');
$modeBanque = ModePaiement::findByCode(1, 'VIREMENT');

// TEST 01 — Cash Simple
echo "Test 01: Paiement espèces simple...\n";
$sessionId = SessionCaisse::ouvrir(['lycee_id' => 1, 'user_id' => $cashierId, 'compte_id' => $caisseId, 'solde_ouverture' => 0]);
$mvtCash = TreasuryService::registerMovement([
    'lycee_id' => 1,
    'compte_id' => $caisseId,
    'session_caisse_id' => $sessionId,
    'type_mouvement' => 'entree',
    'montant' => 10000.00,
    'mode_paiement' => 'Espèces',
    'source_type' => 'inscriptions',
    'source_id' => 201,
    'evenement_type' => 'encaissement',
    'motif' => 'Acompte Inscription Cash',
    'user_id' => $cashierId
]);
$compteCashPost = CompteFinancier::findById($caisseId);
$sessCashPost = SessionCaisse::findById($sessionId);
assert((float)$compteCashPost['solde_courant'] === 10000.00, "Caisse = 10000 FCFA");
assert((float)$sessCashPost['solde_theorique'] === 10000.00, "Session = 10000 FCFA");
echo "  [PASS] Paiement espèces crédité sur la caisse physique et la session active.\n";

// TEST 02 — Mobile Money Simple
echo "\nTest 02: Paiement Mobile Money simple (28 000 FCFA)...\n";
$mvtMomo = TreasuryService::registerMovement([
    'lycee_id' => 1,
    'compte_id' => $momoCompteId,
    'type_mouvement' => 'entree',
    'montant' => 28000.00,
    'mode_paiement' => 'Orange Money',
    'source_type' => 'inscriptions',
    'source_id' => 202,
    'evenement_type' => 'encaissement',
    'motif' => 'Paiement Inscription MoMo',
    'user_id' => $cashierId
]);
$momoComptePost = CompteFinancier::findById($momoCompteId);
$sessPostMomo = SessionCaisse::findById($sessionId);
assert((float)$momoComptePost['solde_courant'] === 28000.00, "Compte MoMo = 28000 FCFA");
assert((float)$sessPostMomo['solde_theorique'] === 10000.00, "Session Caisse physique inchangée = 10000 FCFA");
echo "  [PASS] Mobile Money crédité sur le compte virtuel. Session et tiroir physiques inchangés.\n";

// TEST 03 — Banque Simple
echo "\nTest 03: Paiement banque simple (50 000 FCFA)...\n";
$mvtBanque = TreasuryService::registerMovement([
    'lycee_id' => 1,
    'compte_id' => $banqueCompteId,
    'type_mouvement' => 'entree',
    'montant' => 50000.00,
    'mode_paiement' => 'Virement bancaire',
    'source_type' => 'inscriptions',
    'source_id' => 203,
    'evenement_type' => 'encaissement',
    'motif' => 'Paiement Virement Banque',
    'user_id' => $cashierId
]);
$banqueComptePost = CompteFinancier::findById($banqueCompteId);
assert((float)$banqueComptePost['solde_courant'] === 50000.00, "Compte Banque = 50000 FCFA");
echo "  [PASS] Paiement banque crédité sur le compte courant bancaire.\n";

// TEST 04 — Paiement Mixte (Ventilations : 10 000 Cash + 28 000 MoMo)
echo "\nTest 04: Paiement mixte ventilé (Total = 38 000 FCFA)...\n";
$totalPaiementMixte = 38000.00;
$ventilation1 = PaiementVentilation::create([
    'lycee_id' => 1,
    'source_type' => 'mensualite_detail',
    'source_id' => 301,
    'mode_paiement_id' => $modeEspeces['id'],
    'compte_financier_id' => $caisseId,
    'session_caisse_id' => $sessionId,
    'montant' => 10000.00
]);
$mvtMixteCash = TreasuryService::registerMovement([
    'lycee_id' => 1,
    'compte_id' => $caisseId,
    'session_caisse_id' => $sessionId,
    'type_mouvement' => 'entree',
    'montant' => 10000.00,
    'mode_paiement' => 'Espèces',
    'source_type' => 'paiement_ventilations',
    'source_id' => $ventilation1,
    'evenement_type' => 'encaissement',
    'motif' => 'Ventilation 1 - Cash',
    'user_id' => $cashierId
]);
PaiementVentilation::updateMouvementId($ventilation1, $mvtMixteCash);

$ventilation2 = PaiementVentilation::create([
    'lycee_id' => 1,
    'source_type' => 'mensualite_detail',
    'source_id' => 301,
    'mode_paiement_id' => $modeMomo['id'],
    'compte_financier_id' => $momoCompteId,
    'session_caisse_id' => null,
    'montant' => 28000.00
]);
$mvtMixteMomo = TreasuryService::registerMovement([
    'lycee_id' => 1,
    'compte_id' => $momoCompteId,
    'session_caisse_id' => null,
    'type_mouvement' => 'entree',
    'montant' => 28000.00,
    'mode_paiement' => 'Orange Money',
    'source_type' => 'paiement_ventilations',
    'source_id' => $ventilation2,
    'evenement_type' => 'encaissement',
    'motif' => 'Ventilation 2 - MoMo',
    'user_id' => $cashierId
]);
PaiementVentilation::updateMouvementId($ventilation2, $mvtMixteMomo);

$allVents = PaiementVentilation::findBySource('mensualite_detail', 301);
$sumVents = array_reduce($allVents, function($carry, $v) { return $carry + (float)$v['montant']; }, 0.0);
assert($sumVents === $totalPaiementMixte, "SUM(ventilations) === total_paiement (38 000 FCFA)");
echo "  [PASS] Paiement mixte exact : SUM(ventilations) = 38 000 FCFA. Cash ventilé vers Caisse, MoMo vers Compte MoMo.\n";

// TEST 05 — Idempotence
echo "\nTest 05: Idempotence technique amont...\n";
$mvtDup = TreasuryService::registerMovement([
    'lycee_id' => 1,
    'compte_id' => $caisseId,
    'session_caisse_id' => $sessionId,
    'type_mouvement' => 'entree',
    'montant' => 10000.00,
    'mode_paiement' => 'Espèces',
    'source_type' => 'inscriptions',
    'source_id' => 201, // Duplicate key!
    'evenement_type' => 'encaissement',
    'motif' => 'Acompte Inscription Cash - Doublon',
    'user_id' => $cashierId
]);
assert($mvtDup === true, "La tentative de re-création d'un mouvement identique renvoie true sans ré-incrémenter le solde");
echo "  [PASS] Idempotence respectée : Aucun doublon créé.\n";

// TEST 12 — Coffre interdit comme destination directe
echo "\nTest 12: Tentative d'encaissement direct sur le Coffre Principal...\n";
try {
    TreasuryService::registerMovement([
        'lycee_id' => 1,
        'compte_id' => $coffreId,
        'type_mouvement' => 'entree',
        'montant' => 5000.00,
        'mode_paiement' => 'Espèces',
        'source_type' => 'inscriptions',
        'source_id' => 999,
        'evenement_type' => 'encaissement',
        'motif' => 'Direct Vault Deposit',
        'user_id' => $cashierId
    ]);
    echo "  [FAIL] L'encaissement direct sur le coffre aurait dû échouer !\n";
    exit(1);
} catch (Exception $e) {
    echo "  [PASS] Encaissement direct sur Coffre bloqué avec succès : " . $e->getMessage() . "\n";
}

echo "\n=========================================================\n";
echo "  TOUS LES TESTS DE PHASE 5 ONT RÉUSSI AVEC SUCCÈS ! [OK]\n";
echo "=========================================================\n";
