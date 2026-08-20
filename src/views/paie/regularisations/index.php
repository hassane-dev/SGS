<?php
$title = _("Régularisations de Paie N+1");
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
              <li class="breadcrumb-item" aria-current="page"><?= _('Régularisations') ?></li>
            </ul>
          </div>
          <div class="col-md-5 text-end">
            <?php if (Auth::can('regularize', 'paie')): ?>
              <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#modalCreateRegu">
                <i class="ph-duotone ph-plus-circle fs-5"></i>
                <span><?= _("Créer une Régularisation") ?></span>
              </button>
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
              <i class="ph-duotone ph-arrows-merge text-primary"></i>
              <span><?= _("Historique des Régularisations") ?></span>
              <span class="badge bg-light-primary text-primary font-monospace ms-1"><?= count($regularisations) ?></span>
            </h5>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th><?= _("Bulletin Source") ?></th>
                    <th><?= _("Type") ?></th>
                    <th><?= _("Motif") ?></th>
                    <th><?= _("Delta Brut") ?></th>
                    <th><?= _("Delta Net") ?></th>
                    <th><?= _("Statut") ?></th>
                    <th class="text-end"><?= _("Actions") ?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($regularisations)): ?>
                    <tr>
                      <td colspan="7" class="text-center py-5 text-muted">
                        <i class="ph-duotone ph-arrows-split fs-1 d-block mb-2 text-muted"></i>
                        <?= _("Aucune régularisation enregistrée pour cette période.") ?>
                      </td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($regularisations as $r): ?>
                      <tr>
                        <td><strong>#<?= $r['bulletin_source_id'] ?></strong></td>
                        <td><span class="badge bg-light-info text-info fw-bold"><?= htmlspecialchars($r['type_regularisation']) ?></span></td>
                        <td><?= htmlspecialchars($r['motif']) ?></td>
                        <td><?= number_format($r['montant_brut_delta'], 2) ?></td>
                        <td><strong><?= number_format($r['montant_net_delta'], 2) ?></strong></td>
                        <td><span class="badge bg-light-success text-success fw-bold"><?= htmlspecialchars($r['statut']) ?></span></td>
                        <td class="text-end">
                          <a href="/paie/bulletins/<?= $r['bulletin_source_id'] ?>" class="btn btn-sm btn-light-primary" title="<?= _('Voir bulletin source') ?>">
                            <i class="ph-duotone ph-eye fs-6"></i>
                            <span class="d-none d-md-inline ms-1"><?= _("Voir bulletin") ?></span>
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

<!-- Modal Création Régularisation -->
<div class="modal fade" id="modalCreateRegu" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="/paie/regularisations/store" method="POST">
        <div class="modal-header">
          <h5 class="modal-title"><?= _("Créer une Régularisation N+1") ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label"><?= _("ID Bulletin Source") ?></label>
            <input type="number" name="bulletin_source_id" class="form-control" required placeholder="Ex: 10" />
          </div>
          <div class="mb-3">
            <label class="form-label"><?= _("ID Période Destination (N+1)") ?></label>
            <input type="number" name="periode_destination_id" class="form-control" required placeholder="Ex: 6" />
          </div>
          <div class="mb-3">
            <label class="form-label"><?= _("Type de Régularisation") ?></label>
            <select name="type_regularisation" class="form-select" required>
              <option value="rappel_salaire"><?= _("Rappel de Salaire") ?></option>
              <option value="retenue_trop_percu"><?= _("Retenue Trop-perçu") ?></option>
              <option value="regularisation_cotisation"><?= _("Régularisation Cotisations") ?></option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label"><?= _("Motif") ?></label>
            <input type="text" name="motif" class="form-control" required placeholder="<?= _('Description de la régularisation') ?>" />
          </div>
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label"><?= _("Delta Brut") ?></label>
              <input type="number" step="0.01" name="montant_brut_delta" class="form-control" value="0.00" required />
            </div>
            <div class="col-md-6">
              <label class="form-label"><?= _("Delta Net") ?></label>
              <input type="number" step="0.01" name="montant_net_delta" class="form-control" value="0.00" required />
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= _("Annuler") ?></button>
          <button type="submit" class="btn btn-primary"><?= _("Enregistrer Régularisation") ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer_able.php'; ?>
