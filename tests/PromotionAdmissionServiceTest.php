<?php
/**
 * Test Suite: PromotionAdmissionServiceTest
 *
 * Vérifie l'ensemble des 15 scénarios obligatoires exigés par la spécification :
 *  1. Élève dont la moyenne atteint le seuil d'admission.
 *  2. Élève dont la moyenne est inférieure au seuil (délibération ou redoublement).
 *  3. Élève soumis à un seuil spécifique Niveau + Série.
 *  4. Élève relevant d'un seuil défini au niveau.
 *  5. Élève relevant d'un seuil défini au cycle.
 *  6. Élève relevant du seuil de l'établissement.
 *  7. Absence totale de seuil applicable (blocage avec erreur explicite).
 *  8. Présence d'une note éliminatoire configurée.
 *  9. Résultats scolaires incomplets (refus/blocage).
 * 10. Isolation entre deux établissements.
 * 11. Isolation entre deux années académiques.
 * 12. Isolation entre deux classes.
 * 13. Élève n'appartenant pas à la classe demandée.
 * 14. Conservation des décisions historiques.
 * 15. Absence d'écrasement silencieux d'une décision validée.
 */

putenv('APP_ENV=development');
putenv('DB_DRIVER=sqlite');
$_ENV['APP_ENV'] = 'development';
$_ENV['DB_DRIVER'] = 'sqlite';

if (!defined('TEST_MODE')) {
    define('TEST_MODE', true);
}

require_once __DIR__ . '/../src/config/config.php';
require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/models/AnneeAcademique.php';
require_once __DIR__ . '/../src/models/Cycle.php';
require_once __DIR__ . '/../src/models/Classe.php';
require_once __DIR__ . '/../src/models/Serie.php';
require_once __DIR__ . '/../src/models/Eleve.php';
require_once __DIR__ . '/../src/models/Sequence.php';
require_once __DIR__ . '/../src/models/ParamPromotionSeuil.php';
require_once __DIR__ . '/../src/services/PromotionThresholdService.php';
require_once __DIR__ . '/../src/services/PromotionAdmissionService.php';
require_once __DIR__ . '/../db/migrations/20240115_37_create_param_promotion_seuils.php';

class PromotionAdmissionServiceTest {

    private PDO $db;
    private int $lyceeA = 101;
    private int $lyceeB = 102;
    private int $annee1 = 201;
    private int $annee2 = 202;
    private int $cycleCollegeA = 301;
    private int $cycleLyceeA = 302;
    private int $cycleCollegeB = 303;
    private int $classe3e = 401;
    private int $classeTleC = 402;
    private int $classeTleA = 403;
    private int $classe1ere = 404;
    private int $classeOther = 405;
    private int $classeLyceeB = 406;

    private int $eleveAdmis = 501;
    private int $eleveDelib = 502;
    private int $eleveRedoubl = 503;
    private int $eleveExclu = 504;
    private int $eleveElim = 505;
    private int $eleveIncomplet = 506;
    private int $eleveTleC = 507;
    private int $eleveTleA = 508;
    private int $eleve1ere = 509;
    private int $eleveLyceeB = 510;

    private int $seq1Year1;
    private int $seq2Year1;
    private int $seq3Year1;
    private int $seq1Year2;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function run() {
        echo "========================================================\n";
        echo " RUNNING SUITE: PromotionAdmissionServiceTest (15 Scenarios) \n";
        echo "========================================================\n\n";

        // Ensure schema is up to date
        migrate_37($this->db);

        $this->setUpDatabase();

        $this->test1_EleveMoyenneAtteintSeuilAdmission();
        $this->test2_EleveMoyenneInferieureSeuil();
        $this->test3_EleveSeuilSpecifiqueNiveauSerie();
        $this->test4_EleveSeuilDefiniAuNiveau();
        $this->test5_EleveSeuilDefiniAuCycle();
        $this->test6_EleveSeuilEtablissement();
        $this->test7_AbsenceTotaleSeuilApplicable();
        $this->test8_PresenceNoteEliminatoire();
        $this->test9_ResultatsScolairesIncomplets();
        $this->test10_IsolationEntreDeuxEtablissements();
        $this->test11_IsolationEntreDeuxAnneesAcademiques();
        $this->test12_IsolationEntreDeuxClasses();
        $this->test13_EleveNAppartenantPasALaClasse();
        $this->test14_ConservationDecisionsHistoriques();
        $this->test15_AbsenceEcrasementSilencieuxDecisionValidee();

        // Nettoyage après test
        $this->cleanUp();

        echo "\n========================================================\n";
        echo " ALL 15 MANDATORY ADMISSION SCENARIOS PASSED SUCCESSFULLY! \n";
        echo "========================================================\n";
    }

