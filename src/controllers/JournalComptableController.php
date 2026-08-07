<?php

require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/Cycle.php';
require_once __DIR__ . '/../models/Lycee.php';
require_once __DIR__ . '/../models/JournalComptable.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';

class JournalComptableController {

    private function checkAccess($action = 'view', $resource = 'journal') {
        if (!Auth::can($action, $resource)) {
            if (PHP_SAPI === 'cli') {
                throw new Exception("FORBIDDEN");
            }
            http_response_code(403);
            View::render('errors/403');
            exit();
        }
    }

    public function index() {
        $this->checkAccess('view');

        $db = Database::getInstance();

        // 1. Mono vs Multi-établissement
        $lycees = Lycee::findAll();
        if (count($lycees) > 1) {
            $lycee_id = $_GET['lycee_id'] ?? Auth::getLyceeId();
        } else {
            $lycee_id = !empty($lycees) ? $lycees[0]['id'] : Auth::getLyceeId();
        }

        // 2. Année Académique (Filtre Obligatoire)
        $activeYear = AnneeAcademique::findActive();
        $annee_academique_id = $_GET['annee_academique_id'] ?? ($activeYear ? $activeYear['id'] : null);

        // Fallback to first available if none is active or selected
        if (!$annee_academique_id) {
            $all_years = AnneeAcademique::findAll();
            if (!empty($all_years)) {
                $annee_academique_id = $all_years[0]['id'];
            }
        }

        // 3. View Type
        $view_type = $_GET['view_type'] ?? 'detailed'; // detailed, receipt, class, student

        $filters = [
            'annee_academique_id' => $annee_academique_id,
            'lycee_id' => $lycee_id,
            'cycle_id' => $_GET['cycle_id'] ?? '',
            'niveau' => $_GET['niveau'] ?? '',
            'serie' => $_GET['serie'] ?? '',
            'numero' => $_GET['numero'] ?? '',
            'date_debut' => $_GET['date_debut'] ?? '',
            'date_fin' => $_GET['date_fin'] ?? '',
            'operation' => $_GET['operation'] ?? '',
            'search' => $_GET['search'] ?? '',
            'view_type' => $view_type
        ];

        // Fetch master lists for dropdowns
        $annees = AnneeAcademique::findAll();
        $cycles = Cycle::findByLycee($lycee_id);
        $niveaux = Classe::getDistinctNiveaux($lycee_id);
        $series = Classe::getDistinctSeries($lycee_id);

        $stmt_num = $db->prepare("SELECT DISTINCT numero FROM classes WHERE lycee_id = :lycee_id AND numero IS NOT NULL ORDER BY numero ASC");
        $stmt_num->execute(['lycee_id' => $lycee_id]);
        $numeros = $stmt_num->fetchAll(PDO::FETCH_COLUMN);

        // Fetch detailed entries
        $entries = JournalComptable::findAll($lycee_id, $filters);

        // Vue 2 : Journal par reçu
        $receipt_entries = [];
        if ($view_type === 'receipt') {
            foreach ($entries as $entry) {
                $recu = $entry['recu_numero'];
                if (empty($recu)) {
                    continue;
                }
                if (!isset($receipt_entries[$recu])) {
                    $receipt_entries[$recu] = [
                        'recu_numero' => $recu,
                        'date' => $entry['date_creation'],
                        'eleve_nom' => $entry['eleve_nom'] ?? 'N/A',
                        'eleve_prenom' => $entry['eleve_prenom'] ?? '',
                        'mode_paiement' => $entry['mode_paiement'],
                        'user_nom' => $entry['user_nom'] ?? 'System',
                        'operations' => [],
                        'total' => 0.00
                    ];
                }
                $receipt_entries[$recu]['operations'][] = [
                    'label' => $entry['operation'],
                    'amount' => $entry['montant']
                ];
                $receipt_entries[$recu]['total'] += (float)$entry['montant'];
            }
        }

        // Vue 3 : Synthèse par classe
        $class_entries = [];
        if ($view_type === 'class') {
            foreach ($entries as $entry) {
                $cl_name = ($entry['niveau'] ?? '') . ' ' . ($entry['serie'] ?? '') . ' ' . ($entry['numero'] ?? '');
                $cl_name = trim($cl_name);
                if (empty($cl_name)) {
                    $cl_name = _('Sans classe');
                }
                if (!isset($class_entries[$cl_name])) {
                    $class_entries[$cl_name] = [
                        'class_name' => $cl_name,
                        'total_inscription' => 0.00,
                        'total_mensualites' => 0.00,
                        'total' => 0.00
                    ];
                }
                if (strpos(strtolower($entry['operation']), 'inscription') !== false || strpos(strtolower($entry['operation']), 'frais') !== false) {
                    $class_entries[$cl_name]['total_inscription'] += (float)$entry['montant'];
                } else {
                    $class_entries[$cl_name]['total_mensualites'] += (float)$entry['montant'];
                }
                $class_entries[$cl_name]['total'] += (float)$entry['montant'];
            }
        }

        // Vue 4 : Synthèse par élève
        $student_entries = [];
        if ($view_type === 'student') {
            require_once __DIR__ . '/../models/FinancialStatusService.php';
            foreach ($entries as $entry) {
                $eleve_id = $entry['eleve_id'];
                if (!$eleve_id) continue;
                if (!isset($student_entries[$eleve_id])) {
                    $cl_name = ($entry['niveau'] ?? '') . ' ' . ($entry['serie'] ?? '') . ' ' . ($entry['numero'] ?? '');
                    // Calculate expected and remaining totals via FinancialStatusService
                    $status = FinancialStatusService::getStudentStatus($eleve_id, $annee_academique_id);
                    $student_entries[$eleve_id] = [
                        'id_eleve' => $eleve_id,
                        'nom' => $entry['eleve_nom'] ?? 'N/A',
                        'prenom' => $entry['eleve_prenom'] ?? '',
                        'class_name' => trim($cl_name),
                        'total_du' => $status['total_du'] ?? 0.00,
                        'total_paye' => $status['total_paye'] ?? 0.00,
                        'reste_du' => ($status['total_du'] ?? 0.00) - ($status['total_paye'] ?? 0.00)
                    ];
                }
            }
        }

        View::render('comptabilite/journal/index', [
            'title' => _("Journal Comptable Unique"),
            'entries' => $entries,
            'receipt_entries' => $receipt_entries,
            'class_entries' => $class_entries,
            'student_entries' => $student_entries,
            'filters' => $filters,
            'annees' => $annees,
            'cycles' => $cycles,
            'niveaux' => $niveaux,
            'series' => $series,
            'numeros' => $numeros,
            'selected_year_id' => $annee_academique_id,
            'selected_lycee_id' => $lycee_id,
            'lycees' => $lycees
        ]);
    }

