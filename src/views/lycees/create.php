<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="max-w-2xl mx-auto">
    <h2 class="text-2xl font-bold mb-6"><?= _('Add New High School') ?></h2>

    <div class="bg-white p-8 rounded-lg shadow-lg">
        <form action="/lycees/store" method="POST" enctype="multipart/form-data">
    <?= csrf_field() ?>
            <div class="grid grid-cols-1 gap-6">

                <div>
                    <label for="nom_lycee" class="block text-gray-700 text-sm font-bold mb-2"><?= _('High School Name') ?></label>
                    <input type="text" name="nom_lycee" id="nom_lycee" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
                </div>

                <div>
                    <label for="logo" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Logo') ?></label>
                    <input type="file" name="logo" id="logo" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                </div>

                <div>
                    <label for="type_lycee" class="block text-gray-700 text-sm font-bold mb-2"><?= _('High School Type') ?></label>
                    <select name="type_lycee" id="type_lycee" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
                        <option value="public"><?= _('Public') ?></option>
                        <option value="prive"><?= _('Private') ?></option>
                        <option value="semi-public"><?= _('Semi-public') ?></option>
                    </select>
                </div>

                <div>
                    <label for="mode_cycle" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Structure des Cycles') ?></label>
                    <select name="mode_cycle" id="mode_cycle" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" onchange="renderLyceeCycles()">
                        <option value="separe_ceg_lycee" selected><?= _('Séparé (CEG / Collège + Lycée — 2 Cycles)') ?></option>
                        <option value="lycee_unique"><?= _('Lycée Unique (1 Cycle)') ?></option>
                    </select>
                </div>

                <div class="border-t pt-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2"><?= _('Configuration des Cycles Académiques') ?></label>
                    <div id="lyceeCyclesContainer" class="space-y-3">
                        <!-- Populated dynamically by JavaScript -->
                    </div>
                </div>

                <div>
                    <label for="email" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Email') ?></label>
                    <input type="email" name="email" id="email" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                </div>

                <div>
                    <label for="telephone" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Phone') ?></label>
                    <input type="text" name="telephone" id="telephone" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                </div>

                <div>
                    <label for="adresse" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Address') ?></label>
                    <textarea name="adresse" id="adresse" rows="3" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="ville" class="block text-gray-700 text-sm font-bold mb-2"><?= _('City') ?></label>
                        <input type="text" name="ville" id="ville" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>
                    <div>
                        <label for="quartier" class="block text-gray-700 text-sm font-bold mb-2"><?= _('District') ?></label>
                        <input type="text" name="quartier" id="quartier" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>
                </div>

            </div>

            <div class="mt-8 flex justify-end gap-4">
                <a href="/lycees" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"><?= _('Cancel') ?></a>
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    <?= _('Save') ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function renderLyceeCycles() {
    const mode = document.getElementById('mode_cycle').value;
    const container = document.getElementById('lyceeCyclesContainer');
    if (!container) return;

    container.innerHTML = '';

    if (mode === 'lycee_unique') {
        container.innerHTML = renderLyceeCycleRow(0, 'Lycée Unique', '6e', 'Terminale', '1');
    } else {
        container.innerHTML = renderLyceeCycleRow(0, 'CEG / Collège', '6e', '3e', '0') +
                              renderLyceeCycleRow(1, 'Lycée', '2nde', 'Terminale', '1');
    }
}

function renderLyceeCycleRow(index, defaultNom, defaultStart, defaultEnd, defaultSeries) {
    return `
        <div class="p-3 border rounded bg-gray-50 text-sm">
            <div class="font-bold text-gray-700 mb-2">Cycle ${index + 1} : ${defaultNom}</div>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <div>
                    <label class="block text-xs font-semibold mb-1"><?= _('Nom du Cycle') ?></label>
                    <input type="text" name="cycles[${index}][nom_cycle]" class="w-full border rounded p-1" value="${defaultNom}" required>
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1"><?= _('Niveau Début') ?></label>
                    <input type="text" name="cycles[${index}][niveau_debut]" class="w-full border rounded p-1" value="${defaultStart}">
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1"><?= _('Niveau Fin') ?></label>
                    <input type="text" name="cycles[${index}][niveau_fin]" class="w-full border rounded p-1" value="${defaultEnd}">
                </div>
                <div>
                    <label class="block text-xs font-semibold mb-1"><?= _('Séries') ?></label>
                    <select name="cycles[${index}][utilise_series]" class="w-full border rounded p-1" required>
                        <option value="0" ${defaultSeries === '0' ? 'selected' : ''}><?= _('Non (Collège)') ?></option>
                        <option value="1" ${defaultSeries === '1' ? 'selected' : ''}><?= _('Oui (Lycée)') ?></option>
                    </select>
                </div>
            </div>
        </div>
    `;
}

document.addEventListener('DOMContentLoaded', () => {
    renderLyceeCycles();
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
