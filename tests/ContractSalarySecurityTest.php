<?php
// tests/ContractSalarySecurityTest.php

if (!defined('TEST_MODE')) {
    define('TEST_MODE', true);
}
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/services/PersonnelService.php';
require_once __DIR__ . '/../src/services/PersonnelContractService.php';
require_once __DIR__ . '/../src/services/PaieWorkflowService.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/ParamGeneral.php';

function assertSalaryTest($condition, $message) {
    if ($condition) {
        echo " [PASS] $message\n";
    } else {
        echo " [FAIL] $message\n";
        throw new Exception("Test failed: $message");
    }
}

echo "=== DÉMARRAGE DU SUITE DE TEST DE SÉCURITÉ ET COHÉRENCE SALARIALE (MATRICE COMPLÈTE) ===\n";

$db = Database::getInstance();

// Clean test records
$db->exec("DELETE FROM paie_audit_logs");
$db->exec("DELETE FROM paie_bulletins");
$db->exec("DELETE FROM paie_cahier_texte_validations WHERE cahier_id >= 9000 OR enseignant_id IN (SELECT id_user FROM utilisateurs WHERE email LIKE '%sal_test%')");
$db->exec("DELETE FROM cahier_texte WHERE cahier_id >= 9000 OR personnel_id IN (SELECT id_user FROM utilisateurs WHERE email LIKE '%sal_test%')");
$db->exec("DELETE FROM paie_periodes");
$db->exec("DELETE FROM personnel_contrat_composants");
$db->exec("DELETE FROM personnel_contrats_historique WHERE personnel_id IN (SELECT id_user FROM utilisateurs WHERE email LIKE '%sal_test%')");
$db->exec("DELETE FROM personnel_details WHERE personnel_id IN (SELECT id_user FROM utilisateurs WHERE email LIKE '%sal_test%')");
$db->exec("DELETE FROM utilisateurs WHERE email LIKE '%sal_test%'");

// Ensure type_contrat exists
$stmt_tc = $db->query("SELECT id_contrat FROM type_contrat LIMIT 1");
$tc_id = (int)$stmt_tc->fetchColumn();
if (!$tc_id) {
    $db->exec("INSERT INTO type_contrat (libelle, type_paiement, prise_en_charge) VALUES ('CDI Test', 'mensuel', 'Ecole')");
    $tc_id = (int)$db->lastInsertId();
}

// ---------------------------------------------------------
// SECTION A: TEST DE LA SÉPARATION DES PERMISSIONS FINANCIÈRES
// ---------------------------------------------------------
echo "\n--- SECTION A: PERMISSIONS FINANCIÈRES DÉCOUPÉES ---\n";

// A.1 view_sensitive seul : Consultation autorisée, modification financière refusée
$_SESSION['user'] = [
    'id' => 101,
    'lycee_id' => 1,
    'role_id' => null,
    'permissions' => ['drh' => ['view_sensitive', 'manage_contrats', 'edit']] // NO manage_salaires
];

$pA1 = PersonnelService::savePersonnel([
    'nom' => 'ViewSensitiveOnly',
    'prenom' => 'User',
    'email' => 'sal_test_a1@test.com',
    'mot_de_passe' => 'Pass123!',
    'role_id' => 6,
    'lycee_id' => 1,
    'contrat_id' => $tc_id,
    'date_embauche' => '2024-01-01',
    'salaire_base' => 300000.00, // Fraudulent input from user with only view_sensitive
    'mode_calcul_principal' => 'forfait_fixe'
], 101);

$cA1 = PersonnelContractService::getActiveContract($pA1);
assertSalaryTest((float)$cA1['salaire_base'] === 0.00, "A.1: Utilisateur 'view_sensitive' seul -> Saisie de salaire refusée (salaire initial = 0.00)");

// A.2 manage_salaires seul : Modification financière autorisée
$_SESSION['user'] = [
    'id' => 102,
    'lycee_id' => 1,
    'role_id' => null,
    'permissions' => ['drh' => ['manage_salaires']] // NO view_sensitive, NO manage_contrats
];

