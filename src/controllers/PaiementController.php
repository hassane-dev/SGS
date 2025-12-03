<?php

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/Eleve.php';
require_once __DIR__ . '/../models/Inscription.php';
require_once __DIR__ . '/../models/Mensualite.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/AnneeAcademique.php';
require_once __DIR__ . '/../models/Frais.php';
require_once __DIR__ . '/../models/Sequence.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../models/Etude.php';
require_once __DIR__ . '/../core/View.php';

class PaiementController extends Controller {

    /**
     * Affiche l'interface de paiement pour un élève donné.
     */
    public function show($eleveId) {
        if (!Auth::can('paiement', 'view')) {
            View::render('errors/403');
            return;
        }

        $anneeActive = AnneeAcademique::getActive();
        if (!$anneeActive) {
            // Gérer l'erreur : aucune année active
            $_SESSION['error_message'] = "Aucune année académique active n'est définie.";
            self::redirect('/eleves');
            return;
        }

        $eleve = Eleve::findById($eleveId);
        $etude = Etude::findByEleveAndAnnee($eleveId, $anneeActive['id']);
        $classe = $etude ? Classe::findById($etude['classe_id']) : null;

        if (!$eleve || !$classe) {
            $_SESSION['error_message'] = "L'élève ou sa classe sont introuvables pour l'année en cours.";
            self::redirect('/eleves');
            return;
        }

        $eleve['nom_classe'] = $classe ? Classe::getFormattedName($classe) : 'Non assignée';

        // Récupérer les frais et l'inscription
        $frais = Frais::findForClasse($classe, $anneeActive['id']);
        $inscription = Inscription::findByEleveAndAnnee($eleveId, $anneeActive['id']);

        $fraisInscription = [
            'total' => $frais['frais_inscription'] ?? 0,
            'verse' => $inscription['montant_verse'] ?? 0,
        ];
        $fraisInscription['reste'] = $fraisInscription['total'] - $fraisInscription['verse'];

        $options = $inscription ? json_decode($inscription['details_frais'], true) : ['logo' => false, 'carte' => false];

        // Dérivation automatique des mois réels à partir des séquences
        $sequences = Sequence::findByAnnee($anneeActive['id']);
        $mensualitesPayees = Mensualite::findByEleveAndAnnee($eleveId, $anneeActive['id']);
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
                if (isset($mensualitesPayees[ucfirst($m)])) {
                    $paye[ucfirst($m)] = $mensualitesPayees[ucfirst($m)];
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
            'tranches' => $tranches, // Sera dynamique
            'mensualitesPayees' => $mensualitesPayees,
            'isComptable' => Auth::can('paiement', 'manage')
        ]);
    }

    /**
     * Traite le paiement des frais d'inscription.
     */
    public function processInscription($eleveId) {
        if (!Auth::can('paiement', 'manage')) {
            View::render('errors/403');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            self::redirect('/paiements/show/' . $eleveId);
            return;
        }

        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $anneeActive = AnneeAcademique::getActive();
            $eleve = Eleve::findById($eleveId);
            $etude = Etude::findByEleveAndAnnee($eleveId, $anneeActive['id']);
            $classe = Classe::findById($etude['classe_id']);
            $frais = Frais::findForClasse($classe, $anneeActive['id']);
            $inscription = Inscription::findByEleveAndAnnee($eleveId, $anneeActive['id']);

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
                Etude::activate($etude['id_etude']);

                // Notifier l'admin local que l'inscription est finalisée
                $eleve_nom_complet = $eleve['prenom'] . ' ' . $eleve['nom'];
                $message = "Paiement initial validé et inscription activée pour {$eleve_nom_complet}.";
                $link = "/eleves/details/{$eleveId}"; // Lien vers le profil de l'élève
                Notification::notifyRole('admin_local', Auth::getLyceeId(), $message, $link);
            }

            $db->commit();
            $_SESSION['success_message'] = "Le paiement de l'inscription a été enregistré avec succès.";

        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['error_message'] = "Erreur lors de l'enregistrement du paiement : " . $e->getMessage();
        }

        self::redirect('/paiements/show/' . $eleveId);
    }

    /**
    /**
     * Traite le paiement des mensualités.
     */
    public function processMensualites($eleveId) {
        if (!Auth::can('paiement', 'manage')) {
            View::render('errors/403');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['mensualites'])) {
            self::redirect('/paiements/show/' . $eleveId);
            return;
        }

        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $anneeActive = AnneeAcademique::getActive();
            $etude = Etude::findByEleveAndAnnee($eleveId, $anneeActive['id']);

            $paiements = $_POST['mensualites'];
            $userId = Auth::user()['id'];
            $lyceeId = Auth::getLyceeId();
            $classeId = $etude['classe_id'];

            foreach ($paiements as $mois => $montant) {
                $montant = (float) $montant;
                if ($montant > 0) {
                    $data = [
                        'eleve_id' => $eleveId,
                        'classe_id' => $classeId,
                        'lycee_id' => $lyceeId,
                        'annee_academique_id' => $anneeActive['id'],
                        'mois_ou_sequence' => ucfirst($mois),
                        'montant_verse' => $montant,
                        'user_id' => $userId
                    ];
                    Mensualite::save($data);
                }
            }

            $db->commit();
            $_SESSION['success_message'] = "Les paiements des mensualités ont été enregistrés avec succès.";

        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['error_message'] = "Erreur lors de l'enregistrement des mensualités : " . $e->getMessage();
        }

        self::redirect('/paiements/show/' . $eleveId);
    }
}
