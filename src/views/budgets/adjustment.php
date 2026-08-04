<?php
// src/views/budgets/adjustment.php
include __DIR__ . '/../layouts/header_able.php';
?>

<div class="pc-container">
    <div class="pc-content">
        <!-- [ Breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/home">Accueil</a></li>
                            <li class="breadcrumb-item"><a href="/budgets">Pilotage Budgétaire</a></li>
                            <li class="breadcrumb-item" aria-current="page">Ajustements Budgétaires</li>
                        </ul>
                    </div>
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0">Ajustements et Virements de Crédits</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ Breadcrumb ] end -->

        <?php if (!empty($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="ph-duotone ph-check-circle me-2 fs-5"></i>
                <?= htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="ph-duotone ph-warning me-2 fs-5"></i>
                <?= htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Form Card -->
            <?php if (Auth::can('adjust', 'budget') || Auth::can('transfer', 'budget')): ?>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5>Enregistrer un Ajustement</h5>
                        </div>
                        <div class="card-body">
                            <form action="/budgets/adjustment/store" method="POST">
                                <div class="mb-3">
                                    <label class="form-label" for="type_ajustement">Type d'Ajustement <span class="text-danger">*</span></label>
                                    <select class="form-select" id="type_ajustement" name="type_ajustement" onchange="toggleSourceLine(this.value);" required>
                                        <option value="dotation_supplementaire">Dotation supplémentaire d'urgence</option>
                                        <option value="transfert">Virement / Transfert entre lignes</option>
                                    </select>
                                </div>

                                <div class="mb-3" id="source_line_wrapper" style="display: none;">
                                    <label class="form-label" for="ligne_source_id">Ligne Source (Débitée) <span class="text-danger">*</span></label>
                                    <select class="form-select" id="ligne_source_id" name="ligne_source_id">
                                        <option value="">-- Sélectionner la ligne --</option>
                                        <?php foreach ($lines as $line): ?>
                                            <option value="<?= $line['id'] ?>"><?= htmlspecialchars($line['budget_libelle']) ?> - <?= htmlspecialchars($line['nom_categorie']) ?> <?= !empty($line['nom_centre']) ? '('.htmlspecialchars($line['nom_centre']).')' : '' ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="ligne_destination_id">Ligne Destination (Créditée) <span class="text-danger">*</span></label>
                                    <select class="form-select" id="ligne_destination_id" name="ligne_destination_id" required>
                                        <option value="">-- Sélectionner la ligne --</option>
                                        <?php foreach ($lines as $line): ?>
                                            <option value="<?= $line['id'] ?>"><?= htmlspecialchars($line['budget_libelle']) ?> - <?= htmlspecialchars($line['nom_categorie']) ?> <?= !empty($line['nom_centre']) ? '('.htmlspecialchars($line['nom_centre']).')' : '' ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="montant">Montant à Ajuster (FCFA) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0.01" class="form-control" id="montant" name="montant" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="motif">Justification Obligatoire <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="motif" name="motif" rows="3" placeholder="Saisir la raison et justification réglementaire de cet ajustement..." required></textarea>
                                </div>

                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary w-100 d-inline-flex align-items-center justify-content-center">
                                        <i class="ph-duotone ph-floppy-disk me-2"></i> Valider l'Ajustement
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Traceability List -->
            <div class="<?= (Auth::can('adjust', 'budget') || Auth::can('transfer', 'budget')) ? 'col-md-8' : 'col-md-12' ?>">
                <div class="card">
                    <div class="card-header">
                        <h5>Historique Immuable des Ajustements</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Type d'opération</th>
                                        <th>Imputation Source / Dest.</th>
                                        <th>Montant</th>
                                        <th>Auteur / Justification</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($adjustments)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">
                                                Aucun ajustement enregistré.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($adjustments as $adj): ?>
                                            <tr>
                                                <td><?= date('d/m/Y H:i', strtotime($adj['date_ajustement'])) ?></td>
                                                <td>
                                                    <?php if ($adj['type_ajustement'] === 'transfert'): ?>
                                                        <span class="badge bg-light-warning text-warning">Virement de Crédit</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-light-success text-success">Dotation Supplémentaire</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($adj['type_ajustement'] === 'transfert'): ?>
                                                        <span class="text-danger"><i class="ph-duotone ph-arrow-up-right"></i> <?= htmlspecialchars($adj['src_cat_nom']) ?></span>
                                                        <br>
                                                    <?php endif; ?>
                                                    <span class="text-success"><i class="ph-duotone ph-arrow-down-left"></i> <?= htmlspecialchars($adj['dst_cat_nom']) ?></span>
                                                </td>
                                                <td class="fw-bold"><?= number_format($adj['montant'], 0, ',', ' ') ?> FCFA</td>
                                                <td>
                                                    <span class="text-dark fw-bold"><?= htmlspecialchars($adj['user_nom']) ?> <?= htmlspecialchars($adj['user_prenom']) ?></span>
                                                    <p class="text-muted mb-0" style="max-width: 250px; font-size: 0.85rem;"><?= htmlspecialchars($adj['motif']) ?></p>
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

<script>
function toggleSourceLine(type) {
    var wrapper = document.getElementById('source_line_wrapper');
    var srcInput = document.getElementById('ligne_source_id');
    if (type === 'transfert') {
        wrapper.style.display = 'block';
        srcInput.required = true;
    } else {
        wrapper.style.display = 'none';
        srcInput.required = false;
        srcInput.value = '';
    }
}
</script>

<?php include __DIR__ . '/../layouts/footer_able.php'; ?>