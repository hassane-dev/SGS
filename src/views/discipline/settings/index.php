<?php
$title = _("Paramètres de Vie Scolaire & Discipline");
require_once __DIR__ . '/../../layouts/header_able.php';
require_once __DIR__ . '/../../layouts/sidebar_able.php';

$canManage = Auth::can('manage_config', 'discipline');
?>

<div class="pc-container">
    <div class="pc-content">

        <!-- Breadcrumb -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h5 class="m-b-10"><?= _("Paramètres de Vie Scolaire & Discipline") ?></h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/home"><?= _("Accueil") ?></a></li>
                            <li class="breadcrumb-item"><a href="/discipline/settings"><?= _("Discipline") ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _("Configuration") ?></li>
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

        <!-- Main Card with Tabs -->
        <div class="row">
            <div class="col-sm-12">
                <div class="card card-tab">
                    <div class="card-header border-bottom-0 pb-0">
                        <ul class="nav nav-tabs profile-tabs" id="disciplineTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <a class="nav-link <?= $activeTab === 'incidents' ? 'active' : '' ?>" id="incidents-tab" data-bs-toggle="tab" href="#incidents" role="tab" aria-controls="incidents" aria-selected="<?= $activeTab === 'incidents' ? 'true' : 'false' ?>">
                                    <i class="ph-duotone ph-warning me-2"></i><?= _("Types d'Incidents") ?>
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link <?= $activeTab === 'sanctions' ? 'active' : '' ?>" id="sanctions-tab" data-bs-toggle="tab" href="#sanctions" role="tab" aria-controls="sanctions" aria-selected="<?= $activeTab === 'sanctions' ? 'true' : 'false' ?>">
                                    <i class="ph-duotone ph-gavel me-2"></i><?= _("Types de Sanctions") ?>
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div class="card-body">
                        <div class="tab-content" id="disciplineTabsContent">

                            <!-- TAB 1: INCIDENTS -->
                            <div class="tab-pane fade <?= $activeTab === 'incidents' ? 'show active' : '' ?>" id="incidents" role="tabpanel" aria-labelledby="incidents-tab">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="mb-0"><?= _("Référentiel des Types d'Incidents") ?></h5>
                                    <?php if ($canManage): ?>
                                        <button class="btn btn-primary d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#modalIncident" onclick="resetIncidentForm()">
                                            <i class="ph-duotone ph-plus fs-5"></i><?= _("Nouveau type d'incident") ?>
                                        </button>
                                    <?php endif; ?>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th><?= _("Code") ?></th>
                                                <th><?= _("Libellé") ?></th>
                                                <th><?= _("Niveau de Gravité") ?></th>
                                                <th><?= _("Statut") ?></th>
                                                <?php if ($canManage): ?>
                                                    <th class="text-end"><?= _("Actions") ?></th>
                                                <?php endif; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($incidents)): ?>
                                                <tr>
                                                    <td colspan="<?= $canManage ? 5 : 4 ?>" class="text-center text-muted py-4">
                                                        <i class="ph-duotone ph-info fs-3 d-block mb-2"></i>
                                                        <?= _("Aucun type d'incident configuré pour cet établissement.") ?>
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($incidents as $inc): ?>
                                                    <tr>
                                                        <td><code><?= htmlspecialchars($inc['code']) ?></code></td>
                                                        <td class="fw-bold"><?= htmlspecialchars($inc['libelle']) ?></td>
                                                        <td>
                                                            <?php
                                                            $badgeClass = match($inc['niveau_gravite']) {
                                                                'mineur' => 'bg-light-success text-success',
                                                                'moyen' => 'bg-light-warning text-warning',
                                                                'grave' => 'bg-light-danger text-danger',
                                                                'tres_grave' => 'bg-danger text-white',
                                                                default => 'bg-light-secondary text-secondary'
                                                            };
                                                            $graviteLabel = match($inc['niveau_gravite']) {
                                                                'mineur' => _("Mineur"),
                                                                'moyen' => _("Moyen"),
                                                                'grave' => _("Grave"),
                                                                'tres_grave' => _("Très Grave"),
                                                                default => htmlspecialchars($inc['niveau_gravite'])
                                                            };
                                                            ?>
                                                            <span class="badge <?= $badgeClass ?>"><?= $graviteLabel ?></span>
                                                        </td>
                                                        <td>
                                                            <?php if ($inc['actif']): ?>
                                                                <span class="badge bg-light-success text-success"><i class="ph-duotone ph-check-circle me-1"></i><?= _("Actif") ?></span>
                                                            <?php else: ?>
                                                                <span class="badge bg-light-secondary text-muted"><i class="ph-duotone ph-minus-circle me-1"></i><?= _("Inactif") ?></span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <?php if ($canManage): ?>
                                                            <td class="text-end">
                                                                <button class="btn btn-sm btn-icon btn-light-primary me-1"
                                                                        title="<?= _("Modifier") ?>"
                                                                        onclick='editIncident(<?= json_encode($inc, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                                                    <i class="ph-duotone ph-pencil"></i>
                                                                </button>
                                                                <a href="/discipline/settings/incidents/toggle?id=<?= $inc['id'] ?>"
                                                                   class="btn btn-sm btn-icon <?= $inc['actif'] ? 'btn-light-danger' : 'btn-light-success' ?>"
                                                                   title="<?= $inc['actif'] ? _("Désactiver") : _("Activer") ?>">
                                                                    <i class="ph-duotone <?= $inc['actif'] ? 'ph-lock' : 'ph-lock-key-open' ?>"></i>
                                                                </a>
                                                            </td>
                                                        <?php endif; ?>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- TAB 2: SANCTIONS -->
                            <div class="tab-pane fade <?= $activeTab === 'sanctions' ? 'show active' : '' ?>" id="sanctions" role="tabpanel" aria-labelledby="sanctions-tab">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="mb-0"><?= _("Référentiel des Types de Sanctions") ?></h5>
                                    <?php if ($canManage): ?>
                                        <button class="btn btn-primary d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#modalSanction" onclick="resetSanctionForm()">
                                            <i class="ph-duotone ph-plus fs-5"></i><?= _("Nouveau type de sanction") ?>
                                        </button>
                                    <?php endif; ?>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th><?= _("Code") ?></th>
                                                <th><?= _("Libellé") ?></th>
                                                <th><?= _("Saisie Exigée") ?></th>
                                                <th><?= _("Autorité Minimale") ?></th>
                                                <th><?= _("Bulletin") ?></th>
                                                <th><?= _("Statut") ?></th>
                                                <?php if ($canManage): ?>
                                                    <th class="text-end"><?= _("Actions") ?></th>
                                                <?php endif; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($sanctions)): ?>
                                                <tr>
                                                    <td colspan="<?= $canManage ? 7 : 6 ?>" class="text-center text-muted py-4">
                                                        <i class="ph-duotone ph-info fs-3 d-block mb-2"></i>
                                                        <?= _("Aucun type de sanction configuré pour cet établissement.") ?>
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($sanctions as $sanc): ?>
                                                    <tr>
                                                        <td><code><?= htmlspecialchars($sanc['code']) ?></code></td>
                                                        <td class="fw-bold"><?= htmlspecialchars($sanc['libelle']) ?></td>
                                                        <td>
                                                            <?php
                                                            $reqs = [];
                                                            if ($sanc['demande_duree_jours']) $reqs[] = _("Durée (jours)");
                                                            if ($sanc['demande_heures']) $reqs[] = _("Heures");
                                                            if (empty($reqs)) echo '<span class="text-muted small">' . _("Aucune") . '</span>';
                                                            else echo implode(', ', $reqs);
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <?php
                                                            $autoriteLabel = match($sanc['autorite_min_requise']) {
                                                                'enseignant' => _("Enseignant"),
                                                                'surveillant_general' => _("Surveillant Général"),
                                                                'censeur' => _("Censeur / Directeur des Études"),
                                                                'proviseur' => _("Proviseur / Chef d'établissement"),
                                                                'conseil_discipline' => _("Conseil de Discipline"),
                                                                default => htmlspecialchars($sanc['autorite_min_requise'])
                                                            };
                                                            ?>
                                                            <span class="badge bg-light-info text-info"><?= $autoriteLabel ?></span>
                                                        </td>
                                                        <td>
                                                            <?php if ($sanc['affiche_sur_bulletin']): ?>
                                                                <span class="badge bg-light-primary text-primary"><i class="ph-duotone ph-eye me-1"></i><?= _("Visible") ?></span>
                                                            <?php else: ?>
                                                                <span class="badge bg-light-secondary text-muted"><i class="ph-duotone ph-eye-slash me-1"></i><?= _("Masqué") ?></span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($sanc['actif']): ?>
                                                                <span class="badge bg-light-success text-success"><i class="ph-duotone ph-check-circle me-1"></i><?= _("Actif") ?></span>
                                                            <?php else: ?>
                                                                <span class="badge bg-light-secondary text-muted"><i class="ph-duotone ph-minus-circle me-1"></i><?= _("Inactif") ?></span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <?php if ($canManage): ?>
                                                            <td class="text-end">
                                                                <button class="btn btn-sm btn-icon btn-light-primary me-1"
                                                                        title="<?= _("Modifier") ?>"
                                                                        onclick='editSanction(<?= json_encode($sanc, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                                                    <i class="ph-duotone ph-pencil"></i>
                                                                </button>
                                                                <a href="/discipline/settings/sanctions/toggle?id=<?= $sanc['id'] ?>"
                                                                   class="btn btn-sm btn-icon <?= $sanc['actif'] ? 'btn-light-danger' : 'btn-light-success' ?>"
                                                                   title="<?= $sanc['actif'] ? _("Désactiver") : _("Activer") ?>">
                                                                    <i class="ph-duotone <?= $sanc['actif'] ? 'ph-lock' : 'ph-lock-key-open' ?>"></i>
                                                                </a>
                                                            </td>
                                                        <?php endif; ?>
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

    </div>
