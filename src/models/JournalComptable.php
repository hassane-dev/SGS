<?php

require_once __DIR__ . '/../config/database.php';

class JournalComptable {

    /**
     * Logs an operation in the journal comptable.
     *
     * @param array $data
     * @return int|bool
     */
    public static function log($data) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO journal_comptable (lycee_id, eleve_id, user_id, annee_academique_id, operation, montant, mode_paiement, recu_numero, reference_origine)
            VALUES (:lycee_id, :eleve_id, :user_id, :annee_academique_id, :operation, :montant, :mode_paiement, :recu_numero, :reference_origine)
        ");

        $stmt->execute([
            'lycee_id' => $data['lycee_id'],
            'eleve_id' => $data['eleve_id'] ?? null,
            'user_id' => $data['user_id'],
            'annee_academique_id' => $data['annee_academique_id'],
            'operation' => $data['operation'],
            'montant' => $data['montant'],
            'mode_paiement' => $data['mode_paiement'] ?? null,
            'recu_numero' => $data['recu_numero'] ?? null,
            'reference_origine' => $data['reference_origine'] ?? null
        ]);

        return $db->lastInsertId();
    }

    /**
     * Fetches all entries from the journal comptable with optional filtering.
     *
     * @param int $lycee_id
     * @param array $filters
     * @return array
     */
    public static function findAll($lycee_id, $filters = []) {
        $db = Database::getInstance();

        $sql = "
            SELECT j.*,
                   e.nom as eleve_nom, e.prenom as eleve_prenom, e.identifiant_public as eleve_matricule,
                   u.nom as user_nom, u.prenom as user_prenom,
                   c.niveau as classe_niveau, c.serie as classe_serie, c.numero as classe_numero, cy.nom_cycle
            FROM journal_comptable j
            LEFT JOIN eleves e ON j.eleve_id = e.id_eleve
            LEFT JOIN etudes et ON j.eleve_id = et.eleve_id AND j.annee_academique_id = et.annee_academique_id
            LEFT JOIN classes c ON et.classe_id = c.id_classe
            LEFT JOIN cycles cy ON c.cycle_id = cy.id_cycle
            JOIN utilisateurs u ON j.user_id = u.id_user
            WHERE j.lycee_id = :lycee_id
        ";

        $params = ['lycee_id' => $lycee_id];

        if (!empty($filters['annee_academique_id'])) {
            $sql .= " AND j.annee_academique_id = :annee_academique_id";
            $params['annee_academique_id'] = $filters['annee_academique_id'];
        }
        if (!empty($filters['cycle_id'])) {
            $sql .= " AND c.cycle_id = :cycle_id";
            $params['cycle_id'] = $filters['cycle_id'];
        }
        if (!empty($filters['niveau'])) {
            $sql .= " AND c.niveau = :niveau";
            $params['niveau'] = $filters['niveau'];
        }
        if (!empty($filters['serie'])) {
            $sql .= " AND c.serie = :serie";
            $params['serie'] = $filters['serie'];
        }
        if (!empty($filters['numero'])) {
            $sql .= " AND c.numero = :numero";
            $params['numero'] = $filters['numero'];
        }
        if (!empty($filters['date_debut'])) {
            $sql .= " AND DATE(j.date_creation) >= :date_debut";
            $params['date_debut'] = $filters['date_debut'];
        }
        if (!empty($filters['date_fin'])) {
            $sql .= " AND DATE(j.date_creation) <= :date_fin";
            $params['date_fin'] = $filters['date_fin'];
        }
        if (!empty($filters['operation'])) {
            $sql .= " AND j.operation = :operation";
            $params['operation'] = $filters['operation'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (
                e.nom LIKE :s1
                OR e.prenom LIKE :s2
                OR j.recu_numero LIKE :s3
                OR e.identifiant_public LIKE :s4
            )";
            $s = "%" . $filters['search'] . "%";
            $params['s1'] = $s;
            $params['s2'] = $s;
            $params['s3'] = $s;
            $params['s4'] = $s;
        }

        $sql .= " ORDER BY j.date_creation DESC, j.id DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
