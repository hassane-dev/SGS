<?php

require_once __DIR__ . '/../config/database.php';

class PaieRegleCalcul {

    public static function create(array $data): int {
        $db = Database::getInstance();
        $juridictionCode = $data['juridiction_code'] ?? ($data['pays_code'] ?? 'RCA');
        $codeRegle = $data['code_regle'];
        $dateDebut = $data['date_debut_validite'] ?? date('Y-01-01');

        // Check if an exact (juridiction_code, code_regle, date_debut_validite) collision exists
        $stmtChk = $db->prepare("SELECT id FROM paie_regles_calcul WHERE juridiction_code = :jcode AND code_regle = :cregle AND date_debut_validite = :ddebut");
        $stmtChk->execute([
            'jcode' => $juridictionCode,
            'cregle' => $codeRegle,
            'ddebut' => $dateDebut
        ]);
        $existingId = $stmtChk->fetchColumn();

        if ($existingId) {
            throw new InvalidArgumentException("Une règle avec le code '{$codeRegle}' existe déjà pour la juridiction '{$juridictionCode}' avec la même date d'effet ({$dateDebut}). Veuillez choisir une date d'effet différente ou modifier la règle existante.");
        }

        $stmt = $db->prepare("
            INSERT INTO paie_regles_calcul
            (juridiction_code, pays_code, code_regle, libelle, categorie, mode_calcul, base_calcul_type,
             taux_par_defaut, montant_fixe_salarial, taux_patronal, montant_fixe_patronal,
             seuil_minimum, plafond_maximum, abattement_forfaitaire, abattement_pourcentage,
             ordre_application, type_contrat_id, formule, est_systeme, actif, date_debut_validite, date_fin_validite, created_at)
            VALUES
            (:juridiction_code, :pays_code, :code_regle, :libelle, :categorie, :mode_calcul, :base_calcul_type,
             :taux_par_defaut, :montant_fixe_salarial, :taux_patronal, :montant_fixe_patronal,
             :seuil_minimum, :plafond_maximum, :abattement_forfaitaire, :abattement_pourcentage,
             :ordre_application, :type_contrat_id, :formule, :est_systeme, :actif, :date_debut_validite, :date_fin_validite, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([
            'juridiction_code' => $juridictionCode,
            'pays_code' => $data['pays_code'] ?? ($data['juridiction_code'] ?? 'RCA'),
            'code_regle' => $codeRegle,
            'libelle' => $data['libelle'],
            'categorie' => $data['categorie'],
            'mode_calcul' => $data['mode_calcul'],
            'base_calcul_type' => $data['base_calcul_type'] ?? 'brut_total',
            'taux_par_defaut' => $data['taux_par_defaut'] ?? 0.00,
            'montant_fixe_salarial' => $data['montant_fixe_salarial'] ?? 0.00,
            'taux_patronal' => $data['taux_patronal'] ?? 0.00,
            'montant_fixe_patronal' => $data['montant_fixe_patronal'] ?? 0.00,
            'seuil_minimum' => isset($data['seuil_minimum']) && $data['seuil_minimum'] !== '' ? (float)$data['seuil_minimum'] : null,
            'plafond_maximum' => isset($data['plafond_maximum']) && $data['plafond_maximum'] !== '' ? (float)$data['plafond_maximum'] : null,
            'abattement_forfaitaire' => $data['abattement_forfaitaire'] ?? 0.00,
            'abattement_pourcentage' => $data['abattement_pourcentage'] ?? 0.00,
            'ordre_application' => $data['ordre_application'] ?? 100,
            'type_contrat_id' => !empty($data['type_contrat_id']) ? (int)$data['type_contrat_id'] : null,
            'formule' => $data['formule'] ?? null,
            'est_systeme' => $data['est_systeme'] ?? 0,
            'actif' => $data['actif'] ?? 1,
            'date_debut_validite' => $dateDebut,
            'date_fin_validite' => !empty($data['date_fin_validite']) ? $data['date_fin_validite'] : null
        ]);
        return (int)$db->lastInsertId();
    }

    public static function update(int $id, array $data): bool {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            UPDATE paie_regles_calcul
            SET libelle = :libelle,
                categorie = :categorie,
                mode_calcul = :mode_calcul,
                base_calcul_type = :base_calcul_type,
                taux_par_defaut = :taux_par_defaut,
                montant_fixe_salarial = :montant_fixe_salarial,
                taux_patronal = :taux_patronal,
                montant_fixe_patronal = :montant_fixe_patronal,
                seuil_minimum = :seuil_minimum,
                plafond_maximum = :plafond_maximum,
                abattement_forfaitaire = :abattement_forfaitaire,
                abattement_pourcentage = :abattement_pourcentage,
                ordre_application = :ordre_application,
                type_contrat_id = :type_contrat_id,
                actif = :actif,
                date_debut_validite = :date_debut_validite,
                date_fin_validite = :date_fin_validite
            WHERE id = :id
        ");
        return $stmt->execute([
            'id' => $id,
            'libelle' => $data['libelle'],
            'categorie' => $data['categorie'],
            'mode_calcul' => $data['mode_calcul'],
            'base_calcul_type' => $data['base_calcul_type'] ?? 'brut_total',
            'taux_par_defaut' => $data['taux_par_defaut'] ?? 0.00,
            'montant_fixe_salarial' => $data['montant_fixe_salarial'] ?? 0.00,
            'taux_patronal' => $data['taux_patronal'] ?? 0.00,
            'montant_fixe_patronal' => $data['montant_fixe_patronal'] ?? 0.00,
            'seuil_minimum' => isset($data['seuil_minimum']) && $data['seuil_minimum'] !== '' ? (float)$data['seuil_minimum'] : null,
            'plafond_maximum' => isset($data['plafond_maximum']) && $data['plafond_maximum'] !== '' ? (float)$data['plafond_maximum'] : null,
            'abattement_forfaitaire' => $data['abattement_forfaitaire'] ?? 0.00,
            'abattement_pourcentage' => $data['abattement_pourcentage'] ?? 0.00,
            'ordre_application' => $data['ordre_application'] ?? 100,
            'type_contrat_id' => !empty($data['type_contrat_id']) ? (int)$data['type_contrat_id'] : null,
            'actif' => $data['actif'] ?? 1,
            'date_debut_validite' => $data['date_debut_validite'],
            'date_fin_validite' => !empty($data['date_fin_validite']) ? $data['date_fin_validite'] : null
        ]);
    }

    public static function findById(int $id): ?array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM paie_regles_calcul WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function findAll(): array {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM paie_regles_calcul ORDER BY pays_code ASC, ordre_application ASC, id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findActiveRulesForJurisdiction(string $juridictionCode = 'DEFAULT', ?string $asOfDate = null): array {
        $asOfDate = $asOfDate ?: date('Y-m-d');
        $db = Database::getInstance();

        // Support matching either juridiction_code or pays_code or DEFAULT
        $stmt = $db->prepare("
            SELECT * FROM paie_regles_calcul
            WHERE (juridiction_code = :jcode1 OR pays_code = :jcode2 OR juridiction_code = 'DEFAULT' OR pays_code = 'DEFAULT')
              AND actif = 1
              AND date_debut_validite <= :as_of_date1
              AND (date_fin_validite IS NULL OR date_fin_validite >= :as_of_date2)
            ORDER BY ordre_application ASC, id ASC
        ");
        $stmt->execute([
            'jcode1' => $juridictionCode,
            'jcode2' => $juridictionCode,
            'as_of_date1' => $asOfDate,
            'as_of_date2' => $asOfDate
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function toggleActive(int $id): bool {
        $db = Database::getInstance();
        $stmt = $db->prepare("UPDATE paie_regles_calcul SET actif = CASE WHEN actif = 1 THEN 0 ELSE 1 END WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public static function isRuleUsedInBulletins(int $ruleId): bool {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT COUNT(*) FROM paie_bulletin_regles_snapshot WHERE regle_id = :id");
        $stmt->execute(['id' => $ruleId]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
