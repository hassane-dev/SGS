<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0">Dossier Financier : <?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></h2>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/">Tableau de Bord</a></li>
                            <li class="breadcrumb-item"><a href="/paiements">Finances</a></li>
                            <li class="breadcrumb-item" aria-current="page">Paiements de <?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- [ Main Content ] start -->
        <form id="form-paiement-unique" action="/paiements/process-payment/<?= $eleve['id_eleve'] ?>" method="POST">
            <div class="row">
                <!-- Student Info Summary -->
                <div class="col-12 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <div class="position-relative">
                                        <img src="<?= $eleve['photo'] ?: '/assets/img/default-avatar.png' ?>" alt="student image" class="img-radius wid-80 shadow-sm border border-2 border-white">
                                        <span class="position-absolute bottom-0 end-0 badge rounded-pill bg-<?= $eleve['statut'] === 'actif' ? 'success' : 'warning' ?> p-2 border border-2 border-white">
                                            <span class="visually-hidden">Statut</span>
                                        </span>
                                    </div>
                                </div>
                                <div class="col">
                                    <h4 class="mb-1 text-primary"><?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></h4>
                                    <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                                        <span class="badge bg-light-primary text-primary fs-6">
                                            <i class="ph-duotone ph-chalkboard-teacher me-1"></i> <?= htmlspecialchars($eleve['nom_classe']) ?>
                                        </span>
                                        <span class="badge bg-light-secondary text-secondary fs-6">
                                            <i class="ph-duotone ph-identification-card me-1"></i> Mat: <?= htmlspecialchars($eleve['matricule'] ?? 'N/A') ?>
                                        </span>
                                        <span class="badge bg-light-info text-info fs-6">
                                            <i class="ph-duotone ph-calendar me-1"></i> Né(e) le <?= date('d/m/Y', strtotime($eleve['date_naissance'])) ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="col-auto text-end">
                                    <div class="btn-group shadow-sm">
                                        <a href="/eleves/details?id=<?= $eleve['id_eleve'] ?>" class="btn btn-outline-secondary">
                                            <i class="ph-duotone ph-user-focus me-2"></i>Profil
                                        </a>
                                        <button type="button" class="btn btn-primary" onclick="window.print()">
                                            <i class="ph-duotone ph-printer me-2"></i>Imprimer État
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section Frais d'Inscription (Unique pour ce module) -->
                <div class="col-lg-12 col-md-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="ph-duotone ph-file-text me-2 text-primary"></i>Validation de l'Inscription & Frais Annexes</h5>
                            <?php if ($fraisInscription['reste'] <= 0): ?>
                                <span class="badge bg-light-success text-success"><i class="ph-duotone ph-check-circle me-1"></i>SOLDÉ</span>
                            <?php else: ?>
                                <span class="badge bg-light-danger text-danger"><i class="ph-duotone ph-warning-circle me-1"></i>IMPAYÉ</span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="bg-light-primary p-4 rounded mb-4">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="text-muted">Total Frais d'Inscription :</span>
                                            <span class="fw-bold text-dark" id="inscription-total-attendu" data-base="<?= (float)($frais['frais_inscription'] ?? 0) ?>"><?= number_format($fraisInscription['total'], 0, ',', ' ') ?> FCFA</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2 text-success">
                                            <span>Déjà Versé :</span>
                                            <span class="fw-bold"><?= number_format($fraisInscription['verse'], 0, ',', ' ') ?> FCFA</span>
                                        </div>
                                        <div class="d-flex justify-content-between border-top pt-2 mt-2 <?= $fraisInscription['reste'] > 0 ? 'text-danger' : 'text-success' ?>">
                                            <span class="fw-bold">Reste à Payer :</span>
                                            <h5 class="mb-0 fw-bold" id="inscription-reste-a-payer" data-verse-existant="<?= $fraisInscription['verse'] ?>"><?= number_format($fraisInscription['reste'], 0, ',', ' ') ?> FCFA</h5>
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label for="montant_verse_inscription" class="form-label fw-bold">Montant à encaisser maintenant</label>
                                        <div class="input-group input-group-lg">
                                            <input type="number" id="montant_verse_inscription" name="montant_inscription" class="form-control text-primary fw-bold"
                                                   value=""
                                                   placeholder="Saisir montant..."
                                                   max="<?= $fraisInscription['reste'] ?>"
                                                   <?= (!$isComptable || $fraisInscription['reste'] <= 0) ? 'readonly' : '' ?>>
                                            <span class="input-group-text bg-white">FCFA</span>
                                        </div>
                                        <div id="status-inscription" class="mt-2 small text-muted">Ce montant sera déduit du reste à payer.</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card bg-light border-0 mb-4 h-100">
                                        <div class="card-body p-4">
                                            <h6 class="mb-3 text-muted text-uppercase fw-bold">Options & Services Additionnels</h6>
                                            <?php if ((float)($frais['frais_logo'] ?? 0) > 0): ?>
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input option-checkbox" type="checkbox" id="logo_paye" name="options[logo]" data-price="<?= (float)$frais['frais_logo'] ?>" <?= !empty($options['logo']) ? 'checked disabled' : '' ?> <?= !$isComptable ? 'disabled' : '' ?>>
                                                <label class="form-check-label fs-5" for="logo_paye">Frais de Logo (Macaron) <small class="text-muted d-block mt-1">+<?= number_format($frais['frais_logo'], 0, ',', ' ') ?> FCFA</small></label>
                                            </div>
                                            <?php endif; ?>

                                            <?php if ((float)($frais['frais_carte'] ?? 0) > 0): ?>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input option-checkbox" type="checkbox" id="carte_scolaire_payee" name="options[carte]" data-price="<?= (float)$frais['frais_carte'] ?>" <?= !empty($options['carte']) ? 'checked disabled' : '' ?> <?= !$isComptable ? 'disabled' : '' ?>>
                                                <label class="form-check-label fs-5" for="carte_scolaire_payee">Carte scolaire informatisée <small class="text-muted d-block mt-1">+<?= number_format($frais['frais_carte'], 0, ',', ' ') ?> FCFA</small></label>
                                            </div>
                                            <?php endif; ?>

                                            <hr>

                                            <div class="form-check form-switch mt-3">
                                                <input class="form-check-input" type="checkbox" id="add_first_month" name="add_first_month" value="1">
                                                <label class="form-check-label fw-bold" for="add_first_month">Inclure le premier mois de scolarité ?</label>
                                                <div id="first_month_input" style="display:none;" class="mt-2">
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" name="mensualites[<?= strtolower($allMonths[0] ?? 'septembre') ?>]" class="form-control" placeholder="Montant 1ère mensualité..." value="<?= (float)($frais['frais_mensuel'] ?? 0) ?>">
                                                        <span class="input-group-text">FCFA</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php if ($inscription): ?>
                                <div class="mt-3 text-center border-top pt-3">
                                    <a href="/recu/inscription?id=<?= $eleve['id_eleve'] ?>" target="_blank" class="btn btn-outline-secondary">
                                        <i class="ph-duotone ph-printer me-2"></i>Imprimer le dernier reçu d'inscription
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Footer de Paiement Unifié -->
                <div class="col-12 mt-4">
                    <div class="card border-0 shadow-lg bg-primary text-white overflow-hidden">
                        <div class="card-body p-0">
                            <div class="row g-0">
                                <div class="col-lg-4 bg-dark bg-opacity-10 p-4 d-flex flex-column justify-content-center border-end border-white border-opacity-10">
                                    <div class="text-white text-opacity-75 mb-1 text-uppercase fw-bold small">Total à encaisser</div>
                                    <div class="d-flex align-items-baseline">
                                        <h1 class="text-white mb-0 fw-bold" id="total-general-amount">0</h1>
                                        <span class="ms-2 fs-4 text-white text-opacity-75">FCFA</span>
                                    </div>
                                    <div id="summary-details" class="mt-2 small text-white text-opacity-50">
                                        Aucun montant saisi
                                    </div>
                                </div>
                                <div class="col-lg-8 p-4">
                                    <?php if ($isComptable): ?>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label fw-bold text-white">Mode de Règlement</label>
                                                <select name="mode_paiement" class="form-select border-0 shadow-none">
                                                    <?php
                                                    $modes = !empty($paramGeneral['modalite_paiement'])
                                                        ? explode(',', $paramGeneral['modalite_paiement'])
                                                        : ['Espèces', 'Bordereau Bancaire', 'Chèque', 'Mobile Money'];
                                                    foreach ($modes as $mode):
                                                        $mode = trim($mode);
                                                    ?>
                                                        <option value="<?= htmlspecialchars($mode) ?>"><?= htmlspecialchars($mode) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label fw-bold text-white">Référence / N° Bordereau</label>
                                                <input type="text" name="reference_transaction" class="form-control border-0 shadow-none" placeholder="Laissé vide pour auto-génération" value="<?= $nextRecu ?>">
                                            </div>
                                            <div class="col-12 mt-4">
                                                <div class="d-grid">
                                                    <button type="submit" class="btn btn-light btn-lg text-primary fw-bold shadow-sm">
                                                        <i class="ph-duotone ph-check-circle me-2"></i>Valider et Générer le Reçu
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-light mb-0">
                                            <i class="ph-duotone ph-info me-2"></i>Vous n'avez pas les droits nécessaires pour effectuer des encaissements.
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        <!-- [ Main Content ] end -->
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Éléments Inscription
    const baseInscriptionTotal = parseFloat(document.getElementById('inscription-total-attendu').dataset.base);
    const montantInscriptionInput = document.getElementById('montant_verse_inscription');
    const optionCheckboxes = document.querySelectorAll('.option-checkbox');
    const resteInscriptionElt = document.getElementById('inscription-reste-a-payer');
    const verseExistant = parseFloat(resteInscriptionElt.dataset.verseExistant);

    // Éléments Mensualités
    const trancheCheckboxes = document.querySelectorAll('.checkbox-tranche');
    const moisInputs = document.querySelectorAll('.input-mois');

    // Éléments Total Général
    const totalGeneralAmount = document.getElementById('total-general-amount');
    const summaryDetails = document.getElementById('summary-details');

    function updateAll() {
        // 1. Calcul Inscription
        let currentInscriptionTotal = baseInscriptionTotal;
        optionCheckboxes.forEach(cb => {
            if (cb.checked) currentInscriptionTotal += parseFloat(cb.dataset.price);
        });

        const nouveauResteInscription = currentInscriptionTotal - verseExistant;
        const inscriptionAverser = parseFloat(montantInscriptionInput.value) || 0;

        resteInscriptionElt.textContent = (nouveauResteInscription - inscriptionAverser).toLocaleString('fr-FR') + ' FCFA';
        document.getElementById('inscription-total-attendu').textContent = currentInscriptionTotal.toLocaleString('fr-FR') + ' FCFA';

        // 2. Calcul Mensualités
        let totalMensualites = 0;
        moisInputs.forEach(input => {
            totalMensualites += parseFloat(input.value) || 0;
        });

        // 3. Total Général
        const grandTotal = inscriptionAverser + totalMensualites;
        totalGeneralAmount.textContent = grandTotal.toLocaleString('fr-FR');

        // 4. Résumé
        let summary = [];
        if (inscriptionAverser > 0) summary.push(`Inscription: ${inscriptionAverser.toLocaleString('fr-FR')}`);
        if (totalMensualites > 0) summary.push(`Mensualités: ${totalMensualites.toLocaleString('fr-FR')}`);

        if (summary.length > 0) {
            summaryDetails.textContent = summary.join(' | ');
        } else {
            summaryDetails.textContent = "Aucun montant saisi";
        }
    }

    // Listeners Inscription
    if (montantInscriptionInput) {
        montantInscriptionInput.addEventListener('input', updateAll);
        optionCheckboxes.forEach(cb => cb.addEventListener('change', updateAll));
    }

    // Listeners Mensualités
    trancheCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const trancheId = this.dataset.trancheId;
            const trancheMoisInputs = document.querySelectorAll(`.input-mois[data-tranche-id="${trancheId}"]`);

            if (this.checked) {
                trancheMoisInputs.forEach(input => {
                    if (!input.readOnly) {
                        input.value = input.dataset.reste;
                        input.closest('.card').classList.add('bg-light-success');
                    }
                });
            } else {
                 trancheMoisInputs.forEach(input => {
                    if (!input.readOnly) {
                        input.value = '';
                        input.closest('.card').classList.remove('bg-light-success');
                    }
                });
            }
            updateAll();
        });
    });

    const addFirstMonthCb = document.getElementById('add_first_month');
    const firstMonthInput = document.getElementById('first_month_input');

    if (addFirstMonthCb) {
        addFirstMonthCb.addEventListener('change', function() {
            firstMonthInput.style.display = this.checked ? 'block' : 'none';
            if (!this.checked) {
                firstMonthInput.querySelector('input').value = '';
            } else {
                firstMonthInput.querySelector('input').value = firstMonthInput.querySelector('input').defaultValue;
            }
            updateAll();
        });

        firstMonthInput.querySelector('input').addEventListener('input', updateAll);
    }

    // Initialisation
    updateAll();
});
</script>
<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
