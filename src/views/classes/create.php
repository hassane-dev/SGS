<?php
$title = "Créer une Classe";
ob_start();
?>

<h1 class="h2">Créer une Nouvelle Classe</h1>

<div class="card mt-4">
    <div class="card-body">
        <form action="/classes/store" method="POST">
            <?php include '_form.php'; ?>
            <button type="submit" class="btn btn-primary mt-3">Enregistrer</button>
            <a href="/classes" class="btn btn-secondary mt-3">Annuler</a>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>