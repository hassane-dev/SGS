<?php
require_once __DIR__ . '/../../layouts/header_able.php';
require_once __DIR__ . '/../../layouts/sidebar_able.php';
?>

<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h5 class="m-b-10"><?= _("Fiche Fournisseur") ?></h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/"><?= _("Tableau de Bord") ?></a></li>
                            <li class="breadcrumb-item"><a href="/achats/fournisseurs"><?= _("Fournisseurs") ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= htmlspecialchars($fournisseur['raison_sociale']) ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- [ Main Content ] start -->
        <div class="row">
            <!-- Left Info Panel -->
            <div class="col-xl-4 col-md-12">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body text-center">
                        <div class="avtar avtar-xl bg-light-primary text-primary mx-auto mb-3">
                            <i class="ph-duotone ph-buildings fs-1"></i>
                        </div>
                        <h4 class="mb-1"><?= htmlspecialchars($fournisseur['raison_sociale']) ?></h4>
                        <span class="badge bg-light-secondary text-secondary mb-3"><?= htmlspecialchars($fournisseur['code_fournisseur']) ?></span>

                        <div class="d-flex align-items-center justify-content-center mb-3">
                            <?php if ($fournisseur['actif']): ?>
                                <span class="badge bg-light-success text-success"><i class="ph-duotone ph-check-circle me-1"></i><?= _("Actif") ?></span>
                            <?php else: ?>
                                <span class="badge bg-light-danger text-danger"><i class="ph-duotone ph-x-circle me-1"></i><?= _("Inactif") ?></span>
                            <?php endif; ?>
                        </div>

                        <hr class="my-4">

                        <div class="text-start">
                            <h6 class="mb-3 text-muted"><?= _("Détails d'identification") ?></h6>
                            <p class="mb-2"><strong><?= _("NIF :") ?></strong> <?= htmlspecialchars($fournisseur['nif'] ?: '-') ?></p>
                            <p class="mb-2"><strong><?= _("RCCM :") ?></strong> <?= htmlspecialchars($fournisseur['rccm'] ?: '-') ?></p>
                            <p class="mb-2"><strong><?= _("Compte tiers OHADA :") ?></strong> <code class="text-dark font-weight-bold"><?= htmlspecialchars($fournisseur['compte_comptable_tiers'] ?: '401100') ?></code></p>

                            <h6 class="mt-4 mb-3 text-muted"><?= _("Contact principal") ?></h6>
                            <p class="mb-2"><strong><?= _("Nom :") ?></strong> <?= htmlspecialchars($fournisseur['contact_nom'] ?: '-') ?></p>
                            <p class="mb-2"><strong><?= _("Téléphone :") ?></strong> <?= htmlspecialchars($fournisseur['telephone'] ?: '-') ?></p>
                            <p class="mb-2"><strong><?= _("Email :") ?></strong> <?= htmlspecialchars($fournisseur['email'] ?: '-') ?></p>
                            <p class="mb-2"><strong><?= _("Adresse :") ?></strong> <?= htmlspecialchars($fournisseur['adresse'] ?: '-') ?></p>
                        </div>

                        <?php if (Auth::can('manage', 'fournisseur')): ?>
                            <div class="mt-4 d-grid">
                                <a href="/achats/fournisseurs/edit?id=<?= $fournisseur['id'] ?>" class="btn btn-warning"><i class="ph-duotone ph-pencil me-2"></i><?= _("Modifier la fiche") ?></a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Metrics and Associated Tables -->
            <div class="col-xl-8 col-md-12">
                <!-- Metrics -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm bg-light-primary text-primary">
                            <div class="card-body p-3">
                                <span class="d-block text-muted small mb-1"><?= _("Total Facturé") ?></span>
                                <h4 class="mb-0 font-weight-bold"><?= number_format($metrics['total_facture'], 2, ',', ' ') ?> <small class="fs-6">FCFA</small></h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm bg-light-success text-success">
                            <div class="card-body p-3">
                                <span class="d-block text-muted small mb-1"><?= _("Total Réglé") ?></span>
                                <h4 class="mb-0 font-weight-bold"><?= number_format($metrics['total_paye'], 2, ',', ' ') ?> <small class="fs-6">FCFA</small></h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm bg-light-danger text-danger">
                            <div class="card-body p-3">
                                <span class="d-block text-muted small mb-1"><?= _("Reste à Payer") ?></span>
                                <h4 class="mb-0 font-weight-bold"><?= number_format($metrics['reste_a_payer'], 2, ',', ' ') ?> <small class="fs-6">FCFA</small></h4>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabs for Orders and Invoices -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <ul class="nav nav-tabs card-header-tabs border-bottom-0" id="supplierTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="invoices-tab" data-bs-toggle="tab" data-bs-target="#invoices" type="button" role="tab" aria-controls="invoices" aria-selected="true">
                                    <i class="ph-duotone ph-receipt me-2"></i><?= _("Factures") ?> (<?= count($invoices) ?>)
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders" type="button" role="tab" aria-controls="orders" aria-selected="false">
                                    <i class="ph-duotone ph-shopping-cart me-2"></i><?= _("Bons de commande") ?> (<?= count($orders) ?>)
                                </button>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content" id="supplierTabsContent">
                            <!-- Invoices Tab -->
                            <div class="tab-pane fade show active" id="invoices" role="tabpanel" aria-labelledby="invoices-tab">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0 align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th><?= _("Référence") ?></th>
                                                <th><?= _("Date") ?></th>
                                                <th><?= _("Échéance") ?></th>
                                                <th class="text-end"><?= _("Montant TTC") ?></th>
                                                <th class="text-center"><?= _("Statut") ?></th>
                                                <th class="text-center d-print-none"><?= _("Actions") ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($invoices)): ?>
                                                <tr>
                                                    <td colspan="6" class="text-center py-4 text-muted"><?= _("Aucune facture enregistrée.") ?></td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($invoices as $inv):
                                                    $rem = AchatFacture::getResteAPayer($inv['id']);
                                                ?>
                                                    <tr>
                                                        <td><strong><?= htmlspecialchars($inv['reference_facture']) ?></strong></td>
                                                        <td><?= date('d/m/Y', strtotime($inv['date_facture'])) ?></td>
                                                        <td><?= date('d/m/Y', strtotime($inv['date_echeance'])) ?></td>
                                                        <td class="text-end font-weight-bold"><?= number_format($inv['montant_ttc'], 2, ',', ' ') ?> FCFA</td>
                                                        <td class="text-center">
                                                            <?php if ($inv['statut'] === 'payee'): ?>
                                                                <span class="badge bg-light-success text-success"><?= _("Payée") ?></span>
                                                            <?php elseif ($inv['statut'] === 'payee_partiellement'): ?>
                                                                <span class="badge bg-light-warning text-warning"><?= _("Partiellement payée") ?></span>
                                                            <?php else: ?>
                                                                <span class="badge bg-light-danger text-danger"><?= _("Enregistrée") ?></span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="text-center d-print-none">
                                                            <?php if ($rem > 0 && Auth::can('pay', 'achat_facture')): ?>
                                                                <a href="/achats/factures/pay?id=<?= $inv['id'] ?>" class="btn btn-sm btn-success me-1">
                                                                    <i class="ph-duotone ph-wallet me-1"></i><?= _("Régler") ?>
                                                                </a>
                                                            <?php endif; ?>
                                                            <?php if (Auth::can('create', 'achat_avoir')): ?>
                                                                <a href="/achats/avoirs/create?facture_id=<?= $inv['id'] ?>" class="btn btn-sm btn-outline-danger">
                                                                    <i class="ph-duotone ph-arrow-counter-clockwise me-1"></i><?= _("Avoir") ?>
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

                            <!-- Orders Tab -->
                            <div class="tab-pane fade" id="orders" role="tabpanel" aria-labelledby="orders-tab">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0 align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th><?= _("N° Commande") ?></th>
                                                <th><?= _("Date") ?></th>
                                                <th><?= _("Statut") ?></th>
                                                <th class="text-center d-print-none"><?= _("Actions") ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($orders)): ?>
                                                <tr>
                                                    <td colspan="4" class="text-center py-4 text-muted"><?= _("Aucun bon de commande émis.") ?></td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($orders as $ord): ?>
                                                    <tr>
                                                        <td><strong><?= htmlspecialchars($ord['numero_commande']) ?></strong></td>
                                                        <td><?= date('d/m/Y', strtotime($ord['date_commande'])) ?></td>
                                                        <td>
                                                            <?php if ($ord['statut'] === 'executee'): ?>
                                                                <span class="badge bg-light-success text-success"><?= _("Exécutée") ?></span>
                                                            <?php elseif ($ord['statut'] === 'reception_partielle'): ?>
                                                                <span class="badge bg-light-warning text-warning"><?= _("Réception partielle") ?></span>
                                                            <?php else: ?>
                                                                <span class="badge bg-light-info text-info"><?= _("Émise") ?></span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="text-center d-print-none">
                                                            <a href="/achats/commandes/show?id=<?= $ord['id'] ?>" class="btn btn-sm btn-light-primary">
                                                                <i class="ph-duotone ph-eye me-1"></i><?= _("Consulter") ?>
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
            </div>
        </div>
        <!-- [ Main Content ] end -->
    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer_able.php'; ?>
