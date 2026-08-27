<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<!-- [ Main Content ] start -->
<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= _('Affectations Pédagogiques') ?></h2>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <?php if (Auth::can('manage_affectations', 'pedagogy')): ?>
                        <a href="/affectations-pedagogiques/create" class="btn btn-primary">
                            <i class="ph-duotone ph-plus-circle me-1"></i>
                            <?= _('Nouvelle Affectation') ?>
                        </a>
                        <?php endif; ?>
                        <a href="/affectations-pedagogiques/history" class="btn btn-outline-secondary ms-2">
                            <i class="ph-duotone ph-clock-counter-clockwise me-1"></i>
                            <?= _('Historique') ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- [ Filter Card ] -->
        <div class="row">
            <div class="col-12">
                <div class="card mb-3">
                    <div class="card-body py-3">
                        <form action="/affectations-pedagogiques" method="GET" class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label small"><?= _('Classe') ?></label>
                                <select name="classe_id" class="form-select form-select-sm">
                                    <option value=""><?= _('-- Toutes les classes --') ?></option>
                                    <?php foreach ($classes as $c): ?>
                                        <option value="<?= $c['id_classe'] ?>" <?= (isset($filters['classe_id']) && $filters['classe_id'] == $c['id_classe']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars(Classe::getFormattedName($c)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small"><?= _('Enseignant') ?></label>
                                <select name="enseignant_id" class="form-select form-select-sm">
                                    <option value=""><?= _('-- Tous les enseignants --') ?></option>
                                    <?php foreach ($enseignants as $e): ?>
                                        <option value="<?= $e['id_user'] ?>" <?= (isset($filters['enseignant_id']) && $filters['enseignant_id'] == $e['id_user']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($e['full_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small"><?= _('Statut') ?></label>
                                <select name="statut" class="form-select form-select-sm">
                                    <option value=""><?= _('-- Tous les statuts --') ?></option>
                                    <option value="actif" <?= (isset($filters['statut']) && $filters['statut'] === 'actif') ? 'selected' : '' ?>><?= _('Actif') ?></option>
                                    <option value="suspendu" <?= (isset($filters['statut']) && $filters['statut'] === 'suspendu') ? 'selected' : '' ?>><?= _('Suspendu') ?></option>
                                    <option value="provisoire" <?= (isset($filters['statut']) && $filters['statut'] === 'provisoire') ? 'selected' : '' ?>><?= _('Provisoire') ?></option>
                                    <option value="termine" <?= (isset($filters['statut']) && $filters['statut'] === 'termine') ? 'selected' : '' ?>><?= _('Terminé') ?></option>
                                </select>
                            </div>
                            <div class="col-md-3 text-end">
                                <button type="submit" class="btn btn-sm btn-secondary"><?= _('Filtrer') ?></button>
                                <a href="/affectations-pedagogiques" class="btn btn-sm btn-link-secondary"><?= _('Réinitialiser') ?></a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- [ Main Content ] start -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><?= _('Registre des Affectations Pédagogiques') ?> (<?= htmlspecialchars($active_year['libelle'] ?? 'En cours') ?>)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th><?= _('Classe') ?></th>
                                        <th><?= _('Matière') ?></th>
                                        <th><?= _('Enseignant') ?></th>
                                        <th><?= _('Vol. Hebdo') ?></th>
                                        <th><?= _('Dates Effectives') ?></th>
                                        <th><?= _('Statut') ?></th>
                                        <th class="text-end"><?= _('Actions') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($affectations)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4 text-muted">
                                                <?= _('Aucune affectation pédagogique trouvée pour ces critères.') ?>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($affectations as $aff): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars(($aff['niveau'] ?? '') . ' ' . ($aff['serie'] ?? '') . ' ' . ($aff['numero'] ?? '')) ?></strong>
                                                </td>
                                                <td><?= htmlspecialchars($aff['nom_matiere']) ?></td>
                                                <td>
                                                    <span class="fw-bold"><?= htmlspecialchars($aff['enseignant_nom']) ?></span>
                                                    <?php if (!empty($aff['enseignant_matricule'])): ?>
                                                        <br><small class="text-muted"><?= htmlspecialchars($aff['enseignant_matricule']) ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= number_format($aff['volume_horaire_hebdo'] ?? 0, 1) ?> h/sem</td>
                                                <td>
                                                    <small>
                                                        Du : <?= htmlspecialchars($aff['date_debut'] ?? 'N/A') ?><br>
                                                        Au : <?= htmlspecialchars($aff['date_fin'] ?? 'En cours') ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <?php
                                                    $badge_class = 'badge-success';
                                                    if ($aff['statut'] === 'suspendu') $badge_class = 'badge-warning';
                                                    if ($aff['statut'] === 'provisoire') $badge_class = 'badge-info';
                                                    if ($aff['statut'] === 'termine' || $aff['statut'] === 'annule') $badge_class = 'badge-secondary';
                                                    ?>
                                                    <span class="badge <?= $badge_class ?>"><?= _(ucfirst(htmlspecialchars($aff['statut']))) ?></span>
                                                </td>
                                                <td class="text-end">
                                                    <?php if (Auth::can('manage_affectations', 'pedagogy') && $aff['statut'] === 'actif'): ?>
                                                        <form action="/affectations-pedagogiques/suspend" method="POST" class="d-inline-block" onsubmit="return confirm('<?= _('Êtes-vous sûr de vouloir suspendre cette affectation ?') ?>');">
                                                            <input type="hidden" name="id" value="<?= $aff['id'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-warning me-1"><?= _('Suspendre') ?></button>
                                                        </form>
                                                        <form action="/affectations-pedagogiques/terminate" method="POST" class="d-inline-block" onsubmit="return confirm('<?= _('Êtes-vous sûr de vouloir clôturer cette affectation ?') ?>');">
                                                            <input type="hidden" name="id" value="<?= $aff['id'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger"><?= _('Clôturer') ?></button>
                                                        </form>
                                                    <?php else: ?>
                                                        <span class="text-muted small">-</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
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

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
