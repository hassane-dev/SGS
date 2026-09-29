<?php

require_once __DIR__ . '/../config/database.php';

class SchoolInitializationService {

    /**
     * Atomically initializes a new school (Lycee) and all its required baseline dependencies.
     *
     * @param array $data Input dataset gathered from Setup steps or Admin school creation form.
     * @return array Array containing created 'lycee_id', 'annee_id', 'user_id', 'exercice_id', 'caisse_id', and 'sequences'
     * @throws InvalidArgumentException|Exception
     */
    public static function initializeSchool(array $data): array {
        $db = Database::getInstance();

        // --- 1. PRE-VALIDATIONS ---
        $nomLycee = trim($data['nom_lycee'] ?? '');
        if (empty($nomLycee)) {
            throw new InvalidArgumentException(_("Le nom de l'établissement est obligatoire."));
        }

        $typeLycee = strtolower(trim($data['type_lycee'] ?? 'prive'));
        $allowedTypes = ['prive', 'public', 'parapublic'];
        if (!in_array($typeLycee, $allowedTypes, true)) {
            throw new InvalidArgumentException(_("Le type d'établissement invalide. Valeurs autorisées: public, prive, parapublic."));
        }

        // Admin User Validation (if creating a local admin)
        $adminEmail = trim($data['admin_email'] ?? '');
        $adminPass = $data['admin_pass'] ?? '';
        $adminNom = trim($data['admin_nom'] ?? '');
        $adminPrenom = trim($data['admin_prenom'] ?? '');

        if (!empty($adminEmail)) {
            if (empty($adminPass) || strlen($adminPass) < 4) {
                throw new InvalidArgumentException(_("Le mot de passe de l'administrateur doit contenir au moins 4 caractères."));
            }
        }

        // Academic Year Validation
        $anneeLibelle = trim($data['annee_libelle'] ?? '');
        $anneeDateDebut = trim($data['annee_date_debut'] ?? '');
        $anneeDateFin = trim($data['annee_date_fin'] ?? '');

        if (empty($anneeLibelle) || empty($anneeDateDebut) || empty($anneeDateFin)) {
            throw new InvalidArgumentException(_("Les informations de l'année académique (libellé, date début, date fin) sont obligatoires."));
        }

        if (strtotime($anneeDateFin) <= strtotime($anneeDateDebut)) {
            throw new InvalidArgumentException(_("La date de fin d'année académique doit être strictement postérieure à la date de début."));
        }

        // General Parameters Validation
        $sequenceAnnuelle = ucfirst(strtolower(trim($data['sequence_annuelle'] ?? 'Trimestrielle')));
        if (!in_array($sequenceAnnuelle, ['Trimestrielle', 'Semestrielle'], true)) {
            throw new InvalidArgumentException(_("L'organisation académique doit être 'Trimestrielle' ou 'Semestrielle'."));
        }

        // Academic Periods Validation (3 for Trimestrielle, 2 for Semestrielle)
        $periods = $data['periods'] ?? [];
        $expectedCount = ($sequenceAnnuelle === 'Trimestrielle') ? 3 : 2;

        if (count($periods) !== $expectedCount) {
            throw new InvalidArgumentException(sprintf(
                _("Vous devez configurer exactement %d périodes académiques pour une organisation %s."),
                $expectedCount,
                $sequenceAnnuelle
            ));
        }

        // Validate chronology and boundaries of periods
        $yearStart = strtotime($anneeDateDebut);
        $yearEnd = strtotime($anneeDateFin);
        $prevEnd = $yearStart - 86400; // Day before year start

        foreach ($periods as $idx => $period) {
            $pName = trim($period['nom'] ?? '');
            $pStartStr = trim($period['date_debut'] ?? '');
            $pEndStr = trim($period['date_fin'] ?? '');

            if (empty($pName) || empty($pStartStr) || empty($pEndStr)) {
                throw new InvalidArgumentException(sprintf(_("Informations incomplètes pour la période #%d."), $idx + 1));
            }

            $pStart = strtotime($pStartStr);
            $pEnd = strtotime($pEndStr);

            if ($pStart >= $pEnd) {
                throw new InvalidArgumentException(sprintf(_("La date de début de %s doit être antérieure à sa date de fin."), $pName));
            }

            if ($pStart < $yearStart || $pEnd > $yearEnd) {
                throw new InvalidArgumentException(sprintf(_("Les dates de %s doivent être incluses dans l'année académique (%s au %s)."), $pName, $anneeDateDebut, $anneeDateFin));
            }

            if ($pStart <= $prevEnd) {
                throw new InvalidArgumentException(sprintf(_("La période %s chevauche la période précédente."), $pName));
            }

            $prevEnd = $pEnd;
        }

        // --- 2. ATOMIC TRANSACTIONAL EXECUTION ---
        $db->beginTransaction();

        try {
            // STEP A: Ensure default roles & permissions are seeded safely if missing
            $stmtRolesCount = $db->query("SELECT COUNT(*) FROM roles");
            if (!$stmtRolesCount || (int)$stmtRolesCount->fetchColumn() === 0) {
                $seedSql = @file_get_contents(__DIR__ . '/../../db/seeds.sql');
                if ($seedSql) {
                    if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
                        $seedSql = str_replace('INSERT INTO', 'INSERT OR IGNORE INTO', $seedSql);
                        $seedSql = preg_replace('/ON DUPLICATE KEY UPDATE[^;]+;/i', ';', $seedSql);
                    }
                    $db->exec($seedSql);
                }
            }

            // STEP B: Insert param_lycee
            $stmtLycee = $db->prepare("
                INSERT INTO param_lycee (
                    nom_lycee, type_lycee, sigle, tel, email, ville, quartier, ruelle, boite_postale, arrete, arrondissement, devise, logo, boutique
                ) VALUES (
                    :nom_lycee, :type_lycee, :sigle, :tel, :email, :ville, :quartier, :ruelle, :boite_postale, :arrete, :arrondissement, :devise, :logo, 0
                )
            ");
            $stmtLycee->execute([
                'nom_lycee' => $nomLycee,
                'type_lycee' => $typeLycee,
                'sigle' => !empty($data['sigle']) ? trim($data['sigle']) : null,
                'tel' => !empty($data['tel']) ? trim($data['tel']) : null,
                'email' => !empty($data['email']) ? trim($data['email']) : null,
                'ville' => !empty($data['ville']) ? trim($data['ville']) : null,
                'quartier' => !empty($data['quartier']) ? trim($data['quartier']) : null,
                'ruelle' => !empty($data['ruelle']) ? trim($data['ruelle']) : null,
                'boite_postale' => !empty($data['boite_postale']) ? trim($data['boite_postale']) : null,
                'arrete' => !empty($data['arrete']) ? trim($data['arrete']) : null,
                'arrondissement' => !empty($data['arrondissement']) ? trim($data['arrondissement']) : null,
                'devise' => !empty($data['devise_pays']) ? trim($data['devise_pays']) : 'FCFA',
                'logo' => !empty($data['logo']) ? trim($data['logo']) : null
            ]);
            $lyceeId = (int)$db->lastInsertId();

            // STEP C: Insert param_general
            $stmtParamGen = $db->prepare("
                INSERT INTO param_general (
                    lycee_id, devise_pays, monnaie, nb_langue, langue_1, langue_2, sequence_annuelle, mode_cycle
                ) VALUES (
                    :lycee_id, :devise_pays, :monnaie, :nb_langue, :langue_1, :langue_2, :sequence_annuelle, :mode_cycle
                )
            ");
            $stmtParamGen->execute([
                'lycee_id' => $lyceeId,
                'devise_pays' => !empty($data['devise_pays']) ? trim($data['devise_pays']) : 'FCFA',
                'monnaie' => !empty($data['monnaie']) ? trim($data['monnaie']) : 'FCFA',
                'nb_langue' => !empty($data['nb_langue']) ? (int)$data['nb_langue'] : 1,
                'langue_1' => !empty($data['langue_1']) ? trim($data['langue_1']) : 'Francais',
                'langue_2' => !empty($data['langue_2']) ? trim($data['langue_2']) : null,
                'sequence_annuelle' => $sequenceAnnuelle,
                'mode_cycle' => !empty($data['mode_cycle']) ? trim($data['mode_cycle']) : 'separe_ceg_lycee'
            ]);

            // STEP D: Create Local Admin User (if credentials supplied)
            $userId = null;
            if (!empty($adminEmail)) {
                $stmtCheckEmail = $db->prepare("SELECT id_user FROM utilisateurs WHERE email = :email");
                $stmtCheckEmail->execute(['email' => $adminEmail]);
                if ($stmtCheckEmail->fetchColumn()) {
                    throw new InvalidArgumentException(_("Un utilisateur avec cet email existe déjà dans le système."));
                }

                $hashPassword = password_hash($adminPass, PASSWORD_DEFAULT);
                $stmtUser = $db->prepare("
                    INSERT INTO utilisateurs (
                        nom, prenom, email, mot_de_passe, role_id, lycee_id, actif
                    ) VALUES (
                        :nom, :prenom, :email, :mot_de_passe, 3, :lycee_id, 1
                    )
                ");
                $stmtUser->execute([
                    'nom' => !empty($adminNom) ? $adminNom : 'Admin',
                    'prenom' => !empty($adminPrenom) ? $adminPrenom : 'Local',
                    'email' => $adminEmail,
                    'mot_de_passe' => $hashPassword,
                    'lycee_id' => $lyceeId
                ]);
                $userId = (int)$db->lastInsertId();
            }

            // STEP E: Find or Create Academic Year (by libelle)
            $stmtFindAnnee = $db->prepare("SELECT id FROM annees_academiques WHERE libelle = :libelle");
            $stmtFindAnnee->execute(['libelle' => $anneeLibelle]);
            $anneeId = $stmtFindAnnee->fetchColumn();

            if (!$anneeId) {
                $stmtAnnee = $db->prepare("
                    INSERT INTO annees_academiques (
                        libelle, date_debut, date_fin, est_active, cloturee
                    ) VALUES (
                        :libelle, :date_debut, :date_fin, 1, 0
                    )
                ");
                $stmtAnnee->execute([
                    'libelle' => $anneeLibelle,
                    'date_debut' => $anneeDateDebut,
                    'date_fin' => $anneeDateFin
                ]);
                $anneeId = (int)$db->lastInsertId();
            } else {
                $anneeId = (int)$anneeId;
                $db->prepare("UPDATE annees_academiques SET est_active = 1 WHERE id = :id")->execute(['id' => $anneeId]);
            }

            // STEP F: Seed Default Evaluation Types in param_type_evaluation
            $defaultEvalTypes = [
                ['code' => 'devoir', 'libelle' => 'Devoir', 'bareme' => 20.00, 'nombre' => 2, 'ordre' => 1],
                ['code' => 'interrogation', 'libelle' => 'Interrogation', 'bareme' => 20.00, 'nombre' => 3, 'ordre' => 2],
                ['code' => 'composition', 'libelle' => 'Composition', 'bareme' => 20.00, 'nombre' => 1, 'ordre' => 3]
            ];

            $stmtTypeEval = $db->prepare("
                INSERT INTO param_type_evaluation (
                    lycee_id, code, libelle, bareme_defaut, nombre_evaluation, actif, ordre_affichage
                ) VALUES (
                    :lycee_id, :code, :libelle, :bareme, :nombre, 1, :ordre
                )
            ");

            foreach ($defaultEvalTypes as $tEval) {
                $stmtTypeEval->execute([
                    'lycee_id' => $lyceeId,
                    'code' => $tEval['code'],
                    'libelle' => $tEval['libelle'],
                    'bareme' => $tEval['bareme'],
                    'nombre' => $tEval['nombre'],
                    'ordre' => $tEval['ordre']
                ]);
            }

            // STEP G: Create Active Financial Exercise
            $stmtExercice = $db->prepare("
                INSERT INTO exercices_financiers (
                    lycee_id, libelle, date_debut, date_fin, est_actif, cloture, type_exercice
                ) VALUES (
                    :lycee_id, :libelle, :date_debut, :date_fin, 1, 0, 'normal'
                )
            ");
            $stmtExercice->execute([
                'lycee_id' => $lyceeId,
                'libelle' => 'Exercice ' . $anneeLibelle,
                'date_debut' => $anneeDateDebut,
                'date_fin' => $anneeDateFin
            ]);
            $exerciceId = (int)$db->lastInsertId();

            // STEP H: Create Default Cash Account (Caisse Principale)
            $stmtCompte = $db->prepare("
                INSERT INTO comptes_financiers (
                    lycee_id, nom_compte, type_compte, solde_courant, devise, responsable_id, statut
                ) VALUES (
                    :lycee_id, 'Caisse Principale', 'caisse', 0.00, :devise, :responsable_id, 'actif'
                )
            ");
            $stmtCompte->execute([
                'lycee_id' => $lyceeId,
                'devise' => !empty($data['devise_pays']) ? trim($data['devise_pays']) : 'FCFA',
                'responsable_id' => $userId
            ]);
            $caisseId = (int)$db->lastInsertId();

            // STEP I: Create Academic Periods (Sequences): Period 1 is 'ouverte', subsequent periods are 'planifiee'
            $seqType = (strtolower($sequenceAnnuelle) === 'semestrielle') ? 'semestrielle' : 'trimestrielle';
            $createdSequenceIds = [];

            $stmtSeq = $db->prepare("
                INSERT INTO sequences (
                    lycee_id, annee_academique_id, nom, type, date_debut, date_fin, statut
                ) VALUES (
                    :lycee_id, :annee_academique_id, :nom, :type, :date_debut, :date_fin, :statut
                )
            ");

            foreach ($periods as $idx => $period) {
                $status = ($idx === 0) ? 'ouverte' : 'planifiee';
                $stmtSeq->execute([
                    'lycee_id' => $lyceeId,
                    'annee_academique_id' => $anneeId,
                    'nom' => trim($period['nom']),
                    'type' => $seqType,
                    'date_debut' => trim($period['date_debut']),
                    'date_fin' => trim($period['date_fin']),
                    'statut' => $status
                ]);
                $createdSequenceIds[] = (int)$db->lastInsertId();
            }

            // COMMIT ALL OPERATIONS
            $db->commit();

            return [
                'lycee_id' => $lyceeId,
                'annee_id' => $anneeId,
                'user_id' => $userId,
                'exercice_id' => $exerciceId,
                'caisse_id' => $caisseId,
                'sequence_ids' => $createdSequenceIds
            ];

        } catch (Exception $e) {
            $db->rollBack();
            error_log("SchoolInitializationService failure: " . $e->getMessage());
            throw $e;
        }
    }
}
?>