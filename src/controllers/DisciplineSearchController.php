<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';
require_once __DIR__ . '/../services/DisciplineSearchService.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/Cycle.php';
require_once __DIR__ . '/../models/DisciplineTypeIncident.php';
require_once __DIR__ . '/../models/DisciplineTypeSanction.php';
require_once __DIR__ . '/../models/ParamLycee.php';

class DisciplineSearchController {

    /**
     * Neutralize CSV formula injection risk for values starting with =, +, -, @
     */
    private static function sanitizeCsvValue($val) {
        if ($val === null) return '';
        $str = (string)$val;
        if (preg_match('/^[=+\-@]/', $str)) {
            return "'" . $str;
        }
        return $str;
    }

    public function index() {
        $canViewIncidents = Auth::can('view_incidents', 'discipline') || Auth::can('manage_incident', 'discipline') || Auth::can('view_all', 'eleve');
        $canViewSanctions = Auth::can('view_sanctions', 'discipline') || Auth::can('manage_sanctions', 'discipline') || Auth::can('view_all', 'eleve');

        if (!$canViewIncidents && !$canViewSanctions) {
            $this->forbidden();
        }

        $lyceeId = Auth::getLyceeId();
        $userId = Auth::get('id');
        $userRole = Auth::get('role_name');
        $hasGlobalView = Auth::can('view_all', 'eleve') || Auth::can('manage_incident', 'discipline') || Auth::can('manage_sanctions', 'discipline');

        $tab = ($_GET['tab'] ?? 'incidents') === 'sanctions' ? 'sanctions' : 'incidents';
        $page = max(1, (int)($_GET['page'] ?? 1));

        try {
            if ($tab === 'incidents') {
                $dataset = DisciplineSearchService::getFilteredIncidents($_GET, $userId, $userRole, $lyceeId, $hasGlobalView, $page, 25);
            } else {
                $dataset = DisciplineSearchService::getFilteredSanctions($_GET, $userId, $userRole, $lyceeId, $hasGlobalView, $page, 25);
            }
            $analytics = DisciplineSearchService::getSearchSummaryAnalytics($_GET, $userId, $userRole, $lyceeId, $hasGlobalView);
        } catch (InvalidArgumentException $e) {
            if ($e->getCode() === 403) {
                $this->forbidden();
            }
            throw $e;
        }

        // Referentials for filter dropdowns
        $db = Database::getInstance();
        $activeYear = AnneeAcademique::findActive();
        $isTeacher = ($userRole === 'enseignant') || !empty(User::getTeacherAssignments($userId));

        $academicYears = $hasGlobalView ? $db->query("SELECT id, libelle, est_active FROM annees_academiques ORDER BY date_debut DESC")->fetchAll(PDO::FETCH_ASSOC) : [];
        $cycles = Cycle::findAll();

        if ($isTeacher && !$hasGlobalView) {
            $assignments = User::getTeacherAssignments($userId);
            $assignedClassIds = array_keys($assignments);
            $availableClasses = [];
            if (!empty($assignedClassIds)) {
                $inClauseAssigned = implode(',', array_map('intval', $assignedClassIds));
                $availableClasses = $db->query("
                    SELECT id_classe, TRIM(CONCAT(COALESCE(niveau, ''), ' ', COALESCE(serie, ''), ' ', COALESCE(numero, ''))) AS nom_classe
                    FROM classes
                    WHERE id_classe IN ({$inClauseAssigned}) AND lycee_id = {$lyceeId}
                    ORDER BY niveau, serie, numero
                ")->fetchAll(PDO::FETCH_ASSOC);
            }
        } else {
            $stmtC = $db->prepare("
                SELECT id_classe, TRIM(CONCAT(COALESCE(niveau, ''), ' ', COALESCE(serie, ''), ' ', COALESCE(numero, ''))) AS nom_classe
                FROM classes
                WHERE lycee_id = :lycee_id
                ORDER BY niveau, serie, numero
            ");
            $stmtC->execute([':lycee_id' => $lyceeId]);
            $availableClasses = $stmtC->fetchAll(PDO::FETCH_ASSOC);
        }

        $typeIncidents = DisciplineTypeIncident::findActive($lyceeId);
        $typeSanctions = DisciplineTypeSanction::findActive($lyceeId);

        View::render('discipline/search/index', [
            'tab' => $tab,
            'dataset' => $dataset,
            'analytics' => $analytics,
            'academicYears' => $academicYears,
            'cycles' => $cycles,
            'availableClasses' => $availableClasses,
            'typeIncidents' => $typeIncidents,
            'typeSanctions' => $typeSanctions,
            'activeYear' => $activeYear,
            'isTeacher' => $isTeacher && !$hasGlobalView,
            'queryParams' => $_GET,
            'title' => 'Recherche & Registres Disciplinaires'
        ]);
    }

    /**
     * CSV Export respecting active filters and SSoT scope
     */
    public function exportCsv() {
        $canViewIncidents = Auth::can('view_incidents', 'discipline') || Auth::can('manage_incident', 'discipline') || Auth::can('view_all', 'eleve');
        $canViewSanctions = Auth::can('view_sanctions', 'discipline') || Auth::can('manage_sanctions', 'discipline') || Auth::can('view_all', 'eleve');

        if (!$canViewIncidents && !$canViewSanctions) {
            $this->forbidden();
        }

        $lyceeId = Auth::getLyceeId();
        $userId = Auth::get('id');
        $userRole = Auth::get('role_name');
        $hasGlobalView = Auth::can('view_all', 'eleve') || Auth::can('manage_incident', 'discipline') || Auth::can('manage_sanctions', 'discipline');

        $tab = ($_GET['tab'] ?? 'incidents') === 'sanctions' ? 'sanctions' : 'incidents';

        try {
            if ($tab === 'incidents') {
                $dataset = DisciplineSearchService::getFilteredIncidents($_GET, $userId, $userRole, $lyceeId, $hasGlobalView, 1, 0); // 0 perPage = All
            } else {
                $dataset = DisciplineSearchService::getFilteredSanctions($_GET, $userId, $userRole, $lyceeId, $hasGlobalView, 1, 0);
            }
        } catch (InvalidArgumentException $e) {
            if ($e->getCode() === 403) {
                $this->forbidden();
            }
            throw $e;
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="registre_' . $tab . '_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');
        fputs($output, "\xEF\xBB\xBF"); // UTF-8 BOM

        if ($tab === 'incidents') {
            $headers = ['Code Incident', 'Date', 'Heure', 'Lieu', 'Type Incident', 'Gravité', 'Matricule Élève', 'Nom Élève', 'Rôle Élève', 'Classe', 'Statut', 'Signalé Par', 'Description / Observations'];
            fputcsv($output, $headers, ';');

            foreach ($dataset['items'] as $item) {
                fputcsv($output, [
                    self::sanitizeCsvValue($item['incident_code'] ?? 'N/A'),
                    self::sanitizeCsvValue($item['date_incident'] ?? ''),
                    self::sanitizeCsvValue($item['heure_incident'] ?? ''),
                    self::sanitizeCsvValue($item['lieu'] ?? ''),
                    self::sanitizeCsvValue($item['type_incident_libelle'] ?? ''),
                    self::sanitizeCsvValue($item['niveau_gravite'] ?? ''),
                    self::sanitizeCsvValue($item['eleve_matricule'] ?? ''),
                    self::sanitizeCsvValue($item['eleve_nom_complet'] ?? ''),
                    self::sanitizeCsvValue($item['role_implication'] ?? ''),
                    self::sanitizeCsvValue($item['nom_classe_snapshot'] ?? ''),
                    self::sanitizeCsvValue($item['incident_statut'] ?? ''),
                    self::sanitizeCsvValue($item['signale_par_nom'] ?? ''),
                    self::sanitizeCsvValue($item['description_faits'] ?? '')
                ], ';');
            }
        } else {
            $headers = ['Matricule Élève', 'Nom Élève', 'Classe', 'Type Sanction', 'Réf. Incident', 'Date Décision', 'Période Exécution', 'Durée (Jours/Heures)', 'Statut', 'Motif Décision', 'Motif Levée / Annulation', 'Décidé Par'];
            fputcsv($output, $headers, ';');

            foreach ($dataset['items'] as $item) {
                $periodeExec = (!empty($item['date_debut_execution'])) ? $item['date_debut_execution'] . ' au ' . $item['date_fin_execution'] : 'Non planifiée';
                $dureeStr = ($item['duree_jours'] > 0 ? $item['duree_jours'] . ' j ' : '') . ($item['duree_heures'] > 0 ? $item['duree_heures'] . ' h' : '');

                fputcsv($output, [
                    self::sanitizeCsvValue($item['eleve_matricule'] ?? ''),
                    self::sanitizeCsvValue($item['eleve_nom_complet'] ?? ''),
                    self::sanitizeCsvValue($item['nom_classe_snapshot'] ?? ''),
                    self::sanitizeCsvValue($item['type_sanction_libelle'] ?? ''),
                    self::sanitizeCsvValue($item['incident_code'] ?? 'N/A'),
                    self::sanitizeCsvValue($item['date_decision'] ?? ''),
                    self::sanitizeCsvValue($periodeExec),
                    self::sanitizeCsvValue($dureeStr),
                    self::sanitizeCsvValue($item['sanction_statut'] ?? ''),
                    self::sanitizeCsvValue($item['motif_decision'] ?? ''),
                    self::sanitizeCsvValue($item['motif_levee_annulation'] ?? ''),
                    self::sanitizeCsvValue($item['prononcee_par_nom'] ?? '')
                ], ';');
            }
        }

        fclose($output);
        exit();
    }

    /**
     * PDF Printable Template Export respecting active filters and SSoT scope
     */
    public function exportPdf() {
        $canViewIncidents = Auth::can('view_incidents', 'discipline') || Auth::can('manage_incident', 'discipline') || Auth::can('view_all', 'eleve');
        $canViewSanctions = Auth::can('view_sanctions', 'discipline') || Auth::can('manage_sanctions', 'discipline') || Auth::can('view_all', 'eleve');

        if (!$canViewIncidents && !$canViewSanctions) {
            $this->forbidden();
        }

        $lyceeId = Auth::getLyceeId();
        $userId = Auth::get('id');
        $userRole = Auth::get('role_name');
        $hasGlobalView = Auth::can('view_all', 'eleve') || Auth::can('manage_incident', 'discipline') || Auth::can('manage_sanctions', 'discipline');

        $tab = ($_GET['tab'] ?? 'incidents') === 'sanctions' ? 'sanctions' : 'incidents';

        try {
            if ($tab === 'incidents') {
                $dataset = DisciplineSearchService::getFilteredIncidents($_GET, $userId, $userRole, $lyceeId, $hasGlobalView, 1, 0);
            } else {
                $dataset = DisciplineSearchService::getFilteredSanctions($_GET, $userId, $userRole, $lyceeId, $hasGlobalView, 1, 0);
            }
        } catch (InvalidArgumentException $e) {
            if ($e->getCode() === 403) {
                $this->forbidden();
            }
            throw $e;
        }

        $param_lycee = ParamLycee::findByLyceeId($lyceeId);

        View::render('discipline/search/print_template', [
            'tab' => $tab,
            'dataset' => $dataset,
            'param_lycee' => $param_lycee,
            'queryParams' => $_GET,
            'title' => 'Registre Officiel Disciplinaire - ' . strtoupper($tab)
        ]);
    }

    private function forbidden() {
        http_response_code(403);
        View::render('errors/403');
        exit();
    }
}
