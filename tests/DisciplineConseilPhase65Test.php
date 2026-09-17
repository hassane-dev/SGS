<?php

define('TEST_MODE', true);

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/core/View.php';
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
require_once __DIR__ . '/../src/controllers/EleveController.php';

class DisciplineConseilPhase65Test {

    private function log(string $msg): void {
        echo "[DisciplineConseilPhase65Test] " . $msg . "\n";
    }

    public function runAllTests(): void {
        $this->log("Start Discipline Conseil Phase 6.5 Comprehensive Integration Tests...");

        $this->test1_ClosureBlockedWithPendingDecision();
        $this->test2_ClosureAllowedWithAllDecisionsProcessed();
        $this->test3_ClosureFieldsRecordedCorrectly();
        $this->test4_LockingPostClosure();
        $this->test5_6_StudentDisciplineTabDisplaysCouncilBlockAndData();
        $this->test7_MultiTenantProtection();
        $this->test8_IdorAndTeacherScopeProtection();
        $this->test9_NonRegressionSanctionsPvAndHistory();
        $this->test10_RegressionAllDisciplinePhases();

        $this->log("ALL Discipline Conseil Phase 6.5 Integration Tests PASSED (100% SUCCESS)!");
    }

    private function createDummyContext(int $lyceeId = 951): array {
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
        $stmtInsPres = $db->prepare("INSERT INTO utilisateurs (lycee_id, nom, prenom, email, role_id, fonction, actif) VALUES (:lycee_id, 'President', 'Jean', :email, 3, 'Proviseur', 1)");
        $stmtInsPres->execute([':lycee_id' => $lyceeId, ':email' => 'pres_' . $uniq . '@test.ci']);
        $presidentUserId = (int)$db->lastInsertId();

        Auth::setSessionContext([
            'id_user' => $presidentUserId,
            'lycee_id' => $lyceeId,
            'role_id' => 1,
            'permissions' => [
                'eleve' => ['view_all', 'edit'],
                'discipline' => ['view_councils', 'manage_councils', 'manage_council_decisions', 'report_incident', 'manage_incident', 'view_incidents', 'view_sanctions', 'view_history']
            ],
        ]);

        // 4. Students
        $stmtInsEleve1 = $db->prepare("INSERT INTO eleves (lycee_id, nom, prenom, identifiant_public, statut) VALUES (:lycee_id, 'Koffi', 'Paul', :ident, 'actif')");
        $stmtInsEleve1->execute([':lycee_id' => $lyceeId, ':ident' => 'MAT65_1_' . $uniq]);
        $eleveId1 = (int)$db->lastInsertId();

        $stmtInsEtude1 = $db->prepare("INSERT INTO etudes (eleve_id, classe_id, lycee_id, annee_academique_id, status, is_active) VALUES (:eleve_id, :classe_id, :lycee_id, :annee_id, 'actif', 1)");
        $stmtInsEtude1->execute([':eleve_id' => $eleveId1, ':classe_id' => $classeId, ':lycee_id' => $lyceeId, ':annee_id' => $anneeId]);

        $stmtInsEleve2 = $db->prepare("INSERT INTO eleves (lycee_id, nom, prenom, identifiant_public, statut) VALUES (:lycee_id, 'Aka', 'Awa', :ident, 'actif')");
        $stmtInsEleve2->execute([':lycee_id' => $lyceeId, ':ident' => 'MAT65_2_' . $uniq]);
        $eleveId2 = (int)$db->lastInsertId();

        $stmtInsEtude2 = $db->prepare("INSERT INTO etudes (eleve_id, classe_id, lycee_id, annee_academique_id, status, is_active) VALUES (:eleve_id, :classe_id, :lycee_id, :annee_id, 'actif', 1)");
        $stmtInsEtude2->execute([':eleve_id' => $eleveId2, ':classe_id' => $classeId, ':lycee_id' => $lyceeId, ':annee_id' => $anneeId]);

        // 5. Active Sanction Type
        DisciplineTypeSanction::save([
            'code' => 'EXC_TEMP_' . $uniq,
            'libelle' => 'Exclusion Temporaire',
            'autorite_min_requise' => 'conseil_discipline',
            'demande_duree_jours' => 1,
            'affiche_sur_bulletin' => 1,
            'actif' => 1,
        ]);
        $stRecord = DisciplineTypeSanction::findByCode('EXC_TEMP_' . $uniq, $lyceeId);
        $typeSanctionId = (int)$stRecord['id'];

        // 6. Active Incident Type & Incident
        DisciplineTypeIncident::save([
            'code' => 'INC65_' . $uniq,
            'libelle' => 'Insubordination Phase 6.5',
            'niveau_gravite' => 'tres_grave',
            'actif' => 1,
        ]);
        $incRecord = DisciplineTypeIncident::findByCode('INC65_' . $uniq, $lyceeId);
        $typeIncId = (int)$incRecord['id'];

        $incidentId = DisciplineIncident::create([
            'lycee_id' => $lyceeId,
            'annee_academique_id' => (int)$anneeId,
            'type_incident_id' => (int)$typeIncId,
            'date_incident' => date('Y-m-d'),
            'heure_incident' => '09:00:00',
            'lieu' => 'Cour',
            'description_faits' => 'Description test Phase 6.5',
            'statut' => 'signale',
            'signale_par_user_id' => $presidentUserId,
        ], [
            [
                'eleve_id' => $eleveId1,
                'role_eleve' => 'auteur_principal',
                'observation' => 'Observation 1'
            ],
            [
                'eleve_id' => $eleveId2,
                'role_eleve' => 'co_auteur',
                'observation' => 'Observation 2'
            ]
        ]);

        return [
            'lycee_id' => $lyceeId,
            'annee_id' => (int)$anneeId,
            'classe_id' => (int)$classeId,
            'president_user_id' => $presidentUserId,
            'eleve_id_1' => $eleveId1,
            'eleve_id_2' => $eleveId2,
            'type_sanction_id' => $typeSanctionId,
            'incident_id' => $incidentId,
        ];
    }

