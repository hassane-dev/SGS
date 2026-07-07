<?php include __DIR__ . '/../layouts/header_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/"><?= _('Tableau de bord') ?></a></li>
                            <li class="breadcrumb-item"><a href="/evaluations/settings"><?= _('Paramètres de Saisie') ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _('Nouveaux Paramètres') ?></li>
                        </ul>
                    </div>
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= _('Définir une Période de Saisie') ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- [ Main Content ] start -->
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header">
                        <h5><?= _('Configuration de la période') ?></h5>
                    </div>
                    <div class="card-body">
                        <form action="/evaluations/settings/store" method="POST">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="type"><?= _('Niveau de ciblage') ?></label>
                                    <select class="form-select" id="type" name="type" required onchange="toggleFields()">
                                        <option value="global"><?= _('Global (Tout l\'établissement)') ?></option>
                                        <option value="classe"><?= _('Par classe') ?></option>
                                        <option value="matiere"><?= _('Par matière') ?></option>
                                        <option value="classe_matiere"><?= _('Par classe + matière') ?></option>
                                        <option value="enseignant"><?= _('Par enseignant (Spécifique)') ?></option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="sequence_id"><?= _('Séquence concernée') ?></label>
                                    <select class="form-select" id="sequence_id" name="sequence_id">
                                        <option value=""><?= _('Toutes les séquences') ?></option>
                                        <?php foreach ($sequences as $s): ?>
                                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nom']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-12">
                                    <label class="form-label"><?= _('Nature de l\'évaluation') ?></label>
                                    <div class="d-flex gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="type_evaluation" id="eval_tous" value="tous" checked>
                                            <label class="form-check-label" for="eval_tous">Tous (Devoirs & Compositions)</label>
                                        </div>
                                        <div class="form-check ms-3">
                                            <input class="form-check-input" type="radio" name="type_evaluation" id="eval_devoir" value="devoir">
                                            <label class="form-check-label" for="eval_devoir">Devoirs uniquement</label>
                                        </div>
                                        <div class="form-check ms-3">
                                            <input class="form-check-input" type="radio" name="type_evaluation" id="eval_composition" value="composition">
                                            <label class="form-check-label" for="eval_composition">Compositions uniquement</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-3 d-none" id="field-classe">
                                <div class="col-12">
                                    <label class="form-label" for="classe_id"><?= _('Sélectionner la classe') ?></label>
                                    <select class="form-select" id="classe_id" name="classe_id">
                                        <option value=""><?= _('Choisir une classe...') ?></option>
                                        <?php foreach ($classes as $c): ?>
                                            <option value="<?= $c['id_classe'] ?>"><?= htmlspecialchars($c['nom_classe']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3 d-none" id="field-matiere">
                                <div class="col-12">
                                    <label class="form-label" for="matiere_id"><?= _('Sélectionner la matière') ?></label>
                                    <select class="form-select" id="matiere_id" name="matiere_id">
                                        <option value=""><?= _('Choisir une matière...') ?></option>
                                        <?php foreach ($matieres as $m): ?>
                                            <option value="<?= $m['id_matiere'] ?>"><?= htmlspecialchars($m['nom_matiere']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3 d-none" id="field-enseignant">
                                <div class="col-12">
                                    <label class="form-label" for="enseignant_id"><?= _('Sélectionner l\'enseignant') ?></label>
                                    <select class="form-select" id="enseignant_id" name="enseignant_id">
                                        <option value=""><?= _('Choisir un enseignant...') ?></option>
                                        <?php foreach ($enseignants as $e): ?>
                                            <option value="<?= $e['id_user'] ?>"><?= htmlspecialchars($e['full_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="date_ouverture"><?= _('Date d\'ouverture') ?></label>
                                    <input type="datetime-local" class="form-control" id="date_ouverture" name="date_ouverture" required value="<?= date('Y-m-d\TH:i') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="date_fermeture"><?= _('Date de fermeture') ?></label>
                                    <input type="datetime-local" class="form-control" id="date_fermeture" name="date_fermeture" required value="<?= date('Y-m-d\TH:i', strtotime('+7 days')) ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="commentaire"><?= _('Commentaire / Instruction') ?></label>
                                <textarea class="form-control" id="commentaire" name="commentaire" rows="3" placeholder="<?= _('Ex: Période de saisie normale pour le premier semestre') ?>"></textarea>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="/evaluations/settings" class="btn btn-link text-muted"><?= _('Annuler') ?></a>
                                <button type="submit" class="btn btn-primary"><?= _('Enregistrer les paramètres') ?></button>
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
function toggleFields() {
    const type = document.getElementById('type').value;

    document.getElementById('field-classe').classList.add('d-none');
    document.getElementById('field-matiere').classList.add('d-none');
    document.getElementById('field-enseignant').classList.add('d-none');

    if (type === 'classe') {
        document.getElementById('field-classe').classList.remove('d-none');
    } else if (type === 'matiere') {
        document.getElementById('field-matiere').classList.remove('d-none');
    } else if (type === 'classe_matiere') {
        document.getElementById('field-classe').classList.remove('d-none');
        document.getElementById('field-matiere').classList.remove('d-none');
    } else if (type === 'enseignant') {
        document.getElementById('field-classe').classList.remove('d-none');
        document.getElementById('field-matiere').classList.remove('d-none');
        document.getElementById('field-enseignant').classList.remove('d-none');
    }
}
</script>

<?php include __DIR__ . '/../layouts/footer_able.php'; ?>