    public function grandLivre() {
        $this->checkAccess('view', 'grand_livre');
        $lycee_id = Auth::getLyceeId();
        $db = Database::getInstance();

        require_once __DIR__ . '/../services/GrandLivreService.php';

        // Fetch list of all accounts for the selector
        $stmt_c = $db->query("SELECT id, numero, libelle FROM comptes_comptables WHERE actif = 1 ORDER BY numero ASC");
        $comptes = $stmt_c->fetchAll(PDO::FETCH_ASSOC);

        $selected_compte_id = $_GET['compte_id'] ?? (!empty($comptes) ? $comptes[0]['id'] : null);
        $date_debut = $_GET['date_debut'] ?? null;
        $date_fin = $_GET['date_fin'] ?? null;

        $account_details = null;
        if ($selected_compte_id) {
            $account_details = GrandLivreService::getAccountDetails($selected_compte_id, $date_debut, $date_fin);
        }

        View::render('comptabilite/journal/grand_livre', [
            'title' => _("Grand Livre Général"),
            'comptes' => $comptes,
            'selected_compte_id' => $selected_compte_id,
            'account_details' => $account_details,
            'filters' => [
                'date_debut' => $date_debut,
                'date_fin' => $date_fin
            ]
        ]);
    }

    public function balance() {
        $this->checkAccess('view', 'balance');
        $lycee_id = Auth::getLyceeId();

        require_once __DIR__ . '/../services/BalanceService.php';

        $date_debut = $_GET['date_debut'] ?? null;
        $date_fin = $_GET['date_fin'] ?? null;

        $balance_data = BalanceService::getBalanceData($lycee_id, $date_debut, $date_fin);

        View::render('comptabilite/journal/balance', [
            'title' => _("Balance Générale des Comptes"),
            'balance_data' => $balance_data,
            'filters' => [
                'date_debut' => $date_debut,
                'date_fin' => $date_fin
            ]
        ]);
    }
}
?>