<?php

require_once __DIR__ . '/../config/database.php';

class TestEntree {

    public static function findByEleveId($eleve_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT te.*, c.nom_classe
            FROM tests_entree te
            JOIN classes c ON te.classe_visee_id = c.id_classe
            WHERE te.eleve_id = :eleve_id
            ORDER BY te.date_test DESC
        ");
        $stmt->execute(['eleve_id' => $eleve_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create($data) {
        $db = Database::getInstance();
        $sql = "INSERT INTO tests_entree (eleve_id, classe_visee_id, score, date_test)
                VALUES (:eleve_id, :classe_visee_id, :score, :date_test)";

        $stmt = $db->prepare($sql);
        return $stmt->execute([
            'eleve_id' => $data['eleve_id'],
            'classe_visee_id' => $data['classe_visee_id'],
            'score' => $data['score'] ?: null,
            'date_test' => $data['date_test'] ?: null,
        ]);
    }

    public static function delete($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM tests_entree WHERE id_test = :id");
        return $stmt->execute(['id' => $id]);
    }
}
?>
