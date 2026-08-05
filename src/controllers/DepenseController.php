<?php
// src/controllers/DepenseController.php

require_once __DIR__ . '/../models/Depense.php';
require_once __DIR__ . '/../models/DepenseCategorie.php';
require_once __DIR__ . '/../models/DepenseCentreCout.php';
require_once __DIR__ . '/../models/DepenseBeneficiaire.php';
require_once __DIR__ . '/../models/DepensePiece.php';
require_once __DIR__ . '/../models/CompteFinancier.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../models/ExerciceFinancier.php';
require_once __DIR__ . '/../services/DepenseWorkflowService.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Validator.php';

class DepenseController {

    private function checkAccess($action, $resource = 'depense') {
        if (!Auth::can($action, $resource)) {
            http_response_code(403);
            $_SESSION['error_message'] = "Accès Refusé : Vous n'avez pas la permission '$action' sur '$resource'.";
            header('Location: /login');
            if (!defined('TEST_MODE')) {
                exit();
            }
            return false;
        }
        return true;
    }

    /**
     * Helper to catch and format exception metadata for detailed debugging
     */
    private function handleException(Exception $e, $redirectUrl = '/depenses') {
        $trace = $e->getTraceAsString();
        $truncatedTrace = substr($trace, 0, 400) . (strlen($trace) > 400 ? '...' : '');
        $_SESSION['error_message'] = "Erreur : " . $e->getMessage() .
            " | Fichier: " . $e->getFile() . " (Ligne " . $e->getLine() . ")" .
            " | Trace: " . $truncatedTrace;
        header('Location: ' . $redirectUrl);
        if (!defined('TEST_MODE')) {
            exit();
        }
    }

    public function index() {
        if (!$this->checkAccess('view')) return;

        $lyceeId = Auth::getLyceeId();

        // Paginate server-side
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) $page = 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $filters = [
            'statut' => $_GET['statut'] ?? null,
            'categorie_id' => $_GET['categorie_id'] ?? null,
            'date_debut' => $_GET['date_debut'] ?? null,
            'date_fin' => $_GET['date_fin'] ?? null,
            'search' => $_GET['search'] ?? null
        ];

        $depenses = Depense::findByLycee($lyceeId, $filters, $limit, $offset);
        $total = Depense::countByLycee($lyceeId, $filters);
        $totalPages = ceil($total / $limit);
        if ($totalPages < 1) $totalPages = 1;

        $categories = DepenseCategorie::findByLycee($lyceeId);

