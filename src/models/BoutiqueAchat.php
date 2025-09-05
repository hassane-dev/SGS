<?php

require_once __DIR__ . '/../config/database.php';

class BoutiqueAchat {

    public static function findByEleveId($eleve_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT ba.*, b.nom_article, b.prix
            FROM boutique_achats ba
            JOIN boutique_articles b ON ba.article_id = b.id_article
            WHERE ba.eleve_id = :eleve_id
            ORDER BY ba.date_achat DESC
        ");
        $stmt->execute(['eleve_id' => $eleve_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create($data) {
        $db = Database::getInstance();
        $sql = "INSERT INTO boutique_achats (eleve_id, article_id, quantite, date_achat)
                VALUES (:eleve_id, :article_id, :quantite, NOW())";

        $stmt = $db->prepare($sql);
        $success = $stmt->execute([
            'eleve_id' => $data['eleve_id'],
            'article_id' => $data['article_id'],
            'quantite' => $data['quantite'],
        ]);

        // Also create a corresponding payment record
        if ($success) {
            require_once __DIR__ . '/Paiement.php';
            require_once __DIR__ . '/BoutiqueArticle.php';
            $article = BoutiqueArticle::findById($data['article_id']);
            $total_price = $article['prix'] * $data['quantite'];
            Paiement::create([
                'eleve_id' => $data['eleve_id'],
                'type_paiement' => 'boutique',
                'montant' => $total_price,
                'statut' => 'non_paye' // Default to non_paye, to be updated by cashier
            ]);
        }

        return $success;
    }
}
?>
