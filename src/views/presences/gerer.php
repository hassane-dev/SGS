<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= _('Gestion des Présences') ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><?= htmlspecialchars(Classe::getFormattedName($data['classe'])) ?> - <?= htmlspecialchars($data['date']) ?></h5>
                    </div>
                    <div class="card-body">
                        <form action="/presences/gerer/<?= $data['classe']['id_classe'] ?>" method="GET" class="mb-4">
                            <div class="row align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label"><?= _('Changer de date') ?></label>
                                    <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($data['date']) ?>" onchange="this.form.submit()">
                                </div>
                            </div>
                        </form>

                        <?php if (isset($_GET['success'])): ?>
                            <div class="alert alert-success"><?= _('Présences enregistrées avec succès.') ?></div>
                        <?php endif; ?>

                        <form action="/presences/store" method="POST">
                            <input type="hidden" name="classe_id" value="<?= $data['classe']['id_classe'] ?>">
                            <input type="hidden" name="date_presence" value="<?= $data['date'] ?>">

                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th><?= _('Élève') ?></th>
                                            <th><?= _('Statut') ?></th>
                                            <th><?= _('Commentaire') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($data['eleves'] as $eleve): ?>
                                            <?php $p = $data['presences_map'][$eleve['id_eleve']] ?? null; ?>
                                            <tr>
                                                <td><?= htmlspecialchars($eleve['nom'] . ' ' . $eleve['prenom']) ?></td>
                                                <td>
                                                    <select name="presences[<?= $eleve['id_eleve'] ?>][statut]" class="form-select form-select-sm">
                                                        <option value="present" <?= ($p && $p['statut'] == 'present') ? 'selected' : '' ?>><?= _('Présent') ?></option>
                                                        <option value="absent" <?= ($p && $p['statut'] == 'absent') ? 'selected' : '' ?>><?= _('Absent') ?></option>
                                                        <option value="retard" <?= ($p && $p['statut'] == 'retard') ? 'selected' : '' ?>><?= _('En retard') ?></option>
                                                        <option value="justifie" <?= ($p && $p['statut'] == 'justifie') ? 'selected' : '' ?>><?= _('Justifié') ?></option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="text" name="presences[<?= $eleve['id_eleve'] ?>][commentaire]" class="form-control form-control-sm" value="<?= htmlspecialchars($p['commentaire'] ?? '') ?>">
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary"><?= _('Enregistrer les Présences') ?></button>
                                <a href="/" class="btn btn-link"><?= _('Retour au tableau de bord') ?></a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
