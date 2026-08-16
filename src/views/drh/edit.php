<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <!-- En-tête -->
        <div class="page-header d-print-none mb-3">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-7">
                        <div class="page-header-title d-flex align-items-center gap-2">
                            <a href="/drh/show?id=<?= $p['id_user'] ?>" class="btn btn-sm btn-light-secondary me-2" title="<?= _('Retour au dossier') ?>">
                                <i class="ph-duotone ph-arrow-left fs-5"></i>
                            </a>
                            <div>
                                <h5 class="m-b-10"><?= htmlspecialchars($title) ?></h5>
                                <p class="text-muted mb-0 small">
                                    <i class="ph-duotone ph-user text-primary me-1"></i>
                                    <?= htmlspecialchars($p['prenom'] . ' ' . $p['nom']) ?> &bull; <span class="font-monospace fw-bold text-dark"><?= htmlspecialchars($p['identifiant_public'] ?? 'N/A') ?></span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php $activeTab = 'edit'; require_once __DIR__ . '/_tabs.php'; ?>

        <?php if (!empty($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                <i class="ph-duotone ph-warning-circle me-1 fs-5 align-middle"></i>
                <?= htmlspecialchars($_SESSION['error_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent py-3">
                <h5 class="mb-0 d-flex align-items-center gap-2">
                    <i class="ph-duotone ph-pencil-line text-warning"></i>
                    <span><?= _('Mise à jour de la Fiche Personnel') ?></span>
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="/drh/update" enctype="multipart/form-data">
                    <input type="hidden" name="id_user" value="<?= $p['id_user'] ?>">

                    <!-- Section 1 -->
                    <div class="mb-4">
                        <h6 class="text-primary mb-3 d-flex align-items-center gap-2">
                            <i class="ph-duotone ph-user text-primary fs-5"></i>
                            <span><?= _('1. Identité Civile') ?></span>
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label required small fw-semibold text-muted"><?= _('Nom') ?></label>
                                <input type="text" name="nom" class="form-control" value="<?= htmlspecialchars($p['nom']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label required small fw-semibold text-muted"><?= _('Prénom') ?></label>
                                <input type="text" name="prenom" class="form-control" value="<?= htmlspecialchars($p['prenom']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold text-muted"><?= _('Sexe') ?></label>
                                <select name="sexe" class="form-select">
                                    <option value="Homme" <?= $p['sexe'] === 'Homme' ? 'selected' : '' ?>><?= _('Homme') ?></option>
                                    <option value="Femme" <?= $p['sexe'] === 'Femme' ? 'selected' : '' ?>><?= _('Femme') ?></option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold text-muted"><?= _('Date de Naissance') ?></label>
                                <input type="date" name="date_naissance" class="form-control" value="<?= htmlspecialchars($p['date_naissance'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold text-muted"><?= _('Lieu de Naissance') ?></label>
                                <input type="text" name="lieu_naissance" class="form-control" value="<?= htmlspecialchars($p['lieu_naissance'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold text-muted"><?= _('Situation Matrimoniale') ?></label>
                                <select name="situation_matrimoniale" class="form-select">
                                    <option value="celibataire" <?= ($p['situation_matrimoniale'] ?? '') === 'celibataire' ? 'selected' : '' ?>><?= _('Célibataire') ?></option>
                                    <option value="marie" <?= ($p['situation_matrimoniale'] ?? '') === 'marie' ? 'selected' : '' ?>><?= _('Marié(e)') ?></option>
                                    <option value="divorce" <?= ($p['situation_matrimoniale'] ?? '') === 'divorce' ? 'selected' : '' ?>><?= _('Divorcé(e)') ?></option>
                                    <option value="veuf" <?= ($p['situation_matrimoniale'] ?? '') === 'veuf' ? 'selected' : '' ?>><?= _('Veuf/Veuve') ?></option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold text-muted"><?= _('Nombre d\'Enfants') ?></label>
                                <input type="number" name="nombre_enfants" class="form-control" min="0" value="<?= (int)($p['nombre_enfants'] ?? 0) ?>">
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Section 2 -->
                    <div class="mb-4">
                        <h6 class="text-primary mb-3 d-flex align-items-center gap-2">
                            <i class="ph-duotone ph-phone text-primary fs-5"></i>
                            <span><?= _('2. Coordonnées & Accès') ?></span>
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label required small fw-semibold text-muted"><?= _('Email (Identifiant de connexion)') ?></label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($p['email']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold text-muted"><?= _('Nouveau Mot de Passe (Optionnel)') ?></label>
                                <input type="password" name="mot_de_passe" class="form-control" placeholder="<?= _('Laisser vide si inchangé') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold text-muted"><?= _('Téléphone') ?></label>
                                <input type="text" name="telephone" class="form-control" value="<?= htmlspecialchars($p['telephone'] ?? '') ?>">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-semibold text-muted"><?= _('Adresse Domicile') ?></label>
                                <input type="text" name="adresse" class="form-control" value="<?= htmlspecialchars($p['adresse'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Section 3 -->
                    <div class="mb-4">
                        <h6 class="text-primary mb-3 d-flex align-items-center gap-2">
                            <i class="ph-duotone ph-briefcase text-primary fs-5"></i>
                            <span><?= _('3. Affectation Administrative & RBAC') ?></span>
                        </h6>
                        <div class="row g-3">
                            <?php if (Auth::can('view_all_lycees', 'lycee')): ?>
                            <div class="col-md-4">
                                <label class="form-label required small fw-semibold text-muted"><?= _('Établissement') ?></label>
                                <select name="lycee_id" class="form-select" required>
                                    <?php foreach ($lycees as $ly): ?>
                                        <option value="<?= $ly['id'] ?>" <?= $p['lycee_id'] == $ly['id'] ? 'selected' : '' ?>><?= htmlspecialchars($ly['nom_lycee']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>

                            <div class="col-md-4">
                                <label class="form-label required small fw-semibold text-muted"><?= _('Fonction RH') ?></label>
                                <input type="text" name="fonction" class="form-control" value="<?= htmlspecialchars($p['fonction'] ?? '') ?>" required list="fonctionsList">
                                <datalist id="fonctionsList">
                                    <?php foreach ($fonctions as $f): ?>
                                        <option value="<?= htmlspecialchars($f['libelle']) ?>"><?= htmlspecialchars($f['departement']) ?></option>
                                    <?php endforeach; ?>
                                </datalist>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label required small fw-semibold text-muted"><?= _('Rôle Applicatif RBAC') ?></label>
                                <select name="role_id" class="form-select" required>
                                    <?php foreach ($roles as $r): ?>
                                        <option value="<?= $r['id_role'] ?>" <?= $p['role_id'] == $r['id_role'] ? 'selected' : '' ?>><?= htmlspecialchars($r['nom_role']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-semibold text-muted"><?= _('Type de Contrat Actuel') ?></label>
                                <select name="contrat_id" class="form-select">
                                    <option value=""><?= _('Aucun') ?></option>
                                    <?php foreach ($contrats as $tc): ?>
                                        <option value="<?= $tc['id_contrat'] ?>" <?= $p['contrat_id'] == $tc['id_contrat'] ? 'selected' : '' ?>><?= htmlspecialchars($tc['libelle']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-semibold text-muted"><?= _('Date d\'Embauche') ?></label>
                                <input type="date" name="date_embauche" class="form-control" value="<?= htmlspecialchars($p['date_embauche'] ?? '') ?>">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-semibold text-muted"><?= _('Numéro CNSS') ?></label>
                                <input type="text" name="num_cnss" class="form-control" value="<?= htmlspecialchars($p['num_cnss'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="/drh/show?id=<?= $p['id_user'] ?>" class="btn btn-light-secondary"><?= _('Annuler') ?></a>
                        <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-1">
                            <i class="ph-duotone ph-check fs-5"></i>
                            <span><?= _('Mettre à jour') ?></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
