<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <div class="page-header d-print-none">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= htmlspecialchars($title) ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php $activeTab = 'index'; require_once __DIR__ . '/_tabs.php'; ?>

        <?php if (!empty($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= htmlspecialchars($_SESSION['success_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= htmlspecialchars($_SESSION['error_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <!-- Search & Filter Card -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="/drh" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label"><?= _('Recherche') ?></label>
                        <input type="text" name="search" class="form-control" placeholder="<?= _('Nom, prénom, matricule, email...') ?>" value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label"><?= _('Statut RH') ?></label>
                        <select name="statut_rh" class="form-select">
                            <option value=""><?= _('Tous les statuts') ?></option>
                            <option value="en_activite" <?= ($filters['statut_rh'] ?? '') === 'en_activite' ? 'selected' : '' ?>><?= _('En activité') ?></option>
                            <option value="en_conge" <?= ($filters['statut_rh'] ?? '') === 'en_conge' ? 'selected' : '' ?>><?= _('En congé') ?></option>
                            <option value="suspendu" <?= ($filters['statut_rh'] ?? '') === 'suspendu' ? 'selected' : '' ?>><?= _('Suspendu') ?></option>
                            <option value="demissionne" <?= ($filters['statut_rh'] ?? '') === 'demissionne' ? 'selected' : '' ?>><?= _('Démissionné') ?></option>
                            <option value="licencie" <?= ($filters['statut_rh'] ?? '') === 'licencie' ? 'selected' : '' ?>><?= _('Licencié') ?></option>
                            <option value="retraite" <?= ($filters['statut_rh'] ?? '') === 'retraite' ? 'selected' : '' ?>><?= _('Retraité') ?></option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label"><?= _('Rôle Applicatif') ?></label>
                        <select name="role_id" class="form-select">
                            <option value=""><?= _('Tous les rôles') ?></option>
                            <?php foreach ($roles as $r): ?>
                                <option value="<?= $r['id_role'] ?>" <?= ($filters['role_id'] ?? '') == $r['id_role'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($r['nom_role']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label"><?= _('Cycle Autorisé') ?></label>
                        <select name="cycle_id" class="form-select">
                            <option value=""><?= _('Tous les cycles') ?></option>
                            <?php foreach ($cycles as $cy): ?>
                                <option value="<?= $cy['id_cycle'] ?>" <?= ($filters['cycle_id'] ?? '') == $cy['id_cycle'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cy['nom_cycle']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100 me-2">
                            <i class="ph-duotone ph-magnifying-glass me-1"> <?= _('Filtrer') ?>
                        </button>
                        <a href="/drh" class="btn btn-light-secondary"><?= _('Réinitialiser') ?></a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Directory Table Card -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><?= _('Membres du Personnel') ?> (<?= count($personnels) ?>)</h5>
                <?php if (Auth::can('create', 'drh')): ?>
                <a href="/drh/create" class="btn btn-primary btn-sm">
                    <i class="ph-duotone ph-plus me-1"> <?= _('Ajouter Personnel') ?>
                </a>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th><?= _('Matricule') ?></th>
                                <th><?= _('Nom & Prénom') ?></th>
                                <th><?= _('Contact') ?></th>
                                <th><?= _('Fonction RH') ?></th>
                                <th><?= _('Rôle Applicatif') ?></th>
                                <th><?= _('Établissement') ?></th>
                                <th><?= _('Statut') ?></th>
                                <th class="text-end"><?= _('Actions') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($personnels as $p): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-light-primary text-primary font-monospace fw-bold">
                                        <?= htmlspecialchars($p['identifiant_public'] ?? 'N/A') ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="/drh/show?id=<?= $p['id_user'] ?>" class="fw-bold text-dark text-decoration-none">
                                        <?= htmlspecialchars($p['prenom'] . ' ' . $p['nom']) ?>
                                    </a>
                                </td>
                                <td>
                                    <div><?= htmlspecialchars($p['email']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($p['telephone'] ?? '—') ?></small>
                                </td>
                                <td><?= htmlspecialchars($p['fonction'] ?? '—') ?></td>
                                <td><span class="badge bg-light-info text-info"><?= htmlspecialchars($p['nom_role'] ?? '—') ?></span></td>
                                <td><?= htmlspecialchars($p['nom_lycee'] ?? '—') ?></td>
                                <td>
                                    <?php
                                    $st = $p['statut_rh'] ?? 'en_activite';
                                    $b = match($st) {
                                        'en_activite' => 'bg-success',
                                        'en_conge' => 'bg-warning',
                                        'suspendu' => 'bg-danger',
                                        default => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="badge <?= $b ?>"><?= strtoupper(htmlspecialchars($st)) ?></span>
                                </td>
                                <td class="text-end">
                                    <a href="/drh/show?id=<?= $p['id_user'] ?>" class="btn btn-sm btn-icon btn-light-primary" title="<?= _('Dossier 360°') ?>">
                                        <i class="ph-duotone ph-eye">
                                    </a>
                                    <?php if (Auth::can('edit', 'drh')): ?>
                                    <a href="/drh/edit?id=<?= $p['id_user'] ?>" class="btn btn-sm btn-icon btn-light-warning ms-1" title="<?= _('Modifier') ?>">
                                        <i class="ph-duotone ph-pencil">
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>

                            <?php if (empty($personnels)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="ph-duotone ph-user-minus fs-1 d-block mb-2">
                                    <?= _('Aucun membre du personnel correspondant aux critères.') ?>
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

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
