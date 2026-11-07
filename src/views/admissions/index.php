<?php
// src/views/admissions/index.php
?>
<div class="pc-container">
    <div class="pc-content">
        <!-- Breadcrumb -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <ul class="breadcrumb mb-2">
                            <li class="breadcrumb-item"><a href="/"><i class="ph-duotone ph-house"></i></a></li>
                            <li class="breadcrumb-item"><?= _('Pédagogie') ?></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _("Décisions d'Admission") ?></li>
                        </ul>
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div>
                                <h2 class="mb-0"><?= _("Calcul & Consultation des Décisions d'Admission") ?></h2>
                                <p class="text-muted mb-0"><?= _("Évaluation annuelle des élèves, arbitrage des délibérations et préparation du conseil de classe.") ?></p>
                            </div>
                            <div>
                                <?php if (Auth::can('view', 'promotion_threshold')): ?>
                                    <a href="/settings/promotion-thresholds" class="btn btn-outline-secondary">
                                        <i class="ph-duotone ph-sliders me-1"></i><?= _("Configuration des Seuils") ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Avertissement institutionnel clair -->
        <div class="alert alert-light-info border-info d-flex align-items-start mb-4 shadow-sm" role="alert">
            <i class="ph-duotone ph-info fs-3 text-info me-3 mt-1"></i>
            <div>
                <h5 class="alert-heading fw-bold mb-1"><?= _("Rappel Institutionnel : Statut des Décisions Proposées") ?></h5>
                <p class="mb-0 text-muted small">
                    <?= _("Les résultats et décisions présentés sur cet écran constituent des <strong>propositions d'admission calculées automatiquement</strong> sur la base des bulletins consolidés et du barème des seuils de l'établissement. Elles ne se substituent en aucun cas à la <strong>validation officielle souveraine du conseil de classe</strong> et n'altèrent pas les inscriptions administratives existantes.") ?>
                </p>
            </div>
        </div>

        <?php if (!empty($calculationError)): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4 shadow-sm" role="alert">
                <i class="ph-duotone ph-warning-circle me-2 fs-5"></i>
                <strong><?= _("Erreur de calcul :") ?></strong> <?= htmlspecialchars($calculationError) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Formulaire de filtrage académique en cascade -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="ph-duotone ph-funnel me-2"></i><?= _("Périmètre Académique d'Analyse") ?></h5>
            </div>
            <div class="card-body">
                <form method="GET" action="/admissions" id="filterForm" class="row g-3 align-items-end">
                    <?php if ($isGlobalAdmin && !empty($lycees)): ?>
                        <div class="col-md-3">
                            <label for="lycee_id" class="form-label fw-bold"><?= _("Établissement") ?> <span class="text-danger">*</span></label>
                            <select name="lycee_id" id="lycee_id" class="form-select" onchange="document.getElementById('filterForm').submit()">
                                <?php foreach ($lycees as $l): ?>
                                    <option value="<?= $l['id'] ?>" <?= (int)$selectedLyceeId === (int)$l['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($l['nom_lycee']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php else: ?>
                        <input type="hidden" name="lycee_id" id="lycee_id" value="<?= htmlspecialchars((string)$selectedLyceeId) ?>">
                    <?php endif; ?>

                    <div class="col-md-3">
                        <label for="annee_id" class="form-label fw-bold"><?= _("Année Académique") ?> <span class="text-danger">*</span></label>
                        <select name="annee_id" id="annee_id" class="form-select" onchange="document.getElementById('filterForm').submit()">
                            <?php foreach ($annees as $a): ?>
                                <option value="<?= $a['id'] ?>" <?= (int)$selectedAnneeId === (int)$a['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($a['libelle']) ?><?= !empty($a['est_active']) ? ' (' . _('En cours') . ')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="cycle_id" class="form-label fw-bold"><?= _("Cycle") ?></label>
                        <select name="cycle_id" id="cycle_id" class="form-select" onchange="filterClasses()">
                            <option value=""><?= _("Tous les cycles") ?></option>
                            <?php foreach ($cycles as $cy): ?>
                                <option value="<?= $cy['id_cycle'] ?>" <?= (int)$selectedCycleId === (int)$cy['id_cycle'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cy['nom_cycle']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="classe_id" class="form-label fw-bold"><?= _("Classe") ?> <span class="text-danger">*</span></label>
                        <select name="classe_id" id="classe_id" class="form-select" required>
                            <option value=""><?= _("-- Sélectionner une classe --") ?></option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?= $c['id_classe'] ?>"
                                        data-cycle-id="<?= $c['cycle_id'] ?>"
                                        data-niveau="<?= htmlspecialchars($c['niveau'] ?? '') ?>"
                                        <?= (int)$selectedClasseId === (int)$c['id_classe'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(Classe::getFormattedName($c)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-1">
                        <button type="submit" class="btn btn-primary w-100" title="<?= _("Lancer le calcul") ?>">
                            <i class="ph-duotone ph-calculator fs-5"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php if (!empty($applicableThreshold)): ?>
            <!-- Barème des seuils résolu pour cette classe -->
            <div class="card shadow-sm border-0 mb-4 bg-light-primary border-start border-primary border-4">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <span class="badge bg-primary text-uppercase me-2"><?= sprintf(_("Portée : %s"), $applicableThreshold['portee_type'] ?? '') ?></span>
                            <?php if (!empty($applicableThreshold['inherited'])): ?>
                                <span class="badge bg-secondary"><?= _("Seuil hérité") ?></span>
                            <?php endif; ?>
                            <strong class="text-dark ms-1"><?= _("Critères applicables à cette classe :") ?></strong>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <span class="small">Admis direct : <strong class="text-success">&ge; <?= number_format($applicableThreshold['seuil_admission_directe'], 2) ?></strong></span>
                            <span class="small">Délibération : <strong class="text-warning">[<?= number_format($applicableThreshold['seuil_deliberation_min'], 2) ?> - <?= number_format($applicableThreshold['seuil_deliberation_max'], 2) ?>]</strong></span>
                            <span class="small">Redoublement : <strong class="text-danger">&le; <?= number_format($applicableThreshold['seuil_redoublement_max'], 2) ?></strong></span>
                            <?php if ($applicableThreshold['seuil_exclusion_max'] !== null): ?>
                                <span class="small">Exclusion : <strong class="text-dark">&le; <?= number_format($applicableThreshold['seuil_exclusion_max'], 2) ?></strong></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($results)): ?>
            <!-- Cartes KPI de Synthèse Statistique de la classe -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-2">
                    <div class="card shadow-sm border-0 text-center mb-0">
                        <div class="card-body py-3">
                            <span class="text-muted small d-block"><?= _("Total Évalués") ?></span>
                            <h3 class="mb-0 fw-bold text-dark"><?= $results['total_eleves'] ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="card shadow-sm border-0 text-center mb-0 bg-light-success">
                        <div class="card-body py-3">
                            <span class="text-success small d-block fw-semibold"><?= _("Admis Directs") ?></span>
                            <h3 class="mb-0 fw-bold text-success"><?= $results['summary']['admis'] ?? 0 ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="card shadow-sm border-0 text-center mb-0 bg-light-warning">
                        <div class="card-body py-3">
                            <span class="text-warning small d-block fw-semibold"><?= _("À Délibérer") ?></span>
                            <h3 class="mb-0 fw-bold text-warning"><?= $results['summary']['deliberation'] ?? 0 ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="card shadow-sm border-0 text-center mb-0 bg-light-danger">
                        <div class="card-body py-3">
                            <span class="text-danger small d-block fw-semibold"><?= _("Redoublement") ?></span>
                            <h3 class="mb-0 fw-bold text-danger"><?= $results['summary']['redoublement'] ?? 0 ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="card shadow-sm border-0 text-center mb-0 bg-light-secondary">
                        <div class="card-body py-3">
                            <span class="text-secondary small d-block fw-semibold"><?= _("Exclus") ?></span>
                            <h3 class="mb-0 fw-bold text-dark"><?= $results['summary']['exclu'] ?? 0 ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="card shadow-sm border-0 text-center mb-0">
                        <div class="card-body py-3">
                            <span class="text-muted small d-block"><?= _("Incomplets") ?></span>
                            <h3 class="mb-0 fw-bold text-muted"><?= $results['summary']['non_determine'] ?? 0 ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tableau des décisions individuelles des élèves -->
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="ph-duotone ph-users-three me-2"></i>
                        <?= sprintf(_("Résultats Individuels de la Classe %s (%d élèves)"), htmlspecialchars($results['classe_libelle']), $results['total_eleves']) ?>
                    </h5>
                    <button class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                        <i class="ph-duotone ph-printer me-1"></i><?= _("Imprimer la synthèse") ?>
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th><?= _("Matricule") ?></th>
                                    <th><?= _("Nom & Prénom") ?></th>
                                    <th class="text-center"><?= _("Moyenne Annuelle") ?></th>
                                    <th class="text-center"><?= _("Seuil Admis") ?></th>
                                    <th class="text-center"><?= _("Décision Proposée") ?></th>
                                    <th><?= _("Justification de la décision") ?></th>
                                    <th class="text-end"><?= _("Actions") ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($results['decisions'])): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            <?= _("Aucun élève trouvé dans cette classe pour l'année sélectionnée.") ?>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($results['decisions'] as $d): ?>
                                        <?php
                                            $badgeClass = match($d['decision']) {
                                                'admis' => 'bg-success',
                                                'deliberation' => 'bg-warning text-dark',
                                                'redoublement' => 'bg-danger',
                                                'exclu' => 'bg-dark',
                                                default => 'bg-secondary'
                                            };
                                        ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-light-secondary text-dark font-monospace">
                                                    <?= htmlspecialchars($d['matricule'] ?: '—') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <strong class="text-dark"><?= htmlspecialchars($d['nom']) ?></strong> <?= htmlspecialchars($d['prenom']) ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($d['moyenne_annuelle'] !== null): ?>
                                                    <span class="fw-bold fs-6 <?= $d['moyenne_annuelle'] >= ($d['thresholds']['seuil_admission_directe'] ?? 10) ? 'text-success' : 'text-danger' ?>">
                                                        <?= number_format($d['moyenne_annuelle'], 2) ?>
                                                    </span>
                                                    <small class="text-muted">/20</small>
                                                <?php else: ?>
                                                    <span class="badge bg-light-warning text-warning"><?= _("Incomplète") ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center small text-muted">
                                                &ge; <?= number_format($d['thresholds']['seuil_admission_directe'] ?? 0, 2) ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge <?= $badgeClass ?> px-3 py-2">
                                                    <?= htmlspecialchars($d['decision_libelle']) ?>
                                                </span>
                                            </td>
                                            <td class="small">
                                                <div class="text-truncate" style="max-width: 420px;" title="<?= htmlspecialchars($d['justification']) ?>">
                                                    <?= htmlspecialchars($d['justification']) ?>
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                <a href="/admissions/student-detail?eleve_id=<?= $d['eleve_id'] ?>&classe_id=<?= $results['classe_id'] ?>&annee_id=<?= $results['annee_id'] ?>&lycee_id=<?= $results['lycee_id'] ?>"
                                                   class="btn btn-sm btn-outline-primary"
                                                   title="<?= _("Consulter la fiche détaillée") ?>">
                                                    <i class="ph-duotone ph-file-text me-1"></i><?= _("Détail") ?>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function filterClasses() {
    const cycleId = document.getElementById('cycle_id').value;
    const classeSelect = document.getElementById('classe_id');
    const options = classeSelect.querySelectorAll('option');

    options.forEach(opt => {
        if (!opt.value) return; // ignore placeholder
        const optCycle = opt.getAttribute('data-cycle-id');
        if (!cycleId || optCycle === cycleId) {
            opt.style.display = '';
        } else {
            opt.style.display = 'none';
        }
    });

    // Si la classe actuellement choisie est maintenant masquée, réinitialiser
    const currentSelected = classeSelect.options[classeSelect.selectedIndex];
    if (currentSelected && currentSelected.style.display === 'none') {
        classeSelect.value = '';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    filterClasses();
});
</script>