PersonnelContractService::saveContract([
    'personnel_id' => $pA1,
    'type_contrat_id' => $tc_id,
    'date_debut' => '2024-02-01',
    'salaire_base' => 350000.00,
    'mode_calcul_principal' => 'forfait_fixe',
    'unite_remuneration' => 'mois',
    'periodicite_paiement' => 'mensuel',
    'statut_contrat' => 'actif'
], 102);

$cA2 = PersonnelContractService::getActiveContract($pA1);
assertSalaryTest((float)$cA2['salaire_base'] === 350000.00, "A.2: Utilisateur 'manage_salaires' seul -> Modification financière autorisée (350 000.00 FCFA)");

// A.3 manage_contrats seul : Modification administrative autorisée, données financières préservées
$_SESSION['user'] = [
    'id' => 103,
    'lycee_id' => 1,
    'role_id' => null,
    'permissions' => ['drh' => ['manage_contrats', 'edit']] // NO view_sensitive, NO manage_salaires
];

PersonnelContractService::saveContract([
    'id' => $cA2['id'],
    'personnel_id' => $pA1,
    'type_contrat_id' => $tc_id,
    'date_debut' => '2024-02-01',
    'date_fin' => '2025-12-31',
    'salaire_base' => 100.00, // Fraudulent input
    'mode_calcul_principal' => 'taux_horaire', // Fraudulent input
    'statut_contrat' => 'actif'
], 103);

$cA3 = PersonnelContractService::getActiveContract($pA1);
assertSalaryTest((float)$cA3['salaire_base'] === 350000.00, "A.3: Utilisateur 'manage_contrats' seul -> Salaire préservé à 350 000.00 FCFA (100.00 FCFA ignoré)");
assertSalaryTest($cA3['mode_calcul_principal'] === 'forfait_fixe', "A.3: Mode de calcul préservé à 'forfait_fixe'");
assertSalaryTest($cA3['date_fin'] === '2025-12-31', "A.3: Date de fin administrative correctement mise à jour");

// A.4 view_sensitive + manage_contrats (SANS manage_salaires)
$_SESSION['user'] = [
    'id' => 104,
    'lycee_id' => 1,
    'role_id' => null,
    'permissions' => ['drh' => ['view_sensitive', 'manage_contrats', 'edit']] // NO manage_salaires
];

PersonnelContractService::saveContract([
    'id' => $cA3['id'],
    'personnel_id' => $pA1,
    'type_contrat_id' => $tc_id,
    'date_debut' => '2024-02-01',
    'date_fin' => '2026-06-30',
    'salaire_base' => 1000.00, // Fraudulent input
    'statut_contrat' => 'actif'
], 104);

$cA4 = PersonnelContractService::getActiveContract($pA1);
assertSalaryTest((float)$cA4['salaire_base'] === 350000.00, "A.4: 'view_sensitive' + 'manage_contrats' sans 'manage_salaires' -> Aucune écriture financière autorisée (salaire 350 000.00 FCFA préservé)");

// A.5 Aucune permission financière
$_SESSION['user'] = [
    'id' => 105,
    'lycee_id' => 1,
    'role_id' => null,
    'permissions' => ['drh' => ['edit']] // ZERO contract / salary permissions
];

$pA5 = PersonnelService::savePersonnel([
    'nom' => 'NoPerms',
    'prenom' => 'User',
    'email' => 'sal_test_a5@test.com',
    'mot_de_passe' => 'Pass123!',
    'role_id' => 6,
    'lycee_id' => 1,
    'contrat_id' => $tc_id,
    'date_embauche' => '2024-01-01',
    'salaire_base' => 999999.00
], 105);

$cA5 = PersonnelContractService::getActiveContract($pA5);
assertSalaryTest((float)$cA5['salaire_base'] === 0.00, "A.5: Aucune permission financière -> Contrat créé sans salaire (0.00 FCFA)");

// ---------------------------------------------------------
// SECTION B: TEST DES MODES DE RÉMUNÉRATION & ÉLIGIBILITÉ PAIE
// ---------------------------------------------------------
echo "\n--- SECTION B: MODES DE RÉMUNÉRATION ET ÉLIGIBILITÉ PAIE ---\n";

