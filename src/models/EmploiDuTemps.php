<?php

require_once __DIR__ . '/../config/database.php';

class EmploiDuTemps {

    /**
     * Find a single entry by ID with tenant isolation.
     */
    public static function findById($id, $lycee_id = null) {
        $db = Database::getInstance();
        $sql = "SELECT edt.*, c.niveau, c.serie, c.numero, m.nom_matiere, u.nom as prof_nom, u.prenom as prof_prenom, s.nom_salle
                FROM emploi_du_temps edt
                JOIN classes c ON edt.classe_id = c.id_classe
                JOIN matieres m ON edt.matiere_id = m.id_matiere
                JOIN utilisateurs u ON edt.professeur_id = u.id_user
                LEFT JOIN salles s ON edt.salle_id = s.id_salle
                WHERE edt.id = :id";
        $params = ['id' => $id];
        if ($lycee_id !== null) {
            $sql .= " AND edt.lycee_id = :lycee_id";
            $params['lycee_id'] = $lycee_id;
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Get all timetable entries for a given context (class, teacher, or room) with multi-tenant isolation.
     */
    public static function getByContext($annee_academique_id, $classe_id = null, $professeur_id = null, $salle_id = null, $lycee_id = null) {
        $db = Database::getInstance();
        $sql = "SELECT edt.*, c.niveau, c.serie, c.numero, m.nom_matiere, u.nom as prof_nom, u.prenom as prof_prenom, s.nom_salle
                FROM emploi_du_temps edt
                JOIN classes c ON edt.classe_id = c.id_classe
                JOIN matieres m ON edt.matiere_id = m.id_matiere
                JOIN utilisateurs u ON edt.professeur_id = u.id_user
                LEFT JOIN salles s ON edt.salle_id = s.id_salle
                WHERE edt.annee_academique_id = :annee_academique_id";

        $params = ['annee_academique_id' => $annee_academique_id];

        if ($lycee_id !== null) {
            $sql .= " AND edt.lycee_id = :lycee_id";
            $params['lycee_id'] = $lycee_id;
        }

        if ($classe_id) {
            $sql .= " AND edt.classe_id = :classe_id";
            $params['classe_id'] = $classe_id;
        }
        if ($professeur_id) {
            $sql .= " AND edt.professeur_id = :professeur_id";
            $params['professeur_id'] = $professeur_id;
        }
        if ($salle_id) {
            $sql .= " AND edt.salle_id = :salle_id";
            $params['salle_id'] = $salle_id;
        }

        $sql .= " ORDER BY edt.jour ASC, edt.heure_debut ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Validate payload integrity before conflict checking or persisting.
     */
    public static function validatePayload($data, $lycee_id) {
        $validDays = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
        if (empty($data['jour']) || !in_array($data['jour'], $validDays)) {
            return "Jour invalide.";
        }

        if (empty($data['heure_debut']) || empty($data['heure_fin'])) {
            return "Les heures de début et de fin sont obligatoires.";
        }

        $hDebut = sprintf('%05s', trim($data['heure_debut']));
        $hFin = sprintf('%05s', trim($data['heure_fin']));

        if (strlen($hDebut) == 5) $hDebut .= ':00';
        if (strlen($hFin) == 5) $hFin .= ':00';

        if ($hDebut >= $hFin) {
            return "L'heure de début doit être strictement inférieure à l'heure de fin.";
        }

        if (empty($data['classe_id']) || empty($data['matiere_id']) || empty($data['professeur_id']) || empty($data['annee_academique_id'])) {
            return "Tous les champs obligatoires (classe, matière, professeur, année académique) doivent être renseignés.";
        }

        $db = Database::getInstance();

        // Verify Class
        $stmt = $db->prepare("SELECT id_classe FROM classes WHERE id_classe = :id AND lycee_id = :lycee_id");
        $stmt->execute(['id' => $data['classe_id'], 'lycee_id' => $lycee_id]);
        if (!$stmt->fetch()) {
            return "La classe sélectionnée n'existe pas ou ne vous appartient pas.";
        }

        // Verify Teacher
        $stmt = $db->prepare("SELECT id_user FROM utilisateurs WHERE id_user = :id AND lycee_id = :lycee_id");
        $stmt->execute(['id' => $data['professeur_id'], 'lycee_id' => $lycee_id]);
        if (!$stmt->fetch()) {
            return "Le professeur sélectionné n'existe pas ou ne vous appartient pas.";
        }

        // Verify Room if specified
        if (!empty($data['salle_id'])) {
            $stmt = $db->prepare("SELECT id_salle FROM salles WHERE id_salle = :id AND lycee_id = :lycee_id");
            $stmt->execute(['id' => $data['salle_id'], 'lycee_id' => $lycee_id]);
            if (!$stmt->fetch()) {
                return "La salle sélectionnée n'existe pas ou ne vous appartient pas.";
            }
        }

        return true;
    }

    /**
     * Check for conflicts (Teacher, Class, or Room) before saving/updating.
     */
    public static function checkConflict($data, $exclude_ids = [], $lycee_id = null) {
        $db = Database::getInstance();
        $targetLyceeId = $lycee_id ?? ($data['lycee_id'] ?? Auth::getLyceeId());

        $sql = "SELECT edt.*, c.niveau, c.serie, c.numero, m.nom_matiere, u.nom as prof_nom, u.prenom as prof_prenom, s.nom_salle
                FROM emploi_du_temps edt
                JOIN classes c ON edt.classe_id = c.id_classe
                JOIN matieres m ON edt.matiere_id = m.id_matiere
                JOIN utilisateurs u ON edt.professeur_id = u.id_user
                LEFT JOIN salles s ON edt.salle_id = s.id_salle
                WHERE edt.lycee_id = :lycee_id
                  AND edt.annee_academique_id = :annee_academique_id
                  AND edt.jour = :jour
                  AND (edt.heure_debut < :heure_fin AND edt.heure_fin > :heure_debut)
                  AND (
                      edt.professeur_id = :professeur_id
                      OR edt.classe_id = :classe_id";

        $params = [
            'lycee_id' => $targetLyceeId,
            'annee_academique_id' => $data['annee_academique_id'],
            'jour' => $data['jour'],
            'heure_debut' => $data['heure_debut'],
            'heure_fin' => $data['heure_fin'],
            'professeur_id' => $data['professeur_id'],
            'classe_id' => $data['classe_id'],
        ];

        if (!empty($data['salle_id'])) {
            $sql .= " OR (edt.salle_id IS NOT NULL AND edt.salle_id = :salle_id)";
            $params['salle_id'] = $data['salle_id'];
        }

        $sql .= ")";

        if (!is_array($exclude_ids) && $exclude_ids) {
            $exclude_ids = [$exclude_ids];
        }

        if (!empty($exclude_ids)) {
            $inPlaceholders = [];
            foreach ($exclude_ids as $idx => $exId) {
                $key = ":ex_id_" . $idx;
                $inPlaceholders[] = $key;
                $params['ex_id_' . $idx] = $exId;
            }
            $sql .= " AND edt.id NOT IN (" . implode(', ', $inPlaceholders) . ")";
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        $conflict = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($conflict) {
            if ($conflict['professeur_id'] == $data['professeur_id']) {
                return "Conflit Enseignant : " . $conflict['prof_prenom'] . " " . $conflict['prof_nom'] . " a déjà un cours de " . $conflict['heure_debut'] . " à " . $conflict['heure_fin'] . ".";
            }
            if ($conflict['classe_id'] == $data['classe_id']) {
                return "Conflit Classe : La classe à cette heure est déjà programmée en " . $conflict['nom_matiere'] . " (" . $conflict['heure_debut'] . " - " . $conflict['heure_fin'] . ").";
            }
            if (!empty($data['salle_id']) && $conflict['salle_id'] == $data['salle_id']) {
                return "Conflit Salle : La salle " . ($conflict['nom_salle'] ?? 'sélectionnée') . " est déjà occupée de " . $conflict['heure_debut'] . " à " . $conflict['heure_fin'] . ".";
            }
            return "Conflit détecté avec un autre cours à ce créneau.";
        }

        return false;
    }

    /**
     * Atomically save (INSERT or UPDATE) with transactional locking against concurrent requests.
     */
    public static function save($data, $lycee_id = null) {
        $targetLyceeId = $lycee_id ?? Auth::getLyceeId();

        $validation = self::validatePayload($data, $targetLyceeId);
        if ($validation !== true) {
            $_SESSION['error_message'] = $validation;
            return false;
        }

        $db = Database::getInstance();
        $isUpdate = !empty($data['id']);
        $exclude_id = $isUpdate ? $data['id'] : null;

        try {
            $db->beginTransaction();

            // Lock conflicting rows or lock tenant table lock equivalent
            $isSqlite = ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite');
            $lockSql = "SELECT id FROM emploi_du_temps WHERE lycee_id = :lycee_id AND jour = :jour" . ($isSqlite ? "" : " FOR UPDATE");
            $lockStmt = $db->prepare($lockSql);
            $lockStmt->execute(['lycee_id' => $targetLyceeId, 'jour' => $data['jour']]);

            $conflictErr = self::checkConflict($data, $exclude_id, $targetLyceeId);
            if ($conflictErr !== false) {
                $_SESSION['error_message'] = $conflictErr;
                $db->rollBack();
                return false;
            }

            $sql = $isUpdate
                ? "UPDATE emploi_du_temps SET classe_id = :classe_id, matiere_id = :matiere_id, professeur_id = :professeur_id, lycee_id = :lycee_id, jour = :jour, heure_debut = :heure_debut, heure_fin = :heure_fin, salle_id = :salle_id, annee_academique_id = :annee_academique_id WHERE id = :id AND lycee_id = :lycee_id"
                : "INSERT INTO emploi_du_temps (classe_id, matiere_id, professeur_id, lycee_id, jour, heure_debut, heure_fin, salle_id, annee_academique_id) VALUES (:classe_id, :matiere_id, :professeur_id, :lycee_id, :jour, :heure_debut, :heure_fin, :salle_id, :annee_academique_id)";

            $stmt = $db->prepare($sql);
            $params = [
                'classe_id' => $data['classe_id'],
                'matiere_id' => $data['matiere_id'],
                'professeur_id' => $data['professeur_id'],
                'lycee_id' => $targetLyceeId,
                'jour' => $data['jour'],
                'heure_debut' => $data['heure_debut'],
                'heure_fin' => $data['heure_fin'],
                'salle_id' => !empty($data['salle_id']) ? $data['salle_id'] : null,
                'annee_academique_id' => $data['annee_academique_id'],
            ];

            if ($isUpdate) {
                $params['id'] = $data['id'];
            }

            $success = $stmt->execute($params);
            $db->commit();
            return $success;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $_SESSION['error_message'] = "Erreur lors de l'enregistrement : " . $e->getMessage();
            return false;
        }
    }

    /**
     * Atomically swap time slots & rooms between two course entries in a single transaction.
     */
    public static function swap($id1, $id2, $lycee_id = null) {
        $targetLyceeId = $lycee_id ?? Auth::getLyceeId();
        $db = Database::getInstance();

        try {
            $db->beginTransaction();

            $c1 = self::findById($id1, $targetLyceeId);
            $c2 = self::findById($id2, $targetLyceeId);

            if (!$c1 || !$c2) {
                $_SESSION['error_message'] = "Impossible de permuter : un ou plusieurs cours introuvables.";
                $db->rollBack();
                return false;
            }

            if ($c1['annee_academique_id'] != $c2['annee_academique_id']) {
                $_SESSION['error_message'] = "Impossible de permuter deux cours d'années académiques différentes.";
                $db->rollBack();
                return false;
            }

            // Target payload for course 1 (gets course 2 timing/room)
            $p1 = [
                'id' => $c1['id'],
                'classe_id' => $c1['classe_id'],
                'matiere_id' => $c1['matiere_id'],
                'professeur_id' => $c1['professeur_id'],
                'annee_academique_id' => $c1['annee_academique_id'],
                'jour' => $c2['jour'],
                'heure_debut' => $c2['heure_debut'],
                'heure_fin' => $c2['heure_fin'],
                'salle_id' => $c2['salle_id'],
            ];

            // Target payload for course 2 (gets course 1 timing/room)
            $p2 = [
                'id' => $c2['id'],
                'classe_id' => $c2['classe_id'],
                'matiere_id' => $c2['matiere_id'],
                'professeur_id' => $c2['professeur_id'],
                'annee_academique_id' => $c2['annee_academique_id'],
                'jour' => $c1['jour'],
                'heure_debut' => $c1['heure_debut'],
                'heure_fin' => $c1['heure_fin'],
                'salle_id' => $c1['salle_id'],
            ];

            // Check conflict for course 1 excluding both swapped entries
            $err1 = self::checkConflict($p1, [$c1['id'], $c2['id']], $targetLyceeId);
            if ($err1 !== false) {
                $_SESSION['error_message'] = "Permutation impossible : " . $err1;
                $db->rollBack();
                return false;
            }

            // Check conflict for course 2 excluding both swapped entries
            $err2 = self::checkConflict($p2, [$c1['id'], $c2['id']], $targetLyceeId);
            if ($err2 !== false) {
                $_SESSION['error_message'] = "Permutation impossible : " . $err2;
                $db->rollBack();
                return false;
            }

            // Execute atomic updates
            $upSql = "UPDATE emploi_du_temps SET jour = :jour, heure_debut = :heure_debut, heure_fin = :heure_fin, salle_id = :salle_id WHERE id = :id AND lycee_id = :lycee_id";
            $upStmt = $db->prepare($upSql);

            $upStmt->execute([
                'jour' => $p1['jour'],
                'heure_debut' => $p1['heure_debut'],
                'heure_fin' => $p1['heure_fin'],
                'salle_id' => $p1['salle_id'],
                'id' => $c1['id'],
                'lycee_id' => $targetLyceeId,
            ]);

            $upStmt->execute([
                'jour' => $p2['jour'],
                'heure_debut' => $p2['heure_debut'],
                'heure_fin' => $p2['heure_fin'],
                'salle_id' => $p2['salle_id'],
                'id' => $c2['id'],
                'lycee_id' => $targetLyceeId,
            ]);

            $db->commit();
            return true;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $_SESSION['error_message'] = "Erreur lors de la permutation : " . $e->getMessage();
            return false;
        }
    }

    public static function delete($id, $lycee_id = null) {
        $db = Database::getInstance();
        $sql = "DELETE FROM emploi_du_temps WHERE id = :id";
        $params = ['id' => $id];
        if ($lycee_id !== null) {
            $sql .= " AND lycee_id = :lycee_id";
            $params['lycee_id'] = $lycee_id;
        }
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }
}
?>
