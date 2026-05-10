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
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Liste des élèves en attente de paiement</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nom</th>
                                        <th>Prénom</th>
                                        <th>Classe</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($eleves_en_attente)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center">Aucun élève en attente de paiement.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($eleves_en_attente as $eleve): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($eleve['nom']) ?></td>
                                                <td><?= htmlspecialchars($eleve['prenom']) ?></td>
                                                <td>
                                                    <?= htmlspecialchars($eleve['niveau']) ?>
                                                    <?= !empty($eleve['serie']) ? htmlspecialchars($eleve['serie']) : '' ?>
                                                    <?= htmlspecialchars($eleve['numero']) ?>
                                                </td>
                                                <td>
                                                    <a href="/paiements/show/<?= $eleve['id_eleve'] ?>" class="btn btn-sm btn-primary">
                                                        <i class="ph-duotone ph-currency-dollar"></i> Enregistrer Paiement
                                                    </a>
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
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
