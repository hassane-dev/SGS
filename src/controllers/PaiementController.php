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
require_once __DIR__ . '/../models/ParamLycee.php';
require_once __DIR__ . '/../models/PolitiqueFinanciere.php';
require_once __DIR__ . '/../models/ParametreFinancierEleve.php';
require_once __DIR__ . '/../models/EtatFinancierEleve.php';
require_once __DIR__ . '/../models/FinanceService.php';
require_once __DIR__ . '/../models/FinancialStatusService.php';
require_once __DIR__ . '/../models/ParamGeneral.php';
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

        // 6. Dernières transactions (groupées par reçu pour éviter les doublons)
        $stmt = $db->prepare("
            SELECT
                GROUP_CONCAT(DISTINCT type SEPARATOR ' + ') as type,
                SUM(montant) as montant,
                MAX(date) as date,
                eleve_id,
                user_id,
                recu_numero,
                MAX(mode) as mode
            FROM (
                (SELECT 'Inscription' as type, montant_verse as montant, date_inscription as date, eleve_id, user_id, recu_numero, 'Espèces' as mode
                 FROM inscriptions
                 WHERE lycee_id = :l1)
                UNION ALL
                (SELECT 'Mensualité' as type, md.montant, md.date_paiement as date, m.eleve_id, m.user_id, md.recu_numero, md.mode_paiement as mode
                 FROM mensualite_details md
                 JOIN mensualites m ON md.mensualite_id = m.id_mensualite
                 WHERE m.lycee_id = :l2)
            ) as t
            GROUP BY recu_numero, eleve_id, user_id
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

        $baseInscription = FinanceService::applyFinancialAdvantages($eleveId, 'frais_inscription', (float)($frais['frais_inscription'] ?? 0));
        $baseLogo = FinanceService::applyFinancialAdvantages($eleveId, 'frais_logo', (float)($frais['frais_logo'] ?? 0));
        $baseCarte = FinanceService::applyFinancialAdvantages($eleveId, 'frais_carte', (float)($frais['frais_carte'] ?? 0));

        $fraisInscription = [
            'total' => $baseInscription,
            'verse' => (float) ($inscription['montant_verse'] ?? 0),
        ];

        $options = $inscription ? json_decode($inscription['details_frais'] ?? '[]', true) : ['logo' => false, 'carte' => false];

        // Ajouter les frais d'options au total si cochés, en utilisant les frais configurés
        if (!empty($options['logo'])) $fraisInscription['total'] += $baseLogo;
        if (!empty($options['carte'])) $fraisInscription['total'] += $baseCarte;

        $fraisInscription['reste'] = $fraisInscription['total'] - $fraisInscription['verse'];

        // Dérivation automatique des mois réels à partir des séquences
        $sequences = Sequence::findAll();
        $mensualitesPayees = Mensualite::findByEtude($etude['id_etude']);
        $tranches = [];
        $fmt = new IntlDateFormatter('fr_FR', IntlDateFormatter::FULL, IntlDateFormatter::NONE, 'Africa/Porto-Novo', IntlDateFormatter::GREGORIAN, 'MMMM');

        $nextRecu = Mensualite::generateReceiptNumber($eleve['lycee_id'] ?? Auth::getLyceeId());
        $seenMonths = [];

        foreach ($sequences as $i => $sequence) {
            $mois = [];
            $current = new DateTime($sequence['date_debut']);
            $end = new DateTime($sequence['date_fin']);

            $safety = 0;
            while ($current <= $end && $safety < 12) {
                $monthName = ucfirst($fmt->format($current));
                if (!in_array($monthName, $seenMonths)) {
                    $mois[] = $monthName;
                    $seenMonths[] = $monthName;
                }
                $current->modify('first day of next month');
                $safety++;
            }

            $paye = [];
            foreach($mois as $m) {
                if (isset($mensualitesPayees[$m])) {
                    $paye[$m] = [
                        'verse' => $mensualitesPayees[$m]['total'],
                        'details' => Mensualite::getDetails($mensualitesPayees[$m]['id'])
                    ];
                }
            }

            $tranches["Tranche " . ($i + 1)] = [
                'mois' => $mois,
                'montant_par_mois' => FinanceService::applyFinancialAdvantages($eleveId, 'frais_mensuel', (float)($frais['frais_mensuel'] ?? 0)),
                'paye' => $paye
            ];
        }

        View::render('paiements/show', [
            'eleve' => $eleve,
            'frais' => $frais,
            'fraisInscription' => $fraisInscription,
            'baseInscription' => $baseInscription,
            'baseLogo' => $baseLogo,
            'baseCarte' => $baseCarte,
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
        $activeYear = AnneeAcademique::findActive();

        if (!$activeYear) {
            $_SESSION['error_message'] = "Aucune année académique active.";
            header('Location: /');
            exit();
        }

        View::render('paiements/restes', [
            'title' => 'Gestion des Restes / Dettes'
        ]);
    }

    public function historique() {
        $this->checkAccess('view');
        $lycee_id = Auth::getLyceeId();
        $db = Database::getInstance();

        $date_debut = $_GET['date_debut'] ?? date('Y-m-01');
        $date_fin = $_GET['date_fin'] ?? date('Y-m-d');
        $search = $_GET['search'] ?? '';

        $sql = "
            SELECT * FROM (
                SELECT
                    i.date_inscription as date,
                    e.nom, e.prenom,
                    c.niveau, c.serie, c.numero,
                    'Inscription' as type,
                    i.montant_verse as montant,
                    'Espèces' as mode,
                    i.recu_numero,
                    u.nom as caissier_nom, u.prenom as caissier_prenom,
                    e.id_eleve,
                    e.identifiant_public
                FROM inscriptions i
                JOIN eleves e ON i.eleve_id = e.id_eleve
                JOIN classes c ON i.classe_id = c.id_classe
                LEFT JOIN utilisateurs u ON i.user_id = u.id_user
                WHERE i.lycee_id = :l1

                UNION ALL

                SELECT
                    md.date_paiement as date,
                    e.nom, e.prenom,
                    c.niveau, c.serie, c.numero,
                    CONCAT('Mensualité (', m.mois_ou_sequence, ')') as type,
                    md.montant,
                    md.mode_paiement as mode,
                    md.recu_numero,
                    u.nom as caissier_nom, u.prenom as caissier_prenom,
                    e.id_eleve,
                    e.identifiant_public
                FROM mensualite_details md
                JOIN mensualites m ON md.mensualite_id = m.id_mensualite
                JOIN eleves e ON m.eleve_id = e.id_eleve
                JOIN classes c ON m.classe_id = c.id_classe
                LEFT JOIN utilisateurs u ON m.user_id = u.id_user
                WHERE m.lycee_id = :l2
            ) as transactions
            WHERE DATE(date) BETWEEN :d1 AND :d2
        ";

        $params = [
            'l1' => $lycee_id,
            'l2' => $lycee_id,
            'd1' => $date_debut,
            'd2' => $date_fin
        ];

        if (!empty($search)) {
            $sql .= " AND (nom LIKE :s1 OR prenom LIKE :s2 OR recu_numero LIKE :s3 OR identifiant_public LIKE :s4)";
            $params['s1'] = "%$search%";
            $params['s2'] = "%$search%";
            $params['s3'] = "%$search%";
            $params['s4'] = "%$search%";
        }

        $sql .= " ORDER BY date DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        View::render('paiements/historique', [
            'title' => 'Historique des Paiements',
            'transactions' => $transactions,
            'filters' => [
                'date_debut' => $date_debut,
                'date_fin' => $date_fin,
                'search' => $search
            ]
        ]);
    }

    public function recus() {
        $this->checkAccess('view');
        $lycee_id = Auth::getLyceeId();
        $db = Database::getInstance();

        $search = $_GET['search'] ?? '';

        $sql = "
            SELECT
                recu_numero,
                MAX(date) as date,
                SUM(montant) as montant_total,
                nom, prenom, id_eleve, identifiant_public,
                GROUP_CONCAT(DISTINCT type SEPARATOR ', ') as types
            FROM (
                SELECT
                    i.recu_numero,
                    i.date_inscription as date,
                    i.montant_verse as montant,
                    e.nom, e.prenom, e.id_eleve,
                    'Inscription' as type,
                    e.identifiant_public
                FROM inscriptions i
                JOIN eleves e ON i.eleve_id = e.id_eleve
                WHERE i.lycee_id = :l1

                UNION ALL

                SELECT
                    md.recu_numero,
                    md.date_paiement as date,
                    md.montant,
                    e.nom, e.prenom, e.id_eleve,
                    'Mensualité' as type,
                    e.identifiant_public
                FROM mensualite_details md
                JOIN mensualites m ON md.mensualite_id = m.id_mensualite
                JOIN eleves e ON m.eleve_id = e.id_eleve
                WHERE m.lycee_id = :l2
            ) as t
            WHERE recu_numero IS NOT NULL
        ";

        $params = ['l1' => $lycee_id, 'l2' => $lycee_id];

        if (!empty($search)) {
            $sql .= " AND (recu_numero LIKE :s1 OR nom LIKE :s2 OR prenom LIKE :s3 OR identifiant_public LIKE :s4)";
            $params['s1'] = "%$search%";
            $params['s2'] = "%$search%";
            $params['s3'] = "%$search%";
            $params['s4'] = "%$search%";
        }

        $sql .= " GROUP BY recu_numero, id_eleve, identifiant_public ORDER BY date DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $recus = $stmt->fetchAll(PDO::FETCH_ASSOC);

        View::render('paiements/recus', [
            'title' => 'Gestion des Reçus',
            'recus' => $recus,
            'search' => $search
        ]);
    }

    public function rapports() {
        $this->checkAccess('view');
        $lycee_id = Auth::getLyceeId();
        $db = Database::getInstance();

        $date_debut = $_GET['date_debut'] ?? date('Y-m-01');
        $date_fin = $_GET['date_fin'] ?? date('Y-m-d');

        // 1. Totaux par type
        $stmt = $db->prepare("
            SELECT type, SUM(montant) as total
            FROM (
                SELECT 'Inscription' as type, montant_verse as montant FROM inscriptions WHERE lycee_id = :l1 AND DATE(date_inscription) BETWEEN :d1 AND :d2
                UNION ALL
                SELECT 'Mensualité' as type, md.montant FROM mensualite_details md JOIN mensualites m ON md.mensualite_id = m.id_mensualite WHERE m.lycee_id = :l2 AND DATE(md.date_paiement) BETWEEN :d3 AND :d4
            ) as t
            GROUP BY type
        ");
        $stmt->execute(['l1' => $lycee_id, 'd1' => $date_debut, 'd2' => $date_fin, 'l2' => $lycee_id, 'd3' => $date_debut, 'd4' => $date_fin]);
        $statsType = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. Totaux par mode de paiement
        $stmt = $db->prepare("
            SELECT mode, SUM(montant) as total
            FROM (
                SELECT 'Espèces' as mode, montant_verse as montant FROM inscriptions WHERE lycee_id = :l1 AND DATE(date_inscription) BETWEEN :d1 AND :d2
                UNION ALL
                SELECT mode_paiement as mode, montant FROM mensualite_details md JOIN mensualites m ON md.mensualite_id = m.id_mensualite WHERE m.lycee_id = :l2 AND DATE(md.date_paiement) BETWEEN :d3 AND :d4
            ) as t
            GROUP BY mode
        ");
        $stmt->execute(['l1' => $lycee_id, 'd1' => $date_debut, 'd2' => $date_fin, 'l2' => $lycee_id, 'd3' => $date_debut, 'd4' => $date_fin]);
        $statsMode = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. Évolution quotidienne
        $stmt = $db->prepare("
            SELECT date, SUM(montant) as total
            FROM (
                SELECT DATE(date_inscription) as date, montant_verse as montant FROM inscriptions WHERE lycee_id = :l1 AND DATE(date_inscription) BETWEEN :d1 AND :d2
                UNION ALL
                SELECT DATE(md.date_paiement) as date, md.montant FROM mensualite_details md JOIN mensualites m ON md.mensualite_id = m.id_mensualite WHERE m.lycee_id = :l2 AND DATE(md.date_paiement) BETWEEN :d3 AND :d4
            ) as t
            GROUP BY date
            ORDER BY date ASC
        ");
        $stmt->execute(['l1' => $lycee_id, 'd1' => $date_debut, 'd2' => $date_fin, 'l2' => $lycee_id, 'd3' => $date_debut, 'd4' => $date_fin]);
        $evolutionQuotidienne = $stmt->fetchAll(PDO::FETCH_ASSOC);

        View::render('paiements/rapports', [
            'title' => 'Rapports Financiers',
            'statsType' => $statsType,
            'statsMode' => $statsMode,
            'evolution' => $evolutionQuotidienne,
            'filters' => [
                'date_debut' => $date_debut,
                'date_fin' => $date_fin
            ]
        ]);
    }

    public function controle() {
        $this->checkAccess('view');
        $lycee_id = Auth::getLyceeId();
        $activeYear = AnneeAcademique::findActive();

        if (!$activeYear) {
            $_SESSION['error_message'] = "Aucune année académique active.";
            header('Location: /');
            exit();
        }

        $db = Database::getInstance();

        // 1. Fetch all students enrolled in the active year
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
                $s['inscription_statut'] = $state['inscription_statut'];
                $s['mensualite_statut'] = $state['mensualite_statut'];
                $s['notes_consultation'] = $state['notes_consultation'];
                $s['bulletin_impression'] = $state['bulletin_impression'];
            }
        }

        // 2. Compute Dashboard statistics
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

        View::render('paiements/controle', [
            'title' => 'Contrôle & États Financiers des Élèves',
            'students' => $students,
            'stats' => $stats,
            'activeYear' => $activeYear
        ]);
    }

    /**
     * Interface de règlement d'un élève débiteur (Point 6).
     */
    public function regler($eleveId) {
        $this->checkAccess('view');

        $anneeActive = AnneeAcademique::findActive();
        if (!$anneeActive) {
            $_SESSION['error_message'] = "Aucune année académique active n'est définie.";
            header('Location: /paiements/restes');
            exit();
        }

        $eleve = Eleve::findById($eleveId);
        $etude = Etude::findByEleveAndAnnee($eleveId, $anneeActive['id'], $eleve['lycee_id'] ?? null);
        $classe = $etude ? Classe::findById($etude['classe_id']) : null;

        if (!$eleve || !$classe) {
            $_SESSION['error_message'] = "L'élève ou sa classe sont introuvables pour l'année en cours.";
            header('Location: /paiements/restes');
            exit();
        }

        $eleve['nom_classe'] = $classe ? Classe::getFormattedName($classe) : 'Non assignée';

        // Récupérer la situation financière via le service centralisé (Point 4 & 6)
        $financialStatus = FinancialStatusService::getStudentFinancialStatus($eleveId, $anneeActive['id']);

        // Récupérer les paramètres généraux (pour les modes de paiement)
        $paramGeneral = ParamGeneral::findByLyceeId($eleve['lycee_id'] ?? Auth::getLyceeId());
        $nextRecu = Mensualite::generateReceiptNumber($eleve['lycee_id'] ?? Auth::getLyceeId());

        View::render('paiements/regler', [
            'eleve' => $eleve,
            'financialStatus' => $financialStatus,
            'paramGeneral' => $paramGeneral,
            'nextRecu' => $nextRecu,
            'isComptable' => Auth::can('manage', 'paiement')
        ]);
    }

    /**
     * Charge et retourne la liste filtrée des restes par classe (Point 1, 2, 8).
     */
    public function classRestes($classeId) {
        $this->checkAccess('view');
        $lyceeId = Auth::getLyceeId();
        $activeYear = AnneeAcademique::findActive();

        if (!$activeYear) {
            echo '<div class="alert alert-danger">Aucune année académique active.</div>';
            exit();
        }

        $classe = Classe::findById($classeId);
        if (!$classe || $classe['lycee_id'] != $lyceeId) {
            echo '<div class="alert alert-danger">Classe introuvable.</div>';
            exit();
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT e.*, e.identifiant_public AS matricule, et.id_etude
            FROM eleves e
            JOIN etudes et ON e.id_eleve = et.eleve_id
            WHERE et.classe_id = :classe_id
            AND et.annee_academique_id = :annee_id
            AND (e.statut = 'actif' OR e.statut = 'en_attente_paiement')
            ORDER BY e.nom, e.prenom
        ");
        $stmt->execute(['classe_id' => $classeId, 'annee_id' => $activeYear['id']]);
        $eleves = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $debtors = [];
        foreach ($eleves as $eleve) {
            $status = FinancialStatusService::getStudentFinancialStatus($eleve['id_eleve'], $activeYear['id']);
            if ($status && $status['total_reste'] > 0.01) {
                $eleve['nom_classe'] = Classe::getFormattedName($classe);
                $eleve['reste_inscription'] = $status['reste_inscription'];
                $eleve['reste_mensualite'] = $status['reste_mensualite'];
                $eleve['total_reste'] = $status['total_reste'];
                $debtors[] = $eleve;
            }
        }

        View::render('paiements/restes_table', [
            'restes' => $debtors
        ]);
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

            // Récupérer le mode de paiement par défaut depuis les paramètres
            $paramGeneral = ParamGeneral::findByLyceeId($lyceeId);
            $modes = !empty($paramGeneral['modalite_paiement'])
                ? explode(',', $paramGeneral['modalite_paiement'])
                : ['Espèces'];

            // On récupère le mode et la référence si fournis (depuis mensualites/pay), sinon auto
            $modePaiement = $_POST['mode_paiement'] ?? trim($modes[0]);
            $reference = $_POST['reference_transaction'] ?? Mensualite::generateReceiptNumber($lyceeId);
            $paiementEffectue = false;

            // 1. Traitement Inscription
            $montantInscription = (float) ($_POST['montant_inscription'] ?? 0);
            $options_posted = $_POST['options'] ?? [];

            $classe = Classe::findById($etude['classe_id']);
            $frais = Frais::findForClasse($classe, $anneeActive['id']);
            $inscription = Inscription::findByEleveAndAnnee($eleveId, $anneeActive['id'], $lyceeId);

            $old_options = $inscription ? json_decode($inscription['details_frais'] ?? '[]', true) : [];
            $hasLogo = !empty($old_options['logo']) || isset($options_posted['logo']);
            $hasCarte = !empty($old_options['carte']) || isset($options_posted['carte']);

            // Calculate adjusted base registration fees for the student
            $baseInscription = FinanceService::applyFinancialAdvantages($eleveId, 'frais_inscription', (float)$frais['frais_inscription']);
            $baseLogo = FinanceService::applyFinancialAdvantages($eleveId, 'frais_logo', (float)($frais['frais_logo'] ?? 0));
            $baseCarte = FinanceService::applyFinancialAdvantages($eleveId, 'frais_carte', (float)($frais['frais_carte'] ?? 0));

            $montantTotalInscription = $baseInscription;
            if ($hasLogo) $montantTotalInscription += $baseLogo;
            if ($hasCarte) $montantTotalInscription += $baseCarte;

            if ($montantInscription > 0 || (isset($options_posted['logo']) && empty($old_options['logo'])) || (isset($options_posted['carte']) && empty($old_options['carte']))) {

                $nouveauVerse = (float)($inscription['montant_verse'] ?? 0) + $montantInscription;

                if ($nouveauVerse > $montantTotalInscription + 0.01) {
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
            }

            // 2. Traitement Mensualités
            $montantMensualitesPool = (float) ($_POST['montant_mensualites'] ?? 0);
            if ($montantMensualitesPool > 0) {
                // Get financial status which has details_mensualites in chronological order
                $status = FinancialStatusService::getStudentFinancialStatus($eleveId, $anneeActive['id']);

                foreach ($status['details_mensualites'] as $dm) {
                    if ($montantMensualitesPool <= 0) {
                        break;
                    }

                    $resteMonth = (float)$dm['reste'];
                    if ($resteMonth <= 0) {
                        continue;
                    }

                    // Amount to allocate to this month
                    $allocated = min($montantMensualitesPool, $resteMonth);
                    $montantMensualitesPool -= $allocated;

                    $m_cap = ucfirst($dm['mois']);

                    $dataMensualite = [
                        'etude_id' => $etude['id_etude'],
                        'eleve_id' => $eleveId,
                        'classe_id' => $etude['classe_id'],
                        'lycee_id' => $lyceeId,
                        'annee_academique_id' => $anneeActive['id'],
                        'mois_ou_sequence' => $m_cap,
                        'montant_verse' => $allocated,
                        'montant_attendu' => (float)$dm['attendu'],
                        'user_id' => $userId
                    ];

                    $mensualiteId = Mensualite::findOrCreate($dataMensualite);
                    Mensualite::addDetail([
                        'mensualite_id' => $mensualiteId,
                        'montant' => $allocated,
                        'mode_paiement' => $modePaiement,
                        'reference_transaction' => $reference,
                        'recu_numero' => $reference
                    ]);
                    $paiementEffectue = true;
                }

                if ($montantMensualitesPool > 0.01) {
                    throw new Exception("Le versement de mensualités dépasse le total des dettes exigibles.");
                }
            } elseif (!empty($_POST['mensualites'])) {
                // Fallback to manual selection for backward compatibility
                $mensualitesPayees = Mensualite::findByEtude($etude['id_etude']);

                // Calculate adjusted monthly fees for the student
                $montantMensuelAttendu = FinanceService::applyFinancialAdvantages($eleveId, 'frais_mensuel', (float)($frais['frais_mensuel'] ?? 0));

                foreach ($_POST['mensualites'] as $mois => $montant) {
                    $montant = (float) $montant;
                    $mois = trim($mois);
                    if ($montant > 0) {
                        // Validation : Ne pas dépasser le montant mensuel attendu
                        $dejaVerse = isset($mensualitesPayees[$mois]) ? (float)$mensualitesPayees[$mois]['total'] : 0;
                        if ($dejaVerse + $montant > $montantMensuelAttendu + 0.01) {
                            throw new Exception("Le versement pour le mois de $mois dépasse le montant attendu. (Attendu: $montantMensuelAttendu, Déjà versé: $dejaVerse)");
                        }

                        $dataMensualite = [
                            'etude_id' => $etude['id_etude'],
                            'eleve_id' => $eleveId,
                            'classe_id' => $etude['classe_id'],
                            'lycee_id' => $lyceeId,
                            'annee_academique_id' => $anneeActive['id'],
                            'mois_ou_sequence' => $mois,
                            'montant_verse' => $montant,
                            'montant_attendu' => $montantMensuelAttendu,
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

            // 3. Logique d'activation (Évaluée à chaque paiement réussi)
            $currentInscription = Inscription::findByEleveAndAnnee($eleveId, $anneeActive['id'], $lyceeId);
            $nouveauVerse = $currentInscription ? (float)$currentInscription['montant_verse'] : 0.00;

            $policy = PolitiqueFinanciere::findOrCreate($lyceeId);
            $seuil_type = $policy['activation_seuil_type'] ?? '100';
            $seuil_valeur = (float)($policy['activation_seuil_valeur'] ?? 0);

            $threshold_met = false;
            if ($seuil_type === '100') {
                $threshold_met = ($nouveauVerse >= $montantTotalInscription - 0.01);
            } elseif ($seuil_type === '75') {
                $threshold_met = ($nouveauVerse >= ($montantTotalInscription * 0.75) - 0.01);
            } elseif ($seuil_type === '50') {
                $threshold_met = ($nouveauVerse >= ($montantTotalInscription * 0.5) - 0.01);
            } elseif ($seuil_type === 'montant_minimum') {
                $threshold_met = ($nouveauVerse >= $seuil_valeur - 0.01);
            }

            $typeLycee = ParamLycee::findByLyceeId($lyceeId)['type_lycee'] ?? 'prive';

            if ($typeLycee === 'public' || $threshold_met) {
                // Mise à jour du statut global
                Eleve::updateStatus($eleveId, 'actif');
                Etude::activate($etude['id_etude'], $userId);

                // Nettoyage des notifications d'inscription
                Notification::markAsReadByLink("/paiements/show/{$eleveId}", $lyceeId);
                Notification::markAsReadByLink("/paiements/pending", $lyceeId);
                Notification::markAsReadByLink("/eleves/details?id={$eleveId}", $lyceeId);

                // Notification de succès pour l'administration
                if ($nouveauVerse >= $montantTotalInscription - 0.01) {
                    Notification::notifyRole('admin_local', $lyceeId, "Inscription soldée et élève activé : {$eleve['prenom']} {$eleve['nom']}.", "/eleves/details?id={$eleveId}");
                }
            }

            // Recalculate consolidated financial state of the student
            FinanceService::updateFinancialState($eleveId);

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
