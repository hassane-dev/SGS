<?php require_once __DIR__ . '/../../views/layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../../views/layouts/sidebar_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <!-- Breadcrumb -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= _('Historique des Salaires') ?></h2>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/"><?= _('Tableau de Bord') ?></a></li>
                            <li class="breadcrumb-item"><a href="/paie/periodes"><?= _('Paie') ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _('Historique des Salaires') ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="row">
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="ph-duotone ph-funnel me-2"></i><?= _('Filtres de recherche') ?></h5>
                        <?php if (!empty($_GET)): ?>
                            <a href="/paie/historique" class="btn btn-sm btn-light-secondary">
                                <i class="ph-duotone ph-arrow-counter-clockwise me-1"></i><?= _('Réinitialiser') ?>
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="/paie/historique" class="row g-3">
                            <!-- Personnel Filter -->
                            <div class="col-md-3">
                                <label class="form-label"><?= _('Personnel') ?></label>
                                <select name="personnel_id" class="form-select select2">
                                    <option value=""><?= _('Tous les membres du personnel') ?></option>
                                    <?php foreach ($personnelsOptions as $p): ?>
                                        <option value="<?= $p['id_user'] ?>" <?= ($filters['personnel_id'] ?? '') == $p['id_user'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($p['nom'] . ' ' . $p['prenom'] . ' (' . ($p['identifiant_public'] ?: 'ID#'.$p['id_user']) . ')') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Periode Filter -->
                            <div class="col-md-3">
                                <label class="form-label"><?= _('Période de paie') ?></label>
                                <select name="periode_id" class="form-select">
                                    <option value=""><?= _('Toutes les périodes') ?></option>
                                    <?php foreach ($periodesOptions as $per): ?>
                                        <option value="<?= $per['id'] ?>" <?= ($filters['periode_id'] ?? '') == $per['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($per['code_periode'] . ' (' . str_pad($per['mois'], 2, '0', STR_PAD_LEFT) . '/' . $per['annee'] . ')') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Annee Academique Filter -->
                            <div class="col-md-3">
                                <label class="form-label"><?= _('Année académique') ?></label>
                                <select name="annee_academique_id" class="form-select">
                                    <option value=""><?= _('Toutes les années') ?></option>
                                    <?php foreach ($anneesOptions as $aa): ?>
                                        <option value="<?= $aa['id'] ?>" <?= ($filters['annee_academique_id'] ?? '') == $aa['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($aa['libelle']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Type Contrat Filter -->
                            <div class="col-md-3">
                                <label class="form-label"><?= _('Type de contrat') ?></label>
                                <select name="type_contrat_id" class="form-select">
                                    <option value=""><?= _('Tous les types de contrat') ?></option>
                                    <?php foreach ($typesContratOptions as $tc): ?>
                                        <option value="<?= $tc['id_contrat'] ?>" <?= ($filters['type_contrat_id'] ?? '') == $tc['id_contrat'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($tc['libelle']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Statut Bulletin Filter -->
                            <div class="col-md-2">
                                <label class="form-label"><?= _('Statut bulletin') ?></label>
                                <select name="statut_bulletin" class="form-select">
                                    <option value=""><?= _('Tous les statuts') ?></option>
                                    <option value="brouillon" <?= ($filters['statut_bulletin'] ?? '') === 'brouillon' ? 'selected' : '' ?>><?= _('Brouillon') ?></option>
                                    <option value="valide" <?= ($filters['statut_bulletin'] ?? '') === 'valide' ? 'selected' : '' ?>><?= _('Validé') ?></option>
                                    <option value="cloture" <?= ($filters['statut_bulletin'] ?? '') === 'cloture' ? 'selected' : '' ?>><?= _('Clôturé') ?></option>
                                </select>
                            </div>

                            <!-- Statut Règlement Filter -->
                            <div class="col-md-2">
                                <label class="form-label"><?= _('Règlement') ?></label>
                                <select name="statut_reglement" class="form-select">
                                    <option value=""><?= _('Tous') ?></option>
                                    <option value="paye" <?= ($filters['statut_reglement'] ?? '') === 'paye' ? 'selected' : '' ?>><?= _('Payé') ?></option>
                                    <option value="non_paye" <?= ($filters['statut_reglement'] ?? '') === 'non_paye' ? 'selected' : '' ?>><?= _('Non payé') ?></option>
                                </select>
                            </div>

                            <!-- Date Debut Filter -->
                            <div class="col-md-3">
                                <label class="form-label"><?= _('Période du') ?></label>
                                <input type="date" name="date_debut" class="form-control" value="<?= htmlspecialchars($filters['date_debut'] ?? '') ?>">
                            </div>

                            <!-- Date Fin Filter -->
                            <div class="col-md-3">
                                <label class="form-label"><?= _('Au') ?></label>
                                <input type="date" name="date_fin" class="form-control" value="<?= htmlspecialchars($filters['date_fin'] ?? '') ?>">
                            </div>

                            <!-- Filter Submit -->
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="ph-duotone ph-magnifying-glass me-1"></i><?= _('Filtrer') ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- History Results Card -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="ph-duotone ph-receipt me-2"></i><?= _('Historique des rémunérations') ?>
                            <span class="badge bg-light-primary text-primary ms-2"><?= count($bulletins) ?></span>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th><?= _('Période') ?></th>
                                        <th><?= _('Employé') ?></th>
                                        <th><?= _('Contrat') ?></th>
                                        <th class="text-end"><?= _('Base') ?></th>
                                        <th class="text-end"><?= _('Brut') ?></th>
                                        <th class="text-end"><?= _('Déductions') ?></th>
                                        <th class="text-end"><?= _('Net à payer') ?></th>
                                        <th class="text-center"><?= _('Statut') ?></th>
                                        <th class="text-center"><?= _('Règlement') ?></th>
                                        <th class="text-end"><?= _('Actions') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bulletins as $b): ?>
                                        <?php
                                            $totalDeductions = (float)$b['total_cotisations_salariales'] + (float)$b['total_impots'] + (float)$b['total_retenues'];
                                            $devise = htmlspecialchars($b['devise'] ?: 'FCFA');
                                        ?>
                                        <tr>
                                            <!-- Période -->
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars($b['code_periode']) ?></div>
                                                <small class="text-muted">
                                                    <?= str_pad($b['mois'], 2, '0', STR_PAD_LEFT) . '/' . $b['annee'] ?>
                                                    <?php if (!empty($b['annee_academique_libelle'])): ?>
                                                        • <?= htmlspecialchars($b['annee_academique_libelle']) ?>
                                                    <?php endif; ?>
                                                </small>
                                            </td>

                                            <!-- Employé -->
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars($b['nom'] . ' ' . $b['prenom']) ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($b['identifiant_public'] ?: 'ID#'.$b['personnel_id']) ?></small>
                                            </td>

                                            <!-- Contrat -->
                                            <td>
                                                <div><?= htmlspecialchars($b['type_contrat_libelle'] ?: _('Contrat standard')) ?></div>
                                                <small class="badge bg-light-secondary">
                                                    <?= htmlspecialchars($b['mode_calcul_principal'] ?: 'forfait_fixe') ?>
                                                </small>
                                            </td>

                                            <!-- Base -->
                                            <td class="text-end text-nowrap">
                                                <?= number_format((float)$b['salaire_base'], 0, ',', ' ') ?> <small class="text-muted"><?= $devise ?></small>
                                            </td>

                                            <!-- Brut -->
                                            <td class="text-end text-nowrap">
                                                <?= number_format((float)$b['total_brut'], 0, ',', ' ') ?> <small class="text-muted"><?= $devise ?></small>
                                            </td>

                                            <!-- Déductions -->
                                            <td class="text-end text-nowrap text-danger">
                                                -<?= number_format($totalDeductions, 0, ',', ' ') ?> <small class="text-muted"><?= $devise ?></small>
                                            </td>

                                            <!-- Net à Payer -->
                                            <td class="text-end text-nowrap fw-bold text-success fs-6">
                                                <?= number_format((float)$b['net_a_payer'], 0, ',', ' ') ?> <small><?= $devise ?></small>
                                            </td>

                                            <!-- Statut Bulletin -->
                                            <td class="text-center">
                                                <?php if ($b['statut_bulletin'] === 'valide'): ?>
                                                    <span class="badge bg-light-success text-success"><i class="ph-duotone ph-check-circle me-1"></i><?= _('Validé') ?></span>
                                                <?php elseif ($b['statut_bulletin'] === 'cloture'): ?>
                                                    <span class="badge bg-light-dark text-dark"><i class="ph-duotone ph-lock me-1"></i><?= _('Clôturé') ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-light-warning text-warning"><i class="ph-duotone ph-clock me-1"></i><?= _('Brouillon') ?></span>
                                                <?php endif; ?>

                                                <?php if (!empty($b['est_reprise_legacy'])): ?>
                                                    <div class="mt-1">
                                                        <span class="badge bg-light-info text-info" title="<?= _('Bulletin issu d\'une reprise historique legacy') ?>">
                                                            <i class="ph-duotone ph-history me-1"></i><?= _('Reprise Legacy') ?>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Statut Règlement -->
                                            <td class="text-center">
                                                <?php if ($b['statut_reglement'] === 'paye'): ?>
                                                    <span class="badge bg-success"><i class="ph-duotone ph-check me-1"></i><?= _('Payé') ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary"><i class="ph-duotone ph-hourglass me-1"></i><?= _('Non payé') ?></span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Actions -->
                                            <td class="text-end">
                                                <a href="/paie/bulletins/<?= $b['id'] ?>" class="btn btn-icon btn-light-primary" title="<?= _('Consulter le bulletin de paie') ?>">
                                                    <i class="ph-duotone ph-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>

                                    <?php if (empty($bulletins)): ?>
                                        <tr>
                                            <td colspan="10" class="text-center py-5 text-muted">
                                                <i class="ph-duotone ph-file-x fs-1 d-block mb-2 text-secondary"></i>
                                                <?= _('Aucun bulletin de salaire trouvé dans l\'historique pour les critères sélectionnés.') ?>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../views/layouts/footer_able.php'; ?>
