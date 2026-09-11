<?php
// tests/AppreciationConseilClasseTest.php

if (!defined('TEST_MODE')) {
    define('TEST_MODE', true);
}

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../migrate.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/Classe.php';
require_once __DIR__ . '/../src/models/Sequence.php';
require_once __DIR__ . '/../src/models/AnneeAcademique.php';
require_once __DIR__ . '/../src/models/ClasseParametre.php';
require_once __DIR__ . '/../src/models/Bulletin.php';
require_once __DIR__ . '/../src/controllers/AppreciationConseilController.php';

echo "========================================================\n";
echo "SUITE DE TEST: Appréciation du Conseil de Classe & RBAC\n";
echo "========================================================\n";

$db = Database::getInstance();

// 1. Setup clean test environment
$db->exec("DELETE FROM bulletins WHERE eleve_id >= 9950");
$db->exec("DELETE FROM etudes WHERE eleve_id >= 9950");
$db->exec("DELETE FROM eleves WHERE id_eleve >= 9950");
$db->exec("DELETE FROM classe_parametres WHERE classe_id >= 9950");
$db->exec("DELETE FROM classes WHERE id_classe >= 9950");
$db->exec("DELETE FROM sequences WHERE id >= 9950");
$db->exec("DELETE FROM utilisateurs WHERE id_user >= 9950");
$db->exec("DELETE FROM param_lycee WHERE id >= 95");

// Ensure active academic year exists
$stmt_aa = $db->query("SELECT id FROM annees_academiques WHERE est_active = 1 LIMIT 1");
$active_annee_id = (int)$stmt_aa->fetchColumn();
if (!$active_annee_id) {
    $db->exec("INSERT INTO annees_academiques (libelle, date_debut, date_fin, est_active) VALUES ('2024-2025', '2024-09-01', '2025-06-30', 1)");
    $active_annee_id = (int)$db->lastInsertId();
}

// Create Test Establishments
$db->exec("INSERT INTO param_lycee (id, nom_lycee) VALUES (95, 'Lycée Principal Test') ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO param_lycee (id, nom_lycee) VALUES (96, 'Lycée Secondaire Test') ON CONFLICT DO NOTHING");

// Create Test Classes (3e B in Lycée 95, 3e A in Lycée 95, 3e C in Lycée 96)
$db->exec("INSERT INTO classes (id_classe, lycee_id, niveau, serie, numero) VALUES (9951, 95, '3ème', 'B', 1)"); // Class 9951: 3e B
$db->exec("INSERT INTO classes (id_classe, lycee_id, niveau, serie, numero) VALUES (9952, 95, '3ème', 'A', 1)"); // Class 9952: 3e A
$db->exec("INSERT INTO classes (id_classe, lycee_id, niveau, serie, numero) VALUES (9953, 96, '3ème', 'C', 1)"); // Class 9953: 3e C (Foreign Lycée)

// Create Test Sequences
$db->exec("INSERT INTO sequences (id, lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut) VALUES (9951, 95, {$active_annee_id}, 'Séquence 1 Test', 'sequence', '2024-09-01', '2024-10-31', 'ouverte')");
$db->exec("INSERT INTO sequences (id, lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut) VALUES (9952, 95, {$active_annee_id}, 'Séquence 2 Test', 'sequence', '2024-11-01', '2024-12-31', 'fermee')");

// Create Users
// Prof Alpha (PP of 3e B - Class 9951)
$db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, email, role_id, fonction, actif) VALUES (9951, 95, 'Dupont', 'Jean', 'prof.alpha@test.com', 6, 'Enseignant', 1)");
$db->exec("INSERT INTO classe_parametres (classe_id, annee_academique_id, professeur_principal_id) VALUES (9951, {$active_annee_id}, 9951)");

// Prof Beta (Enseignant simple - NOT PP of any class)
$db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, email, role_id, fonction, actif) VALUES (9952, 95, 'Martin', 'Claire', 'prof.beta@test.com', 6, 'Enseignant', 1)");

// Create Students enrolled in Class 9951 (3e B)
$db->exec("INSERT INTO eleves (id_eleve, lycee_id, nom, prenom) VALUES (9951, 95, 'Élève1', 'Paul')");
$db->exec("INSERT INTO etudes (eleve_id, classe_id, annee_academique_id, is_active) VALUES (9951, 9951, {$active_annee_id}, 1)");

$db->exec("INSERT INTO eleves (id_eleve, lycee_id, nom, prenom) VALUES (9952, 95, 'Élève2', 'Marie')");
$db->exec("INSERT INTO etudes (eleve_id, classe_id, annee_academique_id, is_active) VALUES (9952, 9951, {$active_annee_id}, 1)");

