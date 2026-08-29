<?php
$title = _("Périodes de Paie");
require_once __DIR__ . '/../../layouts/header_able.php';
?>

<div class="pc-container">
  <div class="pc-content">
    <div class="page-header d-print-none mb-3">
      <div class="page-block">
        <div class="row align-items-center">
          <div class="col-md-7">
            <div class="page-header-title">
              <h5 class="m-b-10"><?= htmlspecialchars($title) ?></h5>
            </div>
            <ul class="breadcrumb">
              <li class="breadcrumb-item"><a href="/"><?= _('Tableau de Bord') ?></a></li>
              <li class="breadcrumb-item"><a href="/paie/periodes"><?= _('Paie') ?></a></li>
              <li class="breadcrumb-item" aria-current="page"><?= _('Périodes de paie') ?></li>
            </ul>
          </div>
          <div class="col-md-5 text-end">
            <?php if (Auth::can('create', 'paie')): ?>
              <a href="/paie/periodes/create" class="btn btn-primary d-inline-flex align-items-center gap-1">
                <i class="ph-duotone ph-plus fs-5"></i>
                <span><?= _("Nouvelle Période") ?></span>
              </a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
      <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
        <i class="ph-duotone ph-check-circle me-1 fs-5 align-middle"></i>
        <?= htmlspecialchars($_SESSION['success_message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
      <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
      <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
        <i class="ph-duotone ph-warning-circle me-1 fs-5 align-middle"></i>
        <?= htmlspecialchars($_SESSION['error_message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
      <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <div class="row">
      <div class="col-12">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-transparent py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 d-flex align-items-center gap-2">
              <i class="ph-duotone ph-calendar text-primary"></i>
              <span><?= _("Registre des Périodes de Paie") ?></span>
            </h5>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th><?= _("Code Période") ?></th>
                    <th><?= _("Mois / Année") ?></th>
                    <th><?= _("Dates") ?></th>
                    <th><?= _("Statut") ?></th>
                    <th class="text-end"><?= _("Actions") ?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($periodes)): ?>
                    <tr>
                      <td colspan="5" class="text-center py-5 text-muted">
                        <i class="ph-duotone ph-calendar-x fs-1 d-block mb-2 text-muted"></i>
                        <?= _("Aucune période de paie enregistrée.") ?>
                      </td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($periodes as $p): ?>
                      <tr>
                        <td><strong><?= htmlspecialchars($p['code_periode']) ?></strong></td>
                        <td><?= sprintf('%02d/%04d', $p['mois'], $p['annee']) ?></td>
                        <td><?= htmlspecialchars($p['date_debut']) ?> &rarr; <?= htmlspecialchars($p['date_fin']) ?></td>
                        <td>
                          <?php if ($p['statut'] === 'brouillon'): ?>
                            <span class="badge bg-light-warning text-warning fw-bold"><?= _("Brouillon") ?></span>
                          <?php elseif ($p['statut'] === 'valide'): ?>
                            <span class="badge bg-light-info text-info fw-bold"><?= _("Validée") ?></span>
                          <?php elseif ($p['statut'] === 'cloture'): ?>
                            <span class="badge bg-light-success text-success fw-bold"><?= _("Clôturée") ?></span>
                          <?php endif; ?>
                        </td>
                        <td class="text-end">
                          <div class="btn-group btn-group-sm">
                            <a href="/paie/periodes/<?= $p['id'] ?>" class="btn btn-light-primary" title="<?= _('Voir') ?>">
                              <i class="ph-duotone ph-eye fs-6"></i>
                              <span class="d-none d-md-inline ms-1"><?= _("Voir") ?></span>
                            </a>
                            <?php if ((Auth::can('edit', 'paie') || Auth::can('create', 'paie')) && PaiePeriode::getLockReason($p) === null): ?>
                              <a href="/paie/periodes/<?= $p['id'] ?>/edit" class="btn btn-light-warning" title="<?= _('Modifier') ?>">
                                <i class="ph-duotone ph-pencil-simple fs-6"></i>
                                <span class="d-none d-md-inline ms-1"><?= _("Modifier") ?></span>
                              </a>
                            <?php endif; ?>
                            <a href="/paie/bulletins?periode_id=<?= $p['id'] ?>" class="btn btn-light-secondary" title="<?= _('Bulletins') ?>">
                              <i class="ph-duotone ph-receipt fs-6"></i>
                              <span class="d-none d-md-inline ms-1"><?= _("Bulletins") ?></span>
                            </a>
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
  </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer_able.php'; ?>
