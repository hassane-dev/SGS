<?php

require_once __DIR__ . '/../models/Eleve.php';
require_once __DIR__ . '/../models/Etude.php';
require_once __DIR__ . '/../models/Frais.php';
require_once __DIR__ . '/../models/Inscription.php';
require_once __DIR__ . '/../models/Mensualite.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';

class ComptableController {

    private function checkAccess() {
        if (!Auth::can('manage', 'paiement')) {
            http_response_code(403);
            View::render('errors/403');
            exit();
        }
    }

    public function index() {
        $this->checkAccess();
        $lycee_id = Auth::getLyceeId();
        $activeYear = AnneeAcademique::findActive();

        if (!$activeYear) {
            die("Aucune année académique active.");
        }

        $db = Database::getInstance();

        // 1. Total Inscriptions collectées
        $stmt = $db->prepare("SELECT SUM(montant_verse) FROM inscriptions WHERE lycee_id = :lycee_id AND annee_academique_id = :annee_id");
        $stmt->execute(['lycee_id' => $lycee_id, 'annee_id' => $activeYear['id']]);
        $totalInscriptions = $stmt->fetchColumn() ?: 0;

        // 2. Total Mensualités collectées
        $stmt = $db->prepare("SELECT SUM(montant_verse) FROM mensualites WHERE lycee_id = :lycee_id AND annee_academique_id = :annee_id");
        $stmt->execute(['lycee_id' => $lycee_id, 'annee_id' => $activeYear['id']]);
        $totalMensualites = $stmt->fetchColumn() ?: 0;

        // 3. Arriérés (reste à payer sur inscriptions)
        $stmt = $db->prepare("SELECT SUM(reste_a_payer) FROM inscriptions WHERE lycee_id = :lycee_id AND annee_academique_id = :annee_id");
        $stmt->execute(['lycee_id' => $lycee_id, 'annee_id' => $activeYear['id']]);
        $arrieresInscriptions = $stmt->fetchColumn() ?: 0;

        // 4. Dernières transactions
        $stmt = $db->prepare("
            (SELECT 'Inscription' as type, montant_verse, date_inscription as date, eleve_id, user_id
             FROM inscriptions
             WHERE lycee_id = :lycee_id)
            UNION ALL
            (SELECT 'Mensualité' as type, montant_verse, date_paiement as date, eleve_id, user_id
             FROM mensualites
             WHERE lycee_id = :lycee_id)
            ORDER BY date DESC LIMIT 10
        ");
        $stmt->execute(['lycee_id' => $lycee_id]);
        $recentTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Enrich transactions with student and user names
        foreach ($recentTransactions as &$t) {
            $e = Eleve::findById($t['eleve_id']);
            $t['eleve_nom'] = $e['prenom'] . ' ' . $e['nom'];
        }

        View::render('comptable/index', [
            'totalInscriptions' => $totalInscriptions,
            'totalMensualites' => $totalMensualites,
            'totalGlobal' => $totalInscriptions + $totalMensualites,
            'arrieresInscriptions' => $arrieresInscriptions,
            'recentTransactions' => $recentTransactions,
            'title' => 'Tableau de bord Comptable'
        ]);
    }

    public function listPending() {
        $this->checkAccess();
        $lycee_id = Auth::getLyceeId();
        $eleves_en_attente = Eleve::findByStatus('en_attente', $lycee_id);

        View::render('comptable/pending', [
            'eleves' => $eleves_en_attente,
            'title' => 'Inscriptions en Attente'
        ]);
    }
}
