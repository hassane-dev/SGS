<?php
// tests/AffectationPedagogiqueTest.php

if (!defined('TEST_MODE')) {
    define('TEST_MODE', true);
}

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../migrate.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/Classe.php';
require_once __DIR__ . '/../src/models/Matiere.php';
require_once __DIR__ . '/../src/models/AnneeAcademique.php';
require_once __DIR__ . '/../src/models/AffectationPedagogique.php';
require_once __DIR__ . '/../src/services/AffectationPedagogiqueService.php';

echo "========================================================\n";
echo "SUITE DE TEST: Affectations Pédagogiques & Invariants\n";
echo "========================================================\n";

$db = Database::getInstance();

// 1. Setup mock data
$db->exec("DELETE FROM affectations_pedagogiques WHERE classe_id >= 9900");
$db->exec("DELETE FROM classe_matieres WHERE classe_id >= 9900");
$db->exec("DELETE FROM classes WHERE id_classe >= 9900");
$db->exec("DELETE FROM matieres WHERE id_matiere >= 9900");
$db->exec("DELETE FROM personnel_cycles_assignments WHERE personnel_id >= 9900");
$db->exec("DELETE FROM utilisateurs WHERE id_user >= 9900");
$db->exec("DELETE FROM cycles WHERE id_cycle = 998");

// Create test active academic year
$stmt_aa = $db->query("SELECT id FROM annees_academiques WHERE est_active = 1 LIMIT 1");
$active_annee_id = $stmt_aa->fetchColumn();
if (!$active_annee_id) {
    $db->exec("INSERT INTO annees_academiques (libelle, date_debut, date_fin, est_active) VALUES ('2024-2025', '2024-09-01', '2025-06-30', 1)");
    $active_annee_id = $db->lastInsertId();
}

// Create Test Lycee/Cycle
$db->exec("INSERT INTO cycles (id_cycle, nom_cycle) VALUES (998, 'CEG Test') ON CONFLICT DO NOTHING");

// Create Test Class
$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero) VALUES (9910, 1, 998, '6ème', 'A', 1)");

// Create Test Subjects
$db->exec("INSERT INTO matieres (id_matiere, lycee_id, nom_matiere) VALUES (9901, 1, 'Mathématiques Test')");
$db->exec("INSERT INTO matieres (id_matiere, lycee_id, nom_matiere) VALUES (9902, 1, 'Physique Test')");

// Assign MATH99 to Class 9910 in classe_matieres
$db->exec("INSERT INTO classe_matieres (classe_id, matiere_id, coefficient, statut) VALUES (9910, 9901, 3.0, 'obligatoire')");

// Create Teachers
// Prof A (Has cycle assignment 998)
$db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, email, role_id, fonction, actif) VALUES (9901, 1, 'Prof', 'Alpha', 'prof.alpha@test.com', 6, 'Enseignant', 1)");
$db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, actif) VALUES (9901, 998, '2024-09-01', 1)");

// Prof B (Has cycle assignment 998)
$db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, email, role_id, fonction, actif) VALUES (9902, 1, 'Prof', 'Beta', 'prof.beta@test.com', 6, 'Enseignant', 1)");
$db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, actif) VALUES (9902, 998, '2024-09-01', 1)");

// Prof C (No cycle assignment for 991)
$db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, email, role_id, fonction, actif) VALUES (9903, 1, 'Prof', 'Gamma', 'prof.gamma@test.com', 6, 'Enseignant', 1)");

// Mock Session User (Super Admin with full permissions)
$_SESSION['user'] = [
    'id' => 1,
    'id_user' => 1,
    'lycee_id' => 1,
    'role_id' => 1,
    'role_name' => 'super_admin_createur',
    'auth_version' => 1,
    'permissions' => [
        'lycee' => ['view_all_lycees'],
        'cycle' => ['view_all_cycles'],
        'pedagogy' => ['manage_affectations', 'view_affectations', 'view_my_affectations']
    ]
];
$_SESSION['user_id'] = 1;
$_SESSION['lycee_id'] = 1;
$_SESSION['role_id'] = 1;
$_SESSION['role_name'] = 'super_admin_createur';

echo "[OK] Initial Test Setup Completed.\n\n";

// --- TEST 1: Creation of valid assignment ---
echo "TEST 1: Affectation valide (Prof Alpha -> Classe 9910 -> Math99)\n";
try {
    $aff_id = AffectationPedagogiqueService::createAssignment([
        'enseignant_id' => 9901,
        'classe_id' => 9910,
        'matiere_id' => 9901,
        'volume_horaire_hebdo' => 4.0,
        'date_debut' => '2024-09-01',
        'statut' => 'actif',
        'motif_changement' => 'Affectation initiale'
    ], 1);

    echo "  -> Assignation créée avec succès (ID #$aff_id) | PASS\n";
} catch (Exception $e) {
    echo "  -> ECHEC: " . $e->getMessage() . "\n";
    exit(1);
}

