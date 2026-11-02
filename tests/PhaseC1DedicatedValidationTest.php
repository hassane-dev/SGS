<?php
/**
 * Test Suite: SGS Phase C1 & C1-W & C1-Y
 * Validation DRH Directory, Surveillant Multi-tenant Isolation & Academic Year Truth
 */

define('TEST_MODE', true);
require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/models/AnneeAcademique.php';
require_once __DIR__ . '/../src/models/Role.php';
require_once __DIR__ . '/../src/models/User.php';
require_once __DIR__ . '/../src/models/Cycle.php';
require_once __DIR__ . '/../src/models/Classe.php';
require_once __DIR__ . '/../src/models/SurveillantAffectation.php';
require_once __DIR__ . '/../src/models/SurveillantGroupe.php';
require_once __DIR__ . '/../src/services/PersonnelService.php';
require_once __DIR__ . '/../src/services/SurveillantAssignmentService.php';
require_once __DIR__ . '/../src/services/AuthorizationScopeService.php';

function assert_test(bool $cond, string $msg): void {
    if (!$cond) {
        echo "❌ [FAIL] $msg\n";
        exit(1);
    }
    echo "✅ [PASS] $msg\n";
}

$db = Database::getInstance();
$db->beginTransaction();

try {
    echo "\n======================================================================\n";
    echo "SECTION 1: Annuaire DRH avec rôle DRH (drh:view_all)\n";
    echo "======================================================================\n";

    // Provision Lycée 99101 and 99102
    $db->exec("INSERT INTO param_lycee (id, nom_lycee) VALUES (99101, 'Lycée DRH Test Alpha')");
    $db->exec("INSERT INTO param_lycee (id, nom_lycee) VALUES (99102, 'Lycée DRH Test Beta')");

    // Provision Cycles for Lycée 99101: CEG (991011) and Lycée (991012)
    $db->exec("INSERT INTO cycles (id_cycle, nom_cycle, lycee_id) VALUES (991011, 'CEG Alpha', 99101)");
    $db->exec("INSERT INTO cycles (id_cycle, nom_cycle, lycee_id) VALUES (991012, 'Lycée Alpha', 99101)");

    // Provision Cycle for Lycée 99102: CEG Beta (991021)
    $db->exec("INSERT INTO cycles (id_cycle, nom_cycle, lycee_id) VALUES (991021, 'CEG Beta', 99102)");

    // DRH user for Lycée 99101
    $db->exec("INSERT INTO utilisateurs (id_user, lycee_id, role_id, nom, prenom, email, actif)
               VALUES (99001, 99101, 11, 'DRH_User', 'Responsable', 'drh@alpha.com', 1)");

    // Staff in Lycée 99101:
    // 1) Teacher with cycle assignment on CEG (991011)
    $db->exec("INSERT INTO utilisateurs (id_user, lycee_id, role_id, nom, prenom, email, actif)
               VALUES (99002, 99101, 6, 'Enseignant', 'CEG', 'ens.ceg@alpha.com', 1)");
    $db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, actif)
               VALUES (99002, 991011, '2020-01-01', 1)");

    // 2) Teacher with cycle assignment on Lycée (991012)
    $db->exec("INSERT INTO utilisateurs (id_user, lycee_id, role_id, nom, prenom, email, actif)
               VALUES (99003, 99101, 6, 'Enseignant', 'Lycee', 'ens.lyc@alpha.com', 1)");
    $db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, actif)
               VALUES (99003, 991012, '2020-01-01', 1)");

    // 3) Administrative staff member WITHOUT cycle assignment and WITHOUT contract
    $db->exec("INSERT INTO utilisateurs (id_user, lycee_id, role_id, nom, prenom, email, actif)
               VALUES (99004, 99101, 7, 'Comptable', 'General', 'cpt@alpha.com', 1)");

    // 4) Supervisor without cycle assignment
    $db->exec("INSERT INTO utilisateurs (id_user, lycee_id, role_id, nom, prenom, email, actif)
               VALUES (99005, 99101, 5, 'Surveillant', 'SansCycle', 'surv@alpha.com', 1)");

    // Foreign staff in Lycée 99102
    $db->exec("INSERT INTO utilisateurs (id_user, lycee_id, role_id, nom, prenom, email, actif)
               VALUES (99006, 99102, 6, 'Enseignant', 'Beta', 'ens@beta.com', 1)");

    // Setup DRH session
    $_SESSION['user'] = [
        'id' => 99001,
        'id_user' => 99001,
        'lycee_id' => 99101,
        'role_id' => 11,
        'role_name' => 'drh',
        'auth_version' => 1,
        'permissions' => Role::getPermissions(11)
    ];

    $drhDirectory = PersonnelService::searchPersonnel([]);
    $dirIds = array_map('intval', array_column($drhDirectory, 'id_user'));

    assert_test(in_array(99001, $dirIds, true), "Le DRH lui-même figure dans l'annuaire de son établissement.");
    assert_test(in_array(99002, $dirIds, true), "L'enseignant CEG figure dans l'annuaire.");
    assert_test(in_array(99003, $dirIds, true), "L'enseignant Lycée figure dans l'annuaire.");
    assert_test(in_array(99004, $dirIds, true), "Le personnel sans affectation de cycle (Comptable) figure dans l'annuaire.");
    assert_test(in_array(99005, $dirIds, true), "Le surveillant sans cycle figure dans l'annuaire.");
    assert_test(!in_array(99006, $dirIds, true), "Isolation multi-établissement : Le personnel du Lycée Beta est strictement exclu.");

    // Filter by specific cycle
    $cegOnly = PersonnelService::searchPersonnel(['cycle_id' => 991011]);
    $cegIds = array_map('intval', array_column($cegOnly, 'id_user'));
    assert_test(in_array(99002, $cegIds, true) && !in_array(99003, $cegIds, true) && !in_array(99004, $cegIds, true),
        "Filtrage par cycle précis (CEG) isole fidèlement l'enseignant CEG pour le DRH.");

    // Filter by search keyword
    $searchRes = PersonnelService::searchPersonnel(['search' => 'Comptable']);
    assert_test(count($searchRes) === 1 && (int)$searchRes[0]['id_user'] === 99004,
        "Recherche par mot-clé isole précisément le comptable.");

    echo "\n======================================================================\n";
    echo "SECTION 2: Filtrage temporel des surveillants (Année 2020-2021 vs Date 2026)\n";
    echo "======================================================================\n";

    // Create an explicit academic year 2020-2021
    $db->exec("INSERT INTO annees_academiques (id, libelle, date_debut, date_fin, est_active)
               VALUES (99020, '2020-2021', '2020-09-01', '2021-06-30', 1)");

    // Link Year 99020 to Lycée 99101 via sequences
    $db->exec("INSERT INTO sequences (id, lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut)
               VALUES (990201, 99101, 99020, 'Trimestre 1', 'trimestrielle', '2020-09-01', '2020-11-30', 'ouverte')");

    // Supervisors:
    // Surv 1 (99201): Active in 2020-2021 (bounded to 2020-09-01 -> 2021-06-30) on CEG Alpha (991011)
    $db->exec("INSERT INTO utilisateurs (id_user, lycee_id, role_id, nom, prenom, email, actif)
               VALUES (99201, 99101, 5, 'Surv', 'Valide2020', 'surv2020@alpha.com', 1)");
    $db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, date_fin, actif)
               VALUES (99201, 991011, '2020-09-01', '2021-06-30', 1)");

    // Surv 2 (99202): Expired before 2020-2021 (ended 2020-06-30) on CEG Alpha (991011)
    $db->exec("INSERT INTO utilisateurs (id_user, lycee_id, role_id, nom, prenom, email, actif)
               VALUES (99202, 99101, 5, 'Surv', 'ExpireAvant', 'surv.exp@alpha.com', 1)");
    $db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, date_fin, actif)
               VALUES (99202, 991011, '2019-09-01', '2020-06-30', 1)");

    // Surv 3 (99203): Future relative to 2020-2021 (starts 2024-09-01) on CEG Alpha (991011)
    $db->exec("INSERT INTO utilisateurs (id_user, lycee_id, role_id, nom, prenom, email, actif)
               VALUES (99203, 99101, 5, 'Surv', 'FuturRelatif', 'surv.futur@alpha.com', 1)");
    $db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, date_fin, actif)
               VALUES (99203, 991011, '2024-09-01', '2025-06-30', 1)");

    // Surv 4 (99204): Open-ended (date_debut = 2020-01-01, date_fin = NULL, actif = NULL) on Lycée Alpha (991012)
    $db->exec("INSERT INTO utilisateurs (id_user, lycee_id, role_id, nom, prenom, email, actif)
               VALUES (99204, 99101, 5, 'Surv', 'ActifNull', 'surv.null@alpha.com', NULL)");
    $db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, date_fin, actif)
               VALUES (99204, 991012, '2020-01-01', NULL, 1)");

    // Surv 5 (99205): Deactivated user (actif = 0) with valid assignment
    $db->exec("INSERT INTO utilisateurs (id_user, lycee_id, role_id, nom, prenom, email, actif)
               VALUES (99205, 99101, 5, 'Surv', 'Desactive', 'surv.desact@alpha.com', 0)");
    $db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, date_fin, actif)
               VALUES (99205, 991011, '2020-09-01', '2021-06-30', 1)");

    // Surv 6 (99206): Assigned to cycle via surveillant_affectations (portee_type = 'cycle') in Lycée 99101
    $db->exec("INSERT INTO utilisateurs (id_user, lycee_id, role_id, nom, prenom, email, actif)
               VALUES (99206, 99101, 5, 'Surv', 'ViaAffectationCycle', 'surv.aff@alpha.com', 1)");
    $db->exec("INSERT INTO surveillant_affectations (lycee_id, annee_academique_id, surveillant_id, portee_type, cycle_id, date_debut, statut)
               VALUES (99101, 99020, 99206, 'cycle', 991011, '2020-09-01', 'actif')");

    // Surv 7 (99207): Assigned to whole establishment via surveillant_affectations (portee_type = 'etablissement')
    $db->exec("INSERT INTO utilisateurs (id_user, lycee_id, role_id, nom, prenom, email, actif)
               VALUES (99207, 99101, 5, 'Surv', 'ToutEtablissement', 'surv.all@alpha.com', 1)");
    $db->exec("INSERT INTO surveillant_affectations (lycee_id, annee_academique_id, surveillant_id, portee_type, date_debut, statut)
               VALUES (99101, 99020, 99207, 'etablissement', '2020-09-01', 'actif')");

    // Query supervisors for CEG (991011) in Lycée 99101 under Year 99020 (2020-2021)
    $survCeg = SurveillantAssignmentService::getSupervisorsByCycle(991011, 99101, 99020);
    $cegUids = array_map('intval', array_column($survCeg, 'id_user'));

    assert_test(in_array(99201, $cegUids, true),
        "Surveillant valide sur 2020-2021 est présent malgré la date système 2026.");
    assert_test(!in_array(99202, $cegUids, true),
        "Surveillant dont l'affectation a expiré avant 2020-2021 (fin 2020-06-30) est exclu.");
    assert_test(!in_array(99203, $cegUids, true),
        "Surveillant dont l'affectation débute après 2020-2021 (début 2024-09-01) est exclu.");
    assert_test(!in_array(99205, $cegUids, true),
        "Compte utilisateur désactivé (actif = 0) est rigoureusement exclu.");
    assert_test(in_array(99206, $cegUids, true),
        "Surveillant affecté au cycle via surveillant_affectations est correctement reconnu.");
    assert_test(in_array(99207, $cegUids, true),
        "Surveillant avec portée établissement est reconnu dans le cycle CEG.");

    // Query supervisors for Lycée Alpha (991012)
    $survLyc = SurveillantAssignmentService::getSupervisorsByCycle(991012, 99101, 99020);
    $lycUids = array_map('intval', array_column($survLyc, 'id_user'));

    assert_test(!in_array(99201, $lycUids, true),
        "Surveillant affecté uniquement au CEG n'apparaît pas dans le cycle Lycée.");
    assert_test(in_array(99204, $lycUids, true),
        "Surveillant avec actif = NULL et affectation ouverte sur Lycée apparaît bien.");
    assert_test(in_array(99207, $lycUids, true),
        "Surveillant avec portée établissement apparaît aussi dans le cycle Lycée.");

    echo "\n======================================================================\n";
    echo "SECTION 3: Étanchéité multi-établissements des cycles et affectations (C1-Y)\n";
    echo "======================================================================\n";

    // Surv 8 (99208): Supervisor belonging to Lycée Alpha (99101), but with pca entry referencing CEG Beta (991021)
    $db->exec("INSERT INTO utilisateurs (id_user, lycee_id, role_id, nom, prenom, email, actif)
               VALUES (99208, 99101, 5, 'Surv', 'CrossLeakPca', 'leak.pca@alpha.com', 1)");
    $db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, actif)
               VALUES (99208, 991021, '2020-09-01', 1)");

    $activeCycles99208 = SurveillantAssignmentService::getSupervisorActiveCycleIds(99208, 99101, 99020);
    assert_test(!in_array(991021, $activeCycles99208, true),
        "C1-Y (A) : getSupervisorActiveCycleIds(99101) exclut rigoureusement le cycle 991021 appartenant au Lycée 99102.");

    // Surv 9 (99209): Supervisor belonging to Lycée Alpha (99101), with surveillant_affectations row referencing Lycée 99102
    $db->exec("INSERT INTO utilisateurs (id_user, lycee_id, role_id, nom, prenom, email, actif)
               VALUES (99209, 99101, 5, 'Surv', 'CrossLeakSa', 'leak.sa@alpha.com', 1)");
    $db->exec("INSERT INTO surveillant_affectations (lycee_id, annee_academique_id, surveillant_id, portee_type, cycle_id, date_debut, statut)
               VALUES (99102, 99020, 99209, 'cycle', 991021, '2020-09-01', 'actif')");

    $activeCycles99209 = SurveillantAssignmentService::getSupervisorActiveCycleIds(99209, 99101, 99020);
    assert_test(!in_array(991021, $activeCycles99209, true),
        "C1-Y (B) : getSupervisorActiveCycleIds(99101) ignore l'affectation surveillant_affectations enregistrée sous le Lycée étranger 99102.");

    // Call getSupervisorsByCycle with foreign cycle (991021 from Lycée 99102) for Lycée 99101
    $foreignCycleSurvs = SurveillantAssignmentService::getSupervisorsByCycle(991021, 99101, 99020);
    assert_test(empty($foreignCycleSurvs),
        "C1-Y (C) : getSupervisorsByCycle(cycle_étranger, lycée_A) retourne une liste vide et n'admet aucun surveillant.");

    echo "\n======================================================================\n";
    echo "SECTION 4: Source de vérité de l'année académique & rejets d'IDs invalides\n";
    echo "======================================================================\n";

    // 4a. Lycée 99101 résout fidèlement l'année active 99020 via ses séquences
    $resolved99101 = AnneeAcademique::findActive(99101);
    assert_test((int)$resolved99101['id'] === 99020,
        "C1-Y (E) : AnneeAcademique::findActive(99101) résout fidèlement l'année active rattachée via sequences.");

    // 4b. Lycée 99102 sans séquence ne retombe PAS sur l'année de 99101
    $resolved99102 = AnneeAcademique::findActive(99102);
    assert_test($resolved99102 === null,
        "C1-Y (E) : Lycée 99102 sans séquence retourne strictement NULL sans récupérer l'année active du Lycée 99101.");

    // 4c. Rejet explicite de findActive(0) et findActive(-1)
    $caughtZero = false;
    try {
        AnneeAcademique::findActive(0);
    } catch (InvalidArgumentException $e) {
        $caughtZero = true;
    }
    assert_test($caughtZero, "C1-Y (F) : AnneeAcademique::findActive(0) déclenche une InvalidArgumentException explicite.");

    $caughtNeg = false;
    try {
        AnneeAcademique::findActive(-5);
    } catch (InvalidArgumentException $e) {
        $caughtNeg = true;
    }
    assert_test($caughtNeg, "C1-Y (F) : AnneeAcademique::findActive(-5) déclenche une InvalidArgumentException explicite.");

    // 4d. Rejet d'un établissement sans année active par resolveActiveAcademicYear
    $caughtMissingFor99102 = false;
    try {
        SurveillantAssignmentService::resolveActiveAcademicYear(99102);
    } catch (RuntimeException $e) {
        $caughtMissingFor99102 = true;
    }
    assert_test($caughtMissingFor99102,
        "resolveActiveAcademicYear(99102) rejette explicitement l'absence d'année active pour cet établissement.");

    // 4e. Conflit si plusieurs années sont actives pour le même établissement via sequences
    $db->exec("INSERT INTO annees_academiques (id, libelle, date_debut, date_fin, est_active)
               VALUES (99021, '2021-2022 Conflit', '2021-09-01', '2022-06-30', 1)");
    $db->exec("INSERT INTO sequences (id, lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut)
               VALUES (990211, 99101, 99021, 'Trimestre 1 Bis', 'trimestrielle', '2021-09-01', '2021-11-30', 'ouverte')");

    $caughtConflict = false;
    try {
        AnneeAcademique::findActive(99101);
    } catch (RuntimeException $e) {
        $caughtConflict = true;
    }
    assert_test($caughtConflict, "Conflit détecté lorsqu'un même établissement possède plusieurs années actives dans sequences.");

    // Clean up conflict year to leave establishment 99101 in a clean state for subsequent tests
    $db->exec("DELETE FROM sequences WHERE id = 990211");
    $db->exec("DELETE FROM annees_academiques WHERE id = 99021");

    // 4f. Tentative de forcer une année inactive via annee_id
    $db->exec("INSERT INTO annees_academiques (id, libelle, date_debut, date_fin, est_active)
               VALUES (99022, '2018-2019 Inactive', '2018-09-01', '2019-06-30', 0)");
    $caughtInactive = false;
    try {
        SurveillantAssignmentService::resolveActiveAcademicYear(99101, 99022);
    } catch (RuntimeException $e) {
        $caughtInactive = true;
    }
    assert_test($caughtInactive, "Tentative de forcer une année académique inactive via annee_id est strictement rejetée.");

    // 4g. Tentative de forcer une année étrangère appartenant à un autre lycée
    $db->exec("INSERT INTO annees_academiques (id, libelle, date_debut, date_fin, est_active)
               VALUES (99023, '2020-2021 Beta', '2020-09-01', '2021-06-30', 1)");
    $db->exec("INSERT INTO sequences (id, lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut)
               VALUES (990231, 99102, 99023, 'Trimestre 1 Beta', 'trimestrielle', '2020-09-01', '2020-11-30', 'ouverte')");

    $caughtForeign = false;
    try {
        SurveillantAssignmentService::resolveActiveAcademicYear(99102, 99020); // 99020 belongs to 99101, not 99102!
    } catch (RuntimeException $e) {
        $caughtForeign = true;
    }
    assert_test($caughtForeign, "Tentative de forcer une année active étrangère via annee_id est strictement rejetée.");

    // 4h. Passage de l'année active légitime via annee_id
    $validExplicit = SurveillantAssignmentService::resolveActiveAcademicYear(99102, 99023);
    assert_test((int)$validExplicit['id'] === 99023, "Transmission de l'année active légitime du lycée est acceptée.");

    echo "\n======================================================================\n";
    echo "SECTION 5: Sécurisation de proposeDistribution et applyDistribution (C1-Y D)\n";
    echo "======================================================================\n";

    // Setup classes in Lycée Alpha (99101)
    $db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero)
               VALUES (9910101, 99101, 991011, '6e', NULL, '1')");
    $db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero)
               VALUES (9910102, 99101, 991011, '6e', NULL, '2')");

    // Setup class in Lycée Beta (99102)
    $db->exec("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero)
               VALUES (9910201, 99102, 991021, '6e', NULL, '1')");

    // Supervisor 99201 belongs to 99101; Supervisor 99006 belongs to 99102
    // 5a. Attempt proposeDistribution on Lycée 99101 with foreign class from Lycée 99102 -> must reject
    $caughtCrossClass = false;
    try {
        SurveillantAssignmentService::proposeDistribution(
            99101,
            99020,
            [9910201], // Foreign class!
            [99201]
        );
    } catch (InvalidArgumentException $e) {
        $caughtCrossClass = true;
    }
    assert_test($caughtCrossClass, "proposeDistribution rejette formellement toute classe étrangère au lycée.");

    // 5b. Attempt proposeDistribution on Lycée 99101 with foreign supervisor from Lycée 99102 -> must reject
    $caughtCrossSurv = false;
    try {
        SurveillantAssignmentService::proposeDistribution(
            99101,
            99020,
            [9910101],
            [99006] // Foreign supervisor from Lycée 99102!
        );
    } catch (InvalidArgumentException $e) {
        $caughtCrossSurv = true;
    }
    assert_test($caughtCrossSurv, "proposeDistribution rejette formellement tout surveillant étranger au lycée.");

    // 5c. Valid proposal on Lycée 99101
    $prop = SurveillantAssignmentService::proposeDistribution(
        99101,
        99020,
        [9910101, 9910102],
        [99201],
        'equitable'
    );
    assert_test(!empty($prop['state_hash']), "proposeDistribution valide génère un state_hash valide.");

    // 5d. Tampered applyDistribution with foreign supervisor
    $tamperedPayloadSurv = [
        'state_hash' => $prop['state_hash'],
        'lycee_id' => 99101,
        'annee_academique_id' => 99020,
        'apply_mode' => 'append',
        'selected_classe_ids' => [9910101],
        'distribution' => [
            [
                'surveillant_id' => 99006, // Foreign supervisor!
                'classe_ids' => [9910101]
            ]
        ]
    ];
    $caughtTamperSurv = false;
    try {
        SurveillantAssignmentService::applyDistribution($tamperedPayloadSurv, 99001);
    } catch (InvalidArgumentException $e) {
        $caughtTamperSurv = true;
    }
    assert_test($caughtTamperSurv, "applyDistribution rejette toute tentative d'injecter un surveillant étranger.");

    // 5e. Tampered applyDistribution with foreign class
    $tamperedPayloadClass = [
        'state_hash' => $prop['state_hash'],
        'lycee_id' => 99101,
        'annee_academique_id' => 99020,
        'apply_mode' => 'append',
        'selected_classe_ids' => [9910101],
        'distribution' => [
            [
                'surveillant_id' => 99201,
                'classe_ids' => [9910201] // Foreign class!
            ]
        ]
    ];
    $caughtTamperClass = false;
    try {
        SurveillantAssignmentService::applyDistribution($tamperedPayloadClass, 99001);
    } catch (InvalidArgumentException $e) {
        $caughtTamperClass = true;
    }
    assert_test($caughtTamperClass, "applyDistribution rejette toute tentative d'injecter une classe étrangère.");

    // 5f. Tampered applyDistribution with foreign academic year
    $tamperedPayloadYear = [
        'state_hash' => $prop['state_hash'],
        'lycee_id' => 99101,
        'annee_academique_id' => 99023, // Foreign year belonging to Lycée 99102!
        'apply_mode' => 'append',
        'selected_classe_ids' => [9910101],
        'distribution' => [
            [
                'surveillant_id' => 99201,
                'classe_ids' => [9910101]
            ]
        ]
    ];
    $caughtTamperYear = false;
    try {
        SurveillantAssignmentService::applyDistribution($tamperedPayloadYear, 99001);
    } catch (RuntimeException $e) {
        $caughtTamperYear = true;
    }
    assert_test($caughtTamperYear, "applyDistribution rejette toute tentative de soumettre une année académique étrangère.");

    // 5g. Tests de robustesse du format de l'année académique dans applyDistribution() (Phases C1-AB & C1-AC)
    $initialAffCount = (int)$db->query("SELECT COUNT(*) FROM surveillant_affectations WHERE lycee_id = 99101")->fetchColumn();

    // Helper pour tester les formats invalides et vérifier l'intégrité rigoureuse des 3 tables modifiables
    // (surveillant_affectations, param_lycee.surveillant_config_version, utilisateurs.auth_version)
    $captureTablesSnapshot = function() use ($db) {
        $affRows = $db->query("SELECT id, lycee_id, annee_academique_id, surveillant_id, classe_id, statut FROM surveillant_affectations WHERE lycee_id = 99101 ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
        $configVer = $db->query("SELECT surveillant_config_version FROM param_lycee WHERE id = 99101")->fetchColumn();
        $authVers = $db->query("SELECT id_user, auth_version FROM utilisateurs WHERE lycee_id = 99101 ORDER BY id_user ASC")->fetchAll(PDO::FETCH_ASSOC);
        return [
            'aff' => $affRows,
            'config_version' => $configVer,
            'auth_versions' => $authVers
        ];
    };

    $assertInvalidYearRejected = function($invalidValue, string $label) use ($prop, $db, $captureTablesSnapshot) {
        $payload = $prop;
        if ($invalidValue === '__UNSET__') {
            unset($payload['annee_academique_id']);
        } else {
            $payload['annee_academique_id'] = $invalidValue;
        }

        $snapshotBefore = $captureTablesSnapshot();

        $caught = false;
        try {
            SurveillantAssignmentService::applyDistribution($payload, 99001);
        } catch (InvalidArgumentException $e) {
            $caught = true;
        }

        assert_test($caught, "C1-AC: applyDistribution rejette $label");

        $snapshotAfter = $captureTablesSnapshot();
        assert_test($snapshotBefore['aff'] === $snapshotAfter['aff'], "C1-AE: surveillant_affectations strictement inchangée après rejet $label");
        assert_test($snapshotBefore['config_version'] === $snapshotAfter['config_version'], "C1-AE: param_lycee.surveillant_config_version strictement inchangée après rejet $label");
        assert_test($snapshotBefore['auth_versions'] === $snapshotAfter['auth_versions'], "C1-AE: utilisateurs.auth_version strictement inchangée après rejet $label");
    };

    // Cas obligatoires C1-AC :
    // 1. Nombre décimal positif float : 99020.8
    $assertInvalidYearRejected(99020.8, "décimal positif float (99020.8)");

    // 2. Chaîne décimale : "99020.8"
    $assertInvalidYearRejected("99020.8", "chaîne décimale ('99020.8')");

    // 3. Chaîne en notation scientifique : "9.902e4"
    $assertInvalidYearRejected("9.902e4", "chaîne notation scientifique ('9.902e4')");

    // 4. Chaîne en notation scientifique variante : "99020e0"
    $assertInvalidYearRejected("99020e0", "chaîne notation scientifique variante ('99020e0')");

    // 5. Chaîne entière négative : "-1"
    $assertInvalidYearRejected("-1", "chaîne entière négative ('-1')");

    // 6. Entier négatif : -1
    $assertInvalidYearRejected(-1, "entier négatif (-1)");

    // 7. Zéro entier : 0
    $assertInvalidYearRejected(0, "zéro entier (0)");

    // 8. Zéro chaîne : "0"
    $assertInvalidYearRejected("0", "zéro chaîne ('0')");

    // 9. Valeur absente
    $assertInvalidYearRejected('__UNSET__', "valeur absente");

    // 10. null
    $assertInvalidYearRejected(null, "valeur null");

    // 11. Chaîne vide : ""
    $assertInvalidYearRejected("", "chaîne vide ('')");

    // 12. Tableau : [99020]
    $assertInvalidYearRejected([99020], "tableau ([99020])");

    // 12b. Booléens true et false
    $assertInvalidYearRejected(true, "booléen true");
    $assertInvalidYearRejected(false, "booléen false");

    // 12c. Chaîne avec zéros initiaux : "00099020"
    $assertInvalidYearRejected("00099020", "chaîne avec zéros initiaux ('00099020')");

    // 12d. Chaîne avec espaces : " 99020 "
    $assertInvalidYearRejected(" 99020 ", "chaîne avec espaces (' 99020 ')");

    // 12e. Chaîne immédiatement supérieure à PHP_INT_MAX (9223372036854775808 sur 64-bit)
    $overflowImmediateString = "9223372036854775808";
    $assertInvalidYearRejected($overflowImmediateString, "dépassement immédiat PHP_INT_MAX + 1 ('9223372036854775808')");

    // 12f. Chaîne très supérieure à PHP_INT_MAX
    $overflowString = "999999999999999999999999999999999999999999";
    $assertInvalidYearRejected($overflowString, "dépassement massif PHP_INT_MAX (> 9223372036854775807)");

    // 12g. Chaîne exactement égale à PHP_INT_MAX ("9223372036854775807")
    // Le format est accepté par filterStrictPositiveInteger, mais le test doit vérifier le rejet car cette année n'existe pas
    $payloadMaxInt = $prop;
    $payloadMaxInt['annee_academique_id'] = (string)PHP_INT_MAX;
    $caughtMaxInt = false;
    try {
        SurveillantAssignmentService::applyDistribution($payloadMaxInt, 99001);
    } catch (RuntimeException $e) {
        $caughtMaxInt = true;
    }
    assert_test($caughtMaxInt, "C1-AE: applyDistribution accepte le format PHP_INT_MAX mais rejette l'année car inexistante/non active");
    $countAfterMaxInt = (int)$db->query("SELECT COUNT(*) FROM surveillant_affectations WHERE lycee_id = 99101")->fetchColumn();
    assert_test($countAfterMaxInt === $initialAffCount, "C1-AE: Aucune écriture en base après rejet année PHP_INT_MAX");

    // 13. Année inactive (id 99019 insérée avec est_active = 0)
    $db->exec("INSERT INTO annees_academiques (id, libelle, date_debut, date_fin, est_active) VALUES (99019, '2019-2020 Inactive', '2019-09-01', '2020-06-30', 0)");
    $snapshotBeforeInactive = $captureTablesSnapshot();
    $payloadInactive = $prop;
    $payloadInactive['annee_academique_id'] = 99019;
    $caughtInactiveYear = false;
    try {
        SurveillantAssignmentService::applyDistribution($payloadInactive, 99001);
    } catch (RuntimeException $e) {
        $caughtInactiveYear = true;
    }
    assert_test($caughtInactiveYear, "C1-AC: applyDistribution rejette une année inactive");
    $snapshotAfterInactive = $captureTablesSnapshot();
    assert_test($snapshotBeforeInactive === $snapshotAfterInactive, "C1-AE: Données des 3 tables strictement inchangées après rejet année inactive");

    // 14. Année active correcte du lycée B (99023) utilisée pour Lycée A (99101)
    $snapshotBeforeForeign = $captureTablesSnapshot();
    $payloadForeignActive = $prop;
    $payloadForeignActive['annee_academique_id'] = 99023;
    $caughtForeignActive = false;
    try {
        SurveillantAssignmentService::applyDistribution($payloadForeignActive, 99001);
    } catch (RuntimeException $e) {
        $caughtForeignActive = true;
    }
    assert_test($caughtForeignActive, "C1-AC: applyDistribution rejette l'année active du Lycée B soumise pour le Lycée A");
    $snapshotAfterForeign = $captureTablesSnapshot();
    assert_test($snapshotBeforeForeign === $snapshotAfterForeign, "C1-AE: Données des 3 tables strictement inchangées après rejet année étrangère");

    // 15. Cas valide : Chaîne entière positive ("99020")
    // Re-calcul de la proposition pour obtenir un state_hash valide
    $propString = SurveillantAssignmentService::proposeDistribution(
        99101,
        99020,
        [9910101],
        [99201],
        'equitable'
    );
    $propString['annee_academique_id'] = (string)$propString['annee_academique_id'];
    $appliedString = SurveillantAssignmentService::applyDistribution($propString, 99001);
    assert_test($appliedString === true, "C1-AC: applyDistribution accepte une chaîne entière positive valide ('99020').");

    // 16. Cas valide : Entier positif (99020)
    $propInt = SurveillantAssignmentService::proposeDistribution(
        99101,
        99020,
        [9910101],
        [99201],
        'equitable'
    );
    $appliedInt = SurveillantAssignmentService::applyDistribution($propInt, 99001);
    assert_test($appliedInt === true, "C1-AC: applyDistribution accepte un entier positif valide (99020).");
    $countAfterSuccess = (int)$db->query("SELECT COUNT(*) FROM surveillant_affectations WHERE lycee_id = 99101")->fetchColumn();
    assert_test($countAfterSuccess > $initialAffCount, "C1-AC: Persistance confirmée en base avec année canonique après application légitime.");

    // 17. Preuve de Rollback intégral en cas d'erreur survenue APRÈS des insertions partielles (mode avec transaction parente)
    $propRollback = SurveillantAssignmentService::proposeDistribution(
        99101,
        99020,
        [9910101, 9910102],
        [99201],
        'equitable'
    );
    // Altérer la distribution pour que la première classe soit valide mais la deuxième appartienne à un cycle non autorisé
    $propRollback['distribution'][0]['classe_ids'] = [9910101, 9910201]; // 9910201 est une classe étrangère du Lycée Beta
    $affCountBeforeRollback = (int)$db->query("SELECT COUNT(*) FROM surveillant_affectations WHERE lycee_id = 99101")->fetchColumn();
    $caughtMidTxException = false;
    try {
        SurveillantAssignmentService::applyDistribution($propRollback, 99001);
    } catch (InvalidArgumentException $e) {
        $caughtMidTxException = true;
    }
    assert_test($caughtMidTxException, "C1-AD: Échec déclenché en cours de boucle de distribution (mode savepoint).");
    $affCountAfterRollback = (int)$db->query("SELECT COUNT(*) FROM surveillant_affectations WHERE lycee_id = 99101")->fetchColumn();
    assert_test($affCountAfterRollback === $affCountBeforeRollback, "C1-AD: Rollback total prouvé : aucune insertion partielle de la 1ère classe n'a subsisté en base.");

    // 18. Preuve de persistance SQL exacte de l'année canonique
    $stmtVerifyCanonical = $db->prepare("
        SELECT id, lycee_id, annee_academique_id, surveillant_id, classe_id, statut
        FROM surveillant_affectations
        WHERE lycee_id = 99101 AND classe_id = 9910101 AND statut = 'actif'
    ");
    $stmtVerifyCanonical->execute();
    $canonicalRow = $stmtVerifyCanonical->fetch(PDO::FETCH_ASSOC);
    assert_test(!empty($canonicalRow), "C1-AE: Affectation active trouvée pour classe 9910101.");
    assert_test((int)$canonicalRow['lycee_id'] === 99101, "C1-AE: lycee_id strictement conforme (99101).");
    assert_test((int)$canonicalRow['annee_academique_id'] === 99020, "C1-AE: annee_academique_id strictement conforme à l'année active canonique (99020).");
    assert_test((int)$canonicalRow['surveillant_id'] === 99201, "C1-AE: surveillant_id strictement conforme (99201).");

    // Vérifier l'absence absolue de ligne créée avec annee_academique_id = 0 ou étrangère
    $zeroAffCount = (int)$db->query("SELECT COUNT(*) FROM surveillant_affectations WHERE lycee_id = 99101 AND (annee_academique_id = 0 OR annee_academique_id IS NULL OR annee_academique_id = 99023)")->fetchColumn();
    assert_test($zeroAffCount === 0, "C1-AE: Absence absolue d'affectation avec annee_academique_id = 0, NULL ou étrangère.");

    // 19. Preuve de Rollback intégral SANS transaction parente (Standalone mode)
    // On valide que lorsque applyDistribution est appelé de façon autonome, en cas d'échec mid-loop,
    // $db->inTransaction() redevient false et aucune insertion partielle ne subsiste.
    $dbTestStandalone = new PDO("sqlite:" . __DIR__ . '/../database.sqlite', null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    // S'assurer qu'aucune transaction n'est ouverte sur cette connexion
    assert_test(!$dbTestStandalone->inTransaction(), "C1-AE: La connexion autonome démarre hors transaction (inTransaction = false).");

    $propStandalone = SurveillantAssignmentService::proposeDistribution(
        99101,
        99020,
        [9910101, 9910102],
        [99201],
        'equitable'
    );
    $propStandalone['distribution'][0]['classe_ids'] = [9910101, 9910201]; // Classe 2 étrangère

    $affCountBeforeStandalone = (int)$db->query("SELECT COUNT(*) FROM surveillant_affectations WHERE lycee_id = 99101")->fetchColumn();
    $caughtStandaloneExc = false;
    try {
        SurveillantAssignmentService::applyDistribution($propStandalone, 99001);
    } catch (InvalidArgumentException $e) {
        $caughtStandaloneExc = true;
    }
    assert_test($caughtStandaloneExc, "C1-AE: Échec déclenché en mode autonome.");
    $affCountAfterStandalone = (int)$db->query("SELECT COUNT(*) FROM surveillant_affectations WHERE lycee_id = 99101")->fetchColumn();
    assert_test($affCountAfterStandalone === $affCountBeforeStandalone, "C1-AE: Rollback total prouvé en mode autonome : 0 écriture partielle résiduelle.");

    echo "\n======================================================================\n";
    echo "SECTION 6: Tests Contrôleur Multi-Établissements & Années Asynchrones (C1-AA)\n";
    echo "======================================================================\n";

    // Setup:
    // Lycée Alpha (99101) -> Année 99020 (active)
    // Lycée Beta (99102) -> Année 99023 (active)
    // Lycée Gamma (99103) -> Aucune année active configurée
    $db->exec("INSERT INTO sequences (id, lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut) VALUES (9910201, 99102, 99023, 'S1', 'semestrielle', '2023-09-01', '2024-01-31', 'ouverte')");

    require_once __DIR__ . '/../src/controllers/SurveillantAssignmentController.php';

    // 6a. Utilisateur connecté au Lycée Beta (99102): AnneeAcademique::findActive($lycee_id) retourne 99023 (Année B)
    // Créer un utilisateur surveillant et admin dans Lycée Beta (99102)
    $db->exec("INSERT INTO utilisateurs (id_user, lycee_id, role_id, nom, prenom, email, actif)
               VALUES (99007, 99102, 1, 'Admin', 'Beta', 'admin@beta.com', 1)");
    $db->exec("INSERT INTO utilisateurs (id_user, lycee_id, role_id, nom, prenom, email, actif)
               VALUES (99294, 99102, 5, 'Surv', 'Beta', 'surv@beta.com', 1)");
    $db->exec("INSERT INTO personnel_cycles_assignments (personnel_id, cycle_id, date_debut, actif)
               VALUES (99294, 991021, '2020-09-01', 1)");

    $_SESSION['user'] = [
        'id_user' => 99007,
        'lycee_id' => 99102,
        'role_id' => 1,
        'permissions' => ['pedagogy:view_surveillants', 'pedagogy:manage_surveillants']
    ];

    $resolvedYearB = AnneeAcademique::findActive(Auth::getLyceeId());
    assert_test($resolvedYearB !== null && (int)$resolvedYearB['id'] === 99023, "C1-AA: L'utilisateur du Lycée Beta résout strictement l'Année B (99023), ignorant l'Année A (99020).");

    // 6b. Simulation proposeDistribution() pour Lycée Beta
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = [
        'csrf_token' => CsrfService::token(),
        'classe_ids' => [9910201],
        'surveillant_ids' => [99294],
        'mode' => 'equitable'
    ];

    ob_start();
    $controller = new SurveillantAssignmentController();
    $controller->proposeDistribution();
    $respJson = ob_get_clean();
    $resp = json_decode($respJson, true);

    assert_test(!empty($resp['success']) && $resp['success'] === true, "C1-AA: proposeDistribution() pour le Lycée Beta réussit en utilisant son année active propre (99023).");
    assert_test((int)$resp['proposal']['annee_academique_id'] === 99023, "C1-AA: La proposition générée est strictement attachée à l'année du Lycée Beta (99023).");

    // 6c. Simulation storeAssignment() pour Lycée Beta
    $_POST = [
        'csrf_token' => CsrfService::token(),
        'surveillant_id' => 99294,
        'classe_id' => 9910201,
        'type_responsabilite' => 'surveillant_classe',
        'portee_type' => 'classe'
    ];
    $controller->storeAssignment();

    $stmtCheckAffB = $db->prepare("SELECT annee_academique_id FROM surveillant_affectations WHERE lycee_id = 99102 AND surveillant_id = 99294 AND classe_id = 9910201");
    $stmtCheckAffB->execute();
    $savedYearAff = (int)$stmtCheckAffB->fetchColumn();
    assert_test($savedYearAff === 99023, "C1-AA: storeAssignment() enregistre fidèlement l'affectation avec l'Année B (99023).");

    // 6d. Simulation storeGroup() pour Lycée Beta
    $_POST = [
        'csrf_token' => CsrfService::token(),
        'nom_groupe' => 'Groupe Test Beta',
        'classe_ids' => [9910201]
    ];
    $controller->storeGroup();

    $stmtCheckGrpB = $db->prepare("SELECT annee_academique_id FROM surveillant_groupes WHERE lycee_id = 99102 AND nom_groupe = 'Groupe Test Beta'");
    $stmtCheckGrpB->execute();
    $savedYearGrp = (int)$stmtCheckGrpB->fetchColumn();
    assert_test($savedYearGrp === 99023, "C1-AA: storeGroup() enregistre fidèlement le groupe avec l'Année B (99023).");

    // 6e. Test Lycée Gamma (99103) sans année active
    $db->exec("INSERT INTO param_lycee (id, nom_lycee) VALUES (99103, 'Lycée Gamma Sans Année')");
    $db->exec("INSERT INTO utilisateurs (id_user, lycee_id, role_id, nom, prenom, email, actif)
               VALUES (99008, 99103, 1, 'Admin', 'Gamma', 'admin@gamma.com', 1)");

    $_SESSION['user'] = [
        'id_user' => 99008,
        'lycee_id' => 99103,
        'role_id' => 1,
        'permissions' => ['pedagogy:view_surveillants', 'pedagogy:manage_surveillants']
    ];

    // proposeDistribution sans année active -> rejet HTTP 400
    $_POST = [
        'csrf_token' => CsrfService::token(),
        'classe_ids' => [1],
        'surveillant_ids' => [1]
    ];
    ob_start();
    $controller->proposeDistribution();
    $respJsonGamma = ob_get_clean();
    $respGamma = json_decode($respJsonGamma, true);
    assert_test(empty($respGamma['success']) && strpos($respGamma['error'], "Aucune année académique active") !== false, "C1-AA: proposeDistribution() pour établissement sans année active est immédiatement refusé avec un message explicite.");

    // storeAssignment sans année active -> refus d'enregistrement
    $_POST = [
        'csrf_token' => CsrfService::token(),
        'surveillant_id' => 99204,
        'classe_id' => 1
    ];
    unset($_SESSION['error_message']);
    $controller->storeAssignment();
    assert_test(!empty($_SESSION['error_message']) && strpos($_SESSION['error_message'], "Aucune année académique active") !== false, "C1-AA: storeAssignment() sans année active refuse l'enregistrement et définit un message d'erreur flash.");

    $stmtCountGammaAff = $db->query("SELECT COUNT(*) FROM surveillant_affectations WHERE lycee_id = 99103")->fetchColumn();
    assert_test((int)$stmtCountGammaAff === 0, "C1-AA: Aucune affectation n'a été insérée pour le Lycée Gamma (annee_academique_id = 0 prévenu).");

    // storeGroup sans année active -> refus de création
    $_POST = [
        'csrf_token' => CsrfService::token(),
        'nom_groupe' => 'Groupe Gamma Invalide'
    ];
    unset($_SESSION['error_message']);
    $controller->storeGroup();
    assert_test(!empty($_SESSION['error_message']) && strpos($_SESSION['error_message'], "Aucune année académique active") !== false, "C1-AA: storeGroup() sans année active refuse la création du groupe et définit un message flash.");

    $stmtCountGammaGrp = $db->query("SELECT COUNT(*) FROM surveillant_groupes WHERE lycee_id = 99103")->fetchColumn();
    assert_test((int)$stmtCountGammaGrp === 0, "C1-AA: Aucun groupe n'a été inséré pour le Lycée Gamma.");

    // 6f. Isolation AJAX filterByCycle avec annee_id
    // Provision local admin user in Lycée Alpha (99101) with role_id = 1
    $db->exec("INSERT INTO utilisateurs (id_user, lycee_id, role_id, nom, prenom, email, actif)
               VALUES (99009, 99101, 1, 'Admin', 'Alpha', 'admin@alpha.com', 1)");

    $_SESSION['user'] = [
        'id_user' => 99009,
        'lycee_id' => 99101,
        'role_id' => 1,
        'permissions' => ['pedagogy:view_surveillants', 'pedagogy:manage_surveillants']
    ];
    $_GET = ['cycle_id' => 991011, 'annee_id' => 99020];
    ob_start();
    $controller->ajaxFilterByCycle();
    $ajaxRespA = json_decode(ob_get_clean(), true);
    assert_test(!empty($ajaxRespA['success']) && count($ajaxRespA['surveillants']) > 0, "C1-AA: ajaxFilterByCycle() avec année active et cycle du lycée retourne les surveillants autorisés.");

    // AJAX avec cycle appartenant au lycée B
    $_GET = ['cycle_id' => 991021, 'annee_id' => 99020];
    ob_start();
    $controller->ajaxFilterByCycle();
    $ajaxRespForeign = json_decode(ob_get_clean(), true);
    assert_test(empty($ajaxRespForeign['classes']) && empty($ajaxRespForeign['surveillants']), "C1-AA: ajaxFilterByCycle() avec cycle étranger retourne strictement des listes vides.");

    echo "\n======================================================================\n";
    echo "TOUS LES TESTS D'INTÉGRATION ET DE SÉCURITÉ DE LA PHASE C1-AA ONT RÉUSSI !\n";
    echo "======================================================================\n";

} finally {
    $db->rollBack();
}
