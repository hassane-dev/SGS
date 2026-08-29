<?php
// tests/CahierTexteConsultationAndFilterTest.php

if (!defined('TEST_MODE')) {
    define('TEST_MODE', true);
}

define('TEST_MODE', true);
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/Classe.php';
require_once __DIR__ . '/../src/models/Matiere.php';
require_once __DIR__ . '/../src/models/Cycle.php';
require_once __DIR__ . '/../src/models/AnneeAcademique.php';
require_once __DIR__ . '/../src/models/CahierTexte.php';
require_once __DIR__ . '/../src/controllers/CahierTexteController.php';

echo "========================================================\n";
echo "SUITE DE TEST: Consultation & Filtrage du Cahier de texte\n";
echo "========================================================\n";

$db = Database::getInstance();

// Cleanup prior test data
$db->exec("DELETE FROM cahier_texte WHERE classe_id >= 9950 OR lycee_id = 99");
$db->exec("DELETE FROM classe_matieres WHERE classe_id >= 9950");
$db->exec("DELETE FROM classes WHERE id_classe >= 9950 OR lycee_id = 99");
$db->exec("DELETE FROM matieres WHERE id_matiere >= 9950");
$db->exec("DELETE FROM personnel_cycles_assignments WHERE personnel_id >= 9950");
$db->exec("DELETE FROM utilisateurs WHERE id_user >= 9950");
$db->exec("DELETE FROM cycles WHERE id_cycle IN (995, 996)");
$db->exec("DELETE FROM param_lycee WHERE id = 99");

// Create Academic Year if not exists
$stmt_aa = $db->query("SELECT id FROM annees_academiques WHERE est_active = 1 LIMIT 1");
$active_annee_id = $stmt_aa->fetchColumn();
if (!$active_annee_id) {
    $db->exec("INSERT INTO annees_academiques (libelle, date_debut, date_fin, est_active) VALUES ('2024-2025', '2024-09-01', '2025-06-30', 1)");
    $active_annee_id = $db->lastInsertId();
}

// 1. Setup Cycles: CEG (995) & Lycée (996)
$db->exec("INSERT INTO cycles (id_cycle, nom_cycle, lycee_id) VALUES (995, 'CEG Test CT', 1) ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO cycles (id_cycle, nom_cycle, lycee_id) VALUES (996, 'Lycée Test CT', 1) ON CONFLICT DO NOTHING");

// 2. Setup Classes
// CEG Class: 6ème A 1 (ID 9951)
$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero) VALUES (9951, 1, 995, '6ème', '', 1)");
// Lycée Class: Terminale C 2 (ID 9961)
$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero) VALUES (9961, 1, 996, 'Terminale', 'C', 2)");

// 3. Setup Subjects
$db->exec("INSERT INTO matieres (id_matiere, lycee_id, nom_matiere) VALUES (9951, 1, 'Français CEG')");
$db->exec("INSERT INTO matieres (id_matiere, lycee_id, nom_matiere) VALUES (9961, 1, 'Mathématiques Lycée')");

// Assign subjects to classes
$db->exec("INSERT INTO classe_matieres (classe_id, matiere_id, coefficient) VALUES (9951, 9951, 2.0)");
$db->exec("INSERT INTO classe_matieres (classe_id, matiere_id, coefficient) VALUES (9961, 9961, 5.0)");

// 4. Setup Teachers
// Teacher CEG (9951)
$db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, email, role_id, fonction, actif, identifiant_public) VALUES (9951, 1, 'Tchou', 'Jean', 'jean.ceg@test.com', 6, 'Enseignant', 1, 'ENS-9951')");
$db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, actif) VALUES (9951, 995, '2024-09-01', 1)");

// Teacher Lycée (9961)
$db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, email, role_id, fonction, actif, identifiant_public) VALUES (9961, 1, 'Kouassi', 'Marie', 'marie.lycee@test.com', 6, 'Enseignant', 1, 'ENS-9961')");
$db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, actif) VALUES (9961, 996, '2024-09-01', 1)");

// 5. Setup Foreign Lycée (ID 99)
$db->exec("INSERT INTO param_lycee (id, nom_lycee) VALUES (99, 'Lycée Distant Test') ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero) VALUES (9999, 99, 995, '3ème', '', 1)");
$db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, email, role_id, fonction, actif) VALUES (9999, 99, 'Etranger', 'Paul', 'paul@foreign.com', 6, 'Enseignant', 1)");

