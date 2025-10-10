<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="max-w-2xl mx-auto">
    <h2 class="text-2xl font-bold mb-6"><?= _('Add New Student') ?></h2>

    <div class="bg-white p-8 rounded-lg shadow-lg">
        <form action="/eleves/store" method="POST" enctype="multipart/form-data">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <div>
                    <label for="prenom" class="block text-gray-700 text-sm font-bold mb-2"><?= _('First Name') ?></label>
                    <input type="text" name="prenom" id="prenom" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
                </div>
                <div>
                    <label for="nom" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Last Name') ?></label>
                    <input type="text" name="nom" id="nom" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" required>
                </div>

                <div class="md:col-span-2">
                    <label for="email" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Email') ?></label>
                    <input type="email" name="email" id="email" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                </div>

                <div>
                    <label for="date_naissance" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Date of Birth') ?></label>
                    <input type="date" name="date_naissance" id="date_naissance" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                </div>

                <div>
                    <label for="telephone" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Phone') ?></label>
                    <input type="tel" name="telephone" id="telephone" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                </div>

                <div class="md:col-span-2">
                    <label for="photo" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Photo') ?></label>
                    <input type="file" name="photo" id="photo" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                </div>

            </div>

            <div class="mt-8 flex justify-end gap-4">
                <a href="/eleves" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"><?= _('Cancel') ?></a>
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    <?= _('Save') ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
