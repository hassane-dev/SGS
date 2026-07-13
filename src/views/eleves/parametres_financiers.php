<?php
$title = "Paramètres Financiers - " . htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']);
ob_start();

require_once __DIR__ . '/../layouts/header_able.php';
require_once __DIR__ . '/../layouts/sidebar_able.php';

$frais_concernes_array = [];
if (!empty($params['frais_concernes'])) {
    $frais_concernes_array = json_decode($params['frais_concernes'], true);
    if (!is_array($frais_concernes_array)) {
        $frais_concernes_array = [];
    }
}
?>

<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h5 class="m-b-10"><?= htmlspecialchars($title) ?></h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/home">Tableau de Bord</a></li>
                            <li class="breadcrumb-item"><a href="/eleves">Élèves</a></li>
                            <li class="breadcrumb-item" aria-current="page">Paramètres Financiers</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- [ Main Content ] start -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="card mb-0">
                    <div class="card-body p-2">
                        <ul class="nav nav-pills" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link" href="/eleves/details?id=<?= $eleve['id_eleve'] ?>"><i class="ph-duotone ph-user me-2"></i>Dossier & Informations</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link active" href="/eleves/parametres-financiers?id=<?= $eleve['id_eleve'] ?>"><i class="ph-duotone ph-currency-dollar me-2"></i>Paramètres Financiers</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">

            <!-- Student Header Profile Info -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="position-relative d-inline-block mb-3">
                            <img src="<?= !empty($eleve['photo']) ? htmlspecialchars($eleve['photo']) : '/assets/img/placeholder-photo.png' ?>" alt="Contact" class="img-fluid rounded-circle" style="width: 120px; height: 120px; object-fit: cover; border: 3px solid #eee;">
                        </div>
                        <h4><?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></h4>
                        <p class="text-muted small mb-1">ID Élève: #<?= htmlspecialchars($eleve['id_eleve']) ?></p>
                        <p class="text-muted small">E-mail: <?= htmlspecialchars($eleve['email'] ?? 'N/A') ?></p>

                        <div class="mt-3">
                            <a href="/paiements/show/<?= $eleve['id_eleve'] ?>" class="btn btn-outline-primary btn-sm w-100 mb-2">
                                <i class="ph-duotone ph-currency-dollar me-1"></i>Dossier Comptable
                            </a>
                            <a href="/eleves/details?id=<?= $eleve['id_eleve'] ?>" class="btn btn-outline-secondary btn-sm w-100">
                                <i class="ph-duotone ph-user me-1"></i>Fiche Élève
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Student Financial Settings Form -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5>Avantages Financiers & Exonérations de l'élève</h5>
                        <small class="text-muted">Gérez les réductions, bourses et prises en charge spécifiques à cet élève.</small>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['success_message'])): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="ph-duotone ph-check-circle me-1"></i>
                                <?= $_SESSION['success_message'] ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <?php unset($_SESSION['success_message']); ?>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['error_message'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="ph-duotone ph-warning me-1"></i>
                                <?= $_SESSION['error_message'] ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <?php unset($_SESSION['error_message']); ?>
                        <?php endif; ?>

                        <form action="/eleves/parametres-financiers/update" method="POST">
                            <input type="hidden" name="eleve_id" value="<?= htmlspecialchars($eleve['id_eleve']) ?>">

                            <div class="row">
                                <!-- Type d'avantage -->
                                <div class="col-md-6 mb-3">
                                    <label for="type_avantage" class="form-label">Type d'avantage</label>
                                    <select class="form-select" name="type_avantage" id="type_avantage" required>
                                        <option value="Aucun" <?= ($params['type_avantage'] ?? 'Aucun') === 'Aucun' ? 'selected' : '' ?>>Aucun</option>
                                        <option value="Réduction" <?= ($params['type_avantage'] ?? '') === 'Réduction' ? 'selected' : '' ?>>Réduction</option>
                                        <option value="Exonération" <?= ($params['type_avantage'] ?? '') === 'Exonération' ? 'selected' : '' ?>>Exonération</option>
                                        <option value="Bourse" <?= ($params['type_avantage'] ?? '') === 'Bourse' ? 'selected' : '' ?>>Bourse</option>
                                        <option value="Prise en charge" <?= ($params['type_avantage'] ?? '') === 'Prise en charge' ? 'selected' : '' ?>>Prise en charge</option>
                                        <option value="Autre" <?= ($params['type_avantage'] ?? '') === 'Autre' ? 'selected' : '' ?>>Autre avantage</option>
                                    </select>
                                </div>

                                <!-- Type de valeur -->
                                <div class="col-md-6 mb-3 element-dependant">
                                    <label for="valeur_type" class="form-label">Type de valeur</label>
                                    <select class="form-select" name="valeur_type" id="valeur_type">
                                        <option value="Pourcentage" <?= ($params['valeur_type'] ?? 'Pourcentage') === 'Pourcentage' ? 'selected' : '' ?>>Pourcentage (%)</option>
                                        <option value="Montant fixe" <?= ($params['valeur_type'] ?? '') === 'Montant fixe' ? 'selected' : '' ?>>Montant fixe (FCFA)</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Valeur -->
                                <div class="col-md-6 mb-3 element-dependant">
                                    <label for="valeur" class="form-label">Valeur de l'avantage</label>
                                    <input type="number" step="0.01" class="form-control" name="valeur" id="valeur" value="<?= htmlspecialchars($params['valeur'] ?? '0.00') ?>">
                                </div>

                                <!-- Organisme Financeur -->
                                <div class="col-md-6 mb-3 element-dependant">
                                    <label for="organisme_financeur" class="form-label">Organisme financeur (Optionnel)</label>
                                    <input type="text" class="form-control" name="organisme_financeur" id="organisme_financeur" value="<?= htmlspecialchars($params['organisme_financeur'] ?? '') ?>" placeholder="Ex: État, ONG, Parent d'élève">
                                </div>
                            </div>

                            <div class="row">
                                <!-- Date de début -->
                                <div class="col-md-6 mb-3 element-dependant">
                                    <label for="date_debut" class="form-label">Date de début (Optionnel)</label>
                                    <input type="date" class="form-control" name="date_debut" id="date_debut" value="<?= htmlspecialchars($params['date_debut'] ?? '') ?>">
                                </div>

                                <!-- Date de fin -->
                                <div class="col-md-6 mb-3 element-dependant">
                                    <label for="date_fin" class="form-label">Date de fin (Optionnel)</label>
                                    <input type="date" class="form-control" name="date_fin" id="date_fin" value="<?= htmlspecialchars($params['date_fin'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="mb-3 element-dependant">
                                <label for="motif" class="form-label">Motif initial / Description</label>
                                <textarea class="form-control" name="motif" id="motif" rows="2" placeholder="Expliquez la raison de l'avantage..."><?= htmlspecialchars($params['motif'] ?? '') ?></textarea>
                            </div>

                            <!-- 6. Frais concernés Checkboxes -->
                            <div class="card bg-light-secondary p-3 mb-4 element-dependant">
                                <h6 class="mb-3"><i class="ph-duotone ph-check-square me-2"></i>Frais concernés par l'avantage</h6>

                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" name="tous_frais" id="tous_frais" value="1" <?= !empty($params['tous_frais']) ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-bold text-primary" for="tous_frais">
                                        Tous les frais (Smart Checkbox)
                                    </label>
                                </div>

                                <div class="row" id="individual_fees_container">
                                    <?php foreach ($availableFees as $key => $label): ?>
                                        <div class="col-md-4 col-sm-6 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input fee-checkbox" type="checkbox" name="frais_concernes[]" value="<?= htmlspecialchars($key) ?>" id="fee_<?= htmlspecialchars($key) ?>" <?= in_array($key, $frais_concernes_array) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="fee_<?= htmlspecialchars($key) ?>">
                                                    <?= htmlspecialchars($label) ?>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Mandatory Modification Reason for history logging -->
                            <div class="mb-3">
                                <label for="motif_modification" class="form-label text-danger">Motif de la modification (Requis pour l'historique)</label>
                                <textarea class="form-control border-danger" name="motif_modification" id="motif_modification" rows="2" placeholder="Précisez pourquoi vous effectuez ce changement..." required></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary"><i class="ph-duotone ph-floppy-disk me-1"></i>Enregistrer les modifications</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- History of Financial Decisions -->
            <div class="col-sm-12 mt-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Historique des Décisions Financières</h5>
                        <small class="text-muted">Toutes les modifications apportées à la politique financière de cet élève sont enregistrées ici.</small>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover small">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Auteur</th>
                                        <th>Ancien État</th>
                                        <th>Nouvel État</th>
                                        <th>Motif / Commentaire</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($history)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">Aucune modification enregistrée pour cet élève.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($history as $h):
                                            $old = json_decode($h['ancienne_valeur'] ?? 'null', true);
                                            $new = json_decode($h['nouvelle_valeur'] ?? 'null', true);
                                        ?>
                                            <tr>
                                                <td class="text-nowrap"><?= htmlspecialchars(date('d/m/Y H:i', strtotime($h['date_modification']))) ?></td>
                                                <td><strong><?= htmlspecialchars($h['user_prenom'] . ' ' . $h['user_nom']) ?></strong></td>
                                                <td>
                                                    <?php if ($old): ?>
                                                        <span class="badge bg-light-secondary"><?= htmlspecialchars($old['type_avantage']) ?></span>
                                                        <?php if ($old['type_avantage'] !== 'Aucun'): ?>
                                                            <br><?= htmlspecialchars($old['valeur_type'] === 'Pourcentage' ? $old['valeur'] . '%' : number_format($old['valeur'], 0, ',', ' ') . ' FCFA') ?>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($new): ?>
                                                        <span class="badge bg-light-primary"><?= htmlspecialchars($new['type_avantage']) ?></span>
                                                        <?php if ($new['type_avantage'] !== 'Aucun'): ?>
                                                            <br><?= htmlspecialchars($new['valeur_type'] === 'Pourcentage' ? $new['valeur'] . '%' : number_format($new['valeur'], 0, ',', ' ') . ' FCFA') ?>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($h['motif'] ?? 'Aucun motif renseigné.') ?></td>
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
        <!-- [ Main Content ] end -->
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeSelect = document.getElementById('type_avantage');
    const dependantFields = document.querySelectorAll('.element-dependant');

    function toggleDependantFields() {
        if (typeSelect.value === 'Aucun') {
            dependantFields.forEach(f => f.style.display = 'none');
        } else {
            dependantFields.forEach(f => f.style.display = '');
        }
    }

    typeSelect.addEventListener('change', toggleDependantFields);
    toggleDependantFields();

    // 6. Cases intelligentes ("Tous les frais" smart checkbox logic)
    const tousFraisCheckbox = document.getElementById('tous_frais');
    const feeCheckboxes = document.querySelectorAll('.fee-checkbox');

    function updateSmartCheckboxes() {
        if (tousFraisCheckbox.checked) {
            feeCheckboxes.forEach(cb => {
                cb.checked = true;
                cb.disabled = true;
            });
        } else {
            feeCheckboxes.forEach(cb => {
                cb.disabled = false;
                // Re-read initial state from database or keep manual choices
            });
        }
    }

    tousFraisCheckbox.addEventListener('change', updateSmartCheckboxes);
    updateSmartCheckboxes(); // Run initially
});
</script>

<?php
require_once __DIR__ . '/../layouts/footer_able.php';
$content = ob_get_clean();
echo $content;
?>
