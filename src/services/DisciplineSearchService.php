<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Classe.php';

class DisciplineSearchService {

    /**
     * Resolve and validate user scope parameters for Discipline search
     * Throws InvalidArgumentException on unauthorized access or IDOR
     */
    public static function validateAndResolveScope($filters, $userId, $userRole, $lyceeId, $hasGlobalView) {
        $isTeacher = ($userRole === 'enseignant') || !empty(User::getTeacherAssignments($userId));
        $activeYear = AnneeAcademique::findActive();

        if ($isTeacher && !$hasGlobalView) {
            // Strict teacher scope
            if (!$activeYear || empty($activeYear['id'])) {
                throw new InvalidArgumentException("ACCES_REFUSE_AUCUNE_ANNEE_ACTIVE", 403);
            }

            // Reject historical year request in GET for teachers
            if (isset($filters['annee_academique_id']) && (int)$filters['annee_academique_id'] !== (int)$activeYear['id']) {
                throw new InvalidArgumentException("ACCES_REFUSE_ANNEE_HISTORIQUE_ENSEIGNANT", 403);
            }

            $anneeId = (int)$activeYear['id'];
            $assignments = User::getTeacherAssignments($userId);
            $assignedClassIds = array_keys($assignments);

            if (empty($assignedClassIds)) {
                throw new InvalidArgumentException("ACCES_REFUSE_AUCUNE_AFFECTATION", 403);
            }

            if (!empty($filters['classe_id'])) {
                $requestedClassId = (int)$filters['classe_id'];
                if (!in_array($requestedClassId, $assignedClassIds, true)) {
                    throw new InvalidArgumentException("ACCES_REFUSE_CLASSE_HORS_SCOPE", 403);
                }
                $scopedClassIds = [$requestedClassId];
            } else {
                $scopedClassIds = $assignedClassIds;
            }
        } else {
            // Global user scope
            if (!empty($filters['annee_academique_id'])) {
                $anneeId = (int)$filters['annee_academique_id'];
            } else {
                $anneeId = $activeYear ? (int)$activeYear['id'] : null;
            }

            if (!empty($filters['classe_id'])) {
                $requestedClassId = (int)$filters['classe_id'];
                $targetClass = Classe::findById($requestedClassId);
                if (!$targetClass || (int)$targetClass['lycee_id'] !== (int)$lyceeId) {
                    throw new InvalidArgumentException("ACCES_REFUSE_TENANT_INCORRECT", 403);
                }
                $scopedClassIds = [$requestedClassId];
            } else {
                $scopedClassIds = null;
            }
        }

        return [
            'annee_id' => $anneeId,
            'scoped_class_ids' => $scopedClassIds,
            'is_teacher' => $isTeacher && !$hasGlobalView,
            'active_year' => $activeYear
        ];
    }

