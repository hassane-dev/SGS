<?php

require_once __DIR__ . '/PolitiqueFinanciere.php';
require_once __DIR__ . '/ParametreFinancierEleve.php';
require_once __DIR__ . '/EtatFinancierEleve.php';
require_once __DIR__ . '/Frais.php';
require_once __DIR__ . '/Inscription.php';
require_once __DIR__ . '/Mensualite.php';
require_once __DIR__ . '/AnneeAcademique.php';
require_once __DIR__ . '/Sequence.php';
require_once __DIR__ . '/Classe.php';
require_once __DIR__ . '/Eleve.php';
require_once __DIR__ . '/Etude.php';
require_once __DIR__ . '/FinancialStatusService.php';
require_once __DIR__ . '/../core/Auth.php';

class FinanceService {

    /**
     * Compute adjusted fee based on student advantages.
     */
    public static function applyFinancialAdvantages($eleveId, $feeKey, $baseAmount) {
        return ParametreFinancierEleve::getAdjustedFee($eleveId, $feeKey, $baseAmount);
    }

    /**
     * Compute student's expected fees for the active academic year (adjusted for advantages).
     */
    public static function calculateStudentFees($eleveId) {
        $anneeActive = AnneeAcademique::findActive();
        if (!$anneeActive) {
            return null;
        }

        $eleve = Eleve::findById($eleveId);
        if (!$eleve) {
            return null;
        }

        $etude = Etude::findByEleveAndAnnee($eleveId, $anneeActive['id'], $eleve['lycee_id']);
        if (!$etude) {
            return null;
        }

        $classe = Classe::findById($etude['classe_id']);
        $frais = Frais::findForClasse($classe, $anneeActive['id']);
        if (!$frais) {
            return null;
        }

        return [
            'inscription_base' => (float)$frais['frais_inscription'],
            'inscription_ajustee' => self::applyFinancialAdvantages($eleveId, 'frais_inscription', (float)$frais['frais_inscription']),
            'mensuel_base' => (float)$frais['frais_mensuel'],
            'mensuel_ajustee' => self::applyFinancialAdvantages($eleveId, 'frais_mensuel', (float)$frais['frais_mensuel']),
            'logo_base' => (float)($frais['frais_logo'] ?? 0),
            'logo_ajustee' => self::applyFinancialAdvantages($eleveId, 'frais_logo', (float)($frais['frais_logo'] ?? 0)),
            'carte_base' => (float)($frais['frais_carte'] ?? 0),
            'carte_ajustee' => self::applyFinancialAdvantages($eleveId, 'frais_carte', (float)($frais['frais_carte'] ?? 0)),
        ];
    }

    /**
     * Get or calculate the student's unified financial status (fast cached lookups).
     */
    public static function checkFinancialStatus($eleveId) {
        $status = EtatFinancierEleve::findByEleveId($eleveId);
        if (!$status) {
            $status = self::updateFinancialState($eleveId);
        }
        return $status;
    }

    /**
     * Check notes access permission.
     */
    public static function canAccessNotes($eleveId) {
        $status = self::checkFinancialStatus($eleveId);
        return ($status['notes_consultation'] ?? '') === 'Autorisée';
    }

    /**
     * Check report card print permission.
     */
    public static function canAccessBulletin($eleveId) {
        $status = self::checkFinancialStatus($eleveId);
        return ($status['bulletin_impression'] ?? '') === 'Autorisée';
    }

