<?php
// src/controllers/AchatController.php

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';
require_once __DIR__ . '/../models/Fournisseur.php';
require_once __DIR__ . '/../models/AchatCategorie.php';
require_once __DIR__ . '/../models/AchatArticle.php';
require_once __DIR__ . '/../models/AchatDemande.php';
require_once __DIR__ . '/../models/AchatDemandeLigne.php';
require_once __DIR__ . '/../models/AchatCommande.php';
require_once __DIR__ . '/../models/AchatCommandeLigne.php';
require_once __DIR__ . '/../models/AchatReception.php';
require_once __DIR__ . '/../models/AchatReceptionLigne.php';
require_once __DIR__ . '/../models/AchatFacture.php';
require_once __DIR__ . '/../models/AchatFactureLigne.php';
require_once __DIR__ . '/../models/AchatFactureReglement.php';
require_once __DIR__ . '/../models/AchatAvoirFournisseur.php';
require_once __DIR__ . '/../models/AchatAvoirFournisseurLigne.php';
require_once __DIR__ . '/../models/CompteFinancier.php';
require_once __DIR__ . '/../models/CompteComptable.php';
require_once __DIR__ . '/../models/SessionCaisse.php';
require_once __DIR__ . '/../services/AchatWorkflowService.php';

class AchatController {

    private function checkAccess($action, $resource) {
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

    private function enforceLyceeScope($document) {
        $lyceeId = Auth::getLyceeId();
        if ($document && isset($document['lycee_id']) && $document['lycee_id'] != $lyceeId) {
            if (PHP_SAPI === 'cli') {
                throw new Exception("FORBIDDEN_LYCEE_MISMATCH");
            }
            http_response_code(403);
            View::render('errors/403');
            exit();
        }
    }

    // --- FOURNISSEURS ---

    public function listFournisseurs() {
        $this->checkAccess('view', 'fournisseur');
        $lyceeId = Auth::getLyceeId();
        $fournisseurs = Fournisseur::findByLycee($lyceeId);

        View::render('achats/fournisseurs/index', [
            'fournisseurs' => $fournisseurs,
            'title' => _("Gestion des Fournisseurs")
        ]);
    }

    public function createFournisseur() {
        $this->checkAccess('manage', 'fournisseur');
        $lyceeId = Auth::getLyceeId();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                Fournisseur::create([
                    'lycee_id' => $_POST['lycee_scope'] === 'global' ? null : $lyceeId,
                    'raison_sociale' => trim($_POST['raison_sociale'] ?? ''),
                    'code_fournisseur' => trim($_POST['code_fournisseur'] ?? ''),
                    'nif' => trim($_POST['nif'] ?? null),
                    'rccm' => trim($_POST['rccm'] ?? null),
                    'adresse' => trim($_POST['adresse'] ?? null),
                    'telephone' => trim($_POST['telephone'] ?? null),
                    'email' => trim($_POST['email'] ?? null),
                    'contact_nom' => trim($_POST['contact_nom'] ?? null),
                    'compte_comptable_tiers' => !empty($_POST['compte_comptable_tiers']) ? trim($_POST['compte_comptable_tiers']) : null,
                    'actif' => 1,
                    'cree_par' => Auth::getUserId()
                ]);
                $_SESSION['success_message'] = _("Fournisseur créé avec succès.");
                $this->redirect('/achats/fournisseurs');
            } catch (Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
            }
        }

        $comptesTiers = CompteComptable::findAll(['actif' => 1]);

