<?php

require_once __DIR__ . '/ComptabiliteService.php';

class PaieAccountingAdapter {

    /**
     * Post a payroll bulletin accounting entry to GL via ComptabiliteService.
     */
    public static function postBulletinToGL(array $bulletin, array $lignes, int $userId): int {
        $db = Database::getInstance();

        // 1. Resolve Journal 'OD' or 'PAIE' for Lycée
        $periodeId = (int)$bulletin['periode_id'];
        $stmtPer = $db->prepare("SELECT lycee_id, mois, annee FROM paie_periodes WHERE id = :id");
        $stmtPer->execute(['id' => $periodeId]);
        $periodeData = $stmtPer->fetch(PDO::FETCH_ASSOC);
        $lyceeId = (int)($periodeData['lycee_id'] ?? 1);

        $stmtJ = $db->prepare("SELECT id FROM journaux_comptables WHERE lycee_id = :lycee_id AND code IN ('JS', 'PAIE', 'JO', 'OD') ORDER BY CASE WHEN code = 'JS' THEN 1 WHEN code = 'PAIE' THEN 2 ELSE 3 END LIMIT 1");
        $stmtJ->execute(['lycee_id' => $lyceeId]);
        $journalId = (int)$stmtJ->fetchColumn();

        if (!$journalId) {
            ComptabiliteService::seedDefaultJournalsForLycee($lyceeId);
            $stmtJ->execute(['lycee_id' => $lyceeId]);
            $journalId = (int)$stmtJ->fetchColumn();
        }

        if (!$journalId) {
            $stmtFallback = $db->prepare("SELECT id FROM journaux_comptables WHERE lycee_id = :lycee_id LIMIT 1");
            $stmtFallback->execute(['lycee_id' => $lyceeId]);
            $journalId = (int)$stmtFallback->fetchColumn() ?: 1;
        }

        // 2. Prepare Double-Entry Postings (OHADA Standard)
        // Debit: 661000 (Salaires bruts)
        // Credit: 421000 (Personnel, rémunérations dues - Net à payer)
        // Credit: 431000 (Sécurité sociale / Cotisations salariales)
        // Credit: 447000 (Impôts et taxes retenus à la source)
        $totalBrut = (float)$bulletin['total_brut'];
        $netAPayer = (float)$bulletin['net_a_payer'];
        $totalCotisSal = (float)$bulletin['total_cotisations_salariales'];
        $totalImpots = (float)$bulletin['total_impots'];

        $glLignes = [
            [
                'compte_numero' => '661000',
                'libelle' => 'Salaires bruts personnel',
                'debit' => $totalBrut,
                'credit' => 0.00
            ],
            [
                'compte_numero' => '421000',
                'libelle' => 'Rémunérations dues au personnel',
                'debit' => 0.00,
                'credit' => $netAPayer
            ]
        ];

        if ($totalCotisSal > 0) {
            $glLignes[] = [
                'compte_numero' => '431000',
                'libelle' => 'Cotisations sociales salariales',
                'debit' => 0.00,
                'credit' => $totalCotisSal
            ];
        }

        if ($totalImpots > 0) {
            $glLignes[] = [
                'compte_numero' => '447000',
                'libelle' => 'Impôts et retenues sur salaires',
                'debit' => 0.00,
                'credit' => $totalImpots
            ];
        }

        $libellePiece = "Comptabilisation Bulletin de Paie #" . $bulletin['id'] . " - Periode " . $periodeData['annee'] . "/" . $periodeData['mois'];
        $datePiece = date('Y-m-d');

        return ComptabiliteService::enregistrerPiece(
            $journalId,
            $libellePiece,
            $datePiece,
            'paie_bulletins',
            (int)$bulletin['id'],
            $glLignes,
            $userId
        );
    }

    /**
     * Counterpass / Reverse an existing bulletin accounting entry in GL.
     */
    public static function reverseBulletinInGL(int $bulletinId, int $userId, string $motif = 'Re-tirage bulletin V2'): bool {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT id FROM pieces_comptables WHERE source_table = 'paie_bulletins' AND source_id = :id AND statut = 'valide'");
        $stmt->execute(['id' => $bulletinId]);
        $pieceId = $stmt->fetchColumn();

        if ($pieceId) {
            return (bool)ComptabiliteService::contrepasserPiece((int)$pieceId, $userId, $motif);
        }
        return true; // No piece existed to reverse
    }
}
