<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <!-- Page Header -->
        <div class="page-header d-print-none">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="page-header-title">
                            <h2 class="mb-0">
                                <?= htmlspecialchars($p['prenom'] . ' ' . $p['nom']) ?>
                                <span class="badge bg-light-primary text-primary ms-2"><?= htmlspecialchars($p['identifiant_public'] ?? 'N/A') ?></span>
                            </h2>
                            <p class="text-muted mb-0"><?= htmlspecialchars($p['fonction'] ?? _('Personnel')) ?> &bull; <?= htmlspecialchars($p['nom_lycee'] ?? '') ?></p>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <?php if (Auth::can('edit', 'drh')): ?>
                        <a href="/drh/edit?id=<?= $p['id_user'] ?>" class="btn btn-warning me-1">
                            <i class="ph-duotone ph-pencil me-1"> <?= _('Modifier Fiche') ?>
                        </a>
                        <?php endif; ?>

                        <?php if (Auth::can('manage_statut', 'drh')): ?>
                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#statutModal">
                            <i class="ph-duotone ph-gear me-1"> <?= _('Changer Statut RH') ?>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= htmlspecialchars($_SESSION['success_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= htmlspecialchars($_SESSION['error_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <!-- 360 Navigation Tabs -->
        <ul class="nav nav-tabs mb-4" id="drhTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" id="tab-profil-tab" data-bs-toggle="tab" data-bs-target="#tab-profil" type="button">
                    <i class="ph-duotone ph-user me-1"> <?= _('Identité & Profil') ?>
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="tab-affectations-tab" data-bs-toggle="tab" data-bs-target="#tab-affectations" type="button">
                    <i class="ph-duotone ph-buildings me-1"> <?= _('Affectations & Cycles') ?> (<?= count($assignments) ?>)
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="tab-contrats-tab" data-bs-toggle="tab" data-bs-target="#tab-contrats" type="button">
                    <i class="ph-duotone ph-file-text me-1"> <?= _('Contrats & Rémunération') ?> (<?= count($contracts) ?>)
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="tab-documents-tab" data-bs-toggle="tab" data-bs-target="#tab-documents" type="button">
                    <i class="ph-duotone ph-folder me-1"> <?= _('Pièces Jointes') ?> (<?= count($documents) ?>)
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="tab-historique-tab" data-bs-toggle="tab" data-bs-target="#tab-historique" type="button">
                    <i class="ph-duotone ph-clock-counter-clockwise me-1"> <?= _('Historique RH & Audits') ?> (<?= count($history) ?>)
                </button>
            </li>
        </ul>

        <div class="tab-content" id="drhTabsContent">
            <!-- TAB 1: PROFIL & IDENTITE -->
            <div class="tab-pane fade show active" id="tab-profil" role="tabpanel">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <div class="mb-3">
                                    <img src="<?= !empty($p['photo']) ? htmlspecialchars($p['photo']) : '/assets/img/default-avatar.png' ?>" class="rounded-circle img-thumbnail" style="width:120px; height:120px; object-fit:cover;" alt="Avatar">
                                </div>
                                <h4><?= htmlspecialchars($p['prenom'] . ' ' . $p['nom']) ?></h4>
                                <p class="text-muted mb-2"><?= htmlspecialchars($p['fonction'] ?? '—') ?></p>

                                <?php
                                $st = $p['statut_rh'] ?? 'en_activite';
                                $b = match($st) {
                                    'en_activite' => 'bg-success',
                                    'en_conge' => 'bg-warning',
                                    'suspendu' => 'bg-danger',
                                    default => 'bg-secondary'
                                };
                                ?>
                                <span class="badge <?= $b ?> px-3 py-2 fs-6 mb-3"><?= strtoupper(htmlspecialchars($st)) ?></span>

                                <hr>

                                <div class="text-start">
                                    <p class="mb-1"><strong><?= _('Matricule') ?> :</strong> <?= htmlspecialchars($p['identifiant_public'] ?? 'N/A') ?></p>
                                    <p class="mb-1"><strong><?= _('Rôle RBAC') ?> :</strong> <?= htmlspecialchars($p['nom_role'] ?? 'N/A') ?></p>
                                    <p class="mb-1"><strong><?= _('Établissement') ?> :</strong> <?= htmlspecialchars($p['nom_lycee'] ?? 'N/A') ?></p>
                                    <p class="mb-0"><strong><?= _('Compte Actif') ?> :</strong> <?= $p['actif'] ? '<span class="text-success fw-bold">'._('Oui').'</span>' : '<span class="text-danger fw-bold">'._('Non').'</span>' ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><?= _('Informations Générales & Contact') ?></h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="text-muted d-block"><?= _('Sexe') ?></label>
                                        <strong><?= htmlspecialchars($p['sexe'] ?? 'Non renseigné') ?></strong>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="text-muted d-block"><?= _('Date & Lieu de Naissance') ?></label>
                                        <strong><?= !empty($p['date_naissance']) ? date('d/m/Y', strtotime($p['date_naissance'])) : '—' ?> <?= !empty($p['lieu_naissance']) ? 'à ' . htmlspecialchars($p['lieu_naissance']) : '' ?></strong>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="text-muted d-block"><?= _('Adresse Email') ?></label>
                                        <strong><?= htmlspecialchars($p['email']) ?></strong>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="text-muted d-block"><?= _('Numéro Téléphone') ?></label>
                                        <strong><?= htmlspecialchars($p['telephone'] ?? '—') ?></strong>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="text-muted d-block"><?= _('Adresse Domicile') ?></label>
                                        <strong><?= htmlspecialchars($p['adresse'] ?? '—') ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if ($can_view_sensitive): ?>
                        <div class="card border-warning">
                            <div class="card-header bg-light-warning">
                                <h5 class="mb-0 text-warning"><?= _('Données Confidentielles (Accès Restreint DRH)') ?></h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="text-muted d-block"><?= _('N° Sécurité Sociale (CNSS)') ?></label>
                                        <strong class="font-monospace"><?= htmlspecialchars($p['num_cnss'] ?? 'Non renseigné') ?></strong>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="text-muted d-block"><?= _('Situation Matrimoniale') ?></label>
                                        <strong><?= ucfirst(htmlspecialchars($p['situation_matrimoniale'] ?? 'celibataire')) ?></strong>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="text-muted d-block"><?= _('Nombre d\'Enfants') ?></label>
                                        <strong><?= (int)($p['nombre_enfants'] ?? 0) ?></strong>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="text-muted d-block"><?= _('Date de Sortie / Départ') ?></label>
                                        <strong><?= !empty($p['date_sortie']) ? date('d/m/Y', strtotime($p['date_sortie'])) : 'En poste' ?></strong>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="text-muted d-block"><?= _('Motif de Sortie / Remarques') ?></label>
                                        <strong><?= htmlspecialchars($p['motif_sortie'] ?? $p['remarques'] ?? 'Aucun') ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- TAB 2: AFFECTATIONS & SCOPE -->
            <div class="tab-pane fade" id="tab-affectations" role="tabpanel">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><?= _('Affectations de Cycles (Phase 10 Scope Isolation)') ?></h5>
                        <?php if (Auth::can('manage_affectations', 'drh')): ?>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newAssignmentModal">
                            <i class="ph-duotone ph-plus me-1"> <?= _('Nouvelle Affectation') ?>
                        </button>
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th><?= _('Cycle') ?></th>
                                        <th><?= _('Date Début') ?></th>
                                        <th><?= _('Date Fin') ?></th>
                                        <th><?= _('Statut Temporel') ?></th>
                                        <th><?= _('Etat') ?></th>
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
                                        <td><strong><?= htmlspecialchars($as['nom_cycle']) ?></strong> (<?= htmlspecialchars($as['niveau_debut'] . ' → ' . $as['niveau_fin']) ?>)</td>
                                        <td><?= date('d/m/Y', strtotime($as['date_debut'])) ?></td>
                                        <td><?= !empty($as['date_fin']) ? date('d/m/Y', strtotime($as['date_fin'])) : 'Indéterminée' ?></td>
                                        <td>
                                            <?php if ($isCurrent): ?>
                                                <span class="badge bg-success"><?= _('Actuelle (Accès Valide)') ?></span>
                                            <?php elseif ($isFuture): ?>
                                                <span class="badge bg-info"><?= _('Future (Planifiée)') ?></span>
                                            <?php elseif ($isExpired): ?>
                                                <span class="badge bg-secondary"><?= _('Expirée') ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-warning"><?= _('Désactivée') ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= $as['actif'] ? '<span class="text-success fw-bold">'._('Actif').'</span>' : '<span class="text-muted">'._('Inactif').'</span>' ?>
                                        </td>
                                        <td class="text-end">
                                            <?php if (Auth::can('manage_affectations', 'drh')): ?>
                                            <form method="POST" action="/drh/assignments/delete" style="display:inline;" onsubmit="return confirm('<?= _('Retirer cette affectation ? Les accès de l\'utilisateur seront réactualisés.') ?>');">
                                                <input type="hidden" name="id" value="<?= $as['id'] ?>">
                                                <input type="hidden" name="personnel_id" value="<?= $p['id_user'] ?>">
                                                <button type="submit" class="btn btn-sm btn-icon btn-light-danger" title="<?= _('Retirer') ?>">
                                                    <i class="ph-duotone ph-trash">
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>

                                    <?php if (empty($assignments)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
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

            <!-- TAB 3: CONTRATS & REMUNERATION -->
            <div class="tab-pane fade" id="tab-contrats" role="tabpanel">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><?= _('Historique des Contrats Administratifs') ?></h5>
                        <?php if (Auth::can('manage_contrats', 'drh')): ?>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newContractModal">
                            <i class="ph-duotone ph-plus me-1"> <?= _('Nouveau Contrat') ?>
                        </button>
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th><?= _('Type de Contrat') ?></th>
                                        <th><?= _('Date Début') ?></th>
                                        <th><?= _('Date Fin') ?></th>
                                        <?php if ($can_view_sensitive): ?>
                                        <th><?= _('Salaire de Base') ?></th>
                                        <?php endif; ?>
                                        <th><?= _('Mode Paiement') ?></th>
                                        <th><?= _('Statut Contrat') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($contracts as $c): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($c['contrat_libelle']) ?></strong></td>
                                        <td><?= date('d/m/Y', strtotime($c['date_debut'])) ?></td>
                                        <td><?= !empty($c['date_fin']) ? date('d/m/Y', strtotime($c['date_fin'])) : 'CDI / Indéterminé' ?></td>
                                        <?php if ($can_view_sensitive): ?>
                                        <td class="fw-bold font-monospace text-primary">
                                            <?= number_format($c['salaire_base'] ?? 0, 2) ?> <?= htmlspecialchars($c['devise'] ?? 'XAF') ?>
                                        </td>
                                        <?php endif; ?>
                                        <td><span class="badge bg-light-secondary text-dark"><?= htmlspecialchars($c['type_paiement'] ?? 'fixe') ?></span></td>
                                        <td>
                                            <?php
                                            $stC = $c['statut_contrat'] ?? 'actif';
                                            $bC = match($stC) {
                                                'actif' => 'bg-success',
                                                'renouvele' => 'bg-info',
                                                'termine' => 'bg-secondary',
                                                default => 'bg-warning'
                                            };
                                            ?>
                                            <span class="badge <?= $bC ?>"><?= strtoupper(htmlspecialchars($stC)) ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>

                                    <?php if (empty($contracts)): ?>
                                    <tr>
                                        <td colspan="<?= $can_view_sensitive ? 6 : 5 ?>" class="text-center py-4 text-muted">
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

            <!-- TAB 4: DOCUMENTS -->
            <div class="tab-pane fade" id="tab-documents" role="tabpanel">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><?= _('Coffre-fort Documentaire DRH') ?></h5>
                        <?php if (Auth::can('manage_documents', 'drh')): ?>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#uploadDocModal">
                            <i class="ph-duotone ph-upload me-1"> <?= _('Téléverser Document') ?>
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
                                        <td><strong><?= htmlspecialchars($doc['type_document']) ?></strong></td>
                                        <td><?= htmlspecialchars($doc['nom_fichier']) ?></td>
                                        <td><span class="badge bg-light-info text-info">v<?= (int)$doc['version'] ?></span></td>
                                        <td>
                                            <?= $doc['confidentiel'] ? '<span class="badge bg-danger">'._('Confidentiel').'</span>' : '<span class="badge bg-secondary">'._('Public').'</span>' ?>
                                        </td>
                                        <td><?= date('d/m/Y H:i', strtotime($doc['date_upload'])) ?></td>
                                        <td class="text-end">
                                            <a href="/drh/documents/download?id=<?= $doc['id'] ?>" class="btn btn-sm btn-icon btn-light-primary" target="_blank" title="<?= _('Télécharger / Consulter') ?>">
                                                <i class="ph-duotone ph-download">
                                            </a>
                                            <?php if (Auth::can('manage_documents', 'drh')): ?>
                                            <form method="POST" action="/drh/documents/delete" style="display:inline;" onsubmit="return confirm('<?= _('Supprimer définitivement ce document ?') ?>');">
                                                <input type="hidden" name="id" value="<?= $doc['id'] ?>">
                                                <input type="hidden" name="personnel_id" value="<?= $p['id_user'] ?>">
                                                <button type="submit" class="btn btn-sm btn-icon btn-light-danger ms-1" title="<?= _('Supprimer') ?>">
                                                    <i class="ph-duotone ph-trash">
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>

                                    <?php if (empty($documents)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
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

            <!-- TAB 5: HISTORIQUE RH & AUDITS -->
            <div class="tab-pane fade" id="tab-historique" role="tabpanel">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><?= _('Registre Immuable des Mouvements RH & Audits') ?></h5>
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
                                        <td class="text-nowrap"><?= date('d/m/Y H:i', strtotime($h['date_mouvement'])) ?></td>
                                        <td><span class="badge bg-light-primary text-primary"><?= htmlspecialchars($h['type_mouvement']) ?></span></td>
                                        <td><?= htmlspecialchars($h['motif'] ?? '—') ?></td>
                                        <td><?= htmlspecialchars($h['auteur_nom'] ?? 'Système') ?></td>
                                    </tr>
                                    <?php endforeach; ?>

                                    <?php if (empty($history)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">
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
                    <h5 class="modal-title"><?= _('Changer le Statut RH') ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label"><?= _('Nouveau Statut RH') ?></label>
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
                        <label class="form-label"><?= _('Motif explicite de la décision') ?></label>
                        <textarea name="motif_sortie" class="form-control" rows="3" required placeholder="<?= _('Saisir le motif officiel...') ?>"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= _('Annuler') ?></button>
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
                    <h5 class="modal-title"><?= _('Nouvelle Affectation de Cycle') ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label"><?= _('Cycle concerné') ?></label>
                        <select name="cycle_id" class="form-select" required>
                            <?php foreach ($cycles as $cy): ?>
                                <option value="<?= $cy['id_cycle'] ?>"><?= htmlspecialchars($cy['nom_cycle']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label"><?= _('Date Début') ?></label>
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
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= _('Annuler') ?></button>
                    <button type="submit" class="btn btn-primary"><?= _('Valider Affectation') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 3: CONTRAT -->
<div class="modal fade" id="newContractModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/drh/contracts/store">
                <input type="hidden" name="personnel_id" value="<?= $p['id_user'] ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><?= _('Nouveau Contrat Administratif') ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label"><?= _('Type de Contrat') ?></label>
                        <select name="type_contrat_id" class="form-select" required>
                            <?php foreach ($typeContrats as $tc): ?>
                                <option value="<?= $tc['id_contrat'] ?>"><?= htmlspecialchars($tc['libelle']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label"><?= _('Date Début') ?></label>
                            <input type="date" name="date_debut" class="form-control" required value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-6">
                            <label class="form-label"><?= _('Date Fin') ?></label>
                            <input type="date" name="date_fin" class="form-control">
                        </div>
                    </div>
                    <?php if ($can_view_sensitive): ?>
                    <div class="mb-3">
                        <label class="form-label"><?= _('Salaire de Base Contractuel') ?></label>
                        <input type="number" step="0.01" name="salaire_base" class="form-control" placeholder="0.00">
                    </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label"><?= _('Commentaires / Précisions') ?></label>
                        <textarea name="commentaire" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= _('Annuler') ?></button>
                    <button type="submit" class="btn btn-primary"><?= _('Enregistrer Contrat') ?></button>
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
                    <h5 class="modal-title"><?= _('Téléverser un Document') ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label"><?= _('Type de Document') ?></label>
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
                        <label class="form-label"><?= _('Fichier à Téléverser (PDF, PNG, JPG, DOC)') ?></label>
                        <input type="file" name="document_file" class="form-control" required>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="confidentiel" value="1" id="confCheck" checked>
                        <label class="form-check-label" for="confCheck"><?= _('Document confidentiel (Restreint aux autorisations DRH sensibles)') ?></label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= _('Annuler') ?></button>
                    <button type="submit" class="btn btn-primary"><?= _('Téléverser') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
