<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= _('Historique des Transactions') ?></h2>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/">Tableau de Bord</a></li>
                            <li class="breadcrumb-item"><a href="/paiements"><?= _('Comptabilité') ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _('Historique') ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><?= _('Toutes les transactions') ?></h5>
                        <div class="d-flex gap-2">
                            <input type="text" id="transactionSearch" class="form-control form-control-sm" placeholder="<?= _('Rechercher...') ?>">
                            <button class="btn btn-sm btn-light-secondary"><i class="ph-duotone ph-funnel"></i></button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="transactionTable">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4"><?= _('Date & Heure') ?></th>
                                        <th><?= _('Élève') ?></th>
                                        <th><?= _('Type') ?></th>
                                        <th class="text-end"><?= _('Montant') ?></th>
                                        <th><?= _('Mode') ?></th>
                                        <th><?= _('N° Reçu') ?></th>
                                        <th><?= _('Caissier') ?></th>
                                        <th class="text-end pe-4"><?= _('Action') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($transactions)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-5 text-muted"><?= _('Aucune transaction trouvée.') ?></td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($transactions as $t): ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <span class="fw-bold"><?= date('d/m/Y', strtotime($t['date'])) ?></span><br>
                                                    <small class="text-muted"><?= date('H:i', strtotime($t['date'])) ?></small>
                                                </td>
                                                <td><?= htmlspecialchars($t['prenom'] . ' ' . $t['nom']) ?></td>
                                                <td>
                                                    <span class="badge bg-light-<?= $t['type'] == 'Inscription' ? 'primary' : 'info' ?> text-<?= $t['type'] == 'Inscription' ? 'primary' : 'info' ?>">
                                                        <?= htmlspecialchars($t['type']) ?>
                                                    </span>
                                                </td>
                                                <td class="text-end fw-bold"><?= number_format($t['montant'], 0, ',', ' ') ?> FCFA</td>
                                                <td><?= htmlspecialchars($t['mode']) ?></td>
                                                <td><code class="text-primary"><?= htmlspecialchars($t['recu_numero']) ?></code></td>
                                                <td><?= htmlspecialchars($t['user_prenom'] . ' ' . $t['user_nom']) ?></td>
                                                <td class="text-end pe-4">
                                                    <button class="btn btn-sm btn-icon btn-light-secondary" title="Réimprimer">
                                                        <i class="ph-duotone ph-printer"></i>
                                                    </button>
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
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('transactionSearch');
    const tableRows = document.querySelectorAll('#transactionTable tbody tr');

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
