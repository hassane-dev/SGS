<?php

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/PaieCahierTexteValidation.php';
require_once __DIR__ . '/../models/PaiePeriode.php';
require_once __DIR__ . '/../models/AffectationPedagogique.php';
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
        $limit = isset($_GET['limit']) ? ($_GET['limit'] === 'all' ? null : (int)$_GET['limit']) : null;

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
                $today = date('Y-m-d');
                foreach ($periodes as $p) {
                    if (!empty($p['date_debut']) && !empty($p['date_fin'])) {
                        if ($today >= $p['date_debut'] && $today <= $p['date_fin']) {
                            $selectedPeriode = $p;
                            $periodeId = (int)$p['id'];
                            break;
                        }
                    }
                }
                if ($selectedPeriode === null) {
                    $stmtHasSessions = $db->prepare("
                        SELECT p.id
                        FROM paie_periodes p
                        JOIN cahier_texte ct ON ct.date_cours BETWEEN p.date_debut AND p.date_fin
                        WHERE p.lycee_id = :lycee_id
                        ORDER BY ct.date_cours DESC LIMIT 1
                    ");
                    $stmtHasSessions->execute(['lycee_id' => $lyceeId]);
                    $sessionPId = $stmtHasSessions->fetchColumn();
                    if ($sessionPId) {
                        foreach ($periodes as $p) {
                            if ((int)$p['id'] === (int)$sessionPId) {
                                $selectedPeriode = $p;
                                $periodeId = (int)$p['id'];
                                break;
                            }
                        }
                    }
                }
                if ($selectedPeriode === null) {
                    $selectedPeriode = $periodes[0];
                    $periodeId = (int)$selectedPeriode['id'];
                }
            }
        }

        if ($selectedPeriode) {
            $dateDebut = $selectedPeriode['date_debut'];
            $dateFin = $selectedPeriode['date_fin'];
        }

        error_log(sprintf(
            "[PaieCahierTexte] Resolved Period ID: %s, Date Debut: %s, Date Fin: %s",
            var_export($periodeId, true),
            var_export($dateDebut, true),
            var_export($dateFin, true)
        ));

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
        if ($numero !== null && $numero !== '') {
            $validNumeroStrings = [];
            foreach ($numeros as $numVal) {
                $validNumeroStrings[] = (string)$numVal;
                $validNumeroStrings[] = (string)(int)$numVal;
                $validNumeroStrings[] = sprintf('%02d', (int)$numVal);
            }
            if (!in_array((string)$numero, $validNumeroStrings, true)) {
                $numero = null;
                $classeId = null;
            }
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
            $teachers = AffectationPedagogique::findTeachersByHierarchy($lyceeId, $cycleId, $niveau, $serie, $numero, $classeId, $matiereId);
            $sqlCtTeachers = "
                SELECT DISTINCT u.id_user, u.nom, u.prenom, u.identifiant_public
                FROM cahier_texte ct
                JOIN utilisateurs u ON ct.personnel_id = u.id_user
                LEFT JOIN classes cl ON ct.classe_id = cl.id_classe
                WHERE (ct.lycee_id = :lycee_id OR cl.lycee_id = :lycee_id2)
            ";
            $ctParams = ['lycee_id' => $lyceeId, 'lycee_id2' => $lyceeId];
            if ($classeId) {
                $sqlCtTeachers .= " AND ct.classe_id = :classe_id";
                $ctParams['classe_id'] = $classeId;
            }
            $stmtCtT = $db->prepare($sqlCtTeachers);
            $stmtCtT->execute($ctParams);
            $ctTeachers = $stmtCtT->fetchAll(PDO::FETCH_ASSOC);

            $existingIds = array_map(function($t) { return (int)($t['id_user'] ?? $t['id']); }, $teachers);
            foreach ($ctTeachers as $ctT) {
                if (!in_array((int)$ctT['id_user'], $existingIds, true)) {
                    $teachers[] = $ctT;
                    $existingIds[] = (int)$ctT['id_user'];
                }
            }
        } else {
            $teachers = User::findTeachers($lyceeId);
            if ($teacherId) {
                $assignedClasses = AffectationPedagogique::findClassesForTeacher($teacherId, $lyceeId);
                if (!empty($assignedClasses)) {
                    $classes = $assignedClasses;
                }
            }
        }

        // Ensure selected teacher is valid in list
        if ($teacherId) {
            $validTeacherIds = array_map(function($t) { return (int)($t['id_user'] ?? $t['id']); }, $teachers);
            if (!in_array($teacherId, $validTeacherIds, true)) {
                $stmtCheckT = $db->prepare("SELECT id_user, nom, prenom, identifiant_public FROM utilisateurs WHERE id_user = :id AND lycee_id = :lycee_id");
                $stmtCheckT->execute(['id' => $teacherId, 'lycee_id' => $lyceeId]);
                $tRow = $stmtCheckT->fetch(PDO::FETCH_ASSOC);
                if ($tRow) {
                    $teachers[] = $tRow;
                } else {
                    $teacherId = null;
                }
            }
        }

        // Fetch subjects strictly based on assignment context
        $matieres = [];
        if ($teacherId && $classeId) {
            $matieres = AffectationPedagogique::findSubjectsForTeacherInClass($teacherId, $classeId);
        } elseif ($classeId) {
            $matieres = AffectationPedagogique::findSubjectsForClass($classeId);
        } elseif ($teacherId) {
            $matieres = AffectationPedagogique::findSubjectsForTeacher($teacherId);
        } else {
            $stmtMat = $db->prepare("
                SELECT DISTINCT m.*
                FROM matieres m
                JOIN affectations_pedagogiques ap ON m.id_matiere = ap.matiere_id AND ap.statut = 'actif'
                JOIN classes c ON em.classe_id = c.id_classe
                WHERE c.lycee_id = :lycee_id
                ORDER BY m.nom_matiere ASC
            ");
            $stmtMat->execute(['lycee_id' => $lyceeId]);
            $matieres = $stmtMat->fetchAll(PDO::FETCH_ASSOC);
        }

        if (empty($matieres)) {
            $sqlCtMat = "
                SELECT DISTINCT m.id_matiere, m.nom_matiere
                FROM cahier_texte ct
                JOIN matieres m ON ct.matiere_id = m.id_matiere
                WHERE (ct.lycee_id = :lycee_id)
            ";
            $ctMatParams = ['lycee_id' => $lyceeId];
            if ($teacherId) {
                $sqlCtMat .= " AND ct.personnel_id = :teacher_id";
                $ctMatParams['teacher_id'] = $teacherId;
            }
            if ($classeId) {
                $sqlCtMat .= " AND ct.classe_id = :classe_id";
                $ctMatParams['classe_id'] = $classeId;
            }
            $stmtCtM = $db->prepare($sqlCtMat);
            $stmtCtM->execute($ctMatParams);
            $matieres = $stmtCtM->fetchAll(PDO::FETCH_ASSOC);
        }

        if ($matiereId) {
            $validMatiereIds = array_map(function($m) { return (int)$m['id_matiere']; }, $matieres);
            if (!in_array($matiereId, $validMatiereIds, true)) {
                $stmtCheckM = $db->prepare("SELECT * FROM matieres WHERE id_matiere = :id");
                $stmtCheckM->execute(['id' => $matiereId]);
                $mRow = $stmtCheckM->fetch(PDO::FETCH_ASSOC);
                if ($mRow) {
                    $matieres[] = $mRow;
                } else {
                    $matiereId = null;
                }
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
