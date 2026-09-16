<?php

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/models/AnneeAcademique.php';
require_once __DIR__ . '/../src/models/Eleve.php';
require_once __DIR__ . '/../src/models/DisciplineTypeIncident.php';
require_once __DIR__ . '/../src/models/DisciplineTypeSanction.php';
require_once __DIR__ . '/../src/models/DisciplineIncident.php';
require_once __DIR__ . '/../src/models/DisciplineSanction.php';
require_once __DIR__ . '/../src/models/DisciplineConseil.php';
require_once __DIR__ . '/../src/models/DisciplineConseilMembre.php';
require_once __DIR__ . '/../src/models/DisciplineConseilEleve.php';
require_once __DIR__ . '/../src/models/DisciplineDocument.php';
require_once __DIR__ . '/../src/models/DisciplineNotification.php';
require_once __DIR__ . '/../src/models/DisciplineHistorique.php';
require_once __DIR__ . '/../src/controllers/DisciplineConseilController.php';

class DisciplineConseilPhase64Test {

    private function log(string $msg): void {
        echo "[DisciplineConseilPhase64Test] " . $msg . "\n";
    }

    public function runAllTests(): void {
        $this->log("Start Discipline Conseil Phase 6.4 Comprehensive Integration Tests...");

        $this->testSanctionCreationAndFieldsMapping();
        $this->testSanctionTypeInactiveRefused();
        $this->testEleveNotConvokedRefused();
        $this->testSanctionIdempotencyOnDoubleSubmission();
        $this->testLockingPostClotureAndAnnule();
        $this->testPvGenerationAndIdempotency();
        $this->testMultiTenantIsolationAndIdorProtection();
        $this->testEligibleElevesFilteringAndServerValidation();

        $this->log("ALL Discipline Conseil Phase 6.4 Integration Tests PASSED (100% SUCCESS)!");
    }