    public function test1_ClosureBlockedWithPendingDecision(): void {
        $ctx = $this->createDummyContext();
        $code = 'CD65_1_' . rand(1000, 9999);

        $councilId = DisciplineConseil::create([
            'lycee_id' => $ctx['lycee_id'],
            'annee_academique_id' => $ctx['annee_id'],
            'code' => $code,
            'titre' => 'Conseil Test Clôture Bloquée',
            'date_conseil' => date('Y-m-d'),
            'president_user_id' => $ctx['president_user_id'],
            'statut' => 'planifie',
        ]);

        DisciplineConseilEleve::addEleve([
            'conseil_id' => $councilId,
            'eleve_id' => $ctx['eleve_id_1'],
            'motif_convocation' => 'Motif élève 1',
        ]);

        DisciplineConseilEleve::addEleve([
            'conseil_id' => $councilId,
            'eleve_id' => $ctx['eleve_id_2'],
            'motif_convocation' => 'Motif élève 2',
        ]);

        // Transition: planifie -> convoque -> en_session -> delibere
        DisciplineConseil::updateStatus($councilId, 'convoque', $ctx['president_user_id']);
        DisciplineConseil::updateStatus($councilId, 'en_session', $ctx['president_user_id']);
        DisciplineConseil::updateStatus($councilId, 'delibere', $ctx['president_user_id']);

        // Process decision for student 1 only (student 2 remains 'en_attente')
        DisciplineConseilEleve::recordDecision($councilId, $ctx['eleve_id_1'], [
            'decision_statut' => 'relaxe',
            'votes_pour' => 5,
            'votes_contre' => 0,
            'motivation_decision' => 'Faits non établis pour élève 1',
        ]);

        // Attempt to close council with student 2 in pending decision state
        try {
            DisciplineConseil::updateStatus($councilId, 'cloture', $ctx['president_user_id']);
            throw new Exception("La clôture aurait dû être bloquée tant qu'au moins un élève a une décision 'en_attente'.");
        } catch (InvalidArgumentException $e) {
            $expectedMsg = "Impossible de clôturer le conseil : tous les élèves convoqués doivent avoir une décision enregistrée (aucune décision en attente).";
            if ($e->getMessage() !== $expectedMsg) {
                throw new Exception("Message d'erreur inattendu : " . $e->getMessage());
            }
        }

        $this->log("test1_ClosureBlockedWithPendingDecision passed.");
    }