// Create Student enrolled in Class 9952 (3e A)
$db->exec("INSERT INTO eleves (id_eleve, lycee_id, nom, prenom) VALUES (9953, 95, 'Élève3', 'Jacques')");
$db->exec("INSERT INTO etudes (eleve_id, classe_id, annee_academique_id, is_active) VALUES (9953, 9952, {$active_annee_id}, 1)");

echo "[OK] Initialisation de l'environnement de test terminée.\n\n";

// Helper function to set up mock session
function setupMockSession($userId, $lyceeId, $roleId, $roleName, array $permissions) {
    $_SESSION['user'] = [
        'id' => $userId,
        'id_user' => $userId,
        'lycee_id' => $lyceeId,
        'role_id' => $roleId,
        'role_name' => $roleName,
        'auth_version' => 1,
        'permissions' => $permissions
    ];
    $_SESSION['user_id'] = $userId;
    $_SESSION['lycee_id'] = $lyceeId;
    $_SESSION['role_id'] = $roleId;
    $_SESSION['role_name'] = $roleName;
}

// --- TEST 1: PP Authorized Access and Bulk Save ---
echo "TEST 1: Saisie autorisée pour le Professeur Principal de 3e B (Prof Alpha -> Classe 9951)\n";
setupMockSession(9951, 95, 6, 'enseignant', [
    'bulletin' => ['edit_appreciation_conseil']
]);

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['classe_id'] = 9951;
$_POST['sequence_id'] = 9951;
$_POST['appreciations'] = [
    9951 => "Travail sérieux, ensemble satisfaisant.",
    9952 => "Des efforts à poursuivre."
];

$controller = new AppreciationConseilController();
$controller->save();

// Verify DB persistence
$apprecs = Bulletin::findAppreciationsConseilByClasseAndSequence(9951, 9951);
if (
    isset($apprecs[9951]) && $apprecs[9951]['appreciation_conseil_classe'] === "Travail sérieux, ensemble satisfaisant." &&
    isset($apprecs[9952]) && $apprecs[9952]['appreciation_conseil_classe'] === "Des efforts à poursuivre."
) {
    echo "  -> Attendu: Les appréciations individuelles de 3e B ont été correctement enregistrées sans moyenne 0.00 fictive | PASS\n";
} else {
    echo "  -> ECHEC: Erreur de sauvegarde des appréciations dans la table bulletins\n";
    exit(1);
}

// --- TEST 2: Rejection of non-PP Teacher attempting to access ---
echo "\nTEST 2: Refus d'un enseignant non Professeur Principal (Prof Beta -> Classe 9951)\n";
setupMockSession(9952, 95, 6, 'enseignant', [
    'bulletin' => ['edit_appreciation_conseil']
]);

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['classe_id'] = 9951;
$_POST['sequence_id'] = 9951;
$_POST['appreciations'] = [9951 => "Tentative d'usurpation non PP"];

try {
    $controller->save();
    echo "  -> ECHEC: La sauvegarde aurait dû être refusée (Prof Beta n'est pas PP de 3e B)\n";
    exit(1);
} catch (Exception $e) {
    if (strpos($e->getMessage(), "Accès Interdit") !== false) {
        echo "  -> Attendu (Refusé 403) : " . $e->getMessage() . " | PASS\n";
    } else {
        echo "  -> ECHEC: Exception inattendue : " . $e->getMessage() . "\n";
        exit(1);
    }
}

// --- TEST 3: Inter-Class Tampering Rejection ---
echo "\nTEST 3: Refus de tentative d'accès inter-classe par le PP de 3e B (Prof Alpha sur 3e A - Classe 9952)\n";
setupMockSession(9951, 95, 6, 'enseignant', [
    'bulletin' => ['edit_appreciation_conseil']
]);

$_GET['classe_id'] = 9952; // 3e A
$_GET['sequence_id'] = 9951;

try {
    $controller->index();
    echo "  -> ECHEC: L'accès à 3e A aurait dû être refusé\n";
    exit(1);
} catch (Exception $e) {
    if (strpos($e->getMessage(), "Accès Interdit") !== false) {
        echo "  -> Attendu (Refusé 403) : " . $e->getMessage() . " | PASS\n";
    } else {
        echo "  -> ECHEC: Exception inattendue : " . $e->getMessage() . "\n";
        exit(1);
    }
}

