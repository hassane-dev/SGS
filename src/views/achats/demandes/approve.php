<?php
require_once __DIR__ . '/../../layouts/header_able.php';
require_once __DIR__ . '/../../layouts/sidebar_able.php';

// Calculate total of the request
$totalRequest = 0.00;
foreach ($lignes as $ligne) {
    $totalRequest += (float)$ligne['quantite_demandee'] * (float)$ligne['prix_unitaire_estime'];
}
?>

<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h5 class="m-b-10"><?= _("Traitement Demande d'Achat") ?></h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/"><?= _("Tableau de Bord") ?></a></li>
                            <li class="breadcrumb-item"><a href="/achats/demandes"><?= _("Demandes d'achats") ?></a></li>
                            <li class="breadcrumb-item" aria-current="page">DA-<?= str_pad($demande['id'], 5, '0', STR_PAD_LEFT) ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- Alert messages -->
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="ph-duotone ph-warning-circle me-1"></i>
                <?= htmlspecialchars($_SESSION['error_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <!-- [ Main Content ] start -->
        <div class="row">
            <!-- Details Card -->
            <div class="col-xl-4 col-md-12">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0"><?= _("Résumé de la demande") ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <span class="text-muted d-block small"><?= _("Code Demande") ?></span>
                            <span class="badge bg-light-primary text-primary h5 mb-0">DA-<?= str_pad($demande['id'], 5, '0', STR_PAD_LEFT) ?></span>
                        </div>
                        <div class="mb-3">
                            <span class="text-muted d-block small"><?= _("Date de demande") ?></span>
                            <span class="h6"><?= date('d/m/Y', strtotime($demande['date_demande'])) ?></span>
                        </div>
                        <div class="mb-3">
                            <span class="text-muted d-block small"><?= _("Montant estimé total") ?></span>
                            <span class="h4 text-primary font-weight-bold"><?= number_format($totalRequest, 2, ',', ' ') ?> FCFA</span>
                        </div>
                        <div class="mb-3">
                            <span class="text-muted d-block small"><?= _("Statut actuel") ?></span>
                            <?php if ($demande['statut'] === 'en_attente_approbation'): ?>
                                <span class="badge bg-light-warning text-warning"><i class="ph-duotone ph-clock me-1"></i><?= _("En attente d'approbation") ?></span>
                            <?php elseif ($demande['statut'] === 'approuvee'): ?>
                                <span class="badge bg-light-success text-success"><i class="ph-duotone ph-check-circle me-1"></i><?= _("Approuvée / Réservée") ?></span>
                            <?php elseif ($demande['statut'] === 'rejete'): ?>
                                <span class="badge bg-light-danger text-danger"><i class="ph-duotone ph-x-circle me-1"></i><?= _("Rejetée") ?></span>
                            <?php else: ?>
                                <span class="badge bg-light-secondary text-secondary"><?= htmlspecialchars($demande['statut']) ?></span>
                            <?php endif; ?>
                        </div>

                        <hr class="my-3">
                        <div class="mb-3">
                            <span class="text-muted d-block small"><?= _("Justification") ?></span>
                            <p class="mb-0 bg-light p-2 rounded text-dark italic small" style="white-space: pre-wrap;"><?= htmlspecialchars($demande['justification']) ?></p>
                        </div>

                        <?php if ($demande['approuve_par']): ?>
                            <hr class="my-3">
                            <div class="mb-2">
                                <span class="text-muted d-block small"><?= _("Traité par") ?></span>
                                <span class="font-weight-bold text-dark"><?= htmlspecialchars($demande['motif_statut'] ?: _("Pas de motif précisé")) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Lines and Treatment Card -->
            <div class="col-xl-8 col-md-12">
                <!-- Lines Table Card -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0"><i class="ph-duotone ph-list-numbers me-2 text-primary"></i><?= _("Lignes d'articles de la demande d'achat") ?></h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th><?= _("Référence") ?></th>
                                        <th><?= _("Article / Service") ?></th>
                                        <th class="text-end"><?= _("Quantité") ?></th>
                                        <th class="text-end"><?= _("P.U. Estimé") ?></th>
                                        <th class="text-end"><?= _("Total Estimé") ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lignes as $ligne):
                                        $totalLigne = (float)$ligne['quantite_demandee'] * (float)$ligne['prix_unitaire_estime'];
                                    ?>
                                        <tr>
                                            <td><code class="text-dark font-weight-bold"><?= htmlspecialchars($ligne['article_ref']) ?></code></td>
                                            <td>
                                                <span class="d-block font-weight-bold"><?= htmlspecialchars($ligne['article_libelle']) ?></span>
                                                <span class="text-muted small"><?= htmlspecialchars($ligne['unite_mesure']) ?></span>
                                            </td>
                                            <td class="text-end font-weight-bold"><?= number_format($ligne['quantite_demandee'], 2, ',', ' ') ?></td>
                                            <td class="text-end"><?= number_format($ligne['prix_unitaire_estime'], 2, ',', ' ') ?> FCFA</td>
                                            <td class="text-end font-weight-bold text-primary"><?= number_format($totalLigne, 2, ',', ' ') ?> FCFA</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Treatment Panel (Only for Pending) -->
                <?php if ($demande['statut'] === 'en_attente_approbation' && Auth::can('approve', 'achat_demande')): ?>
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0"><i class="ph-duotone ph-shield-check me-2 text-warning"></i><?= _("Décision de validation") ?></h5>
                        </div>
                        <div class="card-body">
                            <form action="/achats/demandes/approve" method="POST">
                                <input type="hidden" name="id" value="<?= $demande['id'] ?>">

                                <div class="mb-3">
                                    <label class="form-label font-weight-bold" for="motif_statut"><?= _("Observations / Motif de la décision") ?></label>
                                    <textarea name="motif_statut" id="motif_statut" rows="3" class="form-control" placeholder="<?= _("Saisissez une observation ou justification (obligatoire en cas de rejet)... ") ?>"></textarea>
                                </div>

                                <div class="d-flex justify-content-end gap-2">
                                    <a href="/achats/demandes" class="btn btn-light-secondary"><?= _("Retour") ?></a>
                                    <button type="submit" name="action" value="reject" class="btn btn-danger" onclick="return confirm('<?= _("Voulez-vous vraiment rejeter cette demande d'achat ?") ?>');">
                                        <i class="ph-duotone ph-x-circle me-1"></i><?= _("Rejeter la demande") ?>
                                    </button>
                                    <button type="submit" name="action" value="approve" class="btn btn-success" onclick="return confirm('<?= _("Approuver cette demande d'achat réservera automatiquement les crédits budgétaires associés. Continuer ?") ?>');">
                                        <i class="ph-duotone ph-check-circle me-1"></i><?= _("Approuver & Réserver Budget") ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="d-flex justify-content-end gap-2 d-print-none">
                        <a href="/achats/demandes" class="btn btn-light-secondary"><i class="ph-duotone ph-arrow-left me-1"></i><?= _("Retour à la liste") ?></a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <!-- [ Main Content ] end -->
    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer_able.php'; ?>