    public function test2_ClosureAllowedWithAllDecisionsProcessed(): void {
        $ctx = $this->createDummyContext();
        $code = 'CD65_2_' . rand(1000, 9999);

        $councilId = DisciplineConseil::create([
            'lycee_id' => $ctx['lycee_id'],
            'annee_academique_id' => $ctx['annee_id'],
            'code' => $code,
            'titre' => 'Conseil Test Clôture Autorisée',
            'date_conseil' => date('Y-m-d'),
            'president_user_id' => $ctx['president_user_id'],
            'statut' => 'planifie',
        ]);

        DisciplineConseilEleve::addEleve([
            'conseil_id' => $councilId,
            'eleve_id' => $ctx['eleve_id_1'],
            'motif_convocation' => 'Motif élève 1',
        ]);

        DisciplineConseilEleve::addEleve([
            'conseil_id' => $councilId,
            'eleve_id' => $ctx['eleve_id_2'],
            'motif_convocation' => 'Motif élève 2',
        ]);

        DisciplineConseil::updateStatus($councilId, 'convoque', $ctx['president_user_id']);
        DisciplineConseil::updateStatus($councilId, 'en_session', $ctx['president_user_id']);
        DisciplineConseil::updateStatus($councilId, 'delibere', $ctx['president_user_id']);

        // Process decision for student 1: relaxe
        DisciplineConseilEleve::recordDecision($councilId, $ctx['eleve_id_1'], [
            'decision_statut' => 'relaxe',
            'votes_pour' => 5,
            'votes_contre' => 0,
            'motivation_decision' => 'Relaxe accordée',
        ]);

        // Process decision for student 2: sanctionne
        DisciplineConseilEleve::recordDecision($councilId, $ctx['eleve_id_2'], [
            'decision_statut' => 'sanctionne',
            'type_sanction_id' => $ctx['type_sanction_id'],
            'votes_pour' => 4,
            'votes_contre' => 1,
            'duree_jours' => 1,
            'motivation_decision' => 'Sanction prononcée',
        ]);

        // Transition: delibere -> cloture (MUST succeed)
        $res = DisciplineConseil::updateStatus($councilId, 'cloture', $ctx['president_user_id']);
        if (!$res) {
            throw new Exception("La clôture du conseil aurait dû réussir quand toutes les décisions sont traitées.");
        }

        $council = DisciplineConseil::findById($councilId, $ctx['lycee_id']);
        if ($council['statut'] !== 'cloture') {
            throw new Exception("Le statut du conseil aurait dû passer à 'cloture'.");
        }

        $this->log("test2_ClosureAllowedWithAllDecisionsProcessed passed.");
    }

    public function test3_ClosureFieldsRecordedCorrectly(): void {
        $ctx = $this->createDummyContext();
        $code = 'CD65_3_' . rand(1000, 9999);

        $councilId = DisciplineConseil::create([
            'lycee_id' => $ctx['lycee_id'],
            'annee_academique_id' => $ctx['annee_id'],
            'code' => $code,
            'titre' => 'Conseil Test Champs Clôture',
            'date_conseil' => date('Y-m-d'),
            'president_user_id' => $ctx['president_user_id'],
            'statut' => 'planifie',
        ]);

        DisciplineConseilEleve::addEleve([
            'conseil_id' => $councilId,
            'eleve_id' => $ctx['eleve_id_1'],
            'motif_convocation' => 'Motif unique',
        ]);

        DisciplineConseil::updateStatus($councilId, 'convoque', $ctx['president_user_id']);
        DisciplineConseil::updateStatus($councilId, 'en_session', $ctx['president_user_id']);
        DisciplineConseil::updateStatus($councilId, 'delibere', $ctx['president_user_id']);

        DisciplineConseilEleve::recordDecision($councilId, $ctx['eleve_id_1'], [
            'decision_statut' => 'averti',
            'votes_pour' => 5,
            'votes_contre' => 0,
            'motivation_decision' => 'Avertissement motivé',
        ]);

        DisciplineConseil::updateStatus($councilId, 'cloture', $ctx['president_user_id']);

        $council = DisciplineConseil::findById($councilId, $ctx['lycee_id']);
        if (empty($council['date_cloture'])) {
            throw new Exception("Le champ date_cloture n'a pas été renseigné lors de la clôture.");
        }
        if ((int)$council['cloture_par_user_id'] !== $ctx['president_user_id']) {
            throw new Exception("Le champ cloture_par_user_id doit être égal à l'ID de l'utilisateur ayant clôturé.");
        }

        $this->log("test3_ClosureFieldsRecordedCorrectly passed.");
    }

