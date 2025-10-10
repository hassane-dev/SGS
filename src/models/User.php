<?php

require_once __DIR__ . '/../config/database.php';

class User {
    public $id_user;
    public $nom;
    public $prenom;
    public $sexe;
    public $date_naissance;
    public $lieu_naissance;
    public $adresse;
    public $telephone;
    public $email;
    public $mot_de_passe;
    public $fonction;
    public $role_id;
    public $lycee_id;
    public $contrat_id;
    public $date_embauche;
    public $actif;
    public $photo;

    public function __construct($data = []) {
        if (!empty($data)) {
            $this->id_user = $data['id_user'] ?? null;
            $this->nom = $data['nom'] ?? '';
            $this->prenom = $data['prenom'] ?? '';
            $this->sexe = $data['sexe'] ?? null;
            $this->date_naissance = $data['date_naissance'] ?? null;
            $this->lieu_naissance = $data['lieu_naissance'] ?? null;
            $this->adresse = $data['adresse'] ?? null;
            $this->telephone = $data['telephone'] ?? null;
            $this->email = $data['email'] ?? '';
            $this->mot_de_passe = $data['mot_de_passe'] ?? '';
            $this->fonction = $data['fonction'] ?? null;
            $this->role_id = $data['role_id'] ?? null;
            $this->lycee_id = $data['lycee_id'] ?? null;
            $this->contrat_id = $data['contrat_id'] ?? null;
            $this->date_embauche = $data['date_embauche'] ?? null;
            $this->actif = $data['actif'] ?? true;
            $this->photo = $data['photo'] ?? null;
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
        $sql = "SELECT u.id_user, u.nom, u.prenom, u.email, u.role_id, u.actif, u.lycee_id, u.fonction, r.nom_role
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
            $sql = "UPDATE utilisateurs SET
                        nom = :nom, prenom = :prenom, sexe = :sexe, date_naissance = :date_naissance,
                        lieu_naissance = :lieu_naissance, adresse = :adresse, telephone = :telephone,
                        email = :email, fonction = :fonction, role_id = :role_id, lycee_id = :lycee_id,
                        contrat_id = :contrat_id, date_embauche = :date_embauche, actif = :actif, photo = :photo";
            if (!empty($data['mot_de_passe'])) {
                $sql .= ", mot_de_passe = :mot_de_passe";
            }
            $sql .= " WHERE id_user = :id_user";
        } else {
            $sql = "INSERT INTO utilisateurs (
                        nom, prenom, sexe, date_naissance, lieu_naissance, adresse, telephone, email,
                        mot_de_passe, fonction, role_id, lycee_id, contrat_id, date_embauche, actif, photo
                    ) VALUES (
                        :nom, :prenom, :sexe, :date_naissance, :lieu_naissance, :adresse, :telephone, :email,
                        :mot_de_passe, :fonction, :role_id, :lycee_id, :contrat_id, :date_embauche, :actif, :photo
                    )";
        }

        $db = Database::getInstance();
        $stmt = $db->prepare($sql);

        $params = [
            'nom' => $data['nom'],
            'prenom' => $data['prenom'],
            'sexe' => $data['sexe'] ?? null,
            'date_naissance' => $data['date_naissance'] ?? null,
            'lieu_naissance' => $data['lieu_naissance'] ?? null,
            'adresse' => $data['adresse'] ?? null,
            'telephone' => $data['telephone'] ?? null,
            'email' => $data['email'],
            'fonction' => $data['fonction'] ?? null,
            'role_id' => $data['role_id'],
            'lycee_id' => $data['lycee_id'] ?? null,
            'contrat_id' => $data['contrat_id'] ?? null,
            'date_embauche' => $data['date_embauche'] ?? null,
            'actif' => $data['actif'] ?? 1, // Default to active
            'photo' => $data['photo'] ?? null,
        ];

        if (!empty($data['mot_de_passe'])) {
            $params['mot_de_passe'] = password_hash($data['mot_de_passe'], PASSWORD_DEFAULT);
        } elseif (!$isUpdate) {
            // Password is required for new users
            if (empty($data['mot_de_passe'])) return false;
            $params['mot_de_passe'] = password_hash($data['mot_de_passe'], PASSWORD_DEFAULT);
        }


        if ($isUpdate) {
            $params['id_user'] = $data['id_user'];
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

    public static function findAllByRoleName($role_name, $lycee_id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT u.id_user, u.nom, u.prenom FROM utilisateurs u
            JOIN roles r ON u.role_id = r.id_role
            WHERE r.nom_role = :role_name AND u.lycee_id = :lycee_id
            ORDER BY u.nom, u.prenom
        ");
        $stmt->execute(['role_name' => $role_name, 'lycee_id' => $lycee_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findOneByRoleName($role_name) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT u.* FROM utilisateurs u
            JOIN roles r ON u.role_id = r.id_role
            WHERE r.nom_role = :role_name
            LIMIT 1
        ");
        $stmt->execute(['role_name' => $role_name]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

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

    /**
     * Checks if a teacher is visible to a supervisor based on class assignments.
     * A teacher is visible if they teach in at least one class that the supervisor oversees.
     *
     * @param int $teacher_id The ID of the teacher being viewed.
     * @param int $supervisor_id The ID of the supervisor doing the viewing.
     * @return bool True if the teacher is visible, false otherwise.
     */
    public static function isTeacherVisibleToSupervisor($teacher_id, $supervisor_id) {
        $db = Database::getInstance();
        $sql = "SELECT COUNT(*)
                FROM enseignant_matieres em
                WHERE em.enseignant_id = :teacher_id
                  AND em.classe_id IN (
                      SELECT pa.target_id
                      FROM personnel_assignments pa
                      WHERE pa.personnel_id = :supervisor_id
                        AND pa.assignment_type = 'supervises_class'
                  )";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            'teacher_id' => $teacher_id,
            'supervisor_id' => $supervisor_id
        ]);

        return $stmt->fetchColumn() > 0;
    }

    public static function findTeachersBySupervisor($supervisor_id) {
        $db = Database::getInstance();
        $sql = "SELECT DISTINCT u.id_user, u.nom, u.prenom, u.email, u.role_id, u.actif, u.lycee_id, u.fonction, r.nom_role
                FROM utilisateurs u
                JOIN roles r ON u.role_id = r.id_role
                JOIN enseignant_matieres em ON u.id_user = em.enseignant_id
                JOIN personnel_assignments pa ON em.classe_id = pa.target_id
                WHERE pa.personnel_id = :supervisor_id
                  AND pa.assignment_type = 'supervises_class'
                  AND r.nom_role = 'enseignant'
                ORDER BY u.nom, u.prenom ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute(['supervisor_id' => $supervisor_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function assignTeacherToClass($teacher_id, $class_id, $matiere_id) {
        $db = Database::getInstance();
        // Use INSERT IGNORE to prevent errors if the assignment already exists
        $sql = "INSERT IGNORE INTO enseignant_matieres (enseignant_id, classe_id, matiere_id, actif) VALUES (:teacher_id, :class_id, :matiere_id, 1)";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            'teacher_id' => $teacher_id,
            'class_id' => $class_id,
            'matiere_id' => $matiere_id
        ]);
    }

    public static function unassignTeacherFromClass($teacher_id, $class_id, $matiere_id) {
        $db = Database::getInstance();
        $sql = "DELETE FROM enseignant_matieres WHERE enseignant_id = :teacher_id AND classe_id = :class_id AND matiere_id = :matiere_id";
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            'teacher_id' => $teacher_id,
            'class_id' => $class_id,
            'matiere_id' => $matiere_id
        ]);
    }
}
?>
