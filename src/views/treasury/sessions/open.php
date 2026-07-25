<?php include __DIR__ . '/../../layouts/header_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <!-- Breadcrumbs -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/"><?= _("Accueil") ?></a></li>
                            <li class="breadcrumb-item"><a href="javascript: void(0)"><?= _("Trésorerie") ?></a></li>
                            <li class="breadcrumb-item"><a href="/treasury/sessions"><?= _("Sessions de Caisse") ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _("Ouvrir") ?></li>
                        </ul>
                    </div>
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= _("Ouvrir une Session de Caisse") ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $_SESSION['error_message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5><?= _("Informations d'Ouverture") ?></h5>
                    </div>
                    <div class="card-body">
                        <form action="/treasury/sessions/open" method="POST">
                            <div class="mb-3">
                                <label for="compte_id" class="form-label"><?= _("Sélectionner la Caisse Physique") ?> <span class="text-danger">*</span></label>
                                <select class="form-select" id="compte_id" name="compte_id" required>
                                    <option value=""><?= _("-- Choisir une caisse --") ?></option>
                                    <?php foreach ($caisses as $c): ?>
                                        <option value="<?= $c['id'] ?>">
                                            <?= htmlspecialchars($c['nom_compte']) ?> (<?= _("Solde courant") ?> : <?= number_format($c['solde_courant'], 2, ',', ' ') ?> <?= htmlspecialchars($c['devise']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text text-muted"><?= _("Seuls les comptes financiers de type 'Caisse' actifs s'affichent ici.") ?></div>
                            </div>

                            <div class="alert alert-warning mb-4">
                                <i class="ph-duotone ph-info me-2 fs-5"></i>
                                <span><?= _("L'ouverture d'une session de caisse n'entraîne aucun mouvement de trésorerie sur le grand livre. Le solde d'ouverture sert uniquement d'état opérationnel de report.") ?></span>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary d-inline-flex align-items-center justify-content-center">
                                    <i class="ph-duotone ph-check-circle me-2 fs-5"></i><?= _("Confirmer l'ouverture") ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../layouts/footer_able.php'; ?>