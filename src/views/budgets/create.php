<?php
// src/views/budgets/create.php
include __DIR__ . '/../layouts/header_able.php';
?>

<div class="pc-container">
    <div class="pc-content">
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/home">Accueil</a></li>
                            <li class="breadcrumb-item"><a href="/budgets">Pilotage Budgétaire</a></li>
                            <li class="breadcrumb-item" aria-current="page">Nouveau Budget</li>
                        </ul>
                    </div>
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0">Initialiser un Budget Annuel</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8 col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Configuration du budget</h5>
                    </div>
                    <div class="card-body">
                        <form action="/budgets/store" method="POST">
                            <div class="mb-3">
                                <label class="form-label" for="exercice_financier_id">Exercice Financier Principal <span class="text-danger">*</span></label>
                                <select class="form-select" id="exercice_financier_id" name="exercice_financier_id" required>
                                    <option value="">-- Sélectionner l'exercice --</option>
                                    <?php foreach ($exercices as $ef): ?>
                                        <option value="<?= $ef['id'] ?>"><?= htmlspecialchars($ef['libelle']) ?> (<?= date('d/m/Y', strtotime($ef['date_debut'])) ?> - <?= date('d/m/Y', strtotime($ef['date_fin'])) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Le budget sera relié à cet exercice. Un seul budget peut être créé par exercice.</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="libelle">Libellé du Budget <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="libelle" name="libelle" placeholder="Ex: Budget Général de Fonctionnement 2024" required>
                            </div>

                            <div class="d-flex justify-content-end gap-2 mt-4">
                                <a href="/budgets" class="btn btn-light">Annuler</a>
                                <button type="submit" class="btn btn-primary d-inline-flex align-items-center">
                                    <i class="ph-duotone ph-floppy-disk me-2"></i> Enregistrer le Brouillon
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer_able.php'; ?>