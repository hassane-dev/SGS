<?php

require_once __DIR__ . '/../config/database.php';

class Mensualite {

    /**
     * Trouve les paiements mensuels pour un élève et une année académique.
     */
    public static function findByEleveId($eleveId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT m.*, aa.libelle as annee_academique
            FROM mensualites m
            JOIN annees_academiques aa ON m.annee_academique_id = aa.id
            WHERE m.eleve_id = :eleve_id
            ORDER BY m.date_paiement DESC
        ");
        $stmt->execute(['eleve_id' => $eleveId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findByEtudeId($etudeId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT m.*, aa.libelle as annee_academique
            FROM mensualites m
            JOIN annees_academiques aa ON m.annee_academique_id = aa.id
            WHERE m.etude_id = :etude_id
            ORDER BY m.date_paiement DESC
        ");
        $stmt->execute(['etude_id' => $etudeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findByEleveAndAnnee($eleveId, $anneeId) {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT mois_ou_sequence, SUM(montant_verse) as total_verse
             FROM mensualites
             WHERE eleve_id = :eleve_id AND annee_academique_id = :annee_id
             GROUP BY mois_ou_sequence"
        );
        $stmt->execute(['eleve_id' => $eleveId, 'annee_id' => $anneeId]);

        // Retourne un tableau associatif [mois => total_verse]
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $payments = [];
        foreach ($result as $row) {
            $payments[$row['mois_ou_sequence']] = $row['total_verse'];
        }
        return $payments;
    }

    public static function findByEtude($etudeId) {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT mois_ou_sequence, SUM(montant_verse) as total_verse, id_mensualite
             FROM mensualites
             WHERE etude_id = :etude_id
             GROUP BY mois_ou_sequence"
        );
        $stmt->execute(['etude_id' => $etudeId]);

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $payments = [];
        foreach ($result as $row) {
            $payments[$row['mois_ou_sequence']] = [
                'total' => $row['total_verse'],
                'id' => $row['id_mensualite']
            ];
        }
        return $payments;
    }

    /**
     * Récupère les détails d'une mensualité (historique des paiements).
     */
    public static function getDetails($mensualiteId) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM mensualite_details WHERE mensualite_id = :mensualite_id ORDER BY date_paiement DESC");
        $stmt->execute(['mensualite_id' => $mensualiteId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Trouve ou crée une ligne de mensualité pour un mois donné.
     */
    public static function findOrCreate($data) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT id_mensualite FROM mensualites WHERE etude_id = :etude_id AND mois_ou_sequence = :mois");
        $stmt->execute(['etude_id' => $data['etude_id'], 'mois' => $data['mois_ou_sequence']]);
        $id = $stmt->fetchColumn();

        if ($id) {
            // Mettre à jour le montant total versé
            $stmt = $db->prepare("UPDATE mensualites SET montant_verse = montant_verse + :montant WHERE id_mensualite = :id");
            $stmt->execute(['montant' => $data['montant_verse'], 'id' => $id]);
            return $id;
        } else {
            return self::save($data);
        }
    }

    /**
     * Enregistre un paiement mensuel.
     */
    public static function save($data) {
        $db = Database::getInstance();

        $stmt = $db->prepare(
            "INSERT INTO mensualites (etude_id, eleve_id, classe_id, lycee_id, annee_academique_id, mois_ou_sequence, montant_verse, user_id)
             VALUES (:etude_id, :eleve_id, :classe_id, :lycee_id, :annee_academique_id, :mois_ou_sequence, :montant_verse, :user_id)"
        );

        $stmt->execute([
            'etude_id' => $data['etude_id'] ?? null,
            'eleve_id' => $data['eleve_id'],
            'classe_id' => $data['classe_id'],
            'lycee_id' => $data['lycee_id'],
            'annee_academique_id' => $data['annee_academique_id'],
            'mois_ou_sequence' => $data['mois_ou_sequence'],
            'montant_verse' => $data['montant_verse'],
            'user_id' => $data['user_id']
        ]);

        return $db->lastInsertId();
    }

    /**
     * Ajoute un détail de paiement à une mensualité.
     */
    public static function addDetail($data) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO mensualite_details (mensualite_id, montant, mode_paiement, reference_transaction, recu_numero, user_id)
            VALUES (:mensualite_id, :montant, :mode_paiement, :reference_transaction, :recu_numero, :user_id)
        ");
        return $stmt->execute([
            'mensualite_id' => $data['mensualite_id'],
            'montant' => $data['montant'],
            'mode_paiement' => $data['mode_paiement'] ?? null,
            'reference_transaction' => $data['reference_transaction'] ?? null,
            'recu_numero' => $data['recu_numero'] ?? null,
            'user_id' => $data['user_id'] ?? null
        ]);
    }

    /**
     * Génère automatiquement le prochain numéro de reçu.
     */
    public static function generateReceiptNumber($lyceeId) {
        $db = Database::getInstance();

        // Chercher le dernier numéro de reçu dans mensualite_details et inscriptions pour ce lycée
        $stmt = $db->prepare("
            SELECT recu_numero FROM (
                SELECT md.recu_numero
                FROM mensualite_details md
                JOIN mensualites m ON md.mensualite_id = m.id_mensualite
                WHERE m.lycee_id = :l1 AND md.recu_numero LIKE 'REC-%'
                UNION ALL
                SELECT recu_numero
                FROM inscriptions
                WHERE lycee_id = :l2 AND recu_numero LIKE 'REC-%'
            ) as t
            ORDER BY recu_numero DESC
            LIMIT 1
        ");
        $stmt->execute(['l1' => $lyceeId, 'l2' => $lyceeId]);
        $lastRecu = $stmt->fetchColumn();

        if ($lastRecu) {
            // Extraire le numéro (ex: REC-000125 -> 125)
            $number = (int) substr($lastRecu, 4);
            $nextNumber = $number + 1;
        } else {
            $nextNumber = 1;
        }

        return 'REC-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }
}
