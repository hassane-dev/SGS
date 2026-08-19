<?php

require_once __DIR__ . '/../config/database.php';

class PaieCahierTexteValidation {
    public static function create(array $data): int {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO paie_cahier_texte_validations
            (cahier_id, enseignant_id, cycle_id, classe_id, matiere_id, duree_heures, taux_horaire, statut_validation, valide_par, valide_le, created_at)
            VALUES (:cahier_id, :enseignant_id, :cycle_id, :classe_id, :matiere_id, :duree_heures, :taux_horaire, :statut_validation, :valide_par, :valide_le, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([
            'cahier_id' => $data['cahier_id'],
            'enseignant_id' => $data['enseignant_id'],
            'cycle_id' => $data['cycle_id'] ?? null,
            'classe_id' => $data['classe_id'] ?? null,
            'matiere_id' => $data['matiere_id'] ?? null,
            'duree_heures' => $data['duree_heures'],
            'taux_horaire' => $data['taux_horaire'],
            'statut_validation' => $data['statut_validation'] ?? 'en_attente',
            'valide_par' => $data['valide_par'] ?? null,
            'valide_le' => $data['valide_le'] ?? null
        ]);
        return (int)$db->lastInsertId();
    }

    public static function findByCahierId(int $cahierId): ?array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM paie_cahier_texte_validations WHERE cahier_id = :cahier_id");
        $stmt->execute(['cahier_id' => $cahierId]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    public static function findValidatedForTeacherAndDates(int $enseignantId, string $dateDebut, string $dateFin): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT v.*, c.date_cours, c.contenu_cours
            FROM paie_cahier_texte_validations v
            JOIN cahier_texte c ON v.cahier_id = c.cahier_id
            WHERE v.enseignant_id = :enseignant_id
              AND v.statut_validation = 'valide'
              AND c.date_cours BETWEEN :date_debut AND :date_fin
        ");
        $stmt->execute([
            'enseignant_id' => $enseignantId,
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
