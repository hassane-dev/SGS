<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-lg">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fs-2 fw-bold"><?= _('Détail du membre du personnel') ?></h2>
        <a href="/users" class="btn btn-secondary"><?= _('Retour à la liste') ?></a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <div class="row g-4">
                <!-- Left side with Photo -->
                <div class="col-md-4 text-center">
                    <img src="<?= htmlspecialchars($user['photo'] ?? '/img/default-avatar.png') ?>" alt="Photo de profil" class="img-fluid rounded-circle border mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                    <h3 class="h4 fw-bold"><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></h3>
                    <p class="text-muted"><?= htmlspecialchars($user['fonction'] ?? 'Fonction non spécifiée') ?></p>
                    <div>
                        <?php if ($user['actif']): ?>
                            <span class="badge bg-success"><?= _('Actif') ?></span>
                        <?php else: ?>
                            <span class="badge bg-danger"><?= _('Inactif') ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right side with Details -->
                <div class="col-md-8">
                    <!-- Personal Information -->
                    <div class="mb-4">
                        <h5 class="fw-bold border-bottom pb-2 mb-3"><?= _('Informations Personnelles') ?></h5>
                        <div class="row">
                            <div class="col-sm-6 mb-2"><strong><?= _('Sexe') ?>:</strong> <?= htmlspecialchars($user['sexe'] ?? 'N/A') ?></div>
                            <div class="col-sm-6 mb-2"><strong><?= _('Date de Naissance') ?>:</strong> <?= htmlspecialchars($user['date_naissance'] ?? 'N/A') ?></div>
                            <div class="col-sm-6 mb-2"><strong><?= _('Lieu de Naissance') ?>:</strong> <?= htmlspecialchars($user['lieu_naissance'] ?? 'N/A') ?></div>
                            <div class="col-sm-6 mb-2"><strong><?= _('Téléphone') ?>:</strong> <?= htmlspecialchars($user['telephone'] ?? 'N/A') ?></div>
                            <div class="col-12 mb-2"><strong><?= _('Adresse') ?>:</strong> <?= htmlspecialchars($user['adresse'] ?? 'N/A') ?></div>
                        </div>
                    </div>

                    <!-- Professional Information -->
                    <div class="mb-4">
                        <h5 class="fw-bold border-bottom pb-2 mb-3"><?= _('Informations Professionnelles') ?></h5>
                        <div class="row">
                            <div class="col-sm-6 mb-2"><strong><?= _('Rôle') ?>:</strong> <?= htmlspecialchars($role['nom_role'] ?? 'N/A') ?></div>
                            <div class="col-sm-6 mb-2"><strong><?= _('Type de Contrat') ?>:</strong> <?= htmlspecialchars($contrat['libelle'] ?? 'N/A') ?></div>
                            <div class="col-sm-6 mb-2"><strong><?= _('Date d\'embauche') ?>:</strong> <?= htmlspecialchars($user['date_embauche'] ?? 'N/A') ?></div>
                        </div>
                    </div>

                    <!-- Account Information -->
                    <div>
                        <h5 class="fw-bold border-bottom pb-2 mb-3"><?= _('Informations du Compte') ?></h5>
                        <div class="row">
                            <div class="col-12 mb-2"><strong><?= _('Email') ?>:</strong> <?= htmlspecialchars($user['email']) ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($role && $role['nom_role'] === 'surveillant' && !empty($assignable_classes)): ?>
            <!-- Assignment Management -->
            <div class="mt-4 pt-3 border-top">
                <h4 class="fw-bold mb-3"><?= _('Gestion des Assignations (Supervision)') ?></h4>
                <div class="card bg-light border">
                    <div class="card-body">
                        <!-- Existing Assignments -->
                        <h6 class="fw-bold mb-2"><?= _('Classes actuellement supervisées') ?></h6>
                        <?php if (empty($assignments)): ?>
                            <p class="text-muted"><?= _('Aucune classe assignée pour le moment.') ?></p>
                        <?php else: ?>
                            <ul class="list-group mb-3">
                                <?php foreach ($assignments as $assignment): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <?= htmlspecialchars($assignment['target_name']) ?>
                                        <form action="/personnel-assignments/destroy" method="POST" onsubmit="return confirm('<?= _('Retirer cette assignation ?') ?>');">
                                            <input type="hidden" name="id_assignment" value="<?= $assignment['id_assignment'] ?>">
                                            <input type="hidden" name="personnel_id" value="<?= $user['id_user'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">&times;</button>
                                        </form>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <!-- Add Assignment Form -->
                        <h6 class="fw-bold mt-4 mb-2"><?= _('Assigner une nouvelle classe') ?></h6>
                        <form action="/personnel-assignments/store" method="POST" class="row g-2 align-items-end">
                            <input type="hidden" name="personnel_id" value="<?= $user['id_user'] ?>">
                            <input type="hidden" name="assignment_type" value="supervises_class">
                            <div class="col-md-8">
                                <label for="target_id" class="form-label visually-hidden"><?= _('Classe') ?></label>
                                <select name="target_id" id="target_id" class="form-select" required>
                                    <option value=""><?= _('-- Sélectionner une classe --') ?></option>
                                    <?php foreach ($assignable_classes as $classe): ?>
                                        <option value="<?= $classe['id_classe'] ?>"><?= htmlspecialchars($classe['nom_classe']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary w-100"><?= _('Assigner') ?></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div class="mt-4 pt-3 border-top d-flex justify-content-end">
                 <?php if (Auth::get('id') != $user['id_user']): ?>
                    <a href="/users/edit?id=<?= $user['id_user'] ?>" class="btn btn-primary me-2"><?= _('Modifier') ?></a>
                    <form action="/users/destroy" method="POST" class="d-inline" onsubmit="return confirm('<?= _('Êtes-vous sûr de vouloir supprimer ce membre ?') ?>');">
                        <input type="hidden" name="id" value="<?= $user['id_user'] ?>">
                        <button type="submit" class="btn btn-danger"><?= _('Supprimer') ?></button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>