<?php

error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/Lycee.php';
require_once __DIR__ . '/../src/models/CompteFinancier.php';
require_once __DIR__ . '/../src/services/SchoolInitializationService.php';

@session_start();

$dbFile = '/tmp/test_phase_3_provisioning.sqlite';

if (file_exists($dbFile)) {
    unlink($dbFile);
}

$pdo = new PDO('sqlite:' . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

Database::setInstance($pdo);

// Create required SQLite schema matching db/schema.sql
$pdo->exec("
    CREATE TABLE param_lycee (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nom_lycee TEXT NOT NULL,
        type_lycee TEXT,
        adresse TEXT,
        telephone TEXT,
        tel TEXT,
        email TEXT,
        sigle TEXT,
        ville TEXT,
        quartier TEXT,
        ruelle TEXT,
        boite_postale TEXT,
        arrete TEXT,
        arrondissement TEXT,
        devise TEXT,
        logo TEXT,
        boutique INTEGER DEFAULT 0,
        code_lycee TEXT
    );

    CREATE TABLE utilisateurs (
        id_user INTEGER PRIMARY KEY AUTOINCREMENT,
        identifiant TEXT,
        nom TEXT,
        prenom TEXT,
        email TEXT,
        mot_de_passe TEXT,
        role_id INTEGER,
        id_role INTEGER,
        statut TEXT,
        lycee_id INTEGER,
        actif INTEGER DEFAULT 1
    );

    CREATE TABLE roles (
        id_role INTEGER PRIMARY KEY AUTOINCREMENT,
        nom_role TEXT,
        statut TEXT,
        lycee_id INTEGER
    );

    CREATE TABLE permissions (
        id_permission INTEGER PRIMARY KEY AUTOINCREMENT,
        nom_permission TEXT
    );

    CREATE TABLE role_permissions (
        id_role INTEGER,
        id_permission INTEGER
    );

    CREATE TABLE param_general (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lycee_id INTEGER,
        sequence_annuelle TEXT,
        devise_pays TEXT,
        monnaie TEXT,
        symbole_monnaie TEXT,
        modalite_paiement TEXT,
        nb_langue INTEGER DEFAULT 1,
        langue_1 TEXT DEFAULT 'Francais',
        langue_2 TEXT,
        mode_cycle TEXT DEFAULT 'separe_ceg_lycee',
        multilingue_actif INTEGER DEFAULT 0,
        biometrie_actif INTEGER DEFAULT 0,
        confidentialite_nationale INTEGER DEFAULT 0,
        nom_fondateur TEXT,
        titre_fondateur TEXT,
        nom_directeur TEXT,
        titre_directeur TEXT,
        footer_bulletin TEXT,
        signature_directeur TEXT,
        mode_calcul_bulletin TEXT,
        slogan TEXT
    );

    CREATE TABLE annees_academiques (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lycee_id INTEGER,
        libelle TEXT,
        date_debut TEXT,
        date_fin TEXT,
        statut TEXT,
        est_active INTEGER,
        cloturee INTEGER DEFAULT 0
    );

    CREATE TABLE sequences (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lycee_id INTEGER,
        annee_academique_id INTEGER,
        nom TEXT,
        type TEXT,
        date_debut TEXT,
        date_fin TEXT,
        statut TEXT
    );

    CREATE TABLE param_type_evaluation (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lycee_id INTEGER,
        code TEXT,
        libelle TEXT,
        bareme_defaut REAL,
        nombre_evaluation INTEGER,
        actif INTEGER,
        ordre_affichage INTEGER
    );

    CREATE TABLE exercices_financiers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lycee_id INTEGER,
        libelle TEXT,
        date_debut TEXT,
        date_fin TEXT,
        est_actif INTEGER,
        cloture INTEGER,
        type_exercice TEXT
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

    INSERT INTO roles (id_role, nom_role, statut) VALUES (1, 'admin_local', 'actif');
");

echo "=========================================================\n";
echo "  TEST SUITE : PHASE 3 PROVISIONNEMENT COFFRE & CAISSES  \n";
echo "=========================================================\n\n";

// Create test user
$stmtU = $pdo->prepare("INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role_id, statut) VALUES ('Admin', 'Test', 'admin@test.com', 'hash', 1, 'actif')");
$stmtU->execute();
$userId = (int)$pdo->lastInsertId();

// TEST A — Initialisation normale
echo "Test A: Initialisation normale du lycée...\n";
$initData = [
    'nom_lycee' => 'Lycée Test Phase 3',
    'type_lycee' => 'prive',
    'user_id' => $userId,
    'admin_nom' => 'Admin',
    'admin_prenom' => 'Local',
    'admin_email' => 'adminlocal@test.com',
    'admin_pass' => 'password123',
    'annee_libelle' => '2024-2025',
    'annee_date_debut' => '2024-09-01',
    'annee_date_fin' => '2025-06-30',
    'sequence_annuelle' => 'trimestrielle',
    'periods' => [
        ['nom' => 'Trimestre 1', 'date_debut' => '2024-09-01', 'date_fin' => '2024-12-15'],
        ['nom' => 'Trimestre 2', 'date_debut' => '2025-01-05', 'date_fin' => '2025-03-25'],
        ['nom' => 'Trimestre 3', 'date_debut' => '2025-04-05', 'date_fin' => '2025-06-30']
    ],
    'devise_pays' => 'FCFA'
];

$resInit = SchoolInitializationService::initializeSchool($initData);

$comptes = CompteFinancier::findByLycee($resInit['lycee_id']);
assert(count($comptes) === 2, "Le nombre de comptes financiers créés doit être exactement 2");

$coffre = null;
$caisse = null;

foreach ($comptes as $c) {
    if ((int)$c['est_coffre'] === 1) {
        $coffre = $c;
    } else {
        $caisse = $c;
    }
}

assert($coffre !== null, "Le Coffre Principal doit être créé");
assert($caisse !== null, "La Caisse Guichet 1 doit être créée");

assert($coffre['nom_compte'] === 'Coffre Principal', "Nom du coffre = Coffre Principal");
assert($caisse['nom_compte'] === 'Caisse Guichet 1', "Nom de la caisse = Caisse Guichet 1");

assert((int)$coffre['est_coffre'] === 1, "coffre.est_coffre = 1");
assert((int)$caisse['est_coffre'] === 0, "caisse.est_coffre = 0");

assert((int)$coffre['id'] !== (int)$caisse['id'], "coffre.id != caisse.id");

assert((float)$coffre['solde_courant'] === 0.00, "coffre.solde_courant = 0.00");
assert((float)$caisse['solde_courant'] === 0.00, "caisse.solde_courant = 0.00");

echo "  [PASS] Initialisation normale : 1 Coffre Principal (ID: {$coffre['id']}) et 1 Caisse Guichet 1 (ID: {$caisse['id']}) créés avec succès.\n";

// TEST B — Ré-exécution (Idempotence)
echo "\nTest B: Ré-exécution de initializeSchool (Idempotence)...\n";
$initDataReexec = $initData;
$initDataReexec['lycee_id'] = $resInit['lycee_id'];
unset($initDataReexec['admin_email']); // Avoid admin email duplication check when re-running on existing lycee

SchoolInitializationService::initializeSchool($initDataReexec);

$comptesPost = CompteFinancier::findByLycee($resInit['lycee_id']);
assert(count($comptesPost) === 2, "Le nombre de comptes financiers doit rester égal à 2 après ré-exécution");
echo "  [PASS] Idempotence respectée : Aucun doublon créé lors de la ré-exécution.\n";

// TEST C — Tentative de création manuelle d'un second Coffre
echo "\nTest C: Tentative de création manuelle d'un second Coffre Principal...\n";
try {
    CompteFinancier::create([
        'lycee_id' => $resInit['lycee_id'],
        'nom_compte' => 'Deuxième Coffre',
        'type_compte' => 'caisse',
        'solde_courant' => 0.00,
        'devise' => 'FCFA',
        'est_coffre' => 1
    ]);
    echo "  [FAIL] La création d'un second coffre aurait dû être rejetée !\n";
    exit(1);
} catch (Exception $e) {
    echo "  [PASS] Seconde création de coffre correctement rejetée : " . $e->getMessage() . "\n";
}

// TEST D — Isolation des IDs (Coffre vs Caisse)
echo "\nTest D: Isolation des IDs...\n";
assert((int)$coffre['id'] !== (int)$caisse['id'], "IDs distincts garantis");
echo "  [PASS] coffre_id ({$coffre['id']}) != caisse_id ({$caisse['id']}).\n";

// TEST E — Protection contre le scénario historique (26 500 -> 53 000)
echo "\nTest E: Protection contre le scénario d'identité d'IDs (caisseId === coffreId)...\n";
$resolvedCoffreId = CompteFinancier::findCoffreByLycee($resInit['lycee_id']);
assert((int)$resolvedCoffreId === (int)$coffre['id'], "Le Coffre résolu correspond au Coffre Principal");
assert((int)$resolvedCoffreId !== (int)$caisse['id'], "Le Coffre résolu n'est PAS la Caisse Guichet 1");
echo "  [PASS] Résolution du coffre strictement isolée de la caisse opérationnelle.\n";

echo "\n=========================================================\n";
echo "  TOUS LES TESTS DE PHASE 3 ONT RÉUSSI AVEC SUCCÈS ! [OK]\n";
echo "=========================================================\n";
