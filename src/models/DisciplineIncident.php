<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/DisciplineIncidentEleve.php';

class DisciplineIncident {

    public static function findById($id, $lyceeId = null) {
        $db = Database::getInstance();
        $lyceeId = $lyceeId ?? Auth::getLyceeId();
        if (!$id || !$lyceeId) {
            return false;
        }

        $sql = "SELECT i.*, ti.code as type_code, ti.libelle as type_libelle, ti.niveau_gravite,
                       u.nom as signale_nom, u.prenom as signale_prenom,
                       a.libelle as annee_libelle, a.date_debut as annee_debut, a.date_fin as annee_fin
                FROM discipline_incidents i
                JOIN discipline_types_incidents ti ON i.type_incident_id = ti.id
                JOIN utilisateurs u ON i.signale_par_user_id = u.id_user
                JOIN annees_academiques a ON i.annee_academique_id = a.id
                WHERE i.id = :id AND i.lycee_id = :lycee_id";

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute(['id' => $id, 'lycee_id' => $lyceeId]);
            $incident = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($incident) {
                $incident['eleves'] = DisciplineIncidentEleve::findByIncidentId($incident['id']);
            }
            return $incident;
        } catch (PDOException $e) {
            error_log("Error in DisciplineIncident::findById: " . $e->getMessage());
            return false;
        }
    }

    public static function search($filters = [], $lyceeId = null, $isTeacherScoped = false, $teacherUserId = null, $allowedClassIds = []) {
        $db = Database::getInstance();
        $lyceeId = $lyceeId ?? Auth::getLyceeId();
        if (!$lyceeId) {
            return [];
        }

        $sql = "SELECT DISTINCT i.*, ti.code as type_code, ti.libelle as type_libelle, ti.niveau_gravite,
                       u.nom as signale_nom, u.prenom as signale_prenom,
                       (SELECT COUNT(*) FROM discipline_incident_eleves ie WHERE ie.incident_id = i.id) as nb_eleves
                FROM discipline_incidents i
                JOIN discipline_types_incidents ti ON i.type_incident_id = ti.id
                JOIN utilisateurs u ON i.signale_par_user_id = u.id_user
                LEFT JOIN discipline_incident_eleves ie_filter ON i.id = ie_filter.incident_id
                WHERE i.lycee_id = :lycee_id";

        $params = ['lycee_id' => $lyceeId];

        // Teacher scoping
        if ($isTeacherScoped && $teacherUserId) {
            if (!empty($allowedClassIds)) {
                $placeholders = [];
                foreach (array_values($allowedClassIds) as $idx => $cid) {
                    $key = ':t_cls_' . $idx;
                    $placeholders[] = $key;
                    $params[$key] = (int)$cid;
                }
                $sql .= " AND (i.signale_par_user_id = :teacher_uid OR ie_filter.classe_id IN (" . implode(',', $placeholders) . "))";
            } else {
                $sql .= " AND i.signale_par_user_id = :teacher_uid";
            }
            $params['teacher_uid'] = (int)$teacherUserId;
        }

        if (!empty($filters['annee_academique_id'])) {
            $sql .= " AND i.annee_academique_id = :annee_id";
            $params['annee_id'] = $filters['annee_academique_id'];
        }

        if (!empty($filters['type_incident_id'])) {
            $sql .= " AND i.type_incident_id = :type_id";
            $params['type_id'] = $filters['type_incident_id'];
        }

        if (!empty($filters['statut'])) {
            $sql .= " AND i.statut = :statut";
            $params['statut'] = $filters['statut'];
        }

        if (!empty($filters['classe_id'])) {
            $sql .= " AND ie_filter.classe_id = :classe_id";
            $params['classe_id'] = $filters['classe_id'];
        }

        if (!empty($filters['eleve_id'])) {
            $sql .= " AND ie_filter.eleve_id = :eleve_id";
            $params['eleve_id'] = $filters['eleve_id'];
        }

        if (!empty($filters['date_debut'])) {
            $sql .= " AND i.date_incident >= :date_debut";
            $params['date_debut'] = $filters['date_debut'];
        }

        if (!empty($filters['date_fin'])) {
            $sql .= " AND i.date_incident <= :date_fin";
            $params['date_fin'] = $filters['date_fin'];
        }

        $sql .= " ORDER BY i.date_incident DESC, i.id DESC";

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in DisciplineIncident::search: " . $e->getMessage());
            return [];
        }
    }

    public static function create($data, $elevesData, $isTeacherScoped = false, $allowedClassIds = []) {
        $db = Database::getInstance();
        $lyceeId = Auth::getLyceeId();
        $userId = Auth::getUserId();

        if (!$lyceeId || !$userId) {
            throw new InvalidArgumentException("Session ou établissement non valide.");
        }

        if (empty($data['type_incident_id'])) {
            throw new InvalidArgumentException("Le type d'incident est obligatoire.");
        }
        if (empty($data['date_incident'])) {
            throw new InvalidArgumentException("La date de l'incident est obligatoire.");
        }
        if (empty(trim($data['description_faits'] ?? ''))) {
            throw new InvalidArgumentException("La description des faits est obligatoire.");
        }
        if (empty($elevesData) || !is_array($elevesData)) {
            throw new InvalidArgumentException("Au moins un élève doit être associé à l'incident.");
        }

        // Validate active academic year
        $anneeId = $data['annee_academique_id'] ?? null;
        if (!$anneeId) {
            $stmtAnnee = $db->prepare("SELECT id FROM annees_academiques WHERE est_active = 1 LIMIT 1");
            $stmtAnnee->execute();
            $activeAnnee = $stmtAnnee->fetch(PDO::FETCH_ASSOC);
            if (!$activeAnnee) {
                throw new InvalidArgumentException("Aucune année académique active trouvée.");
            }
            $anneeId = $activeAnnee['id'];
        }

        // Verify all selected students belong strictly to lycee_id and capture historical class_id
        $rolesValides = ['auteur_principal', 'co_auteur', 'complice', 'victime', 'temoin'];
        $validatedEleves = [];

        $stmtEleveCheck = $db->prepare("
            SELECT e.id_eleve, et.classe_id
            FROM eleves e
            JOIN etudes et ON e.id_eleve = et.eleve_id
            WHERE e.id_eleve = :eleve_id
              AND e.lycee_id = :lycee_id
              AND et.annee_academique_id = :annee_id
            LIMIT 1
        ");

        foreach ($elevesData as $item) {
            $eleveId = (int)($item['eleve_id'] ?? 0);
            if ($eleveId <= 0) continue;

            $stmtEleveCheck->execute([
                'eleve_id' => $eleveId,
                'lycee_id' => $lyceeId,
                'annee_id' => $anneeId
            ]);
            $eleveInfo = $stmtEleveCheck->fetch(PDO::FETCH_ASSOC);

            if (!$eleveInfo) {
                throw new InvalidArgumentException("L'élève ID $eleveId n'appartient pas à cet établissement ou n'a pas de classe inscrite pour cette année académique.");
            }

            $role = strtolower(trim($item['role_implication'] ?? 'auteur_principal'));
            if (!in_array($role, $rolesValides, true)) {
                $role = 'auteur_principal';
            }

            $studentClasseId = (int)$eleveInfo['classe_id'];

            // Teacher scope check: verify student belongs to an authorized class
            if ($isTeacherScoped) {
                if (empty($allowedClassIds) || !in_array($studentClasseId, $allowedClassIds, true)) {
                    throw new InvalidArgumentException("Vous n'avez pas de périmètre pédagogique actif dans la classe de cet élève.");
                }
            }

            $validatedEleves[] = [
                'eleve_id' => $eleveId,
                'classe_id' => $studentClasseId,
                'role_implication' => $role,
                'observation_individuelle' => trim($item['observation_individuelle'] ?? '')
            ];
        }

        if (empty($validatedEleves)) {
            throw new InvalidArgumentException("Aucun élève valide n'a été fourni.");
        }

        // Transactional Atomic Insertion
        $db->beginTransaction();
        try {
            $now = date('Y-m-d H:i:s');
            $sqlInc = "INSERT INTO discipline_incidents
                       (lycee_id, annee_academique_id, type_incident_id, date_incident, heure_incident, lieu, description_faits, signale_par_user_id, statut, created_at, updated_at)
                       VALUES (:lycee_id, :annee_id, :type_id, :date_inc, :heure_inc, :lieu, :desc, :user_id, :statut, '$now', '$now')";

            $stmtInc = $db->prepare($sqlInc);
            $stmtInc->execute([
                'lycee_id' => $lyceeId,
                'annee_id' => $anneeId,
                'type_id' => (int)$data['type_incident_id'],
                'date_inc' => $data['date_incident'],
                'heure_inc' => !empty($data['heure_incident']) ? $data['heure_incident'] : null,
                'lieu' => !empty($data['lieu']) ? trim($data['lieu']) : null,
                'desc' => trim($data['description_faits']),
                'user_id' => $userId,
                'statut' => 'signale'
            ]);

            $incidentId = (int)$db->lastInsertId();

            $sqlEleve = "INSERT INTO discipline_incident_eleves
                         (incident_id, eleve_id, classe_id, role_implication, observation_individuelle, created_at)
                         VALUES (:incident_id, :eleve_id, :classe_id, :role_imp, :obs, '$now')";
            $stmtEleve = $db->prepare($sqlEleve);

            foreach ($validatedEleves as $ve) {
                $stmtEleve->execute([
                    'incident_id' => $incidentId,
                    'eleve_id' => $ve['eleve_id'],
                    'classe_id' => $ve['classe_id'],
                    'role_imp' => $ve['role_implication'],
                    'obs' => !empty($ve['observation_individuelle']) ? $ve['observation_individuelle'] : null
                ]);
            }

            $db->commit();
            return $incidentId;
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Error in DisciplineIncident::create: " . $e->getMessage());
            throw $e;
        }
    }

    public static function update($incidentId, $data, $elevesData, $isTeacherScoped = false, $teacherUserId = null, $allowedClassIds = []) {
        $db = Database::getInstance();
        $lyceeId = Auth::getLyceeId();

        if (!$incidentId || !$lyceeId) {
            throw new InvalidArgumentException("Incident ou établissement non valide.");
        }

        $incident = self::findById($incidentId, $lyceeId);
        if (!$incident) {
            throw new InvalidArgumentException("Incident introuvable.");
        }

        // Check status: can only edit when statut == 'signale'
        if ($incident['statut'] !== 'signale') {
            throw new InvalidArgumentException("Cet incident est en cours d'instruction ou clôturé et ne peut plus être modifié.");
        }

        // Check teacher scope & ownership
        if ($isTeacherScoped) {
            if ((int)$incident['signale_par_user_id'] !== (int)$teacherUserId) {
                throw new InvalidArgumentException("Vous ne pouvez modifier que les signalements dont vous êtes l'auteur.");
            }
        }

        if (empty($data['type_incident_id'])) {
            throw new InvalidArgumentException("Le type d'incident est obligatoire.");
        }
        if (empty($data['date_incident'])) {
            throw new InvalidArgumentException("La date de l'incident est obligatoire.");
        }
        if (empty(trim($data['description_faits'] ?? ''))) {
            throw new InvalidArgumentException("La description des faits est obligatoire.");
        }
        if (empty($elevesData) || !is_array($elevesData)) {
            throw new InvalidArgumentException("Au moins un élève doit être associé à l'incident.");
        }

        $anneeId = $incident['annee_academique_id'];
        $rolesValides = ['auteur_principal', 'co_auteur', 'complice', 'victime', 'temoin'];
        $validatedEleves = [];

        $stmtEleveCheck = $db->prepare("
            SELECT e.id_eleve, et.classe_id
            FROM eleves e
            JOIN etudes et ON e.id_eleve = et.eleve_id
            WHERE e.id_eleve = :eleve_id
              AND e.lycee_id = :lycee_id
              AND et.annee_academique_id = :annee_id
            LIMIT 1
        ");

        foreach ($elevesData as $item) {
            $eleveId = (int)($item['eleve_id'] ?? 0);
            if ($eleveId <= 0) continue;

            $stmtEleveCheck->execute([
                'eleve_id' => $eleveId,
                'lycee_id' => $lyceeId,
                'annee_id' => $anneeId
            ]);
            $eleveInfo = $stmtEleveCheck->fetch(PDO::FETCH_ASSOC);

            if (!$eleveInfo) {
                throw new InvalidArgumentException("L'élève ID $eleveId n'appartient pas à cet établissement ou n'a pas de classe inscrite pour cette année académique.");
            }

            $studentClasseId = (int)$eleveInfo['classe_id'];

            if ($isTeacherScoped) {
                if (empty($allowedClassIds) || !in_array($studentClasseId, $allowedClassIds, true)) {
                    throw new InvalidArgumentException("Vous n'avez pas de périmètre pédagogique actif dans la classe de cet élève.");
                }
            }

            $role = strtolower(trim($item['role_implication'] ?? 'auteur_principal'));
            if (!in_array($role, $rolesValides, true)) {
                $role = 'auteur_principal';
            }

            $validatedEleves[] = [
                'eleve_id' => $eleveId,
                'classe_id' => $studentClasseId,
                'role_implication' => $role,
                'observation_individuelle' => trim($item['observation_individuelle'] ?? '')
            ];
        }

        if (empty($validatedEleves)) {
            throw new InvalidArgumentException("Aucun élève valide n'a été fourni.");
        }

        $db->beginTransaction();
        try {
            $now = date('Y-m-d H:i:s');
            $sqlInc = "UPDATE discipline_incidents SET
                       type_incident_id = :type_id,
                       date_incident = :date_inc,
                       heure_incident = :heure_inc,
                       lieu = :lieu,
                       description_faits = :desc,
                       updated_at = '$now'
                       WHERE id = :id AND lycee_id = :lycee_id";

            $stmtInc = $db->prepare($sqlInc);
            $stmtInc->execute([
                'type_id' => (int)$data['type_incident_id'],
                'date_inc' => $data['date_incident'],
                'heure_inc' => !empty($data['heure_incident']) ? $data['heure_incident'] : null,
                'lieu' => !empty($data['lieu']) ? trim($data['lieu']) : null,
                'desc' => trim($data['description_faits']),
                'id' => $incidentId,
                'lycee_id' => $lyceeId
            ]);

            // Re-insert students
            $stmtDel = $db->prepare("DELETE FROM discipline_incident_eleves WHERE incident_id = :incident_id");
            $stmtDel->execute(['incident_id' => $incidentId]);

            $sqlEleve = "INSERT INTO discipline_incident_eleves
                         (incident_id, eleve_id, classe_id, role_implication, observation_individuelle, created_at)
                         VALUES (:incident_id, :eleve_id, :classe_id, :role_imp, :obs, '$now')";
            $stmtEleve = $db->prepare($sqlEleve);

            foreach ($validatedEleves as $ve) {
                $stmtEleve->execute([
                    'incident_id' => $incidentId,
                    'eleve_id' => $ve['eleve_id'],
                    'classe_id' => $ve['classe_id'],
                    'role_imp' => $ve['role_implication'],
                    'obs' => !empty($ve['observation_individuelle']) ? $ve['observation_individuelle'] : null
                ]);
            }

            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Error in DisciplineIncident::update: " . $e->getMessage());
            throw $e;
        }
    }

    public static function updateStatus($incidentId, $newStatut) {
        $db = Database::getInstance();
        $lyceeId = Auth::getLyceeId();
        if (!$incidentId || !$lyceeId) {
            return false;
        }

        $statutsValides = ['signale', 'en_instruction', 'classe_sans_suite', 'traite'];
        if (!in_array($newStatut, $statutsValides, true)) {
            throw new InvalidArgumentException("Statut d'incident non valide.");
        }

        $incident = self::findById($incidentId, $lyceeId);
        if (!$incident) {
            throw new InvalidArgumentException("Incident introuvable.");
        }

        // Enforce Workflow Rules
        if (in_array($incident['statut'], ['classe_sans_suite', 'traite'], true)) {
            throw new InvalidArgumentException("Un incident clôturé (traité ou classé sans suite) ne peut plus changer de statut.");
        }

        $now = date('Y-m-d H:i:s');
        $sql = "UPDATE discipline_incidents SET statut = :statut, updated_at = '$now' WHERE id = :id AND lycee_id = :lycee_id";
        try {
            $stmt = $db->prepare($sql);
            return $stmt->execute([
                'statut' => $newStatut,
                'id' => $incidentId,
                'lycee_id' => $lyceeId
            ]);
        } catch (PDOException $e) {
            error_log("Error in DisciplineIncident::updateStatus: " . $e->getMessage());
            return false;
        }
    }
}
?>