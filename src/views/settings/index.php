<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="max-w-4xl mx-auto">
    <h2 class="text-2xl font-bold mb-6">Paramètres Généraux</h2>

    <?php if (!empty($message)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?= htmlspecialchars($message) ?></span>
        </div>
    <?php endif; ?>

    <div class="bg-white p-8 rounded-lg shadow-lg">
        <form action="/settings" method="POST">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <!-- Nom du Lycée -->
                <div>
                    <label for="nom_lycee" class="block text-gray-700 text-sm font-bold mb-2">Nom du Lycée</label>
                    <input type="text" name="nom_lycee" id="nom_lycee" value="<?= htmlspecialchars($settings['nom_lycee'] ?? '') ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                </div>

                <!-- Type de Lycée -->
                <div>
                    <label for="type_lycee" class="block text-gray-700 text-sm font-bold mb-2">Type de Lycée</label>
                    <select name="type_lycee" id="type_lycee" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                        <option value="public" <?= ($settings['type_lycee'] ?? '') === 'public' ? 'selected' : '' ?>>Public</option>
                        <option value="prive" <?= ($settings['type_lycee'] ?? '') === 'prive' ? 'selected' : '' ?>>Privé</option>
                        <option value="parapublic" <?= ($settings['type_lycee'] ?? '') === 'parapublic' ? 'selected' : '' ?>>Parapublic</option>
                    </select>
                </div>

                <!-- Année Académique -->
                <div>
                    <label for="annee_academique" class="block text-gray-700 text-sm font-bold mb-2">Année Académique</label>
                    <input type="text" name="annee_academique" id="annee_academique" placeholder="e.g., 2024-2025" value="<?= htmlspecialchars($settings['annee_academique'] ?? '') ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                </div>

                <!-- Nombre de devoirs -->
                <div>
                    <label for="nombre_devoirs_par_trimestre" class="block text-gray-700 text-sm font-bold mb-2">Devoirs par Trimestre</label>
                    <input type="number" name="nombre_devoirs_par_trimestre" id="nombre_devoirs_par_trimestre" value="<?= htmlspecialchars($settings['nombre_devoirs_par_trimestre'] ?? '2') ?>" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                </div>

                <!-- Modalité de Paiement -->
                <div class="md:col-span-2">
                    <label for="modalite_paiement" class="block text-gray-700 text-sm font-bold mb-2">Modalité de Paiement</label>
                    <select name="modalite_paiement" id="modalite_paiement" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                        <option value="avant_inscription" <?= ($settings['modalite_paiement'] ?? '') === 'avant_inscription' ? 'selected' : '' ?>>Avant inscription</option>
                        <option value="apres_test" <?= ($settings['modalite_paiement'] ?? '') === 'apres_test' ? 'selected' : '' ?>>Après test d'entrée</option>
                        <option value="fractionne" <?= ($settings['modalite_paiement'] ?? '') === 'fractionne' ? 'selected' : '' ?>>Fractionné</option>
                    </select>
                </div>

                <!-- Checkboxes -->
                <div class="md:col-span-2 grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="multilingue_actif" value="1" <?= !empty($settings['multilingue_actif']) ? 'checked' : '' ?> class="form-checkbox h-5 w-5 text-blue-600">
                        <span class="ml-2 text-gray-700">Multilingue</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="biometrie_actif" value="1" <?= !empty($settings['biometrie_actif']) ? 'checked' : '' ?> class="form-checkbox h-5 w-5 text-blue-600">
                        <span class="ml-2 text-gray-700">Biométrie</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="confidentialite_nationale" value="1" <?= !empty($settings['confidentialite_nationale']) ? 'checked' : '' ?> class="form-checkbox h-5 w-5 text-blue-600">
                        <span class="ml-2 text-gray-700">Confidentialité Nationale</span>
                    </label>
                </div>
            </div>

            <div class="mt-8 flex justify-end">
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    Enregistrer les Paramètres
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
