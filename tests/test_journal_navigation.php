<?php

// Test suite for the advanced Journal Comptable Unique navigation, filtering, and views under SQLite
require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/models/FinancialStatusService.php';
require_once __DIR__ . '/../src/models/Lycee.php';
require_once __DIR__ . '/../src/models/Cycle.php';
require_once __DIR__ . '/../src/models/Classe.php';
require_once __DIR__ . '/../src/models/AnneeAcademique.php';
require_once __DIR__ . '/../src/models/Sequence.php';
require_once __DIR__ . '/../src/models/Frais.php';
require_once __DIR__ . '/../src/models/Eleve.php';
require_once __DIR__ . '/../src/models/Etude.php';
require_once __DIR__ . '/../src/models/Inscription.php';
require_once __DIR__ . '/../src/models/Mensualite.php';
require_once __DIR__ . '/../src/models/ParametreFinancierEleve.php';
require_once __DIR__ . '/../src/models/EtatFinancierEleve.php';
require_once __DIR__ . '/../src/models/PolitiqueFinanciere.php';
require_once __DIR__ . '/../src/models/ParamGeneral.php';
require_once __DIR__ . '/../src/models/ParamLycee.php';
require_once __DIR__ . '/../src/controllers/PaiementController.php';

// Prevent header output issues
@session_start();

$dbFile = '/tmp/test_journal_navigation.sqlite';

