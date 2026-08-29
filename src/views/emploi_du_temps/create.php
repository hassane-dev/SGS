<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h5 class="m-b-10"><?= _("Ajouter un Cours à l'Emploi du Temps") ?></h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/home"><?= _('Tableau de Bord') ?></a></li>
                            <li class="breadcrumb-item"><a href="/emploi-du-temps"><?= _('Emploi du Temps') ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _('Ajouter un Cours') ?></li>
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
                        <h5><?= _("Formulaire d'Ajout de Cours") ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_GET['error']) && $_GET['error'] === 'conflict'): ?>
                            <div class="alert alert-danger" role="alert">
                                <?= !empty($_SESSION['error_message']) ? htmlspecialchars($_SESSION['error_message']) : _("Conflit détecté ! Le professeur, la classe ou la salle est déjà occupé(e) à cette heure.") ?>
                            </div>
                            <?php unset($_SESSION['error_message']); ?>
                        <?php endif; ?>

                        <form action="/emploi-du-temps/store" method="POST" id="form-create-edt">
                            <input type="hidden" name="annee_academique_id" value="<?= htmlspecialchars($data['annee_academique_id']) ?>">

                            <!-- Unified SGS Pedagogical Hierarchy Cascade -->
                            <div class="card mb-4 bg-light border-0">
                                <div class="card-body">
                                    <h6 class="fw-bold mb-3 text-primary"><i class="ti ti-sitemap me-1"></i><?= _("Sélection Pédagogique (Convention SGS)") ?></h6>
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <label for="cascade_cycle_id" class="form-label fw-bold"><?= _('Cycle') ?> <span class="text-danger">*</span></label>
                                            <select id="cascade_cycle_id" class="form-select" required>
                                                <option value=""><?= _('-- Choisir Cycle --') ?></option>
                                                <?php foreach ($data['cycles'] as $cyc): ?>
                                                    <option value="<?= $cyc['id_cycle'] ?>"><?= htmlspecialchars($cyc['nom_cycle']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="cascade_niveau" class="form-label fw-bold"><?= _('Niveau') ?> <span class="text-danger">*</span></label>
                                            <select id="cascade_niveau" class="form-select" required>
                                                <option value=""><?= _('-- Choisir d\'abord le cycle --') ?></option>
                                            </select>
                                        </div>
                                        <div class="col-md-3" id="group_serie" style="display: none;">
                                            <label for="cascade_serie" class="form-label fw-bold"><?= _('Série') ?></label>
                                            <select id="cascade_serie" class="form-select">
                                                <option value=""><?= _('-- Choisir Série --') ?></option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="cascade_numero" class="form-label fw-bold"><?= _('Numéro / Classe') ?> <span class="text-danger">*</span></label>
                                            <select id="cascade_numero" class="form-select" required>
                                                <option value=""><?= _('-- Choisir d\'abord le niveau --') ?></option>
                                            </select>
                                        </div>
                                    </div>
                                    <input type="hidden" id="classe_id" name="classe_id" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="matiere_id" class="form-label fw-bold"><?= _('Matière') ?> <span class="text-danger">*</span></label>
                                    <select id="matiere_id" name="matiere_id" class="form-select" required>
                                        <option value=""><?= _('-- Sélectionner d\'abord la classe --') ?></option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="professeur_id" class="form-label fw-bold"><?= _('Enseignant') ?> <span class="text-danger">*</span></label>
                                    <select id="professeur_id" name="professeur_id" class="form-select" required>
                                        <option value=""><?= _('-- Sélectionner d\'abord un cycle --') ?></option>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="salle_id" class="form-label fw-bold"><?= _('Salle') ?></label>
                                    <select id="salle_id" name="salle_id" class="form-select">
                                        <option value=""><?= _('Sélectionner une salle (optionnel)') ?></option>
                                        <?php foreach ($data['salles'] as $salle): ?>
                                            <option value="<?= $salle['id_salle'] ?>"><?= htmlspecialchars($salle['nom_salle']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="jour" class="form-label fw-bold"><?= _('Jour') ?> <span class="text-danger">*</span></label>
                                    <select id="jour" name="jour" class="form-select" required>
                                        <?php $days = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche']; ?>
                                        <?php foreach ($days as $day): ?>
                                            <option value="<?= $day ?>"><?= _($day) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="heure_debut" class="form-label fw-bold"><?= _('Heure de Début') ?> <span class="text-danger">*</span></label>
                                    <input type="time" id="heure_debut" name="heure_debut" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="heure_fin" class="form-label fw-bold"><?= _('Heure de Fin') ?> <span class="text-danger">*</span></label>
                                    <input type="time" id="heure_fin" name="heure_fin" class="form-control" required>
                                </div>
                            </div>

                            <div class="d-flex gap-2 mt-3">
                                <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i><?= _('Enregistrer') ?></button>
                                <a href="/emploi-du-temps" class="btn btn-secondary"><?= _('Annuler') ?></a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ Main Content ] end -->
    </div>
</div>

<script src="/assets/js/sgs-pedagogical-cascade.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new SGSPedagogicalCascade({
        cycleId: 'cascade_cycle_id',
        niveauId: 'cascade_niveau',
        groupSerieId: 'group_serie',
        serieId: 'cascade_serie',
        numeroId: 'cascade_numero',
        classeId: 'classe_id',
        matiereId: 'matiere_id',
        teacherId: 'professeur_id',
        includeAllSubjects: true
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