// --- TEST 2: Rejection of teacher without authorized cycle assignment ---
echo "\nTEST 2: Refus d'un enseignant sans cycle autorisé (Prof Gamma -> Classe 9910)\n";
try {
    AffectationPedagogiqueService::createAssignment([
        'enseignant_id' => 9903,
        'classe_id' => 9910,
        'matiere_id' => 9901,
        'date_debut' => '2024-09-01',
        'statut' => 'actif'
    ], 1);
    echo "  -> ECHEC (L'affectation aurait dû être refusée)\n";
    exit(1);
} catch (InvalidArgumentException $e) {
    echo "  -> Attendu (Refusé) : " . $e->getMessage() . " | PASS\n";
}

// --- TEST 3: Rejection of subject not in class curriculum ---
echo "\nTEST 3: Refus d'une matière absente de classe_matieres (Physique 9902 -> Classe 9910)\n";
try {
    AffectationPedagogiqueService::createAssignment([
        'enseignant_id' => 9901,
        'classe_id' => 9910,
        'matiere_id' => 9902, // PHYS99 is not in classe_matieres
        'date_debut' => '2024-09-01',
        'statut' => 'actif'
    ], 1);
    echo "  -> ECHEC (L'affectation aurait dû être refusée)\n";
    exit(1);
} catch (InvalidArgumentException $e) {
    echo "  -> Attendu (Refusé) : " . $e->getMessage() . " | PASS\n";
}

// --- TEST 4: Invariant 7 Overlap Rejection ---
echo "\nTEST 4: Refus de chevauchement actif (Prof Beta sur le créneau déjà occupé par Prof Alpha)\n";
try {
    AffectationPedagogiqueService::createAssignment([
        'enseignant_id' => 9902,
        'classe_id' => 9910,
        'matiere_id' => 9901,
        'date_debut' => '2024-10-01',
        'statut' => 'actif'
    ], 1);
    echo "  -> ECHEC (Le chevauchement aurait dû être bloqué)\n";
    exit(1);
} catch (InvalidArgumentException $e) {
    echo "  -> Attendu (Chevauchement bloqué) : " . $e->getMessage() . " | PASS\n";
}

// --- TEST 5: Status Transition & Succession ---
echo "\nTEST 5: Clôture de l'affectation de Prof Alpha puis remplacement par Prof Beta\n";
try {
    // Terminate Prof Alpha's assignment on 2024-11-15
    AffectationPedagogiqueService::updateStatus($aff_id, 'termine', 'Fin de contrat', '2024-11-15', 1);

    // Assign Prof Beta starting from 2024-11-16
    $new_aff_id = AffectationPedagogiqueService::createAssignment([
        'enseignant_id' => 9902,
        'classe_id' => 9910,
        'matiere_id' => 9901,
        'volume_horaire_hebdo' => 4.0,
        'date_debut' => '2024-11-16',
        'statut' => 'actif',
        'motif_changement' => 'Remplacement Prof Alpha'
    ], 1);

    echo "  -> Remplacement réussi sans conflit (Nouveau ID #$new_aff_id) | PASS\n";
} catch (Exception $e) {
    echo "  -> ECHEC: " . $e->getMessage() . "\n";
    exit(1);
}

// --- TEST 6: User::getTeacherAssignments Scoping ---
echo "\nTEST 6: Vérification du filtrage de getTeacherAssignments()\n";
$alpha_assignments = User::getTeacherAssignments(9901);
$beta_assignments = User::getTeacherAssignments(9902);

if (count($alpha_assignments) === 0 && count($beta_assignments) === 1 && $beta_assignments[0]['id_classe'] == 9910) {
    echo "  -> Attendu: Prof Alpha a 0 affectation active, Prof Beta en a 1 active | PASS\n";
} else {
    echo "  -> ECHEC du filtrage des affectations actives\n";
    exit(1);
}


// --- TEST 7: Filtering Available Subjects for Class ---
echo "
TEST 7: Filtrage des matières disponibles (Math99 affectée à Prof Beta -> exclue)
";
$avail_matieres = AffectationPedagogique::findAvailableSubjectsForClass(9910);
$avail_ids = array_column($avail_matieres, 'id_matiere');
if (!in_array(9901, $avail_ids)) {
    echo "  -> Attendu: Math99 (9901) est exclue car déjà affectée à Prof Beta | PASS
";
} else {
    echo "  -> ECHEC: Math99 est restée dans la liste des matières disponibles
";
    exit(1);
}

