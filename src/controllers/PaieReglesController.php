<?php

require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/PaieRegleCalcul.php';
require_once __DIR__ . '/../models/PaieBaremeTranche.php';
require_once __DIR__ . '/../models/TypeContrat.php';
require_once __DIR__ . '/../services/PaieRuleRepository.php';

class PaieReglesController {

    public function index() {
        Auth::requirePermission('paie', 'config');
        $lyceeId = Auth::getLyceeId() ?: 1;
        PaieRuleRepository::seedDefaultRulesIfNeeded();
        $rules = PaieRegleCalcul::findAll();

        foreach ($rules as &$r) {
            $r['tiers'] = PaieBaremeTranche::findByRegleId((int)$r['id']);
            $r['is_used'] = PaieRegleCalcul::isRuleUsedInBulletins((int)$r['id']);
        }

        include __DIR__ . '/../views/paie/regles/index.php';
    }

    public function create() {
        Auth::requirePermission('paie', 'config');
        $lyceeId = Auth::getLyceeId() ?: 1;
        $typesContrat = TypeContrat::findAll($lyceeId);

        include __DIR__ . '/../views/paie/regles/create.php';
    }

    public function store() {
        Auth::requirePermission('paie', 'config');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /paie/regles');
            exit();
        }

        try {
            $codeRegle = strtoupper(trim($_POST['code_regle'] ?? ''));
            $libelle = trim($_POST['libelle'] ?? '');
            $categorie = $_POST['categorie'] ?? 'cotisation_salariale';
            $modeCalcul = $_POST['mode_calcul'] ?? 'pourcentage';
            $paysCode = strtoupper(trim($_POST['pays_code'] ?? 'RCA'));
            $baseCalculType = $_POST['base_calcul_type'] ?? 'brut_total';

            if (empty($codeRegle) || empty($libelle)) {
                throw new InvalidArgumentException(_("Le code et le libellé de la règle sont obligatoires."));
            }

            $ruleData = [
                'juridiction_code' => $paysCode,
                'pays_code' => $paysCode,
                'code_regle' => $codeRegle,
                'libelle' => $libelle,
                'categorie' => $categorie,
                'mode_calcul' => $modeCalcul,
                'base_calcul_type' => $baseCalculType,
                'taux_par_defaut' => (float)($_POST['taux_par_defaut'] ?? 0.0),
                'montant_fixe_salarial' => (float)($_POST['montant_fixe_salarial'] ?? 0.0),
                'taux_patronal' => (float)($_POST['taux_patronal'] ?? 0.0),
                'montant_fixe_patronal' => (float)($_POST['montant_fixe_patronal'] ?? 0.0),
                'seuil_minimum' => $_POST['seuil_minimum'] ?? null,
                'plafond_maximum' => $_POST['plafond_maximum'] ?? null,
                'abattement_forfaitaire' => (float)($_POST['abattement_forfaitaire'] ?? 0.0),
                'abattement_pourcentage' => (float)($_POST['abattement_pourcentage'] ?? 0.0),
                'ordre_application' => (int)($_POST['ordre_application'] ?? 100),
                'type_contrat_id' => !empty($_POST['type_contrat_id']) ? (int)$_POST['type_contrat_id'] : null,
                'est_systeme' => 0,
                'actif' => 1,
                'date_debut_validite' => !empty($_POST['date_debut_validite']) ? $_POST['date_debut_validite'] : date('Y-01-01'),
                'date_fin_validite' => !empty($_POST['date_fin_validite']) ? $_POST['date_fin_validite'] : null
            ];

            $regleId = PaieRegleCalcul::create($ruleData);

            // Handle progressive brackets if mode is bareme_progressif
            if ($modeCalcul === 'bareme_progressif' && !empty($_POST['tranches'])) {
                $trancheNum = 1;
                foreach ($_POST['tranches'] as $t) {
                    if (isset($t['limite_inferieure']) && $t['limite_inferieure'] !== '') {
                        PaieBaremeTranche::create([
                            'regle_id' => $regleId,
                            'tranche_numero' => $trancheNum++,
                            'limite_inferieure' => (float)$t['limite_inferieure'],
                            'limite_superieure' => ($t['limite_superieure'] !== '') ? (float)$t['limite_superieure'] : null,
                            'taux' => (float)($t['taux'] ?? 0.0),
                            'montant_fixe' => (float)($t['montant_fixe'] ?? 0.0)
                        ]);
                    }
                }
            }

            $_SESSION['success_message'] = _("Nouvelle règle de paie configurée avec succès.");
            header('Location: /paie/regles');
            exit();
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
            header('Location: /paie/regles/create');
            exit();
        }
    }

    public function edit($id = null) {
        Auth::requirePermission('paie', 'config');
        $id = (int)($id ?: ($_GET['id'] ?? 0));
        $rule = PaieRegleCalcul::findById($id);

        if (!$rule) {
            $_SESSION['error_message'] = _("Règle de paie introuvable.");
            header('Location: /paie/regles');
            exit();
        }

        $lyceeId = Auth::getLyceeId() ?: 1;
        $typesContrat = TypeContrat::findAll($lyceeId);
        $tranches = PaieBaremeTranche::findByRegleId($id);
        $isUsed = PaieRegleCalcul::isRuleUsedInBulletins($id);

        include __DIR__ . '/../views/paie/regles/edit.php';
    }

    public function update($id = null) {
        Auth::requirePermission('paie', 'config');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /paie/regles');
            exit();
        }

        $id = (int)($id ?: ($_POST['id'] ?? 0));
        $existingRule = PaieRegleCalcul::findById($id);

        if (!$existingRule) {
            $_SESSION['error_message'] = _("Règle de paie introuvable.");
            header('Location: /paie/regles');
            exit();
        }

        try {
            $isUsed = PaieRegleCalcul::isRuleUsedInBulletins($id);
            $createNewVersion = !empty($_POST['create_new_version']) || ($isUsed && !empty($_POST['date_debut_validite']) && $_POST['date_debut_validite'] !== $existingRule['date_debut_validite']);

            if ($createNewVersion) {
                // Non-retroactivity guarantee: close existing version's end date and create a new version record
                $newStartDate = $_POST['date_debut_validite'];

                if ($newStartDate === $existingRule['date_debut_validite']) {
                    throw new InvalidArgumentException(sprintf(
                        _("Pour créer une nouvelle version de la règle '%s', vous devez sélectionner une nouvelle date d'effet (date de début de validité) différente de l'existante (%s)."),
                        $existingRule['code_regle'],
                        $existingRule['date_debut_validite']
                    ));
                }

                $oldEndDate = date('Y-m-d', strtotime($newStartDate . ' -1 day'));

                // Close current version
                PaieRegleCalcul::update($id, array_merge($existingRule, [
                    'date_fin_validite' => $oldEndDate
                ]));

                // Create new version
                $ruleData = [
                    'juridiction_code' => $existingRule['pays_code'],
                    'pays_code' => $existingRule['pays_code'],
                    'code_regle' => $existingRule['code_regle'],
                    'libelle' => trim($_POST['libelle'] ?? $existingRule['libelle']),
                    'categorie' => $_POST['categorie'] ?? $existingRule['categorie'],
                    'mode_calcul' => $_POST['mode_calcul'] ?? $existingRule['mode_calcul'],
                    'base_calcul_type' => $_POST['base_calcul_type'] ?? $existingRule['base_calcul_type'],
                    'taux_par_defaut' => (float)($_POST['taux_par_defaut'] ?? 0.0),
                    'montant_fixe_salarial' => (float)($_POST['montant_fixe_salarial'] ?? 0.0),
                    'taux_patronal' => (float)($_POST['taux_patronal'] ?? 0.0),
                    'montant_fixe_patronal' => (float)($_POST['montant_fixe_patronal'] ?? 0.0),
                    'seuil_minimum' => $_POST['seuil_minimum'] ?? null,
                    'plafond_maximum' => $_POST['plafond_maximum'] ?? null,
                    'abattement_forfaitaire' => (float)($_POST['abattement_forfaitaire'] ?? 0.0),
                    'abattement_pourcentage' => (float)($_POST['abattement_pourcentage'] ?? 0.0),
                    'ordre_application' => (int)($_POST['ordre_application'] ?? 100),
                    'type_contrat_id' => !empty($_POST['type_contrat_id']) ? (int)$_POST['type_contrat_id'] : null,
                    'est_systeme' => 0,
                    'actif' => 1,
                    'date_debut_validite' => $newStartDate,
                    'date_fin_validite' => !empty($_POST['date_fin_validite']) ? $_POST['date_fin_validite'] : null
                ];

                $newId = PaieRegleCalcul::create($ruleData);

                if ($_POST['mode_calcul'] === 'bareme_progressif' && !empty($_POST['tranches'])) {
                    $trancheNum = 1;
                    foreach ($_POST['tranches'] as $t) {
                        if (isset($t['limite_inferieure']) && $t['limite_inferieure'] !== '') {
                            PaieBaremeTranche::create([
                                'regle_id' => $newId,
                                'tranche_numero' => $trancheNum++,
                                'limite_inferieure' => (float)$t['limite_inferieure'],
                                'limite_superieure' => ($t['limite_superieure'] !== '') ? (float)$t['limite_superieure'] : null,
                                'taux' => (float)($t['taux'] ?? 0.0),
                                'montant_fixe' => (float)($t['montant_fixe'] ?? 0.0)
                            ]);
                        }
                    }
                }

                $_SESSION['success_message'] = _("Nouvelle version de la règle créée avec succès (version historique préservée).");
            } else {
                // Update in place
                $ruleData = [
                    'libelle' => trim($_POST['libelle'] ?? $existingRule['libelle']),
                    'categorie' => $_POST['categorie'] ?? $existingRule['categorie'],
                    'mode_calcul' => $_POST['mode_calcul'] ?? $existingRule['mode_calcul'],
                    'base_calcul_type' => $_POST['base_calcul_type'] ?? $existingRule['base_calcul_type'],
                    'taux_par_defaut' => (float)($_POST['taux_par_defaut'] ?? 0.0),
                    'montant_fixe_salarial' => (float)($_POST['montant_fixe_salarial'] ?? 0.0),
                    'taux_patronal' => (float)($_POST['taux_patronal'] ?? 0.0),
                    'montant_fixe_patronal' => (float)($_POST['montant_fixe_patronal'] ?? 0.0),
                    'seuil_minimum' => $_POST['seuil_minimum'] ?? null,
                    'plafond_maximum' => $_POST['plafond_maximum'] ?? null,
                    'abattement_forfaitaire' => (float)($_POST['abattement_forfaitaire'] ?? 0.0),
                    'abattement_pourcentage' => (float)($_POST['abattement_pourcentage'] ?? 0.0),
                    'ordre_application' => (int)($_POST['ordre_application'] ?? 100),
                    'type_contrat_id' => !empty($_POST['type_contrat_id']) ? (int)$_POST['type_contrat_id'] : null,
                    'actif' => isset($_POST['actif']) ? (int)$_POST['actif'] : $existingRule['actif'],
                    'date_debut_validite' => !empty($_POST['date_debut_validite']) ? $_POST['date_debut_validite'] : $existingRule['date_debut_validite'],
                    'date_fin_validite' => !empty($_POST['date_fin_validite']) ? $_POST['date_fin_validite'] : null
                ];

                PaieRegleCalcul::update($id, $ruleData);

                if ($_POST['mode_calcul'] === 'bareme_progressif') {
                    PaieBaremeTranche::deleteByRegleId($id);
                    if (!empty($_POST['tranches'])) {
                        $trancheNum = 1;
                        foreach ($_POST['tranches'] as $t) {
                            if (isset($t['limite_inferieure']) && $t['limite_inferieure'] !== '') {
                                PaieBaremeTranche::create([
                                    'regle_id' => $id,
                                    'tranche_numero' => $trancheNum++,
                                    'limite_inferieure' => (float)$t['limite_inferieure'],
                                    'limite_superieure' => ($t['limite_superieure'] !== '') ? (float)$t['limite_superieure'] : null,
                                    'taux' => (float)($t['taux'] ?? 0.0),
                                    'montant_fixe' => (float)($t['montant_fixe'] ?? 0.0)
                                ]);
                            }
                        }
                    }
                }

                $_SESSION['success_message'] = _("Règle de paie mise à jour avec succès.");
            }

            header('Location: /paie/regles');
            exit();
        } catch (Exception $e) {
            $_SESSION['error_message'] = $e->getMessage();
            header("Location: /paie/regles/{$id}/edit");
            exit();
        }
    }

    public function toggle($id = null) {
        Auth::requirePermission('paie', 'config');
        $id = (int)($id ?: ($_POST['id'] ?? 0));

        if ($id > 0) {
            PaieRegleCalcul::toggleActive($id);
            $_SESSION['success_message'] = _("Statut d'activation de la règle modifié.");
        }

        header('Location: /paie/regles');
        exit();
    }
}
