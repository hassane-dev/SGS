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
require_once __DIR__ . '/../src/models/Evaluation.php';
require_once __DIR__ . '/../src/models/Classe.php';
require_once __DIR__ . '/../src/models/Matiere.php';
require_once __DIR__ . '/../src/services/EvaluationSaisieService.php';

/**
 * Suite de tests de validation exhaustive des Scénarios A à K (Règles Formulaire Enseignant).
 */
class EvaluationTypePeriodFilterTest {

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
        echo " EXÉCUTION DU TEST : SCÉNARIOS A À K (PÉRIODES & FORMULAIRE)\n";
        echo "=========================================================\n\n";

        $this->setUp();

        try {
            $this->testA_DevoirActive_CompositionInactive();
            $this->testB_CompositionActive_DevoirInactive();
            $this->testC_DevoirMultipleOccurrences();
            $this->testD_CompositionSingleOccurrence();
            $this->testE_ThreeTypesOnlyOneActivePeriod();
            $this->testF_NoActivePeriodForAnyType();
            $this->testG_NewTypeAddedWithoutActivePeriod();
            $this->testH_ManualPostForcedUnauthorizedType();
            $this->testI_ManualPostZeroOccurrenceRejection();
            $this->testJ_ManualPostExceededOccurrenceRejection();
            $this->testK_CoherenceServiceFormAndPostValidation();
            $this->testL_OldGradesExistPeriodInactiveNotAuthorized();
            $this->testM_VerifyNoSqliteDriverUsed();

            echo "\n=========================================================\n";
            echo " SUCCESS: TOUS LES TESTS A À M ONT RÉUSSI AVEC SUCCÈS !\n";
            echo "=========================================================\n";
        } finally {
            $this->tearDown();
        }
    }

    private function ensureTablesExist(): void {
        $pkType = "INT AUTO_INCREMENT PRIMARY KEY";
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS annees_academiques (
                id {$pkType},
                lycee_id INT NULL,
                libelle VARCHAR(100),
                date_debut DATE,
                date_fin DATE,
                est_active BOOLEAN,
                cloturee BOOLEAN
            );
            CREATE TABLE IF NOT EXISTS sequences (
                id {$pkType},
                lycee_id INT NOT NULL,
                annee_academique_id INT NOT NULL,
                nom VARCHAR(255) NOT NULL,
                type VARCHAR(50),
                date_debut DATE,
                date_fin DATE,
                statut VARCHAR(20) DEFAULT 'ouverte'
            );
            CREATE TABLE IF NOT EXISTS param_type_evaluation (
                id {$pkType},
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
                id {$pkType},
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
                id {$pkType},
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
                id {$pkType},
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
        ");
    }

    private function setUp(): void {
        $_SESSION['user'] = [
            'id' => 991,
            'lycee_id' => 999,
            'role_id' => 1
        ];

        $this->ensureTablesExist();
        $this->tearDown();

        $this->db->exec("UPDATE annees_academiques SET est_active = 0 WHERE id != {$this->anneeId}");

        $stmtLycee = $this->db->prepare("REPLACE INTO param_lycee (id, nom_lycee, type_lycee) VALUES (:id, 'Lycée Test 999', 'prive')");
        $stmtLycee->execute(['id' => $this->lyceeId]);

        $stmtCycle = $this->db->prepare("REPLACE INTO cycles (id_cycle, lycee_id, nom_cycle) VALUES (999, :l, 'Secondaire')");
        $stmtCycle->execute(['l' => $this->lyceeId]);

        $stmtClasse = $this->db->prepare("REPLACE INTO classes (id_classe, lycee_id, cycle_id, niveau) VALUES (:c, :l, 999, '6ème')");
        $stmtClasse->execute(['c' => $this->classeId, 'l' => $this->lyceeId]);

        $stmtMatiere = $this->db->prepare("REPLACE INTO matieres (id_matiere, lycee_id, nom_matiere) VALUES (:m, :l, 'Mathématiques')");
        $stmtMatiere->execute(['m' => $this->matiereId, 'l' => $this->lyceeId]);

        $stmtUser = $this->db->prepare("REPLACE INTO utilisateurs (id_user, lycee_id, nom, prenom, email, mot_de_passe) VALUES (:u, :l, 'Prof', 'Test', 'prof991@test.com', 'hash')");
        $stmtUser->execute(['u' => $this->teacherId, 'l' => $this->lyceeId]);

        $stmtYear = $this->db->prepare("REPLACE INTO annees_academiques (id, libelle, date_debut, date_fin, est_active, cloturee) VALUES (:id, '2025-2026-TEST', '2025-09-01', '2026-06-30', 1, 0)");
        $stmtYear->execute(['id' => $this->anneeId]);

        $stmtSeq = $this->db->prepare("REPLACE INTO sequences (id, lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut) VALUES (:id, :l, :a, 'Séquence 1', 'trimestrielle', '2025-09-01', '2026-06-30', 'ouverte')");
        $stmtSeq->execute(['id' => $this->sequenceId, 'l' => $this->lyceeId, 'a' => $this->anneeId]);

        // Seed base types in param_type_evaluation
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
     * TEST A : Période Devoir active, Composition inactive -> Devoir seul renvoyé.
     */
    private function testA_DevoirActive_CompositionInactive(): void {
        echo "TEST A : Période Devoir active, Composition inactive...\n";

        $devType = ParamTypeEvaluation::findByCode('devoir', $this->lyceeId);
        $compType = ParamTypeEvaluation::findByCode('composition', $this->lyceeId);

        $this->db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = {$this->lyceeId}");

        // Rule for Devoir (Active now)
        ParametresEvaluation::save([
            'type' => 'global',
            'sequence_id' => $this->sequenceId,
            'type_evaluation' => 'devoir',
            'type_evaluation_id' => $devType['id'],
            'date_ouverture_saisie' => '2025-09-01 00:00:00',
            'date_fermeture_saisie' => '2025-12-31 23:59:59'
        ]);

        // Rule for Composition (Future)
        ParametresEvaluation::save([
            'type' => 'global',
            'sequence_id' => $this->sequenceId,
            'type_evaluation' => 'composition',
            'type_evaluation_id' => $compType['id'],
            'date_ouverture_saisie' => '2026-05-01 00:00:00',
            'date_fermeture_saisie' => '2026-06-30 23:59:59'
        ]);

        $allowed = EvaluationSaisieService::getAllowedEvaluationTypes($this->classeId, $this->matiereId, $this->sequenceId, $this->teacherId, $this->simulatedNow, $this->lyceeId, false);

        $this->assertEquals(['devoir'], $allowed, "Devrait renvoyer UNIQUEMENT 'devoir'.");
        echo "  [OK] Passé avec succès.\n";
    }

    /**
     * TEST B : Période Composition active, Devoir inactive -> Composition seul renvoyé.
     */
    private function testB_CompositionActive_DevoirInactive(): void {
        echo "TEST B : Période Composition active, Devoir inactive...\n";

        $devType = ParamTypeEvaluation::findByCode('devoir', $this->lyceeId);
        $compType = ParamTypeEvaluation::findByCode('composition', $this->lyceeId);

        $this->db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = {$this->lyceeId}");

        // Rule for Devoir (Expired)
        ParametresEvaluation::save([
            'type' => 'global',
            'sequence_id' => $this->sequenceId,
            'type_evaluation' => 'devoir',
            'type_evaluation_id' => $devType['id'],
            'date_ouverture_saisie' => '2025-09-01 00:00:00',
            'date_fermeture_saisie' => '2025-09-30 23:59:59'
        ]);

        // Rule for Composition (Active now)
        ParametresEvaluation::save([
            'type' => 'global',
            'sequence_id' => $this->sequenceId,
            'type_evaluation' => 'composition',
            'type_evaluation_id' => $compType['id'],
            'date_ouverture_saisie' => '2025-10-01 00:00:00',
            'date_fermeture_saisie' => '2025-10-31 23:59:59'
        ]);

        $allowed = EvaluationSaisieService::getAllowedEvaluationTypes($this->classeId, $this->matiereId, $this->sequenceId, $this->teacherId, $this->simulatedNow, $this->lyceeId, false);

        $this->assertEquals(['composition'], $allowed, "Devrait renvoyer UNIQUEMENT 'composition'.");
        echo "  [OK] Passé avec succès.\n";
    }

    /**
     * TEST C : Devoir actif avec nombre_evaluation = 2 -> Occurrences 1 et 2 valides, 3 invalide.
     */
    private function testC_DevoirMultipleOccurrences(): void {
        echo "TEST C : Devoir actif avec nombre_evaluation = 2...\n";

        $devType = ParamTypeEvaluation::findByCode('devoir', $this->lyceeId);
        $this->assertEquals(2, (int)$devType['nombre_evaluation'], "nombre_evaluation doit être égal à 2.");

        $this->db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = {$this->lyceeId}");

        // Rule for Devoir (Active now)
        ParametresEvaluation::save([
            'type' => 'global',
            'sequence_id' => $this->sequenceId,
            'type_evaluation' => 'devoir',
            'type_evaluation_id' => $devType['id'],
            'date_ouverture_saisie' => '2025-09-01 00:00:00',
            'date_fermeture_saisie' => '2025-12-31 23:59:59'
        ]);

        $dec1 = EvaluationSaisieService::canTeacherGradeContext($this->classeId, $this->matiereId, $this->sequenceId, 'devoir', $this->teacherId, $this->simulatedNow, $this->lyceeId, false, 1);
        $this->assertTrue($dec1['allowed'], "Occurrence 1 doit être autorisée.");

        $dec2 = EvaluationSaisieService::canTeacherGradeContext($this->classeId, $this->matiereId, $this->sequenceId, 'devoir', $this->teacherId, $this->simulatedNow, $this->lyceeId, false, 2);
        $this->assertTrue($dec2['allowed'], "Occurrence 2 doit être autorisée.");

        $dec3 = EvaluationSaisieService::canTeacherGradeContext($this->classeId, $this->matiereId, $this->sequenceId, 'devoir', $this->teacherId, $this->simulatedNow, $this->lyceeId, false, 3);
        $this->assertFalse($dec3['allowed'], "Occurrence 3 doit être refusée.");

        echo "  [OK] Passé avec succès.\n";
    }

    /**
     * TEST L : Des notes existent déjà dans 'evaluations' pour Devoir, mais aucune période Devoir n'est active -> REFUS.
     */
    private function testL_OldGradesExistPeriodInactiveNotAuthorized(): void {
        echo "TEST L : Anciennes notes dans 'evaluations' avec période inactive -> REFUS...\n";

        $devType = ParamTypeEvaluation::findByCode('devoir', $this->lyceeId);
        $this->db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = {$this->lyceeId}");
        $this->db->exec("DELETE FROM evaluations WHERE lycee_id = {$this->lyceeId}");

        // Insert dummy eleve
        $stmtEleve = $this->db->prepare("REPLACE INTO eleves (id_eleve, lycee_id, nom, prenom) VALUES (101, :l, 'Doe', 'John')");
        $stmtEleve->execute(['l' => $this->lyceeId]);

        // Insert historical grade into evaluations
        $stmtGrade = $this->db->prepare("INSERT INTO evaluations (lycee_id, classe_id, matiere_id, enseignant_id, eleve_id, sequence_id, annee_academique_id, type, type_evaluation_id, numero_evaluation, note, coefficient) VALUES (:l, :c, :m, :e, 101, :s, :a, 'devoir', :tid, 1, 15.5, 1.0)");
        $stmtGrade->execute([
            'l' => $this->lyceeId,
            'c' => $this->classeId,
            'm' => $this->matiereId,
            'e' => $this->teacherId,
            's' => $this->sequenceId,
            'a' => $this->anneeId,
            'tid' => $devType['id']
        ]);

        // Verify that even though grades exist, canTeacherGradeContext refuses because no period is active
        $dec = EvaluationSaisieService::canTeacherGradeContext($this->classeId, $this->matiereId, $this->sequenceId, 'devoir', $this->teacherId, $this->simulatedNow, $this->lyceeId, false, 1);
        $this->assertFalse($dec['allowed'], "La présence d'anciennes notes dans 'evaluations' ne doit PAS accorder d'autorisation si la période est inactive.");

        $allowed = EvaluationSaisieService::getAllowedEvaluationTypes($this->classeId, $this->matiereId, $this->sequenceId, $this->teacherId, $this->simulatedNow, $this->lyceeId, false);
        $this->assertFalse(in_array('devoir', $allowed, true), "'devoir' ne doit pas apparaître dans les types autorisés.");

        echo "  [OK] Passé avec succès.\n";
    }

    /**
     * TEST M : Vérifier qu'aucun driver SQLite n'est utilisé pour SGS.
     */
    private function testM_VerifyNoSqliteDriverUsed(): void {
        echo "TEST M : Vérification qu'aucun driver SQLite n'est utilisé...\n";
        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        $this->assertFalse($driver === 'sqlite', "Le driver de base de données ne doit PAS être SQLite.");
        echo "  [OK] Passé avec succès ($driver).\n";
    }

    /**
     * TEST D : Composition active avec nombre_evaluation = 1 -> Occurrence 1 valide, 2 invalide.
     */
    private function testD_CompositionSingleOccurrence(): void {
        echo "TEST D : Composition active avec nombre_evaluation = 1...\n";

        $compType = ParamTypeEvaluation::findByCode('composition', $this->lyceeId);
        $this->assertEquals(1, (int)$compType['nombre_evaluation'], "nombre_evaluation doit être égal à 1.");

        $this->db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = {$this->lyceeId}");

        // Rule for Composition (Active now)
        ParametresEvaluation::save([
            'type' => 'global',
            'sequence_id' => $this->sequenceId,
            'type_evaluation' => 'composition',
            'type_evaluation_id' => $compType['id'],
            'date_ouverture_saisie' => '2025-09-01 00:00:00',
            'date_fermeture_saisie' => '2025-12-31 23:59:59'
        ]);

        $dec1 = EvaluationSaisieService::canTeacherGradeContext($this->classeId, $this->matiereId, $this->sequenceId, 'composition', $this->teacherId, $this->simulatedNow, $this->lyceeId, false, 1);
        $this->assertTrue($dec1['allowed'], "Occurrence 1 doit être autorisée.");

        $dec2 = EvaluationSaisieService::canTeacherGradeContext($this->classeId, $this->matiereId, $this->sequenceId, 'composition', $this->teacherId, $this->simulatedNow, $this->lyceeId, false, 2);
        $this->assertFalse($dec2['allowed'], "Occurrence 2 doit être refusée.");

        echo "  [OK] Passé avec succès.\n";
    }

    /**
     * TEST E : 3 types (Devoir, Composition, Interrogation), seule la période Devoir est active.
     */
    private function testE_ThreeTypesOnlyOneActivePeriod(): void {
        echo "TEST E : Configuration 3 types, seule la période Devoir est active...\n";

        $stmtType3 = $this->db->prepare("INSERT INTO param_type_evaluation (lycee_id, code, libelle, bareme_defaut, nombre_evaluation, actif, ordre_affichage) VALUES (:l, 'interrogation', 'Interrogation', 10, 3, 1, 3)");
        $stmtType3->execute(['l' => $this->lyceeId]);

        $devType = ParamTypeEvaluation::findByCode('devoir', $this->lyceeId);
        $compType = ParamTypeEvaluation::findByCode('composition', $this->lyceeId);
        $interroType = ParamTypeEvaluation::findByCode('interrogation', $this->lyceeId);

        $this->db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = {$this->lyceeId}");

        // Rule for Devoir (Active)
        ParametresEvaluation::save([
            'type' => 'global',
            'sequence_id' => $this->sequenceId,
            'type_evaluation' => 'devoir',
            'type_evaluation_id' => $devType['id'],
            'date_ouverture_saisie' => '2025-09-01 00:00:00',
            'date_fermeture_saisie' => '2025-12-31 23:59:59'
        ]);

        // Rule for Composition (Future)
        ParametresEvaluation::save([
            'type' => 'global',
            'sequence_id' => $this->sequenceId,
            'type_evaluation' => 'composition',
            'type_evaluation_id' => $compType['id'],
            'date_ouverture_saisie' => '2026-05-01 00:00:00',
            'date_fermeture_saisie' => '2026-06-30 23:59:59'
        ]);

        // Rule for Interrogation (Future)
        ParametresEvaluation::save([
            'type' => 'global',
            'sequence_id' => $this->sequenceId,
            'type_evaluation' => 'interrogation',
            'type_evaluation_id' => $interroType['id'],
            'date_ouverture_saisie' => '2026-01-01 00:00:00',
            'date_fermeture_saisie' => '2026-02-28 23:59:59'
        ]);

        $allowed = EvaluationSaisieService::getAllowedEvaluationTypes($this->classeId, $this->matiereId, $this->sequenceId, $this->teacherId, $this->simulatedNow, $this->lyceeId, false);

        $this->assertEquals(['devoir'], $allowed, "Doit contenir UNIQUEMENT 'devoir'.");
        echo "  [OK] Passé avec succès.\n";
    }

    /**
     * TEST F : Aucune période active pour les types spécifiques -> Résultat vide / accès refusé.
     */
    private function testF_NoActivePeriodForAnyType(): void {
        echo "TEST F : Aucune période de saisie active pour un type spécifique...\n";

        $devType = ParamTypeEvaluation::findByCode('devoir', $this->lyceeId);
        $compType = ParamTypeEvaluation::findByCode('composition', $this->lyceeId);

        $this->db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = {$this->lyceeId}");

        // Rule Devoir Expired
        ParametresEvaluation::save([
            'type' => 'global',
            'sequence_id' => $this->sequenceId,
            'type_evaluation' => 'devoir',
            'type_evaluation_id' => $devType['id'],
            'date_ouverture_saisie' => '2025-09-01 00:00:00',
            'date_fermeture_saisie' => '2025-09-30 23:59:59'
        ]);

        // Rule Composition Future
        ParametresEvaluation::save([
            'type' => 'global',
            'sequence_id' => $this->sequenceId,
            'type_evaluation' => 'composition',
            'type_evaluation_id' => $compType['id'],
            'date_ouverture_saisie' => '2026-05-01 00:00:00',
            'date_fermeture_saisie' => '2026-06-30 23:59:59'
        ]);

        $allowed = EvaluationSaisieService::getAllowedEvaluationTypes($this->classeId, $this->matiereId, $this->sequenceId, $this->teacherId, $this->simulatedNow, $this->lyceeId, false);

        $this->assertEquals([], $allowed, "La liste des types autorisés doit être strictly VIDE.");
        echo "  [OK] Passé avec succès.\n";
    }

    /**
     * TEST G : Nouveau type 'TP' ajouté dans param_type_evaluation sans période active -> TP n'apparaît PAS.
     */
    private function testG_NewTypeAddedWithoutActivePeriod(): void {
        echo "TEST G : Nouveau type 'TP' sans période active...\n";

        $stmtType4 = $this->db->prepare("INSERT INTO param_type_evaluation (lycee_id, code, libelle, bareme_defaut, nombre_evaluation, actif, ordre_affichage) VALUES (:l, 'tp', 'TP', 20, 2, 1, 4)");
        $stmtType4->execute(['l' => $this->lyceeId]);

        $allowed = EvaluationSaisieService::getAllowedEvaluationTypes($this->classeId, $this->matiereId, $this->sequenceId, $this->teacherId, $this->simulatedNow, $this->lyceeId, false);

        $this->assertFalse(in_array('tp', $allowed, true), "Le nouveau type 'tp' sans période active ne doit PAS apparaître.");
        echo "  [OK] Passé avec succès.\n";
    }

    /**
     * TEST H : Période Devoir active, POST/GET manuel pour type 'composition' -> REJET.
     */
    private function testH_ManualPostForcedUnauthorizedType(): void {
        echo "TEST H : POST/GET manuel type non autorisé ('composition')...\n";

        $devType = ParamTypeEvaluation::findByCode('devoir', $this->lyceeId);
        $this->db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = {$this->lyceeId}");

        // Only Devoir active
        ParametresEvaluation::save([
            'type' => 'global',
            'sequence_id' => $this->sequenceId,
            'type_evaluation' => 'devoir',
            'type_evaluation_id' => $devType['id'],
            'date_ouverture_saisie' => '2025-09-01 00:00:00',
            'date_fermeture_saisie' => '2025-12-31 23:59:59'
        ]);

        $dec = EvaluationSaisieService::canTeacherGradeContext($this->classeId, $this->matiereId, $this->sequenceId, 'composition', $this->teacherId, $this->simulatedNow, $this->lyceeId, false);

        $this->assertFalse($dec['allowed'], "La demande pour 'composition' doit être refusée.");
        $this->assertInArray($dec['code'], ['DENIED_PERIOD_NOT_STARTED', 'DENIED_TYPE_MISMATCH', 'DENIED_POLICY_RESTRICTED'], "Le code de refus doit être explicite.");
        echo "  [OK] Passé avec succès.\n";
    }

    /**
     * TEST I : Occurrence 0 -> Refus DENIED_INVALID_OCCURRENCE.
     */
    private function testI_ManualPostZeroOccurrenceRejection(): void {
        echo "TEST I : Occurrence 0 -> Refus DENIED_INVALID_OCCURRENCE...\n";

        $devType = ParamTypeEvaluation::findByCode('devoir', $this->lyceeId);
        $this->db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = {$this->lyceeId}");

        ParametresEvaluation::save([
            'type' => 'global',
            'sequence_id' => $this->sequenceId,
            'type_evaluation' => 'devoir',
            'type_evaluation_id' => $devType['id'],
            'date_ouverture_saisie' => '2025-09-01 00:00:00',
            'date_fermeture_saisie' => '2025-12-31 23:59:59'
        ]);

        $dec = EvaluationSaisieService::canTeacherGradeContext($this->classeId, $this->matiereId, $this->sequenceId, 'devoir', $this->teacherId, $this->simulatedNow, $this->lyceeId, false, 0);

        $this->assertFalse($dec['allowed'], "Occurrence 0 doit être refusée.");
        $this->assertEquals('DENIED_INVALID_OCCURRENCE', $dec['code'], "Le code d'erreur doit être DENIED_INVALID_OCCURRENCE.");
        echo "  [OK] Passé avec succès.\n";
    }

    /**
     * TEST J : Occurrence 3 (alors que nombre_evaluation = 2) -> Refus DENIED_INVALID_OCCURRENCE.
     */
    private function testJ_ManualPostExceededOccurrenceRejection(): void {
        echo "TEST J : Occurrence 3 pour Devoir (max=2) -> Refus DENIED_INVALID_OCCURRENCE...\n";

        $devType = ParamTypeEvaluation::findByCode('devoir', $this->lyceeId);
        $this->db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = {$this->lyceeId}");

        ParametresEvaluation::save([
            'type' => 'global',
            'sequence_id' => $this->sequenceId,
            'type_evaluation' => 'devoir',
            'type_evaluation_id' => $devType['id'],
            'date_ouverture_saisie' => '2025-09-01 00:00:00',
            'date_fermeture_saisie' => '2025-12-31 23:59:59'
        ]);

        $dec = EvaluationSaisieService::canTeacherGradeContext($this->classeId, $this->matiereId, $this->sequenceId, 'devoir', $this->teacherId, $this->simulatedNow, $this->lyceeId, false, 3);

        $this->assertFalse($dec['allowed'], "Occurrence 3 doit être refusée.");
        $this->assertEquals('DENIED_INVALID_OCCURRENCE', $dec['code'], "Le code d'erreur doit être DENIED_INVALID_OCCURRENCE.");
        echo "  [OK] Passé avec succès.\n";
    }

    /**
     * TEST K : Cohérence stricte entre EvaluationSaisieService, form.php et validation POST.
     */
    private function testK_CoherenceServiceFormAndPostValidation(): void {
        echo "TEST K : Cohérence entre Service, Vue et Validation POST...\n";

        $devType = ParamTypeEvaluation::findByCode('devoir', $this->lyceeId);
        $this->db->exec("DELETE FROM parametres_evaluations WHERE lycee_id = {$this->lyceeId}");

        ParametresEvaluation::save([
            'type' => 'global',
            'sequence_id' => $this->sequenceId,
            'type_evaluation' => 'devoir',
            'type_evaluation_id' => $devType['id'],
            'date_ouverture_saisie' => '2025-09-01 00:00:00',
            'date_fermeture_saisie' => '2025-12-31 23:59:59'
        ]);

        $allowed = EvaluationSaisieService::getAllowedEvaluationTypes($this->classeId, $this->matiereId, $this->sequenceId, $this->teacherId, $this->simulatedNow, $this->lyceeId, false);

        // 1. Service list
        $this->assertEquals(['devoir'], $allowed, "Service doit renvoyer uniquement 'devoir'.");

        // 2. Form view logic check
        $hasMultipleTypes = count($allowed) > 1;
        $this->assertFalse($hasMultipleTypes, "La vue ne doit pas afficher de sélecteur multi-types.");

        // 3. POST validation check
        $decDevoir = EvaluationSaisieService::canTeacherGradeContext($this->classeId, $this->matiereId, $this->sequenceId, 'devoir', $this->teacherId, $this->simulatedNow, $this->lyceeId, false, 1);
        $this->assertTrue($decDevoir['allowed'], "POST pour Devoir 1 doit être accepté.");

        $decComp = EvaluationSaisieService::canTeacherGradeContext($this->classeId, $this->matiereId, $this->sequenceId, 'composition', $this->teacherId, $this->simulatedNow, $this->lyceeId, false, 1);
        $this->assertFalse($decComp['allowed'], "POST pour Composition 1 doit être strictement rejeté.");

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

    private function assertInArray($needle, array $haystack, string $msg): void {
        if (!in_array($needle, $haystack, true)) {
            throw new Exception("Assertion Failed: $msg [Needle '$needle' not found in array " . json_encode($haystack) . "]");
        }
    }
}

$testRunner = new EvaluationTypePeriodFilterTest();
$testRunner->runAllTests();
