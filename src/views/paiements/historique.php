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
                        <h5>Filtres de recherche</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="/paiements/historique" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Date début</label>
                                <input type="date" name="date_debut" class="form-control" value="<?= $filters['date_debut'] ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Date fin</label>
                                <input type="date" name="date_fin" class="form-control" value="<?= $filters['date_fin'] ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Recherche (Nom, Prénom, Reçu)</label>
                                <input type="text" name="search" class="form-control" placeholder="Ex: Traoré..." value="<?= htmlspecialchars($filters['search']) ?>">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="ph-duotone ph-magnifying-glass me-2"></i>Filtrer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Élève</th>
                                        <th>Classe</th>
                                        <th class="text-end">Total Inscription</th>
                                        <th class="text-end">Total Mensualités</th>
                                        <th class="text-end">Total Versé</th>
                                        <th class="text-center">Dernier Paiement</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($transactions)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-5 text-muted">
                                                <i class="ph-duotone ph-info fs-1 d-block mb-2"></i>
                                                Aucune transaction trouvée pour cette période.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($transactions as $t): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="flex-grow-1">
                                                            <h6 class="mb-0"><?= htmlspecialchars($t['prenom'] . ' ' . $t['nom']) ?></h6>
                                                            <span class="badge bg-light-secondary text-secondary small">Mat: <?= htmlspecialchars($t['identifiant_public'] ?? 'N/A') ?></span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light-primary text-primary">
                                                        <?= htmlspecialchars($t['nom_classe']) ?>
                                                    </span>
                                                </td>
                                                <td class="text-end fw-bold text-dark"><?= number_format($t['total_inscription'], 0, ',', ' ') ?> <small>FCFA</small></td>
                                                <td class="text-end fw-bold text-dark"><?= number_format($t['total_mensualite'], 0, ',', ' ') ?> <small>FCFA</small></td>
                                                <td class="text-end fw-bold text-success"><?= number_format($t['total_paye'], 0, ',', ' ') ?> <small>FCFA</small></td>
                                                <td class="text-center text-muted"><?= date('d/m/Y H:i', strtotime($t['dernier_paiement'])) ?></td>
                                                <td class="text-center">
                                                    <a href="/paiements/show/<?= $t['id_eleve'] ?>" class="btn btn-icon btn-light-primary" title="Voir dossier financier de l'élève">
                                                        <i class="ph-duotone ph-user-circle fs-5"></i>
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