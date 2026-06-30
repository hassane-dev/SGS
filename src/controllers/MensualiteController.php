<?php

require_once __DIR__ . '/../models/Eleve.php';
require_once __DIR__ . '/../models/Mensualite.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../models/Cycle.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/Etude.php';
require_once __DIR__ . '/../models/Frais.php';
require_once __DIR__ . '/../models/Sequence.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';

class MensualiteController {

    private function checkAccess($action = 'manage') {
        if (!Auth::can($action, 'paiement')) {
            http_response_code(403);
            View::render('errors/403');
            exit();
        }
    }

    public function index() {
        $this->checkAccess('view');
        $lycee_id = Auth::getLyceeId();
        $cycles = Cycle::findAll();

        $filters = [
            'lycee_id' => $lycee_id,
            'cycle_id' => $_GET['cycle_id'] ?? null,
            'niveau'   => $_GET['niveau'] ?? null,
            'serie'    => $_GET['serie'] ?? null,
            'numero'   => $_GET['numero'] ?? null,
        ];

        View::render('mensualites/index', [
            'cycles' => $cycles,
            'filters' => $filters,
            'title' => 'Gestion des Mensualités'
        ]);
    }

    public function classDashboard($classeId) {
        $this->checkAccess('view');
        $lycee_id = Auth::getLyceeId();
        $activeYear = AnneeAcademique::findActive();

        if (!$activeYear) {
            $_SESSION['error_message'] = "Aucune année académique active.";
            header('Location: /mensualites');
            exit();
        }

        $classe = Classe::findById($classeId);
        if (!$classe || $classe['lycee_id'] != $lycee_id) {
            $_SESSION['error_message'] = "Classe introuvable.";
            header('Location: /mensualites');
            exit();
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT e.*, et.id_etude, et.status as etude_status
            FROM eleves e
            JOIN etudes et ON e.id_eleve = et.eleve_id
            WHERE et.classe_id = :classe_id
            AND et.annee_academique_id = :annee_id
            AND (e.statut = 'actif' OR e.statut = 'en_attente_paiement')
            ORDER BY e.nom, e.prenom
        ");
        $stmt->execute(['classe_id' => $classeId, 'annee_id' => $activeYear['id']]);
        $eleves = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $frais = Frais::findForClasse($classe, $activeYear['id']);
        $sequences = Sequence::findAll();

        $fmt = new IntlDateFormatter('fr_FR', IntlDateFormatter::FULL, IntlDateFormatter::NONE, 'Africa/Porto-Novo', IntlDateFormatter::GREGORIAN, 'MMMM');
        $allMonths = [];
        foreach ($sequences as $sequence) {
            $current = new DateTime($sequence['date_debut']);
            $end = new DateTime($sequence['date_fin']);
            while ($current <= $end) {
                $monthName = ucfirst($fmt->format($current));
                if (!in_array($monthName, $allMonths)) {
                    $allMonths[] = $monthName;
                }
                $current->modify('first day of next month');
            }
        }

        // Fetch all payments for these students
        foreach ($eleves as &$eleve) {
            $eleve['payments'] = Mensualite::findByEtude($eleve['id_etude']);
        }

        View::render('mensualites/class_dashboard', [
            'classe' => $classe,
            'eleves' => $eleves,
            'allMonths' => $allMonths,
            'frais' => $frais,
            'title' => 'Tableau de bord - ' . Classe::getFormattedName($classe)
        ]);
    }

    public function pay($eleveId) {
        $this->checkAccess('manage');
        $anneeActive = AnneeAcademique::findActive();
        $eleve = Eleve::findById($eleveId);
        $etude = Etude::findByEleveAndAnnee($eleveId, $anneeActive['id']);
        if (!$etude) {
             $_SESSION['error_message'] = "Dossier académique introuvable.";
             header('Location: /mensualites');
             exit();
        }
        $classe = Classe::findById($etude['classe_id']);
        $frais = Frais::findForClasse($classe, $anneeActive['id']);

        $sequences = Sequence::findAll();
        $mensualitesPayees = Mensualite::findByEtude($etude['id_etude']);
        $tranches = [];
        $fmt = new IntlDateFormatter('fr_FR', IntlDateFormatter::FULL, IntlDateFormatter::NONE, 'Africa/Porto-Novo', IntlDateFormatter::GREGORIAN, 'MMMM');

        foreach ($sequences as $i => $sequence) {
            $mois = [];
            $current = new DateTime($sequence['date_debut']);
            $end = new DateTime($sequence['date_fin']);
            while ($current <= $end) {
                $mois[] = ucfirst($fmt->format($current));
                $current->modify('first day of next month');
            }

            $paye = [];
            foreach($mois as $m) {
                $m_cap = ucfirst($m);
                if (isset($mensualitesPayees[$m_cap])) {
                    $paye[$m_cap] = ['verse' => $mensualitesPayees[$m_cap]['total']];
                }
            }

            $tranches["Tranche " . ($i + 1)] = [
                'mois' => $mois,
                'montant_par_mois' => $frais['frais_mensuel'] ?? 0,
                'paye' => $paye
            ];
        }

        $nextRecu = Mensualite::generateReceiptNumber($eleve['lycee_id']);

        View::render('mensualites/pay', [
            'eleve' => $eleve,
            'tranches' => $tranches,
            'nextRecu' => $nextRecu
        ]);
    }
}
