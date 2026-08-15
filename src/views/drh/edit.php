<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <div class="page-header d-print-none">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= htmlspecialchars($title) ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php $activeTab = 'edit'; require_once __DIR__ . '/_tabs.php'; ?>

        <?php if (!empty($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= htmlspecialchars($_SESSION['error_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><?= _('Modification Fiche Personnel') ?> : <?= htmlspecialchars($p['prenom'] . ' ' . $p['nom']) ?></h5>
            </div>
            <div class="card-body">
                <form method="POST" action="/drh/update" enctype="multipart/form-data">
                    <input type="hidden" name="id_user" value="<?= $p['id_user'] ?>">

                    <h6 class="text-primary mb-3"><i class="ph-duotone ph-user me-1"></i> <?= _('1. Identité Civile') ?></h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label required"><?= _('Nom') ?></label>
                            <input type="text" name="nom" class="form-control" value="<?= htmlspecialchars($p['nom']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required"><?= _('Prénom') ?></label>
                            <input type="text" name="prenom" class="form-control" value="<?= htmlspecialchars($p['prenom']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><?= _('Sexe') ?></label>
                            <select name="sexe" class="form-select">
                                <option value="Homme" <?= $p['sexe'] === 'Homme' ? 'selected' : '' ?>><?= _('Homme') ?></option>
                                <option value="Femme" <?= $p['sexe'] === 'Femme' ? 'selected' : '' ?>><?= _('Femme') ?></option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><?= _('Date de Naissance') ?></label>
                            <input type="date" name="date_naissance" class="form-control" value="<?= htmlspecialchars($p['date_naissance'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><?= _('Lieu de Naissance') ?></label>
                            <input type="text" name="lieu_naissance" class="form-control" value="<?= htmlspecialchars($p['lieu_naissance'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><?= _('Situation Matrimoniale') ?></label>
                            <select name="situation_matrimoniale" class="form-select">
                                <option value="celibataire" <?= ($p['situation_matrimoniale'] ?? '') === 'celibataire' ? 'selected' : '' ?>><?= _('Célibataire') ?></option>
                                <option value="marie" <?= ($p['situation_matrimoniale'] ?? '') === 'marie' ? 'selected' : '' ?>><?= _('Marié(e)') ?></option>
                                <option value="divorce" <?= ($p['situation_matrimoniale'] ?? '') === 'divorce' ? 'selected' : '' ?>><?= _('Divorcé(e)') ?></option>
                                <option value="veuf" <?= ($p['situation_matrimoniale'] ?? '') === 'veuf' ? 'selected' : '' ?>><?= _('Veuf/Veuve') ?></option>
                            </select>
                        </div>
                    </div>

                    <h6 class="text-primary mb-3"><i class="ph-duotone ph-phone me-1"></i> <?= _('2. Coordonnées & Accès') ?></h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label required"><?= _('Email (Identifiant de connexion)') ?></label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($p['email']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><?= _('Nouveau Mot de Passe (Laisser vide pour ne pas modifier)') ?></label>
                            <input type="password" name="mot_de_passe" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><?= _('Téléphone') ?></label>
                            <input type="text" name="telephone" class="form-control" value="<?= htmlspecialchars($p['telephone'] ?? '') ?>">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label"><?= _('Adresse Domicile') ?></label>
                            <input type="text" name="adresse" class="form-control" value="<?= htmlspecialchars($p['adresse'] ?? '') ?>">
                        </div>
                    </div>

                    <h6 class="text-primary mb-3"><i class="ph-duotone ph-briefcase me-1"></i> <?= _('3. Affectation Administrative & RBAC') ?></h6>
                    <div class="row g-3 mb-4">
                        <?php if (Auth::can('view_all_lycees', 'lycee')): ?>
                        <div class="col-md-4">
                            <label class="form-label required"><?= _('Établissement') ?></label>
                            <select name="lycee_id" class="form-select" required>
                                <?php foreach ($lycees as $ly): ?>
                                    <option value="<?= $ly['id'] ?>" <?= $p['lycee_id'] == $ly['id'] ? 'selected' : '' ?>><?= htmlspecialchars($ly['nom_lycee']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>

                        <div class="col-md-4">
                            <label class="form-label required"><?= _('Fonction RH') ?></label>
                            <input type="text" name="fonction" class="form-control" value="<?= htmlspecialchars($p['fonction'] ?? '') ?>" required list="fonctionsList">
                            <datalist id="fonctionsList">
                                <?php foreach ($fonctions as $f): ?>
                                    <option value="<?= htmlspecialchars($f['libelle']) ?>"><?= htmlspecialchars($f['departement']) ?></option>
                                <?php endforeach; ?>
                            </datalist>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label required"><?= _('Rôle Applicatif RBAC') ?></label>
                            <select name="role_id" class="form-select" required>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?= $r['id_role'] ?>" <?= $p['role_id'] == $r['id_role'] ? 'selected' : '' ?>><?= htmlspecialchars($r['nom_role']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label"><?= _('Type de Contrat Actuel') ?></label>
                            <select name="contrat_id" class="form-select">
                                <option value=""><?= _('Aucun') ?></option>
                                <?php foreach ($contrats as $tc): ?>
                                    <option value="<?= $tc['id_contrat'] ?>" <?= $p['contrat_id'] == $tc['id_contrat'] ? 'selected' : '' ?>><?= htmlspecialchars($tc['libelle']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label"><?= _('Date d\'Embauche') ?></label>
                            <input type="date" name="date_embauche" class="form-control" value="<?= htmlspecialchars($p['date_embauche'] ?? '') ?>">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label"><?= _('Numéro CNSS') ?></label>
                            <input type="text" name="num_cnss" class="form-control" value="<?= htmlspecialchars($p['num_cnss'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="/drh/show?id=<?= $p['id_user'] ?>" class="btn btn-light-secondary"><?= _('Annuler') ?></a>
                        <button type="submit" class="btn btn-primary"><i class="ph-duotone ph-check me-1"></i> <?= _('Mettre à jour') ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