    private function createDummyContext(int $lyceeId = 901): array {
        $uniq = rand(10000, 99999);
        $db = Database::getInstance();

        // 1. Academic Year
        $stmtYear = $db->prepare("SELECT id FROM annees_academiques WHERE est_active = 1 LIMIT 1");
        $stmtYear->execute();
        $anneeId = $stmtYear->fetchColumn();

        if (!$anneeId) {
            $stmtInsYear = $db->prepare("INSERT INTO annees_academiques (libelle, date_debut, date_fin, est_active) VALUES ('2025-2026', '2025-09-01', '2026-06-30', 1)");
            $stmtInsYear->execute();
            $anneeId = (int)$db->lastInsertId();
        }

        // 2. Class
        $stmtClass = $db->prepare("SELECT id_classe FROM classes WHERE lycee_id = :lycee_id LIMIT 1");
        $stmtClass->execute([':lycee_id' => $lyceeId]);
        $classeId = $stmtClass->fetchColumn();

        if (!$classeId) {
            $stmtInsClass = $db->prepare("INSERT INTO classes (lycee_id, cycle_id, niveau, serie, numero) VALUES (:lycee_id, 2, 'Terminales', 'C', '1')");
            $stmtInsClass->execute([':lycee_id' => $lyceeId]);
            $classeId = (int)$db->lastInsertId();
        }

        // 3. President & Staff Users
        $uniq = rand(1000, 9999);
        $stmtInsPres = $db->prepare("INSERT INTO utilisateurs (lycee_id, nom, prenom, email, role_id, fonction, actif) VALUES (:lycee_id, 'President', 'Jean', :email, 3, 'Proviseur', 1)");
        $stmtInsPres->execute([':lycee_id' => $lyceeId, ':email' => 'pres_' . $uniq . '@test.ci']);
        $presidentUserId = (int)$db->lastInsertId();

        Auth::setSessionContext([
            'id_user' => $presidentUserId,
            'lycee_id' => $lyceeId,
            'role_id' => 1,
            'permissions' => [
                'discipline' => ['view_councils', 'manage_councils', 'manage_council_decisions', 'report_incident', 'manage_incident']
            ],
        ]);

        // 4. Student
        $stmtInsEleve = $db->prepare("INSERT INTO eleves (lycee_id, nom, prenom, identifiant_public, statut) VALUES (:lycee_id, 'Koffi', 'Paul', :ident, 'actif')");
        $stmtInsEleve->execute([':lycee_id' => $lyceeId, ':ident' => 'MAT64_' . $uniq]);
        $eleveId = (int)$db->lastInsertId();

        // Enroll Student
        $stmtInsEtude = $db->prepare("INSERT INTO etudes (eleve_id, classe_id, lycee_id, annee_academique_id, status, is_active) VALUES (:eleve_id, :classe_id, :lycee_id, :annee_id, 'actif', 1)");
        $stmtInsEtude->execute([':eleve_id' => $eleveId, ':classe_id' => $classeId, ':lycee_id' => $lyceeId, ':annee_id' => $anneeId]);

        // 5. Active Sanction Type
        DisciplineTypeSanction::save([
            'code' => 'EXC_TEMP_' . $uniq,
            'libelle' => 'Exclusion Temporaire (Conseil)',
            'autorite_min_requise' => 'conseil_discipline',
            'demande_duree_jours' => 1,
            'affiche_sur_bulletin' => 1,
            'actif' => 1,
        ]);

        $stRecord = DisciplineTypeSanction::findByCode('EXC_TEMP_' . $uniq, $lyceeId);
        $typeSanctionId = (int)$stRecord['id'];

        // 6. Active Incident Type & Incident
        DisciplineTypeIncident::save([
            'code' => 'INC64_' . $uniq,
            'libelle' => 'Insubordination grave',
            'niveau_gravite' => 'tres_grave',
            'actif' => 1,
        ]);
        $incRecord = DisciplineTypeIncident::findByCode('INC64_' . $uniq, $lyceeId);
        $typeIncId = (int)$incRecord['id'];

        $incidentId = DisciplineIncident::create([
            'lycee_id' => $lyceeId,
            'annee_academique_id' => (int)$anneeId,
            'type_incident_id' => (int)$typeIncId,
            'date_incident' => date('Y-m-d'),
            'heure_incident' => '09:00:00',
            'lieu' => 'Classe',
            'description_faits' => 'Insubordination et refus d intempéries',
            'statut' => 'signale',
            'signale_par_user_id' => $presidentUserId,
        ], [
            [
                'eleve_id' => $eleveId,
                'role_eleve' => 'auteur_principal',
                'observation' => 'Auteur des faits'
            ]
        ]);

        return [
            'lycee_id' => $lyceeId,
            'annee_id' => (int)$anneeId,
            'classe_id' => (int)$classeId,
            'president_user_id' => $presidentUserId,
            'eleve_id' => $eleveId,
            'type_sanction_id' => $typeSanctionId,
            'incident_id' => $incidentId,
        ];
    }

