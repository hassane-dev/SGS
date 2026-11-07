<?php

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/Cycle.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../models/Lycee.php';
require_once __DIR__ . '/../services/AuthorizationScopeService.php';
require_once __DIR__ . '/../services/PromotionThresholdService.php';
require_once __DIR__ . '/../services/PromotionAdmissionService.php';

/**
 * Contrôleur d'interface pour le calcul et la consultation des décisions d'admission.
 */
class PromotionAdmissionController {

    /**
     * Vérifie les droits d'accès à l'interface d'admission.
     */
    private function checkAccess(): void {
        $allowed = Auth::can('view', 'promotion_threshold')
            || Auth::can('validate', 'bulletin')
            || Auth::can('generate', 'bulletin')
            || Auth::can('view_all', 'note')
            || Auth::get('role_name') === 'super_admin_createur'
            || Auth::get('role_name') === 'super_admin_national'
            || Auth::get('role_name') === 'admin_local';

        if (!$allowed) {
            http_response_code(403);
            if (!defined('TEST_MODE')) {
                echo "Accès refusé. Vous n'avez pas les autorisations nécessaires pour consulter les décisions d'admission.";
                exit();
            } else {
                throw new LogicException("Accès refusé (permissions insuffisantes).");
            }
        }
    }

    /**
     * Résout l'établissement actif pour la session de l'utilisateur.
     */
    private function resolveLyceeId(?int $requestedLyceeId = null): int {
        $userLyceeId = Auth::getLyceeId();
        $isGlobalAdmin = Auth::can('view_all_lycees', 'lycee')
            || Auth::get('role_name') === 'super_admin_createur'
            || Auth::get('role_name') === 'super_admin_national';

        if ($requestedLyceeId !== null && $requestedLyceeId > 0) {
            if ($isGlobalAdmin || (int)$requestedLyceeId === (int)$userLyceeId) {
                return (int)$requestedLyceeId;
            }
            throw new LogicException("Accès refusé pour cet établissement (hors périmètre autorisé).");
        }

        if ($userLyceeId) {
            return (int)$userLyceeId;
        }

        throw new LogicException("Aucun établissement actif sélectionné.");
    }

    /**
     * Écran principal de gestion des admissions : filtres, seuils applicables et calcul.
     * Route: GET /admissions
     */
    public function index() {
        $this->checkAccess();

        $selectedLyceeId = !empty($_GET['lycee_id']) ? (int)$_GET['lycee_id'] : null;
        $lyceeId = $this->resolveLyceeId($selectedLyceeId);

        $isGlobalAdmin = Auth::can('view_all_lycees', 'lycee')
            || Auth::get('role_name') === 'super_admin_createur'
            || Auth::get('role_name') === 'super_admin_national';

        $lycees = $isGlobalAdmin ? Lycee::findAll() : [];

        // Années académiques
        $db = Database::getInstance();
        $annees = $db->query("SELECT * FROM annees_academiques ORDER BY date_debut DESC")->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $activeYear = AnneeAcademique::findActive();
        $selectedAnneeId = !empty($_GET['annee_id']) ? (int)$_GET['annee_id'] : ($activeYear['id'] ?? ($annees[0]['id'] ?? 0));

        // Cycles autorisés
        $cycles = AuthorizationScopeService::getPermittedCycles($lyceeId);

        // Classes autorisées
        $authClassIds = AuthorizationScopeService::getAuthorizedClassIds($lyceeId);
        $classes = Classe::findAll($lyceeId, $authClassIds);

        $selectedCycleId = !empty($_GET['cycle_id']) ? (int)$_GET['cycle_id'] : null;
        $selectedNiveau = !empty($_GET['niveau']) ? trim($_GET['niveau']) : null;
        $selectedClasseId = !empty($_GET['classe_id']) ? (int)$_GET['classe_id'] : null;

        $results = null;
        $applicableThreshold = null;
        $calculationError = null;

        if ($selectedClasseId && $selectedAnneeId && $lyceeId) {
            try {
                // Vérifier si la classe appartient bien à ce lycée
                $targetClasse = Classe::findById($selectedClasseId);
                if (!$targetClasse || (int)$targetClasse['lycee_id'] !== $lyceeId) {
                    throw new InvalidArgumentException("La classe sélectionnée n'appartient pas à l'établissement choisi.");
                }

                // Consulter le seuil applicable
                $cId = (int)$targetClasse['cycle_id'];
                $niv = (string)$targetClasse['niveau'];
                $ser = !empty($targetClasse['serie']) ? (string)$targetClasse['serie'] : null;
                $applicableThreshold = PromotionThresholdService::resolveThresholds($lyceeId, $cId, $niv, $ser);

                // Options de calcul facultatives (ex: note éliminatoire)
                $options = [
                    'check_scope' => true,
                    'autoriser_resultats_incomplets' => true
                ];
                if (isset($_GET['note_eliminatoire']) && $_GET['note_eliminatoire'] !== '') {
                    $options['note_eliminatoire_seuil'] = (float)$_GET['note_eliminatoire'];
                }

                // Exécution du calcul métier via PromotionAdmissionService
                $results = PromotionAdmissionService::calculateClassDecisions($lyceeId, $selectedAnneeId, $selectedClasseId, $options);

            } catch (Exception $e) {
                $calculationError = $e->getMessage();
            }
        }

        View::render('admissions/index', [
            'title' => _("Décisions d'Admission & Délibérations"),
            'lycees' => $lycees,
            'selectedLyceeId' => $lyceeId,
            'isGlobalAdmin' => $isGlobalAdmin,
            'annees' => $annees,
            'selectedAnneeId' => $selectedAnneeId,
            'cycles' => $cycles,
            'selectedCycleId' => $selectedCycleId,
            'selectedNiveau' => $selectedNiveau,
            'classes' => $classes,
            'selectedClasseId' => $selectedClasseId,
            'results' => $results,
            'applicableThreshold' => $applicableThreshold,
            'calculationError' => $calculationError
        ]);
    }

