<?php
/**
 * Vue : Fiche Détillée d'un Conseil de Discipline (Phase 6.1, 6.2 & 6.4)
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
$activeSanctionTypes = $activeSanctionTypes ?? [];
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
                <?php if ($canManage): ?>
                    <div class="border-top mt-3 pt-3 d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted fs-7 fw-semibold me-2">Actions de Session :</span>
                            <?php if ($council['statut'] === 'planifie'): ?>
                                <form method="POST" action="/discipline/councils/update-status" class="d-inline">
                                    <input type="hidden" name="council_id" value="<?= $council['id'] ?>">
                                    <input type="hidden" name="statut" value="convoque">
                                    <button type="submit" class="btn btn-sm btn-info text-white me-1">
                                        <i class="ph-duotone ph-paper-plane me-1"></i>Émettre Convocations (Convoquer)
                                    </button>
                                </form>
                            <?php elseif ($council['statut'] === 'convoque'): ?>
                                <form method="POST" action="/discipline/councils/update-status" class="d-inline">
                                    <input type="hidden" name="council_id" value="<?= $council['id'] ?>">
                                    <input type="hidden" name="statut" value="en_session">
                                    <button type="submit" class="btn btn-sm btn-primary me-1">
                                        <i class="ph-duotone ph-play me-1"></i>Ouvrir la Séance (En session)
                                    </button>
                                </form>
                            <?php elseif ($council['statut'] === 'en_session'): ?>
                                <form method="POST" action="/discipline/councils/update-status" class="d-inline">
                                    <input type="hidden" name="council_id" value="<?= $council['id'] ?>">
                                    <input type="hidden" name="statut" value="delibere">
                                    <button type="submit" class="btn btn-sm me-1 text-white" style="background-color: #6f42c1;">
                                        <i class="ph-duotone ph-gavel me-1"></i>Passer en Délibération
                                    </button>
                                </form>
                            <?php elseif ($council['statut'] === 'delibere'): ?>
                                <form method="POST" action="/discipline/councils/update-status" class="d-inline" onsubmit="return confirm('Clôturer définitivement la séance ? Aucune modification ultérieure ne sera permise.');">
                                    <input type="hidden" name="council_id" value="<?= $council['id'] ?>">
                                    <input type="hidden" name="statut" value="cloture">
                                    <button type="submit" class="btn btn-sm btn-success me-1">
                                        <i class="ph-duotone ph-lock-key me-1"></i>Clôturer le Conseil
                                    </button>
                                </form>
                            <?php endif; ?>

                            <!-- PV Buttons (Preview & Generate) -->
                            <a href="/discipline/councils/print-pv?id=<?= $council['id'] ?>" target="_blank" class="btn btn-sm btn-outline-dark me-1">
                                <i class="ph-duotone ph-printer me-1"></i>Aperçu / Imprimer PV
                            </a>

                            <?php if ($council['statut'] !== 'cloture' && $council['statut'] !== 'annule'): ?>
                                <form method="POST" action="/discipline/councils/generate-pv" class="d-inline">
                                    <input type="hidden" name="council_id" value="<?= $council['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-success me-1" title="Archiver officiellement le PV">
                                        <i class="ph-duotone ph-file-arrow-up me-1"></i>Archiver PV Officiel
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>

                        <?php if ($isEditable): ?>
                            <div>
                                <form method="POST" action="/discipline/councils/update-status" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir annuler ce conseil de discipline ?');">
                                    <input type="hidden" name="council_id" value="<?= $council['id'] ?>">
                                    <input type="hidden" name="statut" value="annule">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="ph-duotone ph-x-circle me-1"></i>Annuler le Conseil
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
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
                                            <div>
                                                <?php
                                                $st = $el['decision_statut'];
                                                $badgeClass = match($st) {
                                                    'sanctionne' => 'bg-light-danger text-danger',
                                                    'relaxe' => 'bg-light-success text-success',
                                                    'averti' => 'bg-light-warning text-warning',
                                                    'reoriente' => 'bg-light-info text-info',
                                                    default => 'bg-light-secondary text-secondary'
                                                };
                                                ?>
                                                <span class="badge <?= $badgeClass ?> fw-bold fs-7"><?= ucfirst(str_replace('_', ' ', $st)) ?></span>
                                            </div>
                                        </div>

                                        <p class="mb-2 fs-7 text-muted bg-light p-2 rounded">
                                            <strong>Motif Convocation :</strong> <?= htmlspecialchars($el['motif_convocation']) ?>
                                        </p>

                                        <!-- Détails de la Délibération & Votes si décision saisie -->
                                        <?php if ($el['decision_statut'] !== 'en_attente'): ?>
                                            <div class="p-2 mb-2 rounded border bg-white fs-8">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span class="fw-bold text-dark"><i class="ph-duotone ph-check-square me-1 text-primary"></i>Délibération du Conseil :</span>
                                                    <small class="text-muted">Votes: <strong class="text-success"><?= (int)$el['votes_pour'] ?> Pour</strong> / <strong class="text-danger"><?= (int)$el['votes_contre'] ?> Contre</strong> / <strong><?= (int)$el['abstentions'] ?> Abs.</strong></small>
                                                </div>
                                                <div class="text-muted italic mb-1">
                                                    <strong>Motivation :</strong> <?= htmlspecialchars($el['motivation_decision'] ?? 'Non renseignée') ?>
                                                </div>
                                                <?php if (!empty($el['sanction_id'])): ?>
                                                    <div class="mt-1">
                                                        <a href="/discipline/sanctions/show?id=<?= $el['sanction_id'] ?>" class="btn btn-xs btn-outline-danger py-0 px-2" target="_blank">
                                                            <i class="ph-duotone ph-warning-circle me-1"></i>Voir Sanction Prononcée #<?= $el['sanction_id'] ?>
                                                        </a>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($canManageDecisions) && $council['statut'] === 'delibere'): ?>
                                            <div class="mt-2 text-end">
                                                <button type="button" class="btn btn-xs btn-primary" data-bs-toggle="modal" data-bs-target="#modalRecordDecision_<?= $el['eleve_id'] ?>">
                                                    <i class="ph-duotone ph-gavel me-1"></i>Enregistrer Délibération / Décision
                                                </button>
                                            </div>
                                        <?php endif; ?>

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
                            <label class="form-label fw-semibold">Élève éligible à Convoquer <span class="text-danger">*</span></label>
                            <?php if (!empty($eligibleEleves)): ?>
                                <select name="eleve_id" class="form-select" required>
                                    <option value="">Sélectionner un élève éligible...</option>
                                    <?php foreach ($eligibleEleves as $elElg): ?>
                                        <option value="<?= $elElg['id_eleve'] ?>">
                                            <?= htmlspecialchars($elElg['nom'] . ' ' . $elElg['prenom']) ?> &mdash; Matricule : <?= htmlspecialchars($elElg['identifiant_public']) ?> &mdash; Classe : <?= htmlspecialchars($elElg['nom_classe']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted fs-8 d-block mt-1">Sont affichés uniquement les élèves ayant au moins un incident signalé ou une sanction active (prononcée/en cours), non encore convoqués à ce conseil.</small>
                            <?php else: ?>
                                <div class="alert alert-info py-2 px-3 mb-0 fs-8">
                                    <i class="ph-duotone ph-info me-1 fs-6"></i>
                                    Aucun élève éligible disponible à la convocation pour ce conseil (aucun incident/sanction active en cours ou tous les élèves éligibles sont déjà convoqués).
                                </div>
                            <?php endif; ?>
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

    <!-- Modals Prise de Décision / Délibération pour chaque élève -->
    <?php if (!empty($canManageDecisions) && $council['statut'] === 'delibere'): ?>
        <?php foreach ($eleves as $el): ?>
            <div class="modal fade" id="modalRecordDecision_<?= $el['eleve_id'] ?>" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <form method="POST" action="/discipline/councils/record-decision">
                            <input type="hidden" name="council_id" value="<?= $council['id'] ?>">
                            <input type="hidden" name="eleve_id" value="<?= $el['eleve_id'] ?>">

                            <div class="modal-header bg-light">
                                <h5 class="modal-title"><i class="ph-duotone ph-gavel me-2 text-primary"></i>Délibération & Décision pour <?= htmlspecialchars($el['eleve_nom_complet']) ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>

                            <div class="modal-body">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Décision du Conseil <span class="text-danger">*</span></label>
                                        <select name="decision_statut" class="form-select" id="select_decision_<?= $el['eleve_id'] ?>" onchange="toggleSanctionFields(<?= $el['eleve_id'] ?>)" required>
                                            <option value="en_attente" <?= $el['decision_statut'] === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                                            <option value="relaxe" <?= $el['decision_statut'] === 'relaxe' ? 'selected' : '' ?>>Relaxé / Non lieu</option>
                                            <option value="averti" <?= $el['decision_statut'] === 'averti' ? 'selected' : '' ?>>Avertissement / Mise en garde</option>
                                            <option value="reoriente" <?= $el['decision_statut'] === 'reoriente' ? 'selected' : '' ?>>Réorientation</option>
                                            <option value="sanctionne" <?= $el['decision_statut'] === 'sanctionne' ? 'selected' : '' ?>>Sanctionné (Prononcer une sanction)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Décompte des Votes du Conseil</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light-success text-success">Pour</span>
                                            <input type="number" name="votes_pour" class="form-control" value="<?= (int)$el['votes_pour'] ?>" min="0" required>
                                            <span class="input-group-text bg-light-danger text-danger">Contre</span>
                                            <input type="number" name="votes_contre" class="form-control" value="<?= (int)$el['votes_contre'] ?>" min="0" required>
                                            <span class="input-group-text bg-light">Abs.</span>
                                            <input type="number" name="abstentions" class="form-control" value="<?= (int)$el['abstentions'] ?>" min="0" required>
                                        </div>
                                    </div>
                                </div>

                                <!-- Dynamic Sanction Type Selection (Visible only if decision_statut === 'sanctionne') -->
                                <div id="sanctionFields_<?= $el['eleve_id'] ?>" class="border rounded p-3 mb-3 bg-light-danger" style="display: <?= $el['decision_statut'] === 'sanctionne' ? 'block' : 'none' ?>;">
                                    <h6 class="fw-bold text-danger mb-2"><i class="ph-duotone ph-warning-circle me-1"></i>Configuration de la Sanction à Prononcer</h6>

                                    <div class="row">
                                        <div class="col-md-6 mb-2">
                                            <label class="form-label fw-semibold">Type de Sanction Officiel <span class="text-danger">*</span></label>
                                            <select name="type_sanction_id" class="form-select">
                                                <option value="">Sélectionner un type de sanction...</option>
                                                <?php foreach ($activeSanctionTypes as $st): ?>
                                                    <option value="<?= $st['id'] ?>">
                                                        <?= htmlspecialchars($st['code']) ?> - <?= htmlspecialchars($st['libelle']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <label class="form-label fw-semibold">Durée (Jours)</label>
                                            <input type="number" name="duree_jours" class="form-control" placeholder="ex: 3" min="0">
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <label class="form-label fw-semibold">Durée (Heures)</label>
                                            <input type="number" name="duree_heures" class="form-control" placeholder="ex: 12" min="0">
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Motivation / Considérants et Justification de la décision <span class="text-danger">*</span></label>
                                    <textarea name="motivation_decision" class="form-control" rows="3" placeholder="Insérer les considérants de droit et de fait motivant la décision finale du Conseil..." required><?= htmlspecialchars($el['motivation_decision'] ?? '') ?></textarea>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Annuler</button>
                                <button type="submit" class="btn btn-primary btn-sm"><i class="ph-duotone ph-floppy-disk me-1"></i>Enregistrer Délibération</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <script>
            function toggleSanctionFields(eleveId) {
                var select = document.getElementById('select_decision_' + eleveId);
                var container = document.getElementById('sanctionFields_' + eleveId);
                if (select && container) {
                    if (select.value === 'sanctionne') {
                        container.style.display = 'block';
                    } else {
                        container.style.display = 'none';
                    }
                }
            }
        </script>
    <?php endif; ?>

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