$_SESSION['user'] = [
    'id' => 1,
    'lycee_id' => 1,
    'role_id' => null,
    'permissions' => ['drh' => ['view_sensitive', 'manage_salaires', 'manage_contrats', 'create', 'edit']]
];

// B.1 Forfait fixe renseigné -> Éligible
$pB1 = PersonnelService::savePersonnel([
    'nom' => 'FixeOk',
    'prenom' => 'User',
    'email' => 'sal_test_b1@test.com',
    'mot_de_passe' => 'Pass123!',
    'role_id' => 6,
    'lycee_id' => 1,
    'contrat_id' => $tc_id,
    'date_embauche' => '2024-01-01',
    'salaire_base' => 400000.00,
    'mode_calcul_principal' => 'forfait_fixe'
], 1);

// B.2 Forfait fixe sans salaire ni composant -> Exclu
$pB2 = PersonnelService::savePersonnel([
    'nom' => 'FixeZero',
    'prenom' => 'User',
    'email' => 'sal_test_b2@test.com',
    'mot_de_passe' => 'Pass123!',
    'role_id' => 6,
    'lycee_id' => 1,
    'contrat_id' => $tc_id,
    'date_embauche' => '2024-01-01',
    'salaire_base' => 0.00,
    'mode_calcul_principal' => 'forfait_fixe'
], 1);

// B.3 Taux horaire configuré -> Éligible
$pB3 = PersonnelService::savePersonnel([
    'nom' => 'HoraireOk',
    'prenom' => 'User',
    'email' => 'sal_test_b3@test.com',
    'mot_de_passe' => 'Pass123!',
    'role_id' => 6,
    'lycee_id' => 1,
    'contrat_id' => $tc_id,
    'date_embauche' => '2024-01-01',
    'salaire_base' => 5000.00,
    'mode_calcul_principal' => 'taux_horaire',
    'volume_horaire_mensuel' => 100.00
], 1);

// B.5 Taux journalier configuré -> Éligible
$pB5 = PersonnelService::savePersonnel([
    'nom' => 'JournalierOk',
    'prenom' => 'User',
    'email' => 'sal_test_b5@test.com',
    'mot_de_passe' => 'Pass123!',
    'role_id' => 6,
    'lycee_id' => 1,
    'contrat_id' => $tc_id,
    'date_embauche' => '2024-01-01',
    'salaire_base' => 20000.00,
    'mode_calcul_principal' => 'taux_journalier'
], 1);

// B.6 Taux journalier incomplet (0 FCFA, pas de composant) -> Exclu
$pB6 = PersonnelService::savePersonnel([
    'nom' => 'JournalierZero',
    'prenom' => 'User',
    'email' => 'sal_test_b6@test.com',
    'mot_de_passe' => 'Pass123!',
    'role_id' => 6,
    'lycee_id' => 1,
    'contrat_id' => $tc_id,
    'date_embauche' => '2024-01-01',
    'salaire_base' => 0.00,
    'mode_calcul_principal' => 'taux_journalier'
], 1);

// B.7 Commission configurée -> Éligible
$pB7 = PersonnelService::savePersonnel([
    'nom' => 'CommissionOk',
    'prenom' => 'User',
    'email' => 'sal_test_b7@test.com',
    'mot_de_passe' => 'Pass123!',
    'role_id' => 6,
    'lycee_id' => 1,
    'contrat_id' => $tc_id,
    'date_embauche' => '2024-01-01',
    'salaire_base' => 0.00,
    'mode_calcul_principal' => 'commission'
], 1);

// B.9 Contrat avec primes et indemnités (0 FCFA fixe mais prime > 0) -> Éligible
$pB9 = PersonnelService::savePersonnel([
    'nom' => 'PrimesOk',
    'prenom' => 'User',
    'email' => 'sal_test_b9@test.com',
    'mot_de_passe' => 'Pass123!',
    'role_id' => 6,
    'lycee_id' => 1,
    'contrat_id' => $tc_id,
    'date_embauche' => '2024-01-01',
    'salaire_base' => 0.00,
    'mode_calcul_principal' => 'forfait_fixe'
], 1);

