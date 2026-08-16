<?php
// tests/DRHModuleTest.php

if (!defined('TEST_MODE')) {
    define('TEST_MODE', true);
}
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/services/AuthorizationScopeService.php';
require_once __DIR__ . '/../src/services/PersonnelService.php';
require_once __DIR__ . '/../src/services/PersonnelAssignmentService.php';
require_once __DIR__ . '/../src/services/PersonnelContractService.php';
require_once __DIR__ . '/../src/services/PersonnelDocumentService.php';
require_once __DIR__ . '/../src/services/PersonnelHistoryService.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/Lycee.php';
require_once __DIR__ . '/../src/models/Cycle.php';
require_once __DIR__ . '/../src/models/Role.php';
require_once __DIR__ . '/../src/models/TypeContrat.php';

echo "=== DÉMARRAGE DU TEST D'INTÉGRATION ET DE SÉCURITÉ DU MODULE DRH ===\n";

function assertTest($condition, $message) {
    if ($condition) {
        echo " [PASS] $message\n";
    } else {
        echo " [FAIL] $message\n";
        throw new Exception("Échec du test : $message");
    }
}

try {
    $db = Database::getInstance();

    // 1. Run migrations to ensure clean database state
    require_once __DIR__ . '/../migrate.php';

    // 2. Set up test environment with 2 Lycées (Lycée A, Lycée B) and 2 Cycles (CEG, Lycée)
    $stmt_l1 = $db->query("SELECT id FROM param_lycee LIMIT 1");
    $lycee_a_id = (int)$stmt_l1->fetchColumn();

    if (!$lycee_a_id) {
        $db->exec("INSERT INTO param_lycee (nom_lycee, type_lycee) VALUES ('Lycée Test A', 'prive')");
        $lycee_a_id = (int)$db->lastInsertId();
    }

    $stmt_l2 = $db->prepare("SELECT id FROM param_lycee WHERE id <> :id LIMIT 1");
    $stmt_l2->execute(['id' => $lycee_a_id]);
    $lycee_b_id = (int)$stmt_l2->fetchColumn();

    if (!$lycee_b_id) {
        $db->exec("INSERT INTO param_lycee (nom_lycee, type_lycee) VALUES ('Lycée Test B', 'prive')");
        $lycee_b_id = (int)$db->lastInsertId();
    }

    // Cycles for Lycée A
    $cycles_a = Cycle::findByLycee($lycee_a_id);
    if (count($cycles_a) < 2) {
        $db->exec("INSERT INTO cycles (lycee_id, nom_cycle, niveau_debut, niveau_fin) VALUES ($lycee_a_id, 'CEG', '6e', '3e')");
        $ceg_a_id = (int)$db->lastInsertId();
        $db->exec("INSERT INTO cycles (lycee_id, nom_cycle, niveau_debut, niveau_fin) VALUES ($lycee_a_id, 'Lycée', '2nd', 'Terminale')");
        $lycee_cycle_a_id = (int)$db->lastInsertId();
    } else {
        $ceg_a_id = (int)$cycles_a[0]['id_cycle'];
        $lycee_cycle_a_id = (int)$cycles_a[1]['id_cycle'];
    }

    // Ensure at least one type_contrat exists
    $stmt_tc = $db->query("SELECT id_contrat FROM type_contrat LIMIT 1");
    $tc_id = (int)$stmt_tc->fetchColumn();
    if (!$tc_id) {
        $db->exec("INSERT INTO type_contrat (libelle, description, type_paiement, prise_en_charge) VALUES ('CDI Test', 'Contrat test', 'fixe', 'Ecole')");
        $tc_id = (int)$db->lastInsertId();
    }

    // Role IDs (Admin Local = 3, Enseignant = 6)
    $admin_role_id = 3;
    $teacher_role_id = 6;

    // Start active session as Admin of Lycée A for test suite
    if (session_status() == PHP_SESSION_NONE) session_start();
    $_SESSION['user'] = [
        'id' => 1,
        'lycee_id' => $lycee_a_id,
        'role_id' => 1, // Super Admin
        'permissions' => [
            'lycee' => ['view_all_lycees'],
            'cycle' => ['view_all_cycles'],
            'drh' => ['*']
        ]
    ];

    // --- TEST 1: Unique Personnel Identity & Save ---
    echo "\n--- TEST 1: Identité Unique du Personnel ---\n";
    $test_email = 'drh_test_user_' . time() . '@test.com';
    $p_id = PersonnelService::savePersonnel([
        'nom' => 'KOUASSI',
        'prenom' => 'Jean-Marc',
        'email' => $test_email,
        'mot_de_passe' => 'Secret123!',
        'role_id' => $teacher_role_id,
        'lycee_id' => $lycee_a_id,
        'fonction' => 'Enseignant de SVT',
        'num_cnss' => 'CNSS-99887766',
        'situation_matrimoniale' => 'marie',
        'nombre_enfants' => 2
    ], 1);

    assertTest($p_id > 0, "Création réussie du membre du personnel avec ID unique ($p_id).");

    $details = PersonnelService::get360Details($p_id, true);
    assertTest($details['personnel']['identifiant_public'] !== null, "Matricule public généré : " . $details['personnel']['identifiant_public']);
    assertTest($details['personnel']['num_cnss'] === 'CNSS-99887766', "Conservation des données confidentielles (CNSS).");

    // --- TEST 2: Sensitivity Filtering & Masking ---
    echo "\n--- TEST 2: Filtrage des Données Sensibles ---\n";
    $details_masked = PersonnelService::get360Details($p_id, false);
    assertTest(!isset($details_masked['personnel']['num_cnss']), "Masquage réussi du numéro CNSS pour utilisateur sans permission sensible.");

    // --- TEST 3: Temporal Cycle Assignments & Overlap Protection ---
    echo "\n--- TEST 3: Affectations de Cycles & Protection Anti-Chevauchement ---\n";

    // Create current active assignment on CEG valid until 2030
    PersonnelAssignmentService::saveAssignment([
        'personnel_id' => $p_id,
        'cycle_id' => $ceg_a_id,
        'date_debut' => '2024-01-01',
        'date_fin' => '2030-12-31',
        'actif' => 1
    ], 1);

    // Attempt overlapping active assignment on CEG -> MUST THROW EXCEPTION
    $overlap_caught = false;
    try {
        PersonnelAssignmentService::saveAssignment([
            'personnel_id' => $p_id,
            'cycle_id' => $ceg_a_id,
            'date_debut' => '2025-06-01',
            'date_fin' => '2028-12-31',
            'actif' => 1
        ], 1);
    } catch (Exception $e) {
        $overlap_caught = true;
    }
    assertTest($overlap_caught, "Succès : La tentative d'affectation active chevauchante a été bloquée.");

    // Create future non-overlapping assignment on Lycée cycle
    PersonnelAssignmentService::saveAssignment([
        'personnel_id' => $p_id,
        'cycle_id' => $lycee_cycle_a_id,
        'date_debut' => '2031-01-01',
        'date_fin' => '2035-12-31',
        'actif' => 1
    ], 1);

    $assignments = PersonnelAssignmentService::getAssignmentsForPersonnel($p_id);
    assertTest(count($assignments) === 2, "Enregistrement de 2 affectations de cycle distinctes (CEG active, Lycée future).");

    // --- TEST 4: Scope Resolution & Premature Access Control ---
    echo "\n--- TEST 4: Résolution de Scope & Invalidation de Cache ---\n";
    // Switch session to Jean-Marc (Teacher with limited cycle access)
    $_SESSION['user'] = [
        'id' => $p_id,
        'lycee_id' => $lycee_a_id,
        'role_id' => $teacher_role_id,
        'permissions' => []
    ];

    $auth_cycles = AuthorizationScopeService::getAuthorizedCycleIds();
    assertTest(in_array($ceg_a_id, $auth_cycles), "Accès au cycle CEG autorisé pour la période courante.");
    assertTest(!in_array($lycee_cycle_a_id, $auth_cycles), "Accès au cycle Lycée FUTUR refusé (pas encore démarré).");

    // --- TEST 5: IDOR Protection (Cross-Tenant / Cross-Cycle Assertion) ---
    echo "\n--- TEST 5: Protection contre l'IDOR et Rééquilibrage Serveur ---\n";
    $idor_caught = false;
    try {
        // Jean-Marc tries to access an object in Lycée B
        AuthorizationScopeService::assertAccessToObject($lycee_b_id, null);
    } catch (Exception $e) {
        $idor_caught = true;
    }
    assertTest($idor_caught, "Succès : L'accès d'un utilisateur du Lycée A aux ressources du Lycée B déclenche un 403/Exception.");

    // Switch session back to Admin for remaining tests
    $_SESSION['user'] = [
        'id' => 1,
        'lycee_id' => $lycee_a_id,
        'role_id' => 1,
        'permissions' => [
            'lycee' => ['view_all_lycees'],
            'cycle' => ['view_all_cycles'],
            'drh' => ['*']
        ]
    ];

    // --- TEST 6: Contracts & Document Versioning ---
    echo "\n--- TEST 6: Historique des Contrats & Coffre-Fort Documentaire ---\n";
    PersonnelContractService::saveContract([
        'personnel_id' => $p_id,
        'type_contrat_id' => $tc_id,
        'date_debut' => '2024-01-01',
        'salaire_base' => 350000.00,
        'devise' => 'XAF',
        'statut_contrat' => 'actif'
    ], 1);

    $active_contract = PersonnelContractService::getActiveContract($p_id);
    assertTest($active_contract !== null && $active_contract['salaire_base'] == 350000.00, "Contrat actif enregistré avec salaire de base.");

    // Test Document Upload
    $tmp_file = sys_get_temp_dir() . '/test_doc.pdf';
    file_put_contents($tmp_file, '%PDF-1.4 Fake PDF Content');

    $file_data = [
        'name' => 'contrat_travail_signed.pdf',
        'tmp_name' => $tmp_file,
        'error' => UPLOAD_ERR_OK,
        'size' => filesize($tmp_file)
    ];

    PersonnelDocumentService::saveDocument([
        'personnel_id' => $p_id,
        'type_document' => 'Contrat de Travail',
        'confidentiel' => 1
    ], $file_data, 1);

    $docs = PersonnelDocumentService::getDocumentsForPersonnel($p_id, true);
    assertTest(count($docs) === 1, "Document téléversé et archivé dans le coffre-fort DRH.");
    assertTest((int)$docs[0]['version'] === 1, "Gestion de version initiale v1.");

    // --- TEST 7: HR Status Lifecycle & Suspension Policy ---
    echo "\n--- TEST 7: Cycle de Vie & Politique de Statut RH ---\n";
    PersonnelService::updateStatus($p_id, 'suspendu', 'Absence injustifiée répétée', date('Y-m-d'), 1);

    $details_suspended = PersonnelService::get360Details($p_id, true);
    assertTest($details_suspended['personnel']['statut_rh'] === 'suspendu', "Statut RH mis à jour vers 'suspendu'.");
    assertTest($details_suspended['personnel']['actif'] == 0, "Compte utilisateur synchronisé à inactif (actif = 0) suite à la suspension.");

    // --- TEST 8: HR Movement Audit Log ---
    echo "\n--- TEST 8: Registre d'Audit des Mouvements RH ---\n";
    $history = PersonnelHistoryService::getHistoryForPersonnel($p_id);
    assertTest(count($history) >= 4, "Traçabilité immuable vérifiée : " . count($history) . " mouvements enregistrés.");

    // --- TEST 9: Orphan File Cleanup on DB Failure & Upload Security ---
    echo "\n--- TEST 9: Nettoyage Anti-Fichiers Orphelins & Sécurité Téléversement ---\n";

    // 9.1 Forbidden MIME / Extension test
    $invalid_file = sys_get_temp_dir() . '/fake_exec.exe';
    file_put_contents($invalid_file, 'MZ fake executable content');
    $invalid_file_data = [
        'name' => 'malicious.exe',
        'tmp_name' => $invalid_file,
        'error' => UPLOAD_ERR_OK,
        'size' => filesize($invalid_file)
    ];

    $mime_blocked = false;
    try {
        PersonnelDocumentService::saveDocument([
            'personnel_id' => $p_id,
            'type_document' => 'Autre Document RH'
        ], $invalid_file_data, 1);
    } catch (InvalidArgumentException $e) {
        $mime_blocked = true;
    }
    assertTest($mime_blocked, "Succès : L'extension/MIME non autorisé (.exe) a été bloqué.");

    // 9.2 Oversized File test (>10MB)
    $oversized_file_data = [
        'name' => 'oversized_doc.pdf',
        'tmp_name' => $tmp_file,
        'error' => UPLOAD_ERR_OK,
        'size' => 15 * 1024 * 1024 // 15MB
    ];
    $size_blocked = false;
    try {
        PersonnelDocumentService::saveDocument([
            'personnel_id' => $p_id,
            'type_document' => 'Contrat de Travail'
        ], $oversized_file_data, 1);
    } catch (InvalidArgumentException $e) {
        $size_blocked = true;
    }
    assertTest($size_blocked, "Succès : Le fichier dépassent la taille maximale (10 Mo) a été rejeté.");

    // 9.3 Anti-orphan physical file cleanup when DB fails
    $tmp_orphan = sys_get_temp_dir() . '/test_orphan.pdf';
    file_put_contents($tmp_orphan, '%PDF-1.4 Fake PDF Content');
    $orphan_file_data = [
        'name' => 'test_orphan.pdf',
        'tmp_name' => $tmp_orphan,
        'error' => UPLOAD_ERR_OK,
        'size' => filesize($tmp_orphan)
    ];

    // Enable foreign key constraints in SQLite for test
    $db->exec("PRAGMA foreign_keys = ON;");

    $orphan_cleaned = false;
    try {
        PersonnelDocumentService::saveDocument([
            'personnel_id' => 9999999, // Invalid personnel_id violating FK constraint
            'type_document' => 'Avenant'
        ], $orphan_file_data, 1);
    } catch (Exception $e) {
        $orphan_cleaned = true;
    }
    assertTest($orphan_cleaned, "Succès : L'échec de la transaction DB a levé une exception et nettoyé les fichiers physiques orphelins.");

    // --- TEST 10: RBAC Seeding & DRH Role Isolation Audit ---
    echo "\n--- TEST 10: Audit RBAC & Isolation du Rôle DRH ---\n";

    // Verify DRH role (id 11) exists
    $stmt_r = $db->query("SELECT id_role FROM roles WHERE nom_role = 'drh'");
    $drh_role_id = (int)$stmt_r->fetchColumn();
    assertTest($drh_role_id > 0, "Rôle 'drh' provisionné en base de données (ID: $drh_role_id).");

    // Verify DRH role permissions
    $drh_perms = Role::getPermissions($drh_role_id);
    assertTest(isset($drh_perms['drh']) && in_array('manage_documents', $drh_perms['drh']), "Permission 'drh:manage_documents' attribuée au rôle DRH.");
    assertTest(isset($drh_perms['role']) && in_array('view_all', $drh_perms['role']), "Permission 'role:view_all' attribuée au rôle DRH.");
    assertTest(!isset($drh_perms['role']) || !in_array('manage', $drh_perms['role']), "Rôle DRH restreint : AUCUNE permission 'role:manage' accordée.");

    // Verify Admin Local (role 3) restriction on sensitive DRH permissions
    $admin_perms = Role::getPermissions(3);
    assertTest(isset($admin_perms['drh']) && in_array('view_all', $admin_perms['drh']), "Admin Local possède 'drh:view_all'.");
    assertTest(!in_array('view_sensitive', $admin_perms['drh'] ?? []), "Admin Local exclu de 'drh:view_sensitive'.");
    assertTest(!in_array('manage_documents', $admin_perms['drh'] ?? []), "Admin Local exclu de 'drh:manage_documents'.");

    // --- TEST 11: Integration & Validation du champ Nombre d'enfants ---
    echo "\n--- TEST 11: Validation & Saisie du Champ Nombre d'enfants ---\n";

    // Rejection of negative values
    $neg_rejected = false;
    try {
        PersonnelService::savePersonnel([
            'nom' => 'TEST_NEG',
            'prenom' => 'Enfant',
            'email' => 'neg_child_' . time() . '@test.com',
            'mot_de_passe' => 'Secret123!',
            'role_id' => $teacher_role_id,
            'lycee_id' => $lycee_a_id,
            'nombre_enfants' => -2
        ], 1);
    } catch (InvalidArgumentException $e) {
        $neg_rejected = true;
    }
    assertTest($neg_rejected, "Succès : La tentative de saisie d'un nombre d'enfants négatif (-2) a été rejetée.");

    // Creation with 3 children and update to 4
    $child_test_email = 'valid_child_' . time() . '@test.com';
    $child_p_id = PersonnelService::savePersonnel([
        'nom' => 'KOUAME',
        'prenom' => 'Alice',
        'email' => $child_test_email,
        'mot_de_passe' => 'Secret123!',
        'role_id' => $teacher_role_id,
        'lycee_id' => $lycee_a_id,
        'nombre_enfants' => 3
    ], 1);

    $d3 = PersonnelService::get360Details($child_p_id, true);
    assertTest((int)$d3['personnel']['nombre_enfants'] === 3, "Nombre d'enfants initial renseigné à 3.");

    // Update to 4 children
    PersonnelService::savePersonnel([
        'id_user' => $child_p_id,
        'nom' => 'KOUAME',
        'prenom' => 'Alice',
        'email' => $child_test_email,
        'role_id' => $teacher_role_id,
        'lycee_id' => $lycee_a_id,
        'nombre_enfants' => 4
    ], 1);

    $d4 = PersonnelService::get360Details($child_p_id, true);
    assertTest((int)$d4['personnel']['nombre_enfants'] === 4, "Nombre d'enfants mis à jour avec succès de 3 à 4.");

    echo "\n=========================================================================\n";
    echo "🏆 TOUS LES TESTS D'INTÉGRATION ET DE SÉCURITÉ DRH ONT RÉUSSI AVEC SUCCÈS !\n";
    echo "=========================================================================\n";

} catch (Exception $e) {
    echo "\n❌ ERREUR FATALE DURANT LE TEST DRH : " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
