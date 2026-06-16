<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0">Paiement Mensualités : <?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></h2>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/">Accueil</a></li>
                            <li class="breadcrumb-item"><a href="/mensualites">Mensualités</a></li>
                            <li class="breadcrumb-item" aria-current="page">Encaisser</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <form action="/paiements/process-payment/<?= $eleve['id_eleve'] ?>" method="POST">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5>Sélection des mois à payer</h5>
                        </div>
                        <div class="card-body">
                            <div class="accordion accordion-modern" id="accordionMensualites">
                                <?php foreach ($tranches as $nomTranche => $details):
                                    $trancheClean = str_replace(' ', '', $nomTranche);
                                ?>
                                    <div class="accordion-item mb-3 border rounded">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?= $trancheClean ?>">
                                                <?= htmlspecialchars($nomTranche) ?>
                                            </button>
                                        </h2>
                                        <div id="collapse-<?= $trancheClean ?>" class="accordion-collapse collapse show">
                                            <div class="accordion-body">
                                                <?php foreach ($details['mois'] as $mois):
                                                    $m_cap = ucfirst($mois);
                                                    $paye = $details['paye'][$m_cap] ?? null;
                                                    $verse = $paye ? $paye['verse'] : 0;
                                                    $reste = $details['montant_par_mois'] - $verse;
                                                    if ($reste <= 0) continue;
                                                ?>
                                                    <div class="mb-3 p-3 bg-light rounded border-start border-4 border-primary">
                                                        <div class="row align-items-center">
                                                            <div class="col-md-6">
                                                                <label class="form-label fw-bold"><?= htmlspecialchars($mois) ?></label>
                                                                <div class="small text-muted">Attendu: <?= number_format($details['montant_par_mois'], 0, ',', ' ') ?> FCFA</div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="input-group">
                                                                    <input type="number" name="mensualites[<?= strtolower($mois) ?>]" class="form-control" placeholder="Montant..." max="<?= $reste ?>">
                                                                    <span class="input-group-text">FCFA</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card shadow-lg border-0 bg-primary text-white">
                        <div class="card-body">
                            <h5 class="text-white mb-4">Finalisation du paiement</h5>
                            <div class="mb-3">
                                <label class="form-label text-white">Mode de paiement</label>
                                <select name="mode_paiement" class="form-select border-0 shadow-none">
                                    <option value="Espèces">Espèces</option>
                                    <option value="Banque">Banque</option>
                                    <option value="Mobile Money">Mobile Money</option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="form-label text-white">Référence</label>
                                <input type="text" name="reference_transaction" class="form-control border-0 shadow-none" value="<?= $nextRecu ?>">
                            </div>
                            <button type="submit" class="btn btn-light text-primary w-100 fw-bold py-3">
                                <i class="ph-duotone ph-check-circle me-2"></i>Valider l'encaissement
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
