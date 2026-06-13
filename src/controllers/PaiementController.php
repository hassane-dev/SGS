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

        // 2. Statistiques Temporelles
        $today = date('Y-m-d');
        $stmt = $db->prepare("
            SELECT SUM(montant) FROM (
                SELECT montant_verse as montant FROM inscriptions WHERE lycee_id = :l_id1 AND DATE(date_inscription) = :d1
                UNION ALL
                SELECT montant FROM mensualite_details md JOIN mensualites m ON md.mensualite_id = m.id_mensualite WHERE m.lycee_id = :l_id2 AND DATE(md.date_paiement) = :d2
            ) as t
        ");
        $stmt->execute(['l_id1' => $lycee_id, 'd1' => $today, 'l_id2' => $lycee_id, 'd2' => $today]);
        $totalToday = $stmt->fetchColumn() ?: 0;

        View::render('paiements/index', [
            'totalGlobal' => $totalGlobal,
            'totalToday' => $totalToday,
            'title' => 'Poste de Travail Comptable'
        ]);
    }

    public function searchClass() {
        $this->checkAccess('view');
        $q = $_GET['q'] ?? '';
        $lycee_id = Auth::getLyceeId();

        $db = Database::getInstance();
        // Recherche intelligente : on cherche dans niveau, serie, numero
        $stmt = $db->prepare("
            SELECT id_classe, niveau, serie, numero
            FROM classes
            WHERE lycee_id = :lycee_id
            AND (niveau LIKE :q OR serie LIKE :q OR CONCAT(niveau, ' ', IFNULL(serie,''), ' ', IFNULL(numero,'')) LIKE :q)
            LIMIT 5
        ");
        $stmt->execute(['lycee_id' => $lycee_id, 'q' => "%$q%"]);
        $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        echo json_encode($classes);
        exit();
    }

    public function classDashboard($classId) {
        $this->checkAccess('view');
        $lycee_id = Auth::getLyceeId();
        $activeYear = AnneeAcademique::findActive();

        $classe = Classe::findById($classId);
        if (!$classe || $classe['lycee_id'] != $lycee_id) {
            echo _("Classe introuvable.");
            exit();
        }

        $db = Database::getInstance();

        // 1. Liste des élèves de la classe avec leur étude et inscription
        $stmt = $db->prepare("
            SELECT e.*, et.id_etude, i.montant_verse, i.reste_a_payer, i.montant_total
            FROM eleves e
            JOIN etudes et ON e.id_eleve = et.eleve_id
            LEFT JOIN inscriptions i ON e.id_eleve = i.eleve_id AND i.annee_academique_id = :annee_id
            WHERE et.classe_id = :classe_id
            AND et.annee_academique_id = :annee_id
            ORDER BY e.nom, e.prenom
        ");
        $stmt->execute(['classe_id' => $classId, 'annee_id' => $activeYear['id']]);
        $eleves = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. Frais configurés pour la classe
        $frais = Frais::findForClasse($classe, $activeYear['id']);
        $montantMensuel = (float)($frais['frais_mensuel'] ?? 0);

        // 3. Déterminer le mois courant
        $locale = $_SESSION['lang'] ?? 'fr_FR';
        $fmt = new IntlDateFormatter($locale, IntlDateFormatter::FULL, IntlDateFormatter::NONE, 'Africa/Porto-Novo', IntlDateFormatter::GREGORIAN, 'MMMM');
        $moisCourant = ucfirst($fmt->format(new DateTime()));

        // 4. Bulk fetch des paiements du mois courant pour toute la classe (Optimisation N+1)
        $stmt_mens = $db->prepare("
            SELECT etude_id, SUM(montant_verse) as total
            FROM mensualites
            WHERE classe_id = :classe_id
            AND annee_academique_id = :annee_id
            AND mois_ou_sequence = :mois
            GROUP BY etude_id
        ");
        $stmt_mens->execute([
            'classe_id' => $classId,
            'annee_id' => $activeYear['id'],
            'mois' => $moisCourant
        ]);
        $mensPayees = $stmt_mens->fetchAll(PDO::FETCH_KEY_PAIR);

        // 5. Calcul des stats et enrichissement
        $stats = [
            'total' => count($eleves),
            'a_jour' => 0,
            'partiel' => 0,
            'impaye' => 0,
            'dette_totale' => 0
        ];

        foreach ($eleves as &$e) {
            $totalMoisCourant = (float)($mensPayees[$e['id_etude']] ?? 0);
            $payeMoisCourant = ($totalMoisCourant >= $montantMensuel);

            if ($e['reste_a_payer'] > 0) {
                $e['statut_finance'] = '🔴 ' . _("Impayé (Inscr.)");
                $stats['impaye']++;
            } elseif (!$payeMoisCourant) {
                $e['statut_finance'] = '🟡 ' . _("Partiel");
                $stats['partiel']++;
            } else {
                $e['statut_finance'] = '🟢 ' . _("À jour");
                $stats['a_jour']++;
            }

            $e['dette'] = (float)($e['reste_a_payer'] ?? 0);
            $stats['dette_totale'] += $e['dette'];
            $e['mois_courant'] = $moisCourant;
            $e['montant_mensuel'] = $montantMensuel;
        }

        $stats['pct_a_jour'] = $stats['total'] > 0 ? round(($stats['a_jour'] / $stats['total']) * 100) : 0;

        View::renderPartial('paiements/_class_dashboard', [
            'classe' => $classe,
            'eleves' => $eleves,
            'stats' => $stats,
            'moisCourant' => $moisCourant
        ]);
        exit();
    }

    public function quickPay() {
        $this->checkAccess('manage');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit();

        $eleveId = $_POST['eleve_id'];
        $montant = (float)$_POST['montant'];
        $mois = $_POST['mois'];
        $lyceeId = Auth::getLyceeId();
        $userId = Auth::user()['id'];
        $activeYear = AnneeAcademique::findActive();

        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare("SELECT id_etude, classe_id FROM etudes WHERE eleve_id = :e_id AND annee_academique_id = :a_id");
            $stmt->execute(['e_id' => $eleveId, 'a_id' => $activeYear['id']]);
            $etude = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$etude) throw new Exception("Étude non trouvée.");

            $reference = Mensualite::generateReceiptNumber($lyceeId);

            $dataMensualite = [
                'etude_id' => $etude['id_etude'],
                'eleve_id' => $eleveId,
                'classe_id' => $etude['classe_id'],
                'lycee_id' => $lyceeId,
                'annee_academique_id' => $activeYear['id'],
                'mois_ou_sequence' => $mois,
                'montant_verse' => $montant,
                'user_id' => $userId
            ];

            $mensualiteId = Mensualite::findOrCreate($dataMensualite);
            Mensualite::addDetail([
                'mensualite_id' => $mensualiteId,
                'montant' => $montant,
                'mode_paiement' => 'Espèces',
                'recu_numero' => $reference
            ]);

            $db->commit();
            echo json_encode(['success' => true, 'message' => "Paiement réussi : $reference"]);
        } catch (Exception $e) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
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
