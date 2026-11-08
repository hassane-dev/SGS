<?php

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/Eleve.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/Etude.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../models/ParamLycee.php';
require_once __DIR__ . '/../services/ReinscriptionService.php';
require_once __DIR__ . '/../services/AuthorizationScopeService.php';

class ReinscriptionController {

    private function checkAccess() {
        if (!Auth::can('reinscrire', 'eleve') && !Auth::can('manage', 'inscription')) {
            http_response_code(403);
            echo "Accès Interdit.";
            if (!defined('TEST_MODE')) exit();
            throw new LogicException("Accès Interdit.");
        }
    }

    public function index() {
        $this->checkAccess();
        require_once __DIR__ . '/../views/reinscription/index.php';
    }

    public function search() {
        $this->checkAccess();
        $term = $_POST['search_term'] ?? null;
        if (!$term) {
            header('Location: /reinscription');
            if (!defined('TEST_MODE')) exit();
            return;
        }

        $lycee_id = Auth::getLyceeId() ?: Auth::get('lycee_id');
        $eleves = Eleve::search($term, $lycee_id);

        require_once __DIR__ . '/../views/reinscription/results.php';
    }

    public function confirm() {
        $this->checkAccess();
        $eleve_id = !empty($_GET['eleve_id']) ? (int)$_GET['eleve_id'] : null;
        if (!$eleve_id) {
            header('Location: /reinscription');
            if (!defined('TEST_MODE')) exit();
            return;
        }

        $eleve = Eleve::findById($eleve_id);
        if (!$eleve) {
            header('Location: /reinscription');
            if (!defined('TEST_MODE')) exit();
            return;
        }

        $lycee_id = (int)$eleve['lycee_id'];

        // Sécurité Multi-tenant
        if (!AuthorizationScopeService::canAccessLycee($lycee_id)) {
            http_response_code(403);
            echo "Accès refusé pour cet établissement.";
            if (!defined('TEST_MODE')) exit();
            return;
        }

        // Année académique active contextualisée à l'établissement
        $active_year = AnneeAcademique::findActive($lycee_id) ?: AnneeAcademique::findActive();
        if (!$active_year) {
            $error_message = _("Aucune année académique active n'est configurée pour cet établissement.");
        }

        // 1. Inscription précédente
        $latestEnrollment = ReinscriptionService::getLatestEnrollment($eleve_id, $lycee_id);

        // 2. Décision pédagogique
        $pedagogicalSituation = ReinscriptionService::getPedagogicalSituation($eleve_id, $latestEnrollment);

        // 3. Détermination du niveau de destination
        $destination = ReinscriptionService::resolveDestination($pedagogicalSituation, $lycee_id);

        // 4. Recherche séquentielle des classes compatibles et calcul des capacités
        $targetNiveau = $destination['niveau'] ?? null;
        $targetSerie = $destination['serie'] ?? null;
        $targetCycleId = $destination['cycle_id'] ?? null;

        $availableData = null;
        $classes = [];
        $suggestedClasse = null;

        if ($destination['can_proceed'] && $targetNiveau && $active_year) {
            $availableData = ReinscriptionService::findAvailableClasses(
                $lycee_id,
                (int)$active_year['id'],
                $targetNiveau,
                $targetSerie,
                $targetCycleId
            );
            $classes = $availableData['classes'] ?? [];
            $suggestedClasse = $availableData['suggested_classe'] ?? null;
        } else {
            // Repli : charger l'ensemble des classes pour consultation
            $classes = Classe::findAll($lycee_id);
        }

        require_once __DIR__ . '/../views/reinscription/confirm.php';
    }

    public function process() {
        $this->checkAccess();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /reinscription');
            if (!defined('TEST_MODE')) exit();
            return;
        }

        $eleve_id = (int)($_POST['eleve_id'] ?? 0);
        $classe_id = (int)($_POST['classe_id'] ?? 0);
        $annee_id = (int)($_POST['annee_academique_id'] ?? 0);
        $derogation = !empty($_POST['derogation_capacite']);

        if (!$eleve_id || !$classe_id || !$annee_id) {
            header('Location: /reinscription');
            if (!defined('TEST_MODE')) exit();
            return;
        }

        $eleve = Eleve::findById($eleve_id);
        if (!$eleve) {
            header('Location: /reinscription');
            if (!defined('TEST_MODE')) exit();
            return;
        }

        $lycee_id = (int)$eleve['lycee_id'];
        if (!AuthorizationScopeService::canAccessLycee($lycee_id)) {
            http_response_code(403);
            echo "Accès refusé pour cet établissement.";
            if (!defined('TEST_MODE')) exit();
            return;
        }

        $targetClasse = Classe::findById($classe_id);
        if (!$targetClasse || (int)$targetClasse['lycee_id'] !== $lycee_id) {
            header('Location: /reinscription/confirm?eleve_id=' . $eleve_id . '&error=invalid_class');
            if (!defined('TEST_MODE')) exit();
            return;
        }

        // 1. Règle impérative : Le module de réinscription est exclusivement destiné aux élèves avec historique scolaire
        $latestEnrollment = ReinscriptionService::getLatestEnrollment($eleve_id, $lycee_id);
        if (!$latestEnrollment) {
            header('Location: /reinscription/confirm?eleve_id=' . $eleve_id . '&error=no_history');
            if (!defined('TEST_MODE')) exit();
            return;
        }

