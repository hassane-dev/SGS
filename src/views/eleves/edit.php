<?php
$title = "Modifier l'Élève";
ob_start();
?>

<div class="container">
    <h1>Modifier l'Élève</h1>
    <form action="/eleves/update" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id_eleve" value="<?= $eleve['id_eleve'] ?>">
        <?php include '_form.php'; ?>
        <button type="submit" class="btn btn-primary mt-4">Mettre à jour</button>
        <a href="/eleves" class="btn btn-secondary mt-4">Annuler</a>
    </form>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/header.php';
?>