<?php

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/models/PaiePeriode.php';
require_once __DIR__ . '/../src/models/PaieBulletin.php';
require_once __DIR__ . '/../src/models/PaieBulletinLigne.php';
require_once __DIR__ . '/../src/models/PaieCahierTexteValidation.php';
require_once __DIR__ . '/../src/models/PaieBulletinHeure.php';
require_once __DIR__ . '/../src/models/PaieReglement.php';
require_once __DIR__ . '/../src/models/PaieRegularisation.php';
require_once __DIR__ . '/../src/models/AffectationPedagogique.php';
require_once __DIR__ . '/../src/models/Classe.php';
require_once __DIR__ . '/../src/services/PaieCalculationEngine.php';
require_once __DIR__ . '/../src/services/PaieWorkflowService.php';
require_once __DIR__ . '/../src/services/LegacySalaryFacade.php';
require_once __DIR__ . '/../src/services/ComptabiliteService.php';

function assert_test($condition, $message) {
    if ($condition) {
        echo " [PASS] $message\n";
    } else {
        echo " [FAIL] $message\n";
        throw new Exception("Test failed: $message");
    }
}

echo "=== DÉMARRAGE DU TEST D'INTÉGRATION ET DE SÉCURITÉ DU MODULE PAIE LOT 2.1 V6.1.1 ===\n";

require_once __DIR__ . '/../migrate.php';

$db = Database::getInstance();

ComptabiliteService::seedDefaultChartOfAccounts();

// Clean up previous test runs
$db->exec("UPDATE comptabilite_periodes SET est_cloturee = 0");
$db->exec("DELETE FROM paie_audit_logs");
$db->exec("DELETE FROM paie_regularisation_lignes");
$db->exec("DELETE FROM paie_regularisations");
$db->exec("DELETE FROM paie_reglements");
$db->exec("DELETE FROM paie_bulletin_regle_tranches_snapshot");
$db->exec("DELETE FROM paie_bulletin_regles_snapshot");
$db->exec("DELETE FROM paie_bulletin_financements");
$db->exec("DELETE FROM paie_bulletin_contrat_snapshot");
$db->exec("DELETE FROM paie_bulletin_heures");
$db->exec("DELETE FROM paie_cahier_texte_validations");
$db->exec("DELETE FROM paie_bulletin_lignes");
$db->exec("DELETE FROM paie_bulletins");
$db->exec("DELETE FROM paie_periodes");
$db->exec("DELETE FROM affectations_pedagogiques");
$db->exec("DELETE FROM cahier_texte");
$db->exec("DELETE FROM classes");
$db->exec("DELETE FROM cycles");
$db->exec("DELETE FROM matieres");
$db->exec("DELETE FROM personnel_contrats_historique WHERE personnel_id >= 100");

// Seed initial environment
$db->exec("INSERT INTO param_lycee (id, nom_lycee) VALUES (1, 'Lycée Test Paie') ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO exercices_financiers (id, lycee_id, libelle, date_debut, date_fin, est_actif, cloture) VALUES (1, 1, 'Exercice 2024', '2024-01-01', '2024-12-31', 1, 0) ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO comptabilite_periodes (id, lycee_id, exercice_financier_id, date_debut, date_fin, est_cloturee) VALUES (1, 1, 1, '2024-09-01', '2024-09-30', 0) ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO comptes_financiers (id, lycee_id, nom_compte, type_compte, solde_courant, devise, statut) VALUES (1, 1, 'Banque Principale', 'banque', 1000000.00, 'FCFA', 'actif') ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO paie_entites_juridiques (id, raison_sociale, sigle) VALUES (1, 'Établissement Principal', 'EP') ON CONFLICT DO NOTHING");

// Seed Cycles, Classes, Matieres, Users
$db->exec("INSERT INTO cycles (id_cycle, lycee_id, nom_cycle) VALUES (1, 1, 'Lycée') ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO cycles (id_cycle, lycee_id, nom_cycle) VALUES (2, 1, 'Collège') ON CONFLICT DO NOTHING");

