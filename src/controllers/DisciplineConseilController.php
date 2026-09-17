<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';
require_once __DIR__ . '/../core/Validator.php';
require_once __DIR__ . '/../models/DisciplineConseil.php';
require_once __DIR__ . '/../models/DisciplineConseilMembre.php';
require_once __DIR__ . '/../models/DisciplineConseilEleve.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Eleve.php';
require_once __DIR__ . '/../models/DisciplineIncident.php';
require_once __DIR__ . '/../models/DisciplineDocument.php';
require_once __DIR__ . '/../models/DisciplineNotification.php';
require_once __DIR__ . '/../models/DisciplineHistorique.php';
require_once __DIR__ . '/../models/DisciplineNotification.php';
require_once __DIR__ . '/../models/DisciplineDocument.php';
require_once __DIR__ . '/../services/AuthorizationScopeService.php';

class DisciplineConseilController {

    private function checkAccess(string $action) {
        if (!Auth::can($action, 'discipline')) {
            $this->forbidden();
        }
    }

    private function forbidden() {
        http_response_code(403);
        View::render('errors/403');
        if (!defined('TEST_MODE')) exit();
        return;
    }

    public function index() {
        $this->checkAccess('view_councils');

        $lyceeId = Auth::getLyceeId();
        $userId = Auth::get('id');
        $userRole = Auth::get('role_name');
        $hasGlobalView = Auth::can('manage_councils', 'discipline') || Auth::can('view_all', 'eleve');

        $activeYear = AnneeAcademique::findActive();

        $filters = $_GET;
        $isTeacher = ($userRole === 'enseignant') || !empty(User::getTeacherAssignments($userId));

        if ($isTeacher && !$hasGlobalView) {
            if (!$activeYear || empty($activeYear['id'])) {
                $this->forbidden();
            }
            if (isset($_GET['annee_academique_id']) && (int)$_GET['annee_academique_id'] !== (int)$activeYear['id']) {
                $this->forbidden();
            }
            $filters['annee_academique_id'] = $activeYear['id'];

            $assignments = User::getTeacherAssignments($userId);
            $assignedClassIds = array_keys($assignments);
            if (empty($assignedClassIds)) {
                $this->forbidden();
            }
            $filters['scoped_class_ids'] = $assignedClassIds;
        } else {
            if (empty($filters['annee_academique_id']) && $activeYear) {
                $filters['annee_academique_id'] = $activeYear['id'];
            }
        }

        $councils = DisciplineConseil::findAll($lyceeId, $filters);
        $academicYears = $hasGlobalView ? Database::getInstance()->query("SELECT id, libelle, est_active FROM annees_academiques ORDER BY date_debut DESC")->fetchAll(PDO::FETCH_ASSOC) : [];

        View::render('discipline/councils/index', [
            'councils' => $councils,
            'academicYears' => $academicYears,
            'activeYear' => $activeYear,
            'isTeacher' => $isTeacher && !$hasGlobalView,
            'canManage' => Auth::can('manage_councils', 'discipline'),
            'filters' => $_GET,
            'title' => 'Conseils de Discipline'
        ]);
    }

    public function create() {
        $this->checkAccess('manage_councils');

        $lyceeId = Auth::getLyceeId();
        $activeYear = AnneeAcademique::findActive();

        if (!$activeYear || empty($activeYear['id'])) {
            $_SESSION['error'] = "Impossible de planifier un conseil de discipline : aucune année académique active n'est définie.";
            header('Location: /discipline/councils');
            exit();
        }

        $db = Database::getInstance();
        $stmtUsers = $db->prepare("SELECT id_user, nom, prenom, fonction FROM utilisateurs WHERE lycee_id = :lycee_id AND actif = 1 ORDER BY prenom, nom");
        $stmtUsers->execute([':lycee_id' => $lyceeId]);
        $staffUsers = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

        $suggestedCode = 'CD-' . date('Y') . '-' . sprintf('%03d', rand(1, 999));

        View::render('discipline/councils/create', [
            'activeYear' => $activeYear,
            'staffUsers' => $staffUsers,
            'suggestedCode' => $suggestedCode,
            'title' => 'Planifier un Conseil de Discipline'
        ]);
    }

    public function store() {
        $this->checkAccess('manage_councils');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /discipline/councils');
            exit();
        }

