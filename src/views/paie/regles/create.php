<?php
// src/views/paie/regles/create.php
$title = _("Créer une Nouvelle Règle de Paie");
require_once __DIR__ . '/../../layouts/header_able.php';
?>

<div class="pc-container">
  <div class="pc-content">

    <div class="page-header mb-4">
      <div class="page-block">
        <div class="row align-items-center">
          <div class="col-md-8">
            <div class="page-header-title">
              <h4 class="mb-1 text-primary fw-bold">
                <i class="ph-duotone ph-plus-circle me-2"></i><?= htmlspecialchars($title) ?>
              </h4>
            </div>
            <ul class="breadcrumb mb-0">
              <li class="breadcrumb-item"><a href="/paie/periodes"><?= _('Paie') ?></a></li>
              <li class="breadcrumb-item"><a href="/paie/regles"><?= _('Configuration des Règles') ?></a></li>
              <li class="breadcrumb-item active"><?= _('Créer') ?></li>
            </ul>
          </div>
          <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <a href="/paie/regles" class="btn btn-outline-secondary d-inline-flex align-items-center gap-2">
              <i class="ph-duotone ph-arrow-left fs-5"></i>
              <span><?= _("Retour au catalogue") ?></span>
            </a>
          </div>
        </div>
      </div>
    </div>

    <?php if (!empty($_SESSION['error_message'])): ?>
      <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
        <i class="ph-duotone ph-warning-circle me-2 fs-5 align-middle"></i>
        <?= htmlspecialchars($_SESSION['error_message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
      <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <form action="/paie/regles/store" method="POST" id="ruleForm">
      <div class="row g-4">

        <!-- Colonne Principale: Identification & Mode -->
        <div class="col-lg-8">
          <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
              <h5 class="mb-0 fw-semibold text-dark"><i class="ph-duotone ph-info me-2 text-primary"></i><?= _("Identification de la Règle") ?></h5>
            </div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-4">
                  <label class="form-label fw-bold"><?= _("Pays / Code Juridiction") ?> <span class="text-danger">*</span></label>
                  <input type="text" name="pays_code" class="form-control" value="RCA" required placeholder="Ex: RCA, CMR, GAB">
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-bold"><?= _("Code Unique Règle") ?> <span class="text-danger">*</span></label>
                  <input type="text" name="code_regle" class="form-control font-monospace" required placeholder="Ex: CNSS_SALARIALE, IUTS_IMPOT">
                </div>
                <div class="col-md-4">
                  <label class="form-label fw-bold"><?= _("Ordre d'Application") ?> <span class="text-danger">*</span></label>
                  <input type="number" name="ordre_application" class="form-control" value="100" min="1" step="1" required>
                  <div class="form-text small"><?= _("Séquence de calcul (100=Prélèvements, 200=Impôts)") ?></div>
                </div>

                <div class="col-md-12">
                  <label class="form-label fw-bold"><?= _("Libellé de la Règle") ?> <span class="text-danger">*</span></label>
                  <input type="text" name="libelle" class="form-control" required placeholder="Ex: Cotisation Sécurité Sociale (Part Salariale)">
                </div>

                <div class="col-md-4">
                  <label class="form-label fw-bold"><?= _("Catégorie") ?> <span class="text-danger">*</span></label>
                  <select name="categorie" class="form-select" required>
                    <option value="cotisation_salariale"><?= _("Cotisation Salariale") ?></option>
                    <option value="cotisation_patronale"><?= _("Cotisation Patronale") ?></option>
                    <option value="impot"><?= _("Impôt & Taxe") ?></option>
                    <option value="retenue"><?= _("Retenue / Déduction") ?></option>
                  </select>
                </div>

                <div class="col-md-4">
                  <label class="form-label fw-bold"><?= _("Base de Calcul (Assiette)") ?> <span class="text-danger">*</span></label>
                  <select name="base_calcul_type" class="form-select" required>
                    <option value="brut_total"><?= _("Salaire Brut Total") ?></option>
                    <option value="salaire_base"><?= _("Salaire de Base Uniquement") ?></option>
                    <option value="net_imposable_provisoire"><?= _("Net Imposable Provisoire (Brut - Cotis. Salariale)") ?></option>
                    <option value="heures_validees"><?= _("Total Heures Pédagogiques Validées") ?></option>
                  </select>
                </div>

                <div class="col-md-4">
                  <label class="form-label fw-bold"><?= _("Mode de Calcul") ?> <span class="text-danger">*</span></label>
                  <select name="mode_calcul" id="modeCalculSelect" class="form-select" required onchange="toggleModeCalculFields()">
                    <option value="pourcentage"><?= _("Pourcentage (%)") ?></option>
                    <option value="montant_fixe"><?= _("Montant Fixe") ?></option>
                    <option value="bareme_progressif"><?= _("Barème Progressif par Tranches") ?></option>
                  </select>
                </div>
              </div>
            </div>
          </div>

          <!-- Paramètres Numériques & Taux -->
          <div class="card shadow-sm border-0 mb-4" id="numericParamsCard">
            <div class="card-header bg-white py-3">
              <h5 class="mb-0 fw-semibold text-dark"><i class="ph-duotone ph-calculator me-2 text-primary"></i><?= _("Taux, Fixes & Plafonds") ?></h5>
            </div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-3">
                  <label class="form-label fw-semibold"><?= _("Taux Salarial (%)") ?></label>
                  <input type="number" step="0.0001" name="taux_par_defaut" class="form-control" value="0.0000">
                </div>
                <div class="col-md-3">
                  <label class="form-label fw-semibold"><?= _("Fixe Salarial (FCFA)") ?></label>
                  <input type="number" step="0.01" name="montant_fixe_salarial" class="form-control" value="0.00">
                </div>
                <div class="col-md-3">
                  <label class="form-label fw-semibold"><?= _("Taux Patronal (%)") ?></label>
                  <input type="number" step="0.0001" name="taux_patronal" class="form-control" value="0.0000">
                </div>
                <div class="col-md-3">
                  <label class="form-label fw-semibold"><?= _("Fixe Patronal (FCFA)") ?></label>
                  <input type="number" step="0.01" name="montant_fixe_patronal" class="form-control" value="0.00">
                </div>

                <div class="col-md-3">
                  <label class="form-label small text-muted"><?= _("Seuil Min. Exonéré") ?></label>
                  <input type="number" step="0.01" name="seuil_minimum" class="form-control" placeholder="<?= _("Optionnel") ?>">
                </div>
                <div class="col-md-3">
                  <label class="form-label small text-muted"><?= _("Plafond Max. Assiette") ?></label>
                  <input type="number" step="0.01" name="plafond_maximum" class="form-control" placeholder="<?= _("Optionnel") ?>">
                </div>
                <div class="col-md-3">
                  <label class="form-label small text-muted"><?= _("Abattement Fixe") ?></label>
                  <input type="number" step="0.01" name="abattement_forfaitaire" class="form-control" value="0.00">
                </div>
                <div class="col-md-3">
                  <label class="form-label small text-muted"><?= _("Abattement (%)") ?></label>
                  <input type="number" step="0.01" name="abattement_pourcentage" class="form-control" value="0.00">
                </div>
              </div>
            </div>
          </div>

          <!-- Tranches du Barème Progressif (Masqué si non-progressif) -->
          <div class="card shadow-sm border-0 mb-4 d-none" id="baremeCard">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
              <h5 class="mb-0 fw-semibold text-dark"><i class="ph-duotone ph-list-numbers me-2 text-warning"></i><?= _("Tranches du Barème Progressif (IUTS / Taxe)") ?></h5>
              <button type="button" class="btn btn-sm btn-outline-primary" onclick="addTrancheRow()">
                <i class="ph-duotone ph-plus me-1"></i><?= _("Ajouter une Tranche") ?>
              </button>
            </div>
            <div class="table-responsive">
              <table class="table table-bordered align-middle mb-0" id="tranchesTable">
                <thead class="table-light">
                  <tr>
                    <th><?= _("N°") ?></th>
                    <th><?= _("Limite Inférieure") ?> <span class="text-danger">*</span></th>
                    <th><?= _("Limite Supérieure (Laissez vide si indéfinie)") ?></th>
                    <th><?= _("Taux (%)") ?> <span class="text-danger">*</span></th>
                    <th><?= _("Montant Fixe") ?></th>
                    <th class="text-center"><?= _("Action") ?></th>
                  </tr>
                </thead>
                <tbody id="tranchesBody">
                  <!-- Tranches par défaut -->
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Colonne Secondaire: Portée & Période de Validité -->
        <div class="col-lg-4">
          <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
              <h5 class="mb-0 fw-semibold text-dark"><i class="ph-duotone ph-calendar-blank me-2 text-primary"></i><?= _("Période de Validité & Portée") ?></h5>
            </div>
            <div class="card-body">
              <div class="mb-3">
                <label class="form-label fw-bold"><?= _("Date Début Validité") ?> <span class="text-danger">*</span></label>
                <input type="date" name="date_debut_validite" class="form-control" value="<?= date('Y-01-01') ?>" required>
              </div>

              <div class="mb-3">
                <label class="form-label fw-bold"><?= _("Date Fin Validité") ?></label>
                <input type="date" name="date_fin_validite" class="form-control">
                <div class="form-text small"><?= _("Laissez vide pour une durée de validité indéfinie.") ?></div>
              </div>

              <div class="mb-3">
                <label class="form-label fw-bold"><?= _("Restriction par Type de Contrat") ?></label>
                <select name="type_contrat_id" class="form-select">
                  <option value=""><?= _("-- Tous les types de contrats --") ?></option>
                  <?php foreach ($typesContrat as $tc): ?>
                    <option value="<?= $tc['id_contrat'] ?>"><?= htmlspecialchars($tc['libelle']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <hr>

              <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg">
                  <i class="ph-duotone ph-floppy-disk me-2"></i><?= _("Enregistrer la Règle") ?>
                </button>
                <a href="/paie/regles" class="btn btn-outline-secondary"><?= _("Annuler") ?></a>
              </div>
            </div>
          </div>
        </div>

      </div>
    </form>

  </div>
</div>

<script>
let trancheIndex = 0;

function toggleModeCalculFields() {
  const mode = document.getElementById('modeCalculSelect').value;
  const baremeCard = document.getElementById('baremeCard');
  if (mode === 'bareme_progressif') {
    baremeCard.classList.remove('d-none');
    if (document.querySelectorAll('#tranchesBody tr').length === 0) {
      addTrancheRow(0, 50000, 0);
      addTrancheRow(50000, 150000, 10);
      addTrancheRow(150000, 300000, 15);
      addTrancheRow(300000, '', 20);
    }
  } else {
    baremeCard.classList.add('d-none');
  }
}

function addTrancheRow(inf = 0, sup = '', taux = 0) {
  const tbody = document.getElementById('tranchesBody');
  const tr = document.createElement('tr');
  trancheIndex++;
  tr.innerHTML = `
    <td class="text-center fw-bold">${trancheIndex}</td>
    <td><input type="number" step="0.01" name="tranches[${trancheIndex}][limite_inferieure]" class="form-control form-control-sm" value="${inf}" required></td>
    <td><input type="number" step="0.01" name="tranches[${trancheIndex}][limite_superieure]" class="form-control form-control-sm" value="${sup}" placeholder="Infini"></td>
    <td><input type="number" step="0.0001" name="tranches[${trancheIndex}][taux]" class="form-control form-control-sm" value="${taux}" required></td>
    <td><input type="number" step="0.01" name="tranches[${trancheIndex}][montant_fixe]" class="form-control form-control-sm" value="0.00"></td>
    <td class="text-center"><button type="button" class="btn btn-sm btn-light-danger" onclick="this.closest('tr').remove()"><i class="ph-duotone ph-trash"></i></button></td>
  `;
  tbody.appendChild(tr);
}
</script>

<?php require_once __DIR__ . '/../../layouts/footer_able.php'; ?>
