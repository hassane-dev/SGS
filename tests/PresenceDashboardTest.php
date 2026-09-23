<?php

if (!defined('TEST_MODE')) {
    define('TEST_MODE', true);
}

require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/core/CsrfService.php';
require_once __DIR__ . '/../src/models/AnneeAcademique.php';
require_once __DIR__ . '/../src/models/Eleve.php';
require_once __DIR__ . '/../src/models/Presence.php';
require_once __DIR__ . '/../src/models/Classe.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/services/AuthorizationScopeService.php';
require_once __DIR__ . '/../src/services/ReportingService.php';
require_once __DIR__ . '/../src/services/PresenceDashboardService.php';
require_once __DIR__ . '/../src/controllers/PresenceDashboardController.php';

// Polyfill function _() if gettext is missing
if (!function_exists('_')) {
    function _($string) {
        return $string;
    }
}

function assert_presence_dash($condition, $message) {
    if ($condition) {
        echo " [PASS] $message\n";
    } else {
        echo " [FAIL] $message\n";
        throw new Exception("Test failed: $message");
    }
}

class PresenceDashboardTest {

    public static function run(): void {
        echo "=========================================================\n";
        echo " RUNNING PRESENCE DASHBOARD INTEGRATION TEST (PHASE 4)\n";
        echo "=========================================================\n";

        $dbFile = __DIR__ . "/../database.sqlite";
        $testDb = new \PDO("sqlite:" . $dbFile, null, null, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
        ]);
        Database::setInstance($testDb);
        require_once __DIR__ . '/../migrate.php';
        $db = Database::getInstance();

        $lyceeIdA = 9901;
        $lyceeIdB = 9902;
        $anneeId = 910;

        // Cleanup
        $db->exec("DELETE FROM presences WHERE lycee_id IN ($lyceeIdA, $lyceeIdB)");
        $db->exec("DELETE FROM etudes WHERE lycee_id IN ($lyceeIdA, $lyceeIdB)");
        $db->exec("DELETE FROM eleves WHERE lycee_id IN ($lyceeIdA, $lyceeIdB)");
        $db->exec("DELETE FROM affectations_pedagogiques WHERE annee_academique_id = $anneeId");
        $db->exec("DELETE FROM matieres WHERE lycee_id IN ($lyceeIdA, $lyceeIdB)");
        $db->exec("DELETE FROM classes WHERE lycee_id IN ($lyceeIdA, $lyceeIdB)");
        $db->exec("DELETE FROM reporting_kpi_seuils WHERE lycee_id IN ($lyceeIdA, $lyceeIdB)");

        // 1. Setup Schools, Academic Year & Absenteeism Threshold Configuration
        $db->exec("INSERT INTO param_lycee (id, nom_lycee, type_lycee) VALUES ($lyceeIdA, 'Lycée Présence Test A', 'prive') ON CONFLICT(id) DO UPDATE SET nom_lycee='Lycée Présence Test A'");
        $db->exec("INSERT INTO param_lycee (id, nom_lycee, type_lycee) VALUES ($lyceeIdB, 'Lycée Présence Test B', 'prive') ON CONFLICT(id) DO UPDATE SET nom_lycee='Lycée Présence Test B'");

        $db->exec("INSERT INTO annees_academiques (id, libelle, date_debut, date_fin, est_active) VALUES ($anneeId, '2025-2026', '2025-09-01', '2026-06-30', 1) ON CONFLICT(id) DO UPDATE SET est_active=1");

        // Seed threshold 2 for Lycée A
        ReportingService::saveThreshold($lyceeIdA, 'absenteisme_seuil', 1.0, 2.0, 0.0, 5.0, 'decroissant');

