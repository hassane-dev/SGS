<?php
$title = _("Régularisations de Paie N+1");
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
              <li class="breadcrumb-item" aria-current="page"><?= _('Régularisations') ?></li>
            </ul>
          </div>
          <div class="col-md-5 text-end">
            <?php if (Auth::can('regularize', 'paie')): ?>
              <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#modalCreateRegu">
                <i class="ph-duotone ph-plus-circle fs-5"></i>
                <span><?= _("Créer une Régularisation") ?></span>
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
              <i class="ph-duotone ph-arrows-merge text-primary"></i>
              <span><?= _("Historique des Régularisations") ?></span>
              <span class="badge bg-light-primary text-primary font-monospace ms-1"><?= count($regularisations) ?></span>
            </h5>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th><?= _("Personnel") ?></th>
                    <th><?= _("Origine Source") ?></th>
                    <th><?= _("Type") ?></th>
                    <th><?= _("Motif") ?></th>
                    <th><?= _("Delta Brut") ?></th>
                    <th><?= _("Delta Net") ?></th>
                    <th><?= _("Statut") ?></th>
                    <th class="text-end"><?= _("Actions") ?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($regularisations)): ?>
                    <tr>
                      <td colspan="8" class="text-center py-5 text-muted">
                        <i class="ph-duotone ph-arrows-split fs-1 d-block mb-2 text-muted"></i>
                        <?= _("Aucune régularisation enregistrée pour cette période.") ?>
                      </td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($regularisations as $r): ?>
                      <tr>
                        <td>
                          <strong><?= htmlspecialchars($r['nom'] . ' ' . $r['prenom']) ?></strong>
                          <div class="small text-muted font-monospace"><?= htmlspecialchars($r['identifiant_public'] ?? '') ?></div>
                        </td>
                        <td>
                          <span class="badge bg-light-secondary text-dark fw-bold me-1"><?= htmlspecialchars(strtoupper($r['source_type'] ?? 'bulletin')) ?></span>
                          <?php if (!empty($r['bulletin_source_id'])): ?>
                            <span class="small font-monospace">Bulletin #<?= $r['bulletin_source_id'] ?></span>
                          <?php elseif (!empty($r['periode_source_id'])): ?>
                            <span class="small font-monospace">Période #<?= $r['periode_source_id'] ?></span>
                          <?php else: ?>
                            <span class="small text-muted"><?= _("Directe") ?></span>
                          <?php endif; ?>
                        </td>
                        <td><span class="badge bg-light-info text-info fw-bold"><?= htmlspecialchars($r['type_regularisation']) ?></span></td>
                        <td><?= htmlspecialchars($r['motif']) ?></td>
                        <td><?= number_format($r['montant_brut_delta'], 2) ?></td>
                        <td><strong><?= number_format($r['montant_net_delta'], 2) ?></strong></td>
                        <td>
                          <?php if ($r['statut'] === 'integre'): ?>
                            <span class="badge bg-light-success text-success fw-bold"><?= _("Intégrée") ?></span>
                          <?php elseif ($r['statut'] === 'valide'): ?>
                            <span class="badge bg-light-warning text-warning fw-bold"><?= _("Validée (En attente N+1)") ?></span>
                          <?php else: ?>
                            <span class="badge bg-light-secondary text-secondary fw-bold"><?= htmlspecialchars($r['statut']) ?></span>
                          <?php endif; ?>
                        </td>
                        <td class="text-end">
                          <?php if (!empty($r['bulletin_source_id'])): ?>
                            <a href="/paie/bulletins/<?= $r['bulletin_source_id'] ?>" class="btn btn-sm btn-light-primary" title="<?= _('Voir bulletin source') ?>">
                              <i class="ph-duotone ph-eye fs-6"></i>
                              <span class="d-none d-md-inline ms-1"><?= _("Voir bulletin") ?></span>
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
</div>

