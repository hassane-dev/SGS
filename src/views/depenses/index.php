<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<!-- [ Main Content ] start -->
<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= _('Gestion des Dépenses') ?></h2>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <?php if (Auth::can('create', 'depense')): ?>
                            <a href="/depenses/create" class="btn btn-primary d-inline-flex align-items-center">
                                <i class="ti ti-plus me-1"></i> <?= _('Nouvelle Dépense') ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- Alerts -->
        <?php if (!empty($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['success_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <pre style="white-space: pre-wrap; margin-bottom: 0; font-family: inherit;"><?= htmlspecialchars($_SESSION['error_message']) ?></pre>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="/depenses" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label"><?= _('Recherche') ?></label>
                        <input type="text" name="search" class="form-label form-control" placeholder="<?= _('Pièce, motif, bénéficiaire...') ?>" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label"><?= _('Statut') ?></label>
                        <select name="statut" class="form-select">
                            <option value=""><?= _('Tous les statuts') ?></option>
                            <option value="brouillon" <?= ($_GET['statut'] ?? '') === 'brouillon' ? 'selected' : '' ?>><?= _('Brouillon') ?></option>
                            <option value="en_attente_approbation" <?= ($_GET['statut'] ?? '') === 'en_attente_approbation' ? 'selected' : '' ?>><?= _('En attente d\'approbation') ?></option>
                            <option value="approuve" <?= ($_GET['statut'] ?? '') === 'approuve' ? 'selected' : '' ?>><?= _('Approuvé') ?></option>
                            <option value="rejete" <?= ($_GET['statut'] ?? '') === 'rejete' ? 'selected' : '' ?>><?= _('Rejeté') ?></option>
                            <option value="paye" <?= ($_GET['statut'] ?? '') === 'paye' ? 'selected' : '' ?>><?= _('Payé') ?></option>
                            <option value="paye_partiellement" <?= ($_GET['statut'] ?? '') === 'paye_partiellement' ? 'selected' : '' ?>><?= _('Payé partiellement') ?></option>
                            <option value="annule" <?= ($_GET['statut'] ?? '') === 'annule' ? 'selected' : '' ?>><?= _('Annulé') ?></option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label"><?= _('Catégorie') ?></label>
                        <select name="categorie_id" class="form-select">
                            <option value=""><?= _('Toutes les catégories') ?></option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= ($_GET['categorie_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['nom_categorie']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label"><?= _('Date Début') ?></label>
                        <input type="date" name="date_debut" class="form-control" value="<?= htmlspecialchars($_GET['date_debut'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label"><?= _('Date Fin') ?></label>
                        <input type="date" name="date_fin" class="form-control" value="<?= htmlspecialchars($_GET['date_fin'] ?? '') ?>">
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100"><i class="ti ti-search"></i></button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Expense Table -->
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th><?= _('Pièce N°') ?></th>
                                <th><?= _('Bénéficiaire') ?></th>
                                <th><?= _('Catégorie') ?></th>
                                <th><?= _('Motif') ?></th>
                                <th><?= _('Montant') ?></th>
                                <th><?= _('Statut') ?></th>
                                <th><?= _('Date') ?></th>
                                <th class="text-end"><?= _('Actions') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($depenses)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4"><?= _('Aucune dépense trouvée.') ?></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($depenses as $d): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($d['numero_piece']) ?></strong></td>
                                        <td><?= htmlspecialchars($d['nom_beneficiaire'] ?? 'Inconnu') ?></td>
                                        <td><span class="badge bg-light-primary text-primary"><?= htmlspecialchars($d['nom_categorie'] ?? 'N/A') ?></span></td>
                                        <td><?= htmlspecialchars($d['motif']) ?></td>
                                        <td><strong><?= number_format($d['montant'], 2, ',', ' ') ?> FCFA</strong></td>
                                        <td>
                                            <?php
                                            $badgeClass = 'bg-secondary';
                                            $statusText = $d['statut'];
                                            if ($d['statut'] === 'brouillon') { $badgeClass = 'bg-secondary'; $statusText = _('Brouillon'); }
                                            elseif ($d['statut'] === 'en_attente_approbation') { $badgeClass = 'bg-warning'; $statusText = _('En attente'); }
                                            elseif ($d['statut'] === 'approuve') { $badgeClass = 'bg-info'; $statusText = _('Approuvé'); }
                                            elseif ($d['statut'] === 'rejete') { $badgeClass = 'bg-danger'; $statusText = _('Rejeté'); }
                                            elseif ($d['statut'] === 'paye') { $badgeClass = 'bg-success'; $statusText = _('Payé'); }
                                            elseif ($d['statut'] === 'paye_partiellement') { $badgeClass = 'bg-primary'; $statusText = _('Payé part.'); }
                                            elseif ($d['statut'] === 'annule') { $badgeClass = 'bg-dark'; $statusText = _('Annulé'); }
                                            ?>
                                            <span class="badge <?= $badgeClass ?>"><?= $statusText ?></span>
                                        </td>
                                        <td><?= date('d/m/Y H:i', strtotime($d['date_creation'])) ?></td>
                                        <td class="text-end">
                                            <?php if ($d['statut'] === 'brouillon' && Auth::can('create', 'depense')): ?>
                                                <!-- Action form to submit to validation directly -->
                                                <form action="/depenses/store" method="POST" class="d-inline">
                                                    <input type="hidden" name="id" value="<?= $d['id'] ?>">
                                                    <input type="hidden" name="submit_direct" value="1">
                                                    <!-- Keep existing data -->
                                                    <input type="hidden" name="numero_piece" value="<?= htmlspecialchars($d['numero_piece']) ?>">
                                                    <input type="hidden" name="categorie_id" value="<?= $d['categorie_id'] ?>">
                                                    <input type="hidden" name="beneficiaire_id" value="<?= $d['beneficiaire_id'] ?>">
                                                    <input type="hidden" name="montant" value="<?= $d['montant'] ?>">
                                                    <input type="hidden" name="motif" value="<?= htmlspecialchars($d['motif']) ?>">
                                                    <button type="submit" class="btn btn-sm btn-light-warning" title="<?= _('Soumettre pour approbation') ?>">
                                                        <i class="ti ti-send"></i> <?= _('Soumettre') ?>
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if ($d['statut'] === 'en_attente_approbation' && Auth::can('validate', 'depense')): ?>
                                                <a href="/depenses/validate/<?= $d['id'] ?>" class="btn btn-sm btn-light-info">
                                                    <i class="ti ti-checklist"></i> <?= _('Valider') ?>
                                                </a>
                                            <?php endif; ?>

                                            <?php if ($d['statut'] === 'approuve' && Auth::can('pay', 'depense')): ?>
                                                <a href="/depenses/pay/<?= $d['id'] ?>" class="btn btn-sm btn-light-success">
                                                    <i class="ti ti-cash"></i> <?= _('Payer') ?>
                                                </a>
                                            <?php endif; ?>

                                            <?php if ($d['statut'] === 'paye' && Auth::can('cancel', 'depense')): ?>
                                                <button type="button" class="btn btn-sm btn-light-danger" data-bs-toggle="modal" data-bs-target="#cancelModal<?= $d['id'] ?>">
                                                    <i class="ti ti-circle-x text-danger"></i> <?= _('Annuler') ?>
                                                </button>

                                                <!-- Cancellation Modal -->
                                                <div class="modal fade" id="cancelModal<?= $d['id'] ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <form action="/depenses/cancel/<?= $d['id'] ?>" method="POST" class="modal-content text-start">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title"><?= _('Annulation et Contre-passation de Dépense') ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p><?= _('Voulez-vous vraiment annuler cette dépense réglée ? Une écriture comptable d\'entrée de contre-passation compensatoire sera automatiquement enregistrée dans le Grand Livre.') ?></p>
                                                                <div class="mb-3">
                                                                    <label class="form-label"><?= _('Motif/Justificatif obligatoire d\'annulation') ?></label>
                                                                    <textarea name="motif_annulation" class="form-control" rows="3" required placeholder="<?= _('Saisissez la raison de l\'annulation...') ?>"></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= _('Fermer') ?></button>
                                                                <button type="submit" class="btn btn-danger"><?= _('Confirmer l\'annulation') ?></button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination Footer -->
            <?php if ($totalPages > 1): ?>
                <div class="card-footer d-flex justify-content-between align-items-center">
                    <div>
                        <?= sprintf(_('Page %d sur %d'), $page, $totalPages) ?>
                    </div>
                    <nav>
                        <ul class="pagination mb-0">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>"><?= _('Précédent') ?></a>
                            </li>
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>"><?= _('Suivant') ?></a>
                            </li>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<!-- [ Main Content ] end -->

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>