<?php

require_once __DIR__ . '/../config/database.php';

class ParametresGeneraux {

    public static function getByLyceeId($lycee_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM parametres_generaux WHERE lycee_id = :lycee_id LIMIT 1");
        $stmt->execute(['lycee_id' => $lycee_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function save($data) {
        $existing = self::getByLyceeId($data['lycee_id']);

        $sql = $existing
            ? "UPDATE parametres_generaux SET nom_lycee = :nom_lycee, type_lycee = :type_lycee, annee_academique = :annee_academique, nombre_devoirs_par_trimestre = :nombre_devoirs_par_trimestre, modalite_paiement = :modalite_paiement, multilingue_actif = :multilingue_actif, biometrie_actif = :biometrie_actif, confidentialite_nationale = :confidentialite_nationale WHERE lycee_id = :lycee_id"
            : "INSERT INTO parametres_generaux (lycee_id, nom_lycee, type_lycee, annee_academique, nombre_devoirs_par_trimestre, modalite_paiement, multilingue_actif, biometrie_actif, confidentialite_nationale) VALUES (:lycee_id, :nom_lycee, :type_lycee, :annee_academique, :nombre_devoirs_par_trimestre, :modalite_paiement, :multilingue_actif, :biometrie_actif, :confidentialite_nationale)";

        $db = Database::getInstance();
        $stmt = $db->prepare($sql);

        return $stmt->execute([
            'lycee_id' => $data['lycee_id'],
            'nom_lycee' => $data['nom_lycee'],
            'type_lycee' => $data['type_lycee'],
            'annee_academique' => $data['annee_academique'],
            'nombre_devoirs_par_trimestre' => $data['nombre_devoirs_par_trimestre'] ?? 2,
            'modalite_paiement' => $data['modalite_paiement'],
            'multilingue_actif' => isset($data['multilingue_actif']) ? 1 : 0,
            'biometrie_actif' => isset($data['biometrie_actif']) ? 1 : 0,
            'confidentialite_nationale' => isset($data['confidentialite_nationale']) ? 1 : 0,
        ]);
    }
}
?>
