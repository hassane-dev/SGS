<?php
// src/views/paie/cahier_texte/index.php
?>
<div class="pc-container">
  <div class="pc-content">
    <div class="page-header">
      <div class="page-block">
        <div class="row align-items-center">
          <div class="col-md-12">
            <h5 class="mb-0"><?= _("Validations des Heures Pédagogiques - Cahier de Texte") ?></h5>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h5><?= _("Séances de Cours Validées pour la Paie") ?></h5>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th><?= _("Enseignant") ?></th>
                    <th><?= _("Date Cours") ?></th>
                    <th><?= _("Durée (Heures)") ?></th>
                    <th><?= _("Taux Horaire") ?></th>
                    <th><?= _("Montant") ?></th>
                    <th><?= _("Statut Validation") ?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($validations)): ?>
                    <tr>
                      <td colspan="6" class="text-center text-muted"><?= _("Aucune validation d'heures enregistrée.") ?></td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($validations as $v): ?>
                      <tr>
                        <td><strong><?= htmlspecialchars($v['nom'] . ' ' . $v['prenom']) ?></strong></td>
                        <td><?= htmlspecialchars($v['date_cours']) ?></td>
                        <td><?= number_format($v['duree_heures'], 2) ?> h</td>
                        <td><?= number_format($v['taux_horaire'], 2) ?> FCFA</td>
                        <td><strong><?= number_format($v['duree_heures'] * $v['taux_horaire'], 2) ?> FCFA</strong></td>
                        <td><span class="badge bg-success"><?= _("Validée") ?></span></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
