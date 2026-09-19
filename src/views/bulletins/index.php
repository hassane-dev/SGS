<?php
require_once __DIR__ . '/../layouts/header_able.php';
require_once __DIR__ . '/../layouts/sidebar_able.php';
?>

<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header mb-3">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= _('Bulletins & Impression') ?></h2>
                        </div>
                        <ul class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="/dashboard"><?= _('Accueil') ?></a></li>
                            <li class="breadcrumb-item"><?= _('Pédagogie') ?></li>
                            <li class="breadcrumb-item active"><?= _('Bulletins & Impression') ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- HUB ACTIONS / CTA BAR -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm border-0 bg-grd-primary text-white">
                    <div class="card-body p-3">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <div>
                                <h5 class="text-white mb-1"><i class="ph-duotone ph-file-text me-2"></i><?= _("Hub de Gestion & Arrêté des Bulletins") ?></h5>
                                <p class="text-white-50 small mb-0"><?= _("Générez, consultez, validez et imprimez les bulletins de notes officiels.") ?></p>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="/bulletins" class="btn btn-light btn-sm font-weight-bold">
                                    <i class="ph-duotone ph-list-numbers text-primary me-1"></i><?= _("Résultats de Classe") ?>
                                </a>

                                <?php if (Auth::can('validate', 'bulletin')): ?>
                                    <a href="/bulletins/validation" class="btn btn-warning btn-sm text-dark font-weight-bold">
                                        <i class="ph-duotone ph-check-square-offset me-1"></i><?= _("Validation Globale") ?>
                                    </a>
                                <?php endif; ?>

                                <?php if (Auth::can('generate', 'bulletin') || Auth::can('print', 'bulletin')): ?>
                                    <a href="/bulletins/print" class="btn btn-success btn-sm font-weight-bold">
                                        <i class="ph-duotone ph-printer me-1"></i><?= _("Impression Officielle A4") ?>
                                    </a>
                                <?php endif; ?>

                                <?php if (Auth::can('edit_appreciation_conseil', 'bulletin')): ?>
                                    <a href="/appreciation-conseil" class="btn btn-outline-light btn-sm">
                                        <i class="ph-duotone ph-chat-teardrop-text me-1"></i><?= _("Appréciations Conseil") ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- [ Main Content ] start -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5><?= _('Consulter les Résultats par Classe et Période') ?></h5>
                    </div>
                    <div class="card-body">
                        <form action="/bulletins/class_results" method="POST">
                            <?= csrf_field() ?>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="classe_id" class="form-label"><?= _('Classe') ?></label>
                                        <select name="classe_id" id="classe_id" class="form-select" required>
                                            <option value=""><?= _('-- Sélectionner une classe --') ?></option>
                                            <?php foreach ($classes as $classe): ?>
                                                <option value="<?= $classe['id_classe'] ?>"><?= htmlspecialchars(Classe::getFormattedName($classe)) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="sequence_id" class="form-label"><?= _('Séquence / Période') ?></label>
                                        <select name="sequence_id" id="sequence_id" class="form-select" required>
                                            <option value=""><?= _('-- Sélectionner une période --') ?></option>
                                            <?php foreach ($sequences as $sequence): ?>
                                                <option value="<?= $sequence['id'] ?>"><?= htmlspecialchars($sequence['nom']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary mt-3"><i class="ph-duotone ph-eye me-1"></i><?= _('Afficher les Résultats') ?></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ Main Content ] end -->
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>