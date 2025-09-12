<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="max-w-2xl mx-auto mt-10">
    <div class="bg-white p-8 rounded-lg shadow-lg text-center">
        <h1 class="text-3xl font-bold mb-4"><?= _('Welcome to Application Setup') ?></h1>
        <p class="text-gray-600 mb-8"><?= _('Please choose the installation mode for your application.') ?></p>

        <form action="/setup/choice" method="POST">
            <div class="space-y-4">
                <label class="block p-4 border rounded-lg hover:bg-gray-100 cursor-pointer">
                    <input type="radio" name="install_mode" value="single" class="mr-2" checked>
                    <strong class="text-lg"><?= _('Single School Installation') ?></strong>
                    <p class="text-sm text-gray-500"><?= _('For a private or semi-public school managing only its own data.') ?></p>
                </label>

                <label class="block p-4 border rounded-lg hover:bg-gray-100 cursor-pointer">
                    <input type="radio" name="install_mode" value="multi" class="mr-2">
                    <strong class="text-lg"><?= _('Multi-School Installation') ?></strong>
                    <p class="text-sm text-gray-500"><?= _('For national or regional administration managing multiple schools.') ?></p>
                </label>
            </div>

            <div class="mt-8">
                <button type="submit" class="w-full bg-blue-500 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded">
                    <?= _('Continue') ?> &rarr;
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
