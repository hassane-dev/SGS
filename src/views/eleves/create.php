<?php
$title = "Nouvel Élève";
ob_start();
?>

<div class="container">
    <h1>Nouvel Élève</h1>
    <form action="/eleves/store" method="POST" enctype="multipart/form-data">
        <?php include '_form.php'; ?>
        <button type="submit" class="btn btn-primary mt-4">Enregistrer</button>
        <a href="/eleves" class="btn btn-secondary mt-4">Annuler</a>
    </form>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/header.php';
?>