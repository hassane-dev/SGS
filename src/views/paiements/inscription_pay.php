<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0">Régularisation Inscription : <?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></h2>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/">Accueil</a></li>
                            <li class="breadcrumb-item"><a href="/paiements/restes">Gestion des restes</a></li>
                            <li class="breadcrumb-item" aria-current="page">Régulariser</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <form action="/paiements/process-payment/<?= $eleve['id_eleve'] ?>" method="POST">
            <div class="row">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>Détails de la dette d'inscription</h5>
                        </div>
                        <div class="card-body">
                            <div class="bg-light-danger p-4 rounded mb-4">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Total Inscription + Options :</span>
                                    <span class="fw-bold"><?= number_format($fraisInscription['total'], 0, ',', ' ') ?> FCFA</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2 text-success">
                                    <span>Déjà payé :</span>
                                    <span class="fw-bold"><?= number_format($fraisInscription['verse'], 0, ',', ' ') ?> FCFA</span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between text-danger">
                                    <span class="h5 mb-0">Reste à payer :</span>
                                    <span class="h5 mb-0 fw-bold"><?= number_format($fraisInscription['reste'], 0, ',', ' ') ?> FCFA</span>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Montant de la régularisation</label>
                                <div class="input-group input-group-lg">
                                    <input type="number" name="montant_inscription" class="form-control text-primary fw-bold" placeholder="Entrez le montant..." max="<?= $fraisInscription['reste'] ?>" required>
                                    <span class="input-group-text">FCFA</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h5 class="text-white mb-4">Informations de paiement</h5>
                            <div class="mb-3">
                                <label class="form-label text-white">Mode de règlement</label>
                                <select name="mode_paiement" class="form-select border-0">
                                    <option value="Espèces">Espèces</option>
                                    <option value="Chèque">Chèque</option>
                                    <option value="Virement">Virement</option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="form-label text-white">Référence transaction</label>
                                <input type="text" name="reference_transaction" class="form-control border-0" value="<?= $nextRecu ?>">
                            </div>
                            <button type="submit" class="btn btn-light text-primary w-100 fw-bold py-3">
                                <i class="ph-duotone ph-check-circle me-2"></i>Valider la régularisation
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
