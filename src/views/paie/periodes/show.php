<?php
// src/views/paie/periodes/show.php
?>
<div class="pc-container">
  <div class="pc-content">
    <div class="page-header">
      <div class="page-block">
        <div class="row align-items-center">
          <div class="col-md-12">
            <h5 class="mb-0"><?= _("Détails de la Période de Paie") ?> : <?= htmlspecialchars($periode['code_periode']) ?></h5>
          </div>
        </div>
      </div>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION['success_message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
      <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION['error_message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
      <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <div class="row">
      <div class="col-md-4">
        <div class="card">
          <div class="card-header">
            <h5><?= _("Informations Période") ?></h5>
          </div>
          <div class="card-body">
            <p><strong><?= _("Code") ?> :</strong> <?= htmlspecialchars($periode['code_periode']) ?></p>
            <p><strong><?= _("Mois / Année") ?> :</strong> <?= sprintf('%02d/%04d', $periode['mois'], $periode['annee']) ?></p>
            <p><strong><?= _("Période") ?> :</strong> <?= htmlspecialchars($periode['date_debut']) ?> &rarr; <?= htmlspecialchars($periode['date_fin']) ?></p>
            <p><strong><?= _("Statut") ?> :</strong>
              <?php if ($periode['statut'] === 'brouillon'): ?>
                <span class="badge bg-warning"><?= _("Brouillon") ?></span>
              <?php elseif ($periode['statut'] === 'valide'): ?>
                <span class="badge bg-info"><?= _("Validée") ?></span>
              <?php elseif ($periode['statut'] === 'cloture'): ?>
                <span class="badge bg-success"><?= _("Clôturée") ?></span>
              <?php endif; ?>
            </p>

            <hr/>

            <?php if ($periode['statut'] !== 'cloture'): ?>
              <?php if (Auth::can('calculate', 'paie')): ?>
                <form action="/paie/periodes/calculate" method="POST" class="mb-2">
                  <input type="hidden" name="periode_id" value="<?= $periode['id'] ?>"/>
                  <button type="submit" class="btn btn-warning w-100"><i class="ti ti-calculator me-1"></i><?= _("Calculer les Bulletins") ?></button>
                </form>
              <?php endif; ?>

              <?php if (Auth::can('create', 'paie')): ?>
                <form action="/paie/legacy/import" method="POST" class="mb-2">
                  <input type="hidden" name="periode_id" value="<?= $periode['id'] ?>"/>
                  <button type="submit" class="btn btn-outline-secondary w-100"><i class="ti ti-download me-1"></i><?= _("Importer Salaires Historiques") ?></button>
                </form>
              <?php endif; ?>

              <?php if (Auth::can('close', 'paie')): ?>
                <form action="/paie/periodes/close" method="POST" onsubmit="return confirm('<?= _("Êtes-vous sûr de vouloir clôturer cette période ? Cette action est irréversible.") ?>');">
                  <input type="hidden" name="periode_id" value="<?= $periode['id'] ?>"/>
                  <button type="submit" class="btn btn-danger w-100"><i class="ti ti-lock me-1"></i><?= _("Clôturer la Période") ?></button>
                </form>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="col-md-8">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5><?= _("Bulletins de Paie de la Période") ?> (<?= count($bulletins) ?>)</h5>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th><?= _("Personnel") ?></th>
                    <th><?= _("Version") ?></th>
                    <th><?= _("Brut") ?></th>
                    <th><?= _("Net à Payer") ?></th>
                    <th><?= _("Compta") ?></th>
                    <th><?= _("Règlement") ?></th>
                    <th><?= _("Actions") ?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($bulletins)): ?>
                    <tr>
                      <td colspan="7" class="text-center text-muted"><?= _("Aucun bulletin généré pour cette période.") ?></td>
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
                            <span class="badge bg-success ms-1"><?= _("Active") ?></span>
                          <?php else: ?>
                            <span class="badge bg-secondary ms-1"><?= _("Inactive") ?></span>
                          <?php endif; ?>
                        </td>
                        <td><?= number_format($b['total_brut'], 2) ?> <?= htmlspecialchars($b['devise']) ?></td>
                        <td><strong><?= number_format($b['net_a_payer'], 2) ?> <?= htmlspecialchars($b['devise']) ?></strong></td>
                        <td>
                          <?php if ($b['statut_comptabilisation'] === 'comptabilise'): ?>
                            <span class="badge bg-success"><?= _("Comptabilisé") ?></span>
                          <?php else: ?>
                            <span class="badge bg-warning"><?= _("Non Comptabilisé") ?></span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <?php if ($b['statut_reglement'] === 'paye'): ?>
                            <span class="badge bg-success"><?= _("Payé") ?></span>
                          <?php else: ?>
                            <span class="badge bg-danger"><?= _("Non Payé") ?></span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <a href="/paie/bulletins/show?id=<?= $b['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="ti ti-eye"></i></a>
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
