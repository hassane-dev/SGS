<?php

require_once __DIR__ . '/../models/CompteFinancier.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';
require_once __DIR__ . '/../core/Validator.php';

class CompteFinancierController {

    private function checkAccess($action, $resource = 'comptes_financiers') {
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
        $lyceeId = Auth::getLyceeId();

        $comptes = CompteFinancier::findByLycee($lyceeId);

        // Fetch user manager names
        $db = Database::getInstance();
        foreach ($comptes as &$c) {
            if ($c['responsable_id']) {
                $stmt = $db->prepare("SELECT nom, prenom FROM utilisateurs WHERE id_user = :id");
                $stmt->execute(['id' => $c['responsable_id']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                $c['responsable_nom'] = $user ? $user['prenom'] . ' ' . $user['nom'] : 'N/A';
            } else {
                $c['responsable_nom'] = 'N/A';
            }
        }
        unset($c);

        View::render('comptabilite/comptes_financiers/index', [
            'comptes' => $comptes,
            'title' => _("Comptes Financiers")
        ]);
    }

    public function create() {
        $this->checkAccess('create');
        $lyceeId = Auth::getLyceeId();

        // Get list of users who can be managers
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT id_user, nom, prenom FROM utilisateurs WHERE lycee_id = :lycee_id ORDER BY nom, prenom");
        $stmt->execute(['lycee_id' => $lyceeId]);
        $responsables = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch eligible accounts from chart of accounts
        $stmtCC = $db->prepare("SELECT id, numero, libelle, classe FROM comptes_comptables WHERE actif = 1 AND autoriser_ecriture = 1 ORDER BY numero ASC");
        $stmtCC->execute();
        $comptesComptables = $stmtCC->fetchAll(PDO::FETCH_ASSOC);

        View::render('comptabilite/comptes_financiers/create', [
            'responsables' => $responsables,
            'comptesComptables' => $comptesComptables,
            'title' => _("Créer un Compte Financier")
        ]);
    }

    public function store() {
        $this->checkAccess('create');
        $lyceeId = Auth::getLyceeId();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Validator::sanitize($_POST);

            $nom = trim($data['nom_compte'] ?? '');
            $type = trim($data['type_compte'] ?? '');
            $solde = (float)($data['solde_courant'] ?? 0.00);
            $devise = trim($data['devise'] ?? 'FCFA');
            $responsableId = !empty($data['responsable_id']) ? (int)$data['responsable_id'] : null;

            if (empty($nom) || empty($type)) {
                $_SESSION['error_message'] = _("Le nom du compte et le type sont obligatoires.");
                $this->redirect('/comptes-financiers/create');
            }

            try {
                CompteFinancier::create([
                    'lycee_id' => $lyceeId,
                    'nom_compte' => $nom,
                    'type_compte' => $type,
                    'solde_courant' => $solde,
                    'devise' => $devise,
                    'responsable_id' => $responsableId,
                    'est_coffre' => !empty($data['est_coffre']) ? 1 : 0,
                    'compte_comptable_id' => !empty($data['compte_comptable_id']) ? (int)$data['compte_comptable_id'] : null
                ]);

                $_SESSION['success_message'] = _("Compte financier créé avec succès.");
                $this->redirect('/comptes-financiers');
            } catch (Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
                $this->redirect('/comptes-financiers/create');
            }
        }
        $this->redirect('/comptes-financiers');
    }

    public function edit($id = null) {
        $this->checkAccess('edit');
        $lyceeId = Auth::getLyceeId();

        if ($id === null) {
            $id = $_GET['id'] ?? null;
        }

        if (!$id) {
            $_SESSION['error_message'] = _("ID du compte financier manquant.");
            $this->redirect('/comptes-financiers');
        }

        $compte = CompteFinancier::findById($id);
        if (!$compte || $compte['lycee_id'] != $lyceeId) {
            $_SESSION['error_message'] = _("Compte financier introuvable.");
            $this->redirect('/comptes-financiers');
        }

        // Get list of users who can be managers
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT id_user, nom, prenom FROM utilisateurs WHERE lycee_id = :lycee_id ORDER BY nom, prenom");
        $stmt->execute(['lycee_id' => $lyceeId]);
        $responsables = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch eligible accounts from chart of accounts
        $stmtCC = $db->prepare("SELECT id, numero, libelle, classe FROM comptes_comptables WHERE actif = 1 AND autoriser_ecriture = 1 ORDER BY numero ASC");
        $stmtCC->execute();
        $comptesComptables = $stmtCC->fetchAll(PDO::FETCH_ASSOC);

        View::render('comptabilite/comptes_financiers/edit', [
            'compte' => $compte,
            'responsables' => $responsables,
            'comptesComptables' => $comptesComptables,
            'title' => _("Modifier le Compte Financier")
        ]);
    }

    public function update() {
        $this->checkAccess('edit');
        $lyceeId = Auth::getLyceeId();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = Validator::sanitize($_POST);
            $id = $data['id'] ?? null;

            if (!$id) {
                $_SESSION['error_message'] = _("ID du compte financier manquant.");
                $this->redirect('/comptes-financiers');
            }

            $compte = CompteFinancier::findById($id);
            if (!$compte || $compte['lycee_id'] != $lyceeId) {
                $_SESSION['error_message'] = _("Compte financier introuvable.");
                $this->redirect('/comptes-financiers');
            }

            $nom = trim($data['nom_compte'] ?? '');
            $type = trim($data['type_compte'] ?? '');
            $devise = trim($data['devise'] ?? 'FCFA');
            $responsableId = !empty($data['responsable_id']) ? (int)$data['responsable_id'] : null;
            $statut = trim($data['statut'] ?? 'actif');
            $estCoffre = !empty($data['est_coffre']) ? 1 : 0;
            $compteComptableId = !empty($data['compte_comptable_id']) ? (int)$data['compte_comptable_id'] : null;

            if (empty($nom) || empty($type)) {
                $_SESSION['error_message'] = _("Le nom du compte et le type sont obligatoires.");
                $this->redirect('/comptes-financiers/edit/' . $id);
            }

            if (!$compteComptableId) {
                $_SESSION['error_message'] = _("Un compte comptable général du référentiel doit être sélectionné.");
                $this->redirect('/comptes-financiers/edit/' . $id);
            }

            try {
                $db = Database::getInstance();

                // Validate account from chart
                $stmtCC = $db->prepare("SELECT id, numero, actif, autoriser_ecriture FROM comptes_comptables WHERE id = :id");
                $stmtCC->execute(['id' => $compteComptableId]);
                $cc = $stmtCC->fetch(PDO::FETCH_ASSOC);
                if (!$cc || !$cc['actif'] || !$cc['autoriser_ecriture']) {
                    throw new Exception(_("Le compte comptable sélectionné est invalide, inactif ou n'autorise pas les écritures."));
                }
                $compteComptableNumero = $cc['numero'];

                // Strict Uniqueness check of Coffre Principal per lycée on update
                if ($estCoffre === 1) {
                    $existingCoffre = CompteFinancier::findCoffreByLycee($lyceeId);
                    if ($existingCoffre !== null && $existingCoffre != $id) {
                        throw new Exception(_("Un seul Coffre Principal est autorisé par établissement."));
                    }
                }

                $stmt = $db->prepare("
                    UPDATE comptes_financiers
                    SET nom_compte = :nom, type_compte = :type, devise = :devise, responsable_id = :responsable_id, statut = :statut, est_coffre = :est_coffre, compte_comptable_id = :compte_comptable_id, compte_comptable_numero = :compte_comptable_numero
                    WHERE id = :id AND lycee_id = :lycee_id
                ");
                $stmt->execute([
                    'nom' => $nom,
                    'type' => $type,
                    'devise' => $devise,
                    'responsable_id' => $responsableId,
                    'statut' => $statut,
                    'est_coffre' => $estCoffre,
                    'compte_comptable_id' => $compteComptableId,
                    'compte_comptable_numero' => $compteComptableNumero,
                    'id' => $id,
                    'lycee_id' => $lyceeId
                ]);

                $_SESSION['success_message'] = _("Compte financier mis à jour avec succès.");
                $this->redirect('/comptes-financiers');
            } catch (Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
                $this->redirect('/comptes-financiers/edit/' . $id);
            }
        }
        $this->redirect('/comptes-financiers');
    }

    public function destroy($id = null) {
        $this->checkAccess('manage');
        $lyceeId = Auth::getLyceeId();

        if ($id === null) {
            $id = $_GET['id'] ?? null;
        }

        if (!$id) {
            $_SESSION['error_message'] = _("ID du compte financier manquant.");
            $this->redirect('/comptes-financiers');
        }

        $compte = CompteFinancier::findById($id);
        if (!$compte || $compte['lycee_id'] != $lyceeId) {
            $_SESSION['error_message'] = _("Compte financier introuvable.");
            $this->redirect('/comptes-financiers');
        }

        try {
            $db = Database::getInstance();

            // Check if there are movements_tresorerie tied to this account
            $stmt_check = $db->prepare("SELECT COUNT(*) FROM mouvements_tresorerie WHERE compte_id = :id");
            $stmt_check->execute(['id' => $id]);
            $hasMovements = (int)$stmt_check->fetchColumn() > 0;

            if ($hasMovements) {
                // Suspend the account instead of deleting it to preserve historical ledger integrity
                $stmt_up = $db->prepare("UPDATE comptes_financiers SET statut = 'suspendu' WHERE id = :id");
                $stmt_up->execute(['id' => $id]);
                $_SESSION['success_message'] = _("Le compte financier contient des transactions et a été suspendu pour préserver l'intégrité de la comptabilité.");
            } else {
                $stmt_del = $db->prepare("DELETE FROM comptes_financiers WHERE id = :id");
                $stmt_del->execute(['id' => $id]);
                $_SESSION['success_message'] = _("Compte financier supprimé avec succès.");
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
        }

        $this->redirect('/comptes-financiers');
    }
}
?>