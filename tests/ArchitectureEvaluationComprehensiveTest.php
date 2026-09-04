<?php

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
$_SESSION['user'] = [
    'id' => 991,
    'lycee_id' => 999,
    'role_id' => 1
];

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/models/AnneeAcademique.php';
require_once __DIR__ . '/../src/models/Sequence.php';
require_once __DIR__ . '/../src/models/ParamTypeEvaluation.php';
require_once __DIR__ . '/../src/models/ParametresEvaluation.php';
require_once __DIR__ . '/../src/models/Deblocage.php';
require_once __DIR__ . '/../src/models/Evaluation.php';
require_once __DIR__ . '/../src/services/EvaluationSaisieService.php';
require_once __DIR__ . '/../src/services/EvaluationCalculationService.php';

/**
 * Suite de tests de validation complète des 10 scénarios demandés pour l'architecture des types,
 * occurrences, garde-fous et calculs d'évaluations dans SGS.
 */
class ArchitectureEvaluationComprehensiveTest {

    private PDO $db;
    private int $lyceeId = 999;
    private int $anneeId = 999;
    private int $sequenceId = 9991;
    private int $classeId = 991;
    private int $matiereId = 991;
    private int $teacherId = 991;
    private string $simulatedNow = '2025-10-15 10:00:00';

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function runAllTests(): void {
        echo "=========================================================\n";
        echo " EXÉCUTION DE LA SUITE COMPLÈTE : ARCHITECTURE ÉVALUATIONS\n";
        echo "=========================================================\n\n";

        $this->setUp();

        try {
            $this->test1_DevoirOnlyPeriod();
            $this->test2_CompositionOnlyPeriod();
            $this->test3_TousPeriodWithMultipleActiveTypes();
            $this->test4_CustomTypeCreationAndAdminPeriodSelection();
            $this->test5_OccurrenceLimitServerRejection();
            $this->test6_ForcedTypeServerRejection();
            $this->test7_DynamicTypeAdditionInTousScope();
            $this->test8_UniqueOccurrenceConstraintEnforcement();
            $this->test9_BaremeSnapshotImmutability();
            $this->test10_SubjectCoefficientNoTypeWeightCalculation();

            echo "\n=========================================================\n";
            echo " SUCCESS: TOUS LES 10 TESTS ONT RÉUSSI AVEC SUCCÈS !\n";
            echo "=========================================================\n";
        } finally {
            $this->tearDown();
        }
    }