        // 2. Setup Classes & Matieres
        $classA1 = 99001;
        $classA2 = 99002;
        $matiereId = 99011;
        $db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero) VALUES ($classA1, $lyceeIdA, 1, '6ème', 'A', 1)");
        $db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero) VALUES ($classA2, $lyceeIdA, 1, '6ème', 'B', 2)");
        $db->exec("INSERT INTO matieres (id_matiere, nom_matiere, lycee_id) VALUES ($matiereId, 'Mathématiques', $lyceeIdA)");

        // 3. Setup Active Roster Students for Class A1
        $eleve1 = 99101;
        $eleve2 = 99102;
        $eleve3 = 99103;

        $db->exec("INSERT INTO eleves (id_eleve, lycee_id, nom, prenom, identifiant_public, statut) VALUES ($eleve1, $lyceeIdA, 'Kouassi', 'Jean', '99101E', 'actif')");
        $db->exec("INSERT INTO eleves (id_eleve, lycee_id, nom, prenom, identifiant_public, statut) VALUES ($eleve2, $lyceeIdA, 'Diallo', 'Awa', '99102E', 'actif')");
        $db->exec("INSERT INTO eleves (id_eleve, lycee_id, nom, prenom, identifiant_public, statut) VALUES ($eleve3, $lyceeIdA, 'Traore', 'Moussa', '99103E', 'actif')");

        $db->exec("INSERT INTO etudes (id_etude, eleve_id, classe_id, lycee_id, annee_academique_id, is_active, status) VALUES (99201, $eleve1, $classA1, $lyceeIdA, $anneeId, 1, 'active')");
        $db->exec("INSERT INTO etudes (id_etude, eleve_id, classe_id, lycee_id, annee_academique_id, is_active, status) VALUES (99202, $eleve2, $classA1, $lyceeIdA, $anneeId, 1, 'active')");
        $db->exec("INSERT INTO etudes (id_etude, eleve_id, classe_id, lycee_id, annee_academique_id, is_active, status) VALUES (99203, $eleve3, $classA1, $lyceeIdA, $anneeId, 1, 'active')");

        $dateToday = date('Y-m-d');
        $dateYesterday = date('Y-m-d', strtotime('-1 day'));

        // 4. Insert Attendance Fixtures
        Presence::save([
            'eleve_id' => $eleve1, 'classe_id' => $classA1, 'matiere_id' => null, 'enseignant_id' => 1,
            'annee_academique_id' => $anneeId, 'lycee_id' => $lyceeIdA, 'date_presence' => $dateToday,
            'statut' => 'absent', 'commentaire' => 'TEST_DASHBOARD_1'
        ]);
        Presence::save([
            'eleve_id' => $eleve1, 'classe_id' => $classA1, 'matiere_id' => null, 'enseignant_id' => 1,
            'annee_academique_id' => $anneeId, 'lycee_id' => $lyceeIdA, 'date_presence' => $dateYesterday,
            'statut' => 'absent', 'commentaire' => 'TEST_DASHBOARD_2'
        ]);

        Presence::save([
            'eleve_id' => $eleve2, 'classe_id' => $classA1, 'matiere_id' => null, 'enseignant_id' => 1,
            'annee_academique_id' => $anneeId, 'lycee_id' => $lyceeIdA, 'date_presence' => $dateToday,
            'statut' => 'retard', 'commentaire' => 'TEST_DASHBOARD_3'
        ]);
        Presence::save([
            'eleve_id' => $eleve2, 'classe_id' => $classA1, 'matiere_id' => null, 'enseignant_id' => 1,
            'annee_academique_id' => $anneeId, 'lycee_id' => $lyceeIdA, 'date_presence' => $dateYesterday,
            'statut' => 'justifie', 'commentaire' => 'TEST_DASHBOARD_4'
        ]);

        Presence::save([
            'eleve_id' => $eleve3, 'classe_id' => $classA1, 'matiere_id' => null, 'enseignant_id' => 1,
            'annee_academique_id' => $anneeId, 'lycee_id' => $lyceeIdA, 'date_presence' => $dateToday,
            'statut' => 'present', 'commentaire' => 'TEST_DASHBOARD_5'
        ]);

        // Subject-specific attendance record (matiere_id = 999) -> MUST BE EXCLUDED FROM MACRO KPIS
        Presence::save([
            'eleve_id' => $eleve1, 'classe_id' => $classA1, 'matiere_id' => 999, 'enseignant_id' => 1,
            'annee_academique_id' => $anneeId, 'lycee_id' => $lyceeIdA, 'date_presence' => $dateToday,
            'statut' => 'absent', 'commentaire' => 'TEST_DASHBOARD_SUBJECT'
        ]);

        // Setup Admin Session via Auth::setSessionContext
        Auth::setSessionContext([
            'id' => 1,
            'id_user' => 1,
            'lycee_id' => $lyceeIdA,
            'role_name' => 'super_admin',
            'permissions' => [
                'presence' => ['view', 'manage', 'view_all'],
                'lycee' => ['view_all_lycees'],
                'cycle' => ['view_all_cycles']
            ]
        ]);


        // --- TEST 1: Verification of Macro KPI Calculations & matiere_id IS NULL Isolation ---
        echo "\n--- TEST 1: Verification of Macro KPI Calculations & matiere_id IS NULL Isolation ---\n";

        $filters = [
            'lycee_id' => $lyceeIdA,
            'annee_academique_id' => $anneeId,
            'classe_id' => $classA1,
            'date_debut' => $dateYesterday,
            'date_fin' => $dateToday
        ];

        $dashboardData = PresenceDashboardService::getDashboardData($filters, 1);
        $kpis = $dashboardData['kpis'];

        assert_presence_dash($kpis['total_occurrences_period'] === 5, "Total occurrences should be 5 (excluding matiere_id IS NOT NULL)");
        assert_presence_dash($kpis['today_absences']['occurrences'] === 1, "Today absences occurrences should be 1");
        assert_presence_dash($kpis['today_absences']['students'] === 1, "Today absences students should be 1");
        assert_presence_dash($kpis['today_delays']['occurrences'] === 1, "Today delays occurrences should be 1");
        assert_presence_dash($kpis['today_delays']['students'] === 1, "Today delays students should be 1");
        assert_presence_dash($kpis['unjustified_absences']['occurrences'] === 2, "Unjustified absences occurrences should be 2");
        assert_presence_dash($kpis['unjustified_absences']['students'] === 1, "Unjustified absences students should be 1");
        assert_presence_dash($kpis['justified_absences']['occurrences'] === 1, "Justified absences occurrences should be 1");
        assert_presence_dash($kpis['justified_absences']['students'] === 1, "Justified absences students should be 1");


        // --- TEST 2: Verification of ApexCharts Timeline 4 Mutually Exclusive Series ---
        echo "\n--- TEST 2: Verification of ApexCharts Timeline 4 Mutually Exclusive Series ---\n";

        $timeline = $dashboardData['timeline'];
        assert_presence_dash(count($timeline['series']) === 4, "Timeline must contain exactly 4 series");
        assert_presence_dash($timeline['series'][0]['name'] === 'Présents', "Series 1 must be Présents");
        assert_presence_dash($timeline['series'][1]['name'] === 'Retards', "Series 2 must be Retards");
        assert_presence_dash($timeline['series'][2]['name'] === 'Absences non justifiées', "Series 3 must be Absences non justifiées");
        assert_presence_dash($timeline['series'][3]['name'] === 'Absences justifiées', "Series 4 must be Absences justifiées");


        // --- TEST 3: Verification of Phase 6 Roster Join & Class Absence Rates ---
        echo "\n--- TEST 3: Verification of Phase 6 Roster Join & Class Absence Rates ---\n";

        $classRates = $dashboardData['class_rates'];
        assert_presence_dash(!empty($classRates), "Class rates list should not be empty");

        $targetClassRate = null;
        foreach ($classRates as $cr) {
            if ($cr['classe_id'] === $classA1) {
                $targetClassRate = $cr;
                break;
            }
        }

        assert_presence_dash($targetClassRate !== null, "Target class A1 should be present in class rates");
        assert_presence_dash($targetClassRate['occurrences_enregistrees'] === 5, "Class A1 recorded occurrences should be 5");
        assert_presence_dash($targetClassRate['occurrences_absence'] === 3, "Class A1 absence occurrences should be 3");
        assert_presence_dash($targetClassRate['taux_absence'] === 60.0, "Class A1 absence rate should be 60.0%");


        // --- TEST 4: Verification of Absenteeism Threshold Alerting ---
        echo "\n--- TEST 4: Verification of Absenteeism Threshold Alerting ---\n";

        $repeatedData = PresenceDashboardService::getDashboardData($filters, 1)['repeated_absences']['list'];
        assert_presence_dash(!empty($repeatedData), "Repeated absences list should flag Eleve 1 (threshold 2)");
        assert_presence_dash((int)$repeatedData[0]['id_eleve'] === $eleve1, "Eleve 1 should be flagged for 2 absences");


        // --- TEST 5: Verification of Teacher Scope & Multi-Tenant IDOR Protection ---
        echo "\n--- TEST 5: Verification of Teacher Scope & Multi-Tenant IDOR Protection ---\n";

        $teacherUserId = 99999;
        // Assign teacher to Class A2 ONLY
        $db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, email, mot_de_passe) VALUES ($teacherUserId, $lyceeIdA, 'Prof', 'Test', 'prof_test@sgs.local', 'hash') ON CONFLICT(id_user) DO NOTHING");
        $db->exec("INSERT INTO affectations_pedagogiques (enseignant_id, classe_id, matiere_id, annee_academique_id, date_debut, statut) VALUES ($teacherUserId, $classA2, $matiereId, $anneeId, '2025-09-01', 'actif')");

        Auth::setSessionContext([
            'id' => $teacherUserId,
            'id_user' => $teacherUserId,
            'lycee_id' => $lyceeIdA,
            'role_name' => 'enseignant',
            'permissions' => ['presence' => ['view']]
        ]);

        try {
            // Attempting to access Class A1 (unassigned to this teacher) MUST throw Exception
            PresenceDashboardService::getDashboardData([
                'lycee_id' => $lyceeIdA,
                'annee_academique_id' => $anneeId,
                'classe_id' => $classA1
            ], $teacherUserId);
            echo " [FAIL] Expected Exception for unassigned class access!\n";
            exit(1);
        } catch (Exception $e) {
            echo " [PASS] Unassigned class access rejected with message: " . $e->getMessage() . "\n";
        }

        try {
            // Attempting to access cross-tenant Lycée B MUST throw Exception
            PresenceDashboardService::getDashboardData([
                'lycee_id' => $lyceeIdB,
                'annee_academique_id' => $anneeId
            ], $teacherUserId);
            echo " [FAIL] Expected Exception for cross-tenant lycee access!\n";
            exit(1);
        } catch (Exception $e) {
            echo " [PASS] Cross-tenant Lycée access rejected with message: " . $e->getMessage() . "\n";
        }

        // Cleanup
        $db->exec("DELETE FROM presences WHERE lycee_id IN ($lyceeIdA, $lyceeIdB)");
        $db->exec("DELETE FROM etudes WHERE lycee_id IN ($lyceeIdA, $lyceeIdB)");
        $db->exec("DELETE FROM eleves WHERE lycee_id IN ($lyceeIdA, $lyceeIdB)");
        $db->exec("DELETE FROM affectations_pedagogiques WHERE annee_academique_id = $anneeId");
        $db->exec("DELETE FROM matieres WHERE lycee_id IN ($lyceeIdA, $lyceeIdB)");
        $db->exec("DELETE FROM classes WHERE lycee_id IN ($lyceeIdA, $lyceeIdB)");
        $db->exec("DELETE FROM reporting_kpi_seuils WHERE lycee_id IN ($lyceeIdA, $lyceeIdB)");

        echo "\n=== ALL PRESENCE DASHBOARD INTEGRATION TESTS PASSED SUCCESSFULLY ===\n";
    }
}

// Execute test when called
PresenceDashboardTest::run();
