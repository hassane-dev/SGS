<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<style>
    .timetable-grid {
        display: grid;
        grid-template-columns: 60px repeat(6, 1fr);
        grid-template-rows: 30px repeat(11, 1fr);
        gap: 2px;
    }
    .grid-cell {
        background-color: #f9f9f9;
        border: 1px solid #eee;
        padding: 4px;
        min-height: 60px;
    }
    .grid-header {
        font-weight: bold;
        background-color: #f0f0f0;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .course-block {
        background-color: #e3f2fd;
        border-left: 3px solid #2196f3;
        font-size: 0.8em;
        overflow: hidden;
    }
</style>

<div class="container mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold"><?= _('Timetable') ?></h2>
        <a href="/emploi-du-temps/create" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            <?= _('Add Course to Timetable') ?>
        </a>
    </div>

    <!-- Filter Bar -->
    <div class="mb-4">
        <form action="/emploi-du-temps" method="GET">
            <label for="classe_id" class="mr-2"><?= _('View timetable for class') ?>:</label>
            <select name="classe_id" id="classe_id" onchange="this.form.submit()" class="p-2 border rounded">
                <?php foreach ($classes as $classe): ?>
                    <option value="<?= $classe['id_classe'] ?>" <?= ($view_classe_id == $classe['id_classe']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($classe['nom_classe']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <div class="bg-white shadow-md rounded p-4">
        <div class="timetable-grid">
            <!-- Headers -->
            <div class="grid-header"></div>
            <?php $days = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi']; ?>
            <?php foreach ($days as $day): ?>
                <div class="grid-header"><?= _($day) ?></div>
            <?php endforeach; ?>

            <!-- Timetable Body -->
            <?php foreach ($timetable_grid as $hour => $row): ?>
                <div class="grid-header"><?= $hour ?></div>
                <?php foreach ($days as $day): ?>
                    <div class="grid-cell">
                        <?php if (isset($row[$day]) && $entry = $row[$day]): ?>
                            <div class="course-block p-1 rounded">
                                <strong><?= htmlspecialchars($entry['nom_matiere']) ?></strong><br>
                                <span><?= htmlspecialchars($entry['prof_prenom'] . ' ' . $entry['prof_nom']) ?></span><br>
                                <em class="text-xs"><?= htmlspecialchars($entry['nom_salle']) ?></em>
                                <form action="/emploi-du-temps/destroy" method="POST" onsubmit="return confirm('<?= _('Are you sure?') ?>');" class="text-right">
                                    <input type="hidden" name="id" value="<?= $entry['id'] ?>">
                                    <button type="submit" class="text-red-500 text-xs hover:underline">x</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
