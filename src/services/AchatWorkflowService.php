<?php
// src/services/AchatWorkflowService.php

require_once __DIR__ . '/../config/database.php';
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
require_once __DIR__ . '/../models/BudgetLigne.php';
require_once __DIR__ . '/../models/BudgetEngagement.php';
require_once __DIR__ . '/../models/SessionCaisse.php';
require_once __DIR__ . '/../models/CompteFinancier.php';
require_once __DIR__ . '/BudgetService.php';
require_once __DIR__ . '/BudgetControlService.php';
require_once __DIR__ . '/ComptabiliteService.php';
require_once __DIR__ . '/../models/TreasuryService.php';

class AchatWorkflowService {

    public static function createDemande($lyceeId, $demandeurId, $justification, $items) {
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            if (empty($items)) {
                throw new Exception("Une demande d'achat doit contenir au moins une ligne d'article.");
            }

            $demandeId = AchatDemande::create([
                'lycee_id' => $lyceeId,
                'demandeur_id' => $demandeurId,
                'justification' => $justification,
                'date_demande' => date('Y-m-d'),
                'statut' => 'en_attente_approbation'
            ]);

            foreach ($items as $item) {
                $article = AchatArticle::findById($item['article_id']);
                if (!$article) {
                    throw new Exception("Article introuvable ID: " . $item['article_id']);
                }

                // Check budget availability dynamically if budget line is provided
                if (!empty($item['budget_ligne_id'])) {
                    $avail = BudgetControlService::checkAvailability($item['budget_ligne_id'], $item['quantite'] * $item['prix_unitaire_estime']);
                    if (!$avail['disponible']) {
                        throw new Exception("Crédits budgétaires insuffisants sur la ligne ID: " . $item['budget_ligne_id']);
                    }
                }

                AchatDemandeLigne::create([
                    'demande_id' => $demandeId,
                    'article_id' => $item['article_id'],
                    'quantite_demandee' => $item['quantite'],
                    'prix_unitaire_estime' => $item['prix_unitaire_estime'],
                    'budget_ligne_id' => $item['budget_ligne_id'] ?? null
                ]);
            }

            $db->commit();
            return $demandeId;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public static function approveDemande($id, $userId, $motif = null) {
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $demande = AchatDemande::findById($id);
            if (!$demande) {
                throw new Exception("Demande d'achat introuvable.");
            }
            if ($demande['statut'] !== 'en_attente_approbation') {
                throw new Exception("Cette demande d'achat n'est pas en attente d'approbation.");
            }

            // Segregation of duties: demandeur != approbateur
            if ($demande['demandeur_id'] == $userId) {
                throw new Exception("Séparation des tâches : vous ne pouvez pas approuver votre propre demande d'achat.");
            }

            // Reserve budget polymorphically for each line
            $lignes = AchatDemandeLigne::findByDemande($id);
            foreach ($lignes as $ligne) {
                if (!empty($ligne['budget_ligne_id'])) {
                    $total = $ligne['quantite_demandee'] * $ligne['prix_unitaire_estime'];
                    // Polymorphic reservation
                    BudgetService::reserve(null, $total, $ligne['budget_ligne_id'], 'achat_demandes', $ligne['id']);
                }
            }

            AchatDemande::updateStatus($id, 'approuvee', $userId, $motif);
            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public static function rejectDemande($id, $userId, $motif = null) {
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $demande = AchatDemande::findById($id);
            if (!$demande) {
                throw new Exception("Demande d'achat introuvable.");
            }
            if ($demande['statut'] !== 'en_attente_approbation') {
                throw new Exception("Cette demande d'achat n'est pas en attente d'approbation.");
            }

            if ($demande['demandeur_id'] == $userId) {
                throw new Exception("Séparation des tâches : vous ne pouvez pas rejeter votre propre demande d'achat.");
            }

            AchatDemande::updateStatus($id, 'rejete', $userId, $motif);
            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public static function cancelDemande($id, $userId, $motif = null) {
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $demande = AchatDemande::findById($id);
            if (!$demande) {
                throw new Exception("Demande d'achat introuvable.");
            }
            if ($demande['statut'] !== 'approuvee') {
                throw new Exception("Seule une demande d'achat approuvée peut être annulée.");
            }

            // Release budget polymorphically
            $lignes = AchatDemandeLigne::findByDemande($id);
            foreach ($lignes as $ligne) {
                if (!empty($ligne['budget_ligne_id'])) {
                    BudgetService::release(null, 'achat_demandes', $ligne['id']);
                }
            }

            AchatDemande::updateStatus($id, 'annule', $userId, $motif);
            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public static function createCommande($lyceeId, $demandeId, $fournisseurId, $numeroCommande, $creePar, $items) {
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $fournisseur = Fournisseur::findById($fournisseurId);
            if (!$fournisseur || !$fournisseur['actif']) {
                throw new Exception("Fournisseur introuvable ou inactif.");
            }

            if (empty($items)) {
                throw new Exception("Un bon de commande doit contenir au moins une ligne d'article.");
            }

            $commandeId = AchatCommande::create([
                'lycee_id' => $lyceeId,
                'demande_id' => $demandeId,
                'fournisseur_id' => $fournisseurId,
                'numero_commande' => $numeroCommande,
                'date_commande' => date('Y-m-d'),
                'statut' => 'emis',
                'cree_par' => $creePar
            ]);

            foreach ($items as $item) {
                AchatCommandeLigne::create([
                    'commande_id' => $commandeId,
                    'demande_ligne_id' => $item['demande_ligne_id'] ?? null,
                    'article_id' => $item['article_id'],
                    'quantite_commandee' => $item['quantite_commandee'],
                    'prix_unitaire_negocie' => $item['prix_unitaire_negocie']
                ]);

                // Engage budget if coming from a reserved demand line
                if (!empty($item['demande_ligne_id'])) {
                    BudgetService::engage(null, 'achat_demandes', $item['demande_ligne_id']);
                }
            }

            $db->commit();
            return $commandeId;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public static function receiveCommande($lyceeId, $commandeId, $numeroReception, $receptionnePar, $items) {
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $commande = AchatCommande::findById($commandeId);
            if (!$commande || !in_array($commande['statut'], ['emis', 'reception_partielle'])) {
                throw new Exception("Le bon de commande n'est pas éligible à la réception.");
            }

            if (empty($items)) {
                throw new Exception("Un bon de réception doit contenir au moins un article.");
            }

            $receptionId = AchatReception::create([
                'lycee_id' => $lyceeId,
                'commande_id' => $commandeId,
                'numero_reception' => $numeroReception,
                'date_reception' => date('Y-m-d'),
                'statut' => 'valide',
                'receptionne_par' => $receptionnePar
            ]);

            $allCompleted = true;
            foreach ($items as $item) {
                $cmdLigne = AchatCommandeLigne::findById($item['commande_ligne_id']);
                if (!$cmdLigne) {
                    throw new Exception("Ligne de commande introuvable.");
                }

                // Check cumulative received
                $stmt_sum = $db->prepare("
                    SELECT SUM(quantite_receptionnee)
                    FROM achat_reception_lignes rl
                    INNER JOIN achat_receptions r ON rl.reception_id = r.id
                    WHERE rl.commande_ligne_id = :cmd_line_id AND r.statut = 'valide'
                ");
                $stmt_sum->execute(['cmd_line_id' => $item['commande_ligne_id']]);
                $alreadyReceived = (float)$stmt_sum->fetchColumn();

                $totalNew = $alreadyReceived + (float)$item['quantite_receptionnee'];
                if ($totalNew > (float)$cmdLigne['quantite_commandee']) {
                    throw new Exception("La quantité réceptionnée totale (" . $totalNew . ") ne peut dépasser le commandé (" . $cmdLigne['quantite_commandee'] . ").");
                }

                AchatReceptionLigne::create([
                    'reception_id' => $receptionId,
                    'commande_ligne_id' => $item['commande_ligne_id'],
                    'quantite_receptionnee' => $item['quantite_receptionnee'],
                    'quantite_refusee' => $item['quantite_refusee'] ?? 0.0000,
                    'motif_refus' => $item['motif_refus'] ?? null
                ]);

                if ($totalNew < (float)$cmdLigne['quantite_commandee']) {
                    $allCompleted = false;
                }
            }

            AchatCommande::updateStatus($commandeId, $allCompleted ? 'executee' : 'reception_partielle');
            $db->commit();
            return $receptionId;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public static function createFacture($lyceeId, $fournisseurId, $commandeId, $receptionId, $referenceFacture, $dateFacture, $dateEcheance, $items) {
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $fournisseur = Fournisseur::findById($fournisseurId);
            if (!$fournisseur) {
                throw new Exception("Fournisseur introuvable.");
            }

            // Enforce account mapping fail-fast
            if (empty($fournisseur['compte_comptable_tiers'])) {
                throw new Exception("Le compte tiers associé au fournisseur n'est pas configuré. Validation annulée.");
            }

            if (empty($items)) {
                throw new Exception("Une facture doit contenir au moins une ligne.");
            }

            // Calculate totals
            $montantHt = 0.00;
            $montantTtc = 0.00;

            foreach ($items as $item) {
                $recLigne = AchatReceptionLigne::findById($item['reception_ligne_id']);
                if (!$recLigne) {
                    throw new Exception("Ligne de réception introuvable.");
                }

                $cmdLigne = AchatCommandeLigne::findById($recLigne['commande_ligne_id']);
                if (!$cmdLigne) {
                    throw new Exception("Ligne de commande introuvable.");
                }

                // 3-Way Matching: Qty Factured <= Qty Received, Price Match
                if ((float)$item['quantite_facturee'] > (float)$recLigne['quantite_receptionnee']) {
                    throw new Exception("Quantité facturée (" . $item['quantite_facturee'] . ") supérieure au réceptionné (" . $recLigne['quantite_receptionnee'] . ").");
                }

                if (abs((float)$item['prix_unitaire_facture'] - (float)$cmdLigne['prix_unitaire_negocie']) > 0.001) {
                    throw new Exception("Le prix unitaire facturé (" . $item['prix_unitaire_facture'] . ") ne correspond pas au prix négocié (" . $cmdLigne['prix_unitaire_negocie'] . ").");
                }

                $ht = round((float)$item['quantite_facturee'] * (float)$item['prix_unitaire_facture'], 2);
                $tva = round($ht * (float)($item['taux_tva_facture'] ?? 0.0000), 2);
                $ttc = $ht + $tva;

                $montantHt += $ht;
                $montantTtc += $ttc;
            }

            $factureId = AchatFacture::create([
                'lycee_id' => $lyceeId,
                'fournisseur_id' => $fournisseurId,
                'commande_id' => $commandeId,
                'reception_id' => $receptionId,
                'reference_facture' => $referenceFacture,
                'date_facture' => $dateFacture,
                'date_echeance' => $dateEcheance,
                'montant_ht' => $montantHt,
                'montant_ttc' => $montantTtc,
                'statut' => 'enregistree'
            ]);

            // Save lines & trigger budget consumption / accounting entries
            $lignesComptables = [];
            foreach ($items as $item) {
                $recLigne = AchatReceptionLigne::findById($item['reception_ligne_id']);
                $cmdLigne = AchatCommandeLigne::findById($recLigne['commande_ligne_id']);
                $article = AchatArticle::findById($cmdLigne['article_id']);
                $cat = AchatCategorie::findById($article['categorie_id']);

                if (empty($cat['compte_comptable_charge'])) {
                    throw new Exception("Le compte de charge de la catégorie d'article n'est pas configuré. Validation annulée.");
                }

                $ht = round((float)$item['quantite_facturee'] * (float)$item['prix_unitaire_facture'], 2);
                $tva = round($ht * (float)($item['taux_tva_facture'] ?? 0.0000), 2);
                $ttc = $ht + $tva;

                AchatFactureLigne::create([
                    'facture_id' => $factureId,
                    'reception_ligne_id' => $item['reception_ligne_id'],
                    'quantite_facturee' => $item['quantite_facturee'],
                    'prix_unitaire_facture' => $item['prix_unitaire_facture'],
                    'taux_tva_facture' => $item['taux_tva_facture'] ?? 0.0000,
                    'montant_ht_ligne' => $ht,
                    'montant_ttc_ligne' => $ttc
                ]);

                // Consume budget polymorphically
                if (!empty($cmdLigne['demande_ligne_id'])) {
                    BudgetService::consume(null, 'achat_demandes', $cmdLigne['demande_ligne_id']);
                }
            }

            // Generate Accounting Double Entry: Debit Charge(s), Credit Vendor Account
            // Group charges by account number to allow multi-line balanced postings
            $journal_code = 'JA';
            $stmt_j = $db->prepare("SELECT id FROM journaux_comptables WHERE lycee_id = :lycee_id AND code = :code");
            $stmt_j->execute(['lycee_id' => $lyceeId, 'code' => $journal_code]);
            $journalId = $stmt_j->fetchColumn();

            if (!$journalId) {
                ComptabiliteService::seedDefaultJournalsForLycee($lyceeId);
                $stmt_j->execute(['lycee_id' => $lyceeId, 'code' => $journal_code]);
                $journalId = $stmt_j->fetchColumn();
            }

            $entries = [];
            $factureLignes = AchatFactureLigne::findByFacture($factureId);
            $libellePiece = "Achat fournisseur - Facture N°" . $referenceFacture;

            foreach ($factureLignes as $fLigne) {
                $entries[] = [
                    'compte_numero' => $fLigne['compte_comptable_charge'],
                    'debit' => $fLigne['montant_ttc_ligne'], // TTC if tax recovery is not separate
                    'credit' => 0.00,
                    'libelle_ligne' => $libellePiece
                ];
            }

            // Global Credit line to Vendor account
            $entries[] = [
                'compte_numero' => $fournisseur['compte_comptable_tiers'],
                'debit' => 0.00,
                'credit' => $montantTtc,
                'libelle_ligne' => $libellePiece
            ];

            $pieceId = ComptabiliteService::enregistrerPiece($journalId, $libellePiece, $dateFacture, 'achat_factures', $factureId, $entries, $_SESSION['user']['id_user'] ?? 1);
            AchatFacture::updatePieceComptable($factureId, $pieceId);

            $db->commit();
            return $factureId;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public static function payFacture($factureId, $compteFinancierId, $sessionCaisseId, $montantPaye, $modePaiement, $userId, $idempotencyKey) {
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            // VERROU 1: Lock the Invoice (FOR UPDATE)
            $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
            $lockSql = "SELECT * FROM achat_factures WHERE id = :id";
            if ($driver !== 'sqlite') {
                $lockSql .= " FOR UPDATE";
            }
            $stmt = $db->prepare($lockSql);
            $stmt->execute(['id' => $factureId]);
            $facture = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$facture) {
                throw new Exception("Facture introuvable.");
            }

            // VERROU 2: Lock the Cash Session if Payment is Espèces
            if (strtolower($modePaiement) === 'espèces') {
                if (empty($sessionCaisseId)) {
                    throw new Exception("Une session de caisse active est obligatoire pour un règlement en espèces.");
                }
                $lockSessSql = "SELECT * FROM sessions_caisse WHERE id = :id";
                if ($driver !== 'sqlite') {
                    $lockSessSql .= " FOR UPDATE";
                }
                $stmt_sess = $db->prepare($lockSessSql);
                $stmt_sess->execute(['id' => $sessionCaisseId]);
                $session = $stmt_sess->fetch(PDO::FETCH_ASSOC);

                if (!$session || $session['statut'] !== 'ouverte') {
                    throw new Exception("La session de caisse rattachée n'est pas ouverte ou est inexistante.");
                }
            }

            // Verify Remaining Balance (Reste à Payer)
            $reste = AchatFacture::getResteAPayer($factureId);
            if ($montantPaye <= 0.00) {
                throw new Exception("Le montant du règlement doit être strictement positif.");
            }
            if ($montantPaye > $reste) {
                throw new Exception("Le montant saisi (" . $montantPaye . ") excède le reste à payer (" . $reste . ").");
            }

            $compteFin = CompteFinancier::findById($compteFinancierId);
            if (!$compteFin) {
                throw new Exception("Compte financier introuvable.");
            }
            if (empty($compteFin['compte_comptable_numero'])) {
                throw new Exception("Le compte comptable associé au compte financier émetteur n'est pas configuré. Validation annulée.");
            }

            $fournisseur = Fournisseur::findById($facture['fournisseur_id']);
            if (empty($fournisseur['compte_comptable_tiers'])) {
                throw new Exception("Le compte tiers associé au fournisseur n'est pas configuré. Validation annulée.");
            }

            // 1. Register Cash Movement
            $movementId = TreasuryService::registerMovement([
                'lycee_id' => $facture['lycee_id'],
                'compte_id' => $compteFinancierId,
                'session_caisse_id' => $sessionCaisseId ?: null,
                'type_mouvement' => 'sortie',
                'montant' => $montantPaye,
                'mode_paiement' => $modePaiement,
                'reference_transaction' => 'PAY-FACT-' . $factureId . '-' . time(),
                'source_type' => 'achat_factures',
                'source_id' => $factureId,
                'evenement_type' => 'reglement_fournisseur',
                'motif' => "Règlement Facture N°" . $facture['reference_facture'],
                'user_id' => $userId
            ]);

            // 2. Allocate to the Invoice via pivot table (guaranteeing Unique Idempotency Token)
            AchatFactureReglement::create([
                'facture_id' => $factureId,
                'mouvement_tresorerie_id' => $movementId,
                'montant_alloue' => $montantPaye,
                'idempotency_key' => $idempotencyKey
            ]);

            // Update status dynamically
            $nouveauReste = AchatFacture::getResteAPayer($factureId);
            if ($nouveauReste <= 0.01) {
                AchatFacture::updateStatus($factureId, 'payee');
            } else {
                AchatFacture::updateStatus($factureId, 'payee_partiellement');
            }

            // 3. Generate Double Entry Accounting Voucher
            $journal_code = 'JO';
            $stmt_j = $db->prepare("SELECT id FROM journaux_comptables WHERE lycee_id = :lycee_id AND code = :code");
            $stmt_j->execute(['lycee_id' => $facture['lycee_id'], 'code' => $journal_code]);
            $journalId = $stmt_j->fetchColumn();

            if (!$journalId) {
                ComptabiliteService::seedDefaultJournalsForLycee($facture['lycee_id']);
                $stmt_j->execute(['lycee_id' => $facture['lycee_id'], 'code' => $journal_code]);
                $journalId = $stmt_j->fetchColumn();
            }

            $libellePiece = "Règlement fournisseur N°" . $facture['reference_facture'];
            $entries = [
                [
                    'compte_numero' => $fournisseur['compte_comptable_tiers'],
                    'debit' => $montantPaye,
                    'credit' => 0.00,
                    'libelle_ligne' => $libellePiece
                ],
                [
                    'compte_numero' => $compteFin['compte_comptable_numero'],
                    'debit' => 0.00,
                    'credit' => $montantPaye,
                    'libelle_ligne' => $libellePiece
                ]
            ];

            ComptabiliteService::enregistrerPiece($journalId, $libellePiece, date('Y-m-d'), 'achat_facture_reglements', $movementId, $entries, $userId);

            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public static function createAvoir($lyceeId, $fournisseurId, $factureId, $referenceAvoir, $dateAvoir, $montantHt, $montantTtc, $userId, $items = []) {
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $facture = AchatFacture::findById($factureId);
            if (!$facture) {
                throw new Exception("Facture introuvable.");
            }

            $fournisseur = Fournisseur::findById($fournisseurId);
            if (empty($fournisseur['compte_comptable_tiers'])) {
                throw new Exception("Le compte tiers associé au fournisseur n'est pas configuré. Validation annulée.");
            }

            $avoirId = AchatAvoirFournisseur::create([
                'lycee_id' => $lyceeId,
                'fournisseur_id' => $fournisseurId,
                'facture_id' => $factureId,
                'reference_avoir' => $referenceAvoir,
                'date_avoir' => $dateAvoir,
                'montant_ht' => $montantHt,
                'montant_ttc' => $montantTtc,
                'statut' => 'valide'
            ]);

            foreach ($items as $item) {
                AchatAvoirFournisseurLigne::create([
                    'avoir_id' => $avoirId,
                    'facture_ligne_id' => $item['facture_ligne_id'],
                    'quantite_avoir' => $item['quantite_avoir'],
                    'prix_unitaire_avoir' => $item['prix_unitaire_avoir'],
                    'montant_ht_ligne' => $item['montant_ht_ligne'],
                    'montant_ttc_ligne' => $item['montant_ttc_ligne']
                ]);
            }

            // Generate credit accounting entry: Debit Tiers, Credit Charges
            $journal_code = 'JA';
            $stmt_j = $db->prepare("SELECT id FROM journaux_comptables WHERE lycee_id = :lycee_id AND code = :code");
            $stmt_j->execute(['lycee_id' => $lyceeId, 'code' => $journal_code]);
            $journalId = $stmt_j->fetchColumn();

            if (!$journalId) {
                ComptabiliteService::seedDefaultJournalsForLycee($lyceeId);
                $stmt_j->execute(['lycee_id' => $lyceeId, 'code' => $journal_code]);
                $journalId = $stmt_j->fetchColumn();
            }

            $libellePiece = "Avoir fournisseur sur Facture N°" . $facture['reference_facture'];
            $entries = [];
            foreach ($items as $item) {
                $fLigne = AchatFactureLigne::findById($item['facture_ligne_id']);
                if (empty($fLigne['compte_comptable_charge'])) {
                    throw new Exception("Le compte de charge de la ligne n'est pas configuré. Validation annulée.");
                }
                $entries[] = [
                    'compte_numero' => $fLigne['compte_comptable_charge'],
                    'debit' => 0.00,
                    'credit' => $item['montant_ttc_ligne'],
                    'libelle_ligne' => $libellePiece
                ];
            }

            $entries[] = [
                'compte_numero' => $fournisseur['compte_comptable_tiers'],
                'debit' => $montantTtc,
                'credit' => 0.00,
                'libelle_ligne' => $libellePiece
            ];

            $pieceId = ComptabiliteService::enregistrerPiece($journalId, $libellePiece, $dateAvoir, 'achat_avoirs_fournisseurs', $avoirId, $entries, $userId);
            AchatAvoirFournisseur::updatePieceComptable($avoirId, $pieceId);

            // Recompute remaining balance of original invoice
            $nouveauReste = AchatFacture::getResteAPayer($factureId);
            if ($nouveauReste <= 0.01) {
                AchatFacture::updateStatus($factureId, 'payee');
            } else {
                AchatFacture::updateStatus($factureId, 'payee_partiellement');
            }

            // Restore budget polymorphic
            $factureLignes = AchatFactureLigne::findByFacture($factureId);
            foreach ($factureLignes as $fL) {
                $stmt_cmd_l = $db->prepare("
                    SELECT cl.demande_ligne_id
                    FROM achat_reception_lignes rl
                    INNER JOIN achat_commande_lignes cl ON rl.commande_ligne_id = cl.id
                    WHERE rl.id = :rec_l_id
                ");
                $stmt_cmd_l->execute(['rec_l_id' => $fL['reception_ligne_id']]);
                if ($demLineId = $stmt_cmd_l->fetchColumn()) {
                    BudgetService::restore(null, 'achat_demandes', $demLineId);
                }
            }

            $db->commit();
            return $avoirId;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }
}
?>