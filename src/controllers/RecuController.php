<?php

require_once __DIR__ . '/../models/Eleve.php';
require_once __DIR__ . '/../models/Inscription.php';
require_once __DIR__ . '/../models/Mensualite.php';
require_once __DIR__ . '/../models/Lycee.php';
require_once __DIR__ . '/../models/ParamLycee.php';
require_once __DIR__ . '/../core/Auth.php';

class RecuController {

    public function showInscriptionRecu() {
        if (!Auth::check()) {
            header('Location: /login');
            exit();
        }

        $eleve_id = $_GET['id'] ?? null;
        if (!$eleve_id) {
            die("ID de l'élève manquant.");
        }

        $eleve = Eleve::findById($eleve_id);
        $inscriptions = Inscription::findByEleveId($eleve_id);
        if (empty($inscriptions)) {
            die("Aucune inscription trouvée pour cet élève.");
        }
        $inscription = $inscriptions[0];

        // Récupérer les mensualités liées au même reçu
        $mensualites = [];
        if (!empty($inscription['recu_numero'])) {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                SELECT md.*, m.mois_ou_sequence
                FROM mensualite_details md
                JOIN mensualites m ON md.mensualite_id = m.id_mensualite
                WHERE md.recu_numero = :recu
            ");
            $stmt->execute(['recu' => $inscription['recu_numero']]);
            $mensualites = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $lycee = ParamLycee::findByLyceeId($eleve['lycee_id']);
        $caissier = User::findById($inscription['user_id']);

        require_once __DIR__ . '/../views/recus/inscription.php';
    }

    public function showMensualiteRecu() {
        if (!Auth::check()) {
            header('Location: /login');
            exit();
        }

        $detail_id = $_GET['id'] ?? null;
        if (!$detail_id) {
            die("ID du paiement manquant.");
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT md.*, m.mois_ou_sequence, m.eleve_id, m.classe_id, m.user_id, aa.libelle as annee_academique
            FROM mensualite_details md
            JOIN mensualites m ON md.mensualite_id = m.id_mensualite
            JOIN annees_academiques aa ON m.annee_academique_id = aa.id
            WHERE md.id = :id
        ");
        $stmt->execute(['id' => $detail_id]);
        $paiement = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$paiement) {
            die("Paiement introuvable.");
        }

        $eleve = Eleve::findById($paiement['eleve_id']);
        $caissier = User::findById($paiement['user_id']);
        $classe = Classe::findById($paiement['classe_id']);
        $lycee = ParamLycee::findByLyceeId($eleve['lycee_id']);

        require_once __DIR__ . '/../views/recus/mensualite.php';
    }
}