</div>

<?php if ($canManage): ?>
<!-- MODAL INCIDENT -->
<div class="modal fade" id="modalIncident" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="/discipline/settings/incidents/store" method="POST" id="formIncident">
    <?= csrf_field() ?>
                <input type="hidden" name="id" id="inc_id" value="">
                <div class="modal-header">
                    <h5 class="modal-title" id="titleIncidentModal"><?= _("Nouveau Type d'Incident") ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="inc_code" class="form-label required-field"><?= _("Code Unique") ?></label>
                        <input type="text" name="code" id="inc_code" class="form-control text-uppercase" placeholder="EX: INSOLENCE" required>
                        <div class="form-text text-muted"><?= _("Code alphanumérique interne (ex: RETARD, INSOLENCE, FRAUDE).") ?></div>
                    </div>
                    <div class="mb-3">
                        <label for="inc_libelle" class="form-label required-field"><?= _("Libellé Officiel") ?></label>
                        <input type="text" name="libelle" id="inc_libelle" class="form-control" placeholder="EX: Insolence envers un personnel" required>
                    </div>
                    <div class="mb-3">
                        <label for="inc_gravite" class="form-label required-field"><?= _("Niveau de Gravité") ?></label>
                        <select name="niveau_gravite" id="inc_gravite" class="form-select" required>
                            <option value="mineur"><?= _("Mineur (Rappel à l'ordre, manque d'assiduité léger)") ?></option>
                            <option value="moyen" selected><?= _("Moyen (Perturbation, insolence, retard répété)") ?></option>
                            <option value="grave"><?= _("Grave (Fraude, bagarre, insubordination)") ?></option>
                            <option value="tres_grave"><?= _("Très Grave (Violence physique, vol, dégradation majeure)") ?></option>
                        </select>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" name="actif" id="inc_actif" value="1" checked>
                        <label class="form-check-label" for="inc_actif"><?= _("Type d'incident actif") ?></label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link text-muted" data-bs-dismiss="modal"><?= _("Annuler") ?></button>
                    <button type="submit" class="btn btn-primary"><?= _("Enregistrer") ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL SANCTION -->
