<?php include __DIR__ . '/../layouts/header_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/"><?= _('Tableau de bord') ?></a></li>
                            <li class="breadcrumb-item"><a href="/evaluations/select_class"><?= _('Saisie des Notes') ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _('Formulaire de Saisie') ?></li>
                        </ul>
                    </div>
                    <div class="col-md-12">
                        <div class="page-header-title d-flex justify-content-between align-items-center">
                            <h2 class="mb-0"><?= _('Saisie des Notes') ?> — <?= htmlspecialchars($type_rec['libelle'] ?? ucfirst($type)) ?> <?= $numero_evaluation ?></h2>
                            <a href="/evaluations/select_class" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center">
                                <i class="ph-duotone ph-arrow-left me-1"></i> <?= _('Retour aux classes') ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- Context Banner -->
        <div class="row mb-3">
            <div class="col-md-12">
                <div class="card bg-light-primary border-primary border-start border-4 mb-3">
                    <div class="card-body p-3">
                        <div class="row align-items-center text-center text-md-start">
                            <div class="col-md-3 mb-2 mb-md-0">
                                <span class="text-muted small d-block"><?= _('Classe') ?></span>
                                <span class="fw-bold fs-5 text-dark">
                                    <i class="ph-duotone ph-chalkboard me-1 text-primary"></i>
                                    <?= htmlspecialchars(Classe::getFormattedName($classe)) ?>
                                </span>
                            </div>
                            <div class="col-md-3 mb-2 mb-md-0">
                                <span class="text-muted small d-block"><?= _('Matière') ?></span>
                                <span class="fw-bold fs-5 text-dark">
                                    <i class="ph-duotone ph-book-open me-1 text-info"></i>
                                    <?= htmlspecialchars($matiere['nom_matiere']) ?>
                                </span>
                            </div>
                            <div class="col-md-3 mb-2 mb-md-0">
                                <span class="text-muted small d-block"><?= _('Séquence / Période') ?></span>
                                <span class="fw-bold fs-5 text-dark">
                                    <i class="ph-duotone ph-calendar me-1 text-success"></i>
                                    <?= htmlspecialchars($active_sequence['nom'] ?? '') ?>
                                </span>
                            </div>
                            <div class="col-md-3">
                                <span class="text-muted small d-block"><?= _('Coefficient Matière') ?></span>
                                <span class="badge bg-primary fs-6">
                                    <?= htmlspecialchars($coefficient) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dynamic Type Navigation & Occurrence Selector -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header pb-2 border-bottom-0 d-flex justify-content-between align-items-center">
                        <h5><i class="ph-duotone ph-sliders me-2 text-primary"></i><?= _('Nature et occurrence de l\'évaluation') ?></h5>
                        <span class="badge bg-light-info text-info fs-6">
                            <?= _('Barème') ?> : /<?= number_format((float)$bareme_defaut, 0) ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <?php
                            $baseUrl = "/evaluations/form?classe_id=" . $classe['id_classe'] . "&matiere_id=" . $matiere['id_matiere'] . "&sequence_id=" . $sequence_id;
                            foreach ($allowed_types as $tCode):
                                $tRecObj = ParamTypeEvaluation::findByCode($tCode);
                                $isSel = ($type === $tCode);
                            ?>
                                <a href="<?= $baseUrl ?>&type=<?= $tCode ?>&numero=1"
                                   class="btn <?= $isSel ? 'btn-primary' : 'btn-outline-primary' ?> d-inline-flex align-items-center px-3 py-2">
                                    <i class="ph-duotone ph-notebook me-2 fs-5"></i>
                                    <span class="fw-bold"><?= htmlspecialchars($tRecObj['libelle'] ?? ucfirst($tCode)) ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>

                        <!-- Occurrence Number Tabs (Devoir 1, Devoir 2, Devoir 3...) -->
                        <div class="mt-3 pt-3 border-top d-flex align-items-center gap-2">
                            <span class="text-muted small fw-bold me-2"><?= _('Occurrence / Numéro') ?> :</span>
                            <?php for ($numIter = 1; $numIter <= 5; $numIter++): ?>
                                <a href="<?= $baseUrl ?>&type=<?= $type ?>&numero=<?= $numIter ?>"
                                   class="btn btn-sm <?= $numero_evaluation === $numIter ? 'btn-secondary' : 'btn-outline-secondary' ?>">
                                    N° <?= $numIter ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grade Entry Table Card -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>
                            <i class="ph-duotone ph-users me-2 text-primary"></i>
                            <?= _('Saisie des notes') ?> : <span class="text-primary fw-bold"><?= htmlspecialchars($type_rec['libelle'] ?? ucfirst($type)) ?> N° <?= $numero_evaluation ?></span>
                        </h5>
                        <span class="badge bg-light-info text-info">
                            <?= count($eleves) ?> <?= _('élève(s) inscrit(s)') ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <form action="/evaluations/save" method="POST">
                            <input type="hidden" name="classe_id" value="<?= $classe['id_classe'] ?>">
                            <input type="hidden" name="matiere_id" value="<?= $matiere['id_matiere'] ?>">
                            <input type="hidden" name="sequence_id" value="<?= $sequence_id ?>">
                            <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
                            <input type="hidden" name="numero_evaluation" value="<?= $numero_evaluation ?>">
                            <input type="hidden" name="bareme" value="<?= htmlspecialchars($bareme_defaut) ?>">
                            <input type="hidden" name="coefficient" value="<?= htmlspecialchars($coefficient) ?>">

                            <div class="table-responsive mb-4">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 5%;">#</th>
                                            <th style="width: 35%;"><?= _('Nom & Prénom de l\'Élève') ?></th>
                                            <th style="width: 20%;"><?= _('Note') ?> / <?= number_format((float)$bareme_defaut, 0) ?></th>
                                            <th style="width: 40%;"><?= _('Appréciation Note Individuelle') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($eleves)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center text-muted py-4">
                                                    <i class="ph-duotone ph-user-minus fs-2 mb-2 d-block text-secondary"></i>
                                                    <?= _('Aucun élève n\'est inscrit dans cette classe pour le moment.') ?>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php $index = 1; foreach ($eleves as $eleve):
                                                $grade = $grades[$eleve['id_eleve']] ?? null;
                                            ?>
                                                <tr>
                                                    <td class="text-muted fw-bold"><?= $index++ ?></td>
                                                    <td>
                                                        <span class="fw-bold text-dark">
                                                            <?= htmlspecialchars($eleve['nom'] . ' ' . $eleve['prenom']) ?>
                                                        </span>
                                                        <small class="text-muted d-block">Mat: <?= htmlspecialchars($eleve['matricule'] ?? 'N/A') ?></small>
                                                    </td>
                                                    <td>
                                                        <div class="input-group">
                                                            <input type="number"
                                                                   step="0.25"
                                                                   min="0"
                                                                   max="<?= (float)$bareme_defaut ?>"
                                                                   class="form-control fw-bold text-primary"
                                                                   name="grades[<?= $eleve['id_eleve'] ?>][note]"
                                                                   value="<?= htmlspecialchars($grade['note'] ?? '') ?>"
                                                                   placeholder="e.g. 15.5">
                                                            <span class="input-group-text">/ <?= number_format((float)$bareme_defaut, 0) ?></span>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <input type="text"
                                                               class="form-control"
                                                               name="grades[<?= $eleve['id_eleve'] ?>][appreciation]"
                                                               value="<?= htmlspecialchars($grade['appreciation'] ?? '') ?>"
                                                               placeholder="<?= _('Appréciation note (ex: Bon travail)...') ?>">
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                                <a href="/evaluations/select_class" class="btn btn-outline-secondary">
                                    <i class="ph-duotone ph-x me-1"></i> <?= _('Annuler') ?>
                                </a>
                                <button type="submit" class="btn btn-primary btn-lg d-inline-flex align-items-center">
                                    <i class="ph-duotone ph-floppy-disk me-2 fs-4"></i>
                                    <?= _('Enregistrer les Notes') ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer_able.php'; ?>
