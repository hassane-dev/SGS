<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="max-w-2xl mx-auto mt-10">
    <div class="bg-white p-8 rounded-lg shadow-lg">
        <h1 class="text-3xl font-bold mb-6"><?= _('Setup: Multi-School') ?></h1>

        <form action="/setup/finish" method="POST">
            <input type="hidden" name="install_mode" value="multi">

            <fieldset class="border p-4 rounded-md">
                <legend class="text-xl font-semibold px-2"><?= _('National Administrator Account') ?></legend>
                <p class="text-sm text-gray-600 mb-4"><?= _('This account will have the rights to create and manage all high schools.') ?></p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label for="prenom" class="block text-sm font-bold mb-2"><?= _('First Name') ?></label>
                        <input type="text" name="prenom" id="prenom" class="shadow border rounded w-full py-2 px-3" required>
                    </div>
                    <div>
                        <label for="nom" class="block text-sm font-bold mb-2"><?= _('Last Name') ?></label>
                        <input type="text" name="nom" id="nom" class="shadow border rounded w-full py-2 px-3" required>
                    </div>
                    <div class="md:col-span-2">
                        <label for="email" class="block text-sm font-bold mb-2"><?= _('Email') ?></label>
                        <input type="email" name="email" id="email" class="shadow border rounded w-full py-2 px-3" required>
                    </div>
                    <div class="md:col-span-2">
                        <label for="mot_de_passe" class="block text-sm font-bold mb-2"><?= _('Password') ?></label>
                        <input type="password" name="mot_de_passe" id="mot_de_passe" class="shadow border rounded w-full py-2 px-3" required>
                    </div>
                </div>
            </fieldset>

            <div class="mt-8">
                <button type="submit" class="w-full bg-blue-500 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded">
                    <?= _('Complete Setup') ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
