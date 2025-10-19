<table class="table table-bordered">
    <thead class="thead-light">
        <tr>
            <th>Matières</th>
            <th>Note / 20</th>
            <th>Coefficient</th>
            <th>Total (Note x Coef)</th>
            <th>Appréciations de l'enseignant</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($bulletin['matieres'] as $matiere): ?>
        <tr>
            <td><?= htmlspecialchars($matiere['nom']) ?></td>
            <td><?= number_format($matiere['note'], 2) ?></td>
            <td><?= htmlspecialchars($matiere['coefficient']) ?></td>
            <td><?= number_format($matiere['total_points'], 2) ?></td>
            <td><?= htmlspecialchars($matiere['appreciation']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot class="font-weight-bold">
        <tr>
            <td>Totaux</td>
            <td></td>
            <td><?= htmlspecialchars($bulletin['total_coefficients']) ?></td>
            <td><?= number_format($bulletin['total_points'], 2) ?></td>
            <td></td>
        </tr>
    </tfoot>
</table>