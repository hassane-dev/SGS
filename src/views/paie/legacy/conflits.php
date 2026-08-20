<?php
$title = _("Reprises Historiques & Conflits");
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
              <li class="breadcrumb-item" aria-current="page"><?= _('Reprises historiques') ?></li>
            </ul>
          </div>
          <div class="col-md-5 text-end">
            <?php if (!empty($periodes) && Auth::can('create', 'paie')): ?>
              <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#modalImportLegacy">
                <i class="ph-duotone ph-download-simple fs-5"></i>
                <span><?= _("Importer vers une période") ?></span>
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
              <i class="ph-duotone ph-history text-primary"></i>
              <span><?= _("Registre des Salaires Historiques Legacy") ?></span>
              <span class="badge bg-light-primary text-primary font-monospace ms-1"><?= count($legacySalaires) ?></span>
            </h5>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th># ID</th>
                    <th><?= _("Personnel / Employé") ?></th>
                    <th><?= _("Montant Net Legacy") ?></th>
                    <th><?= _("Date Enregistrement") ?></th>
                    <th><?= _("Statut Reprise") ?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($legacySalaires)): ?>
                    <tr>
                      <td colspan="5" class="text-center py-5 text-muted">
                        <i class="ph-duotone ph-check-circle fs-1 d-block mb-2 text-muted"></i>
                        <?= _("Aucun enregistrement de salaire legacy en attente de reprise.") ?>
                      </td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($legacySalaires as $ls): ?>
                      <tr>
                        <td><code>#<?= $ls['id_salaire'] ?? $ls['id'] ?? '' ?></code></td>
                        <td>
                          <strong><?= htmlspecialchars(($ls['nom'] ?? '') . ' ' . ($ls['prenom'] ?? '')) ?></strong>
                          <div class="small text-muted">ID: <?= htmlspecialchars($ls['personnel_id'] ?? 'N/A') ?></div>
                        </td>
                        <td><strong><?= number_format((float)($ls['montant_net'] ?? $ls['montant'] ?? 0.00), 2) ?> FCFA</strong></td>
                        <td><?= htmlspecialchars($ls['created_at'] ?? $ls['date_paiement'] ?? 'N/A') ?></td>
                        <td>
                          <span class="badge bg-light-info text-info fw-bold"><?= _("Historique disponible") ?></span>
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

<!-- Modal Import Legacy -->
<div class="modal fade" id="modalImportLegacy" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="/paie/legacy/import" method="POST">
        <div class="modal-header">
          <h5 class="modal-title"><?= _("Importer Salaires Historiques vers la Paie") ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label"><?= _("Période de Paie Cible") ?></label>
            <select name="periode_id" class="form-select" required>
              <?php foreach ($periodes as $p): ?>
                <option value="<?= $p['id'] ?>">
                  <?= htmlspecialchars($p['code_periode']) ?> (<?= sprintf('%02d/%04d', $p['mois'], $p['annee']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <p class="small text-muted mb-0">
            <i class="ph-duotone ph-info me-1"></i>
            <?= _("Cette opération va créer les bulletins de paie correspondants marqués comme 'est_reprise_legacy = 1'.") ?>
          </p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= _("Annuler") ?></button>
          <button type="submit" class="btn btn-primary"><?= _("Exécuter l'importation") ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer_able.php'; ?>