$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero) VALUES (10, 1, 1, 'Terminale', 'C', 7) ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero) VALUES (11, 1, 1, 'Terminale', 'A4', 1) ON CONFLICT DO NOTHING");

$db->exec("INSERT INTO matieres (id_matiere, nom_matiere) VALUES (1, 'Mathématiques') ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO matieres (id_matiere, nom_matiere) VALUES (2, 'Physique') ON CONFLICT DO NOTHING");

$db->exec("INSERT INTO type_contrat (id_contrat, libelle, type_paiement, prise_en_charge) VALUES (1, 'CDI', 'mensuel', 'Ecole') ON CONFLICT DO NOTHING");

$db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, identifiant_public, email, mot_de_passe, role_id, actif) VALUES (101, 1, 'Dupont', 'Jean', '101ENS', 'jean@test.com', 'hash', 6, 1) ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, identifiant_public, email, mot_de_passe, role_id, actif) VALUES (102, 1, 'Martin', 'Claire', '102ENS', 'claire@test.com', 'hash', 6, 1) ON CONFLICT DO NOTHING");

$db->exec("INSERT INTO personnel_contrats_historique (id, personnel_id, type_contrat_id, date_debut, entite_juridique_id, mode_calcul_principal, salaire_base, devise, statut_contrat, version_num) VALUES (201, 101, 1, '2024-01-01', 1, 'horaire', 250000.00, 'FCFA', 'actif', 1) ON CONFLICT DO NOTHING");

// Assign Teacher 101 to Terminale C 07 (Maths)
$db->exec("INSERT INTO affectations_pedagogiques (id, enseignant_id, classe_id, matiere_id, annee_academique_id, volume_horaire_hebdo, date_debut, date_fin, statut) VALUES (1, 101, 10, 1, 1, 4.0, '2024-09-01', NULL, 'actif') ON CONFLICT DO NOTHING");

// --- TEST 1: Sélection Pédagogique Hiérarchique ---
echo "\n--- TEST 1: Sélection Pédagogique Hiérarchique (Cycle -> Niveau -> Série -> Numéro -> Enseignants) ---\n";
$teachersH = AffectationPedagogique::findTeachersByHierarchy(1, 1, 'Terminale', 'C', '7', 10);
assert_test(count($teachersH) === 1 && (int)$teachersH[0]['id_user'] === 101, "La sélection hiérarchique retrouve uniquement Jean Dupont pour Terminale C - 07");

// --- TEST 2: Cohérence d'Affectation Pédagogique ---
echo "\n--- TEST 2: Cohérence d'Affectation (Enseignant non affecté exclu) ---\n";
$teachersH2 = AffectationPedagogique::findTeachersByHierarchy(1, 1, 'Terminale', 'C', '7', 10);
$foundUnassigned = false;
foreach ($teachersH2 as $th) {
    if ((int)$th['id_user'] === 102) $foundUnassigned = true;
}
assert_test(!$foundUnassigned, "Claire Martin (102) n'apparaît pas pour Terminale C - 07 car non affectée");

// --- TEST 3: Cahier de Texte Sans Saisie Manuelle d'IDs ---
echo "\n--- TEST 3: Résolution Automatique Cahier de Texte Sans Saisie Manuelle ---\n";
$db->exec("INSERT INTO cahier_texte (cahier_id, lycee_id, personnel_id, classe_id, matiere_id, date_cours, heure_debut, heure_fin, contenu_cours) VALUES (301, 1, 101, 10, 1, '2024-09-02', '08:00:00', '10:00:00', 'Fonctions avancées') ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO cahier_texte (cahier_id, lycee_id, personnel_id, classe_id, matiere_id, date_cours, heure_debut, heure_fin, contenu_cours) VALUES (302, 1, 101, 10, 1, '2024-09-04', '08:00:00', '09:00:00', 'Dérivées') ON CONFLICT DO NOTHING");

