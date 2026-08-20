<?php
$title = _("Créer un Exercice Financier");
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
              <li class="breadcrumb-item"><a href="/comptabilite/exercices"><?= _('Comptabilité') ?></a></li>
              <li class="breadcrumb-item"><a href="/comptabilite/exercices"><?= _('Exercices Financiers') ?></a></li>
              <li class="breadcrumb-item" aria-current="page"><?= _('Nouvel Exercice') ?></li>
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
              <span><?= _("Formulaire de Création d'un Exercice") ?></span>
            </h5>
          </div>
          <div class="card-body">
            <form action="/comptabilite/exercices/store" method="POST">
              <div class="mb-3">
                <label class="form-label"><?= _("Libellé de l'Exercice") ?> <span class="text-danger">*</span></label>
                <input type="text" name="libelle" class="form-control" placeholder="Ex: Exercice 2026" value="<?= htmlspecialchars($_POST['libelle'] ?? '') ?>" required />
              </div>

              <div class="row mb-3">
                <div class="col-md-6">
                  <label class="form-label"><?= _("Date de Début") ?> <span class="text-danger">*</span></label>
                  <input type="date" name="date_debut" class="form-control" value="<?= htmlspecialchars($_POST['date_debut'] ?? date('Y-01-01')) ?>" required />
                </div>
                <div class="col-md-6">
                  <label class="form-label"><?= _("Date de Fin") ?> <span class="text-danger">*</span></label>
                  <input type="date" name="date_fin" class="form-control" value="<?= htmlspecialchars($_POST['date_fin'] ?? date('Y-12-31')) ?>" required />
                </div>
              </div>

              <div class="mb-3">
                <div class="form-check form-switch">
                  <input class="form-check-input" type="checkbox" name="est_actif" id="est_actif" value="1" checked>
                  <label class="form-check-label" for="est_actif"><?= _("Définir comme exercice actif par défaut") ?></label>
                </div>
              </div>

              <div class="d-flex justify-content-between pt-2">
                <a href="/comptabilite/exercices" class="btn btn-outline-secondary"><?= _("Annuler") ?></a>
                <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-1">
                  <i class="ph-duotone ph-floppy-disk fs-5"></i>
                  <span><?= _("Enregistrer l'Exercice") ?></span>
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer_able.php'; ?>
