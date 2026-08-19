<?php
// src/views/paie/bulletins/show.php
?>
<div class="pc-container">
  <div class="pc-content">
    <div class="page-header">
      <div class="page-block">
        <div class="row align-items-center">
          <div class="col-md-12 d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><?= _("Fiche Bulletin de Paie 360°") ?> #<?= $bulletin['id'] ?> (v<?= $bulletin['version_num'] ?>)</h5>
            <a href="/paie/periodes/show?id=<?= $bulletin['periode_id'] ?>" class="btn btn-outline-secondary btn-sm"><i class="ti ti-arrow-left me-1"></i><?= _("Retour à la Période") ?></a>
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
      <!-- Bulletin Summary Box -->
      <div class="col-md-4">
        <div class="card">
          <div class="card-header">
            <h5><?= _("Synthèse du Bulletin") ?></h5>
          </div>
          <div class="card-body">
            <p><strong><?= _("Salaire de Base") ?> :</strong> <?= number_format($bulletin['salaire_base'], 2) ?> <?= htmlspecialchars($bulletin['devise']) ?></p>
            <p><strong><?= _("Total Brut") ?> :</strong> <?= number_format($bulletin['total_brut'], 2) ?> <?= htmlspecialchars($bulletin['devise']) ?></p>
            <p><strong><?= _("Cotisations Salariales") ?> :</strong> -<?= number_format($bulletin['total_cotisations_salariales'], 2) ?> <?= htmlspecialchars($bulletin['devise']) ?></p>
            <p><strong><?= _("Impôts & Taxes") ?> :</strong> -<?= number_format($bulletin['total_impots'], 2) ?> <?= htmlspecialchars($bulletin['devise']) ?></p>
            <hr/>
            <h4 class="text-primary"><strong><?= _("Net à Payer") ?> :</strong> <?= number_format($bulletin['net_a_payer'], 2) ?> <?= htmlspecialchars($bulletin['devise']) ?></h4>
            <p class="text-muted small"><strong><?= _("Coût Employeur") ?> :</strong> <?= number_format($bulletin['cout_total_employeur'], 2) ?> <?= htmlspecialchars($bulletin['devise']) ?></p>

            <hr/>

            <p><strong><?= _("Statut Compta") ?> :</strong>
              <?php if ($bulletin['statut_comptabilisation'] === 'comptabilise'): ?>
                <span class="badge bg-success"><?= _("Comptabilisé") ?></span>
              <?php else: ?>
                <span class="badge bg-warning"><?= _("Non Comptabilisé") ?></span>
              <?php endif; ?>
            </p>

            <p><strong><?= _("Statut Règlement") ?> :</strong>
              <?php if ($bulletin['statut_reglement'] === 'paye'): ?>
                <span class="badge bg-success"><?= _("Payé") ?></span>
              <?php else: ?>
                <span class="badge bg-danger"><?= _("Non Payé") ?></span>
              <?php endif; ?>
            </p>

            <!-- Actions -->
            <?php if ($bulletin['est_version_active']): ?>
              <?php if ($bulletin['statut_comptabilisation'] !== 'comptabilise' && Auth::can('accounting', 'paie')): ?>
                <form action="/paie/bulletins/post-accounting" method="POST" class="mb-2">
                  <input type="hidden" name="bulletin_id" value="<?= $bulletin['id'] ?>"/>
                  <button type="submit" class="btn btn-info w-100"><i class="ti ti-book me-1"></i><?= _("Comptabiliser au Grand Livre") ?></button>
                </form>
              <?php endif; ?>

              <?php if ($bulletin['statut_reglement'] !== 'paye' && Auth::can('settle', 'paie')): ?>
                <button type="button" class="btn btn-success w-100 mb-2" data-bs-toggle="modal" data-bs-target="#modalSettle"><i class="ti ti-cash me-1"></i><?= _("Régler le Salaire") ?></button>
              <?php endif; ?>

              <?php if (Auth::can('redraw', 'paie')): ?>
                <button type="button" class="btn btn-outline-warning w-100" data-bs-toggle="modal" data-bs-target="#modalRedraw"><i class="ti ti-refresh me-1"></i><?= _("Exécuter Re-tirage (V2)") ?></button>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Rubriques Breakdown -->
      <div class="col-md-8">
        <div class="card">
          <div class="card-header">
            <h5><?= _("Rubriques & Éléments de Paie") ?></h5>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-bordered table-striped">
                <thead>
                  <tr>
                    <th><?= _("Code") ?></th>
                    <th><?= _("Rubrique") ?></th>
                    <th><?= _("Base") ?></th>
                    <th><?= _("Taux / Montant Salarial") ?></th>
                    <th><?= _("Part Patronale") ?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($lignes as $l): ?>
                    <tr>
                      <td><code><?= htmlspecialchars($l['code_rubrique']) ?></code></td>
                      <td><?= htmlspecialchars($l['libelle']) ?></td>
                      <td><?= number_format($l['base_calcul'], 2) ?></td>
                      <td>
                        <strong><?= number_format($l['montant_salarial'], 2) ?></strong>
                        <?php if ($l['taux'] > 0): ?>
                          <span class="small text-muted">(<?= number_format($l['taux'], 2) ?>%)</span>
                        <?php endif; ?>
                      </td>
                      <td><?= number_format($l['montant_patronal'], 2) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Settlement -->
<div class="modal fade" id="modalSettle" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="/paie/bulletins/settle" method="POST">
        <input type="hidden" name="bulletin_id" value="<?= $bulletin['id'] ?>"/>
        <div class="modal-header">
          <h5 class="modal-title"><?= _("Règlement du Salaire") ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label"><?= _("Compte Financier") ?></label>
            <select name="compte_financier_id" class="form-select" required>
              <?php foreach ($comptesFinanciers as $cf): ?>
                <option value="<?= $cf['id'] ?>"><?= htmlspecialchars($cf['nom_compte']) ?> (Solde: <?= number_format($cf['solde_courant'], 2) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label"><?= _("Mode de Règlement") ?></label>
            <select name="mode_reglement" class="form-select" required>
              <option value="virement"><?= _("Virement Bancaire") ?></option>
              <option value="especes"><?= _("Espèces / Caisse") ?></option>
              <option value="cheque"><?= _("Chèque") ?></option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= _("Annuler") ?></button>
          <button type="submit" class="btn btn-success"><?= _("Confirmer le Règlement") ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Redraw -->
<div class="modal fade" id="modalRedraw" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="/paie/bulletins/redraw" method="POST">
        <input type="hidden" name="bulletin_id" value="<?= $bulletin['id'] ?>"/>
        <div class="modal-header">
          <h5 class="modal-title"><?= _("Re-tirage Bulletin (Création V2)") ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p><?= _("Ajustement du Salaire de Base pour la nouvelle version :") ?></p>
          <div class="mb-3">
            <label class="form-label"><?= _("Nouveau Salaire de Base") ?></label>
            <input type="number" step="0.01" name="salaire_base" class="form-control" value="<?= $bulletin['salaire_base'] ?>" required />
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= _("Annuler") ?></button>
          <button type="submit" class="btn btn-warning"><?= _("Valider Re-tirage") ?></button>
        </div>
      </form>
    </div>
  </div>
</div>
