<?php
// src/views/paie/periodes/index.php
?>
<div class="pc-container">
  <div class="pc-content">
    <div class="page-header">
      <div class="page-block">
        <div class="row align-items-center">
          <div class="col-md-12">
            <h5 class="mb-0"><?= _("Périodes de Paie") ?></h5>
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
      <div class="col-12">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5><?= _("Registre des Périodes de Paie") ?></h5>
            <?php if (Auth::can('create', 'paie')): ?>
              <a href="/paie/periodes/create" class="btn btn-primary"><i class="ti ti-plus me-1"></i><?= _("Nouvelle Période") ?></a>
            <?php endif; ?>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th><?= _("Code Période") ?></th>
                    <th><?= _("Mois / Année") ?></th>
                    <th><?= _("Dates") ?></th>
                    <th><?= _("Statut") ?></th>
                    <th><?= _("Actions") ?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($periodes)): ?>
                    <tr>
                      <td colspan="5" class="text-center text-muted"><?= _("Aucune période de paie enregistrée.") ?></td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($periodes as $p): ?>
                      <tr>
                        <td><strong><?= htmlspecialchars($p['code_periode']) ?></strong></td>
                        <td><?= sprintf('%02d/%04d', $p['mois'], $p['annee']) ?></td>
                        <td><?= htmlspecialchars($p['date_debut']) ?> &rarr; <?= htmlspecialchars($p['date_fin']) ?></td>
                        <td>
                          <?php if ($p['statut'] === 'brouillon'): ?>
                            <span class="badge bg-warning"><?= _("Brouillon") ?></span>
                          <?php elseif ($p['statut'] === 'valide'): ?>
                            <span class="badge bg-info"><?= _("Validée") ?></span>
                          <?php elseif ($p['statut'] === 'cloture'): ?>
                            <span class="badge bg-success"><?= _("Clôturée") ?></span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <a href="/paie/periodes/show?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="ti ti-eye me-1"></i><?= _("Détails") ?></a>
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
