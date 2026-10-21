<?php

require_once __DIR__ . '/../config/database.php';

class SurveillantAffectation {

    public static function findById(int $id): ?array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM surveillant_affectations WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    public static function findActiveByLyceeAndAnnee(int $lycee_id, int $annee_id): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT sa.*,
                   u.nom as surveillant_nom, u.prenom as surveillant_prenom, u.email as surveillant_email,
                   c.niveau as classe_niveau, c.serie as classe_serie, c.numero as classe_numero,
                   sg.nom_groupe,
                   cy.nom_cycle
            FROM surveillant_affectations sa
            LEFT JOIN utilisateurs u ON sa.surveillant_id = u.id_user
            LEFT JOIN classes c ON sa.classe_id = c.id_classe
            LEFT JOIN surveillant_groupes sg ON sa.groupe_id = sg.id
            LEFT JOIN cycles cy ON sa.cycle_id = cy.id_cycle
            WHERE sa.lycee_id = :lycee_id
              AND sa.annee_academique_id = :annee_id
              AND sa.statut = 'actif'
            ORDER BY sa.type_responsabilite DESC, u.nom ASC, u.prenom ASC, sa.id ASC
        ");
        $stmt->execute(['lycee_id' => $lycee_id, 'annee_id' => $annee_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findAllByLyceeAndAnnee(int $lycee_id, int $annee_id): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT sa.*,
                   u.nom as surveillant_nom, u.prenom as surveillant_prenom, u.email as surveillant_email,
                   c.niveau as classe_niveau, c.serie as classe_serie, c.numero as classe_numero,
                   sg.nom_groupe,
                   cy.nom_cycle
            FROM surveillant_affectations sa
            LEFT JOIN utilisateurs u ON sa.surveillant_id = u.id_user
            LEFT JOIN classes c ON sa.classe_id = c.id_classe
            LEFT JOIN surveillant_groupes sg ON sa.groupe_id = sg.id
            LEFT JOIN cycles cy ON sa.cycle_id = cy.id_cycle
            WHERE sa.lycee_id = :lycee_id
              AND sa.annee_academique_id = :annee_id
            ORDER BY sa.statut ASC, sa.type_responsabilite DESC, sa.id DESC
        ");
        $stmt->execute(['lycee_id' => $lycee_id, 'annee_id' => $annee_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findActiveForUser(int $user_id, int $annee_id): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT sa.*
            FROM surveillant_affectations sa
            WHERE sa.surveillant_id = :user_id
              AND sa.annee_academique_id = :annee_id
              AND sa.statut = 'actif'
        ");
        $stmt->execute(['user_id' => $user_id, 'annee_id' => $annee_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function save(array $data): int {
        $db = Database::getInstance();
        $id = !empty($data['id']) ? (int)$data['id'] : null;

        $lycee_id = (int)$data['lycee_id'];
        $annee_id = (int)$data['annee_academique_id'];
        $surveillant_id = !empty($data['surveillant_id']) ? (int)$data['surveillant_id'] : null;
        $type_resp = $data['type_responsabilite'] ?? 'surveillant_classe';
        $portee_type = $data['portee_type'];
        $classe_id = !empty($data['classe_id']) ? (int)$data['classe_id'] : null;
        $groupe_id = !empty($data['groupe_id']) ? (int)$data['groupe_id'] : null;
        $cycle_id = !empty($data['cycle_id']) ? (int)$data['cycle_id'] : null;
        $niveau_code = !empty($data['niveau_code']) ? trim($data['niveau_code']) : null;
        $date_debut = $data['date_debut'] ?? date('Y-m-d');
        $date_fin = !empty($data['date_fin']) ? $data['date_fin'] : null;
        $statut = $data['statut'] ?? 'actif';
        $created_by = !empty($data['created_by']) ? (int)$data['created_by'] : null;

        if ($id) {
            $stmt = $db->prepare("
                UPDATE surveillant_affectations
                SET lycee_id = :lycee_id,
                    annee_academique_id = :annee_id,
                    surveillant_id = :surveillant_id,
                    type_responsabilite = :type_resp,
                    portee_type = :portee_type,
                    classe_id = :classe_id,
                    groupe_id = :groupe_id,
                    cycle_id = :cycle_id,
                    niveau_code = :niveau_code,
                    date_debut = :date_debut,
                    date_fin = :date_fin,
                    statut = :statut
                WHERE id = :id
            ");
            $stmt->execute([
                'lycee_id' => $lycee_id,
                'annee_id' => $annee_id,
                'surveillant_id' => $surveillant_id,
                'type_resp' => $type_resp,
                'portee_type' => $portee_type,
                'classe_id' => $classe_id,
                'groupe_id' => $groupe_id,
                'cycle_id' => $cycle_id,
                'niveau_code' => $niveau_code,
                'date_debut' => $date_debut,
                'date_fin' => $date_fin,
                'statut' => $statut,
                'id' => $id
            ]);
            return $id;
        } else {
            $stmt = $db->prepare("
                INSERT INTO surveillant_affectations (
                    lycee_id, annee_academique_id, surveillant_id, type_responsabilite, portee_type,
                    classe_id, groupe_id, cycle_id, niveau_code, date_debut, date_fin, statut, created_by
                ) VALUES (
                    :lycee_id, :annee_id, :surveillant_id, :type_resp, :portee_type,
                    :classe_id, :groupe_id, :cycle_id, :niveau_code, :date_debut, :date_fin, :statut, :created_by
                )
            ");
            $stmt->execute([
                'lycee_id' => $lycee_id,
                'annee_id' => $annee_id,
                'surveillant_id' => $surveillant_id,
                'type_resp' => $type_resp,
                'portee_type' => $portee_type,
                'classe_id' => $classe_id,
                'groupe_id' => $groupe_id,
                'cycle_id' => $cycle_id,
                'niveau_code' => $niveau_code,
                'date_debut' => $date_debut,
                'date_fin' => $date_fin,
                'statut' => $statut,
                'created_by' => $created_by
            ]);
            return (int)$db->lastInsertId();
        }
    }
}
