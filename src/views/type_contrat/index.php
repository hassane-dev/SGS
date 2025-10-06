<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fs-2 fw-bold"><?= _('Gestion des Types de Contrat') ?></h2>
    <a href="/contrats/create" class="btn btn-primary">
        <?= _('Ajouter un type de contrat') ?>
    </a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <table class="table table-hover">
            <thead class="table-light">
                <tr>
                    <th scope="col"><?= _('Libellé') ?></th>
                    <th scope="col"><?= _('Type de Paiement') ?></th>
                    <th scope="col"><?= _('Prise en Charge') ?></th>
                    <th scope="col" class="text-end"><?= _('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($contrats as $contrat): ?>
                    <tr>
                        <td><?= htmlspecialchars($contrat['libelle']) ?></td>
                        <td><?= htmlspecialchars(_(ucfirst(str_replace('_', ' ', $contrat['type_paiement'])))) ?></td>
                        <td><?= htmlspecialchars(_($contrat['prise_en_charge'])) ?></td>
                        <td class="text-end">
                            <a href="/contrats/edit?id=<?= $contrat['id_contrat'] ?>" class="btn btn-sm btn-outline-primary"><?= _('Modifier') ?></a>
                            <form action="/contrats/destroy" method="POST" class="d-inline ms-2" onsubmit="return confirm('<?= _('Êtes-vous sûr ?') ?>');">
                                <input type="hidden" name="id" value="<?= $contrat['id_contrat'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"><?= _('Supprimer') ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>