<?php

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/PaieCahierTexteValidation.php';
require_once __DIR__ . '/../models/PaiePeriode.php';
require_once __DIR__ . '/../models/EnseignantMatiere.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/Cycle.php';
require_once __DIR__ . '/../models/User.php';

class PaieCahierTexteController {

    public function index() {
        Auth::requirePermission('paie', 'view');
        $lyceeId = Auth::getLyceeId();
        $db = Database::getInstance();

        // Search mode: 'pedagogique' (Mode A) or 'rh' (Mode B)
        $searchMode = $_GET['search_mode'] ?? 'pedagogique';

        // Filter inputs
        $periodeParam = $_GET['periode_id'] ?? null;
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
        $periodeId = null;
        $dateDebut = null;
        $dateFin = null;

        if ($periodeParam === 'all') {
            $periodeId = 'all';
            $dateDebut = null;
            $dateFin = null;
        } elseif ($periodeParam !== null && is_numeric($periodeParam)) {
            $pId = (int)$periodeParam;
            foreach ($periodes as $p) {
                if ((int)$p['id'] === $pId) {
                    $selectedPeriode = $p;
                    $periodeId = $pId;
                    break;
                }
            }
        }

        if ($selectedPeriode === null && $periodeParam !== 'all') {
            if (!empty($periodes)) {
                $selectedPeriode = $periodes[0];
                $periodeId = (int)$selectedPeriode['id'];
            }
        }

        if ($selectedPeriode) {
            $dateDebut = $selectedPeriode['date_debut'];
            $dateFin = $selectedPeriode['date_fin'];
        }

        // Fetch dynamic cycles using Cycle model as single source of truth
        $cycles = Cycle::findByLycee($lyceeId);
        $validCycleIds = array_map(function($c) { return (int)$c['id_cycle']; }, $cycles);
        if ($cycleId && !in_array($cycleId, $validCycleIds, true)) {
            $cycleId = null;
        }

        // 1. Resolve Classe hierarchy bidirectional consistency
        if ($classeId) {
            $selectedClasse = Classe::findById($classeId);
            if ($selectedClasse && (int)$selectedClasse['lycee_id'] === $lyceeId) {
                $cycleId = (int)$selectedClasse['cycle_id'];
                $niveau = $selectedClasse['niveau'];
                $serie = $selectedClasse['serie'];
                $numero = (string)$selectedClasse['numero'];
            } else {
                $classeId = null;
            }
        }

        // Fetch dynamic hierarchy levels, series, numbers based on cycle
        $niveaux = $cycleId ? Classe::findDistinctNiveauxByCycle($cycleId, $lyceeId) : Classe::getDistinctNiveaux($lyceeId);
        if ($niveau && !in_array($niveau, $niveaux, true)) {
            $niveau = null;
            $serie = null;
            $numero = null;
            $classeId = null;
        }

        $series = $niveau ? Classe::findDistinctSeriesByNiveau($niveau, $lyceeId, $cycleId) : [];
        if ($serie && !in_array($serie, $series, true)) {
            $serie = null;
            $numero = null;
            $classeId = null;
        }

        $numeros = $niveau ? Classe::findAvailableNumeros($niveau, $serie, $lyceeId, $cycleId) : [];
        if ($numero !== null && $numero !== '' && !in_array($numero, array_map('strval', $numeros), true)) {
            $numero = null;
            $classeId = null;
        }

        // If cycle, niveau, (serie), and numero are selected, resolve target classe_id
        if (!$classeId && $niveau && $numero !== null && $numero !== '') {
            $resolvedId = Classe::findIdByDetails($lyceeId, $niveau, $serie, $numero, $cycleId);
            if ($resolvedId) {
                $classeId = (int)$resolvedId;
            }
        }

        // Fetch classes list for select box
        $classes = Classe::findAll($lyceeId);

        // Fetch teachers based on mode and active assignments
        if ($searchMode === 'pedagogique') {
            $teachers = EnseignantMatiere::findTeachersByHierarchy($lyceeId, $cycleId, $niveau, $serie, $numero, $classeId, $matiereId);
        } else {
            $teachers = User::findTeachers($lyceeId);
            if ($teacherId) {
                $assignedClasses = EnseignantMatiere::findClassesForTeacher($teacherId, $lyceeId);
                if (!empty($assignedClasses)) {
                    $classes = $assignedClasses;
                }
            }
        }

        // Ensure selected teacher is valid in list
        if ($teacherId) {
            $validTeacherIds = array_map(function($t) { return (int)($t['id_user'] ?? $t['id']); }, $teachers);
            if (!in_array($teacherId, $validTeacherIds, true)) {
                $teacherId = null;
            }
        }

        // Fetch subjects strictly based on assignment context
        $matieres = [];
        if ($teacherId && $classeId) {
            $matieres = EnseignantMatiere::findSubjectsForTeacherInClass($teacherId, $classeId);
        } elseif ($classeId) {
            $matieres = EnseignantMatiere::findSubjectsForClass($classeId);
        } elseif ($teacherId) {
            $matieres = EnseignantMatiere::findSubjectsForTeacher($teacherId);
        } else {
            $stmtMat = $db->prepare("
                SELECT DISTINCT m.*
                FROM matieres m
                JOIN enseignant_matieres em ON m.id_matiere = em.matiere_id
                JOIN classes c ON em.classe_id = c.id_classe
                WHERE c.lycee_id = :lycee_id
                ORDER BY m.nom_matiere ASC
            ");
            $stmtMat->execute(['lycee_id' => $lyceeId]);
            $matieres = $stmtMat->fetchAll(PDO::FETCH_ASSOC);
        }

        if ($matiereId) {
            $validMatiereIds = array_map(function($m) { return (int)$m['id_matiere']; }, $matieres);
            if (!in_array($matiereId, $validMatiereIds, true)) {
                $matiereId = null;
            }
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

        // Metrics for active filters
        $metrics = PaieCahierTexteValidation::getTeacherHoursMetrics($sessionFilters);

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