        View::render('achats/fournisseurs/create', [
            'comptesTiers' => $comptesTiers,
            'title' => _("Créer un Fournisseur")
        ]);
    }

    public function showFournisseur() {
        $this->checkAccess('view', 'fournisseur');
        $id = $_GET['id'] ?? null;
        $fournisseur = Fournisseur::findById($id);

        $this->enforceLyceeScope($fournisseur);

        // Fetch supplier metrics
        $metrics = Fournisseur::getMetrics($id);

        // Fetch orders and invoices for this supplier
        $db = Database::getInstance();
        $stmt_orders = $db->prepare("SELECT * FROM achat_commandes WHERE fournisseur_id = :id ORDER BY date_commande DESC");
        $stmt_orders->execute(['id' => $id]);
        $orders = $stmt_orders->fetchAll(PDO::FETCH_ASSOC);

        $stmt_invoices = $db->prepare("SELECT * FROM achat_factures WHERE fournisseur_id = :id ORDER BY date_facture DESC");
        $stmt_invoices->execute(['id' => $id]);
        $invoices = $stmt_invoices->fetchAll(PDO::FETCH_ASSOC);

        View::render('achats/fournisseurs/show', [
            'fournisseur' => $fournisseur,
            'metrics' => $metrics,
            'orders' => $orders,
            'invoices' => $invoices,
            'title' => _("Fiche Fournisseur")
        ]);
    }

    public function editFournisseur() {
        $this->checkAccess('manage', 'fournisseur');
        $id = $_GET['id'] ?? null;
        $fournisseur = Fournisseur::findById($id);

        $this->enforceLyceeScope($fournisseur);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                Fournisseur::update($id, [
                    'raison_sociale' => trim($_POST['raison_sociale'] ?? ''),
                    'nif' => trim($_POST['nif'] ?? null),
                    'rccm' => trim($_POST['rccm'] ?? null),
                    'adresse' => trim($_POST['adresse'] ?? null),
                    'telephone' => trim($_POST['telephone'] ?? null),
                    'email' => trim($_POST['email'] ?? null),
                    'contact_nom' => trim($_POST['contact_nom'] ?? null),
                    'compte_comptable_tiers' => !empty($_POST['compte_comptable_tiers']) ? trim($_POST['compte_comptable_tiers']) : null,
                    'actif' => isset($_POST['actif']) ? 1 : 0
                ]);
                $_SESSION['success_message'] = _("Fournisseur mis à jour avec succès.");
                $this->redirect('/achats/fournisseurs');
            } catch (Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
            }
        }

        $comptesTiers = CompteComptable::findAll(['actif' => 1]);

        View::render('achats/fournisseurs/edit', [
            'fournisseur' => $fournisseur,
            'comptesTiers' => $comptesTiers,
            'title' => _("Modifier le Fournisseur")
        ]);
    }

    public function toggleFournisseur() {
        $this->checkAccess('manage', 'fournisseur');
        $id = $_GET['id'] ?? null;
        $fournisseur = Fournisseur::findById($id);

        $this->enforceLyceeScope($fournisseur);

        try {
            $newActif = $fournisseur['actif'] ? 0 : 1;
            Fournisseur::update($id, ['actif' => $newActif]);
            $_SESSION['success_message'] = $newActif ? _("Fournisseur activé avec succès.") : _("Fournisseur désactivé avec succès.");
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
        }
        $this->redirect('/achats/fournisseurs');
    }

    public function deleteFournisseur() {
        $this->checkAccess('manage', 'fournisseur');
        $id = $_GET['id'] ?? null;
        $fournisseur = Fournisseur::findById($id);

        $this->enforceLyceeScope($fournisseur);

        try {
            Fournisseur::delete($id);
            $_SESSION['success_message'] = _("Fournisseur supprimé ou désactivé avec succès.");
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
        }
        $this->redirect('/achats/fournisseurs');
    }

    // --- CATEGORIES ---

    public function listCategories() {
        $this->checkAccess('manage', 'achat_categorie');
        $categories = AchatCategorie::findAll();

        View::render('achats/categories/index', [
            'categories' => $categories,
            'title' => _("Catégories d'Achats")
        ]);
    }

    public function createCategory() {
        $this->checkAccess('manage', 'achat_categorie');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                AchatCategorie::create([
                    'libelle' => trim($_POST['libelle'] ?? ''),
                    'compte_comptable_charge' => trim($_POST['compte_comptable_charge'] ?? ''),
                    'actif' => 1
                ]);
                $_SESSION['success_message'] = _("Catégorie créée avec succès.");
                $this->redirect('/achats/categories');
            } catch (Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
            }
        }

        $comptesCharges = CompteComptable::findAll(['actif' => 1]);

        View::render('achats/categories/create', [
            'comptesCharges' => $comptesCharges,
            'title' => _("Créer une Catégorie")
        ]);
    }

    public function editCategory() {
        $this->checkAccess('manage', 'achat_categorie');
        $id = $_GET['id'] ?? null;
        $category = AchatCategorie::findById($id);

        if (!$category) {
            $_SESSION['error_message'] = _("Catégorie introuvable.");
            $this->redirect('/achats/categories');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                AchatCategorie::update($id, [
                    'libelle' => trim($_POST['libelle'] ?? ''),
                    'compte_comptable_charge' => trim($_POST['compte_comptable_charge'] ?? ''),
                    'actif' => isset($_POST['actif']) ? 1 : 0
                ]);
                $_SESSION['success_message'] = _("Catégorie mise à jour avec succès.");
                $this->redirect('/achats/categories');
            } catch (Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
            }
        }

        $comptesCharges = CompteComptable::findAll(['actif' => 1]);

        View::render('achats/categories/edit', [
            'category' => $category,
            'comptesCharges' => $comptesCharges,
            'title' => _("Modifier la Catégorie")
        ]);
    }

    public function deleteCategory() {
        $this->checkAccess('manage', 'achat_categorie');
        $id = $_GET['id'] ?? null;

        try {
            AchatCategorie::delete($id);
            $_SESSION['success_message'] = _("Catégorie supprimée ou désactivée avec succès.");
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
        }
        $this->redirect('/achats/categories');
    }

    // --- ARTICLES ---

    public function listArticles() {
        $this->checkAccess('view', 'achat_article');
        $articles = AchatArticle::findAll();

        View::render('achats/articles/index', [
            'articles' => $articles,
            'title' => _("Catalogue Articles & Services")
        ]);
    }

    public function createArticle() {
        $this->checkAccess('manage', 'achat_article');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                AchatArticle::create([
                    'categorie_id' => $_POST['categorie_id'],
                    'libelle' => trim($_POST['libelle'] ?? ''),
                    'reference' => trim($_POST['reference'] ?? ''),
                    'unite_mesure' => trim($_POST['unite_mesure'] ?? ''),
                    'prix_unitaire_estime' => (float)$_POST['prix_unitaire_estime'],
                    'is_service' => isset($_POST['is_service']) ? 1 : 0,
                    'actif' => 1
                ]);
                $_SESSION['success_message'] = _("Article ajouté avec succès.");
                $this->redirect('/achats/articles');
            } catch (Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
            }
        }

        $categories = AchatCategorie::findActive();
        View::render('achats/articles/create', [
            'categories' => $categories,
            'title' => _("Ajouter un Article")
        ]);
    }

    public function editArticle() {
        $this->checkAccess('manage', 'achat_article');
        $id = $_GET['id'] ?? null;
        $article = AchatArticle::findById($id);

        if (!$article) {
            $_SESSION['error_message'] = _("Article introuvable.");
            $this->redirect('/achats/articles');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                AchatArticle::update($id, [
                    'categorie_id' => $_POST['categorie_id'],
                    'libelle' => trim($_POST['libelle'] ?? ''),
                    'unite_mesure' => trim($_POST['unite_mesure'] ?? ''),
                    'prix_unitaire_estime' => (float)$_POST['prix_unitaire_estime'],
                    'is_service' => isset($_POST['is_service']) ? 1 : 0,
                    'actif' => isset($_POST['actif']) ? 1 : 0
                ]);
                $_SESSION['success_message'] = _("Article mis à jour avec succès.");
                $this->redirect('/achats/articles');
            } catch (Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
            }
        }

        $categories = AchatCategorie::findActive();
        View::render('achats/articles/edit', [
            'article' => $article,
            'categories' => $categories,
            'title' => _("Modifier l'Article")
        ]);
    }

    public function deleteArticle() {
        $this->checkAccess('manage', 'achat_article');
        $id = $_GET['id'] ?? null;

        try {
            AchatArticle::delete($id);
            $_SESSION['success_message'] = _("Article supprimé ou désactivé avec succès.");
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
        }
        $this->redirect('/achats/articles');
    }

    // --- DEMANDES D'ACHATS ---

    public function listDemandes() {
        $this->checkAccess('view', 'achat_demande');
        $lyceeId = Auth::getLyceeId();
        $demandes = AchatDemande::findByLycee($lyceeId);

        View::render('achats/demandes/index', [
            'demandes' => $demandes,
            'title' => _("Demandes d'Achats")
        ]);
    }

    public function createDemande() {
        $this->checkAccess('create', 'achat_demande');
        $lyceeId = Auth::getLyceeId();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $items = [];
                if (isset($_POST['articles']) && is_array($_POST['articles'])) {
                    foreach ($_POST['articles'] as $index => $artId) {
                        $items[] = [
                            'article_id' => $artId,
                            'quantite' => (float)$_POST['quantites'][$index],
                            'prix_unitaire_estime' => (float)$_POST['prix_unitaires'][$index],
                            'budget_ligne_id' => $_POST['budget_lignes'][$index] ?: null
                        ];
                    }
                }

                AchatWorkflowService::createDemande($lyceeId, Auth::getUserId(), trim($_POST['justification'] ?? ''), $items);
                $_SESSION['success_message'] = _("Demande d'achat soumise pour approbation.");
                $this->redirect('/achats/demandes');
            } catch (Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
            }
        }

        $articles = AchatArticle::findActive();
        // Load active budget lines dynamically
        $db = Database::getInstance();
        $stmt_bl = $db->prepare("
            SELECT bl.id, bl.allocation_initiale, a.nom_categorie as cat_libelle
            FROM budget_lignes bl
            INNER JOIN budgets b ON bl.budget_id = b.id
            INNER JOIN depense_categories a ON bl.categorie_id = a.id
            WHERE b.lycee_id = :lycee_id AND b.statut = 'approuve'
        ");
        $stmt_bl->execute(['lycee_id' => $lyceeId]);
        $budget_lignes = $stmt_bl->fetchAll(PDO::FETCH_ASSOC);

        View::render('achats/demandes/create', [
            'articles' => $articles,
            'budget_lignes' => $budget_lignes,
            'title' => _("Créer une Demande d'Achat")
        ]);
    }

    public function approveDemande() {
        $this->checkAccess('approve', 'achat_demande');
        $id = $_GET['id'] ?? $_POST['id'] ?? null;
        $demande = AchatDemande::findById($id);

        $this->enforceLyceeScope($demande);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $motif = trim($_POST['motif_statut'] ?? '');
                if ($_POST['action'] === 'approve') {
                    AchatWorkflowService::approveDemande($id, Auth::getUserId(), $motif);
                    $_SESSION['success_message'] = _("Demande d'achat approuvée et réservée budgétairement.");
                } else {
                    AchatWorkflowService::rejectDemande($id, Auth::getUserId(), $motif);
                    $_SESSION['success_message'] = _("Demande d'achat rejetée.");
                }
                $this->redirect('/achats/demandes');
            } catch (Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
            }
        }

        $lignes = AchatDemandeLigne::findByDemande($id);
        View::render('achats/demandes/approve', [
            'demande' => $demande,
            'lignes' => $lignes,
            'title' => _("Approuver la Demande d'Achat")
        ]);
    }

    // --- COMMANDES ---

    public function listCommandes() {
        $this->checkAccess('view', 'achat_commande');
        $lyceeId = Auth::getLyceeId();
        $commandes = AchatCommande::findByLycee($lyceeId);

        View::render('achats/commandes/index', [
            'commandes' => $commandes,
            'title' => _("Bons de Commande")
        ]);
    }

    public function createCommande() {
        $this->checkAccess('create', 'achat_commande');
        $lyceeId = Auth::getLyceeId();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $items = [];
                if (isset($_POST['articles']) && is_array($_POST['articles'])) {
                    foreach ($_POST['articles'] as $index => $artId) {
                        $items[] = [
                            'article_id' => $artId,
                            'demande_ligne_id' => $_POST['demande_ligne_ids'][$index] ?: null,
                            'quantite_commandee' => (float)$_POST['quantites'][$index],
                            'prix_unitaire_negocie' => (float)$_POST['prix_unitaires'][$index]
                        ];
                    }
                }

                $numCmd = 'BC-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
                AchatWorkflowService::createCommande($lyceeId, $_POST['demande_id'] ?: null, $_POST['fournisseur_id'], $numCmd, Auth::getUserId(), $items);
                $_SESSION['success_message'] = _("Bon de commande émis avec succès.");
                $this->redirect('/achats/commandes');
            } catch (Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
            }
        }

        $fournisseurs = Fournisseur::findActiveByLycee($lyceeId);
        $articles = AchatArticle::findActive();

        View::render('achats/commandes/create', [
            'fournisseurs' => $fournisseurs,
            'articles' => $articles,
            'title' => _("Émettre un Bon de Commande")
        ]);
    }

    public function showCommande() {
        $this->checkAccess('view', 'achat_commande');
        $id = $_GET['id'] ?? null;
        $commande = AchatCommande::findById($id);

        $this->enforceLyceeScope($commande);

        $fournisseur = Fournisseur::findById($commande['fournisseur_id']);
        $lignes = AchatCommandeLigne::findByCommande($id);

        View::render('achats/commandes/show', [
            'commande' => $commande,
            'fournisseur' => $fournisseur,
            'lignes' => $lignes,
            'title' => _("Bon de Commande")
        ]);
    }

    // --- RECEPTIONS ---

    public function listReceptions() {
        $this->checkAccess('view', 'achat_reception');
        $lyceeId = Auth::getLyceeId();
        $receptions = AchatReception::findByLycee($lyceeId);

        View::render('achats/receptions/index', [
            'receptions' => $receptions,
            'title' => _("Bons de Réception")
        ]);
    }

    public function createReception() {
        $this->checkAccess('create', 'achat_reception');
        $lyceeId = Auth::getLyceeId();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $items = [];
                if (isset($_POST['commande_ligne_ids']) && is_array($_POST['commande_ligne_ids'])) {
                    foreach ($_POST['commande_ligne_ids'] as $index => $cmdLineId) {
                        $items[] = [
                            'commande_ligne_id' => $cmdLineId,
                            'quantite_receptionnee' => (float)$_POST['quantites_recues'][$index],
                            'quantite_refusee' => (float)$_POST['quantites_refusees'][$index],
                            'motif_refus' => $_POST['motifs_refus'][$index] ?: null
                        ];
                    }
                }

                $numRec = 'BR-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
                AchatWorkflowService::receiveCommande($lyceeId, $_POST['commande_id'], $numRec, Auth::getUserId(), $items);
                $_SESSION['success_message'] = _("Bon de réception validé.");
                $this->redirect('/achats/receptions');
            } catch (Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
            }
        }

        $id = $_GET['commande_id'] ?? null;
        $commande = AchatCommande::findById($id);
        $this->enforceLyceeScope($commande);

        $lignes = AchatCommandeLigne::findByCommande($id);
        View::render('achats/receptions/create', [
            'commande' => $commande,
            'lignes' => $lignes,
            'title' => _("Réceptionner la Commande")
        ]);
    }

    public function showReception() {
        $this->checkAccess('view', 'achat_reception');
        $id = $_GET['id'] ?? null;
        $reception = AchatReception::findById($id);

        $this->enforceLyceeScope($reception);

        $commande = AchatCommande::findById($reception['commande_id']);
        $lignes = AchatReceptionLigne::findByReception($id);

        View::render('achats/receptions/show', [
            'reception' => $reception,
            'commande' => $commande,
            'lignes' => $lignes,
            'title' => _("Bon de Réception")
        ]);
    }

    // --- FACTURES & PAIEMENTS ---

    public function listFactures() {
        $this->checkAccess('view', 'achat_facture');
        $lyceeId = Auth::getLyceeId();
        $factures = AchatFacture::findByLycee($lyceeId);

        View::render('achats/factures/index', [
            'factures' => $factures,
            'title' => _("Factures Fournisseurs")
        ]);
    }

    public function matchFacture() {
        $this->checkAccess('create', 'achat_facture');
        $lyceeId = Auth::getLyceeId();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $items = [];
                if (isset($_POST['reception_ligne_ids']) && is_array($_POST['reception_ligne_ids'])) {
                    foreach ($_POST['reception_ligne_ids'] as $index => $recLigneId) {
                        $items[] = [
                            'reception_ligne_id' => $recLigneId,
                            'quantite_facturee' => (float)$_POST['quantites_facturees'][$index],
                            'prix_unitaire_facture' => (float)$_POST['prix_unitaires_facturees'][$index],
                            'taux_tva_facture' => (float)$_POST['taux_tva_facturees'][$index]
                        ];
                    }
                }

                AchatWorkflowService::createFacture(
                    $lyceeId, $_POST['fournisseur_id'], $_POST['commande_id'] ?: null, $_POST['reception_id'] ?: null,
                    trim($_POST['reference_facture']), $_POST['date_facture'], $_POST['date_echeance'], $items
                );
                $_SESSION['success_message'] = _("Facture fournisseur enregistrée et comptabilisée.");
                $this->redirect('/achats/factures');
            } catch (Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
            }
        }

        $id = $_GET['reception_id'] ?? null;
        $reception = AchatReception::findById($id);
        $this->enforceLyceeScope($reception);

        $commande = AchatCommande::findById($reception['commande_id']);
        $lignes = AchatReceptionLigne::findByReception($id);
        $fournisseur = Fournisseur::findById($commande['fournisseur_id']);

        View::render('achats/factures/rapprochement', [
            'reception' => $reception,
            'commande' => $commande,
            'fournisseur' => $fournisseur,
            'lignes' => $lignes,
            'title' => _("Enregistrer la Facture (3-Way Matching)")
        ]);
    }

    public function payFacture() {
        $this->checkAccess('pay', 'achat_facture');
        $lyceeId = Auth::getLyceeId();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                AchatWorkflowService::payFacture(
                    $_POST['facture_id'], $_POST['compte_financier_id'], $_POST['session_caisse_id'] ?: null,
                    (float)$_POST['montant_paye'], $_POST['mode_paiement'], Auth::getUserId(), $_POST['idempotency_key']
                );
                $_SESSION['success_message'] = _("Facture réglée avec succès.");
                $this->redirect('/achats/factures');
            } catch (Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
            }
        }

        $id = $_GET['id'] ?? null;
        $facture = AchatFacture::findById($id);
        $this->enforceLyceeScope($facture);

        $reste = AchatFacture::getResteAPayer($id);
        $comptes = CompteFinancier::findByLycee($lyceeId);
        $session = SessionCaisse::findActiveByUser(Auth::getUserId(), $lyceeId);

        View::render('achats/factures/pay', [
            'facture' => $facture,
            'reste' => $reste,
            'comptes' => $comptes,
            'session' => $session,
            'title' => _("Régler la Facture Fournisseur")
        ]);
    }

    // --- AVOIRS ---

    public function createAvoir() {
        $this->checkAccess('create', 'achat_avoir');
        $lyceeId = Auth::getLyceeId();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $items = [];
                if (isset($_POST['facture_ligne_ids']) && is_array($_POST['facture_ligne_ids'])) {
                    foreach ($_POST['facture_ligne_ids'] as $index => $flId) {
                        $items[] = [
                            'facture_ligne_id' => $flId,
                            'quantite_avoir' => (float)$_POST['quantites_avoir'][$index],
                            'prix_unitaire_avoir' => (float)$_POST['prix_unitaires_avoir'][$index],
                            'montant_ht_ligne' => (float)$_POST['quantites_avoir'][$index] * (float)$_POST['prix_unitaires_avoir'][$index],
                            'montant_ttc_ligne' => (float)$_POST['quantites_avoir'][$index] * (float)$_POST['prix_unitaires_avoir'][$index] * (1.0000 + (float)$_POST['taux_tva_avoir'][$index])
                        ];
                    }
                }

                $montantHt = array_sum(array_column($items, 'montant_ht_ligne'));
                $montantTtc = array_sum(array_column($items, 'montant_ttc_ligne'));

                $refAvoir = 'AV-' . date('Y') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
                AchatWorkflowService::createAvoir($lyceeId, $_POST['fournisseur_id'], $_POST['facture_id'], $refAvoir, date('Y-m-d'), $montantHt, $montantTtc, Auth::getUserId(), $items);
                $_SESSION['success_message'] = _("Avoir fournisseur validé et contrepassé.");
                $this->redirect('/achats/factures');
            } catch (Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
            }
        }

        $id = $_GET['facture_id'] ?? null;
        $facture = AchatFacture::findById($id);
        $this->enforceLyceeScope($facture);

        $lignes = AchatFactureLigne::findByFacture($id);
        View::render('achats/avoirs/create', [
            'facture' => $facture,
            'lignes' => $lignes,
            'title' => _("Enregistrer un Avoir Fournisseur")
        ]);
    }
}
?>