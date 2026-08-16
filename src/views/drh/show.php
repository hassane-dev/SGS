<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <!-- En-tête Dossier 360° -->
        <div class="page-header d-print-none mb-3">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-7">
                        <div class="page-header-title d-flex align-items-center gap-2">
                            <a href="/drh" class="btn btn-sm btn-light-secondary me-2" title="<?= _('Retour à l\'annuaire') ?>">
                                <i class="ph-duotone ph-arrow-left fs-5"></i>
                            </a>
                            <div>
                                <h2 class="mb-0 d-flex align-items-center gap-2">
                                    <span><?= htmlspecialchars($p['prenom'] . ' ' . $p['nom']) ?></span>
                                    <span class="badge bg-light-primary text-primary font-monospace fs-6"><?= htmlspecialchars($p['identifiant_public'] ?? 'N/A') ?></span>
                                </h2>
                                <p class="text-muted mb-0 small">
                                    <?= htmlspecialchars($p['fonction'] ?? _('Personnel')) ?> &bull; <?= htmlspecialchars($p['nom_lycee'] ?? '') ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5 text-end d-flex justify-content-end gap-2">
                        <?php if (Auth::can('edit', 'drh')): ?>
                        <a href="/drh/edit?id=<?= $p['id_user'] ?>" class="btn btn-warning btn-sm d-inline-flex align-items-center gap-1">
                            <i class="ph-duotone ph-pencil fs-5"></i>
                            <span><?= _('Modifier Fiche') ?></span>
                        </a>
                        <?php endif; ?>

                        <?php if (Auth::can('manage_statut', 'drh')): ?>
                        <button type="button" class="btn btn-outline-danger btn-sm d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#statutModal">
                            <i class="ph-duotone ph-gear fs-5"></i>
                            <span><?= _('Changer Statut RH') ?></span>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                <i class="ph-duotone ph-check-circle me-1 fs-5 align-middle"></i>
                <?= htmlspecialchars($_SESSION['success_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                <i class="ph-duotone ph-warning-circle me-1 fs-5 align-middle"></i>
                <?= htmlspecialchars($_SESSION['error_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <!-- 360° Navigation Onglets structurés -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent p-2 border-0">
                <ul class="nav nav-pills card-header-pills gap-1" id="drh360Tabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active d-inline-flex align-items-center gap-1" id="tab-identite-btn" data-bs-toggle="tab" data-bs-target="#tab-identite" type="button" role="tab">
                            <i class="ph-duotone ph-user fs-5"></i>
                            <span><?= _('Identité') ?></span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link d-inline-flex align-items-center gap-1" id="tab-affectations-btn" data-bs-toggle="tab" data-bs-target="#tab-affectations" type="button" role="tab">
                            <i class="ph-duotone ph-buildings fs-5"></i>
                            <span><?= _('Affectations') ?></span>
                            <span class="badge bg-light-primary text-primary font-monospace ms-1"><?= count($assignments) ?></span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link d-inline-flex align-items-center gap-1" id="tab-contrats-btn" data-bs-toggle="tab" data-bs-target="#tab-contrats" type="button" role="tab">
                            <i class="ph-duotone ph-file-text fs-5"></i>
                            <span><?= _('Contrats') ?></span>
                            <span class="badge bg-light-primary text-primary font-monospace ms-1"><?= count($contracts) ?></span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link d-inline-flex align-items-center gap-1" id="tab-remuneration-btn" data-bs-toggle="tab" data-bs-target="#tab-remuneration" type="button" role="tab">
                            <i class="ph-duotone ph-currency-circle-dollar fs-5"></i>
                            <span><?= _('Rémunération / Paie') ?></span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link d-inline-flex align-items-center gap-1" id="tab-documents-btn" data-bs-toggle="tab" data-bs-target="#tab-documents" type="button" role="tab">
                            <i class="ph-duotone ph-folder fs-5"></i>
                            <span><?= _('Documents') ?></span>
                            <span class="badge bg-light-primary text-primary font-monospace ms-1"><?= count($documents) ?></span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link d-inline-flex align-items-center gap-1" id="tab-historique-btn" data-bs-toggle="tab" data-bs-target="#tab-historique" type="button" role="tab">
                            <i class="ph-duotone ph-clock-counter-clockwise fs-5"></i>
                            <span><?= _('Historique') ?></span>
                            <span class="badge bg-light-primary text-primary font-monospace ms-1"><?= count($history) ?></span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link d-inline-flex align-items-center gap-1" id="tab-securite-btn" data-bs-toggle="tab" data-bs-target="#tab-securite" type="button" role="tab">
                            <i class="ph-duotone ph-shield-check fs-5"></i>
                            <span><?= _('Sécurité & Habilitation') ?></span>
                        </button>
                    </li>
                </ul>
            </div>
        </div>

        <div class="tab-content" id="drh360Content">
            <!-- ONGLET 1 : IDENTITÉ -->
            <div class="tab-pane fade show active" id="tab-identite" role="tabpanel">
                <div class="row g-3">
                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm text-center">
                            <div class="card-body">
                                <div class="mb-3 position-relative d-inline-block">
                                    <img src="<?= !empty($p['photo']) ? htmlspecialchars($p['photo']) : '/assets/img/default-avatar.png' ?>" class="rounded-circle img-thumbnail shadow-sm" style="width:120px; height:120px; object-fit:cover;" alt="Avatar">
                                </div>
                                <h4 class="mb-1 fw-bold"><?= htmlspecialchars($p['prenom'] . ' ' . $p['nom']) ?></h4>
                                <p class="text-muted mb-2"><?= htmlspecialchars($p['fonction'] ?? '—') ?></p>

                                <?php
                                $st = $p['statut_rh'] ?? 'en_activite';
                                $b = match($st) {
                                    'en_activite' => 'bg-light-success text-success',
                                    'en_conge' => 'bg-light-warning text-warning',
                                    'suspendu' => 'bg-light-danger text-danger',
                                    default => 'bg-light-secondary text-secondary'
                                };
                                $stLabel = match($st) {
                                    'en_activite' => _('En activité'),
                                    'en_conge' => _('En congé'),
                                    'suspendu' => _('Suspendu'),
                                    'demissionne' => _('Démissionné'),
                                    'licencie' => _('Licencié'),
                                    'retraite' => _('Retraité'),
                                    default => strtoupper($st)
                                };
                                ?>
                                <span class="badge <?= $b ?> px-3 py-2 fs-6 mb-3 fw-bold"><?= htmlspecialchars($stLabel) ?></span>

                                <hr class="my-3">

                                <div class="text-start small">
                                    <div class="d-flex justify-content-between py-1 border-bottom">
                                        <span class="text-muted"><?= _('Matricule Public') ?></span>
                                        <span class="font-monospace fw-bold text-dark"><?= htmlspecialchars($p['identifiant_public'] ?? 'N/A') ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between py-1 border-bottom">
                                        <span class="text-muted"><?= _('Rôle RBAC') ?></span>
                                        <span class="badge bg-light-primary text-primary"><?= htmlspecialchars($p['nom_role'] ?? 'N/A') ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between py-1 border-bottom">
                                        <span class="text-muted"><?= _('Établissement') ?></span>
                                        <span class="fw-semibold text-dark"><?= htmlspecialchars($p['nom_lycee'] ?? 'N/A') ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between py-1">
                                        <span class="text-muted"><?= _('Compte Actif') ?></span>
                                        <?= $p['actif'] ? '<span class="text-success fw-bold"><i class="ph-duotone ph-check-circle"></i> '._('Oui').'</span>' : '<span class="text-danger fw-bold"><i class="ph-duotone ph-x-circle"></i> '._('Non').'</span>' ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-8">
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-header bg-transparent py-3">
                                <h5 class="mb-0 d-flex align-items-center gap-2">
                                    <i class="ph-duotone ph-address-book text-primary"></i>
                                    <span><?= _('Informations Personnelles & Contact') ?></span>
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <label class="text-muted small d-block"><?= _('Sexe') ?></label>
                                        <span class="fw-semibold text-dark"><?= htmlspecialchars($p['sexe'] ?? 'Non renseigné') ?></span>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="text-muted small d-block"><?= _('Date & Lieu de Naissance') ?></label>
                                        <span class="fw-semibold text-dark">
                                            <?= !empty($p['date_naissance']) ? date('d/m/Y', strtotime($p['date_naissance'])) : '—' ?>
                                            <?= !empty($p['lieu_naissance']) ? 'à ' . htmlspecialchars($p['lieu_naissance']) : '' ?>
                                        </span>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="text-muted small d-block"><?= _('Adresse Email') ?></label>
                                        <span class="fw-semibold text-dark"><?= htmlspecialchars($p['email']) ?></span>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="text-muted small d-block"><?= _('Numéro Téléphone') ?></label>
                                        <span class="fw-semibold text-dark"><?= htmlspecialchars($p['telephone'] ?? '—') ?></span>
                                    </div>
                                    <div class="col-12">
                                        <label class="text-muted small d-block"><?= _('Adresse Domicile') ?></label>
                                        <span class="fw-semibold text-dark"><?= htmlspecialchars($p['adresse'] ?? '—') ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if ($can_view_sensitive): ?>
                        <div class="card border border-warning border-opacity-50 shadow-sm">
                            <div class="card-header bg-light-warning py-3">
                                <h5 class="mb-0 d-flex align-items-center gap-2 text-warning">
                                    <i class="ph-duotone ph-lock-key"></i>
                                    <span><?= _('Données Confidentielles (Accès Restreint DRH)') ?></span>
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <label class="text-muted small d-block"><?= _('N° Sécurité Sociale (CNSS)') ?></label>
                                        <span class="font-monospace fw-bold text-dark"><?= htmlspecialchars($p['num_cnss'] ?? 'Non renseigné') ?></span>
                                    </div>
                                    <div class="col-sm-3">
                                        <label class="text-muted small d-block"><?= _('Situation Matrimoniale') ?></label>
                                        <span class="fw-semibold text-dark"><?= ucfirst(htmlspecialchars($p['situation_matrimoniale'] ?? 'celibataire')) ?></span>
                                    </div>
                                    <div class="col-sm-3">
                                        <label class="text-muted small d-block"><?= _('Nombre d\'Enfants') ?></label>
                                        <span class="fw-semibold text-dark"><?= (int)($p['nombre_enfants'] ?? 0) ?></span>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="text-muted small d-block"><?= _('Date de Sortie / Départ') ?></label>
                                        <span class="fw-semibold text-dark"><?= !empty($p['date_sortie']) ? date('d/m/Y', strtotime($p['date_sortie'])) : _('En poste') ?></span>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="text-muted small d-block"><?= _('Motif / Remarques') ?></label>
                                        <span class="fw-semibold text-dark"><?= htmlspecialchars($p['motif_sortie'] ?? $p['remarques'] ?? _('Aucun')) ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ONGLET 2 : AFFECTATIONS -->
            <div class="tab-pane fade" id="tab-affectations" role="tabpanel">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 d-flex align-items-center gap-2">
                            <i class="ph-duotone ph-buildings text-primary"></i>
                            <span><?= _('Affectations de Cycles & Isolation de Scope (Phase 10)') ?></span>
                        </h5>
                        <?php if (Auth::can('manage_affectations', 'drh')): ?>
                        <button type="button" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#newAssignmentModal">
                            <i class="ph-duotone ph-plus fs-5"></i>
                            <span><?= _('Nouvelle Affectation') ?></span>
                        </button>
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th><?= _('Cycle Concerné') ?></th>
                                        <th><?= _('Date Début') ?></th>
                                        <th><?= _('Date Fin') ?></th>
                                        <th><?= _('Statut Temporel') ?></th>
                                        <th><?= _('État') ?></th>
                                        <th class="text-end"><?= _('Actions') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $today = date('Y-m-d');
                                    foreach ($assignments as $as):
                                        $isCurrent = ($as['actif'] == 1 && $as['date_debut'] <= $today && (empty($as['date_fin']) || $as['date_fin'] >= $today));
                                        $isFuture = ($as['actif'] == 1 && $as['date_debut'] > $today);
                                        $isExpired = (!empty($as['date_fin']) && $as['date_fin'] < $today);
                                    ?>
                                    <tr>
                                        <td>
                                            <span class="fw-bold text-dark"><?= htmlspecialchars($as['nom_cycle']) ?></span>
                                            <div class="small text-muted"><?= htmlspecialchars($as['niveau_debut'] . ' → ' . $as['niveau_fin']) ?></div>
                                        </td>
                                        <td><?= date('d/m/Y', strtotime($as['date_debut'])) ?></td>
                                        <td><?= !empty($as['date_fin']) ? date('d/m/Y', strtotime($as['date_fin'])) : _('Indéterminée') ?></td>
                                        <td>
                                            <?php if ($isCurrent): ?>
                                                <span class="badge bg-light-success text-success fw-bold"><i class="ph-duotone ph-check-circle me-1"></i><?= _('Actuelle (Accès Valide)') ?></span>
                                            <?php elseif ($isFuture): ?>
                                                <span class="badge bg-light-info text-info fw-bold"><i class="ph-duotone ph-clock me-1"></i><?= _('Future (Planifiée)') ?></span>
                                            <?php elseif ($isExpired): ?>
                                                <span class="badge bg-light-secondary text-secondary"><i class="ph-duotone ph-calendar-x me-1"></i><?= _('Expirée') ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-light-warning text-warning"><i class="ph-duotone ph-minus-circle me-1"></i><?= _('Désactivée') ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= $as['actif'] ? '<span class="text-success fw-bold">'._('Actif').'</span>' : '<span class="text-muted">'._('Inactif').'</span>' ?>
                                        </td>
                                        <td class="text-end">
                                            <?php if (Auth::can('manage_affectations', 'drh')): ?>
                                            <form method="POST" action="/drh/assignments/delete" class="d-inline" onsubmit="return confirm('<?= _('Retirer cette affectation ? Les accès de l\'utilisateur seront réactualisés.') ?>');">
                                                <input type="hidden" name="id" value="<?= $as['id'] ?>">
                                                <input type="hidden" name="personnel_id" value="<?= $p['id_user'] ?>">
                                                <button type="submit" class="btn btn-sm btn-icon btn-light-danger" title="<?= _('Retirer') ?>">
                                                    <i class="ph-duotone ph-trash fs-6"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>

                                    <?php if (empty($assignments)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            <i class="ph-duotone ph-buildings fs-2 d-block mb-2 text-muted"></i>
                                            <?= _('Aucune affectation de cycle enregistrée pour ce personnel.') ?>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ONGLET 3 : CONTRATS -->
            <div class="tab-pane fade" id="tab-contrats" role="tabpanel">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 d-flex align-items-center gap-2">
                            <i class="ph-duotone ph-file-text text-primary"></i>
                            <span><?= _('Historique des Contrats & Avenants') ?></span>
                        </h5>
                        <?php if (Auth::can('manage_contrats', 'drh') || Auth::can('create_contracts', 'drh')): ?>
                        <button type="button" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1" onclick="openNewContractModal()">
                            <i class="ph-duotone ph-plus fs-5"></i>
                            <span><?= _('Nouveau Contrat / Avenant') ?></span>
                        </button>
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th><?= _('Version & Souche') ?></th>
                                        <th><?= _('Type de Contrat') ?></th>
                                        <th><?= _('Employeur') ?></th>
                                        <th><?= _('Période') ?></th>
                                        <?php if ($can_view_sensitive): ?>
                                        <th><?= _('Salaire de Base') ?></th>
                                        <?php endif; ?>
                                        <th><?= _('Statut') ?></th>
                                        <th class="text-end"><?= _('Actions') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($contracts as $c): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-light-primary text-primary font-monospace fw-bold">v<?= (int)($c['version_num'] ?? 1) ?></span>
                                            <span class="text-muted small font-monospace d-block">CTR-<?= sprintf('%06d', $c['contrat_souche_id'] ?? $c['id']) ?></span>
                                        </td>
                                        <td><span class="fw-bold text-dark"><?= htmlspecialchars($c['contrat_libelle']) ?></span></td>
                                        <td><?= htmlspecialchars($c['employeur_nom'] ?? $c['employeur_sigle'] ?? _('Établissement')) ?></td>
                                        <td>
                                            <div class="small fw-semibold text-dark"><?= date('d/m/Y', strtotime($c['date_debut'])) ?></div>
                                            <div class="small text-muted"><?= !empty($c['date_fin']) ? date('d/m/Y', strtotime($c['date_fin'])) : _('Indéterminé') ?></div>
                                        </td>
                                        <?php if ($can_view_sensitive): ?>
                                        <td class="fw-bold font-monospace text-primary">
                                            <?= number_format($c['salaire_base'] ?? 0, 2) ?> <?= htmlspecialchars($c['devise'] ?? 'XAF') ?>
                                        </td>
                                        <?php endif; ?>
                                        <td>
                                            <?php
                                            $stC = $c['statut_contrat'] ?? 'actif';
                                            $bC = match($stC) {
                                                'actif' => 'bg-light-success text-success',
                                                'avenant_remplace' => 'bg-light-info text-info',
                                                'termine' => 'bg-light-secondary text-secondary',
                                                'annule' => 'bg-light-danger text-danger',
                                                default => 'bg-light-warning text-warning'
                                            };
                                            $stCLabel = match($stC) {
                                                'actif' => _('Actif'),
                                                'avenant_remplace' => _('Remplacé par Avenant'),
                                                'termine' => _('Terminé'),
                                                'annule' => _('Annulé'),
                                                default => strtoupper($stC)
                                            };
                                            ?>
                                            <span class="badge <?= $bC ?> fw-bold"><?= htmlspecialchars($stCLabel) ?></span>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-light-primary" title="<?= _('Visualiser') ?>" onclick="viewContractDetails(<?= $c['id'] ?>)">
                                                    <i class="ph-duotone ph-eye fs-6"></i>
                                                </button>

                                                <?php if (($c['statut_contrat'] ?? 'actif') === 'actif'): ?>
                                                    <?php if (Auth::can('manage_contrats', 'drh') || Auth::can('create_amendments', 'drh')): ?>
                                                    <button type="button" class="btn btn-light-warning" title="<?= _('Modifier par Avenant') ?>" onclick="prepareAmendment(<?= htmlspecialchars(json_encode($c)) ?>)">
                                                        <i class="ph-duotone ph-pencil fs-6"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-light-danger" title="<?= _('Annuler le contrat') ?>" onclick="prepareCancelContract(<?= $c['id'] ?>, <?= $c['version_num'] ?? 1 ?>)">
                                                        <i class="ph-duotone ph-x-circle fs-6"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <?php if (Auth::can('manage_contrats', 'drh') || Auth::can('create_amendments', 'drh')): ?>
                                                    <button type="button" class="btn btn-light-info" title="<?= _('Créer un avenant à partir de cette version') ?>" onclick="prepareAmendment(<?= htmlspecialchars(json_encode($c)) ?>)">
                                                        <i class="ph-duotone ph-plus-circle fs-6"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>

                                    <?php if (empty($contracts)): ?>
                                    <tr>
                                        <td colspan="<?= $can_view_sensitive ? 7 : 6 ?>" class="text-center py-4 text-muted">
                                            <i class="ph-duotone ph-file-x fs-2 d-block mb-2 text-muted"></i>
                                            <?= _('Aucun historique contractuel enregistré.') ?>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ONGLET 4 : RÉMUNÉRATION / PAIE -->
            <div class="tab-pane fade" id="tab-remuneration" role="tabpanel">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent py-3">
                        <h5 class="mb-0 d-flex align-items-center gap-2">
                            <i class="ph-duotone ph-currency-circle-dollar text-success"></i>
                            <span><?= _('Synthèse Rémunération & Structure de Paie') ?></span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($can_view_sensitive): ?>
                            <?php if ($active_contract): ?>
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <div class="p-3 bg-light rounded border">
                                        <div class="text-muted small mb-1"><?= _('Salaire de Base Contractuel') ?></div>
                                        <div class="h4 mb-0 font-monospace text-primary fw-bold">
                                            <?= number_format($active_contract['salaire_base'] ?? 0, 2) ?> <?= htmlspecialchars($active_contract['devise'] ?? 'XAF') ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-3 bg-light rounded border">
                                        <div class="text-muted small mb-1"><?= _('Type de Contrat Actif') ?></div>
                                        <div class="h5 mb-0 fw-bold text-dark">
                                            <?= htmlspecialchars($active_contract['contrat_libelle'] ?? '—') ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-3 bg-light rounded border">
                                        <div class="text-muted small mb-1"><?= _('Modalité de Paiement') ?></div>
                                        <div class="h5 mb-0 fw-bold text-dark">
                                            <?= ucfirst(htmlspecialchars($active_contract['type_paiement'] ?? 'Fixe Mensuel')) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-info d-flex align-items-center gap-2 mb-0">
                                <i class="ph-duotone ph-info fs-4"></i>
                                <div><?= _('Aucun contrat actif configuré pour la détermination automatique des éléments de paie.') ?></div>
                            </div>
                            <?php endif; ?>
                        <?php else: ?>
                        <div class="alert alert-warning d-flex align-items-center gap-2 mb-0">
                            <i class="ph-duotone ph-lock-key fs-4"></i>
                            <div><?= _('Accès restreint. Vous ne disposez pas des permissions DRH pour consulter les données de rémunération.') ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ONGLET 5 : DOCUMENTS -->
            <div class="tab-pane fade" id="tab-documents" role="tabpanel">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 d-flex align-items-center gap-2">
                            <i class="ph-duotone ph-folder text-primary"></i>
                            <span><?= _('Coffre-fort Documentaire DRH') ?></span>
                        </h5>
                        <?php if (Auth::can('manage_documents', 'drh')): ?>
                        <button type="button" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#uploadDocModal">
                            <i class="ph-duotone ph-upload-simple fs-5"></i>
                            <span><?= _('Téléverser Document') ?></span>
                        </button>
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th><?= _('Type Document') ?></th>
                                        <th><?= _('Nom Fichier') ?></th>
                                        <th><?= _('Version') ?></th>
                                        <th><?= _('Confidentialité') ?></th>
                                        <th><?= _('Date Téléversement') ?></th>
                                        <th class="text-end"><?= _('Actions') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($documents as $doc): ?>
                                    <tr>
                                        <td><span class="fw-bold text-dark"><?= htmlspecialchars($doc['type_document']) ?></span></td>
                                        <td><?= htmlspecialchars($doc['nom_fichier']) ?></td>
                                        <td><span class="badge bg-light-info text-info font-monospace">v<?= (int)$doc['version'] ?></span></td>
                                        <td>
                                            <?= $doc['confidentiel'] ? '<span class="badge bg-light-danger text-danger"><i class="ph-duotone ph-lock me-1"></i>'._('Confidentiel').'</span>' : '<span class="badge bg-light-secondary text-secondary">'._('Public').'</span>' ?>
                                        </td>
                                        <td><?= date('d/m/Y H:i', strtotime($doc['date_upload'])) ?></td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <a href="/drh/documents/download?id=<?= $doc['id'] ?>" class="btn btn-light-primary" target="_blank" title="<?= _('Télécharger') ?>">
                                                    <i class="ph-duotone ph-download-simple fs-6"></i>
                                                </a>
                                                <?php if (Auth::can('manage_documents', 'drh')): ?>
                                                <form method="POST" action="/drh/documents/delete" class="d-inline" onsubmit="return confirm('<?= _('Supprimer définitivement ce document ?') ?>');">
                                                    <input type="hidden" name="id" value="<?= $doc['id'] ?>">
                                                    <input type="hidden" name="personnel_id" value="<?= $p['id_user'] ?>">
                                                    <button type="submit" class="btn btn-light-danger" title="<?= _('Supprimer') ?>">
                                                        <i class="ph-duotone ph-trash fs-6"></i>
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>

                                    <?php if (empty($documents)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            <i class="ph-duotone ph-folder-open fs-2 d-block mb-2 text-muted"></i>
                                            <?= _('Aucun document archivé dans le coffre-fort pour ce personnel.') ?>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ONGLET 6 : HISTORIQUE -->
            <div class="tab-pane fade" id="tab-historique" role="tabpanel">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent py-3">
                        <h5 class="mb-0 d-flex align-items-center gap-2">
                            <i class="ph-duotone ph-clock-counter-clockwise text-primary"></i>
                            <span><?= _('Registre Immuable des Mouvements RH & Audits') ?></span>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th><?= _('Date & Heure') ?></th>
                                        <th><?= _('Mouvement') ?></th>
                                        <th><?= _('Motif / Détails') ?></th>
                                        <th><?= _('Auteur') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($history as $h): ?>
                                    <tr>
                                        <td class="text-nowrap font-monospace small"><?= date('d/m/Y H:i', strtotime($h['date_mouvement'])) ?></td>
                                        <td><span class="badge bg-light-primary text-primary fw-bold"><?= htmlspecialchars($h['type_mouvement']) ?></span></td>
                                        <td><?= htmlspecialchars($h['motif'] ?? '—') ?></td>
                                        <td><span class="fw-semibold text-dark"><?= htmlspecialchars($h['auteur_nom'] ?? 'Système') ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>

                                    <?php if (empty($history)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">
                                            <i class="ph-duotone ph-history fs-2 d-block mb-2 text-muted"></i>
                                            <?= _('Aucun mouvement répertorié.') ?>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ONGLET 7 : SÉCURITÉ & HABILITATION -->
            <div class="tab-pane fade" id="tab-securite" role="tabpanel">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent py-3">
                        <h5 class="mb-0 d-flex align-items-center gap-2">
                            <i class="ph-duotone ph-shield-check text-primary"></i>
                            <span><?= _('Sécurité, Habilitations & Statut du Compte') ?></span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded border">
                                    <div class="text-muted small mb-1"><?= _('Rôle Applicatif RBAC') ?></div>
                                    <div class="h5 mb-0 text-primary fw-bold"><?= htmlspecialchars($p['nom_role'] ?? '—') ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded border">
                                    <div class="text-muted small mb-1"><?= _('Statut du Compte Applicatif') ?></div>
                                    <div class="h5 mb-0">
                                        <?= $p['actif'] ? '<span class="text-success fw-bold"><i class="ph-duotone ph-check-circle me-1"></i>'._('Actif / Connectable').'</span>' : '<span class="text-danger fw-bold"><i class="ph-duotone ph-lock-key me-1"></i>'._('Désactivé').'</span>' ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 mt-3">
                                <div class="p-3 border rounded">
                                    <div class="fw-bold mb-1"><i class="ph-duotone ph-key me-1 text-warning"></i><?= _('Réinitialisation du Mot de Passe') ?></div>
                                    <p class="text-muted small mb-2"><?= _('Pour modifier les identifiants ou réinitialiser le mot de passe de cet utilisateur, veuillez utiliser le formulaire de modification de la fiche.') ?></p>
                                    <?php if (Auth::can('edit', 'drh')): ?>
                                    <a href="/drh/edit?id=<?= $p['id_user'] ?>" class="btn btn-sm btn-light-primary">
                                        <i class="ph-duotone ph-pencil me-1"></i><?= _('Modifier le compte / mot de passe') ?>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL 1: STATUT RH -->
<div class="modal fade" id="statutModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/drh/update-status">
                <input type="hidden" name="personnel_id" value="<?= $p['id_user'] ?>">
                <div class="modal-header">
                    <h5 class="modal-title d-flex align-items-center gap-2">
                        <i class="ph-duotone ph-gear text-primary"></i>
                        <span><?= _('Changer le Statut RH') ?></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required"><?= _('Nouveau Statut RH') ?></label>
                        <select name="statut_rh" class="form-select" required>
                            <option value="en_activite" <?= ($p['statut_rh'] ?? '') === 'en_activite' ? 'selected' : '' ?>><?= _('En activité') ?></option>
                            <option value="en_conge" <?= ($p['statut_rh'] ?? '') === 'en_conge' ? 'selected' : '' ?>><?= _('En congé') ?></option>
                            <option value="suspendu" <?= ($p['statut_rh'] ?? '') === 'suspendu' ? 'selected' : '' ?>><?= _('Suspendu') ?></option>
                            <option value="demissionne" <?= ($p['statut_rh'] ?? '') === 'demissionne' ? 'selected' : '' ?>><?= _('Démissionné') ?></option>
                            <option value="licencie" <?= ($p['statut_rh'] ?? '') === 'licencie' ? 'selected' : '' ?>><?= _('Licencié') ?></option>
                            <option value="retraite" <?= ($p['statut_rh'] ?? '') === 'retraite' ? 'selected' : '' ?>><?= _('Retraité') ?></option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= _('Date d\'effet / Date de sortie') ?></label>
                        <input type="date" name="date_sortie" class="form-control" value="<?= htmlspecialchars($p['date_sortie'] ?? date('Y-m-d')) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label required"><?= _('Motif explicite de la décision') ?></label>
                        <textarea name="motif_sortie" class="form-control" rows="3" required placeholder="<?= _('Saisir le motif officiel...') ?>"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal"><?= _('Annuler') ?></button>
                    <button type="submit" class="btn btn-primary"><?= _('Enregistrer la décision') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 2: AFFECTATION -->
<div class="modal fade" id="newAssignmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/drh/assignments/store">
                <input type="hidden" name="personnel_id" value="<?= $p['id_user'] ?>">
                <div class="modal-header">
                    <h5 class="modal-title d-flex align-items-center gap-2">
                        <i class="ph-duotone ph-buildings text-primary"></i>
                        <span><?= _('Nouvelle Affectation de Cycle') ?></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required"><?= _('Cycle concerné') ?></label>
                        <select name="cycle_id" class="form-select" required>
                            <?php foreach ($cycles as $cy): ?>
                                <option value="<?= $cy['id_cycle'] ?>"><?= htmlspecialchars($cy['nom_cycle']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label required"><?= _('Date Début') ?></label>
                            <input type="date" name="date_debut" class="form-control" required value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label"><?= _('Date Fin (Optionnel)') ?></label>
                            <input type="date" name="date_fin" class="form-control">
                        </div>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="actif" value="1" id="actifCheck" checked>
                        <label class="form-check-label" for="actifCheck"><?= _('Activer l\'affectation immédiatement') ?></label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal"><?= _('Annuler') ?></button>
                    <button type="submit" class="btn btn-primary"><?= _('Valider Affectation') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 3: CONTRAT & AVENANTS -->
<div class="modal fade" id="newContractModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="/drh/contracts/store">
                <input type="hidden" name="personnel_id" value="<?= $p['id_user'] ?>">
                <input type="hidden" name="idempotency_key" value="<?= bin2hex(random_bytes(16)) ?>">
                <div class="modal-header">
                    <h5 class="modal-title d-flex align-items-center gap-2">
                        <i class="ph-duotone ph-file-text text-primary"></i>
                        <span><?= _('Nouveau Contrat / Avenant Administratif') ?></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label required"><?= _('Type de Contrat') ?></label>
                            <select name="type_contrat_id" class="form-select" required>
                                <?php foreach ($typeContrats as $tc): ?>
                                    <option value="<?= $tc['id_contrat'] ?>"><?= htmlspecialchars($tc['libelle']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= _('Employeur Juridique') ?></label>
                            <select name="entite_juridique_id" class="form-select">
                                <option value=""><?= _('Utiliser l\'employeur par défaut') ?></option>
                                <?php if (!empty($entitesJuridiques)): foreach ($entitesJuridiques as $ej): ?>
                                    <option value="<?= $ej['id'] ?>"><?= htmlspecialchars($ej['raison_sociale'] . ($ej['sigle'] ? ' ('.$ej['sigle'].')' : '')) ?></option>
                                <?php endforeach; endif; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label required"><?= _('Date Début') ?></label>
                            <input type="date" name="date_debut" class="form-control" required value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><?= _('Date Fin (laisser vide pour CDI)') ?></label>
                            <input type="date" name="date_fin" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><?= _('Période d\'essai (Jours)') ?></label>
                            <input type="number" name="periode_essai_jours" class="form-control" value="0" min="0">
                        </div>
                    </div>

                    <?php if ($can_view_sensitive): ?>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label"><?= _('Salaire de Base Contractuel') ?></label>
                            <input type="number" step="0.01" name="salaire_base" class="form-control" placeholder="0.00">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><?= _('Mode de Calcul') ?></label>
                            <select name="mode_calcul_principal" class="form-select">
                                <option value="forfait_fixe"><?= _('Forfait Fixe') ?></option>
                                <option value="taux_horaire"><?= _('Taux Horaire') ?></option>
                                <option value="taux_journalier"><?= _('Taux Journalier') ?></option>
                                <option value="commission"><?= _('Commission') ?></option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><?= _('Périodicité') ?></label>
                            <select name="periodicite_paiement" class="form-select">
                                <option value="mensuel"><?= _('Mensuel') ?></option>
                                <option value="bi_mensuel"><?= _('Bi-mensuel / Quinzaine') ?></option>
                                <option value="hebdomadaire"><?= _('Hebdomadaire') ?></option>
                                <option value="a_la_prestation"><?= _('À la prestation') ?></option>
                            </select>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label"><?= _('Type d\'Acte / Avenant') ?></label>
                            <select name="type_avenant" class="form-select">
                                <option value="creation"><?= _('Contrat Initial / Création') ?></option>
                                <option value="revalorisation_salariale"><?= _('Avenant de revalorisation salariale') ?></option>
                                <option value="changement_volume_horaire"><?= _('Avenant de changement de volume horaire') ?></option>
                                <option value="extension_duree"><?= _('Avenant d\'extension de durée / Renouvellement') ?></option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= _('N° d\'Avenant ou de Référence Légale') ?></label>
                            <input type="text" name="avenant_numero" class="form-control" placeholder="<?= _('Ex: AV-2024-001') ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label"><?= _('Commentaires / Offre Contractuelle') ?></label>
                        <textarea name="commentaire" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal"><?= _('Annuler') ?></button>
                    <button type="submit" class="btn btn-primary"><?= _('Enregistrer Contrat / Avenant') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 4: DOCUMENT UPLOAD -->
<div class="modal fade" id="uploadDocModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/drh/documents/store" enctype="multipart/form-data">
                <input type="hidden" name="personnel_id" value="<?= $p['id_user'] ?>">
                <div class="modal-header">
                    <h5 class="modal-title d-flex align-items-center gap-2">
                        <i class="ph-duotone ph-upload-simple text-primary"></i>
                        <span><?= _('Téléverser un Document') ?></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required"><?= _('Type de Document') ?></label>
                        <select name="type_document" class="form-select" required>
                            <option value="Contrat de Travail"><?= _('Contrat de Travail') ?></option>
                            <option value="Avenant"><?= _('Avenant') ?></option>
                            <option value="Pièce d\'Identité (CNI / Passeport)"><?= _('Pièce d\'Identité (CNI / Passeport)') ?></option>
                            <option value="Diplôme / Attestation"><?= _('Diplôme / Attestation') ?></option>
                            <option value="Attestation CNSS"><?= _('Attestation CNSS') ?></option>
                            <option value="Justificatif de Congé"><?= _('Justificatif de Congé') ?></option>
                            <option value="Décision de Suspension"><?= _('Décision de Suspension') ?></option>
                            <option value="Autre Document RH"><?= _('Autre Document RH') ?></option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required"><?= _('Fichier à Téléverser (PDF, PNG, JPG, DOC)') ?></label>
                        <input type="file" name="document_file" class="form-control" required>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="confidentiel" value="1" id="confCheck" checked>
                        <label class="form-check-label" for="confCheck"><?= _('Document confidentiel (Restreint aux autorisations DRH sensibles)') ?></label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal"><?= _('Annuler') ?></button>
                    <button type="submit" class="btn btn-primary"><?= _('Téléverser') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 5: VISUALISER CONTRAT -->
<div class="modal fade" id="viewContractModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title d-flex align-items-center gap-2">
                    <i class="ph-duotone ph-file-text text-primary"></i>
                    <span id="vContractTitle"><?= _('Détails de la Version Contractuelle') ?></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="vContractLoading" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="text-muted small mt-2"><?= _('Chargement des éléments contractuels...') ?></p>
                </div>
                <div id="vContractBody" class="d-none">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="text-muted small d-block"><?= _('Type de Contrat') ?></label>
                            <span class="fw-bold text-dark fs-6" id="vTypeContrat">—</span>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted small d-block"><?= _('Employeur Juridique') ?></label>
                            <span class="fw-bold text-dark fs-6" id="vEmployeur">—</span>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted small d-block"><?= _('Statut Contractuel') ?></label>
                            <span id="vStatutBadge" class="badge bg-light-primary text-primary fw-bold px-3 py-2">—</span>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="text-muted small d-block"><?= _('Date de Début') ?></label>
                            <span class="fw-semibold text-dark" id="vDateDebut">—</span>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted small d-block"><?= _('Date de Fin') ?></label>
                            <span class="fw-semibold text-dark" id="vDateFin">—</span>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted small d-block"><?= _('Période d\'Essai') ?></label>
                            <span class="fw-semibold text-dark" id="vPeriodeEssai">—</span>
                        </div>
                    </div>

                    <?php if ($can_view_sensitive): ?>
                    <div class="row g-3 mb-3 p-3 bg-light rounded border">
                        <div class="col-md-4">
                            <label class="text-muted small d-block"><?= _('Salaire de Base Contractuel') ?></label>
                            <span class="font-monospace fw-bold text-primary fs-5" id="vSalaireBase">—</span>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted small d-block"><?= _('Mode de Calcul') ?></label>
                            <span class="fw-semibold text-dark" id="vModeCalcul">—</span>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted small d-block"><?= _('Périodicité de Paiement') ?></label>
                            <span class="fw-semibold text-dark" id="vPeriodicite">—</span>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <h6 class="fw-bold mb-2"><i class="ph-duotone ph-list-plus me-1 text-primary"></i><?= _('Composants de Rémunération') ?></h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th><?= _('Code') ?></th>
                                        <th><?= _('Libellé') ?></th>
                                        <th><?= _('Nature') ?></th>
                                        <th><?= _('Valeur') ?></th>
                                    </tr>
                                </thead>
                                <tbody id="vComposantsBody">
                                    <tr><td colspan="4" class="text-center text-muted"><?= _('Aucun composant spécifique enregistré.') ?></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mb-3">
                        <h6 class="fw-bold mb-2"><i class="ph-duotone ph-coins me-1 text-primary"></i><?= _('Financements & Prise en Charge') ?></h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th><?= _('Financeur') ?></th>
                                        <th><?= _('Type') ?></th>
                                        <th><?= _('Prise en charge (%)') ?></th>
                                        <th><?= _('Plafond') ?></th>
                                    </tr>
                                </thead>
                                <tbody id="vFinancementsBody">
                                    <tr><td colspan="4" class="text-center text-muted"><?= _('Aucun financement spécifique configuré.') ?></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="text-muted small d-block"><?= _('Commentaires / Acte Légatif') ?></label>
                        <div class="p-2 bg-light border rounded small font-monospace text-dark" id="vCommentaire">—</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= _('Fermer') ?></button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL 6: ANNULER CONTRAT -->
<div class="modal fade" id="cancelContractModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/drh/contracts/cancel">
                <input type="hidden" name="personnel_id" value="<?= $p['id_user'] ?>">
                <input type="hidden" name="contract_id" id="cancelContractId">
                <div class="modal-header">
                    <h5 class="modal-title d-flex align-items-center gap-2 text-danger">
                        <i class="ph-duotone ph-warning-circle"></i>
                        <span><?= _('Annuler un Contrat / Avenant') ?></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning small mb-3">
                        <i class="ph-duotone ph-info me-1"></i>
                        <?= _('Cette action ne supprime pas la ligne d\'historique (interdiction légale). Elle marque la version contractuelle comme annulée tout en conservant l\'audit complet.') ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required"><?= _('Motif obligatoire d\'annulation') ?></label>
                        <textarea name="motif_annulation" class="form-control" rows="3" required placeholder="<?= _('Saisir le motif officiel d\'annulation...') ?>"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal"><?= _('Abandonner') ?></button>
                    <button type="submit" class="btn btn-danger"><?= _('Confirmer l\'annulation') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openNewContractModal() {
    const modalEl = document.getElementById('newContractModal');
    const form = modalEl.querySelector('form');
    form.reset();
    form.querySelector('input[name="id"]')?.remove();
    modalEl.querySelector('.modal-title span').textContent = '<?= _('Nouveau Contrat / Avenant Administratif') ?>';
    const bsModal = new bootstrap.Modal(modalEl);
    bsModal.show();
}

function prepareAmendment(contractData) {
    const modalEl = document.getElementById('newContractModal');
    const form = modalEl.querySelector('form');

    form.querySelector('input[name="id"]')?.remove();

    if (form.querySelector('select[name="type_contrat_id"]')) {
        form.querySelector('select[name="type_contrat_id"]').value = contractData.type_contrat_id || '';
    }
    if (form.querySelector('select[name="entite_juridique_id"]')) {
        form.querySelector('select[name="entite_juridique_id"]').value = contractData.entite_juridique_id || '';
    }
    if (form.querySelector('input[name="date_debut"]')) {
        form.querySelector('input[name="date_debut"]').value = '<?= date('Y-m-d') ?>';
    }
    if (form.querySelector('input[name="date_fin"]')) {
        form.querySelector('input[name="date_fin"]').value = contractData.date_fin || '';
    }
    if (form.querySelector('input[name="salaire_base"]')) {
        form.querySelector('input[name="salaire_base"]').value = contractData.salaire_base || '';
    }
    if (form.querySelector('select[name="mode_calcul_principal"]')) {
        form.querySelector('select[name="mode_calcul_principal"]').value = contractData.mode_calcul_principal || 'forfait_fixe';
    }
    if (form.querySelector('select[name="periodicite_paiement"]')) {
        form.querySelector('select[name="periodicite_paiement"]').value = contractData.periodicite_paiement || 'mensuel';
    }
    if (form.querySelector('select[name="type_avenant"]')) {
        form.querySelector('select[name="type_avenant"]').value = 'revalorisation_salariale';
    }

    modalEl.querySelector('.modal-title span').textContent = '<?= _('Créer un Avenant au Contrat') ?> v' + (contractData.version_num || 1);
    const bsModal = new bootstrap.Modal(modalEl);
    bsModal.show();
}

function prepareCancelContract(contractId, versionNum) {
    document.getElementById('cancelContractId').value = contractId;
    const modalEl = document.getElementById('cancelContractModal');
    const bsModal = new bootstrap.Modal(modalEl);
    bsModal.show();
}

function viewContractDetails(contractId) {
    const modalEl = document.getElementById('viewContractModal');
    document.getElementById('vContractLoading').classList.remove('d-none');
    document.getElementById('vContractBody').classList.add('d-none');

    const bsModal = new bootstrap.Modal(modalEl);
    bsModal.show();

    fetch('/drh/contracts/details?id=' + contractId)
        .then(r => r.json())
        .then(res => {
            if (!res.success || !res.contract) {
                alert(res.error || '<?= _('Erreur lors de la récupération des détails.') ?>');
                bsModal.hide();
                return;
            }
            const c = res.contract;
            document.getElementById('vContractTitle').textContent = '<?= _('Détails Version') ?> v' + (c.version_num || 1) + ' (CTR-' + String(c.contrat_souche_id || c.id).padStart(6, '0') + ')';
            document.getElementById('vTypeContrat').textContent = c.contrat_libelle || '—';
            document.getElementById('vEmployeur').textContent = c.employeur_nom || c.employeur_sigle || 'Établissement';

            const badge = document.getElementById('vStatutBadge');
            badge.textContent = (c.statut_contrat || 'actif').toUpperCase();

            document.getElementById('vDateDebut').textContent = c.date_debut ? new Date(c.date_debut).toLocaleDateString('fr-FR') : '—';
            document.getElementById('vDateFin').textContent = c.date_fin ? new Date(c.date_fin).toLocaleDateString('fr-FR') : 'Indéterminée / CDI';
            document.getElementById('vPeriodeEssai').textContent = (c.periode_essai_jours || 0) + ' jours';

            const salEl = document.getElementById('vSalaireBase');
            if (salEl) {
                salEl.textContent = parseFloat(c.salaire_base || 0).toLocaleString('fr-FR', {minimumFractionDigits: 2}) + ' ' + (c.devise || 'XAF');
            }
            const modeEl = document.getElementById('vModeCalcul');
            if (modeEl) modeEl.textContent = c.mode_calcul_principal || 'forfait_fixe';
            const perEl = document.getElementById('vPeriodicite');
            if (perEl) perEl.textContent = c.periodicite_paiement || 'mensuel';

            document.getElementById('vCommentaire').textContent = c.commentaire || '<?= _('Aucune observation.') ?>';

            const cBody = document.getElementById('vComposantsBody');
            if (c.composants && c.composants.length > 0) {
                cBody.innerHTML = c.composants.map(comp => `
                    <tr>
                        <td class="font-monospace">${comp.code_composant || ''}</td>
                        <td>${comp.libelle || ''}</td>
                        <td><span class="badge bg-light-secondary text-dark">${comp.nature_composant || ''}</span></td>
                        <td class="fw-bold font-monospace">${parseFloat(comp.valeur_numerique || 0).toFixed(2)} ${comp.devise_code || ''}</td>
                    </tr>
                `).join('');
            } else {
                cBody.innerHTML = '<tr><td colspan="4" class="text-center text-muted"><?= _('Aucun composant spécifique enregistré.') ?></td></tr>';
            }

            const fBody = document.getElementById('vFinancementsBody');
            if (c.financements && c.financements.length > 0) {
                fBody.innerHTML = c.financements.map(fin => `
                    <tr>
                        <td>${fin.financeur_nom || ''}</td>
                        <td>${fin.type_financeur || ''}</td>
                        <td>${parseFloat(fin.pourcentage_prise_en_charge || 100).toFixed(2)}%</td>
                        <td>${fin.montant_plafone ? parseFloat(fin.montant_plafone).toFixed(2) : 'Sans plafond'}</td>
                    </tr>
                `).join('');
            } else {
                fBody.innerHTML = '<tr><td colspan="4" class="text-center text-muted"><?= _('Aucun financement spécifique configuré.') ?></td></tr>';
            }

            document.getElementById('vContractLoading').classList.add('d-none');
            document.getElementById('vContractBody').classList.remove('d-none');
        })
        .catch(err => {
            alert('<?= _('Erreur réseau lors de la récupération des détails.') ?>');
            bsModal.hide();
        });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
