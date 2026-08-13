<?php
// src/controllers/ReportingController.php

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';
require_once __DIR__ . '/../services/KpiService.php';
require_once __DIR__ . '/../services/ReportingService.php';
require_once __DIR__ . '/../services/ForecastService.php';
require_once __DIR__ . '/../models/Lycee.php';

class ReportingController {

    private function checkAccess($action, $resource = 'reporting') {
        if (!Auth::check()) {
            header('Location: /login');
            if (!defined('TEST_MODE')) exit();
            return;
        }

        if (!Auth::can($action, $resource)) {
            http_response_code(403);
            View::render('errors/403');
            if (!defined('TEST_MODE')) exit();
            return;
        }
    }

    private function resolveLyceeId() {
        $sessionLyceeId = Auth::getLyceeId();
        $selectedLyceeId = $sessionLyceeId;

        $reqLyceeId = null;
        if (isset($_GET['lycee_id'])) {
            $reqLyceeId = (int)$_GET['lycee_id'];
        } elseif (isset($_POST['lycee_id'])) {
            $reqLyceeId = (int)$_POST['lycee_id'];
        }

        if ($reqLyceeId !== null && $reqLyceeId !== $sessionLyceeId) {
            // Require view_all_lycees special permission to see another school's data
            if (!Auth::can('view_all_lycees', 'reporting')) {
                http_response_code(403);
                View::render('errors/403');
                if (!defined('TEST_MODE')) exit();
                return null;
            }
            $selectedLyceeId = $reqLyceeId;
        }

        return $selectedLyceeId;
    }

    /**
     * Dashboard view
     */
    public function dashboard() {
        $this->checkAccess('dashboard');
        $lyceeId = $this->resolveLyceeId();
        if ($lyceeId === null) return;

        // Retrieve filters
        $dateDebut = $_GET['date_debut'] ?? date('Y-m-01');
        $dateFin = $_GET['date_fin'] ?? date('Y-m-d');
        $filters = ['date_debut' => $dateDebut, 'date_fin' => $dateFin];

        // Retrieve core KPIs
        $kpis = [
            'liquidites_totales' => KpiService::computeKpi('liquidites_totales', $lyceeId, $filters),
            'entrees_treso' => KpiService::computeKpi('entrees_treso', $lyceeId, $filters),
            'sorties_treso' => KpiService::computeKpi('sorties_treso', $lyceeId, $filters),
            'resultat' => KpiService::computeKpi('resultat', $lyceeId, $filters),
            'budget_initial' => KpiService::computeKpi('budget_initial', $lyceeId, $filters),
            'consommation_budget' => KpiService::computeKpi('consommation_budget', $lyceeId, $filters),
            'disponible_budget' => KpiService::computeKpi('disponible_budget', $lyceeId, $filters),
            'dette_fournisseur' => KpiService::computeKpi('dette_fournisseur', $lyceeId, $filters),
            'recettes_scolaires' => KpiService::computeKpi('recettes_scolaires', $lyceeId, $filters),
            'taux_recouvrement' => KpiService::computeKpi('taux_recouvrement', $lyceeId, $filters),
        ];

        // Reconciliations
        $reconciliations = KpiService::validateReconciliations($lyceeId, $filters);

        // Threshold evaluation
        $statuses = [];
        foreach ($kpis as $code => $val) {
            $statuses[$code] = ReportingService::evaluateKpiValue($lyceeId, $code, $val);
        }

        // List of lycées (only for DG/SuperAdmin)
        $lycees = Auth::can('view_all_lycees', 'reporting') ? Lycee::findAll() : [];

        View::render('reporting/dashboard', [
            'title' => _("Tableau de bord exécutif"),
            'kpis' => $kpis,
            'statuses' => $statuses,
            'reconciliations' => $reconciliations,
            'lycees' => $lycees,
            'selectedLyceeId' => $lyceeId,
            'filters' => $filters
        ]);
    }