<div class="modal fade" id="modalSanction" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="/discipline/settings/sanctions/store" method="POST" id="formSanction">
    <?= csrf_field() ?>
                <input type="hidden" name="id" id="sanc_id" value="">
                <div class="modal-header">
                    <h5 class="modal-title" id="titleSanctionModal"><?= _("Nouveau Type de Sanction") ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="sanc_code" class="form-label required-field"><?= _("Code Unique") ?></label>
                        <input type="text" name="code" id="sanc_code" class="form-control text-uppercase" placeholder="EX: AVERTISSEMENT" required>
                        <div class="form-text text-muted"><?= _("Code alphanumérique interne (ex: AVERTISSEMENT, BLAME, EXCLUSION_TEMP).") ?></div>
                    </div>
                    <div class="mb-3">
                        <label for="sanc_libelle" class="form-label required-field"><?= _("Libellé Officiel") ?></label>
                        <input type="text" name="libelle" id="sanc_libelle" class="form-control" placeholder="EX: Avertissement écrit" required>
                    </div>
                    <div class="mb-3">
                        <label for="sanc_autorite" class="form-label required-field"><?= _("Autorité Minimale Requise") ?></label>
                        <select name="autorite_min_requise" id="sanc_autorite" class="form-select" required>
                            <option value="enseignant"><?= _("Enseignant") ?></option>
                            <option value="surveillant_general" selected><?= _("Surveillant Général") ?></option>
                            <option value="censeur"><?= _("Censeur / Directeur des Études") ?></option>
                            <option value="proviseur"><?= _("Proviseur / Chef d'établissement") ?></option>
                            <option value="conseil_discipline"><?= _("Conseil de Discipline") ?></option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= _("Informations Exigées lors de la Saisie") ?></label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="demande_duree_jours" id="sanc_demande_duree" value="1">
                            <label class="form-check-label" for="sanc_demande_duree"><?= _("Exiger une durée en jours (ex: 3 jours d'exclusion)") ?></label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="demande_heures" id="sanc_demande_heures" value="1">
                            <label class="form-check-label" for="sanc_demande_heures"><?= _("Exiger un nombre d'heures (ex: 2 heures de retenue)") ?></label>
                        </div>
                    </div>
                    <div class="mb-3 border-top pt-3">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="affiche_sur_bulletin" id="sanc_affiche_bulletin" value="1">
                            <label class="form-check-label" for="sanc_affiche_bulletin"><?= _("Afficher sur le bulletin scolaire (Option de paramétrage)") ?></label>
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="actif" id="sanc_actif" value="1" checked>
                            <label class="form-check-label" for="sanc_actif"><?= _("Type de sanction actif") ?></label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link text-muted" data-bs-dismiss="modal"><?= _("Annuler") ?></button>
                    <button type="submit" class="btn btn-primary"><?= _("Enregistrer") ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function resetIncidentForm() {
    document.getElementById('formIncident').reset();
    document.getElementById('inc_id').value = '';
    document.getElementById('inc_actif').checked = true;
    document.getElementById('titleIncidentModal').innerText = "<?= _("Nouveau Type d'Incident") ?>";
}

