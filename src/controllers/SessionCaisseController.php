<?php

require_once __DIR__ . '/../models/SessionCaisse.php';
require_once __DIR__ . '/../models/CompteFinancier.php';
require_once __DIR__ . '/../models/TreasuryService.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';

class SessionCaisseController {

    private function checkAccess($action, $resource = 'sessions_caisse') {
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

        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT s.*, c.nom_compte, u.nom as user_nom, u.prenom as user_prenom
            FROM sessions_caisse s
            JOIN comptes_financiers c ON s.compte_id = c.id
            JOIN utilisateurs u ON s.user_id = u.id_user
            WHERE s.lycee_id = :lycee_id
            ORDER BY s.date_ouverture DESC
        ");
        $stmt->execute(['lycee_id' => $lyceeId]);
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $activeSession = SessionCaisse::findActiveByUser(Auth::getUserId(), $lyceeId);

        View::render('treasury/sessions/index', [
            'sessions' => $sessions,
            'activeSession' => $activeSession,
            'title' => _("Sessions de Caisse")
        ]);
    }

    public function open() {
        $this->checkAccess('create');
        $lyceeId = Auth::getLyceeId();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $compteId = $_POST['compte_id'] ?? null;
            if (!$compteId) {
                $_SESSION['error_message'] = _("Le compte financier est obligatoire.");
                $this->redirect('/treasury/sessions/open');
            }

            $compte = CompteFinancier::findById($compteId);
            if (!$compte || $compte['lycee_id'] != $lyceeId) {
                $_SESSION['error_message'] = _("Compte financier introuvable.");
                $this->redirect('/treasury/sessions/open');
            }

            if ($compte['type_compte'] !== 'caisse') {
                $_SESSION['error_message'] = _("Seul un compte de type caisse peut faire l'objet d'une session de caisse.");
                $this->redirect('/treasury/sessions/open');
            }

            if ($compte['statut'] !== 'actif') {
                $_SESSION['error_message'] = _("Ce compte financier est suspendu.");
                $this->redirect('/treasury/sessions/open');
            }

            // Check if there is already an active session for this account
            $active = SessionCaisse::findActiveByCompte($compteId);
            if ($active) {
                $_SESSION['error_message'] = _("Une session de caisse est déjà active sur ce compte financier.");
                $this->redirect('/treasury/sessions/open');
            }

            // Check if this user already has an active session
            $userActive = SessionCaisse::findActiveByUser(Auth::getUserId(), $lyceeId);
            if ($userActive) {
                $_SESSION['error_message'] = _("Vous possédez déjà une session de caisse active.");
                $this->redirect('/treasury/sessions');
            }

            try {
                $db = Database::getInstance();
                $db->beginTransaction();

                SessionCaisse::ouvrir([
                    'lycee_id' => $lyceeId,
                    'user_id' => Auth::getUserId(),
                    'compte_id' => $compteId
                ]);

                $db->commit();
                $_SESSION['success_message'] = _("Session de caisse ouverte avec succès.");
                $this->redirect('/treasury/sessions');
            } catch (Exception $e) {
                if (isset($db) && $db->inTransaction()) $db->rollBack();
                $_SESSION['error_message'] = $e->getMessage();
                $this->redirect('/treasury/sessions/open');
            }
        }

        // Fetch eligible accounts
        $comptes = CompteFinancier::findByLycee($lyceeId);
        $caisses = array_filter($comptes, function($c) {
            return $c['type_compte'] === 'caisse' && $c['statut'] === 'actif';
        });

