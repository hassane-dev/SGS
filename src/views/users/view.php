<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold"><?= _('Détail du membre du personnel') ?></h2>
        <a href="/users" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"><?= _('Retour à la liste') ?></a>
    </div>

    <div class="bg-white p-8 rounded-lg shadow-lg">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Left side with Photo -->
            <div class="md:col-span-1 flex flex-col items-center">
                <img src="<?= htmlspecialchars($user['photo'] ?? '/img/default-avatar.png') ?>" alt="Photo de profil" class="w-40 h-40 rounded-full object-cover border-4 border-gray-200 mb-4">
                <h3 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></h3>
                <p class="text-gray-600"><?= htmlspecialchars($user['fonction'] ?? 'Fonction non spécifiée') ?></p>
                <div class="mt-2">
                    <?php if ($user['actif']): ?>
                        <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                            <?= _('Actif') ?>
                        </span>
                    <?php else: ?>
                        <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                            <?= _('Inactif') ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right side with Details -->
            <div class="md:col-span-2">
                <!-- Personal Information -->
                <div class="mb-6">
                    <h4 class="font-bold text-lg text-gray-700 border-b pb-2 mb-3"><?= _('Informations Personnelles') ?></h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        <p><strong><?= _('Sexe') ?>:</strong> <?= htmlspecialchars($user['sexe'] ?? 'N/A') ?></p>
                        <p><strong><?= _('Date de Naissance') ?>:</strong> <?= htmlspecialchars($user['date_naissance'] ?? 'N/A') ?></p>
                        <p><strong><?= _('Lieu de Naissance') ?>:</strong> <?= htmlspecialchars($user['lieu_naissance'] ?? 'N/A') ?></p>
                        <p><strong><?= _('Téléphone') ?>:</strong> <?= htmlspecialchars($user['telephone'] ?? 'N/A') ?></p>
                        <p class="sm:col-span-2"><strong><?= _('Adresse') ?>:</strong> <?= htmlspecialchars($user['adresse'] ?? 'N/A') ?></p>
                    </div>
                </div>

                <!-- Professional Information -->
                <div class="mb-6">
                    <h4 class="font-bold text-lg text-gray-700 border-b pb-2 mb-3"><?= _('Informations Professionnelles') ?></h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        <p><strong><?= _('Rôle') ?>:</strong> <?= htmlspecialchars($role['nom_role'] ?? 'N/A') ?></p>
                        <p><strong><?= _('Type de Contrat') ?>:</strong> <?= htmlspecialchars($contrat['libelle'] ?? 'N/A') ?></p>
                        <p><strong><?= _('Date d\'embauche') ?>:</strong> <?= htmlspecialchars($user['date_embauche'] ?? 'N/A') ?></p>
                    </div>
                </div>

                <!-- Account Information -->
                <div>
                    <h4 class="font-bold text-lg text-gray-700 border-b pb-2 mb-3"><?= _('Informations du Compte') ?></h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                        <p><strong><?= _('Email') ?>:</strong> <?= htmlspecialchars($user['email']) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="mt-8 pt-6 border-t flex justify-end gap-4">
             <?php if (Auth::get('id') != $user['id_user']): ?>
                <a href="/users/edit?id=<?= $user['id_user'] ?>" class="bg-indigo-500 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded"><?= _('Modifier') ?></a>
                <form action="/users/destroy" method="POST" class="inline-block" onsubmit="return confirm('<?= _('Êtes-vous sûr de vouloir supprimer ce membre ?') ?>');">
                    <input type="hidden" name="id" value="<?= $user['id_user'] ?>">
                    <button type="submit" class="bg-red-600 hover:bg-red-800 text-white font-bold py-2 px-4 rounded"><?= _('Supprimer') ?></button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>