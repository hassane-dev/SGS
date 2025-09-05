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
            <h2 class="text-xl">Bulletin de Notes - Année Académique <?= htmlspecialchars($bulletin_data['info']['annee_academique']) ?></h2>
        </div>

        <!-- Student Info -->
        <div class="mb-8 border p-4">
            <p><strong>Nom & Prénom:</strong> <?= htmlspecialchars($bulletin_data['info']['nom'] . ' ' . $bulletin_data['info']['prenom']) ?></p>
            <p><strong>Classe:</strong> <?= htmlspecialchars($bulletin_data['info']['nom_classe'] . ' (' . $bulletin_data['info']['serie'] . ')') ?></p>
            <p><strong>Date de Naissance:</strong> <?= htmlspecialchars(date('d/m/Y', strtotime($bulletin_data['info']['date_naissance']))) ?></p>
        </div>

        <!-- Grades Table -->
        <table class="w-full table-auto table-bordered">
            <thead class="bg-gray-200">
                <tr>
                    <th class="p-2">Matières</th>
                    <th class="p-2">Coef.</th>
                    <th class="p-2">Moyenne Devoirs</th>
                    <th class="p-2">Note Composition</th>
                    <th class="p-2">Moyenne Matière</th>
                    <th class="p-2">Total (Moy * Coef)</th>
                    <th class="p-2">Appréciation</th>
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
                <h3 class="text-lg font-bold mb-4">Résumé</h3>
                <div class="flex justify-between">
                    <span>Moyenne Générale:</span>
                    <span class="font-bold text-xl"><?= number_format($bulletin_data['moyenne_generale'], 2) ?> / 20</span>
                </div>
                <div class="flex justify-between mt-2">
                    <span>Appréciation Générale:</span>
                    <span class="font-bold"></span> <!-- Placeholder -->
                </div>
                 <div class="flex justify-between mt-2">
                    <span>Rang:</span>
                    <span class="font-bold"></span> <!-- Placeholder -->
                </div>
            </div>
        </div>

        <!-- Signatures -->
        <div class="mt-16 flex justify-between text-center">
            <div>
                <p>Signature du Parent</p>
                <p class="mt-8">___________________</p>
            </div>
            <div>
                <p>Le Proviseur</p>
                 <p class="mt-8">___________________</p>
            </div>
        </div>
    </div>

    <div class="text-center mt-8 no-print">
        <button onclick="window.print()" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            Imprimer le Bulletin
        </button>
        <a href="/eleves" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded ml-4">
            Retour
        </a>
    </div>

</body>
</html>
