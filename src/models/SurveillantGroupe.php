<?php

require_once __DIR__ . '/../config/database.php';

class SurveillantGroupe {

    public static function findById(int $id): ?array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM surveillant_groupes WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    public static function findByLyceeAndAnnee(int $lycee_id, int $annee_id): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT sg.*,
                   (SELECT COUNT(*) FROM surveillant_groupe_classes sgc WHERE sgc.groupe_id = sg.id) as total_classes
            FROM surveillant_groupes sg
            WHERE sg.lycee_id = :lycee_id AND sg.annee_academique_id = :annee_id
            ORDER BY sg.nom_groupe ASC
        ");
        $stmt->execute(['lycee_id' => $lycee_id, 'annee_id' => $annee_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getClassesForGroup(int $groupe_id): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT c.*, cy.nom_cycle
            FROM surveillant_groupe_classes sgc
            JOIN classes c ON sgc.classe_id = c.id_classe
            JOIN cycles cy ON c.cycle_id = cy.id_cycle
            WHERE sgc.groupe_id = :groupe_id
            ORDER BY c.niveau ASC, c.serie ASC, c.numero ASC, c.id_classe ASC
        ");
        $stmt->execute(['groupe_id' => $groupe_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getClassIdsForGroup(int $groupe_id): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT classe_id FROM surveillant_groupe_classes WHERE groupe_id = :groupe_id");
        $stmt->execute(['groupe_id' => $groupe_id]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public static function save(array $data): int {
        $db = Database::getInstance();
        $id = !empty($data['id']) ? (int)$data['id'] : null;

        $lycee_id = (int)$data['lycee_id'];
        $annee_id = (int)$data['annee_academique_id'];
        $nom_groupe = trim($data['nom_groupe'] ?? '');
        $description = !empty($data['description']) ? trim($data['description']) : null;
        $type_groupe = !empty($data['type_groupe']) ? $data['type_groupe'] : 'personnalise';
        $niveau = !empty($data['niveau']) ? trim($data['niveau']) : null;
        $cycle_id = !empty($data['cycle_id']) ? (int)$data['cycle_id'] : null;

        if ($id) {
            $stmt = $db->prepare("
                UPDATE surveillant_groupes
                SET lycee_id = :lycee_id,
                    annee_academique_id = :annee_id,
                    nom_groupe = :nom_groupe,
                    description = :description,
                    type_groupe = :type_groupe,
                    niveau = :niveau,
                    cycle_id = :cycle_id
                WHERE id = :id
            ");
            $stmt->execute([
                'lycee_id' => $lycee_id,
                'annee_id' => $annee_id,
                'nom_groupe' => $nom_groupe,
                'description' => $description,
                'type_groupe' => $type_groupe,
                'niveau' => $niveau,
                'cycle_id' => $cycle_id,
                'id' => $id
            ]);
            return $id;
        } else {
            $stmt = $db->prepare("
                INSERT INTO surveillant_groupes (lycee_id, annee_academique_id, nom_groupe, description, type_groupe, niveau, cycle_id)
                VALUES (:lycee_id, :annee_id, :nom_groupe, :description, :type_groupe, :niveau, :cycle_id)
            ");
            $stmt->execute([
                'lycee_id' => $lycee_id,
                'annee_id' => $annee_id,
                'nom_groupe' => $nom_groupe,
                'description' => $description,
                'type_groupe' => $type_groupe,
                'niveau' => $niveau,
                'cycle_id' => $cycle_id
            ]);
            return (int)$db->lastInsertId();
        }
    }

    public static function syncClasses(int $groupe_id, array $classe_ids): void {
        $db = Database::getInstance();
        $stmtDel = $db->prepare("DELETE FROM surveillant_groupe_classes WHERE groupe_id = :groupe_id");
        $stmtDel->execute(['groupe_id' => $groupe_id]);

        $stmtIns = $db->prepare("INSERT INTO surveillant_groupe_classes (groupe_id, classe_id) VALUES (:groupe_id, :classe_id)");
        foreach ($classe_ids as $cid) {
            $cid = (int)$cid;
            if ($cid > 0) {
                $stmtIns->execute(['groupe_id' => $groupe_id, 'classe_id' => $cid]);
            }
        }
    }

    public static function delete(int $id): bool {
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM surveillant_groupes WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
