<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>

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

        <?php if (!empty($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                <?= htmlspecialchars($_SESSION['success_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                <?= htmlspecialchars($_SESSION['error_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <div class="alert alert-info d-flex align-items-center gap-3 mb-4" role="alert">
            <i class="ph-duotone ph-info fs-3"></i>
            <div>
                <strong><?= _('Référentiel des Types de Contrat') ?> :</strong>
                <?= _('Cette page permet de configurer les catégories de contrat (CDI, CDD, Vacataire...). Pour consulter ou gérer les contrats individuels, avenants et rémunérations du personnel, utilisez la') ?>
                <a href="/drh" class="alert-link fw-bold text-decoration-underline"><?= _('Fiche Personnel 360° du Module DRH') ?></a>.
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-end">
                            <a href="/contrats/create" class="btn btn-primary">
                                <i class="ph-duotone ph-plus"></i> <?= _('Ajouter un type de contrat') ?>
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
                                                        <i class="ph-duotone ph-pencil"></i>
                                                    </a>
                                                    <form action="/contrats/destroy" method="POST" class="d-inline ms-2" onsubmit="return confirm('<?= _('Êtes-vous sûr ?') ?>');">
                                                        <input type="hidden" name="id" value="<?= $contrat['id_contrat'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-icon btn-light-danger" title="<?= _('Supprimer') ?>">
                                                            <i class="ph-duotone ph-trash"></i>
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
