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

<?php
require_once __DIR__ . '/../../../models/DisciplineDocument.php';
require_once __DIR__ . '/../../../models/DisciplineNotification.php';
require_once __DIR__ . '/../../../models/DisciplineHistorique.php';

$documents = DisciplineDocument::findByTarget('sanction', $sanction['id']);
$notifications = DisciplineNotification::findByTarget('sanction', $sanction['id']);
$historique = DisciplineHistorique::findByTarget('sanction', $sanction['id']);

$canManageDocs = Auth::can('manage_documents', 'discipline');
$canManageNotifs = Auth::can('manage_notifications', 'discipline');
$canViewHistory = Auth::can('view_history', 'discipline');
?>

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

                        <?php if (!empty($sanction['motif_levee_annulation'])): ?>
                            <div class="border-top pt-3 mt-3">
                                <label class="text-muted small fw-bold text-danger"><?= _("Motif de la levée / annulation") ?></label>
                                <div class="p-3 bg-light-danger text-danger rounded mt-1">
                                    <?= htmlspecialchars($sanction['motif_levee_annulation']) ?>
                                    <?php if (!empty($sanction['date_levee_annulation'])): ?>
                                        <div class="small text-muted mt-1"><?= _("Enregistré le") ?> <?= date('d/m/Y H:i', strtotime($sanction['date_levee_annulation'])) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>

                <!-- Pièces Jointes & Documents -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="ph-duotone ph-paperclip me-2 text-primary"></i><?= _("Pièces Jointes & Documents") ?> (<?= count($documents) ?>)</h5>
                        <?php if ($canManageDocs): ?>
                            <button class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#modalUploadDocSanction">
                                <i class="ph-duotone ph-upload-simple"></i><?= _("Ajouter un document") ?>
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($documents)): ?>
                            <div class="p-3 text-center text-muted small">
                                <?= _("Aucun document joint à cette sanction.") ?>
                            </div>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($documents as $doc): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center p-3">
                                        <div>
                                            <div class="fw-bold"><i class="ph-duotone ph-file-pdf me-2 text-danger"></i><?= htmlspecialchars($doc['nom_original']) ?></div>
                                            <span class="text-muted small"><?= round($doc['taille'] / 1024, 1) ?> Ko • <?= date('d/m/Y H:i', strtotime($doc['created_at'])) ?> par <?= htmlspecialchars($doc['uploader_prenom'] . ' ' . $doc['uploader_nom']) ?></span>
                                        </div>
                                        <div>
                                            <a href="/discipline/documents/download?id=<?= $doc['id'] ?>" class="btn btn-sm btn-light-primary me-1" title="<?= _("Télécharger") ?>">
                                                <i class="ph-duotone ph-download-simple"></i>
                                            </a>
                                            <?php if ($canManageDocs): ?>
                                                <a href="/discipline/documents/delete?id=<?= $doc['id'] ?>" class="btn btn-sm btn-light-danger" onclick="return confirm('<?= _('Supprimer cette pièce jointe ?') ?>')" title="<?= _("Supprimer") ?>">
                                                    <i class="ph-duotone ph-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Notifications aux Parents -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="ph-duotone ph-bell-ringing me-2 text-primary"></i><?= _("Suivi des Notifications Parents") ?> (<?= count($notifications) ?>)</h5>
                        <?php if ($canManageNotifs): ?>
                            <button class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#modalNotifParentSanction">
                                <i class="ph-duotone ph-plus"></i><?= _("Consigner notification") ?>
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($notifications)): ?>
                            <div class="p-3 text-center text-muted small">
                                <?= _("Aucune notification parent enregistrée pour cette sanction.") ?>
                            </div>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($notifications as $n): ?>
                                    <li class="list-group-item p-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <div class="fw-bold"><?= htmlspecialchars($n['objet']) ?></div>
                                            <span class="badge bg-light-info text-info"><?= htmlspecialchars($n['mode_notification']) ?></span>
                                        </div>
                                        <div class="small text-muted mb-1">
                                            <strong><?= _("Destinataire :") ?></strong> <?= htmlspecialchars($n['destinataire_nom']) ?> (<?= htmlspecialchars($n['destinataire_contact'] ?? '-') ?>) |
                                            <strong><?= _("Transmise le :") ?></strong> <?= date('d/m/Y H:i', strtotime($n['date_envoi'])) ?>
                                        </div>
                                        <?php if (!empty($n['message'])): ?>
                                            <div class="small text-secondary bg-light p-2 rounded mt-1"><?= htmlspecialchars($n['message']) ?></div>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Journal d'Audit Immuable -->
                <?php if ($canViewHistory): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="ph-duotone ph-clock-counter-clockwise me-2 text-primary"></i><?= _("Journal d'Audit Immuable") ?></h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($historique)): ?>
                                <div class="p-3 text-center text-muted small">
                                    <?= _("Aucun événement d'historique enregistré.") ?>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th><?= _("Date / Heure") ?></th>
                                                <th><?= _("Action") ?></th>
                                                <th><?= _("Auteur") ?></th>
                                                <th><?= _("Détails") ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($historique as $h): ?>
                                                <tr>
                                                    <td class="small text-muted"><?= date('d/m/Y H:i', strtotime($h['created_at'])) ?></td>
                                                    <td><span class="badge bg-light-secondary text-dark"><?= htmlspecialchars($h['action']) ?></span></td>
                                                    <td class="small"><?= htmlspecialchars($h['user_prenom'] . ' ' . $h['user_nom']) ?></td>
                                                    <td class="small text-wrap"><?= htmlspecialchars($h['description']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

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
                                    <button type="button" class="btn btn-info w-100 d-inline-flex align-items-center justify-content-center gap-2" data-bs-toggle="modal" data-bs-target="#modalLeverSanction">
                                        <i class="ph-duotone ph-arrow-up-right fs-5"></i><?= _("Lever la sanction (Anticipé)") ?>
                                    </button>
                                <?php endif; ?>

                                <?php if (in_array($sanction['statut'], ['prononcee', 'en_cours'], true)): ?>
                                    <button type="button" class="btn btn-outline-secondary w-100 d-inline-flex align-items-center justify-content-center gap-2" data-bs-toggle="modal" data-bs-target="#modalAnnulerSanction">
                                        <i class="ph-duotone ph-x-circle fs-5"></i><?= _("Annuler la sanction") ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            </div>

        </div>

    </div>
</div>

<?php if ($canManageDocs): ?>
<!-- MODAL UPLOAD DOC -->
<div class="modal fade" id="modalUploadDocSanction" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="/discipline/documents/upload" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="sanction_id" value="<?= $sanction['id'] ?>">
                <input type="hidden" name="eleve_id" value="<?= $sanction['eleve_id'] ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ph-duotone ph-paperclip me-2 text-primary"></i><?= _("Ajouter une pièce jointe à la sanction") ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="documentFileSanc" class="form-label required-field"><?= _("Sélectionner le document") ?></label>
                        <input type="file" name="document" id="documentFileSanc" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                        <div class="form-text text-muted"><?= _("Formats acceptés : PDF, DOC, DOCX, JPG, PNG (Max: 10 Mo).") ?></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link text-muted" data-bs-dismiss="modal"><?= _("Annuler") ?></button>
                    <button type="submit" class="btn btn-primary"><?= _("Téléverser") ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($canManageNotifs): ?>
<!-- MODAL NOTIF PARENT -->
<div class="modal fade" id="modalNotifParentSanction" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="/discipline/notifications/store" method="POST">
                <input type="hidden" name="sanction_id" value="<?= $sanction['id'] ?>">
                <input type="hidden" name="eleve_id" value="<?= $sanction['eleve_id'] ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ph-duotone ph-bell-ringing me-2 text-primary"></i><?= _("Consigner une notification parent") ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="destinataire_nom_sanc" class="form-label required-field"><?= _("Nom du responsable / parent") ?></label>
                        <input type="text" name="destinataire_nom" id="destinataire_nom_sanc" class="form-control" placeholder="<?= _('Ex: M. DUPONT Pierre') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="destinataire_contact_sanc" class="form-label"><?= _("Contact / Téléphone / Email") ?></label>
                        <input type="text" name="destinataire_contact" id="destinataire_contact_sanc" class="form-control" placeholder="<?= _('Ex: +236 75 00 00 00') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="mode_notification_sanc" class="form-label required-field"><?= _("Mode de transmission") ?></label>
                        <select name="mode_notification" id="mode_notification_sanc" class="form-select" required>
                            <option value="main_propre"><?= _("Remise en main propre contre décharge") ?></option>
                            <option value="courrier_decharge"><?= _("Courrier d'information") ?></option>
                            <option value="appel_telephonique"><?= _("Appel téléphonique") ?></option>
                            <option value="email"><?= _("Email d'information") ?></option>
                            <option value="sms"><?= _("SMS d'alerte") ?></option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="notif_objet_sanc" class="form-label required-field"><?= _("Objet de la notification") ?></label>
                        <input type="text" name="objet" id="notif_objet_sanc" class="form-control" value="<?= htmlspecialchars("Notification de la sanction : " . $sanction['type_libelle']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="notif_message_sanc" class="form-label"><?= _("Message / Résumé") ?></label>
                        <textarea name="message" id="notif_message_sanc" rows="3" class="form-control" placeholder="<?= _('Résumé de la décision transmise au responsable...') ?>"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link text-muted" data-bs-dismiss="modal"><?= _("Annuler") ?></button>
                    <button type="submit" class="btn btn-primary"><?= _("Consigner") ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($canManage && $sanction['statut'] === 'en_cours'): ?>
<!-- MODAL LEVER SANCTION -->
<div class="modal fade" id="modalLeverSanction" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="/discipline/sanctions/update-status" method="POST">
                <input type="hidden" name="id" value="<?= $sanction['id'] ?>">
                <input type="hidden" name="statut" value="levee">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ph-duotone ph-arrow-up-right me-2 text-info"></i><?= _("Lever la Sanction (Anticipé)") ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 small">
                        <?= _("Vous êtes sur le point de lever prématurément cette sanction pour l'élève.") ?>
                    </div>
                    <div class="mb-3">
                        <label for="motif_levee" class="form-label required-field"><?= _("Motif de la levée (Obligatoire)") ?></label>
                        <textarea name="motif_levee_annulation" id="motif_levee" rows="3" class="form-control" placeholder="<?= _('Ex: Conduite exemplaire, travaux d\'intérêt exécutés satisfaisants...') ?>" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link text-muted" data-bs-dismiss="modal"><?= _("Annuler") ?></button>
                    <button type="submit" class="btn btn-info"><?= _("Confirmer la levée") ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($canManage && in_array($sanction['statut'], ['prononcee', 'en_cours'], true)): ?>
<!-- MODAL ANNULER SANCTION -->
<div class="modal fade" id="modalAnnulerSanction" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="/discipline/sanctions/update-status" method="POST">
                <input type="hidden" name="id" value="<?= $sanction['id'] ?>">
                <input type="hidden" name="statut" value="annulee">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ph-duotone ph-x-circle me-2 text-danger"></i><?= _("Annuler la Sanction") ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning py-2 small">
                        <?= _("L'annulation effacera l'effet de cette sanction de la fiche active de l'élève.") ?>
                    </div>
                    <div class="mb-3">
                        <label for="motif_annulation" class="form-label required-field"><?= _("Motif de l'annulation (Obligatoire)") ?></label>
                        <textarea name="motif_levee_annulation" id="motif_annulation" rows="3" class="form-control" placeholder="<?= _('Ex: Erreur matérielle, révision en conseil de discipline...') ?>" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link text-muted" data-bs-dismiss="modal"><?= _("Annuler") ?></button>
                    <button type="submit" class="btn btn-danger"><?= _("Confirmer l'annulation") ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../layouts/footer_able.php'; ?>