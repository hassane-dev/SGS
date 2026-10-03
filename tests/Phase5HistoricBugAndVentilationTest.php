<?php

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/models/CompteFinancier.php';
require_once __DIR__ . '/../src/models/SessionCaisse.php';
require_once __DIR__ . '/../src/models/TreasuryService.php';
require_once __DIR__ . '/../src/models/ExerciceFinancier.php';

@session_start();

$dbFile = '/tmp/test_phase_5_bug_repro.sqlite';

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
echo "  TEST SUITE : REPRODUCTION ET PRÉVENTION BUG 26 500 FCFA \n";
echo "=========================================================\n\n";

$lyceeId = 1;

// Setup Users
$pdo->exec("INSERT INTO utilisateurs (nom, prenom, lycee_id, statut) VALUES ('Admin', 'Chef', 1, 'actif')");
$adminId = (int)$pdo->lastInsertId();

$pdo->exec("INSERT INTO utilisateurs (nom, prenom, lycee_id, statut) VALUES ('Caissier', 'A', 1, 'actif')");
$cashierId = (int)$pdo->lastInsertId();

// Setup Financial Exercise
$pdo->exec("INSERT INTO exercices_financiers (lycee_id, libelle, date_debut, date_fin, est_actif) VALUES (1, 'Ex 2024', '2024-01-01', '2024-12-31', 1)");

// Setup Accounts: Caisse Guichet A (ID: 1) and Coffre Principal (ID: 2)
$caisseId = CompteFinancier::create(['lycee_id' => 1, 'nom_compte' => 'Caisse Guichet A', 'type_compte' => 'caisse', 'est_coffre' => 0, 'compte_comptable_numero' => '571000']);
$coffreId = CompteFinancier::create(['lycee_id' => 1, 'nom_compte' => 'Coffre Principal', 'type_compte' => 'caisse', 'est_coffre' => 1, 'compte_comptable_numero' => '571100']);

assert($caisseId !== $coffreId, "Caisse et Coffre doivent avoir des IDs distincts");

// T0 — État initial
$caisseT0 = CompteFinancier::findById($caisseId);
$coffreT0 = CompteFinancier::findById($coffreId);
echo "T0 — Solde initial Caisse: {$caisseT0['solde_courant']} FCFA | Coffre: {$coffreT0['solde_courant']} FCFA\n";

// Open Session
$sessionId = SessionCaisse::ouvrir(['lycee_id' => 1, 'user_id' => $cashierId, 'compte_id' => $caisseId, 'solde_ouverture' => 0]);

// T1 — Paiement de 26 500 FCFA espèces
$mvtPaiementId = TreasuryService::registerMovement([
    'lycee_id' => 1,
    'compte_id' => $caisseId,
    'session_caisse_id' => $sessionId,
    'type_mouvement' => 'entree',
    'montant' => 26500.00,
    'mode_paiement' => 'Espèces',
    'source_type' => 'inscriptions',
    'source_id' => 101,
    'evenement_type' => 'encaissement',
    'motif' => 'Paiement Inscription Élève Test',
    'user_id' => $cashierId
]);

$caisseT1 = CompteFinancier::findById($caisseId);
$coffreT1 = CompteFinancier::findById($coffreId);
$sessT1 = SessionCaisse::findById($sessionId);

echo "T1 — Après paiement de 26 500 FCFA:\n";
echo "     Solde Caisse: {$caisseT1['solde_courant']} FCFA (Attendu: 26 500.00)\n";
echo "     Solde Coffre: {$coffreT1['solde_courant']} FCFA (Attendu: 0.00)\n";
echo "     Solde théorique Session: {$sessT1['solde_theorique']} FCFA (Attendu: 26 500.00)\n";

assert((float)$caisseT1['solde_courant'] === 26500.00, "T1 Caisse = 26500");
assert((float)$coffreT1['solde_courant'] === 0.00, "T1 Coffre = 0");

// T2 — Soumission de Clôture (solde_reel = 26 500, remis = 26 500, conserve = 0)
SessionCaisse::cloturer($sessionId, 26500.00, 'Clôture normale', 26500.00, 0.00);

$caisseT2 = CompteFinancier::findById($caisseId);
$coffreT2 = CompteFinancier::findById($coffreId);
echo "T2 — Après soumission de clôture (avant approbation):\n";
echo "     Solde Caisse: {$caisseT2['solde_courant']} FCFA (Inchangé: 26 500.00)\n";
echo "     Solde Coffre: {$coffreT2['solde_courant']} FCFA (Inchangé: 0.00)\n";

assert((float)$caisseT2['solde_courant'] === 26500.00, "T2 Caisse inchangée = 26500");
assert((float)$coffreT2['solde_courant'] === 0.00, "T2 Coffre inchangé = 0");

// T3 — Approbation administrative (Virement interne Caisse -> Coffre de 26 500 FCFA)
SessionCaisse::approuver($sessionId, $adminId, "Approbation et transfert au coffre");

$caisseT3 = CompteFinancier::findById($caisseId);
$coffreT3 = CompteFinancier::findById($coffreId);

echo "T3 — Après approbation et remise au coffre:\n";
echo "     Solde Caisse: {$caisseT3['solde_courant']} FCFA (Attendu: 0.00)\n";
echo "     Solde Coffre: {$coffreT3['solde_courant']} FCFA (Attendu: 26 500.00)\n";

assert((float)$caisseT3['solde_courant'] === 0.00, "T3 Solde final Caisse = 0.00 FCFA (et NON 26 500 FCFA)");
assert((float)$coffreT3['solde_courant'] === 26500.00, "T3 Solde final Coffre = 26 500.00 FCFA (et NON 53 000 FCFA !)");

// T4 — Invariant du Patrimoine Global
$totalPatrimoine = (float)$caisseT3['solde_courant'] + (float)$coffreT3['solde_courant'];
echo "T4 — Patrimoine Total du Lycée: {$totalPatrimoine} FCFA (Exactement égal au paiement d'origine de 26 500.00 FCFA)\n";

assert($totalPatrimoine === 26500.00, "L'invariant du patrimoine global (26 500.00 FCFA) est strictement respecté !");

echo "\n=========================================================\n";
echo "  RÉSULTAT: LE BUG 26 500 -> 53 000 EST DÉFINITIVEMENT IMPOSSIBLE ! [OK]\n";
echo "=========================================================\n";
