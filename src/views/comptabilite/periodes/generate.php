<?php
$title = _("Génération Automatique des Périodes Comptables");
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
              <li class="breadcrumb-item"><a href="/comptabilite/periodes"><?= _('Périodes Comptables') ?></a></li>
              <li class="breadcrumb-item" aria-current="page"><?= _('Génération par Lot') ?></li>
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
              <i class="ph-duotone ph-lightning text-primary"></i>
              <span><?= _("Générer les 12 Périodes Mensuelles d'un Exercice") ?></span>
            </h5>
          </div>
          <div class="card-body">
            <p class="text-muted">
              <?= _("Cet outil va créer automatiquement les périodes mensuelles ouvertes pour toute la durée de l'exercice financier sélectionné, en sautant les plages déjà créées pour éviter les chevauchements.") ?>
            </p>
            <form action="/comptabilite/periodes/generate" method="POST">
              <div class="mb-4">
                <label class="form-label"><?= _("Sélectionner l'Exercice Financier Target") ?> <span class="text-danger">*</span></label>
                <select name="exercice_financier_id" class="form-select" required>
                  <option value=""><?= _("-- Sélectionner un exercice --") ?></option>
                  <?php foreach ($exercices as $ex): ?>
                    <option value="<?= $ex['id'] ?>" <?= (!empty($ex['est_actif'])) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($ex['libelle']) ?> (<?= htmlspecialchars($ex['date_debut']) ?> &rarr; <?= htmlspecialchars($ex['date_fin']) ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="d-flex justify-content-between pt-2">
                <a href="/comptabilite/periodes" class="btn btn-outline-secondary"><?= _("Annuler") ?></a>
                <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-1">
                  <i class="ph-duotone ph-lightning fs-5"></i>
                  <span><?= _("Générer les Périodes Mensuelles") ?></span>
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
