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
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Élève</th>
                                        <th>Lycée</th>
                                        <th>Date d'inscription</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($eleves as $e): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($e['prenom'] . ' ' . $e['nom']) ?></td>
                                            <td><?= htmlspecialchars($e['lycee_id']) ?></td>
                                            <td><?= date('d/m/Y', strtotime($e['date_naissance'])) ?></td>
                                            <td class="text-center">
                                                <a href="/paiements/show/<?= $e['id_eleve'] ?>" class="btn btn-primary btn-sm">
                                                    Procéder au paiement
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($eleves)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center">Aucun élève en attente de paiement.</td>
                                        </tr>
                                    <?php endif; ?>
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
