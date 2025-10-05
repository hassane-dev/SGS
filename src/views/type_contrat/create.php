<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="max-w-3xl mx-auto">
    <h2 class="text-2xl font-bold mb-6"><?= _('Ajouter un nouveau type de contrat') ?></h2>

    <div class="bg-white p-8 rounded-lg shadow-lg">
        <?php
        $form_action = '/contrats/store';
        // $contrat, $is_edit, $lycees are prepared in the controller
        require __DIR__ . '/_form.php';
        ?>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>