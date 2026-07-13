<?php
$title = "Contrôle Financier & Droits Établissement";
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
                            <h5 class="m-b-10"><?= htmlspecialchars($title) ?></h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/home">Tableau de Bord</a></li>
                            <li class="breadcrumb-item" aria-current="page">Contrôle Financier</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- [ Stats Widgets ] start -->
        <div class="row">
            <!-- Total Students -->
            <div class="col-md-6 col-xl-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avtar avtar-s bg-light-primary">
                                    <i class="ph-duotone ph-users text-primary f-24"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h3 class="mb-0"><?= $stats['total_students'] ?></h3>
                                <p class="text-muted mb-0">Élèves Enregistrés</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Exonerated/Advantaged Students -->
            <div class="col-md-6 col-xl-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avtar avtar-s bg-light-success">
                                    <i class="ph-duotone ph-medal text-success f-24"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h3 class="mb-0"><?= $stats['advantages_count'] ?></h3>
                                <p class="text-muted mb-0">Élèves avec Avantages</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes consultation blocked -->
            <div class="col-md-6 col-xl-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avtar avtar-s bg-light-warning">
                                    <i class="ph-duotone ph-lock text-warning f-24"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h3 class="mb-0"><?= $stats['blocked_notes_count'] ?></h3>
                                <p class="text-muted mb-0">Consultation Notes Bloquée</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bulletin printing blocked -->
            <div class="col-md-6 col-xl-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="avtar avtar-s bg-light-danger">
                                    <i class="ph-duotone ph-file-text text-danger f-24"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h3 class="mb-0"><?= $stats['blocked_bulletins_count'] ?></h3>
                                <p class="text-muted mb-0">Impression Bulletins Bloquée</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ Stats Widgets ] end -->

        <!-- [ Main Content ] start -->
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Rapport des États Financiers et Droits d'Accès</h5>
                        <span class="badge bg-light-dark">Année: <?= htmlspecialchars($activeYear['libelle']) ?></span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="controlFinanceTable">
                                <thead>
                                    <tr>
                                        <th>Élève</th>
                                        <th>Classe</th>
                                        <th>Politique Particulière</th>
                                        <th>Inscription</th>
                                        <th>Mensualités</th>
                                        <th>Accès Notes</th>
                                        <th>Impression Bulletin</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($students)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center text-muted">Aucun élève inscrit pour l'année en cours.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($students as $s): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($s['prenom'] . ' ' . $s['nom']) ?></strong>
                                                    <span class="d-block text-muted small">ID: #<?= $s['id_eleve'] ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light-secondary"><?= htmlspecialchars(Classe::getFormattedName($s['niveau'], $s['serie'], $s['numero'])) ?></span>
                                                </td>
                                                <td>
                                                    <?php if (!empty($s['type_avantage']) && $s['type_avantage'] !== 'Aucun'): ?>
                                                        <span class="badge bg-light-success text-success"><i class="ph-duotone ph-award me-1"></i><?= htmlspecialchars($s['type_avantage']) ?></span>
                                                        <span class="d-block text-muted small"><?= $s['valeur_type'] === 'Pourcentage' ? $s['valeur'] . '%' : number_format($s['valeur'], 0, ',', ' ') . ' FCFA' ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted small">Standard</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (($s['inscription_statut'] ?? '') === 'Payée'): ?>
                                                        <span class="badge bg-light-success text-success">Payée</span>
                                                    <?php elseif (($s['inscription_statut'] ?? '') === 'Partiellement payée'): ?>
                                                        <span class="badge bg-light-warning text-warning">Partiel</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-light-danger text-danger">Impayée</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (($s['mensualite_statut'] ?? '') === 'À jour'): ?>
                                                        <span class="badge bg-light-success text-success">À jour</span>
                                                    <?php elseif (($s['mensualite_statut'] ?? '') === 'Partiellement payée'): ?>
                                                        <span class="badge bg-light-warning text-warning">Partiel</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-light-danger text-danger">En retard</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (($s['notes_consultation'] ?? '') === 'Autorisée'): ?>
                                                        <span class="badge bg-light-success text-success"><i class="ph-duotone ph-check-circle me-1"></i>Autorisée</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-light-danger text-danger"><i class="ph-duotone ph-x-circle me-1"></i>Interdite</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (($s['bulletin_impression'] ?? '') === 'Autorisée'): ?>
                                                        <span class="badge bg-light-success text-success"><i class="ph-duotone ph-check-circle me-1"></i>Autorisée</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-light-danger text-danger"><i class="ph-duotone ph-x-circle me-1"></i>Interdite</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="/eleves/parametres-financiers/<?= $s['id_eleve'] ?>" class="btn btn-sm btn-outline-primary" title="Avantages financiers">
                                                            <i class="ph-duotone ph-gear"></i>
                                                        </a>
                                                        <a href="/paiements/show/<?= $s['id_eleve'] ?>" class="btn btn-sm btn-outline-secondary" title="Dossier comptable">
                                                            <i class="ph-duotone ph-currency-dollar"></i>
                                                        </a>
                                                    </div>
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
        </div>
        <!-- [ Main Content ] end -->
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Standard table filter or enhancements can be added here
});
</script>

<?php
require_once __DIR__ . '/../layouts/footer_able.php';
$content = ob_get_clean();
echo $content;
?>
