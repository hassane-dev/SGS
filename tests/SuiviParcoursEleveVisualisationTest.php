<?php
/**
 * Test d'intégration automatisé pour les séries de visualisation du Suivi et Parcours Élève.
 * Utilise une connexion PDO SQLite en mémoire pour garantir l'isolation totale.
 */

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/services/AcademicAnalysisService.php';

echo "========================================================\n";
echo "SUITE DE TEST : SÉRIES ET VISUALISATIONS DU SUIVI ÉLÈVE\n";
echo "========================================================\n";

$db = Database::getInstance();

// Initialiser des données de test simulées
$db->exec("DELETE FROM bulletin_details");
$db->exec("DELETE FROM bulletins");
$db->exec("DELETE FROM evaluations");
$db->exec("DELETE FROM etudes");
$db->exec("DELETE FROM eleves");
$db->exec("DELETE FROM sequences");
$db->exec("DELETE FROM classes");
$db->exec("DELETE FROM matieres");
$db->exec("DELETE FROM cycles");
$db->exec("DELETE FROM annees_academiques");
$db->exec("DELETE FROM param_lycee");

$db->exec("INSERT INTO annees_academiques (id, libelle, date_debut, date_fin, est_active, cloturee) VALUES (1, '2025-2026', '2025-09-01', '2026-06-30', 1, 0)");
$db->exec("INSERT INTO param_lycee (id, nom_lycee, type_lycee) VALUES (1, 'Lycee d Essai', 'prive')");
$db->exec("INSERT INTO cycles (id_cycle, lycee_id, nom_cycle) VALUES (1, 1, 'College')");
$db->exec("INSERT INTO classes (id_classe, niveau, serie, numero, cycle_id, lycee_id) VALUES (10, '5ème', 'A', 1, 1, 1)");
$db->exec("INSERT INTO matieres (id_matiere, nom_matiere, lycee_id) VALUES (101, 'Mathématiques', 1), (102, 'Français', 1)");

$db->exec("INSERT INTO eleves (id_eleve, lycee_id, nom, prenom, statut, identifiant_public) VALUES (50, 1, 'DUPONT', 'Jean', 'actif', '20250901-0050E')");
$db->exec("INSERT INTO etudes (id_etude, eleve_id, classe_id, lycee_id, annee_academique_id, is_active, status) VALUES (1, 50, 10, 1, 1, 1, 'active')");

$db->exec("INSERT INTO sequences (id, lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut) VALUES (1, 1, 1, 'Trimestre 1', 'trimestrielle', '2025-09-15', '2025-12-15', 'fermee')");
$db->exec("INSERT INTO sequences (id, lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut) VALUES (2, 1, 1, 'Trimestre 2', 'trimestrielle', '2026-01-05', '2026-03-25', 'fermee')");
$db->exec("INSERT INTO sequences (id, lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut) VALUES (3, 1, 1, 'Trimestre 3', 'trimestrielle', '2026-04-01', '2026-06-15', 'ouverte')");

// Bulletin T1 (Fermé)
$db->exec("INSERT INTO bulletins (id, eleve_id, sequence_id, annee_academique_id, lycee_id, classe_id, nom_classe_snapshot, effectif_classe, moyenne_generale, moyenne_classe, rang_int, rang, statut) VALUES (1, 50, 1, 1, 1, 10, '5ème A 1', 30, 12.00, 11.50, 10, '10ème', 'valide')");
$db->exec("INSERT INTO bulletin_details (bulletin_id, matiere_id, nom_matiere_snapshot, moyenne_matiere, coefficient_snapshot, points_ponderes, moyenne_classe_matiere) VALUES (1, 101, 'Mathématiques', 14.00, 4.0, 56.00, 12.00)");
$db->exec("INSERT INTO bulletin_details (bulletin_id, matiere_id, nom_matiere_snapshot, moyenne_matiere, coefficient_snapshot, points_ponderes, moyenne_classe_matiere) VALUES (1, 102, 'Français', 10.00, 3.0, 30.00, 11.00)");

