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
                            <h2 class="mb-0"><?= _('Régulariser la situation') ?></h2>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/home"><?= _('Tableau de Bord') ?></a></li>
                            <li class="breadcrumb-item"><a href="/paiements"><?= _('Comptabilité') ?></a></li>
                            <li class="breadcrumb-item"><a href="/paiements/restes"><?= _('Gestion des restes') ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _('Règlement') ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- [ Main Content ] start -->
        <div class="row">
            <!-- Student Header -->
            <div class="col-12 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <img src="<?= $eleve['photo'] ?: '/assets/img/default-avatar.png' ?>" alt="student image" class="img-radius wid-70 border border-2 border-primary shadow-sm">
                            </div>
                            <div class="col">
                                <h4 class="mb-1 text-primary"><?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></h4>
                                <span class="badge bg-light-primary text-primary fs-6 me-2">
                                    <i class="ph-duotone ph-chalkboard-teacher me-1"></i> <?= htmlspecialchars($eleve['nom_classe']) ?>
                                </span>
                                <span class="badge bg-light-secondary text-secondary fs-6">
                                    <i class="ph-duotone ph-identification-card me-1"></i> Mat: <?= htmlspecialchars($eleve['matricule'] ?? 'N/A') ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <form action="/paiements/process-payment/<?= $eleve['id_eleve'] ?>" method="POST" class="col-12 row">
                <!-- Section Dettes & Détails (Gauche) -->
                <div class="col-lg-7">
                    <!-- Reste d'Inscription -->
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="ph-duotone ph-file-text text-primary me-2"></i><?= _('Frais d\'inscription') ?></h5>
                            <?php if ($financialStatus['reste_inscription'] <= 0): ?>
                                <span class="badge bg-light-success text-success"><?= _('SOLDÉ') ?></span>
                            <?php else: ?>
                                <span class="badge bg-light-danger text-danger"><?= _('RESTANT') ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-3 bg-light p-3 rounded">
                                <span class="fw-bold text-muted"><?= _('Reste d\'inscription à payer :') ?></span>
                                <h5 class="mb-0 text-danger fw-bold"><?= number_format($financialStatus['reste_inscription'], 0, ',', ' ') ?> FCFA</h5>
                            </div>

                            <?php if ($financialStatus['reste_inscription'] > 0): ?>
                                <div class="mb-3">
                                    <label class="form-label fw-bold"><?= _('Montant à verser pour l\'inscription') ?></label>
                                    <div class="input-group">
                                        <input type="number" id="montant_inscription" name="montant_inscription" class="form-control form-control-lg fw-bold text-primary" placeholder="0" max="<?= $financialStatus['reste_inscription'] ?>" value="<?= $financialStatus['reste_inscription'] ?>">
                                        <span class="input-group-text bg-white">FCFA</span>
                                    </div>
                                    <div class="form-text text-muted"><?= _('Le montant maximum autorisé est de ') ?><?= number_format($financialStatus['reste_inscription'], 0, ',', ' ') ?> FCFA</div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Détails des Mensualités (Point 6) -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="ph-duotone ph-calendar-check text-success me-2"></i><?= _('Détails des mensualités exigibles') ?></h5>
                            <?php if ($financialStatus['reste_mensualite'] <= 0): ?>
                                <span class="badge bg-light-success text-success"><?= _('À JOUR') ?></span>
                            <?php else: ?>
                                <span class="badge bg-light-danger text-danger"><?= _('EN RETARD') ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <!-- Tableau des mois / séquences (Point 6) -->
                            <div class="table-responsive mb-4">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th><?= _('Mois') ?></th>
                                            <th class="text-end"><?= _('Attendu') ?></th>
                                            <th class="text-end"><?= _('Payé') ?></th>
                                            <th class="text-end"><?= _('Reste') ?></th>
                                            <th class="text-center"><?= _('Statut') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($financialStatus['details_mensualites'])): ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-4 text-muted"><?= _('Aucune mensualité exigible pour le moment.') ?></td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($financialStatus['details_mensualites'] as $dm):
                                                $badgeColor = 'danger';
                                                if ($dm['statut'] === 'Payé') $badgeColor = 'success';
                                                elseif ($dm['statut'] === 'Partiellement payé') $badgeColor = 'warning';
                                            ?>
                                                <tr>
                                                    <td class="fw-bold"><?= htmlspecialchars($dm['mois']) ?></td>
                                                    <td class="text-end"><?= number_format($dm['attendu'], 0, ',', ' ') ?> FCFA</td>
                                                    <td class="text-end text-success"><?= number_format($dm['verse'], 0, ',', ' ') ?> FCFA</td>
                                                    <td class="text-end text-danger fw-bold"><?= number_format($dm['reste'], 0, ',', ' ') ?> FCFA</td>
                                                    <td class="text-center">
                                                        <span class="badge bg-light-<?= $badgeColor ?> text-<?= $badgeColor ?>"><?= htmlspecialchars($dm['statut']) ?></span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-between mb-3 bg-light p-3 rounded">
                                <span class="fw-bold text-muted"><?= _('Total mensualités exigibles :') ?></span>
                                <h5 class="mb-0 text-danger fw-bold"><?= number_format($financialStatus['reste_mensualite'], 0, ',', ' ') ?> FCFA</h5>
                            </div>

                            <?php if ($financialStatus['reste_mensualite'] > 0): ?>
                                <div class="mb-3">
                                    <label class="form-label fw-bold"><?= _('Montant à verser pour les mensualités') ?></label>
                                    <div class="input-group mb-2">
                                        <input type="number" id="montant_mensualites" name="montant_mensualites" class="form-control form-control-lg fw-bold text-primary" placeholder="0" max="<?= $financialStatus['reste_mensualite'] ?>" value="<?= $financialStatus['reste_mensualite'] ?>">
                                        <span class="input-group-text bg-white">FCFA</span>
                                    </div>
                                    <div class="form-text text-muted">
                                        <i class="ph-duotone ph-info text-info me-1"></i>
                                        <?= _('Les paiements seront affectés chronologiquement du mois le plus ancien au plus récent (Point 7).') ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Finalisation Encaissement (Droite) -->
                <div class="col-lg-5">
                    <div class="card shadow-sm bg-primary text-white border-0">
                        <div class="card-body">
                            <h5 class="text-white mb-4"><i class="ph-duotone ph-hand-coins me-2"></i><?= _('Finalisation du règlement') ?></h5>

                            <div class="bg-white bg-opacity-10 p-3 rounded mb-4">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-white text-opacity-75"><?= _('Total général à payer :') ?></span>
                                    <h4 class="mb-0 text-white fw-bold" id="total_general_lbl">0 FCFA</h4>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-white"><?= _('Mode de paiement') ?></label>
                                <select name="mode_paiement" class="form-select border-0 shadow-none">
                                    <?php
                                    $modes = !empty($paramGeneral['modalite_paiement'])
                                        ? explode(',', $paramGeneral['modalite_paiement'])
                                        : ['Espèces'];
                                    foreach ($modes as $m):
                                        $m = trim($m);
                                    ?>
                                        <option value="<?= htmlspecialchars($m) ?>"><?= htmlspecialchars($m) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="form-label text-white"><?= _('Numéro de référence / Reçu') ?></label>
                                <input type="text" name="reference_transaction" class="form-control border-0 shadow-none" value="<?= htmlspecialchars($nextRecu) ?>" required>
                            </div>

                            <?php if ($isComptable): ?>
                                <button type="submit" class="btn btn-light text-primary w-100 fw-bold py-3">
                                    <i class="ph-duotone ph-check-circle me-2"></i><?= _('Valider l\'encaissement global') ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <!-- [ Main Content ] end -->
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputInscription = document.getElementById('montant_inscription');
    const inputMensualites = document.getElementById('montant_mensualites');
    const totalGeneralLbl = document.getElementById('total_general_lbl');

    function calculateTotal() {
        let total = 0;
        if (inputInscription) {
            total += parseFloat(inputInscription.value) || 0;
        }
        if (inputMensualites) {
            total += parseFloat(inputMensualites.value) || 0;
        }
        totalGeneralLbl.textContent = total.toLocaleString('fr-FR') + ' FCFA';
    }

    if (inputInscription) inputInscription.addEventListener('input', calculateTotal);
    if (inputMensualites) inputMensualites.addEventListener('input', calculateTotal);

    calculateTotal();
});
</script>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
