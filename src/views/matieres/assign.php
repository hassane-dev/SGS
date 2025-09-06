<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="max-w-2xl mx-auto">
    <h2 class="text-2xl font-bold mb-2"><?= _('Assign Subjects') ?></h2>
    <p class="text-lg text-gray-600 mb-6"><?= _('For class') ?>: <span class="font-semibold"><?= htmlspecialchars($classe['nom_classe']) ?></span></p>

    <div class="bg-white p-8 rounded-lg shadow-lg">
        <form action="/matieres/assign" method="POST">
            <input type="hidden" name="class_id" value="<?= htmlspecialchars($classe['id_classe']) ?>">

            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                <?php foreach ($all_matieres as $matiere): ?>
                    <label class="flex items-center p-2 rounded-lg border hover:bg-gray-100">
                        <input type="checkbox" name="matieres[]" value="<?= $matiere['id_matiere'] ?>"
                               class="form-checkbox h-5 w-5 text-blue-600"
                               <?= in_array($matiere['id_matiere'], $assigned_matieres_ids) ? 'checked' : '' ?>>
                        <span class="ml-3 text-gray-700"><?= htmlspecialchars($matiere['nom_matiere']) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>

            <div class="mt-8 flex justify-end gap-4">
                <a href="/classes" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"><?= _('Cancel') ?></a>
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    <?= _('Save Assignments') ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