function editIncident(data) {
    document.getElementById('inc_id').value = data.id || '';
    document.getElementById('inc_code').value = data.code || '';
    document.getElementById('inc_libelle').value = data.libelle || '';
    document.getElementById('inc_gravite').value = data.niveau_gravite || 'moyen';
    document.getElementById('inc_actif').checked = parseInt(data.actif) === 1;
    document.getElementById('titleIncidentModal').innerText = "<?= _("Modifier le Type d'Incident") ?>";
    new bootstrap.Modal(document.getElementById('modalIncident')).show();
}

function resetSanctionForm() {
    document.getElementById('formSanction').reset();
    document.getElementById('sanc_id').value = '';
    document.getElementById('sanc_actif').checked = true;
    document.getElementById('titleSanctionModal').innerText = "<?= _("Nouveau Type de Sanction") ?>";
}

function editSanction(data) {
    document.getElementById('sanc_id').value = data.id || '';
    document.getElementById('sanc_code').value = data.code || '';
    document.getElementById('sanc_libelle').value = data.libelle || '';
    document.getElementById('sanc_autorite').value = data.autorite_min_requise || 'surveillant_general';
    document.getElementById('sanc_demande_duree').checked = parseInt(data.demande_duree_jours) === 1;
    document.getElementById('sanc_demande_heures').checked = parseInt(data.demande_heures) === 1;
    document.getElementById('sanc_affiche_bulletin').checked = parseInt(data.affiche_sur_bulletin) === 1;
    document.getElementById('sanc_actif').checked = parseInt(data.actif) === 1;
    document.getElementById('titleSanctionModal').innerText = "<?= _("Modifier le Type de Sanction") ?>";
    new bootstrap.Modal(document.getElementById('modalSanction')).show();
}
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../../layouts/footer_able.php'; ?>