<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800">Détails de la Classe: <?= htmlspecialchars($classe['nom_classe']) ?></h1>
    <p class="mb-4">
        Niveau: <?= htmlspecialchars($classe['niveau']) ?> | Série: <?= htmlspecialchars($classe['serie'] ?? 'N/A') ?>
    </p>

    <div class="row">
        <!-- Assign Subjects & Supervisors Column -->
        <div class="col-lg-4">
            <!-- Assign Subjects Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Attribuer une Matière</h6>
                </div>
                <div class="card-body">
                    <?php if (Auth::can('edit', 'class')): ?>
                    <form action="/classes/assignMatiere" method="POST">
                        <input type="hidden" name="classe_id" value="<?= $classe['id_classe'] ?>">
                        <div class="form-group">
                            <label for="matiere_id">Matière</label>
                            <select name="matiere_id" id="matiere_id" class="form-control" required>
                                <option value="">-- Choisir une matière --</option>
                                <?php
                                $assigned_ids = array_column($assigned_matieres, 'id_matiere');
                                foreach ($all_matieres as $matiere):
                                    if (!in_array($matiere['id_matiere'], $assigned_ids)): ?>
                                        <option value="<?= $matiere['id_matiere'] ?>"><?= htmlspecialchars($matiere['nom_matiere']) ?></option>
                                <?php endif; endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="coefficient">Coefficient</label>
                            <input type="number" step="0.25" name="coefficient" id="coefficient" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="statut">Statut</label>
                            <select name="statut" id="statut" class="form-control" required>
                                <option value="obligatoire">Obligatoire</option>
                                <option value="optionnelle">Optionnelle</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary mt-3">Ajouter la Matière</button>
                    </form>
                    <?php else: ?>
                        <p>Vous n'avez pas la permission d'attribuer des matières.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Assign Supervisor Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Assigner un Surveillant</h6>
                </div>
                <div class="card-body">
                    <?php if (Auth::can('edit', 'class')): ?>
                    <form action="/classes/assignSupervisor" method="POST">
                        <input type="hidden" name="classe_id" value="<?= $classe['id_classe'] ?>">
                        <div class="form-group">
                            <label for="surveillant_id">Surveillant</label>
                            <select name="surveillant_id" id="surveillant_id" class="form-control" required>
                                <option value="">-- Choisir un surveillant --</option>
                                <?php foreach ($all_supervisors as $supervisor): ?>
                                    <option value="<?= $supervisor['id_user'] ?>"><?= htmlspecialchars($supervisor['prenom'] . ' ' . $supervisor['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary mt-3">Assigner le Surveillant</button>
                    </form>
                    <hr>
                    <h6>Surveillants Actuels:</h6>
                    <ul class="list-group">
                        <?php foreach ($assigned_supervisors as $supervisor): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?= htmlspecialchars($supervisor['prenom'] . ' ' . $supervisor['nom']) ?>
                                <!-- Optional: Add a button to unassign -->
                            </li>
                        <?php endforeach; ?>
                         <?php if (empty($assigned_supervisors)): ?>
                            <li class="list-group-item">Aucun surveillant assigné</li>
                        <?php endif; ?>
                    </ul>
                    <?php else: ?>
                        <p>Vous n'avez pas la permission d'assigner des surveillants.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Assigned Subjects List -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Matières et Enseignants</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Matière</th>
                                    <th>Coefficient</th>
                                    <th>Enseignant et Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assigned_matieres as $matiere): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($matiere['nom_matiere']) ?></td>
                                        <td><?= htmlspecialchars($matiere['coefficient']) ?></td>
                                        <td>
                                            <?php if (isset($teacher_assignments[$matiere['id_matiere']])):
                                                $assignment = $teacher_assignments[$matiere['id_matiere']];
                                                $status_badge = '';
                                                switch ($assignment['statut']) {
                                                    case 'valide':
                                                        $status_badge = '<span class="badge badge-success">Validé</span>';
                                                        break;
                                                    case 'refuse':
                                                        $status_badge = '<span class="badge badge-danger">Refusé</span>';
                                                        break;
                                                    case 'en_attente':
                                                    default:
                                                        $status_badge = '<span class="badge badge-warning">En attente</span>';
                                                        break;
                                                }
                                            ?>
                                                <div><?= htmlspecialchars($assignment['enseignant_nom']) . ' ' . $status_badge ?></div>

                                                <?php if ($assignment['statut'] == 'en_attente' && Auth::can('validate', 'assignment')): ?>
                                                    <div class="mt-2">
                                                        <a href="/classes/approveAssignment?assignment_id=<?= $assignment['id'] ?>&classe_id=<?= $classe['id_classe'] ?>" class="btn btn-sm btn-success" title="Valider l'attribution"><i class="fas fa-check"></i></a>
                                                        <a href="/classes/rejectAssignment?assignment_id=<?= $assignment['id'] ?>&classe_id=<?= $classe['id_classe'] ?>" class="btn btn-sm btn-danger" title="Refuser l'attribution"><i class="fas fa-times"></i></a>
                                                    </div>
                                                <?php endif; ?>

                                            <?php else: ?>
                                                <?php if (Auth::can('edit', 'class')): ?>
                                                <form action="/classes/assignEnseignant" method="POST" class="d-flex">
                                                    <input type="hidden" name="classe_id" value="<?= $classe['id_classe'] ?>">
                                                    <input type="hidden" name="matiere_id" value="<?= $matiere['id_matiere'] ?>">
                                                    <select name="enseignant_id" class="form-control form-control-sm" required>
                                                        <option value="">-- Assigner --</option>
                                                        <?php foreach ($enseignants as $enseignant): ?>
                                                            <option value="<?= $enseignant['id_user'] ?>"><?= htmlspecialchars($enseignant['full_name']) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <button type="submit" class="btn btn-sm btn-primary ml-2" title="Soumettre pour validation"><i class="fas fa-paper-plane"></i></button>
                                                </form>
                                                <?php else: ?>
                                                    <span class="text-muted">Non assigné</span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (Auth::can('edit', 'class')): ?>
                                                <a href="/classes/removeMatiere?id=<?= $matiere['id'] ?>&classe_id=<?= $classe['id_classe'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir retirer cette matière de la classe ?');" title="Retirer la matière">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
