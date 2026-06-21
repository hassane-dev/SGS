<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title"><h2 class="mb-0"><?= $title ?></h2></div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/">Accueil</a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= $title ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col">
                                <h5>Liste des Reçus émis</h5>
                            </div>
                            <div class="col-auto">
                                <form method="GET" action="/paiements/recus" class="d-flex">
                                    <input type="text" name="search" class="form-control me-2" placeholder="N° Reçu ou Nom..." value="<?= htmlspecialchars($search) ?>">
                                    <button type="submit" class="btn btn-primary">Chercher</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>N° Reçu</th>
                                        <th>Date d'émission</th>
                                        <th>Élève</th>
                                        <th>Nature des paiements</th>
                                        <th class="text-end">Montant Total</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recus)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-5 text-muted">
                                                <i class="ph-duotone ph-receipt fs-1 d-block mb-2"></i>
                                                Aucun reçu trouvé.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recus as $r): ?>
                                            <tr>
                                                <td><span class="badge bg-light-dark text-dark fs-6"><?= $r['recu_numero'] ?></span></td>
                                                <td><?= date('d/m/Y H:i', strtotime($r['date'])) ?></td>
                                                <td class="fw-bold"><?= htmlspecialchars($r['nom'] . ' ' . $r['prenom']) ?></td>
                                                <td>
                                                    <small class="text-muted"><?= htmlspecialchars($r['types']) ?></small>
                                                </td>
                                                <td class="text-end fw-bold text-primary"><?= number_format($r['montant_total'], 0, ',', ' ') ?> <small>FCFA</small></td>
                                                <td class="text-center">
                                                    <a href="/recu/print?numero=<?= $r['recu_numero'] ?>" target="_blank" class="btn btn-icon btn-primary" title="Imprimer le reçu">
                                                        <i class="ph-duotone ph-printer"></i>
                                                    </a>
                                                    <a href="/paiements/show/<?= $r['id_eleve'] ?>" class="btn btn-icon btn-light-secondary" title="Historique élève">
                                                        <i class="ph-duotone ph-clock-counter-clockwise"></i>
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
