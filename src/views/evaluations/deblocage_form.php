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
                            <li class="breadcrumb-item"><a href="/evaluations/deblocage"><?= _('Déblocages') ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _('Nouveau Déblocage') ?></li>
                        </ul>
                    </div>
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= _('Accorder un Déblocage Exceptionnel') ?></h2>
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
                        <h5><?= _('Paramètres du déblocage') ?></h5>
                    </div>
                    <div class="card-body">
                        <form action="/evaluations/deblocage/store" method="POST">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="type"><?= _('Type de déblocage') ?></label>
                                    <select class="form-select" id="type" name="type" required onchange="toggleFields()">
                                        <option value="global"><?= _('Global (Tout l\'établissement)') ?></option>
                                        <option value="classe"><?= _('Par classe') ?></option>
                                        <option value="matiere"><?= _('Par matière') ?></option>
                                        <option value="classe_matiere"><?= _('Par classe + matière') ?></option>
                                        <option value="enseignant"><?= _('Par enseignant (Le plus fin)') ?></option>
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
                                    <label class="form-label" for="date_debut"><?= _('Date de début') ?></label>
                                    <input type="datetime-local" class="form-control" id="date_debut" name="date_debut" required value="<?= date('Y-m-d\TH:i') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="date_fin"><?= _('Date d\'expiration') ?></label>
                                    <input type="datetime-local" class="form-control" id="date_fin" name="date_fin" required value="<?= date('Y-m-d\TH:i', strtotime('+2 days')) ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="motif"><?= _('Motif du déblocage') ?></label>
                                <textarea class="form-control" id="motif" name="motif" rows="3" placeholder="<?= _('Ex: Correction d\'erreur de saisie demandée par le proviseur') ?>"></textarea>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="/evaluations/deblocage" class="btn btn-link text-muted"><?= _('Annuler') ?></a>
                                <button type="submit" class="btn btn-primary"><?= _('Accorder le déblocage') ?></button>
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