    /**
     * KPIs Catalog & Drill-down
     */
    public function kpis() {
        $this->checkAccess('kpis');
        $lyceeId = $this->resolveLyceeId();
        if ($lyceeId === null) return;

        $filters = [
            'date_debut' => $_GET['date_debut'] ?? date('Y-01-01'),
            'date_fin' => $_GET['date_fin'] ?? date('Y-m-d')
        ];

        $definitions = KpiService::getDefinitions();
        $kpis = [];
        $statuses = [];
        foreach ($definitions as $code => $def) {
            if ($code === 'liquidites_par_compte') {
                $kpis[$code] = KpiService::computeKpi($code, $lyceeId, $filters);
            } else {
                $val = KpiService::computeKpi($code, $lyceeId, $filters);
                $kpis[$code] = $val;
                $statuses[$code] = ReportingService::evaluateKpiValue($lyceeId, $code, $val);
            }
        }

        $thresholds = ReportingService::getThresholds($lyceeId);
        $lycees = Auth::can('view_all_lycees', 'reporting') ? Lycee::findAll() : [];

        View::render('reporting/kpis', [
            'title' => _("Catalogue des indicateurs de performance"),
            'definitions' => $definitions,
            'kpis' => $kpis,
            'statuses' => $statuses,
            'thresholds' => $thresholds,
            'lycees' => $lycees,
            'selectedLyceeId' => $lyceeId,
            'filters' => $filters
        ]);
    }

    /**
     * Temporal trend analysis
     */
    public function analyse() {
        $this->checkAccess('analyse');
        $lyceeId = $this->resolveLyceeId();
        if ($lyceeId === null) return;

        $history = ForecastService::getMonthlyHistory($lyceeId, 12);
        $lycees = Auth::can('view_all_lycees', 'reporting') ? Lycee::findAll() : [];

        View::render('reporting/analyse', [
            'title' => _("Analyses Temporelles & Évolution"),
            'history' => $history,
            'lycees' => $lycees,
            'selectedLyceeId' => $lyceeId
        ]);
    }

    /**
     * School comparisons
     */
    public function comparaison() {
        $this->checkAccess('comparaison');

        // Multi-school analysis always lists all lycees
        $lycees = Lycee::findAll();
        $comparisonData = [];

        foreach ($lycees as $l) {
            $lycId = $l['id'];
            $filters = ['date_debut' => date('Y-01-01'), 'date_fin' => date('Y-m-d')];

            // Aggregated metrics
            $totalStudents = 0;
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT COUNT(*) FROM etudes WHERE lycee_id = :l AND status = 'active'");
            $stmt->execute(['l' => $lycId]);
            $totalStudents = (int)$stmt->fetchColumn();

            $liq = KpiService::computeKpi('liquidites_totales', $lycId, $filters);
            $recettes = KpiService::computeKpi('recettes_scolaires', $lycId, $filters);
            $charges = KpiService::computeKpi('charges', $lycId, $filters);
            $budget = KpiService::computeKpi('budget_initial', $lycId, $filters);
            $consomme = KpiService::computeKpi('consommation_budget', $lycId, $filters);

            $comparisonData[] = [
                'id' => $lycId,
                'nom_lycee' => $l['nom_lycee'],
                'effectif' => $totalStudents,
                'liquidites' => $liq,
                'recettes' => $recettes,
                'charges' => $charges,
                'budget' => $budget,
                'consomme' => $consomme,
                'taux_execution' => $budget > 0 ? ($consomme / $budget) * 100 : 0.00,
                'charges_par_eleve' => $totalStudents > 0 ? ($charges / $totalStudents) : 0.00
            ];
        }

        View::render('reporting/comparaison', [
            'title' => _("Comparaison inter-établissements"),
            'comparisonData' => $comparisonData,
            'lycees' => $lycees
        ]);
    }

