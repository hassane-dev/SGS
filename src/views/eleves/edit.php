<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="max-w-2xl mx-auto">
    <h2 class="text-2xl font-bold mb-6">Modifier la Fiche de l'Élève</h2>

    <div class="bg-white p-8 rounded-lg shadow-lg">
        <form action="/eleves/update" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id_eleve" value="<?= htmlspecialchars($eleve['id_eleve']) ?>">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <div>
                    <label for="prenom" class="block text-gray-700 text-sm font-bold mb-2">Prénom</label>
                    <input type="text" name="prenom" id="prenom" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value="<?= htmlspecialchars($eleve['prenom']) ?>" required>
                </div>
                <div>
                    <label for="nom" class="block text-gray-700 text-sm font-bold mb-2">Nom</label>
                    <input type="text" name="nom" id="nom" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value="<?= htmlspecialchars($eleve['nom']) ?>" required>
                </div>

                <div class="md:col-span-2">
                    <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email</label>
                    <input type="email" name="email" id="email" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value="<?= htmlspecialchars($eleve['email']) ?>">
                </div>

                <div>
                    <label for="date_naissance" class="block text-gray-700 text-sm font-bold mb-2">Date de Naissance</label>
                    <input type="date" name="date_naissance" id="date_naissance" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value="<?= htmlspecialchars($eleve['date_naissance']) ?>">
                </div>

                <div>
                    <label for="telephone" class="block text-gray-700 text-sm font-bold mb-2">Téléphone</label>
                    <input type="tel" name="telephone" id="telephone" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value="<?= htmlspecialchars($eleve['telephone']) ?>">
                </div>

                <div class="md:col-span-2">
                    <label for="photo" class="block text-gray-700 text-sm font-bold mb-2">Changer la Photo</label>
                    <?php if (!empty($eleve['photo'])): ?>
                        <div class="mb-2">
                            <img src="<?= htmlspecialchars($eleve['photo']) ?>" alt="Photo actuelle" class="h-20 w-20 rounded-full object-cover">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="photo" id="photo" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                </div>

            </div>

            <div class="mt-8 flex justify-end gap-4">
                <a href="/eleves" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">Annuler</a>
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Mettre à jour
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
