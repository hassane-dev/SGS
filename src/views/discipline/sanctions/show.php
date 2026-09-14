<?php
$title = _("Détail de la Sanction Disciplinaire");
require_once __DIR__ . '/../../layouts/header_able.php';
require_once __DIR__ . '/../../layouts/sidebar_able.php';

$canManage = Auth::can('manage_sanctions', 'discipline');
?>

<div class="pc-container">
    <div class="pc-content">

        <!-- Breadcrumb -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h5 class="m-b-10"><?= _("Sanction Disciplinaire #") ?><?= $sanction['id'] ?></h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/home"><?= _("Accueil") ?></a></li>
                            <li class="breadcrumb-item"><a href="/discipline/sanctions"><?= _("Discipline") ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _("Sanction #") ?><?= $sanction['id'] ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Flash messages -->
        <?php if (!empty($_SESSION['flash_success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="ph-duotone ph-check-circle me-2 fs-5"></i>
                <?= htmlspecialchars($_SESSION['flash_success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['flash_error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="ph-duotone ph-warning-circle me-2 fs-5"></i>
                <?= htmlspecialchars($_SESSION['flash_error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <div class="row">

            <!-- Detail Sanction Card -->
            <div class="col-lg-7">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="ph-duotone ph-gavel me-2 text-primary"></i><?= _("Décision & Exécution") ?></h5>
                        <?php
                        $badgeStatut = match($sanction['statut']) {
                            'prononcee' => 'bg-light-primary text-primary',
                            'en_cours' => 'bg-light-warning text-warning',
                            'executee' => 'bg-light-success text-success',
                            'levee' => 'bg-light-info text-info',
                            'annulee' => 'bg-light-secondary text-muted',
                            default => 'bg-light-secondary text-secondary'
                        };
                        $labelStatut = match($sanction['statut']) {
                            'prononcee' => _("Prononcée"),
                            'en_cours' => _("En cours d'exécution"),
                            'executee' => _("Exécutée"),
                            'levee' => _("Levée"),
                            'annulee' => _("Annulée"),
                            default => htmlspecialchars($sanction['statut'])
                        };
                        ?>
                        <span class="badge fs-6 <?= $badgeStatut ?>"><?= $labelStatut ?></span>
                    </div>
                    <div class="card-body">

                        <!-- Student Identity -->
                        <div class="p-3 bg-light rounded mb-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="text-muted small"><?= _("Élève Sanctionné") ?></div>
                                    <a href="/eleves/details?id=<?= $sanction['eleve_id'] ?>" class="fw-bold fs-5 text-dark text-decoration-none">
                                        <?= htmlspecialchars($sanction['eleve_nom'] . ' ' . $sanction['eleve_prenom']) ?>
                                    </a>
                                    <div class="text-muted small">
                                        Matricule : <strong><?= htmlspecialchars($sanction['eleve_matricule']) ?></strong> |
                                        Classe : <strong><?= htmlspecialchars($sanction['classe_niveau'] . ($sanction['classe_serie'] ? ' ' . $sanction['classe_serie'] : '') . ($sanction['classe_numero'] ? ' ' . $sanction['classe_numero'] : '')) ?></strong>
                                    </div>
                                </div>
                                <i class="ph-duotone ph-user-circle fs-1 text-secondary"></i>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="text-muted small"><?= _("Type de Sanction") ?></label>
                                <div class="fw-bold fs-6"><?= htmlspecialchars($sanction['type_libelle']) ?></div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small"><?= _("Autorité Minimale Requise") ?></label>
                                <div>
                                    <span class="badge bg-light-info text-info"><?= htmlspecialchars($sanction['autorite_min_requise']) ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="text-muted small"><?= _("Motif Officiel") ?></label>
                            <div class="fw-bold fs-6 text-dark"><?= htmlspecialchars($sanction['motif']) ?></div>
                        </div>

                        <?php if (!empty($sanction['duree_jours']) || !empty($sanction['duree_heures'])): ?>
                            <div class="row mb-3 p-2 bg-light-warning rounded mx-0">
                                <?php if (!empty($sanction['duree_jours'])): ?>
                                    <div class="col-md-6">
                                        <label class="text-muted small"><?= _("Durée") ?></label>
                                        <div class="fw-bold text-warning"><?= (int)$sanction['duree_jours'] ?> <?= _("jour(s) d'exclusion") ?></div>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($sanction['duree_heures'])): ?>
                                    <div class="col-md-6">
                                        <label class="text-muted small"><?= _("Volume horaire") ?></label>
                                        <div class="fw-bold text-warning"><?= (int)$sanction['duree_heures'] ?> <?= _("heure(s) de retenue") ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="text-muted small"><?= _("Date de Décision") ?></label>
                                <div><i class="ph-duotone ph-calendar me-1"></i><?= date('d/m/Y', strtotime($sanction['date_decision'])) ?></div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small"><?= _("Prononcée par") ?></label>
                                <div class="fw-bold"><?= htmlspecialchars($sanction['prononcee_prenom'] . ' ' . $sanction['prononcee_nom']) ?></div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="text-muted small"><?= _("Début Exécution") ?></label>
                                <div><?= !empty($sanction['date_debut_execution']) ? date('d/m/Y', strtotime($sanction['date_debut_execution'])) : '-' ?></div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small"><?= _("Fin Exécution") ?></label>
                                <div><?= !empty($sanction['date_fin_execution']) ? date('d/m/Y', strtotime($sanction['date_fin_execution'])) : '-' ?></div>
                            </div>
                        </div>

                        <?php if (!empty($sanction['details'])): ?>
                            <div class="border-top pt-3">
                                <label class="text-muted small"><?= _("Détails / Instructions") ?></label>
                                <div class="p-3 bg-light rounded mt-1"><?= htmlspecialchars($sanction['details']) ?></div>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>

            <!-- Side Card: Incident Link & Status Actions -->
            <div class="col-lg-5">

                <!-- Associated Incident if present -->
                <?php if (!empty($sanction['incident_id'])): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-light-info">
                            <h5 class="mb-0 text-info"><i class="ph-duotone ph-link me-2"></i><?= _("Incident d'Origine #") ?><?= $sanction['incident_id'] ?></h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <label class="text-muted small"><?= _("Type & Date") ?></label>
                                <div class="fw-bold">
                                    <?= htmlspecialchars($sanction['incident_type_libelle'] ?? '-') ?> | <?= date('d/m/Y', strtotime($sanction['date_incident'])) ?>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="text-muted small"><?= _("Description") ?></label>
                                <div class="small text-muted p-2 bg-light rounded">
                                    <?= htmlspecialchars($sanction['incident_description'] ?? '') ?>
                                </div>
                            </div>
                            <a href="/discipline/incidents/show?id=<?= $sanction['incident_id'] ?>" class="btn btn-sm btn-outline-info w-100">
                                <i class="ph-duotone ph-arrow-square-out me-1"></i><?= _("Consulter l'incident complet") ?>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Status Workflow Control Card -->
                <?php if ($canManage && !in_array($sanction['statut'], ['executee', 'levee', 'annulee'], true)): ?>
                    <div class="card">
                        <div class="card-header bg-light-primary">
                            <h5 class="mb-0 text-primary"><i class="ph-duotone ph-gear-six me-2"></i><?= _("Gestion du Cycle de Vie") ?></h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small mb-3">
                                <?= _("Vous pouvez faire évoluer le statut de la sanction selon son exécution réelle.") ?>
                            </p>

                            <div class="d-grid gap-2">
                                <?php if ($sanction['statut'] === 'prononcee'): ?>
                                    <form action="/discipline/sanctions/update-status" method="POST">
                                        <input type="hidden" name="id" value="<?= $sanction['id'] ?>">
                                        <input type="hidden" name="statut" value="en_cours">
                                        <button type="submit" class="btn btn-warning w-100 d-inline-flex align-items-center justify-content-center gap-2">
                                            <i class="ph-duotone ph-play fs-5"></i><?= _("Passer en cours d'exécution") ?>
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <?php if (in_array($sanction['statut'], ['prononcee', 'en_cours'], true)): ?>
                                    <form action="/discipline/sanctions/update-status" method="POST" onsubmit="return confirm('<?= _('Confirmer que cette sanction a été pleinement exécutée ?') ?>')">
                                        <input type="hidden" name="id" value="<?= $sanction['id'] ?>">
                                        <input type="hidden" name="statut" value="executee">
                                        <button type="submit" class="btn btn-success w-100 d-inline-flex align-items-center justify-content-center gap-2">
                                            <i class="ph-duotone ph-check-circle fs-5"></i><?= _("Marquer comme Exécutée") ?>
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <?php if ($sanction['statut'] === 'en_cours'): ?>
                                    <form action="/discipline/sanctions/update-status" method="POST" onsubmit="return confirm('<?= _('Confirmer la levée anticipée de cette sanction ?') ?>')">
                                        <input type="hidden" name="id" value="<?= $sanction['id'] ?>">
                                        <input type="hidden" name="statut" value="levee">
                                        <button type="submit" class="btn btn-info w-100 d-inline-flex align-items-center justify-content-center gap-2">
                                            <i class="ph-duotone ph-arrow-up-right fs-5"></i><?= _("Lever la sanction (Anticipé)") ?>
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <?php if (in_array($sanction['statut'], ['prononcee', 'en_cours'], true)): ?>
                                    <form action="/discipline/sanctions/update-status" method="POST" onsubmit="return confirm('<?= _('Êtes-vous sûr de vouloir annuler définitivement cette sanction ?') ?>')">
                                        <input type="hidden" name="id" value="<?= $sanction['id'] ?>">
                                        <input type="hidden" name="statut" value="annulee">
                                        <button type="submit" class="btn btn-outline-secondary w-100 d-inline-flex align-items-center justify-content-center gap-2">
                                            <i class="ph-duotone ph-x-circle fs-5"></i><?= _("Annuler la sanction") ?>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            </div>

        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/footer_able.php'; ?>