    public function test4_LockingPostClosure(): void {
        $ctx = $this->createDummyContext();
        $code = 'CD65_4_' . rand(1000, 9999);

        $councilId = DisciplineConseil::create([
            'lycee_id' => $ctx['lycee_id'],
            'annee_academique_id' => $ctx['annee_id'],
            'code' => $code,
            'titre' => 'Conseil Test Verrouillage Clôture',
            'date_conseil' => date('Y-m-d'),
            'president_user_id' => $ctx['president_user_id'],
            'statut' => 'delibere',
        ]);

        DisciplineConseilEleve::addEleve([
            'conseil_id' => $councilId,
            'eleve_id' => $ctx['eleve_id_1'],
            'motif_convocation' => 'Motif convocation',
        ]);

        DisciplineConseilEleve::recordDecision($councilId, $ctx['eleve_id_1'], [
            'decision_statut' => 'relaxe',
            'votes_pour' => 5,
            'votes_contre' => 0,
            'motivation_decision' => 'Relaxe accordée',
        ]);

        DisciplineConseil::updateStatus($councilId, 'cloture', $ctx['president_user_id']);

        // 1. Attempt status update on closed council
        try {
            DisciplineConseil::updateStatus($councilId, 'delibere', $ctx['president_user_id']);
            throw new Exception("Un conseil clôturé ne peut plus changer de statut.");
        } catch (InvalidArgumentException $e) {
            // Expected
        }

        // 2. Attempt decision update on closed council
        try {
            DisciplineConseilEleve::recordDecision($councilId, $ctx['eleve_id_1'], [
                'decision_statut' => 'averti',
                'votes_pour' => 5,
                'votes_contre' => 0,
                'motivation_decision' => 'Modification interdite',
            ]);
            throw new Exception("L'enregistrement de décision doit être bloqué sur un conseil clôturé.");
        } catch (InvalidArgumentException $e) {
            // Expected
        }

        $this->log("test4_LockingPostClosure passed.");
    }

    public function test5_6_StudentDisciplineTabDisplaysCouncilBlockAndData(): void {
        $ctx = $this->createDummyContext();
        $code = 'CD65_5_' . rand(1000, 9999);

        $councilId = DisciplineConseil::create([
            'lycee_id' => $ctx['lycee_id'],
            'annee_academique_id' => $ctx['annee_id'],
            'code' => $code,
            'titre' => 'Conseil Affichage Fiche Élève',
            'date_conseil' => date('Y-m-d'),
            'president_user_id' => $ctx['president_user_id'],
            'statut' => 'delibere',
        ]);

        DisciplineConseilEleve::addEleve([
            'conseil_id' => $councilId,
            'eleve_id' => $ctx['eleve_id_1'],
            'motif_convocation' => 'Comportement inacceptable en cours',
            'presence_eleve' => 1,
            'presence_representant_legal' => 1,
            'nom_representant_legal' => 'M. Koffi Père',
        ]);

        DisciplineConseilEleve::recordDecision($councilId, $ctx['eleve_id_1'], [
            'decision_statut' => 'sanctionne',
            'type_sanction_id' => $ctx['type_sanction_id'],
            'votes_pour' => 5,
            'votes_contre' => 0,
            'duree_jours' => 1,
            'motivation_decision' => 'Auteur de faits graves',
        ]);

        $_GET['id'] = $ctx['eleve_id_1'];

        ob_start();
        $controller = new EleveController();
        $controller->discipline();
        $output = ob_get_clean();

        // Check required fields rendered in view
        if (!str_contains($output, 'Conseils de Discipline')) {
            throw new Exception("Le bloc 'Conseils de Discipline' est absent du dossier élève.");
        }
        if (!str_contains($output, $code)) {
            throw new Exception("Le code du conseil '{$code}' n'est pas affiché dans la fiche élève.");
        }
        if (!str_contains($output, 'Conseil Affichage Fiche Élève')) {
            throw new Exception("Le titre du conseil n'est pas affiché dans la fiche élève.");
        }
        if (!str_contains($output, 'Comportement inacceptable en cours')) {
            throw new Exception("Le motif de convocation n'est pas affiché dans la fiche élève.");
        }
        if (!str_contains($output, 'Sanctionné(e)')) {
            throw new Exception("Le statut de décision n'est pas affiché correctement.");
        }
        if (!str_contains($output, 'Auteur de faits graves')) {
            throw new Exception("La motivation de la décision n'est pas affichée.");
        }

        $this->log("test5_6_StudentDisciplineTabDisplaysCouncilBlockAndData passed.");
    }

