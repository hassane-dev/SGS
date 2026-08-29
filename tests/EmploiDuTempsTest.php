<?php

/**
 * Integration Test Suite for Emploi du Temps Module (Point 5).
 */

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/models/EmploiDuTemps.php';
require_once __DIR__ . '/../src/models/Classe.php';
require_once __DIR__ . '/../src/models/Matiere.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/Salle.php';
require_once __DIR__ . '/../src/models/AnneeAcademique.php';
require_once __DIR__ . '/../src/controllers/EmploiDuTempsController.php';

function assertCondition($condition, $message) {
    if (!$condition) {
        echo "❌ [FAIL] $message\n";
        exit(1);
    }
    echo "✅ [PASS] $message\n";
}

echo "========================================================\n";
echo "DÉBUT DE LA SUITE DE TESTS : EMPLOI DU TEMPS (POINT 5)\n";
echo "========================================================\n";

$db = Database::getInstance();
$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

// Setup Test Environment
$db->exec("DELETE FROM emploi_du_temps WHERE lycee_id IN (901, 902)");
$db->exec("DELETE FROM salles WHERE lycee_id IN (901, 902)");
$db->exec("DELETE FROM classes WHERE lycee_id IN (901, 902)");
$db->exec("DELETE FROM utilisateurs WHERE lycee_id IN (901, 902)");
$db->exec("DELETE FROM param_lycee WHERE id IN (901, 902)");

$db->exec("INSERT INTO param_lycee (id, nom_lycee) VALUES (901, 'Lycée Test EDT 1'), (902, 'Lycée Test EDT 2')");

$activeYear = AnneeAcademique::findActive();
if (!$activeYear) {
    $db->exec("INSERT INTO annees_academiques (nom, date_debut, date_fin, est_active) VALUES ('2024-2025', '2024-09-01', '2025-06-30', 1)");
    $activeYear = AnneeAcademique::findActive();
}
$anneeId1 = (int)$activeYear['id'];

// Ensure a test cycle exists
$stmtC = $db->query("SELECT id_cycle FROM cycles LIMIT 1");
$cRow = $stmtC->fetch();
if ($cRow) {
    $cycleIdTest = (int)$cRow['id_cycle'];
} else {
    $db->exec("INSERT INTO cycles (nom_cycle, lycee_id) VALUES ('Cycle Test', 901)");
    $cycleIdTest = (int)$db->lastInsertId();
}

// Classes
$db->exec("INSERT INTO classes (niveau, serie, numero, cycle_id, lycee_id) VALUES ('6eme', 'A', 1, $cycleIdTest, 901)");
$classeId1 = (int)$db->lastInsertId();

$db->exec("INSERT INTO classes (niveau, serie, numero, cycle_id, lycee_id) VALUES ('5eme', 'B', 1, $cycleIdTest, 901)");
$classeId2 = (int)$db->lastInsertId();

$db->exec("INSERT INTO classes (niveau, serie, numero, cycle_id, lycee_id) VALUES ('4eme', 'A', 1, $cycleIdTest, 902)");
$classeIdTenant2 = (int)$db->lastInsertId();

// Teachers
$db->exec("INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role_id, lycee_id, actif) VALUES ('Prof1', 'Jean', 'prof1@edt.test', 'secret', 6, 901, 1)");
$profId1 = (int)$db->lastInsertId();

$db->exec("INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role_id, lycee_id, actif) VALUES ('Prof2', 'Marie', 'prof2@edt.test', 'secret', 6, 901, 1)");
$profId2 = (int)$db->lastInsertId();

$db->exec("INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role_id, lycee_id, actif) VALUES ('ProfTenant2', 'Pierre', 'prof_t2@edt.test', 'secret', 6, 902, 1)");
$profIdTenant2 = (int)$db->lastInsertId();

// Matiere
$stmtM = $db->query("SELECT id_matiere FROM matieres LIMIT 1");
$mRow = $stmtM->fetch();
if ($mRow) {
    $matiereId1 = (int)$mRow['id_matiere'];
} else {
    $db->exec("INSERT INTO matieres (nom_matiere, code_matiere) VALUES ('Mathématiques', 'MATH')");
    $matiereId1 = (int)$db->lastInsertId();
}

