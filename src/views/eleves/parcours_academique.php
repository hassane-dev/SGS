<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<!-- [ Main Content ] start -->
<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= _('Parcours Académique Longitudinal') ?></h2>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <a href="/eleves/details?id=<?= $eleve['id_eleve'] ?>" class="btn btn-secondary">
                            <i class="ph-duotone ph-arrow-left me-1"></i>
                            <?= _('Retour au Dossier') ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- Navigation Tabs -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="card mb-0">
                    <div class="card-body p-2">
                        <ul class="nav nav-pills" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link" href="/eleves/details?id=<?= $eleve['id_eleve'] ?>"><i class="ph-duotone ph-user me-2"></i>Dossier & Informations</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/eleves/parametres-financiers?id=<?= $eleve['id_eleve'] ?>"><i class="ph-duotone ph-currency-dollar me-2"></i>Paramètres Financiers</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link active" href="/eleves/parcours-academique?id=<?= $eleve['id_eleve'] ?>"><i class="ph-duotone ph-chart-line-up me-2"></i>Parcours Académique</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Identity Summary Card -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-light-primary border-primary">
                    <div class="card-body d-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="mb-1 text-primary"><?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></h4>
                            <p class="mb-0 text-muted">
                                <strong>Matricule :</strong> <?= htmlspecialchars($eleve['identifiant_public'] ?? 'N/A') ?> |
                                <strong>Statut :</strong> <span class="badge bg-success"><?= htmlspecialchars(ucfirst($eleve['statut'])) ?></span>
                            </p>
                        </div>
                        <div class="text-end">
                            <i class="ph-duotone ph-student f-36 text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 1 : Reconstitution Chronologique Multi-Années -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="ph-duotone ph-clock-counter-clockwise me-2"></i><?= _('Reconstitution Chronologique du Parcours') ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($timeline)): ?>
                            <div class="alert alert-info mb-0">
                                <i class="ph-duotone ph-info me-2"></i><?= _('Aucun historique académique enregistré pour cet élève.') ?>
                            </div>
                        <?php else: ?>
                            <div class="accordion" id="accordionTimeline">
                                <?php foreach ($timeline as $index => $item): ?>
                                    <?php
                                        $etude = $item['etude'];
                                        $matieres = $item['matieres'];
                                        $collapseId = 'collapseYear_' . $etude['id_etude'];
                                        $headingId = 'headingYear_' . $etude['id_etude'];
                                        $isLatest = ($index === count($timeline) - 1);
                                    ?>
                                    <div class="accordion-item mb-2 border rounded">
                                        <h2 class="accordion-header" id="<?= $headingId ?>">
                                            <button class="accordion-button <?= $isLatest ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $collapseId ?>" aria-expanded="<?= $isLatest ? 'true' : 'false' ?>" aria-controls="<?= $collapseId ?>">
                                                <div class="d-flex align-items-center justify-content-between w-100 me-3">
                                                    <div>
                                                        <strong class="text-primary fs-5"><?= htmlspecialchars($etude['annee_libelle']) ?></strong>
                                                        <span class="ms-2 badge bg-light-secondary text-dark">
                                                            Classe : <?= htmlspecialchars(trim(($etude['niveau'] ?? '') . ' ' . ($etude['serie'] ?? '') . ' ' . ($etude['classe_numero'] ?? ''))) ?>
                                                        </span>
                                                        <?php if (!empty($etude['nom_cycle'])): ?>
                                                            <span class="badge bg-light-info text-info me-1"><?= htmlspecialchars($etude['nom_cycle']) ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div>
                                                        <?php if ($etude['is_active']): ?>
                                                            <span class="badge bg-success"><?= _('Inscription Active') ?></span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary"><?= _('Historique') ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </button>
                                        </h2>
                                        <div id="<?= $collapseId ?>" class="accordion-collapse collapse <?= $isLatest ? 'show' : '' ?>" aria-labelledby="<?= $headingId ?>" data-bs-parent="#accordionTimeline">
                                            <div class="accordion-body">
                                                <?php if (empty($matieres)): ?>
                                                    <p class="text-muted mb-0"><?= _('Aucune évaluation enregistrée pour cette année académique.') ?></p>
                                                <?php else: ?>
                                                    <div class="table-responsive">
                                                        <table class="table table-bordered table-sm align-middle">
                                                            <thead class="table-light">
                                                                <tr>
                                                                    <th><?= _('Matière') ?></th>
                                                                    <th><?= _('Séquence') ?></th>
                                                                    <th><?= _('Type Évaluation') ?></th>
                                                                    <th class="text-center"><?= _('Note') ?></th>
                                                                    <th class="text-center"><?= _('Coef (Historique)') ?></th>
                                                                    <th><?= _('Appréciation / Date') ?></th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php foreach ($matieres as $m): ?>
                                                                    <?php foreach ($m['sequences'] as $seq): ?>
                                                                        <?php foreach ($seq['evaluations'] as $ev): ?>
                                                                            <tr>
                                                                                <td class="fw-bold"><?= htmlspecialchars($m['nom_matiere']) ?></td>
                                                                                <td><span class="badge bg-light-primary text-primary"><?= htmlspecialchars($seq['sequence_nom']) ?></span></td>
                                                                                <td><?= htmlspecialchars(ucfirst($ev['type'])) ?></td>
                                                                                <td class="text-center fw-bold <?= $ev['note'] >= 10 ? 'text-success' : 'text-danger' ?>">
                                                                                    <?= number_format($ev['note'], 2) ?> / 20
                                                                                </td>
                                                                                <td class="text-center"><?= number_format($ev['coefficient'], 2) ?></td>
                                                                                <td>
                                                                                    <small class="text-muted">
                                                                                        <?= !empty($ev['appreciation']) ? htmlspecialchars($ev['appreciation']) . ' — ' : '' ?>
                                                                                        <?= date('d/m/Y', strtotime($ev['date_saisie'])) ?>
                                                                                    </small>
                                                                                </td>
                                                                            </tr>
                                                                        <?php endforeach; ?>
                                                                    <?php endforeach; ?>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2 : Synthèse Annuelle des Moyennes par Matière -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="ph-duotone ph-calculator me-2"></i><?= _('Moyennes Annuelles Calculées par Matière') ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($subjectAverages)): ?>
                            <p class="text-muted mb-0"><?= _('Aucune moyenne calculable.') ?></p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover table-striped align-middle">
                                    <thead class="table-dark">
                                        <tr>
                                            <th><?= _('Année Académique') ?></th>
                                            <th><?= _('Classe') ?></th>
                                            <th><?= _('Matière') ?></th>
                                            <th class="text-center"><?= _('Total Points') ?></th>
                                            <th class="text-center"><?= _('Total Coefs') ?></th>
                                            <th class="text-center"><?= _('Moyenne Annuelle') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($subjectAverages as $anneeData): ?>
                                            <?php foreach ($anneeData['matieres'] as $m): ?>
                                                <tr>
                                                    <td><strong><?= htmlspecialchars($anneeData['annee_libelle']) ?></strong></td>
                                                    <td><?= htmlspecialchars($anneeData['classe_libelle']) ?></td>
                                                    <td class="fw-bold"><?= htmlspecialchars($m['nom_matiere']) ?></td>
                                                    <td class="text-center"><?= number_format($m['total_points'], 2) ?></td>
                                                    <td class="text-center"><?= number_format($m['total_coefficients'], 2) ?></td>
                                                    <td class="text-center">
                                                        <span class="badge fs-6 <?= $m['annual_average'] >= 10 ? 'bg-light-success text-success' : 'bg-light-danger text-danger' ?>">
                                                            <?= number_format($m['annual_average'], 2) ?> / 20
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 3 : Évolution Interannuelle et Variations (Δ) -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="ph-duotone ph-trend-up me-2"></i><?= _('Variations Interannuelles et Tendance') ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($variations)): ?>
                            <p class="text-muted mb-0"><?= _('Le suivi d\'évolution interannuelle nécessite des notes sur au moins 2 années consécutives.') ?></p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th><?= _('Matière') ?></th>
                                            <th class="text-center"><?= _('Première Année') ?></th>
                                            <th class="text-center"><?= _('Dernière Année') ?></th>
                                            <th class="text-center"><?= _('Variation Globale (Δ)') ?></th>
                                            <th><?= _('Détail Consécutif') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($variations as $var): ?>
                                            <tr>
                                                <td class="fw-bold"><?= htmlspecialchars($var['nom_matiere']) ?></td>
                                                <td class="text-center"><?= number_format($var['first_average'], 2) ?> / 20</td>
                                                <td class="text-center"><?= number_format($var['latest_average'], 2) ?> / 20</td>
                                                <td class="text-center">
                                                    <?php if ($var['total_delta'] > 0): ?>
                                                        <span class="badge bg-success"><i class="ph-duotone ph-arrow-up me-1"></i>+<?= number_format($var['total_delta'], 2) ?> pts</span>
                                                    <?php elseif ($var['total_delta'] < 0): ?>
                                                        <span class="badge bg-danger"><i class="ph-duotone ph-arrow-down me-1"></i><?= number_format($var['total_delta'], 2) ?> pts</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary"><i class="ph-duotone ph-minus me-1"></i>0.00 pt</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <ul class="list-unstyled mb-0 small">
                                                        <?php foreach ($var['consecutive_variations'] as $cVar): ?>
                                                            <li>
                                                                <strong><?= htmlspecialchars($cVar['from_year']) ?></strong> (<?= number_format($cVar['from_average'], 2) ?>)
                                                                $\rightarrow$
                                                                <strong><?= htmlspecialchars($cVar['to_year']) ?></strong> (<?= number_format($cVar['to_average'], 2) ?>) :
                                                                <span class="<?= $cVar['delta'] >= 0 ? 'text-success' : 'text-danger' ?> fw-bold">
                                                                    <?= sprintf("%+0.2f", $cVar['delta']) ?> pts
                                                                </span>
                                                            </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 4 : Relevés & Snapshots des Bulletins Officiels -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="ph-duotone ph-seal-check me-2"></i><?= _('Snapshots des Bulletins Officiels Scellés') ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($bulletinSnapshots)): ?>
                            <p class="text-muted mb-0"><?= _('Aucun bulletin officiel scellé pour cet élève.') ?></p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped align-middle">
                                    <thead>
                                        <tr>
                                            <th><?= _('Année') ?></th>
                                            <th><?= _('Séquence') ?></th>
                                            <th class="text-center"><?= _('Moyenne Générale Scellée') ?></th>
                                            <th class="text-center"><?= _('Rang Officiel') ?></th>
                                            <th><?= _('Appréciation Conseil') ?></th>
                                            <th><?= _('Statut Bulletin') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($bulletinSnapshots as $b): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($b['annee_libelle']) ?></td>
                                                <td><?= htmlspecialchars($b['sequence_nom']) ?></td>
                                                <td class="text-center fw-bold fs-6 text-primary"><?= number_format($b['moyenne_generale'], 2) ?> / 20</td>
                                                <td class="text-center"><?= htmlspecialchars($b['rang'] ?? 'N/A') ?></td>
                                                <td><?= htmlspecialchars($b['appreciation'] ?? '') ?></td>
                                                <td>
                                                    <?php if ($b['statut'] === 'publie'): ?>
                                                        <span class="badge bg-success"><?= _('Publié') ?></span>
                                                    <?php elseif ($b['statut'] === 'valide'): ?>
                                                        <span class="badge bg-primary"><?= _('Validé') ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning text-dark"><?= _('Provisoire') ?></span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12 text-end">
                <a href="/eleves" class="btn btn-secondary mt-2"><?= _('Retour à la liste des élèves') ?></a>
            </div>
        </div>

    </div>
</div>
<!-- [ Main Content ] end -->

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
