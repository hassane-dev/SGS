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
                        <ul class="breadcrumb mb-2">
                            <li class="breadcrumb-item"><a href="/"><?= _('Tableau de Bord') ?></a></li>
                            <li class="breadcrumb-item"><a href="/cahier-texte"><?= _('Cahier de Texte') ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _('Détails de la Séance') ?></li>
                        </ul>
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= _('Consultation de la Séance') ?></h2>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <a href="/cahier-texte" class="btn btn-outline-secondary">
                            <i class="ph-duotone ph-arrow-left me-1"></i>
                            <?= _('Retour à la liste') ?>
                        </a>
                        <?php if (Auth::can('manage', 'cahier_texte') || Auth::getUserId() == $entry['personnel_id']): ?>
                            <a href="/cahier-texte/edit?id=<?= $entry['cahier_id'] ?>" class="btn btn-primary ms-2">
                                <i class="ph-duotone ph-pencil me-1"></i>
                                <?= _('Modifier') ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- [ Main Cards ] start -->
        <div class="row">
            <!-- Information générale / Métadonnées -->
            <div class="col-lg-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light py-3">
                        <h5 class="mb-0"><i class="ph-duotone ph-info me-2 text-primary"></i><?= _('Informations Générales') ?></h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted"><i class="ph-duotone ph-calendar me-2"></i><?= _('Date du cours') ?></span>
                                <span class="fw-bold"><?= htmlspecialchars(date('d/m/Y', strtotime($entry['date_cours']))) ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted"><i class="ph-duotone ph-clock me-2"></i><?= _('Horaires') ?></span>
                                <span class="fw-bold">
                                    <?= htmlspecialchars($entry['heure_debut'] ? date('H:i', strtotime($entry['heure_debut'])) : '--:--') ?>
                                    -
                                    <?= htmlspecialchars($entry['heure_fin'] ? date('H:i', strtotime($entry['heure_fin'])) : '--:--') ?>
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted"><i class="ph-duotone ph-timer me-2"></i><?= _('Durée estimée') ?></span>
                                <span class="badge bg-light-info text-info fs-6"><?= htmlspecialchars($entry['duree_formatee']) ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted"><i class="ph-duotone ph-user me-2"></i><?= _('Enseignant') ?></span>
                                <span class="fw-bold text-end">
                                    <?= htmlspecialchars(($entry['prenom_personnel'] ?? '') . ' ' . ($entry['nom_personnel'] ?? '')) ?>
                                    <?php if (!empty($entry['matricule_personnel'])): ?>
                                        <br><small class="text-muted font-monospace"><?= htmlspecialchars($entry['matricule_personnel']) ?></small>
                                    <?php endif; ?>
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted"><i class="ph-duotone ph-student me-2"></i><?= _('Classe') ?></span>
                                <span class="badge bg-light-primary text-primary fs-6"><?= htmlspecialchars(Classe::getFormattedName($entry)) ?></span>
                            </li>
                            <?php if (!empty($entry['nom_cycle'])): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted"><i class="ph-duotone ph-buildings me-2"></i><?= _('Cycle') ?></span>
                                <span class="fw-bold"><?= htmlspecialchars($entry['nom_cycle']) ?></span>
                            </li>
                            <?php endif; ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted"><i class="ph-duotone ph-book-open me-2"></i><?= _('Matière') ?></span>
                                <span class="fw-bold text-primary"><?= htmlspecialchars($entry['nom_matiere'] ?? 'N/A') ?></span>
                            </li>
                            <?php if (!empty($entry['annee_academique_libelle'])): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted"><i class="ph-duotone ph-graduation-cap me-2"></i><?= _('Année Académique') ?></span>
                                <span><?= htmlspecialchars($entry['annee_academique_libelle']) ?></span>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Contenu de la séance, travail à faire et observations -->
            <div class="col-lg-8">
                <!-- Contenu du cours -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light py-3">
                        <h5 class="mb-0 text-primary"><i class="ph-duotone ph-notebook me-2"></i><?= _('Contenu du Cours') ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty(trim($entry['contenu_cours'] ?? ''))): ?>
                            <div class="p-3 bg-light-subtle rounded border">
                                <?= nl2br(htmlspecialchars($entry['contenu_cours'])) ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted italic mb-0"><i class="ph-duotone ph-info me-1"></i><?= _('Aucun contenu rédigé pour ce cours.') ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Travail donné (Devoirs) -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light py-3">
                        <h5 class="mb-0 text-warning"><i class="ph-duotone ph-pencil-line me-2"></i><?= _('Travail à Faire / Devoirs') ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty(trim($entry['travail_donne'] ?? ''))): ?>
                            <div class="p-3 bg-light-warning rounded border border-warning-subtle">
                                <?= nl2br(htmlspecialchars($entry['travail_donne'])) ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted italic mb-0"><i class="ph-duotone ph-check-circle me-1"></i><?= _('Aucun travail particulier assigné.') ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Observations -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light py-3">
                        <h5 class="mb-0 text-secondary"><i class="ph-duotone ph-chat-teardrop-text me-2"></i><?= _('Observations / Remarques') ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty(trim($entry['observation'] ?? ''))): ?>
                            <div class="p-3 bg-light rounded border">
                                <?= nl2br(htmlspecialchars($entry['observation'])) ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted italic mb-0"><?= _('Aucune observation saisie.') ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ Main Cards ] end -->
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