// Salles
$db->exec("INSERT INTO salles (nom_salle, capacite, lycee_id) VALUES ('Salle 101', 30, 901)");
$salleId1 = (int)$db->lastInsertId();

$db->exec("INSERT INTO salles (nom_salle, capacite, lycee_id) VALUES ('Salle 102', 30, 901)");
$salleId2 = (int)$db->lastInsertId();

// Set Auth Context
$_SESSION = [
    'user' => [
        'id_user' => 1,
        'lycee_id' => 901,
        'role' => 'Admin',
    ],
    'permissions' => [
        'timetable' => ['manage' => true],
        'lycee' => ['view_all_lycees' => false]
    ]
];

try {
    // TEST 1: Arbitrary non-standard time slot saving & exact interval grid rendering.
    echo "\nTEST 1: Horaires arbitraires et durées non standards (07:15-08:05, 08:15-09:37, 13:17-14:42)...\n";
    $p1 = [
        'classe_id' => $classeId1,
        'matiere_id' => $matiereId1,
        'professeur_id' => $profId1,
        'salle_id' => $salleId1,
        'annee_academique_id' => $anneeId1,
        'jour' => 'Lundi',
        'heure_debut' => '07:15',
        'heure_fin' => '08:05',
    ];
    $p2 = [
        'classe_id' => $classeId1,
        'matiere_id' => $matiereId1,
        'professeur_id' => $profId1,
        'salle_id' => $salleId1,
        'annee_academique_id' => $anneeId1,
        'jour' => 'Lundi',
        'heure_debut' => '08:15',
        'heure_fin' => '09:37',
    ];
    $p3 = [
        'classe_id' => $classeId1,
        'matiere_id' => $matiereId1,
        'professeur_id' => $profId1,
        'salle_id' => $salleId1,
        'annee_academique_id' => $anneeId1,
        'jour' => 'Mardi',
        'heure_debut' => '13:17',
        'heure_fin' => '14:42',
    ];

    assertCondition(EmploiDuTemps::save($p1, 901), "Sauvegarde du créneau 07:15-08:05 réussie.");
    assertCondition(EmploiDuTemps::save($p2, 901), "Sauvegarde du créneau 08:15-09:37 réussie.");
    assertCondition(EmploiDuTemps::save($p3, 901), "Sauvegarde du créneau 13:17-14:42 réussie.");

    $entries = EmploiDuTemps::getByContext($anneeId1, $classeId1, null, null, 901);
    assertCondition(count($entries) === 3, "3 cours récupérés pour la classe.");

    $controller = new EmploiDuTempsController();
    $refMethod = new ReflectionMethod(EmploiDuTempsController::class, 'buildGrid');
    $refMethod->setAccessible(true);
    $gridData = $refMethod->invoke($controller, $entries);

    assertCondition(count($gridData['intervals']) === 3, "La grille dynamique contient 3 créneaux exacts.");
    assertCondition($gridData['intervals'][0]['label'] === '07:15 - 08:05', "1er créneau = '07:15 - 08:05'.");
    assertCondition($gridData['intervals'][1]['label'] === '08:15 - 09:37', "2ème créneau = '08:15 - 09:37'.");
    assertCondition($gridData['intervals'][2]['label'] === '13:17 - 14:42', "3ème créneau = '13:17 - 14:42'.");

    // TEST 2: Validation refusal when start time >= end time.
    echo "\nTEST 2: Validation refusée si heure_debut >= heure_fin...\n";
    $pInvalid = [
        'classe_id' => $classeId1,
        'matiere_id' => $matiereId1,
        'professeur_id' => $profId1,
        'annee_academique_id' => $anneeId1,
        'jour' => 'Lundi',
        'heure_debut' => '10:00',
        'heure_fin' => '09:00',
    ];
    $resInv = EmploiDuTemps::save($pInvalid, 901);
    assertCondition($resInv === false, "Créneau 10:00->09:00 refusé.");
    assertCondition(strpos($_SESSION['error_message'], "strictement inférieure") !== false, "Message d'erreur explicite renvoyé.");

    // Clear test 1 entries
    $db->exec("DELETE FROM emploi_du_temps WHERE lycee_id = 901");

    // TEST 3: Conflict detection (Teacher, Class, Room overlap).
    echo "\nTEST 3: Détection de chevauchement (Enseignant, Classe, Salle)...\n";
    $c1 = [
        'classe_id' => $classeId1,
        'matiere_id' => $matiereId1,
        'professeur_id' => $profId1,
        'salle_id' => $salleId1,
        'annee_academique_id' => $anneeId1,
        'jour' => 'Lundi',
        'heure_debut' => '08:00',
        'heure_fin' => '10:00',
    ];
    assertCondition(EmploiDuTemps::save($c1, 901), "Cours initial 08:00-10:00 sauvegardé.");

    // Enseignant chevauchement
    $cTeacherOverlap = [
        'classe_id' => $classeId2,
        'matiere_id' => $matiereId1,
        'professeur_id' => $profId1,
        'salle_id' => $salleId2,
        'annee_academique_id' => $anneeId1,
        'jour' => 'Lundi',
        'heure_debut' => '09:00',
        'heure_fin' => '11:00',
    ];
    assertCondition(EmploiDuTemps::save($cTeacherOverlap, 901) === false, "Chevauchement enseignant (09:00-11:00) refusé.");
    assertCondition(strpos($_SESSION['error_message'], "Conflit Enseignant") !== false, "Alerte conflit enseignant affichée.");

    // Classe chevauchement
    $cClassOverlap = [
        'classe_id' => $classeId1,
        'matiere_id' => $matiereId1,
        'professeur_id' => $profId2,
        'salle_id' => $salleId2,
        'annee_academique_id' => $anneeId1,
        'jour' => 'Lundi',
        'heure_debut' => '08:30',
        'heure_fin' => '09:30',
    ];
    assertCondition(EmploiDuTemps::save($cClassOverlap, 901) === false, "Chevauchement classe (08:30-09:30) refusé.");
    assertCondition(strpos($_SESSION['error_message'], "Conflit Classe") !== false, "Alerte conflit classe affichée.");

    // Salle chevauchement
    $cRoomOverlap = [
        'classe_id' => $classeId2,
        'matiere_id' => $matiereId1,
        'professeur_id' => $profId2,
        'salle_id' => $salleId1,
        'annee_academique_id' => $anneeId1,
        'jour' => 'Lundi',
        'heure_debut' => '07:30',
        'heure_fin' => '08:30',
    ];
    assertCondition(EmploiDuTemps::save($cRoomOverlap, 901) === false, "Chevauchement salle (07:30-08:30) refusé.");
    assertCondition(strpos($_SESSION['error_message'], "Conflit Salle") !== false, "Alerte conflit salle affichée.");

    // Créneau consécutif exact (10:00-12:00)
    $cConsecutive = [
        'classe_id' => $classeId1,
        'matiere_id' => $matiereId1,
        'professeur_id' => $profId1,
        'salle_id' => $salleId1,
        'annee_academique_id' => $anneeId1,
        'jour' => 'Lundi',
        'heure_debut' => '10:00',
        'heure_fin' => '12:00',
    ];
    assertCondition(EmploiDuTemps::save($cConsecutive, 901), "Créneau consécutif exact 10:00-12:00 accepté.");

    // TEST 4: Auto-exclusion lors des modifications.
    echo "\nTEST 4: Modification d'un cours (Auto-exclusion du conflit)...\n";
    $cModif = [
        'classe_id' => $classeId1,
        'matiere_id' => $matiereId1,
        'professeur_id' => $profId1,
        'salle_id' => $salleId1,
        'annee_academique_id' => $anneeId1,
        'jour' => 'Mardi',
        'heure_debut' => '08:00',
        'heure_fin' => '10:00',
    ];
    assertCondition(EmploiDuTemps::save($cModif, 901), "Cours créé le Mardi.");

    $saved = EmploiDuTemps::getByContext($anneeId1, $classeId1, null, null, 901);
    $savedId = null;
    foreach ($saved as $s) {
        if ($s['jour'] === 'Mardi') {
            $savedId = $s['id'];
            break;
        }
    }

    $updatePayload = [
        'id' => $savedId,
        'classe_id' => $classeId1,
        'matiere_id' => $matiereId1,
        'professeur_id' => $profId1,
        'salle_id' => $salleId2,
        'annee_academique_id' => $anneeId1,
        'jour' => 'Mardi',
        'heure_debut' => '08:00',
        'heure_fin' => '10:00',
    ];
    assertCondition(EmploiDuTemps::save($updatePayload, 901), "Modification de la salle réussie sans faux conflit auto-induit.");

    // TEST 5: Atomic Swap.
    echo "\nTEST 5: Permutation atomique (Swap) de deux cours...\n";
    $db->exec("DELETE FROM emploi_du_temps WHERE lycee_id = 901");

    $cA = [
        'classe_id' => $classeId1,
        'matiere_id' => $matiereId1,
        'professeur_id' => $profId1,
        'salle_id' => $salleId1,
        'annee_academique_id' => $anneeId1,
        'jour' => 'Lundi',
        'heure_debut' => '08:00',
        'heure_fin' => '09:00',
    ];
    assertCondition(EmploiDuTemps::save($cA, 901), "Cours A enregistré (Lundi 08:00-09:00).");

    $cB = [
        'classe_id' => $classeId2,
        'matiere_id' => $matiereId1,
        'professeur_id' => $profId2,
        'salle_id' => $salleId2,
        'annee_academique_id' => $anneeId1,
        'jour' => 'Lundi',
        'heure_debut' => '09:00',
        'heure_fin' => '10:00',
    ];
    assertCondition(EmploiDuTemps::save($cB, 901), "Cours B enregistré (Lundi 09:00-10:00).");

    $entriesSwap = EmploiDuTemps::getByContext($anneeId1, null, null, null, 901);
    $idA = $entriesSwap[0]['id'];
    $idB = $entriesSwap[1]['id'];

    assertCondition(EmploiDuTemps::swap($idA, $idB, 901), "Permutation atomique réalisée.");

    $afterA = EmploiDuTemps::findById($idA, 901);
    $afterB = EmploiDuTemps::findById($idB, 901);

    assertCondition(substr($afterA['heure_debut'], 0, 5) === '09:00' && $afterA['salle_id'] == $salleId2, "Cours A mis à jour sur le créneau de B (09:00-10:00, Salle 102).");
    assertCondition(substr($afterB['heure_debut'], 0, 5) === '08:00' && $afterB['salle_id'] == $salleId1, "Cours B mis à jour sur le créneau de A (08:00-09:00, Salle 101).");

    // TEST 6: Multi-tenant Isolation.
    echo "\nTEST 6: Isolation multi-tenant lycee_id...\n";
    $cIso = [
        'classe_id' => $classeId1,
        'matiere_id' => $matiereId1,
        'professeur_id' => $profId1,
        'annee_academique_id' => $anneeId1,
        'jour' => 'Mercredi',
        'heure_debut' => '08:00',
        'heure_fin' => '10:00',
    ];
    assertCondition(EmploiDuTemps::save($cIso, 901), "Cours créé sous Lycée 901.");

    $entriesT2 = EmploiDuTemps::getByContext($anneeId1, null, null, null, 902);
    assertCondition(count($entriesT2) === 0, "Lycée 902 ne voit aucun cours du Lycée 901.");

    $cCross = [
        'classe_id' => $classeId1,
        'matiere_id' => $matiereId1,
        'professeur_id' => $profIdTenant2,
        'annee_academique_id' => $anneeId1,
        'jour' => 'Mercredi',
        'heure_debut' => '10:00',
        'heure_fin' => '12:00',
    ];
    assertCondition(EmploiDuTemps::save($cCross, 901) === false, "Insertion inter-établissement avec un enseignant du lycée 902 rejetée.");

    // Cleanup
    $db->exec("DELETE FROM emploi_du_temps WHERE lycee_id IN (901, 902)");
    $db->exec("DELETE FROM salles WHERE lycee_id IN (901, 902)");
    $db->exec("DELETE FROM classes WHERE lycee_id IN (901, 902)");
    $db->exec("DELETE FROM utilisateurs WHERE lycee_id IN (901, 902)");
    $db->exec("DELETE FROM param_lycee WHERE id IN (901, 902)");

    echo "\n========================================================\n";
    echo "RÉSULTAT FINAL: TOUS LES TESTS D'EMPLOI DU TEMPS ONT RÉUSSI !\n";
    echo "========================================================\n";

} catch (Exception $e) {
    echo "❌ [EXCEPTION] Erreur durant les tests : " . $e->getMessage() . "\n";
    exit(1);
}
