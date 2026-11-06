<?php
// src/views/promotion_thresholds/index.php
require_once __DIR__ . '/../layouts/header_able.php';
require_once __DIR__ . '/../layouts/sidebar_able.php';

$generalRule = $matrix['general_rule'] ?? null;
$cycleRules = $matrix['cycle_rules'] ?? [];
$niveauRules = $matrix['niveau_rules'] ?? [];
$niveauSerieRules = $matrix['niveau_serie_rules'] ?? [];
?>

<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <ul class="breadcrumb mb-2">
                            <li class="breadcrumb-item"><a href="/"><i class="ph-duotone ph-house"></i></a></li>
                            <li class="breadcrumb-item"><?= _('Administration & Paramètres') ?></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _('Seuils de Promotion') ?></li>
                        </ul>
                        <h2 class="mb-0"><?= _('Paramétrage des Seuils de Promotion') ?></h2>
                        <p class="text-muted"><?= _('Configuration hiérarchique des critères d\'admission directe, délibération, redoublement et exclusion.') ?></p>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- Alerts Section -->
        <?php if (!empty($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="ph-duotone ph-check-circle me-2"></i><?= htmlspecialchars($_SESSION['success_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="ph-duotone ph-warning me-2"></i><?= htmlspecialchars($_SESSION['error_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <!-- Config Alert Banner if no General Rule -->
        <?php if (!$generalRule): ?>
            <div class="alert alert-warning border-start border-warning border-4 shadow-sm mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <i class="ph-duotone ph-warning-circle text-warning fs-1"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="alert-heading mb-1"><?= _("Seuils généraux non configurés") ?></h5>
                        <p class="mb-0"><?= _("Votre établissement n'a pas encore défini sa règle générale de seuils par défaut. La résolution des seuils pour un cycle ou un niveau non personnalisé renverra l'état 'non configuré' tant qu'une règle générale n'est pas enregistrée.") ?></p>
                    </div>
                    <?php if ($canManage): ?>
                        <div class="ms-3">
                            <button type="button" class="btn btn-warning" onclick="openThresholdModal('etablissement', null, null, null)">
                                <i class="ph-duotone ph-plus-circle me-1"></i><?= _("Définir la règle générale") ?>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- SECTION 1: Règle Générale de l'Établissement -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0"><i class="ph-duotone ph-buildings me-2 text-primary"></i><?= _("1. Règle Générale de l'Établissement (Défaut)") ?></h5>
                    <small class="text-muted"><?= _("S'applique automatiquement à tous les cycles et niveaux en l'absence de règle spécifique.") ?></small>
                </div>
                <?php if ($canManage): ?>
                    <div>
                        <?php if ($generalRule): ?>
                            <button class="btn btn-sm btn-outline-primary" onclick='editThresholdModal(<?= json_encode($generalRule) ?>)'>
                                <i class="ph-duotone ph-pencil me-1"></i><?= _("Modifier la règle générale") ?>
                            </button>
                            <form action="/settings/promotion-thresholds/delete" method="POST" class="d-inline" onsubmit="return confirm('<?= _("Êtes-vous sûr de vouloir supprimer la règle générale ?") ?>');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= $generalRule['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger ms-1">
                                    <i class="ph-duotone ph-trash"></i>
                                </button>
                            </form>
                        <?php else: ?>
                            <button class="btn btn-sm btn-primary" onclick="openThresholdModal('etablissement', null, null, null)">
                                <i class="ph-duotone ph-plus me-1"></i><?= _("Ajouter la règle générale") ?>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if ($generalRule): ?>
                    <div class="row g-3 text-center">
                        <div class="col-md-3">
                            <div class="p-3 border rounded bg-light-success">
                                <span class="text-muted d-block small"><?= _("Admission directe") ?></span>
                                <h3 class="text-success mb-0">&ge; <?= number_format($generalRule['seuil_admission_directe'], 2) ?> <small class="fs-6">/20</small></h3>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 border rounded bg-light-warning">
                                <span class="text-muted d-block small"><?= _("Zone de délibération") ?></span>
                                <h3 class="text-warning mb-0"><?= number_format($generalRule['seuil_deliberation_min'], 2) ?> &ndash; <?= number_format($generalRule['seuil_deliberation_max'], 2) ?></h3>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 border rounded bg-light-danger">
                                <span class="text-muted d-block small"><?= _("Proposition de redoublement") ?></span>
                                <h3 class="text-danger mb-0">&le; <?= number_format($generalRule['seuil_redoublement_max'], 2) ?></h3>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3 border rounded bg-light">
                                <span class="text-muted d-block small"><?= _("Seuil d'exclusion") ?></span>
                                <h3 class="text-dark mb-0">
                                    <?= ($generalRule['seuil_exclusion_max'] !== null) ? '&le; ' . number_format($generalRule['seuil_exclusion_max'], 2) : '<span class="badge bg-secondary">' . _("Désactivé") . '</span>' ?>
                                </h3>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0 fst-italic text-center py-3"><?= _("Aucune règle générale définie.") ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- SECTION 2: Règles Spécifiques par Cycle, Niveau et Série -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0"><i class="ph-duotone ph-tree-structure me-2 text-primary"></i><?= _("2. Personnalisation Hiérarchique (Cycles, Niveaux & Séries)") ?></h5>
                    <small class="text-muted"><?= _("Ordre d'application : Série > Niveau > Cycle > Établissement.") ?></small>
                </div>
            </div>
            <div class="card-body p-0">
                <?php if (empty($cycles)): ?>
                    <div class="p-4 text-center text-muted">
                        <?= _("Aucun cycle n'a été créé pour cet établissement.") ?>
                    </div>
                <?php else: ?>
                    <div class="accordion accordion-flush" id="accordionCycles">
                        <?php foreach ($cycles as $cyIdx => $cycle):
                            $cId = (int)$cycle['id_cycle'];
                            $cRule = $cycleRules[$cId] ?? null;
                            $niveaux = $cycleNiveaux[$cId] ?? [];
                            $utiliseSeries = (isset($cycle['utilise_series']) && $cycle['utilise_series'] !== '') ? (int)$cycle['utilise_series'] : null;
                        ?>
                            <div class="accordion-item border-bottom">
                                <h2 class="accordion-header" id="headingCycle<?= $cId ?>">
                                    <button class="accordion-button <?= $cyIdx === 0 ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCycle<?= $cId ?>" aria-expanded="<?= $cyIdx === 0 ? 'true' : 'false' ?>" aria-controls="collapseCycle<?= $cId ?>">
                                        <div class="d-flex align-items-center justify-content-between w-100 me-3">
                                            <div>
                                                <strong class="fs-6 me-2"><?= htmlspecialchars($cycle['nom_cycle']) ?></strong>
                                                <?php if (!empty($cycle['niveau_debut']) && !empty($cycle['niveau_fin'])): ?>
                                                    <span class="badge bg-light-primary text-primary"><?= htmlspecialchars($cycle['niveau_debut']) ?> &rarr; <?= htmlspecialchars($cycle['niveau_fin']) ?></span>
                                                <?php endif; ?>
                                                <?php if ($utiliseSeries === 1): ?>
                                                    <span class="badge bg-light-info text-info ms-1"><?= _("Avec séries") ?></span>
                                                <?php elseif ($utiliseSeries === 0): ?>
                                                    <span class="badge bg-light-secondary text-secondary ms-1"><?= _("Sans séries") ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-light-warning text-warning ms-1"><?= _("Séries non configurées") ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <?php if ($cRule): ?>
                                                    <span class="badge bg-success me-2"><?= _("Règle spécifique au cycle") ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-light-secondary text-secondary me-2"><?= _("Hérite de l'établissement") ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </button>
                                </h2>
                                <div id="collapseCycle<?= $cId ?>" class="accordion-collapse collapse <?= $cyIdx === 0 ? 'show' : '' ?>" aria-labelledby="headingCycle<?= $cId ?>" data-bs-parent="#accordionCycles">
                                    <div class="accordion-body bg-light-50">
                                        <!-- Cycle Level Rule Summary -->
                                        <div class="card border mb-3">
                                            <div class="card-body p-3 d-flex justify-content-between align-items-center">
                                                <div>
                                                    <strong><?= _("Règle applicable au Cycle complet :") ?></strong>
                                                    <?php if ($cRule): ?>
                                                        <span class="ms-2">Admis &ge; <strong><?= number_format($cRule['seuil_admission_directe'], 2) ?></strong> | Délib. [<strong><?= number_format($cRule['seuil_deliberation_min'], 2) ?> - <?= number_format($cRule['seuil_deliberation_max'], 2) ?></strong>] | Redoubl. &le; <strong><?= number_format($cRule['seuil_redoublement_max'], 2) ?></strong></span>
                                                    <?php elseif ($generalRule): ?>
                                                        <span class="ms-2 text-muted fst-italic"><?= _("Hérite des seuils généraux de l'établissement") ?> (Admis &ge; <?= number_format($generalRule['seuil_admission_directe'], 2) ?>)</span>
                                                    <?php else: ?>
                                                        <span class="ms-2 text-danger fst-italic"><?= _("Non configuré") ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($canManage): ?>
                                                    <div>
                                                        <?php if ($cRule): ?>
                                                            <button class="btn btn-sm btn-outline-primary" onclick='editThresholdModal(<?= json_encode($cRule) ?>)'>
                                                                <i class="ph-duotone ph-pencil me-1"></i><?= _("Modifier règle cycle") ?>
                                                            </button>
                                                            <form action="/settings/promotion-thresholds/delete" method="POST" class="d-inline" onsubmit="return confirm('<?= _("Supprimer cette règle spécifique de cycle ? Le cycle héritera alors de l\'établissement.") ?>');">
                                                                <?= csrf_field() ?>
                                                                <input type="hidden" name="id" value="<?= $cRule['id'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-outline-danger ms-1">
                                                                    <i class="ph-duotone ph-trash"></i>
                                                                </button>
                                                            </form>
                                                        <?php else: ?>
                                                            <button class="btn btn-sm btn-outline-secondary" onclick="openThresholdModal('cycle', <?= $cId ?>, null, null)">
                                                                <i class="ph-duotone ph-plus me-1"></i><?= _("Définir une règle pour ce cycle") ?>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- Levels & Series Table -->
                                        <div class="table-responsive bg-white border rounded">
                                            <table class="table table-hover align-middle mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th><?= _("Niveau / Série") ?></th>
                                                        <th><?= _("Portée effective") ?></th>
                                                        <th><?= _("Admission directe") ?></th>
                                                        <th><?= _("Délibération") ?></th>
                                                        <th><?= _("Redoublement") ?></th>
                                                        <th><?= _("Exclusion") ?></th>
                                                        <th class="text-end"><?= _("Actions") ?></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (empty($niveaux)): ?>
                                                        <tr>
                                                            <td colspan="7" class="text-center text-muted py-3">
                                                                <?= _("Aucun niveau associé à ce cycle. Configurez les bornes du cycle dans Administration > Cycles.") ?>
                                                            </td>
                                                        </tr>
                                                    <?php else: ?>
                                                        <?php foreach ($niveaux as $niv):
                                                            $nivKey = $cId . '_' . $niv;
                                                            $nRule = $niveauRules[$nivKey] ?? null;

                                                            // Determine effective level rule
                                                            $effRule = $nRule ?? $cRule ?? $generalRule;
                                                            $origin = $nRule ? 'niveau' : ($cRule ? 'cycle' : ($generalRule ? 'etablissement' : 'none'));
                                                        ?>
                                                            <tr class="<?= $nRule ? 'table-primary-light' : '' ?>">
                                                                <td>
                                                                    <strong class="fs-6"><?= htmlspecialchars($niv) ?></strong>
                                                                </td>
                                                                <td>
                                                                    <?php if ($origin === 'niveau'): ?>
                                                                        <span class="badge bg-primary"><?= _("Spécifique au niveau") ?></span>
                                                                    <?php elseif ($origin === 'cycle'): ?>
                                                                        <span class="badge bg-light-info text-info"><?= _("Hérité du cycle") ?></span>
                                                                    <?php elseif ($origin === 'etablissement'): ?>
                                                                        <span class="badge bg-light-secondary text-secondary"><?= _("Hérité de l'école") ?></span>
                                                                    <?php else: ?>
                                                                        <span class="badge bg-danger"><?= _("Non configuré") ?></span>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td>
                                                                    <?php if ($effRule): ?>
                                                                        <strong class="text-success">&ge; <?= number_format($effRule['seuil_admission_directe'], 2) ?></strong>
                                                                    <?php else: ?>—<?php endif; ?>
                                                                </td>
                                                                <td>
                                                                    <?php if ($effRule): ?>
                                                                        <span class="text-warning fw-semibold"><?= number_format($effRule['seuil_deliberation_min'], 2) ?> &ndash; <?= number_format($effRule['seuil_deliberation_max'], 2) ?></span>
                                                                    <?php else: ?>—<?php endif; ?>
                                                                </td>
                                                                <td>
                                                                    <?php if ($effRule): ?>
                                                                        <span class="text-danger">&le; <?= number_format($effRule['seuil_redoublement_max'], 2) ?></span>
                                                                    <?php else: ?>—<?php endif; ?>
                                                                </td>
                                                                <td>
                                                                    <?php if ($effRule && $effRule['seuil_exclusion_max'] !== null): ?>
                                                                        <span class="text-dark">&le; <?= number_format($effRule['seuil_exclusion_max'], 2) ?></span>
                                                                    <?php else: ?>
                                                                        <span class="text-muted small"><?= _("Non activé") ?></span>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td class="text-end">
                                                                    <?php if ($canManage): ?>
                                                                        <?php if ($nRule): ?>
                                                                            <button class="btn btn-sm btn-icon btn-outline-primary" title="<?= _("Modifier") ?>" onclick='editThresholdModal(<?= json_encode($nRule) ?>)'>
                                                                                <i class="ph-duotone ph-pencil"></i>
                                                                            </button>
                                                                            <form action="/settings/promotion-thresholds/delete" method="POST" class="d-inline" onsubmit="return confirm('<?= _("Supprimer cette règle spécifique de niveau ?") ?>');">
                                                                                <?= csrf_field() ?>
                                                                                <input type="hidden" name="id" value="<?= $nRule['id'] ?>">
                                                                                <button type="submit" class="btn btn-sm btn-icon btn-outline-danger ms-1" title="<?= _("Supprimer") ?>">
                                                                                    <i class="ph-duotone ph-trash"></i>
                                                                                </button>
                                                                            </form>
                                                                        <?php else: ?>
                                                                            <button class="btn btn-sm btn-outline-primary" onclick="openThresholdModal('niveau', <?= $cId ?>, '<?= htmlspecialchars($niv) ?>', null)">
                                                                                <i class="ph-duotone ph-plus me-1"></i><?= _("Personnaliser") ?>
                                                                            </button>
                                                                        <?php endif; ?>

                                                                        <?php if ($utiliseSeries === 1 && !empty($series)): ?>
                                                                            <button class="btn btn-sm btn-outline-info ms-1" onclick="openThresholdModal('niveau_serie', <?= $cId ?>, '<?= htmlspecialchars($niv) ?>', '')">
                                                                                <i class="ph-duotone ph-git-branch me-1"></i><?= _("+ Série") ?>
                                                                            </button>
                                                                        <?php endif; ?>
                                                                    <?php endif; ?>
                                                                </td>
                                                            </tr>

                                                            <!-- Series specific rows if any -->
                                                            <?php if ($utiliseSeries === 1): ?>
                                                                <?php foreach ($series as $ser):
                                                                    $nsKey = $cId . '_' . $niv . '_' . $ser['nom_serie'];
                                                                    $nsRule = $niveauSerieRules[$nsKey] ?? null;
                                                                    if ($nsRule):
                                                                ?>
                                                                    <tr class="table-info-light">
                                                                        <td class="ps-4">
                                                                            <i class="ph-duotone ph-arrow-elbow-down-right me-1 text-info"></i>
                                                                            <span class="badge bg-info"><?= _("Série") ?> <?= htmlspecialchars($ser['nom_serie']) ?></span>
                                                                        </td>
                                                                        <td>
                                                                            <span class="badge bg-dark"><?= _("Spécifique Série") ?></span>
                                                                        </td>
                                                                        <td><strong class="text-success">&ge; <?= number_format($nsRule['seuil_admission_directe'], 2) ?></strong></td>
                                                                        <td><span class="text-warning fw-semibold"><?= number_format($nsRule['seuil_deliberation_min'], 2) ?> &ndash; <?= number_format($nsRule['seuil_deliberation_max'], 2) ?></span></td>
                                                                        <td><span class="text-danger">&le; <?= number_format($nsRule['seuil_redoublement_max'], 2) ?></span></td>
                                                                        <td>
                                                                            <?= ($nsRule['seuil_exclusion_max'] !== null) ? '&le; ' . number_format($nsRule['seuil_exclusion_max'], 2) : '<span class="text-muted small">' . _("Non activé") . '</span>' ?>
                                                                        </td>
                                                                        <td class="text-end">
                                                                            <?php if ($canManage): ?>
                                                                                <button class="btn btn-sm btn-icon btn-outline-primary" title="<?= _("Modifier") ?>" onclick='editThresholdModal(<?= json_encode($nsRule) ?>)'>
                                                                                    <i class="ph-duotone ph-pencil"></i>
                                                                                </button>
                                                                                <form action="/settings/promotion-thresholds/delete" method="POST" class="d-inline" onsubmit="return confirm('<?= _("Supprimer cette règle spécifique de série ?") ?>');">
                                                                                    <?= csrf_field() ?>
                                                                                    <input type="hidden" name="id" value="<?= $nsRule['id'] ?>">
                                                                                    <button type="submit" class="btn btn-sm btn-icon btn-outline-danger ms-1" title="<?= _("Supprimer") ?>">
                                                                                        <i class="ph-duotone ph-trash"></i>
                                                                                    </button>
                                                                                </form>
                                                                            <?php endif; ?>
                                                                        </td>
                                                                    </tr>
                                                                <?php endif; endforeach; ?>
                                                            <?php endif; ?>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- SECTION 3: Simulateur / Test de Résolution Directe -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="ph-duotone ph-magnifying-glass me-2 text-primary"></i><?= _("3. Simulateur de Résolution des Seuils") ?></h5>
                <small class="text-muted"><?= _("Vérifiez quelle règle et quels seuils s'appliquent exactement à un élève selon son cycle, niveau et série.") ?></small>
            </div>
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label"><?= _("Cycle") ?></label>
                        <select id="simCycle" class="form-select" onchange="onSimCycleChange()">
                            <option value=""><?= _("-- Sélectionner --") ?></option>
                            <?php foreach ($cycles as $cy): ?>
                                <option value="<?= $cy['id_cycle'] ?>" data-utilise-series="<?= $cy['utilise_series'] ?>"><?= htmlspecialchars($cy['nom_cycle']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><?= _("Niveau") ?></label>
                        <select id="simNiveau" class="form-select">
                            <option value=""><?= _("-- Sélectionner d'abord le cycle --") ?></option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label"><?= _("Série (si applicable)") ?></label>
                        <select id="simSerie" class="form-select" disabled>
                            <option value=""><?= _("-- Aucune série --") ?></option>
                            <?php foreach ($series as $ser): ?>
                                <option value="<?= htmlspecialchars($ser['nom_serie']) ?>"><?= htmlspecialchars($ser['nom_serie']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-primary w-100" onclick="runResolutionSimulation()">
                            <i class="ph-duotone ph-play me-1"></i><?= _("Tester la résolution") ?>
                        </button>
                    </div>
                </div>

                <div id="simResultContainer" class="mt-4" style="display: none;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Formulaire Création / Modification des Seuils -->
<div class="modal fade" id="thresholdModal" tabindex="-1" aria-labelledby="thresholdModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="/settings/promotion-thresholds/store" method="POST" id="thresholdForm">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="modalRuleId" value="">
            <input type="hidden" name="portee_type" id="modalPorteeType" value="">
            <input type="hidden" name="cycle_id" id="modalCycleId" value="">

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="thresholdModalLabel"><?= _("Définir une règle de seuils") ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Scope Context Banner -->
                    <div class="alert alert-info py-2 mb-3" id="modalScopeDesc"></div>

                    <!-- Target Fields for Niveau and Serie -->
                    <div class="row g-3 mb-3" id="modalTargetFields" style="display: none;">
                        <div class="col-md-6" id="modalNiveauContainer">
                            <label class="form-label fw-bold"><?= _("Niveau scolaire") ?></label>
                            <input type="text" name="niveau" id="modalNiveau" class="form-control" readonly>
                        </div>
                        <div class="col-md-6" id="modalSerieContainer" style="display: none;">
                            <label class="form-label fw-bold"><?= _("Série") ?></label>
                            <select name="serie" id="modalSerie" class="form-select">
                                <option value=""><?= _("-- Choisir une série --") ?></option>
                                <?php foreach ($series as $ser): ?>
                                    <option value="<?= htmlspecialchars($ser['nom_serie']) ?>"><?= htmlspecialchars($ser['nom_serie']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Threshold Inputs -->
                    <h6 class="text-uppercase fw-bold text-muted small border-bottom pb-2 mb-3"><?= _("Critères numériques (/20)") ?></h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="seuil_admission_directe" class="form-label fw-bold"><?= _("Admission directe (seuil minimal)") ?> <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" max="20" name="seuil_admission_directe" id="seuil_admission_directe" class="form-control" required placeholder="10.00" oninput="autoAdjustBounds()">
                                <span class="input-group-text">/20</span>
                            </div>
                            <small class="text-muted"><?= _("Moyenne générale requise pour un passage sans délibération.") ?></small>
                        </div>

                        <div class="col-md-6">
                            <label for="seuil_redoublement_max" class="form-label fw-bold"><?= _("Redoublement (seuil maximal)") ?> <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" max="20" name="seuil_redoublement_max" id="seuil_redoublement_max" class="form-control" required placeholder="8.99" oninput="autoAdjustBounds()">
                                <span class="input-group-text">/20</span>
                            </div>
                            <small class="text-muted"><?= _("En dessous de cette note, proposition de redoublement.") ?></small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold"><?= _("Zone de délibération (Bornes Min & Max)") ?> <span class="text-danger">*</span></label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="number" step="0.01" min="0" max="20" name="seuil_deliberation_min" id="seuil_deliberation_min" class="form-control" required placeholder="9.00">
                                </div>
                                <div class="col-6">
                                    <input type="number" step="0.01" min="0" max="20" name="seuil_deliberation_max" id="seuil_deliberation_max" class="form-control" required placeholder="9.99">
                                </div>
                            </div>
                            <small class="text-muted"><?= _("Intermédiaire soumis à l'arbitrage du conseil de classe.") ?></small>
                        </div>

                        <div class="col-md-6">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="activer_exclusion" id="activer_exclusion" value="1" onchange="toggleExclusionInput()">
                                <label class="form-check-label fw-bold" for="activer_exclusion"><?= _("Activer un seuil d'exclusion") ?></label>
                            </div>
                            <div class="input-group" id="exclusionInputContainer" style="display: none;">
                                <input type="number" step="0.01" min="0" max="20" name="seuil_exclusion_max" id="seuil_exclusion_max" class="form-control" placeholder="6.00">
                                <span class="input-group-text">/20</span>
                            </div>
                            <small class="text-muted" id="exclusionHelpText"><?= _("Optionnel. Définit la note en dessous de laquelle l'exclusion est suggérée.") ?></small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal"><?= _("Annuler") ?></button>
                    <button type="submit" class="btn btn-primary"><?= _("Enregistrer la règle") ?></button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
const cycleNiveauxMap = <?= json_encode($cycleNiveaux) ?>;
const cyclesData = <?= json_encode(array_column($cycles, null, 'id_cycle')) ?>;

function openThresholdModal(portee, cycleId, niveau, serie) {
    document.getElementById('thresholdForm').reset();
    document.getElementById('modalRuleId').value = '';
    document.getElementById('modalPorteeType').value = portee;
    document.getElementById('modalCycleId').value = cycleId || '';

    const modalTitle = document.getElementById('thresholdModalLabel');
    const scopeDesc = document.getElementById('modalScopeDesc');
    const targetFields = document.getElementById('modalTargetFields');
    const modalNiveau = document.getElementById('modalNiveau');
    const modalSerieContainer = document.getElementById('modalSerieContainer');
    const modalSerie = document.getElementById('modalSerie');

    targetFields.style.display = 'none';
    modalSerieContainer.style.display = 'none';
    modalSerie.required = false;

    if (portee === 'etablissement') {
        modalTitle.textContent = "<?= _("Règle Générale de l'Établissement") ?>";
        scopeDesc.textContent = "<?= _("Cette règle s'appliquera par défaut à tous les élèves de l'école n'ayant pas de règle spécifique.") ?>";
    } else if (portee === 'cycle') {
        const cName = cyclesData[cycleId] ? cyclesData[cycleId].nom_cycle : '';
        modalTitle.textContent = "<?= _("Règle pour le Cycle : ") ?>" + cName;
        scopeDesc.textContent = "<?= _("Cette règle s'appliquera à tous les niveaux de ce cycle sauf ceux ayant une personnalisation.") ?>";
    } else if (portee === 'niveau') {
        const cName = cyclesData[cycleId] ? cyclesData[cycleId].nom_cycle : '';
        modalTitle.textContent = "<?= _("Personnaliser le Niveau : ") ?>" + niveau;
        scopeDesc.textContent = "<?= _("Cette règle s'appliquera à l'ensemble du niveau ") ?>" + niveau + " (Cycle " + cName + ").";
        targetFields.style.display = 'flex';
        modalNiveau.value = niveau;
    } else if (portee === 'niveau_serie') {
        const cName = cyclesData[cycleId] ? cyclesData[cycleId].nom_cycle : '';
        modalTitle.textContent = "<?= _("Personnaliser Niveau + Série : ") ?>" + niveau;
        scopeDesc.textContent = "<?= _("Cette règle spécifique s'appliquera uniquement aux élèves de ce niveau et de la série choisie.") ?>";
        targetFields.style.display = 'flex';
        modalNiveau.value = niveau;
        modalSerieContainer.style.display = 'block';
        modalSerie.value = serie || '';
        modalSerie.required = true;
    }

    // Default bounds suggestion
    document.getElementById('seuil_admission_directe').value = '10.00';
    document.getElementById('seuil_deliberation_max').value = '9.99';
    document.getElementById('seuil_deliberation_min').value = '9.00';
    document.getElementById('seuil_redoublement_max').value = '8.99';
    document.getElementById('activer_exclusion').checked = false;
    toggleExclusionInput();

    const myModal = new bootstrap.Modal(document.getElementById('thresholdModal'));
    myModal.show();
}

function editThresholdModal(rule) {
    openThresholdModal(rule.portee_type, rule.cycle_id, rule.niveau, rule.serie);
    document.getElementById('modalRuleId').value = rule.id;
    document.getElementById('seuil_admission_directe').value = parseFloat(rule.seuil_admission_directe).toFixed(2);
    document.getElementById('seuil_deliberation_max').value = parseFloat(rule.seuil_deliberation_max).toFixed(2);
    document.getElementById('seuil_deliberation_min').value = parseFloat(rule.seuil_deliberation_min).toFixed(2);
    document.getElementById('seuil_redoublement_max').value = parseFloat(rule.seuil_redoublement_max).toFixed(2);

    if (rule.seuil_exclusion_max !== null && rule.seuil_exclusion_max !== '') {
        document.getElementById('activer_exclusion').checked = true;
        document.getElementById('seuil_exclusion_max').value = parseFloat(rule.seuil_exclusion_max).toFixed(2);
    } else {
        document.getElementById('activer_exclusion').checked = false;
        document.getElementById('seuil_exclusion_max').value = '';
    }
    toggleExclusionInput();
}

function toggleExclusionInput() {
    const isChecked = document.getElementById('activer_exclusion').checked;
    const container = document.getElementById('exclusionInputContainer');
    const input = document.getElementById('seuil_exclusion_max');
    if (isChecked) {
        container.style.display = 'flex';
        input.required = true;
    } else {
        container.style.display = 'none';
        input.required = false;
        input.value = '';
    }
}

function autoAdjustBounds() {
    const adm = parseFloat(document.getElementById('seuil_admission_directe').value);
    if (!isNaN(adm)) {
        document.getElementById('seuil_deliberation_max').value = (adm - 0.01).toFixed(2);
    }
    const red = parseFloat(document.getElementById('seuil_redoublement_max').value);
    if (!isNaN(red)) {
        document.getElementById('seuil_deliberation_min').value = (red + 0.01).toFixed(2);
    }
}

function onSimCycleChange() {
    const cId = document.getElementById('simCycle').value;
    const niveauSelect = document.getElementById('simNiveau');
    const serieSelect = document.getElementById('simSerie');

    niveauSelect.innerHTML = '<option value="">-- Choisir un niveau --</option>';
    serieSelect.value = '';

    if (!cId) {
        serieSelect.disabled = true;
        return;
    }

    const cycleInfo = cyclesData[cId];
    const utiliseSeries = cycleInfo && cycleInfo.utilise_series !== null ? parseInt(cycleInfo.utilise_series) : 0;
    serieSelect.disabled = (utiliseSeries !== 1);

    const niveaux = cycleNiveauxMap[cId] || [];
    niveaux.forEach(n => {
        const opt = document.createElement('option');
        opt.value = n;
        opt.textContent = n;
        niveauSelect.appendChild(opt);
    });
}

function runResolutionSimulation() {
    const cycleId = document.getElementById('simCycle').value;
    const niveau = document.getElementById('simNiveau').value;
    const serie = document.getElementById('simSerie').value;
    const resultBox = document.getElementById('simResultContainer');

    if (!cycleId || !niveau) {
        alert("<?= _('Veuillez sélectionner au moins un cycle et un niveau.') ?>");
        return;
    }

    resultBox.style.display = 'block';
    resultBox.innerHTML = '<div class="spinner-border spinner-border-sm text-primary me-2"></div> Résolution en cours...';

    fetch(`/settings/promotion-thresholds/resolve-preview?cycle_id=${encodeURIComponent(cycleId)}&niveau=${encodeURIComponent(niveau)}&serie=${encodeURIComponent(serie)}`)
        .then(res => res.json())
        .then(data => {
            if (!data.success || data.result.status === 'not_configured') {
                resultBox.innerHTML = `
                    <div class="alert alert-danger mb-0">
                        <i class="ph-duotone ph-x-circle me-2"></i><strong>Non configuré :</strong> ${data.result ? data.result.message : data.message}
                    </div>
                `;
            } else {
                const r = data.result;
                let originBadge = '';
                if (r.portee_type === 'niveau_serie') originBadge = '<span class="badge bg-dark">Spécifique Série</span>';
                else if (r.portee_type === 'niveau') originBadge = '<span class="badge bg-primary">Spécifique Niveau</span>';
                else if (r.portee_type === 'cycle') originBadge = '<span class="badge bg-info">Hérité du Cycle</span>';
                else originBadge = '<span class="badge bg-secondary">Hérité de l\'Établissement</span>';

                resultBox.innerHTML = `
                    <div class="card border border-success bg-light-success">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0 text-success fw-bold"><i class="ph-duotone ph-check-circle me-1"></i> Règle Applicable Résolue avec Succès</h6>
                                <div>${originBadge} ${r.inherited ? '<span class="badge bg-light-warning text-warning ms-1">Héritée</span>' : '<span class="badge bg-light-success text-success ms-1">Définie directement</span>'}</div>
                            </div>
                            <div class="row g-2 text-center mt-1">
                                <div class="col-3">
                                    <small class="text-muted d-block">Admission directe</small>
                                    <strong class="text-success">&ge; ${parseFloat(r.seuil_admission_directe).toFixed(2)}</strong>
                                </div>
                                <div class="col-3">
                                    <small class="text-muted d-block">Délibération</small>
                                    <strong class="text-warning">${parseFloat(r.seuil_deliberation_min).toFixed(2)} &ndash; ${parseFloat(r.seuil_deliberation_max).toFixed(2)}</strong>
                                </div>
                                <div class="col-3">
                                    <small class="text-muted d-block">Redoublement</small>
                                    <strong class="text-danger">&le; ${parseFloat(r.seuil_redoublement_max).toFixed(2)}</strong>
                                </div>
                                <div class="col-3">
                                    <small class="text-muted d-block">Exclusion</small>
                                    <strong>${r.seuil_exclusion_max !== null ? '&le; ' + parseFloat(r.seuil_exclusion_max).toFixed(2) : '<span class="text-muted">Désactivée</span>'}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }
        })
        .catch(err => {
            resultBox.innerHTML = `<div class="alert alert-danger mb-0"><?= _("Erreur lors de la communication avec le serveur.") ?></div>`;
        });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
