<?php include __DIR__ . '/../layouts/header_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/"><?= _('Tableau de bord') ?></a></li>
                            <li class="breadcrumb-item"><a href="javascript: void(0)"><?= _('Évaluations') ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _('Gestion des Déblocages') ?></li>
                        </ul>
                    </div>
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= _('Gestion des Déblocages des Notes') ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- [ Main Content ] start -->
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><?= _('Historique des Déblocages') ?></h5>
                        <a href="/evaluations/deblocage/create" class="btn btn-primary d-inline-flex">
                            <i class="ph-duotone ph-plus-circle me-2"></i> <?= _('Nouveau Déblocage') ?>
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="pc-dt-simple">
                                <thead>
                                    <tr>
                                        <th><?= _('Niveau') ?></th>
                                        <th><?= _('Cible') ?></th>
                                        <th><?= _('Période') ?></th>
                                        <th><?= _('Statut') ?></th>
                                        <th><?= _('Motif') ?></th>
                                        <th><?= _('Accordé par') ?></th>
                                        <th><?= _('Actions') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($deblocages as $d):
                                        $now = time();
                                        $start = strtotime($d['date_debut']);
                                        $end = strtotime($d['date_fin']);
                                        $is_active = ($now >= $start && $now <= $end);
                                        $is_expired = ($now > $end);
                                    ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-light-info text-info text-uppercase"><?= str_replace('_', ' + ', $d['type']) ?></span>
                                            <div class="mt-1">
                                                <span class="badge bg-light-dark text-dark"><?= ucfirst($d['type_evaluation'] == 'tous' ? 'Tous types' : $d['type_evaluation']) ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($d['type'] == 'global'): ?>
                                                <span class="text-muted"><?= _('Tout l\'établissement') ?></span>
                                            <?php elseif ($d['type'] == 'classe'): ?>
                                                <strong><?= htmlspecialchars($d['nom_classe']) ?></strong>
                                            <?php elseif ($d['type'] == 'matiere'): ?>
                                                <strong><?= htmlspecialchars($d['nom_matiere']) ?></strong>
                                            <?php elseif ($d['type'] == 'classe_matiere'): ?>
                                                <strong><?= htmlspecialchars($d['nom_classe']) ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars($d['nom_matiere']) ?></small>
                                            <?php elseif ($d['type'] == 'enseignant'): ?>
                                                <strong><?= htmlspecialchars($d['enseignant_prenom'] . ' ' . $d['enseignant_nom']) ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars($d['nom_classe'] . ' / ' . $d['nom_matiere']) ?></small>
                                            <?php endif; ?>

                                            <?php if ($d['sequence_nom']): ?>
                                                <div class="mt-1"><span class="badge bg-light-secondary text-secondary"><?= htmlspecialchars($d['sequence_nom']) ?></span></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small>
                                                <?= _('Du') ?>: <?= date('d/m/Y H:i', $start) ?><br>
                                                <?= _('Au') ?>: <?= date('d/m/Y H:i', $end) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php if ($is_active): ?>
                                                <span class="badge bg-success"><?= _('Actif') ?></span>
                                            <?php elseif ($is_expired): ?>
                                                <span class="badge bg-danger"><?= _('Expiré') ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-warning"><?= _('À venir') ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="text-truncate d-inline-block" style="max-width: 150px;" title="<?= htmlspecialchars($d['motif'] ?? '') ?>">
                                                <?= htmlspecialchars($d['motif'] ?? '-') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($d['creator_prenom'] . ' ' . $d['creator_nom']) ?><br>
                                            <small class="text-muted"><?= date('d/m/Y', strtotime($d['cree_le'])) ?></small>
                                        </td>
                                        <td>
                                            <a href="/evaluations/deblocage/delete?id=<?= $d['id'] ?>" class="btn btn-sm btn-light-danger" onclick="return confirm('<?= _('Êtes-vous sûr de vouloir supprimer ce déblocage ?') ?>')">
                                                <i class="ph-duotone ph-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
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

<?php include __DIR__ . '/../layouts/footer_able.php'; ?>
