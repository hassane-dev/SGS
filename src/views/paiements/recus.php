<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= _('Gestion des Reçus') ?></h2>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/">Tableau de Bord</a></li>
                            <li class="breadcrumb-item"><a href="/paiements"><?= _('Comptabilité') ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _('Reçus') ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><?= _('Liste des reçus générés') ?></h5>
                        <input type="text" id="recuSearch" class="form-control form-control-sm w-25" placeholder="<?= _('Numéro de reçu ou élève...') ?>">
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="recuTable">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4"><?= _('N° Reçu') ?></th>
                                        <th><?= _('Date') ?></th>
                                        <th><?= _('Élève') ?></th>
                                        <th><?= _('Type Principal') ?></th>
                                        <th class="text-end"><?= _('Montant Total') ?></th>
                                        <th class="text-end pe-4"><?= _('Actions') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recus)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-5 text-muted"><?= _('Aucun reçu trouvé.') ?></td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recus as $r): ?>
                                            <tr>
                                                <td class="ps-4"><code class="fw-bold fs-6"><?= htmlspecialchars($r['recu_numero']) ?></code></td>
                                                <td><?= date('d/m/Y H:i', strtotime($r['date'])) ?></td>
                                                <td><?= htmlspecialchars($r['prenom'] . ' ' . $r['nom']) ?></td>
                                                <td>
                                                    <span class="badge bg-light-secondary text-secondary"><?= htmlspecialchars($r['type']) ?></span>
                                                </td>
                                                <td class="text-end fw-bold text-primary"><?= number_format($r['total_montant'], 0, ',', ' ') ?> FCFA</td>
                                                <td class="text-end pe-4">
                                                    <?php if ($r['type'] == 'Inscription'): ?>
                                                        <a href="/recu/inscription?id=<?= $r['eleve_id'] ?>&recu=<?= $r['recu_numero'] ?>" target="_blank" class="btn btn-sm btn-icon btn-light-primary" title="Imprimer">
                                                            <i class="ph-duotone ph-printer"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="/recu/mensualite?id=<?= $r['eleve_id'] ?>&recu=<?= $r['recu_numero'] ?>" target="_blank" class="btn btn-sm btn-icon btn-light-primary" title="Imprimer">
                                                            <i class="ph-duotone ph-printer"></i>
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
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('recuSearch');
    const tableRows = document.querySelectorAll('#recuTable tbody tr');

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
