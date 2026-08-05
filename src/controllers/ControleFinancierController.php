<?php

require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/EtatFinancierEleve.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';

class ControleFinancierController {

    private function checkAccess($action = 'view', $resource = 'paiement') {
        if (!Auth::can($action, $resource)) {
            if (PHP_SAPI === 'cli') {
                throw new Exception("FORBIDDEN");
            }
            http_response_code(403);
            View::render('errors/403');
            exit();
        }
    }

    private function redirect($url) {
        if (PHP_SAPI === 'cli') {
            throw new Exception("REDIRECT: " . $url);
        }
        header('Location: ' . $url);
        exit();
    }

    public function index() {
        $this->checkAccess('view');
        $lycee_id = Auth::getLyceeId();
        $activeYear = AnneeAcademique::findActive();

        if (!$activeYear) {
            $_SESSION['error_message'] = _("Aucune année académique active.");
            $this->redirect('/');
        }

        $db = Database::getInstance();

        // Fetch all students enrolled in the active year
        $stmt = $db->prepare("
            SELECT e.id_eleve, e.nom, e.prenom, c.niveau, c.serie, c.numero,
                   efe.inscription_statut, efe.mensualite_statut, efe.notes_consultation, efe.bulletin_impression,
                   pfe.type_avantage, pfe.valeur_type, pfe.valeur
            FROM eleves e
            JOIN etudes et ON e.id_eleve = et.eleve_id
            JOIN classes c ON et.classe_id = c.id_classe
            LEFT JOIN etats_financiers_eleves efe ON e.id_eleve = efe.eleve_id
            LEFT JOIN parametres_financiers_eleves pfe ON e.id_eleve = pfe.eleve_id
            WHERE e.lycee_id = :lycee_id AND et.annee_academique_id = :annee_id
            ORDER BY e.nom, e.prenom
        ");
        $stmt->execute(['lycee_id' => $lycee_id, 'annee_id' => $activeYear['id']]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Recalculate if any student has no cached state
        foreach ($students as &$s) {
            if (empty($s['inscription_statut'])) {
                $state = EtatFinancierEleve::recalculateState($s['id_eleve']);
                $s['inscription_statut'] = $state['inscription_statut'] ?? 'Impayée';
                $s['mensualite_statut'] = $state['mensualite_statut'] ?? 'En retard';
                $s['notes_consultation'] = $state['notes_consultation'] ?? 'Interdite';
                $s['bulletin_impression'] = $state['bulletin_impression'] ?? 'Interdite';
            }
        }
        unset($s);

        // Compute statistics
        $stats = [
            'total_students' => count($students),
            'advantages_count' => 0,
            'blocked_notes_count' => 0,
            'blocked_bulletins_count' => 0
        ];

        foreach ($students as $s) {
            if (!empty($s['type_avantage']) && $s['type_avantage'] !== 'Aucun') {
                $stats['advantages_count']++;
            }
            if (($s['notes_consultation'] ?? '') === 'Interdite') {
                $stats['blocked_notes_count']++;
            }
            if (($s['bulletin_impression'] ?? '') === 'Interdite') {
                $stats['blocked_bulletins_count']++;
            }
        }

        View::render('comptabilite/controle_financier/index', [
            'title' => _("Contrôle Financier & Droits d'Accès"),
            'students' => $students,
            'stats' => $stats,
            'activeYear' => $activeYear
        ]);
    }
}
?>