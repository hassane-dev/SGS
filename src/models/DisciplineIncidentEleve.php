<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Auth.php';

class DisciplineIncidentEleve {

    public static function findByIncidentId($incidentId) {
        $db = Database::getInstance();
        $sql = "SELECT ie.*, e.nom, e.prenom, c.niveau as nom_niveau, c.serie as nom_serie, c.numero as nom_numero
                FROM discipline_incident_eleves ie
                JOIN eleves e ON ie.eleve_id = e.id_eleve
                JOIN classes c ON ie.classe_id = c.id_classe
                WHERE ie.incident_id = :incident_id
                ORDER BY ie.id ASC";

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute(['incident_id' => $incidentId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in DisciplineIncidentEleve::findByIncidentId: " . $e->getMessage());
            return [];
        }
    }
}
?>