        $lyceeId = Auth::getLyceeId();
        $activeYear = AnneeAcademique::findActive();

        if (!$activeYear || empty($activeYear['id'])) {
            $_SESSION['error'] = "Aucune année académique active définie.";
            header('Location: /discipline/councils');
            exit();
        }

        $code = trim($_POST['code'] ?? '');
        $titre = trim($_POST['titre'] ?? '');
        $dateConseil = trim($_POST['date_conseil'] ?? '');
        $presidentUserId = filter_input(INPUT_POST, 'president_user_id', FILTER_VALIDATE_INT);
        $secretaireUserId = filter_input(INPUT_POST, 'secretaire_user_id', FILTER_VALIDATE_INT) ?: null;

        if (empty($code) || empty($titre) || empty($dateConseil) || !$presidentUserId) {
            $_SESSION['error'] = "Veuillez remplir tous les champs obligatoires (Code, Titre, Date, Président).";
            header('Location: /discipline/councils/create');
            exit();
        }

        // Validate president and secretary belong to lycee_id
        $db = Database::getInstance();
        $stmtCheck = $db->prepare("SELECT COUNT(*) FROM utilisateurs WHERE id_user = :user_id AND lycee_id = :lycee_id");
        $stmtCheck->execute([':user_id' => $presidentUserId, ':lycee_id' => $lyceeId]);
        if ((int)$stmtCheck->fetchColumn() === 0) {
            $_SESSION['error'] = "Le président désigné n'appartient pas à cet établissement.";
            header('Location: /discipline/councils/create');
            exit();
        }

