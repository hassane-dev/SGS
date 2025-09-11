<?php

require_once __DIR__ . '/../config/database.php';

class User {
    public $id_user;
    public $nom;
    public $prenom;
    public $email;
    public $mot_de_passe;
    public $role;
    public $lycee_id;
    public $actif;

    public function __construct($data = []) {
        if (!empty($data)) {
            $this->id_user = $data['id_user'] ?? null;
            $this->nom = $data['nom'] ?? '';
            $this->prenom = $data['prenom'] ?? '';
            $this->email = $data['email'] ?? '';
            $this->mot_de_passe = $data['mot_de_passe'] ?? '';
            $this->role_id = $data['role_id'] ?? null;
            $this->lycee_id = $data['lycee_id'] ?? null;
            $this->actif = $data['actif'] ?? true;
        }
    }

    /**
     * Find a user by their email address.
     *
     * @param string $email The user's email.
     * @return User|false A User object if found, otherwise false.
     */
    public static function findByEmail($email) {
        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT * FROM utilisateurs WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $userData = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($userData) {
                return new User($userData);
            }
            return false;
        } catch (PDOException $e) {
            // In a real app, log the error
            error_log("Error in User::findByEmail: " . $e->getMessage());
            return false;
        }
    }

    /**
     * A placeholder method to create the initial super user.
     * This should only be run once during setup.
     */
    public static function createSuperUser() {
        // Check if the super user already exists
        $existingUser = self::findByEmail('HasMixiOne@mine.io');
        if ($existingUser) {
            return; // Already exists
        }

        $db = Database::getInstance();
        $password = password_hash('H@s7511mat9611', PASSWORD_DEFAULT);
        // Assumes role_id 1 is 'super_admin_createur' as per seeds.sql
        $sql = "INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role_id, actif)
                VALUES ('Super', 'Creator', 'HasMixiOne@mine.io', :password, 1, true)";

        $stmt = $db->prepare($sql);
        $stmt->execute(['password' => $password]);
    }

    // --- Full CRUD Methods ---

    public static function findAll($lycee_id = null) {
        $db = Database::getInstance();
        $sql = "SELECT u.id_user, u.nom, u.prenom, u.email, u.role_id, u.actif, u.lycee_id, r.nom_role
                FROM utilisateurs u
                LEFT JOIN roles r ON u.role_id = r.id_role";
        if ($lycee_id !== null) {
            $sql .= " WHERE u.lycee_id = :lycee_id";
        }
        $sql .= " ORDER BY nom, prenom ASC";

        $stmt = $db->prepare($sql);
        if ($lycee_id !== null) {
            $stmt->execute(['lycee_id' => $lycee_id]);
        } else {
            $stmt->execute();
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findById($id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT id_user, nom, prenom, email, role_id, actif, lycee_id FROM utilisateurs WHERE id_user = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function save($data) {
        $isUpdate = !empty($data['id_user']);

        if ($isUpdate) {
            $sql = "UPDATE utilisateurs SET nom = :nom, prenom = :prenom, email = :email, role_id = :role_id, actif = :actif, lycee_id = :lycee_id";
            // Only update password if a new one is provided
            if (!empty($data['mot_de_passe'])) {
                $sql .= ", mot_de_passe = :mot_de_passe";
            }
            $sql .= " WHERE id_user = :id_user";
        } else {
            $sql = "INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role_id, actif, lycee_id)
                    VALUES (:nom, :prenom, :email, :mot_de_passe, :role_id, :actif, :lycee_id)";
        }

        $db = Database::getInstance();
        $stmt = $db->prepare($sql);

        $params = [
            'nom' => $data['nom'],
            'prenom' => $data['prenom'],
            'email' => $data['email'],
            'role_id' => $data['role_id'],
            'actif' => $data['actif'] ?? 0,
            'lycee_id' => $data['lycee_id'] ?: null,
        ];

        if (!empty($data['mot_de_passe'])) {
            $params['mot_de_passe'] = password_hash($data['mot_de_passe'], PASSWORD_DEFAULT);
        }

        if ($isUpdate) {
            $params['id_user'] = $data['id_user'];
        } else {
            // Password is required for new users
            if (empty($data['mot_de_passe'])) return false;
        }

        return $stmt->execute($params);
    }

    public static function delete($id) {
        // Prevent deleting the main super user
        if ($id == 1) {
            return false;
        }
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM utilisateurs WHERE id_user = :id");
        return $stmt->execute(['id' => $id]);
    }

    // --- Teacher-specific methods ---

    public static function getTeacherAssignments($teacher_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT c.id_classe, c.nom_classe, c.serie, m.id_matiere, m.nom_matiere
            FROM enseignant_matieres em
            JOIN classes c ON em.classe_id = c.id_classe
            JOIN matieres m ON em.matiere_id = m.id_matiere
            WHERE em.enseignant_id = :teacher_id AND em.actif = 1
            ORDER BY c.nom_classe, m.nom_matiere
        ");
        $stmt->execute(['teacher_id' => $teacher_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
