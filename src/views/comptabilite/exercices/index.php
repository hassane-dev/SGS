<?php
$title = _("Gestion des Exercices Financiers");
require_once __DIR__ . '/../../layouts/header_able.php';
?>

<div class="pc-container">
  <div class="pc-content">
    <div class="page-header d-print-none mb-3">
      <div class="page-block">
        <div class="row align-items-center">
          <div class="col-md-8">
            <div class="page-header-title">
              <h5 class="m-b-10"><?= htmlspecialchars($title) ?></h5>
            </div>
            <ul class="breadcrumb">
              <li class="breadcrumb-item"><a href="/"><?= _('Tableau de Bord') ?></a></li>
              <li class="breadcrumb-item"><a href="/comptabilite/exercices"><?= _('Comptabilité') ?></a></li>
              <li class="breadcrumb-item" aria-current="page"><?= _('Exercices Financiers') ?></li>
            </ul>
          </div>
          <div class="col-md-4 text-end">
            <?php if (Auth::can('create', 'comptabilite')): ?>
              <a href="/comptabilite/exercices/create" class="btn btn-primary d-inline-flex align-items-center gap-1">
                <i class="ph-duotone ph-plus-circle fs-5"></i>
                <span><?= _("Nouvel Exercice") ?></span>
              </a>
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
          <div class="card-header bg-transparent py-3 d-flex align-items-center justify-content-between">
            <h5 class="mb-0 d-flex align-items-center gap-2">
              <i class="ph-duotone ph-calendar text-primary"></i>
              <span><?= _("Liste des Exercices Financiers") ?></span>
            </h5>
            <a href="/comptabilite/periodes" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
              <i class="ph-duotone ph-clock text-primary"></i>
              <span><?= _("Gérer les Périodes Comptables") ?></span>
            </a>
          </div>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th><?= _("Libellé") ?></th>
                  <th><?= _("Date de Début") ?></th>
                  <th><?= _("Date de Fin") ?></th>
                  <th><?= _("Nombre de Périodes") ?></th>
                  <th><?= _("Statut") ?></th>
                  <th class="text-end"><?= _("Actions") ?></th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($exercices)): ?>
                  <tr>
                    <td colspan="6" class="text-center py-4 text-muted">
                      <i class="ph-duotone ph-folder-open fs-2 d-block mb-2"></i>
                      <?= _("Aucun exercice financier configuré pour cet établissement.") ?>
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($exercices as $ex): ?>
                    <tr>
                      <td><strong><?= htmlspecialchars($ex['libelle']) ?></strong></td>
                      <td><?= htmlspecialchars($ex['date_debut']) ?></td>
                      <td><?= htmlspecialchars($ex['date_fin']) ?></td>
                      <td>
                        <span class="badge bg-light-primary text-primary fs-6">
                          <?= (int)$ex['nb_periodes'] ?>
                        </span>
                      </td>
                      <td>
                        <?php if (!empty($ex['cloture'])): ?>
                          <span class="badge bg-danger"><?= _("Clôturé") ?></span>
                        <?php elseif (!empty($ex['est_actif'])): ?>
                          <span class="badge bg-success"><?= _("Actif / En cours") ?></span>
                        <?php else: ?>
                          <span class="badge bg-secondary"><?= _("Inactif") ?></span>
                        <?php endif; ?>
                      </td>
                      <td class="text-end">
                        <a href="/comptabilite/periodes?exercice_id=<?= $ex['id'] ?>" class="btn btn-sm btn-light-primary" title="<?= _('Voir Périodes') ?>">
                          <i class="ph-duotone ph-clock"></i> <?= _("Périodes") ?>
                        </a>
                        <?php if (empty($ex['cloture']) && empty($ex['est_actif']) && Auth::can('edit', 'comptabilite')): ?>
                          <a href="/comptabilite/exercices/<?= $ex['id'] ?>/activate" class="btn btn-sm btn-light-success" title="<?= _('Activer') ?>">
                            <i class="ph-duotone ph-check"></i>
                          </a>
                        <?php endif; ?>
                        <?php if (empty($ex['cloture']) && Auth::can('close', 'comptabilite')): ?>
                          <a href="/comptabilite/exercices/<?= $ex['id'] ?>/close" class="btn btn-sm btn-light-danger" onclick="return confirm('<?= _("Êtes-vous sûr de vouloir clôturer cet exercice ?") ?>');" title="<?= _('Clôturer') ?>">
                            <i class="ph-duotone ph-lock"></i>
                          </a>
                        <?php endif; ?>
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

<?php require_once __DIR__ . '/../../layouts/footer_able.php'; ?>
