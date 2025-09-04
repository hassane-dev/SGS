<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="max-w-4xl mx-auto">
    <h2 class="text-2xl font-bold mb-2">Saisie des Notes</h2>
    <p class="text-lg text-gray-600 mb-6">
        Classe: <span class="font-semibold"><?= htmlspecialchars($classe['nom_classe']) ?></span> |
        Matière: <span class="font-semibold"><?= htmlspecialchars($matiere['nom_matiere']) ?></span> |
        Type: <span class="font-semibold"><?= htmlspecialchars(ucfirst($type)) ?></span>
    </p>

    <div class="bg-white p-8 rounded-lg shadow-lg">
        <form action="/notes/save" method="POST">
            <input type="hidden" name="class_id" value="<?= htmlspecialchars($classe['id_classe']) ?>">
            <input type="hidden" name="matiere_id" value="<?= htmlspecialchars($matiere['id_matiere']) ?>">
            <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">

            <table class="min-w-full table-auto">
                <thead class="bg-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom de l'Élève</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider" style="width: 150px;">Note</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($students as $student): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?= htmlspecialchars($student['prenom'] . ' ' . $student['nom']) ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input type="number" step="0.01" min="0" max="20"
                                       name="notes[<?= $student['id_eleve'] ?>]"
                                       value="<?= htmlspecialchars($student['note'] ?? '') ?>"
                                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="mt-8 flex justify-end gap-4">
                <a href="/notes" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">Annuler</a>
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Enregistrer les Notes
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
