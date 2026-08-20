<?php

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/PaieCahierTexteValidation.php';
require_once __DIR__ . '/../models/PaiePeriode.php';
require_once __DIR__ . '/../models/EnseignantMatiere.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/User.php';

class PaieCahierTexteController {

    public function index() {
        Auth::requirePermission('paie', 'view');
        $lyceeId = Auth::getLyceeId();
        $db = Database::getInstance();

        // Search mode: 'pedagogique' (Mode A) or 'rh' (Mode B)
        $searchMode = $_GET['search_mode'] ?? 'pedagogique';

        // Filter inputs
        $periodeId = !empty($_GET['periode_id']) ? (int)$_GET['periode_id'] : null;
        $cycleId = !empty($_GET['cycle_id']) ? (int)$_GET['cycle_id'] : null;
        $niveau = !empty($_GET['niveau']) ? trim($_GET['niveau']) : null;
        $serie = !empty($_GET['serie']) ? trim($_GET['serie']) : null;
        $numero = (isset($_GET['numero']) && $_GET['numero'] !== '') ? trim($_GET['numero']) : null;
        $classeId = !empty($_GET['classe_id']) ? (int)$_GET['classe_id'] : null;
        $teacherId = !empty($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : null;
        $matiereId = !empty($_GET['matiere_id']) ? (int)$_GET['matiere_id'] : null;
        $limit = isset($_GET['limit']) && $_GET['limit'] !== 'all' ? (int)$_GET['limit'] : null;

        // Fetch payroll periods
        $stmtPer = $db->prepare("SELECT * FROM paie_periodes WHERE lycee_id = :lycee_id ORDER BY annee DESC, mois DESC");
        $stmtPer->execute(['lycee_id' => $lyceeId]);
        $periodes = $stmtPer->fetchAll(PDO::FETCH_ASSOC);

        $selectedPeriode = null;
        if ($periodeId) {
            foreach ($periodes as $p) {
                if ((int)$p['id'] === $periodeId) {
                    $selectedPeriode = $p;
                    break;
                }
            }
        }
        if (!$selectedPeriode && !empty($periodes)) {
            $selectedPeriode = $periodes[0];
            $periodeId = (int)$selectedPeriode['id'];
        }

        $dateDebut = $selectedPeriode['date_debut'] ?? date('Y-m-01');
        $dateFin = $selectedPeriode['date_fin'] ?? date('Y-m-t');

        // Fetch dynamic cycles
        $stmtCyc = $db->prepare("SELECT * FROM cycles WHERE lycee_id = :lycee_id ORDER BY id_cycle ASC");
        $stmtCyc->execute(['lycee_id' => $lyceeId]);
        $cycles = $stmtCyc->fetchAll(PDO::FETCH_ASSOC);

        // Fetch dynamic hierarchy levels, series, numbers
        $niveaux = $cycleId ? Classe::findDistinctNiveauxByCycle($cycleId, $lyceeId) : Classe::getDistinctNiveaux($lyceeId);
        $series = $niveau ? Classe::findDistinctSeriesByNiveau($niveau, $lyceeId) : Classe::getDistinctSeries($lyceeId);
        $numeros = $niveau ? Classe::findAvailableNumeros($niveau, $serie, $lyceeId) : [];

        // Fetch classes
        $classes = Classe::findAll($lyceeId);

        // Fetch teachers based on mode and hierarchy
        if ($searchMode === 'pedagogique') {
            $teachers = EnseignantMatiere::findTeachersByHierarchy($lyceeId, $cycleId, $niveau, $serie, $numero, $classeId);
        } else {
            $teachers = User::findTeachers($lyceeId);
        }

        // Fetch subjects
        $matieres = [];
        if ($teacherId && $classeId) {
            $matieres = EnseignantMatiere::findSubjectsForTeacherInClass($teacherId, $classeId);
        } else {
            $stmtMat = $db->prepare("SELECT * FROM matieres ORDER BY nom_matiere ASC");
            $stmtMat->execute();
            $matieres = $stmtMat->fetchAll(PDO::FETCH_ASSOC);
        }

        // Query Sessions
        $sessionFilters = [
            'lycee_id' => $lyceeId,
            'teacher_id' => $teacherId,
            'cycle_id' => $cycleId,
            'niveau' => $niveau,
            'serie' => $serie,
            'numero' => $numero,
            'classe_id' => $classeId,
            'matiere_id' => $matiereId,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'limit' => $limit
        ];
        $sessions = PaieCahierTexteValidation::findSessionsForContext($sessionFilters);

        // Metrics for selected teacher
        $metrics = null;
        if ($teacherId) {
            $metrics = PaieCahierTexteValidation::getTeacherHoursMetrics($teacherId, $dateDebut, $dateFin);
        }

        include __DIR__ . '/../views/paie/cahier_texte/index.php';
    }

    public function validate() {
        Auth::requirePermission('paie', 'validate');
        $cahierId = (int)($_POST['cahier_id'] ?? 0);
        $tauxHoraire = !empty($_POST['taux_horaire']) ? (float)$_POST['taux_horaire'] : null;
        $userId = Auth::getUserId();

        try {
            if ($cahierId <= 0) {
                throw new InvalidArgumentException(_("Veuillez sélectionner une séance valide."));
            }
            PaieCahierTexteValidation::validateSession($cahierId, $userId, $tauxHoraire);
            $_SESSION['success_message'] = _("Séance de cours validée avec succès pour la paie.");
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
        }

        $redirect = $_SERVER['HTTP_REFERER'] ?? '/paie/cahier-texte';
        header('Location: ' . $redirect);
        exit();
    }

    public function bulkValidate() {
        Auth::requirePermission('paie', 'validate');
        $cahierIds = $_POST['cahier_ids'] ?? [];
        $tauxHoraire = !empty($_POST['taux_horaire']) ? (float)$_POST['taux_horaire'] : null;
        $userId = Auth::getUserId();

        try {
            if (empty($cahierIds) || !is_array($cahierIds)) {
                throw new InvalidArgumentException(_("Aucune séance sélectionnée pour la validation."));
            }
            $count = PaieCahierTexteValidation::bulkValidateSessions($cahierIds, $userId, $tauxHoraire);
            $_SESSION['success_message'] = sprintf(_("%d séance(s) validée(s) avec succès pour la paie."), $count);
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
        }

        $redirect = $_SERVER['HTTP_REFERER'] ?? '/paie/cahier-texte';
        header('Location: ' . $redirect);
        exit();
    }
}
