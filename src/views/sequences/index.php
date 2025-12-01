<?php
require_once __DIR__ . '/../layouts/header_able.php';
require_once __DIR__ . '/../layouts/sidebar_able.php';
?>

<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h5 class="m-b-10">Gestion des Séquences</h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/home">Tableau de Bord</a></li>
                            <li class="breadcrumb-item" aria-current="page">Séquences Annuelles</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- [ Main Content ] start -->
        <div class="row">
            <!-- [ sample-page ] start -->
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Liste des Séquences pour l'année en cours</h5>
                        <?php if (Auth::can('manage', 'sequence')): ?>
                            <a href="/sequences/create" class="btn btn-primary btn-sm">
                                <i class="ti ti-plus"></i> Nouvelle Séquence
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['success_message'])): ?>
                            <div class="alert alert-success" role="alert">
                                <?= $_SESSION['success_message'] ?>
                            </div>
                            <?php unset($_SESSION['success_message']); ?>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['error_message'])): ?>
                            <div class="alert alert-danger" role="alert">
                                <?= $_SESSION['error_message'] ?>
                            </div>
                            <?php unset($_SESSION['error_message']); ?>
                        <?php endif; ?>

                        <div class="table-responsive">
                            <table class="table table-hover">
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
                                    <?php if (empty($sequences)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center">Aucune séquence trouvée pour l'année académique en cours.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($sequences as $sequence): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($sequence['nom']) ?></td>
                                                <td><?= ucfirst(htmlspecialchars($sequence['type'] === 'trimestrielle' ? 'Trimestre' : 'Semestre')) ?></td>
                                                <td><?= htmlspecialchars(date('d/m/Y', strtotime($sequence['date_debut']))) ?></td>
                                                <td><?= htmlspecialchars(date('d/m/Y', strtotime($sequence['date_fin']))) ?></td>
                                                <td>
                                                    <span class="badge bg-light-<?= $sequence['statut'] === 'ouverte' ? 'success' : 'danger' ?>">
                                                        <?= ucfirst(htmlspecialchars($sequence['statut'])) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if (Auth::can('manage', 'sequence')): ?>
                                                        <a href="/sequences/edit?id=<?= $sequence['id'] ?>" class="btn btn-sm btn-light-warning" title="Modifier">
                                                            <i class="ti ti-edit"></i>
                                                        </a>
                                                        <form action="/sequences/destroy" method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette séquence ?');">
                                                            <input type="hidden" name="id" value="<?= $sequence['id'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-light-danger" title="Supprimer">
                                                                <i class="ti ti-trash"></i>
                                                            </button>
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
                </div>
            </div>
            <!-- [ sample-page ] end -->
        </div>
        <!-- [ Main Content ] end -->
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