    /**
     * Endpoint de détail pour consulter la fiche individuelle complète d'un élève.
     * Route: GET /admissions/student-detail
     */
    public function studentDetail() {
        $this->checkAccess();

        $eleveId = (int)($_GET['eleve_id'] ?? 0);
        $classeId = (int)($_GET['classe_id'] ?? 0);
        $anneeId = (int)($_GET['annee_id'] ?? 0);
        $requestedLyceeId = !empty($_GET['lycee_id']) ? (int)$_GET['lycee_id'] : null;

        try {
            $lyceeId = $this->resolveLyceeId($requestedLyceeId);

            if (!$eleveId || !$classeId || !$anneeId) {
                throw new InvalidArgumentException("Paramètres incomplets (élève, classe et année requis).");
            }

            $options = [
                'check_scope' => true,
                'autoriser_resultats_incomplets' => true
            ];
            if (isset($_GET['note_eliminatoire']) && $_GET['note_eliminatoire'] !== '') {
                $options['note_eliminatoire_seuil'] = (float)$_GET['note_eliminatoire'];
            }

            $decision = PromotionAdmissionService::calculateStudentDecision($lyceeId, $anneeId, $classeId, $eleveId, $options);

            // Si requête AJAX / modal
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'decision' => $decision
                ]);
                if (!defined('TEST_MODE')) exit();
                return;
            }

            View::render('admissions/student_detail', [
                'title' => sprintf(_("Fiche Individuelle d'Admission — %s %s"), $decision['prenom'], $decision['nom']),
                'decision' => $decision
            ]);

        } catch (Exception $e) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
                if (!defined('TEST_MODE')) exit();
                return;
            }

            $_SESSION['error_message'] = $e->getMessage();
            header('Location: /admissions');
            if (!defined('TEST_MODE')) exit();
            return;
        }
    }

    /**
     * Endpoint AJAX retournant les classes autorisées d'un cycle / niveau pour cascade dynamique.
     * Route: GET /admissions/ajax-classes
     */
    public function ajaxClasses() {
        $this->checkAccess();
        header('Content-Type: application/json');

        try {
            $requestedLyceeId = !empty($_GET['lycee_id']) ? (int)$_GET['lycee_id'] : null;
            $lyceeId = $this->resolveLyceeId($requestedLyceeId);
            $cycleId = !empty($_GET['cycle_id']) ? (int)$_GET['cycle_id'] : null;
            $niveau = !empty($_GET['niveau']) ? trim($_GET['niveau']) : null;

            $authClassIds = AuthorizationScopeService::getAuthorizedClassIds($lyceeId);
            $classes = Classe::findAll($lyceeId, $authClassIds);

            $filtered = [];
            foreach ($classes as $c) {
                if ($cycleId && (int)$c['cycle_id'] !== $cycleId) {
                    continue;
                }
                if ($niveau && trim((string)$c['niveau']) !== $niveau) {
                    continue;
                }
                $filtered[] = [
                    'id_classe' => (int)$c['id_classe'],
                    'libelle' => Classe::getFormattedName($c),
                    'niveau' => $c['niveau'],
                    'serie' => $c['serie'] ?? '',
                    'cycle_id' => (int)$c['cycle_id']
                ];
            }

            echo json_encode(['success' => true, 'classes' => $filtered]);
            if (!defined('TEST_MODE')) exit();
            return;

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            if (!defined('TEST_MODE')) exit();
            return;
        }
    }
}