$cB9 = PersonnelContractService::getActiveContract($pB9);
PersonnelContractService::saveContract([
    'id' => $cB9['id'],
    'personnel_id' => $pB9,
    'type_contrat_id' => $tc_id,
    'date_debut' => '2024-01-01',
    'salaire_base' => 0.00,
    'mode_calcul_principal' => 'forfait_fixe',
    'composants' => [
        [
            'code_composant' => 'PRIME_LOGEMENT',
            'libelle' => 'Prime de logement',
            'nature_composant' => 'prime',
            'valeur_numerique' => 75000.00
        ]
    ],
    'statut_contrat' => 'actif'
], 1);

// Create test period for year 2025 month 3
$db->exec("INSERT INTO comptabilite_periodes (id, lycee_id, exercice_financier_id, date_debut, date_fin, est_cloturee) VALUES (97, 1, 1, '2025-03-01', '2025-03-31', 0) ON CONFLICT DO NOTHING");
$periodId2 = PaieWorkflowService::createPeriod(1, 97, 'PAIE-TEST-SAL-2025-03', 3, 2025, '2025-03-01', '2025-03-31', 1);

$eligibleContracts = PersonnelContractService::getEligibleContractsForPeriod($periodId2, 1);
$eligiblePids = array_map(function($c) { return (int)$c['personnel_id']; }, $eligibleContracts);

assertSalaryTest(in_array($pB1, $eligiblePids, true), "B.1: Forfait fixe (400 000 FCFA) -> ÉLIGIBLE");
assertSalaryTest(!in_array($pB2, $eligiblePids, true), "B.2: Forfait fixe (0 FCFA) -> EXCLU");
assertSalaryTest(in_array($pB3, $eligiblePids, true), "B.3: Taux horaire (5 000 FCFA/h) -> ÉLIGIBLE");
assertSalaryTest(in_array($pB5, $eligiblePids, true), "B.5: Taux journalier (20 000 FCFA/j) -> ÉLIGIBLE");
assertSalaryTest(!in_array($pB6, $eligiblePids, true), "B.6: Taux journalier incomplet (0 FCFA, pas de composant) -> STRICTEMENT EXCLU (pas de bulletin à 0 FCFA)");
assertSalaryTest(in_array($pB7, $eligiblePids, true), "B.7: Mode commission -> ÉLIGIBLE");
assertSalaryTest(in_array($pB9, $eligiblePids, true), "B.9: Contrat 0 FCFA fixe avec Prime de Logement (75 000 FCFA) -> ÉLIGIBLE via composant");

// ---------------------------------------------------------
// SECTION C: VÉRIFICATION DU SEEDER db/seeds.sql
// ---------------------------------------------------------
echo "\n--- SECTION C: VÉRIFICATION DU SEEDER db/seeds.sql ---\n";

$seedsContent = file_get_contents(__DIR__ . '/../db/seeds.sql');

$hasPermDef = strpos($seedsContent, "'drh', 'manage_salaires'") !== false;
assertSalaryTest($hasPermDef, "C.1: Permission 'drh:manage_salaires' présente dans le catalogue db/seeds.sql");

$occurrences = substr_count($seedsContent, "'drh', 'manage_salaires'");
assertSalaryTest($occurrences === 1, "C.2: Aucune duplication de la permission 'drh:manage_salaires' (1 occurrence unique)");

$hasRoleMapping = strpos($seedsContent, "(9, 187)") !== false;
assertSalaryTest($hasRoleMapping, "C.3: Attribution contrôlée de la permission manage_salaires aux rôles habilités");

// ---------------------------------------------------------
// SECTION D: CONTRÔLE DES CONTRATS À TAUX HORAIRE INCOMPLETS ET BULLETINS À ZÉRO
// ---------------------------------------------------------
echo "\n--- SECTION D: TESTS DÉDIÉS AUX CONTRATS À TAUX HORAIRE & PRÉVENTION DES BULLETINS À ZÉRO ---\n";

