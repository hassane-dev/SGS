<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h5 class="m-b-10"><?= _('Gestion des Types de Contrat') ?></h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/home"><?= _('Tableau de Bord') ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _('Types de Contrat') ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-end">
                            <a href="/contrats/create" class="btn btn-primary">
                                <i class="ti ti-plus"></i> <?= _('Ajouter un type de contrat') ?>
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th scope="col"><?= _('Libellé') ?></th>
                                        <th scope="col"><?= _('Type de Paiement') ?></th>
                                        <th scope="col"><?= _('Prise en Charge') ?></th>
                                        <th scope="col" class="text-end"><?= _('Actions') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($contrats)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center"><?= _('Aucun type de contrat trouvé.') ?></td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($contrats as $contrat): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($contrat['libelle']) ?></td>
                                                <td>
                                                    <span class="badge bg-light-secondary">
                                                        <?= htmlspecialchars(_(ucfirst(str_replace('_', ' ', $contrat['type_paiement'] ?? '')))) ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars(_($contrat['prise_en_charge'] ?? '')) ?></td>
                                                <td class="text-end">
                                                    <a href="/contrats/edit?id=<?= $contrat['id_contrat'] ?>" class="btn btn-sm btn-icon btn-light-primary" title="<?= _('Modifier') ?>">
                                                        <i class="ti ti-edit"></i>
                                                    </a>
                                                    <form action="/contrats/destroy" method="POST" class="d-inline ms-2" onsubmit="return confirm('<?= _('Êtes-vous sûr ?') ?>');">
                                                        <input type="hidden" name="id" value="<?= $contrat['id_contrat'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-icon btn-light-danger" title="<?= _('Supprimer') ?>">
                                                            <i class="ti ti-trash"></i>
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
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
