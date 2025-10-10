<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= _('Gestion des Élèves') ?></h1>
        <a href="/eleves/create" class="btn btn-primary">
            <i class="fas fa-plus"></i> <?= _('Ajouter un élève') ?>
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">
            <h6 class="m-0 font-weight-bold text-primary"><?= _('Liste des Élèves') ?></h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th><?= _('Photo') ?></th>
                            <th><?= _('Nom Complet') ?></th>
                            <th><?= _('Email') ?></th>
                            <th class="text-end"><?= _('Actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($eleves)): ?>
                            <tr>
                                <td colspan="4" class="text-center"><?= _('Aucun élève trouvé.') ?></td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($eleves as $eleve): ?>
                                <tr>
                                    <td class="text-center">
                                        <img src="<?= htmlspecialchars($eleve['photo'] ?? '/assets/img/default-avatar.png') ?>" alt="<?= _('Photo de l\'élève') ?>" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                                    </td>
                                    <td><?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></td>
                                    <td><?= htmlspecialchars($eleve['email']) ?></td>
                                    <td class="text-end">
                                        <a href="/eleves/details?id=<?= $eleve['id_eleve'] ?>" class="btn btn-info btn-sm" title="<?= _('Détails et Bulletin') ?>">
                                            <i class="fas fa-info-circle"></i>
                                        </a>
                                        <a href="/paiements?eleve_id=<?= $eleve['id_eleve'] ?>" class="btn btn-warning btn-sm" title="<?= _('Voir les Paiements') ?>">
                                            <i class="fas fa-dollar-sign"></i>
                                        </a>
                                        <a href="/inscriptions/show?eleve_id=<?= $eleve['id_eleve'] ?>" class="btn btn-success btn-sm" title="<?= _('Inscrire à une classe') ?>">
                                            <i class="fas fa-user-plus"></i>
                                        </a>
                                        <a href="/eleves/edit?id=<?= $eleve['id_eleve'] ?>" class="btn btn-secondary btn-sm" title="<?= _('Modifier') ?>">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="/eleves/destroy" method="POST" class="d-inline" onsubmit="return confirm('<?= _('Êtes-vous sûr de vouloir supprimer cet élève ?') ?>');">
                                            <input type="hidden" name="id" value="<?= $eleve['id_eleve'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" title="<?= _('Supprimer') ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
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

<!-- Font Awesome for icons -->
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>