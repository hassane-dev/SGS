<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= _('Détail de l\'Élève') ?></h1>
        <a href="/eleves" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> <?= _('Retour à la liste') ?>
        </a>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-body text-center">
                    <img src="<?= htmlspecialchars($eleve['photo'] ?? '/assets/img/default-avatar.png') ?>" alt="<?= _('Photo de l\'élève') ?>" class="rounded-circle img-fluid" style="width: 150px; height: 150px; object-fit: cover;">
                    <h4 class="my-3"><?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></h4>
                    <p class="text-muted mb-1"><?= htmlspecialchars($eleve['email']) ?></p>
                    <p class="text-muted mb-4"><?= htmlspecialchars($eleve['telephone']) ?></p>
                </div>
            </div>
            <div class="card shadow-sm mb-4">
                 <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary"><?= _('Actions Rapides') ?></h6>
                </div>
                <div class="list-group list-group-flush">
                    <a href="/paiements?eleve_id=<?= $eleve['id_eleve'] ?>" class="list-group-item list-group-item-action"><?= _('Historique des Paiements') ?></a>
                    <a href="/tests_entree?eleve_id=<?= $eleve['id_eleve'] ?>" class="list-group-item list-group-item-action"><?= _('Tests d\'Entrée') ?></a>
                    <a href="/carte/generer?eleve_id=<?= $eleve['id_eleve'] ?>" class="list-group-item list-group-item-action" target="_blank"><?= _('Générer la Carte Scolaire') ?></a>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary"><?= _('Historique des Inscriptions') ?></h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th><?= _('Année Académique') ?></th>
                                    <th><?= _('Classe') ?></th>
                                    <th><?= _('Statut') ?></th>
                                    <th class="text-end"><?= _('Actions') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($etudes)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center"><?= _('Aucune inscription trouvée.') ?></td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($etudes as $etude): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($etude['annee_academique']) ?></td>
                                            <td><?= htmlspecialchars($etude['nom_classe'] . ' (' . $etude['serie'] . ')') ?></td>
                                            <td>
                                                <?php if ($etude['actif']): ?>
                                                    <span class="badge bg-success"><?= _('Actif') ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger"><?= _('Inactif') ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <a href="/bulletin/show?etude_id=<?= $etude['id_etude'] ?>" class="btn btn-info btn-sm" title="<?= _('Voir le Bulletin') ?>">
                                                    <i class="fas fa-file-alt"></i>
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

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>