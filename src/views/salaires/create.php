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
                            <li class="breadcrumb-item"><a href="/salaires">Salaires</a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= $title ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h5>Détails du Paiement</h5>
                    </div>
                    <div class="card-body">
                        <form action="/salaires/store" method="POST">
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Membre du Personnel</label>
                                    <select name="personnel_id" class="form-select" required>
                                        <option value="">Sélectionner un employé...</option>
                                        <?php foreach ($personnels as $personnel): ?>
                                            <option value="<?= $personnel['id_user'] ?>"><?= htmlspecialchars($personnel['prenom'] . ' ' . $personnel['nom']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Mois</label>
                                    <input type="number" name="mois" class="form-control" value="<?= date('m') ?>" min="1" max="12" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Année</label>
                                    <input type="number" name="annee" class="form-control" value="<?= date('Y') ?>" required>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Date de Paiement</label>
                                    <input type="date" name="date_paiement" class="form-control" value="<?= date('Y-m-d') ?>">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Montant Brut</label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" name="montant_brut" class="form-control" placeholder="0.00">
                                        <span class="input-group-text">FCFA</span>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Montant Net</label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" name="montant_net" class="form-control" placeholder="0.00" required>
                                        <span class="input-group-text">FCFA</span>
                                    </div>
                                </div>
                            </div>

                            <div class="text-end mt-4">
                                <a href="/salaires" class="btn btn-light-secondary me-2">Annuler</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="ph-duotone ph-floppy-disk me-2"></i>Enregistrer le Paiement
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
