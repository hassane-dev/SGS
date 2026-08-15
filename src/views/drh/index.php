<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <!-- 1. En-tête avec Fil d'Ariane & Actions globales -->
        <div class="page-header d-print-none mb-3">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-7">
                        <div class="page-header-title">
                            <h5 class="m-b-10"><?= htmlspecialchars($title) ?></h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/home"><?= _('Tableau de Bord') ?></a></li>
                            <li class="breadcrumb-item"><a href="/drh"><?= _('Ressources Humaines') ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _('Annuaire DRH') ?></li>
                        </ul>
                    </div>
                    <div class="col-md-5 text-end d-flex justify-content-end gap-2">
                        <?php if (Auth::can('create', 'drh')): ?>
                        <a href="/drh/create" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1">
                            <i class="ph-duotone ph-user-plus fs-5"></i>
                            <span><?= _('Nouveau Personnel') ?></span>
                        </a>
                        <?php endif; ?>
                        <?php if (Auth::can('export', 'drh')): ?>
                        <a href="/drh/export<?= !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '' ?>" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
                            <i class="ph-duotone ph-download-simple fs-5"></i>
                            <span><?= _('Exporter (CSV)') ?></span>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php $activeTab = 'index'; require_once __DIR__ . '/_tabs.php'; ?>

        <?php if (!empty($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                <i class="ph-duotone ph-check-circle me-1 fs-5 align-middle"></i>
                <?= htmlspecialchars($_SESSION['success_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                <i class="ph-duotone ph-warning-circle me-1 fs-5 align-middle"></i>
                <?= htmlspecialchars($_SESSION['error_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <!-- 2. Recherche & 3. Filtres RH -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent py-3">
                <h5 class="mb-0 d-flex align-items-center gap-2 fs-6">
                    <i class="ph-duotone ph-magnifying-glass text-primary"></i>
                    <span><?= _('Recherche Globale & Filtres RH') ?></span>
                </h5>
            </div>
            <div class="card-body">
                <form method="GET" action="/drh" class="row g-3">
                    <div class="col-md-4 col-lg-3">
                        <label class="form-label small fw-semibold text-muted"><?= _('Recherche Globale') ?></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="ph-duotone ph-magnifying-glass text-muted"></i></span>
                            <input type="text" name="search" class="form-control border-start-0" placeholder="<?= _('Nom, prénom, matricule, email...') ?>" value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="col-md-3 col-lg-2">
                        <label class="form-label small fw-semibold text-muted"><?= _('Statut RH') ?></label>
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

                    <div class="col-md-3 col-lg-2">
                        <label class="form-label small fw-semibold text-muted"><?= _('Rôle RBAC') ?></label>
                        <select name="role_id" class="form-select">
                            <option value=""><?= _('Tous les rôles') ?></option>
                            <?php foreach ($roles as $r): ?>
                                <option value="<?= $r['id_role'] ?>" <?= ($filters['role_id'] ?? '') == $r['id_role'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($r['nom_role']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3 col-lg-3">
                        <label class="form-label small fw-semibold text-muted"><?= _('Cycle Autorisé') ?></label>
                        <select name="cycle_id" class="form-select">
                            <option value=""><?= _('Tous les cycles') ?></option>
                            <?php foreach ($cycles as $cy): ?>
                                <option value="<?= $cy['id_cycle'] ?>" <?= ($filters['cycle_id'] ?? '') == $cy['id_cycle'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cy['nom_cycle']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2 col-lg-2 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary w-100 d-inline-flex align-items-center justify-content-center gap-1">
                            <i class="ph-duotone ph-funnel"></i>
                            <span><?= _('Filtrer') ?></span>
                        </button>
                        <?php if (!empty($filters['search']) || !empty($filters['statut_rh']) || !empty($filters['role_id']) || !empty($filters['cycle_id'])): ?>
                        <a href="/drh" class="btn btn-light-secondary d-inline-flex align-items-center justify-content-center" title="<?= _('Réinitialiser') ?>">
                            <i class="ph-duotone ph-arrow-counter-clockwise"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- 4. Tableau Personnel, 5. Actions compactes & 6. Pagination/Footer -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 d-flex align-items-center gap-2">
                    <i class="ph-duotone ph-users-three text-primary"></i>
                    <span><?= _('Effectif du Personnel') ?></span>
                    <span class="badge bg-light-primary text-primary font-monospace ms-1"><?= count($personnels) ?></span>
                </h5>
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
                                <th><?= _('Rôle RBAC') ?></th>
                                <th><?= _('Établissement') ?></th>
                                <th><?= _('Statut') ?></th>
                                <th class="text-end" style="min-width: 100px;"><?= _('Actions') ?></th>
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
                                    <div class="small fw-semibold text-dark"><?= htmlspecialchars($p['email']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($p['telephone'] ?? '—') ?></small>
                                </td>
                                <td><?= htmlspecialchars($p['fonction'] ?? '—') ?></td>
                                <td><span class="badge bg-light-info text-info fw-medium"><?= htmlspecialchars($p['nom_role'] ?? '—') ?></span></td>
                                <td><span class="small text-muted"><?= htmlspecialchars($p['nom_lycee'] ?? '—') ?></span></td>
                                <td>
                                    <?php
                                    $st = $p['statut_rh'] ?? 'en_activite';
                                    $b = match($st) {
                                        'en_activite' => 'bg-light-success text-success',
                                        'en_conge' => 'bg-light-warning text-warning',
                                        'suspendu' => 'bg-light-danger text-danger',
                                        default => 'bg-light-secondary text-secondary'
                                    };
                                    $stLabel = match($st) {
                                        'en_activite' => _('En activité'),
                                        'en_conge' => _('En congé'),
                                        'suspendu' => _('Suspendu'),
                                        'demissionne' => _('Démissionné'),
                                        'licencie' => _('Licencié'),
                                        'retraite' => _('Retraité'),
                                        default => strtoupper($st)
                                    };
                                    ?>
                                    <span class="badge <?= $b ?> fw-bold"><?= htmlspecialchars($stLabel) ?></span>
                                </td>
                                <!-- 5. Actions compactes (Aucune icône/bouton redondant) -->
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="/drh/show?id=<?= $p['id_user'] ?>" class="btn btn-light-primary" title="<?= _('Consulter dossier 360°') ?>">
                                            <i class="ph-duotone ph-eye fs-6"></i>
                                        </a>
                                        <?php if (Auth::can('edit', 'drh')): ?>
                                        <a href="/drh/edit?id=<?= $p['id_user'] ?>" class="btn btn-light-warning" title="<?= _('Modifier la fiche') ?>">
                                            <i class="ph-duotone ph-pencil fs-6"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>

                            <?php if (empty($personnels)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="ph-duotone ph-user-minus fs-1 d-block mb-2 text-muted"></i>
                                    <div class="fw-semibold mb-1"><?= _('Aucun membre du personnel correspondant aux critères.') ?></div>
                                    <span class="small text-muted"><?= _('Essayez de réinitialiser les filtres de recherche.') ?></span>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- 6. Pied de tableau & Synthèse -->
            <div class="card-footer bg-transparent py-3 d-flex align-items-center justify-content-between border-top">
                <span class="small text-muted">
                    <?= sprintf(_('Affichage de %d membre(s) du personnel'), count($personnels)) ?>
                </span>
                <div>
                    <span class="badge bg-light-secondary text-dark small"><i class="ph-duotone ph-shield-check me-1 text-success"></i><?= _('Isolation Scope Phase 10 active') ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
