<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= $title ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Ajouter une Série</h5>
                    </div>
                    <div class="card-body">
                        <form action="/series/store" method="POST">
                            <div class="mb-3">
                                <label class="form-label">Nom de la Série</label>
                                <input type="text" name="nom_serie" class="form-control" placeholder="Ex: A1, C, D, G2..." required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Catégorie</label>
                                <select name="categorie" class="form-select" required>
                                    <option value="Scientifique">Scientifique</option>
                                    <option value="Littéraire">Littéraire</option>
                                    <option value="Technique">Technique</option>
                                    <option value="Autre">Autre</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Enregistrer</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Série</th>
                                        <th>Catégorie</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($series as $s): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($s['nom_serie']) ?></td>
                                            <td><?= htmlspecialchars($s['categorie']) ?></td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-icon btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $s['id'] ?>">
                                                    <i class="ti ti-edit"></i>
                                                </button>
                                                <form action="/series/destroy" method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette série ?');">
                                                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-icon btn-outline-danger">
                                                        <i class="ti ti-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>

                                        <!-- Modal Edit -->
                                        <div class="modal fade" id="editModal<?= $s['id'] ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Modifier la Série</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <form action="/series/update" method="POST">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                                            <div class="mb-3">
                                                                <label class="form-label">Nom de la Série</label>
                                                                <input type="text" name="nom_serie" class="form-control" value="<?= htmlspecialchars($s['nom_serie']) ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Catégorie</label>
                                                                <select name="categorie" class="form-select" required>
                                                                    <option value="Scientifique" <?= $s['categorie'] == 'Scientifique' ? 'selected' : '' ?>>Scientifique</option>
                                                                    <option value="Littéraire" <?= $s['categorie'] == 'Littéraire' ? 'selected' : '' ?>>Littéraire</option>
                                                                    <option value="Technique" <?= $s['categorie'] == 'Technique' ? 'selected' : '' ?>>Technique</option>
                                                                    <option value="Autre" <?= $s['categorie'] == 'Autre' ? 'selected' : '' ?>>Autre</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                            <button type="submit" class="btn btn-primary">Mettre à jour</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