    /**
     * Recalculates and updates the cached database record in `etats_financiers_eleves`.
     * The official payments and policy configurations remain the source of truth.
     */
    public static function updateFinancialState($eleveId) {
        $anneeActive = AnneeAcademique::findActive();
        if (!$anneeActive) {
            return [
                'eleve_id' => $eleveId,
                'inscription_statut' => 'Non payée',
                'mensualite_statut' => 'À jour',
                'notes_consultation' => 'Interdite',
                'bulletin_impression' => 'Interdite'
            ];
        }

        $eleve = Eleve::findById($eleveId);
        if (!$eleve) {
            return null;
        }

        $lyceeId = $eleve['lycee_id'];
        $policy = PolitiqueFinanciere::findOrCreate($lyceeId);

        // If policy is not active, permissions are automatically authorized
        $isPolicyActive = isset($policy['active']) ? (bool)$policy['active'] : true;

        $etude = Etude::findByEleveAndAnnee($eleveId, $anneeActive['id'], $lyceeId);
        if (!$etude) {
            $data = [
                'eleve_id' => $eleveId,
                'inscription_statut' => 'Non payée',
                'mensualite_statut' => 'À jour',
                'notes_consultation' => $isPolicyActive ? 'Interdite' : 'Autorisée',
                'bulletin_impression' => $isPolicyActive ? 'Interdite' : 'Autorisée'
            ];
            EtatFinancierEleve::save($data);
            return $data;
        }

        // Get status using the unified service (Point 1 & 4)
        $status = FinancialStatusService::getStudentFinancialStatus($eleveId, $anneeActive['id']);
        if (!$status) {
            return null;
        }

        // 1. Calculate Inscription status
        $inscription_statut = 'Non payée';
        if ($status['reste_inscription'] <= 0.01) {
            $inscription_statut = 'Payée';
        } else {
            // Find expected total and verse to see if it's partially paid
            $classe = Classe::findById($etude['classe_id']);
            $frais = Frais::findForClasse($classe, $anneeActive['id']);
            $expectedInscription = self::applyFinancialAdvantages($eleveId, 'frais_inscription', (float)($frais['frais_inscription'] ?? 0));
            $inscription = Inscription::findByEleveAndAnnee($eleveId, $anneeActive['id'], $lyceeId);
            $options = $inscription ? json_decode($inscription['details_frais'] ?? '[]', true) : [];
            if (!empty($options['logo'])) {
                $expectedInscription += self::applyFinancialAdvantages($eleveId, 'frais_logo', (float)($frais['frais_logo'] ?? 0));
            }
            if (!empty($options['carte'])) {
                $expectedInscription += self::applyFinancialAdvantages($eleveId, 'frais_carte', (float)($frais['frais_carte'] ?? 0));
            }
            $verseInscription = $inscription ? (float)$inscription['montant_verse'] : 0.00;
            if ($verseInscription > 0.01) {
                $inscription_statut = 'Partiellement payée';
            }
        }

        // 2. Calculate Monthly Payments status
        $totalElapsed = count($status['details_mensualites']);
        $fullyPaidCount = 0;
        $unpaidCount = 0;

        foreach ($status['details_mensualites'] as $dm) {
            if ($dm['reste'] <= 0.01) {
                $fullyPaidCount++;
            } elseif ($dm['verse'] <= 0.01) {
                $unpaidCount++;
            }
        }

        if ($totalElapsed === 0) {
            $mensualite_statut = 'À jour';
        } elseif ($fullyPaidCount === $totalElapsed) {
            $mensualite_statut = 'À jour';
        } elseif ($unpaidCount === $totalElapsed) {
            $mensualite_statut = 'En retard';
        } elseif ($unpaidCount > 0) {
            $mensualite_statut = 'En retard';
        } else {
            $mensualite_statut = 'Partiellement payée';
        }

        // 3. Notes Permission
        if (!$isPolicyActive) {
            $notes_consultation = 'Autorisée';
        } else {
            $notes_seuil = (int)($policy['notes_seuil_mensualites'] ?? 0);
            if ($fullyPaidCount >= $notes_seuil) {
                $notes_consultation = 'Autorisée';
            } else {
                $notes_consultation = 'Interdite';
            }
        }

        // 4. Bulletin Print Permission
        if (!$isPolicyActive || empty($policy['bulletin_seuil_complet'])) {
            $bulletin_impression = 'Autorisée';
        } else {
            $sequences = Sequence::findAll();
            $activeSeq = Sequence::findActive() ?? (!empty($sequences) ? $sequences[0] : null);
            if ($activeSeq) {
                $seqMonths = [];
                $fmt = new IntlDateFormatter('fr_FR', IntlDateFormatter::FULL, IntlDateFormatter::NONE, 'Africa/Porto-Novo', IntlDateFormatter::GREGORIAN, 'MMMM');
                $current = new DateTime($activeSeq['date_debut']);
                $current->modify('first day of this month');
                $end = new DateTime($activeSeq['date_fin']);
                $end->modify('first day of this month');

                $safety = 0;
                while ($current <= $end && $safety < 12) {
                    $monthName = ucfirst($fmt->format($current));
                    if (!in_array($monthName, $seqMonths)) {
                        $seqMonths[] = $monthName;
                    }
                    $current->modify('first day of next month');
                    $safety++;
                }

                $allSeqPaid = true;
                $classe = Classe::findById($etude['classe_id']);
                $frais = Frais::findForClasse($classe, $anneeActive['id']);
                $expectedMonthly = self::applyFinancialAdvantages($eleveId, 'frais_mensuel', (float)$frais['frais_mensuel']);
                $mensualitesPayees = Mensualite::findByEtude($etude['id_etude']);

                foreach ($seqMonths as $m) {
                    $verse = isset($mensualitesPayees[$m]) ? (float)$mensualitesPayees[$m]['total'] : 0.00;
                    if ($expectedMonthly > 0.01 && $verse < $expectedMonthly - 0.01) {
                        $allSeqPaid = false;
                        break;
                    }
                }

                $bulletin_impression = $allSeqPaid ? 'Autorisée' : 'Interdite';
            } else {
                $bulletin_impression = 'Autorisée';
            }
        }

        $data = [
            'eleve_id' => $eleveId,
            'inscription_statut' => $inscription_statut,
            'mensualite_statut' => $mensualite_statut,
            'notes_consultation' => $notes_consultation,
            'bulletin_impression' => $bulletin_impression
        ];

        EtatFinancierEleve::save($data);
        return $data;
    }
}