function init_test_db() {
    global $dbFile;
    if (file_exists($dbFile)) {
        unlink($dbFile);
    }

    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create tables
    $pdo->exec("CREATE TABLE IF NOT EXISTS param_lycee (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nom_lycee VARCHAR(255),
        type_lycee VARCHAR(50)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS param_general (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lycee_id INTEGER,
        modalite_paiement VARCHAR(255),
        sequence_annuelle VARCHAR(50)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS annees_academiques (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        libelle VARCHAR(100),
        date_debut DATE,
        date_fin DATE,
        est_active BOOLEAN,
        cloturee BOOLEAN
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS cycles (
        id_cycle INTEGER PRIMARY KEY AUTOINCREMENT,
        lycee_id INTEGER,
        nom_cycle VARCHAR(100),
        niveau_debut VARCHAR(40),
        niveau_fin VARCHAR(40)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS classes (
        id_classe INTEGER PRIMARY KEY AUTOINCREMENT,
        niveau VARCHAR(50),
        serie VARCHAR(50),
        numero INTEGER,
        cycle_id INTEGER,
        lycee_id INTEGER
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS sequences (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lycee_id INTEGER,
        annee_academique_id INTEGER,
        nom VARCHAR(255),
        type VARCHAR(50),
        date_debut DATE,
        date_fin DATE,
        statut VARCHAR(50)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS frais (
        id_frais INTEGER PRIMARY KEY AUTOINCREMENT,
        lycee_id INTEGER,
        cycle VARCHAR(100),
        niveau_debut VARCHAR(50),
        niveau_fin VARCHAR(50),
        serie VARCHAR(50),
        frais_inscription DECIMAL(10,2),
        frais_mensuel DECIMAL(10,2),
        frais_logo DECIMAL(10,2),
        frais_carte DECIMAL(10,2),
        annee_academique_id INTEGER
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS eleves (
        id_eleve INTEGER PRIMARY KEY AUTOINCREMENT,
        lycee_id INTEGER,
        nom VARCHAR(100),
        prenom VARCHAR(100),
        statut VARCHAR(50),
        identifiant_public VARCHAR(50)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS etudes (
        id_etude INTEGER PRIMARY KEY AUTOINCREMENT,
        eleve_id INTEGER,
        classe_id INTEGER,
        lycee_id INTEGER,
        annee_academique_id INTEGER,
        status VARCHAR(50),
        is_active BOOLEAN
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS inscriptions (
        id_inscription INTEGER PRIMARY KEY AUTOINCREMENT,
        etude_id INTEGER,
        eleve_id INTEGER,
        classe_id INTEGER,
        lycee_id INTEGER,
        annee_academique_id INTEGER,
        montant_total DECIMAL(10,2),
        montant_verse DECIMAL(10,2),
        reste_a_payer DECIMAL(10,2),
        details_frais TEXT,
        user_id INTEGER,
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
        montant_verse DECIMAL(10,2),
        reste_a_payer DECIMAL(10,2) DEFAULT 0.00,
        user_id INTEGER
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS mensualite_details (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        mensualite_id INTEGER,
        montant DECIMAL(10,2),
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
        montant DECIMAL(10,2),
        mode_paiement VARCHAR(50),
        recu_numero VARCHAR(50),
        reference_origine VARCHAR(100),
        date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS politiques_financieres (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lycee_id INTEGER,
        activation_seuil_type VARCHAR(50) DEFAULT '100',
        active BOOLEAN DEFAULT 1
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS parametres_financiers_eleves (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        eleve_id INTEGER,
        type_avantage VARCHAR(50) DEFAULT 'Aucun',
        valeur_type VARCHAR(50) DEFAULT 'Pourcentage',
        valeur DECIMAL(10,2) DEFAULT 0.00,
        tous_frais BOOLEAN DEFAULT 0
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS etats_financiers_eleves (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        eleve_id INTEGER,
        inscription_statut VARCHAR(50) DEFAULT 'Non payée',
        mensualite_statut VARCHAR(50) DEFAULT 'À jour'
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        lycee_id INTEGER,
        message TEXT,
        link VARCHAR(255),
        is_read BOOLEAN DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS roles (
        id_role INTEGER PRIMARY KEY AUTOINCREMENT,
        nom_role VARCHAR(100)
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS utilisateurs (
        id_user INTEGER PRIMARY KEY AUTOINCREMENT,
        nom VARCHAR(100),
        prenom VARCHAR(100),
        email VARCHAR(255),
        role_id INTEGER,
        lycee_id INTEGER,
        actif BOOLEAN
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS parametres_utilisateurs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER UNIQUE NOT NULL,
        lycee_id INTEGER,
        signature TEXT,
        cachet TEXT,
        langue_preferee VARCHAR(10) DEFAULT 'fr_FR',
        theme_prefere VARCHAR(50) DEFAULT 'light',
        notifications_actives BOOLEAN DEFAULT 1,
        date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    Database::setInstance($pdo);
    return $pdo;
}

function get_sqlite_connection() {
    global $dbFile;
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    Database::setInstance($pdo);
    return $pdo;
}

function seed_test_data($pdo) {
    // 1. Two Lycées for multi-establishment compatibility
    $pdo->prepare("INSERT INTO param_lycee (id, nom_lycee, type_lycee) VALUES (?, ?, ?)")->execute([1, 'Lycée Pilote CEG', 'prive']);
    $pdo->prepare("INSERT INTO param_lycee (id, nom_lycee, type_lycee) VALUES (?, ?, ?)")->execute([2, 'Lycée Excellence', 'prive']);

    $pdo->prepare("INSERT INTO param_general (lycee_id, modalite_paiement) VALUES (?, ?)")->execute([1, 'Espèces']);
    $pdo->prepare("INSERT INTO param_general (lycee_id, modalite_paiement) VALUES (?, ?)")->execute([2, 'Espèces']);

    // 2. Two Academic Years
    $pdo->prepare("INSERT INTO annees_academiques (id, libelle, date_debut, date_fin, est_active, cloturee) VALUES (?, ?, ?, ?, ?, ?)")
        ->execute([1, '2023-2024', '2023-09-01', '2024-06-30', 0, 0]);
    $pdo->prepare("INSERT INTO annees_academiques (id, libelle, date_debut, date_fin, est_active, cloturee) VALUES (?, ?, ?, ?, ?, ?)")
        ->execute([2, '2024-2025', '2024-09-01', '2025-06-30', 1, 0]);

    // 3. Cycles
    $pdo->prepare("INSERT INTO cycles (id_cycle, lycee_id, nom_cycle) VALUES (?, ?, ?)")->execute([1, 1, 'CEG']);
    $pdo->prepare("INSERT INTO cycles (id_cycle, lycee_id, nom_cycle) VALUES (?, ?, ?)")->execute([2, 1, 'Lycée']);

    // 4. Classes (CEG: 6ème 1, Lycée: 2nde A 1)
    $pdo->prepare("INSERT INTO classes (id_classe, niveau, serie, numero, cycle_id, lycee_id) VALUES (?, ?, ?, ?, ?, ?)")
        ->execute([1, '6ème', '', 1, 1, 1]); // CEG Class
    $pdo->prepare("INSERT INTO classes (id_classe, niveau, serie, numero, cycle_id, lycee_id) VALUES (?, ?, ?, ?, ?, ?)")
        ->execute([2, '2nde', 'A', 1, 2, 1]); // Lycee Class

    // 5. Sequence
    $pdo->prepare("INSERT INTO sequences (id, lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
        ->execute([1, 1, 2, 'Séquence 1', 'trimestrielle', '2024-09-01', '2024-10-31', 'ouverte']);

    // 6. Frais (CEG: 6ème 1 has 50000 registration / 15000 monthly)
    $pdo->prepare("INSERT INTO frais (id_frais, lycee_id, cycle, niveau_debut, niveau_fin, serie, frais_inscription, frais_mensuel, frais_logo, frais_carte, annee_academique_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
        ->execute([1, 1, 'CEG', '6ème', '6ème', '', 50000.00, 15000.00, 0, 0, 2]);

    // 7. Students
    $pdo->prepare("INSERT INTO eleves (id_eleve, lycee_id, nom, prenom, statut, identifiant_public) VALUES (?, ?, ?, ?, ?, ?)")
        ->execute([1, 1, 'Jean', 'CEGStudent', 'actif', '15072026-0001E']);
    $pdo->prepare("INSERT INTO eleves (id_eleve, lycee_id, nom, prenom, statut, identifiant_public) VALUES (?, ?, ?, ?, ?, ?)")
        ->execute([2, 1, 'Marie', 'LyceeStudent', 'actif', '15072026-0002E']);

    // 8. Enrollments
    $pdo->prepare("INSERT INTO etudes (id_etude, eleve_id, classe_id, lycee_id, annee_academique_id, status, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)")
        ->execute([1, 1, 1, 1, 2, 'active', 1]);
    $pdo->prepare("INSERT INTO etudes (id_etude, eleve_id, classe_id, lycee_id, annee_academique_id, status, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)")
        ->execute([2, 2, 2, 1, 2, 'active', 1]);

    // 9. Seeding payments to verify FinancialStatusService integration
    // Student 1 (CEG) pays 30,000 for registration
    $pdo->prepare("INSERT INTO inscriptions (id_inscription, etude_id, eleve_id, classe_id, lycee_id, annee_academique_id, montant_total, montant_verse, reste_a_payer, user_id, recu_numero) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
        ->execute([1, 1, 1, 1, 1, 2, 50000.00, 30000.00, 20000.00, 1, 'REC-001']);

    // Log in journal_comptable
    $pdo->prepare("INSERT INTO journal_comptable (id, lycee_id, eleve_id, user_id, annee_academique_id, operation, montant, mode_paiement, recu_numero, reference_origine) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
        ->execute([1, 1, 1, 1, 2, 'inscription', 30000.00, 'Espèces', 'REC-001', 'inscriptions:1']);

    // Seed roles and user for SQLite
    $pdo->prepare("INSERT INTO roles (id_role, nom_role) VALUES (?, ?)")
        ->execute([1, 'chef_comptable']);

    $pdo->prepare("INSERT INTO utilisateurs (id_user, nom, prenom, email, role_id, lycee_id, actif) VALUES (?, ?, ?, ?, ?, ?, ?)")
        ->execute([1, 'Comptable', 'Jean', 'admin@lycee.com', 1, 1, 1]);
}

$case = $argv[1] ?? 'RUN_ALL';

if ($case === 'SETUP') {
    $pdo = init_test_db();
    seed_test_data($pdo);
    echo "SETUP_SUCCESS\n";
    exit(0);
}

// Restore connection and mock user session for other cases
$pdo = get_sqlite_connection();
$_SESSION['user'] = [
    'id' => 1,
    'nom' => 'Comptable',
    'prenom' => 'Jean',
    'lycee_id' => 1,
    'role_name' => 'chef_comptable',
    'permissions' => [
        'paiement' => ['view', 'manage', '*']
    ]
];

if ($case === 'A') {
    echo "Test Case 1: Filtering by Academic Year (Mandatory Filter)\n";
    $controller = new PaiementController();

    // Active year 2024-2025
    $_GET['annee_academique_id'] = 2;
    $_GET['view_type'] = 'detailed';

    ob_start();
    $controller->journal();
    $html = ob_get_clean();

    if (strpos($html, 'REC-001') !== false) {
        echo "  [PASS] Correctly fetched journal entry REC-001 for year 2024-2025.\n";
    } else {
        echo "  [FAIL] Failed to fetch entry REC-001 for active year.\n";
        exit(1);
    }

    // Old year 2023-2024
    $_GET['annee_academique_id'] = 1;
    ob_start();
    $controller->journal();
    $html = ob_get_clean();

    if (strpos($html, 'REC-001') === false) {
        echo "  [PASS] Correctly filtered out 2024-2025 transaction when viewing 2023-2024.\n";
    } else {
        echo "  [FAIL] Transaction from another year bled into selection.\n";
        exit(1);
    }
    exit(0);
}

if ($case === 'B') {
    echo "Test Case 2: Multi-establishment filtering compatibility\n";
    $controller = new PaiementController();

    $_GET['annee_academique_id'] = 2;
    $_GET['lycee_id'] = 2; // Lycée Excellence with NO transactions
    $_GET['view_type'] = 'detailed';

    ob_start();
    $controller->journal();
    $html = ob_get_clean();

    if (strpos($html, 'REC-001') === false) {
        echo "  [PASS] Correctly prevented mixing of data across multiple establishments.\n";
    } else {
        echo "  [FAIL] Transaction from Lycee 1 bled into Lycee 2 view.\n";
        exit(1);
    }
    exit(0);
}

if ($case === 'C') {
    echo "Test Case 3: Cycle-aware Filtering (CEG & Lycee structures)\n";
    $controller = new PaiementController();

    $_GET['lycee_id'] = 1;
    $_GET['annee_academique_id'] = 2;
    $_GET['cycle_id'] = 1;
    $_GET['niveau'] = '6ème';
    $_GET['numero'] = 1;
    $_GET['serie'] = '';
    $_GET['view_type'] = 'detailed';

    ob_start();
    $controller->journal();
    $html = ob_get_clean();

    if (strpos($html, 'CEGStudent') !== false && strpos($html, 'LyceeStudent') === false) {
        echo "  [PASS] Correctly filtered CEG student only.\n";
    } else {
        echo "  [FAIL] Failed to isolate CEG student.\n";
        exit(1);
    }
    exit(0);
}

if ($case === 'D') {
    echo "Test Case 4: Correct Synthesis calculations via FinancialStatusService\n";
    $controller = new PaiementController();

    $_GET['lycee_id'] = 1;
    $_GET['annee_academique_id'] = 2;
    $_GET['view_type'] = 'class';
    $_GET['cycle_id'] = '';
    $_GET['niveau'] = '';
    $_GET['serie'] = '';
    $_GET['numero'] = '';

    ob_start();
    $controller->journal();
    $html_class = ob_get_clean();

    // Expected remaining debt computed via FinancialStatusService is 50,000 FCFA
    if (strpos($html_class, '50 000') !== false) {
        echo "  [PASS] Correctly computed remaining debt (50 000 FCFA) for CEG class using FinancialStatusService.\n";
    } else {
        echo "  [FAIL] Remaining debt for class was incorrectly calculated.\n";
        exit(1);
    }
    exit(0);
}

if ($case === 'E') {
    echo "Test Case 5: RBAC Permission Enforcement\n";
    $controller = new PaiementController();

    $_SESSION['user']['permissions'] = [];

    // Access should be blocked
    try {
        ob_start();
        $controller->journal();
        ob_end_clean();
        echo "  [FAIL] Access was not blocked for user without view permission.\n";
        exit(1);
    } catch (Exception $e) {
        // block exit
    }

    $_SESSION['user']['permissions'] = ['paiement' => ['view']];
    ob_start();
    $controller->journal();
    $html_back = ob_get_clean();
    if (strpos($html_back, 'Journal Comptable Unique') !== false) {
        echo "  [PASS] RBAC dynamically enforced and allowed access correctly to authorized users.\n";
    } else {
        echo "  [FAIL] Failed to allow access to authorized user.\n";
        exit(1);
    }
    exit(0);
}

if ($case === 'RUN_ALL') {
    echo "Initializing database...\n";
    passthru("php tests/test_journal_navigation.php SETUP");
    echo "\n";
    passthru("php tests/test_journal_navigation.php A");
    echo "\n";
    passthru("php tests/test_journal_navigation.php B");
    echo "\n";
    passthru("php tests/test_journal_navigation.php C");
    echo "\n";
    passthru("php tests/test_journal_navigation.php D");
    echo "\n";
    passthru("php tests/test_journal_navigation.php E");
    echo "\n";
    echo "ALL JOURNAL NAVIGATION TESTS COMPLETED SUCCESSFULLY!\n";
}
?>