    public function testSanctionCreationAndFieldsMapping(): void {
        $ctx = $this->createDummyContext();
        $code = 'CD64_' . rand(10000, 99999);

        $conseilId = DisciplineConseil::create([
            'lycee_id' => $ctx['lycee_id'],
            'annee_academique_id' => $ctx['annee_id'],
            'code' => $code,
            'titre' => 'Conseil Test Phase 6.4 Sanctions',
            'date_conseil' => date('Y-m-d'),
            'president_user_id' => $ctx['president_user_id'],
            'statut' => 'planifie',
        ]);

        DisciplineConseilEleve::addEleve([
            'conseil_id' => $conseilId,
            'eleve_id' => $ctx['eleve_id'],
            'motif_convocation' => 'Motif convocation test 6.4',
        ]);

        DisciplineConseilEleve::linkIncident($conseilId, $ctx['incident_id'], $ctx['eleve_id']);

        // Transition: planifie -> convoque -> en_session -> delibere
        DisciplineConseil::updateStatus($conseilId, 'convoque', $ctx['president_user_id']);
        DisciplineConseil::updateStatus($conseilId, 'en_session', $ctx['president_user_id']);
        DisciplineConseil::updateStatus($conseilId, 'delibere', $ctx['president_user_id']);

        // Record Decision: sanctionne
        $motivation = "L élève s est rendu coupable d insubordination grave et de violences verbales.";
        $res = DisciplineConseilEleve::recordDecision($conseilId, $ctx['eleve_id'], [
            'decision_statut' => 'sanctionne',
            'type_sanction_id' => $ctx['type_sanction_id'],
            'votes_pour' => 5,
            'votes_contre' => 1,
            'abstentions' => 0,
            'duree_jours' => 3,
            'motivation_decision' => $motivation,
        ]);

        if (!$res) {
            throw new Exception("L enregistrement de la décision 'sanctionne' aurait dû réussir.");
        }

        // Verify convocation row updated with sanction_id
        $eleves = DisciplineConseilEleve::findByConseilId($conseilId);
        $conv = $eleves[0];
        if ($conv['decision_statut'] !== 'sanctionne' || empty($conv['sanction_id'])) {
            throw new Exception("Le statut 'sanctionne' et l ID de sanction auraient dû être enregistrés dans discipline_conseil_eleves.");
        }

        // Verify Sanction Record in discipline_sanctions
        $sanctionId = (int)$conv['sanction_id'];
        $sanction = DisciplineSanction::findById($sanctionId, $ctx['lycee_id']);

        if (!$sanction) {
            throw new Exception("La sanction créée dans discipline_sanctions est introuvable.");
        }

        // Validate 7 Mandated Mappings
        if ((int)$sanction['eleve_id'] !== $ctx['eleve_id']) {
            throw new Exception("1. eleve_id ne correspond pas.");
        }
        if ((int)$sanction['prononcee_par_user_id'] !== $ctx['president_user_id']) {
            throw new Exception("2. prononcee_par_user_id doit être le Président du conseil.");
        }
        if ((int)$sanction['classe_id'] !== $ctx['classe_id']) {
            throw new Exception("3. classe_id doit être le snapshot classe du conseil.");
        }
        if ($sanction['date_decision'] !== date('Y-m-d')) {
            throw new Exception("4. date_decision doit correspondre à la date du conseil.");
        }
        if ($sanction['motif'] !== $motivation) {
            throw new Exception("5. La motivation n a pas été correctement transmise.");
        }
        if ((int)$sanction['incident_id'] !== $ctx['incident_id']) {
            throw new Exception("6. L incident rattaché n a pas été associé à la sanction.");
        }
        if ((int)$sanction['type_sanction_id'] !== $ctx['type_sanction_id']) {
            throw new Exception("7. Le type de sanction ne correspond pas.");
        }

        $this->log("testSanctionCreationAndFieldsMapping passed.");
    }

    public function testSanctionTypeInactiveRefused(): void {
        $ctx = $this->createDummyContext();
        $code = 'CD64_' . rand(1000, 9999);

        // Create Inactive Sanction Type
        $codeInact = 'INACT_' . rand(1000, 9999);
        DisciplineTypeSanction::save([
            'code' => $codeInact,
            'libelle' => 'Type Inactif',
            'actif' => 0,
        ]);
        $inactRec = DisciplineTypeSanction::findByCode($codeInact, $ctx['lycee_id']);
        $inactiveTypeId = (int)$inactRec['id'];

        $conseilId = DisciplineConseil::create([
            'lycee_id' => $ctx['lycee_id'],
            'annee_academique_id' => $ctx['annee_id'],
            'code' => $code,
            'titre' => 'Conseil Type Inactif Test',
            'date_conseil' => date('Y-m-d'),
            'president_user_id' => $ctx['president_user_id'],
            'statut' => 'delibere',
        ]);

        DisciplineConseilEleve::addEleve([
            'conseil_id' => $conseilId,
            'eleve_id' => $ctx['eleve_id'],
            'motif_convocation' => 'Test motif',
        ]);

        try {
            DisciplineConseilEleve::recordDecision($conseilId, $ctx['eleve_id'], [
                'decision_statut' => 'sanctionne',
                'type_sanction_id' => $inactiveTypeId,
                'votes_pour' => 4,
                'votes_contre' => 0,
                'motivation_decision' => 'Test motivation avec type inactif',
            ]);
            throw new Exception("L utilisation d un type de sanction inactif doit être strictement rejetée.");
        } catch (InvalidArgumentException $e) {
            // Expected
        }

        $this->log("testSanctionTypeInactiveRefused passed.");
    }

