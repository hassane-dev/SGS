<?php

// Comprehensive Financial workflow test suite under SQLite
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

// Prevent PHP session header warnings by silencing them
@session_start();

register_shutdown_function(function() {
    if (isset($_SESSION['error_message'])) {
        echo "\n[SHUTDOWN_ALERT] ERROR_MESSAGE: " . $_SESSION['error_message'] . "\n";
    }
    if (isset($_SESSION['success_message'])) {
        echo "\n[SHUTDOWN_ALERT] SUCCESS_MESSAGE: " . $_SESSION['success_message'] . "\n";
    }
});

// Define default server variables for CLI test execution
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/';

$dbFile = '/tmp/test_db.sqlite';

function get_sqlite_connection() {
    global $dbFile;
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    Database::setInstance($pdo);
    return $pdo;
}

function setup_sqlite_db() {
    global $dbFile;
    if (file_exists($dbFile)) {
        unlink($dbFile);
    }

    $pdo = get_sqlite_connection();

    // Create required tables in memory
    $pdo->exec("CREATE TABLE IF NOT EXISTS param_lycee (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nom_lycee VARCHAR(255),
        type_lycee VARCHAR(50),
        sigle VARCHAR(50),
        tel VARCHAR(50),
        email VARCHAR(255),
        ville VARCHAR(100),
        quartier VARCHAR(100),
        ruelle VARCHAR(100),
        boite_postale VARCHAR(50),
        arrete VARCHAR(255),
        arrondissement VARCHAR(100),
        devise VARCHAR(255),
        logo TEXT,
        boutique BOOLEAN,
        header_primary TEXT,
        header_secondary TEXT,
        signature_directeur TEXT,
        tampon_ecole TEXT
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS param_general (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        lycee_id INTEGER,
        devise_pays VARCHAR(100),
        monnaie VARCHAR(10),
        modalite_paiement VARCHAR(255),
        nb_langue INTEGER,
        langue_1 VARCHAR(50),
        langue_2 VARCHAR(50),
        sequence_annuelle VARCHAR(50),
        mode_cycle VARCHAR(50),
        multilingue_actif BOOLEAN,
        biometrie_actif BOOLEAN,
        confidentialite_nationale BOOLEAN
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
        nom_cycle VARCHAR(100),
        niveau_debut VARCHAR(50),
        niveau_fin VARCHAR(50)
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
        autres_frais TEXT,
        annee_academique_id INTEGER
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS eleves (
        id_eleve INTEGER PRIMARY KEY AUTOINCREMENT,
        lycee_id INTEGER,
        nom VARCHAR(100),
        prenom VARCHAR(100),
        date_naissance DATE,
        lieu_naissance VARCHAR(255),
        sexe VARCHAR(50),
        nationalite VARCHAR(100),
        quartier VARCHAR(255),
        tel_parent VARCHAR(50),
        nom_pere VARCHAR(255),
        nom_mere VARCHAR(255),
        profession_pere VARCHAR(255),
        profession_mere VARCHAR(255),
        photo TEXT,
        email VARCHAR(255),
        telephone VARCHAR(50),
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
        is_active BOOLEAN,
        date_activation DATETIME,
        active_par INTEGER,
        motif_inactif TEXT
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
        statut VARCHAR(50) DEFAULT 'valide',
        date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP
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
        date_paiement DATETIME DEFAULT CURRENT_TIMESTAMP,
        user_id INTEGER
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS mensualite_details (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        mensualite_id INTEGER,
        montant DECIMAL(10,2),
        mode_paiement VARCHAR(50),
        reference_transaction VARCHAR(100),
        date_paiement DATETIME DEFAULT CURRENT_TIMESTAMP,
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
        activation_seuil_valeur DECIMAL(10,2) DEFAULT 0.00,
        notes_seuil_mensualites INTEGER DEFAULT 0,
        bulletin_seuil_complet BOOLEAN DEFAULT 1,
        active BOOLEAN DEFAULT 1
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS parametres_financiers_eleves (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        eleve_id INTEGER,
        type_avantage VARCHAR(50) DEFAULT 'Aucun',
        valeur_type VARCHAR(50) DEFAULT 'Pourcentage',
        valeur DECIMAL(10,2) DEFAULT 0.00,
        date_debut DATE,
        date_fin DATE,
        motif TEXT,
        organisme_financeur VARCHAR(255),
        frais_concernes TEXT,
        tous_frais BOOLEAN DEFAULT 0
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS etats_financiers_eleves (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        eleve_id INTEGER,
        inscription_statut VARCHAR(50) DEFAULT 'Non payée',
        mensualite_statut VARCHAR(50) DEFAULT 'À jour',
        notes_consultation VARCHAR(50) DEFAULT 'Interdite',
        bulletin_impression VARCHAR(50) DEFAULT 'Interdite'
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
        nom_role VARCHAR(100),
        lycee_id INTEGER
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS utilisateurs (
        id_user INTEGER PRIMARY KEY AUTOINCREMENT,
        nom VARCHAR(100),
        prenom VARCHAR(100),
        email VARCHAR(255),
        role_id INTEGER,
        lycee_id INTEGER,
        actif BOOLEAN,
        identifiant_public VARCHAR(50)
    )");

    Database::setInstance($pdo);
    return $pdo;
}

$case = $argv[1] ?? 'RUN_ALL';

if ($case === 'SETUP') {
    setup_sqlite_db();

    // Seed basic data
    $pdo = get_sqlite_connection();
    $lyceeId = 1;
    $pdo->prepare("INSERT INTO param_lycee (id, nom_lycee, type_lycee) VALUES (?, ?, ?)")
        ->execute([$lyceeId, 'Lycée Pilote de Cotonou', 'prive']);

    $pdo->prepare("INSERT INTO param_general (lycee_id, modalite_paiement) VALUES (?, ?)")
        ->execute([$lyceeId, 'Espèces, Chèque']);

    $yearId = 1;
    $pdo->prepare("INSERT INTO annees_academiques (id, libelle, date_debut, date_fin, est_active, cloturee) VALUES (?, ?, ?, ?, ?, ?)")
        ->execute([$yearId, '2024-2025', '2024-09-01', '2025-06-30', 1, 0]);

    $cycleId = 1;
    $pdo->prepare("INSERT INTO cycles (id_cycle, nom_cycle, niveau_debut, niveau_fin) VALUES (?, ?, ?, ?)")
        ->execute([$cycleId, 'CEG', '6ème', '3ème']);

    $classeId = 1;
    $pdo->prepare("INSERT INTO classes (id_classe, niveau, serie, numero, cycle_id, lycee_id) VALUES (?, ?, ?, ?, ?, ?)")
        ->execute([$classeId, '6ème', '', 1, $cycleId, $lyceeId]);

    $pdo->prepare("INSERT INTO sequences (id, lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
        ->execute([1, $lyceeId, $yearId, 'Séquence 1', 'trimestrielle', '2024-09-01', '2024-10-31', 'ouverte']);

    $pdo->prepare("INSERT INTO frais (id_frais, lycee_id, cycle, niveau_debut, niveau_fin, serie, frais_inscription, frais_mensuel, frais_logo, frais_carte, annee_academique_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
        ->execute([1, $lyceeId, 'CEG', '6ème', '6ème', '', 50000.00, 15000.00, 2000.00, 3000.00, $yearId]);

    $eleveId = 1;
    $pdo->prepare("INSERT INTO eleves (id_eleve, lycee_id, nom, prenom, statut, identifiant_public) VALUES (?, ?, ?, ?, ?, ?)")
        ->execute([$eleveId, $lyceeId, 'Dupont', 'Jean', 'en_attente_paiement', '15072026-0001E']);

    $pdo->prepare("INSERT INTO etudes (id_etude, eleve_id, classe_id, lycee_id, annee_academique_id, status, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)")
        ->execute([1, $eleveId, $classeId, $lyceeId, $yearId, 'en_attente_paiement', 0]);

    // Seed roles and user for notification testing
    $pdo->prepare("INSERT INTO roles (id_role, nom_role, lycee_id) VALUES (?, ?, ?)")
        ->execute([1, 'admin_local', $lyceeId]);

    $pdo->prepare("INSERT INTO utilisateurs (id_user, nom, prenom, email, role_id, lycee_id, actif, identifiant_public) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
        ->execute([1, 'Directeur', 'Admin', 'admin@lycee.com', 1, $lyceeId, 1, '0001ADM']);

    echo "SETUP_SUCCESS\n";
    exit(0);
}

// Ensure session exists and DB is set
$pdo = get_sqlite_connection();
$_SESSION['user'] = [
    'id' => 1,
    'nom' => 'Comptable',
    'prenom' => 'Jean',
    'lycee_id' => 1,
    'role_name' => 'super_admin_createur',
    'permissions' => [
        'paiement' => ['view', 'manage', '*']
    ]
];

if ($case === 'A') {
    echo "Running Cas A: Consultation of /paiements/show for new student...\n";
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $controller = new PaiementController();
    ob_start();
    $controller->show(1);
    ob_end_clean();
    echo "CAS_A_SUCCESS\n";
    exit(0);
}

if ($case === 'B') {
    echo "Running Cas B: Consultation of /paiements/regulariser-inscription for new student...\n";
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $controller = new PaiementController();
    ob_start();
    $controller->regulariserInscription(1);
    ob_end_clean();
    echo "CAS_B_SUCCESS\n";
    exit(0);
}

if ($case === 'C') {
    echo "Running Cas C: First payment of 20,000 FCFA...\n";
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST['montant_inscription'] = 20000.00;
    $_POST['montant_mensualites'] = 0;
    $_POST['options'] = [];
    $_POST['mode_paiement'] = 'Espèces';
    $_POST['reference_transaction'] = 'REC-000001';

    $controller = new PaiementController();
    ob_start();
    $controller->processPayment(1);
    ob_end_clean();

    if (isset($_SESSION['error_message'])) {
        echo "ERROR_IN_C: " . $_SESSION['error_message'] . "\n";
    } else {
        echo "CAS_C_SUCCESS\n";
    }
    exit(0);
}

if ($case === 'D') {
    echo "Running Cas D: Second payment of 30,000 FCFA...\n";
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST['montant_inscription'] = 30000.00;
    $_POST['montant_mensualites'] = 0;
    $_POST['options'] = [];
    $_POST['mode_paiement'] = 'Espèces';
    $_POST['reference_transaction'] = 'REC-000002';

    $controller = new PaiementController();
    ob_start();
    $controller->processPayment(1);
    ob_end_clean();

    if (isset($_SESSION['error_message'])) {
        echo "ERROR_IN_D: " . $_SESSION['error_message'] . "\n";
    } else {
        echo "CAS_D_SUCCESS\n";
    }
    exit(0);
}

if ($case === 'E') {
    echo "Running Cas E: Verify automatic activation...\n";

    $stmt_eleve = $pdo->prepare("SELECT statut FROM eleves WHERE id_eleve = 1");
    $stmt_eleve->execute();
    $eleve_statut = $stmt_eleve->fetchColumn();

    $stmt_etude = $pdo->prepare("SELECT * FROM etudes WHERE eleve_id = 1");
    $stmt_etude->execute();
    $etude = $stmt_etude->fetch(PDO::FETCH_ASSOC);

    if ($eleve_statut !== 'actif') {
        throw new Exception("Expected student statut to be 'actif', got " . $eleve_statut);
    }
    if ($etude['status'] !== 'active') {
        throw new Exception("Expected etudes status to be 'active', got " . $etude['status']);
    }
    if ($etude['is_active'] != 1) {
        throw new Exception("Expected etudes is_active to be 1, got " . $etude['is_active']);
    }
    if (empty($etude['date_activation'])) {
        throw new Exception("Expected etudes date_activation to be populated!");
    }
    if ($etude['active_par'] != 1) {
        throw new Exception("Expected etudes active_par to be 1, got " . $etude['active_par']);
    }

    echo "CAS_E_SUCCESS\n";
    exit(0);
}

if ($case === 'F') {
    echo "Running Cas F: Student with reduction...\n";
    // Create second student
    $eleveId2 = 2;
    $pdo->prepare("INSERT INTO eleves (id_eleve, lycee_id, nom, prenom, statut, identifiant_public) VALUES (?, ?, ?, ?, ?, ?)")
        ->execute([$eleveId2, 1, 'Boni', 'Marie', 'en_attente_paiement', '15072026-0002E']);

    $pdo->prepare("INSERT INTO etudes (id_etude, eleve_id, classe_id, lycee_id, annee_academique_id, status, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)")
        ->execute([2, $eleveId2, 1, 1, 1, 'en_attente_paiement', 0]);

    // Add 50% Reduction on 'frais_inscription'
    $pdo->prepare("INSERT INTO parametres_financiers_eleves (eleve_id, type_avantage, valeur_type, valeur, tous_frais) VALUES (?, ?, ?, ?, ?)")
        ->execute([$eleveId2, 'Réduction', 'Pourcentage', 50.00, 1]);

    $status_adv = FinancialStatusService::getStudentFinancialStatus($eleveId2, 1);

    // Expected Inscription fee after 50% reduction = 25,000 FCFA
    if ($status_adv['reste_inscription'] != 25000.00) {
        throw new Exception("Expected adjusted inscription fee of 25000.00, got " . $status_adv['reste_inscription']);
    }

    echo "CAS_F_SUCCESS\n";
    exit(0);
}

if ($case === 'G') {
    echo "Running Cas G: Verify Payment History grouping for unique student rows...\n";
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET['date_debut'] = '2024-09-01';
    $_GET['date_fin'] = date('Y-m-d');
    $_GET['search'] = '';

    $controller = new PaiementController();
    ob_start();
    $controller->historique();
    $html = ob_get_clean();

    // The student should be listed exactly once in the table.
    // Jean Dupont has made multiple payments (REC-000001, REC-000002)
    // We expect their total versé to be aggregated: 20000 + 30000 = 50000.
    if (strpos($html, 'Jean') === false) {
        throw new Exception("Student Jean not found in grouped history.");
    }

    // Extract the table body to avoid counting matches in headers, footers, alerts, or notifications
    if (preg_match('#<tbody>(.*?)</tbody>#s', $html, $matches)) {
        $tbody = $matches[1];
    } else {
        $tbody = $html;
    }

    $occurrences = substr_count($tbody, 'Dupont');
    if ($occurrences > 1) {
        echo "\n[DEBUG] Dupont found $occurrences times inside tbody. Printing matching lines of tbody:\n";
        $lines = explode("\n", $tbody);
        foreach ($lines as $idx => $line) {
            if (strpos($line, 'Dupont') !== false) {
                echo "Line " . ($idx + 1) . ": " . trim($line) . "\n";
            }
        }
        throw new Exception("Duplicate student row found in payment history! Occurrences: " . $occurrences);
    }

    echo "CAS_G_SUCCESS\n";
    exit(0);
}

if ($case === 'RUN_ALL') {
    // Run them in sequence by executing shell sub-processes
    echo "Initializing database...\n";
    passthru("php tests/test_financial_status.php SETUP");
    echo "\n";
    passthru("php tests/test_financial_status.php A");
    echo "\n";
    passthru("php tests/test_financial_status.php B");
    echo "\n";
    passthru("php tests/test_financial_status.php C");
    echo "\n";
    passthru("php tests/test_financial_status.php D");
    echo "\n";
    passthru("php tests/test_financial_status.php E");
    echo "\n";
    passthru("php tests/test_financial_status.php F");
    echo "\n";
    passthru("php tests/test_financial_status.php G");
    echo "\n";
}
?>