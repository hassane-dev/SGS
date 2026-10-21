<?php
/**
 * Vue : Tableau de bord des affectations des surveillants
 */
require_once __DIR__ . '/../layouts/header_able.php';
require_once __DIR__ . '/../layouts/sidebar_able.php';

$canManage = Auth::can('manage_surveillants', 'pedagogy') || Auth::can('view_all_lycees', 'lycee');

$respLabels = [
    'surveillant_classe' => 'Surveillant de classe (Opérationnel)',
    'surveillant_general' => 'Surveillant Général (Supervision)'
];

$porteeLabels = [
    'classe' => 'Classe unique',
    'groupe_classes' => 'Groupe de classes',
    'niveau' => 'Niveau entier',
    'cycle' => 'Cycle entier',
    'etablissement' => 'Établissement entier'
];
?>

<div class="pc-container">
    <div class="pc-content">
        <!-- Breadcrumb & Header -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h5 class="m-b-10"><?= _("Affectation & Répartition des Surveillants") ?></h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/dashboard"><?= _("Accueil") ?></a></li>
                            <li class="breadcrumb-item"><?= _("Vie Scolaire") ?></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _("Surveillants") ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Flash messages -->
        <?php if (!empty($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="ph-duotone ph-check-circle me-2"></i><?= htmlspecialchars($_SESSION['success_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="ph-duotone ph-warning-circle me-2"></i><?= htmlspecialchars($_SESSION['error_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <!-- Action Bar / CTAs -->
        <div class="row mb-4">
            <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <span class="badge bg-primary fs-6">
                        <i class="ph-duotone ph-calendar me-1"></i>
                        <?= htmlspecialchars($activeYear['libelle'] ?? 'Année active') ?>
                    </span>
                </div>
                <?php if ($canManage): ?>
                <div class="d-flex gap-2">
                    <a href="/surveillants/repartition" class="btn btn-primary">
                        <i class="ph-duotone ph-arrows-merge me-1"></i><?= _("Répartition Automatique") ?>
                    </a>
                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalNewGroup">
                        <i class="ph-duotone ph-folder-plus me-1"></i><?= _("Créer un Groupe") ?>
                    </button>
                    <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalNewAssignment">
                        <i class="ph-duotone ph-user-plus me-1"></i><?= _("Nouvelle Affectation") ?>
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Groupes de classes récapitulatif -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="ph-duotone ph-folders me-2 text-primary"></i><?= _("Groupes de Classes Configurés") ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($groupes)): ?>
                            <p class="text-muted mb-0"><?= _("Aucun groupe de classes configuré pour cette année académique.") ?></p>
                        <?php else: ?>
                            <div class="row g-3">
                                <?php foreach ($groupes as $g): ?>
                                    <div class="col-md-4 col-sm-6">
                                        <div class="card border h-100 mb-0">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <h6 class="fw-bold text-primary mb-1"><?= htmlspecialchars($g['nom_groupe']) ?></h6>
                                                    <?php if ($canManage): ?>
                                                    <form method="POST" action="/surveillants/delete-group" onsubmit="return confirm('Supprimer ce groupe ?');" class="d-inline">
                                                        <?= csrf_field() ?>
                                                        <input type="hidden" name="id" value="<?= $g['id'] ?>">
                                                        <button type="submit" class="btn btn-link text-danger p-0 border-0"><i class="ph-duotone ph-trash fs-5"></i></button>
                                                    </form>
                                                    <?php endif; ?>
                                                </div>
                                                <p class="small text-muted mb-2"><?= htmlspecialchars($g['description'] ?? 'Pas de description') ?></p>
                                                <span class="badge bg-light-primary text-primary me-1">
                                                    <?= (int)$g['total_classes'] ?> <?= _("classes membres") ?>
                                                </span>
                                                <span class="badge bg-light-secondary text-secondary">
                                                    <?= htmlspecialchars($g['type_groupe']) ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Registre Général des Affectations -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="ph-duotone ph-users-three me-2 text-primary"></i><?= _("Registre des Affectations Actives & Historiques") ?></h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th><?= _("Surveillant") ?></th>
                                        <th><?= _("Responsabilité") ?></th>
                                        <th><?= _("Portée & Cible") ?></th>
                                        <th><?= _("Date début") ?></th>
                                        <th><?= _("Statut") ?></th>
                                        <th class="text-end"><?= _("Actions") ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($affectations)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted">
                                                <i class="ph-duotone ph-tray fs-2 d-block mb-2"></i>
                                                <?= _("Aucune affectation enregistrée pour cette année académique.") ?>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($affectations as $aff): ?>
                                            <tr>
                                                <td>
                                                    <?php if (!empty($aff['surveillant_nom'])): ?>
                                                        <div class="fw-bold"><?= htmlspecialchars($aff['surveillant_prenom'] . ' ' . $aff['surveillant_nom']) ?></div>
                                                        <small class="text-muted"><?= htmlspecialchars($aff['surveillant_email'] ?? '') ?></small>
                                                    <?php else: ?>
                                                        <span class="text-muted fst-italic"><?= htmlspecialchars($aff['surveillant_nom_snapshot'] ?? _("Utilisateur archivé")) ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge <?= $aff['type_responsabilite'] === 'surveillant_general' ? 'bg-purple text-white' : 'bg-info text-white' ?>">
                                                        <?= htmlspecialchars($respLabels[$aff['type_responsabilite']] ?? $aff['type_responsabilite']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="fw-semibold me-1"><?= htmlspecialchars($porteeLabels[$aff['portee_type']] ?? $aff['portee_type']) ?> :</div>
                                                    <?php if ($aff['portee_type'] === 'classe'): ?>
                                                        <span class="badge bg-light-dark text-dark">
                                                            <?= htmlspecialchars(($aff['classe_niveau'] ?? '') . ' ' . ($aff['classe_serie'] ?? '') . ' ' . ($aff['classe_numero'] ?? '') ?: ($aff['classe_nom_snapshot'] ?? '')) ?>
                                                        </span>
                                                    <?php elseif ($aff['portee_type'] === 'groupe_classes'): ?>
                                                        <span class="badge bg-light-primary text-primary">
                                                            <?= htmlspecialchars($aff['nom_groupe'] ?? $aff['groupe_nom_snapshot'] ?? '') ?>
                                                        </span>
                                                    <?php elseif ($aff['portee_type'] === 'niveau'): ?>
                                                        <span class="badge bg-light-warning text-dark">
                                                            Niveau <?= htmlspecialchars($aff['niveau_code'] ?? '') ?>
                                                        </span>
                                                    <?php elseif ($aff['portee_type'] === 'cycle'): ?>
                                                        <span class="badge bg-light-success text-success">
                                                            Cycle <?= htmlspecialchars($aff['nom_cycle'] ?? $aff['cycle_nom_snapshot'] ?? '') ?>
                                                        </span>
                                                    <?php elseif ($aff['portee_type'] === 'etablissement'): ?>
                                                        <span class="badge bg-light-danger text-danger">
                                                            <?= _("Tout l'établissement") ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($aff['date_debut']) ?></td>
                                                <td>
                                                    <?php if ($aff['statut'] === 'actif'): ?>
                                                        <span class="badge bg-success"><?= _("Actif") ?></span>
                                                    <?php elseif ($aff['statut'] === 'suspendu'): ?>
                                                        <span class="badge bg-warning text-dark"><?= _("Suspendu") ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary"><?= _("Terminé / Archivé") ?></span>
                                                    <?php endif; ?>

                                                    <?php if (!empty($aff['est_reprise_legacy'])): ?>
                                                        <span class="badge bg-light-secondary text-secondary ms-1" title="<?= _("Reprise historique d'anciennes données") ?>">Legacy</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end">
                                                    <?php if ($canManage && $aff['statut'] === 'actif'): ?>
                                                        <form method="POST" action="/surveillants/terminate" onsubmit="return confirm('Clôturer cette affectation ?');" class="d-inline">
                                                            <?= csrf_field() ?>
                                                            <input type="hidden" name="id" value="<?= $aff['id'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="<?= _("Clôturer") ?>">
                                                                <i class="ph-duotone ph-stop-circle me-1"></i><?= _("Clôturer") ?>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
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
    </div>
</div>

<!-- Modal Nouveau Groupe -->
<div class="modal fade" id="modalNewGroup" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="/surveillants/store-group">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ph-duotone ph-folder-plus me-2"></i><?= _("Créer un Groupe de Classes") ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold"><?= _("Nom du groupe") ?> *</label>
                        <input type="text" name="nom_groupe" class="form-control" placeholder="ex: Groupe 6e (6e1 à 6e3)" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= _("Description") ?></label>
                        <textarea name="description" class="form-control" rows="2" placeholder="description ou motif..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold"><?= _("Sélectionner les classes membres") ?> *</label>
                        <div class="row g-2" style="max-height: 250px; overflow-y: auto;">
                            <?php foreach ($classes as $c): ?>
                                <div class="col-md-4 col-sm-6">
                                    <div class="form-check border rounded p-2">
                                        <input class="form-check-input" type="checkbox" name="classe_ids[]" value="<?= $c['id_classe'] ?>" id="chk_grp_c_<?= $c['id_classe'] ?>">
                                        <label class="form-check-label w-100" for="chk_grp_c_<?= $c['id_classe'] ?>">
                                            <?= htmlspecialchars($c['niveau'] . ' ' . ($c['serie'] ?? '') . ' ' . ($c['numero'] ?? '')) ?>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= _("Annuler") ?></button>
                    <button type="submit" class="btn btn-primary"><?= _("Enregistrer le groupe") ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Nouvelle Affectation -->
<div class="modal fade" id="modalNewAssignment" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/surveillants/store-assignment">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title"><div class="ph-duotone ph-user-plus me-2 d-inline"></div><?= _("Nouvelle Affectation Individuelle") ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold"><?= _("Surveillant") ?> *</label>
                        <select name="surveillant_id" class="form-select" required>
                            <option value=""><?= _("-- Sélectionner un surveillant --") ?></option>
                            <?php foreach ($surveillants as $s): ?>
                                <option value="<?= $s['id_user'] ?>"><?= htmlspecialchars($s['prenom'] . ' ' . $s['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold"><?= _("Type de Responsabilité") ?> *</label>
                        <select name="type_responsabilite" class="form-select" required>
                            <option value="surveillant_classe"><?= _("Surveillant de classe (Opérationnel)") ?></option>
                            <option value="surveillant_general"><?= _("Surveillant Général (Supervision)") ?></option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold"><?= _("Type de Portée") ?> *</label>
                        <select name="portee_type" id="sel_portee_type" class="form-select" onchange="togglePorteeTargetFields();" required>
                            <option value="classe"><?= _("Classe unique") ?></option>
                            <option value="groupe_classes"><?= _("Groupe de classes") ?></option>
                            <option value="niveau"><?= _("Niveau entier") ?></option>
                            <option value="etablissement"><?= _("Tout l'établissement") ?></option>
                        </select>
                    </div>

                    <!-- Target: Classe -->
                    <div class="mb-3 portee-target" id="tgt_classe">
                        <label class="form-label fw-bold"><?= _("Classe cible") ?></label>
                        <select name="classe_id" class="form-select">
                            <option value=""><?= _("-- Sélectionner une classe --") ?></option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?= $c['id_classe'] ?>"><?= htmlspecialchars($c['niveau'] . ' ' . ($c['serie'] ?? '') . ' ' . ($c['numero'] ?? '')) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Target: Groupe -->
                    <div class="mb-3 portee-target d-none" id="tgt_groupe">
                        <label class="form-label fw-bold"><?= _("Groupe cible") ?></label>
                        <select name="groupe_id" class="form-select">
                            <option value=""><?= _("-- Sélectionner un groupe --") ?></option>
                            <?php foreach ($groupes as $g): ?>
                                <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['nom_groupe']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Target: Niveau -->
                    <div class="mb-3 portee-target d-none" id="tgt_niveau">
                        <label class="form-label fw-bold"><?= _("Code niveau (ex: 6e)") ?></label>
                        <input type="text" name="niveau_code" class="form-control" placeholder="ex: 6e">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold"><?= _("Date de début") ?> *</label>
                        <input type="date" name="date_debut" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= _("Annuler") ?></button>
                    <button type="submit" class="btn btn-success"><?= _("Créer l'affectation") ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function togglePorteeTargetFields() {
    const val = document.getElementById('sel_portee_type').value;
    document.querySelectorAll('.portee-target').forEach(el => el.classList.add('d-none'));

    if (val === 'classe') {
        document.getElementById('tgt_classe').classList.remove('d-none');
    } else if (val === 'groupe_classes') {
        document.getElementById('tgt_groupe').classList.remove('d-none');
    } else if (val === 'niveau') {
        document.getElementById('tgt_niveau').classList.remove('d-none');
    }
}
</script>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
