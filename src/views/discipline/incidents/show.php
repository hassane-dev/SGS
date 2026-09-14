<?php
$title = _("Détail de l'Incident Disciplinaire");
require_once __DIR__ . '/../../layouts/header_able.php';
require_once __DIR__ . '/../../layouts/sidebar_able.php';

$canManage = Auth::can('manage_incident', 'discipline');
?>

<div class="pc-container">
    <div class="pc-content">

        <!-- Breadcrumb -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h5 class="m-b-10"><?= _("Détails de l'Incident") ?> #<?= $incident['id'] ?></h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/home"><?= _("Accueil") ?></a></li>
                            <li class="breadcrumb-item"><a href="/discipline/incidents"><?= _("Discipline") ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _("Incident #") ?><?= $incident['id'] ?></li>
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

            <!-- Information générale -->
            <div class="col-lg-7">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="ph-duotone ph-file-text me-2 text-primary"></i><?= _("Faits & Circonstances") ?></h5>
                        <?php
                        $badgeStatut = match($incident['statut']) {
                            'signale' => 'bg-light-warning text-warning',
                            'en_instruction' => 'bg-light-info text-info',
                            'traite' => 'bg-light-success text-success',
                            'classe_sans_suite' => 'bg-light-secondary text-muted',
                            default => 'bg-light-secondary text-secondary'
                        };
                        $labelStatut = match($incident['statut']) {
                            'signale' => _("Signalé"),
                            'en_instruction' => _("En instruction"),
                            'traite' => _("Traité"),
                            'classe_sans_suite' => _("Classé sans suite"),
                            default => htmlspecialchars($incident['statut'])
                        };
                        ?>
                        <div>
                            <?php if ($incident['statut'] === 'signale' && (int)$incident['signale_par_user_id'] === (int)Auth::getUserId()): ?>
                                <a href="/discipline/incidents/edit?id=<?= $incident['id'] ?>" class="btn btn-sm btn-outline-primary me-2">
                                    <i class="ph-duotone ph-pencil me-1"></i><?= _("Modifier mon signalement") ?>
                                </a>
                            <?php endif; ?>
                            <span class="badge fs-6 <?= $badgeStatut ?>"><?= $labelStatut ?></span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="text-muted small"><?= _("Type d'incident") ?></label>
                                <div class="fw-bold fs-6"><?= htmlspecialchars($incident['type_libelle']) ?></div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small"><?= _("Niveau de gravité") ?></label>
                                <div>
                                    <?php
                                    $badgeGravite = match($incident['niveau_gravite']) {
                                        'mineur' => 'bg-light-success text-success',
                                        'moyen' => 'bg-light-warning text-warning',
                                        'grave' => 'bg-light-danger text-danger',
                                        'tres_grave' => 'bg-danger text-white',
                                        default => 'bg-light-secondary text-secondary'
                                    };
                                    ?>
                                    <span class="badge <?= $badgeGravite ?>"><?= htmlspecialchars($incident['niveau_gravite']) ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="text-muted small"><?= _("Date & Heure") ?></label>
                                <div>
                                    <i class="ph-duotone ph-calendar me-1"></i><?= date('d/m/Y', strtotime($incident['date_incident'])) ?>
                                    <?php if (!empty($incident['heure_incident'])): ?>
                                        <span class="ms-2"><i class="ph-duotone ph-clock me-1"></i><?= date('H:i', strtotime($incident['heure_incident'])) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small"><?= _("Lieu") ?></label>
                                <div><i class="ph-duotone ph-map-pin me-1"></i><?= htmlspecialchars($incident['lieu'] ?? _('Non spécifié')) ?></div>
                            </div>
                        </div>

                        <div class="mb-3 border-top pt-3">
                            <label class="text-muted small"><?= _("Signalé par") ?></label>
                            <div class="fw-bold"><i class="ph-duotone ph-user me-1"></i><?= htmlspecialchars($incident['signale_prenom'] . ' ' . $incident['signale_nom']) ?></div>
                            <div class="text-muted small"><?= _("Signalé le") ?> <?= date('d/m/Y H:i', strtotime($incident['created_at'])) ?></div>
                        </div>

                        <div class="border-top pt-3">
                            <label class="text-muted small"><?= _("Description des faits") ?></label>
                            <div class="p-3 bg-light rounded mt-1 text-wrap" style="white-space: pre-wrap;"><?= htmlspecialchars($incident['description_faits']) ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Élèves & Workflow -->
            <div class="col-lg-5">

                <!-- Élèves impliqués -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="ph-duotone ph-users me-2 text-primary"></i><?= _("Élèves Impliqués") ?> (<?= count($incident['eleves']) ?>)</h5>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <?php foreach ($incident['eleves'] as $e): ?>
                                <li class="list-group-item p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <div>
                                            <a href="/eleves/details?id=<?= $e['eleve_id'] ?>" class="fw-bold text-dark text-decoration-none">
                                                <?= htmlspecialchars($e['nom'] . ' ' . $e['prenom']) ?>
                                            </a>
                                            <span class="text-muted small ms-1">(<?= htmlspecialchars($e['nom_niveau'] . ($e['nom_serie'] ? ' ' . $e['nom_serie'] : '') . ($e['nom_numero'] ? ' ' . $e['nom_numero'] : '')) ?>)</span>
                                        </div>
                                        <?php
                                        $badgeRole = match($e['role_implication']) {
                                            'auteur_principal' => 'bg-danger text-white',
                                            'co_auteur' => 'bg-light-danger text-danger',
                                            'complice' => 'bg-light-warning text-warning',
                                            'victime' => 'bg-light-info text-info',
                                            'temoin' => 'bg-light-secondary text-secondary',
                                            default => 'bg-light-secondary text-secondary'
                                        };
                                        $labelRole = match($e['role_implication']) {
                                            'auteur_principal' => _("Auteur Principal"),
                                            'co_auteur' => _("Co-auteur"),
                                            'complice' => _("Complice"),
                                            'victime' => _("Victime"),
                                            'temoin' => _("Témoin"),
                                            default => htmlspecialchars($e['role_implication'])
                                        };
                                        ?>
                                        <span class="badge <?= $badgeRole ?>"><?= $labelRole ?></span>
                                    </div>
                                    <?php if (!empty($e['observation_individuelle'])): ?>
                                        <div class="text-muted small fst-italic mt-1 bg-light p-2 rounded">
                                            "<?= htmlspecialchars($e['observation_individuelle']) ?>"
                                        </div>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <!-- Workflow & Instruction -->
                <?php if ($canManage && !in_array($incident['statut'], ['traite', 'classe_sans_suite'], true)): ?>
                    <div class="card">
                        <div class="card-header bg-light-primary">
                            <h5 class="mb-0 text-primary"><i class="ph-duotone ph-gear-six me-2"></i><?= _("Instruction & Traitement") ?></h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small mb-3">
                                <?= _("En tant que responsable de la vie scolaire ou direction, vous pouvez instruire cet incident, le classer sans suite ou marquer son traitement comme terminé.") ?>
                            </p>

                            <div class="d-grid gap-2">
                                <?php if ($incident['statut'] === 'signale'): ?>
                                    <form action="/discipline/incidents/update-status" method="POST">
                                        <input type="hidden" name="id" value="<?= $incident['id'] ?>">
                                        <input type="hidden" name="statut" value="en_instruction">
                                        <button type="submit" class="btn btn-info w-100 d-inline-flex align-items-center justify-content-center gap-2">
                                            <i class="ph-duotone ph-magnifying-glass fs-5"></i><?= _("Passer en instruction") ?>
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <?php if (in_array($incident['statut'], ['signale', 'en_instruction'], true)): ?>
                                    <form action="/discipline/incidents/update-status" method="POST" onsubmit="return confirm('<?= _('Confirmer le traitement terminé de cet incident ?') ?>')">
                                        <input type="hidden" name="id" value="<?= $incident['id'] ?>">
                                        <input type="hidden" name="statut" value="traite">
                                        <button type="submit" class="btn btn-success w-100 d-inline-flex align-items-center justify-content-center gap-2">
                                            <i class="ph-duotone ph-check-circle fs-5"></i><?= _("Marquer comme Traité") ?>
                                        </button>
                                    </form>

                                    <form action="/discipline/incidents/update-status" method="POST" onsubmit="return confirm('<?= _('Êtes-vous sûr de vouloir classer cet incident sans suite ?') ?>')">
                                        <input type="hidden" name="id" value="<?= $incident['id'] ?>">
                                        <input type="hidden" name="statut" value="classe_sans_suite">
                                        <button type="submit" class="btn btn-outline-secondary w-100 d-inline-flex align-items-center justify-content-center gap-2">
                                            <i class="ph-duotone ph-x-circle fs-5"></i><?= _("Classer sans suite") ?>
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