<?php
$title = "Gestion de la Grille Tarifaire";
ob_start();

require_once __DIR__ . '/../layouts/header_able.php';
require_once __DIR__ . '/../layouts/sidebar_able.php';
?>

<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h5 class="m-b-10"><?= $title ?></h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/home">Tableau de bord</a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= $title ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- [ Main Content ] start -->
        <div class="row">
            <!-- [ sample-page ] start -->
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Liste des Grilles Tarifaires - Année <?= htmlspecialchars($activeYear['libelle'] ?? 'N/A') ?></h5>
                        <a href="/frais/create" class="btn btn-primary">Ajouter une grille</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped" id="fraisTable">
                                <thead>
                                    <tr>
                                        <th>Configuration</th>
                                        <th>Détails</th>
                                        <th>Inscription</th>
                                        <th>Mensualité</th>
                                        <th>Logo</th>
                                        <th>Carte</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($frais)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">Aucune grille tarifaire définie pour cette année.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($frais as $f): ?>
                                            <tr>
                                                <td>
                                                    <?php if (!empty($f['cycle'])): ?>
                                                        <span class="badge bg-light-primary">Cycle</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-light-info">Plage de Niveaux</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($f['cycle'])): ?>
                                                        <?= htmlspecialchars($f['cycle']) ?>
                                                    <?php else: ?>
                                                        De <strong><?= htmlspecialchars($f['niveau_debut']) ?></strong> à <strong><?= htmlspecialchars($f['niveau_fin']) ?></strong>
                                                        <?php if (!empty($f['serie'])): ?>
                                                            (Série: <?= htmlspecialchars($f['serie']) ?>)
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars(number_format($f['frais_inscription'], 0, ',', ' ')) ?></td>
                                                <td><?= htmlspecialchars(number_format($f['frais_mensuel'], 0, ',', ' ')) ?></td>
                                                <td><?= $f['frais_logo'] ? htmlspecialchars(number_format($f['frais_logo'], 0, ',', ' ')) : '-' ?></td>
                                                <td><?= $f['frais_carte'] ? htmlspecialchars(number_format($f['frais_carte'], 0, ',', ' ')) : '-' ?></td>
                                                <td>
                                                    <!-- Actions buttons (edit, delete) can be added here -->
                                                    <a href="#" class="btn btn-sm btn-outline-primary">Modifier</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <!-- [ sample-page ] end -->
        </div>
        <!-- [ Main Content ] end -->
    </div>
</div>

<?php
require_once __DIR__ . '/../layouts/footer_able.php';
$content = ob_get_clean();
echo $content;
?>