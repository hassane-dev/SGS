<?php
/**
 * Vue : Planification d'un Conseil de Discipline (Phase 6.1)
 */
require_once __DIR__ . '/../../layouts/header_able.php';
require_once __DIR__ . '/../../layouts/sidebar_able.php';
?>

<!-- [ Main Content ] start -->
<div class="pc-container">
    <div class="pc-content">

        <!-- Fil d'Ariane -->
        <div class="page-header mb-3">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/dashboard">Accueil</a></li>
                            <li class="breadcrumb-item"><a href="/discipline/councils">Conseils de Discipline</a></li>
                            <li class="breadcrumb-item active">Planification</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="ph-duotone ph-calendar-plus me-2 text-primary"></i>Planifier une Nouvelle Session du Conseil</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="/discipline/councils/store">
    <?= csrf_field() ?>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Code Session <span class="text-danger">*</span></label>
                                    <input type="text" name="code" class="form-control" value="<?= htmlspecialchars($suggestedCode) ?>" required>
                                    <small class="text-muted fs-8">Code unique dans l'établissement</small>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label fw-semibold">Titre du Conseil <span class="text-danger">*</span></label>
                                    <input type="text" name="titre" class="form-control" placeholder="ex: Conseil de Discipline du 1er Trimestre" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Date de Tenue <span class="text-danger">*</span></label>
                                    <input type="date" name="date_conseil" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Heure Début</label>
                                    <input type="time" name="heure_debut" class="form-control" value="09:00">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Heure Fin Estimatife</label>
                                    <input type="time" name="heure_fin" class="form-control" value="12:00">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Lieu de Séance</label>
                                <input type="text" name="lieu" class="form-control" placeholder="ex: Salle des professeurs / Salle du Conseil">
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Président du Conseil <span class="text-danger">*</span></label>
                                    <select name="president_user_id" class="form-select" required>
                                        <option value="">Sélectionner le Président...</option>
                                        <?php foreach ($staffUsers as $usr): ?>
                                            <option value="<?= $usr['id_user'] ?>">
                                                <?= htmlspecialchars($usr['prenom'] . ' ' . $usr['nom']) ?> (<?= htmlspecialchars($usr['fonction'] ?? 'Personnel') ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Secrétaire de Séance</label>
                                    <select name="secretaire_user_id" class="form-select">
                                        <option value="">Sélectionner le Secrétaire (optionnel)...</option>
                                        <?php foreach ($staffUsers as $usr): ?>
                                            <option value="<?= $usr['id_user'] ?>">
                                                <?= htmlspecialchars($usr['prenom'] . ' ' . $usr['nom']) ?> (<?= htmlspecialchars($usr['fonction'] ?? 'Personnel') ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold">Observations Préliminaires / Ordre du jour</label>
                                <textarea name="observations_generales" class="form-control" rows="3" placeholder="Contextes et motifs généraux d'ouverture de la session..."></textarea>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <a href="/discipline/councils" class="btn btn-outline-secondary">
                                    <i class="ph-duotone ph-arrow-left me-1"></i>Annuler
                                </a>
                                <button type="submit" class="btn btn-primary fw-bold px-4">
                                    <i class="ph-duotone ph-floppy-disk me-1"></i>Enregistrer & Planifier
                                </button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
<!-- [ Main Content ] end -->

<?php require_once __DIR__ . '/../../layouts/footer_able.php'; ?>