    public function test7_MultiTenantProtection(): void {
        $ctx1 = $this->createDummyContext(961);
        $ctx2 = $this->createDummyContext(962);

        $code = 'CD65_7_' . rand(1000, 9999);
        $councilId = DisciplineConseil::create([
            'lycee_id' => $ctx1['lycee_id'],
            'annee_academique_id' => $ctx1['annee_id'],
            'code' => $code,
            'titre' => 'Conseil Tenant 961',
            'date_conseil' => date('Y-m-d'),
            'president_user_id' => $ctx1['president_user_id'],
            'statut' => 'delibere',
        ]);

        DisciplineConseilEleve::addEleve([
            'conseil_id' => $councilId,
            'eleve_id' => $ctx1['eleve_id_1'],
            'motif_convocation' => 'Motif convocation multi-tenant',
        ]);

        // Set Auth context to Lycee 962
        Auth::setSessionContext([
            'id_user' => 9999,
            'lycee_id' => $ctx2['lycee_id'],
            'role_id' => 1,
            'permissions' => ['eleve' => ['view_all'], 'discipline' => ['view_councils', 'view_incidents', 'view_sanctions']],
        ]);

        $_GET['id'] = $ctx1['eleve_id_1'];

        ob_start();
        $controller = new EleveController();
        try {
            $controller->discipline();
        } catch (Throwable $e) {}
        $output = ob_get_clean();

        if (http_response_code() !== 403 && str_contains($output, $code)) {
            throw new Exception("Failles multi-tenant : Un utilisateur de l'établissement B a pu consulter la fiche disciplinaire d'un élève de l'établissement A !");
        }

        $this->log("test7_MultiTenantProtection passed.");
    }

    public function test8_IdorAndTeacherScopeProtection(): void {
        $db = Database::getInstance();
        $ctx = $this->createDummyContext(971);

        // Teacher user assigned ONLY to class $ctx['classe_id']
        $stmtInsTeacher = $db->prepare("INSERT INTO utilisateurs (lycee_id, nom, prenom, email, role_id, fonction, actif) VALUES (:lycee_id, 'Enseignant', 'Scope', :email, 6, 'Professeur', 1)");
        $stmtInsTeacher->execute([':lycee_id' => $ctx['lycee_id'], ':email' => 'teacher_scope_' . rand(1000, 9999) . '@test.ci']);
        $teacherUserId = (int)$db->lastInsertId();

        // Create student in another class (50000) not taught by teacher
        $stmtInsEleveOther = $db->prepare("INSERT INTO eleves (lycee_id, nom, prenom, identifiant_public, statut) VALUES (:lycee_id, 'OutScope', 'Student', :ident, 'actif')");
        $stmtInsEleveOther->execute([':lycee_id' => $ctx['lycee_id'], ':ident' => 'MAT_OUT_' . rand(1000, 9999)]);
        $outScopeEleveId = (int)$db->lastInsertId();

        $stmtInsEtudeOther = $db->prepare("INSERT INTO etudes (eleve_id, classe_id, lycee_id, annee_academique_id, status, is_active) VALUES (:eleve_id, 50000, :lycee_id, :annee_id, 'actif', 1)");
        $stmtInsEtudeOther->execute([':eleve_id' => $outScopeEleveId, ':lycee_id' => $ctx['lycee_id'], ':annee_id' => $ctx['annee_id']]);

        // Teacher assignment in $ctx['classe_id']
        $stmtInsAssign = $db->prepare("INSERT INTO affectations_pedagogiques (lycee_id, annee_academique_id, enseignant_id, classe_id, matiere_id, statut, date_debut) VALUES (:lycee_id, :annee_id, :ens_id, :cls_id, 1, 'actif', '2025-09-01')");
        $stmtInsAssign->execute([':lycee_id' => $ctx['lycee_id'], ':annee_id' => $ctx['annee_id'], ':ens_id' => $teacherUserId, ':cls_id' => $ctx['classe_id']]);

        Auth::setSessionContext([
            'id_user' => $teacherUserId,
            'lycee_id' => $ctx['lycee_id'],
            'role_name' => 'enseignant',
            'permissions' => ['discipline' => ['view_incidents', 'view_sanctions', 'view_councils']],
        ]);

        $_GET['id'] = $outScopeEleveId;

        ob_start();
        $controller = new EleveController();
        try {
            $controller->discipline();
        } catch (Throwable $e) {}
        $output = ob_get_clean();

        if (http_response_code() !== 403) {
            throw new Exception("Protection IDOR/Scope enseignant en échec : un enseignant a pu consulter un élève hors de sa classe d'affectation !");
        }

        $this->log("test8_IdorAndTeacherScopeProtection passed.");
    }

