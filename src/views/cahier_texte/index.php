<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fs-2 fw-bold"><?= $is_admin ? _('Consultation du Cahier de Texte') : _('Mon Cahier de Texte') ?></h2>
    <?php if (Auth::get('role_name') === 'enseignant'): ?>
        <a href="/cahier-texte/create" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i> <?= _('Nouvelle Entrée') ?>
        </a>
    <?php endif; ?>
</div>

<?php if ($is_admin): ?>
<div class="card shadow-sm mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0"><?= _('Filtres') ?></h5>
    </div>
    <div class="card-body">
        <form action="/cahier-texte" method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="personnel_id" class="form-label"><?= _('Filtrer par enseignant') ?></label>
                <select name="personnel_id" id="personnel_id" class="form-select">
                    <option value=""><?= _('Tous les enseignants') ?></option>
                    <?php foreach ($teachers as $teacher): ?>
                        <option value="<?= $teacher['id_user'] ?>" <?= ($filters['personnel_id_filter'] == $teacher['id_user']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($teacher['prenom'] . ' ' . $teacher['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="classe_id" class="form-label"><?= _('Filtrer par classe') ?></label>
                <select name="classe_id" id="classe_id" class="form-select">
                    <option value=""><?= _('Toutes les classes') ?></option>
                    <?php foreach ($classes as $classe): ?>
                         <option value="<?= $classe['id_classe'] ?>" <?= ($filters['classe_id_filter'] == $classe['id_classe']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($classe['nom_classe'] . ' ' . $classe['serie']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="date" class="form-label"><?= _('Filtrer par date') ?></label>
                <input type="date" name="date" id="date" value="<?= htmlspecialchars($filters['date_filter'] ?? '') ?>" class="form-control">
            </div>
            <div class="col-md-3 d-flex">
                <button type="submit" class="btn btn-info me-2"><?= _('Filtrer') ?></button>
                <a href="/cahier-texte" class="btn btn-secondary"><?= _('Effacer') ?></a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <table class="table table-hover">
            <thead class="table-light">
                <tr>
                    <th scope="col"><?= _('Date') ?></th>
                    <?php if ($is_admin): ?>
                        <th scope="col"><?= _('Enseignant') ?></th>
                    <?php endif; ?>
                    <th scope="col"><?= _('Classe') ?></th>
                    <th scope="col"><?= _('Matière') ?></th>
                    <th scope="col"><?= _('Contenu') ?></th>
                    <th scope="col" class="text-end"><?= _('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($entries)): ?>
                    <tr>
                        <td colspan="<?= $is_admin ? '6' : '5' ?>" class="text-center p-5">
                            <?= _('Aucune entrée trouvée.') ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($entries as $entry): ?>
                        <tr>
                            <td><?= htmlspecialchars(date('d/m/Y', strtotime($entry['date_cours']))) ?></td>
                            <?php if ($is_admin): ?>
                                <td><?= htmlspecialchars($entry['prenom_personnel'] . ' ' . $entry['nom_personnel']) ?></td>
                            <?php endif; ?>
                            <td><?= htmlspecialchars($entry['nom_classe'] . ' ' . $entry['serie']) ?></td>
                            <td><?= htmlspecialchars($entry['nom_matiere']) ?></td>
                            <td class="text-truncate" style="max-width: 300px;"><?= htmlspecialchars($entry['contenu_cours']) ?></td>
                            <td class="text-end">
                                <?php if ($is_admin || Auth::get('id') == $entry['personnel_id']): ?>
                                    <a href="/cahier-texte/edit?id=<?= $entry['cahier_id'] ?>" class="btn btn-sm btn-outline-primary"><?= _('Modifier') ?></a>
                                    <form action="/cahier-texte/destroy" method="POST" class="d-inline ms-2" onsubmit="return confirm('<?= _('Êtes-vous sûr de vouloir supprimer cette entrée ?') ?>');">
                                        <input type="hidden" name="id" value="<?= $entry['cahier_id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><?= _('Supprimer') ?></button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>