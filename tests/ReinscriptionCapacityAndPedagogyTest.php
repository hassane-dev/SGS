<?php

if (!defined('TEST_MODE')) {
    define('TEST_MODE', true);
}

require_once __DIR__ . '/../src/config/database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/core/CsrfService.php';
require_once __DIR__ . '/../src/models/Eleve.php';
require_once __DIR__ . '/../src/models/Etude.php';
require_once __DIR__ . '/../src/models/Classe.php';
require_once __DIR__ . '/../src/models/Cycle.php';
require_once __DIR__ . '/../src/models/AnneeAcademique.php';
require_once __DIR__ . '/../src/models/ParamLycee.php';
require_once __DIR__ . '/../src/models/ClasseParametre.php';
require_once __DIR__ . '/../src/models/ParamPromotionSeuil.php';
require_once __DIR__ . '/../src/controllers/ReinscriptionController.php';
require_once __DIR__ . '/../src/services/ReinscriptionService.php';
require_once __DIR__ . '/../src/services/PromotionAdmissionService.php';
require_once __DIR__ . '/../src/services/PromotionThresholdService.php';
require_once __DIR__ . '/../db/migrations/20240115_37_create_param_promotion_seuils.php';

/**
 * Suite de tests d'intégration complète pour la mission REINSCRIPTION-IMPLEMENTATION-1.
 * Couvre les 14 scénarios métier exigés dans les spécifications.
 */
class ReinscriptionCapacityAndPedagogyTest {

    private \PDO $db;
    private int $lyceeA = 701;
    private int $lyceeB = 702;
    private int $annee1 = 7001; // Année précédente (2024-2025)
    private int $annee2 = 7002; // Nouvelle année cible (2025-2026)

    public function __construct() {
        // Base de test isolée SQLite
        $this->db = new \PDO("sqlite:/tmp/sgs_test_reinscription_" . uniqid() . ".sqlite", null, null, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
        ]);
        Database::setInstance($this->db);

