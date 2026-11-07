<?php
// src/views/admissions/student_detail.php
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
                            <li class="breadcrumb-item"><a href="/admissions"><?= _("Décisions d'Admission") ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _("Fiche Individuelle") ?></li>
                        </ul>
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div>
                                <h2 class="mb-0">
                                    <?= htmlspecialchars($decision['prenom'] . ' ' . $decision['nom']) ?>
                                    <span class="badge bg-light-secondary text-dark font-monospace fs-6 ms-2">
                                        <?= htmlspecialchars($decision['matricule'] ?: 'Sans matricule') ?>
                                    </span>
                                </h2>
                                <p class="text-muted mb-0">
                                    <?= sprintf(_("Classe de %s • Cycle %s"), htmlspecialchars($decision['classe_libelle']), htmlspecialchars($decision['niveau'])) ?>
                                </p>
                            </div>
                            <div>
                                <a href="/admissions?lycee_id=<?= $decision['lycee_id'] ?>&annee_id=<?= $decision['annee_id'] ?>&classe_id=<?= $decision['classe_id'] ?>"
                                   class="btn btn-outline-secondary">
                                    <i class="ph-duotone ph-arrow-left me-1"></i><?= _("Retour à la classe") ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Avertissement institutionnel -->
        <div class="alert alert-light-warning border-warning d-flex align-items-center mb-4 shadow-sm" role="alert">
            <i class="ph-duotone ph-info fs-3 text-warning me-3"></i>
            <div>
                <strong><?= _("Information importante :") ?></strong>
                <?= _("Cette fiche présente la proposition automatique de décision basée sur le barème officiel. La décision finale reste de la compétence exclusive du Conseil de Classe.") ?>
            </div>
        </div>

        <div class="row g-4">
            <!-- Bloc Décision Proposée et Justification -->
            <div class="col-lg-5">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="ph-duotone ph-stamp me-2"></i><?= _("Verdict & Décision Proposée") ?></h5>
                    </div>
                    <div class="card-body">
                        <?php
                            $badgeClass = match($decision['decision']) {
                                'admis' => 'bg-success',
                                'deliberation' => 'bg-warning text-dark',
                                'redoublement' => 'bg-danger',
                                'exclu' => 'bg-dark',
                                default => 'bg-secondary'
                            };
                        ?>
                        <div class="text-center p-4 border rounded bg-light mb-4">
                            <span class="text-muted small d-block mb-1"><?= _("Statut calculé") ?></span>
                            <span class="badge <?= $badgeClass ?> fs-4 px-4 py-2 text-uppercase mb-3">
                                <?= htmlspecialchars($decision['decision_libelle']) ?>
                            </span>

                            <div class="mt-2">
                                <span class="text-muted small d-block"><?= _("Moyenne générale annuelle consolidée") ?></span>
                                <h1 class="display-5 fw-bold mb-0 <?= ($decision['moyenne_annuelle'] ?? 0) >= ($decision['thresholds']['seuil_admission_directe'] ?? 10) ? 'text-success' : 'text-danger' ?>">
                                    <?= $decision['moyenne_annuelle'] !== null ? number_format($decision['moyenne_annuelle'], 2) : '—' ?>
                                    <small class="fs-4 text-muted">/ 20</small>
                                </h1>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h6 class="fw-bold mb-2"><i class="ph-duotone ph-article me-1"></i><?= _("Motivation de la décision :") ?></h6>
                            <div class="p-3 border rounded bg-light-info text-dark">
                                <?= htmlspecialchars($decision['justification']) ?>
                            </div>
                        </div>

                        <div>
                            <h6 class="fw-bold mb-2"><i class="ph-duotone ph-sliders me-1"></i><?= _("Barème des seuils appliqué :") ?></h6>
                            <ul class="list-group list-group-flush border rounded">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><?= _("Portée de la règle résolue") ?></span>
                                    <span class="badge bg-primary text-uppercase"><?= htmlspecialchars($decision['thresholds']['portee_type'] ?? '') ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><?= _("Admission directe") ?></span>
                                    <strong class="text-success">&ge; <?= number_format($decision['thresholds']['seuil_admission_directe'] ?? 0, 2) ?></strong>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><?= _("Fourchette de délibération") ?></span>
                                    <strong class="text-warning">[<?= number_format($decision['thresholds']['seuil_deliberation_min'] ?? 0, 2) ?> - <?= number_format($decision['thresholds']['seuil_deliberation_max'] ?? 0, 2) ?>]</strong>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><?= _("Seuil maximal de redoublement") ?></span>
                                    <strong class="text-danger">&le; <?= number_format($decision['thresholds']['seuil_redoublement_max'] ?? 0, 2) ?></strong>
                                </li>
                                <?php if (($decision['thresholds']['seuil_exclusion_max'] ?? null) !== null): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><?= _("Seuil d'exclusion") ?></span>
                                        <strong class="text-dark">&le; <?= number_format($decision['thresholds']['seuil_exclusion_max'], 2) ?></strong>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bloc Détails des Résultats par Matière & Bulletins -->
            <div class="col-lg-7">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="ph-duotone ph-books me-2"></i><?= _("Moyennes Annuelles par Matière") ?></h5>
                        <span class="badge bg-light-primary text-primary">
                            <?= sprintf(_("%d bulletins enregistrés"), $decision['academic_details']['recorded_bulletins_count'] ?? 0) ?>
                        </span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th><?= _("Matière") ?></th>
                                        <th class="text-center"><?= _("Coefficient") ?></th>
                                        <th class="text-center"><?= _("Nombre de notes") ?></th>
                                        <th class="text-end"><?= _("Moyenne Annuelle") ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($decision['academic_details']['matieres'])): ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">
                                                <?= _("Aucune moyenne par matière enregistrée dans les bulletins.") ?>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($decision['academic_details']['matieres'] as $mat): ?>
                                            <?php $isElim = ($mat['moyenne_annuelle'] < 5.00); ?>
                                            <tr class="<?= $isElim ? 'table-warning' : '' ?>">
                                                <td>
                                                    <strong><?= htmlspecialchars($mat['nom_matiere']) ?></strong>
                                                    <?php if ($isElim): ?>
                                                        <span class="badge bg-danger ms-1"><?= _("Note éliminatoire") ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center"><?= number_format($mat['coefficient'], 1) ?></td>
                                                <td class="text-center"><?= $mat['nombre_evaluations'] ?></td>
                                                <td class="text-end fw-bold <?= $mat['moyenne_annuelle'] >= 10 ? 'text-success' : 'text-danger' ?>">
                                                    <?= number_format($mat['moyenne_annuelle'], 2) ?> <small class="text-muted">/20</small>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0 bg-light">
                    <div class="card-body">
                        <h6 class="fw-bold mb-2"><i class="ph-duotone ph-check-square me-1"></i><?= _("Complétude du dossier :") ?></h6>
                        <?php if ($decision['is_complete']): ?>
                            <p class="text-success mb-0 small">
                                <i class="ph-duotone ph-check-circle me-1"></i>
                                <?= _("Toutes les séquences obligatoires de l'année sont clôturées et les bulletins correspondants sont validés.") ?>
                            </p>
                        <?php else: ?>
                            <p class="text-warning mb-0 small">
                                <i class="ph-duotone ph-warning me-1"></i>
                                <?= sprintf(_("Dossier incomplet : %s"), htmlspecialchars($decision['incomplete_reason'] ?? '')) ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
