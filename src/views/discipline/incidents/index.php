<?php
$title = _("Registre des Incidents Disciplinaires");
require_once __DIR__ . '/../../layouts/header_able.php';
require_once __DIR__ . '/../../layouts/sidebar_able.php';

$canReport = Auth::can('report_incident', 'discipline');
?>

<div class="pc-container">
    <div class="pc-content">

        <!-- Breadcrumb -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h5 class="m-b-10"><?= _("Vie Scolaire & Discipline") ?></h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/home"><?= _("Accueil") ?></a></li>
                            <li class="breadcrumb-item"><a href="/discipline/incidents"><?= _("Discipline") ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _("Incidents") ?></li>
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

        <!-- Action & Filter Card -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><?= _("Registre des Incidents Signalés") ?></h5>
                <?php if ($canReport): ?>
                    <a href="/discipline/incidents/create" class="btn btn-primary d-inline-flex align-items-center gap-1">
                        <i class="ph-duotone ph-plus fs-5"></i><?= _("Signaler un incident") ?>
                    </a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <form action="/discipline/incidents" method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="type_incident_id" class="form-label"><?= _("Type d'Incident") ?></label>
                        <select name="type_incident_id" id="type_incident_id" class="form-select">
                            <option value=""><?= _("Tous les types") ?></option>
                            <?php foreach ($typesIncidents as $ti): ?>
                                <option value="<?= $ti['id'] ?>" <?= ($filters['type_incident_id'] == $ti['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($ti['libelle']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="statut" class="form-label"><?= _("Statut") ?></label>
                        <select name="statut" id="statut" class="form-select">
                            <option value=""><?= _("Tous les statuts") ?></option>
                            <option value="signale" <?= ($filters['statut'] === 'signale') ? 'selected' : '' ?>><?= _("Signalé") ?></option>
                            <option value="en_instruction" <?= ($filters['statut'] === 'en_instruction') ? 'selected' : '' ?>><?= _("En Instruction") ?></option>
                            <option value="traite" <?= ($filters['statut'] === 'traite') ? 'selected' : '' ?>><?= _("Traité") ?></option>
                            <option value="classe_sans_suite" <?= ($filters['statut'] === 'classe_sans_suite') ? 'selected' : '' ?>><?= _("Classé sans suite") ?></option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="classe_id" class="form-label"><?= _("Classe") ?></label>
                        <select name="classe_id" id="classe_id" class="form-select">
                            <option value=""><?= _("Toutes les classes") ?></option>
                            <?php foreach ($classes as $cls): ?>
                                <option value="<?= $cls['id_classe'] ?>" <?= ($filters['classe_id'] == $cls['id_classe']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cls['niveau'] . ($cls['serie'] ? ' ' . $cls['serie'] : '') . ($cls['numero'] ? ' ' . $cls['numero'] : '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="date_debut" class="form-label"><?= _("Du") ?></label>
                        <input type="date" name="date_debut" id="date_debut" class="form-control" value="<?= htmlspecialchars($filters['date_debut'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="date_fin" class="form-label"><?= _("Au") ?></label>
                        <input type="date" name="date_fin" id="date_fin" class="form-control" value="<?= htmlspecialchars($filters['date_fin'] ?? '') ?>">
                    </div>
                    <div class="col-12 text-end mt-3">
                        <a href="/discipline/incidents" class="btn btn-outline-secondary me-2"><?= _("Réinitialiser") ?></a>
                        <button type="submit" class="btn btn-secondary d-inline-flex align-items-center gap-1">
                            <i class="ph-duotone ph-funnel"></i><?= _("Filtrer") ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Incidents Table Card -->
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th><?= _("Date / Heure") ?></th>
                                <th><?= _("Type d'Incident") ?></th>
                                <th><?= _("Gravité") ?></th>
                                <th><?= _("Lieu") ?></th>
                                <th><?= _("Élève(s) impliqué(s)") ?></th>
                                <th><?= _("Déclarant") ?></th>
                                <th><?= _("Statut") ?></th>
                                <th class="text-end"><?= _("Action") ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($incidents)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-5">
                                        <i class="ph-duotone ph-shield-warning fs-1 d-block mb-2 text-warning"></i>
                                        <?= _("Aucun incident disciplinaire trouvé selon les critères de recherche.") ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($incidents as $inc): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?= date('d/m/Y', strtotime($inc['date_incident'])) ?></div>
                                            <?php if (!empty($inc['heure_incident'])): ?>
                                                <span class="text-muted small"><i class="ph-duotone ph-clock me-1"></i><?= date('H:i', strtotime($inc['heure_incident'])) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="fw-bold"><?= htmlspecialchars($inc['type_libelle']) ?></td>
                                        <td>
                                            <?php
                                            $badgeGravite = match($inc['niveau_gravite']) {
                                                'mineur' => 'bg-light-success text-success',
                                                'moyen' => 'bg-light-warning text-warning',
                                                'grave' => 'bg-light-danger text-danger',
                                                'tres_grave' => 'bg-danger text-white',
                                                default => 'bg-light-secondary text-secondary'
                                            };
                                            $labelGravite = match($inc['niveau_gravite']) {
                                                'mineur' => _("Mineur"),
                                                'moyen' => _("Moyen"),
                                                'grave' => _("Grave"),
                                                'tres_grave' => _("Très Grave"),
                                                default => htmlspecialchars($inc['niveau_gravite'])
                                            };
                                            ?>
                                            <span class="badge <?= $badgeGravite ?>"><?= $labelGravite ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($inc['lieu'] ?? '-') ?></td>
                                        <td>
                                            <span class="badge bg-light-primary text-primary">
                                                <i class="ph-duotone ph-users me-1"></i><?= (int)$inc['nb_eleves'] ?> <?= _("élève(s)") ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($inc['signale_prenom'] . ' ' . $inc['signale_nom']) ?></td>
                                        <td>
                                            <?php
                                            $badgeStatut = match($inc['statut']) {
                                                'signale' => 'bg-light-warning text-warning',
                                                'en_instruction' => 'bg-light-info text-info',
                                                'traite' => 'bg-light-success text-success',
                                                'classe_sans_suite' => 'bg-light-secondary text-muted',
                                                default => 'bg-light-secondary text-secondary'
                                            };
                                            $labelStatut = match($inc['statut']) {
                                                'signale' => _("Signalé"),
                                                'en_instruction' => _("En instruction"),
                                                'traite' => _("Traité"),
                                                'classe_sans_suite' => _("Classé sans suite"),
                                                default => htmlspecialchars($inc['statut'])
                                            };
                                            ?>
                                            <span class="badge <?= $badgeStatut ?>"><?= $labelStatut ?></span>
                                        </td>
                                        <td class="text-end">
                                            <a href="/discipline/incidents/show?id=<?= $inc['id'] ?>" class="btn btn-sm btn-light-primary d-inline-flex align-items-center gap-1">
                                                <i class="ph-duotone ph-eye"></i><?= _("Consulter") ?>
                                            </a>
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

<?php require_once __DIR__ . '/../../layouts/footer_able.php'; ?>