<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="max-w-4xl mx-auto">
    <h2 class="text-2xl font-bold mb-6"><?= _('Ajouter un nouveau membre du personnel') ?></h2>

    <div class="bg-white p-8 rounded-lg shadow-lg">
        <?php
        // Define variables for the form partial
        $form_action = '/users/store';
        // $is_edit is already set in the controller
        // $user is already set in the controller
        // $roles, $contrats, $lycees are already set in the controller

        // Include the form partial
        require __DIR__ . '/_form.php';
        ?>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>