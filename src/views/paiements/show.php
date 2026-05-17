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
                            <h2 class="mb-0">Paiements : <?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></h2>
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
        <div class="row">
            <!-- Student Info Summary -->
            <div class="col-12 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <img src="<?= $eleve['photo'] ?: '/assets/img/default-avatar.png' ?>" alt="student image" class="img-radius wid-80">
                            </div>
                            <div class="flex-grow-1 ms-4">
                                <h4 class="mb-1"><?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></h4>
                                <p class="mb-0 text-muted">
                                    <span class="badge bg-light-primary text-primary fs-6 me-2">
                                        <i class="ph-duotone ph-chalkboard-teacher me-1"></i> <?= htmlspecialchars($eleve['nom_classe']) ?>
                                    </span>
                                    <span class="badge bg-light-secondary text-secondary fs-6">
                                        <i class="ph-duotone ph-calendar me-1"></i> Né(e) le <?= date('d/m/Y', strtotime($eleve['date_naissance'])) ?>
                                    </span>
                                </p>
                            </div>
                            <div class="flex-shrink-0">
                                <a href="/eleves/details?id=<?= $eleve['id_eleve'] ?>" class="btn btn-outline-secondary">
                                    <i class="ph-duotone ph-user-focus me-2"></i>Voir le profil
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section Frais d'Inscription (Gauche) -->
            <div class="col-lg-5 col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="ph-duotone ph-file-text me-2 text-primary"></i>Frais d'Inscription</h5>
                    </div>
                    <div class="card-body">
                        <form id="form-inscription" action="/paiements/process-inscription/<?= $eleve['id_eleve'] ?>" method="POST">
                            <div class="mb-3">
                                <label class="form-label">Montant Total de l'Inscription</label>
                                <div class="input-group">
                                    <input type="text" class="form-control fw-bold" value="<?= number_format($fraisInscription['total'], 0, ',', ' ') ?>" readonly>
                                    <span class="input-group-text">FCFA</span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="montant_verse_inscription" class="form-label">Montant Versé</label>
                                <div class="input-group">
                                    <input type="number" id="montant_verse_inscription" name="montant_verse" class="form-control form-control-lg text-primary fw-bold" value="<?= htmlspecialchars($fraisInscription['verse']) ?>" <?= !$isComptable ? 'readonly' : '' ?>>
                                    <span class="input-group-text">FCFA</span>
                                </div>
                            </div>
                            <div id="status-inscription" class="mb-3">
                                <!-- Le statut (reste à payer) sera injecté ici par JS -->
                            </div>
                            <hr>
                            <h6>Options & Services</h6>
                            <div class="bg-light p-3 rounded mb-3">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" id="logo_paye" name="options[logo]" <?= !empty($options['logo']) ? 'checked' : '' ?> <?= !$isComptable ? 'disabled' : '' ?>>
                                    <label class="form-check-label" for="logo_paye">Logo de l'établissement</label>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="carte_scolaire_payee" name="options[carte]" <?= !empty($options['carte']) ? 'checked' : '' ?> <?= !$isComptable ? 'disabled' : '' ?>>
                                    <label class="form-check-label" for="carte_scolaire_payee">Carte scolaire informatisée</label>
                                </div>
                            </div>

                            <?php if ($isComptable): ?>
                                <button type="submit" class="btn btn-primary w-100 py-2">
                                    <i class="ph-duotone ph-floppy-disk me-2"></i>Enregistrer l'inscription
                                </button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Section Mensualités (Droite) -->
            <div class="col-lg-7 col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="ph-duotone ph-calendar-check me-2 text-success"></i>Paiement des Scolarités</h5>
                    </div>
                    <div class="card-body">
                        <form id="form-mensualites" action="/paiements/process-mensualites/<?= $eleve['id_eleve'] ?>" method="POST">
                            <div class="accordion accordion-flush" id="accordionMensualites">
                                <?php foreach ($tranches as $nomTranche => $details):
                                    $trancheClean = str_replace(' ', '', $nomTranche);
                                ?>
                                    <div class="accordion-item border rounded mb-3 overflow-hidden">
                                        <h2 class="accordion-header" id="heading-<?= $trancheClean ?>">
                                            <button class="accordion-button collapsed bg-light-primary text-primary fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?= $trancheClean ?>" aria-expanded="false" aria-controls="collapse-<?= $trancheClean ?>">
                                                <i class="ph-duotone ph-stack me-2"></i> <?= htmlspecialchars($nomTranche) ?>
                                                <span class="ms-auto me-3 small text-muted"><?= implode(' / ', $details['mois']) ?></span>
                                            </button>
                                        </h2>
                                        <div id="collapse-<?= $trancheClean ?>" class="accordion-collapse collapse" aria-labelledby="heading-<?= $trancheClean ?>" data-bs-parent="#accordionMensualites">
                                            <div class="accordion-body">
                                                <div class="form-check form-check-inline mb-4 p-3 bg-light-success rounded w-100">
                                                    <input class="form-check-input checkbox-tranche ms-1" type="checkbox" id="tranche_payee_<?= $trancheClean ?>" data-tranche-id="<?= $trancheClean ?>" data-montant-total="<?= htmlspecialchars($details['montant_par_mois']) ?>" <?= !$isComptable ? 'disabled' : '' ?>>
                                                    <label class="form-check-label fw-bold text-success ms-2" for="tranche_payee_<?= $trancheClean ?>">
                                                        Marquer toute la tranche comme payée
                                                    </label>
                                                </div>
                                                <div id="feedback-tranche-<?= $trancheClean ?>"></div>

                                                <?php foreach ($details['mois'] as $mois):
                                                    $paye = $details['paye'][$mois] ?? null;
                                                    $verse = $paye ? $paye['verse'] : 0;
                                                    $reste = $details['montant_par_mois'] - $verse;
                                                ?>
                                                    <div class="row align-items-center mb-4 pb-2 border-bottom">
                                                        <div class="col-sm-4">
                                                            <label class="fw-bold mb-0 text-uppercase small">
                                                                <?= htmlspecialchars($mois) ?>
                                                            </label>
                                                            <?php if ($verse > 0): ?>
                                                                <div class="badge bg-light-success text-success mt-1">
                                                                    Payé: <?= number_format($verse, 0, ',', ' ') ?> FCFA
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="col-sm-8">
                                                            <div class="input-group">
                                                                <input type="number" name="mensualites[<?= strtolower($mois) ?>]" class="form-control input-mois" data-tranche-id="<?= $trancheClean ?>" placeholder="<?= $reste > 0 ? "Reste: " . number_format($reste, 0, ',', ' ') : "Déjà réglé" ?>" value="" <?= (!$isComptable || $reste <= 0) ? 'readonly' : '' ?>>
                                                                <span class="input-group-text bg-light">FCFA</span>
                                                            </div>
                                                            <?php if ($paye && !empty($paye['details'])): ?>
                                                                <div class="mt-2 text-end">
                                                                    <button type="button" class="btn btn-link-secondary btn-sm p-0" data-bs-toggle="collapse" data-bs-target="#details-<?= strtolower($mois) ?>">
                                                                        <i class="ph-duotone ph-clock-counter-clockwise me-1"></i> Historique
                                                                    </button>
                                                                    <div class="collapse mt-2 text-start" id="details-<?= strtolower($mois) ?>">
                                                                        <div class="card card-body p-2 bg-light shadow-none border small">
                                                                            <?php foreach ($paye['details'] as $d): ?>
                                                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                                                    <span>
                                                                                        <?= date('d/m/Y', strtotime($d['date_paiement'])) ?> :
                                                                                        <strong><?= number_format($d['montant'], 0, ',', ' ') ?></strong>
                                                                                    </span>
                                                                                    <a href="/recu/mensualite?id=<?= $d['id'] ?>" class="btn btn-icon btn-sm btn-link-primary" title="Imprimer le reçu" target="_blank">
                                                                                        <i class="ph-duotone ph-printer"></i>
                                                                                    </a>
                                                                                </div>
                                                                            <?php endforeach; ?>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <?php if ($isComptable): ?>
                                <div class="bg-light p-4 rounded mt-4">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">Mode de Règlement</label>
                                            <select name="mode_paiement" class="form-select">
                                                <option value="Espèces">Espèces</option>
                                                <option value="Chèque">Chèque</option>
                                                <option value="Versement">Versement Bancaire</option>
                                                <option value="Mobile Money">Orange Money / Moov Money</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">Référence / N° de pièce</label>
                                            <input type="text" name="reference_transaction" class="form-control" placeholder="Ex: N° Chèque ou Transaction">
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-success w-100 mt-4 py-2">
                                        <i class="ph-duotone ph-check-circle me-2"></i>Valider les paiements sélectionnés
                                    </button>
                                </div>
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
    const montantTotal = <?= (float)($fraisInscription['total'] ?? 0) ?>;
    const montantVerseInput = document.getElementById('montant_verse_inscription');
    const statusDiv = document.getElementById('status-inscription');

    function updateInscriptionStatus() {
        const montantVerse = parseFloat(montantVerseInput.value) || 0;
        const reste = montantTotal - montantVerse;

        statusDiv.innerHTML = '';

        if (montantVerse > montantTotal) {
            statusDiv.innerHTML = '<div class="alert alert-danger py-2 mb-0"><i class="ph-duotone ph-warning-circle me-2"></i>Le montant dépasse le total attendu.</div>';
            montantVerseInput.classList.add('is-invalid');
        } else if (reste === 0) {
            statusDiv.innerHTML = '<div class="alert alert-success py-2 mb-0"><i class="ph-duotone ph-check-circle me-2"></i>Paiement complet enregistré.</div>';
            montantVerseInput.classList.remove('is-invalid');
            montantVerseInput.classList.add('is-valid');
        } else {
            statusDiv.innerHTML = `<div class="alert alert-warning py-2 mb-0"><i class="ph-duotone ph-info me-2"></i>Reste à percevoir : <strong>${reste.toLocaleString('fr-FR')} FCFA</strong></div>`;
            montantVerseInput.classList.remove('is-invalid');
            montantVerseInput.classList.remove('is-valid');
        }
    }

    if (montantVerseInput) {
        montantVerseInput.addEventListener('input', updateInscriptionStatus);
        updateInscriptionStatus();
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
                    if (!input.readOnly) {
                        input.value = montantParMois;
                        input.classList.add('bg-light-success');
                    }
                });
                feedbackDiv.innerHTML = '<div class="alert alert-success py-1 small mb-3">Toute la tranche est sélectionnée.</div>';
            } else {
                 moisInputs.forEach(input => {
                    if (!input.readOnly) {
                        input.value = '';
                        input.classList.remove('bg-light-success');
                    }
                });
            }
        });
    });
});
</script>
<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
