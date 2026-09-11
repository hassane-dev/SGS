<?php
// src/views/appreciation_conseil/index.php
?>
<div class="pc-container">
  <div class="pc-content">

    <!-- Page Header -->
    <div class="page-header">
      <div class="page-block">
        <div class="row align-items-center">
          <div class="col-md-12">
            <ul class="breadcrumb mb-2">
              <li class="breadcrumb-item"><a href="/dashboard"><i class="ph-duotone ph-house me-1"></i><?= _("Tableau de bord") ?></a></li>
              <li class="breadcrumb-item"><a href="javascript:void(0)"><?= _("Pédagogie") ?></a></li>
              <li class="breadcrumb-item" aria-current="page"><?= htmlspecialchars($title ?? _("Appréciation du conseil de classe")) ?></li>
            </ul>
            <h2 class="mb-0"><?= htmlspecialchars($title ?? _("Appréciation du conseil de classe")) ?></h2>
            <p class="text-muted mb-0"><?= _("Saisie collective des appréciations du conseil de classe pour les élèves de votre classe principale.") ?></p>
          </div>
        </div>
      </div>
    </div>

    <!-- Alert Messages -->
    <?php if (!empty($_SESSION['success_message'])): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="ph-duotone ph-check-circle me-2 fs-5"></i><?= htmlspecialchars($_SESSION['success_message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
      <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error_message'])): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="ph-duotone ph-warning-circle me-2 fs-5"></i><?= htmlspecialchars($_SESSION['error_message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
      <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
      <div class="alert alert-warning" role="alert">
        <i class="ph-duotone ph-info me-2 fs-5"></i><?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <!-- Context Filter Card -->
    <div class="card mb-4">
      <div class="card-body">
        <form method="GET" action="/appreciation-conseil" class="row g-3 align-items-end">
          <div class="col-md-5">
            <label for="classe_id" class="form-label fw-bold"><i class="ph-duotone ph-chalkboard-teacher me-1"></i><?= _("Classe principale") ?></label>
            <select name="classe_id" id="classe_id" class="form-select" onchange="this.form.submit()">
              <?php if (empty($classes)): ?>
                <option value=""><?= _("Aucune classe sous votre responsabilité") ?></option>
              <?php else: ?>
                <?php foreach ($classes as $c): ?>
                  <option value="<?= $c['id_classe'] ?>" <?= ($selected_classe && $selected_classe['id_classe'] == $c['id_classe']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars(trim(($c['niveau'] ?? '') . ' ' . ($c['serie'] ?? '') . ' ' . ($c['numero'] ?? ''))) ?>
                  </option>
                <?php endforeach; ?>
              <?php endif; ?>
            </select>
          </div>

          <div class="col-md-5">
            <label for="sequence_id" class="form-label fw-bold"><i class="ph-duotone ph-calendar me-1"></i><?= _("Séquence académique") ?></label>
            <select name="sequence_id" id="sequence_id" class="form-select" onchange="this.form.submit()">
              <?php if (empty($sequences)): ?>
                <option value=""><?= _("Aucune séquence disponible") ?></option>
              <?php else: ?>
                <?php foreach ($sequences as $s): ?>
                  <option value="<?= $s['id'] ?>" <?= ($selected_sequence && $selected_sequence['id'] == $s['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($s['nom'] ?? '') ?> <?= ($s['statut'] === 'ouverte') ? ' (' . _('Ouverte') . ')' : ' (' . _('Fermée') . ')' ?>
                  </option>
                <?php endforeach; ?>
              <?php endif; ?>
            </select>
          </div>

          <div class="col-md-2 d-grid">
            <button type="submit" class="btn btn-outline-primary">
              <i class="ph-duotone ph-funnel me-1"></i><?= _("Filtrer") ?>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Collective Entry Form -->
    <?php if (!empty($selected_classe) && !empty($selected_sequence)): ?>
      <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
          <h5 class="mb-0">
            <i class="ph-duotone ph-user-list me-2"></i>
            <?= htmlspecialchars(trim(($selected_classe['niveau'] ?? '') . ' ' . ($selected_classe['serie'] ?? '') . ' ' . ($selected_classe['numero'] ?? ''))) ?>
            &mdash; <?= htmlspecialchars($selected_sequence['nom'] ?? '') ?>
            <span class="badge bg-light-primary text-primary ms-2"><?= count($eleves) ?> <?= _("élève(s)") ?></span>
          </h5>
        </div>
        <div class="card-body p-0">
          <?php if (empty($eleves)): ?>
            <div class="p-4 text-center text-muted">
              <i class="ph-duotone ph-user-minus fs-1 d-block mb-2 text-secondary"></i>
              <?= _("Aucun élève actif inscrit dans cette classe pour la séquence sélectionnée.") ?>
            </div>
          <?php else: ?>
            <form action="/appreciation-conseil/save" method="POST">
              <input type="hidden" name="classe_id" value="<?= $selected_classe['id_classe'] ?>">
              <input type="hidden" name="sequence_id" value="<?= $selected_sequence['id'] ?>">

              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                  <thead class="table-light">
                    <tr>
                      <th style="width: 60px;" class="text-center">#</th>
                      <th style="width: 280px;"><?= _("Élève") ?></th>
                      <th><?= _("Appréciation du conseil de classe") ?></th>
                      <th style="width: 130px;" class="text-center"><?= _("Statut") ?></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php $idx = 1; foreach ($eleves as $e): ?>
                      <?php
                        $eId = (int)$e['id_eleve'];
                        $apprecData = $appreciations[$eId] ?? null;
                        $apprecVal = $apprecData['appreciation_conseil_classe'] ?? '';
                        $statutVal = $apprecData['statut'] ?? 'provisoire';
                        $isLocked = in_array($statutVal, ['valide', 'publie']);
                      ?>
                      <tr>
                        <td class="text-center text-muted fw-bold"><?= $idx++ ?></td>
                        <td>
                          <div class="d-flex align-items-center">
                            <?php if (!empty($e['photo'])): ?>
                              <img src="<?= htmlspecialchars($e['photo']) ?>" alt="Photo" class="rounded-circle me-2" style="width: 38px; height: 38px; object-fit: cover;">
                            <?php else: ?>
                              <div class="avatar-soft-primary rounded-circle me-2 d-flex align-items-center justify-content-center fw-bold" style="width: 38px; height: 38px; background-color: #eef2f6; color: #4680ff;">
                                <?= strtoupper(substr($e['prenom'] ?? '', 0, 1) . substr($e['nom'] ?? '', 0, 1)) ?>
                              </div>
                            <?php endif; ?>
                            <div>
                              <div class="fw-bold text-dark"><?= htmlspecialchars(($e['nom'] ?? '') . ' ' . ($e['prenom'] ?? '')) ?></div>
                              <div class="small text-muted">ID: #<?= $eId ?></div>
                            </div>
                          </div>
                        </td>
                        <td>
                          <?php if ($isLocked): ?>
                            <div class="p-2 rounded bg-light border text-muted">
                              <i class="ph-duotone ph-lock me-1"></i><?= htmlspecialchars($apprecVal ?: _('Aucune appréciation enregistrée.')) ?>
                            </div>
                            <input type="hidden" name="appreciations[<?= $eId ?>]" value="<?= htmlspecialchars($apprecVal) ?>">
                          <?php else: ?>
                            <textarea name="appreciations[<?= $eId ?>]" class="form-control" rows="2" placeholder="<?= _('Saisir l\'appréciation du conseil de classe...') ?>"><?= htmlspecialchars($apprecVal) ?></textarea>
                          <?php endif; ?>
                        </td>
                        <td class="text-center">
                          <?php if ($statutVal === 'publie'): ?>
                            <span class="badge bg-light-success text-success"><i class="ph-duotone ph-globe me-1"></i><?= _("Publié") ?></span>
                          <?php elseif ($statutVal === 'valide'): ?>
                            <span class="badge bg-light-info text-info"><i class="ph-duotone ph-check-circle me-1"></i><?= _("Validé") ?></span>
                          <?php else: ?>
                            <span class="badge bg-light-secondary text-secondary"><i class="ph-duotone ph-clock me-1"></i><?= _("Provisoire") ?></span>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>

              <div class="card-footer d-flex align-items-center justify-content-between p-3">
                <span class="text-muted small">
                  <i class="ph-duotone ph-info me-1"></i><?= _("Les appréciations enregistrées s'inscriront directement sur le bulletin de l'élève.") ?>
                </span>
                <button type="submit" class="btn btn-primary d-inline-flex align-items-center">
                  <i class="ph-duotone ph-floppy-disk me-2"></i><?= _("Enregistrer les appréciations") ?>
                </button>
              </div>
            </form>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

  </div>
</div>