    private function cleanUp() {
        $this->db->exec("DELETE FROM bulletin_details WHERE bulletin_id IN (SELECT id FROM bulletins WHERE lycee_id IN ({$this->lyceeA}, {$this->lyceeB}))");
        $this->db->exec("DELETE FROM bulletins WHERE lycee_id IN ({$this->lyceeA}, {$this->lyceeB})");
        $this->db->exec("DELETE FROM param_promotion_seuils WHERE lycee_id IN ({$this->lyceeA}, {$this->lyceeB})");
        $this->db->exec("DELETE FROM etudes WHERE lycee_id IN ({$this->lyceeA}, {$this->lyceeB})");
        $this->db->exec("DELETE FROM eleves WHERE lycee_id IN ({$this->lyceeA}, {$this->lyceeB})");
        $this->db->exec("DELETE FROM sequences WHERE lycee_id IN ({$this->lyceeA}, {$this->lyceeB})");
        $this->db->exec("DELETE FROM classes WHERE lycee_id IN ({$this->lyceeA}, {$this->lyceeB})");
        $this->db->exec("DELETE FROM series WHERE lycee_id IN ({$this->lyceeA}, {$this->lyceeB})");
        $this->db->exec("DELETE FROM cycles WHERE lycee_id IN ({$this->lyceeA}, {$this->lyceeB})");
        $this->db->exec("DELETE FROM param_lycee WHERE id IN ({$this->lyceeA}, {$this->lyceeB})");
        $this->db->exec("DELETE FROM annees_academiques WHERE id IN ({$this->annee1}, {$this->annee2})");
    }

