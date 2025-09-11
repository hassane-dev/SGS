<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container mx-auto">
    <h2 class="text-2xl font-bold mb-4">Emploi du Temps - <?= htmlspecialchars($classe['nom_classe']) ?></h2>

    <div class="mb-4">
        <!-- TODO: Add a class selector dropdown -->
        <a href="/emploi-du-temps/new?classe_id=<?= $classe['id_classe'] ?>" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            <?= _('Add Entry') ?>
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <?php
        $jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi'];
        foreach ($jours as $jour):
        ?>
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="font-bold text-lg mb-2 border-b pb-2"><?= _($jour) ?></h3>
                <div class="space-y-2">
                    <?php if (isset($grouped_timetable[$jour])): ?>
                        <?php foreach ($grouped_timetable[$jour] as $entry): ?>
                            <div class="p-2 rounded border border-gray-200 bg-gray-50">
                                <p class="font-semibold"><?= htmlspecialchars($entry['nom_matiere']) ?></p>
                                <p class="text-sm text-gray-600"><?= htmlspecialchars(substr($entry['heure_debut'], 0, 5)) ?> - <?= htmlspecialchars(substr($entry['heure_fin'], 0, 5)) ?></p>
                                <p class="text-sm text-gray-600"><?= _('Prof:') ?> <?= htmlspecialchars($entry['prenom'] . ' ' . $entry['nom_professeur']) ?></p>
                                <p class="text-sm text-gray-600"><?= _('Salle:') ?> <?= htmlspecialchars($entry['nom_salle']) ?></p>
                                <?php if ($entry['modifiable']): ?>
                                    <a href="/emploi-du-temps/edit?id=<?= $entry['id'] ?>" class="text-xs text-blue-500 hover:underline"><?= _('Edit') ?></a>
                                    <form action="/emploi-du-temps/destroy" method="POST" class="inline-block ml-2" onsubmit="return confirm('<?= _('Are you sure?') ?>');">
                                        <input type="hidden" name="id" value="<?= $entry['id'] ?>">
                                        <button type="submit" class="text-xs text-red-500 hover:underline"><?= _('Delete') ?></button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-gray-500"><?= _('No classes scheduled.') ?></p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
