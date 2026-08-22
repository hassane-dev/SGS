<?php
$title = _("Préparation des Bulletins de Paie");
require_once __DIR__ . '/../../layouts/header_able.php';

$isPreviewStage = isset($previewItems) && is_array($previewItems);
?>

<div class="pc-container">
  <div class="pc-content">
    <!-- Header Block -->
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
              <li class="breadcrumb-item"><a href="/paie/bulletins"><?= _('Bulletins') ?></a></li>
              <li class="breadcrumb-item" aria-current="page"><?= $isPreviewStage ? _('Prévisualisation') : _('Préparation') ?></li>
            </ul>
          </div>
          <div class="col-md-5 text-end">
            <a href="/paie/bulletins" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1">
              <i class="ph-duotone ph-arrow-left fs-5"></i>
              <span><?= _("Liste des Bulletins") ?></span>
            </a>
          </div>
        </div>
      </div>
    </div>

    <!-- Alert Notifications -->
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

    <?php if (!$isPreviewStage): ?>
      <!-- STAGE 1: PREPARATION & SELECTION -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-transparent py-3">
          <h5 class="mb-0 text-primary d-flex align-items-center gap-2">
            <i class="ph-duotone ph-list-checks fs-4"></i>
            <span>Étape 1 : Périmètre & Sélection des Salariés</span>
          </h5>
        </div>
        <div class="card-body">
          <form method="POST" action="/paie/bulletins/preview" id="prepForm">
            <!-- Period Selector -->
            <div class="row g-3 mb-4">
              <div class="col-md-6 col-lg-5">
                <label class="form-label fw-semibold text-dark"><?= _("Période de Paie Concernée") ?></label>
                <select name="periode_id" class="form-select form-select-lg" onchange="window.location.href='/paie/bulletins/prepare?periode_id='+this.value">
                  <?php if (empty($periodes)): ?>
                    <option value=""><?= _("Aucune période disponible") ?></option>
                  <?php endif; ?>
                  <?php foreach ($periodes as $p): ?>
                    <option value="<?= $p['id'] ?>" <?= ($selectedPeriode && $selectedPeriode['id'] == $p['id']) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($p['code_periode']) ?> (<?= htmlspecialchars($p['date_debut']) ?> au <?= htmlspecialchars($p['date_fin']) ?>) - <?= strtoupper($p['statut']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <?php if ($selectedPeriode): ?>
                <div class="col-md-6 col-lg-7 d-flex align-items-center">
                  <div class="p-3 bg-light rounded w-100 border">
                    <span class="d-block text-muted small fw-bold text-uppercase"><?= _("Statut Officiel Période") ?></span>
                    <?php if ($selectedPeriode['statut'] === 'cloture'): ?>
                      <span class="badge bg-light-danger text-danger fs-6 mt-1"><i class="ph-duotone ph-lock me-1"></i> <?= _("Clôturée - Génération verrouillée") ?></span>
                    <?php else: ?>
                      <span class="badge bg-light-success text-success fs-6 mt-1"><i class="ph-duotone ph-check-circle me-1"></i> <?= _("Ouverte à la paie") ?></span>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endif; ?>
            </div>

            <?php if ($selectedPeriode && $selectedPeriode['statut'] !== 'cloture'): ?>
              <!-- Scope Selection Radio -->
              <div class="card bg-light-primary border-primary border-opacity-25 mb-4">
                <div class="card-body">
                  <label class="form-label fw-bold text-dark mb-2"><?= _("Mode de Détermination du Périmètre") ?></label>
                  <div class="d-flex flex-wrap gap-4">
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="scope" id="scopeAll" value="all" checked onchange="toggleScopeMode(this.value)">
                      <label class="form-check-label fw-bold text-dark" for="scopeAll">
                        <i class="ph-duotone ph-users me-1 text-primary"></i> <?= _("Tous les personnels éligibles à la paie") ?>
                        <span class="badge bg-primary ms-1"><?= count($eligibleContracts) ?></span>
                      </label>
                      <div class="form-text text-muted extra-small"><?= _("Traite automatiquement tous les contrats actifs de la période côté serveur.") ?></div>
                    </div>

                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="scope" id="scopeSelection" value="selection" onchange="toggleScopeMode(this.value)">
                      <label class="form-check-label fw-bold text-dark" for="scopeSelection">
                        <i class="ph-duotone ph-user-focus me-1 text-primary"></i> <?= _("Sélection personnalisée (Un ou plusieurs personnels)") ?>
                      </label>
                      <div class="form-text text-muted extra-small"><?= _("Cochez individuellement les membres du personnel à traiter.") ?></div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Search and Display Filter Controls -->
              <div class="row g-3 align-items-center mb-3">
                <div class="col-md-6">
                  <div class="input-group">
                    <span class="input-group-text bg-white"><i class="ph-duotone ph-magnifying-glass"></i></span>
                    <input type="text" id="searchInput" class="form-control" placeholder="<?= _('Filtrer la liste par nom, prénom ou identifiant...') ?>" onkeyup="filterPersonnelList()" />
                  </div>
                </div>
                <div class="col-md-6 text-end">
                  <button type="button" class="btn btn-sm btn-outline-secondary" onclick="checkAllPersonnel(true)"><?= _("Tout cocher") ?></button>
                  <button type="button" class="btn btn-sm btn-outline-secondary" onclick="checkAllPersonnel(false)"><?= _("Tout décocher") ?></button>
                </div>
              </div>

              <!-- Personnel List Table -->
              <div class="table-responsive border rounded mb-4" style="max-height: 450px; overflow-y: auto;">
                <table class="table table-hover align-middle mb-0" id="personnelTable">
                  <thead class="table-light sticky-top">
                    <tr>
                      <th style="width: 50px;" class="text-center">
                        <input type="checkbox" id="masterCheck" class="form-check-input" onclick="toggleMasterCheck(this)" />
                      </th>
                      <th><?= _("Matricule") ?></th>
                      <th><?= _("Nom & Prénom") ?></th>
                      <th><?= _("Intitulé Contrat") ?></th>
                      <th><?= _("Mode Calcul") ?></th>
                      <th><?= _("Base Mensuelle") ?></th>
                      <th><?= _("Éligibilité Période") ?></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (empty($eligibleContracts)): ?>
                      <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                          <i class="ph-duotone ph-user-minus fs-2 d-block mb-1"></i>
                          <?= _("Aucun membre du personnel éligible trouvé pour cette période.") ?>
                        </td>
                      </tr>
                    <?php else: ?>
                      <?php foreach ($eligibleContracts as $c): ?>
                        <tr class="personnel-row">
                          <td class="text-center">
                            <input type="checkbox" name="personnel_ids[]" value="<?= $c['personnel_id'] ?>" class="form-check-input personnel-checkbox" checked />
                          </td>
                          <td><span class="font-monospace fw-bold"><?= htmlspecialchars($c['identifiant_public'] ?? '--') ?></span></td>
                          <td><strong><?= htmlspecialchars($c['nom'] . ' ' . $c['prenom']) ?></strong></td>
                          <td><?= htmlspecialchars($c['contrat_libelle'] ?? 'CDI') ?> (v<?= $c['version_num'] ?>)</td>
                          <td>
                            <span class="badge bg-light-info text-info">
                              <?= htmlspecialchars($c['mode_calcul_principal']) ?>
                            </span>
                          </td>
                          <td><?= number_format($c['salaire_base'], 2) ?> <?= htmlspecialchars($c['devise'] ?? 'FCFA') ?></td>
                          <td><span class="badge bg-light-success text-success fw-bold"><i class="ph-duotone ph-check me-1"></i> Éligible</span></td>
                        </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>

              <div class="d-flex justify-content-between align-items-center pt-2">
                <span class="text-muted small" id="selectedCountLabel">
                  <?= sprintf(_("%d membre(s) du personnel éligible(s) au total."), count($eligibleContracts)) ?>
                </span>
                <button type="submit" class="btn btn-primary btn-lg d-inline-flex align-items-center gap-2">
                  <i class="ph-duotone ph-eye fs-4"></i>
                  <span><?= _("Prévisualiser les Bulletins") ?></span>
                </button>
              </div>
            <?php endif; ?>
          </form>
        </div>
      </div>
    <?php else: ?>
      <!-- STAGE 2: READ-ONLY PREVIEW RESULTS -->
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light-primary py-3 d-flex justify-content-between align-items-center">
          <h5 class="mb-0 text-primary d-flex align-items-center gap-2">
            <i class="ph-duotone ph-calculator fs-4"></i>
            <span>Étape 2 : Résultats Prévisualisés (Calcul 100% Hors-Persistance)</span>
            <span class="badge bg-primary font-monospace ms-2"><?= count($previewItems) ?> <?= _("salarié(s)") ?></span>
          </h5>
          <span class="badge bg-light-info text-info p-2"><i class="ph-duotone ph-info me-1"></i> <?= _("Aucun bulletin créé en BDD") ?></span>
        </div>

        <div class="card-body">
          <div class="alert alert-info d-flex align-items-center gap-2 mb-3">
            <i class="ph-duotone ph-info fs-3"></i>
            <div>
              <strong><?= _("Contrôle avant validation finale :") ?></strong>
              <?= _("Vérifiez la liste ci-dessous. Aucune régularisation n'a été consommée et aucun bulletin n'a été enregistré. Cliquez sur « Générer les bulletins » pour confirmer.") ?>
            </div>
          </div>

          <div class="table-responsive border rounded mb-4">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th><?= _("Matricule") ?></th>
                  <th><?= _("Enseignant / Personnel") ?></th>
                  <th><?= _("Salaire Brut") ?></th>
                  <th><?= _("Retenues / Cotis.") ?></th>
                  <th><?= _("Impôts") ?></th>
                  <th><?= _("Net à Payer") ?></th>
                  <th><?= _("Éléments Inclus") ?></th>
                  <th><?= _("État") ?></th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($previewItems)): ?>
                  <tr>
                    <td colspan="8" class="text-center py-4 text-muted">
                      <?= _("Aucun élément prévisualisé.") ?>
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($previewItems as $item): ?>
                    <tr class="<?= $item['status'] === 'warning' ? 'table-warning' : '' ?>">
                      <td><span class="font-monospace fw-bold"><?= htmlspecialchars($item['identifiant_public'] ?? '--') ?></span></td>
                      <td>
                        <strong><?= htmlspecialchars($item['nom'] . ' ' . $item['prenom']) ?></strong>
                        <div class="small text-muted"><?= htmlspecialchars($item['contrat_libelle']) ?></div>
                      </td>
                      <td><?= number_format($item['total_brut'], 2) ?> <?= htmlspecialchars($item['devise']) ?></td>
                      <td><?= number_format($item['total_cotisations_salariales'], 2) ?> <?= htmlspecialchars($item['devise']) ?></td>
                      <td><?= number_format($item['total_impots'], 2) ?> <?= htmlspecialchars($item['devise']) ?></td>
                      <td><strong><?= number_format($item['net_a_payer'], 2) ?> <?= htmlspecialchars($item['devise']) ?></strong></td>
                      <td>
                        <span class="badge bg-light-secondary text-secondary extra-small">
                          <?= $item['heures_validees_count'] ?> h. pédago.
                        </span>
                        <?php if ($item['regularisations_count'] > 0): ?>
                          <span class="badge bg-light-warning text-warning extra-small ms-1">
                            <?= $item['regularisations_count'] ?> régul.
                          </span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <?php if ($item['status'] === 'warning'): ?>
                          <span class="badge bg-warning text-dark"><i class="ph-duotone ph-warning me-1"></i> <?= htmlspecialchars($item['message']) ?></span>
                        <?php else: ?>
                          <span class="badge bg-success"><i class="ph-duotone ph-check-circle me-1"></i> <?= _("OK") ?></span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <!-- Final Confirmation Action Form -->
          <form method="POST" action="/paie/bulletins/calculate" id="calcForm">
            <input type="hidden" name="periode_id" value="<?= $selectedPeriode['id'] ?>" />
            <input type="hidden" name="scope" value="<?= htmlspecialchars($_POST['scope'] ?? 'all') ?>" />
            <input type="hidden" name="idempotency_key" value="BULK-GEN-<?= $selectedPeriode['id'] ?>-<?= time() ?>" />

            <?php if (isset($_POST['personnel_ids']) && is_array($_POST['personnel_ids'])): ?>
              <?php foreach ($_POST['personnel_ids'] as $pid): ?>
                <input type="hidden" name="personnel_ids[]" value="<?= (int)$pid ?>" />
              <?php endforeach; ?>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center pt-2">
              <a href="/paie/bulletins/prepare?periode_id=<?= $selectedPeriode['id'] ?>" class="btn btn-outline-secondary btn-lg d-inline-flex align-items-center gap-2">
                <i class="ph-duotone ph-arrow-left fs-4"></i>
                <span><?= _("Retourner à la Sélection") ?></span>
              </a>

              <button type="submit" class="btn btn-success btn-lg d-inline-flex align-items-center gap-2">
                <i class="ph-duotone ph-check-circle fs-4"></i>
                <span><?= _("Générer les Bulletins Définis") ?></span>
              </button>
            </div>
          </form>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<script>
function toggleScopeMode(mode) {
  const checkboxes = document.querySelectorAll('.personnel-checkbox');
  const masterCheck = document.getElementById('masterCheck');
  if (mode === 'all') {
    checkboxes.forEach(cb => cb.checked = true);
    if (masterCheck) masterCheck.checked = true;
  }
}

function checkAllPersonnel(checked) {
  const scopeSel = document.getElementById('scopeSelection');
  if (scopeSel) scopeSel.checked = true;
  const checkboxes = document.querySelectorAll('.personnel-checkbox');
  const masterCheck = document.getElementById('masterCheck');
  checkboxes.forEach(cb => cb.checked = checked);
  if (masterCheck) masterCheck.checked = checked;
}

function toggleMasterCheck(source) {
  checkAllPersonnel(source.checked);
}

function filterPersonnelList() {
  const input = document.getElementById('searchInput');
  const filter = input.value.toLowerCase();
  const rows = document.querySelectorAll('.personnel-row');

  rows.forEach(row => {
    const text = row.textContent.toLowerCase();
    row.style.display = text.includes(filter) ? '' : 'none';
  });
}
</script>

<?php require_once __DIR__ . '/../../layouts/footer_able.php'; ?>
