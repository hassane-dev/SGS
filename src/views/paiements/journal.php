<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header d-print-none">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= _('Journal Comptable Unique') ?></h2>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/home"><?= _('Accueil') ?></a></li>
                            <li class="breadcrumb-item"><a href="/paiements"><?= _('Comptabilité') ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _('Journal Comptable') ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- Printable Header Section (Only visible during print) -->
        <div class="d-none d-print-block mb-4">
            <div class="text-center">
                <h2><?= htmlspecialchars($title) ?></h2>
                <h5><?= htmlspecialchars(Auth::user()['nom'] ?? '') ?></h5>
                <p class="mb-1"><?= _('Généré le') ?> <?= date('d/m/Y H:i') ?></p>
                <p class="text-muted small"><?= _('Période du') ?> <?= htmlspecialchars($filters['date_debut']) ?> <?= _('au') ?> <?= htmlspecialchars($filters['date_fin']) ?></p>
            </div>
            <hr>
        </div>

        <!-- [ Main Content ] start -->
        <div class="row">
            <!-- Filter Section -->
            <div class="col-12 d-print-none">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0"><i class="ph-duotone ph-funnel me-2"></i><?= _('Filtres de recherche') ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="/paiements/journal" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label fw-bold"><?= _('Période du') ?></label>
                                <input type="date" name="date_debut" class="form-control" value="<?= htmlspecialchars($filters['date_debut']) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold"><?= _('au') ?></label>
                                <input type="date" name="date_fin" class="form-control" value="<?= htmlspecialchars($filters['date_fin']) ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold"><?= _('Type d\'opération') ?></label>
                                <select name="operation" class="form-select">
                                    <option value=""><?= _('Toutes') ?></option>
                                    <option value="inscription" <?= $filters['operation'] === 'inscription' ? 'selected' : '' ?>><?= _('Inscription') ?></option>
                                    <option value="mensualite" <?= $filters['operation'] === 'mensualite' ? 'selected' : '' ?>><?= _('Mensualité') ?></option>
                                    <option value="annulation" <?= $filters['operation'] === 'annulation' ? 'selected' : '' ?>><?= _('Annulation') ?></option>
                                    <option value="remboursement" <?= $filters['operation'] === 'remboursement' ? 'selected' : '' ?>><?= _('Remboursement') ?></option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold"><?= _('Recherche (Élève, Reçu, Matricule)') ?></label>
                                <div class="input-group">
                                    <input type="text" name="search" class="form-control" placeholder="<?= _('Nom, prénom, reçu, identifiant public...') ?>" value="<?= htmlspecialchars($filters['search']) ?>">
                                    <button type="submit" class="btn btn-primary"><i class="ph-duotone ph-magnifying-glass me-1"></i><?= _('Rechercher') ?></button>
                                    <button type="button" onclick="window.print()" class="btn btn-secondary"><i class="ph-duotone ph-printer me-1"></i><?= _('Imprimer') ?></button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Journal Entries Table -->
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-3 d-print-none">
                        <div class="row align-items-center">
                            <div class="col">
                                <h5 class="mb-0 text-primary">
                                    <i class="ph-duotone ph-book-open me-2"></i><?= _('Lignes du journal') ?>
                                </h5>
                            </div>
                            <div class="col-auto">
                                <span class="badge bg-light-primary text-primary"><?= count($entries) ?> <?= _('opérations enregistrées') ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light-layout">
                                    <tr>
                                        <th class="ps-4"><?= _('Date & Heure') ?></th>
                                        <th><?= _('Élève (Identifiant)') ?></th>
                                        <th><?= _('Opération') ?></th>
                                        <th class="text-end"><?= _('Montant') ?></th>
                                        <th><?= _('Mode') ?></th>
                                        <th><?= _('Reçu N°') ?></th>
                                        <th><?= _('Opérateur') ?></th>
                                        <th class="d-print-none"><?= _('Réf Origine') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($entries)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-5 text-muted">
                                                <i class="ph-duotone ph-folder-open fs-1 d-block mb-2"></i>
                                                <?= _('Aucune opération enregistrée dans le journal pour les critères sélectionnés.') ?>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php
                                        $totalJournal = 0.00;
                                        foreach ($entries as $entry):
                                            $totalJournal += (float)$entry['montant'];

                                            // Badges for operation status
                                            $opClass = 'bg-light-success text-success';
                                            $opLabel = _('Mensualité');
                                            if ($entry['operation'] === 'inscription') {
                                                $opClass = 'bg-light-info text-info';
                                                $opLabel = _('Inscription');
                                            } elseif ($entry['operation'] === 'annulation') {
                                                $opClass = 'bg-light-danger text-danger';
                                                $opLabel = _('Annulation');
                                            } elseif ($entry['operation'] === 'remboursement') {
                                                $opClass = 'bg-light-warning text-warning';
                                                $opLabel = _('Remboursement');
                                            }
                                        ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <?= date('d/m/Y H:i', strtotime($entry['date_creation'])) ?>
                                                </td>
                                                <td>
                                                    <?php if ($entry['eleve_id']): ?>
                                                        <span class="fw-bold"><?= htmlspecialchars($entry['eleve_prenom'] . ' ' . $entry['eleve_nom']) ?></span>
                                                        <br>
                                                        <small class="text-muted">Mat: <?= htmlspecialchars($entry['eleve_matricule'] ?? 'N/A') ?></small>
                                                    <?php else: ?>
                                                        <span class="text-muted">N/A</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge <?= $opClass ?>"><?= $opLabel ?></span>
                                                </td>
                                                <td class="text-end fw-bold <?= $entry['montant'] < 0 ? 'text-danger' : 'text-dark' ?>">
                                                    <?= number_format($entry['montant'], 0, ',', ' ') ?> FCFA
                                                </td>
                                                <td>
                                                    <?= htmlspecialchars($entry['mode_paiement'] ?? 'N/A') ?>
                                                </td>
                                                <td>
                                                    <?php if ($entry['recu_numero']): ?>
                                                        <span class="badge bg-light-secondary text-dark"><?= htmlspecialchars($entry['recu_numero']) ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?= htmlspecialchars($entry['user_prenom'] . ' ' . $entry['user_nom']) ?>
                                                </td>
                                                <td class="d-print-none text-muted small">
                                                    <code><?= htmlspecialchars($entry['reference_origine'] ?? '') ?></code>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <tr class="table-light fw-bold fs-6">
                                            <td colspan="3" class="ps-4 text-end text-uppercase"><?= _('Bilan Net') ?></td>
                                            <td class="text-end text-primary"><?= number_format($totalJournal, 0, ',', ' ') ?> FCFA</td>
                                            <td colspan="4"></td>
                                        </tr>
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

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
