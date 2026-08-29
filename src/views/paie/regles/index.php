<?php
// src/views/paie/regles/index.php
$title = _("Configuration des Règles de Paie & Barèmes Fiscalité");
require_once __DIR__ . '/../../layouts/header_able.php';
?>

<div class="pc-container">
  <div class="pc-content">

    <div class="page-header mb-4">
      <div class="page-block">
        <div class="row align-items-center">
          <div class="col-md-8">
            <div class="page-header-title">
              <h4 class="mb-1 text-primary fw-bold">
                <i class="ph-duotone ph-sliders me-2"></i><?= htmlspecialchars($title) ?>
              </h4>
            </div>
            <ul class="breadcrumb mb-0">
              <li class="breadcrumb-item"><a href="/paie/periodes"><?= _('Paie') ?></a></li>
              <li class="breadcrumb-item active"><?= _('Configuration des Règles') ?></li>
            </ul>
          </div>
          <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <?php if (Auth::hasPermission('paie', 'config')): ?>
              <a href="/paie/regles/create" class="btn btn-primary d-inline-flex align-items-center gap-2">
                <i class="ph-duotone ph-plus-circle fs-5"></i>
                <span><?= _("Nouvelle Règle de Paie") ?></span>
              </a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <?php if (!empty($_SESSION['success_message'])): ?>
      <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="ph-duotone ph-check-circle me-2 fs-5 align-middle"></i>
        <?= htmlspecialchars($_SESSION['success_message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
      <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error_message'])): ?>
      <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <i class="ph-duotone ph-warning-circle me-2 fs-5 align-middle"></i>
        <?= htmlspecialchars($_SESSION['error_message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
      <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
      <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 text-dark fw-semibold"><?= _("Catalogue Général des Règles & Cotisations") ?></h5>
        <span class="badge bg-light-primary text-primary"><?= count($rules) ?> <?= _("règles référencées") ?></span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th><?= _("Ordre") ?></th>
              <th><?= _("Pays / Code") ?></th>
              <th><?= _("Libellé & Catégorie") ?></th>
              <th><?= _("Assiette") ?></th>
              <th><?= _("Mode & Taux / Montant") ?></th>
              <th><?= _("Limites & Plafonds") ?></th>
              <th><?= _("Période Validité") ?></th>
              <th class="text-center"><?= _("Statut") ?></th>
              <th class="text-end"><?= _("Actions") ?></th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($rules)): ?>
              <tr>
                <td colspan="9" class="text-center py-4 text-muted">
                  <i class="ph-duotone ph-folder-open fs-2 d-block mb-2"></i>
                  <?= _("Aucune règle de paie configurée.") ?>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($rules as $r): ?>
                <tr>
                  <td class="fw-bold text-center">
                    <span class="badge bg-light-secondary text-dark"><?= (int)$r['ordre_application'] ?></span>
                  </td>
                  <td>
                    <span class="fw-bold text-primary"><?= htmlspecialchars($r['pays_code']) ?></span>
                    <br><small class="text-muted font-monospace"><?= htmlspecialchars($r['code_regle']) ?></small>
                  </td>
                  <td>
                    <div class="fw-bold text-dark"><?= htmlspecialchars($r['libelle']) ?></div>
                    <small class="badge bg-light-info text-info">
                      <?= htmlspecialchars(ucwords(str_replace('_', ' ', $r['categorie']))) ?>
                    </small>
                  </td>
                  <td>
                    <small class="fw-semibold text-dark">
                      <?= htmlspecialchars(ucwords(str_replace('_', ' ', $r['base_calcul_type'] ?? 'brut_total'))) ?>
                    </small>
                  </td>
                  <td>
                    <?php if ($r['mode_calcul'] === 'pourcentage'): ?>
                      <div>
                        <span class="fw-bold text-dark"><?= number_format($r['taux_par_defaut'], 2) ?>%</span>
                        <small class="text-muted">(Salarial)</small>
                      </div>
                      <?php if ($r['taux_patronal'] > 0): ?>
                        <div>
                          <span class="fw-bold text-secondary"><?= number_format($r['taux_patronal'], 2) ?>%</span>
                          <small class="text-muted">(Patronal)</small>
                        </div>
                      <?php endif; ?>
                    <?php elseif ($r['mode_calcul'] === 'montant_fixe'): ?>
                      <div>
                        <span class="fw-bold text-dark"><?= number_format($r['montant_fixe_salarial'], 0, ',', ' ') ?> FCFA</span>
                      </div>
                    <?php elseif ($r['mode_calcul'] === 'bareme_progressif'): ?>
                      <span class="badge bg-light-warning text-warning fw-semibold">
                        <?= _("Barème Progressif") ?> (<?= count($r['tiers']) ?> <?= _("tranches") ?>)
                      </span>
                    <?php endif; ?>
                  </td>
                  <td class="small text-muted">
                    <?php if ($r['plafond_maximum']): ?>
                      <div><?= _("Plafond") ?> : <?= number_format($r['plafond_maximum'], 0, ',', ' ') ?></div>
                    <?php endif; ?>
                    <?php if ($r['seuil_minimum']): ?>
                      <div><?= _("Seuil") ?> : <?= number_format($r['seuil_minimum'], 0, ',', ' ') ?></div>
                    <?php endif; ?>
                    <?php if (!$r['plafond_maximum'] && !$r['seuil_minimum']): ?>
                      <span class="text-muted">—</span>
                    <?php endif; ?>
                  </td>
                  <td class="small">
                    <div><i class="ph-duotone ph-calendar-blank me-1 text-success"></i><?= htmlspecialchars($r['date_debut_validite']) ?></div>
                    <?php if ($r['date_fin_validite']): ?>
                      <div><i class="ph-duotone ph-calendar-x me-1 text-danger"></i><?= htmlspecialchars($r['date_fin_validite']) ?></div>
                    <?php else: ?>
                      <span class="badge bg-light-success text-success"><?= _("Indéfinie") ?></span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center">
                    <?php if ($r['actif']): ?>
                      <span class="badge bg-success"><?= _("Actif") ?></span>
                    <?php else: ?>
                      <span class="badge bg-secondary"><?= _("Inactif") ?></span>
                    <?php endif; ?>
                  </td>
                  <td class="text-end">
                    <div class="btn-group btn-group-sm">
                      <a href="/paie/regles/<?= $r['id'] ?>/edit" class="btn btn-light-primary" title="<?= _("Modifier") ?>">
                        <i class="ph-duotone ph-pencil"></i>
                      </a>
                      <form action="/paie/regles/toggle" method="POST" class="d-inline">
                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                        <button type="submit" class="btn btn-light-<?= $r['actif'] ? 'warning' : 'success' ?>" title="<?= $r['actif'] ? _("Désactiver") : _("Activer") ?>">
                          <i class="ph-duotone ph-power"></i>
                        </button>
                      </form>
                    </div>
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

<?php require_once __DIR__ . '/../../layouts/footer_able.php'; ?>
