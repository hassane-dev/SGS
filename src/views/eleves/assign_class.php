<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= _('Assigner une Classe à l\'Élève') ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="mb-4"><?= _('Élève') ?>: <?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></h4>

                        <form id="assign-class-form" action="/eleves/process-assignment" method="POST">
                            <input type="hidden" name="eleve_id" value="<?= $eleve['id_eleve'] ?>">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="cycle_id" class="form-label"><?= _('Cycle d\'Études') ?></label>
                                    <select id="cycle_id" name="cycle_id" class="form-select" required>
                                        <option value=""><?= _('-- Sélectionner un cycle --') ?></option>
                                        <?php foreach ($cycles as $cycle): ?>
                                            <option value="<?= $cycle['id_cycle'] ?>"><?= htmlspecialchars($cycle['nom_cycle']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="niveau" class="form-label"><?= _('Niveau') ?></label>
                                    <select id="niveau" name="niveau" class="form-select" required disabled>
                                        <option value=""><?= _('-- Sélectionner un niveau --') ?></option>
                                    </select>
                                </div>

                                <div class="col-md-6" id="serie-container" style="display: none;">
                                    <label for="serie" class="form-label"><?= _('Série') ?></label>
                                    <select id="serie" name="serie" class="form-select" disabled>
                                        <option value=""><?= _('-- Sélectionner une série --') ?></option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="numero" class="form-label"><?= _('Numéro de la Classe') ?></label>
                                    <select id="numero" name="numero" class="form-select" required disabled>
                                        <option value=""><?= _('-- Sélectionner un numéro --') ?></option>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-4 d-flex justify-content-end">
                                <a href="/eleves" class="btn btn-secondary me-2"><?= _('Annuler') ?></a>
                                <button type="submit" class="btn btn-primary"><?= _('Assigner la Classe') ?></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cycleSelect = document.getElementById('cycle_id');
    const niveauSelect = document.getElementById('niveau');
    const serieContainer = document.getElementById('serie-container');
    const serieSelect = document.getElementById('serie');
    const numeroSelect = document.getElementById('numero');
    const lyceeId = <?= Auth::getLyceeId() ?>;

    function resetSelect(select, defaultOptionText) {
        select.innerHTML = `<option value="">${defaultOptionText}</option>`;
        select.disabled = true;
    }

    cycleSelect.addEventListener('change', function() {
        const cycleId = this.value;
        const selectedCycleText = this.options[this.selectedIndex].text;

        resetSelect(niveauSelect, '<?= _('-- Sélectionner un niveau --') ?>');
        resetSelect(serieSelect, '<?= _('-- Sélectionner une série --') ?>');
        resetSelect(numeroSelect, '<?= _('-- Sélectionner un numéro --') ?>');
        serieContainer.style.display = 'none';

        if (cycleId) {
            fetch(`/classes/get-niveaux?cycle_id=${cycleId}&lycee_id=${lyceeId}`)
                .then(response => response.json())
                .then(data => {
                    niveauSelect.disabled = false;
                    data.forEach(niveau => {
                        const option = new Option(niveau, niveau);
                        niveauSelect.add(option);
                    });
                });

            if (selectedCycleText.toLowerCase() === 'lycée') {
                serieContainer.style.display = 'block';
            }
        }
    });

    niveauSelect.addEventListener('change', function() {
        const niveau = this.value;
        const selectedCycleText = cycleSelect.options[cycleSelect.selectedIndex].text;

        resetSelect(serieSelect, '<?= _('-- Sélectionner une série --') ?>');
        resetSelect(numeroSelect, '<?= _('-- Sélectionner un numéro --') ?>');

        if (niveau && selectedCycleText.toLowerCase() === 'lycée') {
            fetch(`/classes/get-series?niveau=${niveau}&lycee_id=${lyceeId}`)
                .then(response => response.json())
                .then(data => {
                    serieSelect.disabled = false;
                    data.forEach(serie => {
                        const option = new Option(serie, serie);
                        serieSelect.add(option);
                    });
                });
        } else if (niveau) {
            fetch(`/classes/get-numeros?niveau=${niveau}&lycee_id=${lyceeId}`)
                .then(response => response.json())
                .then(data => {
                    numeroSelect.disabled = false;
                    data.forEach(numero => {
                        const option = new Option(numero, numero);
                        numeroSelect.add(option);
                    });
                });
        }
    });

    serieSelect.addEventListener('change', function() {
        const serie = this.value;
        const niveau = niveauSelect.value;

        resetSelect(numeroSelect, '<?= _('-- Sélectionner un numéro --') ?>');

        if (serie && niveau) {
            fetch(`/classes/get-numeros?niveau=${niveau}&serie=${serie}&lycee_id=${lyceeId}`)
                .then(response => response.json())
                .then(data => {
                    numeroSelect.disabled = false;
                    data.forEach(numero => {
                        const option = new Option(numero, numero);
                        numeroSelect.add(option);
                    });
                });
        }
    });
});
</script>