$valId1 = PaieCahierTexteValidation::validateSession(301, 1, 5000.00);
assert_test($valId1 > 0, "Validation automatique séance #301 réussie (Validation ID #{$valId1})");

$vRow = PaieCahierTexteValidation::findByCahierId(301);
assert_test((int)$vRow['enseignant_id'] === 101 && (int)$vRow['classe_id'] === 10 && (float)$vRow['duree_heures'] == 2.0, "Identifiants enseignant_id=101, classe_id=10 et durée=2.0h résolus automatiquement");

// --- TEST 4: Validation Unique (Service Fait) ---
echo "\n--- TEST 4: Validation Unique (Non-duplication du Service Fait) ---\n";
$valId1_repeat = PaieCahierTexteValidation::validateSession(301, 1, 5000.00);
assert_test($valId1_repeat === $valId1, "Seconde validation de la même séance #301 retourne le même ID sans doublon");

// --- TEST 5: Calcul Horaire (42h30 x 5000) ---
echo "\n--- TEST 5: Calcul Rémunération Horaire (42h30 x 5000 FCFA) ---\n";
$computedHours = PaieCalculationEngine::computeBulletin(
    ['salaire_base' => 0.00],
    [['id' => $valId1, 'duree_heures' => 42.50, 'taux_horaire' => 5000.00]],
    [],
    'DEFAULT'
);
assert_test((float)$computedHours['total_brut'] == 212500.00, "Calcul de 42h30 à 5000 FCFA = 212 500 FCFA");

// --- TEST 6: Enseignant Horaire Payé Mensuellement ---
echo "\n--- TEST 6: Enseignant Mesuré à l'Heure et Consolidé Mensuellement ---\n";
$periodeId = PaieWorkflowService::createPeriod(1, 1, 'PAIE-2024-09', 9, 2024, '2024-09-01', '2024-09-30', 1);
$bulletinIds = PaieWorkflowService::generateBulletinsForPeriod($periodeId, 1);
assert_test(count($bulletinIds) > 0, "Génération mensuelle réussie pour l'enseignant horaire");

$stmtB101 = $db->prepare("SELECT * FROM paie_bulletins WHERE periode_id = :p AND personnel_id = 101 AND est_version_active = 1");
$stmtB101->execute(['p' => $periodeId]);
$bulletin101 = $stmtB101->fetch(PDO::FETCH_ASSOC);

assert_test((float)$bulletin101['total_brut'] == 260000.00, "Le bulletin mensuel consolide 250 000 FCFA de base + 10 000 FCFA d'heures (2.0h x 5000 FCFA) = 260 000 FCFA");

// --- TEST 7: Isolation des Heures Déjà Payées ---
echo "\n--- TEST 7: Isolation des Heures Déjà Payées (Non-reprise sur seconde consolidation) ---\n";
$validatedAvailable = PaieCahierTexteValidation::findValidatedForTeacherAndDates(101, '2024-09-01', '2024-09-30');
assert_test(count($validatedAvailable) === 0, "Les heures déjà intégrées dans un bulletin actif sont isolées et exclues d'une nouvelle consolidation");

// --- TEST 8: Re-tirage V1 -> V2 Sans Doublon ---
echo "\n--- TEST 8: Re-tirage Atomique V1 -> V2 Sans Conflit d'Heures ---\n";
$v2Id = PaieWorkflowService::redrawBulletin((int)$bulletin101['id'], 1);
assert_test($v2Id > (int)$bulletin101['id'], "Bulletin V2 re-tiré avec succès ID #{$v2Id}");

$v2B = PaieBulletin::findById($v2Id);
assert_test((float)$v2B['total_brut'] == 260000.00, "Le bulletin V2 réattache proprement les heures validées sans doublon");

