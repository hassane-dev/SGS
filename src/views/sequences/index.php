<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Gestion des Séquences</h1>
        <?php if (Auth::can('sequence:manage')): ?>
            <a href="/sequences/create" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nouvelle Séquence
            </a>
        <?php endif; ?>
    </div>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'delete_failed'): ?>
        <div class="alert alert-danger">
            La suppression a échoué. La séquence est probablement utilisée dans des évaluations.
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Liste des Séquences pour l'année en cours</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Type</th>
                            <th>Date de Début</th>
                            <th>Date de Fin</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sequences as $sequence): ?>
                            <tr>
                                <td><?= htmlspecialchars($sequence['nom']) ?></td>
                                <td><?= ucfirst(htmlspecialchars($sequence['type'])) ?></td>
                                <td><?= htmlspecialchars($sequence['date_debut']) ?></td>
                                <td><?= htmlspecialchars($sequence['date_fin']) ?></td>
                                <td>
                                    <span class="badge badge-<?= $sequence['statut'] === 'ouverte' ? 'success' : 'danger' ?>">
                                        <?= ucfirst(htmlspecialchars($sequence['statut'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (Auth::can('sequence:manage')): ?>
                                        <a href="/sequences/edit?id=<?= $sequence['id'] ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="/sequences/destroy" method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette séquence ?');">
                                            <input type="hidden" name="id" value="<?= $sequence['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
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