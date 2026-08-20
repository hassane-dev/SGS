<?php
$title = _("Validations des Heures Pédagogiques - Cahier de Texte");
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
              <li class="breadcrumb-item" aria-current="page"><?= _('Cahier de texte / Heures') ?></li>
            </ul>
          </div>
          <div class="col-md-5 text-end">
            <?php if (Auth::can('validate', 'paie')): ?>
              <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#modalValidateHours">
                <i class="ph-duotone ph-check-circle fs-5"></i>
                <span><?= _("Valider une séance") ?></span>
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
              <i class="ph-duotone ph-book-open-text text-primary"></i>
              <span><?= _("Séances de Cours Validées pour la Paie") ?></span>
              <span class="badge bg-light-primary text-primary font-monospace ms-1"><?= count($validations) ?></span>
            </h5>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th><?= _("Enseignant") ?></th>
                    <th><?= _("Date Cours") ?></th>
                    <th><?= _("Durée (Heures)") ?></th>
                    <th><?= _("Taux Horaire") ?></th>
                    <th><?= _("Montant") ?></th>
                    <th><?= _("Statut Validation") ?></th>
                    <th class="text-end"><?= _("Actions") ?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($validations)): ?>
                    <tr>
                      <td colspan="7" class="text-center py-5 text-muted">
                        <i class="ph-duotone ph-clock fs-1 d-block mb-2 text-muted"></i>
                        <?= _("Aucune validation d'heures enregistrée.") ?>
                      </td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($validations as $v): ?>
                      <tr>
                        <td><strong><?= htmlspecialchars($v['nom'] . ' ' . $v['prenom']) ?></strong></td>
                        <td><?= htmlspecialchars($v['date_cours']) ?></td>
                        <td><?= number_format($v['duree_heures'], 2) ?> h</td>
                        <td><?= number_format($v['taux_horaire'], 2) ?> FCFA</td>
                        <td><strong><?= number_format($v['duree_heures'] * $v['taux_horaire'], 2) ?> FCFA</strong></td>
                        <td><span class="badge bg-light-success text-success fw-bold"><?= _("Validée") ?></span></td>
                        <td class="text-end">
                          <a href="/paie/bulletins?personnel_id=<?= $v['enseignant_id'] ?>" class="btn btn-sm btn-light-primary" title="<?= _('Voir le bulletin concerné') ?>">
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

<!-- Modal Validation Directe -->
<div class="modal fade" id="modalValidateHours" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="/paie/cahier-texte/validate" method="POST">
        <div class="modal-header">
          <h5 class="modal-title"><?= _("Valider des Heures Pédagogiques") ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label"><?= _("ID Cahier de Texte") ?></label>
            <input type="number" name="cahier_id" class="form-control" required placeholder="Ex: 101" />
          </div>
          <div class="mb-3">
            <label class="form-label"><?= _("ID Enseignant") ?></label>
            <input type="number" name="enseignant_id" class="form-control" required placeholder="Ex: 5" />
          </div>
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label"><?= _("Durée (Heures)") ?></label>
              <input type="number" step="0.5" name="duree_heures" class="form-control" value="2.0" required />
            </div>
            <div class="col-md-6">
              <label class="form-label"><?= _("Taux Horaire") ?></label>
              <input type="number" step="100" name="taux_horaire" class="form-control" value="5000" required />
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= _("Annuler") ?></button>
          <button type="submit" class="btn btn-primary"><?= _("Confirmer la Validation") ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer_able.php'; ?>