// Bulletin T2 (Fermé)
$db->exec("INSERT INTO bulletins (id, eleve_id, sequence_id, annee_academique_id, lycee_id, classe_id, nom_classe_snapshot, effectif_classe, moyenne_generale, moyenne_classe, rang_int, rang, statut) VALUES (2, 50, 2, 1, 1, 10, '5ème A 1', 30, 14.50, 11.80, 4, '4ème', 'valide')");
$db->exec("INSERT INTO bulletin_details (bulletin_id, matiere_id, nom_matiere_snapshot, moyenne_matiere, coefficient_snapshot, points_ponderes, moyenne_classe_matiere) VALUES (2, 101, 'Mathématiques', 17.00, 4.0, 68.00, 12.50)");
$db->exec("INSERT INTO bulletin_details (bulletin_id, matiere_id, nom_matiere_snapshot, moyenne_matiere, coefficient_snapshot, points_ponderes, moyenne_classe_matiere) VALUES (2, 102, 'Français', 12.00, 3.0, 36.00, 11.20)");

// Évaluations T3 (Séquence Ouverte / Provisoire)
$db->exec("INSERT INTO evaluations (lycee_id, classe_id, matiere_id, enseignant_id, eleve_id, sequence_id, annee_academique_id, type, note, bareme_snapshot, coefficient) VALUES (1, 10, 101, 1, 50, 3, 1, 'devoir', 18.00, 20.00, 4.0)");
$db->exec("INSERT INTO evaluations (lycee_id, classe_id, matiere_id, enseignant_id, eleve_id, sequence_id, annee_academique_id, type, note, bareme_snapshot, coefficient) VALUES (1, 10, 102, 1, 50, 3, 1, 'devoir', 14.00, 20.00, 3.0)");

echo "[OK] Données de test initialisées.\n\n";

// Test 1: getSequentialSeriesData (inclut T1 fermé, T2 fermé, T3 ouvert)
echo "TEST 1 : getSequentialSeriesData()...\n";
$seqData = AcademicAnalysisService::getSequentialSeriesData(50);
assert(count($seqData) === 3, "Doit retourner 3 séquences (2 fermées + 1 ouverte)");
assert($seqData[2]['bulletin_statut'] === 'provisoire', "T3 doit être de statut provisoire");
echo "  -> 3 séquences extraits (T1/T2 fermés, T3 ouvert) | PASS\n\n";

// Test 2: getGeneralAverageTrendSeries (G1)
echo "TEST 2 : getGeneralAverageTrendSeries()...\n";
$g1 = AcademicAnalysisService::getGeneralAverageTrendSeries(50);
assert(count($g1['categories']) === 3, "3 catégories pour G1");
assert($g1['series'][0]['data'][0] === 12.0, "Moyenne T1 = 12.00");
assert($g1['series'][0]['data'][1] === 14.5, "Moyenne T2 = 14.50");
assert($g1['statuses'][2] === 'provisoire', "Statut T3 = provisoire");
echo "  -> Série G1 exacte avec support des séquences ouvertes | PASS\n\n";

// Test 3: getStudentVsClassSeries (G2)
echo "TEST 3 : getStudentVsClassSeries()...\n";
$g2 = AcademicAnalysisService::getStudentVsClassSeries(50);
assert($g2['series'][0]['data'][1] === 14.5, "Moyenne Élève T2 = 14.5");
assert($g2['series'][1]['data'][1] === 11.8, "Moyenne Classe T2 = 11.8");
assert($g2['gaps'][1] === 2.7, "Écart T2 = +2.70");
echo "  -> Série G2 exacte (Écart +2.70) | PASS\n\n";

// Test 4: getRankTrendSeries (G3)
echo "TEST 4 : getRankTrendSeries()...\n";
$g3 = AcademicAnalysisService::getRankTrendSeries(50);
assert($g3['ranks'][0] === 10, "Rang T1 = 10");
assert($g3['ranks'][1] === 4, "Rang T2 = 4");
echo "  -> Série G3 exacte (Rang 10 -> 4) | PASS\n\n";

// Test 5: getPerformanceSummary
echo "TEST 5 : getPerformanceSummary()...\n";
$summary = AcademicAnalysisService::getPerformanceSummary(50);
assert($summary['has_data'] === true, "A des données");
assert($summary['latest_average'] === 16.29, "Dernière moyenne T3 = 16.29");
assert($summary['general_trend'] === 'En progression', "Tendance = En progression");
echo "  -> Résumé de performance exact | PASS\n\n";

echo "========================================================\n";
echo "TOUS LES TESTS DES SÉRIES ET VISUALISATIONS ONT RÉUSSI !\n";
echo "========================================================\n";
