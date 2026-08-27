<?php
$title = _("Créer une Nouvelle Période de Paie");
require_once __DIR__ . '/../../layouts/header_able.php';
?>

<div class="pc-container">
  <div class="pc-content">
    <div class="page-header d-print-none mb-3">
      <div class="page-block">
        <div class="row align-items-center">
          <div class="col-md-12">
            <div class="page-header-title">
              <h5 class="m-b-10"><?= htmlspecialchars($title) ?></h5>
            </div>
            <ul class="breadcrumb">
              <li class="breadcrumb-item"><a href="/"><?= _('Tableau de Bord') ?></a></li>
              <li class="breadcrumb-item"><a href="/paie/periodes"><?= _('Paie') ?></a></li>
              <li class="breadcrumb-item"><a href="/paie/periodes"><?= _('Périodes de paie') ?></a></li>
              <li class="breadcrumb-item" aria-current="page"><?= _('Nouvelle Période') ?></li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <?php if (isset($_SESSION['error_message'])): ?>
      <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
        <i class="ph-duotone ph-warning-circle me-1 fs-5 align-middle"></i>
        <?= htmlspecialchars($_SESSION['error_message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
      <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <div class="row">
      <div class="col-md-8 mx-auto">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-transparent py-3">
            <h5 class="mb-0 d-flex align-items-center gap-2">
              <i class="ph-duotone ph-calendar-plus text-primary"></i>
              <span><?= _("Formulaire de Création") ?></span>
            </h5>
          </div>
          <div class="card-body">
            <form action="/paie/periodes/store" method="POST">
              <div class="row mb-3">
                <div class="col-md-6">
                  <label class="form-label"><?= _("Mois") ?></label>
                  <select name="mois" class="form-select" required>
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                      <option value="<?= $m ?>" <?= $m == date('n') ? 'selected' : '' ?>><?= sprintf('%02d', $m) ?></option>
                    <?php endfor; ?>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label"><?= _("Année") ?></label>
                  <input type="number" name="annee" class="form-control" value="<?= date('Y') ?>" required />
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label"><?= _("Période Comptable Associée (Source Comptabilité)") ?> <span class="text-danger">*</span></label>
                <?php if (!empty($comptaPeriodes)): ?>
                  <select name="periode_comptable_id" id="periode_comptable_id" class="form-select" required onchange="updateDatesFromSelectedCompta(this)">
                    <option value=""><?= _("-- Sélectionner une période comptable ouverte --") ?></option>
                    <?php foreach ($comptaPeriodes as $cp): ?>
                      <option value="<?= $cp['id'] ?>" data-debut="<?= htmlspecialchars($cp['date_debut']) ?>" data-fin="<?= htmlspecialchars($cp['date_fin']) ?>">
                        <?= htmlspecialchars($cp['exercice_libelle']) ?> &mdash; Période du <?= htmlspecialchars($cp['date_debut']) ?> au <?= htmlspecialchars($cp['date_fin']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                <?php else: ?>
                  <div class="alert alert-warning mb-0">
                    <i class="ph-duotone ph-warning me-1"></i>
                    <?= _("Aucune période comptable ouverte n'est disponible. Veuillez d'abord configurer une période comptable dans le module Comptabilité.") ?>
                    <a href="/comptabilite/periodes/create" class="alert-link ms-2"><?= _("Créer une période comptable") ?></a>
                  </div>
                <?php endif; ?>
              </div>

              <div class="row mb-3">
                <div class="col-md-6">
                  <label class="form-label"><?= _("Date de Début") ?></label>
                  <input type="date" name="date_debut" id="date_debut" class="form-control" value="<?= date('Y-m-01') ?>" readonly required />
                </div>
                <div class="col-md-6">
                  <label class="form-label"><?= _("Date de Fin") ?></label>
                  <input type="date" name="date_fin" id="date_fin" class="form-control" value="<?= date('Y-m-t') ?>" readonly required />
                </div>
              </div>

              <div class="d-flex justify-content-between pt-2">
                <a href="/paie/periodes" class="btn btn-outline-secondary"><?= _("Annuler") ?></a>
                <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-1" <?= empty($comptaPeriodes) ? 'disabled' : '' ?>>
                  <i class="ph-duotone ph-floppy-disk fs-5"></i>
                  <span><?= _("Enregistrer Période") ?></span>
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function updateDatesFromSelectedCompta(selectElem) {
  var selectedOption = selectElem.options[selectElem.selectedIndex];
  if (selectedOption && selectedOption.dataset.debut && selectedOption.dataset.fin) {
    document.getElementById('date_debut').value = selectedOption.dataset.debut;
    document.getElementById('date_fin').value = selectedOption.dataset.fin;
  }
}
</script>

<?php require_once __DIR__ . '/../../layouts/footer_able.php'; ?>
