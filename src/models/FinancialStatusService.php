<?php

require_once __DIR__ . '/FinanceService.php';

class FinancialStatusService {

    /**
     * Compute and retrieve a student's unified financial status, including registration remains
     * and due monthly remains.
     *
     * This is the single source of truth for all modules in the application (Point 4).
     *
     * @param int $eleveId
     * @param int|null $anneeAcademiqueId
     * @return array|null
     */
    public static function getStudentFinancialStatus($eleveId, $anneeAcademiqueId = null) {
        if (!$anneeAcademiqueId) {
            $activeYear = AnneeAcademique::findActive();
            $anneeAcademiqueId = $activeYear ? $activeYear['id'] : null;
        }

        if (!$anneeAcademiqueId) {
            return [
                'eleve_id' => $eleveId,
                'reste_inscription' => 0.0,
                'reste_mensualite' => 0.0,
                'total_reste' => 0.0,
                'details_mensualites' => [],
                'is_active' => false,
                'paye_inscription' => 0.0,
                'paye_mensualite' => 0.0,
                'total_paye' => 0.0,
                'total_du' => 0.0
            ];
        }

        $eleve = Eleve::findById($eleveId);
        if (!$eleve) {
            return null;
        }

        $lyceeId = $eleve['lycee_id'] ?? Auth::getLyceeId();
        $etude = Etude::findByEleveAndAnnee($eleveId, $anneeAcademiqueId, $lyceeId);
        if (!$etude) {
            return [
                'eleve_id' => $eleveId,
                'reste_inscription' => 0.0,
                'reste_mensualite' => 0.0,
                'total_reste' => 0.0,
                'details_mensualites' => [],
                'is_active' => false,
                'paye_inscription' => 0.0,
                'paye_mensualite' => 0.0,
                'total_paye' => 0.0,
                'total_du' => 0.0
            ];
        }

        $classe = Classe::findById($etude['classe_id']);
        if (!$classe) {
            return [
                'eleve_id' => $eleveId,
                'reste_inscription' => 0.0,
                'reste_mensualite' => 0.0,
                'total_reste' => 0.0,
                'details_mensualites' => [],
                'is_active' => false,
                'paye_inscription' => 0.0,
                'paye_mensualite' => 0.0,
                'total_paye' => 0.0,
                'total_du' => 0.0
            ];
        }

        $frais = Frais::findForClasse($classe, $anneeAcademiqueId);
        if (!$frais) {
            return [
                'eleve_id' => $eleveId,
                'reste_inscription' => 0.0,
                'reste_mensualite' => 0.0,
                'total_reste' => 0.0,
                'details_mensualites' => [],
                'is_active' => false,
                'paye_inscription' => 0.0,
                'paye_mensualite' => 0.0,
                'total_paye' => 0.0,
                'total_du' => 0.0
            ];
        }

        // 1. Calculate Inscription Reste
        $inscription = Inscription::findByEleveAndAnnee($eleveId, $anneeAcademiqueId, $lyceeId);
        $expectedInscription = FinanceService::applyFinancialAdvantages($eleveId, 'frais_inscription', (float)$frais['frais_inscription']);

        $options = $inscription ? json_decode($inscription['details_frais'] ?? '[]', true) : [];
        if (!empty($options['logo'])) {
            $expectedInscription += FinanceService::applyFinancialAdvantages($eleveId, 'frais_logo', (float)($frais['frais_logo'] ?? 0));
        }
        if (!empty($options['carte'])) {
            $expectedInscription += FinanceService::applyFinancialAdvantages($eleveId, 'frais_carte', (float)($frais['frais_carte'] ?? 0));
        }

        $verseInscription = ($inscription && ($inscription['statut'] ?? 'valide') === 'valide') ? (float)$inscription['montant_verse'] : 0.00;
        $resteInscription = max(0.0, $expectedInscription - $verseInscription);

        // Avoid floating point tiny residues
        if ($resteInscription <= 0.01) {
            $resteInscription = 0.0;
        }

        // 2. Determine Active Financial Period & Sequences (Point 3)
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT * FROM sequences
            WHERE lycee_id = :lycee_id
            AND annee_academique_id = :annee_academique_id
            ORDER BY date_debut ASC
        ");
        $stmt->execute([
            'lycee_id' => $lyceeId,
            'annee_academique_id' => $anneeAcademiqueId
        ]);
        $sequences = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Find active sequence (statut = 'ouverte')
        $activeSeq = null;
        foreach ($sequences as $seq) {
            if ($seq['statut'] === 'ouverte') {
                $activeSeq = $seq;
                break;
            }
        }

        // Fallback if no sequence is marked as open: use the one covering today, or by date_debut <= today
        if (!$activeSeq) {
            $todayStr = date('Y-m-d');
            foreach ($sequences as $seq) {
                if ($seq['date_debut'] <= $todayStr && $seq['date_fin'] >= $todayStr) {
                    $activeSeq = $seq;
                    break;
                }
            }
        }
        if (!$activeSeq) {
            // Find latest sequence that has started
            $todayStr = date('Y-m-d');
            foreach (array_reverse($sequences) as $seq) {
                if ($seq['date_debut'] <= $todayStr) {
                    $activeSeq = $seq;
                    break;
                }
            }
        }
        // Ultimate fallback: if no sequences have started or exist, use the first sequence
        if (!$activeSeq && !empty($sequences)) {
            $activeSeq = $sequences[0];
        }

        // Determine which sequences are eligible (due)
        $dueSequences = [];
        if ($activeSeq) {
            foreach ($sequences as $seq) {
                if ($seq['date_debut'] <= $activeSeq['date_debut']) {
                    $dueSequences[] = $seq;
                }
            }
        }

        // Calculate expected monthly fees
        $expectedMonthly = FinanceService::applyFinancialAdvantages($eleveId, 'frais_mensuel', (float)$frais['frais_mensuel']);
        $mensualitesPayees = Mensualite::findByEtude($etude['id_etude']);

        $fmt = new IntlDateFormatter('fr_FR', IntlDateFormatter::FULL, IntlDateFormatter::NONE, 'Africa/Porto-Novo', IntlDateFormatter::GREGORIAN, 'MMMM');

        $dueMonths = [];
        $seenMonths = [];

        // Generate list of months that are due from eligible (due) sequences
        foreach ($dueSequences as $seq) {
            $current = new DateTime($seq['date_debut']);
            $end = new DateTime($seq['date_fin']);

            $safety = 0;
            while ($current <= $end && $safety < 12) {
                $monthName = ucfirst($fmt->format($current));
                if (!in_array($monthName, $seenMonths)) {
                    $dueMonths[] = $monthName;
                    $seenMonths[] = $monthName;
                }
                $current->modify('first day of next month');
                $safety++;
            }
        }

        $resteMensualite = 0.0;
        $detailsMensualites = [];

        foreach ($dueMonths as $month) {
            $verse = isset($mensualitesPayees[$month]) ? (float)$mensualitesPayees[$month]['total'] : 0.00;
            $resteMonth = max(0.0, $expectedMonthly - $verse);
            if ($resteMonth <= 0.01) {
                $resteMonth = 0.0;
            }

            $resteMensualite += $resteMonth;
            $detailsMensualites[] = [
                'mois' => $month,
                'attendu' => $expectedMonthly,
                'verse' => $verse,
                'reste' => $resteMonth,
                'statut' => ($resteMonth <= 0) ? 'Payé' : (($verse > 0) ? 'Partiellement payé' : 'Impayé')
            ];
        }

        $totalReste = $resteInscription + $resteMensualite;

        // Calculate total paid monthly fees from the due months list
        $payeMensualite = 0.0;
        foreach ($detailsMensualites as $dm) {
            $payeMensualite += (float)$dm['verse'];
        }

        $totalPaye = $verseInscription + $payeMensualite;
        $totalDu = $totalPaye + $totalReste;

        return [
            'eleve_id' => $eleveId,
            'reste_inscription' => $resteInscription,
            'reste_mensualite' => $resteMensualite,
            'total_reste' => $totalReste,
            'details_mensualites' => $detailsMensualites,
            'is_active' => (bool)$etude['is_active'],
            'paye_inscription' => $verseInscription,
            'paye_mensualite' => $payeMensualite,
            'total_paye' => $totalPaye,
            'total_du' => $totalDu
        ];
    }
}