    /**
     * Financial forecasting
     */
    public function previsions() {
        $this->checkAccess('previsions');
        $lyceeId = $this->resolveLyceeId();
        if ($lyceeId === null) return;

        $method = $_GET['method'] ?? 'moving_average';
        $scenario = $_GET['scenario'] ?? 'central';
        $horizon = (int)($_GET['horizon'] ?? 3);

        $forecast = ForecastService::predict($lyceeId, $method, $horizon, $scenario);
        $lycees = Auth::can('view_all_lycees', 'reporting') ? Lycee::findAll() : [];

        View::render('reporting/previsions', [
            'title' => _("Prévisions & Analyse prédictive"),
            'forecast' => $forecast,
            'selectedMethod' => $method,
            'selectedScenario' => $scenario,
            'selectedHorizon' => $horizon,
            'lycees' => $lycees,
            'selectedLyceeId' => $lyceeId
        ]);
    }

    /**
     * Save KPI threshold configurations
     */
    public function save_threshold() {
        $this->checkAccess('threshold_manage');
        $lyceeId = $this->resolveLyceeId();
        if ($lyceeId === null) return;

        $kpi = $_POST['kpi_code'] ?? '';
        $min = (float)($_POST['seuil_min'] ?? 0);
        $warn = (float)($_POST['seuil_warning'] ?? 0);
        $obj = (float)($_POST['objectif'] ?? 0);
        $dang = (float)($_POST['seuil_danger'] ?? 0);
        $sens = $_POST['sens_variation'] ?? 'croissant';

        if (!empty($kpi)) {
            ReportingService::saveThreshold($lyceeId, $kpi, $min, $warn, $obj, $dang, $sens);
            ReportingService::logAudit(Auth::getUserId(), $lyceeId, 'modification_seuil_kpi', "Seuils modifiés pour le KPI: $kpi");
            $_SESSION['success_message'] = _("Seuils enregistrés avec succès.");
        }

        header('Location: /reporting/kpis?lycee_id=' . $lyceeId);
        if (!defined('TEST_MODE')) exit();
    }

    /**
     * Manually snapshot a KPI value
     */
    public static function generate_snapshot_manually() {
        if (!Auth::check() || !Auth::can('snapshot_manage', 'reporting')) {
            http_response_code(403);
            exit();
        }

        $lyceeId = Auth::getLyceeId();
        $kpi = $_POST['kpi_code'] ?? '';
        $valeur = (float)($_POST['valeur'] ?? 0);
        $freq = $_POST['frequence'] ?? 'mensuelle';
        $exerciceId = (int)($_POST['exercice_financier_id'] ?? 1);

        if (!empty($kpi)) {
            ReportingService::generateSnapshot($lyceeId, $exerciceId, null, date('Y-m-d'), $freq, $kpi, $valeur);
            ReportingService::logAudit(Auth::getUserId(), $lyceeId, 'generation_manuelle_snapshot', "Snapshot manuel généré pour le KPI: $kpi");
            $_SESSION['success_message'] = _("Snapshot enregistré avec succès.");
        }

        header('Location: /reporting/kpis?lycee_id=' . $lyceeId);
        if (!defined('TEST_MODE')) exit();
    }

    /**
     * Secure CSV Export
     */
    public function export() {
        $this->checkAccess('export');
        $lyceeId = $this->resolveLyceeId();
        if ($lyceeId === null) return;

        ReportingService::logAudit(Auth::getUserId(), $lyceeId, 'export', "Exportation CSV des indicateurs clés.");

        $filters = [
            'date_debut' => $_GET['date_debut'] ?? date('Y-01-01'),
            'date_fin' => $_GET['date_fin'] ?? date('Y-m-d')
        ];

        $definitions = KpiService::getDefinitions();

        // Standard clean headers for HTTP response
        if (!defined('TEST_MODE')) {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=SGS_Reporting_KPI_' . date('Ymd') . '.csv');
        }

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Code KPI', 'Libelle', 'Categorie', 'Valeur', 'Unite', 'Formule', 'Sens Interpretation']);

        foreach ($definitions as $code => $def) {
            if ($code === 'liquidites_par_compte') continue; // structured array

            $val = KpiService::computeKpi($code, $lyceeId, $filters);
            fputcsv($output, [
                $def['code'],
                $def['libelle'],
                $def['categorie'],
                $val,
                $def['unite'],
                $def['formule'],
                $def['sens_interpretation']
            ]);
        }

        fclose($output);
        if (!defined('TEST_MODE')) exit();
    }
}
?>
