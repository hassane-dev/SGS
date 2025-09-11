<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container mx-auto">
    <h2 class="text-2xl font-bold mb-4"><?= _('Select a Class') ?></h2>

    <div class="bg-white p-6 rounded-lg shadow-md">
        <ul class="space-y-2">
            <?php foreach ($classes as $classe): ?>
                <li>
                    <a href="/emploi-du-temps?classe_id=<?= $classe['id_classe'] ?>" class="text-blue-500 hover:underline">
                        <?= htmlspecialchars($classe['nom_classe']) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
