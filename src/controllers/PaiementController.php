<?php

require_once __DIR__ . '/../models/Eleve.php';
require_once __DIR__ . '/../models/Etude.php';
require_once __DIR__ . '/../models/Frais.php';
require_once __DIR__ . '/../models/Inscription.php';
require_once __DIR__ . '/../models/Mensualite.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../models/Sequence.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/View.php';

class PaiementController {

    private function checkAccess($action = 'manage') {
        if (!Auth::can('paiement', $action)) {
            http_response_code(403);
            View::render('errors/403');
            exit();
        }
    }

    public function index() {
        $this->checkAccess('view');
        $lycee_id = Auth::getLyceeId();
        $activeYear = AnneeAcademique::findActive();

        if (!$activeYear) {
            $_SESSION['error_message'] = "Aucune année académique active.";
            header('Location: /');
            exit();
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
            $t['eleve_nom'] = ($e['prenom'] ?? '') . ' ' . ($e['nom'] ?? '');
        }

        View::render('paiements/index', [
            'totalInscriptions' => $totalInscriptions,
            'totalMensualites' => $totalMensualites,
            'totalGlobal' => $totalInscriptions + $totalMensualites,
            'arrieresInscriptions' => $arrieresInscriptions,
            'recentTransactions' => $recentTransactions,
            'title' => 'Tableau de bord Comptable'
        ]);
    }

    public function listPending() {
        $this->checkAccess('view');
        $lycee_id = Auth::getLyceeId();
        $eleves_en_attente = Eleve::findByStatus('en_attente_paiement', $lycee_id);

        View::render('paiements/pending', [
            'eleves' => $eleves_en_attente,
            'title' => 'Inscriptions en Attente'
        ]);
    }

    /**
     * Affiche l'interface de paiement pour un élève donné.
     */
    public function show($eleveId) {
        $this->checkAccess('view');

        $anneeActive = AnneeAcademique::findActive();
        if (!$anneeActive) {
            $_SESSION['error_message'] = "Aucune année académique active n'est définie.";
            header('Location: /eleves');
            exit();
        }

        $eleve = Eleve::findById($eleveId);
        $etude = Etude::findByEleveAndAnnee($eleveId, $anneeActive['id'], $eleve['lycee_id'] ?? null);
        $classe = $etude ? Classe::findById($etude['classe_id']) : null;

        if (!$eleve || !$classe) {
            $_SESSION['error_message'] = "L'élève ou sa classe sont introuvables pour l'année en cours.";
            header('Location: /eleves');
            exit();
        }

        $eleve['nom_classe'] = $classe ? Classe::getFormattedName($classe) : 'Non assignée';

        // Récupérer les frais et l'inscription
        $frais = Frais::findForClasse($classe, $anneeActive['id']);
        $inscription = Inscription::findByEleveAndAnnee($eleveId, $anneeActive['id'], $eleve['lycee_id'] ?? null);

        $fraisInscription = [
            'total' => $frais['frais_inscription'] ?? 0,
            'verse' => $inscription['montant_verse'] ?? 0,
        ];
        $fraisInscription['reste'] = $fraisInscription['total'] - $fraisInscription['verse'];

        $options = $inscription ? json_decode($inscription['details_frais'], true) : ['logo' => false, 'carte' => false];

        // Dérivation automatique des mois réels à partir des séquences
        $sequences = Sequence::findAll();
        $mensualitesPayees = Mensualite::findByEtude($etude['id_etude']);
        $tranches = [];
        $fmt = new IntlDateFormatter('fr_FR', IntlDateFormatter::FULL, IntlDateFormatter::NONE, 'Africa/Porto-Novo', IntlDateFormatter::GREGORIAN, 'MMMM');

        foreach ($sequences as $i => $sequence) {
            $mois = [];
            $current = new DateTime($sequence['date_debut']);
            $end = new DateTime($sequence['date_fin']);

            while ($current <= $end) {
                $mois[] = ucfirst($fmt->format($current));
                $current->modify('first day of next month');
            }

            $paye = [];
            foreach($mois as $m) {
                $m_cap = ucfirst($m);
                if (isset($mensualitesPayees[$m_cap])) {
                    $paye[$m_cap] = [
                        'verse' => $mensualitesPayees[$m_cap]['total'],
                        'details' => Mensualite::getDetails($mensualitesPayees[$m_cap]['id'])
                    ];
                }
            }

            $tranches["Tranche " . ($i + 1)] = [
                'mois' => $mois,
                'montant_par_mois' => $frais['frais_mensuel'] ?? 0,
                'paye' => $paye
            ];
        }

        View::render('paiements/show', [
            'eleve' => $eleve,
            'fraisInscription' => $fraisInscription,
            'inscription' => $inscription,
            'options' => $options,
            'tranches' => $tranches,
            'mensualitesPayees' => $mensualitesPayees,
            'isComptable' => Auth::can('paiement', 'manage')
        ]);
    }

