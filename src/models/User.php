<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/AnneeAcademique.php';

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

        // --- Validation ---
        if (empty($data['nom']) || empty($data['prenom'])) {
            throw new InvalidArgumentException("Le nom et le prénom sont obligatoires.");
        }
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Une adresse email valide est obligatoire.");
        }
        if (empty($data['role_id'])) {
            throw new InvalidArgumentException("Un rôle est obligatoire.");
        }
        if (!$isUpdate && empty($data['mot_de_passe'])) {
            throw new InvalidArgumentException("Un mot de passe est obligatoire pour les nouveaux utilisateurs.");
        }
        // --- End Validation ---

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

    public static function findTeachers($lycee_id) {
        if (!$lycee_id) return [];

        $db = Database::getInstance();
        $sql = "
            SELECT u.id_user, CONCAT(u.prenom, ' ', u.nom) as full_name
            FROM utilisateurs u
            JOIN roles r ON u.role_id = r.id_role
            WHERE u.lycee_id = :lycee_id
            AND r.nom_role = 'enseignant'
            ORDER BY full_name ASC
        ";
        try {
            $stmt = $db->prepare($sql);
            $stmt->execute(['lycee_id' => $lycee_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in User::findTeachers: " . $e->getMessage());
            return [];
        }
    }

    public static function findSubjectsTaughtByTeacher($enseignant_id) {
        $active_year = AnneeAcademique::findActive();
        if (!$active_year) return [];

        $sql = "
            SELECT
                em.classe_id,
                em.matiere_id,
                c.nom_classe,
                m.nom_matiere
            FROM enseignant_matieres em
            JOIN classes c ON em.classe_id = c.id_classe
            JOIN matieres m ON em.matiere_id = m.id_matiere
            WHERE em.enseignant_id = :enseignant_id
            AND em.annee_academique_id = :annee_id
            GROUP BY em.classe_id, em.matiere_id, c.nom_classe, m.nom_matiere
            ORDER BY c.nom_classe, m.nom_matiere
        ";

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare($sql);
            $stmt->execute([
                'enseignant_id' => $enseignant_id,
                'annee_id' => $active_year['id']
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in User::findSubjectsTaughtByTeacher: " . $e->getMessage());
            return [];
        }
    }
}
?>