    public function testEleveNotConvokedRefused(): void {
        $ctx = $this->createDummyContext();
        $code = 'CD64_' . rand(1000, 9999);

        $conseilId = DisciplineConseil::create([
            'lycee_id' => $ctx['lycee_id'],
            'annee_academique_id' => $ctx['annee_id'],
            'code' => $code,
            'titre' => 'Conseil Non Convoque Test',
            'date_conseil' => date('Y-m-d'),
            'president_user_id' => $ctx['president_user_id'],
            'statut' => 'delibere',
        ]);

        try {
            DisciplineConseilEleve::recordDecision($conseilId, 999999, [
                'decision_statut' => 'relaxe',
                'votes_pour' => 3,
                'votes_contre' => 0,
                'motivation_decision' => 'Motif élève inconnu',
            ]);
            throw new Exception("Une décision sur un élève non convoqué doit être refusée.");
        } catch (InvalidArgumentException $e) {
            // Expected
        }

        $this->log("testEleveNotConvokedRefused passed.");
    }

    public function testSanctionIdempotencyOnDoubleSubmission(): void {
        $ctx = $this->createDummyContext();
        $code = 'CD64_' . rand(1000, 9999);

        $conseilId = DisciplineConseil::create([
            'lycee_id' => $ctx['lycee_id'],
            'annee_academique_id' => $ctx['annee_id'],
            'code' => $code,
            'titre' => 'Conseil Idempotency Test',
            'date_conseil' => date('Y-m-d'),
            'president_user_id' => $ctx['president_user_id'],
            'statut' => 'delibere',
        ]);

        DisciplineConseilEleve::addEleve([
            'conseil_id' => $conseilId,
            'eleve_id' => $ctx['eleve_id'],
            'motif_convocation' => 'Convocation test',
        ]);

        // First Submission
        DisciplineConseilEleve::recordDecision($conseilId, $ctx['eleve_id'], [
            'decision_statut' => 'sanctionne',
            'type_sanction_id' => $ctx['type_sanction_id'],
            'votes_pour' => 5,
            'votes_contre' => 0,
            'duree_jours' => 2,
            'motivation_decision' => 'Motivation initiale',
        ]);

        $eleves1 = DisciplineConseilEleve::findByConseilId($conseilId);
        $initialSanctionId = $eleves1[0]['sanction_id'];

        // Second Submission (Simulating Double Click)
        DisciplineConseilEleve::recordDecision($conseilId, $ctx['eleve_id'], [
            'decision_statut' => 'sanctionne',
            'type_sanction_id' => $ctx['type_sanction_id'],
            'votes_pour' => 5,
            'votes_contre' => 0,
            'duree_jours' => 2,
            'motivation_decision' => 'Motivation soumission 2',
        ]);

        $eleves2 = DisciplineConseilEleve::findByConseilId($conseilId);
        $finalSanctionId = $eleves2[0]['sanction_id'];

        if ((int)$initialSanctionId !== (int)$finalSanctionId) {
            throw new Exception("L idempotence a échoué : la double soumission a généré deux sanctions distinctes !");
        }

        $this->log("testSanctionIdempotencyOnDoubleSubmission passed.");
    }

