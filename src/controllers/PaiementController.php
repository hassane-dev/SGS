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
require_once __DIR__ . '/../config/database.php';
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
            SELECT e.*, c.id_classe, c.niveau, c.serie, c.cycle_id, c.niveau as nom_classe, 'En attente' as etat_finance, 0 as verse, 0 as reste
            FROM eleves e
            JOIN etudes et ON e.id_eleve = et.eleve_id
            JOIN classes c ON et.classe_id = c.id_classe
            WHERE e.lycee_id = :lycee_id
            AND e.statut = 'en_attente_paiement'
            AND et.annee_academique_id = :annee_id1
            AND NOT EXISTS (SELECT 1 FROM inscriptions i WHERE i.eleve_id = e.id_eleve AND i.annee_academique_id = :annee_id2)
        ");
        $stmt->execute(['lycee_id' => $lycee_id, 'annee_id1' => $activeYear['id'], 'annee_id2' => $activeYear['id']]);
        $en_attente = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculer le montant réel attendu pour ceux qui n'ont rien payé
        foreach ($en_attente as &$e) {
            $frais = Frais::findForClasse($e, $activeYear['id']);
            if ($frais) {
                $e['reste'] = (float)$frais['frais_inscription'];
            }
        }

        // Elèves avec paiement partiel - On ne les affiche plus ici s'ils sont déjà actifs
        // car ils basculent dans "Gestion des restes"
        $stmt = $db->prepare("
            SELECT e.*, c.niveau as nom_classe, 'Partiel' as etat_finance, i.montant_verse as verse, i.reste_a_payer as reste
            FROM eleves e
            JOIN etudes et ON e.id_eleve = et.eleve_id
            JOIN classes c ON et.classe_id = c.id_classe
            JOIN inscriptions i ON e.id_eleve = i.eleve_id AND i.annee_academique_id = :annee_id
            WHERE e.lycee_id = :lycee_id
            AND e.statut = 'en_attente_paiement'
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
    public function regulariserInscription($eleveId) {
        $this->checkAccess('view');
        $anneeActive = AnneeAcademique::findActive();
        $eleve = Eleve::findById($eleveId);
        $etude = Etude::findByEleveAndAnnee($eleveId, $anneeActive['id']);
        $classe = Classe::findById($etude['classe_id']);
        $frais = Frais::findForClasse($classe, $anneeActive['id']);
        $inscription = Inscription::findByEleveAndAnnee($eleveId, $anneeActive['id']);

        $fraisInscription = [
            'total' => (float) ($inscription['montant_total'] ?? 0),
            'verse' => (float) ($inscription['montant_verse'] ?? 0),
        ];
        $fraisInscription['reste'] = $fraisInscription['total'] - $fraisInscription['verse'];

        $nextRecu = Mensualite::generateReceiptNumber($eleve['lycee_id']);

        View::render('paiements/inscription_pay', [
            'eleve' => $eleve,
            'fraisInscription' => $fraisInscription,
            'nextRecu' => $nextRecu
        ]);
    }

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

        // Récupérer les paramètres généraux (pour les modes de paiement)
        $paramGeneral = ParamGeneral::findByLyceeId($eleve['lycee_id'] ?? Auth::getLyceeId());

        // Récupérer les frais et l'inscription
        $frais = Frais::findForClasse($classe, $anneeActive['id']);
        $inscription = Inscription::findByEleveAndAnnee($eleveId, $anneeActive['id'], $eleve['lycee_id'] ?? null);

        $fraisInscription = [
            'total' => (float) ($frais['frais_inscription'] ?? 0),
            'verse' => (float) ($inscription['montant_verse'] ?? 0),
        ];

        $options = $inscription ? json_decode($inscription['details_frais'], true) : ['logo' => false, 'carte' => false];

        // Ajouter les frais d'options au total si cochés, en utilisant les frais configurés
        if (!empty($options['logo'])) $fraisInscription['total'] += (float)($frais['frais_logo'] ?? 0);
        if (!empty($options['carte'])) $fraisInscription['total'] += (float)($frais['frais_carte'] ?? 0);

        $fraisInscription['reste'] = $fraisInscription['total'] - $fraisInscription['verse'];

        // Dérivation automatique des mois réels à partir des séquences
        $sequences = Sequence::findAll();
        $mensualitesPayees = Mensualite::findByEtude($etude['id_etude']);
        $tranches = [];
        $fmt = new IntlDateFormatter('fr_FR', IntlDateFormatter::FULL, IntlDateFormatter::NONE, 'Africa/Porto-Novo', IntlDateFormatter::GREGORIAN, 'MMMM');

        $nextRecu = Mensualite::generateReceiptNumber($eleve['lycee_id'] ?? Auth::getLyceeId());

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
            'frais' => $frais,
            'fraisInscription' => $fraisInscription,
            'inscription' => $inscription,
            'options' => $options,
            'tranches' => $tranches,
            'mensualitesPayees' => $mensualitesPayees,
            'paramGeneral' => $paramGeneral,
            'nextRecu' => $nextRecu,
            'isComptable' => Auth::can('manage', 'paiement')
        ]);
    }

    /**
     * Gestion des restes à payer (Inscriptions et Mensualités).
     */
    public function restes() {
        $this->checkAccess('view');
        $lycee_id = Auth::getLyceeId();
        $activeYear = AnneeAcademique::findActive();

        if (!$activeYear) {
            $_SESSION['error_message'] = "Aucune année académique active.";
            header('Location: /');
            exit();
        }

        $db = Database::getInstance();

        // 1. Restes d'inscription
        $stmt = $db->prepare("
            SELECT i.reste_a_payer as montant, i.date_inscription as date, 'Inscription' as type, e.nom, e.prenom, e.id_eleve, c.niveau, c.serie, c.numero
            FROM inscriptions i
            JOIN eleves e ON i.eleve_id = e.id_eleve
            JOIN etudes et ON i.etude_id = et.id_etude
            JOIN classes c ON et.classe_id = c.id_classe
            WHERE i.lycee_id = :l1 AND i.annee_academique_id = :a1 AND i.reste_a_payer > 0
        ");
        $stmt->execute(['l1' => $lycee_id, 'a1' => $activeYear['id']]);
        $restesInscription = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. Restes de mensualités
        // On récupère tous les élèves actifs
        $stmt = $db->prepare("
            SELECT e.id_eleve, e.nom, e.prenom, c.id_classe, c.niveau, c.serie, c.numero, et.id_etude
            FROM eleves e
            JOIN etudes et ON e.id_eleve = et.eleve_id
            JOIN classes c ON et.classe_id = c.id_classe
            WHERE e.lycee_id = :l AND et.annee_academique_id = :a AND e.statut = 'actif'
        ");
        $stmt->execute(['l' => $lycee_id, 'a' => $activeYear['id']]);
        $elevesActifs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sequences = Sequence::findAll();
        $fmt = new IntlDateFormatter('fr_FR', IntlDateFormatter::FULL, IntlDateFormatter::NONE, 'Africa/Porto-Novo', IntlDateFormatter::GREGORIAN, 'MMMM');

        $monthsToDate = [];
        $today = new DateTime();
        foreach ($sequences as $seq) {
            $current = new DateTime($seq['date_debut']);
            $end = new DateTime($seq['date_fin']);
            while ($current <= $end && $current <= $today) {
                $monthsToDate[] = ucfirst($fmt->format($current));
                $current->modify('first day of next month');
            }
        }

        $restesMensualites = [];
        foreach ($elevesActifs as $eleve) {
            $frais = Frais::findForClasse($eleve, $activeYear['id']);
            if (!$frais) continue;

            $attenduParMois = (float)($frais['frais_mensuel'] ?? 0);
            if ($attenduParMois <= 0) continue;

            $payes = Mensualite::findByEtude($eleve['id_etude']);

            foreach ($monthsToDate as $month) {
                $verse = isset($payes[$month]) ? (float)$payes[$month]['total'] : 0;
                if ($verse < $attenduParMois) {
                    $restesMensualites[] = [
                        'montant' => $attenduParMois - $verse,
                        'date' => null,
                        'type' => 'Mensualité (' . $month . ')',
                        'nom' => $eleve['nom'],
                        'prenom' => $eleve['prenom'],
                        'id_eleve' => $eleve['id_eleve'],
                        'niveau' => $eleve['niveau'],
                        'serie' => $eleve['serie'],
                        'numero' => $eleve['numero']
                    ];
                }
            }
        }

        $allRestes = array_merge($restesInscription, $restesMensualites);

        View::render('paiements/restes', [
            'restes' => $allRestes,
            'title' => 'Gestion des Restes / Dettes'
        ]);
    }

    public function historique() {
        $this->checkAccess('view');
        View::render('paiements/historique', ['title' => 'Historique des Paiements']);
    }

    public function recus() {
        $this->checkAccess('view');
        View::render('paiements/recus', ['title' => 'Gestion des Reçus']);
    }

    public function rapports() {
        $this->checkAccess('view');
        View::render('paiements/rapports', ['title' => 'Rapports Financiers']);
    }

    /**
     * Traite le paiement unifié (Inscription + Mensualités).
     */
    public function processPayment($eleveId) {
        $this->checkAccess('manage');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /paiements/show/' . $eleveId);
            exit();
        }

        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $anneeActive = AnneeAcademique::findActive();
            if (!$anneeActive) throw new Exception("Aucune année académique active.");

            $eleve = Eleve::findById($eleveId);
            if (!$eleve) throw new Exception("Élève non trouvé.");

            $etude = Etude::findByEleveAndAnnee($eleveId, $anneeActive['id'], $eleve['lycee_id'] ?? null);
            if (!$etude) throw new Exception("Dossier académique non trouvé.");

            $lyceeId = $eleve['lycee_id'] ?? Auth::getLyceeId();
            $userId = Auth::user()['id'];
            $modePaiement = $_POST['mode_paiement'] ?? 'Espèces';

            $reference = $_POST['reference_transaction'] ?? null;
            if (empty($reference)) {
                $reference = Mensualite::generateReceiptNumber($lyceeId);
            }

            $paiementEffectue = false;

            // 1. Traitement Inscription
            $montantInscription = (float) ($_POST['montant_inscription'] ?? 0);
            if ($montantInscription > 0 || isset($_POST['options'])) {
                $classe = Classe::findById($etude['classe_id']);
                $frais = Frais::findForClasse($classe, $anneeActive['id']);
                $inscription = Inscription::findByEleveAndAnnee($eleveId, $anneeActive['id'], $lyceeId);

                $hasLogo = isset($_POST['options']['logo']) || (!empty($inscription) && json_decode($inscription['details_frais'] ?? '[]', true)['logo']);
                $hasCarte = isset($_POST['options']['carte']) || (!empty($inscription) && json_decode($inscription['details_frais'] ?? '[]', true)['carte']);

                $montantTotalInscription = (float) $frais['frais_inscription'];
                if ($hasLogo) $montantTotalInscription += (float)($frais['frais_logo'] ?? 0);
                if ($hasCarte) $montantTotalInscription += (float)($frais['frais_carte'] ?? 0);

                $nouveauVerse = (float)($inscription['montant_verse'] ?? 0) + $montantInscription;

                if ($nouveauVerse > $montantTotalInscription + 0.01) { // Tolérance float
                    throw new Exception("Le versement inscription dépasse le total attendu.");
                }

                $dataInscription = [
                    'id_inscription' => $inscription['id_inscription'] ?? null,
                    'etude_id' => $etude['id_etude'],
                    'eleve_id' => $eleveId,
                    'classe_id' => $etude['classe_id'],
                    'lycee_id' => $lyceeId,
                    'annee_academique_id' => $anneeActive['id'],
                    'montant_total' => $montantTotalInscription,
                    'montant_verse' => $nouveauVerse,
                    'reste_a_payer' => max(0, $montantTotalInscription - $nouveauVerse),
                    'details_frais' => json_encode(['logo' => $hasLogo, 'carte' => $hasCarte]),
                    'user_id' => $userId,
                    'recu_numero' => $reference
                ];

                Inscription::save($dataInscription);
                $paiementEffectue = true;

                // Activation si soldé
                if ($dataInscription['reste_a_payer'] <= 0) {
                    Eleve::updateStatus($eleveId, 'actif');
                    Etude::activate($etude['id_etude'], $userId);
                    Notification::notifyRole('admin_local', $lyceeId, "Inscription soldée pour {$eleve['prenom']} {$eleve['nom']}.", "/eleves/details?id={$eleveId}");
                }
            }

            // 2. Traitement Mensualités
            if (!empty($_POST['mensualites'])) {
                foreach ($_POST['mensualites'] as $mois => $montant) {
                    $montant = (float) $montant;
                    if ($montant > 0) {
                        $dataMensualite = [
                            'etude_id' => $etude['id_etude'],
                            'eleve_id' => $eleveId,
                            'classe_id' => $etude['classe_id'],
                            'lycee_id' => $lyceeId,
                            'annee_academique_id' => $anneeActive['id'],
                            'mois_ou_sequence' => ucfirst($mois),
                            'montant_verse' => $montant,
                            'user_id' => $userId
                        ];

                        $mensualiteId = Mensualite::findOrCreate($dataMensualite);
                        Mensualite::addDetail([
                            'mensualite_id' => $mensualiteId,
                            'montant' => $montant,
                            'mode_paiement' => $modePaiement,
                            'reference_transaction' => $reference,
                            'recu_numero' => $reference
                        ]);
                        $paiementEffectue = true;
                    }
                }
            }

            if (!$paiementEffectue) {
                throw new Exception("Aucun montant à encaisser n'a été saisi.");
            }

            // Marquer les notifications "En attente de paiement" comme traitées
            Notification::markAsReadByLink("/paiements/show/{$eleveId}", $lyceeId);

            $db->commit();
            $_SESSION['success_message'] = "Opération d'encaissement réussie. Reçu N° {$reference}";

        } catch (Throwable $e) {
            if (isset($db) && $db->inTransaction()) $db->rollBack();
            $_SESSION['error_message'] = "Erreur lors de l'encaissement : " . $e->getMessage();
        }

        header('Location: /paiements/show/' . $eleveId);
        exit();
    }
}
