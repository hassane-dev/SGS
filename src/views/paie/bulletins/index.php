<?php
$title = _("Bulletins de Paie");
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
              <li class="breadcrumb-item" aria-current="page"><?= _('Bulletins') ?></li>
            </ul>
          </div>
          <div class="col-md-5 text-end">
            <a href="/paie/periodes" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1">
              <i class="ph-duotone ph-calendar fs-5"></i>
              <span><?= _("Voir les Périodes") ?></span>
            </a>
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

    <!-- Period Filter Form -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body py-3">
        <form method="GET" action="/paie/bulletins" class="row g-3 align-items-center">
          <div class="col-md-6 col-lg-4">
            <label class="form-label small fw-semibold text-muted mb-1"><?= _("Filtrer par Période de Paie") ?></label>
            <select name="periode_id" class="form-select" onchange="this.form.submit()">
              <option value=""><?= _("Toutes les périodes de paie") ?></option>
              <?php foreach ($periodes as $p): ?>
                <option value="<?= $p['id'] ?>" <?= ($periodeId == $p['id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($p['code_periode']) ?> (<?= sprintf('%02d/%04d', $p['mois'], $p['annee']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3 d-flex align-items-end">
            <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-1">
              <i class="ph-duotone ph-funnel"></i>
              <span><?= _("Filtrer") ?></span>
            </button>
            <?php if ($periodeId > 0): ?>
              <a href="/paie/bulletins" class="btn btn-light-secondary ms-2" title="<?= _('Réinitialiser') ?>">
                <i class="ph-duotone ph-arrow-counter-clockwise"></i>
              </a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>

    <div class="row">
      <div class="col-12">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-transparent py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 d-flex align-items-center gap-2">
              <i class="ph-duotone ph-receipt text-primary"></i>
              <span><?= _("Liste des Bulletins de Paie") ?></span>
              <span class="badge bg-light-primary text-primary font-monospace ms-1"><?= count($bulletins) ?></span>
            </h5>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th><?= _("Période") ?></th>
                    <th><?= _("Personnel") ?></th>
                    <th><?= _("Version") ?></th>
                    <th><?= _("Salaire Brut") ?></th>
                    <th><?= _("Net à Payer") ?></th>
                    <th><?= _("Compta") ?></th>
                    <th><?= _("Règlement") ?></th>
                    <th class="text-end"><?= _("Actions") ?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($bulletins)): ?>
                    <tr>
                      <td colspan="8" class="text-center py-5 text-muted">
                        <i class="ph-duotone ph-file-x fs-1 d-block mb-2 text-muted"></i>
                        <?= _("Aucun bulletin de paie trouvé.") ?>
                      </td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($bulletins as $b): ?>
                      <tr class="<?= $b['est_version_active'] ? '' : 'table-secondary text-muted' ?>">
                        <td><strong><?= htmlspecialchars($b['code_periode'] ?? '') ?></strong></td>
                        <td>
                          <strong><?= htmlspecialchars(($b['nom'] ?? '') . ' ' . ($b['prenom'] ?? '')) ?></strong>
                          <div class="small text-muted"><?= htmlspecialchars($b['identifiant_public'] ?? '') ?></div>
                        </td>
                        <td>
                          v<?= $b['version_num'] ?>
                          <?php if ($b['est_version_active']): ?>
                            <span class="badge bg-light-success text-success ms-1"><?= _("Active") ?></span>
                          <?php else: ?>
                            <span class="badge bg-light-secondary text-secondary ms-1"><?= _("Inactive") ?></span>
                          <?php endif; ?>
                        </td>
                        <td><?= number_format($b['total_brut'], 2) ?> <?= htmlspecialchars($b['devise']) ?></td>
                        <td><strong><?= number_format($b['net_a_payer'], 2) ?> <?= htmlspecialchars($b['devise']) ?></strong></td>
                        <td>
                          <?php if ($b['statut_comptabilisation'] === 'comptabilise'): ?>
                            <span class="badge bg-light-success text-success"><?= _("Comptabilisé") ?></span>
                          <?php else: ?>
                            <span class="badge bg-light-warning text-warning"><?= _("Non Comptabilisé") ?></span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <?php if ($b['statut_reglement'] === 'paye'): ?>
                            <span class="badge bg-light-success text-success"><?= _("Payé") ?></span>
                          <?php else: ?>
                            <span class="badge bg-light-danger text-danger"><?= _("Non Payé") ?></span>
                          <?php endif; ?>
                        </td>
                        <td class="text-end">
                          <a href="/paie/bulletins/<?= $b['id'] ?>" class="btn btn-sm btn-light-primary" title="<?= _('Voir') ?>">
                            <i class="ph-duotone ph-eye fs-6"></i>
                            <span class="d-none d-md-inline ms-1"><?= _("Voir") ?></span>
                          </a>
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
