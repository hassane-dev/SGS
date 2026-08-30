<?php

use PHPUnit\Framework\TestCase;

define('TEST_MODE', true);
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/services/AcademicAnalysisService.php';
require_once __DIR__ . '/../src/services/AuthorizationScopeService.php';
require_once __DIR__ . '/../src/models/Eleve.php';
require_once __DIR__ . '/../src/models/Etude.php';

echo "========================================================\n";
echo "SUITE DE TEST : PARCOURS ACADÉMIQUE LONGITUDINAL\n";
echo "========================================================\n";

$db = Database::getInstance();

// Seed data IDs
$testEleveId = 7700;
$annee1 = 7701;
$annee2 = 7702;
$classe1 = 7710;
$classe2 = 7711;
$seq1 = 7720;
$seq2 = 7721;
$matMath = 7730;
$matFr = 7731;

// Cleanup any old test records
$db->exec("DELETE FROM evaluations WHERE eleve_id = $testEleveId");
$db->exec("DELETE FROM bulletins WHERE eleve_id = $testEleveId");
$db->exec("DELETE FROM sequences WHERE id IN ($seq1, $seq2)");
$db->exec("DELETE FROM matieres WHERE id_matiere IN ($matMath, $matFr)");
$db->exec("DELETE FROM etudes WHERE eleve_id = $testEleveId");
$db->exec("DELETE FROM eleves WHERE id_eleve = $testEleveId");
$db->exec("DELETE FROM classes WHERE id_classe IN ($classe1, $classe2)");
$db->exec("DELETE FROM annees_academiques WHERE id IN ($annee1, $annee2)");

// 1. Create Academic Years
$db->exec("INSERT INTO annees_academiques (id, libelle, date_debut, date_fin, est_active, cloturee) VALUES ($annee1, '2023-2024', '2023-09-01', '2024-06-30', 0, 1)");
$db->exec("INSERT INTO annees_academiques (id, libelle, date_debut, date_fin, est_active, cloturee) VALUES ($annee2, '2024-2025', '2024-09-01', '2025-06-30', 1, 0)");

// 2. Create Classes
$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, numero) VALUES ($classe1, 1, 1, '6ème', 1)");
$db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, numero) VALUES ($classe2, 1, 1, '5ème', 1)");

// 3. Create Student
$db->exec("INSERT INTO eleves (id_eleve, lycee_id, nom, prenom, statut, identifiant_public) VALUES ($testEleveId, 1, 'Tchamba', 'Aline', 'actif', 'TEST-LONG-77')");

// 4. Enroll Student in 2023-2024 (6ème 1) and 2024-2025 (5ème 1)
$db->exec("INSERT INTO etudes (id_etude, eleve_id, classe_id, lycee_id, annee_academique_id, status, is_active) VALUES (7740, $testEleveId, $classe1, 1, $annee1, 'active', 0)");
$db->exec("INSERT INTO etudes (id_etude, eleve_id, classe_id, lycee_id, annee_academique_id, status, is_active) VALUES (7741, $testEleveId, $classe2, 1, $annee2, 'active', 1)");

// 5. Create Subjects
$db->exec("INSERT INTO matieres (id_matiere, lycee_id, nom_matiere) VALUES ($matMath, 1, 'Mathématiques')");
$db->exec("INSERT INTO matieres (id_matiere, lycee_id, nom_matiere) VALUES ($matFr, 1, 'Français')");

// 6. Create Sequences
$db->exec("INSERT INTO sequences (id, lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut) VALUES ($seq1, 1, $annee1, 'Séquence 1', 'trimestrielle', '2023-09-15', '2023-11-01', 'fermee')");
$db->exec("INSERT INTO sequences (id, lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut) VALUES ($seq2, 1, $annee2, 'Séquence 1', 'trimestrielle', '2024-09-15', '2024-11-01', 'ouverte')");

// 7. Insert Grades
// Year 1 (6ème) : Math 12.00 (Coef 3), Français 14.00 (Coef 2)
$db->exec("INSERT INTO evaluations (id, lycee_id, classe_id, matiere_id, enseignant_id, eleve_id, sequence_id, annee_academique_id, type, note, coefficient) VALUES (7750, 1, $classe1, $matMath, 1, $testEleveId, $seq1, $annee1, 'devoir', 12.00, 3.00)");
$db->exec("INSERT INTO evaluations (id, lycee_id, classe_id, matiere_id, enseignant_id, eleve_id, sequence_id, annee_academique_id, type, note, coefficient) VALUES (7751, 1, $classe1, $matFr, 1, $testEleveId, $seq1, $annee1, 'devoir', 14.00, 2.00)");

// Year 2 (5ème) : Math 16.00 (Coef 3), Français 11.00 (Coef 2)
$db->exec("INSERT INTO evaluations (id, lycee_id, classe_id, matiere_id, enseignant_id, eleve_id, sequence_id, annee_academique_id, type, note, coefficient) VALUES (7752, 1, $classe2, $matMath, 1, $testEleveId, $seq2, $annee2, 'devoir', 16.00, 3.00)");
$db->exec("INSERT INTO evaluations (id, lycee_id, classe_id, matiere_id, enseignant_id, eleve_id, sequence_id, annee_academique_id, type, note, coefficient) VALUES (7753, 1, $classe2, $matFr, 1, $testEleveId, $seq2, $annee2, 'devoir', 11.00, 2.00)");

