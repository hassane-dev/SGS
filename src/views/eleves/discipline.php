<?php
/**
 * Vue : Fiche Élève - Discipline & Vie Scolaire
 */
require_once __DIR__ . '/../layouts/header_able.php';
require_once __DIR__ . '/../layouts/sidebar_able.php';

$roleLabels = [
    'auteur_principal' => 'Auteur Principal',
    'co_auteur' => 'Co-auteur',
    'complice' => 'Complice',
    'victime' => 'Victime',
    'temoin' => 'Témoin',
];

$roleBadges = [
    'auteur_principal' => 'bg-danger',
    'co_auteur' => 'bg-warning text-dark',
    'complice' => 'bg-info text-white',
    'victime' => 'bg-primary',
    'temoin' => 'bg-secondary',
];

$incStatutBadges = [
    'signale' => 'bg-warning text-dark',
    'en_instruction' => 'bg-info text-white',
    'traite' => 'bg-success',
    'classe_sans_suite' => 'bg-secondary',
];

$sanctStatutBadges = [
    'prononcee' => 'bg-warning text-dark',
    'en_cours' => 'bg-primary',
    'executee' => 'bg-success',
    'levee' => 'bg-info text-white',
    'annulee' => 'bg-secondary',
];

$conseilStatutBadges = [
    'planifie' => 'bg-secondary',
    'convoque' => 'bg-info text-white',
    'en_session' => 'bg-primary',
    'delibere' => 'bg-warning text-dark',
    'cloture' => 'bg-success',
    'annule' => 'bg-danger',
];

$decisionStatutBadges = [
    'en_attente' => 'bg-warning text-dark',
    'relaxe' => 'bg-success',
    'averti' => 'bg-info text-white',
    'reoriente' => 'bg-secondary',
    'sanctionne' => 'bg-danger',
];

$decisionStatutLabels = [
    'en_attente' => 'En attente',
    'relaxe' => 'Relaxé(e)',
    'averti' => 'Averti(e)',
    'reoriente' => 'Réorienté(e)',
    'sanctionne' => 'Sanctionné(e)',
];
?>

