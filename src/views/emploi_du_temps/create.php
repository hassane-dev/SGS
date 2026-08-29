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
                                                    <option value="<?= $cyc['id'] ?>" data-nom="<?= htmlspecialchars($cyc['nom']) ?>"><?= htmlspecialchars($cyc['nom']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="cascade_niveau" class="form-label fw-bold"><?= _('Niveau') ?> <span class="text-danger">*</span></label>
                                            <select id="cascade_niveau" class="form-select" disabled required>
                                                <option value=""><?= _('-- Choisir d\'abord le cycle --') ?></option>
                                            </select>
                                        </div>
                                        <div class="col-md-3" id="group_serie" style="display: none;">
                                            <label for="cascade_serie" class="form-label fw-bold"><?= _('Série') ?></label>
                                            <select id="cascade_serie" class="form-select" disabled>
                                                <option value=""><?= _('-- Choisir Série --') ?></option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="cascade_numero" class="form-label fw-bold"><?= _('Numéro / Classe') ?> <span class="text-danger">*</span></label>
                                            <select id="cascade_numero" class="form-select" disabled required>
                                                <option value=""><?= _('-- Choisir d\'abord le niveau --') ?></option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <label for="classe_id" class="form-label fw-bold"><?= _('Classe Sélectionnée') ?> <span class="text-danger">*</span></label>
                                        <select id="classe_id" name="classe_id" class="form-select" required>
                                            <option value=""><?= _('-- Sélectionner la classe --') ?></option>
                                            <?php foreach ($data['classes'] as $classe): ?>
                                                <option value="<?= $classe['id_classe'] ?>"><?= htmlspecialchars(Classe::getFormattedName($classe)) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="matiere_id" class="form-label fw-bold"><?= _('Matière') ?> <span class="text-danger">*</span></label>
                                    <select id="matiere_id" name="matiere_id" class="form-select" required>
                                        <option value=""><?= _('Sélectionner une matière') ?></option>
                                        <?php foreach ($data['matieres'] as $matiere): ?>
                                            <option value="<?= $matiere['id_matiere'] ?>"><?= htmlspecialchars($matiere['nom_matiere']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="professeur_id" class="form-label fw-bold"><?= _('Enseignant') ?> <span class="text-danger">*</span></label>
                                    <select id="professeur_id" name="professeur_id" class="form-select" required>
                                        <option value=""><?= _('Sélectionner un enseignant') ?></option>
                                        <?php foreach ($data['professeurs'] as $prof): ?>
                                            <option value="<?= $prof['id_user'] ?>"><?= htmlspecialchars($prof['prenom'] . ' ' . $prof['nom']) ?></option>
                                        <?php endforeach; ?>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cycleSelect = document.getElementById('cascade_cycle_id');
    const niveauSelect = document.getElementById('cascade_niveau');
    const groupSerie = document.getElementById('group_serie');
    const serieSelect = document.getElementById('cascade_serie');
    const numeroSelect = document.getElementById('cascade_numero');
    const classeSelect = document.getElementById('classe_id');

    function resetSelect(el, defaultText) {
        el.innerHTML = `<option value="">${defaultText}</option>`;
        el.disabled = true;
    }

    cycleSelect.addEventListener('change', function() {
        const cycleId = this.value;
        resetSelect(niveauSelect, '-- Choisir Niveau --');
        resetSelect(serieSelect, '-- Choisir Série --');
        resetSelect(numeroSelect, '-- Choisir Numéro --');
        groupSerie.style.display = 'none';

        if (!cycleId) return;

        fetch(`/affectations-pedagogiques/get-niveaux?cycle_id=${cycleId}`)
            .then(res => res.json())
            .then(data => {
                niveauSelect.disabled = false;
                data.forEach(niv => {
                    niveauSelect.innerHTML += `<option value="${niv}">${niv}</option>`;
                });
            });
    });

    niveauSelect.addEventListener('change', function() {
        const niveau = this.value;
        const cycleId = cycleSelect.value;
        resetSelect(serieSelect, '-- Choisir Série --');
        resetSelect(numeroSelect, '-- Choisir Numéro --');

        if (!niveau) return;

        fetch(`/affectations-pedagogiques/get-series?niveau=${encodeURIComponent(niveau)}&cycle_id=${cycleId}`)
            .then(res => res.json())
            .then(series => {
                if (series && series.length > 0) {
                    groupSerie.style.display = 'block';
                    serieSelect.disabled = false;
                    series.forEach(s => {
                        serieSelect.innerHTML += `<option value="${s}">${s}</option>`;
                    });
                } else {
                    groupSerie.style.display = 'none';
                    fetch(`/affectations-pedagogiques/get-numeros?niveau=${encodeURIComponent(niveau)}&cycle_id=${cycleId}`)
                        .then(res => res.json())
                        .then(numeros => {
                            numeroSelect.disabled = false;
                            numeros.forEach(n => {
                                numeroSelect.innerHTML += `<option value="${n}">${n}</option>`;
                            });
                        });
                }
            });
    });

    serieSelect.addEventListener('change', function() {
        const niveau = niveauSelect.value;
        const cycleId = cycleSelect.value;
        const serie = this.value;
        resetSelect(numeroSelect, '-- Choisir Numéro --');

        if (!serie) return;

        fetch(`/affectations-pedagogiques/get-numeros?niveau=${encodeURIComponent(niveau)}&serie=${encodeURIComponent(serie)}&cycle_id=${cycleId}`)
            .then(res => res.json())
            .then(data => {
                numeroSelect.disabled = false;
                data.forEach(n => {
                    numeroSelect.innerHTML += `<option value="${n}">${n}</option>`;
                });
            });
    });

    numeroSelect.addEventListener('change', function() {
        const cycleId = cycleSelect.value;
        const niveau = niveauSelect.value;
        const serie = (groupSerie.style.display !== 'none') ? serieSelect.value : '';
        const numero = this.value;

        if (!numero) return;

        fetch(`/affectations-pedagogiques/get-classe-id?niveau=${encodeURIComponent(niveau)}&serie=${encodeURIComponent(serie)}&numero=${encodeURIComponent(numero)}&cycle_id=${cycleId}`)
            .then(res => res.json())
            .then(data => {
                if (data.id_classe) {
                    classeSelect.value = data.id_classe;
                }
            });
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
