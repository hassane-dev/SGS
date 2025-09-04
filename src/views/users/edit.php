<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="max-w-2xl mx-auto">
    <h2 class="text-2xl font-bold mb-6">Modifier un Utilisateur</h2>

    <div class="bg-white p-8 rounded-lg shadow-lg">
        <form action="/users/update" method="POST">
            <input type="hidden" name="id_user" value="<?= htmlspecialchars($user['id_user']) ?>">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <div>
                    <label for="prenom" class="block text-gray-700 text-sm font-bold mb-2">Prénom</label>
                    <input type="text" name="prenom" id="prenom" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value="<?= htmlspecialchars($user['prenom']) ?>" required>
                </div>
                <div>
                    <label for="nom" class="block text-gray-700 text-sm font-bold mb-2">Nom</label>
                    <input type="text" name="nom" id="nom" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value="<?= htmlspecialchars($user['nom']) ?>" required>
                </div>

                <div class="md:col-span-2">
                    <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email</label>
                    <input type="email" name="email" id="email" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>

                <div class="md:col-span-2">
                    <label for="mot_de_passe" class="block text-gray-700 text-sm font-bold mb-2">Nouveau Mot de passe</label>
                    <input type="password" name="mot_de_passe" id="mot_de_passe" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" placeholder="Laisser vide pour ne pas changer">
                </div>

                <div>
                    <label for="role" class="block text-gray-700 text-sm font-bold mb-2">Rôle</label>
                    <select name="role" id="role" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
                        <option value="enseignant" <?= $user['role'] === 'enseignant' ? 'selected' : '' ?>>Enseignant</option>
                        <option value="surveillant" <?= $user['role'] === 'surveillant' ? 'selected' : '' ?>>Surveillant</option>
                        <option value="censeur" <?= $user['role'] === 'censeur' ? 'selected' : '' ?>>Censeur</option>
                        <?php if (Auth::get('role') === 'super_admin_national'): ?>
                        <option value="admin_local" <?= $user['role'] === 'admin_local' ? 'selected' : '' ?>>Administrateur Local</option>
                        <option value="admin_regional" <?= $user['role'] === 'admin_regional' ? 'selected' : '' ?>>Administrateur Régional</option>
                        <?php endif; ?>
                    </select>
                </div>

                <?php if (Auth::get('role') === 'super_admin_national'): ?>
                <div>
                    <label for="lycee_id" class="block text-gray-700 text-sm font-bold mb-2">Lycée</label>
                    <select name="lycee_id" id="lycee_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                        <option value="">-- Aucun --</option>
                        <?php foreach ($lycees as $lycee): ?>
                            <option value="<?= $lycee['id_lycee'] ?>" <?= $user['lycee_id'] == $lycee['id_lycee'] ? 'selected' : '' ?>><?= htmlspecialchars($lycee['nom_lycee']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php else: ?>
                    <input type="hidden" name="lycee_id" value="<?= htmlspecialchars($user['lycee_id']) ?>">
                <?php endif; ?>

                <div class="md:col-span-2">
                     <label class="flex items-center">
                        <input type="checkbox" name="actif" value="1" <?= !empty($user['actif']) ? 'checked' : '' ?> class="form-checkbox h-5 w-5 text-blue-600">
                        <span class="ml-2 text-gray-700">Compte Actif</span>
                    </label>
                </div>

            </div>

            <div class="mt-8 flex justify-end gap-4">
                <a href="/users" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">Annuler</a>
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Mettre à jour
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
