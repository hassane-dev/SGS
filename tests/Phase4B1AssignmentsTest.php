<?php

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/CompteFinancier.php';
require_once __DIR__ . '/../src/models/AffectationCaisse.php';
require_once __DIR__ . '/../src/models/SessionCaisse.php';
require_once __DIR__ . '/../db/migrations/20240115_29_create_affectations_caisses_and_extend_sessions.php';

@session_start();

$dbFile = '/tmp/test_phase_4b1_assignments.sqlite';

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

    CREATE TABLE affectations_caisses (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lycee_id INTEGER,
        compte_id INTEGER,
        user_id INTEGER,
        type_affectation TEXT DEFAULT 'titulaire',
        date_debut TEXT,
        date_fin TEXT,
        statut TEXT DEFAULT 'actif',
        attribue_par INTEGER,
        motif_remplacement TEXT,
        cree_le TEXT DEFAULT CURRENT_TIMESTAMP
    );
");

echo "=========================================================\n";
echo "  TEST SUITE : PHASE 4B.1 AFFECTATIONS & TRANSMISSION   \n";
echo "=========================================================\n\n";

$lyceeId = 1;

// Create Admin & Cashiers
$pdo->exec("INSERT INTO utilisateurs (nom, prenom, lycee_id, statut) VALUES ('Admin', 'Chef', 1, 'actif')");
$adminId = (int)$pdo->lastInsertId();

$pdo->exec("INSERT INTO utilisateurs (nom, prenom, lycee_id, statut) VALUES ('Caissier', 'A', 1, 'actif')");
$userA = (int)$pdo->lastInsertId();

$pdo->exec("INSERT INTO utilisateurs (nom, prenom, lycee_id, statut) VALUES ('Caissier', 'B', 1, 'actif')");
$userB = (int)$pdo->lastInsertId();

// Create 1 Coffre and 2 Caisses
$coffreId = CompteFinancier::create(['lycee_id' => 1, 'nom_compte' => 'Coffre Principal', 'type_compte' => 'caisse', 'est_coffre' => 1]);
$caisseAId = CompteFinancier::create(['lycee_id' => 1, 'nom_compte' => 'Caisse Guichet A', 'type_compte' => 'caisse', 'est_coffre' => 0]);
$caisseBId = CompteFinancier::create(['lycee_id' => 1, 'nom_compte' => 'Caisse Guichet B', 'type_compte' => 'caisse', 'est_coffre' => 0]);

// TEST 1 — Anti-Overlap Titulaire Check
echo "Test 1: Anti-chevauchement des titulaires sur Caisse A...\n";
AffectationCaisse::create([
    'lycee_id' => 1,
    'compte_id' => $caisseAId,
    'user_id' => $userA,
    'type_affectation' => 'titulaire',
    'date_debut' => '2024-01-01',
    'date_fin' => null,
    'attribue_par' => $adminId
]);

try {
    AffectationCaisse::create([
        'lycee_id' => 1,
        'compte_id' => $caisseAId,
        'user_id' => $userB,
        'type_affectation' => 'titulaire',
        'date_debut' => '2024-02-01',
        'date_fin' => null,
        'attribue_par' => $adminId
    ]);
    echo "  [FAIL] Le second titulaire aurait dû être rejeté !\n";
    exit(1);
} catch (Exception $e) {
    echo "  [PASS] Chevauchement titulaire bloqué : " . $e->getMessage() . "\n";
}

// TEST 2 — Remplacement temporaire autorisé
echo "\nTest 2: Création d'un remplacement temporaire pour Caissier B sur Caisse A...\n";
$affB = AffectationCaisse::create([
    'lycee_id' => 1,
    'compte_id' => $caisseAId,
    'user_id' => $userB,
    'type_affectation' => 'remplacant',
    'date_debut' => date('Y-m-d'),
    'date_fin' => date('Y-m-d', strtotime('+7 days')),
    'attribue_par' => $adminId,
    'motif_remplacement' => 'Congé maladie Caissier A'
]);
assert($affB > 0, "Remplacement temporaire créé avec succès");
echo "  [PASS] Remplacement temporaire enregistré (ID #{$affB}).\n";