        $pedagogicalSituation = ReinscriptionService::getPedagogicalSituation($eleve_id, $latestEnrollment);
        $destination = ReinscriptionService::resolveDestination($pedagogicalSituation, $lycee_id);

        // Les blocages pédagogiques (exclu, délibération en cours, dossier incomplet, fin de secondaire)
        // sont ABSOLUS et ne peuvent JAMAIS être contournés par une dérogation de capacité
        if (!$destination['can_proceed']) {
            header('Location: /reinscription/confirm?eleve_id=' . $eleve_id . '&error=' . urlencode($destination['type'] ?? 'cannot_proceed'));
            if (!defined('TEST_MODE')) exit();
            return;
        }

        // Si une dérogation de capacité est invoquée, vérifier les droits RBAC côté serveur
        if ($derogation && !Auth::can('manage', 'inscription')) {
            http_response_code(403);
            echo "Accès refusé. Seul un gestionnaire habilité peut autoriser une dérogation de sur-effectif.";
            if (!defined('TEST_MODE')) exit();
            return;
        }

        $db = Database::getInstance();
        $isSqlite = ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite');

        // Début de transaction atomique pour sécuriser la concurrence sur les places et éviter les doublons
        $db->beginTransaction();

        try {
            // A. Verrou pessimiste commun au couple (élève, année académique)
            // Pour empêcher deux requêtes concurrentes de réinscrire le même élève dans deux classes différentes
            // nous verrouillons la ligne de l'élève en base via SELECT ... FOR UPDATE (MySQL/MariaDB)
            if (!$isSqlite) {
                $stmtLockEleve = $db->prepare("SELECT id_eleve FROM eleves WHERE id_eleve = :eid FOR UPDATE");
                $stmtLockEleve->execute(['eid' => $eleve_id]);

                // Verrouillage pessimiste de la classe cible
                $stmtLockClasse = $db->prepare("SELECT id FROM classe_parametres WHERE classe_id = :cid AND annee_academique_id = :aid FOR UPDATE");
                $stmtLockClasse->execute(['cid' => $classe_id, 'aid' => $annee_id]);
            }

            // B. Prévention stricte des doubles inscriptions pour l'année scolaire sous verrou exclusif
            if (Etude::isEnrolled($eleve_id, $annee_id, $lycee_id)) {
                $db->rollBack();
                header('Location: /reinscription/confirm?eleve_id=' . $eleve_id . '&error=enrolled');
                if (!defined('TEST_MODE')) exit();
                return;
            }

            // C. Vérification de la capacité sous transaction et verrou
            $capDetails = ReinscriptionService::getClassCapacityDetails($classe_id, $annee_id);
            if ($capDetails['is_complete'] && !$derogation) {
                $db->rollBack();
                header('Location: /reinscription/confirm?eleve_id=' . $eleve_id . '&error=class_full');
                if (!defined('TEST_MODE')) exit();
                return;
            }

            // C. Détermination du circuit financier (Public vs Privé)
            $lycee = ParamLycee::findByLyceeId($lycee_id);
            $type_lycee = $lycee['type_lycee'] ?? 'prive';

            $is_active = 0;
            $status = 'en_attente_paiement';

            if ($type_lycee === 'public') {
                $is_active = 1;
                $status = 'active';
            }

            // D. Création de la nouvelle affectation annuelle dans la table 'etudes'
            $stmtInsert = $db->prepare("
                INSERT INTO etudes (eleve_id, classe_id, lycee_id, annee_academique_id, is_active, status)
                VALUES (:eleve_id, :classe_id, :lycee_id, :annee_academique_id, :is_active, :status)
            ");
            $stmtInsert->execute([
                'eleve_id' => $eleve_id,
                'classe_id' => $classe_id,
                'lycee_id' => $lycee_id,
                'annee_academique_id' => $annee_id,
                'is_active' => $is_active,
                'status' => $status
            ]);
            $etude_id = (int)$db->lastInsertId();

            if ($is_active) {
                Eleve::changeStatus($eleve_id, 'actif');
                Etude::activate($etude_id, Auth::getUserId() ?: Auth::get('id'));
            }

            // Mettre à jour le compteur d'effectif dans classe_parametres
            Classe::incrementerEffectifActuel($classe_id, $annee_id);

            // Validation de la transaction
            $db->commit();

            // E. Notification financière pour les comptables
            $message = "Demande de réinscription pour " . $eleve['prenom'] . " " . $eleve['nom'] . " (" . Classe::getFormattedName($targetClasse) . ").";
            $link = "/paiements/show/" . $eleve_id;
            Notification::notifyAccountants($lycee_id, $message, $link);

            header('Location: /reinscription?success=1');
            if (!defined('TEST_MODE')) exit();
            return;

        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Error in ReinscriptionController::process: " . $e->getMessage());
            header('Location: /reinscription/confirm?eleve_id=' . $eleve_id . '&error=system_error');
            if (!defined('TEST_MODE')) exit();
            return;
        }
    }
}