    /**
     * Traite le paiement des frais d'inscription.
     */
    public function processInscription($eleveId) {
        $this->checkAccess('manage');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /paiements/show/' . $eleveId);
            exit();
        }

        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $anneeActive = AnneeAcademique::findActive();
            if (!$anneeActive) {
                throw new Exception("Aucune année académique active.");
            }
            $eleve = Eleve::findById($eleveId);
            if (!$eleve) {
                throw new Exception("Élève non trouvé.");
            }
            $etude = Etude::findByEleveAndAnnee($eleveId, $anneeActive['id'], $eleve['lycee_id'] ?? null);
            if (!$etude) {
                throw new Exception("Dossier académique non trouvé.");
            }
            $classe = Classe::findById($etude['classe_id']);
            $frais = Frais::findForClasse($classe, $anneeActive['id']);
            $inscription = Inscription::findByEleveAndAnnee($eleveId, $anneeActive['id'], $eleve['lycee_id'] ?? null);

            $montantVerse = (float) $_POST['montant_verse'];
            $montantTotal = (float) $frais['frais_inscription'];

            if ($montantVerse > $montantTotal) {
                throw new Exception("Le montant versé ne peut pas être supérieur au montant total de l'inscription.");
            }

            $resteAPayer = $montantTotal - $montantVerse;

            $detailsFrais = json_encode([
                'logo' => isset($_POST['options']['logo']),
                'carte' => isset($_POST['options']['carte'])
            ]);

            $data = [
                'id_inscription' => $inscription['id_inscription'] ?? null,
                'etude_id' => $etude['id_etude'],
                'eleve_id' => $eleveId,
                'classe_id' => $classe['id_classe'],
                'lycee_id' => Auth::getLyceeId(),
                'annee_academique_id' => $anneeActive['id'],
                'montant_total' => $montantTotal,
                'montant_verse' => $montantVerse,
                'reste_a_payer' => $resteAPayer,
                'details_frais' => $detailsFrais,
                'user_id' => Auth::user()['id']
            ];

            Inscription::save($data);

            // Si le paiement est complet, activer l'élève et l'étude
            if ($resteAPayer <= 0) {
                Eleve::updateStatus($eleveId, 'actif');
                Etude::activate($etude['id_etude'], Auth::user()['id']);

                // Notifier l'admin local que l'inscription est finalisée
                $eleve_nom_complet = $eleve['prenom'] . ' ' . $eleve['nom'];
                $message = "Paiement initial validé et inscription activée pour {$eleve_nom_complet}.";
                $link = "/eleves/details?id={$eleveId}";
                Notification::notifyRole('admin_local', $eleve['lycee_id'] ?? Auth::getLyceeId(), $message, $link);
            }

            $db->commit();
            $_SESSION['success_message'] = "Le paiement de l'inscription a été enregistré avec succès.";

        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['error_message'] = "Erreur lors de l'enregistrement du paiement : " . $e->getMessage();
        }

        header('Location: /paiements/show/' . $eleveId);
        exit();
    }

    /**
     * Traite le paiement des mensualités.
     */
    public function processMensualites($eleveId) {
        $this->checkAccess('manage');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['mensualites'])) {
            header('Location: /paiements/show/' . $eleveId);
            exit();
        }

        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $anneeActive = AnneeAcademique::findActive();
            if (!$anneeActive) {
                throw new Exception("Aucune année académique active.");
            }
            $eleve = Eleve::findById($eleveId);
            if (!$eleve) {
                throw new Exception("Élève non trouvé.");
            }
            $etude = Etude::findByEleveAndAnnee($eleveId, $anneeActive['id'], $eleve['lycee_id'] ?? null);
            if (!$etude) {
                throw new Exception("Dossier académique non trouvé.");
            }

            $paiements = $_POST['mensualites'];
            $userId = Auth::user()['id'];
            $lyceeId = $eleve['lycee_id'] ?? Auth::getLyceeId();
            $classeId = $etude['classe_id'];

            foreach ($paiements as $mois => $montant) {
                $montant = (float) $montant;
                if ($montant > 0) {
                    $m_cap = ucfirst($mois);
                    $data = [
                        'etude_id' => $etude['id_etude'],
                        'eleve_id' => $eleveId,
                        'classe_id' => $classeId,
                        'lycee_id' => $lyceeId,
                        'annee_academique_id' => $anneeActive['id'],
                        'mois_ou_sequence' => $m_cap,
                        'montant_verse' => $montant,
                        'user_id' => $userId
                    ];

                    $mensualiteId = Mensualite::findOrCreate($data);

                    // Ajouter le détail
                    Mensualite::addDetail([
                        'mensualite_id' => $mensualiteId,
                        'montant' => $montant,
                        'mode_paiement' => $_POST['mode_paiement'] ?? 'Espèces',
                        'reference_transaction' => $_POST['reference_transaction'] ?? null,
                        'recu_numero' => 'REC-' . time() . '-' . $eleveId
                    ]);
                }
            }

            $db->commit();
            $_SESSION['success_message'] = "Les paiements des mensualités ont été enregistrés avec succès.";

        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['error_message'] = "Erreur lors de l'enregistrement des mensualités : " . $e->getMessage();
        }

        header('Location: /paiements/show/' . $eleveId);
        exit();
    }
}