<!-- [ Main Content ] start -->
<div class="pc-container">
    <div class="pc-content">

        <!-- Fil d'Ariane -->
        <div class="page-header mb-3">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/dashboard"><?= _('Accueil') ?></a></li>
                            <li class="breadcrumb-item"><a href="/eleves"><?= _('Élèves') ?></a></li>
                            <li class="breadcrumb-item active"><?= _('Discipline & Vie Scolaire') ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation par Onglets Fiche Élève -->
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
                                <a class="nav-link" href="/eleves/parcours-academique?id=<?= $eleve['id_eleve'] ?>"><i class="ph-duotone ph-chart-line-up me-2"></i>Parcours Académique</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link active" href="/eleves/discipline?id=<?= $eleve['id_eleve'] ?>"><i class="ph-duotone ph-gavel me-2"></i>Vie Scolaire & Discipline</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- En-tête Élève -->
        <div class="card border-0 shadow-sm bg-primary text-white mb-4">
            <div class="card-body p-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avtar avtar-xl bg-white text-primary rounded-circle fs-2 fw-bold">
                            <?= strtoupper(substr($eleve['prenom'], 0, 1) . substr($eleve['nom'], 0, 1)) ?>
                        </div>
                        <div>
                            <h3 class="text-white mb-1"><?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></h3>
                            <p class="mb-0 text-white-50">
                                <span class="me-3"><i class="ph-duotone ph-identification-card me-1"></i>Matricule : <strong><?= htmlspecialchars($eleve['matricule'] ?? 'N/A') ?></strong></span>
                                <span><i class="ph-duotone ph-gender-intersex me-1"></i>Sexe : <strong><?= htmlspecialchars($eleve['sexe'] ?? 'N/A') ?></strong></span>
                            </p>
                        </div>
                    </div>
                    <?php if (Auth::can('report_incident', 'discipline')): ?>
                        <a href="/discipline/incidents/create?eleve_id=<?= $eleve['id_eleve'] ?>" class="btn btn-light text-primary fw-bold">
                            <i class="ph-duotone ph-plus me-1"></i>Signaler un incident
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Synthèse Résumé -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-muted mb-1">Total Incidents</h6>
                                <h3 class="mb-0 text-primary"><?= $summary['total_incidents'] ?></h3>
                            </div>
                            <div class="avtar bg-light-primary text-primary">
                                <i class="ph-duotone ph-warning fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-muted mb-1">Conseils Discipline</h6>
                                <h3 class="mb-0 text-purple"><?= $summary['total_conseils'] ?? 0 ?></h3>
                            </div>
                            <div class="avtar bg-light-purple text-purple">
                                <i class="ph-duotone ph-scales fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-muted mb-1">Total Sanctions</h6>
                                <h3 class="mb-0 text-warning"><?= $summary['total_sanctions'] ?></h3>
                            </div>
                            <div class="avtar bg-light-warning text-warning">
                                <i class="ph-duotone ph-gavel fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-muted mb-1">Sanctions En Cours</h6>
                                <h3 class="mb-0 text-danger"><?= $summary['sanctions_en_cours'] ?></h3>
                            </div>
                            <div class="avtar bg-light-danger text-danger">
                                <i class="ph-duotone ph-clock fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Incidents Concernant l'Élève -->
        <?php if ($canViewIncidents): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="ph-duotone ph-warning me-2 text-warning"></i>Incidents Disciplinaires</h5>
                    <span class="badge bg-light-secondary text-secondary"><?= count($incidents) ?> enregistré(s)</span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($incidents)): ?>
                        <div class="p-4 text-center text-muted">
                            <i class="ph-duotone ph-check-circle fs-1 text-success mb-2 d-block"></i>
                            Aucun incident disciplinaire enregistré pour cet élève.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Code / Date</th>
                                        <th>Type & Gravité</th>
                                        <th>Rôle de l'élève</th>
                                        <th>Classe Snapshot</th>
                                        <th>Signale par</th>
                                        <th>Statut</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($incidents as $inc): ?>
                                        <tr>
                                            <td>
                                                <strong class="d-block text-dark"><?= htmlspecialchars($inc['code']) ?></strong>
                                                <small class="text-muted"><i class="ph-duotone ph-calendar me-1"></i><?= date('d/m/Y', strtotime($inc['date_incident'])) ?></small>
                                            </td>
                                            <td>
                                                <span class="fw-semibold"><?= htmlspecialchars($inc['type_incident_libelle'] ?? 'N/A') ?></span>
                                                <small class="d-block text-muted">Gravité: <?= htmlspecialchars($inc['niveau_gravite'] ?? 'N/A') ?></small>
                                            </td>
                                            <td>
                                                <span class="badge <?= $roleBadges[$inc['role_implication']] ?? 'bg-secondary' ?>">
                                                    <?= htmlspecialchars($roleLabels[$inc['role_implication']] ?? $inc['role_implication']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-light-primary text-primary fw-bold">
                                                    <?= htmlspecialchars($inc['nom_classe_snapshot'] ?? 'Inconnue') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-muted"><i class="ph-duotone ph-user me-1"></i><?= htmlspecialchars($inc['signale_par_nom'] ?? 'Système') ?></small>
                                            </td>
                                            <td>
                                                <span class="badge <?= $incStatutBadges[$inc['statut']] ?? 'bg-secondary' ?>">
                                                    <?= ucfirst(str_replace('_', ' ', $inc['statut'])) ?>
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <a href="/discipline/incidents/show?id=<?= $inc['incident_id'] ?>" class="btn btn-sm btn-outline-primary" title="Voir la fiche incident">
                                                    <i class="ph-duotone ph-eye me-1"></i>Voir
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
        <?php endif; ?>

        <!-- Conseils de Discipline -->
        <?php if (!empty($canViewCouncils)): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="ph-duotone ph-scales me-2 text-primary"></i>Conseils de Discipline</h5>
                    <span class="badge bg-light-secondary text-secondary"><?= count($councils ?? []) ?> convocation(s) / séance(s)</span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($councils)): ?>
                        <div class="p-4 text-center text-muted">
                            <i class="ph-duotone ph-check-circle fs-1 text-success mb-2 d-block"></i>
                            Aucun conseil de discipline enregistré pour cet élève.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Code & Titre</th>
                                        <th>Date</th>
                                        <th>Statut Conseil</th>
                                        <th>Motif Convocation</th>
                                        <th>Présence Élève / Tuteur</th>
                                        <th>Décision & Motivation</th>
                                        <th>Sanction Liée</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($councils as $csl): ?>
                                        <tr>
                                            <td>
                                                <strong class="d-block text-dark"><?= htmlspecialchars($csl['conseil_code']) ?></strong>
                                                <small class="text-muted"><?= htmlspecialchars($csl['conseil_titre']) ?></small>
                                            </td>
                                            <td>
                                                <small class="text-muted"><i class="ph-duotone ph-calendar me-1"></i><?= date('d/m/Y', strtotime($csl['date_conseil'])) ?></small>
                                            </td>
                                            <td>
                                                <span class="badge <?= $conseilStatutBadges[$csl['conseil_statut']] ?? 'bg-secondary' ?>">
                                                    <?= ucfirst(str_replace('_', ' ', $csl['conseil_statut'])) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-dark d-block text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($csl['motif_convocation']) ?>">
                                                    <?= htmlspecialchars($csl['motif_convocation']) ?>
                                                </small>
                                            </td>
                                            <td>
                                                <small class="d-block">Élève : <?= $csl['presence_eleve'] ? '<span class="text-success fw-bold"><i class="ph-duotone ph-check-circle me-1"></i>Présent</span>' : '<span class="text-danger fw-bold"><i class="ph-duotone ph-x-circle me-1"></i>Absent</span>' ?></small>
                                                <small class="d-block text-muted">Représentant : <?= $csl['presence_representant_legal'] ? '<span class="text-success fw-bold"><i class="ph-duotone ph-check-circle me-1"></i>Présent</span>' : '<span class="text-muted"><i class="ph-duotone ph-x-circle me-1"></i>Absent</span>' ?></small>
                                                <?php if (!empty($csl['nom_representant_legal'])): ?>
                                                    <small class="d-block text-muted">(<?= htmlspecialchars($csl['nom_representant_legal']) ?>)</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge <?= $decisionStatutBadges[$csl['decision_statut']] ?? 'bg-secondary' ?>">
                                                    <?= htmlspecialchars($decisionStatutLabels[$csl['decision_statut']] ?? $csl['decision_statut']) ?>
                                                </span>
                                                <?php if (!empty($csl['motivation_decision'])): ?>
                                                    <small class="d-block text-muted text-truncate mt-1" style="max-width: 200px;" title="<?= htmlspecialchars($csl['motivation_decision']) ?>">
                                                        <?= htmlspecialchars($csl['motivation_decision']) ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($csl['sanction_id'])): ?>
                                                    <span class="fw-bold text-dark d-block"><?= htmlspecialchars($csl['type_sanction_libelle'] ?? 'Sanction #' . $csl['sanction_id']) ?></span>
                                                    <span class="badge <?= $sanctStatutBadges[$csl['sanction_statut']] ?? 'bg-secondary' ?>">
                                                        <?= ucfirst(str_replace('_', ' ', $csl['sanction_statut'] ?? 'prononcee')) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <small class="text-muted">Aucune</small>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <a href="/discipline/councils/show?id=<?= $csl['conseil_id'] ?>" class="btn btn-sm btn-outline-primary" title="Voir la fiche du conseil">
                                                    <i class="ph-duotone ph-eye me-1"></i>Voir
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
        <?php endif; ?>

        <!-- Sanctions Prononcées -->
        <?php if ($canViewSanctions): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="ph-duotone ph-gavel me-2 text-danger"></i>Sanctions Prononcées</h5>
                    <span class="badge bg-light-secondary text-secondary"><?= count($sanctions) ?> enregistrée(s)</span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($sanctions)): ?>
                        <div class="p-4 text-center text-muted">
                            <i class="ph-duotone ph-shield-check fs-1 text-success mb-2 d-block"></i>
                            Aucune sanction n'a été prononcée contre cet élève.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date Décision</th>
                                        <th>Type Sanction</th>
                                        <th>Classe Snapshot</th>
                                        <th>Prononcée Par</th>
                                        <th>Exécution</th>
                                        <th>Statut</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sanctions as $sanc): ?>
                                        <tr>
                                            <td>
                                                <strong class="d-block text-dark"><?= date('d/m/Y', strtotime($sanc['date_decision'])) ?></strong>
                                                <?php if (!empty($sanc['incident_code'])): ?>
                                                    <small class="text-muted">Incident : <?= htmlspecialchars($sanc['incident_code']) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="fw-bold text-dark"><?= htmlspecialchars($sanc['type_sanction_libelle'] ?? 'N/A') ?></span>
                                                <?php if (!empty($sanc['motif_decision'])): ?>
                                                    <small class="d-block text-muted text-truncate" style="max-width:200px;"><?= htmlspecialchars($sanc['motif_decision']) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-light-primary text-primary fw-bold">
                                                    <?= htmlspecialchars($sanc['nom_classe_snapshot'] ?? 'Inconnue') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-muted"><i class="ph-duotone ph-user me-1"></i><?= htmlspecialchars($sanc['prononcee_par_nom'] ?? 'N/A') ?></small>
                                            </td>
                                            <td>
                                                <?php if (!empty($sanc['date_debut_execution'])): ?>
                                                    <small class="d-block text-muted"><i class="ph-duotone ph-calendar me-1"></i><?= date('d/m/Y', strtotime($sanc['date_debut_execution'])) ?> - <?= date('d/m/Y', strtotime($sanc['date_fin_execution'])) ?></small>
                                                <?php else: ?>
                                                    <small class="text-muted">Non planifiée</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge <?= $sanctStatutBadges[$sanc['statut']] ?? 'bg-secondary' ?>">
                                                    <?= ucfirst(str_replace('_', ' ', $sanc['statut'])) ?>
                                                </span>
                                                <?php if (in_array($sanc['statut'], ['levee', 'annulee']) && !empty($sanc['motif_levee_annulation'])): ?>
                                                    <small class="d-block text-info mt-1" title="<?= htmlspecialchars($sanc['motif_levee_annulation']) ?>">
                                                        <i class="ph-duotone ph-info me-1"></i>Motif renseigné
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <a href="/discipline/sanctions/show?id=<?= $sanc['sanction_id'] ?>" class="btn btn-sm btn-outline-primary" title="Voir la fiche sanction">
                                                    <i class="ph-duotone ph-eye me-1"></i>Voir
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
        <?php endif; ?>

        <!-- Audit Trail & Journal d'Historique -->
        <?php if ($canViewHistory && !empty($history)): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="ph-duotone ph-clock-counter-clockwise me-2 text-info"></i>Journal d'Audit Disciplinaire</h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($history as $h): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center p-3">
                                <div>
                                    <span class="badge bg-light-primary text-primary me-2"><?= htmlspecialchars($h['action']) ?></span>
                                    <span class="fw-semibold text-dark"><?= htmlspecialchars($h['description']) ?></span>
                                    <small class="d-block text-muted mt-1">
                                        Par : <strong><?= htmlspecialchars($h['auteur_nom'] ?? 'Système') ?></strong>
                                        <?php if (!empty($h['statut_avant']) || !empty($h['statut_apres'])): ?>
                                            • Statut : <code><?= htmlspecialchars($h['statut_avant'] ?? 'N/A') ?></code> &rarr; <code><?= htmlspecialchars($h['statut_apres'] ?? 'N/A') ?></code>
                                        <?php endif; ?>
                                    </small>
                                </div>
                                <small class="text-muted"><i class="ph-duotone ph-clock me-1"></i><?= date('d/m/Y H:i', strtotime($h['created_at'])) ?></small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <!-- Notifications Parentales & Convocations -->
        <?php if ($canViewNotifications && !empty($notifications)): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="ph-duotone ph-envelope-simple me-2 text-primary"></i>Convocations & Notifications Parentales</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date d'Envoi</th>
                                    <th>Canal & Type</th>
                                    <th>Destinataire</th>
                                    <th>Incident Réf.</th>
                                    <th>Émetteur</th>
                                    <th>Statut Transmission</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($notifications as $notif): ?>
                                    <tr>
                                        <td><?= date('d/m/Y H:i', strtotime($notif['created_at'])) ?></td>
                                        <td>
                                            <span class="badge bg-light-dark text-dark fw-bold"><?= strtoupper(htmlspecialchars($notif['canal'])) ?></span>
                                            <small class="d-block text-muted"><?= htmlspecialchars($notif['type_notification']) ?></small>
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($notif['destinataire_nom']) ?></strong>
                                            <small class="d-block text-muted"><?= htmlspecialchars($notif['destinataire_contact']) ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($notif['incident_code'] ?? 'N/A') ?></td>
                                        <td><small class="text-muted"><?= htmlspecialchars($notif['emetteur_nom'] ?? 'Système') ?></small></td>
                                        <td>
                                            <span class="badge bg-success"><?= ucfirst(htmlspecialchars($notif['statut_envoi'])) ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>
<!-- [ Main Content ] end -->

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
