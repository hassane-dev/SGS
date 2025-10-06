<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-lg">
    <h2 class="fs-2 fw-bold mb-4"><?= _('Ajouter un nouveau type de contrat') ?></h2>

    <div class="card shadow-sm">
        <div class="card-body">
            <?php
            $form_action = '/contrats/store';
            // $contrat, $is_edit, $lycees are prepared in the controller
            require __DIR__ . '/_form.php';
            ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>