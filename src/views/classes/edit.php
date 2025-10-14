<?php
$title = "Modifier la Classe";
ob_start();
?>

<h1 class="h2">Modifier la Classe</h1>

<div class="card mt-4">
    <div class="card-body">
        <form action="/classes/update" method="POST">
            <input type="hidden" name="id_classe" value="<?= $classe['id_classe'] ?>">
            <?php include '_form.php'; ?>
            <button type="submit" class="btn btn-primary mt-3">Mettre à jour</button>
            <a href="/classes" class="btn btn-secondary mt-3">Annuler</a>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>