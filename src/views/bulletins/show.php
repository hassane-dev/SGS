<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bulletin de Notes</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body { -webkit-print-color-adjust: exact; }
            .no-print { display: none; }
        }
        .table-bordered, .table-bordered th, .table-bordered td {
            border: 1px solid black;
        }
    </style>
</head>
<body class="bg-gray-100">

    <div class="container mx-auto my-8 p-8 bg-white shadow-lg" id="bulletin">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold"><?= htmlspecialchars($bulletin_data['info']['nom_lycee']) ?></h1>
            <h2 class="text-xl"><?= _('Report Card') ?> - <?= _('Academic Year') ?> <?= htmlspecialchars($bulletin_data['info']['annee_academique']) ?></h2>
        </div>

        <!-- Student Info -->
        <div class="mb-8 border p-4">
            <p><strong><?= _('Full Name') ?>:</strong> <?= htmlspecialchars($bulletin_data['info']['nom'] . ' ' . $bulletin_data['info']['prenom']) ?></p>
            <p><strong><?= _('Class') ?>:</strong> <?= htmlspecialchars($bulletin_data['info']['nom_classe'] . ' (' . $bulletin_data['info']['serie'] . ')') ?></p>
            <p><strong><?= _('Date of Birth') ?>:</strong> <?= htmlspecialchars(date('d/m/Y', strtotime($bulletin_data['info']['date_naissance']))) ?></p>
        </div>

        <!-- Grades Table -->
        <table class="w-full table-auto table-bordered">
            <thead class="bg-gray-200">
                <tr>
                    <th class="p-2"><?= _('Subjects') ?></th>
                    <th class="p-2"><?= _('Coeff.') ?></th>
                    <th class="p-2"><?= _('Homework Avg.') ?></th>
                    <th class="p-2"><?= _('Exam Grade') ?></th>
                    <th class="p-2"><?= _('Subject Avg.') ?></th>
                    <th class="p-2"><?= _('Total (Avg * Coeff)') ?></th>
                    <th class="p-2"><?= _('Appreciation') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bulletin_data['results'] as $result): ?>
                <tr>
                    <td class="p-2"><?= htmlspecialchars($result['nom_matiere']) ?></td>
                    <td class="p-2 text-center"><?= htmlspecialchars($result['coef']) ?></td>
                    <td class="p-2 text-center"><?= $result['moyenne_devoirs'] !== null ? number_format($result['moyenne_devoirs'], 2) : 'N/A' ?></td>
                    <td class="p-2 text-center"><?= $result['note_composition'] !== null ? number_format($result['note_composition'], 2) : 'N/A' ?></td>
                    <td class="p-2 text-center font-bold"><?= $result['moyenne_matiere'] !== null ? number_format($result['moyenne_matiere'], 2) : 'N/A' ?></td>
                    <td class="p-2 text-center"><?= $result['moyenne_matiere'] !== null ? number_format($result['moyenne_matiere'] * $result['coef'], 2) : 'N/A' ?></td>
                    <td class="p-2"></td> <!-- Placeholder for teacher comments -->
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Summary -->
        <div class="mt-8 flex justify-end">
            <div class="w-1/2 border p-4">
                <h3 class="text-lg font-bold mb-4"><?= _('Summary') ?></h3>
                <div class="flex justify-between">
                    <span><?= _('Overall Average') ?>:</span>
                    <span class="font-bold text-xl"><?= number_format($bulletin_data['moyenne_generale'], 2) ?> / 20</span>
                </div>
                <div class="flex justify-between mt-2">
                    <span><?= _('General Appreciation') ?>:</span>
                    <span class="font-bold"></span> <!-- Placeholder -->
                </div>
                 <div class="flex justify-between mt-2">
                    <span><?= _('Rank') ?>:</span>
                    <span class="font-bold"></span> <!-- Placeholder -->
                </div>
            </div>
        </div>

        <!-- Signatures -->
        <div class="mt-16 flex justify-between text-center">
            <div>
                <p><?= _('Parent\'s Signature') ?></p>
                <p class="mt-8">___________________</p>
            </div>
            <div>
                <p><?= _('Headmaster\'s Signature') ?></p>
                 <p class="mt-8">___________________</p>
            </div>
        </div>
    </div>

    <div class="text-center mt-8 no-print">
        <button onclick="window.print()" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            <?= _('Print Report Card') ?>
        </button>
        <a href="/eleves" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded ml-4">
            <?= _('Back') ?>
        </a>
    </div>

</body>
</html>