    /**
     * Build WHERE clauses and PDO parameters for Incident Search
     */
    public static function buildIncidentQueryContext($filters, $scopeInfo, $lyceeId) {
        $where = ["i.lycee_id = :lycee_id"];
        $params = [':lycee_id' => $lyceeId];

        if ($scopeInfo['annee_id'] !== null) {
            $where[] = "i.annee_academique_id = :annee_id";
            $params[':annee_id'] = $scopeInfo['annee_id'];
        }

        if ($scopeInfo['scoped_class_ids'] !== null) {
            if (empty($scopeInfo['scoped_class_ids'])) {
                $where[] = "1 = 0";
            } else {
                $inClause = implode(',', array_map('intval', $scopeInfo['scoped_class_ids']));
                $where[] = "ie.classe_id IN ({$inClause})";
            }
        }

        if (!empty($filters['cycle_id'])) {
            $where[] = "c.cycle_id = :cycle_id";
            $params[':cycle_id'] = (int)$filters['cycle_id'];
        }
        if (!empty($filters['niveau'])) {
            $where[] = "c.niveau = :niveau";
            $params[':niveau'] = $filters['niveau'];
        }
        if (!empty($filters['serie'])) {
            $where[] = "c.serie = :serie";
            $params[':serie'] = $filters['serie'];
        }
        if (!empty($filters['eleve_id'])) {
            $where[] = "ie.eleve_id = :eleve_id";
            $params[':eleve_id'] = (int)$filters['eleve_id'];
        }
        if (!empty($filters['type_incident_id'])) {
            $where[] = "i.type_incident_id = :type_incident_id";
            $params[':type_incident_id'] = (int)$filters['type_incident_id'];
        }
        if (!empty($filters['niveau_gravite'])) {
            $where[] = "ti.niveau_gravite = :niveau_gravite";
            $params[':niveau_gravite'] = $filters['niveau_gravite'];
        }
        if (!empty($filters['statut'])) {
            $where[] = "i.statut = :statut";
            $params[':statut'] = $filters['statut'];
        }
        if (!empty($filters['role_implication'])) {
            $where[] = "ie.role_implication = :role_implication";
            $params[':role_implication'] = $filters['role_implication'];
        }
        if (!empty($filters['date_debut'])) {
            $where[] = "i.date_incident >= :date_debut";
            $params[':date_debut'] = $filters['date_debut'];
        }
        if (!empty($filters['date_fin'])) {
            $where[] = "i.date_incident <= :date_fin";
            $params[':date_fin'] = $filters['date_fin'];
        }

        return [
            'sql_where' => implode(' AND ', $where),
            'params' => $params
        ];
    }

    /**
     * Build WHERE clauses and PDO parameters for Sanctions Search
     */
    public static function buildSanctionQueryContext($filters, $scopeInfo, $lyceeId) {
        $where = ["s.lycee_id = :lycee_id"];
        $params = [':lycee_id' => $lyceeId];

        if ($scopeInfo['annee_id'] !== null) {
            $where[] = "s.annee_academique_id = :annee_id";
            $params[':annee_id'] = $scopeInfo['annee_id'];
        }

        if ($scopeInfo['scoped_class_ids'] !== null) {
            if (empty($scopeInfo['scoped_class_ids'])) {
                $where[] = "1 = 0";
            } else {
                $inClause = implode(',', array_map('intval', $scopeInfo['scoped_class_ids']));
                $where[] = "s.classe_id IN ({$inClause})";
            }
        }

        if (!empty($filters['cycle_id'])) {
            $where[] = "c.cycle_id = :cycle_id";
            $params[':cycle_id'] = (int)$filters['cycle_id'];
        }
        if (!empty($filters['niveau'])) {
            $where[] = "c.niveau = :niveau";
            $params[':niveau'] = $filters['niveau'];
        }
        if (!empty($filters['serie'])) {
            $where[] = "c.serie = :serie";
            $params[':serie'] = $filters['serie'];
        }
        if (!empty($filters['eleve_id'])) {
            $where[] = "s.eleve_id = :eleve_id";
            $params[':eleve_id'] = (int)$filters['eleve_id'];
        }
        if (!empty($filters['type_sanction_id'])) {
            $where[] = "s.type_sanction_id = :type_sanction_id";
            $params[':type_sanction_id'] = (int)$filters['type_sanction_id'];
        }
        if (!empty($filters['statut'])) {
            $where[] = "s.statut = :statut";
            $params[':statut'] = $filters['statut'];
        }
        if (!empty($filters['date_debut'])) {
            $where[] = "s.date_decision >= :date_debut";
            $params[':date_debut'] = $filters['date_debut'];
        }
        if (!empty($filters['date_fin'])) {
            $where[] = "s.date_decision <= :date_fin";
            $params[':date_fin'] = $filters['date_fin'];
        }

        return [
            'sql_where' => implode(' AND ', $where),
            'params' => $params
        ];
    }