<!-- Modal Création Régularisation (UX Métier Met en Place) -->
<div class="modal fade" id="modalCreateRegu" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form action="/paie/regularisations/store" method="POST">
        <div class="modal-header">
          <h5 class="modal-title"><?= _("Créer une Régularisation de Paie (N+1)") ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold"><?= _("Salarié / Enseignant") ?> <span class="text-danger">*</span></label>
              <select name="personnel_id" id="regu_personnel_id" class="form-select" required onchange="filterSourceBulletins()">
                <option value=""><?= _("Sélectionner un salarié...") ?></option>
                <?php foreach ($teachers as $t): ?>
                  <option value="<?= $t['id_user'] ?>">
                    <?= htmlspecialchars($t['nom'] . ' ' . $t['prenom']) ?> (<?= htmlspecialchars($t['identifiant_public'] ?? '') ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold"><?= _("Période Destination (N+1)") ?> <span class="text-danger">*</span></label>
              <select name="periode_destination_id" class="form-select" required>
                <option value=""><?= _("Sélectionner la période ouverte N+1...") ?></option>
                <?php foreach ($openPeriodes as $op): ?>
                  <option value="<?= $op['id'] ?>" <?= ($periodeId == $op['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($op['code_periode']) ?> (<?= htmlspecialchars($op['date_debut']) ?> au <?= htmlspecialchars($op['date_fin']) ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold"><?= _("Origine de la Régularisation") ?> <span class="text-danger">*</span></label>
              <select name="source_type" id="regu_source_type" class="form-select" required onchange="toggleSourceFields()">
                <option value="bulletin"><?= _("Correction d'un Bulletin Existant") ?></option>
                <option value="service_fait"><?= _("Omission Service Fait / Période Passée") ?></option>
                <option value="autre"><?= _("Ajustement / Motif Administratif") ?></option>
              </select>
            </div>

            <!-- Dynamic Source Field: Bulletin -->
            <div class="col-md-6" id="field_bulletin_source">
              <label class="form-label fw-semibold"><?= _("Bulletin Source Existant") ?></label>
              <select name="bulletin_source_id" id="regu_bulletin_source_id" class="form-select">
                <option value=""><?= _("Sélectionner un bulletin source...") ?></option>
                <?php foreach ($bulletins as $b): ?>
                  <option value="<?= $b['id'] ?>" data-personnel="<?= $b['personnel_id'] ?>">
                    #<?= $b['id'] ?> - <?= htmlspecialchars($b['nom'] . ' ' . $b['prenom']) ?> - <?= htmlspecialchars($b['code_periode']) ?> (Net: <?= number_format($b['net_a_payer'], 0) ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Dynamic Source Field: Periode -->
            <div class="col-md-6 d-none" id="field_periode_source">
              <label class="form-label fw-semibold"><?= _("Période Source Concerné") ?></label>
              <select name="periode_source_id" id="regu_periode_source_id" class="form-select">
                <option value=""><?= _("Sélectionner la période passée...") ?></option>
                <?php foreach ($allPeriodes as $ap): ?>
                  <option value="<?= $ap['id'] ?>">
                    <?= htmlspecialchars($ap['code_periode']) ?> (<?= htmlspecialchars($ap['date_debut']) ?> au <?= htmlspecialchars($ap['date_fin']) ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold"><?= _("Type de Régularisation") ?> <span class="text-danger">*</span></label>
            <select name="type_regularisation" class="form-select" required>
              <option value="rappel_salaire"><?= _("Rappel de Salaire (Gain Brut)") ?></option>
              <option value="retenue_trop_percu"><?= _("Retenue Trop-Perçu (Déduction Brute)") ?></option>
              <option value="regularisation_cotisation"><?= _("Ajustement Net Spécifique") ?></option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold"><?= _("Motif détaillé") ?> <span class="text-danger">*</span></label>
            <input type="text" name="motif" class="form-control" required placeholder="<?= _('Description précise de la régularisation (min. 10 caractères)') ?>" minlength="10" />
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold"><?= _("Delta Brut (FCFA)") ?></label>
              <input type="number" step="0.01" name="montant_brut_delta" class="form-control" value="0.00" required placeholder="+25000 ou -10000" />
              <span class="text-muted small"><?= _("Rappel (+), Retenue (-)") ?></span>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold"><?= _("Delta Net Explicite (FCFA)") ?></label>
              <input type="number" step="0.01" name="montant_net_delta" class="form-control" value="0.00" required placeholder="0.00 si brut renseigné" />
              <span class="text-muted small"><?= _("Uniquement pour ajustement net direct") ?></span>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= _("Annuler") ?></button>
          <button type="submit" class="btn btn-primary"><?= _("Enregistrer la Régularisation") ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function toggleSourceFields() {
  const st = document.getElementById('regu_source_type').value;
  const fBul = document.getElementById('field_bulletin_source');
  const fPer = document.getElementById('field_periode_source');

  if (st === 'bulletin') {
    fBul.classList.remove('d-none');
    fPer.classList.add('d-none');
  } else if (st === 'service_fait') {
    fBul.classList.add('d-none');
    fPer.classList.remove('d-none');
  } else {
    fBul.classList.add('d-none');
    fPer.classList.add('d-none');
  }
}

function filterSourceBulletins() {
  const pId = document.getElementById('regu_personnel_id').value;
  const selectBul = document.getElementById('regu_bulletin_source_id');
  const options = selectBul.querySelectorAll('option');

  options.forEach(opt => {
    if (!opt.value) return;
    if (!pId || opt.getAttribute('data-personnel') === pId) {
      opt.style.display = '';
    } else {
      opt.style.display = 'none';
    }
  });
}
</script>

<?php require_once __DIR__ . '/../../layouts/footer_able.php'; ?>
