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
            "SELECT mois_ou_sequence, montant_verse, reste_a_payer, id_mensualite
             FROM mensualites
             WHERE etude_id = :etude_id"
        );
        $stmt->execute(['etude_id' => $etudeId]);

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $payments = [];
        foreach ($result as $row) {
            $payments[$row['mois_ou_sequence']] = [
                'total' => $row['montant_verse'],
                'reste' => $row['reste_a_payer'],
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
     * Calcule automatiquement le reste à payer si montant_attendu est fourni.
     */
    public static function findOrCreate($data) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT id_mensualite, montant_verse FROM mensualites WHERE etude_id = :etude_id AND mois_ou_sequence = :mois");
        $stmt->execute(['etude_id' => $data['etude_id'], 'mois' => $data['mois_ou_sequence']]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $id = $existing['id_mensualite'];
            $nouveauTotal = (float)$existing['montant_verse'] + (float)$data['montant_verse'];
            $reste = 0;
            if (isset($data['montant_attendu'])) {
                $reste = max(0, (float)$data['montant_attendu'] - $nouveauTotal);
            } else {
                $reste = $data['reste_a_payer'] ?? 0;
            }

            $stmt = $db->prepare("UPDATE mensualites SET montant_verse = :total, reste_a_payer = :reste WHERE id_mensualite = :id");
            $stmt->execute([
                'total' => $nouveauTotal,
                'reste' => $reste,
                'id' => $id
            ]);
            return $id;
        } else {
            if (isset($data['montant_attendu'])) {
                $data['reste_a_payer'] = max(0, (float)$data['montant_attendu'] - (float)$data['montant_verse']);
            }
            return self::save($data);
        }
    }

    /**
     * Enregistre un paiement mensuel.
     */
    public static function save($data) {
        $db = Database::getInstance();

        $stmt = $db->prepare(
            "INSERT INTO mensualites (etude_id, eleve_id, classe_id, lycee_id, annee_academique_id, mois_ou_sequence, montant_verse, reste_a_payer, user_id)
             VALUES (:etude_id, :eleve_id, :classe_id, :lycee_id, :annee_academique_id, :mois_ou_sequence, :montant_verse, :reste_a_payer, :user_id)"
        );

        $stmt->execute([
            'etude_id' => $data['etude_id'] ?? null,
            'eleve_id' => $data['eleve_id'],
            'classe_id' => $data['classe_id'],
            'lycee_id' => $data['lycee_id'],
            'annee_academique_id' => $data['annee_academique_id'],
            'mois_ou_sequence' => $data['mois_ou_sequence'],
            'montant_verse' => $data['montant_verse'],
            'reste_a_payer' => $data['reste_a_payer'] ?? 0,
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
            INSERT INTO mensualite_details (mensualite_id, montant, mode_paiement, reference_transaction, recu_numero)
            VALUES (:mensualite_id, :montant, :mode_paiement, :reference_transaction, :recu_numero)
        ");
        return $stmt->execute([
            'mensualite_id' => $data['mensualite_id'],
            'montant' => $data['montant'],
            'mode_paiement' => $data['mode_paiement'] ?? null,
            'reference_transaction' => $data['reference_transaction'] ?? null,
            'recu_numero' => $data['recu_numero'] ?? null
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

    /**
     * Updates a payment detail status and recalculates the parent mensualite.
     */
    public static function updateStatusAndRecalculate($detailId, $newStatus) {
        $db = Database::getInstance();

        // 1. Get the detail info
        $stmt = $db->prepare("SELECT * FROM mensualite_details WHERE id = :id");
        $stmt->execute(['id' => $detailId]);
        $detail = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$detail) return false;

        // 2. Set new status on the detail
        $stmt = $db->prepare("UPDATE mensualite_details SET statut = :status WHERE id = :id");
        $stmt->execute(['status' => $newStatus, 'id' => $detailId]);

        // 3. Recalculate parent mensualite
        $mensualiteId = $detail['mensualite_id'];

        // Sum of all 'valide' details for this mensualite
        $stmt = $db->prepare("SELECT SUM(montant) FROM mensualite_details WHERE mensualite_id = :m_id AND statut = 'valide'");
        $stmt->execute(['m_id' => $mensualiteId]);
        $totalValide = (float)$stmt->fetchColumn() ?: 0.00;

        // Get the expected amount of the mensualite
        $stmt = $db->prepare("SELECT * FROM mensualites WHERE id_mensualite = :m_id");
        $stmt->execute(['m_id' => $mensualiteId]);
        $mensualite = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$mensualite) return false;

        $expected = (float)$mensualite['montant_verse'] + (float)$mensualite['reste_a_payer'];
        $newReste = max(0.00, $expected - $totalValide);

        // Update parent mensualite
        $stmt = $db->prepare("UPDATE mensualites SET montant_verse = :total, reste_a_payer = :reste WHERE id_mensualite = :m_id");
        return $stmt->execute([
            'total' => $totalValide,
            'reste' => $newReste,
            'm_id' => $mensualiteId
        ]);
    }
}
