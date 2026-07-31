<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= _('Validation de Demande de Dépense') ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <pre style="white-space: pre-wrap; margin-bottom: 0; font-family: inherit;"><?= htmlspecialchars($_SESSION['error_message']) ?></pre>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><?= _('Détails de l\'engagement') ?></h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr>
                                <th class="ps-0" style="width: 40%;"><?= _('Pièce N°') ?></th>
                                <td><strong><?= htmlspecialchars($depense['numero_piece']) ?></strong></td>
                            </tr>
                            <tr>
                                <th class="ps-0"><?= _('Montant total') ?></th>
                                <td><h4 class="text-primary mb-0"><?= number_format($depense['montant'], 2, ',', ' ') ?> FCFA</h4></td>
                            </tr>
                            <tr>
                                <th class="ps-0"><?= _('Motif/Désignation') ?></th>
                                <td><?= htmlspecialchars($depense['motif']) ?></td>
                            </tr>
                            <tr>
                                <th class="ps-0"><?= _('Date d\'engagement') ?></th>
                                <td><?= date('d/m/Y H:i', strtotime($depense['date_creation'])) ?></td>
                            </tr>
                        </table>

                        <?php if (!empty($pieces)): ?>
                            <h5 class="mt-4 mb-2"><?= _('Justificatifs joints') ?></h5>
                            <div class="list-group">
                                <?php foreach ($pieces as $p): ?>
                                    <a href="<?= htmlspecialchars($p['chemin_fichier']) ?>" target="_blank" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                        <span><i class="ti ti-file-text me-2"></i> <?= htmlspecialchars($p['nom_fichier']) ?></span>
                                        <span class="badge bg-light-secondary text-dark"><?= number_format($p['taille_octets'] / 1024, 1) ?> Ko</span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5><?= _('Avis / Décision du Validateur') ?></h5>
                    </div>
                    <form action="/depenses/vote/<?= $depense['id'] ?>" method="POST" class="card-body">
                        <div class="mb-3">
                            <label class="form-label"><?= _('Commentaire ou motif de décision') ?></label>
                            <textarea name="motif_vote" class="form-control" rows="4" required placeholder="<?= _('Saisissez une observation pour motiver votre vote...') ?>"></textarea>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="/depenses" class="btn btn-outline-secondary"><?= _('Retour') ?></a>
                            <div>
                                <button type="submit" name="vote_action" value="reject" class="btn btn-danger me-2">
                                    <i class="ti ti-circle-x"></i> <?= _('Rejeter la demande') ?>
                                </button>
                                <button type="submit" name="vote_action" value="approve" class="btn btn-success">
                                    <i class="ti ti-circle-check"></i> <?= _('Approuver la demande') ?>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>