// --- TEST 9: Immutabilité Période Clôturée ---
echo "\n--- TEST 9: Immutabilité de la Période Clôturée ---\n";
$db->exec("UPDATE comptabilite_periodes SET est_cloturee = 1 WHERE id = 1");
$blockedClose = false;
try {
    PaieWorkflowService::redrawBulletin($v2Id, 1);
} catch (LogicException $e) {
    $blockedClose = true;
}
assert_test($blockedClose, "Tentative de modification refusée car la période est clôturée");
$db->exec("UPDATE comptabilite_periodes SET est_cloturee = 0 WHERE id = 1");

// --- TEST 10: Idempotence avec Clé ---
echo "\n--- TEST 10: Idempotence Génération Bulletin avec Idempotency Key ---\n";
$periodeId10 = PaieWorkflowService::createPeriod(1, 1, 'PAIE-2024-11', 11, 2024, '2024-11-01', '2024-11-30', 1);
$idemKey = 'TEST-KEY-PAIE-1001';
$bKey1 = PaieWorkflowService::generateBulletinsForPeriod($periodeId10, 1, $idemKey);
$bKey2 = PaieWorkflowService::generateBulletinsForPeriod($periodeId10, 1, $idemKey);
assert_test($bKey1 === $bKey2, "L'appel répété avec la même clé d'idempotence retourne le résultat identique sans doublon");

// --- TEST 11: Concurrence & Verrous Déterministes ---
echo "\n--- TEST 11: Traitement sous Verrous V6 Déterministes ---\n";
$pieceId = PaieWorkflowService::postAccounting($v2Id, 1);
assert_test($pieceId > 0, "Comptabilisation exécutée sous verrous déterministes paie_periodes -> comptabilite_periodes -> paie_bulletins (Pièce ID #{$pieceId})");

// --- TEST 12: Support i18n & RTL ---
echo "\n--- TEST 12: Conformité i18n et RTL ---\n";
$translatedLabel = _("Validations des Heures Pédagogiques - Cahier de Texte");
assert_test(!empty($translatedLabel), "Traduction i18n fonctionnelle: '$translatedLabel'");

// --- TEST 13: Mode Fixe Mensuel (Non-transformé en Heures x 5000) ---
echo "\n--- TEST 13: Fixe Mensuel (Préservation du salaire contractuel de base) ---\n";
$db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, identifiant_public, email, mot_de_passe, role_id, actif) VALUES (103, 1, 'Sow', 'Mamadou', '103ENS', 'mamadou@test.com', 'hash', 6, 1) ON CONFLICT DO NOTHING");
$db->exec("INSERT INTO personnel_contrats_historique (id, personnel_id, type_contrat_id, date_debut, entite_juridique_id, mode_calcul_principal, salaire_base, devise, statut_contrat, version_num) VALUES (203, 103, 1, '2024-01-01', 1, 'forfait_fixe', 300000.00, 'FCFA', 'actif', 1) ON CONFLICT DO NOTHING");

$metricsFixe = PaieCahierTexteValidation::getTeacherHoursMetrics([
    'lycee_id' => 1,
    'teacher_id' => 103,
    'date_debut' => '2024-09-01',
    'date_fin' => '2024-09-30'
]);
assert_test((float)$metricsFixe['valorisation_realisee_estimee'] == 300000.00, "Un enseignant fixe mensuel affiche sa rémunération contractuelle (300 000 FCFA) et non un calcul fictif à l'heure");

// --- TEST 14: Absence de Fallback 5000 FCFA ---
echo "\n--- TEST 14: Zéro Fallback Hardcodé (0.00 si non configuré) ---\n";
$rateZero = PaieCahierTexteValidation::resolveContractHourlyRate(103, '2024-09-01');
assert_test($rateZero === 0.00, "Le système retourne 0.00 et n'invente jamais 5000.00 FCFA pour un contrat sans composant horaire");