// 6. Setup Cahier de texte Entries
// Entry 1: CEG session by Jean (ID 99501) (2 hours: 08:00 to 10:00)
$db->exec("INSERT INTO cahier_texte (cahier_id, personnel_id, classe_id, matiere_id, date_cours, heure_debut, heure_fin, contenu_cours, travail_donne, observation, annee_id, lycee_id)
           VALUES (99501, 9951, 9951, 9951, '2024-10-10', '08:00:00', '10:00:00', 'Grammaire et Vocabulaire', 'Faire exercice 3 p. 45', 'Rien à signaler', {$active_annee_id}, 1)");

// Entry 2: Lycée session by Marie (ID 99601) (1h30: 10:00 to 11:30)
$db->exec("INSERT INTO cahier_texte (cahier_id, personnel_id, classe_id, matiere_id, date_cours, heure_debut, heure_fin, contenu_cours, travail_donne, observation, annee_id, lycee_id)
           VALUES (99601, 9961, 9961, 9961, '2024-10-11', '10:00:00', '11:30:00', 'Nombres Complexes', 'Faire exercices 10 et 12', 'Très bonne participation', {$active_annee_id}, 1)");

// Entry 3: Foreign Lycée session (ID 99901)
$db->exec("INSERT INTO cahier_texte (cahier_id, personnel_id, classe_id, matiere_id, date_cours, heure_debut, heure_fin, contenu_cours, travail_donne, observation, annee_id, lycee_id)
           VALUES (99901, 9999, 9999, 9951, '2024-10-10', '08:00:00', '10:00:00', 'Cours distant secret', '', '', {$active_annee_id}, 99)");

echo "[OK] Données de test initialisées avec succès.\n\n";

// Function helper to mock session
function mockSession($userId, $lyceeId, $roleName, $permissions = []) {
    $_SESSION['user'] = [
        'id' => $userId,
        'id_user' => $userId,
        'lycee_id' => $lyceeId,
        'role_id' => null, // Prevents Auth::can() from overriding permissions via Role::getPermissions()
        'role_name' => $roleName,
        'auth_version' => 1,
        'permissions' => $permissions
    ];
    $_SESSION['user_id'] = $userId;
    $_SESSION['lycee_id'] = $lyceeId;
    $_SESSION['role_id'] = null;
    $_SESSION['role_name'] = $roleName;
}

// -------------------------------------------------------------
// SCÉNARIO 1 : Consultation d'une séance existante et calcul de la durée
// -------------------------------------------------------------
echo "SCÉNARIO 1 : Consultation détaillée d'une séance et calcul de la durée\n";
mockSession(1, 1, 'super_admin_createur', ['cahier_texte' => ['manage', 'view_all']]);

$detailsCEG = CahierTexte::findDetailsById(99501, 1);
if (
    $detailsCEG &&
    $detailsCEG['cahier_id'] == 99501 &&
    $detailsCEG['nom_personnel'] === 'Tchou' &&
    $detailsCEG['nom_cycle'] === 'CEG Test CT' &&
    $detailsCEG['nom_matiere'] === 'Français CEG' &&
    $detailsCEG['duree_heures'] == 2.00 &&
    $detailsCEG['duree_formatee'] === '2h 00min'
) {
    echo "  -> Attendu: Détails CEG récupérés correctement (2h 00min) | PASS\n";
} else {
    echo "  -> ECHEC: Métadonnées CEG incorrectes ou durée mal calculée\n";
    print_r($detailsCEG);
    exit(1);
}

$detailsLycee = CahierTexte::findDetailsById(99601, 1);
if (
    $detailsLycee &&
    $detailsLycee['cahier_id'] == 99601 &&
    $detailsLycee['nom_personnel'] === 'Kouassi' &&
    $detailsLycee['nom_cycle'] === 'Lycée Test CT' &&
    $detailsLycee['duree_formatee'] === '1h 30min'
) {
    echo "  -> Attendu: Détails Lycée récupérés correctement (1h 30min) | PASS\n";
} else {
    echo "  -> ECHEC: Métadonnées Lycée ou durée 1h 30min incorrecte\n";
    print_r($detailsLycee);
    exit(1);
}

// -------------------------------------------------------------
// SCÉNARIO 2 : Refus d'accès à une séance d'un autre lycée & isolation multi-tenant
// -------------------------------------------------------------
echo "\nSCÉNARIO 2 : Refus d'accès à une séance d'un autre lycée (multi-tenant)\n";
mockSession(1, 1, 'super_admin_createur', ['cahier_texte' => ['manage', 'view_all']]);

$foreignDetails = CahierTexte::findDetailsById(99901, 1); // Lycée 1 attempting to view session from Lycée 99
if (empty($foreignDetails) || !isset($foreignDetails['cahier_id'])) {
    echo "  -> Attendu: Impossibilité de consulter la séance d'un autre lycée (renvoie false) | PASS\n";
} else {
    echo "  -> ECHEC: Fuite multi-tenant détectée lors de la consultation d'une séance distante\n";
    exit(1);
}

// -------------------------------------------------------------
// SCÉNARIO 3 : Contrôle RBAC (Enseignant ne voit que ses séances)
// -------------------------------------------------------------
echo "\nSCÉNARIO 3 : Respect du RBAC (Enseignant Jean ne peut pas consulter la séance de Marie)\n";
// Logged in as Teacher Jean (9951)
mockSession(9951, 1, 'enseignant', ['cahier_texte' => ['create_own', 'edit_own']]);

$_GET = ['id' => 99601]; // Session 99601 belongs to Marie (9961)
$controller = new CahierTexteController();

ob_start();
$controller->show();
$output = ob_get_clean();
$code = http_response_code();

if (strpos($output, 'Accès Interdit') !== false) {
    echo "  -> Attendu: Message 'Accès Interdit' renvoyé à l'enseignant tentant de voir la séance d'un collègue | PASS\n";
} else {
    echo "  -> ECHEC RBAC: Output était '$output' au lieu de 'Accès Interdit'\n";
    exit(1);
}

// -------------------------------------------------------------
// SCÉNARIO 4 : Filtrage hiérarchique CEG (Cycle 995 -> 6ème -> Numéro 1)
// -------------------------------------------------------------
echo "\nSCÉNARIO 4 : Filtrage hiérarchique CEG (Cycle -> Niveau 6ème -> Numéro 1)\n";
mockSession(1, 1, 'super_admin_createur', ['cahier_texte' => ['manage', 'view_all']]);

$ceg_results = CahierTexte::findAllByPersonnel(null, 1, [
    'cycle_id' => 995,
    'niveau' => '6ème',
    'numero' => '1'
]);

if (count($ceg_results) === 1 && $ceg_results[0]['cahier_id'] == 99501) {
    echo "  -> Attendu: Filtrage CEG retourne uniquement la séance 99501 | PASS\n";
} else {
    echo "  -> ECHEC: Filtrage CEG incorrect (" . count($ceg_results) . " entrées retournées)\n";
    exit(1);
}

// -------------------------------------------------------------
// SCÉNARIO 5 : Filtrage hiérarchique Lycée avec Série (Cycle 996 -> Terminale -> Série C -> Numéro 2)
// -------------------------------------------------------------
echo "\nSCÉNARIO 5 : Filtrage hiérarchique Lycée (Cycle -> Niveau Terminale -> Série C -> Numéro 2)\n";

$lycee_results = CahierTexte::findAllByPersonnel(null, 1, [
    'cycle_id' => 996,
    'niveau' => 'Terminale',
    'serie' => 'C',
    'numero' => '2'
]);

if (count($lycee_results) === 1 && $lycee_results[0]['cahier_id'] == 99601) {
    echo "  -> Attendu: Filtrage Lycée avec série C retourne uniquement la séance 99601 | PASS\n";
} else {
    echo "  -> ECHEC: Filtrage Lycée avec série incorrect\n";
    exit(1);
}

// -------------------------------------------------------------
// SCÉNARIO 6 : Filtrage par Matière et Enseignant
// -------------------------------------------------------------
echo "\nSCÉNARIO 6 : Filtrage par Matière et par Enseignant\n";

$matiere_results = CahierTexte::findAllByPersonnel(null, 1, [
    'matiere_id' => 9961
]);
if (count($matiere_results) === 1 && $matiere_results[0]['cahier_id'] == 99601) {
    echo "  -> Attendu: Filtrage par matière 9961 (Mathématiques Lycée) isole la séance 99601 | PASS\n";
} else {
    echo "  -> ECHEC du filtrage par matière\n";
    exit(1);
}

$teacher_results = CahierTexte::findAllByPersonnel(null, 1, [
    'personnel_id_filter' => 9951
]);
if (count($teacher_results) === 1 && $teacher_results[0]['cahier_id'] == 99501) {
    echo "  -> Attendu: Filtrage par enseignant 9951 (Jean) isole la séance 99501 | PASS\n";
} else {
    echo "  -> ECHEC du filtrage par enseignant\n";
    exit(1);
}

// -------------------------------------------------------------
// SCÉNARIO 7 : Incohérence de filtres GET & Protection Serveur
// -------------------------------------------------------------
echo "\nSCÉNARIO 7 : Détection et neutralisation par le serveur des filtres falsifiés (GET tampering)\n";
mockSession(1, 1, 'super_admin_createur', ['cahier_texte' => ['manage', 'view_all']]);
// Simulation GET params where classe_id is 9951 (CEG 6ème A 1), but cycle_id is forced to 996 (Lycée)
$_GET = [
    'cycle_id' => 996,
    'niveau' => '6ème',
    'classe_id' => 9951
];

$controller = new CahierTexteController();
ob_start();
$controller->index();
$htmlOutput = ob_get_clean();

// Access the internal filters evaluated by the controller logic in index()
$inconsistent_results = CahierTexte::findAllByPersonnel(null, 1, [
    'cycle_id' => 996, // Lycée
    'classe_id' => 9951 // CEG class (belongs to cycle 995)
]);
if (count($inconsistent_results) === 0) {
    echo "  -> Attendu: Aucune donnée retournée si cycle (Lycée) et classe (CEG) sont contradictoires | PASS\n";
} else {
    echo "  -> ECHEC: Une incohérence entre cycle et classe n'a pas été filtrée par SQL\n";
    exit(1);
}

// -------------------------------------------------------------
// SCÉNARIO 8 : Non-régression du CRUD (Store, Update, Delete)
// -------------------------------------------------------------
echo "\nSCÉNARIO 8 : Non-régression du workflow CRUD (Création, Édition, Suppression)\n";

// A. Store
mockSession(9951, 1, 'enseignant', ['cahier_texte' => ['create_own', 'edit_own']]);
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'class_subject' => '9951-9951',
    'date_cours' => '2024-10-15',
    'heure_debut' => '14:00',
    'heure_fin' => '16:00',
    'contenu_cours' => 'Séance de test CRUD automatique',
    'travail_donne' => 'Exercice 1',
    'observation' => 'RAS'
];

ob_start();
$controller->store();
ob_get_clean();

// Check created entry in DB
$created = $db->query("SELECT * FROM cahier_texte WHERE personnel_id = 9951 AND date_cours = '2024-10-15' ORDER BY cahier_id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if ($created && $created['contenu_cours'] === 'Séance de test CRUD automatique') {
    echo "  -> 8.1 Store : Entrée créée avec succès (cahier_id #{$created['cahier_id']}) | PASS\n";
} else {
    echo "  -> ECHEC de la création d'entrée Cahier de texte\n";
    exit(1);
}

// B. Update
$created_id = $created['cahier_id'];
$_POST = [
    'cahier_id' => $created_id,
    'class_subject' => '9951-9951',
    'date_cours' => '2024-10-15',
    'heure_debut' => '14:00',
    'heure_fin' => '16:00',
    'contenu_cours' => 'Séance de test CRUD MAJ',
    'travail_donne' => 'Exercice 1 & 2',
    'observation' => 'Mis à jour'
];

ob_start();
$controller->update();
ob_get_clean();

$updated = CahierTexte::findById($created_id);
if ($updated && $updated['contenu_cours'] === 'Séance de test CRUD MAJ') {
    echo "  -> 8.2 Update : Entrée mise à jour avec succès | PASS\n";
} else {
    echo "  -> ECHEC de la mise à jour d'entrée Cahier de texte\n";
    exit(1);
}

// C. Destroy
$_POST = ['id' => $created_id];
ob_start();
$controller->destroy();
ob_get_clean();

$deleted = CahierTexte::findById($created_id);
if (!$deleted) {
    echo "  -> 8.3 Destroy : Entrée supprimée avec succès | PASS\n";
} else {
    echo "  -> ECHEC de la suppression d'entrée Cahier de texte\n";
    exit(1);
}

// Final Cleanup
$db->exec("DELETE FROM cahier_texte WHERE classe_id >= 9950 OR lycee_id = 99");
$db->exec("DELETE FROM classe_matieres WHERE classe_id >= 9950");
$db->exec("DELETE FROM classes WHERE id_classe >= 9950 OR lycee_id = 99");
$db->exec("DELETE FROM matieres WHERE id_matiere >= 9950");
$db->exec("DELETE FROM personnel_cycles_assignments WHERE personnel_id >= 9950");
$db->exec("DELETE FROM utilisateurs WHERE id_user >= 9950");
$db->exec("DELETE FROM cycles WHERE id_cycle IN (995, 996)");
$db->exec("DELETE FROM param_lycee WHERE id = 99");

echo "\n========================================================\n";
echo "RÉSULTAT FINAL: TOUS LES SCÉNARIOS CAHIER DE TEXTE ONT RÉUSSI !\n";
echo "========================================================\n";