// --- TEST 8: Suspension and Reactivation Workflow ---
echo "
TEST 8: Suspension puis réactivation de l'affectation de Prof Beta
";
try {
    // Suspend
    AffectationPedagogiqueService::updateStatus($new_aff_id, 'suspendu', 'Absence maladie', null, 1);

    // Attempt reactivation
    AffectationPedagogiqueService::reactivateAssignment($new_aff_id, 1);

    $reloaded = AffectationPedagogique::findById($new_aff_id);
    if ($reloaded['statut'] === 'actif') {
        echo "  -> Réactivation réussie avec statut 'actif' | PASS
";
    } else {
        echo "  -> ECHEC de la réactivation
";
        exit(1);
    }
} catch (Exception $e) {
    echo "  -> ECHEC: " . $e->getMessage() . "
";
    exit(1);
}

// --- TEST 9: Replacement Workflow without History Loss ---
echo "
TEST 9: Remplacement de Prof Beta par Prof Alpha sans perte d'historique
";
try {
    // Replace Beta (9902) with Alpha (9901) starting 2024-12-01
    $replaced_id = AffectationPedagogiqueService::replaceAssignment($new_aff_id, [
        'enseignant_id' => 9901,
        'volume_horaire_hebdo' => 4.0,
        'date_debut' => '2024-12-01',
        'statut' => 'actif',
        'motif_changement' => 'Remplacement définitif'
    ], 1);

    $old_aff = AffectationPedagogique::findById($new_aff_id);
    $new_aff = AffectationPedagogique::findById($replaced_id);

    if ($old_aff['statut'] === 'termine' && $new_aff['statut'] === 'actif' && $new_aff['enseignant_id'] == 9901) {
        echo "  -> Remplacement réussi : Ancienne affectation terminée le {$old_aff['date_fin']}, nouvelle créée (#$replaced_id) | PASS
";
    } else {
        echo "  -> ECHEC du remplacement historisé
";
        exit(1);
    }
} catch (Exception $e) {
    echo "  -> ECHEC: " . $e->getMessage() . "
";
    exit(1);
}

// --- TEST 10: Edit/Update Assignment with Historization vs In-place ---
echo "
TEST 10: Édition / Modification avec historization vs modification en place
";
try {
    // Non-structural update (volume_horaire) on $replaced_id -> in-place update
    $updated_same_id = AffectationPedagogiqueService::updateAssignment($replaced_id, [
        'volume_horaire_hebdo' => 5.5,
        'date_debut' => '2024-12-01',
        'statut' => 'actif',
        'motif_changement' => 'Ajustement horaire'
    ], 1);

    if ($updated_same_id === $replaced_id) {
        $check = AffectationPedagogique::findById($replaced_id);
        if ((float)$check['volume_horaire_hebdo'] === 5.5) {
            echo "  -> Modification non-structurante réussie en place (ID #$replaced_id, 5.5h) | PASS
";
        } else {
            echo "  -> ECHEC de la mise à jour du volume horaire
";
            exit(1);
        }
    } else {
        echo "  -> ECHEC: L'ID aurait dû rester identique
";
        exit(1);
    }

    // Structural update (Teacher change from Alpha 9901 to Beta 9902) -> historization
    $new_edited_id = AffectationPedagogiqueService::updateAssignment($replaced_id, [
        'enseignant_id' => 9902,
        'volume_horaire_hebdo' => 5.5,
        'date_debut' => '2024-12-15',
        'statut' => 'actif',
        'motif_changement' => 'Correction titulaire via édition'
    ], 1);

    $old_edited = AffectationPedagogique::findById($replaced_id);
    $new_edited = AffectationPedagogique::findById($new_edited_id);

    if ($new_edited_id !== $replaced_id && $old_edited['statut'] === 'termine' && $new_edited['statut'] === 'actif' && $new_edited['enseignant_id'] == 9902) {
        echo "  -> Modification structurante historisée avec succès (Ancienne #$replaced_id terminée, nouvelle #$new_edited_id créée) | PASS
";
    } else {
        echo "  -> ECHEC de l'historisation lors d'une modification structurante via edit
";
        exit(1);
    }

} catch (Exception $e) {
    echo "  -> ECHEC: " . $e->getMessage() . "
";
    exit(1);
}

// Cleanup mock test data
$db->exec("DELETE FROM affectations_pedagogiques WHERE classe_id >= 9900");
$db->exec("DELETE FROM classe_matieres WHERE classe_id >= 9900");
$db->exec("DELETE FROM classes WHERE id_classe >= 9900");
$db->exec("DELETE FROM matieres WHERE id_matiere >= 9900");
$db->exec("DELETE FROM personnel_cycles_assignments WHERE personnel_id >= 9900");
$db->exec("DELETE FROM utilisateurs WHERE id_user >= 9900");
$db->exec("DELETE FROM cycles WHERE id_cycle = 998");

echo "\n========================================================\n";
echo "RÉSULTAT FINAL: TOUS LES TESTS D'AFFECTATION ONT RÉUSSI !\n";
echo "========================================================\n";
