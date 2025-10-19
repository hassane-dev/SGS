<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Bulletin de Notes</h1>
        <div>
            <a href="#" class="btn btn-primary" onclick="window.print();">
                <i class="fas fa-print"></i> Imprimer / PDF
            </a>
             <a href="/bulletins" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body" id="bulletin-content">
            <div class="bulletin-header text-center mb-4">
                <h4>Lycée <?= htmlspecialchars($bulletin['eleve']['nom_lycee']) ?></h4>
                <h5>Année Académique <?= htmlspecialchars($bulletin['eleve']['annee_academique']) ?></h5>
                <h6>Bulletin de la Séquence : <?= htmlspecialchars($bulletin['sequence']['nom']) ?></h6>
            </div>

            <div class="student-info mb-4">
                <p><strong>Nom & Prénom :</strong> <?= htmlspecialchars($bulletin['eleve']['prenom'] . ' ' . $bulletin['eleve']['nom']) ?></p>
                <p><strong>Date de Naissance :</strong> <?= htmlspecialchars($bulletin['eleve']['date_naissance']) ?></p>
                <p><strong>Classe :</strong> <?= htmlspecialchars($bulletin['eleve']['nom_classe']) ?></p>
            </div>

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

            <div class="summary mt-4">
                <form action="/bulletins/appreciation/save" method="POST">
                    <input type="hidden" name="eleve_id" value="<?= $bulletin['eleve']['id_eleve'] ?>">
                    <input type="hidden" name="sequence_id" value="<?= $bulletin['sequence']['id'] ?>">
                    <input type="hidden" name="moyenne_generale" value="<?= $bulletin['moyenne_generale'] ?>">

                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Moyenne Générale :</strong> <span class="h5"><?= number_format($bulletin['moyenne_generale'], 2) ?> / 20</span></p>

                            <?php if (Auth::can('bulletin:validate')): ?>
                                <div class="form-group">
                                    <label for="rang"><strong>Rang de l'élève</strong></label>
                                    <input type="text" name="rang" id="rang" class="form-control" value="<?= htmlspecialchars($bulletin['bulletin_record']['rang'] ?? '') ?>">
                                </div>
                            <?php else: ?>
                                <p><strong>Rang :</strong> <?= htmlspecialchars($bulletin['bulletin_record']['rang'] ?? 'Non défini') ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <div class="border p-3">
                                <h5>Appréciation du Conseil de Classe</h5>
                                <?php if (Auth::can('bulletin:validate')): ?>
                                    <div class="form-group">
                                        <textarea name="appreciation" class="form-control" rows="3"><?= htmlspecialchars($bulletin['bulletin_record']['appreciation'] ?? '') ?></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label for="statut"><strong>Statut du bulletin</strong></label>
                                        <select name="statut" id="statut" class="form-control">
                                            <option value="provisoire" <?= ($bulletin['bulletin_record']['statut'] ?? '') == 'provisoire' ? 'selected' : '' ?>>Provisoire</option>
                                            <option value="valide" <?= ($bulletin['bulletin_record']['statut'] ?? '') == 'valide' ? 'selected' : '' ?>>Validé</option>
                                            <option value="publie" <?= ($bulletin['bulletin_record']['statut'] ?? '') == 'publie' ? 'selected' : '' ?>>Publié</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-success mt-2">Enregistrer l'Appréciation</button>
                                <?php else: ?>
                                    <p><?= htmlspecialchars($bulletin['bulletin_record']['appreciation'] ?? 'Aucune appréciation.') ?></p>
                                    <p><strong>Statut :</strong> <span class="badge badge-info"><?= ucfirst(htmlspecialchars($bulletin['bulletin_record']['statut'] ?? 'Provisoire')) ?></span></p>
                                <?php endif; ?>
                                <p class="mt-3"><strong>Le Chef d'établissement</strong></p>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    .card-body, .card-body * {
        visibility: visible;
    }
    .card-body {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
    .btn {
        display: none;
    }
}
</style>