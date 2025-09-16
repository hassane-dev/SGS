<?php

require_once __DIR__ . '/../config/database.php';

class CahierTexte {

    public static function findByTeacher($professeur_id, $lycee_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT ct.*, c.nom_classe, m.nom_matiere
            FROM cahier_texte ct
            JOIN classes c ON ct.classe_id = c.id_classe
            JOIN matieres m ON ct.matiere_id = m.id_matiere
            WHERE ct.professeur_id = :professeur_id AND ct.lycee_id = :lycee_id
            ORDER BY ct.date_cours DESC
        ");
        $stmt->execute(['professeur_id' => $professeur_id, 'lycee_id' => $lycee_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create($data) {
        $db = Database::getInstance();
        $sql = "INSERT INTO cahier_texte (professeur_id, classe_id, matiere_id, date_cours, heure_debut, heure_fin, contenu_cours, exercices, lycee_id)
                VALUES (:professeur_id, :classe_id, :matiere_id, :date_cours, :heure_debut, :heure_fin, :contenu_cours, :exercices, :lycee_id)";

        $stmt = $db->prepare($sql);
        return $stmt->execute([
            'professeur_id' => $data['professeur_id'],
            'classe_id' => $data['classe_id'],
            'matiere_id' => $data['matiere_id'],
            'date_cours' => $data['date_cours'],
            'heure_debut' => $data['heure_debut'] ?: null,
            'heure_fin' => $data['heure_fin'] ?: null,
            'contenu_cours' => $data['contenu_cours'] ?: null,
            'exercices' => $data['exercices'] ?: null,
            'lycee_id' => $data['lycee_id'],
        ]);
    }

    public static function delete($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM cahier_texte WHERE id_cahier = :id");
        return $stmt->execute(['id' => $id]);
    }
}
?>