    public function testLockingPostClotureAndAnnule(): void {
        $ctx = $this->createDummyContext();
        $code = 'CD64_' . rand(1000, 9999);

        $conseilId = DisciplineConseil::create([
            'lycee_id' => $ctx['lycee_id'],
            'annee_academique_id' => $ctx['annee_id'],
            'code' => $code,
            'titre' => 'Conseil Locking Test',
            'date_conseil' => date('Y-m-d'),
            'president_user_id' => $ctx['president_user_id'],
            'statut' => 'delibere',
        ]);

        DisciplineConseilEleve::addEleve([
            'conseil_id' => $conseilId,
            'eleve_id' => $ctx['eleve_id'],
            'motif_convocation' => 'Test lock',
        ]);

        // Close Council
        DisciplineConseil::updateStatus($conseilId, 'cloture', $ctx['president_user_id']);

        // Attempt Decision on Closed Council
        try {
            DisciplineConseilEleve::recordDecision($conseilId, $ctx['eleve_id'], [
                'decision_statut' => 'relaxe',
                'votes_pour' => 5,
                'votes_contre' => 0,
                'motivation_decision' => 'Tentative post cloture',
            ]);
            throw new Exception("La prise de décision doit échouer sur un conseil clôturé.");
        } catch (InvalidArgumentException $e) {
            // Expected
        }

        // Attempt PV generation on Closed Council
        Auth::setSessionContext([
            'id_user' => $ctx['president_user_id'],
            'lycee_id' => $ctx['lycee_id'],
            'role_id' => 1,
            'permissions' => [
                'discipline' => ['view_councils', 'manage_councils', 'manage_council_decisions']
            ],
        ]);
        $_POST['council_id'] = $conseilId;
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $ctrl = new DisciplineConseilController();
        // Test controller locking
        unset($_SESSION['success'], $_SESSION['error']);
        $ctrl->generatePv();
        if (empty($_SESSION['error'])) {
            var_dump("SESSION DUMP:", $_SESSION);
            throw new Exception("La génération d un nouveau PV doit être refusée sur un conseil clôturé.");
        }

        $this->log("testLockingPostClotureAndAnnule passed.");
    }

    public function testPvGenerationAndIdempotency(): void {
        $ctx = $this->createDummyContext();
        $code = 'CD64_' . rand(1000, 9999);

        $conseilId = DisciplineConseil::create([
            'lycee_id' => $ctx['lycee_id'],
            'annee_academique_id' => $ctx['annee_id'],
            'code' => $code,
            'titre' => 'Conseil PV Test',
            'date_conseil' => date('Y-m-d'),
            'president_user_id' => $ctx['president_user_id'],
            'statut' => 'delibere',
        ]);

        Auth::setSessionContext([
            'id_user' => $ctx['president_user_id'],
            'lycee_id' => $ctx['lycee_id'],
            'role_id' => 1,
            'permissions' => [
                'discipline' => ['view_councils', 'manage_councils', 'manage_council_decisions']
            ],
        ]);

        // First PV generation
        $_POST['council_id'] = $conseilId;
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $ctrl = new DisciplineConseilController();
        $ctrl->generatePv();

        $docs1 = DisciplineDocument::findByConseilId($conseilId, $ctx['lycee_id']);
        if (count($docs1) !== 1) {
            throw new Exception("Le PV officiel aurait dû être archivé dans discipline_documents.");
        }

        if ((int)$docs1[0]['conseil_id'] !== $conseilId) {
            throw new Exception("Le conseil_id du document n est pas correctement renseigné.");
        }

        // Second PV generation (Idempotency test)
        unset($_SESSION['success'], $_SESSION['error']);
        $ctrl->generatePv();

        $docs2 = DisciplineDocument::findByConseilId($conseilId, $ctx['lycee_id']);
        if (count($docs2) !== 1) {
            throw new Exception("L idempotence du PV a échoué : un deuxième document PV a été créé !");
        }

        $this->log("testPvGenerationAndIdempotency passed.");
    }

