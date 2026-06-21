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

                <!-- Section Frais d'Inscription (Gauche) -->
                <div class="col-lg-5 col-md-12">
                    <form action="/paiements/process-payment/<?= $eleve['id_eleve'] ?>" method="POST">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="ph-duotone ph-file-text me-2 text-primary"></i>1. Inscription & Frais Annexes</h5>
                                <?php if ($fraisInscription['reste'] <= 0): ?>
                                    <span class="badge bg-light-success text-success"><i class="ph-duotone ph-check-circle me-1"></i>SOLDÉ</span>
                                <?php else: ?>
                                    <span class="badge bg-light-danger text-danger"><i class="ph-duotone ph-warning-circle me-1"></i>IMPAYÉ</span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <div class="bg-light-primary p-3 rounded mb-4">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted">Total Attendu :</span>
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
                                    <label for="montant_verse_inscription" class="form-label fw-bold">Montant à verser (Inscription)</label>
                                    <div class="input-group input-group-lg">
                                        <input type="number" id="montant_verse_inscription" name="montant_inscription" class="form-control text-primary fw-bold"
                                               value=""
                                               placeholder="Saisir montant..."
                                               max="<?= $fraisInscription['reste'] ?>"
                                               <?= (!$isComptable || $fraisInscription['reste'] <= 0) ? 'readonly' : '' ?>>
                                        <span class="input-group-text bg-white">FCFA</span>
                                    </div>
                                    <div id="status-inscription" class="mt-2"></div>
                                </div>

                                <div class="card bg-light border-0 mb-4">
                                    <div class="card-body p-3">
                                        <h6 class="mb-3 text-muted">Options & Services Additionnels</h6>
                                        <?php if ((float)($frais['frais_logo'] ?? 0) > 0): ?>
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input option-checkbox" type="checkbox" id="logo_paye" name="options[logo]" data-price="<?= (float)$frais['frais_logo'] ?>" <?= !empty($options['logo']) ? 'checked disabled' : '' ?> <?= !$isComptable ? 'disabled' : '' ?>>
                                            <label class="form-check-label" for="logo_paye">Frais de Logo (Macaron) <small class="text-muted">(+<?= number_format($frais['frais_logo'], 0, ',', ' ') ?> FCFA)</small></label>
                                        </div>
                                        <?php endif; ?>

                                        <?php if ((float)($frais['frais_carte'] ?? 0) > 0): ?>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input option-checkbox" type="checkbox" id="carte_scolaire_payee" name="options[carte]" data-price="<?= (float)$frais['frais_carte'] ?>" <?= !empty($options['carte']) ? 'checked disabled' : '' ?> <?= !$isComptable ? 'disabled' : '' ?>>
                                            <label class="form-check-label" for="carte_scolaire_payee">Carte scolaire informatisée <small class="text-muted">(+<?= number_format($frais['frais_carte'], 0, ',', ' ') ?> FCFA)</small></label>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php if ($isComptable && $fraisInscription['reste'] > 0): ?>
                                <div class="d-grid mb-3">
                                    <button type="submit" class="btn btn-primary btn-lg fw-bold">
                                        <i class="ph-duotone ph-check-circle me-2"></i>Enregistrer les frais d'inscription
                                    </button>
                                </div>
                                <?php endif; ?>

                                <?php if ($inscription): ?>
                                    <div class="mt-3 text-center">
                                        <a href="/recu/inscription?id=<?= $eleve['id_eleve'] ?>" target="_blank" class="btn btn-link-secondary btn-sm">
                                            <i class="ph-duotone ph-printer me-1"></i>Imprimer le dernier reçu d'inscription
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Section Mensualités (Droite) -->
                <div class="col-lg-7 col-md-12">
                    <form action="/paiements/process-payment/<?= $eleve['id_eleve'] ?>" method="POST">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="ph-duotone ph-calendar-check me-2 text-success"></i>2. Gestion des Scolarités (Mensualités)</h5>
                                <div class="dropdown">
                                    <button class="btn btn-link-secondary btn-icon btn-sm dropdown-toggle arrow-none" type="button" data-bs-toggle="dropdown">
                                        <i class="ph-duotone ph-dots-three-outline"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end">
                                        <a class="dropdown-item" href="#"><i class="ph-duotone ph-list-numbers me-2"></i>Historique complet</a>
                                        <a class="dropdown-item text-danger" href="#"><i class="ph-duotone ph-warning me-2"></i>Signaler arriéré</a>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="accordion accordion-modern shadow-none" id="accordionMensualites">
                                    <?php foreach ($tranches as $nomTranche => $details):
                                        $trancheClean = str_replace(' ', '', $nomTranche);

                                        // Calculer l'état global de la tranche
                                        $nbMois = count($details['mois']);
                                        $nbPayes = 0;
                                        foreach($details['mois'] as $m) {
                                            $m_cap = ucfirst($m);
                                            $verseMois = $details['paye'][$m_cap]['verse'] ?? 0;
                                            if ($verseMois >= $details['montant_par_mois']) $nbPayes++;
                                        }

                                        $trancheStatus = 'danger';
                                        if ($nbPayes === $nbMois) $trancheStatus = 'success';
                                        elseif ($nbPayes > 0) $trancheStatus = 'warning';
                                    ?>
                                        <div class="accordion-item border rounded mb-3">
                                            <h2 class="accordion-header" id="heading-<?= $trancheClean ?>">
                                                <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?= $trancheClean ?>">
                                                    <div class="d-flex align-items-center w-100">
                                                        <span class="avtar avtar-s bg-light-<?= $trancheStatus ?> text-<?= $trancheStatus ?> me-3">
                                                            <i class="ph-duotone ph-stack"></i>
                                                        </span>
                                                        <div class="flex-grow-1">
                                                            <span class="h6 mb-0"><?= htmlspecialchars($nomTranche) ?></span>
                                                            <div class="small text-muted"><?= implode(', ', $details['mois']) ?></div>
                                                        </div>
                                                        <div class="me-3">
                                                            <span class="badge bg-light-<?= $trancheStatus ?> text-<?= $trancheStatus ?>">
                                                                <?= $nbPayes ?>/<?= $nbMois ?> mois
                                                            </span>
                                                        </div>
                                                    </div>
                                                </button>
                                            </h2>
                                            <div id="collapse-<?= $trancheClean ?>" class="accordion-collapse collapse" data-bs-parent="#accordionMensualites">
                                                <div class="accordion-body bg-light-layout">
                                                    <div class="form-check form-switch mb-4 p-3 bg-white rounded shadow-sm">
                                                        <input class="form-check-input checkbox-tranche ms-1" type="checkbox" id="tranche_payee_<?= $trancheClean ?>" data-tranche-id="<?= $trancheClean ?>" data-montant-mensuel="<?= htmlspecialchars($details['montant_par_mois']) ?>" <?= (!$isComptable || $nbPayes === $nbMois) ? 'disabled' : '' ?>>
                                                        <label class="form-check-label fw-bold text-success ms-2" for="tranche_payee_<?= $trancheClean ?>">
                                                            Payer toute la tranche (<?= htmlspecialchars($nomTranche) ?>)
                                                        </label>
                                                    </div>

                                                    <?php foreach ($details['mois'] as $mois):
                                                        $m_cap = ucfirst($mois);
                                                        $paye = $details['paye'][$m_cap] ?? null;
                                                        $verse = $paye ? $paye['verse'] : 0;
                                                        $reste = $details['montant_par_mois'] - $verse;

                                                        $moisColor = 'danger';
                                                        if ($reste <= 0) $moisColor = 'success';
                                                        elseif ($verse > 0) $moisColor = 'warning';
                                                    ?>
                                                        <div class="card mb-2 border-0 shadow-sm border-start border-4 border-<?= $moisColor ?>">
                                                            <div class="card-body p-3">
                                                                <div class="row align-items-center">
                                                                    <div class="col-md-4">
                                                                        <div class="fw-bold text-uppercase small text-<?= $moisColor ?>">
                                                                            <?= htmlspecialchars($mois) ?>
                                                                        </div>
                                                                        <?php if ($verse > 0): ?>
                                                                            <div class="small text-muted">Déjà Versé: <?= number_format($verse, 0, ',', ' ') ?></div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                    <div class="col-md-8">
                                                                        <div class="input-group input-group-sm">
                                                                            <input type="number" name="mensualites[<?= strtolower($mois) ?>]"
                                                                                   class="form-control input-mois border-<?= $moisColor ?>"
                                                                                   data-tranche-id="<?= $trancheClean ?>"
                                                                                   data-reste="<?= $reste ?>"
                                                                                   placeholder="<?= $reste > 0 ? "Reste: " . number_format($reste, 0, ',', ' ') : "SOLDÉ" ?>"
                                                                                   value=""
                                                                                   <?= (!$isComptable || $reste <= 0) ? 'readonly' : '' ?>>
                                                                            <span class="input-group-text bg-light border-<?= $moisColor ?>">FCFA</span>
                                                                            <?php if ($paye && !empty($paye['details'])): ?>
                                                                                <button type="button" class="btn btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#details-<?= strtolower($mois) ?>">
                                                                                    <i class="ph-duotone ph-receipt"></i>
                                                                                </button>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <?php if ($paye && !empty($paye['details'])): ?>
                                                                    <div class="collapse mt-2" id="details-<?= strtolower($mois) ?>">
                                                                        <div class="table-responsive">
                                                                            <table class="table table-sm table-borderless mb-0 small">
                                                                                <thead class="bg-light">
                                                                                    <tr>
                                                                                        <th>Date</th>
                                                                                        <th>Montant</th>
                                                                                        <th class="text-end">Reçu</th>
                                                                                    </tr>
                                                                                </thead>
                                                                                <tbody>
                                                                                    <?php foreach ($paye['details'] as $d): ?>
                                                                                        <tr>
                                                                                            <td><?= date('d/m/Y', strtotime($d['date_paiement'])) ?></td>
                                                                                            <td class="fw-bold"><?= number_format($d['montant'], 0, ',', ' ') ?></td>
                                                                                            <td class="text-end text-primary">
                                                                                                <a href="/recu/mensualite?id=<?= $d['id'] ?>" target="_blank"><i class="ph-duotone ph-printer"></i></a>
                                                                                            </td>
                                                                                        </tr>
                                                                                    <?php endforeach; ?>
                                                                                </tbody>
                                                                            </table>
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
                                <div class="d-grid mt-4">
                                    <button type="submit" class="btn btn-success btn-lg fw-bold">
                                        <i class="ph-duotone ph-check-circle me-2"></i>Enregistrer les mensualités
                                    </button>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
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
                                <div class="col-lg-8 p-4 d-flex align-items-center justify-content-center">
                                    <div class="text-center">
                                        <p class="mb-0"><i class="ph-duotone ph-info me-2"></i>Le mode de règlement et le numéro de reçu sont gérés automatiquement.</p>
                                        <p class="small text-white text-opacity-50">Utilisez les boutons "Enregistrer" de chaque section pour valider vos opérations.</p>
                                    </div>
                                </div>
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
        if (inscriptionAverser > 0) summary.push(`Inscription: ${inscriptionAverser.toLocaleString('fr-FR')} FCFA`);
        if (totalMensualites > 0) summary.push(`Mensualités: ${totalMensualites.toLocaleString('fr-FR')} FCFA`);

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

    moisInputs.forEach(input => {
        input.addEventListener('input', () => {
            if (input.value !== '') {
                input.closest('.card').classList.add('bg-light-warning');
            } else {
                input.closest('.card').classList.remove('bg-light-warning');
            }
            updateAll();
        });
    });

    // Initialisation
    updateAll();
});
</script>
<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
