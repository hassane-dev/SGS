<?php
$title = "Tableau de Bord";
ob_start();
?>

<h1 class="h2">Tableau de Bord</h1>
<p>Bienvenue, <?= htmlspecialchars(Auth::get('prenom') . ' ' . Auth::get('nom')) ?>.</p>

<div class="row mt-4">
    <div class="col-md-4">
        <div class="card text-white bg-primary mb-3">
            <div class="card-header">Élèves</div>
            <div class="card-body">
                <h5 class="card-title">XX</h5>
                <p class="card-text">Nombre total d'élèves actifs.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-success mb-3">
            <div class="card-header">Enseignants</div>
            <div class="card-body">
                <h5 class="card-title">XX</h5>
                <p class="card-text">Nombre total d'enseignants.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-dark bg-warning mb-3">
            <div class="card-header">Inscriptions en Attente</div>
            <div class="card-body">
                <h5 class="card-title"><?= $pending_enrollments_count ?? 0 ?></h5>
                <p class="card-text">Validations de paiement requises.</p>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layouts/main.php';
?>