    public function testEligibleElevesFilteringAndServerValidation(): void {
        $db = Database::getInstance();
        $ctx = $this->createDummyContext();
        $code = 'CD64_ELG_' . rand(1000, 9999);

        $conseilId = DisciplineConseil::create([
            'lycee_id' => $ctx['lycee_id'],
            'annee_academique_id' => $ctx['annee_id'],
            'code' => $code,
            'titre' => 'Conseil Eligible Test',
            'date_conseil' => date('Y-m-d'),
            'president_user_id' => $ctx['president_user_id'],
            'statut' => 'planifie',
        ]);

        // Student A (ctx['eleve_id']) has an incident -> ELIGIBLE
        $eligibleList = DisciplineConseilEleve::findEligibleElevesForCouncil($conseilId);
        $eligibleIds = array_column($eligibleList, 'id_eleve');

        if (!in_array($ctx['eleve_id'], $eligibleIds)) {
            throw new Exception("L élève ayant un incident signalé DOIT figurer dans la liste des éligibles !");
        }

        // Student B: Clean student without incidents or active sanctions -> NOT ELIGIBLE
        $uniq = rand(10000, 99999);
        $stmtInsEleveB = $db->prepare("INSERT INTO eleves (lycee_id, nom, prenom, identifiant_public, statut) VALUES (:lycee_id, 'Clean', 'Student', :ident, 'actif')");
        $stmtInsEleveB->execute([':lycee_id' => $ctx['lycee_id'], ':ident' => 'MAT_CLEAN_' . $uniq]);
        $eleveCleanId = (int)$db->lastInsertId();

        $stmtInsEtudeB = $db->prepare("INSERT INTO etudes (eleve_id, classe_id, lycee_id, annee_academique_id, status, is_active) VALUES (:eleve_id, :classe_id, :lycee_id, :annee_id, 'actif', 1)");
        $stmtInsEtudeB->execute([':eleve_id' => $eleveCleanId, ':classe_id' => $ctx['classe_id'], ':lycee_id' => $ctx['lycee_id'], ':annee_id' => $ctx['annee_id']]);

        $eligibleList2 = DisciplineConseilEleve::findEligibleElevesForCouncil($conseilId);
        $eligibleIds2 = array_column($eligibleList2, 'id_eleve');

        if (in_array($eleveCleanId, $eligibleIds2)) {
            throw new Exception("L élève sans incident ni sanction active NE DOIT PAS figurer dans la liste des éligibles !");
        }

        // Test Server-side rejection when attempting to convoke an ineligible student
        try {
            DisciplineConseilEleve::addEleve([
                'conseil_id' => $conseilId,
                'eleve_id' => $eleveCleanId,
                'motif_convocation' => 'Tentative convocation élève inéligible',
            ]);
            throw new Exception("Le serveur doit strictement rejeter la convocation d un élève non éligible !");
        } catch (InvalidArgumentException $e) {
            // Expected
        }

        // Convoke Student A -> verify Student A is now EXCLUDED from eligible list (no duplicates)
        DisciplineConseilEleve::addEleve([
            'conseil_id' => $conseilId,
            'eleve_id' => $ctx['eleve_id'],
            'motif_convocation' => 'Convocation valide',
        ]);

        $eligibleList3 = DisciplineConseilEleve::findEligibleElevesForCouncil($conseilId);
        $eligibleIds3 = array_column($eligibleList3, 'id_eleve');

        if (in_array($ctx['eleve_id'], $eligibleIds3)) {
            throw new Exception("Un élève DÉJÀ convoqué à ce conseil NE DOIT PLUS figurer dans la liste des éligibles !");
        }

        $this->log("testEligibleElevesFilteringAndServerValidation passed.");
    }

    public function testMultiTenantIsolationAndIdorProtection(): void {
        $ctx = $this->createDummyContext(901);
        $otherLyceeId = 902;

        $code = 'CD64_' . rand(1000, 9999);
        $conseilId = DisciplineConseil::create([
            'lycee_id' => $ctx['lycee_id'],
            'annee_academique_id' => $ctx['annee_id'],
            'code' => $code,
            'titre' => 'Conseil Tenant Isolation Test',
            'date_conseil' => date('Y-m-d'),
            'president_user_id' => $ctx['president_user_id'],
            'statut' => 'delibere',
        ]);

        // Attempt access from another lycee tenant
        $councilCheck = DisciplineConseil::findById($conseilId, $otherLyceeId);
        if ($councilCheck !== null) {
            throw new Exception("IDOR/Multi-tenant failure: Conseil visible depuis un autre établissement !");
        }

        $this->log("testMultiTenantIsolationAndIdorProtection passed.");
    }
}