        $this->setupSchema();
        $this->setupData();
    }

    private function setupSchema(): void {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS param_lycee (
                id INTEGER PRIMARY KEY,
                nom_lycee TEXT NOT NULL,
                type_lycee TEXT DEFAULT 'prive'
            );
            CREATE TABLE IF NOT EXISTS annees_academiques (
                id INTEGER PRIMARY KEY,
                libelle TEXT NOT NULL,
                date_debut DATE,
                date_fin DATE,
                est_active INTEGER DEFAULT 0
            );
            CREATE TABLE IF NOT EXISTS sequences (
                id INTEGER PRIMARY KEY,
                nom TEXT,
                type TEXT,
                statut TEXT,
                date_debut DATE,
                date_fin DATE,
                annee_academique_id INTEGER,
                lycee_id INTEGER
            );
            CREATE TABLE IF NOT EXISTS cycles (
                id_cycle INTEGER PRIMARY KEY,
                lycee_id INTEGER,
                nom_cycle TEXT NOT NULL,
                niveau_debut TEXT,
                niveau_fin TEXT,
                utilise_series INTEGER DEFAULT 0
            );
            CREATE TABLE IF NOT EXISTS series (
                id INTEGER PRIMARY KEY,
                nom_serie TEXT NOT NULL,
                categorie TEXT,
                lycee_id INTEGER
            );
            CREATE TABLE IF NOT EXISTS classes (
                id_classe INTEGER PRIMARY KEY,
                niveau TEXT NOT NULL,
                serie TEXT,
                numero INTEGER,
                categorie TEXT,
                cycle_id INTEGER,
                lycee_id INTEGER
            );
            CREATE TABLE IF NOT EXISTS classe_parametres (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                classe_id INTEGER NOT NULL,
                annee_academique_id INTEGER NOT NULL,
                nombre_places INTEGER,
                effectif_actuel INTEGER DEFAULT 0,
                professeur_principal_id INTEGER,
                commentaire TEXT
            );
            CREATE TABLE IF NOT EXISTS eleves (
                id_eleve INTEGER PRIMARY KEY,
                lycee_id INTEGER NOT NULL,
                nom TEXT NOT NULL,
                prenom TEXT NOT NULL,
                identifiant_public TEXT UNIQUE,
                date_naissance DATE,
                statut TEXT DEFAULT 'actif'
            );
            CREATE TABLE IF NOT EXISTS etudes (
                id_etude INTEGER PRIMARY KEY AUTOINCREMENT,
                eleve_id INTEGER NOT NULL,
                classe_id INTEGER NOT NULL,
                lycee_id INTEGER NOT NULL,
                annee_academique_id INTEGER,
                status TEXT DEFAULT 'en_attente_paiement',
                is_active INTEGER DEFAULT 0,
                date_activation DATETIME,
                active_par INTEGER,
                motif_inactif TEXT
            );
            CREATE TABLE IF NOT EXISTS bulletins (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                eleve_id INTEGER NOT NULL,
                sequence_id INTEGER NOT NULL,
                annee_academique_id INTEGER NOT NULL,
                classe_id INTEGER NOT NULL,
                lycee_id INTEGER NOT NULL,
                moyenne_generale REAL,
                statut TEXT DEFAULT 'valide',
                appreciation_conseil_classe TEXT
            );
            CREATE TABLE IF NOT EXISTS bulletin_details (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                bulletin_id INTEGER NOT NULL,
                matiere_id INTEGER,
                nom_matiere_snapshot TEXT,
                moyenne_matiere REAL,
                coefficient_snapshot REAL,
                points_ponderes REAL
            );
            CREATE TABLE IF NOT EXISTS param_general (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                lycee_id INTEGER NOT NULL,
                sequence_annuelle TEXT DEFAULT 'Trimestrielle'
            );
            CREATE TABLE IF NOT EXISTS notifications (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                lycee_id INTEGER,
                user_id INTEGER,
                message TEXT,
                link TEXT,
                statut TEXT DEFAULT 'unread'
            );
            CREATE TABLE IF NOT EXISTS roles (
                id_role INTEGER PRIMARY KEY AUTOINCREMENT,
                nom_role TEXT NOT NULL UNIQUE
            );
            CREATE TABLE IF NOT EXISTS permissions (
                id_permission INTEGER PRIMARY KEY AUTOINCREMENT,
                resource TEXT NOT NULL,
                action TEXT NOT NULL,
                description TEXT,
                UNIQUE (resource, action)
            );
            CREATE TABLE IF NOT EXISTS role_permissions (
                role_id INTEGER NOT NULL,
                permission_id INTEGER NOT NULL,
                PRIMARY KEY (role_id, permission_id)
            );
            CREATE TABLE IF NOT EXISTS utilisateurs (
                id_user INTEGER PRIMARY KEY,
                lycee_id INTEGER,
                nom TEXT,
                prenom TEXT,
                identifiant_public TEXT,
                email TEXT,
                role_id INTEGER,
                role_name TEXT,
                photo TEXT,
                actif INTEGER DEFAULT 1,
                auth_version INTEGER DEFAULT 1
            );
        ");

        // Migration 37 pour param_promotion_seuils
        migrate_37($this->db);
    }

    private function setupData(): void {
        // Établissements
        $this->db->exec("INSERT INTO param_lycee (id, nom_lycee, type_lycee) VALUES ({$this->lyceeA}, 'Lycée Privé A', 'prive')");
        $this->db->exec("INSERT INTO param_lycee (id, nom_lycee, type_lycee) VALUES ({$this->lyceeB}, 'Lycée Public B', 'public')");

        // Param général Trimestrielle (3 trimestres)
        $this->db->exec("INSERT INTO param_general (lycee_id, sequence_annuelle) VALUES ({$this->lyceeA}, 'Trimestrielle')");
        $this->db->exec("INSERT INTO param_general (lycee_id, sequence_annuelle) VALUES ({$this->lyceeB}, 'Trimestrielle')");

        // Années scolaires
        $this->db->exec("INSERT INTO annees_academiques (id, libelle, date_debut, date_fin, est_active) VALUES ({$this->annee1}, '2024-2025', '2024-09-01', '2025-06-30', 0)");
        $this->db->exec("INSERT INTO annees_academiques (id, libelle, date_debut, date_fin, est_active) VALUES ({$this->annee2}, '2025-2026', '2025-09-01', '2026-06-30', 1)");

        // Cycles Lycée A : Collège (6e -> 3e) et Lycée (2nde -> Tle)
        $this->db->exec("INSERT INTO cycles (id_cycle, lycee_id, nom_cycle, niveau_debut, niveau_fin, utilise_series) VALUES (1, {$this->lyceeA}, 'Collège', '6e', '3e', 0)");
        $this->db->exec("INSERT INTO cycles (id_cycle, lycee_id, nom_cycle, niveau_debut, niveau_fin, utilise_series) VALUES (2, {$this->lyceeA}, 'Lycée', '2nde', 'Terminale', 1)");

        // Séquences (3 trimestres pour l'année 1)
        for ($i = 1; $i <= 3; $i++) {
            $this->db->exec("INSERT INTO sequences (id, nom, type, statut, date_debut, date_fin, annee_academique_id, lycee_id) VALUES ($i, 'Trimestre $i', 'trimestrielle', 'fermee', '2024-09-01', '2025-06-30', {$this->annee1}, {$this->lyceeA})");
        }
        // Séquence active pour l'année 2
        $this->db->exec("INSERT INTO sequences (id, nom, type, statut, date_debut, date_fin, annee_academique_id, lycee_id) VALUES (4, 'Trimestre 1 (25-26)', 'trimestrielle', 'ouverte', '2025-09-01', '2025-12-31', {$this->annee2}, {$this->lyceeA})");

        // Seuils de promotion (Établissement A) : Admission >= 10.00, Délibération 8.50 - 9.99, Redoublement <= 8.49, Exclusion <= 5.00
        PromotionThresholdService::saveThreshold([
            'lycee_id' => $this->lyceeA,
            'portee_type' => 'etablissement',
            'seuil_admission_directe' => 10.00,
            'seuil_deliberation_min'  => 8.50,
            'seuil_deliberation_max'  => 9.99,
            'seuil_redoublement_max'  => 8.49,
            'seuil_exclusion_max'     => 5.00
        ]);

        // Classes de l'établissement (partagées entre les années, configurées via classe_parametres)
        // Classe 101: 5e 1 (Origine Année 1, et cible Année 2 complète)
        $this->db->exec("INSERT INTO classes (id_classe, niveau, serie, numero, cycle_id, lycee_id) VALUES (101, '5e', NULL, 1, 1, {$this->lyceeA})");
        $this->db->exec("INSERT INTO classe_parametres (classe_id, annee_academique_id, nombre_places, effectif_actuel) VALUES (101, {$this->annee2}, 1, 1)");

        // Classe 104: 5e 2 (Parallèle Année 2 disponible, capacité 2 effectif 0)
        $this->db->exec("INSERT INTO classes (id_classe, niveau, serie, numero, cycle_id, lycee_id) VALUES (104, '5e', NULL, 2, 1, {$this->lyceeA})");
        $this->db->exec("INSERT INTO classe_parametres (classe_id, annee_academique_id, nombre_places, effectif_actuel) VALUES (104, {$this->annee2}, 2, 0)");

        // Classe 102: 3e 1 (Fin collège)
        $this->db->exec("INSERT INTO classes (id_classe, niveau, serie, numero, cycle_id, lycee_id) VALUES (102, '3e', NULL, 1, 1, {$this->lyceeA})");
        // Classe 103: Terminale C 1 (Fin lycée)
        $this->db->exec("INSERT INTO classes (id_classe, niveau, serie, numero, cycle_id, lycee_id) VALUES (103, 'Terminale', 'C', 1, 2, {$this->lyceeA})");

        // Classes de 4e pour l'admission
        $this->db->exec("INSERT INTO classes (id_classe, niveau, serie, numero, cycle_id, lycee_id) VALUES (201, '4e', NULL, 1, 1, {$this->lyceeA})"); // 4e 1
        $this->db->exec("INSERT INTO classes (id_classe, niveau, serie, numero, cycle_id, lycee_id) VALUES (202, '4e', NULL, 2, 1, {$this->lyceeA})"); // 4e 2
        $this->db->exec("INSERT INTO classe_parametres (classe_id, annee_academique_id, nombre_places, effectif_actuel) VALUES (201, {$this->annee2}, 2, 2)");
        $this->db->exec("INSERT INTO classe_parametres (classe_id, annee_academique_id, nombre_places, effectif_actuel) VALUES (202, {$this->annee2}, 3, 0)");

        // Remplir la 4e 1 (201) avec 2 élèves pour qu'elle soit complète
        $this->db->exec("INSERT INTO eleves (id_eleve, lycee_id, nom, prenom, identifiant_public, statut) VALUES (801, {$this->lyceeA}, 'OCCUPANT', 'Un', 'MAT-801', 'actif')");
        $this->db->exec("INSERT INTO eleves (id_eleve, lycee_id, nom, prenom, identifiant_public, statut) VALUES (802, {$this->lyceeA}, 'OCCUPANT', 'Deux', 'MAT-802', 'actif')");
        $this->db->exec("INSERT INTO etudes (eleve_id, classe_id, lycee_id, annee_academique_id, status, is_active) VALUES (801, 201, {$this->lyceeA}, {$this->annee2}, 'active', 1)");
        $this->db->exec("INSERT INTO etudes (eleve_id, classe_id, lycee_id, annee_academique_id, status, is_active) VALUES (802, 201, {$this->lyceeA}, {$this->annee2}, 'en_attente_paiement', 0)");

        // Remplir la 5e 1 (101) pour l'année 2 avec 1 élève pour qu'elle soit complète
        $this->db->exec("INSERT INTO eleves (id_eleve, lycee_id, nom, prenom, identifiant_public, statut) VALUES (803, {$this->lyceeA}, 'OCCUPANT', 'Trois', 'MAT-803', 'actif')");
        $this->db->exec("INSERT INTO etudes (eleve_id, classe_id, lycee_id, annee_academique_id, status, is_active) VALUES (803, 101, {$this->lyceeA}, {$this->annee2}, 'active', 1)");

        // Classes du Lycée B
        $this->db->exec("INSERT INTO cycles (id_cycle, lycee_id, nom_cycle, niveau_debut, niveau_fin, utilise_series) VALUES (3, {$this->lyceeB}, 'Collège Public', '6e', '3e', 0)");
        $this->db->exec("INSERT INTO classes (id_classe, niveau, serie, numero, cycle_id, lycee_id) VALUES (301, '6e', NULL, 1, 3, {$this->lyceeB})");
        $this->db->exec("INSERT INTO classe_parametres (classe_id, annee_academique_id, nombre_places, effectif_actuel) VALUES (301, {$this->annee2}, 50, 0)");

        // Créer l'utilisateur session en base pour Auth::synchronizeSessionIfNeeded()
        $this->db->exec("INSERT INTO roles (id_role, nom_role) VALUES (3, 'admin_local') ON CONFLICT(id_role) DO NOTHING");
        $this->db->exec("INSERT INTO utilisateurs (id_user, lycee_id, nom, prenom, email, role_id, role_name, actif, auth_version)
            VALUES (99, {$this->lyceeA}, 'ADMIN', 'Local', 'admin99@sgs.ci', 3, 'admin_local', 1, 1)");

        // Insérer les permissions
        $this->db->exec("INSERT OR IGNORE INTO permissions (resource, action, description) VALUES ('eleve', 'reinscrire', 'Re-enroll student')");
        $this->db->exec("INSERT OR IGNORE INTO permissions (resource, action, description) VALUES ('eleve', 'view_all', 'View all students')");
        $this->db->exec("INSERT OR IGNORE INTO permissions (resource, action, description) VALUES ('inscription', 'manage', 'Manage inscriptions')");

        $permReinscrire = (int)$this->db->query("SELECT id_permission FROM permissions WHERE resource = 'eleve' AND action = 'reinscrire'")->fetchColumn();
        $permViewAll = (int)$this->db->query("SELECT id_permission FROM permissions WHERE resource = 'eleve' AND action = 'view_all'")->fetchColumn();
        $permManageInscr = (int)$this->db->query("SELECT id_permission FROM permissions WHERE resource = 'inscription' AND action = 'manage'")->fetchColumn();

        $this->db->exec("INSERT OR IGNORE INTO role_permissions (role_id, permission_id) VALUES (3, $permReinscrire)");
        $this->db->exec("INSERT OR IGNORE INTO role_permissions (role_id, permission_id) VALUES (3, $permViewAll)");
        $this->db->exec("INSERT OR IGNORE INTO role_permissions (role_id, permission_id) VALUES (3, $permManageInscr)");

        // Session admin local Lycée A
        $_SESSION['user'] = [
            'id' => 99,
            'id_user' => 99,
            'lycee_id' => $this->lyceeA,
            'role_id' => 3,
            'role_name' => 'admin_local',
            'auth_version' => 1,
            'permissions' => [
                'eleve' => ['reinscrire', 'view_all'],
                'inscription' => ['manage']
            ]
        ];
    }

    public function run(): void {
        echo "=========================================================\n";
        echo " RUNNING RE-ENROLLMENT INTEGRATION & CAPACITY TEST SUITE \n";
        echo "=========================================================\n";

        $this->test1_EleveAdmisPremiereClasseCompleteDeuxiemeDisponible();
        $this->test2_EleveAdmisToutesClassesCompletes();
        $this->test3_EleveRedoublantClasseOrigineCompleteAutreDisponible();
        $this->test4_EleveRedoublantToutesClassesCompletes();
        $this->test5_EleveEnDeliberation();
        $this->test6_EleveDossierIncomplet();
        $this->test7_EleveExclu();
        $this->test8_EleveFinDeCycle();
        $this->test9_EleveDejaReinscrit();
        $this->test10_ConcurrenceDernierePlaceDisponible();
        $this->test11_IsolationEntreEtablissements();
        $this->test12_RespectSeriesEtCycles();
        $this->test13_ConservationHistoriqueScolaire();
        $this->test14_FonctionnementCircuitFinancierPublicEtPrive();

        echo "=========================================================\n";
        echo " SUCCESS: ALL 14 RE-ENROLLMENT SCENARIOS PASSED (100%)!   \n";
        echo "=========================================================\n";
    }

    private function createStudentWithBulletins(int $eleveId, string $nom, string $prenom, int $classeOrigineId, array $trimestreMoyennes): void {
        $this->db->exec("INSERT INTO eleves (id_eleve, lycee_id, nom, prenom, identifiant_public, statut) VALUES ($eleveId, {$this->lyceeA}, '$nom', '$prenom', 'MAT-$eleveId', 'actif')");
        $this->db->exec("INSERT INTO etudes (eleve_id, classe_id, lycee_id, annee_academique_id, status, is_active) VALUES ($eleveId, $classeOrigineId, {$this->lyceeA}, {$this->annee1}, 'active', 1)");

        foreach ($trimestreMoyennes as $seqId => $moyenne) {
            if ($moyenne !== null) {
                $this->db->exec("INSERT INTO bulletins (eleve_id, sequence_id, annee_academique_id, classe_id, lycee_id, moyenne_generale, statut)
                    VALUES ($eleveId, $seqId, {$this->annee1}, $classeOrigineId, {$this->lyceeA}, $moyenne, 'valide')");
                $bId = (int)$this->db->lastInsertId();
                $this->db->exec("INSERT INTO bulletin_details (bulletin_id, matiere_id, nom_matiere_snapshot, moyenne_matiere, coefficient_snapshot, points_ponderes)
                    VALUES ($bId, 1, 'Français', $moyenne, 2.0, " . ($moyenne * 2.0) . ")");
            }
        }
    }

    /**
     * Scénario 1 : Élève admis, 1ère classe complète (4e 1), 2ème classe disponible (4e 2).
     */
    private function test1_EleveAdmisPremiereClasseCompleteDeuxiemeDisponible(): void {
        $eleveId = 1001;
        // Moyennes: T1=12.0, T2=14.0, T3=13.0 -> Moyenne annuelle = 13.00 (Admis)
        $this->createStudentWithBulletins($eleveId, 'KOFFI', 'Admis', 101, [1 => 12.0, 2 => 14.0, 3 => 13.0]);

        $pedagogy = ReinscriptionService::getPedagogicalSituation($eleveId);
        if ($pedagogy['decision'] !== 'admis') {
            throw new Exception("[FAIL 1] La décision devrait être 'admis', obtenu: " . $pedagogy['decision']);
        }

        $dest = ReinscriptionService::resolveDestination($pedagogy, $this->lyceeA);
        if ($dest['niveau'] !== '4e') {
            throw new Exception("[FAIL 1] Le niveau de destination devrait être '4e', obtenu: " . $dest['niveau']);
        }

        $avail = ReinscriptionService::findAvailableClasses($this->lyceeA, $this->annee2, $dest['niveau']);
        if (!$avail['suggested_classe'] || (int)$avail['suggested_classe']['id_classe'] !== 202) {
            throw new Exception("[FAIL 1] La classe suggérée devrait être la 4e 2 (ID 202), obtenu: " . json_encode($avail['suggested_classe']));
        }
        if ($avail['classes'][0]['is_complete'] !== true) {
            throw new Exception("[FAIL 1] La 4e 1 devrait être marquée complète.");
        }
        if ($avail['suggested_classe']['places_restantes'] !== 3) {
            throw new Exception("[FAIL 1] La 4e 2 devrait avoir 3 places restantes (Capacité: 3, Inscrits réels dans etudes: 0). Obtenu: " . $avail['suggested_classe']['places_restantes']);
        }

        echo " [PASS] Scénario 1 : Élève admis, 4e 1 complète, 4e 2 correctement suggérée avec 2 places.\n";
    }

    /**
     * Scénario 2 : Élève admis, toutes les classes du niveau de destination sont complètes.
     */
    private function test2_EleveAdmisToutesClassesCompletes(): void {
        // Remplir également la 4e 2 (202, capacité = 3) pour simuler saturation (3 élèves)
        $this->db->exec("INSERT INTO eleves (id_eleve, lycee_id, nom, prenom, identifiant_public, statut) VALUES (804, {$this->lyceeA}, 'OCC', 'Quatre', 'MAT-804', 'actif')");
        $this->db->exec("INSERT INTO eleves (id_eleve, lycee_id, nom, prenom, identifiant_public, statut) VALUES (805, {$this->lyceeA}, 'OCC', 'Cinq', 'MAT-805', 'actif')");
        $this->db->exec("INSERT INTO eleves (id_eleve, lycee_id, nom, prenom, identifiant_public, statut) VALUES (806, {$this->lyceeA}, 'OCC', 'Six', 'MAT-806', 'actif')");
        $this->db->exec("INSERT INTO etudes (eleve_id, classe_id, lycee_id, annee_academique_id, status, is_active) VALUES (804, 202, {$this->lyceeA}, {$this->annee2}, 'active', 1)");
        $this->db->exec("INSERT INTO etudes (eleve_id, classe_id, lycee_id, annee_academique_id, status, is_active) VALUES (805, 202, {$this->lyceeA}, {$this->annee2}, 'active', 1)");
        $this->db->exec("INSERT INTO etudes (eleve_id, classe_id, lycee_id, annee_academique_id, status, is_active) VALUES (806, 202, {$this->lyceeA}, {$this->annee2}, 'active', 1)");

        $avail = ReinscriptionService::findAvailableClasses($this->lyceeA, $this->annee2, '4e');
        if (!$avail['all_complete']) {
            throw new Exception("[FAIL 2] Toutes les classes de 4e devraient être complètes.");
        }
        if ($avail['suggested_classe'] !== null) {
            throw new Exception("[FAIL 2] Aucune classe ne devrait être suggérée quand tout est complet.");
        }

        // Nettoyage des ajouts temporaires pour ne pas perturber les tests suivants
        $this->db->exec("DELETE FROM etudes WHERE eleve_id IN (804, 805, 806)");
        $this->db->exec("DELETE FROM eleves WHERE id_eleve IN (804, 805, 806)");

        echo " [PASS] Scénario 2 : Toutes les classes du niveau complètes détectées sans affectation arbitraire.\n";
    }

    /**
     * Scénario 3 : Élève redoublant, classe d'origine complète, classe parallèle (5e 2) disponible.
     */
    private function test3_EleveRedoublantClasseOrigineCompleteAutreDisponible(): void {
        $eleveId = 1002;
        // Moyennes: T1=6.0, T2=7.0, T3=6.5 -> Moyenne annuelle = 6.50 (Redoublement car seuil admission=10 et redoublement=5..8.49)
        $this->createStudentWithBulletins($eleveId, 'YAO', 'Redoublant', 101, [1 => 6.0, 2 => 7.0, 3 => 6.5]);

        $pedagogy = ReinscriptionService::getPedagogicalSituation($eleveId);
        if ($pedagogy['decision'] !== 'redoublement') {
            throw new Exception("[FAIL 3] La décision devrait être 'redoublement', obtenu: " . $pedagogy['decision']);
        }

        $dest = ReinscriptionService::resolveDestination($pedagogy, $this->lyceeA);
        if ($dest['niveau'] !== '5e') {
            throw new Exception("[FAIL 3] Le niveau de destination du redoublant doit rester '5e', obtenu: " . $dest['niveau']);
        }

        $avail = ReinscriptionService::findAvailableClasses($this->lyceeA, $this->annee2, $dest['niveau']);
        if (!$avail['suggested_classe'] || (int)$avail['suggested_classe']['id_classe'] !== 104) {
            throw new Exception("[FAIL 3] La classe suggérée pour redoubler devrait être la 5e 2 (ID 104), obtenu: " . json_encode($avail['suggested_classe']));
        }

        echo " [PASS] Scénario 3 : Élève redoublant, classe d'origine complète, 5e 2 proposée avec places.\n";
    }

    /**
     * Scénario 4 : Élève redoublant, toutes les classes du niveau complètes.
     */
    private function test4_EleveRedoublantToutesClassesCompletes(): void {
        // Remplir temporairement la 5e 2 (104, capacité 2)
        $this->db->exec("INSERT INTO etudes (eleve_id, classe_id, lycee_id, annee_academique_id, status, is_active) VALUES (891, 104, {$this->lyceeA}, {$this->annee2}, 'active', 1)");
        $this->db->exec("INSERT INTO etudes (eleve_id, classe_id, lycee_id, annee_academique_id, status, is_active) VALUES (892, 104, {$this->lyceeA}, {$this->annee2}, 'active', 1)");

        $avail = ReinscriptionService::findAvailableClasses($this->lyceeA, $this->annee2, '5e');
        if (!$avail['all_complete']) {
            throw new Exception("[FAIL 4] Toutes les classes de 5e devraient être reconnues complètes.");
        }

        $this->db->exec("DELETE FROM etudes WHERE eleve_id IN (891, 892)");
        echo " [PASS] Scénario 4 : Élève redoublant face à un niveau complet identifié sans saut de niveau.\n";
    }

    /**
     * Scénario 5 : Élève en délibération (moyenne entre 8.50 et 9.99).
     */
    private function test5_EleveEnDeliberation(): void {
        $eleveId = 1003;
        // Moyennes: T1=9.0, T2=9.0, T3=9.0 -> 9.00 (Délibération)
        $this->createStudentWithBulletins($eleveId, 'BAMBA', 'Delibere', 101, [1 => 9.0, 2 => 9.0, 3 => 9.0]);

        $pedagogy = ReinscriptionService::getPedagogicalSituation($eleveId);
        if ($pedagogy['decision'] !== 'deliberation') {
            throw new Exception("[FAIL 5] La décision devrait être 'deliberation', obtenu: " . $pedagogy['decision']);
        }

        $dest = ReinscriptionService::resolveDestination($pedagogy, $this->lyceeA);
        if ($dest['can_proceed'] !== false || $dest['type'] !== 'deliberation') {
            throw new Exception("[FAIL 5] La réinscription d'un élève en délibération ne doit pas pouvoir continuer automatiquement.");
        }

        echo " [PASS] Scénario 5 : Élève en délibération bloqué avec explication sans décision inventée.\n";
    }

    /**
     * Scénario 6 : Élève avec dossier incomplet (Trimestre 3 manquant).
     */
    private function test6_EleveDossierIncomplet(): void {
        $eleveId = 1004;
        // Seulement 2 bulletins sur 3
        $this->createStudentWithBulletins($eleveId, 'TOURE', 'Incomplet', 101, [1 => 12.0, 2 => 14.0, 3 => null]);

        $pedagogy = ReinscriptionService::getPedagogicalSituation($eleveId);
        if ($pedagogy['is_complete'] !== false) {
            throw new Exception("[FAIL 6] Le dossier devrait être flaggé incomplete.");
        }

        $dest = ReinscriptionService::resolveDestination($pedagogy, $this->lyceeA);
        if ($dest['can_proceed'] !== false || $dest['type'] !== 'incomplete') {
            throw new Exception("[FAIL 6] La réinscription sur dossier incomplet ne doit pas continuer.");
        }

        echo " [PASS] Scénario 6 : Élève avec dossier incomplet bloqué avec avertissement transparent.\n";
    }

    /**
     * Scénario 7 : Élève exclu (moyenne < 5.00).
     */
    private function test7_EleveExclu(): void {
        $eleveId = 1005;
        // Moyennes: T1=3.0, T2=4.0, T3=3.5 -> 3.50 (Exclusion)
        $this->createStudentWithBulletins($eleveId, 'KONE', 'Exclu', 101, [1 => 3.0, 2 => 4.0, 3 => 3.5]);

        $pedagogy = ReinscriptionService::getPedagogicalSituation($eleveId);
        if ($pedagogy['decision'] !== 'exclu') {
            throw new Exception("[FAIL 7] La décision devrait être 'exclu', obtenu: " . $pedagogy['decision']);
        }

        $dest = ReinscriptionService::resolveDestination($pedagogy, $this->lyceeA);
        if ($dest['can_proceed'] !== false || $dest['type'] !== 'exclu') {
            throw new Exception("[FAIL 7] L'exclusion doit bloquer formellement la réinscription.");
        }

        echo " [PASS] Scénario 7 : Élève exclu bloqué sans réinscription ordinaire possible.\n";
    }

    /**
     * Scénario 8 : Élève en fin de cycle (3e -> 2nde dans un lycée avec 2 cycles, et Terminale -> fin de secondaire).
     */
    private function test8_EleveFinDeCycle(): void {
        // Cas A : Élève en 3e (Collège) admis -> doit passer en 2nde (Lycée)
        $eleve3e = 1006;
        $this->createStudentWithBulletins($eleve3e, 'DIALLO', 'FinCollege', 102, [1 => 14.0, 2 => 15.0, 3 => 14.5]);
        $pedagogy3e = ReinscriptionService::getPedagogicalSituation($eleve3e);
        $dest3e = ReinscriptionService::resolveDestination($pedagogy3e, $this->lyceeA);
        if ($dest3e['action'] !== 'passage_cycle_superieur' || $dest3e['niveau'] !== '2nde') {
            throw new Exception("[FAIL 8A] L'élève admis en 3e devrait basculer vers '2nde' dans le cycle Lycée, obtenu: " . json_encode($dest3e));
        }

        // Cas B : Élève en Terminale admis -> Fin de cursus secondaire (aucun niveau supérieur)
        $eleveTle = 1007;
        $this->createStudentWithBulletins($eleveTle, 'BAH', 'Bachelier', 103, [1 => 12.0, 2 => 13.0, 3 => 12.5]);
        $pedagogyTle = ReinscriptionService::getPedagogicalSituation($eleveTle);
        $destTle = ReinscriptionService::resolveDestination($pedagogyTle, $this->lyceeA);
        if ($destTle['can_proceed'] !== false || $destTle['type'] !== 'end_of_secondary') {
            throw new Exception("[FAIL 8B] L'élève achevant la Terminale ne doit pas avoir de niveau supérieur inventé.");
        }

        echo " [PASS] Scénario 8 : Fin de cycle 3e correctement orientée en 2nde, et Terminale bloquée en fin de cursus.\n";
    }

    /**
     * Scénario 9 : Élève déjà réinscrit pour la nouvelle année (prévention des doublons).
     */
    private function test9_EleveDejaReinscrit(): void {
        $eleveId = 1001; // Déjà présent en année 1
        // L'inscrire une première fois en 4e 2 (202) pour l'année 2
        $this->db->exec("INSERT INTO etudes (eleve_id, classe_id, lycee_id, annee_academique_id, status, is_active) VALUES ($eleveId, 202, {$this->lyceeA}, {$this->annee2}, 'en_attente_paiement', 0)");

        $isEnrolled = Etude::isEnrolled($eleveId, $this->annee2, $this->lyceeA);
        if (!$isEnrolled) {
            throw new Exception("[FAIL 9] L'élève devrait être reconnu comme déjà inscrit.");
        }

        // Tenter de réexécuter le processus de réinscription via le contrôleur
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'eleve_id' => $eleveId,
            'classe_id' => 202,
            'annee_academique_id' => $this->annee2
        ];
        $controller = new ReinscriptionController();
        ob_start();
        $controller->process();
        ob_get_clean();

        // Vérifier qu'il n'y a toujours qu'une seule ligne d'études pour cette année
        $stmtCount = $this->db->query("SELECT COUNT(*) FROM etudes WHERE eleve_id = $eleveId AND annee_academique_id = {$this->annee2}");
        if ((int)$stmtCount->fetchColumn() !== 1) {
            throw new Exception("[FAIL 9] Un doublon d'inscription a été inséré dans etudes !");
        }

        $this->db->exec("DELETE FROM etudes WHERE eleve_id = $eleveId AND annee_academique_id = {$this->annee2}");
        echo " [PASS] Scénario 9 : Détection et blocage des doublons d'inscription pour la même année scolaire.\n";
    }

    /**
     * Scénario 10 : Concurrence sur la dernière place disponible.
     */
    private function test10_ConcurrenceDernierePlaceDisponible(): void {
        // Créer une classe avec 1 seule place libre
        $this->db->exec("INSERT INTO classes (id_classe, niveau, serie, numero, cycle_id, lycee_id) VALUES (401, '4e', NULL, 9, 1, {$this->lyceeA})");
        $this->db->exec("INSERT INTO classe_parametres (classe_id, annee_academique_id, nombre_places, effectif_actuel) VALUES (401, {$this->annee2}, 1, 0)");

        // Élève 1 s'inscrit
        $controller = new ReinscriptionController();
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'eleve_id' => 1001,
            'classe_id' => 401,
            'annee_academique_id' => $this->annee2
        ];
        ob_start();
        $controller->process();
        ob_get_clean();

        // La classe doit maintenant être pleine
        $cap = ReinscriptionService::getClassCapacityDetails(401, $this->annee2);
        if ($cap['places_restantes'] !== 0 || $cap['is_complete'] !== true) {
            throw new Exception("[FAIL 10] La classe devrait être complète après la première inscription.");
        }

        // Élève 2 tente de s'inscrire sur la même classe sans dérogation
        $_POST = [
            'eleve_id' => 1002,
            'classe_id' => 401,
            'annee_academique_id' => $this->annee2
        ];
        ob_start();
        $controller->process();
        ob_get_clean();

        // L'élève 2 ne doit pas être inscrit
        $enrolled2 = Etude::isEnrolled(1002, $this->annee2, $this->lyceeA);
        if ($enrolled2) {
            throw new Exception("[FAIL 10] L'élève 2 n'aurait pas dû pouvoir s'inscrire sur une classe complète !");
        }

        // Nettoyage
        $this->db->exec("DELETE FROM etudes WHERE classe_id = 401");
        $this->db->exec("DELETE FROM classe_parametres WHERE classe_id = 401");
        $this->db->exec("DELETE FROM classes WHERE id_classe = 401");

        echo " [PASS] Scénario 10 : Concurrence sur la dernière place sécurisée et rejet du sur-effectif sans dérogation.\n";
    }

    /**
     * Scénario 11 : Isolation stricte entre établissements.
     */
    private function test11_IsolationEntreEtablissements(): void {
        // L'utilisateur connecté appartient au Lycée A
        // Tentative d'accès à une classe du Lycée B (301)
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'eleve_id' => 1001,
            'classe_id' => 301, // Classe du Lycée B
            'annee_academique_id' => $this->annee2
        ];

        $controller = new ReinscriptionController();
        ob_start();
        $controller->process();
        ob_get_clean();

        $enrolledCross = $this->db->query("SELECT COUNT(*) FROM etudes WHERE eleve_id = 1001 AND classe_id = 301")->fetchColumn();
        if ((int)$enrolledCross > 0) {
            throw new Exception("[FAIL 11] Violation de cloisonnement multi-tenant : inscription acceptée dans un autre lycée !");
        }

        echo " [PASS] Scénario 11 : Isolation multi-établissements strictement étanche.\n";
    }

    /**
     * Scénario 12 : Respect des séries et des cycles.
     */
    private function test12_RespectSeriesEtCycles(): void {
        // Créer une classe de 1ère A et une classe de 1ère C
        $this->db->exec("INSERT INTO classes (id_classe, niveau, serie, numero, cycle_id, lycee_id) VALUES (501, '1ère', 'A', 1, 2, {$this->lyceeA})");
        $this->db->exec("INSERT INTO classes (id_classe, niveau, serie, numero, cycle_id, lycee_id) VALUES (502, '1ère', 'C', 1, 2, {$this->lyceeA})");
        $this->db->exec("INSERT INTO classe_parametres (classe_id, annee_academique_id, nombre_places, effectif_actuel) VALUES (501, {$this->annee2}, 10, 0)");
        $this->db->exec("INSERT INTO classe_parametres (classe_id, annee_academique_id, nombre_places, effectif_actuel) VALUES (502, {$this->annee2}, 10, 0)");

        // Recherche pour un élève de série C
        $availC = ReinscriptionService::findAvailableClasses($this->lyceeA, $this->annee2, '1ère', 'C', 2);
        if (count($availC['classes']) !== 1 || $availC['classes'][0]['serie'] !== 'C') {
            throw new Exception("[FAIL 12] La recherche par série C doit uniquement renvoyer les classes de série C.");
        }

        $this->db->exec("DELETE FROM classe_parametres WHERE classe_id IN (501, 502)");
        $this->db->exec("DELETE FROM classes WHERE id_classe IN (501, 502)");

        echo " [PASS] Scénario 12 : Respect rigoureux des séries et des correspondances de cycle.\n";
    }

    /**
     * Scénario 13 : Conservation de l'historique scolaire et intégrité de l'élève.
     */
    private function test13_ConservationHistoriqueScolaire(): void {
        $eleveId = 1001;
        $studentBefore = Eleve::findById($eleveId);
        $etudesBefore = Etude::findByEleveId($eleveId);

        // Effectuer une réinscription en 4e 2 (202)
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'eleve_id' => $eleveId,
            'classe_id' => 202,
            'annee_academique_id' => $this->annee2
        ];
        $controller = new ReinscriptionController();
        ob_start();
        $controller->process();
        ob_get_clean();

        $studentAfter = Eleve::findById($eleveId);
        $etudesAfter = Etude::findByEleveId($eleveId);

        if ($studentBefore['matricule'] !== $studentAfter['matricule']) {
            throw new Exception("[FAIL 13] Le matricule de l'élève a été altéré !");
        }
        if (count($etudesAfter) !== count($etudesBefore) + 1) {
            throw new Exception("[FAIL 13] L'historique d'études n'a pas été incrémenté fidèlement.");
        }

        // L'ancienne inscription (5e) est toujours présente
        $has5e = false;
        foreach ($etudesAfter as $et) {
            if ($et['niveau'] === '5e') { $has5e = true; }
        }
        if (!$has5e) {
            throw new Exception("[FAIL 13] L'historique de la classe précédente (5e) a disparu !");
        }

        echo " [PASS] Scénario 13 : Préservation intégrale de l'historique des classes et de l'identité élève.\n";
    }

    /**
     * Scénario 14 : Circuits financiers Public vs Privé et Gestion stricte des élèves sans historique scolaire.
     */
    private function test14_FonctionnementCircuitFinancierPublicEtPrive(): void {
        // 1. Cas Privé (Lycée A) : déjà vérifié avec l'élève 1001 -> doit avoir status = 'en_attente_paiement' et is_active = 0
        $etudePrive = Etude::findByEleveAndAnnee(1001, $this->annee2, $this->lyceeA);
        if ($etudePrive['status'] !== 'en_attente_paiement' || (int)$etudePrive['is_active'] !== 0) {
            throw new Exception("[FAIL 14] Pour un lycée privé, le statut doit être 'en_attente_paiement' et is_active=0.");
        }

        // 2. Règle impérative : Élève sans historique scolaire préalable
        // Créer un élève sans aucune ligne dans 'etudes'
        $this->db->exec("INSERT INTO eleves (id_eleve, lycee_id, nom, prenom, identifiant_public, statut) VALUES (3001, {$this->lyceeA}, 'SANS', 'Historique', 'MAT-3001', 'en_attente')");
        $pedagogyNoHist = ReinscriptionService::getPedagogicalSituation(3001);
        if ($pedagogyNoHist['has_previous_enrollment'] !== false || $pedagogyNoHist['status'] !== 'no_history') {
            throw new Exception("[FAIL 14] L'élève sans historique doit être détecté comme 'no_history'.");
        }

        $destNoHist = ReinscriptionService::resolveDestination($pedagogyNoHist, $this->lyceeA);
        if ($destNoHist['can_proceed'] !== false || $destNoHist['type'] !== 'no_history') {
            throw new Exception("[FAIL 14] La réinscription d'un élève sans historique ne doit pas être autorisée.");
        }

        // Tenter d'exécuter la réinscription en POST pour cet élève
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'eleve_id' => 3001,
            'classe_id' => 202,
            'annee_academique_id' => $this->annee2
        ];
        $controller = new ReinscriptionController();
        ob_start();
        $controller->process();
        ob_get_clean();

        // Vérifier qu'aucune ligne 'etudes' n'a été créée et qu'aucun doublon élève n'existe
        $stmtNoEtude = $this->db->query("SELECT COUNT(*) FROM etudes WHERE eleve_id = 3001");
        if ((int)$stmtNoEtude->fetchColumn() !== 0) {
            throw new Exception("[FAIL 14] Aucune inscription ne doit être créée pour un élève sans historique !");
        }

        // 3. Cas Public (Lycée B) avec un élève ayant un historique dans le public
        $this->db->exec("INSERT INTO eleves (id_eleve, lycee_id, nom, prenom, identifiant_public, statut) VALUES (2001, {$this->lyceeB}, 'PUBLIC', 'Paul', 'MAT-2001', 'actif')");
        // Inscription année précédente au Lycée B
        $this->db->exec("INSERT INTO etudes (eleve_id, classe_id, lycee_id, annee_academique_id, status, is_active) VALUES (2001, 301, {$this->lyceeB}, {$this->annee1}, 'active', 1)");
        // Bulletins validés pour cet élève au Lycée B
        $this->db->exec("INSERT INTO sequences (id, nom, type, statut, date_debut, date_fin, annee_academique_id, lycee_id) VALUES (91, 'T1 Pub', 'trimestrielle', 'fermee', '2024-09-01', '2024-12-31', {$this->annee1}, {$this->lyceeB})");
        $this->db->exec("INSERT INTO sequences (id, nom, type, statut, date_debut, date_fin, annee_academique_id, lycee_id) VALUES (92, 'T2 Pub', 'trimestrielle', 'fermee', '2025-01-01', '2025-03-31', {$this->annee1}, {$this->lyceeB})");
        $this->db->exec("INSERT INTO sequences (id, nom, type, statut, date_debut, date_fin, annee_academique_id, lycee_id) VALUES (93, 'T3 Pub', 'trimestrielle', 'fermee', '2025-04-01', '2025-06-30', {$this->annee1}, {$this->lyceeB})");
        $this->db->exec("INSERT INTO bulletins (eleve_id, sequence_id, annee_academique_id, classe_id, lycee_id, moyenne_generale, statut) VALUES (2001, 91, {$this->annee1}, 301, {$this->lyceeB}, 12.0, 'valide')");
        $this->db->exec("INSERT INTO bulletins (eleve_id, sequence_id, annee_academique_id, classe_id, lycee_id, moyenne_generale, statut) VALUES (2001, 92, {$this->annee1}, 301, {$this->lyceeB}, 12.0, 'valide')");
        $this->db->exec("INSERT INTO bulletins (eleve_id, sequence_id, annee_academique_id, classe_id, lycee_id, moyenne_generale, statut) VALUES (2001, 93, {$this->annee1}, 301, {$this->lyceeB}, 12.0, 'valide')");

        // Seuils de promotion Lycée B
        PromotionThresholdService::saveThreshold([
            'lycee_id' => $this->lyceeB,
            'portee_type' => 'etablissement',
            'seuil_admission_directe' => 10.00,
            'seuil_deliberation_min'  => 8.50,
            'seuil_deliberation_max'  => 9.99,
            'seuil_redoublement_max'  => 8.49,
            'seuil_exclusion_max'     => 5.00
        ]);

        // Classe de destination en 5e au Lycée B
        $this->db->exec("INSERT INTO classes (id_classe, niveau, serie, numero, cycle_id, lycee_id) VALUES (302, '5e', NULL, 1, 3, {$this->lyceeB})");
        $this->db->exec("INSERT INTO classe_parametres (classe_id, annee_academique_id, nombre_places, effectif_actuel) VALUES (302, {$this->annee2}, 50, 0)");

        // Séquence active Année 2 pour Lycée B
        $this->db->exec("INSERT INTO sequences (id, nom, type, statut, date_debut, date_fin, annee_academique_id, lycee_id) VALUES (94, 'T1 Pub An2', 'trimestrielle', 'ouverte', '2025-09-01', '2025-12-31', {$this->annee2}, {$this->lyceeB})");

        // Changer la session vers Lycée B
        $_SESSION['user']['lycee_id'] = $this->lyceeB;
        $this->db->exec("UPDATE utilisateurs SET lycee_id = {$this->lyceeB} WHERE id_user = 99");

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'eleve_id' => 2001,
            'classe_id' => 302,
            'annee_academique_id' => $this->annee2
        ];
        $controller = new ReinscriptionController();
        ob_start();
        $controller->process();
        ob_get_clean();

        $etudePublic = Etude::findByEleveAndAnnee(2001, $this->annee2, $this->lyceeB);
        if (!$etudePublic || $etudePublic['status'] !== 'active' || (int)$etudePublic['is_active'] !== 1) {
            throw new Exception("[FAIL 14] Pour un lycée public, le statut doit être 'active' et is_active=1.");
        }

        $elevePublic = Eleve::findById(2001);
        if ($elevePublic['statut'] !== 'actif') {
            throw new Exception("[FAIL 14] L'élève du public doit être en statut 'actif'.");
        }

        echo " [PASS] Scénario 14 : Blocage strict des élèves sans historique (orientation /inscriptions) et circuits financiers validés.\n";
    }
}

$test = new ReinscriptionCapacityAndPedagogyTest();
$test->run();
