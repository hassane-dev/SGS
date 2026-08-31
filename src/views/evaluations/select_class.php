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
                            <li class="breadcrumb-item"><?= _('Pédagogie') ?></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _('Saisie des Notes') ?></li>
                        </ul>
                    </div>
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= _('Saisie des Notes — Choix de la Classe et de la Matière') ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <?php if (isset($_GET['success']) && $_GET['success'] === 'grades_saved'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="ph-duotone ph-check-circle me-2 fs-5 align-middle"></i>
                <?= _('Les notes ont été enregistrées avec succès.') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="ph-duotone ph-chalkboard-teacher me-2 text-primary"></i><?= _('Mes affectations pédagogiques') ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($subjects_taught)): ?>
                            <div class="alert alert-warning mb-0" role="alert">
                                <i class="ph-duotone ph-warning me-2 fs-5 align-middle"></i>
                                <?= _('Aucune matière ne vous est actuellement assignée pour l\'année académique active. Veuillez contacter l\'administration.') ?>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th><?= _('Classe') ?></th>
                                            <th><?= _('Matière') ?></th>
                                            <th class="text-end"><?= _('Action') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($subjects_taught as $subject):
                                            $nomClasse = $subject['nom_classe'] ?? trim($subject['niveau'] . ' ' . ($subject['serie'] ?? '') . ' ' . ($subject['numero'] ?? ''));
                                        ?>
                                            <tr>
                                                <td>
                                                    <span class="fw-bold text-dark">
                                                        <i class="ph-duotone ph-chalkboard me-2 text-primary"></i>
                                                        <?= htmlspecialchars($nomClasse) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light-info text-info fs-6">
                                                        <i class="ph-duotone ph-book-open me-1"></i>
                                                        <?= htmlspecialchars($subject['nom_matiere']) ?>
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <a href="/notes/saisir/<?= $subject['classe_id'] ?>/<?= $subject['matiere_id'] ?>" class="btn btn-primary btn-sm d-inline-flex align-items-center">
                                                        <i class="ph-duotone ph-note-pencil me-1"></i>
                                                        <?= _('Saisir les notes') ?>
                                                    </a>
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
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer_able.php'; ?>
