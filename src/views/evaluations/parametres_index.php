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
                            <li class="breadcrumb-item" aria-current="page"><?= _('Gestion des Périodes de Saisie') ?></li>
                        </ul>
                    </div>
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= _('Gestion des Périodes de Saisie des Notes') ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- [ Main Content ] start -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><?= _('Paramètres de Blocage de Saisie') ?></h5>
                        <a href="/evaluations/settings/create" class="btn btn-primary d-inline-flex">
                            <i class="ph-duotone ph-plus-circle me-2"></i> <?= _('Définir une période') ?>
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="pc-dt-simple">
                                <thead>
                                    <tr>
                                        <th><?= _('Type') ?></th>
                                        <th><?= _('Cible') ?></th>
                                        <th><?= _('Séquence') ?></th>
                                        <th><?= _('Nature') ?></th>
                                        <th><?= _('Ouverture') ?></th>
                                        <th><?= _('Fermeture') ?></th>
                                        <th><?= _('Commentaire') ?></th>
                                        <th><?= _('Actions') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($params as $p):
                                        $badge_class = 'bg-light-secondary';
                                        $target = _('Global');

                                        switch($p['type']) {
                                            case 'classe':
                                                $badge_class = 'bg-light-primary';
                                                $target = _('Classe: ') . htmlspecialchars($p['nom_classe']);
                                                break;
                                            case 'matiere':
                                                $badge_class = 'bg-light-info';
                                                $target = _('Matière: ') . htmlspecialchars($p['nom_matiere']);
                                                break;
                                            case 'classe_matiere':
                                                $badge_class = 'bg-light-warning';
                                                $target = htmlspecialchars($p['nom_classe'] . ' / ' . $p['nom_matiere']);
                                                break;
                                            case 'enseignant':
                                                $badge_class = 'bg-light-success';
                                                $target = htmlspecialchars($p['nom_classe'] . ' / ' . $p['nom_matiere']) . '<br><small>' . htmlspecialchars($p['enseignant_prenom'] . ' ' . $p['enseignant_nom']) . '</small>';
                                                break;
                                        }
                                    ?>
                                        <tr>
                                            <td><span class="badge <?= $badge_class ?>"><?= ucfirst($p['type']) ?></span></td>
                                            <td><?= $target ?></td>
                                            <td><?= $p['sequence_nom'] ?? _('Toutes') ?></td>
                                            <td><?= ucfirst($p['type_evaluation']) ?></td>
                                            <td><?= date('d/m/Y H:i', strtotime($p['date_ouverture_saisie'])) ?></td>
                                            <td><?= date('d/m/Y H:i', strtotime($p['date_fermeture_saisie'])) ?></td>
                                            <td><?= htmlspecialchars($p['commentaire'] ?? '') ?></td>
                                            <td>
                                                <a href="/evaluations/settings/delete?id=<?= $p['id'] ?>" class="btn btn-sm btn-light-danger" onclick="return confirm('<?= _('Êtes-vous sûr de vouloir supprimer ce paramètre ?') ?>')">
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