// D.1 Taux horaire positif (5000 FCFA/h) + 2 séances validées (3h + 2h = 5h) -> Calcul normal
$pD1 = PersonnelService::savePersonnel([
    'nom' => 'HoraireOkSessions',
    'prenom' => 'User',
    'email' => 'sal_test_d1@test.com',
    'mot_de_passe' => 'Pass123!',
    'role_id' => 6,
    'lycee_id' => 1,
    'contrat_id' => $tc_id,
    'date_embauche' => '2024-01-01',
    'salaire_base' => 5000.00,
    'mode_calcul_principal' => 'taux_horaire',
    'unite_remuneration' => 'heure'
], 1);

$cD1 = PersonnelContractService::getActiveContract($pD1);

$db->exec("INSERT INTO cahier_texte (cahier_id, lycee_id, personnel_id, classe_id, matiere_id, date_cours, heure_debut, heure_fin, contenu_cours) VALUES (9101, 1, {$pD1}, 1, 1, '2025-03-05', '08:00', '11:00', 'Cour D1-1')");
$db->exec("INSERT INTO cahier_texte (cahier_id, lycee_id, personnel_id, classe_id, matiere_id, date_cours, heure_debut, heure_fin, contenu_cours) VALUES (9102, 1, {$pD1}, 1, 1, '2025-03-10', '14:00', '16:00', 'Cour D1-2')");
PaieCahierTexteValidation::validateSession(9101, 1, 5000.00);
PaieCahierTexteValidation::validateSession(9102, 1, 5000.00);

$bD1Id = PaieWorkflowService::generateBulletinForEmployee($periodId2, $pD1, (int)$cD1['id'], 1);
$bD1 = PaieBulletin::findById($bD1Id);
assertSalaryTest((float)$bD1['total_brut'] == 25000.00, "D.1: Taux horaire 5000 FCFA/h + 5h validées -> Bulletin généré à 25 000.00 FCFA");

// D.2 Taux horaire positif (5000 FCFA/h) + AUCUNE séance validée -> Bloqué par LogicException (pas de bulletin à 0 FCFA)
$pD2 = PersonnelService::savePersonnel([
    'nom' => 'HoraireOkNoSessions',
    'prenom' => 'User',
    'email' => 'sal_test_d2@test.com',
    'mot_de_passe' => 'Pass123!',
    'role_id' => 6,
    'lycee_id' => 1,
    'contrat_id' => $tc_id,
    'date_embauche' => '2024-01-01',
    'salaire_base' => 5000.00,
    'mode_calcul_principal' => 'taux_horaire',
    'unite_remuneration' => 'heure'
], 1);

$cD2 = PersonnelContractService::getActiveContract($pD2);
$d2Blocked = false;
try {
    PaieWorkflowService::generateBulletinForEmployee($periodId2, $pD2, (int)$cD2['id'], 1);
} catch (LogicException $e) {
    $d2Blocked = true;
}
assertSalaryTest($d2Blocked, "D.2: Taux horaire positif SANS séance validée -> Génération directe BLOQUÉE (LogicException)");

// D.3 Taux horaire nul (0 FCFA) + Séances validées SANS taux (0 FCFA) -> Bloqué par InvalidArgumentException
$pD3 = PersonnelService::savePersonnel([
    'nom' => 'HoraireZeroWithSessions',
    'prenom' => 'User',
    'email' => 'sal_test_d3@test.com',
    'mot_de_passe' => 'Pass123!',
    'role_id' => 6,
    'lycee_id' => 1,
    'contrat_id' => $tc_id,
    'date_embauche' => '2024-01-01',
    'salaire_base' => 0.00,
    'mode_calcul_principal' => 'taux_horaire'
], 1);

$cD3 = PersonnelContractService::getActiveContract($pD3);
$db->exec("INSERT INTO cahier_texte (cahier_id, lycee_id, personnel_id, classe_id, matiere_id, date_cours, heure_debut, heure_fin, contenu_cours) VALUES (9103, 1, {$pD3}, 1, 1, '2025-03-12', '08:00', '10:00', 'Cour D3')");
PaieCahierTexteValidation::validateSession(9103, 1, 0.00); // 0.00 rate

