<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<!-- [ Main Content ] start -->
<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= _('Détails de la Classe') ?>: <?= htmlspecialchars($classe['nom_complet']) ?></h2>
                        </div>
                        <p class="mb-0">
                            <?= _('Niveau') ?>: <?= htmlspecialchars($classe['niveau']) ?> | <?= _('Série') ?>: <?= htmlspecialchars($classe['serie'] ?? 'N/A') ?> | <?= _('Catégorie') ?>: <?= htmlspecialchars($classe['categorie'] ?? 'N/A') ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- [ Main Content ] start -->
        <div class="row">
            <!-- Annual Parameters Card -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5><?= _('Paramètres pour l\'année') ?> <?= htmlspecialchars($active_year['libelle'] ?? 'N/A') ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if (Auth::can('edit', 'class') && $active_year): ?>
                        <form action="/classes/updateParams" method="POST">
                            <input type="hidden" name="classe_id" value="<?= $classe['id_classe'] ?>">
                            <input type="hidden" name="annee_academique_id" value="<?= $active_year['id'] ?>">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="nombre_places" class="form-label"><?= _('Nombre de places') ?></label>
                                    <input type="number" class="form-control" name="nombre_places" value="<?= htmlspecialchars($params_annuels['nombre_places'] ?? '') ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label"><?= _('Effectif actuel') ?></label>
                                    <p class="form-control-plaintext"><?= htmlspecialchars($params_annuels['effectif_actuel'] ?? 0) ?> <?= _('élèves') ?></p>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="professeur_principal_id" class="form-label"><?= _('Professeur Principal') ?></label>
                                <select name="professeur_principal_id" class="form-select">
                                    <option value=""><?= _('-- Aucun --') ?></option>
                                    <?php foreach ($enseignants as $enseignant): ?>
                                        <option value="<?= $enseignant['id_user'] ?>" <?= (isset($params_annuels['professeur_principal_id']) && $params_annuels['professeur_principal_id'] == $enseignant['id_user']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($enseignant['full_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="commentaire" class="form-label"><?= _('Commentaire') ?></label>
                                <textarea name="commentaire" class="form-control" rows="2"><?= htmlspecialchars($params_annuels['commentaire'] ?? '') ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-success"><?= _('Enregistrer les paramètres') ?></button>
                        </form>
                        <?php else: ?>
                            <p><?= _('Les paramètres annuels ne peuvent pas être modifiés car aucune année académique n\'est active ou vous n\'avez pas les permissions.') ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Assign Subjects Card -->
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5><?= _('Attribuer une Matière') ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if (Auth::can('edit', 'class')): ?>
                        <form action="/classes/assignMatiere" method="POST">
                            <input type="hidden" name="classe_id" value="<?= $classe['id_classe'] ?>">
                            <div class="mb-3">
                                <label for="matiere_id" class="form-label"><?= _('Matière') ?></label>
                                <select name="matiere_id" id="matiere_id" class="form-select" required>
                                    <option value=""><?= _('-- Choisir une matière --') ?></option>
                                    <?php
                                    $assigned_ids = array_column($assigned_matieres, 'id_matiere');
                                    foreach ($all_matieres as $matiere):
                                        if (!in_array($matiere['id_matiere'], $assigned_ids)): ?>
                                            <option value="<?= $matiere['id_matiere'] ?>"><?= htmlspecialchars($matiere['nom_matiere']) ?></option>
                                    <?php endif; endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="coefficient" class="form-label"><?= _('Coefficient') ?></label>
                                <input type="number" step="0.25" name="coefficient" id="coefficient" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="statut" class="form-label"><?= _('Statut') ?></label>
                                <select name="statut" id="statut" class="form-select" required>
                                    <option value="obligatoire"><?= _('Obligatoire') ?></option>
                                    <option value="optionnelle"><?= _('Optionnelle') ?></option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary mt-3"><?= _('Ajouter la Matière') ?></button>
                        </form>
                        <?php else: ?>
                            <p><?= _('Vous n\'avez pas la permission d\'attribuer des matières.') ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Assigned Subjects List -->
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h5><?= _('Matières et Enseignants') ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th><?= _('Matière') ?></th>
                                        <th><?= _('Coefficient') ?></th>
                                        <th><?= _('Enseignant') ?></th>
                                        <th class="text-end"><?= _('Actions') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($assigned_matieres as $matiere): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($matiere['nom_matiere']) ?></td>
                                            <td><?= htmlspecialchars($matiere['coefficient']) ?></td>
                                            <td>
                                                <?php if (isset($teacher_assignments[$matiere['id_matiere']])):
                                                    $assignment = $teacher_assignments[$matiere['id_matiere']];
                                                ?>
                                                    <span><?= htmlspecialchars($assignment['enseignant_nom']) ?></span>

                                                    <div class="d-inline-flex">
                                                        <?php if (Auth::can('manage_settings', 'evaluation')): ?>
                                                        <a href="/evaluations/settings?classe_id=<?= $classe['id_classe'] ?>&matiere_id=<?= $matiere['id_matiere'] ?>" class="btn btn-sm btn-outline-secondary ms-2" title="<?= _('Paramètres des évaluations') ?>">
                                                            <?= _('Params Éval.') ?>
                                                        </a>
                                                        <?php endif; ?>

                                                        <?php if (Auth::can('edit', 'class')): ?>
                                                        <a href="/classes/unassignEnseignant?assignment_id=<?= $assignment['id'] ?>&classe_id=<?= $classe['id_classe'] ?>" class="btn btn-sm btn-outline-danger ms-1" title="<?= _('Dissocier l\'enseignant') ?>">
                                                            X
                                                        </a>
                                                        <?php endif; ?>
                                                    </div>

                                                <?php else: ?>
                                                    <?php if (Auth::can('edit', 'class')): ?>
                                                    <form action="/classes/assignEnseignant" method="POST" class="d-flex">
                                                        <input type="hidden" name="classe_id" value="<?= $classe['id_classe'] ?>">
                                                        <input type="hidden" name="matiere_id" value="<?= $matiere['id_matiere'] ?>">
                                                        <select name="enseignant_id" class="form-select form-select-sm" required>
                                                            <option value=""><?= _('-- Assigner --') ?></option>
                                                            <?php foreach ($enseignants as $enseignant): ?>
                                                                <option value="<?= $enseignant['id_user'] ?>"><?= htmlspecialchars($enseignant['full_name']) ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <button type="submit" class="btn btn-sm btn-success ms-2">+</button>
                                                    </form>
                                                    <?php else: ?>
                                                        <span class="text-muted"><?= _('Non assigné') ?></span>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <?php if (Auth::can('edit', 'class')): ?>
                                                    <a href="/classes/removeMatiere?id=<?= $matiere['id'] ?>&classe_id=<?= $classe['id_classe'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('<?= _('Êtes-vous sûr de vouloir retirer cette matière de la classe ?') ?>');" title="<?= _('Retirer la matière') ?>">
                                                        <?= _('Retirer') ?>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ Main Content ] end -->
    </div>
</div>
<!-- [ Main Content ] end -->

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