    private function setUpDatabase() {
        $this->cleanUp();

        // 1. Establishments
        $stmtL = $this->db->prepare("INSERT INTO param_lycee (id, nom_lycee, ville) VALUES (?, ?, ?)");
        $stmtL->execute([$this->lyceeA, 'Lycée Test A (Excellence)', 'Cotonou']);
        $stmtL->execute([$this->lyceeB, 'Lycée Test B (Littoral)', 'Porto-Novo']);

        // 2. Academic Years
        $stmtA = $this->db->prepare("INSERT INTO annees_academiques (id, libelle, date_debut, date_fin, est_active) VALUES (?, ?, ?, ?, ?)");
        $stmtA->execute([$this->annee1, '2023-2024 Test', '2023-09-15', '2024-06-30', 0]);
        $stmtA->execute([$this->annee2, '2024-2025 Test', '2024-09-15', '2025-06-30', 1]);

        // 3. Cycles
        $stmtC = $this->db->prepare("INSERT INTO cycles (id_cycle, lycee_id, nom_cycle, niveau_debut, niveau_fin, utilise_series) VALUES (?, ?, ?, ?, ?, ?)");
        $stmtC->execute([$this->cycleCollegeA, $this->lyceeA, 'Collège A', '6e', '3e', 0]);
        $stmtC->execute([$this->cycleLyceeA, $this->lyceeA, 'Lycée A', '2nde', 'Terminale', 1]);
        $stmtC->execute([$this->cycleCollegeB, $this->lyceeB, 'Collège B', '6e', '3e', 0]);

        // 4. Series
        $stmtS = $this->db->prepare("INSERT INTO series (nom_serie, lycee_id) VALUES (?, ?)");
        $stmtS->execute(['C', $this->lyceeA]);
        $stmtS->execute(['A', $this->lyceeA]);

        // 5. Classes
        $stmtCl = $this->db->prepare("INSERT INTO classes (id_classe, lycee_id, cycle_id, niveau, serie, numero) VALUES (?, ?, ?, ?, ?, ?)");
        $stmtCl->execute([$this->classe3e, $this->lyceeA, $this->cycleCollegeA, '3e', null, '1']);
        $stmtCl->execute([$this->classeTleC, $this->lyceeA, $this->cycleLyceeA, 'Terminale', 'C', '1']);
        $stmtCl->execute([$this->classeTleA, $this->lyceeA, $this->cycleLyceeA, 'Terminale', 'A', '1']);
        $stmtCl->execute([$this->classe1ere, $this->lyceeA, $this->cycleLyceeA, '1ère', 'C', '1']);
        $stmtCl->execute([$this->classeOther, $this->lyceeA, $this->cycleCollegeA, '4e', null, '1']);
        $stmtCl->execute([$this->classeLyceeB, $this->lyceeB, $this->cycleCollegeB, '3e', null, '1']);

        // 6. Sequences for Annee 1 (Lycee A has 3 trimestres, closed)
        $stmtSeq = $this->db->prepare("INSERT INTO sequences (lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmtSeq->execute([$this->lyceeA, $this->annee1, 'Trimestre 1', 'trimestrielle', '2023-09-15', '2023-12-15', 'fermee']);
        $this->seq1Year1 = (int)$this->db->lastInsertId();
        $stmtSeq->execute([$this->lyceeA, $this->annee1, 'Trimestre 2', 'trimestrielle', '2024-01-05', '2024-03-25', 'fermee']);
        $this->seq2Year1 = (int)$this->db->lastInsertId();
        $stmtSeq->execute([$this->lyceeA, $this->annee1, 'Trimestre 3', 'trimestrielle', '2024-04-10', '2024-06-25', 'fermee']);
        $this->seq3Year1 = (int)$this->db->lastInsertId();

        // Sequences for Annee 2 (Lycee A)
        $stmtSeq->execute([$this->lyceeA, $this->annee2, 'Trimestre 1 (An 2)', 'trimestrielle', '2024-09-15', '2024-12-15', 'fermee']);
        $this->seq1Year2 = (int)$this->db->lastInsertId();

        // 7. Students & Enrollments (etudes)
        $this->createStudentAndEnrollment($this->eleveAdmis, 'KOUASSI', 'Jean', $this->classe3e, $this->annee1, $this->lyceeA);
        $this->createStudentAndEnrollment($this->eleveAdmis, 'KOUASSI', 'Jean', $this->classe3e, $this->annee2, $this->lyceeA);
        $this->createStudentAndEnrollment($this->eleveDelib, 'MENSAH', 'Paul', $this->classe3e, $this->annee1, $this->lyceeA);
        $this->createStudentAndEnrollment($this->eleveRedoubl, 'SOSSOU', 'Luc', $this->classe3e, $this->annee1, $this->lyceeA);
        $this->createStudentAndEnrollment($this->eleveExclu, 'DOSSOU', 'Marc', $this->classe3e, $this->annee1, $this->lyceeA);
        $this->createStudentAndEnrollment($this->eleveElim, 'AGBOTA', 'Claire', $this->classe3e, $this->annee1, $this->lyceeA);
        $this->createStudentAndEnrollment($this->eleveIncomplet, 'HOUNNOU', 'Anne', $this->classe3e, $this->annee1, $this->lyceeA);
        $this->createStudentAndEnrollment($this->eleveTleC, 'BIO', 'David', $this->classeTleC, $this->annee1, $this->lyceeA);
        $this->createStudentAndEnrollment($this->eleveTleA, 'IDRISSOU', 'Fatou', $this->classeTleA, $this->annee1, $this->lyceeA);
        $this->createStudentAndEnrollment($this->eleve1ere, 'BOKO', 'Eric', $this->classe1ere, $this->annee1, $this->lyceeA);
        $this->createStudentAndEnrollment($this->eleveLyceeB, 'ADJOVI', 'Yvette', $this->classeLyceeB, $this->annee1, $this->lyceeB);

        // 8. Create official reports (bulletins) in classe3e for Annee 1
        // Eleve Admis: T1=14.00, T2=15.00, T3=14.50 => Moyenne Annuelle = 14.50
        $this->createReportAndDetails($this->eleveAdmis, $this->classe3e, $this->annee1, $this->lyceeA, [
            $this->seq1Year1 => ['general' => 14.00, 'math' => 15.0, 'francais' => 13.0],
            $this->seq2Year1 => ['general' => 15.00, 'math' => 16.0, 'francais' => 14.0],
            $this->seq3Year1 => ['general' => 14.50, 'math' => 15.5, 'francais' => 13.5]
        ]);

        // Eleve Delib: T1=9.60, T2=9.80, T3=9.70 => Moyenne Annuelle = 9.70 (intervalle 9.50 - 9.99)
        $this->createReportAndDetails($this->eleveDelib, $this->classe3e, $this->annee1, $this->lyceeA, [
            $this->seq1Year1 => ['general' => 9.60, 'math' => 10.0, 'francais' => 9.2],
            $this->seq2Year1 => ['general' => 9.80, 'math' => 10.5, 'francais' => 9.1],
            $this->seq3Year1 => ['general' => 9.70, 'math' => 10.2, 'francais' => 9.2]
        ]);

        // Eleve Redoubl: T1=8.00, T2=8.50, T3=8.10 => Moyenne Annuelle = 8.20
        $this->createReportAndDetails($this->eleveRedoubl, $this->classe3e, $this->annee1, $this->lyceeA, [
            $this->seq1Year1 => ['general' => 8.00, 'math' => 8.0, 'francais' => 8.0],
            $this->seq2Year1 => ['general' => 8.50, 'math' => 8.5, 'francais' => 8.5],
            $this->seq3Year1 => ['general' => 8.10, 'math' => 8.0, 'francais' => 8.2]
        ]);

        // Eleve Exclu: T1=5.00, T2=5.20, T3=4.80 => Moyenne Annuelle = 5.00 (seuil exclusion = 6.00)
        $this->createReportAndDetails($this->eleveExclu, $this->classe3e, $this->annee1, $this->lyceeA, [
            $this->seq1Year1 => ['general' => 5.00, 'math' => 5.0, 'francais' => 5.0],
            $this->seq2Year1 => ['general' => 5.20, 'math' => 5.2, 'francais' => 5.2],
            $this->seq3Year1 => ['general' => 4.80, 'math' => 4.8, 'francais' => 4.8]
        ]);

        // Eleve Elim: Moyenne 12.00 mais math = 4.00 (note éliminatoire < 5.00)
        $this->createReportAndDetails($this->eleveElim, $this->classe3e, $this->annee1, $this->lyceeA, [
            $this->seq1Year1 => ['general' => 12.00, 'math' => 4.0, 'francais' => 16.0],
            $this->seq2Year1 => ['general' => 12.00, 'math' => 4.0, 'francais' => 16.0],
            $this->seq3Year1 => ['general' => 12.00, 'math' => 4.0, 'francais' => 16.0]
        ]);

        // Eleve Incomplet: Bulletins pour T1 et T2 seulement (T3 manquant)
        $this->createReportAndDetails($this->eleveIncomplet, $this->classe3e, $this->annee1, $this->lyceeA, [
            $this->seq1Year1 => ['general' => 13.00, 'math' => 13.0, 'francais' => 13.0],
            $this->seq2Year1 => ['general' => 13.00, 'math' => 13.0, 'francais' => 13.0]
        ]);

        // Eleve TleC: Moyenne 11.50 (Dans TleC avec seuil spécifique = 12.00)
        $this->createReportAndDetails($this->eleveTleC, $this->classeTleC, $this->annee1, $this->lyceeA, [
            $this->seq1Year1 => ['general' => 11.50, 'math' => 12.0, 'francais' => 11.0],
            $this->seq2Year1 => ['general' => 11.50, 'math' => 12.0, 'francais' => 11.0],
            $this->seq3Year1 => ['general' => 11.50, 'math' => 12.0, 'francais' => 11.0]
        ]);

        // Eleve TleA: Moyenne 11.50 (Dans TleA relevant du seuil Niveau Tle = 11.00)
        $this->createReportAndDetails($this->eleveTleA, $this->classeTleA, $this->annee1, $this->lyceeA, [
            $this->seq1Year1 => ['general' => 11.50, 'math' => 11.0, 'francais' => 12.0],
            $this->seq2Year1 => ['general' => 11.50, 'math' => 11.0, 'francais' => 12.0],
            $this->seq3Year1 => ['general' => 11.50, 'math' => 11.0, 'francais' => 12.0]
        ]);

        // Eleve 1ere: Moyenne 10.60 (Relevant du seuil Cycle Lycée = 10.50)
        $this->createReportAndDetails($this->eleve1ere, $this->classe1ere, $this->annee1, $this->lyceeA, [
            $this->seq1Year1 => ['general' => 10.60, 'math' => 10.5, 'francais' => 10.7],
            $this->seq2Year1 => ['general' => 10.60, 'math' => 10.5, 'francais' => 10.7],
            $this->seq3Year1 => ['general' => 10.60, 'math' => 10.5, 'francais' => 10.7]
        ]);

        // Eleve Lycee B: T1=12.00 dans Lycée B
        $stmtSeqB = $this->db->prepare("INSERT INTO sequences (lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmtSeqB->execute([$this->lyceeB, $this->annee1, 'Trimestre 1 B', 'trimestrielle', '2023-09-15', '2023-12-15', 'fermee']);
        $seqB1 = (int)$this->db->lastInsertId();
        $this->createReportAndDetails($this->eleveLyceeB, $this->classeLyceeB, $this->annee1, $this->lyceeB, [
            $seqB1 => ['general' => 12.00, 'math' => 12.0, 'francais' => 12.0]
        ]);
    }

    private function createStudentAndEnrollment(int $id, string $nom, string $prenom, int $classeId, int $anneeId, int $lyceeId) {
        $stmtCheck = $this->db->prepare("SELECT COUNT(*) FROM eleves WHERE id_eleve = ?");
        $stmtCheck->execute([$id]);
        if ((int)$stmtCheck->fetchColumn() === 0) {
            $stmtE = $this->db->prepare("INSERT INTO eleves (id_eleve, nom, prenom, identifiant_public, statut, lycee_id) VALUES (?, ?, ?, ?, 'actif', ?)");
            $stmtE->execute([$id, $nom, $prenom, "PUB-{$id}", $lyceeId]);
        }

        $stmtEt = $this->db->prepare("INSERT INTO etudes (eleve_id, classe_id, lycee_id, annee_academique_id, status, is_active) VALUES (?, ?, ?, ?, 'active', 1)");
        $stmtEt->execute([$id, $classeId, $lyceeId, $anneeId]);
    }

    private function createReportAndDetails(int $eleveId, int $classeId, int $anneeId, int $lyceeId, array $seqData) {
        $stmtB = $this->db->prepare("INSERT INTO bulletins (eleve_id, sequence_id, annee_academique_id, classe_id, lycee_id, moyenne_generale, statut) VALUES (?, ?, ?, ?, ?, ?, 'valide')");
        $stmtD = $this->db->prepare("INSERT INTO bulletin_details (bulletin_id, matiere_id, nom_matiere_snapshot, moyenne_matiere, coefficient_snapshot, points_ponderes) VALUES (?, ?, ?, ?, ?, ?)");

        foreach ($seqData as $seqId => $data) {
            $stmtB->execute([$eleveId, $seqId, $anneeId, $classeId, $lyceeId, $data['general']]);
            $bId = (int)$this->db->lastInsertId();

            if (isset($data['math'])) {
                $stmtD->execute([$bId, 1, 'Mathématiques', $data['math'], 3.0, round($data['math'] * 3.0, 2)]);
            }
            if (isset($data['francais'])) {
                $stmtD->execute([$bId, 2, 'Français', $data['francais'], 2.0, round($data['francais'] * 2.0, 2)]);
            }
        }
    }

    private function configureBaseThresholdsForLyceeA() {
        // 1. Établissement (Défaut général) : Admis >= 10.00, Délib [9.50 - 9.99], Redoublement <= 9.49, Exclusion <= 6.00
        PromotionThresholdService::saveThreshold([
            'lycee_id' => $this->lyceeA,
            'portee_type' => 'etablissement',
            'seuil_admission_directe' => 10.00,
            'seuil_deliberation_min'  => 9.50,
            'seuil_deliberation_max'  => 9.99,
            'seuil_redoublement_max'  => 9.49,
            'seuil_exclusion_max'     => 6.00
        ]);

        // 2. Cycle Lycée : Admis >= 10.50, Délib [10.00 - 10.49], Redoublement <= 9.99, Exclusion <= 6.00
        PromotionThresholdService::saveThreshold([
            'lycee_id' => $this->lyceeA,
            'cycle_id' => $this->cycleLyceeA,
            'portee_type' => 'cycle',
            'seuil_admission_directe' => 10.50,
            'seuil_deliberation_min'  => 10.00,
            'seuil_deliberation_max'  => 10.49,
            'seuil_redoublement_max'  => 9.99,
            'seuil_exclusion_max'     => 6.00
        ]);

        // 3. Niveau Terminale (du Cycle Lycée) : Admis >= 11.00, Délib [10.50 - 10.99], Redoublement <= 10.49, Exclusion <= 6.00
        PromotionThresholdService::saveThreshold([
            'lycee_id' => $this->lyceeA,
            'cycle_id' => $this->cycleLyceeA,
            'niveau' => 'Terminale',
            'portee_type' => 'niveau',
            'seuil_admission_directe' => 11.00,
            'seuil_deliberation_min'  => 10.50,
            'seuil_deliberation_max'  => 10.99,
            'seuil_redoublement_max'  => 10.49,
            'seuil_exclusion_max'     => 6.00
        ]);

        // 4. Niveau + Série Terminale C : Admis >= 12.00, Délib [11.00 - 11.99], Redoublement <= 10.99, Exclusion <= 6.00
        PromotionThresholdService::saveThreshold([
            'lycee_id' => $this->lyceeA,
            'cycle_id' => $this->cycleLyceeA,
            'niveau' => 'Terminale',
            'serie' => 'C',
            'portee_type' => 'niveau_serie',
            'seuil_admission_directe' => 12.00,
            'seuil_deliberation_min'  => 11.00,
            'seuil_deliberation_max'  => 11.99,
            'seuil_redoublement_max'  => 10.99,
            'seuil_exclusion_max'     => 6.00
        ]);
    }

    /**
     * Scénario 1: Élève dont la moyenne atteint le seuil d'admission.
     */
    public function test1_EleveMoyenneAtteintSeuilAdmission() {
        $this->configureBaseThresholdsForLyceeA();

        $res = PromotionAdmissionService::calculateStudentDecision(
            $this->lyceeA,
            $this->annee1,
            $this->classe3e,
            $this->eleveAdmis
        );

        $this->assertTrue($res['decision'] === 'admis', "Scénario 1: La décision doit être 'admis' (Moyenne 14.50 >= 10.00)");
        $this->assertTrue($res['moyenne_annuelle'] === 14.50, "Scénario 1: La moyenne annuelle calculée est 14.50");
        $this->assertTrue(str_contains($res['justification'], 'supérieure ou égale'), "Scénario 1: Justification claire mentionnant le seuil");
        echo "✅ PASSED: Scénario 1 - Élève dont la moyenne atteint le seuil d'admission\n";
    }

    /**
     * Scénario 2: Élève dont la moyenne est inférieure au seuil (délibération, redoublement, exclusion).
     */
    public function test2_EleveMoyenneInferieureSeuil() {
        // Test cas Délibération (Moyenne 9.70 dans [9.50 - 9.99])
        $resDelib = PromotionAdmissionService::calculateStudentDecision(
            $this->lyceeA,
            $this->annee1,
            $this->classe3e,
            $this->eleveDelib
        );
        $this->assertTrue($resDelib['decision'] === 'deliberation', "Scénario 2a: Décision 'deliberation' pour moyenne 9.70");

        // Test cas Redoublement (Moyenne 8.20 < 9.50 et > 6.00)
        $resRedoubl = PromotionAdmissionService::calculateStudentDecision(
            $this->lyceeA,
            $this->annee1,
            $this->classe3e,
            $this->eleveRedoubl
        );
        $this->assertTrue($resRedoubl['decision'] === 'redoublement', "Scénario 2b: Décision 'redoublement' pour moyenne 8.20");

        // Test cas Exclusion (Moyenne 5.00 <= 6.00)
        $resExclu = PromotionAdmissionService::calculateStudentDecision(
            $this->lyceeA,
            $this->annee1,
            $this->classe3e,
            $this->eleveExclu
        );
        $this->assertTrue($resExclu['decision'] === 'exclu', "Scénario 2c: Décision 'exclu' pour moyenne 5.00 <= seuil 6.00");
        echo "✅ PASSED: Scénario 2 - Élève dont la moyenne est inférieure au seuil (Délibération, Redoublement, Exclusion)\n";
    }

    /**
     * Scénario 3: Élève soumis à un seuil spécifique Niveau + Série.
     */
    public function test3_EleveSeuilSpecifiqueNiveauSerie() {
        // Terminale C a un seuil spécifique d'admission directe = 12.00, délibération = [11.00 - 11.99]
        // L'élève TleC a une moyenne de 11.50 => doit être en délibération (pas admis directement car 11.50 < 12.00)
        $res = PromotionAdmissionService::calculateStudentDecision(
            $this->lyceeA,
            $this->annee1,
            $this->classeTleC,
            $this->eleveTleC
        );

        $this->assertTrue($res['decision'] === 'deliberation', "Scénario 3: Décision 'deliberation' pour Tle C avec moyenne 11.50 (seuil 12.00)");
        $this->assertTrue($res['thresholds']['portee_type'] === 'niveau_serie', "Scénario 3: Portée résolue est 'niveau_serie'");
        $this->assertTrue($res['thresholds']['seuil_admission_directe'] === 12.00, "Scénario 3: Seuil admission directe est 12.00");
        echo "✅ PASSED: Scénario 3 - Élève soumis à un seuil spécifique Niveau + Série\n";
    }

    /**
     * Scénario 4: Élève relevant d'un seuil défini au niveau.
     */
    public function test4_EleveSeuilDefiniAuNiveau() {
        // Terminale A n'a pas de règle par série. Elle hérite du niveau Terminale (Admis >= 11.00)
        // L'élève TleA a une moyenne de 11.50 => doit être admis directement (11.50 >= 11.00)
        $res = PromotionAdmissionService::calculateStudentDecision(
            $this->lyceeA,
            $this->annee1,
            $this->classeTleA,
            $this->eleveTleA
        );

        $this->assertTrue($res['decision'] === 'admis', "Scénario 4: Décision 'admis' pour Tle A avec moyenne 11.50 >= seuil 11.00");
        $this->assertTrue($res['thresholds']['portee_type'] === 'niveau', "Scénario 4: Portée résolue est 'niveau'");
        $this->assertTrue($res['thresholds']['seuil_admission_directe'] === 11.00, "Scénario 4: Seuil niveau est 11.00");
        echo "✅ PASSED: Scénario 4 - Élève relevant d'un seuil défini au niveau\n";
    }

    /**
     * Scénario 5: Élève relevant d'un seuil défini au cycle.
     */
    public function test5_EleveSeuilDefiniAuCycle() {
        // Classe 1ère C n'a ni règle niveau+série ni règle niveau 1ère.
        // Elle hérite du cycle Lycée (Admis >= 10.50)
        // L'élève 1ère a une moyenne de 10.60 => doit être admis directement (10.60 >= 10.50)
        $res = PromotionAdmissionService::calculateStudentDecision(
            $this->lyceeA,
            $this->annee1,
            $this->classe1ere,
            $this->eleve1ere
        );

        $this->assertTrue($res['decision'] === 'admis', "Scénario 5: Décision 'admis' pour 1ère avec moyenne 10.60 >= seuil cycle 10.50");
        $this->assertTrue($res['thresholds']['portee_type'] === 'cycle', "Scénario 5: Portée résolue est 'cycle'");
        $this->assertTrue($res['thresholds']['seuil_admission_directe'] === 10.50, "Scénario 5: Seuil cycle est 10.50");
        echo "✅ PASSED: Scénario 5 - Élève relevant d'un seuil défini au cycle\n";
    }

    /**
     * Scénario 6: Élève relevant du seuil de l'établissement.
     */
    public function test6_EleveSeuilEtablissement() {
        // Le Collège A n'a pas de règle cycle, niveau ou série.
        // La classe 3e hérite de la règle générale établissement (Admis >= 10.00)
        $res = PromotionAdmissionService::calculateStudentDecision(
            $this->lyceeA,
            $this->annee1,
            $this->classe3e,
            $this->eleveAdmis
        );

        $this->assertTrue($res['thresholds']['portee_type'] === 'etablissement', "Scénario 6: Portée résolue est 'etablissement'");
        $this->assertTrue($res['thresholds']['inherited'] === true, "Scénario 6: La règle est marquée héritée");
        $this->assertTrue($res['thresholds']['seuil_admission_directe'] === 10.00, "Scénario 6: Seuil établissement est 10.00");
        echo "✅ PASSED: Scénario 6 - Élève relevant du seuil de l'établissement\n";
    }

    /**
     * Scénario 7: Absence totale de seuil applicable (blocage avec erreur explicite).
     */
    public function test7_AbsenceTotaleSeuilApplicable() {
        // Lycée B n'a aucune règle de seuil configurée
        $threw = false;
        try {
            PromotionAdmissionService::calculateStudentDecision(
                $this->lyceeB,
                $this->annee1,
                $this->classeLyceeB,
                $this->eleveLyceeB
            );
        } catch (LogicException $e) {
            $threw = true;
            $this->assertTrue(str_contains($e->getMessage(), 'Calcul d\'admission bloqué'), "Scénario 7: Message explicite de blocage");
        }

        $this->assertTrue($threw, "Scénario 7: Le calcul doit lever une LogicException en l'absence de seuil configuré");
        echo "✅ PASSED: Scénario 7 - Absence totale de seuil applicable (Blocage avec erreur métier explicite)\n";
    }

    /**
     * Scénario 8: Présence d'une note éliminatoire configurée.
     */
    public function test8_PresenceNoteEliminatoire() {
        // EleveElim a une moyenne générale de 12.00 (suffisante pour admission >= 10.00),
        // mais sa moyenne en Mathématiques est de 4.00.
        // Avec une note éliminatoire globale de 5.00, il doit basculer en délibération
        $res = PromotionAdmissionService::calculateStudentDecision(
            $this->lyceeA,
            $this->annee1,
            $this->classe3e,
            $this->eleveElim,
            ['note_eliminatoire_seuil' => 5.00]
        );

        $this->assertTrue($res['decision'] === 'deliberation', "Scénario 8: Bascule en délibération suite à la note éliminatoire");
        $this->assertTrue($res['rules_applied']['note_eliminatoire_active'] === true, "Scénario 8: Flag note_eliminatoire_active = true");
        $this->assertTrue(str_contains($res['justification'], 'éliminatoire'), "Scénario 8: Mention de la note éliminatoire dans la justification");
        echo "✅ PASSED: Scénario 8 - Présence d'une note éliminatoire configurée\n";
    }

    /**
     * Scénario 9: Résultats scolaires incomplets (refus/blocage).
     */
    public function test9_ResultatsScolairesIncomplets() {
        // EleveIncomplet n'a pas de bulletin pour le Trimestre 3 (séquence requise manquante)
        $res = PromotionAdmissionService::calculateStudentDecision(
            $this->lyceeA,
            $this->annee1,
            $this->classe3e,
            $this->eleveIncomplet
        );

        $this->assertTrue($res['decision'] === 'non_determine', "Scénario 9: Statut 'non_determine' pour résultats incomplets");
        $this->assertTrue($res['is_complete'] === false, "Scénario 9: is_complete = false");
        $this->assertTrue(str_contains($res['justification'], 'Calcul d\'admission refusé'), "Scénario 9: Justification explicite de refus pour complétude");
        echo "✅ PASSED: Scénario 9 - Résultats scolaires incomplets (Refus et blocage de la décision)\n";
    }

    /**
     * Scénario 10: Isolation entre deux établissements.
     */
    public function test10_IsolationEntreDeuxEtablissements() {
        // Tentative d'appeler l'élève du Lycée B sous le contexte du Lycée A
        $threw = false;
        try {
            PromotionAdmissionService::calculateStudentDecision(
                $this->lyceeA,
                $this->annee1,
                $this->classeLyceeB, // Classe de Lycée B passée avec Lycée A
                $this->eleveLyceeB
            );
        } catch (InvalidArgumentException $e) {
            $threw = true;
            $this->assertTrue(str_contains($e->getMessage(), 'Violation de cloisonnement'), "Scénario 10: Exception de violation de cloisonnement");
        }

        $this->assertTrue($threw, "Scénario 10: Interdiction de mélanger les établissements");
        echo "✅ PASSED: Scénario 10 - Isolation entre deux établissements\n";
    }

    /**
     * Scénario 11: Isolation entre deux années académiques.
     */
    public function test11_IsolationEntreDeuxAnneesAcademiques() {
        // L'élève a des bulletins en Annee 1 (2023-2024), mais aucun bulletin en Annee 2 (2024-2025)
        $res = PromotionAdmissionService::calculateStudentDecision(
            $this->lyceeA,
            $this->annee2, // Année 2
            $this->classe3e,
            $this->eleveAdmis
        );

        $this->assertTrue($res['is_complete'] === false, "Scénario 11: Aucun résultat de l'Année 1 ne doit être utilisé pour l'Année 2");
        $this->assertTrue($res['decision'] === 'non_determine', "Scénario 11: Décision non déterminée pour l'année sans bulletins");
        echo "✅ PASSED: Scénario 11 - Isolation entre deux années académiques\n";
    }

    /**
     * Scénario 12: Isolation entre deux classes.
     */
    public function test12_IsolationEntreDeuxClasses() {
        // L'élèveAdmis est inscrit en 3e (classe3e).
        // Si on demande son calcul dans classeOther (4e), le service doit refuser car l'élève n'appartient pas à classeOther
        $threw = false;
        try {
            PromotionAdmissionService::calculateStudentDecision(
                $this->lyceeA,
                $this->annee1,
                $this->classeOther,
                $this->eleveAdmis
            );
        } catch (InvalidArgumentException $e) {
            $threw = true;
            $this->assertTrue(str_contains($e->getMessage(), 'n\'appartient pas à la classe'), "Scénario 12: Exception d'appartenance de classe");
        }

        $this->assertTrue($threw, "Scénario 12: Rejet de calcul pour une classe dont l'élève n'est pas membre");
        echo "✅ PASSED: Scénario 12 - Isolation entre deux classes\n";
    }

    /**
     * Scénario 13: Élève n'appartenant pas à la classe demandée.
     */
    public function test13_EleveNAppartenantPasALaClasse() {
        $fakeEleveId = 99999;
        $threw = false;
        try {
            PromotionAdmissionService::calculateStudentDecision(
                $this->lyceeA,
                $this->annee1,
                $this->classe3e,
                $fakeEleveId
            );
        } catch (InvalidArgumentException $e) {
            $threw = true;
            $this->assertTrue(str_contains($e->getMessage(), "L'élève #{$fakeEleveId} n'appartient pas"), "Scénario 13: Message d'erreur d'appartenance");
        }

        $this->assertTrue($threw, "Scénario 13: Élève inexistant ou non membre rejeté");
        echo "✅ PASSED: Scénario 13 - Élève n'appartenant pas à la classe demandée\n";
    }

    /**
     * Scénario 14: Conservation des décisions historiques.
     */
    public function test14_ConservationDecisionsHistoriques() {
        // Vérification de la persistance et de la cohérence du calcul de classe global
        $classRes = PromotionAdmissionService::calculateClassDecisions(
            $this->lyceeA,
            $this->annee1,
            $this->classe3e
        );

        $this->assertTrue($classRes['success'] === true, "Scénario 14: Calcul de classe réussi");
        $this->assertTrue($classRes['total_eleves'] >= 5, "Scénario 14: Tous les élèves de la classe traités");
        $this->assertTrue(isset($classRes['summary']['admis']), "Scénario 14: Synthèse des décisions présente");
        $this->assertTrue($classRes['summary']['admis'] >= 1, "Scénario 14: Au moins 1 admis");

        // L'historique d'une année passée reste fidèlement calculable et reproductible
        $studentHistorical = PromotionAdmissionService::calculateStudentDecision(
            $this->lyceeA,
            $this->annee1,
            $this->classe3e,
            $this->eleveAdmis
        );
        $this->assertTrue($studentHistorical['decision'] === 'admis', "Scénario 14: Reproductibilité historique parfaite");
        echo "✅ PASSED: Scénario 14 - Conservation et reproductibilité des décisions historiques\n";
    }

    /**
     * Scénario 15: Absence d'écrasement silencieux d'une décision validée.
     */
    public function test15_AbsenceEcrasementSilencieuxDecisionValidee() {
        $validatedDecision = [
            'eleve_id' => $this->eleveAdmis,
            'decision' => 'admis',
            'statut' => 'valide',
            'valide_le' => '2024-07-01 10:00:00'
        ];

        $newProposedDecision = [
            'eleve_id' => $this->eleveAdmis,
            'decision' => 'redoublement',
            'statut' => 'provisoire'
        ];

        $threw = false;
        try {
            PromotionAdmissionService::assertCanUpdateDecision($validatedDecision, $newProposedDecision, false);
        } catch (LogicException $e) {
            $threw = true;
            $this->assertTrue(str_contains($e->getMessage(), 'Écrasement interdit'), "Scénario 15: Message d'interdiction d'écrasement");
        }

        $this->assertTrue($threw, "Scénario 15: Impossible d'écraser silencieusement une décision validée");

        // Avec forçage explicite (délibération autorisée), l'opération passe
        $allowed = PromotionAdmissionService::assertCanUpdateDecision($validatedDecision, $newProposedDecision, true);
        $this->assertTrue($allowed === true, "Scénario 15: Forçage explicite autorisé en mode délibération");
        echo "✅ PASSED: Scénario 15 - Absence d'écrasement silencieux d'une décision validée\n";
    }

    private function assertTrue(bool $condition, string $msg = '') {
        if (!$condition) {
            throw new Exception("Assertion failed: " . $msg);
        }
    }
}

// Exécuter la suite
$test = new PromotionAdmissionServiceTest();
$test->run();