        View::render('treasury/sessions/open', [
            'caisses' => $caisses,
            'title' => _("Ouvrir une Session de Caisse")
        ]);
    }

    public function show($id) {
        $this->checkAccess('view');
        $lyceeId = Auth::getLyceeId();

        $session = SessionCaisse::findById($id);
        if (!$session || $session['lycee_id'] != $lyceeId) {
            $_SESSION['error_message'] = _("Session de caisse introuvable.");
            $this->redirect('/treasury/sessions');
        }

        $compte = CompteFinancier::findById($session['compte_id']);

        // Calculate dynamic entries and exits for this session from movements_tresorerie
        $db = Database::getInstance();
        $stmt_mvt = $db->prepare("
            SELECT * FROM mouvements_tresorerie
            WHERE session_caisse_id = :session_id AND lycee_id = :lycee_id
            ORDER BY date_mouvement DESC
        ");
        $stmt_mvt->execute(['session_id' => $id, 'lycee_id' => $lyceeId]);
        $movements = $stmt_mvt->fetchAll(PDO::FETCH_ASSOC);

        $totalEntrees = 0.00;
        $totalSorties = 0.00;
        foreach ($movements as $m) {
            if ($m['type_mouvement'] === 'entree') {
                $totalEntrees += (float)$m['montant'];
            } else {
                $totalSorties += (float)$m['montant'];
            }
        }

        $soldeTheorique = (float)$session['solde_ouverture'] + $totalEntrees - $totalSorties;

        // Fetch destination coffre details for validation overview
        $coffreId = CompteFinancier::findCoffreByLycee($session['lycee_id']);
        $coffreCompte = $coffreId ? CompteFinancier::findById($coffreId) : null;

        View::render('treasury/sessions/show', [
            'session' => $session,
            'compte' => $compte,
            'movements' => $movements,
            'totalEntrees' => $totalEntrees,
            'totalSorties' => $totalSorties,
            'soldeTheorique' => $soldeTheorique,
            'coffreCompte' => $coffreCompte,
            'title' => _("Détail de la Session")
        ]);
    }

    public function close() {
        $this->checkAccess('edit');
        $lyceeId = Auth::getLyceeId();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? null;
            $soldeReel = (float)($_POST['solde_reel'] ?? 0);
            $montantRemis = isset($_POST['montant_remis']) ? (float)$_POST['montant_remis'] : null;
            $fondsCaisseConserve = isset($_POST['fonds_caisse_conserve']) ? (float)$_POST['fonds_caisse_conserve'] : null;
            $justificatif = trim($_POST['justificatif'] ?? '');

            $db = Database::getInstance();
            $db->beginTransaction();

            try {
                // Symmetric row locking to prevent concurrent closures or payments
                $driverName = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
                $lockSql = "SELECT * FROM sessions_caisse WHERE id = :id";
                if ($driverName !== 'sqlite') {
                    $lockSql .= " FOR UPDATE";
                }
                $lockStmt = $db->prepare($lockSql);
                $lockStmt->execute(['id' => $id]);
                $session = $lockStmt->fetch(PDO::FETCH_ASSOC);

                if (!$session || $session['lycee_id'] != $lyceeId) {
                    throw new Exception(_("Session de caisse introuvable."));
                }

                if ($session['statut'] !== 'ouverte') {
                    throw new Exception(_("Cette session de caisse n'est plus ouverte."));
                }

                if ($session['user_id'] != Auth::getUserId()) {
                    throw new Exception(_("Vous ne pouvez pas clôturer la session de caisse d'un autre caissier."));
                }

                // If values were supplied, enforce strict server-side validation
                if ($montantRemis !== null && $fondsCaisseConserve !== null) {
                    if ($soldeReel < 0 || $montantRemis < 0 || $fondsCaisseConserve < 0) {
                        throw new Exception(_("Les montants déclarés de clôture ne peuvent pas être négatifs."));
                    }
                    if (abs($soldeReel - ($montantRemis + $fondsCaisseConserve)) > 0.01) {
                        throw new Exception(_("Incohérence physique détectée : le solde réel doit correspondre exactement à la somme du montant remis et du fonds conservé."));
                    }
                } else {
                    $montantRemis = $soldeReel;
                    $fondsCaisseConserve = 0.00;
                }

                // Calculate dynamic balance to get theoretical balance inside the transaction
                $stmt = $db->prepare("
                    SELECT
                        SUM(CASE WHEN type_mouvement = 'entree' THEN montant ELSE 0 END) as entrees,
                        SUM(CASE WHEN type_mouvement = 'sortie' THEN montant ELSE 0 END) as sorties
                    FROM mouvements_tresorerie
                    WHERE session_caisse_id = :session_id
                ");
                $stmt->execute(['session_id' => $id]);
                $soldeData = $stmt->fetch(PDO::FETCH_ASSOC);

                $theo = (float)$session['solde_ouverture'] + (float)$soldeData['entrees'] - (float)$soldeData['sorties'];
                $ecart = $soldeReel - $theo;

                if ($ecart !== 0.00 && empty($justificatif)) {
                    throw new Exception(_("Un motif de justification est obligatoire en cas d'écart de caisse."));
                }

                $stmt_close = $db->prepare("
                    UPDATE sessions_caisse SET
                        date_fermeture = :date_fermeture,
                        solde_theorique = :theorique,
                        solde_reel = :solde_reel,
                        ecart = :ecart,
                        justificatif_ecart = :justificatif,
                        montant_remis = :montant_remis,
                        fonds_caisse_conserve = :fonds_caisse_conserve,
                        statut = 'fermee_a_valider'
                    WHERE id = :id
                ");

                $stmt_close->execute([
                    'date_fermeture' => date('Y-m-d H:i:s'),
                    'theorique' => $theo,
                    'solde_reel' => $soldeReel,
                    'ecart' => $ecart,
                    'justificatif' => $justificatif,
                    'montant_remis' => $montantRemis,
                    'fonds_caisse_conserve' => $fondsCaisseConserve,
                    'id' => $id
                ]);

                $db->commit();
                $_SESSION['success_message'] = _("Demande de clôture de caisse soumise avec succès.");
                $this->redirect('/treasury/sessions');
            } catch (Exception $e) {
                if ($db->inTransaction()) $db->rollBack();
                $_SESSION['error_message'] = $e->getMessage();
                $this->redirect('/treasury/sessions/show/' . $id);
            }
        }
        $this->redirect('/treasury/sessions');
    }

    public function approve() {
        $this->checkAccess('validate');
        $lyceeId = Auth::getLyceeId();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? null;
            $motif = trim($_POST['motif_validation'] ?? '');

            if (!$id) {
                $_SESSION['error_message'] = _("ID de la session manquant.");
                $this->redirect('/treasury/sessions');
            }

            try {
                SessionCaisse::approuver($id, Auth::getUserId(), $motif);
                $_SESSION['success_message'] = _("La session de caisse a été approuvée et validée avec succès. Les régularisations d'écart nécessaires ont été comptabilisées.");
            } catch (Exception $e) {
                $_SESSION['error_message'] = $e->getMessage();
            }
            $this->redirect('/treasury/sessions/show/' . $id);
        }
        $this->redirect('/treasury/sessions');
    }
}
?>