<?php
$evaluationColumns = $bulletin['evaluation_columns'] ?? [];
?>
<table class="table table-bordered align-middle">
    <thead class="thead-light table-light text-center">
        <tr>
            <th class="text-start" style="min-width: 180px;"><?= _('Matières') ?></th>
            <?php if (!empty($evaluationColumns)): ?>
                <?php foreach ($evaluationColumns as $col): ?>
                    <th style="min-width: 80px;"><?= htmlspecialchars($col['label']) ?></th>
                <?php endforeach; ?>
            <?php endif; ?>
            <th style="min-width: 90px;"><?= _('Moyenne / 20') ?></th>
            <th style="min-width: 70px;"><?= _('Coef') ?></th>
            <th style="min-width: 100px;"><?= _('Total Points') ?></th>
            <th class="text-start" style="min-width: 200px;"><?= _("Appréciations de l'enseignant") ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($bulletin['matieres'] as $matiere): ?>
        <tr>
            <td class="text-start"><strong><?= htmlspecialchars($matiere['nom']) ?></strong></td>
            <?php if (!empty($evaluationColumns)): ?>
                <?php foreach ($evaluationColumns as $col): ?>
                    <?php
                    $val = $matiere['evaluation_values'][$col['key']] ?? null;
                    ?>
                    <td class="text-center">
                        <?= ($val !== null) ? number_format((float)$val, 2) : '-' ?>
                    </td>
                <?php endforeach; ?>
            <?php endif; ?>
            <td class="text-center font-weight-bold"><?= number_format($matiere['note'], 2) ?></td>
            <td class="text-center"><?= htmlspecialchars($matiere['coefficient']) ?></td>
            <td class="text-center font-weight-bold"><?= number_format($matiere['total_points'], 2) ?></td>
            <td class="text-start"><?= htmlspecialchars($matiere['appreciation'] ?? '') ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot class="font-weight-bold table-secondary">
        <tr>
            <td class="text-start"><?= _('Totaux') ?></td>
            <?php if (!empty($evaluationColumns)): ?>
                <td colspan="<?= count($evaluationColumns) ?>"></td>
            <?php endif; ?>
            <td></td>
            <td class="text-center"><?= htmlspecialchars($bulletin['total_coefficients']) ?></td>
            <td class="text-center"><?= number_format($bulletin['total_points'], 2) ?></td>
            <td></td>
        </tr>
    </tfoot>
</table>