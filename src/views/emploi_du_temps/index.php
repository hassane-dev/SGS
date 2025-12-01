<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h5 class="m-b-10">Gestion de l'Emploi du Temps</h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/home">Tableau de Bord</a></li>
                            <li class="breadcrumb-item" aria-current="page">Emploi du Temps</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- [ Main Content ] start -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5>Emploi du Temps</h5>
                            <a href="/emploi-du-temps/create" class="btn btn-primary btn-sm">
                                <i class="ti ti-plus"></i> Ajouter un Cours
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Filter Bar -->
                        <div class="mb-4">
                            <form action="/emploi-du-temps" method="GET" class="row g-3 align-items-center">
                                <div class="col-auto">
                                    <label for="classe_id" class="form-label">Voir l'emploi du temps pour la classe :</label>
                                </div>
                                <div class="col-auto">
                                    <select name="classe_id" id="classe_id" onchange="this.form.submit()" class="form-select form-select-sm" style="width: 200px;">
                                        <?php if (empty($classes)): ?>
                                            <option>Aucune classe trouvée</option>
                                        <?php else: ?>
                                            <?php foreach ($classes as $classe): ?>
                                                <option value="<?= $classe['id_classe'] ?>" <?= ($view_classe_id == $classe['id_classe']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($classe['nom_classe']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </form>
                        </div>

                        <!-- Timetable -->
                        <div class="table-responsive">
                            <table class="table table-bordered text-center">
                                <thead>
                                    <tr>
                                        <th style="width: 100px;">Heure</th>
                                        <?php $days = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi']; ?>
                                        <?php foreach ($days as $day): ?>
                                            <th><?= _($day) ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($timetable_grid as $hour => $row): ?>
                                        <tr>
                                            <td><strong><?= $hour ?></strong></td>
                                            <?php foreach ($days as $day): ?>
                                                <td>
                                                    <?php if (isset($row[$day]) && $entry = $row[$day]): ?>
                                                        <div class="alert alert-primary p-2" role="alert">
                                                            <strong><?= htmlspecialchars($entry['nom_matiere']) ?></strong><br>
                                                            <small><?= htmlspecialchars($entry['prof_prenom'] . ' ' . $entry['prof_nom']) ?></small><br>
                                                            <em class="text-muted" style="font-size: 0.8em;"><?= htmlspecialchars($entry['nom_salle']) ?></em>
                                                            <form action="/emploi-du-temps/destroy" method="POST" onsubmit="return confirm('<?= _('Are you sure?') ?>');" class="d-inline">
                                                                <input type="hidden" name="id" value="<?= $entry['id'] ?>">
                                                                <button type="submit" class="btn btn-danger btn-sm p-0" style="line-height: 1; width: 20px; height: 20px; float: right;">&times;</button>
                                                            </form>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ Main Content ] end -->
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
