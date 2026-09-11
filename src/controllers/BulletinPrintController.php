<?php

require_once __DIR__ . '/../models/Bulletin.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/Cycle.php';
require_once __DIR__ . '/../models/Sequence.php';
require_once __DIR__ . '/../models/ParamLycee.php';
require_once __DIR__ . '/../models/ParamGeneral.php';
require_once __DIR__ . '/../models/FinanceService.php';
require_once __DIR__ . '/../services/AuthorizationScopeService.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';

class BulletinPrintController {

    private function checkAccess() {
        if (!Auth::can('generate', 'bulletin') && !Auth::can('print', 'bulletin') && !Auth::can('view', 'bulletin')) {
            http_response_code(403);
            View::render('errors/403');
            if (!defined('TEST_MODE')) exit();
            return;
        }
    }

    /**
     * Shows the institutional print selection page (/bulletins/print)
     */
    public function index() {
        $this->checkAccess();

        $lyceeId = Auth::getLyceeId();
        $sequences = Sequence::findAll();
        $permittedCycles = AuthorizationScopeService::getPermittedCycles($lyceeId);

        // Fetch classes grouped by cycle & niveau for cascading UI filters
        $classes = Classe::findAll($lyceeId);

        View::render('bulletins/print', [
            'sequences' => $sequences,
            'cycles' => $permittedCycles,
            'classes' => $classes,
            'title' => 'Impression des Bulletins Officiels'
        ]);
    }

