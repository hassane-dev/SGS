<div class="row mt-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="mb-0 text-primary">
                            <i class="ph-duotone ph-student me-2"></i><?= _('Liste des Élèves') ?> - <?= htmlspecialchars(Classe::getFormattedName($classe)) ?>
                        </h5>
                    </div>
                    <div class="col-auto">
                        <a href="/carte/generer?classe_id=<?= $classe['id_classe'] ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                            <i class="ph-duotone ph-cardholder me-1"></i>
                            <?= _('Imprimer les cartes de la classe') ?>
                        </a>
                    </div>
                    <div class="col-auto">
                        <span class="badge bg-light-primary text-primary"><?= count($eleves) ?> <?= _('élèves actifs') ?></span>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="table-mensualites">
                        <thead class="bg-light-layout">
                            <tr>
                                <th class="ps-4" style="min-width: 250px;"><?= _('Élève') ?></th>
                                <?php foreach ($allMonths as $month): ?>
                                    <th class="text-center"><?= htmlspecialchars($month) ?></th>
                                <?php endforeach; ?>
                                <th class="text-end pe-4"><?= _('Actions') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($eleves)): ?>
                                <tr>
                                    <td colspan="<?= count($allMonths) + 2 ?>" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="ph-duotone ph-users-slash fs-1 d-block mb-2"></i>
                                            <?= _('Aucun élève actif trouvé dans cette classe.') ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($eleves as $eleve): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0">
                                                    <img src="<?= $eleve['photo'] ?: '/assets/img/default-avatar.png' ?>" class="img-radius wid-40" alt="">
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <h6 class="mb-0"><?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></h6>
                                                    <span class="text-muted small">Mat: <?= htmlspecialchars($eleve['matricule'] ?? 'N/A') ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <?php
                                        $allPaid = true;
                                        foreach ($allMonths as $month):
                                            $m_cap = ucfirst($month);
                                            $payment = $eleve['payments'][$m_cap] ?? null;
                                            $verse = $payment ? $payment['total'] : 0;
                                            $expectedMonthly = FinanceService::applyFinancialAdvantages($eleve['id_eleve'], 'frais_mensuel', (float)($frais['frais_mensuel'] ?? 0));
                                            $reste = $expectedMonthly - $verse;

                                            $statusClass = 'bg-light-danger text-danger';
                                            $icon = 'ph-x-circle';
                                            if ($reste <= 0) {
                                                $statusClass = 'bg-light-success text-success';
                                                $icon = 'ph-check-circle';
                                            } elseif ($verse > 0) {
                                                $statusClass = 'bg-light-warning text-warning';
                                                $icon = 'ph-clock';
                                                $allPaid = false;
                                            } else {
                                                $allPaid = false;
                                            }
                                        ?>
                                            <td class="text-center">
                                                <span class="badge <?= $statusClass ?> p-2" title="<?= $reste > 0 ? 'Reste: ' . number_format($reste, 0, ',', ' ') . ' FCFA' : 'Payé' ?>">
                                                    <i class="ph-duotone <?= $icon ?> fs-5"></i>
                                                </span>
                                            </td>
                                        <?php endforeach; ?>
                                        <td class="text-end pe-4">
                                            <a href="/mensualites/pay/<?= $eleve['id_eleve'] ?>" class="btn btn-sm btn-light-primary">
                                                <i class="ph-duotone ph-money me-1"></i><?= _('Encaisser') ?>
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

<div class="row mt-3">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="avtar avtar-s bg-light-success text-success">
                            <i class="ph-duotone ph-check-circle fs-4"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <p class="text-muted mb-0"><?= _('À jour') ?></p>
                        <h4 class="mb-0" id="stat-a-jour">0</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="avtar avtar-s bg-light-warning text-warning">
                            <i class="ph-duotone ph-clock fs-4"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <p class="text-muted mb-0"><?= _('Partiels') ?></p>
                        <h4 class="mb-0" id="stat-partiel">0</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="avtar avtar-s bg-light-danger text-danger">
                            <i class="ph-duotone ph-warning fs-4"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <p class="text-muted mb-0"><?= _('En retard') ?></p>
                        <h4 class="mb-0" id="stat-retard">0</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    // Calcul rapide des stats pour le dashboard de classe
    let aJour = 0;
    let partiel = 0;
    let retard = 0;

    const rows = document.querySelectorAll('#table-mensualites tbody tr');
    rows.forEach(row => {
        const badges = row.querySelectorAll('.badge');
        if (badges.length === 0) return;

        let rowRetard = false;
        let rowPartiel = false;
        let rowAllSuccess = true;

        badges.forEach(badge => {
            if (badge.classList.contains('bg-light-danger')) {
                rowRetard = true;
                rowAllSuccess = false;
            }
            if (badge.classList.contains('bg-light-warning')) {
                rowPartiel = true;
                rowAllSuccess = false;
            }
        });

        if (rowAllSuccess) aJour++;
        else if (rowPartiel || rowRetard) {
             // On peut affiner : si au moins un rouge c'est retard, sinon si au moins un jaune c'est partiel
             if (rowRetard) retard++;
             else partiel++;
        }
    });

    document.getElementById('stat-a-jour').textContent = aJour;
    document.getElementById('stat-partiel').textContent = partiel;
    document.getElementById('stat-retard').textContent = retard;
})();
</script>
