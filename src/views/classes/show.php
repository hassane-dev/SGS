<div class="container-fluid">
    <h1 class="h3 mb-2 text-gray-800">Détails de la Classe: <?= htmlspecialchars($classe['nom_classe']) ?></h1>
    <p class="mb-4">
        Niveau: <?= htmlspecialchars($classe['niveau']) ?> | Série: <?= htmlspecialchars($classe['serie'] ?? 'N/A') ?>
    </p>

    <div class="row">
        <!-- Assign Subjects Card -->
        <div class="col-lg-4">
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
                                    <th>Enseignant</th>
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
                                            ?>
                                                <span><?= htmlspecialchars($assignment['enseignant_nom']) ?></span>

                                                <?php if (Auth::can('manage_settings', 'evaluation')): ?>
                                                <a href="/evaluations/settings?classe_id=<?= $classe['id_classe'] ?>&matiere_id=<?= $matiere['id_matiere'] ?>" class="btn btn-sm btn-outline-secondary ml-2" title="Paramètres des évaluations">
                                                    <i class="fas fa-cog"></i>
                                                </a>
                                                <?php endif; ?>

                                                <?php if (Auth::can('edit', 'class')): ?>
                                                <a href="/classes/unassignEnseignant?assignment_id=<?= $assignment['id'] ?>&classe_id=<?= $classe['id_classe'] ?>" class="btn btn-sm btn-outline-danger" title="Dissocier l'enseignant">
                                                    <i class="fas fa-times"></i>
                                                </a>
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
                                                    <button type="submit" class="btn btn-sm btn-success ml-2"><i class="fas fa-check"></i></button>
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