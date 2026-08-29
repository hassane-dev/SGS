<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($context_title) ?></title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11pt;
            color: #222;
            margin: 0;
            padding: 10px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #222;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .header h2 {
            margin: 0 0 5px 0;
            text-transform: uppercase;
            font-size: 16pt;
        }
        .header h3 {
            margin: 0;
            font-size: 13pt;
            color: #444;
            font-weight: normal;
        }
        .meta-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 10pt;
        }
        table.timetable {
            width: 100%;
            border-collapse: collapse;
            text-align: center;
            table-layout: fixed;
        }
        table.timetable th, table.timetable td {
            border: 1px solid #444;
            padding: 6px;
            vertical-align: top;
            word-wrap: break-word;
        }
        table.timetable th {
            background-color: #e9ecef;
            font-weight: bold;
            font-size: 10pt;
        }
        .slot-time {
            font-weight: bold;
            background-color: #f8f9fa;
            font-size: 9pt;
            white-space: nowrap;
        }
        .course-box {
            background-color: #f0f4f8;
            border: 1px solid #a3b8cc;
            border-radius: 4px;
            padding: 4px;
            text-align: left;
            font-size: 9pt;
            margin-bottom: 4px;
        }
        .course-title {
            font-weight: bold;
            color: #0d6efd;
            margin-bottom: 2px;
        }
        .course-details {
            font-size: 8.5pt;
            color: #333;
        }
        .no-print {
            margin-bottom: 15px;
            text-align: right;
        }
        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <button onclick="window.print()" style="padding: 8px 16px; font-size: 12pt; cursor: pointer; background-color: #0d6efd; color: #fff; border: none; border-radius: 4px;">
            🖨️ Imprimer l'emploi du temps
        </button>
    </div>

    <div class="header">
        <h2><?= htmlspecialchars($context_title) ?></h2>
        <h3>Année Académique : <?= htmlspecialchars($active_year['nom'] ?? 'N/A') ?></h3>
    </div>

    <div class="meta-info">
        <div><strong>Établissement :</strong> SGS School Management</div>
        <div><strong>Date d'impression :</strong> <?= date('d/m/Y H:i') ?></div>
    </div>

    <?php if (empty($grid['intervals'])): ?>
        <p style="text-align: center; margin-top: 30px; font-style: italic;">Aucun cours programmé pour ce contexte.</p>
    <?php else: ?>
        <table class="timetable">
            <thead>
                <tr>
                    <th style="width: 12%;">Créneau</th>
                    <?php foreach ($grid['days'] as $day): ?>
                        <th><?= htmlspecialchars($day) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($grid['intervals'] as $slot): ?>
                    <?php $slotKey = $slot['label']; ?>
                    <tr>
                        <td class="slot-time"><?= htmlspecialchars($slotKey) ?></td>
                        <?php foreach ($grid['days'] as $day): ?>
                            <td>
                                <?php if (!empty($grid['grid'][$slotKey][$day])): ?>
                                    <?php foreach ($grid['grid'][$slotKey][$day] as $entry): ?>
                                        <div class="course-box">
                                            <div class="course-title"><?= htmlspecialchars($entry['nom_matiere']) ?></div>
                                            <div class="course-details">
                                                <strong>Classe :</strong> <?= htmlspecialchars(Classe::getFormattedName($entry)) ?><br>
                                                <strong>Ens. :</strong> <?= htmlspecialchars($entry['prof_prenom'] . ' ' . $entry['prof_nom']) ?><br>
                                                <?php if (!empty($entry['nom_salle'])): ?>
                                                    <strong>Salle :</strong> <?= htmlspecialchars($entry['nom_salle']) ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

</body>
</html>
