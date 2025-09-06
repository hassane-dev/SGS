<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="max-w-md mx-auto mt-10">
    <div class="bg-white p-8 rounded-lg shadow-lg">
        <h2 class="text-2xl font-bold mb-6 text-center"><?= _('Login') ?></h2>

        <?php if (isset($_GET['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold"><?= _('Error!') ?></strong>
                <span class="block sm:inline"><?= _('Incorrect email or password.') ?></span>
            </div>
        <?php endif; ?>

        <form action="/login" method="POST">
            <div class="mb-4">
                <label for="email" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Email') ?></label>
                <input type="email" name="email" id="email"
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                       required
                       value="HasMixiOne@mine.io">
            </div>
            <div class="mb-6">
                <label for="password" class="block text-gray-700 text-sm font-bold mb-2"><?= _('Password') ?></label>
                <input type="password" name="password" id="password"
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline"
                       required
                       value="H@s7511mat9611">
            </div>
            <div class="flex items-center justify-between">
                <button type="submit"
                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full">
                    <?= _('Login') ?>
                </button>
            </div>
        </form>
    </div>
</div>


<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
