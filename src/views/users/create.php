<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-lg">
    <h2 class="fs-2 fw-bold mb-4"><?= _('Ajouter un nouveau membre du personnel') ?></h2>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <?php
            // Define variables for the form partial
            $form_action = '/users/store';
            // $is_edit, $user, $roles, $contrats, $lycees are already set in the controller

            // Include the form partial
            require __DIR__ . '/_form.php';
            ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>