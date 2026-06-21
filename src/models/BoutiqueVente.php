<?php

require_once __DIR__ . '/../config/database.php';

class BoutiqueVente {

    public static function create($data) {
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            // 1. Create the sale record
            $sqlVente = "INSERT INTO boutique_ventes (eleve_id, lycee_id, user_id, montant_total, recu_numero, date_vente)
                         VALUES (:eleve_id, :lycee_id, :user_id, :montant_total, :recu_numero, NOW())";

            $stmtVente = $db->prepare($sqlVente);
            $stmtVente->execute([
                'eleve_id' => $data['eleve_id'],
                'lycee_id' => $data['lycee_id'],
                'user_id' => Auth::getUserId(),
                'montant_total' => $data['montant_total'],
                'recu_numero' => self::generateReceiptNumber(),
            ]);

            $vente_id = $db->lastInsertId();

            // 2. Process each item in the cart
            foreach ($data['cart'] as $item) {
                // Insert into boutique_achats
                $sqlAchat = "INSERT INTO boutique_achats (vente_id, eleve_id, article_id, quantite, prix_unitaire, date_achat)
                             VALUES (:vente_id, :eleve_id, :article_id, :quantite, :prix_unitaire, NOW())";
                $stmtAchat = $db->prepare($sqlAchat);
                $stmtAchat->execute([
                    'vente_id' => $vente_id,
                    'eleve_id' => $data['eleve_id'],
                    'article_id' => $item['id'],
                    'quantite' => $item['quantity'],
                    'prix_unitaire' => $item['price'],
                ]);

                // Decrement stock
                $sqlStock = "UPDATE boutique_articles SET stock = stock - :qty WHERE id_article = :id";
                $stmtStock = $db->prepare($sqlStock);
                $stmtStock->execute([
                    'qty' => $item['quantity'],
                    'id' => $item['id'],
                ]);
            }

            // 3. Optional: Create a general payment record for accounting
            // Assuming Paiement.php might exist in some installations as mentioned in memories.
            // Using a safe check.
            if (file_exists(__DIR__ . '/Paiement.php')) {
                require_once __DIR__ . '/Paiement.php';
                Paiement::create([
                    'eleve_id' => $data['eleve_id'],
                    'type_paiement' => 'boutique',
                    'montant' => $data['montant_total'],
                    'statut' => 'paye', // Shop sales are usually immediate
                    'date_paiement' => date('Y-m-d H:i:s'),
                ]);
            }

            $db->commit();
            return $vente_id;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    private static function generateReceiptNumber() {
        $db = Database::getInstance();
        // Simple receipt generation logic, similar to what's described in memories
        $stmt = $db->query("SELECT MAX(id_vente) as max_id FROM boutique_ventes");
        $row = $stmt->fetch();
        $next_id = ($row['max_id'] ?? 0) + 1;
        return 'REC-B-' . str_pad($next_id, 6, '0', STR_PAD_LEFT);
    }

    public static function findById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT bv.*, e.nom as eleve_nom, e.prenom as eleve_prenom, u.nom as user_nom, u.prenom as user_prenom
            FROM boutique_ventes bv
            JOIN eleves e ON bv.eleve_id = e.id_eleve
            JOIN utilisateurs u ON bv.user_id = u.id_user
            WHERE bv.id_vente = :id
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function getItems($vente_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT ba.*, art.nom_article
            FROM boutique_achats ba
            JOIN boutique_articles art ON ba.article_id = art.id_article
            WHERE ba.vente_id = :vente_id
        ");
        $stmt->execute(['vente_id' => $vente_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findByLycee($lycee_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT bv.*, e.nom as eleve_nom, e.prenom as eleve_prenom
            FROM boutique_ventes bv
            JOIN eleves e ON bv.eleve_id = e.id_eleve
            WHERE bv.lycee_id = :lycee_id
            ORDER BY bv.date_vente DESC
        ");
        $stmt->execute(['lycee_id' => $lycee_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getStats($lycee_id, $period = 'day') {
        $db = Database::getInstance();
        $sql = "SELECT SUM(montant_total) as revenue, COUNT(*) as count FROM boutique_ventes WHERE lycee_id = :lycee_id";
        if ($period === 'day') {
            $sql .= " AND DATE(date_vente) = CURDATE()";
        } elseif ($period === 'month') {
            $sql .= " AND MONTH(date_vente) = MONTH(CURDATE()) AND YEAR(date_vente) = YEAR(CURDATE())";
        }
        $stmt = $db->prepare($sql);
        $stmt->execute(['lycee_id' => $lycee_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