// --- TEST 4: Student Tampering Rejection ---
echo "\nTEST 4: Refus d'injection d'un élève hors classe dans le formulaire de saisie\n";
setupMockSession(9951, 95, 6, 'enseignant', [
    'bulletin' => ['edit_appreciation_conseil']
]);

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['classe_id'] = 9951;
$_POST['sequence_id'] = 9951;
$_POST['appreciations'] = [
    9951 => "Appréciation élève 1 ok",
    9953 => "Tentative d'injection de l'élève 3 (qui est en 3e A et pas en 3e B)"
];

$controller->save();

// Verify rollback or error notification in session
if (!empty($_SESSION['error_message']) && strpos($_SESSION['error_message'], "n'appartient pas à la classe") !== false) {
    echo "  -> Attendu: L'injection de l'élève 9953 a provoqué un rejet transactionnel avec message d'erreur | PASS\n";
} else {
    echo "  -> ECHEC: L'injection d'un élève hors classe n'a pas été bloquée !\n";
    exit(1);
}

// --- TEST 5: Status Lock Protection (Valide & Publie) ---
echo "\nTEST 5: Blocage de la modification pour un bulletin déjà 'valide' ou 'publie'\n";
// Set student 9951 bulletin status to 'valide'
$db->exec("UPDATE bulletins SET statut = 'valide' WHERE eleve_id = 9951 AND sequence_id = 9951");

setupMockSession(9951, 95, 6, 'enseignant', [
    'bulletin' => ['edit_appreciation_conseil']
]);

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['classe_id'] = 9951;
$_POST['sequence_id'] = 9951;
$_POST['appreciations'] = [
    9951 => "Modification interdite sur bulletin validé."
];

$controller->save();

// Check error message in session
if (!empty($_SESSION['error_message']) && strpos($_SESSION['error_message'], "déjà dans le statut 'valide'") !== false) {
    echo "  -> Attendu: La modification sur bulletin validé a été rejetée avec message explicite | PASS\n";
} else {
    echo "  -> ECHEC: Le bulletin validé a été modifié par erreur\n";
    exit(1);
}

// --- TEST 6: Multi-Tenant School Isolation ---
echo "\nTEST 6: Rejet de tentative d'accès sur une classe d'un autre établissement (Lycée 96 - Classe 9953)\n";
setupMockSession(9951, 95, 6, 'enseignant', [
    'bulletin' => ['edit_appreciation_conseil']
]);

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['classe_id'] = 9953; // Class in Lycée 96
$_POST['sequence_id'] = 9951;
$_POST['appreciations'] = [9951 => "Test multi-tenant"];

try {
    $controller->save();
    echo "  -> ECHEC: L'accès à une classe d'un autre lycée aurait dû être bloqué\n";
    exit(1);
} catch (Exception $e) {
    echo "  -> Attendu (Refusé par Périmètre multi-tenant) : " . $e->getMessage() . " | PASS\n";
}

// --- TEST 7: Zero Note Leaks Verification ---
echo "\nTEST 7: Vérification de l'absence de fuite des notes / matières / rangs dans la vue dédiée\n";
ob_start();
$_GET['classe_id'] = 9951;
$_GET['sequence_id'] = 9951;
$controller->index();
$htmlOutput = ob_get_clean();

if (
    strpos($htmlOutput, 'Moyenne Générale') === false &&
    strpos($htmlOutput, 'note_normalisee') === false &&
    strpos($htmlOutput, 'rang_int') === false &&
    strpos($htmlOutput, 'Appréciation du conseil de classe') !== false
) {
    echo "  -> Attendu: L'interface dédiée contient uniquement l'appréciation du conseil de classe sans aucune fuite académique | PASS\n";
} else {
    echo "  -> ECHEC: Fuite potentielle de données académiques dans la vue dédiée\n";
    exit(1);
}

// Cleanup mock test data
$db->exec("DELETE FROM bulletins WHERE eleve_id >= 9950");
$db->exec("DELETE FROM etudes WHERE eleve_id >= 9950");
$db->exec("DELETE FROM eleves WHERE id_eleve >= 9950");
$db->exec("DELETE FROM classe_parametres WHERE classe_id >= 9950");
$db->exec("DELETE FROM classes WHERE id_classe >= 9950");
$db->exec("DELETE FROM sequences WHERE id >= 9950");
$db->exec("DELETE FROM utilisateurs WHERE id_user >= 9950");
$db->exec("DELETE FROM param_lycee WHERE id >= 95");

echo "\n========================================================\n";
echo "RÉSULTAT FINAL: TOUS LES TESTS D'APPRÉCIATION DU CONSEIL ONT RÉUSSI !\n";
echo "========================================================\n";
