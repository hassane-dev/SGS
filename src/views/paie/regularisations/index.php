<?php
// src/views/paie/regularisations/index.php
?>
<div class="pc-container">
  <div class="pc-content">
    <div class="page-header">
      <div class="page-block">
        <div class="row align-items-center">
          <div class="col-md-12">
            <h5 class="mb-0"><?= _("Régularisations de Paie N+1") ?></h5>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h5><?= _("Historique des Régularisations") ?></h5>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th><?= _("Bulletin Source") ?></th>
                    <th><?= _("Type") ?></th>
                    <th><?= _("Motif") ?></th>
                    <th><?= _("Delta Brut") ?></th>
                    <th><?= _("Delta Net") ?></th>
                    <th><?= _("Statut") ?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($regularisations)): ?>
                    <tr>
                      <td colspan="6" class="text-center text-muted"><?= _("Aucune régularisation enregistrée pour cette période.") ?></td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($regularisations as $r): ?>
                      <tr>
                        <td>#<?= $r['bulletin_source_id'] ?></td>
                        <td><?= htmlspecialchars($r['type_regularisation']) ?></td>
                        <td><?= htmlspecialchars($r['motif']) ?></td>
                        <td><?= number_format($r['montant_brut_delta'], 2) ?></td>
                        <td><strong><?= number_format($r['montant_net_delta'], 2) ?></strong></td>
                        <td><span class="badge bg-success"><?= htmlspecialchars($r['statut']) ?></span></td>
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
