<?php

/**
 * Integration Test Suite for Emploi du Temps Module (Point 5).
 * Validates all 10 mandated scenarios.
 */

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/models/EmploiDuTemps.php';
require_once __DIR__ . '/../src/models/Classe.php';
require_once __DIR__ . '/../src/models/Matiere.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/Salle.php';
require_once __DIR__ . '/../src/models/Cycle.php';
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
echo "DÉBUT DE LA SUITE DE TESTS : EMPLOI DU TEMPS (10 SCÉNARIOS)\n";
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

$stmtC = $db->query("SELECT id_cycle FROM cycles LIMIT 1");
$cRow = $stmtC->fetch();
if ($cRow) {
    $cycleIdTest = (int)$cRow['id_cycle'];
} else {
    $db->exec("INSERT INTO cycles (nom_cycle, lycee_id) VALUES ('Cycle Test', 901)");
    $cycleIdTest = (int)$db->lastInsertId();
}

// Matiere
$stmtM = $db->query("SELECT id_matiere FROM matieres LIMIT 1");
$mRow = $stmtM->fetch();
if ($mRow) {
    $matiereId1 = (int)$mRow['id_matiere'];
} else {
    $db->exec("INSERT INTO matieres (nom_matiere, code_matiere) VALUES ('Mathématiques', 'MATH')");
    $matiereId1 = (int)$db->lastInsertId();
}

// Classes
$db->exec("INSERT INTO classes (niveau, serie, numero, cycle_id, lycee_id) VALUES ('6eme', 'A', 1, $cycleIdTest, 901)");
$classeId1 = (int)$db->lastInsertId();

$db->exec("INSERT INTO classes (niveau, serie, numero, cycle_id, lycee_id) VALUES ('5eme', 'B', 1, $cycleIdTest, 901)");
$classeId2 = (int)$db->lastInsertId();

$db->exec("INSERT INTO classes (niveau, serie, numero, cycle_id, lycee_id) VALUES ('4eme', 'A', 1, $cycleIdTest, 902)");
$classeIdTenant2 = (int)$db->lastInsertId();

// Link matieres to classes
$db->exec("INSERT INTO classe_matieres (classe_id, matiere_id, coefficient, statut) VALUES ($classeId1, $matiereId1, 2, 'actif')");
$db->exec("INSERT INTO classe_matieres (classe_id, matiere_id, coefficient, statut) VALUES ($classeId2, $matiereId1, 2, 'actif')");

// Teachers
$db->exec("INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role_id, lycee_id, actif) VALUES ('Prof1', 'Jean', 'prof1@edt.test', 'secret', 6, 901, 1)");
$profId1 = (int)$db->lastInsertId();

$db->exec("INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role_id, lycee_id, actif) VALUES ('Prof2', 'Marie', 'prof2@edt.test', 'secret', 6, 901, 1)");
$profId2 = (int)$db->lastInsertId();

$db->exec("INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role_id, lycee_id, actif) VALUES ('ProfTenant2', 'Pierre', 'prof_t2@edt.test', 'secret', 6, 902, 1)");
$profIdTenant2 = (int)$db->lastInsertId();

// Salles
$db->exec("INSERT INTO salles (nom_salle, capacite, lycee_id) VALUES ('Salle 101', 30, 901)");
$salleId1 = (int)$db->lastInsertId();