$d3Blocked = false;
try {
    PaieWorkflowService::generateBulletinForEmployee($periodId2, $pD3, (int)$cD3['id'], 1);
} catch (InvalidArgumentException $e) {
    $d3Blocked = true;
}
assertSalaryTest($d3Blocked, "D.3: Taux horaire nul (0 FCFA) avec séances à taux nul -> Bloqué par InvalidArgumentException (Contrat incomplet)");

// D.4 Taux horaire nul (0 FCFA) + AUCUNE séance validée -> Bloqué par InvalidArgumentException
$pD4 = PersonnelService::savePersonnel([
    'nom' => 'HoraireZeroNoSessions',
    'prenom' => 'User',
    'email' => 'sal_test_d4@test.com',
    'mot_de_passe' => 'Pass123!',
    'role_id' => 6,
    'lycee_id' => 1,
    'contrat_id' => $tc_id,
    'date_embauche' => '2024-01-01',
    'salaire_base' => 0.00,
    'mode_calcul_principal' => 'taux_horaire'
], 1);

$cD4 = PersonnelContractService::getActiveContract($pD4);
$d4Blocked = false;
try {
    PaieWorkflowService::generateBulletinForEmployee($periodId2, $pD4, (int)$cD4['id'], 1);
} catch (InvalidArgumentException $e) {
    $d4Blocked = true;
}
assertSalaryTest($d4Blocked, "D.4: Taux horaire nul (0 FCFA) sans séance -> Bloqué par InvalidArgumentException (Contrat incomplet)");

// D.5 Contrat horaire SANS séance + Prime de logement -> BLOQUÉ (les primes ne remplacent pas le service fait)
$pD5 = PersonnelService::savePersonnel([
    'nom' => 'HorairePrimeLogement',
    'prenom' => 'User',
    'email' => 'sal_test_d5@test.com',
    'mot_de_passe' => 'Pass123!',
    'role_id' => 6,
    'lycee_id' => 1,
    'contrat_id' => $tc_id,
    'date_embauche' => '2024-01-01',
    'salaire_base' => 5000.00,
    'mode_calcul_principal' => 'taux_horaire'
], 1);

$cD5 = PersonnelContractService::getActiveContract($pD5);
PersonnelContractService::saveContract([
    'id' => $cD5['id'],
    'personnel_id' => $pD5,
    'type_contrat_id' => $tc_id,
    'date_debut' => '2024-01-01',
    'salaire_base' => 5000.00,
    'mode_calcul_principal' => 'taux_horaire',
    'composants' => [
        [
            'code_composant' => 'PRIME_LOGEMENT',
            'libelle' => 'Prime de logement',
            'nature_composant' => 'prime',
            'valeur_numerique' => 50000.00
        ]
    ],
    'statut_contrat' => 'actif'
], 1);

$d5Blocked = false;
try {
    PaieWorkflowService::generateBulletinForEmployee($periodId2, $pD5, (int)$cD5['id'], 1);
} catch (LogicException $e) {
    $d5Blocked = true;
}
assertSalaryTest($d5Blocked, "D.5: Contrat horaire SANS séance + Prime de logement -> Génération BLOQUÉE (la prime ne contourne pas le service fait)");

// D.6 Taux contractuel nul (0 FCFA) + Séance validée avec taux positif propre (6000 FCFA/h) -> Calcul fonctionnel
$pD6 = PersonnelService::savePersonnel([
    'nom' => 'HoraireZeroSessionRate',
    'prenom' => 'User',
    'email' => 'sal_test_d6@test.com',
    'mot_de_passe' => 'Pass123!',
    'role_id' => 6,
    'lycee_id' => 1,
    'contrat_id' => $tc_id,
    'date_embauche' => '2024-01-01',
    'salaire_base' => 0.00,
    'mode_calcul_principal' => 'taux_horaire'
], 1);

$cD6 = PersonnelContractService::getActiveContract($pD6);
$db->exec("INSERT INTO cahier_texte (cahier_id, lycee_id, personnel_id, classe_id, matiere_id, date_cours, heure_debut, heure_fin, contenu_cours) VALUES (9106, 1, {$pD6}, 1, 1, '2025-03-15', '08:00', '10:00', 'Cour D6')");
PaieCahierTexteValidation::validateSession(9106, 1, 6000.00); // Taux séance = 6000.00 FCFA