        try {
            $councilId = DisciplineConseil::create([
                'lycee_id' => $lyceeId,
                'annee_academique_id' => $activeYear['id'],
                'code' => $code,
                'titre' => $titre,
                'date_conseil' => $dateConseil,
                'heure_debut' => !empty($_POST['heure_debut']) ? $_POST['heure_debut'] : null,
                'heure_fin' => !empty($_POST['heure_fin']) ? $_POST['heure_fin'] : null,
                'lieu' => !empty($_POST['lieu']) ? trim($_POST['lieu']) : null,
                'president_user_id' => $presidentUserId,
                'secretaire_user_id' => $secretaireUserId,
                'statut' => 'planifie',
                'observations_generales' => !empty($_POST['observations_generales']) ? trim($_POST['observations_generales']) : null,
            ]);

            // Add president as permanent member
            DisciplineConseilMembre::addMembre([
                'conseil_id' => $councilId,
                'user_id' => $presidentUserId,
                'qualite_membre' => 'president',
                'a_droit_vote' => 1,
                'est_present' => 1
            ]);

            if ($secretaireUserId) {
                DisciplineConseilMembre::addMembre([
                    'conseil_id' => $councilId,
                    'user_id' => $secretaireUserId,
                    'qualite_membre' => 'secretaire',
                    'a_droit_vote' => 1,
                    'est_present' => 1
                ]);
            }

            DisciplineHistorique::log([
                'lycee_id' => $lyceeId,
                'annee_academique_id' => $activeYear['id'],
                'user_id' => Auth::get('id'),
                'action' => 'PLANIFIE_CONSEIL',
                'statut_avant' => null,
                'statut_apres' => 'planifie',
                'description' => "Planification du conseil de discipline '{$code}' pour le {$dateConseil}."
            ]);

            $_SESSION['success'] = "Conseil de discipline '{$code}' planifié avec succès.";
            header('Location: /discipline/councils/show?id=' . $councilId);
            exit();
        } catch (Exception $e) {
            $_SESSION['error'] = "Erreur : " . $e->getMessage();
            header('Location: /discipline/councils/create');
            exit();
        }
    }

    public function show() {
        $this->checkAccess('view_councils');

        $councilId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$councilId) {
            header('Location: /discipline/councils');
            exit();
        }

        $lyceeId = Auth::getLyceeId();
        $council = DisciplineConseil::findById($councilId, $lyceeId);

        if (!$council) {
            $this->forbidden();
        }

        // Scope verification for teachers
        $userId = Auth::get('id');
        $userRole = Auth::get('role_name');
        $isTeacher = ($userRole === 'enseignant') || !empty(User::getTeacherAssignments($userId));
        $hasGlobalView = Auth::can('manage_councils', 'discipline') || Auth::can('view_all', 'eleve');

        $membres = DisciplineConseilMembre::findByConseilId($councilId);
        $isMember = false;
        foreach ($membres as $m) {
            if ((int)$m['user_id'] === (int)$userId) {
                $isMember = true;
                break;
            }
        }

        if ($isTeacher && !$hasGlobalView && !$isMember) {
            $activeYear = AnneeAcademique::findActive();
            if (!$activeYear || (int)$council['annee_academique_id'] !== (int)$activeYear['id']) {
                $this->forbidden();
            }

            $assignments = User::getTeacherAssignments($userId);
            $assignedClassIds = array_keys($assignments);

            $eleves = DisciplineConseilEleve::findByConseilId($councilId);
            $hasSharedStudent = false;
            foreach ($eleves as $el) {
                if (in_array((int)$el['classe_id'], $assignedClassIds, true)) {
                    $hasSharedStudent = true;
                    break;
                }
            }

            if (!$hasSharedStudent) {
                $this->forbidden();
            }
        } else {
            $eleves = DisciplineConseilEleve::findByConseilId($councilId);
        }

        $db = Database::getInstance();
        $stmtStaff = $db->prepare("SELECT id_user, nom, prenom, fonction FROM utilisateurs WHERE lycee_id = :lycee_id AND actif = 1 ORDER BY prenom, nom");
        $stmtStaff->execute([':lycee_id' => $lyceeId]);
        $staffUsers = $stmtStaff->fetchAll(PDO::FETCH_ASSOC);

        // Eligible incidents for convoked students
        $convokedEleveIds = array_column($eleves, 'eleve_id');
        $availableIncidents = [];
        if (!empty($convokedEleveIds)) {
            $inClause = implode(',', array_map('intval', $convokedEleveIds));
            $stmtInc = $db->prepare("
                SELECT
                    i.id AS incident_id,
                    ti.code AS incident_code,
                    ti.libelle AS type_incident_libelle,
                    i.date_incident,
                    ie.eleve_id,
                    CONCAT(e.prenom, ' ', e.nom) AS eleve_nom
                FROM discipline_incident_eleves ie
                JOIN discipline_incidents i ON i.id = ie.incident_id
                LEFT JOIN discipline_types_incidents ti ON ti.id = i.type_incident_id
                LEFT JOIN eleves e ON e.id_eleve = ie.eleve_id
                WHERE i.lycee_id = :lycee_id AND ie.eleve_id IN ({$inClause})
                ORDER BY i.date_incident DESC
            ");
            $stmtInc->execute([':lycee_id' => $lyceeId]);
            $availableIncidents = $stmtInc->fetchAll(PDO::FETCH_ASSOC);
        }

        // Attached documents and notifications
        $stmtDoc = $db->prepare("
            SELECT d.*, CONCAT(u.prenom, ' ', u.nom) AS ajoute_par_nom
            FROM discipline_documents d
            LEFT JOIN utilisateurs u ON u.id_user = d.uploaded_by_user_id
            WHERE d.lycee_id = :lycee_id AND d.conseil_id = :conseil_id
            ORDER BY d.created_at DESC
        ");
        $stmtDoc->execute([':lycee_id' => $lyceeId, ':conseil_id' => $councilId]);
        $documents = $stmtDoc->fetchAll(PDO::FETCH_ASSOC);

        $stmtNotif = $db->prepare("
            SELECT n.*, CONCAT(u.prenom, ' ', u.nom) AS emetteur_nom
            FROM discipline_notifications n
            LEFT JOIN utilisateurs u ON u.id_user = n.created_by_user_id
            WHERE n.lycee_id = :lycee_id AND n.conseil_id = :conseil_id
            ORDER BY n.created_at DESC
        ");
        $stmtNotif->execute([':lycee_id' => $lyceeId, ':conseil_id' => $councilId]);
        $notifications = $stmtNotif->fetchAll(PDO::FETCH_ASSOC);

        // Fetch eligible students for convocation dropdown
        $teacherAllowedClassIds = ($isTeacher && !$hasGlobalView) ? array_keys(User::getTeacherAssignments($userId)) : [];
        $eligibleEleves = DisciplineConseilEleve::findEligibleElevesForCouncil($councilId, $teacherAllowedClassIds);

        View::render('discipline/councils/show', [
            'council' => $council,
            'membres' => $membres,
            'eleves' => $eleves,
            'eligibleEleves' => $eligibleEleves,
            'staffUsers' => $staffUsers,
            'availableIncidents' => $availableIncidents,
            'documents' => $documents,
            'notifications' => $notifications,
            'canManage' => Auth::can('manage_councils', 'discipline'),
            'title' => 'Fiche Conseil - ' . htmlspecialchars($council['code'])
        ]);
    }

    public function updateStatus() {
        $this->checkAccess('manage_councils');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /discipline/councils');
            exit();
        }

        $councilId = filter_input(INPUT_POST, 'council_id', FILTER_VALIDATE_INT) ?: (int)($_POST['council_id'] ?? 0);
        $newStatut = trim($_POST['statut'] ?? '');

        if (!$councilId || empty($newStatut)) {
            $_SESSION['error'] = "Paramètres de statut invalides.";
            header('Location: /discipline/councils');
            exit();
        }

        $council = DisciplineConseil::findById($councilId, Auth::getLyceeId());
        if (!$council) {
            $this->forbidden();
        }

        try {
            DisciplineConseil::updateStatus($councilId, $newStatut, Auth::get('id'));

            DisciplineHistorique::log([
                'lycee_id' => Auth::getLyceeId(),
                'annee_academique_id' => $council['annee_academique_id'],
                'user_id' => Auth::get('id'),
                'action' => 'STATUT_CONSEIL',
                'statut_avant' => $council['statut'],
                'statut_apres' => $newStatut,
                'description' => "Passage du conseil '{$council['code']}' au statut '{$newStatut}'."
            ]);

            $_SESSION['success'] = "Statut du conseil mis à jour vers '" . ucfirst($newStatut) . "'.";
            header('Location: /discipline/councils/show?id=' . $councilId);
            exit();
        } catch (Exception $e) {
            $_SESSION['error'] = "Erreur : " . $e->getMessage();
            header('Location: /discipline/councils/show?id=' . $councilId);
            exit();
        }
    }

    public function updateMemberPresence(): void {
        $this->checkAccess('manage_councils');
        $lyceeId = Auth::getLyceeId();
        $conseilId = (int)($_POST['conseil_id'] ?? 0);
        $userId = (int)($_POST['user_id'] ?? 0);
        $estPresent = isset($_POST['est_present']) ? (int)$_POST['est_present'] : 0;

        try {
            $conseil = DisciplineConseil::findById($conseilId, $lyceeId);
            if (!$conseil) {
                throw new InvalidArgumentException("Conseil introuvable.");
            }

            DisciplineConseilMembre::updatePresence($conseilId, $userId, $estPresent);

            DisciplineHistorique::log([
                'lycee_id' => $lyceeId,
                'user_id' => Auth::getUserId(),
                'action' => 'PRESENCE_MEMBRE_CONSEIL',
                'description' => "Mise à jour de la présence du membre ID {$userId} pour le conseil ID {$conseilId} : " . ($estPresent ? 'Présent' : 'Absent')
            ]);

            $_SESSION['success'] = "Présence du membre mise à jour avec succès.";
        } catch (Exception $e) {
            $_SESSION['error'] = "Erreur : " . $e->getMessage();
        }

        header('Location: /discipline/councils/show?id=' . $conseilId);
        exit();
    }

    public function updateEleveConvocation(): void {
        $this->checkAccess('manage_councils');
        $lyceeId = Auth::getLyceeId();
        $conseilId = (int)($_POST['conseil_id'] ?? 0);
        $eleveId = (int)($_POST['eleve_id'] ?? 0);

        try {
            $conseil = DisciplineConseil::findById($conseilId, $lyceeId);
            if (!$conseil) {
                throw new InvalidArgumentException("Conseil introuvable.");
            }

            DisciplineConseilEleve::updateConvocation($conseilId, $eleveId, [
                'motif_convocation' => $_POST['motif_convocation'] ?? null,
                'presence_eleve' => $_POST['presence_eleve'] ?? 'non_specifie',
                'presence_representant_legal' => $_POST['presence_representant_legal'] ?? 'non_specifie',
                'nom_representant_legal' => $_POST['nom_representant_legal'] ?? null,
            ]);

            DisciplineHistorique::log([
                'lycee_id' => $lyceeId,
                'user_id' => Auth::getUserId(),
                'action' => 'CONVOCATION_ELEVE_CONSEIL',
                'description' => "Mise à jour des informations de convocation pour l'élève ID {$eleveId} (Conseil ID {$conseilId})"
            ]);

            $_SESSION['success'] = "Convocation de l'élève mise à jour avec succès.";
        } catch (Exception $e) {
            $_SESSION['error'] = "Erreur : " . $e->getMessage();
        }

        header('Location: /discipline/councils/show?id=' . $conseilId);
        exit();
    }

    public function removeIncident(): void {
        $this->checkAccess('manage_councils');
        $lyceeId = Auth::getLyceeId();
        $conseilId = (int)($_POST['conseil_id'] ?? 0);
        $incidentId = (int)($_POST['incident_id'] ?? 0);
        $eleveId = (int)($_POST['eleve_id'] ?? 0);

        try {
            $conseil = DisciplineConseil::findById($conseilId, $lyceeId);
            if (!$conseil) {
                throw new InvalidArgumentException("Conseil introuvable.");
            }

            DisciplineConseilEleve::unlinkIncident($conseilId, $incidentId, $eleveId);

            DisciplineHistorique::log([
                'lycee_id' => $lyceeId,
                'user_id' => Auth::getUserId(),
                'action' => 'RETRAIT_INCIDENT_CONSEIL',
                'description' => "Retrait de l'incident ID {$incidentId} pour l'élève ID {$eleveId} du conseil ID {$conseilId}"
            ]);

            $_SESSION['success'] = "Incident retiré du conseil.";
        } catch (Exception $e) {
            $_SESSION['error'] = "Erreur : " . $e->getMessage();
        }

        header('Location: /discipline/councils/show?id=' . $conseilId);
        exit();
    }

    public function addMembre() {
        $this->checkAccess('manage_councils');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /discipline/councils');
            exit();
        }

        $councilId = filter_input(INPUT_POST, 'conseil_id', FILTER_VALIDATE_INT);
        $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        $qualiteMembre = trim($_POST['qualite_membre'] ?? 'membre_permanent');
        $aDroitVote = isset($_POST['a_droit_vote']) ? 1 : 0;

        if (!$councilId || !$userId) {
            $_SESSION['error'] = "Sélection de membre invalide.";
            header('Location: /discipline/councils');
            exit();
        }

        try {
            DisciplineConseilMembre::addMembre([
                'conseil_id' => $councilId,
                'user_id' => $userId,
                'qualite_membre' => $qualiteMembre,
                'a_droit_vote' => $aDroitVote
            ]);

            $_SESSION['success'] = "Membre ajouté au conseil de discipline.";
            header('Location: /discipline/councils/show?id=' . $councilId);
            exit();
        } catch (Exception $e) {
            $_SESSION['error'] = "Erreur : " . $e->getMessage();
            header('Location: /discipline/councils/show?id=' . $councilId);
            exit();
        }
    }

    public function removeMembre() {
        $this->checkAccess('manage_councils');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /discipline/councils');
            exit();
        }

        $councilId = filter_input(INPUT_POST, 'conseil_id', FILTER_VALIDATE_INT);
        $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

        if (!$councilId || !$userId) {
            $_SESSION['error'] = "Paramètres de membre invalides.";
            header('Location: /discipline/councils');
            exit();
        }

        try {
            DisciplineConseilMembre::removeMembre($councilId, $userId);
            $_SESSION['success'] = "Membre retiré du conseil de discipline.";
            header('Location: /discipline/councils/show?id=' . $councilId);
            exit();
        } catch (Exception $e) {
            $_SESSION['error'] = "Erreur : " . $e->getMessage();
            header('Location: /discipline/councils/show?id=' . $councilId);
            exit();
        }
    }

    public function addEleve() {
        $this->checkAccess('manage_councils');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /discipline/councils');
            exit();
        }

        $councilId = filter_input(INPUT_POST, 'conseil_id', FILTER_VALIDATE_INT);
        $eleveId = filter_input(INPUT_POST, 'eleve_id', FILTER_VALIDATE_INT);
        $motifConvocation = trim($_POST['motif_convocation'] ?? '');

        if (!$councilId || !$eleveId || empty($motifConvocation)) {
            $_SESSION['error'] = "Élève et motif de convocation obligatoires.";
            header('Location: /discipline/councils/show?id=' . $councilId);
            exit();
        }

        try {
            DisciplineConseilEleve::addEleve([
                'conseil_id' => $councilId,
                'eleve_id' => $eleveId,
                'motif_convocation' => $motifConvocation,
                'presence_eleve' => isset($_POST['presence_eleve']) ? 1 : 0,
                'presence_representant_legal' => isset($_POST['presence_representant_legal']) ? 1 : 0,
                'nom_representant_legal' => !empty($_POST['nom_representant_legal']) ? trim($_POST['nom_representant_legal']) : null
            ]);

            $_SESSION['success'] = "Élève convoqué ajouté au conseil de discipline.";
            header('Location: /discipline/councils/show?id=' . $councilId);
            exit();
        } catch (Exception $e) {
            $_SESSION['error'] = "Erreur : " . $e->getMessage();
            header('Location: /discipline/councils/show?id=' . $councilId);
            exit();
        }
    }

    public function addIncident() {
        $this->checkAccess('manage_councils');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /discipline/councils');
            exit();
        }

        $councilId = filter_input(INPUT_POST, 'conseil_id', FILTER_VALIDATE_INT);
        $incidentId = filter_input(INPUT_POST, 'incident_id', FILTER_VALIDATE_INT);
        $eleveId = filter_input(INPUT_POST, 'eleve_id', FILTER_VALIDATE_INT);

        if (!$councilId || !$incidentId || !$eleveId) {
            $_SESSION['error'] = "Sélection d'incident/élève invalide.";
            header('Location: /discipline/councils/show?id=' . $councilId);
            exit();
        }

        try {
            DisciplineConseilEleve::linkIncident($councilId, $incidentId, $eleveId);
            $_SESSION['success'] = "Incident rattaché au dossier du conseil pour cet élève.";
            header('Location: /discipline/councils/show?id=' . $councilId);
            exit();
        } catch (Exception $e) {
            $_SESSION['error'] = "Erreur : " . $e->getMessage();
            header('Location: /discipline/councils/show?id=' . $councilId);
            exit();
        }
    }

    public function recordDecision(): void {
        $this->checkAccess('manage_council_decisions');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /discipline/councils');
            exit();
        }

        $councilId = filter_input(INPUT_POST, 'council_id', FILTER_VALIDATE_INT) ?: (int)($_POST['council_id'] ?? 0);
        $eleveId = filter_input(INPUT_POST, 'eleve_id', FILTER_VALIDATE_INT) ?: (int)($_POST['eleve_id'] ?? 0);

        if (!$councilId || !$eleveId) {
            $_SESSION['error'] = "Paramètres de décision invalides.";
            header('Location: /discipline/councils');
            exit();
        }

        $lyceeId = Auth::getLyceeId();
        $council = DisciplineConseil::findById($councilId, $lyceeId);
        if (!$council) {
            $this->forbidden();
        }

        if (in_array($council['statut'], ['cloture', 'annule'], true)) {
            $_SESSION['error'] = "Impossible de modifier la décision pour un conseil clôturé ou annulé.";
            header('Location: /discipline/councils/show?id=' . $councilId);
            exit();
        }

        try {
            DisciplineConseilEleve::recordDecision($councilId, $eleveId, $_POST);
            $_SESSION['success'] = "Délibération et décision enregistrées avec succès.";
        } catch (Exception $e) {
            $_SESSION['error'] = "Erreur : " . $e->getMessage();
        }

        header('Location: /discipline/councils/show?id=' . $councilId);
        exit();
    }

    public function printPv(): void {
        $this->checkAccess('view_councils');

        $councilId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: (int)($_GET['id'] ?? 0);
        if (!$councilId) {
            header('Location: /discipline/councils');
            exit();
        }

        $lyceeId = Auth::getLyceeId();
        $council = DisciplineConseil::findById($councilId, $lyceeId);

        if (!$council) {
            $this->forbidden();
        }

        $membres = DisciplineConseilMembre::findByConseilId($councilId);
        $eleves = DisciplineConseilEleve::findByConseilId($councilId);

        // Fetch detailed sanction records for sanctioned students
        foreach ($eleves as &$el) {
            if (!empty($el['sanction_id'])) {
                $el['sanction_details'] = DisciplineSanction::findById($el['sanction_id'], $lyceeId);
            }
        }

        // Fetch establishment info for header
        $db = Database::getInstance();
        $stmtLycee = $db->prepare("SELECT * FROM param_lycee WHERE id = :id LIMIT 1");
        $stmtLycee->execute([':id' => $lyceeId]);
        $paramLycee = $stmtLycee->fetch(PDO::FETCH_ASSOC) ?: [];

        View::render('discipline/councils/print_pv', [
            'council' => $council,
            'membres' => $membres,
            'eleves' => $eleves,
            'paramLycee' => $paramLycee,
            'title' => 'Procès-Verbal Officiel - ' . htmlspecialchars($council['code'])
        ]);
    }

    public function generatePv(): void {
        $this->checkAccess('manage_councils');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if (!defined('TEST_MODE')) { header('Location: /discipline/councils'); exit(); }
            return;
        }

        $councilId = filter_input(INPUT_POST, 'council_id', FILTER_VALIDATE_INT) ?: (int)($_POST['council_id'] ?? 0);
        if (!$councilId) {
            $_SESSION['error'] = "Conseil spécifié invalide.";
            if (!defined('TEST_MODE')) { header('Location: /discipline/councils'); exit(); }
            return;
        }

        $lyceeId = Auth::getLyceeId();
        $council = DisciplineConseil::findById($councilId, $lyceeId);

        if (!$council) {
            $this->forbidden();
        }

        if (in_array($council['statut'], ['cloture', 'annule'], true)) {
            $_SESSION['error'] = "Impossible d'archiver un nouveau PV pour un conseil clôturé ou annulé.";
            if (!defined('TEST_MODE')) {
                header('Location: /discipline/councils/show?id=' . $councilId);
                exit();
            }
            return;
        }

        try {
            // IDEMPOTENCY: Check if an official PV document already exists for this council
            $existingDocs = DisciplineDocument::findByConseilId($councilId, $lyceeId);
            $pvDoc = null;
            foreach ($existingDocs as $doc) {
                if (($doc['mime_type'] ?? '') === 'application/pdf_pv' || str_contains($doc['nom_original'], 'PV_OFFICIEL_')) {
                    $pvDoc = $doc;
                    break;
                }
            }

            if ($pvDoc) {
                $_SESSION['success'] = "Le Procès-Verbal officiel de ce conseil est déjà archivé (" . htmlspecialchars($pvDoc['nom_original']) . ").";
                if (!defined('TEST_MODE')) {
                    header('Location: /discipline/councils/show?id=' . $councilId);
                    exit();
                }
                return;
            }

            $fileName = 'PV_OFFICIEL_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $council['code']) . '.pdf';
            $storageDir = __DIR__ . '/../../public/uploads/discipline_documents';
            if (!is_dir($storageDir)) {
                mkdir($storageDir, 0755, true);
            }

            $storageName = 'pv_' . $councilId . '_' . uniqid() . '.pdf';
            $fullPath = $storageDir . '/' . $storageName;

            // Generate HTML snapshot as document file
            $pvHtml = "PROCÈS-VERBAL OFFICIEL DE CONSEIL DE DISCIPLINE\n";
            $pvHtml .= "Code: " . $council['code'] . "\n";
            $pvHtml .= "Titre: " . $council['titre'] . "\n";
            $pvHtml .= "Date: " . $council['date_conseil'] . "\n";
            $pvHtml .= "Statut: " . $council['statut'] . "\n";
            file_put_contents($fullPath, $pvHtml);

            DisciplineDocument::create([
                'lycee_id' => $lyceeId,
                'conseil_id' => $councilId,
                'nom_original' => $fileName,
                'nom_stockage' => $storageName,
                'chemin_interne' => $fullPath,
                'mime_type' => 'application/pdf_pv',
                'taille' => filesize($fullPath),
            ]);

            DisciplineHistorique::log([
                'lycee_id' => $lyceeId,
                'user_id' => Auth::getUserId(),
                'annee_academique_id' => $council['annee_academique_id'],
                'action' => 'ARCHIVAGE_PV_CONSEIL',
                'description' => "Archivage officiel du Procès-Verbal pour le Conseil '{$council['code']}'."
            ]);

            $_SESSION['success'] = "Procès-Verbal officiel archivé avec succès dans les documents du conseil.";
        } catch (Exception $e) {
            $_SESSION['error'] = "Erreur lors de l'archivage du PV : " . $e->getMessage();
        }

        if (!defined('TEST_MODE')) {
            header('Location: /discipline/councils/show?id=' . $councilId);
            exit();
        }
        return;
    }
}
