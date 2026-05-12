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
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/">Tableau de Bord</a></li>
                            <li class="breadcrumb-item"><a href="/paiements">Finances</a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= $title ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Liste des élèves en attente de paiement initial</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Élève</th>
                                        <th>Nationalité</th>
                                        <th>Sexe</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($eleves as $e): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-shrink-0">
                                                        <img src="<?= $e['photo'] ?: '/assets/img/default-avatar.png' ?>" alt="user image" class="img-radius wid-40">
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <h6 class="mb-0"><?= htmlspecialchars($e['prenom'] . ' ' . $e['nom']) ?></h6>
                                                        <p class="mb-0 text-muted">Né(e) le <?= date('d/m/Y', strtotime($e['date_naissance'])) ?></p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($e['nationalite']) ?></td>
                                            <td><?= htmlspecialchars($e['sexe']) ?></td>
                                            <td class="text-center">
                                                <a href="/paiements/show/<?= $e['id_eleve'] ?>" class="btn btn-primary btn-sm">
                                                    <i class="ph-duotone ph-receipt me-2"></i>Procéder au paiement
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
