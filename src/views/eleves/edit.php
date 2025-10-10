<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-lg">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= _('Modifier l\'élève') ?></h1>
        <a href="/eleves" class="btn btn-secondary"><?= _('Retour à la liste') ?></a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <?php
                $is_edit = true;
                $form_action = "/eleves/update?id=" . $eleve['id_eleve'];
                require '_form.php';
            ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>