    /**
     * Get Filtered Incidents Dataset (Paginated or All for Exports)
     */
    public static function getFilteredIncidents($filters, $userId, $userRole, $lyceeId, $hasGlobalView, $page = 1, $perPage = 25) {
        $scopeInfo = self::validateAndResolveScope($filters, $userId, $userRole, $lyceeId, $hasGlobalView);
        $ctx = self::buildIncidentQueryContext($filters, $scopeInfo, $lyceeId);
        $db = Database::getInstance();

        // 1. Total Count
        $stmtCount = $db->prepare("
            SELECT COUNT(DISTINCT ie.id)
            FROM discipline_incident_eleves ie
            JOIN discipline_incidents i ON i.id = ie.incident_id
            LEFT JOIN discipline_types_incidents ti ON ti.id = i.type_incident_id
            LEFT JOIN classes c ON c.id_classe = ie.classe_id
            LEFT JOIN eleves e ON e.id_eleve = ie.eleve_id
            WHERE {$ctx['sql_where']}
        ");
        $stmtCount->execute($ctx['params']);
        $totalRows = (int)$stmtCount->fetchColumn();

        // 2. Fetch Data
        $offset = max(0, ($page - 1) * $perPage);
        $limitClause = ($perPage > 0) ? "LIMIT " . (int)$perPage . " OFFSET " . (int)$offset : "";

        $stmtData = $db->prepare("
            SELECT
                i.id AS incident_id,
                ti.code AS incident_code,
                i.date_incident,
                i.heure_incident,
                i.lieu,
                i.description_faits,
                i.statut AS incident_statut,
                ti.libelle AS type_incident_libelle,
                ti.niveau_gravite,
                ie.eleve_id,
                CONCAT(e.prenom, ' ', e.nom) AS eleve_nom_complet,
                e.matricule AS eleve_matricule,
                ie.role_implication,
                ie.observation_individuelle,
                TRIM(CONCAT(COALESCE(c.niveau, ''), ' ', COALESCE(c.serie, ''), ' ', COALESCE(c.numero, ''))) AS nom_classe_snapshot,
                CONCAT(u.prenom, ' ', u.nom) AS signale_par_nom
            FROM discipline_incident_eleves ie
            JOIN discipline_incidents i ON i.id = ie.incident_id
            LEFT JOIN discipline_types_incidents ti ON ti.id = i.type_incident_id
            LEFT JOIN classes c ON c.id_classe = ie.classe_id
            LEFT JOIN eleves e ON e.id_eleve = ie.eleve_id
            LEFT JOIN utilisateurs u ON u.id_user = i.signale_par_user_id
            WHERE {$ctx['sql_where']}
            ORDER BY i.date_incident DESC, i.created_at DESC
            {$limitClause}
        ");
        $stmtData->execute($ctx['params']);
        $items = $stmtData->fetchAll(PDO::FETCH_ASSOC);

        return [
            'total' => $totalRows,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ($perPage > 0) ? (int)ceil($totalRows / $perPage) : 1,
            'items' => $items,
            'scope_info' => $scopeInfo
        ];
    }

    /**
     * Get Filtered Sanctions Dataset (Paginated or All for Exports)
     */
    public static function getFilteredSanctions($filters, $userId, $userRole, $lyceeId, $hasGlobalView, $page = 1, $perPage = 25) {
        $scopeInfo = self::validateAndResolveScope($filters, $userId, $userRole, $lyceeId, $hasGlobalView);
        $ctx = self::buildSanctionQueryContext($filters, $scopeInfo, $lyceeId);
        $db = Database::getInstance();

        // 1. Total Count
        $stmtCount = $db->prepare("
            SELECT COUNT(DISTINCT s.id)
            FROM discipline_sanctions s
            LEFT JOIN discipline_types_sanctions ts ON ts.id = s.type_sanction_id
            LEFT JOIN classes c ON c.id_classe = s.classe_id
            LEFT JOIN eleves e ON e.id_eleve = s.eleve_id
            WHERE {$ctx['sql_where']}
        ");
        $stmtCount->execute($ctx['params']);
        $totalRows = (int)$stmtCount->fetchColumn();

        // 2. Fetch Data
        $offset = max(0, ($page - 1) * $perPage);
        $limitClause = ($perPage > 0) ? "LIMIT " . (int)$perPage . " OFFSET " . (int)$offset : "";

        $stmtData = $db->prepare("
            SELECT
                s.id AS sanction_id,
                s.incident_id,
                s.date_decision,
                s.date_debut_execution,
                s.date_fin_execution,
                s.duree_jours,
                s.duree_heures,
                s.statut AS sanction_statut,
                s.motif AS motif_decision,
                s.motif_levee_annulation,
                s.date_levee_annulation,
                ts.code AS type_sanction_code,
                ts.libelle AS type_sanction_libelle,
                s.eleve_id,
                CONCAT(e.prenom, ' ', e.nom) AS eleve_nom_complet,
                e.matricule AS eleve_matricule,
                TRIM(CONCAT(COALESCE(c.niveau, ''), ' ', COALESCE(c.serie, ''), ' ', COALESCE(c.numero, ''))) AS nom_classe_snapshot,
                CONCAT(u_dec.prenom, ' ', u_dec.nom) AS prononcee_par_nom,
                CONCAT(u_lev.prenom, ' ', u_lev.nom) AS levee_par_nom,
                ti.code AS incident_code
            FROM discipline_sanctions s
            LEFT JOIN discipline_types_sanctions ts ON ts.id = s.type_sanction_id
            LEFT JOIN classes c ON c.id_classe = s.classe_id
            LEFT JOIN eleves e ON e.id_eleve = s.eleve_id
            LEFT JOIN utilisateurs u_dec ON u_dec.id_user = s.prononcee_par_user_id
            LEFT JOIN utilisateurs u_lev ON u_lev.id_user = s.par_user_id_levee_annulation
            LEFT JOIN discipline_incidents i ON i.id = s.incident_id
            LEFT JOIN discipline_types_incidents ti ON ti.id = i.type_incident_id
            WHERE {$ctx['sql_where']}
            ORDER BY s.date_decision DESC, s.created_at DESC
            {$limitClause}
        ");
        $stmtData->execute($ctx['params']);
        $items = $stmtData->fetchAll(PDO::FETCH_ASSOC);

        return [
            'total' => $totalRows,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ($perPage > 0) ? (int)ceil($totalRows / $perPage) : 1,
            'items' => $items,
            'scope_info' => $scopeInfo
        ];
    }

    /**
     * Compute Filtered KPIs & Visual Analytics for Search Results
     */
    public static function getSearchSummaryAnalytics($filters, $userId, $userRole, $lyceeId, $hasGlobalView) {
        $scopeInfo = self::validateAndResolveScope($filters, $userId, $userRole, $lyceeId, $hasGlobalView);
        $incCtx = self::buildIncidentQueryContext($filters, $scopeInfo, $lyceeId);
        $sancCtx = self::buildSanctionQueryContext($filters, $scopeInfo, $lyceeId);
        $db = Database::getInstance();

        // 1. Incidents Totaux (Excluding classe_sans_suite)
        $incWhereNoDismissed = $incCtx['sql_where'] . " AND i.statut != 'classe_sans_suite' ";
        $stmtKpiInc = $db->prepare("
            SELECT COUNT(DISTINCT i.id)
            FROM discipline_incidents i
            JOIN discipline_incident_eleves ie ON ie.incident_id = i.id
            LEFT JOIN discipline_types_incidents ti ON ti.id = i.type_incident_id
            LEFT JOIN classes c ON c.id_classe = ie.classe_id
            WHERE {$incWhereNoDismissed}
        ");
        $stmtKpiInc->execute($incCtx['params']);
        $totalIncidents = (int)$stmtKpiInc->fetchColumn();

        // 2. Eleves Impliques
        $stmtKpiElevesImp = $db->prepare("
            SELECT COUNT(DISTINCT ie.eleve_id)
            FROM discipline_incident_eleves ie
            JOIN discipline_incidents i ON i.id = ie.incident_id
            LEFT JOIN discipline_types_incidents ti ON ti.id = i.type_incident_id
            LEFT JOIN classes c ON c.id_classe = ie.classe_id
            WHERE {$incWhereNoDismissed}
        ");
        $stmtKpiElevesImp->execute($incCtx['params']);
        $elevesImpliques = (int)$stmtKpiElevesImp->fetchColumn();

        // 3. Eleves Responsables
        $stmtKpiElevesResp = $db->prepare("
            SELECT COUNT(DISTINCT ie.eleve_id)
            FROM discipline_incident_eleves ie
            JOIN discipline_incidents i ON i.id = ie.incident_id
            LEFT JOIN discipline_types_incidents ti ON ti.id = i.type_incident_id
            LEFT JOIN classes c ON c.id_classe = ie.classe_id
            WHERE {$incWhereNoDismissed}
              AND ie.role_implication IN ('auteur_principal', 'co_auteur', 'complice')
        ");
        $stmtKpiElevesResp->execute($incCtx['params']);
        $elevesResponsables = (int)$stmtKpiElevesResp->fetchColumn();

        // 4. Eleves Recidivistes
        $stmtKpiRecidive = $db->prepare("
            SELECT COUNT(*) FROM (
                SELECT ie.eleve_id
                FROM discipline_incident_eleves ie
                JOIN discipline_incidents i ON i.id = ie.incident_id
                LEFT JOIN discipline_types_incidents ti ON ti.id = i.type_incident_id
                LEFT JOIN classes c ON c.id_classe = ie.classe_id
                WHERE {$incWhereNoDismissed}
                  AND ie.role_implication IN ('auteur_principal', 'co_auteur')
                GROUP BY ie.eleve_id
                HAVING COUNT(DISTINCT ie.incident_id) >= 2
            ) AS recidivistes
        ");
        $stmtKpiRecidive->execute($incCtx['params']);
        $elevesRecidivistes = (int)$stmtKpiRecidive->fetchColumn();

        // 5. Sanctions Totales
        $stmtKpiSanctions = $db->prepare("
            SELECT COUNT(DISTINCT s.id)
            FROM discipline_sanctions s
            LEFT JOIN discipline_types_sanctions ts ON ts.id = s.type_sanction_id
            LEFT JOIN classes c ON c.id_classe = s.classe_id
            WHERE {$sancCtx['sql_where']}
        ");
        $stmtKpiSanctions->execute($sancCtx['params']);
        $totalSanctions = (int)$stmtKpiSanctions->fetchColumn();

        // Visual Charts Data
        // Severity Chart
        $stmtSeverity = $db->prepare("
            SELECT COALESCE(ti.niveau_gravite, 'Non spécifié') AS gravite, COUNT(DISTINCT i.id) AS total
            FROM discipline_incidents i
            JOIN discipline_incident_eleves ie ON ie.incident_id = i.id
            LEFT JOIN discipline_types_incidents ti ON ti.id = i.type_incident_id
            LEFT JOIN classes c ON c.id_classe = ie.classe_id
            WHERE {$incWhereNoDismissed}
            GROUP BY ti.niveau_gravite
        ");
        $stmtSeverity->execute($incCtx['params']);
        $severityStats = $stmtSeverity->fetchAll(PDO::FETCH_KEY_PAIR);

        // Sanction Status Chart
        $stmtSancStatus = $db->prepare("
            SELECT s.statut, COUNT(DISTINCT s.id) AS total
            FROM discipline_sanctions s
            LEFT JOIN discipline_types_sanctions ts ON ts.id = s.type_sanction_id
            LEFT JOIN classes c ON c.id_classe = s.classe_id
            WHERE {$sancCtx['sql_where']}
            GROUP BY s.statut
        ");
        $stmtSancStatus->execute($sancCtx['params']);
        $sanctionStatusStats = $stmtSancStatus->fetchAll(PDO::FETCH_KEY_PAIR);

        return [
            'totalIncidents' => $totalIncidents,
            'elevesImpliques' => $elevesImpliques,
            'elevesResponsables' => $elevesResponsables,
            'elevesRecidivistes' => $elevesRecidivistes,
            'totalSanctions' => $totalSanctions,
            'severityStats' => $severityStats,
            'sanctionStatusStats' => $sanctionStatusStats
        ];
    }
}
