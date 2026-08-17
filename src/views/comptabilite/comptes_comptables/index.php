<?php
$title = _("Plan de Comptes Comptables Général");
ob_start();

require_once __DIR__ . '/../../layouts/header_able.php';
require_once __DIR__ . '/../../layouts/sidebar_able.php';
?>

<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h5 class="m-b-10"><?= htmlspecialchars($title) ?></h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/"><?= _('Tableau de Bord') ?></a></li>
                            <li class="breadcrumb-item"><a href="/journal"><?= _('Comptabilité') ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _("Plan de Comptes") ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- Alerts -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="ph-duotone ph-check-circle me-1"></i>
                <?= htmlspecialchars($_SESSION['success_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="ph-duotone ph-warning-circle me-1"></i>
                <?= htmlspecialchars($_SESSION['error_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <!-- Filter Card -->
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" action="/comptes-comptables" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="search" class="form-label font-weight-bold"><?= _("Rechercher (Numéro / Libellé)") ?></label>
                        <input type="text" name="search" id="search" class="form-control" placeholder="Ex: 571100, Caisse..." value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                    </div>

                    <div class="col-md-4">
                        <label for="classe" class="form-label font-weight-bold"><?= _("Classe Comptable OHADA") ?></label>
                        <select name="classe" id="classe" class="form-select">
                            <option value=""><?= _("-- Toutes les classes --") ?></option>
                            <?php foreach ($classes as $cNum => $cName): ?>
                                <option value="<?= $cNum ?>" <?= ($filters['classe'] ?? '') == $cNum ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cName) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="actif" class="form-label font-weight-bold"><?= _("Statut") ?></label>
                        <select name="actif" id="actif" class="form-select">
                            <option value=""><?= _("Tous") ?></option>
                            <option value="1" <?= ($filters['actif'] ?? '') === '1' ? 'selected' : '' ?>><?= _("Actifs seuls") ?></option>
                            <option value="0" <?= ($filters['actif'] ?? '') === '0' ? 'selected' : '' ?>><?= _("Inactifs seuls") ?></option>
                        </select>
                    </div>

                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ph-duotone ph-funnel me-1"></i> <?= _("Filtrer") ?>
                        </button>
                        <a href="/comptes-comptables" class="btn btn-outline-secondary" title="<?= _("Réinitialiser") ?>">
                            <i class="ph-duotone ph-arrows-counter-clockwise"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Main Content -->
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><?= _("Référentiel des Comptes (Plan de Comptes OHADA)") ?></h5>
                        <?php if (Auth::can('create', 'comptes_comptables')): ?>
                            <a href="/comptes-comptables/create" class="btn btn-primary d-inline-flex align-items-center">
                                <i class="ph-duotone ph-plus-circle me-1"></i> <?= _("Nouveau Compte Comptable") ?>
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th><?= _("Numéro") ?></th>
                                        <th><?= _("Libellé du Compte") ?></th>
                                        <th><?= _("Classe") ?></th>
                                        <th><?= _("Nature") ?></th>
                                        <th><?= _("Compte Parent") ?></th>
                                        <th><?= _("Écriture") ?></th>
                                        <th><?= _("Statut") ?></th>
                                        <th class="text-end"><?= _("Actions") ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($comptes)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">
                                                <i class="ph-duotone ph-folder-open f-30 d-block mb-2"></i>
                                                <?= _("Aucun compte comptable trouvé.") ?>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($comptes as $c): ?>
                                            <tr>
                                                <td>
                                                    <code class="fw-bold fs-6 text-primary"><?= htmlspecialchars($c['numero']) ?></code>
                                                    <?php if (!empty($c['est_systeme'])): ?>
                                                        <span class="badge bg-light-info text-info ms-1" title="<?= _("Compte Système") ?>"><i class="ph-duotone ph-lock-key"></i></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="fw-bold"><?= htmlspecialchars($c['libelle']) ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light-primary text-primary"><?= _("Classe") ?> <?= $c['classe'] ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light-secondary text-secondary text-capitalize"><?= htmlspecialchars($c['nature']) ?></span>
                                                </td>
                                                <td>
                                                    <?php if ($c['compte_parent_id'] && !empty($c['parent_numero'])): ?>
                                                        <code><?= htmlspecialchars($c['parent_numero']) ?></code> — <small class="text-muted"><?= htmlspecialchars($c['parent_libelle']) ?></small>
                                                    <?php else: ?>
                                                        <span class="text-muted small"><?= _("Racine") ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($c['autoriser_ecriture'])): ?>
                                                        <span class="badge bg-light-success text-success"><i class="ph-duotone ph-check-circle me-1"></i><?= _("Autorisée") ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-light-warning text-warning"><i class="ph-duotone ph-minus-circle me-1"></i><?= _("Regroupement") ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($c['actif'])): ?>
                                                        <span class="badge bg-success"><?= _("Actif") ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary"><?= _("Inactif") ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end">
                                                    <div class="dropdown d-inline-block">
                                                        <button class="btn btn-sm btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="ph-duotone ph-dots-three-vertical"></i>
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            <?php if (Auth::can('edit', 'comptes_comptables')): ?>
                                                                <li>
                                                                    <a class="dropdown-menu-item dropdown-item" href="/comptes-comptables/edit/<?= $c['id'] ?>">
                                                                        <i class="ph-duotone ph-note-pencil me-2 text-primary"></i><?= _("Modifier") ?>
                                                                    </a>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-menu-item dropdown-item" href="/comptes-comptables/toggle/<?= $c['id'] ?>">
                                                                        <i class="ph-duotone ph-power me-2 <?= $c['actif'] ? 'text-warning' : 'text-success' ?>"></i>
                                                                        <?= $c['actif'] ? _("Désactiver") : _("Activer") ?>
                                                                    </a>
                                                                </li>
                                                            <?php endif; ?>

                                                            <?php if (Auth::can('delete', 'comptes_comptables') && empty($c['est_systeme'])): ?>
                                                                <li><hr class="dropdown-divider"></li>
                                                                <li>
                                                                    <a class="dropdown-menu-item dropdown-item text-danger" href="/comptes-comptables/destroy/<?= $c['id'] ?>" onclick="return confirm('<?= _("Êtes-vous sûr de vouloir supprimer ce compte comptable ? S'il est utilisé, il sera désactivé pour préserver l'intégrité.") ?>');">
                                                                        <i class="ph-duotone ph-trash me-2"></i><?= _("Supprimer") ?>
                                                                    </a>
                                                                </li>
                                                            <?php endif; ?>
                                                        </ul>
                                                    </div>
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

<?php
require_once __DIR__ . '/../../layouts/footer_able.php';
$content = ob_get_clean();
echo $content;
?>
