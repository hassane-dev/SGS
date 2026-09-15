<?php
/**
 * Vue : Fiche Détillée d'un Conseil de Discipline (Phase 6.1)
 */
require_once __DIR__ . '/../../layouts/header_able.php';
require_once __DIR__ . '/../../layouts/sidebar_able.php';

$councilStatutBadges = [
    'planifie' => 'bg-warning text-dark',
    'convoque' => 'bg-info text-white',
    'en_session' => 'bg-primary',
    'delibere' => 'bg-purple text-white',
    'cloture' => 'bg-success',
    'annule' => 'bg-secondary',
];

$councilStatutLabels = [
    'planifie' => 'Planifié',
    'convoque' => 'Convoqué',
    'en_session' => 'En session',
    'delibere' => 'En délibération',
    'cloture' => 'Clôturé',
    'annule' => 'Annulé',
];

$roleLabels = [
    'auteur_principal' => 'Auteur Principal',
    'co_auteur' => 'Co-auteur',
    'complice' => 'Complice',
    'victime' => 'Victime',
    'temoin' => 'Témoin',
];

$isEditable = !in_array($council['statut'], ['cloture', 'annule'], true);
?>

<!-- [ Main Content ] start -->
<div class="pc-container">
    <div class="pc-content">

        <!-- Fil d'Ariane -->
        <div class="page-header mb-3">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <ul class="breadcrumb mb-1">
                            <li class="breadcrumb-item"><a href="/dashboard">Accueil</a></li>
                            <li class="breadcrumb-item"><a href="/discipline/councils">Conseils de Discipline</a></li>
                            <li class="breadcrumb-item active"><?= htmlspecialchars($council['code']) ?></li>
                        </ul>
                        <h4 class="mb-0"><?= htmlspecialchars($council['titre']) ?></h4>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <span class="badge <?= $councilStatutBadges[$council['statut']] ?? 'bg-secondary' ?> fs-6 px-3 py-2">
                            <?= $councilStatutLabels[$council['statut']] ?? ucfirst($council['statut']) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Flash Messages -->
        <?php if (!empty($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="ph-duotone ph-check-circle me-2 fs-5"></i><?= htmlspecialchars($_SESSION['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="ph-duotone ph-warning-circle me-2 fs-5"></i><?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Entête Informations Conseil -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 border-end">
                        <small class="text-muted d-block fw-semibold mb-1">Code Session</small>
                        <strong class="fs-5 text-primary"><?= htmlspecialchars($council['code']) ?></strong>
                    </div>
                    <div class="col-md-3 border-end">
                        <small class="text-muted d-block fw-semibold mb-1">Date & Horaires</small>
                        <strong><i class="ph-duotone ph-calendar me-1"></i><?= date('d/m/Y', strtotime($council['date_conseil'])) ?></strong>
                        <small class="d-block text-muted"><?= htmlspecialchars($council['heure_debut'] ?? '00:00') ?> &rarr; <?= htmlspecialchars($council['heure_fin'] ?? '00:00') ?></small>
                    </div>
                    <div class="col-md-3 border-end">
                        <small class="text-muted d-block fw-semibold mb-1">Lieu</small>
                        <strong><i class="ph-duotone ph-map-pin me-1"></i><?= htmlspecialchars($council['lieu'] ?? 'Non précisé') ?></strong>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted d-block fw-semibold mb-1">Président & Secrétaire</small>
                        <small class="d-block">Président : <strong><?= htmlspecialchars($council['president_nom'] ?? 'N/A') ?></strong></small>
                        <small class="d-block text-muted">Secrétaire : <?= htmlspecialchars($council['secretaire_nom'] ?? 'Non désigné') ?></small>
                    </div>
                </div>

                <!-- Barre d'Actions / Transitions de Statut pour les Gestionnaires -->
                <?php if ($canManage && $isEditable): ?>
                    <div class="border-top mt-3 pt-3 d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted fs-7 fw-semibold me-2">Actions de Session :</span>
                            <?php if ($council['statut'] === 'planifie'): ?>
                                <form method="POST" action="/discipline/councils/update-status" class="d-inline">
                                    <input type="hidden" name="council_id" value="<?= $council['id'] ?>">
                                    <input type="hidden" name="statut" value="convoque">
                                    <button type="submit" class="btn btn-sm btn-info text-white">
                                        <i class="ph-duotone ph-paper-plane me-1"></i>Émettre Convocations (Convoquer)
                                    </button>
                                </form>
                            <?php elseif ($council['statut'] === 'convoque'): ?>
                                <form method="POST" action="/discipline/councils/update-status" class="d-inline">
                                    <input type="hidden" name="council_id" value="<?= $council['id'] ?>">
                                    <input type="hidden" name="statut" value="en_session">
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class="ph-duotone ph-play me-1"></i>Ouvrir la Séance (En session)
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>

                        <div>
                            <form method="POST" action="/discipline/councils/update-status" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir annuler ce conseil de discipline ?');">
                                <input type="hidden" name="council_id" value="<?= $council['id'] ?>">
                                <input type="hidden" name="statut" value="annule">
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="ph-duotone ph-x-circle me-1"></i>Annuler le Conseil
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row">
            <!-- Colonne 1 : Membres du Conseil -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="ph-duotone ph-users-three me-2 text-info"></i>Membres du Conseil</h5>
                        <?php if ($canManage && $isEditable): ?>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalAddMembre">
                                <i class="ph-duotone ph-plus me-1"></i>Ajouter Membre
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($membres)): ?>
                            <div class="p-4 text-center text-muted">Aucun membre enregistré pour cette session.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Nom / Qualité</th>
                                            <th>Droit de Vote</th>
                                            <th>Présence</th>
                                            <?php if ($canManage && $isEditable): ?><th class="text-end">Action</th><?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($membres as $m): ?>
                                            <tr>
                                                <td>
                                                    <strong class="d-block text-dark"><?= htmlspecialchars($m['nom_snapshot']) ?></strong>
                                                    <small class="text-muted"><?= ucfirst(str_replace('_', ' ', $m['qualite_membre'])) ?> &bull; <?= htmlspecialchars($m['fonction_snapshot'] ?? '') ?></small>
                                                </td>
                                                <td>
                                                    <?php if ($m['a_droit_vote']): ?>
                                                        <span class="badge bg-light-success text-success">Délibératif (Vote)</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-light-secondary text-secondary">Consultatif</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($m['est_present']): ?>
                                                        <span class="badge bg-success">Présent</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Absent</span>
                                                    <?php endif; ?>
                                                </td>
                                                <?php if ($canManage && $isEditable): ?>
                                                    <td class="text-end">
                                                        <?php if ($m['qualite_membre'] !== 'president'): ?>
                                                            <form method="POST" action="/discipline/councils/remove-membre" class="d-inline" onsubmit="return confirm('Retirer ce membre ?');">
                                                                <input type="hidden" name="conseil_id" value="<?= $council['id'] ?>">
                                                                <input type="hidden" name="user_id" value="<?= $m['user_id'] ?>">
                                                                <button type="submit" class="btn btn-sm btn-link text-danger p-0" title="Retirer">
                                                                    <i class="ph-duotone ph-trash fs-5"></i>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Colonne 2 : Élèves Traduits en Conseil -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="ph-duotone ph-student me-2 text-danger"></i>Élèves Convoqués</h5>
                        <?php if ($canManage && $isEditable): ?>
                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalAddEleve">
                                <i class="ph-duotone ph-plus me-1"></i>Convoquer Élève
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($eleves)): ?>
                            <div class="p-4 text-center text-muted">Aucun élève convoqué pour cette session.</div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($eleves as $el): ?>
                                    <div class="list-group-item p-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div>
                                                <strong class="fs-6 text-dark d-block"><?= htmlspecialchars($el['eleve_nom_complet']) ?></strong>
                                                <small class="text-muted">Matricule : <?= htmlspecialchars($el['eleve_matricule']) ?> &bull; Classe : <span class="badge bg-light-primary text-primary"><?= htmlspecialchars($el['nom_classe_snapshot']) ?></span></small>
                                            </div>
                                            <span class="badge bg-light-warning text-warning fw-bold"><?= ucfirst(str_replace('_', ' ', $el['decision_statut'])) ?></span>
                                        </div>

                                        <p class="mb-2 fs-7 text-muted bg-light p-2 rounded">
                                            <strong>Motif Convocation :</strong> <?= htmlspecialchars($el['motif_convocation']) ?>
                                        </p>

                                        <!-- Incidents Rattachés à cet Élève -->
                                        <div class="mb-2">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <small class="fw-semibold text-dark"><i class="ph-duotone ph-warning me-1 text-warning"></i>Incidents Examinés :</small>
                                                <?php if ($canManage && $isEditable): ?>
                                                    <button type="button" class="btn btn-xs btn-outline-secondary p-1 fs-8" data-bs-toggle="modal" data-bs-target="#modalAddIncident" onclick="document.getElementById('modal_eleve_id').value = '<?= $el['eleve_id'] ?>';">
                                                        <i class="ph-duotone ph-link me-1"></i>Rattacher Incident
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                            <?php if (empty($el['incidents'])): ?>
                                                <small class="text-muted italic ms-2">Aucun incident spécifique rattaché.</small>
                                            <?php else: ?>
                                                <ul class="list-unstyled mb-0 ms-2">
                                                    <?php foreach ($el['incidents'] as $inc): ?>
                                                        <li class="fs-8 text-dark mb-1">
                                                            &bull; <strong><?= htmlspecialchars($inc['incident_code']) ?></strong> - <?= htmlspecialchars($inc['type_incident_libelle']) ?> (<?= date('d/m/Y', strtotime($inc['date_incident'])) ?>)
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php endif; ?>
                                        </div>

                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
<!-- [ Main Content ] end -->

<!-- Modals pour Phase 6.1 (Ajout Membre, Élève, Incident) -->
<?php if ($canManage && $isEditable): ?>
    <!-- Modal Ajouter Membre -->
    <div class="modal fade" id="modalAddMembre" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="/discipline/councils/add-membre">
                    <input type="hidden" name="conseil_id" value="<?= $council['id'] ?>">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="ph-duotone ph-user-plus me-2 text-primary"></i>Ajouter un Membre au Conseil</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Membre du Personnel <span class="text-danger">*</span></label>
                            <select name="user_id" class="form-select" required>
                                <option value="">Sélectionner un utilisateur...</option>
                                <?php foreach ($staffUsers as $usr): ?>
                                    <option value="<?= $usr['id_user'] ?>">
                                        <?= htmlspecialchars($usr['prenom'] . ' ' . $usr['nom']) ?> (<?= htmlspecialchars($usr['fonction'] ?? 'Personnel') ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Qualité / Rôle au Conseil</label>
                            <select name="qualite_membre" class="form-select">
                                <option value="membre_permanent">Membre Permanent</option>
                                <option value="representant_enseignant">Représentant Enseignant</option>
                                <option value="representant_parents">Représentant Parents</option>
                                <option value="invite">Invité / Expert</option>
                            </select>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="a_droit_vote" value="1" id="a_droit_vote" checked>
                            <label class="form-check-label fw-semibold" for="a_droit_vote">Membre avec Voix Délibérative (Droit de Vote)</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary btn-sm"><i class="ph-duotone ph-plus me-1"></i>Ajouter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Convoquer Élève -->
    <div class="modal fade" id="modalAddEleve" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="/discipline/councils/add-eleve">
                    <input type="hidden" name="conseil_id" value="<?= $council['id'] ?>">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="ph-duotone ph-student me-2 text-danger"></i>Convoquer un Élève</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Élève à Convoquer <span class="text-danger">*</span></label>
                            <input type="number" name="eleve_id" class="form-control" placeholder="ID de l'élève" required>
                            <small class="text-muted fs-8">La classe active sera résolue automatiquement côté serveur (Snapshot)</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Motif de Convocation <span class="text-danger">*</span></label>
                            <textarea name="motif_convocation" class="form-control" rows="3" placeholder="Description explicite des griefs et faits reprochés..." required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nom du Représentant Légal</label>
                            <input type="text" name="nom_representant_legal" class="form-control" placeholder="ex: M. KOUASSI Pierre">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-danger btn-sm"><i class="ph-duotone ph-paper-plane me-1"></i>Convoquer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Rattacher Incident -->
    <div class="modal fade" id="modalAddIncident" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="/discipline/councils/add-incident">
                    <input type="hidden" name="conseil_id" value="<?= $council['id'] ?>">
                    <input type="hidden" name="eleve_id" id="modal_eleve_id" value="">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="ph-duotone ph-link me-2 text-primary"></i>Rattacher un Incident au Conseil</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Sélectionner l'Incident à Examiner <span class="text-danger">*</span></label>
                            <select name="incident_id" class="form-select" required>
                                <option value="">Sélectionner un incident...</option>
                                <?php foreach ($availableIncidents as $inc): ?>
                                    <option value="<?= $inc['incident_id'] ?>">
                                        <?= htmlspecialchars($inc['incident_code']) ?> - <?= htmlspecialchars($inc['type_incident_libelle']) ?> (<?= date('d/m/Y', strtotime($inc['date_incident'])) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted fs-8">Le serveur vérifiera strictement l'implication réelle de l'élève dans cet incident.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary btn-sm"><i class="ph-duotone ph-link me-1"></i>Rattacher</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../layouts/footer_able.php'; ?>
