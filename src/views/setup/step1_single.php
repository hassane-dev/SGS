<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="max-w-3xl mx-auto mt-10">
    <div class="bg-white p-8 rounded-lg shadow-lg">
        <h1 class="text-3xl font-bold mb-6"><?= _('Setup: Single School') ?></h1>

        <form action="/setup/finish" method="POST">
            <input type="hidden" name="install_mode" value="single">

            <!-- School Details -->
            <fieldset class="border p-4 rounded-md mb-6">
                <legend class="text-xl font-semibold px-2"><?= _('School Information') ?></legend>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label for="nom_lycee" class="block text-sm font-bold mb-2"><?= _('High School Name') ?></label>
                        <input type="text" name="nom_lycee" id="nom_lycee" class="shadow border rounded w-full py-2 px-3" required>
                    </div>
                    <div>
                        <label for="type_lycee" class="block text-sm font-bold mb-2"><?= _('High School Type') ?></label>
                        <select name="type_lycee" id="type_lycee" class="shadow border rounded w-full py-2 px-3" required>
                            <option value="prive"><?= _('Private') ?></option>
                            <option value="parapublic"><?= _('Parapublic') ?></option>
                            <option value="public"><?= _('Public') ?></option>
                        </select>
                    </div>
                     <div class="md:col-span-2">
                        <label for="annee_academique" class="block text-sm font-bold mb-2"><?= _('Current Academic Year') ?></label>
                        <input type="text" name="annee_academique" id="annee_academique" class="shadow border rounded w-full py-2 px-3" placeholder="Ex: 2024-2025" required>
                    </div>
                </div>
            </fieldset>

            <!-- Admin Account -->
            <fieldset class="border p-4 rounded-md">
                <legend class="text-xl font-semibold px-2"><?= _('Local Administrator Account') ?></legend>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label for="admin_prenom" class="block text-sm font-bold mb-2"><?= _('First Name') ?></label>
                        <input type="text" name="admin_prenom" id="admin_prenom" class="shadow border rounded w-full py-2 px-3" required>
                    </div>
                    <div>
                        <label for="admin_nom" class="block text-sm font-bold mb-2"><?= _('Last Name') ?></label>
                        <input type="text" name="admin_nom" id="admin_nom" class="shadow border rounded w-full py-2 px-3" required>
                    </div>
                    <div class="md:col-span-2">
                        <label for="admin_email" class="block text-sm font-bold mb-2"><?= _('Email') ?></label>
                        <input type="email" name="admin_email" id="admin_email" class="shadow border rounded w-full py-2 px-3" required>
                    </div>
                    <div class="md:col-span-2">
                        <label for="admin_pass" class="block text-sm font-bold mb-2"><?= _('Password') ?></label>
                        <input type="password" name="admin_pass" id="admin_pass" class="shadow border rounded w-full py-2 px-3" required>
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
