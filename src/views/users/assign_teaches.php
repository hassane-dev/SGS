<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-lg">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= _('Assigner les Cours à') ?>: <?= htmlspecialchars($teacher['prenom'] . ' ' . $teacher['nom']) ?></h1>
        <a href="/users" class="btn btn-secondary"><?= _('Retour à la liste') ?></a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <p><?= _('Cochez les cases pour assigner cet enseignant à une matière dans une classe spécifique.') ?></p>

            <form action="/users/update-assignments" method="POST">
                <input type="hidden" name="teacher_id" value="<?= htmlspecialchars($teacher['id_user']) ?>">

                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-light">
                            <tr>
                                <th scope="col"><?= _('Classe') ?></th>
                                <th scope="col"><?= _('Matières') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($classes as $classe): ?>
                                <tr>
                                    <td class="fw-bold"><?= htmlspecialchars($classe['nom_classe']) ?></td>
                                    <td>
                                        <div class="row g-2">
                                            <?php foreach ($matieres as $matiere):
                                                $assignment_id = $classe['id_classe'] . '-' . $matiere['id_matiere'];
                                                $is_checked = false;
                                                foreach ($assignments as $assignment) {
                                                    if ($assignment['id_classe'] == $classe['id_classe'] && $assignment['id_matiere'] == $matiere['id_matiere']) {
                                                        $is_checked = true;
                                                        break;
                                                    }
                                                }
                                            ?>
                                                <div class="col-md-4 col-sm-6">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="assignments[]" value="<?= $assignment_id ?>" id="assign_<?= $assignment_id ?>" <?= $is_checked ? 'checked' : '' ?>>
                                                        <label class="form-check-label" for="assign_<?= $assignment_id ?>">
                                                            <?= htmlspecialchars($matiere['nom_matiere']) ?>
                                                        </label>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary"><?= _('Enregistrer les assignations') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>