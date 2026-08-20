<?php
$title = _("Gestion des Périodes Comptables");
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
              <li class="breadcrumb-item" aria-current="page"><?= _('Périodes Comptables') ?></li>
            </ul>
          </div>
          <div class="col-md-4 text-end d-flex gap-2 justify-content-end">
            <?php if (Auth::can('create', 'comptabilite')): ?>
              <a href="/comptabilite/periodes/generate" class="btn btn-outline-primary d-inline-flex align-items-center gap-1">
                <i class="ph-duotone ph-lightning fs-5"></i>
                <span><?= _("Générer par Lot") ?></span>
              </a>
              <a href="/comptabilite/periodes/create" class="btn btn-primary d-inline-flex align-items-center gap-1">
                <i class="ph-duotone ph-plus-circle fs-5"></i>
                <span><?= _("Nouvelle Période") ?></span>
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

    <!-- Filter Card -->
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-body py-3">
        <form action="/comptabilite/periodes" method="GET" class="row g-2 align-items-center">
          <div class="col-md-4">
            <select name="exercice_id" class="form-select" onchange="this.form.submit()">
              <option value=""><?= _("Tous les exercices financiers") ?></option>
              <?php foreach ($exercices as $ex): ?>
                <option value="<?= $ex['id'] ?>" <?= ($exerciceId == $ex['id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($ex['libelle']) ?> (<?= htmlspecialchars($ex['date_debut']) ?> &rarr; <?= htmlspecialchars($ex['date_fin']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php if ($exerciceId): ?>
            <div class="col-md-2">
              <a href="/comptabilite/periodes" class="btn btn-light-secondary btn-sm"><?= _("Réinitialiser filtre") ?></a>
            </div>
          <?php endif; ?>
        </form>
      </div>
    </div>

    <div class="row">
      <div class="col-12">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-transparent py-3">
            <h5 class="mb-0 d-flex align-items-center gap-2">
              <i class="ph-duotone ph-clock text-primary"></i>
              <span><?= _("Liste des Périodes Comptables") ?></span>
            </h5>
          </div>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>#</th>
                  <th><?= _("Exercice") ?></th>
                  <th><?= _("Date de Début") ?></th>
                  <th><?= _("Date de Fin") ?></th>
                  <th><?= _("Statut") ?></th>
                  <th><?= _("Clôturée Par / Le") ?></th>
                  <th class="text-end"><?= _("Actions") ?></th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($periodes)): ?>
                  <tr>
                    <td colspan="7" class="text-center py-4 text-muted">
                      <i class="ph-duotone ph-folder-open fs-2 d-block mb-2"></i>
                      <?= _("Aucune période comptable configurée.") ?>
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($periodes as $p): ?>
                    <tr>
                      <td>#<?= $p['id'] ?></td>
                      <td><strong><?= htmlspecialchars($p['exercice_libelle']) ?></strong></td>
                      <td><?= htmlspecialchars($p['date_debut']) ?></td>
                      <td><?= htmlspecialchars($p['date_fin']) ?></td>
                      <td>
                        <?php if (!empty($p['est_cloturee'])): ?>
                          <span class="badge bg-danger"><?= _("Clôturée") ?></span>
                        <?php else: ?>
                          <span class="badge bg-success"><?= _("Ouverte") ?></span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <?php if (!empty($p['est_cloturee'])): ?>
                          <small class="text-muted">
                            <?= htmlspecialchars(($p['cloture_par_prenom'] ?? '') . ' ' . ($p['cloture_par_nom'] ?? 'Système')) ?><br/>
                            <?= htmlspecialchars($p['cloturee_le'] ?? '') ?>
                          </small>
                        <?php else: ?>
                          <span class="text-muted">&mdash;</span>
                        <?php endif; ?>
                      </td>
                      <td class="text-end">
                        <?php if (empty($p['est_cloturee']) && Auth::can('close', 'comptabilite')): ?>
                          <a href="/comptabilite/periodes/<?= $p['id'] ?>/cloture" class="btn btn-sm btn-light-danger" onclick="return confirm('<?= _("Êtes-vous sûr de vouloir clôturer cette période comptable ?") ?>');" title="<?= _('Clôturer') ?>">
                            <i class="ph-duotone ph-lock me-1"></i><?= _("Clôturer") ?>
                          </a>
                        <?php elseif (!empty($p['est_cloturee']) && Auth::can('reopen', 'comptabilite')): ?>
                          <a href="/comptabilite/periodes/<?= $p['id'] ?>/reouvrir" class="btn btn-sm btn-light-warning" onclick="return confirm('<?= _("Êtes-vous sûr de vouloir réouvrir cette période comptable ?") ?>');" title="<?= _('Réouvrir') ?>">
                            <i class="ph-duotone ph-lock-key-open me-1"></i><?= _("Réouvrir") ?>
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