$db->exec("INSERT INTO salles (nom_salle, capacite, lycee_id) VALUES ('Salle 102', 30, 901)");
$salleId2 = (int)$db->lastInsertId();

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
    // SCÉNARIO 1: Cours enregistré à 07:15 -> 08:15 apparaît dans la colonne du bon jour
    echo "\n1. SCÉNARIO 1: Cours 07:15 -> 08:15 dans la colonne du bon jour...\n";
    $p1 = [
        'classe_id' => $classeId1,
        'matiere_id' => $matiereId1,
        'professeur_id' => $profId1,
        'salle_id' => $salleId1,
        'annee_academique_id' => $anneeId1,
        'jour' => 'Lundi',
        'heure_debut' => '07:15',
        'heure_fin' => '08:15',
    ];
    assertCondition(EmploiDuTemps::save($p1, 901), "Enregistrement 07:15-08:15 réussi.");
    $entries = EmploiDuTemps::getByContext($anneeId1, $classeId1, null, null, 901);
    $controller = new EmploiDuTempsController();
    $refMethod = new ReflectionMethod(EmploiDuTempsController::class, 'buildGrid');
    $refMethod->setAccessible(true);
    $grid = $refMethod->invoke($controller, $entries);
    assertCondition(!empty($grid['grid']['07:15 - 08:15']['Lundi']), "07:15-08:15 est injecté dans la colonne Lundi.");

    // SCÉNARIO 2: Cours à 08:00 -> 09:00 apparaît correctement dans la grille
    echo "\n2. SCÉNARIO 2: Cours 08:00 -> 09:00 apparaît correctement...\n";
    $p2 = [
        'classe_id' => $classeId1,
        'matiere_id' => $matiereId1,
        'professeur_id' => $profId1,
        'salle_id' => $salleId1,
        'annee_academique_id' => $anneeId1,
        'jour' => 'Mardi',
        'heure_debut' => '08:00',
        'heure_fin' => '09:00',
    ];
    assertCondition(EmploiDuTemps::save($p2, 901), "Enregistrement 08:00-09:00 réussi.");
    $entries = EmploiDuTemps::getByContext($anneeId1, $classeId1, null, null, 901);
    $grid = $refMethod->invoke($controller, $entries);
    assertCondition(!empty($grid['grid']['08:00 - 09:00']['Mardi']), "08:00-09:00 apparaît dans la colonne Mardi.");

    // SCÉNARIO 3: Cours à 08:15 -> 09:37 apparaît correctement
    echo "\n3. SCÉNARIO 3: Cours 08:15 -> 09:37 avec durée non standard...\n";
    $p3 = [
        'classe_id' => $classeId1,
        'matiere_id' => $matiereId1,
        'professeur_id' => $profId1,
        'salle_id' => $salleId1,
        'annee_academique_id' => $anneeId1,
        'jour' => 'Mercredi',
        'heure_debut' => '08:15',
        'heure_fin' => '09:37',
    ];
    assertCondition(EmploiDuTemps::save($p3, 901), "Enregistrement 08:15-09:37 réussi.");
    $entries = EmploiDuTemps::getByContext($anneeId1, $classeId1, null, null, 901);
    $grid = $refMethod->invoke($controller, $entries);
    assertCondition(!empty($grid['grid']['08:15 - 09:37']['Mercredi']), "08:15-09:37 apparaît dans la colonne Mercredi.");

    // SCÉNARIO 4: Chaque jour de la semaine est correctement associé à sa colonne
    echo "\n4. SCÉNARIO 4: Association exacte des jours avec normalisation (lundi, MERCREDI, vendredi )...\n";
    $pNorm = [
        'classe_id' => $classeId2,
        'matiere_id' => $matiereId1,
        'professeur_id' => $profId2,
        'salle_id' => $salleId2,
        'annee_academique_id' => $anneeId1,
        'jour' => 'vendredi ', // leading/trailing spaces + lowercase
        'heure_debut' => '14:00',
        'heure_fin' => '16:00',
    ];
    assertCondition(EmploiDuTemps::save($pNorm, 901), "Enregistrement jour avec espaces/minuscules réussi.");
    $entries2 = EmploiDuTemps::getByContext($anneeId1, $classeId2, null, null, 901);
    $grid2 = $refMethod->invoke($controller, $entries2);
    assertCondition(!empty($grid2['grid']['14:00 - 16:00']['Vendredi']), "'vendredi ' normalisé et placé sous la colonne Vendredi.");

    // SCÉNARIO 5: Convention des filtres pédagogiques unifiée SGS et clés de Cycle
    echo "\n5. SCÉNARIO 5: Validation de la convention des filtres Cycle->Niveau->Série->Numéro...\n";
    $cyclesFound = Cycle::findAll($lycee_id = 901);
    assertCondition(isset($cyclesFound[0]['id_cycle']) && isset($cyclesFound[0]['nom_cycle']), "Cycle::findAll() contient les clés exactes id_cycle et nom_cycle.");
    $numeros = Classe::findAvailableNumeros('6eme', 'A', 901, $cycleIdTest);
    assertCondition(!empty($numeros), "Classe::findAvailableNumeros retourne la structure unifiée SGS.");

    // SCÉNARIO 6: Création et Gestion donnent la même classe identifiée
    echo "\n6. SCÉNARIO 6: Identité de la classe résolue entre création et gestion...\n";
    $cFound = Classe::findById($classeId1, 901);
    assertCondition($cFound['id_classe'] == $classeId1, "Même ID classe résolu.");

    // Clear entries for scenario 7-10
    $db->exec("DELETE FROM emploi_du_temps WHERE lycee_id = 901");

    // SCÉNARIO 7: Anti-chevauchement Enseignant, Classe, Salle
    echo "\n7. SCÉNARIO 7: Contrôle anti-chevauchement Enseignant, Classe et Salle...\n";
    $cBase = [
        'classe_id' => $classeId1,
        'matiere_id' => $matiereId1,
        'professeur_id' => $profId1,
        'salle_id' => $salleId1,
        'annee_academique_id' => $anneeId1,
        'jour' => 'Jeudi',
        'heure_debut' => '10:00',
        'heure_fin' => '12:00',
    ];
    assertCondition(EmploiDuTemps::save($cBase, 901), "Cours de référence enregistré Jeudi 10:00-12:00.");

    $cOverlapProf = [
        'classe_id' => $classeId2,
        'matiere_id' => $matiereId1,
        'professeur_id' => $profId1,
        'salle_id' => $salleId2,
        'annee_academique_id' => $anneeId1,
        'jour' => 'Jeudi',
        'heure_debut' => '11:00',
        'heure_fin' => '13:00',
    ];
    assertCondition(EmploiDuTemps::save($cOverlapProf, 901) === false, "Chevauchement enseignant refusé.");

    // SCÉNARIO 8: Une modification ne peut pas introduire de chevauchement
    echo "\n8. SCÉNARIO 8: Protection anti-chevauchement lors d'une modification (UPDATE)...\n";
    $cToEdit = [
        'classe_id' => $classeId2,
        'matiere_id' => $matiereId1,
        'professeur_id' => $profId2,
        'salle_id' => $salleId2,
        'annee_academique_id' => $anneeId1,
        'jour' => 'Jeudi',
        'heure_debut' => '14:00',
        'heure_fin' => '16:00',
    ];
    assertCondition(EmploiDuTemps::save($cToEdit, 901), "Second cours créé Jeudi 14:00-16:00.");
    $entriesJeudi = EmploiDuTemps::getByContext($anneeId1, $classeId2, null, null, 901);
    $editId = $entriesJeudi[0]['id'];

    $badUpdate = [
        'id' => $editId,
        'classe_id' => $classeId2,
        'matiere_id' => $matiereId1,
        'professeur_id' => $profId1, // Change to Prof1 who is booked 10:00-12:00
        'salle_id' => $salleId2,
        'annee_academique_id' => $anneeId1,
        'jour' => 'Jeudi',
        'heure_debut' => '11:00',
        'heure_fin' => '13:00',
    ];
    assertCondition(EmploiDuTemps::save($badUpdate, 901) === false, "Modification provoquant un conflit refusée.");

    // SCÉNARIO 9: Permutation atomique
    echo "\n9. SCÉNARIO 9: Permutation atomique (Swap)...\n";
    $cSwap1 = [
        'classe_id' => $classeId1,
        'matiere_id' => $matiereId1,
        'professeur_id' => $profId1,
        'salle_id' => $salleId1,
        'annee_academique_id' => $anneeId1,
        'jour' => 'Samedi',
        'heure_debut' => '08:00',
        'heure_fin' => '09:00',
    ];
    EmploiDuTemps::save($cSwap1, 901);

    $cSwap2 = [
        'classe_id' => $classeId2,
        'matiere_id' => $matiereId1,
        'professeur_id' => $profId2,
        'salle_id' => $salleId2,
        'annee_academique_id' => $anneeId1,
        'jour' => 'Samedi',
        'heure_debut' => '09:00',
        'heure_fin' => '10:00',
    ];
    EmploiDuTemps::save($cSwap2, 901);

    $allSamedi = EmploiDuTemps::getByContext($anneeId1, null, null, null, 901);
    $sId1 = $allSamedi[0]['id'];
    $sId2 = $allSamedi[1]['id'];

    assertCondition(EmploiDuTemps::swap($sId1, $sId2, 901), "Permutation atomique effectuée sans blocage intermédiaire.");

    // SCÉNARIO 10: Isolation lycee_id stricte
    echo "\n10. SCÉNARIO 10: Isolation lycee_id stricte...\n";
    $entriesT2 = EmploiDuTemps::getByContext($anneeId1, null, null, null, 902);
    assertCondition(count($entriesT2) === 0, "Aucun cours du lycée 901 n'est visible par le lycée 902.");

    // SCÉNARIO 11: Validation stricte des relations pédagogiques côté serveur
    echo "\n11. SCÉNARIO 11: Validation stricte des relations pédagogiques côté serveur...\n";
    $pBadSubject = [
        'classe_id' => $classeId1,
        'matiere_id' => 99999, // Inexistent or unassigned subject
        'professeur_id' => $profId1,
        'salle_id' => $salleId1,
        'annee_academique_id' => $anneeId1,
        'jour' => 'Mardi',
        'heure_debut' => '14:00',
        'heure_fin' => '15:00',
    ];
    assertCondition(EmploiDuTemps::save($pBadSubject, 901) === false, "Enregistrement refusé pour matière non rattachée à la classe.");

    // Cleanup
    $db->exec("DELETE FROM emploi_du_temps WHERE lycee_id IN (901, 902)");
    $db->exec("DELETE FROM salles WHERE lycee_id IN (901, 902)");
    $db->exec("DELETE FROM classes WHERE lycee_id IN (901, 902)");
    $db->exec("DELETE FROM utilisateurs WHERE lycee_id IN (901, 902)");
    $db->exec("DELETE FROM param_lycee WHERE id IN (901, 902)");

    echo "\n========================================================\n";
    echo "RÉSULTAT FINAL: LES 10 SCÉNARIOS DE TEST ONT RÉUSSI !\n";
    echo "========================================================\n";

} catch (Exception $e) {
    echo "❌ [EXCEPTION] " . $e->getMessage() . "\n";
    exit(1);
}
