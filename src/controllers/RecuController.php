<?php

require_once __DIR__ . '/../models/Eleve.php';
require_once __DIR__ . '/../models/Inscription.php';
require_once __DIR__ . '/../models/Mensualite.php';
require_once __DIR__ . '/../models/Lycee.php';
require_once __DIR__ . '/../models/ParamLycee.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/User.php';
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

    public function print() {
        if (!Auth::check()) {
            header('Location: /login');
            exit();
        }

        $recu_numero = $_GET['numero'] ?? null;
        if (!$recu_numero) {
            die("Numéro de reçu manquant.");
        }

        $db = Database::getInstance();

        // 1. Chercher si c'est une inscription
        $stmt = $db->prepare("SELECT * FROM inscriptions WHERE recu_numero = :num");
        $stmt->execute(['num' => $recu_numero]);
        $inscription = $stmt->fetch(PDO::FETCH_ASSOC);

        // 2. Chercher les mensualités
        $stmt = $db->prepare("
            SELECT md.*, m.mois_ou_sequence, m.eleve_id, m.classe_id, m.user_id, aa.libelle as annee_academique
            FROM mensualite_details md
            JOIN mensualites m ON md.mensualite_id = m.id_mensualite
            JOIN annees_academiques aa ON m.annee_academique_id = aa.id
            WHERE md.recu_numero = :num
        ");
        $stmt->execute(['num' => $recu_numero]);
        $mensualites = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$inscription && empty($mensualites)) {
            die("Reçu introuvable.");
        }

        // Récupérer l'élève (soit via inscription, soit via la première mensualité)
        $eleve_id = $inscription ? $inscription['eleve_id'] : $mensualites[0]['eleve_id'];
        $eleve = Eleve::findById($eleve_id);

        $user_id = $inscription ? $inscription['user_id'] : $mensualites[0]['user_id'];
        $caissier = User::findById($user_id);

        $classe_id = $inscription ? $inscription['classe_id'] : $mensualites[0]['classe_id'];
        $classe = Classe::findById($classe_id);

        $lycee = ParamLycee::findByLyceeId($eleve['lycee_id']);

        // Pour garantir un "reçu unique" de type inscription même si seule la scolarité est payée
        // On préfère la vue inscription.php qui est plus complète
        if (!$inscription && !empty($mensualites)) {
            // On récupère une inscription existante de l'élève pour peupler les infos (classe, année)
            $existingInscriptions = Inscription::findByEleveId($eleve_id);
            if (!empty($existingInscriptions)) {
                $inscription = $existingInscriptions[0];
                // On met à zéro les montants d'inscription pour ce reçu spécifique
                $inscription['montant_verse'] = 0;
                $inscription['reste_a_payer'] = $inscription['montant_total'] - $inscription['montant_verse']; // Approximatif mais suffisant
                $inscription['date_inscription'] = $mensualites[0]['date_paiement'];
                $inscription['recu_numero'] = $recu_numero;
            }
        }

        if ($inscription) {
            require_once __DIR__ . '/../views/recus/inscription.php';
        } else if (!empty($mensualites)) {
            $paiement = $mensualites[0];
            require_once __DIR__ . '/../views/recus/mensualite.php';
        } else {
            die("Reçu introuvable.");
        }
    }
}
