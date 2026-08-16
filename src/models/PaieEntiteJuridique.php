<?php
// src/models/PaieEntiteJuridique.php

require_once __DIR__ . '/../config/database.php';

class PaieEntiteJuridique {

    public static function findAll(): array {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM paie_entites_juridiques WHERE actif = 1 ORDER BY raison_sociale ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findById(int $id): ?array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM paie_entites_juridiques WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    public static function save(array $data): bool {
        $db = Database::getInstance();
        $id = !empty($data['id']) ? (int)$data['id'] : null;

        if ($id) {
            $stmt = $db->prepare("
                UPDATE paie_entites_juridiques SET
                    organisation_id = :org_id, raison_sociale = :raison, sigle = :sigle,
                    immatriculation_fiscale = :ifu, numero_cnss_employeur = :cnss,
                    adresse_siege = :adresse, actif = :actif
                WHERE id = :id
            ");
            return $stmt->execute([
                'org_id' => $data['organisation_id'] ?? null,
                'raison' => $data['raison_sociale'],
                'sigle' => $data['sigle'] ?? null,
                'ifu' => $data['immatriculation_fiscale'] ?? null,
                'cnss' => $data['numero_cnss_employeur'] ?? null,
                'adresse' => $data['adresse_siege'] ?? null,
                'actif' => isset($data['actif']) ? (int)$data['actif'] : 1,
                'id' => $id
            ]);
        } else {
            $stmt = $db->prepare("
                INSERT INTO paie_entites_juridiques (
                    organisation_id, raison_sociale, sigle, immatriculation_fiscale,
                    numero_cnss_employeur, adresse_siege, actif
                ) VALUES (
                    :org_id, :raison, :sigle, :ifu, :cnss, :adresse, :actif
                )
            ");
            return $stmt->execute([
                'org_id' => $data['organisation_id'] ?? null,
                'raison' => $data['raison_sociale'],
                'sigle' => $data['sigle'] ?? null,
                'ifu' => $data['immatriculation_fiscale'] ?? null,
                'cnss' => $data['numero_cnss_employeur'] ?? null,
                'adresse' => $data['adresse_siege'] ?? null,
                'actif' => isset($data['actif']) ? (int)$data['actif'] : 1
            ]);
        }
    }
}
