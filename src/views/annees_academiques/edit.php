<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-lg">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= _('Modifier l\'Année Académique') ?></h1>
        <a href="/annees-academiques" class="btn btn-secondary"><?= _('Retour à la liste') ?></a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <?php
                $is_edit = true;
                $form_action = "/annees-academiques/update?id=" . $annee['id'];
                require '_form.php';
            ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>