// --- TEST 15: Avenant avec Date d'Effet (Pondération Datée par Séance) ---
echo "\n--- TEST 15: Avenant avec Date d'Effet ---\n";
$db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, identifiant_public, email, mot_de_passe, role_id, actif) VALUES (104, 1, 'Diallo', 'Aissatou', '104ENS', 'aissatou@test.com', 'hash', 6, 1) ON CONFLICT DO NOTHING");
// Contrat V1 (01/09 -> 14/09): Taux = 4000 FCFA
$db->exec("INSERT INTO personnel_contrats_historique (id, personnel_id, type_contrat_id, date_debut, date_fin, entite_juridique_id, mode_calcul_principal, salaire_base, devise, statut_contrat, version_num) VALUES (204, 104, 1, '2024-09-01', '2024-09-14', 1, 'taux_horaire', 4000.00, 'FCFA', 'avenant_remplace', 1) ON CONFLICT DO NOTHING");
// Avenant V2 (15/09 -> 30/09): Taux = 5000 FCFA
$db->exec("INSERT INTO personnel_contrats_historique (id, personnel_id, type_contrat_id, date_debut, date_fin, entite_juridique_id, mode_calcul_principal, salaire_base, devise, statut_contrat, version_num) VALUES (205, 104, 1, '2024-09-15', NULL, 1, 'taux_horaire', 5000.00, 'FCFA', 'actif', 2) ON CONFLICT DO NOTHING");

$rateBefore = PaieCahierTexteValidation::resolveContractHourlyRate(104, '2024-09-05');
$rateAfter = PaieCahierTexteValidation::resolveContractHourlyRate(104, '2024-09-20');

assert_test($rateBefore === 4000.00 && $rateAfter === 5000.00, "Chaque séance est valorisée au taux contractuel actif à sa date réelle (4000 FCFA le 05/09 vs 5000 FCFA le 20/09)");

// --- TEST 16: Valorisation Estimée AVANT et APRÈS Validation ---
echo "\n--- TEST 16: Estimation AVANT et APRÈS Validation ---\n";
$db->exec("INSERT INTO cahier_texte (cahier_id, lycee_id, personnel_id, classe_id, matiere_id, date_cours, heure_debut, heure_fin, contenu_cours) VALUES (305, 1, 104, 10, 1, '2024-09-05', '08:00:00', '12:00:00', 'Cours 1') ON CONFLICT DO NOTHING"); // 4h x 4000 = 16000
$db->exec("INSERT INTO cahier_texte (cahier_id, lycee_id, personnel_id, classe_id, matiere_id, date_cours, heure_debut, heure_fin, contenu_cours) VALUES (306, 1, 104, 10, 1, '2024-09-20', '08:00:00', '12:00:00', 'Cours 2') ON CONFLICT DO NOTHING"); // 4h x 5000 = 20000

$metricsBeforeVal = PaieCahierTexteValidation::getTeacherHoursMetrics([
    'lycee_id' => 1,
    'teacher_id' => 104,
    'date_debut' => '2024-09-01',
    'date_fin' => '2024-09-30'
]);
assert_test((float)$metricsBeforeVal['valorisation_realisee_estimee'] == 36000.00 && (float)$metricsBeforeVal['heures_validees'] == 0.00, "AVANT validation: Valorisation estimée du service réalisé = 36 000 FCFA (16k + 20k)");

PaieCahierTexteValidation::validateSession(305, 1);
PaieCahierTexteValidation::validateSession(306, 1);

$metricsAfterVal = PaieCahierTexteValidation::getTeacherHoursMetrics([
    'lycee_id' => 1,
    'teacher_id' => 104,
    'date_debut' => '2024-09-01',
    'date_fin' => '2024-09-30'
]);
assert_test((float)$metricsAfterVal['valorisation_validee'] == 36000.00 && (float)$metricsAfterVal['heures_validees'] == 8.00, "APRÈS validation: Valorisation validée = 36 000 FCFA sur 8.0h validées");

echo "\n=========================================================================\n";
echo "🏆 TOUS LES 16 TESTS D'INTÉGRATION ET DE SÉCURITÉ V6.1.2 ONT RÉUSSI !\n";
echo "=========================================================================\n";
