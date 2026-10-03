<?php

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/CompteFinancier.php';
require_once __DIR__ . '/../src/models/SessionCaisse.php';

@session_start();

$dbFile = '/tmp/test_phase_4_sessions.sqlite';

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
");

echo "=========================================================\n";
echo "  TEST SUITE : PHASE 4 SESSIONS ET ISOLATION MULTI-CAISSIERS \n";
echo "=========================================================\n\n";

$lyceeId = 1;

// Create 3 Users / Cashiers
$pdo->exec("INSERT INTO utilisateurs (nom, prenom, lycee_id, statut) VALUES ('Caissier', 'A', 1, 'actif')");
$userA = (int)$pdo->lastInsertId();

$pdo->exec("INSERT INTO utilisateurs (nom, prenom, lycee_id, statut) VALUES ('Caissier', 'B', 1, 'actif')");
$userB = (int)$pdo->lastInsertId();

$pdo->exec("INSERT INTO utilisateurs (nom, prenom, lycee_id, statut) VALUES ('Caissier', 'C', 1, 'actif')");
$userC = (int)$pdo->lastInsertId();

// Create 1 Coffre and 3 Caisses
$coffreId = CompteFinancier::create(['lycee_id' => 1, 'nom_compte' => 'Coffre Principal', 'type_compte' => 'caisse', 'est_coffre' => 1]);
$caisseAId = CompteFinancier::create(['lycee_id' => 1, 'nom_compte' => 'Caisse Guichet A', 'type_compte' => 'caisse', 'est_coffre' => 0]);
$caisseBId = CompteFinancier::create(['lycee_id' => 1, 'nom_compte' => 'Caisse Guichet B', 'type_compte' => 'caisse', 'est_coffre' => 0]);
$caisseCId = CompteFinancier::create(['lycee_id' => 1, 'nom_compte' => 'Caisse Guichet C', 'type_compte' => 'caisse', 'est_coffre' => 0]);

// TEST 1 — Interdiction d'ouverture de session sur le Coffre Principal
echo "Test 1: Tentative d'ouverture de session sur le Coffre Principal...\n";
try {
    SessionCaisse::ouvrir(['lycee_id' => 1, 'user_id' => $userA, 'compte_id' => $coffreId, 'solde_ouverture' => 0]);
    echo "  [FAIL] L'ouverture de session sur le Coffre aurait dû être rejetée !\n";
    exit(1);
} catch (Exception $e) {
    echo "  [PASS] Session sur Coffre bloquée avec succès : " . $e->getMessage() . "\n";
}

// TEST 2 — 3 Caissiers sur 3 Caisses Simultanées
echo "\nTest 2: Ouverture de 3 sessions simultanées sur 3 caisses différentes...\n";
$sessionA = SessionCaisse::ouvrir(['lycee_id' => 1, 'user_id' => $userA, 'compte_id' => $caisseAId, 'solde_ouverture' => 0]);
$sessionB = SessionCaisse::ouvrir(['lycee_id' => 1, 'user_id' => $userB, 'compte_id' => $caisseBId, 'solde_ouverture' => 0]);
$sessionC = SessionCaisse::ouvrir(['lycee_id' => 1, 'user_id' => $userC, 'compte_id' => $caisseCId, 'solde_ouverture' => 0]);

assert($sessionA && $sessionB && $sessionC, "Les 3 sessions doivent être ouvertes avec succès");
echo "  [PASS] 3 sessions simultanées ouvertes (Sessions #{$sessionA}, #{$sessionB}, #{$sessionC}).\n";

// TEST 3 — Tentative pour un MÊME caissier d'ouvrir 2 caisses simultanées
echo "\nTest 3: Tentative pour Caissier A d'ouvrir une seconde caisse simultanément...\n";
try {
    SessionCaisse::ouvrir(['lycee_id' => 1, 'user_id' => $userA, 'compte_id' => $caisseBId, 'solde_ouverture' => 0]);
    echo "  [FAIL] Caissier A ne devrait pas pouvoir ouvrir 2 caisses simultanément !\n";
    exit(1);
} catch (Exception $e) {
    echo "  [PASS] Multi-session caissier bloquée avec succès : " . $e->getMessage() . "\n";
}

// TEST 4 — Succession de caissiers sur la MÊME caisse (Session 1 fermée -> Session 2 ouverte par Caissier A)
echo "\nTest 4: Succession de caissiers sur Caisse A...\n";
// Caissier A clôture sa session A
SessionCaisse::cloturer($sessionA, 15000, 'Fin de service', 10000, 5000);
$pdo->exec("UPDATE sessions_caisse SET statut = 'fermee_validee' WHERE id = " . $sessionA);

// Caissier B clôture aussi sa session B
SessionCaisse::cloturer($sessionB, 20000, 'Fin de service', 20000, 0);
$pdo->exec("UPDATE sessions_caisse SET statut = 'fermee_validee' WHERE id = " . $sessionB);

// Caissier B ouvre maintenant Caisse A avec confirmation de prise en charge du fonds de 5 000 FCFA
$sessionA2 = SessionCaisse::ouvrir(['lycee_id' => 1, 'user_id' => $userB, 'compte_id' => $caisseAId, 'solde_ouverture' => 0, 'prise_en_charge_confirmee' => 1]);
$sessA2Obj = SessionCaisse::findById($sessionA2);

assert((float)$sessA2Obj['solde_ouverture'] === 5000.00, "Le solde d'ouverture doit hériter automatiquement des 5 000 FCFA conservés");
echo "  [PASS] Succession de caissiers réussie : Session #{$sessionA2} ouverte sur Caisse A par Caissier B avec solde d'ouverture automatique de 5 000 FCFA.\n";

echo "\n=========================================================\n";
echo "  TOUS LES TESTS DE PHASE 4 ONT RÉUSSI AVEC SUCCÈS ! [OK]\n";
echo "=========================================================\n";
