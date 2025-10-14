<?php
$title = "Gestion de la Grille Tarifaire";
ob_start();
?>

<div class="container">
    <h1>Grille Tarifaire - Année <?= htmlspecialchars($activeYear['libelle']) ?></h1>

    <div class="card mt-4">
        <div class="card-header">
            <h4>Ajouter une nouvelle grille de frais</h4>
        </div>
        <div class="card-body">
            <form action="/frais/store" method="POST">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="niveau">Niveau</label>
                            <input type="text" class="form-control" name="niveau" placeholder="Ex: 6ème, Seconde" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="serie">Série (Optionnel)</label>
                            <input type="text" class="form-control" name="serie" placeholder="Ex: C, D, A4">
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="frais_inscription">Frais d'Inscription</label>
                            <input type="number" step="0.01" class="form-control" name="frais_inscription" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="frais_mensuel">Frais Mensuel</label>
                            <input type="number" step="0.01" class="form-control" name="frais_mensuel" required>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary mt-4">Enregistrer</button>
            </form>
        </div>
    </div>

    <h3 class="mt-5">Grilles Existantes</h3>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Niveau</th>
                <th>Série</th>
                <th>Frais d'Inscription</th>
                <th>Frais Mensuel</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($frais)): ?>
                <tr>
                    <td colspan="4" class="text-center">Aucune grille tarifaire définie pour cette année.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($frais as $f): ?>
                    <tr>
                        <td><?= htmlspecialchars($f['niveau']) ?></td>
                        <td><?= htmlspecialchars($f['serie']) ?></td>
                        <td><?= htmlspecialchars(number_format($f['frais_inscription'], 2)) ?></td>
                        <td><?= htmlspecialchars(number_format($f['frais_mensuel'], 2)) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/header.php';
?>