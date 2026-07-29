<?php include __DIR__ . '/../../layouts/header_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <!-- Breadcrumbs -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/"><?= _("Accueil") ?></a></li>
                            <li class="breadcrumb-item"><a href="javascript: void(0)"><?= _("Trésorerie") ?></a></li>
                            <li class="breadcrumb-item"><a href="/treasury/sessions"><?= _("Sessions de Caisse") ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _("Détail de la Session N°") ?><?= $session['id'] ?></li>
                        </ul>
                    </div>
                    <div class="col-md-12">
                        <div class="page-header-title d-flex justify-content-between align-items-center">
                            <h2 class="mb-0"><?= _("Détail de la Session N°") ?><?= $session['id'] ?></h2>
                            <div>
                                <?php if ($session['statut'] === 'ouverte'): ?>
                                    <span class="badge bg-light-primary text-primary fs-6"><?= _("OUVERTE") ?></span>
                                <?php elseif ($session['statut'] === 'fermee_a_valider'): ?>
                                    <span class="badge bg-light-warning text-warning fs-6"><?= _("EN ATTENTE DE VALIDATION") ?></span>
                                <?php else: ?>
                                    <span class="badge bg-light-success text-success fs-6"><?= _("CLÔTURÉE & VALIDÉE") ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $_SESSION['error_message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <div class="row">
            <!-- Sidebar info/close -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5><?= _("Résumé Opérationnel") ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3 border-bottom pb-2">
                            <span class="text-muted d-block small"><?= _("Caisse Physique") ?></span>
                            <strong class="fs-5 text-dark"><?= htmlspecialchars($compte['nom_compte']) ?></strong>
                        </div>
                        <div class="mb-3 border-bottom pb-2">
                            <span class="text-muted d-block small"><?= _("Report d'ouverture") ?></span>
                            <strong class="fs-5 text-dark"><?= number_format($session['solde_ouverture'], 2, ',', ' ') ?> FCFA</strong>
                        </div>
                        <div class="mb-3 border-bottom pb-2">
                            <span class="text-muted d-block small"><?= _("Total des encaissements (Entrées)") ?></span>
                            <strong class="fs-5 text-success">+ <?= number_format($totalEntrees, 2, ',', ' ') ?> FCFA</strong>
                        </div>
                        <div class="mb-3 border-bottom pb-2">
                            <span class="text-muted d-block small"><?= _("Total des décaissements (Sorties)") ?></span>
                            <strong class="fs-5 text-danger">- <?= number_format($totalSorties, 2, ',', ' ') ?> FCFA</strong>
                        </div>
                        <div class="mb-3 border-bottom pb-2">
                            <span class="text-muted d-block small"><?= _("Solde Théorique de Caisse") ?></span>
                            <strong class="fs-4 text-primary"><?= number_format($soldeTheorique, 2, ',', ' ') ?> FCFA</strong>
                        </div>

                        <?php if ($session['statut'] !== 'ouverte'): ?>
                            <div class="mb-3 border-bottom pb-2">
                                <span class="text-muted d-block small"><?= _("Montant Réel Déclaré") ?></span>
                                <strong class="fs-4 text-dark"><?= number_format($session['solde_reel'], 2, ',', ' ') ?> FCFA</strong>
                            </div>
                            <div class="mb-3 border-bottom pb-2">
                                <span class="text-muted d-block small"><?= _("Écart constaté") ?></span>
                                <strong class="fs-4 <?= $session['ecart'] < 0 ? 'text-danger' : ($session['ecart'] > 0 ? 'text-warning' : 'text-success') ?>">
                                    <?= $session['ecart'] > 0 ? '+' : '' ?><?= number_format($session['ecart'], 2, ',', ' ') ?> FCFA
                                </strong>
                            </div>
                            <?php if (!empty($session['justificatif_ecart'])): ?>
                                <div class="mb-3">
                                    <span class="text-muted d-block small"><?= _("Justification de l'écart") ?></span>
                                    <p class="text-dark bg-light p-2 rounded small"><?= htmlspecialchars($session['justificatif_ecart']) ?></p>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <!-- Close Form for Caissier -->
                        <?php if ($session['statut'] === 'ouverte' && $session['user_id'] == Auth::getUserId()): ?>
                            <hr>
                            <form action="/treasury/sessions/close" method="POST">
                                <input type="hidden" name="id" value="<?= $session['id'] ?>">

                                <div class="mb-3">
                                    <label for="solde_reel" class="form-label fw-bold"><?= _("Argent Réel Compté (Espèces)") ?> <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" class="form-control" id="solde_reel" name="solde_reel" required placeholder="0.00">
                                        <span class="input-group-text">FCFA</span>
                                    </div>
                                </div>

                                <div class="mb-3 d-none" id="motif_ecart_block">
                                    <label for="justificatif" class="form-label fw-bold text-danger"><?= _("Justifier l'écart de caisse") ?> <span class="text-danger">*</span></label>
                                    <textarea class="form-control border-danger" id="justificatif" name="justificatif" rows="3" placeholder="<?= _("Saisissez le motif de la différence...") ?>"></textarea>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-danger d-inline-flex align-items-center justify-content-center">
                                        <i class="ph-duotone ph-lock me-2"></i><?= _("Soumettre pour clôture") ?>
                                    </button>
                                </div>
                            </form>

                            <script>
                                document.getElementById('solde_reel').addEventListener('input', function() {
                                    const theorique = <?= $soldeTheorique ?>;
                                    const reel = parseFloat(this.value) || 0;
                                    const block = document.getElementById('motif_ecart_block');
                                    const textarea = document.getElementById('justificatif');

                                    if (Math.abs(reel - theorique) > 0.01) {
                                        block.classList.remove('d-none');
                                        textarea.setAttribute('required', 'required');
                                    } else {
                                        block.classList.add('d-none');
                                        textarea.removeAttribute('required');
                                    }
                                });
                            </script>
                        <?php endif; ?>

                        <!-- Validation Panel for Chef Comptable/Admin -->
                        <?php if ($session['statut'] === 'fermee_a_valider' && Auth::can('validate', 'sessions_caisse')): ?>
                            <hr>
                            <?php if ($session['user_id'] == Auth::getUserId()): ?>
                                <div class="alert alert-warning border border-warning small">
                                    <i class="ph-duotone ph-warning-circle me-1"></i>
                                    <?= _("Règle de séparation des tâches : vous ne pouvez pas approuver ou valider votre propre session de caisse.") ?>
                                </div>
                            <?php else: ?>
                                <h5><?= _("Contrôle d'Audit de Caisse") ?></h5>
                                <form action="/treasury/sessions/approve" method="POST">
                                    <input type="hidden" name="id" value="<?= $session['id'] ?>">

                                    <div class="mb-3">
                                        <label for="motif_validation" class="form-label fw-bold"><?= _("Commentaire d'audit / Validation") ?></label>
                                        <textarea class="form-control" id="motif_validation" name="motif_validation" rows="3" placeholder="<?= _("Saisissez vos remarques de contrôle...") ?>"></textarea>
                                    </div>

                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-success d-inline-flex align-items-center justify-content-center">
                                            <i class="ph-duotone ph-check-circle me-2"></i><?= _("Approuver et valider") ?>
                                        </button>
                                    </div>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- List of movements -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5><?= _("Mouvements de Trésorerie de la Session") ?></h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th><?= _("Date") ?></th>
                                        <th><?= _("Référence") ?></th>
                                        <th><?= _("Type d'opération") ?></th>
                                        <th><?= _("Mode") ?></th>
                                        <th><?= _("Événement") ?></th>
                                        <th><?= _("Désignation / Motif") ?></th>
                                        <th class="text-end"><?= _("Montant") ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($movements)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4 text-muted"><?= _("Aucun mouvement enregistré durant cette session.") ?></td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($movements as $m): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($m['date_mouvement']) ?></td>
                                                <td><code><?= htmlspecialchars($m['reference_transaction'] ?: '-') ?></code></td>
                                                <td><span class="badge bg-light-secondary text-secondary"><?= htmlspecialchars($m['source_type']) ?></span></td>
                                                <td><?= htmlspecialchars($m['mode_paiement']) ?></td>
                                                <td>
                                                    <?php if ($m['evenement_type'] === 'encaissement'): ?>
                                                        <span class="badge bg-light-success text-success"><?= _("Encaissement") ?></span>
                                                    <?php elseif ($m['evenement_type'] === 'annulation'): ?>
                                                        <span class="badge bg-light-danger text-danger"><?= _("Annulation") ?></span>
                                                    <?php elseif ($m['evenement_type'] === 'remboursement'): ?>
                                                        <span class="badge bg-light-warning text-warning"><?= _("Remboursement") ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-light-info text-info"><?= _("Correction") ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><small class="text-muted"><?= htmlspecialchars($m['motif']) ?></small></td>
                                                <td class="text-end fw-bold <?= $m['type_mouvement'] === 'entree' ? 'text-success' : 'text-danger' ?>">
                                                    <?= $m['type_mouvement'] === 'entree' ? '+' : '-' ?> <?= number_format($m['montant'], 2, ',', ' ') ?> FCFA
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

<?php include __DIR__ . '/../../layouts/footer_able.php'; ?>