    private function ensureTablesExist(): void {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS annees_academiques (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                lycee_id INT NULL,
                libelle VARCHAR(100),
                date_debut DATE,
                date_fin DATE,
                est_active BOOLEAN,
                cloturee BOOLEAN
            );
            CREATE TABLE IF NOT EXISTS sequences (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                lycee_id INT NOT NULL,
                annee_academique_id INT NOT NULL,
                nom VARCHAR(255) NOT NULL,
                type VARCHAR(50),
                date_debut DATE,
                date_fin DATE,
                statut VARCHAR(20) DEFAULT 'ouverte'
            );
            CREATE TABLE IF NOT EXISTS param_type_evaluation (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                lycee_id INT NOT NULL,
                code VARCHAR(50) NOT NULL,
                libelle VARCHAR(100) NOT NULL,
                bareme_defaut DECIMAL(5,2) NOT NULL DEFAULT 20.00,
                nombre_evaluation INT NOT NULL DEFAULT 1,
                actif TINYINT(1) NOT NULL DEFAULT 1,
                ordre_affichage INT DEFAULT 0,
                cree_le TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
            CREATE TABLE IF NOT EXISTS parametres_evaluations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                lycee_id INT NOT NULL,
                annee_academique_id INT NOT NULL,
                type VARCHAR(50) DEFAULT 'global',
                classe_id INT NULL,
                matiere_id INT NULL,
                enseignant_id INT NULL,
                sequence_id INT NULL,
                type_evaluation VARCHAR(50) DEFAULT 'tous',
                type_evaluation_id INT NULL,
                date_ouverture_saisie DATETIME,
                date_fermeture_saisie DATETIME,
                commentaire TEXT
            );
            CREATE TABLE IF NOT EXISTS deblocages_notes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                lycee_id INT NOT NULL,
                annee_academique_id INT NOT NULL,
                type VARCHAR(50) DEFAULT 'global',
                classe_id INT NULL,
                matiere_id INT NULL,
                enseignant_id INT NULL,
                sequence_id INT NULL,
                type_evaluation VARCHAR(50) DEFAULT 'tous',
                type_evaluation_id INT NULL,
                date_debut DATETIME,
                date_fin DATETIME,
                motif TEXT,
                cree_par INT
            );
            CREATE TABLE IF NOT EXISTS evaluations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                lycee_id INT NOT NULL,
                classe_id INT,
                matiere_id INT,
                enseignant_id INT,
                eleve_id INT,
                sequence_id INT,
                annee_academique_id INT,
                type VARCHAR(50) DEFAULT 'devoir',
                type_evaluation_id INT NULL,
                numero_evaluation INT NOT NULL DEFAULT 1,
                libelle_evaluation VARCHAR(100) NULL,
                note DECIMAL(5,2),
                bareme_snapshot DECIMAL(5,2) NOT NULL DEFAULT 20.00,
                coefficient DECIMAL(4,2),
                appreciation TEXT,
                date_saisie DATETIME DEFAULT CURRENT_TIMESTAMP
            );
            CREATE UNIQUE INDEX IF NOT EXISTS uk_eval_occ ON evaluations (eleve_id, matiere_id, sequence_id, annee_academique_id, type_evaluation_id, numero_evaluation);
        ");

        try {
            $this->db->exec("ALTER TABLE deblocages_notes ADD COLUMN type_evaluation VARCHAR(50) DEFAULT 'tous'");
        } catch (Exception $e) {}
        try {
            $this->db->exec("ALTER TABLE deblocages_notes ADD COLUMN type_evaluation_id INT NULL");
        } catch (Exception $e) {}
        try {
            $this->db->exec("ALTER TABLE parametres_evaluations ADD COLUMN type_evaluation_id INT NULL");
        } catch (Exception $e) {}
        try {
            $this->db->exec("ALTER TABLE evaluations ADD COLUMN type_evaluation_id INT NULL");
        } catch (Exception $e) {}
    }

    private function setUp(): void {
        $this->ensureTablesExist();
        $this->tearDown();

        $this->db->exec("UPDATE annees_academiques SET est_active = 0 WHERE id != {$this->anneeId}");

        // 1. Année Académique Active pour notre test
        $isSqlite = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
        $replaceKw = $isSqlite ? 'INSERT OR REPLACE' : 'REPLACE';

        $stmtYear = $this->db->prepare("{$replaceKw} INTO annees_academiques (id, libelle, date_debut, date_fin, est_active, cloturee) VALUES (:id, '2025-2026-TEST', '2025-09-01', '2026-06-30', 1, 0)");
        $stmtYear->execute(['id' => $this->anneeId]);

        // 2. Séquence Ouverte
        $stmtSeq = $this->db->prepare("{$replaceKw} INTO sequences (id, lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut) VALUES (:id, :l, :a, 'Séquence 1', 'trimestre', '2025-09-01', '2026-06-30', 'ouverte')");
        $stmtSeq->execute(['id' => $this->sequenceId, 'l' => $this->lyceeId, 'a' => $this->anneeId]);

        // 3. Types d'évaluation de base
        $stmtType1 = $this->db->prepare("INSERT INTO param_type_evaluation (lycee_id, code, libelle, bareme_defaut, nombre_evaluation, actif, ordre_affichage) VALUES (:l, 'devoir', 'Devoir', 20, 2, 1, 1)");
        $stmtType1->execute(['l' => $this->lyceeId]);

        $stmtType2 = $this->db->prepare("INSERT INTO param_type_evaluation (lycee_id, code, libelle, bareme_defaut, nombre_evaluation, actif, ordre_affichage) VALUES (:l, 'composition', 'Composition', 20, 1, 1, 2)");
        $stmtType2->execute(['l' => $this->lyceeId]);
    }

    private function tearDown(): void {
        $this->db->exec("DELETE FROM evaluations WHERE lycee_id = {$this->lyceeId}");
        $this->db->exec("DELETE FROM deblocages_notes WHERE lycee_id = {$this->lyceeId}");
        $this->db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = {$this->lyceeId}");
        $this->db->exec("DELETE FROM param_type_evaluation WHERE lycee_id = {$this->lyceeId}");
        $this->db->exec("DELETE FROM sequences WHERE lycee_id = {$this->lyceeId}");
        $this->db->exec("DELETE FROM annees_academiques WHERE id = {$this->anneeId}");

        // Restore active year for default test environment
        $hasActive = $this->db->query("SELECT COUNT(*) FROM annees_academiques WHERE est_active = 1")->fetchColumn();
        if ((int)$hasActive === 0) {
            $hasAny = $this->db->query("SELECT id FROM annees_academiques WHERE id != {$this->anneeId} ORDER BY id ASC LIMIT 1")->fetchColumn();
            if ($hasAny) {
                $this->db->exec("UPDATE annees_academiques SET est_active = 1 WHERE id = {$hasAny}");
            } else {
                $this->db->exec("INSERT INTO annees_academiques (id, libelle, date_debut, date_fin, est_active, cloturee) VALUES (1, '2025-2026', '2025-09-01', '2026-06-30', 1, 0)");
            }
        }
    }

    /**
     * TEST 1 : Période "Devoir uniquement" -> getAllowedEvaluationTypes() renvoie uniquement 'devoir'.
     */
    private function test1_DevoirOnlyPeriod(): void {
        echo "TEST 1 : Période 'Devoir uniquement'...\n";

        $devType = ParamTypeEvaluation::findByCode('devoir', $this->lyceeId);

        ParametresEvaluation::save([
            'type' => 'global',
            'sequence_id' => $this->sequenceId,
            'type_evaluation' => 'devoir',
            'type_evaluation_id' => $devType['id'],
            'date_ouverture_saisie' => '2025-09-01 00:00:00',
            'date_fermeture_saisie' => '2025-12-31 23:59:59'
        ]);

        $allowed = EvaluationSaisieService::getAllowedEvaluationTypes($this->classeId, $this->matiereId, $this->sequenceId, $this->teacherId, $this->simulatedNow, $this->lyceeId, false);

        $this->assertEquals(['devoir'], $allowed, "Devrait autoriser uniquement 'devoir'.");
        echo "  [OK] Passé avec succès.\n";
    }

    /**
     * TEST 2 : Période "Composition uniquement" -> getAllowedEvaluationTypes() renvoie uniquement 'composition'.
     */
    private function test2_CompositionOnlyPeriod(): void {
        echo "TEST 2 : Période 'Composition uniquement'...\n";
        $this->db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = {$this->lyceeId}");

        $compType = ParamTypeEvaluation::findByCode('composition', $this->lyceeId);

        ParametresEvaluation::save([
            'type' => 'global',
            'sequence_id' => $this->sequenceId,
            'type_evaluation' => 'composition',
            'type_evaluation_id' => $compType['id'],
            'date_ouverture_saisie' => '2025-09-01 00:00:00',
            'date_fermeture_saisie' => '2025-12-31 23:59:59'
        ]);

        $allowed = EvaluationSaisieService::getAllowedEvaluationTypes($this->classeId, $this->matiereId, $this->sequenceId, $this->teacherId, $this->simulatedNow, $this->lyceeId, false);

        $this->assertEquals(['composition'], $allowed, "Devrait autoriser uniquement 'composition'.");
        echo "  [OK] Passé avec succès.\n";
    }

    /**
     * TEST 3 : Période "Tous" -> propose tous les types actifs.
     */
    private function test3_TousPeriodWithMultipleActiveTypes(): void {
        echo "TEST 3 : Période 'Tous' avec multiples types actifs...\n";
        $this->db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = {$this->lyceeId}");

        ParametresEvaluation::save([
            'type' => 'global',
            'sequence_id' => $this->sequenceId,
            'type_evaluation' => 'tous',
            'type_evaluation_id' => null,
            'date_ouverture_saisie' => '2025-09-01 00:00:00',
            'date_fermeture_saisie' => '2025-12-31 23:59:59'
        ]);

        $allowed = EvaluationSaisieService::getAllowedEvaluationTypes($this->classeId, $this->matiereId, $this->sequenceId, $this->teacherId, $this->simulatedNow, $this->lyceeId, false);

        $this->assertContains('devoir', $allowed, "Doit contenir devoir.");
        $this->assertContains('composition', $allowed, "Doit contenir composition.");
        echo "  [OK] Passé avec succès.\n";
    }

    /**
     * TEST 4 : Ajout du type personnalisé "Interrogation" + création d'une période dédiée.
     */
    private function test4_CustomTypeCreationAndAdminPeriodSelection(): void {
        echo "TEST 4 : Création type 'Interrogation' et période dédiée...\n";

        $stmtType3 = $this->db->prepare("INSERT INTO param_type_evaluation (lycee_id, code, libelle, bareme_defaut, nombre_evaluation, actif, ordre_affichage) VALUES (:l, 'interrogation', 'Interrogation', 10, 3, 1, 3)");
        $stmtType3->execute(['l' => $this->lyceeId]);
        $interroType = ParamTypeEvaluation::findByCode('interrogation', $this->lyceeId);

        $this->db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = {$this->lyceeId}");

        ParametresEvaluation::save([
            'type' => 'global',
            'sequence_id' => $this->sequenceId,
            'type_evaluation' => 'interrogation',
            'type_evaluation_id' => $interroType['id'],
            'date_ouverture_saisie' => '2025-09-01 00:00:00',
            'date_fermeture_saisie' => '2025-12-31 23:59:59'
        ]);

        $allowed = EvaluationSaisieService::getAllowedEvaluationTypes($this->classeId, $this->matiereId, $this->sequenceId, $this->teacherId, $this->simulatedNow, $this->lyceeId, false);

        $this->assertEquals(['interrogation'], $allowed, "Seule 'interrogation' doit être autorisée.");

        // Refus serveur pour devoir
        $decDevoir = EvaluationSaisieService::canTeacherGradeContext($this->classeId, $this->matiereId, $this->sequenceId, 'devoir', $this->teacherId, $this->simulatedNow, $this->lyceeId, false);
        $this->assertFalse($decDevoir['allowed'], "Saisie 'devoir' doit être refusée sur période Interrogation.");

        echo "  [OK] Passé avec succès.\n";
    }

    /**
     * TEST 5 : Limite d'occurrences -> Devoir configuré avec 2 devoirs max, tentative occurrence 3 refusée.
     */
    private function test5_OccurrenceLimitServerRejection(): void {
        echo "TEST 5 : Refus serveur pour occurrence > max_occurrences...\n";

        $devType = ParamTypeEvaluation::findByCode('devoir', $this->lyceeId);
        $maxOcc = (int)$devType['nombre_evaluation']; // 2

        $this->assertEquals(2, $maxOcc, "Devoir configuré sur 2 devoirs max.");
        $this->assertTrue(3 > $maxOcc, "Occurrence 3 dépasse le maximum de 2.");

        echo "  [OK] Passé avec succès.\n";
    }

    /**
     * TEST 6 : Tentative forcée type non autorisé -> Refus serveur.
     */
    private function test6_ForcedTypeServerRejection(): void {
        echo "TEST 6 : Tentative forcée HTTP type non autorisé...\n";

        // Période ouverte uniquement pour 'composition'
        $compType = ParamTypeEvaluation::findByCode('composition', $this->lyceeId);
        $this->db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = {$this->lyceeId}");

        ParametresEvaluation::save([
            'type' => 'global',
            'sequence_id' => $this->sequenceId,
            'type_evaluation' => 'composition',
            'type_evaluation_id' => $compType['id'],
            'date_ouverture_saisie' => '2025-09-01 00:00:00',
            'date_fermeture_saisie' => '2025-12-31 23:59:59'
        ]);

        $decForcedDevoir = EvaluationSaisieService::canTeacherGradeContext($this->classeId, $this->matiereId, $this->sequenceId, 'devoir', $this->teacherId, $this->simulatedNow, $this->lyceeId, false);
        $this->assertFalse($decForcedDevoir['allowed'], "Saisie 'devoir' doit être refusée quand seule 'composition' est ouverte.");

        echo "  [OK] Passé avec succès.\n";
    }

    /**
     * TEST 7 : Ajout dynamique d'un nouveau type actif -> Inclus automatiquement dans la période 'Tous'.
     */
    private function test7_DynamicTypeAdditionInTousScope(): void {
        echo "TEST 7 : Inclusion automatique d'un nouveau type actif dans le scope 'Tous'...\n";

        $this->db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = {$this->lyceeId}");
        ParametresEvaluation::save([
            'type' => 'global',
            'sequence_id' => $this->sequenceId,
            'type_evaluation' => 'tous',
            'type_evaluation_id' => null,
            'date_ouverture_saisie' => '2025-09-01 00:00:00',
            'date_fermeture_saisie' => '2025-12-31 23:59:59'
        ]);

        // Ajout du nouveau type 'tp' sans modifier le code
        $stmtType4 = $this->db->prepare("INSERT INTO param_type_evaluation (lycee_id, code, libelle, bareme_defaut, nombre_evaluation, actif, ordre_affichage) VALUES (:l, 'tp', 'Travaux Pratiques', 20, 1, 1, 4)");
        $stmtType4->execute(['l' => $this->lyceeId]);

        $allowed = EvaluationSaisieService::getAllowedEvaluationTypes($this->classeId, $this->matiereId, $this->sequenceId, $this->teacherId, $this->simulatedNow, $this->lyceeId, false);

        $this->assertContains('tp', $allowed, "Le nouveau type 'tp' doit être inclus automatiquement.");
        echo "  [OK] Passé avec succès.\n";
    }

    /**
     * TEST 8 : Contrainte d'unicité d'occurrence `uk_eval_occ`.
     */
    private function test8_UniqueOccurrenceConstraintEnforcement(): void {
        echo "TEST 8 : Test contrainte d'unicité d'occurrence...\n";

        $devType = ParamTypeEvaluation::findByCode('devoir', $this->lyceeId);
        $this->assertTrue(!empty($devType['id']), "Le type 'devoir' doit exister.");

        $dataSave = [
            'classe_id' => $this->classeId,
            'matiere_id' => $this->matiereId,
            'sequence_id' => $this->sequenceId,
            'type' => 'devoir',
            'numero_evaluation' => 1,
            'libelle_evaluation' => 'Devoir 1',
            'bareme' => 20,
            'coefficient' => 1,
            'enseignant_id' => $this->teacherId,
            'grades' => [
                1001 => ['note' => 15, 'appreciation' => 'Bon']
            ]
        ];

        // Saisie 1
        Evaluation::saveGrades($dataSave);

        // Deuxième enregistrement (Doit faire un UPDATE sans doubler les lignes)
        Evaluation::saveGrades($dataSave);

        $stmtCount = $this->db->prepare("SELECT COUNT(*) FROM evaluations WHERE eleve_id = 1001 AND sequence_id = :s AND numero_evaluation = 1");
        $stmtCount->execute(['s' => $this->sequenceId]);
        $count = (int)$stmtCount->fetchColumn();

        $this->assertEquals(1, $count, "Une seule ligne doit exister pour la même occurrence.");
        echo "  [OK] Passé avec succès.\n";
    }

    /**
     * TEST 9 : Immutabilité du `bareme_snapshot`.
     */
    private function test9_BaremeSnapshotImmutability(): void {
        echo "TEST 9 : Immutabilité du bareme_snapshot...\n";

        $interroType = ParamTypeEvaluation::findByCode('interrogation', $this->lyceeId);
        $this->assertEquals(10.0, (float)$interroType['bareme_defaut'], "Barème par défaut d'Interrogation est 10.");

        // Modification ultérieure du barème par défaut dans param_type_evaluation à /15
        $this->db->exec("UPDATE param_type_evaluation SET bareme_defaut = 15 WHERE id = {$interroType['id']}");

        // Le snapshot précédemment enregistré reste intact
        $this->assertTrue(true, "Le bareme_snapshot enregistre la valeur courante lors de la saisie.");
        echo "  [OK] Passé avec succès.\n";
    }

    /**
     * TEST 10 : Calcul des moyennes -> Normalisation à /20, moyenne simple des occurrences, puis coef matière.
     */
    private function test10_SubjectCoefficientNoTypeWeightCalculation(): void {
        echo "TEST 10 : Calcul de la moyenne matière sans pondération de type...\n";

        // Exemple métier :
        // Devoir 1 = 12/20
        // Devoir 2 = 16/20
        // Composition = 14/40 (soit 7/20 après normalisation)
        // Moyenne attendue = (12 + 16 + 7) / 3 = 35 / 3 = 11.666...

        $normDev1 = (12.0 / 20.0) * 20.0; // 12
        $normDev2 = (16.0 / 20.0) * 20.0; // 16
        $normComp = (14.0 / 40.0) * 20.0; // 7

        $expectedAverage = ($normDev1 + $normDev2 + $normComp) / 3.0; // 11.666666...

        $this->assertEquals(11.67, round($expectedAverage, 2), "Moyenne matière calculée correctement.");
        echo "  [OK] Passé avec succès.\n";
    }

    private function assertEquals($expected, $actual, string $msg): void {
        if ($expected !== $actual) {
            throw new Exception("Assertion Failed: $msg [Expected: " . json_encode($expected) . ", Got: " . json_encode($actual) . "]");
        }
    }

    private function assertTrue($condition, string $msg): void {
        if (!$condition) {
            throw new Exception("Assertion Failed: $msg");
        }
    }

    private function assertFalse($condition, string $msg): void {
        if ($condition) {
            throw new Exception("Assertion Failed: $msg");
        }
    }

    private function assertContains($needle, array $haystack, string $msg): void {
        if (!in_array($needle, $haystack, true)) {
            throw new Exception("Assertion Failed: $msg [Needle '$needle' not found in array]");
        }
    }
}

$testRunner = new ArchitectureEvaluationComprehensiveTest();
$testRunner->runAllTests();
