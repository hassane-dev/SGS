<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= _('Gestion des Restes / Dettes') ?></h2>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/home"><?= _('Tableau de Bord') ?></a></li>
                            <li class="breadcrumb-item"><a href="/paiements"><?= _('Comptabilité') ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _('Gestion des restes') ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- [ Main Content ] start -->
        <div class="row">
            <!-- Statistics Cards -->
            <div class="col-md-6 col-xl-4">
                <div class="card border-0 shadow-sm bg-danger text-white">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avtar avtar-s bg-white bg-opacity-20 text-white">
                                    <i class="ph-duotone ph-warning-circle fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="text-white text-opacity-75 mb-0"><?= _('Total des dettes') ?></p>
                                <h3 class="mb-0 text-white"><?= number_format(array_sum(array_column($restes, 'montant')), 0, ',', ' ') ?> <small class="fs-6">FCFA</small></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avtar avtar-s bg-light-danger text-danger">
                                    <i class="ph-duotone ph-users fs-4"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <p class="text-muted mb-0"><?= _('Élèves endettés') ?></p>
                                <h3 class="mb-0"><?= count(array_unique(array_column($restes, 'id_eleve'))) ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Table -->
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><?= _('Liste des montants restant à payer') ?></h5>
                        <div class="card-header-right">
                            <input type="text" id="debtSearch" class="form-control form-control-sm" placeholder="<?= _('Rechercher un élève...') ?>">
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="debtTable">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4"><?= _('Élève') ?></th>
                                        <th><?= _('Classe') ?></th>
                                        <th><?= _('Type de dette') ?></th>
                                        <th class="text-end"><?= _('Montant restant') ?></th>
                                        <th><?= _('Date origine') ?></th>
                                        <th class="text-center"><?= _('Statut') ?></th>
                                        <th class="text-end pe-4"><?= _('Actions') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($restes)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-5 text-muted">
                                                <i class="ph-duotone ph-check-circle fs-1 d-block mb-2 text-success"></i>
                                                <?= _('Aucune dette enregistrée. Tous les paiements sont à jour !') ?>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($restes as $reste): ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <div class="d-flex align-items-center">
                                                        <div class="flex-grow-1">
                                                            <h6 class="mb-0"><?= htmlspecialchars($reste['prenom'] . ' ' . $reste['nom']) ?></h6>
                                                            <span class="text-muted small">ID: #<?= $reste['id_eleve'] ?></span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?= htmlspecialchars($reste['niveau']) ?>
                                                    <?= !empty($reste['serie']) ? htmlspecialchars($reste['serie']) : '' ?>
                                                    <?= $reste['numero'] ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light-info text-info">
                                                        <?= htmlspecialchars($reste['type']) ?>
                                                    </span>
                                                </td>
                                                <td class="text-end fw-bold text-danger">
                                                    <?= number_format($reste['montant'], 0, ',', ' ') ?> FCFA
                                                </td>
                                                <td>
                                                    <?= $reste['date'] ? date('d/m/Y', strtotime($reste['date'])) : '<span class="text-muted">N/A</span>' ?>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-light-warning text-warning"><?= _('En attente') ?></span>
                                                </td>
                                                <td class="text-end pe-4">
                                                    <?php if (strpos($reste['type'], 'Inscription') !== false): ?>
                                                        <a href="/paiements/regulariser-inscription/<?= $reste['id_eleve'] ?>" class="btn btn-sm btn-primary">
                                                            <i class="ph-duotone ph-hand-coins me-1"></i><?= _('Régulariser') ?>
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="/mensualites/pay/<?= $reste['id_eleve'] ?>" class="btn btn-sm btn-info text-white">
                                                            <i class="ph-duotone ph-calendar me-1"></i><?= _('Régulariser') ?>
                                                        </a>
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
        </div>
        <!-- [ Main Content ] end -->
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('debtSearch');
    const tableRows = document.querySelectorAll('#debtTable tbody tr');

    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase();
        tableRows.forEach(row => {
            const text = row.innerText.toLowerCase();
            row.style.display = text.includes(query) ? '' : 'none';
        });
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
