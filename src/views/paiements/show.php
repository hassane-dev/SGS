<?php
// Fichier: src/views/paiements/show.php
// Les données ($eleve, $fraisInscription, $options, $tranches, $isComptable)
// sont injectées par PaiementController::show()

require_once __DIR__ . '/../layouts/header_able.php';
require_once __DIR__ . '/../layouts/sidebar_able.php';
?>
<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h5 class="m-b-10">Tableau de Bord de Paiement</h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/home">Tableau de Bord</a></li>
                            <li class="breadcrumb-item"><a href="/eleves">Élèves</a></li>
                            <li class="breadcrumb-item" aria-current="page">Paiement de <?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- [ Main Content ] start -->
        <div class="row">
            <!-- Section Frais d'Inscription (Gauche) -->
            <div class="col-lg-5 col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="ti ti-file-invoice me-2"></i>Frais d'Inscription</h5>
                    </div>
                    <div class="card-body">
                        <form id="form-inscription" action="/paiements/process-inscription/<?= $eleve['id_eleve'] ?? 1 ?>" method="POST">
                            <div class="mb-3">
                                <label class="form-label">Montant Total de l'Inscription</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($fraisInscription['total']) ?> FCFA" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="montant_verse_inscription" class="form-label">Montant Versé</label>
                                <input type="number" id="montant_verse_inscription" name="montant_verse" class="form-control" value="<?= htmlspecialchars($fraisInscription['verse']) ?>" <?= !$isComptable ? 'disabled' : '' ?>>
                            </div>
                            <div id="status-inscription" class="mb-3">
                                <!-- Le statut (reste à payer) sera injecté ici par JS -->
                            </div>
                            <hr>
                            <h6>Options</h6>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="logo_paye" name="options[logo]" <?= !empty($options['logo']) ? 'checked' : '' ?> <?= !$isComptable ? 'disabled' : '' ?>>
                                <label class="form-check-label" for="logo_paye">Logo payé</label>
                            </div>
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="carte_scolaire_payee" name="options[carte]" <?= !empty($options['carte']) ? 'checked' : '' ?> <?= !$isComptable ? 'disabled' : '' ?>>
                                <label class="form-check-label" for="carte_scolaire_payee">Carte scolaire payée</label>
                            </div>

                            <?php if ($isComptable): ?>
                                <button type="submit" class="btn btn-primary w-100">Enregistrer le Paiement d'Inscription</button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Section Mensualités (Droite) -->
            <div class="col-lg-7 col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="ti ti-calendar-event me-2"></i>Paiement des Mensualités / Tranches</h5>
                    </div>
                    <div class="card-body">
                        <form id="form-mensualites" action="/paiements/process-mensualites/<?= $eleve['id_eleve'] ?? 1 ?>" method="POST">
                            <?php foreach ($tranches as $nomTranche => $details): ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading-<?= str_replace(' ', '', $nomTranche) ?>">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?= str_replace(' ', '', $nomTranche) ?>" aria-expanded="false" aria-controls="collapse-<?= str_replace(' ', '', $nomTranche) ?>">
                                            <?= htmlspecialchars($nomTranche) ?> - <span class="fw-bold ms-1"><?= implode(' / ', $details['mois']) ?></span>
                                        </button>
                                    </h2>
                                    <div id="collapse-<?= str_replace(' ', '', $nomTranche) ?>" class="accordion-collapse collapse" aria-labelledby="heading-<?= str_replace(' ', '', $nomTranche) ?>">
                                        <div class="accordion-body">
                                            <div class="form-check mb-3">
                                                <input class="form-check-input checkbox-tranche" type="checkbox" id="tranche_payee_<?= str_replace(' ', '', $nomTranche) ?>" data-tranche-id="<?= str_replace(' ', '', $nomTranche) ?>" data-montant-total="<?= htmlspecialchars($details['montant_par_mois']) ?>" <?= !$isComptable ? 'disabled' : '' ?>>
                                                <label class="form-check-label" for="tranche_payee_<?= str_replace(' ', '', $nomTranche) ?>">Tranche entièrement payée</label>
                                            </div>
                                            <div id="feedback-tranche-<?= str_replace(' ', '', $nomTranche) ?>"></div>

                                            <?php foreach ($details['mois'] as $mois): ?>
                                                <div class="row align-items-center mb-2">
                                                    <div class="col-sm-4">
                                                        <label for="montant_<?= strtolower($mois) ?>" class="col-form-label"><?= htmlspecialchars($mois) ?></label>
                                                    </div>
                                                    <div class="col-sm-8">
                                                        <div class="input-group">
                                                            <input type="number" id="montant_<?= strtolower($mois) ?>" name="mensualites[<?= strtolower($mois) ?>]" class="form-control input-mois" data-tranche-id="<?= str_replace(' ', '', $nomTranche) ?>" placeholder="Montant" value="<?= htmlspecialchars($details['paye'][$mois] ?? '') ?>" <?= !$isComptable ? 'disabled' : '' ?>>
                                                            <span class="input-group-text">FCFA</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <?php if ($isComptable): ?>
                                <button type="submit" class="btn btn-success w-100 mt-3">Enregistrer le Paiement des Mensualités</button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ Main Content ] end -->
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion de la section inscription
    const montantTotalInput = <?= (float)($fraisInscription['total'] ?? 0) ?>;
    const montantVerseInput = document.getElementById('montant_verse_inscription');
    const statusDiv = document.getElementById('status-inscription');

    function updateInscriptionStatus() {
        const montantVerse = parseFloat(montantVerseInput.value) || 0;
        const reste = montantTotalInput - montantVerse;

        statusDiv.innerHTML = '';
        let statusMessage = '';
        let alertClass = '';

        if (montantVerse > montantTotalInput) {
            statusMessage = `❌ Montant supérieur au total`;
            alertClass = 'alert alert-danger';
            montantVerseInput.classList.add('is-invalid');
        } else if (reste === 0) {
            statusMessage = `✔ Inscription réglée — reste 0 FCFA`;
            alertClass = 'alert alert-success';
            montantVerseInput.classList.remove('is-invalid');
        } else {
            statusMessage = `⚠ Reste à payer : ${reste.toLocaleString('fr-FR')} FCFA`;
            alertClass = 'alert alert-warning';
            montantVerseInput.classList.remove('is-invalid');
        }

        const feedbackDiv = document.createElement('div');
        feedbackDiv.className = alertClass;
        feedbackDiv.setAttribute('role', 'alert');
        feedbackDiv.textContent = statusMessage;
        statusDiv.appendChild(feedbackDiv);
    }

    if (montantVerseInput) {
        montantVerseInput.addEventListener('input', updateInscriptionStatus);
        updateInscriptionStatus(); // Initial check
    }

    // Gestion de la section mensualités
    const trancheCheckboxes = document.querySelectorAll('.checkbox-tranche');

    trancheCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const trancheId = this.dataset.trancheId;
            const moisInputs = document.querySelectorAll(`.input-mois[data-tranche-id="${trancheId}"]`);
            const feedbackDiv = document.getElementById(`feedback-tranche-${trancheId}`);
            feedbackDiv.innerHTML = '';

            if (this.checked) {
                const montantParMois = parseFloat(this.dataset.montantTotal);
                moisInputs.forEach(input => {
                    input.value = montantParMois;
                    input.disabled = true;
                    input.classList.add('is-valid');
                });
                const successDiv = document.createElement('div');
                successDiv.className = 'alert alert-success';
                successDiv.textContent = '✔ Tranche entièrement payée';
                feedbackDiv.appendChild(successDiv);
            } else {
                 moisInputs.forEach(input => {
                    input.disabled = <?= $isComptable ? 'false' : 'true' ?>;
                    input.classList.remove('is-valid');
                });
            }
        });
    });
});
</script>
<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
