<input type="hidden" id="currentClassId" value="<?= $classe['id_classe'] ?>">

<!-- Statistiques de la Classe -->
<div class="row">
    <div class="col-md-3">
        <div class="card bg-light-primary">
            <div class="card-body">
                <h6 class="text-primary mb-1">Effectif Total</h6>
                <h3 class="mb-0"><?= $stats['total'] ?> Élèves</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-light-success">
            <div class="card-body">
                <h6 class="text-success mb-1">Taux de Paiement</h6>
                <h3 class="mb-0"><?= $stats['pct_a_jour'] ?> %</h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-light-danger">
            <div class="card-body">
                <h6 class="text-danger mb-1">Impayés / Partiels</h6>
                <h3 class="mb-0"><?= $stats['impaye'] + $stats['partiel'] ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-light-warning">
            <div class="card-body">
                <h6 class="text-warning mb-1">Dette Restante (Inscr.)</h6>
                <h3 class="mb-0"><?= number_format($stats['dette_totale'], 0, ',', ' ') ?> <small>FCFA</small></h3>
            </div>
        </div>
    </div>
</div>

<!-- Liste des Élèves -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5>Liste des élèves - <?= htmlspecialchars($classe['niveau'] . ' ' . $classe['serie'] . ' ' . $classe['numero']) ?></h5>
        <span class="badge bg-primary"><?= $moisCourant ?></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Élève</th>
                        <th>Statut (<?= $moisCourant ?>)</th>
                        <th class="text-end">Reste Inscription</th>
                        <th class="text-center">Actions Rapides</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($eleves as $e): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avtar avtar-s bg-light-secondary me-2">
                                        <i class="ph-duotone ph-user"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0"><?= htmlspecialchars($e['prenom'] . ' ' . $e['nom']) ?></h6>
                                        <small class="text-muted"><?= $e['matricule'] ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php
                                $badgeClass = 'secondary';
                                if (strpos($e['statut_finance'], '🟢') !== false) $badgeClass = 'success';
                                if (strpos($e['statut_finance'], '🟡') !== false) $badgeClass = 'warning';
                                if (strpos($e['statut_finance'], '🔴') !== false) $badgeClass = 'danger';
                                ?>
                                <span class="badge bg-light-<?= $badgeClass ?> text-<?= $badgeClass ?>">
                                    <?= $e['statut_finance'] ?>
                                </span>
                            </td>
                            <td class="text-end fw-bold">
                                <?= number_format($e['dette'], 0, ',', ' ') ?> <small>FCFA</small>
                            </td>
                            <td class="text-center">
                                <?php if (strpos($e['statut_finance'], '🟢') === false && $e['montant_mensuel'] > 0): ?>
                                    <button class="btn btn-sm btn-success btn-quick-pay"
                                            data-eleve-id="<?= $e['id_eleve'] ?>"
                                            data-montant="<?= $e['montant_mensuel'] ?>"
                                            data-mois="<?= $moisCourant ?>"
                                            title="Payer le mois en cours">
                                        <i class="ph-duotone ph-money me-1"></i> 💰 Payer <?= $moisCourant ?>
                                    </button>
                                <?php endif; ?>
                                <a href="/paiements/show/<?= $e['id_eleve'] ?>" class="btn btn-sm btn-light-primary ms-1" title="Détails & Historique">
                                    <i class="ph-duotone ph-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