// 8. Insert Official Bulletin Snapshots
$db->exec("INSERT INTO bulletins (id, eleve_id, sequence_id, annee_academique_id, lycee_id, moyenne_generale, rang, appreciation, statut) VALUES (7760, $testEleveId, $seq1, $annee1, 1, 12.80, '5e/30', 'Bon travail', 'valide')");
$db->exec("INSERT INTO bulletins (id, eleve_id, sequence_id, annee_academique_id, lycee_id, moyenne_generale, rang, appreciation, statut) VALUES (7761, $testEleveId, $seq2, $annee2, 1, 14.00, '2e/28', 'Très bien', 'provisoire')");

echo "[OK] Données de test initialisées.\n\n";

// --- TEST 1 : Timeline Chronologique ---
echo "TEST 1 : getStudentLongitudinalTimeline()...\n";
$timeline = AcademicAnalysisService::getStudentLongitudinalTimeline($testEleveId);
assert(count($timeline) === 2, "La timeline doit contenir exactement 2 inscriptions.");
assert($timeline[0]['etude']['annee_libelle'] === '2023-2024', "L'année 1 doit être 2023-2024.");
assert($timeline[1]['etude']['annee_libelle'] === '2024-2025', "L'année 2 doit être 2024-2025.");
echo "  -> Timeline conforme (2 années isolées) | PASS\n\n";

// --- TEST 2 : Moyennes Annuelles par Matière ---
echo "TEST 2 : getSubjectAnnualAverages()...\n";
$subjectAverages = AcademicAnalysisService::getSubjectAnnualAverages($testEleveId);
assert(count($subjectAverages) === 2, "2 années d'agrégation de moyennes attendues.");

$yr1Math = null;
foreach ($subjectAverages[0]['matieres'] as $m) {
    if ($m['matiere_id'] == $matMath) {
        $yr1Math = $m['annual_average'];
    }
}
assert($yr1Math === 12.00, "Moyenne Math 2023-2024 doit être 12.00, obtenu: $yr1Math");

$yr2Math = null;
foreach ($subjectAverages[1]['matieres'] as $m) {
    if ($m['matiere_id'] == $matMath) {
        $yr2Math = $m['annual_average'];
    }
}
assert($yr2Math === 16.00, "Moyenne Math 2024-2025 doit être 16.00, obtenu: $yr2Math");
echo "  -> Moyennes annuelles par matière exactes (Math: 12.00 -> 16.00) | PASS\n\n";

// --- TEST 3 : Variations Interannuelles ---
echo "TEST 3 : getInterannualVariations()...\n";
$variations = AcademicAnalysisService::getInterannualVariations($testEleveId);
assert(count($variations) === 2, "2 matières doivent avoir des séries de variation.");

$mathDelta = null;
$frDelta = null;
foreach ($variations as $var) {
    if ($var['matiere_id'] == $matMath) {
        $mathDelta = $var['total_delta'];
    }
    if ($var['matiere_id'] == $matFr) {
        $frDelta = $var['total_delta'];
    }
}

assert($mathDelta === 4.00, "Variation Math doit être +4.00, obtenu: $mathDelta");
assert($frDelta === -3.00, "Variation Français doit être -3.00, obtenu: $frDelta");
echo "  -> Variations interannuelles exactes (Math: +4.00, Français: -3.00) | PASS\n\n";

// --- TEST 4 : Performance Raw Metrics ---
echo "TEST 4 : getRawPerformanceMetrics()...\n";
$metrics = AcademicAnalysisService::getRawPerformanceMetrics($testEleveId);
assert($metrics['latest_year'] === '2024-2025', "Dernière année doit être 2024-2025.");
assert($metrics['latest_subjects_sorted'][0]['matiere_id'] == $matMath, "La meilleure matière 2024-2025 doit être Math.");
echo "  -> Métriques de performance ordonnées avec succès | PASS\n\n";

// --- TEST 5 : Snapshots Bulletins Officiels ---
echo "TEST 5 : getOfficialBulletinSnapshots()...\n";
$snapshots = AcademicAnalysisService::getOfficialBulletinSnapshots($testEleveId);
assert(count($snapshots) === 2, "2 bulletins officiels scellés attendus.");
assert((float)$snapshots[0]['moyenne_generale'] === 12.80, "Bulletin 1 moyenne doit être 12.80");
assert((float)$snapshots[1]['moyenne_generale'] === 14.00, "Bulletin 2 moyenne doit être 14.00");
echo "  -> Snapshots officiels de bulletins conformes | PASS\n\n";

// --- CLEANUP ---
$db->exec("DELETE FROM evaluations WHERE eleve_id = $testEleveId");
$db->exec("DELETE FROM bulletins WHERE eleve_id = $testEleveId");
$db->exec("DELETE FROM sequences WHERE id IN ($seq1, $seq2)");
$db->exec("DELETE FROM matieres WHERE id_matiere IN ($matMath, $matFr)");
$db->exec("DELETE FROM etudes WHERE eleve_id = $testEleveId");
$db->exec("DELETE FROM eleves WHERE id_eleve = $testEleveId");
$db->exec("DELETE FROM classes WHERE id_classe IN ($classe1, $classe2)");
$db->exec("DELETE FROM annees_academiques WHERE id IN ($annee1, $annee2)");

echo "========================================================\n";
echo "TOUS LES TESTS DU PARCOURS ACADÉMIQUE ONT RÉUSSI !\n";
echo "========================================================\n";
