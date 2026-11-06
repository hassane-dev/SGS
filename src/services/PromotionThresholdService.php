<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/ParamPromotionSeuil.php';
require_once __DIR__ . '/../models/Cycle.php';
require_once __DIR__ . '/../models/Classe.php';
require_once __DIR__ . '/../models/Serie.php';
require_once __DIR__ . '/../core/Auth.php';

/**
 * Single Source of Truth (SSoT) for promotion, deliberation and repeat thresholds.
 *
 * Implements strict 4-tier hierarchical resolution:
 * Level + Series -> Level -> Cycle -> School Default.
 */
class PromotionThresholdService {

    /**
     * Resolves the effective promotion thresholds for a given academic target.
     *
     * @param int $lyceeId
     * @param int $cycleId
     * @param string $niveau
     * @param string|null $serie
     * @return array
     */
    public static function resolveThresholds(int $lyceeId, int $cycleId, string $niveau, ?string $serie = null): array {
        if ($lyceeId <= 0 || $cycleId <= 0 || empty(trim($niveau))) {
            return [
                'status' => 'not_configured',
                'message' => _("Paramètres d'identification académique invalides pour la résolution des seuils.")
            ];
        }

        $cleanNiveau = trim($niveau);
        $cleanSerie = !empty($serie) ? trim($serie) : null;

        $db = Database::getInstance();

        // Query candidates strictly belonging to this establishment and cycle
        $sql = "
            SELECT * FROM param_promotion_seuils
            WHERE lycee_id = :lycee_id
              AND (
                  (portee_type = 'niveau_serie' AND cycle_id = :cycle_id AND niveau = :niveau AND serie = :serie)
                  OR
                  (portee_type = 'niveau'        AND cycle_id = :cycle_id AND niveau = :niveau)
                  OR
                  (portee_type = 'cycle'         AND cycle_id = :cycle_id)
                  OR
                  (portee_type = 'etablissement' AND cycle_id IS NULL AND niveau IS NULL AND serie IS NULL)
              )
            ORDER BY
                CASE portee_type
                    WHEN 'niveau_serie'  THEN 1
                    WHEN 'niveau'        THEN 2
                    WHEN 'cycle'         THEN 3
                    WHEN 'etablissement' THEN 4
                END ASC
            LIMIT 1
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            'lycee_id' => $lyceeId,
            'cycle_id' => $cycleId,
            'niveau' => $cleanNiveau,
            'serie' => $cleanSerie
        ]);
        $rule = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$rule) {
            return [
                'status' => 'not_configured',
                'rule_id' => null,
                'portee_type' => null,
                'inherited' => false,
                'message' => sprintf(
                    _("Aucun seuil de promotion n'est configuré pour l'établissement #%d (Cycle #%d, Niveau '%s'%s)."),
                    $lyceeId,
                    $cycleId,
                    $cleanNiveau,
                    $cleanSerie ? " Série '{$cleanSerie}'" : ""
                )
            ];
        }

        $resolvedPortee = $rule['portee_type'];
        $isExactRequestedScope = false;
        if (!empty($cleanSerie)) {
            $isExactRequestedScope = ($resolvedPortee === 'niveau_serie');
        } else {
            $isExactRequestedScope = ($resolvedPortee === 'niveau');
        }

        return [
            'status' => 'configured',
            'rule_id' => (int)$rule['id'],
            'portee_type' => $resolvedPortee,
            'inherited' => !$isExactRequestedScope,
            'seuil_admission_directe' => (float)$rule['seuil_admission_directe'],
            'seuil_deliberation_min'  => (float)$rule['seuil_deliberation_min'],
            'seuil_deliberation_max'  => (float)$rule['seuil_deliberation_max'],
            'seuil_redoublement_max'  => (float)$rule['seuil_redoublement_max'],
            'seuil_exclusion_max'     => ($rule['seuil_exclusion_max'] !== null && $rule['seuil_exclusion_max'] !== '')
                ? (float)$rule['seuil_exclusion_max']
                : null,
            'rule' => $rule
        ];
    }

    /**
     * Validates business and mathematical consistency of a threshold definition.
     *
     * @param array $data
     * @return array List of error strings, empty if valid.
     */
    public static function validateThresholds(array $data): array {
        $errors = [];

        // 1. Mandatory numeric fields
        $fields = [
            'seuil_admission_directe' => _("Le seuil d'admission directe"),
            'seuil_deliberation_min'  => _("La borne minimale de délibération"),
            'seuil_deliberation_max'  => _("La borne maximale de délibération"),
            'seuil_redoublement_max'  => _("Le seuil maximal de redoublement")
        ];

        foreach ($fields as $field => $label) {
            if (!isset($data[$field]) || $data[$field] === '' || !is_numeric($data[$field])) {
                $errors[] = sprintf(_("%s est obligatoire et doit être une valeur numérique."), $label);
            }
        }

        if (!empty($errors)) {
            return $errors;
        }

        $admission = (float)$data['seuil_admission_directe'];
        $delibMin = (float)$data['seuil_deliberation_min'];
        $delibMax = (float)$data['seuil_deliberation_max'];
        $redoublMax = (float)$data['seuil_redoublement_max'];

        $hasExclusion = isset($data['seuil_exclusion_max']) && $data['seuil_exclusion_max'] !== '' && $data['seuil_exclusion_max'] !== null;
        $exclusionMax = $hasExclusion ? (float)$data['seuil_exclusion_max'] : null;

        // V1: Scale 0.00 to 20.00
        $allValues = [$admission, $delibMin, $delibMax, $redoublMax];
        if ($hasExclusion) {
            $allValues[] = $exclusionMax;
        }

        foreach ($allValues as $val) {
            if ($val < 0.0 || $val > 20.0) {
                $errors[] = _("Tous les seuils doivent être compris entre 0,00 et 20,00.");
                break;
            }
        }

        // V2: Precision (max 2 decimals)
        foreach (['seuil_admission_directe', 'seuil_deliberation_min', 'seuil_deliberation_max', 'seuil_redoublement_max'] as $f) {
            $valStr = (string)$data[$f];
            if (strpos($valStr, '.') !== false) {
                $parts = explode('.', $valStr);
                if (strlen($parts[1]) > 2) {
                    $errors[] = _("La précision décimale maximale autorisée est de deux chiffres après la virgule.");
                    break;
                }
            }
        }

        // V3: Deliberation bounds
        if ($delibMin >= $delibMax) {
            $errors[] = _("La borne minimale de délibération doit être strictement inférieure à la borne maximale.");
        }

        // V4: Repeat vs Deliberation continuity
        // Redoublement max must be strictly less than Deliberation min
        if ($redoublMax >= $delibMin) {
            $errors[] = _("Le seuil de redoublement doit être strictement inférieur à la borne minimale de délibération.");
        } elseif (round($delibMin - $redoublMax, 2) > 0.01) {
            $errors[] = sprintf(
                _("Discontinuité détectée entre le seuil de redoublement (%.2f) et la délibération (%.2f). L'écart ne doit pas dépasser 0,01."),
                $redoublMax, $delibMin
            );
        }

        // V5: Deliberation vs Direct Admission continuity
        if ($delibMax >= $admission) {
            $errors[] = _("La borne maximale de délibération doit être strictement inférieure au seuil d'admission directe.");
        } elseif (round($admission - $delibMax, 2) > 0.01) {
            $errors[] = sprintf(
                _("Discontinuité détectée entre la borne de délibération (%.2f) et l'admission directe (%.2f). L'écart ne doit pas dépasser 0,01."),
                $delibMax, $admission
            );
        }

        // V6: Optional exclusion bound
        if ($hasExclusion) {
            if ($exclusionMax < 0.0) {
                $errors[] = _("Le seuil d'exclusion ne peut pas être négatif.");
            }
            if ($exclusionMax >= $redoublMax) {
                $errors[] = _("Le seuil d'exclusion doit être strictement inférieur au seuil de redoublement.");
            }
        }

        // V7 - V10: Scope integrity
        $porteeType = $data['portee_type'] ?? '';
        $validPortees = ['etablissement', 'cycle', 'niveau', 'niveau_serie'];
        if (!in_array($porteeType, $validPortees, true)) {
            $errors[] = _("Type de portée invalide. Valeurs autorisées : etablissement, cycle, niveau, niveau_serie.");
            return $errors;
        }

        $lyceeId = (int)($data['lycee_id'] ?? 0);
        $cycleId = !empty($data['cycle_id']) ? (int)$data['cycle_id'] : null;
        $niveau = !empty($data['niveau']) ? trim($data['niveau']) : null;
        $serie = !empty($data['serie']) ? trim($data['serie']) : null;

        if ($porteeType === 'etablissement') {
            if ($cycleId !== null || $niveau !== null || $serie !== null) {
                $errors[] = _("Une règle de portée 'établissement' ne doit spécifier ni cycle, ni niveau, ni série.");
            }
        } else {
            // cycle, niveau, niveau_serie require a valid cycle for this lycee
            if (!$cycleId) {
                $errors[] = _("Un cycle d'enseignement est obligatoire pour cette portée.");
                return $errors;
            }

            $cycle = Cycle::findById($cycleId);
            if (!$cycle) {
                $errors[] = _("Le cycle sélectionné est introuvable.");
                return $errors;
            }

            if (!empty($cycle['lycee_id']) && (int)$cycle['lycee_id'] !== $lyceeId) {
                $errors[] = _("Le cycle sélectionné n'appartient pas à cet établissement.");
            }

            // Check utilise_series
            $utiliseSeries = (isset($cycle['utilise_series']) && $cycle['utilise_series'] !== '') ? (int)$cycle['utilise_series'] : null;

            if ($porteeType === 'cycle') {
                if ($niveau !== null || $serie !== null) {
                    $errors[] = _("Une règle de portée 'cycle' ne doit spécifier ni niveau, ni série.");
                }
            } elseif ($porteeType === 'niveau') {
                if (empty($niveau)) {
                    $errors[] = _("Le niveau scolaire est obligatoire pour une portée de niveau.");
                }
                if ($serie !== null) {
                    $errors[] = _("Une règle de portée 'niveau' ne doit pas spécifier de série.");
                }
                // Verify level is within cycle boundaries
                if (!empty($niveau) && !empty($cycle['niveau_debut']) && !empty($cycle['niveau_fin'])) {
                    $startOrder = Classe::resolveLevelOrder($cycle['niveau_debut']);
                    $endOrder = Classe::resolveLevelOrder($cycle['niveau_fin']);
                    $targetOrder = Classe::resolveLevelOrder($niveau);

                    if ($targetOrder === null || $startOrder === null || $endOrder === null || $targetOrder < $startOrder || $targetOrder > $endOrder) {
                        $errors[] = sprintf(
                            _("Le niveau '%s' n'appartient pas aux bornes du cycle '%s' ('%s' à '%s')."),
                            $niveau, $cycle['nom_cycle'], $cycle['niveau_debut'], $cycle['niveau_fin']
                        );
                    }
                }
            } elseif ($porteeType === 'niveau_serie') {
                if (empty($niveau)) {
                    $errors[] = _("Le niveau scolaire est obligatoire pour une portée niveau + série.");
                }
                if (empty($serie)) {
                    $errors[] = _("La série est obligatoire pour une portée niveau + série.");
                }

                if ($utiliseSeries === null) {
                    $errors[] = _("La configuration des séries de ce cycle est indéterminée (utilise_series non défini). Veuillez configurer le cycle dans les paramètres avant d'ajouter une règle par série.");
                } elseif ($utiliseSeries === 0) {
                    $errors[] = sprintf(_("Le cycle '%s' n'utilise pas de séries. Les règles par série y sont interdites."), $cycle['nom_cycle']);
                }

                // Verify serie validity for the school
                if (!empty($serie)) {
                    $db = Database::getInstance();
                    $stmtS = $db->prepare("SELECT COUNT(*) FROM series WHERE nom_serie = :nom AND (lycee_id = :lycee_id OR lycee_id IS NULL)");
                    $stmtS->execute(['nom' => $serie, 'lycee_id' => $lyceeId]);
                    if ((int)$stmtS->fetchColumn() === 0) {
                        $errors[] = sprintf(_("La série '%s' n'est pas reconnue dans cet établissement."), $serie);
                    }
                }

                // Verify level within cycle
                if (!empty($niveau) && !empty($cycle['niveau_debut']) && !empty($cycle['niveau_fin'])) {
                    $startOrder = Classe::resolveLevelOrder($cycle['niveau_debut']);
                    $endOrder = Classe::resolveLevelOrder($cycle['niveau_fin']);
                    $targetOrder = Classe::resolveLevelOrder($niveau);

                    if ($targetOrder === null || $startOrder === null || $endOrder === null || $targetOrder < $startOrder || $targetOrder > $endOrder) {
                        $errors[] = sprintf(
                            _("Le niveau '%s' n'appartient pas aux bornes du cycle '%s' ('%s' à '%s')."),
                            $niveau, $cycle['nom_cycle'], $cycle['niveau_debut'], $cycle['niveau_fin']
                        );
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Validates and saves a threshold rule.
     *
     * @param array $data
     * @return int Created or updated ID
     * @throws InvalidArgumentException
     */
    public static function saveThreshold(array $data): int {
        $errors = self::validateThresholds($data);
        if (!empty($errors)) {
            throw new InvalidArgumentException(implode(' ', $errors));
        }

        // Prevent duplicate scope if creating or changing scope
        $existing = ParamPromotionSeuil::findExactScope(
            (int)$data['lycee_id'],
            $data['portee_type'],
            !empty($data['cycle_id']) ? (int)$data['cycle_id'] : null,
            !empty($data['niveau']) ? trim($data['niveau']) : null,
            !empty($data['serie']) ? trim($data['serie']) : null
        );

        if ($existing && (empty($data['id']) || (int)$existing['id'] !== (int)$data['id'])) {
            throw new InvalidArgumentException(_("Une règle de seuils identique existe déjà pour ce périmètre exact."));
        }

        return ParamPromotionSeuil::save($data);
    }

    /**
     * Deletes a threshold rule.
     */
    public static function deleteThreshold(int $id, int $lyceeId): bool {
        return ParamPromotionSeuil::delete($id, $lyceeId);
    }

    /**
     * Returns an overview matrix of configured and inherited rules for a school.
     */
    public static function getEffectiveMatrix(int $lyceeId): array {
        $cycles = Cycle::findByLycee($lyceeId);
        $rules = ParamPromotionSeuil::findByLycee($lyceeId);

        $generalRule = null;
        $cycleRules = [];       // [cycle_id => rule]
        $niveauRules = [];      // [cycle_id_niveau => rule]
        $niveauSerieRules = []; // [cycle_id_niveau_serie => rule]

        foreach ($rules as $r) {
            $pType = $r['portee_type'];
            if ($pType === 'etablissement') {
                $generalRule = $r;
            } elseif ($pType === 'cycle') {
                $cycleRules[(int)$r['cycle_id']] = $r;
            } elseif ($pType === 'niveau') {
                $key = $r['cycle_id'] . '_' . $r['niveau'];
                $niveauRules[$key] = $r;
            } elseif ($pType === 'niveau_serie') {
                $key = $r['cycle_id'] . '_' . $r['niveau'] . '_' . $r['serie'];
                $niveauSerieRules[$key] = $r;
            }
        }

        return [
            'general_rule' => $generalRule,
            'cycle_rules' => $cycleRules,
            'niveau_rules' => $niveauRules,
            'niveau_serie_rules' => $niveauSerieRules,
            'cycles' => $cycles,
            'rules' => $rules
        ];
    }
}
