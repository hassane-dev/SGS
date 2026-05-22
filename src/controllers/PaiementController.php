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
        if (!Auth::can($action, 'paiement')) {
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

        // 1. Statistiques Globales (Inscriptions + Mensualités)
        $stmt = $db->prepare("SELECT SUM(montant_verse) FROM inscriptions WHERE lycee_id = :lycee_id AND annee_academique_id = :annee_id");
        $stmt->execute(['lycee_id' => $lycee_id, 'annee_id' => $activeYear['id']]);
        $totalInscriptions = $stmt->fetchColumn() ?: 0;

        $stmt = $db->prepare("SELECT SUM(montant) FROM mensualite_details md JOIN mensualites m ON md.mensualite_id = m.id_mensualite WHERE m.lycee_id = :lycee_id AND m.annee_academique_id = :annee_id");
        $stmt->execute(['lycee_id' => $lycee_id, 'annee_id' => $activeYear['id']]);
        $totalMensualites = $stmt->fetchColumn() ?: 0;

        $totalGlobal = $totalInscriptions + $totalMensualites;

        // 2. Statistiques Temporelles (Aujourd'hui et Ce mois)
        $today = date('Y-m-d');
        $thisMonth = date('Y-m');

        $stmt = $db->prepare("
            SELECT SUM(montant) FROM (
                SELECT montant_verse as montant FROM inscriptions WHERE lycee_id = :l_id1 AND DATE(date_inscription) = :d1
                UNION ALL
                SELECT montant FROM mensualite_details md JOIN mensualites m ON md.mensualite_id = m.id_mensualite WHERE m.lycee_id = :l_id2 AND DATE(md.date_paiement) = :d2
            ) as t
        ");
        $stmt->execute(['l_id1' => $lycee_id, 'd1' => $today, 'l_id2' => $lycee_id, 'd2' => $today]);
        $totalToday = $stmt->fetchColumn() ?: 0;

        $stmt = $db->prepare("
            SELECT SUM(montant) FROM (
                SELECT montant_verse as montant FROM inscriptions WHERE lycee_id = :l_id1 AND DATE_FORMAT(date_inscription, '%Y-%m') = :m1
                UNION ALL
                SELECT montant FROM mensualite_details md JOIN mensualites m ON md.mensualite_id = m.id_mensualite WHERE m.lycee_id = :l_id2 AND DATE_FORMAT(md.date_paiement, '%Y-%m') = :m2
            ) as t
        ");
        $stmt->execute(['l_id1' => $lycee_id, 'm1' => $thisMonth, 'l_id2' => $lycee_id, 'm2' => $thisMonth]);
        $totalMonth = $stmt->fetchColumn() ?: 0;

        // 3. Statuts des élèves (Financier)
        // En attente de validation = Dossier créé mais aucun paiement initial
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM eleves e
            WHERE e.lycee_id = :lycee_id
            AND e.statut = 'en_attente_paiement'
            AND NOT EXISTS (SELECT 1 FROM inscriptions i WHERE i.eleve_id = e.id_eleve AND i.annee_academique_id = :annee_id)
        ");
        $stmt->execute(['lycee_id' => $lycee_id, 'annee_id' => $activeYear['id']]);
        $nbEnAttente = $stmt->fetchColumn() ?: 0;

        // Partiellement payés = Inscription existante mais reste_a_payer > 0
        $stmt = $db->prepare("SELECT COUNT(*) FROM inscriptions WHERE lycee_id = :lycee_id AND annee_academique_id = :annee_id AND reste_a_payer > 0");
        $stmt->execute(['lycee_id' => $lycee_id, 'annee_id' => $activeYear['id']]);
        $nbPartiel = $stmt->fetchColumn() ?: 0;

        // Totalement validés = Statut Actif (ce qui implique paiement initial OK)
        $stmt = $db->prepare("SELECT COUNT(*) FROM eleves WHERE lycee_id = :lycee_id AND statut = 'actif'");
        $stmt->execute(['lycee_id' => $lycee_id]);
        $nbActif = $stmt->fetchColumn() ?: 0;

        // 4. Arriérés de scolarité (Inscriptions non soldées)
        $stmt = $db->prepare("SELECT SUM(reste_a_payer) FROM inscriptions WHERE lycee_id = :lycee_id AND annee_academique_id = :annee_id");
        $stmt->execute(['lycee_id' => $lycee_id, 'annee_id' => $activeYear['id']]);
        $arrieresInscriptions = $stmt->fetchColumn() ?: 0;

        // 5. Alertes Financières (Ex: Paiements partiels dépassant un certain délai - simulation pour le dashboard)
        $alerts = [];
        if ($nbPartiel > 0) {
            $alerts[] = [
                'type' => 'warning',
                'message' => "Il y a $nbPartiel élèves avec des inscriptions incomplètes.",
                'link' => '/paiements/pending'
            ];
        }

        // 6. Dernières transactions (plus détaillées)
        $stmt = $db->prepare("
            (SELECT 'Inscription' as type, montant_verse as montant, date_inscription as date, eleve_id, user_id, NULL as mode
             FROM inscriptions
             WHERE lycee_id = :l1)
            UNION ALL
            (SELECT 'Mensualité' as type, md.montant, md.date_paiement as date, m.eleve_id, m.user_id, md.mode_paiement as mode
             FROM mensualite_details md
             JOIN mensualites m ON md.mensualite_id = m.id_mensualite
             WHERE m.lycee_id = :l2)
            ORDER BY date DESC LIMIT 10
        ");
        $stmt->execute(['l1' => $lycee_id, 'l2' => $lycee_id]);
        $recentTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Enrich transactions with student and user names
        foreach ($recentTransactions as &$t) {
            $e = Eleve::findById($t['eleve_id']);
            $t['eleve_nom'] = ($e['prenom'] ?? '') . ' ' . ($e['nom'] ?? '');

            $u = User::findById($t['user_id']);
            $t['caissier'] = ($u['prenom'] ?? '') . ' ' . ($u['nom'] ?? '');
        }

        View::render('paiements/index', [
            'totalGlobal' => $totalGlobal,
            'totalToday' => $totalToday,
            'totalMonth' => $totalMonth,
            'nbEnAttente' => $nbEnAttente,
            'nbPartiel' => $nbPartiel,
            'nbActif' => $nbActif,
            'arrieresInscriptions' => $arrieresInscriptions,
            'recentTransactions' => $recentTransactions,
            'alerts' => $alerts,
            'title' => 'Tableau de bord Comptable'
        ]);
    }

    public function listPending() {
        $this->checkAccess('view');
        $lycee_id = Auth::getLyceeId();
        $activeYear = AnneeAcademique::findActive();

        $db = Database::getInstance();

        // Elèves en attente de paiement initial (jamais payé)
        $stmt = $db->prepare("
            SELECT e.*, c.niveau as nom_classe, 'En attente' as etat_finance, 0 as verse, 0 as reste
            FROM eleves e
            JOIN etudes et ON e.id_eleve = et.eleve_id
            JOIN classes c ON et.classe_id = c.id_classe
            WHERE e.lycee_id = :lycee_id
            AND e.statut = 'en_attente_paiement'
            AND et.annee_academique_id = :annee_id
            AND NOT EXISTS (SELECT 1 FROM inscriptions i WHERE i.eleve_id = e.id_eleve AND i.annee_academique_id = :annee_id)
        ");
        $stmt->execute(['lycee_id' => $lycee_id, 'annee_id' => $activeYear['id']]);
        $en_attente = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Elèves avec paiement partiel
        $stmt = $db->prepare("
            SELECT e.*, c.niveau as nom_classe, 'Partiel' as etat_finance, i.montant_verse as verse, i.reste_a_payer as reste
            FROM eleves e
            JOIN etudes et ON e.id_eleve = et.eleve_id
            JOIN classes c ON et.classe_id = c.id_classe
            JOIN inscriptions i ON e.id_eleve = i.eleve_id AND i.annee_academique_id = :annee_id
            WHERE e.lycee_id = :lycee_id
            AND i.reste_a_payer > 0
        ");
        $stmt->execute(['lycee_id' => $lycee_id, 'annee_id' => $activeYear['id']]);
        $partiels = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $eleves = array_merge($en_attente, $partiels);

        View::render('paiements/pending', [
            'eleves' => $eleves,
            'title' => 'File d\'attente Comptable'
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
            'total' => (float) ($frais['frais_inscription'] ?? 0),
            'verse' => (float) ($inscription['montant_verse'] ?? 0),
        ];

        $options = $inscription ? json_decode($inscription['details_frais'], true) : ['logo' => false, 'carte' => false];

        // Ajouter les frais d'options au total si cochés
        if (!empty($options['logo'])) $fraisInscription['total'] += 2000; // Exemple: 2000 FCFA
        if (!empty($options['carte'])) $fraisInscription['total'] += 3000; // Exemple: 3000 FCFA

        $fraisInscription['reste'] = $fraisInscription['total'] - $fraisInscription['verse'];

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
            'isComptable' => Auth::can('manage', 'paiement')
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
            $hasLogo = isset($_POST['options']['logo']);
            $hasCarte = isset($_POST['options']['carte']);

            if ($hasLogo) $montantTotal += 2000;
            if ($hasCarte) $montantTotal += 3000;

            if ($montantVerse > $montantTotal) {
                throw new Exception("Le montant versé ne peut pas être supérieur au montant total de l'inscription.");
            }

            $resteAPayer = $montantTotal - $montantVerse;

            $detailsFrais = json_encode([
                'logo' => $hasLogo,
                'carte' => $hasCarte
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

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
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

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $_SESSION['error_message'] = "Erreur lors de l'enregistrement des mensualités : " . $e->getMessage();
        }

        header('Location: /paiements/show/' . $eleveId);
        exit();
    }
}
