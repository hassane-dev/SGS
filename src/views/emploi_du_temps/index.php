<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h5 class="m-b-10"><?= _("Gestion de l'Emploi du Temps") ?></h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/home"><?= _('Tableau de Bord') ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _('Emploi du Temps') ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <?php if (!empty($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['success_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['error_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <!-- [ Main Content ] start -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h5><?= _('Consulter et Gérer l\'Emploi du Temps') ?></h5>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#swapModal">
                                    <i class="ti ti-arrows-exchange"></i> <?= _('Permuter 2 Cours') ?>
                                </button>
                                <a href="/emploi-du-temps/print?view_mode=<?= urlencode($view_mode) ?>&classe_id=<?= urlencode($view_classe_id ?? '') ?>&professeur_id=<?= urlencode($view_professeur_id ?? '') ?>&salle_id=<?= urlencode($view_salle_id ?? '') ?>" target="_blank" class="btn btn-outline-info btn-sm">
                                    <i class="ti ti-printer"></i> <?= _('Imprimer / PDF') ?>
                                </a>
                                <a href="/emploi-du-temps/create" class="btn btn-primary btn-sm">
                                    <i class="ti ti-plus"></i> <?= _('Ajouter un Cours') ?>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Dynamic Multi-Criteria Filter Bar using Unified SGS Convention -->
                        <form action="/emploi-du-temps" method="GET" class="card p-3 mb-4 bg-light border-0" id="form-filter-edt">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label for="view_mode" class="form-label fw-bold"><?= _('Mode de vue :') ?></label>
                                    <select name="view_mode" id="view_mode" onchange="this.form.submit()" class="form-select form-select-sm">
                                        <option value="classe" <?= ($view_mode === 'classe') ? 'selected' : '' ?>><?= _('Par Classe') ?></option>
                                        <option value="professeur" <?= ($view_mode === 'professeur') ? 'selected' : '' ?>><?= _('Par Enseignant') ?></option>
                                        <option value="salle" <?= ($view_mode === 'salle') ? 'selected' : '' ?>><?= _('Par Salle') ?></option>
                                    </select>
                                </div>

                                <?php if ($view_mode === 'classe'): ?>
                                    <div class="col-md-2">
                                        <label for="cycle_id" class="form-label fw-bold"><?= _('Cycle :') ?></label>
                                        <select name="cycle_id" id="cycle_id" class="form-select form-select-sm">
                                            <option value=""><?= _('Tous les cycles') ?></option>
                                            <?php foreach ($cycles as $cyc): ?>
                                                <option value="<?= $cyc['id'] ?>" <?= ($cycle_id == $cyc['id']) ? 'selected' : '' ?>><?= htmlspecialchars($cyc['nom']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label for="niveau" class="form-label fw-bold"><?= _('Niveau :') ?></label>
                                        <select name="niveau" id="niveau" class="form-select form-select-sm">
                                            <option value=""><?= _('Tous les niveaux') ?></option>
                                            <?php
                                            $niveauxList = array_unique(array_filter(array_column($classes, 'niveau')));
                                            sort($niveauxList);
                                            foreach ($niveauxList as $niv): ?>
                                                <option value="<?= htmlspecialchars($niv) ?>" <?= ($niveau === $niv) ? 'selected' : '' ?>><?= htmlspecialchars($niv) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2" id="group_serie_index" style="display: none;">
                                        <label for="serie" class="form-label fw-bold"><?= _('Série :') ?></label>
                                        <select name="serie" id="serie" class="form-select form-select-sm">
                                            <option value=""><?= _('Toutes les séries') ?></option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="classe_id" class="form-label fw-bold"><?= _('Classe :') ?></label>
                                        <select name="classe_id" id="classe_id" onchange="this.form.submit()" class="form-select form-select-sm">
                                            <?php if (empty($classes)): ?>
                                                <option value=""><?= _('Aucune classe trouvée') ?></option>
                                            <?php else: ?>
                                                <?php foreach ($classes as $classe): ?>
                                                    <?php
                                                    if ($cycle_id && !empty($classe['cycle_id']) && $classe['cycle_id'] != $cycle_id) continue;
                                                    if ($niveau && $classe['niveau'] !== $niveau) continue;
                                                    ?>
                                                    <option value="<?= $classe['id_classe'] ?>" <?= ($view_classe_id == $classe['id_classe']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars(Classe::getFormattedName($classe)) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                <?php elseif ($view_mode === 'professeur'): ?>
                                    <div class="col-md-6">
                                        <label for="professeur_id" class="form-label fw-bold"><?= _('Enseignant :') ?></label>
                                        <select name="professeur_id" id="professeur_id" onchange="this.form.submit()" class="form-select form-select-sm">
                                            <?php foreach ($professeurs as $prof): ?>
                                                <option value="<?= $prof['id_user'] ?>" <?= ($view_professeur_id == $prof['id_user']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($prof['prenom'] . ' ' . $prof['nom']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                <?php elseif ($view_mode === 'salle'): ?>
                                    <div class="col-md-6">
                                        <label for="salle_id" class="form-label fw-bold"><?= _('Salle :') ?></label>
                                        <select name="salle_id" id="salle_id" onchange="this.form.submit()" class="form-select form-select-sm">
                                            <?php foreach ($salles as $salle): ?>
                                                <option value="<?= $salle['id_salle'] ?>" <?= ($view_salle_id == $salle['id_salle']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($salle['nom_salle']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </form>

                        <!-- Timetable Grid Driven Strictly By Recorded Time Intervals -->
                        <?php if (empty($timetable_grid['intervals'])): ?>
                            <div class="alert alert-info text-center my-4" role="alert">
                                <i class="ti ti-info-circle me-1"></i> <?= _("Aucun cours n'est programmé pour le filtre sélectionné.") ?>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle text-center">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 140px;"><?= _('Créneau Horaires') ?></th>
                                            <?php foreach ($timetable_grid['days'] as $day): ?>
                                                <th><?= _($day) ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($timetable_grid['intervals'] as $slot): ?>
                                            <?php $slotKey = $slot['label']; ?>
                                            <tr>
                                                <td class="bg-light fw-bold text-nowrap p-2">
                                                    <i class="ti ti-clock me-1 text-muted"></i><?= htmlspecialchars($slotKey) ?>
                                                </td>
                                                <?php foreach ($timetable_grid['days'] as $day): ?>
                                                    <td>
                                                        <?php if (!empty($timetable_grid['grid'][$slotKey][$day])): ?>
                                                            <?php foreach ($timetable_grid['grid'][$slotKey][$day] as $entry): ?>
                                                                <div class="alert alert-primary p-2 mb-2 text-start position-relative shadow-sm" role="alert">
                                                                    <div class="fw-bold mb-1 text-primary">
                                                                        <i class="ti ti-book me-1"></i><?= htmlspecialchars($entry['nom_matiere']) ?>
                                                                    </div>
                                                                    <div class="small">
                                                                        <strong>Classe :</strong> <?= htmlspecialchars(Classe::getFormattedName($entry)) ?><br>
                                                                        <strong>Enseignant :</strong> <?= htmlspecialchars($entry['prof_prenom'] . ' ' . $entry['prof_nom']) ?><br>
                                                                        <?php if (!empty($entry['nom_salle'])): ?>
                                                                            <span class="badge bg-light-dark text-dark mt-1">
                                                                                <i class="ti ti-building me-1"></i><?= htmlspecialchars($entry['nom_salle']) ?>
                                                                            </span>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                    <div class="mt-2 d-flex justify-content-end gap-1">
                                                                        <a href="/emploi-du-temps/edit?id=<?= $entry['id'] ?>" class="btn btn-sm btn-outline-primary py-0 px-1" title="<?= _('Modifier') ?>">
                                                                            <i class="ti ti-edit"></i>
                                                                        </a>
                                                                        <form action="/emploi-du-temps/destroy" method="POST" onsubmit="return confirm('<?= _('Êtes-vous sûr de vouloir supprimer ce cours ?') ?>');" class="d-inline">
                                                                            <input type="hidden" name="id" value="<?= $entry['id'] ?>">
                                                                            <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1" title="<?= _('Supprimer') ?>">&times;</button>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                    </td>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ Main Content ] end -->
    </div>
</div>

<!-- Modal Swap Courses -->
<div class="modal fade" id="swapModal" tabindex="-1" aria-labelledby="swapModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="/emploi-du-temps/swap" method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="swapModalLabel"><i class="ti ti-arrows-exchange me-1"></i><?= _('Permuter 2 Cours') ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-3"><?= _("La permutation échange de manière atomique le créneau horaire, le jour et la salle entre les deux cours sélectionnés.") ?></p>

                    <div class="mb-3">
                        <label for="id1" class="form-label fw-bold"><?= _('Premier cours :') ?></label>
                        <select name="id1" id="id1" class="form-select" required>
                            <option value=""><?= _('-- Sélectionner le cours 1 --') ?></option>
                            <?php foreach ($timetable_grid['raw_entries'] as $re): ?>
                                <option value="<?= $re['id'] ?>">
                                    <?= htmlspecialchars($re['jour'] . ' ' . substr($re['heure_debut'], 0, 5) . '-' . substr($re['heure_fin'], 0, 5) . ' | ' . $re['nom_matiere'] . ' (' . Classe::getFormattedName($re) . ')') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="id2" class="form-label fw-bold"><?= _('Second cours :') ?></label>
                        <select name="id2" id="id2" class="form-select" required>
                            <option value=""><?= _('-- Sélectionner le cours 2 --') ?></option>
                            <?php foreach ($timetable_grid['raw_entries'] as $re): ?>
                                <option value="<?= $re['id'] ?>">
                                    <?= htmlspecialchars($re['jour'] . ' ' . substr($re['heure_debut'], 0, 5) . '-' . substr($re['heure_fin'], 0, 5) . ' | ' . $re['nom_matiere'] . ' (' . Classe::getFormattedName($re) . ')') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= _('Annuler') ?></button>
                    <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i><?= _('Confirmer la permutation') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cycleSelect = document.getElementById('cycle_id');
    const niveauSelect = document.getElementById('niveau');
    const groupSerie = document.getElementById('group_serie_index');
    const serieSelect = document.getElementById('serie');
    const classeSelect = document.getElementById('classe_id');

    if (cycleSelect && niveauSelect) {
        cycleSelect.addEventListener('change', function() {
            const cycleId = this.value;
            if (!cycleId) {
                this.form.submit();
                return;
            }
            fetch(`/affectations-pedagogiques/get-niveaux?cycle_id=${cycleId}`)
                .then(res => res.json())
                .then(data => {
                    niveauSelect.innerHTML = '<option value="">Tous les niveaux</option>';
                    data.forEach(niv => {
                        niveauSelect.innerHTML += `<option value="${niv}">${niv}</option>`;
                    });
                });
        });
    }

    if (niveauSelect) {
        niveauSelect.addEventListener('change', function() {
            const niveau = this.value;
            const cycleId = cycleSelect ? cycleSelect.value : '';
            if (!niveau) {
                this.form.submit();
                return;
            }
            fetch(`/affectations-pedagogiques/get-series?niveau=${encodeURIComponent(niveau)}&cycle_id=${cycleId}`)
                .then(res => res.json())
                .then(series => {
                    if (groupSerie && series && series.length > 0) {
                        groupSerie.style.display = 'block';
                        serieSelect.innerHTML = '<option value="">Toutes les séries</option>';
                        series.forEach(s => {
                            serieSelect.innerHTML += `<option value="${s}">${s}</option>`;
                        });
                    } else if (groupSerie) {
                        groupSerie.style.display = 'none';
                    }
                });
        });
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
