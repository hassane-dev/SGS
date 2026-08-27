<?php

require_once __DIR__ . '/../config/database.php';

class PaieRegularisationLigne {
    public static function create(array $data): int {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO paie_regularisation_lignes
            (regularisation_id, code_rubrique, libelle, base_calcul_delta, montant_salarial_delta, montant_patronal_delta)
            VALUES (:regularisation_id, :code_rubrique, :libelle, :base_calcul_delta, :montant_salarial_delta, :montant_patronal_delta)
        ");
        $stmt->execute([
            'regularisation_id' => $data['regularisation_id'],
            'code_rubrique' => $data['code_rubrique'],
            'libelle' => $data['libelle'],
            'base_calcul_delta' => $data['base_calcul_delta'] ?? 0.00,
            'montant_salarial_delta' => $data['montant_salarial_delta'] ?? 0.00,
            'montant_patronal_delta' => $data['montant_patronal_delta'] ?? 0.00
        ]);
        return (int)$db->lastInsertId();
    }

    public static function findByRegularisationId(int $regularisationId): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM paie_regularisation_lignes WHERE regularisation_id = :regularisation_id ORDER BY id ASC");
        $stmt->execute(['regularisation_id' => $regularisationId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
