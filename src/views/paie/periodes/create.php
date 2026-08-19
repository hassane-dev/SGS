<?php
// src/views/paie/periodes/create.php
?>
<div class="pc-container">
  <div class="pc-content">
    <div class="page-header">
      <div class="page-block">
        <div class="row align-items-center">
          <div class="col-md-12">
            <h5 class="mb-0"><?= _("Créer une Nouvelle Période de Paie") ?></h5>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-8 mx-auto">
        <div class="card">
          <div class="card-header">
            <h5><?= _("Formulaire de Création") ?></h5>
          </div>
          <div class="card-body">
            <form action="/paie/periodes/store" method="POST">
              <div class="row mb-3">
                <div class="col-md-6">
                  <label class="form-label"><?= _("Mois") ?></label>
                  <select name="mois" class="form-select" required>
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                      <option value="<?= $m ?>" <?= $m == date('n') ? 'selected' : '' ?>><?= sprintf('%02d', $m) ?></option>
                    <?php endfor; ?>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label"><?= _("Année") ?></label>
                  <input type="number" name="annee" class="form-control" value="<?= date('Y') ?>" required />
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label"><?= _("Période Comptable Associée") ?></label>
                <select name="periode_comptable_id" class="form-select" required>
                  <?php if (!empty($comptaPeriodes)): ?>
                    <?php foreach ($comptaPeriodes as $cp): ?>
                      <option value="<?= $cp['id'] ?>"><?= htmlspecialchars($cp['date_debut']) ?> &rarr; <?= htmlspecialchars($cp['date_fin']) ?> (#<?= $cp['id'] ?>)</option>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <option value="1"><?= _("Période comptable par défaut (#1)") ?></option>
                  <?php endif; ?>
                </select>
              </div>

              <div class="row mb-3">
                <div class="col-md-6">
                  <label class="form-label"><?= _("Date de Début") ?></label>
                  <input type="date" name="date_debut" class="form-control" value="<?= date('Y-m-01') ?>" required />
                </div>
                <div class="col-md-6">
                  <label class="form-label"><?= _("Date de Fin") ?></label>
                  <input type="date" name="date_fin" class="form-control" value="<?= date('Y-m-t') ?>" required />
                </div>
              </div>

              <div class="d-flex justify-content-between">
                <a href="/paie/periodes" class="btn btn-outline-secondary"><?= _("Annuler") ?></a>
                <button type="submit" class="btn btn-primary"><?= _("Enregistrer Période") ?></button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