    public function test9_NonRegressionSanctionsPvAndHistory(): void {
        $ctx = $this->createDummyContext();
        $code = 'CD65_9_' . rand(1000, 9999);

        $conseilId = DisciplineConseil::create([
            'lycee_id' => $ctx['lycee_id'],
            'annee_academique_id' => $ctx['annee_id'],
            'code' => $code,
            'titre' => 'Conseil Non-Régression',
            'date_conseil' => date('Y-m-d'),
            'president_user_id' => $ctx['president_user_id'],
            'statut' => 'delibere',
        ]);

        DisciplineConseilEleve::addEleve([
            'conseil_id' => $councilId,
            'eleve_id' => $ctx['eleve_id_1'],
            'motif_convocation' => 'Test motif non-régression',
        ]);

        DisciplineConseilEleve::recordDecision($councilId, $ctx['eleve_id_1'], [
            'decision_statut' => 'sanctionne',
            'type_sanction_id' => $ctx['type_sanction_id'],
            'votes_pour' => 5,
            'votes_contre' => 0,
            'duree_jours' => 1,
            'motivation_decision' => 'Faits caractérisés',
        ]);

        // Verify audit trail entry written to discipline_historique
        $db = Database::getInstance();
        $stmtH = $db->prepare("SELECT COUNT(*) FROM discipline_historique WHERE eleve_id = :eleve_id AND action = 'DECISION_CONSEIL'");
        $stmtH->execute([':eleve_id' => $ctx['eleve_id_1']]);
        if ((int)$stmtH->fetchColumn() === 0) {
            throw new Exception("L'historique d'audit DECISION_CONSEIL n'a pas été enregistré dans discipline_historique.");
        }

        // Verify PV Generation
        $_POST['council_id'] = $councilId;
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $councilCtrl = new DisciplineConseilController();
        $councilCtrl->generatePv();

        $docs = DisciplineDocument::findByConseilId($councilId, $ctx['lycee_id']);
        if (count($docs) !== 1) {
            throw new Exception("Le PV n'a pas été correctement généré ou archivé.");
        }

        $this->log("test9_NonRegressionSanctionsPvAndHistory passed.");
    }

    public function test10_RegressionAllDisciplinePhases(): void {
        $this->log("Checking regression on Phase 1 referentials...");
        DisciplineTypeIncident::findByCode('INC65_REG', 999);
        DisciplineTypeSanction::findByCode('SANC65_REG', 999);

        $this->log("Checking regression on Phase 2 incidents...");
        $ctx = $this->createDummyContext(999);
        $inc = DisciplineIncident::findById($ctx['incident_id'], 999);
        if (!$inc) throw new Exception("Incident Phase 2 non trouvé.");

        $this->log("Checking regression on Phase 3 sanctions...");
        $sancList = DisciplineSanction::findAll(999);
        if (!is_array($sancList)) throw new Exception("Sanctions Phase 3 error.");

        $this->log("Checking regression on Phase 4 audit trail & documents...");
        $docs = DisciplineDocument::findByConseilId(99999, 999);
        if (!is_array($docs)) throw new Exception("Phase 4 document retrieval failed.");

        $this->log("test10_RegressionAllDisciplinePhases passed.");
    }
}

// Execution block
$test = new DisciplineConseilPhase65Test();
$test->runAllTests();
