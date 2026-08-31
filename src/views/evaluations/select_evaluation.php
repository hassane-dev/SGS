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
                            <li class="breadcrumb-item"><a href="/evaluations/select_class"><?= _('Saisie des Notes') ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _('Choix de l\'Évaluation') ?></li>
                        </ul>
                    </div>
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= _('Saisie des Notes — Choix de la Période') ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>
                            <i class="ph-duotone ph-calendar-blank me-2 text-primary"></i>
                            <?= htmlspecialchars(Classe::getFormattedName($classe)) ?> — <?= htmlspecialchars($matiere['nom_matiere']) ?> (<?= ucfirst($type) ?>)
                        </h5>
                        <a href="/evaluations/select_class" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center">
                            <i class="ph-duotone ph-arrow-left me-1"></i> <?= _('Retour') ?>
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (isset($is_devoir_open) && isset($is_composition_open) && $is_devoir_open && $is_composition_open): ?>
                            <div class="mb-3 d-flex gap-2">
                                <a href="/evaluations/select_evaluation?classe_id=<?= $classe['id_classe'] ?>&matiere_id=<?= $matiere['id_matiere'] ?>&type=devoir" class="btn btn-sm <?= $type === 'devoir' ? 'btn-primary' : 'btn-outline-primary' ?>">
                                    <i class="ph-duotone ph-notebook me-1"></i><?= _('Devoir') ?>
                                </a>
                                <a href="/evaluations/select_evaluation?classe_id=<?= $classe['id_classe'] ?>&matiere_id=<?= $matiere['id_matiere'] ?>&type=composition" class="btn btn-sm <?= $type === 'composition' ? 'btn-primary' : 'btn-outline-primary' ?>">
                                    <i class="ph-duotone ph-exam me-1"></i><?= _('Composition') ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        <?php if (empty($evaluations)): ?>
                            <div class="alert alert-warning mb-3" role="alert">
                                <i class="ph-duotone ph-warning me-2 fs-5 align-middle"></i>
                                <?= _('Aucune période de saisie n\'est actuellement ouverte pour cette matière. Veuillez contacter l\'administration pour configurer les paramètres d\'évaluation.') ?>
                            </div>
                            <a href="/evaluations/select_class" class="btn btn-secondary"><?= _('Retour à la sélection') ?></a>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($evaluations as $eval): ?>
                                    <a href="/evaluations/form?classe_id=<?= $classe['id_classe'] ?>&matiere_id=<?= $matiere['id_matiere'] ?>&sequence_id=<?= $eval['sequence_id'] ?>&type=<?= $type ?>" class="list-group-item list-group-item-action p-3">
                                        <div class="d-flex w-100 justify-content-between align-items-center mb-1">
                                            <h5 class="mb-0 text-primary">
                                                <i class="ph-duotone ph-clock me-2"></i><?= htmlspecialchars($eval['sequence_nom']) ?>
                                            </h5>
                                            <span class="badge bg-light-primary text-primary">
                                                <?= (new DateTime($eval['date_ouverture_saisie']))->format('d/m/Y H:i') ?> → <?= (new DateTime($eval['date_fermeture_saisie']))->format('d/m/Y H:i') ?>
                                            </span>
                                        </div>
                                        <p class="mb-0 text-muted small"><?= htmlspecialchars($eval['commentaire'] ?? _("Saisir les notes pour cette période.")) ?></p>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer_able.php'; ?>
