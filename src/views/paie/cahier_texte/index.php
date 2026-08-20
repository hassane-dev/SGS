<?php
$title = _("Validations des Heures Pédagogiques - Cahier de Texte");
require_once __DIR__ . '/../../layouts/header_able.php';

$searchMode = $searchMode ?? 'pedagogique';
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
              <li class="breadcrumb-item"><a href="/"><?= _("Tableau de Bord") ?></a></li>
              <li class="breadcrumb-item"><a href="/paie/periodes"><?= _("Paie") ?></a></li>
              <li class="breadcrumb-item" aria-current="page"><?= _("Cahier de texte / Service Fait") ?></li>
            </ul>
          </div>
          <div class="col-md-5 text-end">
            <div class="btn-group" role="group" aria-label="Modes de recherche">
              <a href="?search_mode=pedagogique" class="btn btn-outline-primary <?= $searchMode === 'pedagogique' ? 'active' : '' ?>">
                <i class="ph-duotone ph-tree-structure me-1"></i> <?= _("Recherche Pédagogique") ?>
              </a>
              <a href="?search_mode=rh" class="btn btn-outline-primary <?= $searchMode === 'rh' ? 'active' : '' ?>">
                <i class="ph-duotone ph-user-list me-1"></i> <?= _("Recherche RH / Paie") ?>
              </a>
            </div>
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

    <!-- Dynamic Filter Form Card -->
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-header bg-transparent py-3">
        <h6 class="mb-0 fw-bold d-flex align-items-center gap-2">
          <i class="ph-duotone ph-funnel text-primary fs-5"></i>
          <span><?= $searchMode === 'pedagogique' ? _("Filtres Pédagogiques Hiérarchiques (Cycle → Niveau → Série → Numéro → Classe)") : _("Filtres RH & Enseignant") ?></span>
        </h6>
      </div>
      <div class="card-body">
        <form method="GET" action="/paie/cahier-texte" id="filterForm">
          <input type="hidden" name="search_mode" value="<?= htmlspecialchars($searchMode) ?>" />

          <div class="row g-2">
            <!-- Période de paie -->
            <div class="col-md-3">
              <label class="form-label text-muted small fw-bold mb-1"><?= _("Période de paie") ?></label>
              <select name="periode_id" class="form-select form-select-sm" onchange="this.form.submit()">
                <?php foreach ($periodes as $p): ?>
                  <option value="<?= $p['id'] ?>" <?= ($periodeId == $p['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($p['code_periode']) ?> (<?= htmlspecialchars($p['date_debut']) ?> au <?= htmlspecialchars($p['date_fin']) ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <?php if ($searchMode === 'pedagogique'): ?>
              <!-- Mode A: Hierarchical Search -->
              <div class="col-md-2">
                <label class="form-label text-muted small fw-bold mb-1"><?= _("Cycle") ?></label>
                <select name="cycle_id" class="form-select form-select-sm" onchange="this.form.submit()">
                  <option value=""><?= _("Tous les cycles") ?></option>
                  <?php foreach ($cycles as $cy): ?>
                    <option value="<?= $cy['id_cycle'] ?>" <?= ($cycleId == $cy['id_cycle']) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($cy['nom_cycle']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="col-md-2">
                <label class="form-label text-muted small fw-bold mb-1"><?= _("Niveau") ?></label>
                <select name="niveau" class="form-select form-select-sm" onchange="this.form.submit()">
                  <option value=""><?= _("Tous les niveaux") ?></option>
                  <?php foreach ($niveaux as $niv): ?>
                    <option value="<?= htmlspecialchars($niv) ?>" <?= ($niveau === $niv) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($niv) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="col-md-2">
                <label class="form-label text-muted small fw-bold mb-1"><?= _("Série") ?></label>
                <select name="serie" class="form-select form-select-sm" onchange="this.form.submit()" <?= empty($series) ? 'disabled' : '' ?>>
                  <option value=""><?= empty($series) ? _("N/A") : _("Toutes") ?></option>
                  <?php foreach ($series as $ser): ?>
                    <option value="<?= htmlspecialchars($ser) ?>" <?= ($serie === $ser) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($ser) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="col-md-1">
                <label class="form-label text-muted small fw-bold mb-1"><?= _("Numéro") ?></label>
                <select name="numero" class="form-select form-select-sm" onchange="this.form.submit()">
                  <option value=""><?= _("Tous") ?></option>
                  <?php foreach ($numeros as $num): ?>
                    <option value="<?= htmlspecialchars($num) ?>" <?= ($numero !== null && (string)$numero === (string)$num) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($num) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="col-md-2">
                <label class="form-label text-muted small fw-bold mb-1"><?= _("Classe") ?></label>
                <select name="classe_id" class="form-select form-select-sm" onchange="this.form.submit()">
                  <option value=""><?= _("Toutes les classes") ?></option>
                  <?php foreach ($classes as $cl): ?>
                    <?php
                      if ($cycleId && $cl['cycle_id'] != $cycleId) continue;
                      if ($niveau && $cl['niveau'] !== $niveau) continue;
                      if ($serie && $cl['serie'] !== $serie) continue;
                      if ($numero !== null && $numero !== '' && (string)$cl['numero'] !== (string)$numero) continue;
                    ?>
                    <option value="<?= $cl['id_classe'] ?>" <?= ($classeId == $cl['id_classe']) ? 'selected' : '' ?>>
                      <?= htmlspecialchars(Classe::getFormattedName($cl)) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="col-md-3 mt-2">
                <label class="form-label text-muted small fw-bold mb-1"><?= _("Enseignant affecté") ?></label>
                <select name="teacher_id" class="form-select form-select-sm" onchange="this.form.submit()">
                  <option value=""><?= _("Tous les enseignants") ?></option>
                  <?php foreach ($teachers as $t): ?>
                    <option value="<?= $t['id_user'] ?>" <?= ($teacherId == $t['id_user']) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($t['nom'] . ' ' . $t['prenom']) ?> (<?= htmlspecialchars($t['identifiant_public'] ?? '') ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

            <?php else: ?>
              <!-- Mode B: RH / Teacher Direct Search -->
              <div class="col-md-4">
                <label class="form-label text-muted small fw-bold mb-1"><?= _("Enseignant / Salarié") ?></label>
                <select name="teacher_id" class="form-select form-select-sm" onchange="this.form.submit()">
                  <option value=""><?= _("Sélectionnez un enseignant") ?></option>
                  <?php foreach ($teachers as $t): ?>
                    <option value="<?= $t['id_user'] ?>" <?= ($teacherId == $t['id_user']) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($t['full_name'] ?? ($t['nom'] . ' ' . $t['prenom'])) ?> (<?= htmlspecialchars($t['identifiant_public'] ?? '') ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="col-md-3">
                <label class="form-label text-muted small fw-bold mb-1"><?= _("Classe concernée") ?></label>
                <select name="classe_id" class="form-select form-select-sm" onchange="this.form.submit()">
                  <option value=""><?= _("Toutes les classes de l'enseignant") ?></option>
                  <?php foreach ($classes as $cl): ?>
                    <option value="<?= $cl['id_classe'] ?>" <?= ($classeId == $cl['id_classe']) ? 'selected' : '' ?>>
                      <?= htmlspecialchars(Classe::getFormattedName($cl)) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            <?php endif; ?>

            <!-- Matière & Limite d'affichage -->
            <div class="col-md-3 mt-2">
              <label class="form-label text-muted small fw-bold mb-1"><?= _("Matière") ?></label>
              <select name="matiere_id" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value=""><?= _("Toutes les matières") ?></option>
                <?php foreach ($matieres as $m): ?>
                  <option value="<?= $m['id_matiere'] ?>" <?= ($matiereId == $m['id_matiere']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($m['nom_matiere']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-2 mt-2">
              <label class="form-label text-muted small fw-bold mb-1"><?= _("Affichage séances") ?></label>
              <select name="limit" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="5" <?= ($limit === 5) ? 'selected' : '' ?>><?= _("5 dernières") ?></option>
                <option value="10" <?= ($limit === 10 || $limit === null) ? 'selected' : '' ?>><?= _("10 dernières") ?></option>
                <option value="20" <?= ($limit === 20) ? 'selected' : '' ?>><?= _("20 dernières") ?></option>
                <option value="all" <?= ($limit === null && isset($_GET['limit']) && $_GET['limit'] === 'all') ? 'selected' : '' ?>><?= _("Toutes les séances") ?></option>
              </select>
            </div>

            <div class="col-md-2 mt-2 d-flex align-items-end">
              <a href="/paie/cahier-texte?search_mode=<?= $searchMode ?>" class="btn btn-sm btn-light-secondary w-100">
                <i class="ph-duotone ph-arrows-counter-clockwise me-1"></i> <?= _("Réinitialiser") ?>
              </a>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Metrics Cards (If teacher selected) -->
    <?php if ($metrics): ?>
      <div class="row g-3 mb-3">
        <div class="col-md-2 col-6">
          <div class="card border-0 shadow-sm text-center py-2">
            <span class="text-muted small fw-bold text-uppercase"><?= _("Heures réalisées") ?></span>
            <h4 class="mb-0 text-dark font-monospace"><?= number_format($metrics['heures_realisees'], 2) ?> h</h4>
          </div>
        </div>
        <div class="col-md-2 col-6">
          <div class="card border-0 shadow-sm text-center py-2">
            <span class="text-success small fw-bold text-uppercase"><?= _("Heures validées") ?></span>
            <h4 class="mb-0 text-success font-monospace"><?= number_format($metrics['heures_validees'], 2) ?> h</h4>
          </div>
        </div>
        <div class="col-md-2 col-6">
          <div class="card border-0 shadow-sm text-center py-2">
            <span class="text-primary small fw-bold text-uppercase"><?= _("Heures payées") ?></span>
            <h4 class="mb-0 text-primary font-monospace"><?= number_format($metrics['heures_payees'], 2) ?> h</h4>
          </div>
        </div>
        <div class="col-md-2 col-6">
          <div class="card border-0 shadow-sm text-center py-2">
            <span class="text-warning small fw-bold text-uppercase"><?= _("À consolider") ?></span>
            <h4 class="mb-0 text-warning font-monospace"><?= number_format($metrics['heures_a_consolider'], 2) ?> h</h4>
          </div>
        </div>
        <div class="col-md-2 col-6">
          <div class="card border-0 shadow-sm text-center py-2">
            <span class="text-muted small fw-bold text-uppercase"><?= _("Montant estimé") ?></span>
            <h5 class="mb-0 text-dark font-monospace"><?= number_format($metrics['montant_estime'], 0, ',', ' ') ?> FCFA</h5>
          </div>
        </div>
        <div class="col-md-2 col-6">
          <div class="card border-0 shadow-sm text-center py-2">
            <span class="text-success small fw-bold text-uppercase"><?= _("Montant payé") ?></span>
            <h5 class="mb-0 text-success font-monospace"><?= number_format($metrics['montant_paye'], 0, ',', ' ') ?> FCFA</h5>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- Main Sessions Table Card -->
    <div class="card border-0 shadow-sm">
      <form action="/paie/cahier-texte/bulk-validate" method="POST" id="bulkForm">
        <div class="card-header bg-transparent py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
          <h5 class="mb-0 d-flex align-items-center gap-2">
            <i class="ph-duotone ph-book-open-text text-primary"></i>
            <span><?= _("Séances du Cahier de Texte & Service Fait") ?></span>
            <span class="badge bg-light-primary text-primary font-monospace ms-1"><?= count($sessions) ?></span>
          </h5>

          <?php if (Auth::can('validate', 'paie')): ?>
            <div class="d-flex align-items-center gap-2">
              <button type="submit" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1">
                <i class="ph-duotone ph-check-circle fs-6"></i>
                <span><?= _("Valider la sélection") ?></span>
              </button>
              <?php if ($teacherId): ?>
                <a href="/paie/bulletins?personnel_id=<?= $teacherId ?>" class="btn btn-sm btn-outline-success d-inline-flex align-items-center gap-1">
                  <i class="ph-duotone ph-receipt fs-6"></i>
                  <span><?= _("Préparer le bulletin") ?></span>
                </a>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>

        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th style="width: 40px;" class="text-center">
                    <input type="checkbox" class="form-check-input" id="checkAll" onclick="toggleCheckAll(this)" />
                  </th>
                  <th><?= _("Date & Heures") ?></th>
                  <th><?= _("Durée") ?></th>
                  <th><?= _("Classe & Matière") ?></th>
                  <th><?= _("Enseignant") ?></th>
                  <th><?= _("Contenu du cours") ?></th>
                  <th><?= _("Statut Service Fait") ?></th>
                  <th class="text-end"><?= _("Action") ?></th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($sessions)): ?>
                  <tr>
                    <td colspan="8" class="text-center py-5 text-muted">
                      <i class="ph-duotone ph-clock-afternoon fs-1 d-block mb-2 text-muted"></i>
                      <?= _("Aucune séance de cours trouvée pour les critères sélectionnés.") ?>
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($sessions as $s): ?>
                    <tr>
                      <td class="text-center">
                        <?php if ($s['status_code'] === 'a_valider'): ?>
                          <input type="checkbox" name="cahier_ids[]" value="<?= $s['cahier_id'] ?>" class="form-check-input session-checkbox" />
                        <?php else: ?>
                          <i class="ph-duotone ph-check text-muted opacity-50"></i>
                        <?php endif; ?>
                      </td>
                      <td>
                        <strong class="d-block"><?= htmlspecialchars($s['date_cours']) ?></strong>
                        <span class="text-muted small">
                          <?= htmlspecialchars($s['heure_debut'] ?? '--') ?> - <?= htmlspecialchars($s['heure_fin'] ?? '--') ?>
                        </span>
                      </td>
                      <td>
                        <span class="badge bg-light-dark text-dark fw-bold font-monospace">
                          <?= number_format($s['calculated_duration'], 2) ?> h
                        </span>
                      </td>
                      <td>
                        <div class="fw-semibold">
                          <?= htmlspecialchars($s['niveau'] . ' ' . ($s['serie'] ?? '') . ' ' . ($s['numero'] ?? '')) ?>
                        </div>
                        <span class="text-muted small"><?= htmlspecialchars($s['nom_matiere']) ?></span>
                      </td>
                      <td>
                        <div class="fw-bold"><?= htmlspecialchars($s['enseignant_nom'] . ' ' . $s['enseignant_prenom']) ?></div>
                        <span class="text-muted small font-monospace"><?= htmlspecialchars($s['enseignant_identifiant'] ?? '') ?></span>
                      </td>
                      <td>
                        <div class="text-truncate" style="max-width: 250px;" title="<?= htmlspecialchars($s['contenu_cours'] ?? '') ?>">
                          <?= htmlspecialchars($s['contenu_cours'] ?? '--') ?>
                        </div>
                      </td>
                      <td>
                        <?php if ($s['status_code'] === 'paye'): ?>
                          <span class="badge bg-light-primary text-primary fw-bold">
                            <i class="ph-duotone ph-check-square me-1"></i> <?= _("Payée") ?> (V<?= $s['bulletin_version'] ?>)
                          </span>
                        <?php elseif ($s['status_code'] === 'valide'): ?>
                          <span class="badge bg-light-success text-success fw-bold">
                            <i class="ph-duotone ph-check-circle me-1"></i> <?= _("Validée pour Paie") ?>
                          </span>
                        <?php elseif ($s['status_code'] === 'refuse'): ?>
                          <span class="badge bg-light-danger text-danger fw-bold">
                            <i class="ph-duotone ph-x-circle me-1"></i> <?= _("Refusée") ?>
                          </span>
                        <?php else: ?>
                          <span class="badge bg-light-warning text-warning fw-bold">
                            <i class="ph-duotone ph-hourglass me-1"></i> <?= _("À valider") ?>
                          </span>
                        <?php endif; ?>
                      </td>
                      <td class="text-end">
                        <?php if ($s['status_code'] === 'a_valider' && Auth::can('validate', 'paie')): ?>
                          <form action="/paie/cahier-texte/validate" method="POST" class="d-inline">
                            <input type="hidden" name="cahier_id" value="<?= $s['cahier_id'] ?>" />
                            <button type="submit" class="btn btn-sm btn-success d-inline-flex align-items-center gap-1">
                              <i class="ph-duotone ph-check fs-6"></i>
                              <span><?= _("Valider") ?></span>
                            </button>
                          </form>
                        <?php else: ?>
                          <button class="btn btn-sm btn-light disabled" disabled>
                            <i class="ph-duotone ph-lock fs-6"></i>
                          </button>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function toggleCheckAll(source) {
  const checkboxes = document.querySelectorAll('.session-checkbox');
  checkboxes.forEach(cb => cb.checked = source.checked);
}
</script>

<?php require_once __DIR__ . '/../../layouts/footer_able.php'; ?>
