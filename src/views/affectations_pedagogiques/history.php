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
                            <h2 class="mb-0"><?= _('Historique des Affectations Pédagogiques') ?></h2>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <a href="/affectations-pedagogiques" class="btn btn-outline-secondary">
                            <i class="ph-duotone ph-arrow-left me-1"></i>
                            <?= _('Registre Actif') ?>
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
                        <form action="/affectations-pedagogiques/history" method="GET" class="row g-2 align-items-end">
                            <div class="col-md-5">
                                <label class="form-label small"><?= _('Filtrer par Classe') ?></label>
                                <select name="classe_id" class="form-select form-select-sm">
                                    <option value=""><?= _('-- Toutes les classes --') ?></option>
                                    <?php foreach ($classes as $c): ?>
                                        <option value="<?= $c['id_classe'] ?>" <?= (isset($filters['classe_id']) && $filters['classe_id'] == $c['id_classe']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars(Classe::getFormattedName($c)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small"><?= _('Filtrer par Enseignant') ?></label>
                                <select name="enseignant_id" class="form-select form-select-sm">
                                    <option value=""><?= _('-- Tous les enseignants --') ?></option>
                                    <?php foreach ($enseignants as $e): ?>
                                        <option value="<?= $e['id_user'] ?>" <?= (isset($filters['enseignant_id']) && $filters['enseignant_id'] == $e['id_user']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($e['full_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2 text-end">
                                <button type="submit" class="btn btn-sm btn-secondary w-100"><?= _('Filtrer') ?></button>
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
                        <h5><?= _('Journal d\'Audit des Mouvements et Historique d\'Affectation') ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th><?= _('Année') ?></th>
                                        <th><?= _('Classe') ?></th>
                                        <th><?= _('Matière') ?></th>
                                        <th><?= _('Enseignant') ?></th>
                                        <th><?= _('Période d\'Activité') ?></th>
                                        <th><?= _('Statut') ?></th>
                                        <th><?= _('Motif / Remarque') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($affectations)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4 text-muted">
                                                <?= _('Aucun historique d\'affectation disponible.') ?>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($affectations as $aff): ?>
                                            <tr>
                                                <td><small class="fw-bold"><?= htmlspecialchars($aff['annee_libelle'] ?? 'N/A') ?></small></td>
                                                <td><?= htmlspecialchars(($aff['niveau'] ?? '') . ' ' . ($aff['serie'] ?? '') . ' ' . ($aff['numero'] ?? '')) ?></td>
                                                <td><?= htmlspecialchars($aff['nom_matiere']) ?></td>
                                                <td>
                                                    <strong><?= htmlspecialchars($aff['enseignant_nom']) ?></strong>
                                                </td>
                                                <td>
                                                    <small>
                                                        Du : <?= htmlspecialchars($aff['date_debut'] ?? 'N/A') ?><br>
                                                        Au : <?= htmlspecialchars($aff['date_fin'] ?? 'Présent') ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <?php
                                                    $badge = 'bg-success';
                                                    if ($aff['statut'] === 'suspendu') $badge = 'bg-warning text-dark';
                                                    if ($aff['statut'] === 'termine' || $aff['statut'] === 'annule') $badge = 'bg-secondary';
                                                    ?>
                                                    <span class="badge <?= $badge ?>"><?= _(ucfirst(htmlspecialchars($aff['statut']))) ?></span>
                                                </td>
                                                <td><small class="text-muted"><?= htmlspecialchars($aff['motif_changement'] ?? '-') ?></small></td>
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