// TEST 3 — Scénario A -> A (Titulaire ouvre Caisse A, clôture avec 5000 FCFA conservés)
echo "\nTest 3: Session 1 (Caissier A sur Caisse A, clôture avec 5 000 FCFA conservés)...\n";
$compteAObj = CompteFinancier::findById($caisseAId);
$soldeCourantInitial = (float)$compteAObj['solde_courant'];

$sess1 = SessionCaisse::ouvrir(['lycee_id' => 1, 'user_id' => $userA, 'compte_id' => $caisseAId, 'solde_ouverture' => 0]);
SessionCaisse::cloturer($sess1, 15000, 'Fin de journée', 10000, 5000);
$pdo->exec("UPDATE sessions_caisse SET statut = 'fermee_validee' WHERE id = " . $sess1);

$compteAPost = CompteFinancier::findById($caisseAId);
assert((float)$compteAPost['solde_courant'] === $soldeCourantInitial, "Le solde courant ne doit pas changer lors de la clôture/ouverture");
echo "  [PASS] Session #{$sess1} clôturée et validée. Fonds conservé = 5 000 FCFA.\n";

// TEST 4 — Scénario B -> A (Remplaçant B ouvre Caisse A sans confirmation de prise en charge -> REJET)
echo "\nTest 4: Tentative d'ouverture sans confirmation de prise en charge...\n";
try {
    SessionCaisse::ouvrir(['lycee_id' => 1, 'user_id' => $userB, 'compte_id' => $caisseAId, 'prise_en_charge_confirmee' => 0]);
    echo "  [FAIL] L'ouverture sans confirmation de prise en charge aurait dû échouer !\n";
    exit(1);
} catch (Exception $e) {
    echo "  [PASS] Ouverture sans confirmation rejetée : " . $e->getMessage() . "\n";
}

// TEST 5 — Scénario B -> A (Remplaçant B ouvre Caisse A AVEC confirmation de prise en charge -> SUCCÈS)
echo "\nTest 5: Ouverture de Caisse A par Caissier B avec confirmation de prise en charge...\n";
$sess2 = SessionCaisse::ouvrir(['lycee_id' => 1, 'user_id' => $userB, 'compte_id' => $caisseAId, 'prise_en_charge_confirmee' => 1]);
$sess2Obj = SessionCaisse::findById($sess2);

assert((float)$sess2Obj['solde_ouverture'] === 5000.00, "Solde d'ouverture = 5 000 FCFA");
assert((int)$sess2Obj['fonds_source_session_id'] === (int)$sess1, "Session source = Session #{$sess1}");
assert((int)$sess2Obj['fonds_source_user_id'] === (int)$userA, "Utilisateur source = Caissier A");
assert((int)$sess2Obj['prise_en_charge_confirmee'] === 1, "Prise en charge confirmée = TRUE");
assert(!empty($sess2Obj['date_prise_en_charge']), "Date de prise en charge enregistrée");

echo "  [PASS] Session #{$sess2} ouverte par Caissier B avec preuve inaltérable de transmission : 5 000 FCFA hérités de Caissier A (Session #{$sess1}).\n";

// TEST 6 — Absence de mutation de solde_courant
$compteAFinal = CompteFinancier::findById($caisseAId);
assert((float)$compteAFinal['solde_courant'] === $soldeCourantInitial, "solde_courant Caisse A n'a subi AUCUNE mutation lors de la transmission");
echo "  [PASS] Vérification d'invariant : solde_courant Caisse A strictement inchangé.\n";

echo "\n=========================================================\n";
echo "  TOUS LES TESTS DE PHASE 4B.1 ONT RÉUSSI AVEC SUCCÈS ! [OK]\n";
echo "=========================================================\n";
