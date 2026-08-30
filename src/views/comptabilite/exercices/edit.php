<?php
$title = _("Modifier un Exercice Financier");
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
              <li class="breadcrumb-item" aria-current="page"><?= _('Modifier l\'Exercice') ?></li>
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

    <?php if (!empty($hasDependentData)): ?>
      <div class="alert alert-info alert-dismissible fade show mb-3" role="alert">
        <i class="ph-duotone ph-info me-1 fs-5 align-middle"></i>
        <?= _("Cet exercice contient déjà des données comptables ou financières rattachées (périodes, écritures, dépenses, budgets, trésorerie). Les dates et le type d'exercice sont verrouillés pour préserver l'intégrité comptable.") ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>

    <div class="row">
      <div class="col-md-8 mx-auto">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-transparent py-3">
            <h5 class="mb-0 d-flex align-items-center gap-2">
              <i class="ph-duotone ph-pencil text-primary"></i>
              <span><?= _("Formulaire de Modification d'un Exercice") ?></span>
            </h5>
          </div>
          <div class="card-body">
            <form action="/comptabilite/exercices/<?= (int)$exercice['id'] ?>/update" method="POST">
              <input type="hidden" name="id" value="<?= (int)$exercice['id'] ?>" />

              <div class="mb-3">
                <label class="form-label"><?= _("Libellé de l'Exercice") ?> <span class="text-danger">*</span></label>
                <input type="text" name="libelle" class="form-control" value="<?= htmlspecialchars($_POST['libelle'] ?? $exercice['libelle']) ?>" required />
              </div>

              <div class="row mb-3">
                <div class="col-md-6">
                  <label class="form-label"><?= _("Date de Début") ?> <span class="text-danger">*</span></label>
                  <input type="date" name="date_debut" class="form-control" value="<?= htmlspecialchars($_POST['date_debut'] ?? $exercice['date_debut']) ?>" <?= !empty($hasDependentData) ? 'readonly' : 'required' ?> />
                  <?php if (!empty($hasDependentData)): ?>
                    <input type="hidden" name="date_debut" value="<?= htmlspecialchars($exercice['date_debut']) ?>" />
                  <?php endif; ?>
                </div>
                <div class="col-md-6">
                  <label class="form-label"><?= _("Date de Fin") ?> <span class="text-danger">*</span></label>
                  <input type="date" name="date_fin" class="form-control" value="<?= htmlspecialchars($_POST['date_fin'] ?? $exercice['date_fin']) ?>" <?= !empty($hasDependentData) ? 'readonly' : 'required' ?> />
                  <?php if (!empty($hasDependentData)): ?>
                    <input type="hidden" name="date_fin" value="<?= htmlspecialchars($exercice['date_fin']) ?>" />
                  <?php endif; ?>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label"><?= _("Type d'Exercice") ?></label>
                <select name="type_exercice" class="form-select" <?= !empty($hasDependentData) ? 'disabled' : '' ?>>
                  <option value="normal" <?= ($exercice['type_exercice'] ?? 'normal') === 'normal' ? 'selected' : '' ?>><?= _("Normal") ?></option>
                  <option value="initial" <?= ($exercice['type_exercice'] ?? 'normal') === 'initial' ? 'selected' : '' ?>><?= _("Initial") ?></option>
                </select>
                <?php if (!empty($hasDependentData)): ?>
                  <input type="hidden" name="type_exercice" value="<?= htmlspecialchars($exercice['type_exercice'] ?? 'normal') ?>" />
                <?php endif; ?>
              </div>

              <div class="d-flex justify-content-between pt-2">
                <a href="/comptabilite/exercices" class="btn btn-outline-secondary"><?= _("Annuler") ?></a>
                <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-1">
                  <i class="ph-duotone ph-floppy-disk fs-5"></i>
                  <span><?= _("Mettre à Jour") ?></span>
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
