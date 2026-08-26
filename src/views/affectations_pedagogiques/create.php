<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<!-- [ Main Content ] start -->
<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= _('Nouvelle Affectation Pédagogique') ?></h2>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <a href="/affectations-pedagogiques" class="btn btn-outline-secondary">
                            <i class="ph-duotone ph-arrow-left me-1"></i>
                            <?= _('Retour à la liste') ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- [ Main Content ] start -->
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h5><?= _('Formulaire d\'affectation') ?> (<?= htmlspecialchars($active_year['libelle'] ?? '') ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['error_message'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?= htmlspecialchars($_SESSION['error_message']) ?>
                                <?php unset($_SESSION['error_message']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form action="/affectations-pedagogiques/store" method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="classe_id" class="form-label"><?= _('Classe') ?> <span class="text-danger">*</span></label>
                                    <select name="classe_id" id="classe_id" class="form-select" required onchange="window.location.href='/affectations-pedagogiques/create?classe_id=' + this.value;">
                                        <option value=""><?= _('-- Choisir une classe --') ?></option>
                                        <?php foreach ($classes as $c): ?>
                                            <option value="<?= $c['id_classe'] ?>" <?= (isset($selected_classe_id) && $selected_classe_id == $c['id_classe']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars(Classe::getFormattedName($c)) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="matiere_id" class="form-label"><?= _('Matière') ?> <span class="text-danger">*</span></label>
                                    <select name="matiere_id" id="matiere_id" class="form-select" required>
                                        <option value=""><?= _('-- Choisir une matière --') ?></option>
                                        <?php foreach ($matieres as $m): ?>
                                            <option value="<?= $m['id_matiere'] ?>" <?= (isset($selected_matiere_id) && $selected_matiere_id == $m['id_matiere']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($m['nom_matiere']) ?> (Coef: <?= $m['coefficient'] ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (empty($matieres) && $selected_classe_id): ?>
                                        <small class="text-danger"><?= _('Aucune matière n\'est inscrite au programme de cette classe. Ajoutez-en d\'abord.') ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="enseignant_id" class="form-label"><?= _('Enseignant') ?> <span class="text-danger">*</span></label>
                                <select name="enseignant_id" id="enseignant_id" class="form-select" required>
                                    <option value=""><?= _('-- Choisir un enseignant qualifié --') ?></option>
                                    <?php foreach ($enseignants as $e): ?>
                                        <option value="<?= $e['id_user'] ?>">
                                            <?= htmlspecialchars($e['full_name']) ?> (<?= htmlspecialchars($e['identifiant_public'] ?? 'ENS') ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="volume_horaire_hebdo" class="form-label"><?= _('Volume Horaire Hebdomadaire (heures)') ?></label>
                                    <input type="number" step="0.5" min="0" name="volume_horaire_hebdo" id="volume_horaire_hebdo" class="form-control" value="2.0">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="statut" class="form-label"><?= _('Statut Initial') ?></label>
                                    <select name="statut" id="statut" class="form-select">
                                        <option value="actif" selected><?= _('Actif (Titulaire en poste)') ?></option>
                                        <option value="provisoire"><?= _('Provisoire (En attente validation)') ?></option>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="date_debut" class="form-label"><?= _('Date d\'effet / Début') ?> <span class="text-danger">*</span></label>
                                    <input type="date" name="date_debut" id="date_debut" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="date_fin" class="form-label"><?= _('Date de fin (Optionnelle / Remplacement)') ?></label>
                                    <input type="date" name="date_fin" id="date_fin" class="form-control">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="motif_changement" class="form-label"><?= _('Commentaire / Motif') ?></label>
                                <textarea name="motif_changement" id="motif_changement" class="form-control" rows="2" placeholder="<?= _('Titulariat initial, remplacement, etc.') ?>"></textarea>
                            </div>

                            <div class="text-end">
                                <a href="/affectations-pedagogiques" class="btn btn-link-secondary me-2"><?= _('Annuler') ?></a>
                                <button type="submit" class="btn btn-primary"><?= _('Enregistrer l\'affectation') ?></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ Main Content ] end -->
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
