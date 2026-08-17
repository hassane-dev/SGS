<?php

require_once __DIR__ . '/../config/database.php';

class CompteComptable {

    public static function findById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM comptes_comptables WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findByNumero($numero) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM comptes_comptables WHERE TRIM(numero) = :numero");
        $stmt->execute(['numero' => trim($numero)]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function findAll($filters = []) {
        $db = Database::getInstance();
        $sql = "SELECT c.*, p.numero as parent_numero, p.libelle as parent_libelle
                FROM comptes_comptables c
                LEFT JOIN comptes_comptables p ON c.compte_parent_id = p.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['classe'])) {
            $sql .= " AND c.classe = :classe";
            $params['classe'] = (int)$filters['classe'];
        }

        if (isset($filters['actif']) && $filters['actif'] !== '') {
            $sql .= " AND c.actif = :actif";
            $params['actif'] = (int)$filters['actif'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (c.numero LIKE :search OR c.libelle LIKE :search)";
            $params['search'] = '%' . trim($filters['search']) . '%';
        }

        $sql .= " ORDER BY c.numero ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getClasses() {
        return [
            1 => 'Classe 1 - Comptes de ressources durables',
            2 => 'Classe 2 - Comptes d\'actif immobilisé',
            3 => 'Classe 3 - Comptes de stocks',
            4 => 'Classe 4 - Comptes de tiers',
            5 => 'Classe 5 - Comptes de trésorerie',
            6 => 'Classe 6 - Comptes de charges',
            7 => 'Classe 7 - Comptes de produits'
        ];
    }

    public static function create($data) {
        $db = Database::getInstance();

        $numero = trim($data['numero'] ?? '');
        $libelle = trim($data['libelle'] ?? '');
        $classe = (int)($data['classe'] ?? 0);
        $nature = trim($data['nature'] ?? 'actif');
        $compteParentId = !empty($data['compte_parent_id']) ? (int)$data['compte_parent_id'] : null;
        $autoriserEcriture = isset($data['autoriser_ecriture']) ? (int)$data['autoriser_ecriture'] : 1;
        $actif = isset($data['actif']) ? (int)$data['actif'] : 1;

        if (empty($numero) || empty($libelle) || $classe <= 0) {
            throw new Exception("Le numéro, le libellé et la classe sont obligatoires.");
        }

        $existing = self::findByNumero($numero);
        if ($existing) {
            throw new Exception("Un compte comptable existe déjà avec le numéro '{$numero}'.");
        }

        $stmt = $db->prepare("
            INSERT INTO comptes_comptables (numero, libelle, classe, nature, compte_parent_id, autoriser_ecriture, est_systeme, actif)
            VALUES (:numero, :libelle, :classe, :nature, :compte_parent_id, :autoriser_ecriture, 0, :actif)
        ");
        $stmt->execute([
            'numero' => $numero,
            'libelle' => $libelle,
            'classe' => $classe,
            'nature' => $nature,
            'compte_parent_id' => $compteParentId,
            'autoriser_ecriture' => $autoriserEcriture,
            'actif' => $actif
        ]);

        return $db->lastInsertId();
    }

    public static function update($id, $data) {
        $db = Database::getInstance();

        $current = self::findById($id);
        if (!$current) {
            throw new Exception("Compte comptable introuvable.");
        }

        $numero = trim($data['numero'] ?? $current['numero']);
        $libelle = trim($data['libelle'] ?? $current['libelle']);
        $classe = (int)($data['classe'] ?? $current['classe']);
        $nature = trim($data['nature'] ?? $current['nature']);
        $compteParentId = array_key_exists('compte_parent_id', $data) ? (!empty($data['compte_parent_id']) ? (int)$data['compte_parent_id'] : null) : $current['compte_parent_id'];
        $autoriserEcriture = isset($data['autoriser_ecriture']) ? (int)$data['autoriser_ecriture'] : $current['autoriser_ecriture'];
        $actif = isset($data['actif']) ? (int)$data['actif'] : $current['actif'];

        if (empty($numero) || empty($libelle) || $classe <= 0) {
            throw new Exception("Le numéro, le libellé et la classe sont obligatoires.");
        }

        // Check uniqueness if number changed
        if ($numero !== $current['numero']) {
            $existing = self::findByNumero($numero);
            if ($existing && $existing['id'] != $id) {
                throw new Exception("Un autre compte comptable existe déjà avec le numéro '{$numero}'.");
            }
        }

        $stmt = $db->prepare("
            UPDATE comptes_comptables
            SET numero = :numero, libelle = :libelle, classe = :classe, nature = :nature,
                compte_parent_id = :compte_parent_id, autoriser_ecriture = :autoriser_ecriture, actif = :actif
            WHERE id = :id
        ");
        $stmt->execute([
            'numero' => $numero,
            'libelle' => $libelle,
            'classe' => $classe,
            'nature' => $nature,
            'compte_parent_id' => $compteParentId,
            'autoriser_ecriture' => $autoriserEcriture,
            'actif' => $actif,
            'id' => $id
        ]);

        return true;
    }

    public static function isUsed($id) {
        $db = Database::getInstance();
        $account = self::findById($id);
        if (!$account) {
            return false;
        }

        $numero = trim($account['numero']);

        // 1. Check ecritures_comptables
        $stmt = $db->prepare("SELECT COUNT(*) FROM ecritures_comptables WHERE compte_comptable_id = :id");
        $stmt->execute(['id' => $id]);
        if ((int)$stmt->fetchColumn() > 0) {
            return true;
        }

        // 2. Check comptes_financiers
        $stmt = $db->prepare("SELECT COUNT(*) FROM comptes_financiers WHERE compte_comptable_id = :id OR TRIM(compte_comptable_numero) = :num");
        $stmt->execute(['id' => $id, 'num' => $numero]);
        if ((int)$stmt->fetchColumn() > 0) {
            return true;
        }

        // 3. Check parent accounts
        $stmt = $db->prepare("SELECT COUNT(*) FROM comptes_comptables WHERE compte_parent_id = :id");
        $stmt->execute(['id' => $id]);
        if ((int)$stmt->fetchColumn() > 0) {
            return true;
        }

        // 4. Check fournisseurs
        $stmt = $db->prepare("SELECT COUNT(*) FROM fournisseurs WHERE TRIM(compte_comptable_tiers) = :num");
        $stmt->execute(['num' => $numero]);
        if ((int)$stmt->fetchColumn() > 0) {
            return true;
        }

        // 5. Check achat_categories
        $stmt = $db->prepare("SELECT COUNT(*) FROM achat_categories WHERE TRIM(compte_comptable_charge) = :num");
        $stmt->execute(['num' => $numero]);
        if ((int)$stmt->fetchColumn() > 0) {
            return true;
        }

        return false;
    }

    public static function delete($id) {
        $db = Database::getInstance();
        $account = self::findById($id);
        if (!$account) {
            throw new Exception("Compte comptable introuvable.");
        }

        if (!empty($account['est_systeme'])) {
            throw new Exception("Les comptes comptables système ne peuvent pas être supprimés.");
        }

        if (self::isUsed($id)) {
            // Soft deactivate if used instead of breaking historical ledger integrity
            self::update($id, ['actif' => 0]);
            throw new Exception("Ce compte comptable est déjà utilisé dans des écritures ou paramétrages comptables. Pour préserver l'intégrité de la comptabilité, il a été désactivé au lieu d'être supprimé.");
        }

        $stmt = $db->prepare("DELETE FROM comptes_comptables WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return true;
    }
}