$bD6Id = PaieWorkflowService::generateBulletinForEmployee($periodId2, $pD6, (int)$cD6['id'], 1);
$bD6 = PaieBulletin::findById($bD6Id);
assertSalaryTest((float)$bD6['total_brut'] == 12000.00, "D.6: Taux contractuel 0 FCFA + Séance à 6000 FCFA/h (2h) -> Bulletin généré à 12 000.00 FCFA");

// D.7 Enseignant multi-contrats (Contrat Collège vs Contrat Lycée) -> Isolation stricte des séances par cycle
$pD7 = PersonnelService::savePersonnel([
    'nom' => 'MultiContract',
    'prenom' => 'Teacher',
    'email' => 'sal_test_d7@test.com',
    'mot_de_passe' => 'Pass123!',
    'role_id' => 6,
    'lycee_id' => 1,
    'contrat_id' => $tc_id,
    'date_embauche' => '2024-01-01',
    'salaire_base' => 4000.00,
    'mode_calcul_principal' => 'taux_horaire'
], 1);

// Ensure Collège and Lycée cycles exist
$db->exec("INSERT INTO cycles (id_cycle, lycee_id, nom_cycle, niveau_debut, niveau_fin) VALUES (88, 1, 'CEG_D7', '6e', '3e') ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO cycles (id_cycle, lycee_id, nom_cycle, niveau_debut, niveau_fin) VALUES (89, 1, 'LYC_D7', '2nd', 'Terminale') ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO classes (id_classe, niveau, serie, numero, lycee_id, cycle_id) VALUES (881, '6eme', 'A', '1', 1, 88) ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO classes (id_classe, niveau, serie, numero, lycee_id, cycle_id) VALUES (891, '2nde', 'C', '1', 1, 89) ON CONFLICT DO NOTHING");

// Contract 1: Collège (cycle_id = 88)
$cD7_College = PersonnelContractService::getActiveContract($pD7);
$cD7_College['cycle_id'] = 88;

// Contract 2: Lycée (cycle_id = 89)
$cD7_Lycee = $cD7_College;
$cD7_Lycee['cycle_id'] = 89;

// Add validated session ONLY in Collège class (id_classe = 881, cycle_id = 88)
$db->exec("INSERT INTO cahier_texte (cahier_id, lycee_id, personnel_id, classe_id, matiere_id, date_cours, heure_debut, heure_fin, contenu_cours) VALUES (9107, 1, {$pD7}, 881, 1, '2025-03-20', '08:00', '10:00', 'Cour Collège')");
PaieCahierTexteValidation::validateSession(9107, 1, 4000.00);

// Query Collège sessions (cycle_id = 88) -> Should find 1 session
$collegeSessions = PaieCahierTexteValidation::findValidatedForTeacherAndDates($pD7, '2025-03-01', '2025-03-31', 88);
assertSalaryTest(count($collegeSessions) === 1, "D.7a: 1 séance Collège trouvée pour le périmètre Collège (cycle_id = 88)");

// Query Lycée sessions (cycle_id = 89) -> Should find 0 sessions (Collège session isolated)
$lyceeSessions = PaieCahierTexteValidation::findValidatedForTeacherAndDates($pD7, '2025-03-01', '2025-03-31', 89);
assertSalaryTest(count($lyceeSessions) === 0, "D.7b: 0 séance Lycée trouvée pour le périmètre Lycée (cycle_id = 89, fuite évitée)");

// D.8 Préservation des bulletins et snapshots historiques
$bHistCount = $db->query("SELECT COUNT(*) FROM paie_bulletin_contrat_snapshot")->fetchColumn();
assertSalaryTest((int)$bHistCount > 0, "D.8: Préservation des snapshots contractuels historiques vérifiée en BDD ({$bHistCount} enregistrés)");

echo "\n=========================================================================\n";
echo "🏆 TOUS LES TESTS DE SÉCURITÉ ET COHÉRENCE SALARIALE ONT RÉUSSI (100% GREEN) !\n";
echo "=========================================================================\n";
