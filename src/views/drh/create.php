<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <!-- En-tête -->
        <div class="page-header d-print-none mb-3">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-7">
                        <div class="page-header-title d-flex align-items-center gap-2">
                            <a href="/drh" class="btn btn-sm btn-light-secondary me-2" title="<?= _('Retour à l\'annuaire') ?>">
                                <i class="ph-duotone ph-arrow-left fs-5"></i>
                            </a>
                            <div>
                                <h5 class="m-b-10"><?= htmlspecialchars($title) ?></h5>
                                <p class="text-muted mb-0 small">
                                    <i class="ph-duotone ph-user-plus me-1 text-primary"></i>
                                    <?= _('Enregistrement d\'un nouveau collaborateur dans le système SGS') ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php $activeTab = 'create'; require_once __DIR__ . '/_tabs.php'; ?>

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
                    <i class="ph-duotone ph-id-card text-primary"></i>
                    <span><?= _('Formulaire de Création du Personnel') ?></span>
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="/drh/store" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <!-- Section 1 -->
                    <div class="mb-4">
                        <h6 class="text-primary mb-3 d-flex align-items-center gap-2">
                            <i class="ph-duotone ph-user text-primary fs-5"></i>
                            <span><?= _('1. Identité Civile') ?></span>
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label required small fw-semibold text-muted"><?= _('Nom') ?></label>
                                <input type="text" name="nom" class="form-control" required placeholder="<?= _('Saisir le nom...') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label required small fw-semibold text-muted"><?= _('Prénom') ?></label>
                                <input type="text" name="prenom" class="form-control" required placeholder="<?= _('Saisir le prénom...') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold text-muted"><?= _('Sexe') ?></label>
                                <select name="sexe" class="form-select">
                                    <option value="Homme"><?= _('Homme') ?></option>
                                    <option value="Femme"><?= _('Femme') ?></option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold text-muted"><?= _('Date de Naissance') ?></label>
                                <input type="date" name="date_naissance" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold text-muted"><?= _('Lieu de Naissance') ?></label>
                                <input type="text" name="lieu_naissance" class="form-control" placeholder="<?= _('Lieu de naissance...') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold text-muted"><?= _('Situation Matrimoniale') ?></label>
                                <select name="situation_matrimoniale" class="form-select">
                                    <option value="celibataire"><?= _('Célibataire') ?></option>
                                    <option value="marie"><?= _('Marié(e)') ?></option>
                                    <option value="divorce"><?= _('Divorcé(e)') ?></option>
                                    <option value="veuf"><?= _('Veuf/Veuve') ?></option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold text-muted"><?= _('Nombre d\'Enfants') ?></label>
                                <input type="number" name="nombre_enfants" class="form-control" min="0" value="0">
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
                                <input type="email" name="email" class="form-control" required placeholder="nom@etablissement.com">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label required small fw-semibold text-muted"><?= _('Mot de Passe') ?></label>
                                <input type="password" name="mot_de_passe" class="form-control" required placeholder="••••••••">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold text-muted"><?= _('Téléphone') ?></label>
                                <input type="text" name="telephone" class="form-control" placeholder="+241 ...">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-semibold text-muted"><?= _('Adresse Domicile') ?></label>
                                <input type="text" name="adresse" class="form-control" placeholder="<?= _('Adresse physique du domicile...') ?>">
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
                                        <option value="<?= $ly['id'] ?>"><?= htmlspecialchars($ly['nom_lycee']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>

                            <div class="col-md-4">
                                <label class="form-label required small fw-semibold text-muted"><?= _('Fonction RH') ?></label>
                                <input type="text" name="fonction" class="form-control" placeholder="<?= _('ex: Enseignant de Physique') ?>" required list="fonctionsList">
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
                                        <option value="<?= $r['id_role'] ?>"><?= htmlspecialchars($r['nom_role']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-semibold text-muted"><?= _('Contrat Initial (Optionnel)') ?></label>
                                <select name="contrat_id" class="form-select">
                                    <option value=""><?= _('Aucun pour le moment') ?></option>
                                    <?php foreach ($contrats as $tc): ?>
                                        <option value="<?= $tc['id_contrat'] ?>"><?= htmlspecialchars($tc['libelle']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-semibold text-muted"><?= _('Date d\'Embauche') ?></label>
                                <input type="date" name="date_embauche" class="form-control" value="<?= date('Y-m-d') ?>">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-semibold text-muted"><?= _('Numéro CNSS') ?></label>
                                <input type="text" name="num_cnss" class="form-control" placeholder="CNSS-12345678">
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="/drh" class="btn btn-light-secondary"><?= _('Annuler') ?></a>
                        <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-1">
                            <i class="ph-duotone ph-check fs-5"></i>
                            <span><?= _('Créer Personnel') ?></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
