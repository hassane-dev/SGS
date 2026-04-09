<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h5 class="m-b-10"><?= htmlspecialchars($title) ?></h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/home"><?= _('Tableau de Bord') ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _('Paramètres Compositions') ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- [ Main Content ] start -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Configuration des Compositions pour l'Année en Cours</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_GET['success'])): ?>
                            <div class="alert alert-success">Les paramètres ont été mis à jour avec succès.</div>
                        <?php elseif (isset($_GET['error'])): ?>
                            <div class="alert alert-danger">Une erreur est survenue lors de la mise à jour.</div>
                        <?php endif; ?>

                        <div class="alert alert-info">
                            Ces paramètres s'appliquent à toute l'école pour l'année académique active.
                        </div>

                        <form action="/param-composition/update" method="POST">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="nombre_composition_par_sequence" class="form-label">Nombre de compositions autorisées par séquence</label>
                                    <input type="number" id="nombre_composition_par_sequence" name="nombre_composition_par_sequence" class="form-control" value="<?= htmlspecialchars($params['nombre_composition_par_sequence'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="note_maximale" class="form-label">Note maximale pour une composition</label>
                                    <input type="number" step="0.25" id="note_maximale" name="note_maximale" class="form-control" value="<?= htmlspecialchars($params['note_maximale'] ?? '') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="date_debut_insertion" class="form-label">Date d'ouverture globale de la saisie</label>
                                    <input type="datetime-local" id="date_debut_insertion" name="date_debut_insertion" class="form-control" value="<?= !empty($params['date_debut_insertion']) ? (new DateTime($params['date_debut_insertion']))->format('Y-m-d\TH:i') : '' ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="date_fin_insertion" class="form-label">Date de fermeture globale de la saisie</label>
                                    <input type="datetime-local" id="date_fin_insertion" name="date_fin_insertion" class="form-control" value="<?= !empty($params['date_fin_insertion']) ? (new DateTime($params['date_fin_insertion']))->format('Y-m-d\TH:i') : '' ?>">
                                </div>
                                <div class="col-12">
                                    <div class="form-check">
                                        <input type="checkbox" id="deblocage_urgence" name="deblocage_urgence" class="form-check-input" value="1" <?= ($params['deblocage_urgence'] ?? 0) ? 'checked' : '' ?>>
                                        <label for="deblocage_urgence" class="form-check-label">Débloquer la saisie en urgence</label>
                                        <small class="form-text text-muted d-block">Cocher cette case ignore les dates de restriction pour tous.</small>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
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