        require_once __DIR__ . '/../views/depenses/index.php';
    }

    public function create() {
        if (!$this->checkAccess('create')) return;

        $lyceeId = Auth::getLyceeId();
        $categories = DepenseCategorie::findActiveByLycee($lyceeId);
        $centres = DepenseCentreCout::findActiveByLycee($lyceeId);
        $beneficiaires = DepenseBeneficiaire::findActiveByLycee($lyceeId);

        require_once __DIR__ . '/../views/depenses/create.php';
    }

    public function store() {
        if (!$this->checkAccess('create')) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /depenses');
            if (!defined('TEST_MODE')) exit();
            return;
        }

        try {
            $lyceeId = Auth::getLyceeId();
            $userId = Auth::getUserId();

            // Resolve active exercise or active academic year
            $activeYear = AnneeAcademique::findActive();
            if (!$activeYear) {
                throw new Exception("Aucune année académique active n'est configurée.");
            }
            if ($activeYear['cloturee']) {
                throw new Exception("L'année académique active est clôturée.");
            }

            $data = Validator::sanitize($_POST);

            // Format number or piece
            if (empty($data['numero_piece'])) {
                $data['numero_piece'] = 'DEP-' . date('Ymd') . '-' . rand(1000, 9999);
            }

            $data['lycee_id'] = $lyceeId;
            $data['cree_par'] = $userId;
            $data['exercice_financier_id'] = $activeYear['id'];

            // Validate category and beneficiary presence
            if (empty($data['categorie_id'])) {
                throw new Exception("La catégorie de dépense est requise.");
            }
            if (empty($data['beneficiaire_id'])) {
                throw new Exception("Le bénéficiaire est requis.");
            }
            if (empty($data['montant']) || (float)$data['montant'] <= 0) {
                throw new Exception("Le montant doit être supérieur à zéro.");
            }

            // If ID is provided, this is an update to an existing draft
            $depenseId = isset($_POST['id']) ? (int)$_POST['id'] : null;
            if ($depenseId) {
                $depense = Depense::findById($depenseId);
                if (!$depense) {
                    throw new Exception("Dépense introuvable.");
                }
                if ($depense['lycee_id'] !== $lyceeId) {
                    throw new Exception("Accès non autorisé.");
                }
                Depense::update($depenseId, $data);
            } else {
                // Create the draft Depense
                $depenseId = Depense::create($data);
            }

            // Handle file uploads (pieces jointes)
            if (isset($_FILES['pieces_jointes']) && !empty($_FILES['pieces_jointes']['name'][0])) {
                $uploadDir = UPLOAD_BASE_DIR . '/depenses/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $files = $_FILES['pieces_jointes'];
                $count = count($files['name']);

                for ($i = 0; $i < $count; $i++) {
                    if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                        continue;
                    }

                    $tmpName = $files['tmp_name'][$i];
                    $fileName = $files['name'][$i];
                    $fileSize = $files['size'][$i];

                    // Verify actual real MIME type
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mimeType = finfo_file($finfo, $tmpName);
                    finfo_close($finfo);

                    // Compute SHA-256 hash
                    $sha256 = hash_file('sha256', $tmpName);

                    // Destination path
                    $extension = pathinfo($fileName, PATHINFO_EXTENSION);
                    $newFileName = $sha256 . '.' . $extension;
                    $destPath = $uploadDir . $newFileName;

                    // Validate through DepensePiece model validations
                    DepensePiece::validateAttachment([
                        'taille_octets' => $fileSize,
                        'sha256_hash' => $sha256,
                        'type_mime' => $mimeType
                    ]);

                    if (move_uploaded_file($tmpName, $destPath)) {
                        // Store metadata in DB
                        DepensePiece::create([
                            'depense_id' => $depenseId,
                            'nom_fichier' => $fileName,
                            'chemin_fichier' => '/uploads/depenses/' . $newFileName,
                            'type_mime' => $mimeType,
                            'taille_octets' => $fileSize,
                            'sha256_hash' => $sha256
                        ]);
                    } else {
                        throw new Exception("Échec du téléversement du justificatif : " . $fileName);
                    }
                }
            }

            // Check if user requested direct submission
            if (!empty($_POST['submit_direct'])) {
                DepenseWorkflowService::submitForApproval($depenseId, $userId);
                $_SESSION['success_message'] = "Demande de dépense créée et soumise avec succès.";
            } else {
                $_SESSION['success_message'] = "Brouillon de dépense enregistré avec succès.";
            }

            header('Location: /depenses');
            if (!defined('TEST_MODE')) exit();
            return;

        } catch (Exception $e) {
            $this->handleException($e, '/depenses/create');
        }
    }

    public function validate($id) {
        if (!$this->checkAccess('validate')) return;

        $depense = Depense::findById($id);
        if (!$depense) {
            $_SESSION['error_message'] = "Dépense introuvable.";
            header('Location: /depenses');
            if (!defined('TEST_MODE')) exit();
            return;
        }

        // Enforce lycee isolation
        if ($depense['lycee_id'] !== Auth::getLyceeId()) {
            $_SESSION['error_message'] = "Accès non autorisé.";
            header('Location: /depenses');
            if (!defined('TEST_MODE')) exit();
            return;
        }

        $pieces = DepensePiece::findByDepense($id);

        require_once __DIR__ . '/../views/depenses/validate.php';
    }

    public function vote($id) {
        if (!$this->checkAccess('validate')) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /depenses');
            if (!defined('TEST_MODE')) exit();
            return;
        }

        try {
            $userId = Auth::getUserId();
            $action = $_POST['vote_action'] ?? ''; // 'approve' or 'reject'
            $motif = Validator::sanitize($_POST['motif_vote'] ?? '');

            if ($action === 'approve') {
                DepenseWorkflowService::approve($id, $userId, $motif);
                $_SESSION['success_message'] = "Dépense approuvée avec succès.";
            } elseif ($action === 'reject') {
                DepenseWorkflowService::reject($id, $userId, $motif);
                $_SESSION['success_message'] = "Dépense rejetée.";
            } else {
                throw new Exception("Action de vote non spécifiée.");
            }

            header('Location: /depenses');
            if (!defined('TEST_MODE')) exit();
            return;

        } catch (Exception $e) {
            $this->handleException($e, '/depenses/validate/' . $id);
        }
    }

    public function pay($id) {
        if (!$this->checkAccess('pay')) return;

        $depense = Depense::findById($id);
        if (!$depense) {
            $_SESSION['error_message'] = "Dépense introuvable.";
            header('Location: /depenses');
            if (!defined('TEST_MODE')) exit();
            return;
        }

        if ($depense['lycee_id'] !== Auth::getLyceeId()) {
            $_SESSION['error_message'] = "Accès non autorisé.";
            header('Location: /depenses');
            if (!defined('TEST_MODE')) exit();
            return;
        }

        $lyceeId = Auth::getLyceeId();
        $comptes = CompteFinancier::findByLycee($lyceeId);

        require_once __DIR__ . '/../views/depenses/pay.php';
    }

    public function processPayment($id) {
        if (!$this->checkAccess('pay')) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /depenses');
            if (!defined('TEST_MODE')) exit();
            return;
        }

        try {
            $userId = Auth::getUserId();
            $compteId = $_POST['compte_id'] ?? null;
            $motif = Validator::sanitize($_POST['motif_payment'] ?? 'Règlement de dépense');

            if (!$compteId) {
                throw new Exception("Veuillez sélectionner un compte financier pour effectuer le paiement.");
            }

            DepenseWorkflowService::pay($id, $compteId, $userId, $motif);
            $_SESSION['success_message'] = "Paiement de la dépense validé et enregistré en comptabilité.";

            header('Location: /depenses');
            if (!defined('TEST_MODE')) exit();
            return;

        } catch (Exception $e) {
            $this->handleException($e, '/depenses/pay/' . $id);
        }
    }

    public function cancel($id) {
        if (!$this->checkAccess('cancel')) return;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /depenses');
            if (!defined('TEST_MODE')) exit();
            return;
        }

        try {
            $userId = Auth::getUserId();
            $motif = Validator::sanitize($_POST['motif_annulation'] ?? '');

            if (empty($motif)) {
                throw new Exception("Le motif d'annulation est obligatoire pour exécuter une contre-passation.");
            }

            DepenseWorkflowService::cancel($id, $userId, $motif);
            $_SESSION['success_message'] = "Dépense annulée avec succès et contre-passation enregistrée.";

            header('Location: /depenses');
            if (!defined('TEST_MODE')) exit();
            return;

        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    public function validation() {
        if (!$this->checkAccess('view')) return;
        $lyceeId = Auth::getLyceeId();

        $filters = ['statut' => 'en_attente_approbation'];
        $depenses = Depense::findByLycee($lyceeId, $filters, 100, 0);

        require_once __DIR__ . '/../views/depenses/validation.php';
    }

    public function payments() {
        if (!$this->checkAccess('view')) return;
        $lyceeId = Auth::getLyceeId();

        $filters = ['statut' => 'approuve'];
        $depenses = Depense::findByLycee($lyceeId, $filters, 100, 0);

        require_once __DIR__ . '/../views/depenses/payments.php';
    }

    public function history() {
        if (!$this->checkAccess('view')) return;
        $lyceeId = Auth::getLyceeId();

        $depenses = Depense::findByLycee($lyceeId, [], 100, 0);
        // filter on paid / cancelled / rejected
        $depenses = array_filter($depenses, function($d) {
            return in_array($d['statut'], ['paye', 'annule', 'rejete']);
        });

        require_once __DIR__ . '/../views/depenses/history.php';
    }

    public function categories() {
        if (!$this->checkAccess('manage')) return;
        $lyceeId = Auth::getLyceeId();
        $categories = DepenseCategorie::findByLycee($lyceeId);

        require_once __DIR__ . '/../views/depenses/categories.php';
    }

    public function storeCategory() {
        if (!$this->checkAccess('manage')) return;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $lyceeId = Auth::getLyceeId();
                $data = Validator::sanitize($_POST);
                $data['lycee_id'] = $lyceeId;

                DepenseCategorie::create($data);
                $_SESSION['success_message'] = _("Catégorie de dépense créée avec succès.");
            } catch (Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
            }
        }
        header('Location: /depenses/categories');
        exit();
    }

    public function updateCategory() {
        if (!$this->checkAccess('manage')) return;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $id = $_POST['id'] ?? null;
                $data = Validator::sanitize($_POST);

                DepenseCategorie::update($id, $data);
                $_SESSION['success_message'] = _("Catégorie mise à jour.");
            } catch (Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
            }
        }
        header('Location: /depenses/categories');
        exit();
    }

    public function deleteCategory() {
        if (!$this->checkAccess('manage')) return;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $id = $_POST['id'] ?? null;
                DepenseCategorie::delete($id);
                $_SESSION['success_message'] = _("Catégorie supprimée ou désactivée.");
            } catch (Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
            }
        }
        header('Location: /depenses/categories');
        exit();
    }

    public function centresCouts() {
        if (!$this->checkAccess('manage')) return;
        $lyceeId = Auth::getLyceeId();
        $centres = DepenseCentreCout::findByLycee($lyceeId);

        require_once __DIR__ . '/../views/depenses/centres_couts.php';
    }

    public function storeCentreCout() {
        if (!$this->checkAccess('manage')) return;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $lyceeId = Auth::getLyceeId();
                $data = Validator::sanitize($_POST);
                $data['lycee_id'] = $lyceeId;

                DepenseCentreCout::create($data);
                $_SESSION['success_message'] = _("Centre de coût créé avec succès.");
            } catch (Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
            }
        }
        header('Location: /depenses/centres-couts');
        exit();
    }

    public function updateCentreCout() {
        if (!$this->checkAccess('manage')) return;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $id = $_POST['id'] ?? null;
                $data = Validator::sanitize($_POST);

                DepenseCentreCout::update($id, $data);
                $_SESSION['success_message'] = _("Centre de coût mis à jour.");
            } catch (Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
            }
        }
        header('Location: /depenses/centres-couts');
        exit();
    }

    public function deleteCentreCout() {
        if (!$this->checkAccess('manage')) return;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $id = $_POST['id'] ?? null;
                DepenseCentreCout::delete($id);
                $_SESSION['success_message'] = _("Centre de coût supprimé ou désactivé.");
            } catch (Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
            }
        }
        header('Location: /depenses/centres-couts');
        exit();
    }

    public function beneficiaires() {
        if (!$this->checkAccess('manage')) return;
        $lyceeId = Auth::getLyceeId();
        $beneficiaires = DepenseBeneficiaire::findByLycee($lyceeId);

        require_once __DIR__ . '/../views/depenses/beneficiaires.php';
    }

    public function storeBeneficiaire() {
        if (!$this->checkAccess('manage')) return;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $lyceeId = Auth::getLyceeId();
                $data = Validator::sanitize($_POST);
                $data['lycee_id'] = $lyceeId;

                DepenseBeneficiaire::create($data);
                $_SESSION['success_message'] = _("Bénéficiaire créé avec succès.");
            } catch (Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
            }
        }
        header('Location: /depenses/beneficiaires');
        exit();
    }

    public function updateBeneficiaire() {
        if (!$this->checkAccess('manage')) return;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $id = $_POST['id'] ?? null;
                $data = Validator::sanitize($_POST);

                DepenseBeneficiaire::update($id, $data);
                $_SESSION['success_message'] = _("Bénéficiaire mis à jour.");
            } catch (Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
            }
        }
        header('Location: /depenses/beneficiaires');
        exit();
    }

    public function deleteBeneficiaire() {
        if (!$this->checkAccess('manage')) return;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $id = $_POST['id'] ?? null;
                DepenseBeneficiaire::delete($id);
                $_SESSION['success_message'] = _("Bénéficiaire supprimé ou désactivé.");
            } catch (Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
            }
        }
        header('Location: /depenses/beneficiaires');
        exit();
    }
}
?>