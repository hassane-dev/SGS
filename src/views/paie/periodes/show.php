<?php
$title = sprintf(_("Détails de la Période de Paie : %s"), $periode['code_periode']);
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
              <li class="breadcrumb-item"><a href="/paie/periodes"><?= _('Périodes de paie') ?></a></li>
              <li class="breadcrumb-item" aria-current="page"><?= htmlspecialchars($periode['code_periode']) ?></li>
            </ul>
          </div>
          <div class="col-md-5 text-end d-flex justify-content-end gap-2">
            <a href="/paie/bulletins?periode_id=<?= $periode['id'] ?>" class="btn btn-outline-primary d-inline-flex align-items-center gap-1">
              <i class="ph-duotone ph-receipt fs-5"></i>
              <span><?= _("Bulletins de la période") ?></span>
            </a>
            <a href="/paie/periodes" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1">
              <i class="ph-duotone ph-arrow-left fs-5"></i>
              <span><?= _("Retour aux Périodes") ?></span>
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

    <div class="row">
      <div class="col-md-4">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-transparent py-3">
            <h5 class="mb-0 d-flex align-items-center gap-2">
              <i class="ph-duotone ph-info text-primary"></i>
              <span><?= _("Informations Période") ?></span>
            </h5>
          </div>
          <div class="card-body">
            <p class="mb-2"><strong><?= _("Code") ?> :</strong> <?= htmlspecialchars($periode['code_periode']) ?></p>
            <p class="mb-2"><strong><?= _("Mois / Année") ?> :</strong> <?= sprintf('%02d/%04d', $periode['mois'], $periode['annee']) ?></p>
            <p class="mb-2"><strong><?= _("Période") ?> :</strong> <?= htmlspecialchars($periode['date_debut']) ?> &rarr; <?= htmlspecialchars($periode['date_fin']) ?></p>
            <p class="mb-2"><strong><?= _("Statut") ?> :</strong>
              <?php if ($periode['statut'] === 'brouillon'): ?>
                <span class="badge bg-light-warning text-warning fw-bold"><?= _("Brouillon") ?></span>
              <?php elseif ($periode['statut'] === 'valide'): ?>
                <span class="badge bg-light-info text-info fw-bold"><?= _("Validée") ?></span>
              <?php elseif ($periode['statut'] === 'cloture'): ?>
                <span class="badge bg-light-success text-success fw-bold"><?= _("Clôturée") ?></span>
              <?php endif; ?>
            </p>

            <hr/>

            <?php
            $lockReason = PaiePeriode::getLockReason($periode);
            ?>

            <?php if ((Auth::can('edit', 'paie') || Auth::can('create', 'paie'))): ?>
              <?php if ($lockReason === null): ?>
                <a href="/paie/periodes/<?= $periode['id'] ?>/edit" class="btn btn-outline-primary w-100 mb-2 d-inline-flex align-items-center justify-content-center gap-1">
                  <i class="ph-duotone ph-pencil-simple fs-5"></i>
                  <span><?= _("Modifier la Période") ?></span>
                </a>
              <?php else: ?>
                <div class="alert alert-light-warning border-warning p-2 mb-2 small">
                  <i class="ph-duotone ph-lock-key me-1 align-middle text-warning fs-6"></i>
                  <strong><?= _("Édition verrouillée :") ?></strong> <?= htmlspecialchars($lockReason) ?>
                </div>
              <?php endif; ?>
            <?php endif; ?>

            <?php if ($periode['statut'] !== 'cloture'): ?>
              <?php if (Auth::can('calculate', 'paie')): ?>
                <form action="/paie/periodes/calculate" method="POST" class="mb-2">
                  <input type="hidden" name="periode_id" value="<?= $periode['id'] ?>"/>
                  <button type="submit" class="btn btn-warning w-100 d-inline-flex align-items-center justify-content-center gap-1">
                    <i class="ph-duotone ph-calculator fs-5"></i>
                    <span><?= _("Calculer") ?> / <?= _("Générer Bulletins") ?></span>
                  </button>
                </form>
              <?php endif; ?>

              <?php if (Auth::can('create', 'paie')): ?>
                <form action="/paie/legacy/import" method="POST" class="mb-2">
                  <input type="hidden" name="periode_id" value="<?= $periode['id'] ?>"/>
                  <button type="submit" class="btn btn-outline-secondary w-100 d-inline-flex align-items-center justify-content-center gap-1">
                    <i class="ph-duotone ph-download-simple fs-5"></i>
                    <span><?= _("Importer Salaires Historiques") ?></span>
                  </button>
                </form>
              <?php endif; ?>

              <?php if (Auth::can('close', 'paie')): ?>
                <form action="/paie/periodes/<?= $periode['id'] ?>/cloture" method="POST" onsubmit="return confirm('<?= _("Êtes-vous sûr de vouloir clôturer cette période ? Cette action est irréversible.") ?>');">
                  <input type="hidden" name="periode_id" value="<?= $periode['id'] ?>"/>
                  <button type="submit" class="btn btn-danger w-100 d-inline-flex align-items-center justify-content-center gap-1">
                    <i class="ph-duotone ph-lock-key fs-5"></i>
                    <span><?= _("Clôturer") ?></span>
                  </button>
                </form>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="col-md-8">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-transparent py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 d-flex align-items-center gap-2">
              <i class="ph-duotone ph-receipt text-primary"></i>
              <span><?= _("Bulletins de Paie de la Période") ?> (<?= count($bulletins) ?>)</span>
            </h5>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th><?= _("Personnel") ?></th>
                    <th><?= _("Version") ?></th>
                    <th><?= _("Brut") ?></th>
                    <th><?= _("Net à Payer") ?></th>
                    <th><?= _("Compta") ?></th>
                    <th><?= _("Règlement") ?></th>
                    <th class="text-end"><?= _("Actions") ?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($bulletins)): ?>
                    <tr>
                      <td colspan="7" class="text-center py-5 text-muted">
                        <i class="ph-duotone ph-file-x fs-1 d-block mb-2 text-muted"></i>
                        <?= _("Aucun bulletin généré pour cette période.") ?>
                      </td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($bulletins as $b): ?>
                      <tr class="<?= $b['est_version_active'] ? '' : 'table-secondary text-muted' ?>">
                        <td>
                          <strong><?= htmlspecialchars($b['nom'] . ' ' . $b['prenom']) ?></strong>
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
