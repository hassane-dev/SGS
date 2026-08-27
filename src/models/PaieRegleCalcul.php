<?php

require_once __DIR__ . '/../config/database.php';

class PaieRegleCalcul {
    public static function create(array $data): int {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO paie_regles_calcul
            (juridiction_code, code_regle, libelle, categorie, mode_calcul, taux_par_defaut, formule, est_systeme, actif, date_debut_validite, date_fin_validite, created_at)
            VALUES (:juridiction_code, :code_regle, :libelle, :categorie, :mode_calcul, :taux_par_defaut, :formule, :est_systeme, :actif, :date_debut_validite, :date_fin_validite, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([
            'juridiction_code' => $data['juridiction_code'] ?? 'DEFAULT',
            'code_regle' => $data['code_regle'],
            'libelle' => $data['libelle'],
            'categorie' => $data['categorie'],
            'mode_calcul' => $data['mode_calcul'],
            'taux_par_defaut' => $data['taux_par_defaut'] ?? 0.00,
            'formule' => $data['formule'] ?? null,
            'est_systeme' => $data['est_systeme'] ?? 1,
            'actif' => $data['actif'] ?? 1,
            'date_debut_validite' => $data['date_debut_validite'] ?? date('Y-01-01'),
            'date_fin_validite' => $data['date_fin_validite'] ?? null
        ]);
        return (int)$db->lastInsertId();
    }

    public static function findActiveRulesForJurisdiction(string $juridictionCode = 'DEFAULT', string $asOfDate = null): array {
        $asOfDate = $asOfDate ?: date('Y-m-d');
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT * FROM paie_regles_calcul
            WHERE (juridiction_code = :juridiction_code OR juridiction_code = 'DEFAULT')
              AND actif = 1
              AND date_debut_validite <= :as_of_date1
              AND (date_fin_validite IS NULL OR date_fin_validite >= :as_of_date2)
            ORDER BY id ASC
        ");
        $stmt->execute([
            'juridiction_code' => $juridictionCode,
            'as_of_date1' => $asOfDate,
            'as_of_date2' => $asOfDate
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