    /**
     * Resolves target classes according to scope_type (cycle, niveau, classe) and scope_id.
     */
    private function resolveTargetClasses(int $lyceeId, string $scopeType, int $scopeId): array {
        $db = Database::getInstance();

        if ($scopeType === 'classe') {
            $stmt = $db->prepare("SELECT id_classe, niveau, serie, numero, cycle_id FROM classes WHERE id_classe = :id AND lycee_id = :lycee_id");
            $stmt->execute(['id' => $scopeId, 'lycee_id' => $lyceeId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } elseif ($scopeType === 'niveau') {
            // scopeId in this context refers to a class ID representing the target niveau & cycle
            $refClass = Classe::findById($scopeId);
            if (!$refClass || (int)$refClass['lycee_id'] !== $lyceeId) {
                return [];
            }
            $stmt = $db->prepare("
                SELECT id_classe, niveau, serie, numero, cycle_id
                FROM classes
                WHERE lycee_id = :lycee_id AND cycle_id = :cycle_id AND niveau = :niveau
            ");
            $stmt->execute([
                'lycee_id' => $lyceeId,
                'cycle_id' => $refClass['cycle_id'],
                'niveau' => $refClass['niveau']
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } elseif ($scopeType === 'cycle') {
            $stmt = $db->prepare("
                SELECT id_classe, niveau, serie, numero, cycle_id
                FROM classes
                WHERE lycee_id = :lycee_id AND cycle_id = :cycle_id
            ");
            $stmt->execute(['lycee_id' => $lyceeId, 'cycle_id' => $scopeId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return [];
    }

    /**
     * Pre-print summary metrics API (/bulletins/print/summary)
     */
    public function summary() {
        $this->checkAccess();

        if (!headers_sent()) {
            header('Content-Type: application/json');
        }

        $lyceeId = Auth::getLyceeId();
        $sequenceId = (int)($_GET['sequence_id'] ?? $_POST['sequence_id'] ?? 0);
        $scopeType = trim($_GET['scope_type'] ?? $_POST['scope_type'] ?? 'classe');
        $scopeId = (int)($_GET['scope_id'] ?? $_POST['scope_id'] ?? 0);

        if (!$sequenceId || !$scopeId || !in_array($scopeType, ['cycle', 'niveau', 'classe'])) {
            echo json_encode(['error' => _('Paramètres invalides pour le résumé d\'impression.')]);
            if (!defined('TEST_MODE')) exit();
            return;
        }

        $classes = $this->resolveTargetClasses($lyceeId, $scopeType, $scopeId);
        if (empty($classes)) {
            echo json_encode(['error' => _('Aucune classe trouvée dans le périmètre sélectionné.')]);
            if (!defined('TEST_MODE')) exit();
            return;
        }

        $classIds = array_column($classes, 'id_classe');
        $db = Database::getInstance();

        // Enforce RBAC scope checks for target cycles
        foreach ($classes as $c) {
            if (!empty($c['cycle_id'])) {
                AuthorizationScopeService::assertAccessToObject($lyceeId, (int)$c['cycle_id']);
            }
        }

        // Fetch students enrolled in these classes
        $inClause = implode(',', array_map('intval', $classIds));
        $stmtEleves = $db->prepare("
            SELECT e.id_eleve, e.nom, e.prenom, et.classe_id
            FROM eleves e
            JOIN etudes et ON e.id_eleve = et.eleve_id
            WHERE et.classe_id IN ({$inClause}) AND (et.is_active = 1 OR et.status = 'active')
            ORDER BY e.nom, e.prenom
        ");
        $stmtEleves->execute();
        $students = $stmtEleves->fetchAll(PDO::FETCH_ASSOC);

        $stats = [
            'classes_count' => count($classes),
            'total_students_count' => count($students),
            'printable_official_count' => 0,
            'printable_provisional_count' => 0,
            'blocked_financial_count' => 0,
            'no_bulletin_count' => 0,
            'incomplete_count' => 0,
            'printable_total' => 0
        ];

        foreach ($students as $s) {
            $eId = (int)$s['id_eleve'];

            // Financial check
            if (!FinanceService::canAccessBulletin($eId)) {
                $stats['blocked_financial_count']++;
                continue;
            }

            // Report card resolution
            $bData = Bulletin::generateForStudent($eId, $sequenceId);
            if (!$bData || empty($bData['matieres'])) {
                $stats['no_bulletin_count']++;
                continue;
            }

            $statut = $bData['bulletin_record']['statut'] ?? 'provisoire';
            if (in_array($statut, ['valide', 'publie'])) {
                $stats['printable_official_count']++;
                $stats['printable_total']++;
            } else {
                $stats['printable_provisional_count']++;
                $stats['printable_total']++;
            }
        }

        echo json_encode([
            'success' => true,
            'stats' => $stats
        ]);
        if (!defined('TEST_MODE')) exit();
    }

    /**
     * Executes bulk report card printing (/bulletins/print/execute)
     */
    public function execute() {
        $this->checkAccess();

        $lyceeId = Auth::getLyceeId();
        $sequenceId = (int)($_GET['sequence_id'] ?? $_POST['sequence_id'] ?? 0);
        $scopeType = trim($_GET['scope_type'] ?? $_POST['scope_type'] ?? 'classe');
        $scopeId = (int)($_GET['scope_id'] ?? $_POST['scope_id'] ?? 0);
        $includeProvisoire = isset($_GET['include_provisoire']) ? (bool)$_GET['include_provisoire'] : true;

        if (!$sequenceId || !$scopeId) {
            if (!defined('TEST_MODE')) {
                header('Location: /bulletins/print');
                exit();
            }
            return;
        }

        $classes = $this->resolveTargetClasses($lyceeId, $scopeType, $scopeId);
        if (empty($classes)) {
            if (!defined('TEST_MODE')) {
                header('Location: /bulletins/print');
                exit();
            }
            return;
        }

        $classIds = array_column($classes, 'id_classe');
        $db = Database::getInstance();

        foreach ($classes as $c) {
            if (!empty($c['cycle_id'])) {
                AuthorizationScopeService::assertAccessToObject($lyceeId, (int)$c['cycle_id']);
            }
        }

        $inClause = implode(',', array_map('intval', $classIds));
        $stmtEleves = $db->prepare("
            SELECT e.id_eleve
            FROM eleves e
            JOIN etudes et ON e.id_eleve = et.eleve_id
            WHERE et.classe_id IN ({$inClause}) AND (et.is_active = 1 OR et.status = 'active')
            ORDER BY et.classe_id, e.nom, e.prenom
        ");
        $stmtEleves->execute();
        $students = $stmtEleves->fetchAll(PDO::FETCH_ASSOC);

        $bulletinsData = [];
        foreach ($students as $s) {
            $eId = (int)$s['id_eleve'];

            // Financial block check
            if (!FinanceService::canAccessBulletin($eId)) {
                continue;
            }

            $bData = Bulletin::generateForStudent($eId, $sequenceId);
            if (!$bData || empty($bData['matieres'])) {
                continue;
            }

            $statut = $bData['bulletin_record']['statut'] ?? 'provisoire';
            if (!$includeProvisoire && $statut === 'provisoire') {
                continue;
            }

            $bulletinsData[] = $bData;
        }

        if (empty($bulletinsData)) {
            $_SESSION['error_message'] = _("Aucun bulletin imprimable trouvé pour le périmètre sélectionné.");
            if (!defined('TEST_MODE')) {
                header('Location: /bulletins/print');
                exit();
            }
            return;
        }

        $lycee = ParamLycee::findByLyceeId($lyceeId) ?: [];
        $paramGeneral = ParamGeneral::findByLyceeId($lyceeId) ?: ['nb_langue' => 1, 'langue_1' => 'fr_FR'];

        include __DIR__ . '/../views/bulletins/print_template.php';
        if (!defined('TEST_MODE')) exit();
    }

    /**
     * Dedicated individual student report card printing (/bulletins/student/print)
     */
    public function printSingle() {
        $this->checkAccess();

        $eleveId = (int)($_GET['eleve_id'] ?? 0);
        $sequenceId = (int)($_GET['sequence_id'] ?? 0);

        if (!$eleveId || !$sequenceId) {
            if (!defined('TEST_MODE')) {
                header('Location: /bulletins');
                exit();
            }
            return;
        }

        if (!FinanceService::canAccessBulletin($eleveId)) {
            $_SESSION['error_message'] = _("L'accès au bulletin de cet élève est bloqué pour motif financier.");
            if (!defined('TEST_MODE')) {
                header('Location: /bulletins');
                exit();
            }
            return;
        }

        $bData = Bulletin::generateForStudent($eleveId, $sequenceId);
        if (!$bData) {
            View::render('errors/404', ['message' => _("Aucune donnée de bulletin trouvée.")]);
            if (!defined('TEST_MODE')) exit();
            return;
        }

        $lyceeId = $bData['eleve']['lycee_id'] ?? Auth::getLyceeId();
        $lycee = ParamLycee::findByLyceeId($lyceeId) ?: [];
        $paramGeneral = ParamGeneral::findByLyceeId($lyceeId) ?: ['nb_langue' => 1, 'langue_1' => 'fr_FR'];

        $bulletinsData = [$bData];

        include __DIR__ . '/../views/bulletins/print_template.php';
        if (!defined('TEST_MODE')) exit();
    }
}
