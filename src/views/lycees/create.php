<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="max-w-2xl mx-auto">
    <h2 class="text-2xl font-bold mb-6">Ajouter un nouveau Lycée</h2>

    <div class="bg-white p-8 rounded-lg shadow-lg">
        <form action="/lycees/store" method="POST">
            <div class="grid grid-cols-1 gap-6">

                <div>
                    <label for="nom_lycee" class="block text-gray-700 text-sm font-bold mb-2">Nom du Lycée</label>
                    <input type="text" name="nom_lycee" id="nom_lycee" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
                </div>

                <div>
                    <label for="type_lycee" class="block text-gray-700 text-sm font-bold mb-2">Type de Lycée</label>
                    <select name="type_lycee" id="type_lycee" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
                        <option value="public">Public</option>
                        <option value="prive">Privé</option>
                        <option value="parapublic">Parapublic</option>
                    </select>
                </div>

                <div>
                    <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email</label>
                    <input type="email" name="email" id="email" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                </div>

                <div>
                    <label for="telephone" class="block text-gray-700 text-sm font-bold mb-2">Téléphone</label>
                    <input type="text" name="telephone" id="telephone" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                </div>

                <div>
                    <label for="adresse" class="block text-gray-700 text-sm font-bold mb-2">Adresse</label>
                    <textarea name="adresse" id="adresse" rows="3" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="ville" class="block text-gray-700 text-sm font-bold mb-2">Ville</label>
                        <input type="text" name="ville" id="ville" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>
                    <div>
                        <label for="quartier" class="block text-gray-700 text-sm font-bold mb-2">Quartier</label>
                        <input type="text" name="quartier" id="quartier" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>
                </div>

            </div>

            <div class="mt-8 flex justify-end gap-4">
                <a href="/lycees" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">Annuler</a>
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Enregistrer
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
