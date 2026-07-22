<?php
$title = _("Politique Financière de l'Établissement");
ob_start();

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
                            <h5 class="m-b-10"><?= htmlspecialchars($title) ?></h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/home"><?= _('Tableau de Bord') ?></a></li>
                            <li class="breadcrumb-item"><a href="/settings"><?= _('Paramètres Lycée') ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _('Politique Financière') ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- [ Main Content ] start -->
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <h5><?= _('Règles Financières Globales') ?></h5>
                        <small class="text-muted"><?= _("Ces règles s'appliquent à tous les élèves de l'établissement pour l'accès aux notes, l'impression des bulletins et l'activation de leur dossier.") ?></small>
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

                        <form action="/settings/politique-financiere/update" method="POST">

                            <!-- 1. Activation de l'élève -->
                            <h5 class="text-primary mb-3 mt-2"><i class="ph-duotone ph-user-check me-2"></i><?= _("Activation de l'Élève") ?></h5>
                            <p class="text-muted small"><?= _("Définissez le seuil de versement des frais d'inscription requis pour activer automatiquement le dossier académique et financier d'un élève.") ?></p>
                            <div class="row mb-4">
                                <div class="col-md-6 mb-3">
                                    <label for="activation_seuil_type" class="form-label"><?= _("Seuil d'activation") ?></label>
                                    <select class="form-select" name="activation_seuil_type" id="activation_seuil_type" required>
                                        <option value="100" <?= ($policy['activation_seuil_type'] ?? '') === '100' ? 'selected' : '' ?>><?= _("100 % des frais d'inscription") ?></option>
                                        <option value="75" <?= ($policy['activation_seuil_type'] ?? '') === '75' ? 'selected' : '' ?>><?= _("75 % des frais d'inscription") ?></option>
                                        <option value="50" <?= ($policy['activation_seuil_type'] ?? '') === '50' ? 'selected' : '' ?>><?= _("50 % des frais d'inscription") ?></option>
                                        <option value="montant_minimum" <?= ($policy['activation_seuil_type'] ?? '') === 'montant_minimum' ? 'selected' : '' ?>><?= _('Montant fixe minimum') ?></option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3" id="activation_valeur_wrapper" style="display: none;">
                                    <label for="activation_seuil_valeur" class="form-label"><?= _('Montant minimum (FCFA)') ?></label>
                                    <input type="number" step="0.01" class="form-control" name="activation_seuil_valeur" id="activation_seuil_valeur" value="<?= htmlspecialchars($policy['activation_seuil_valeur'] ?? '') ?>" placeholder="<?= _('Ex: 25000') ?>">
                                </div>
                            </div>

                            <hr>

                            <!-- 2. Consultation des Notes -->
                            <h5 class="text-primary mb-3 mt-3"><i class="ph-duotone ph-graduation-cap me-2"></i><?= _('Consultation des Notes') ?></h5>
                            <p class="text-muted small"><?= _("Nombre de mensualités qui doivent être soldées par l'élève afin de lui permettre, ainsi qu'à ses parents, de consulter ses notes de devoirs et compositions.") ?></p>
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label for="notes_seuil_mensualites" class="form-label"><?= _('Nombre de mensualités exigées') ?></label>
                                    <input type="number" min="0" max="12" class="form-control" name="notes_seuil_mensualites" id="notes_seuil_mensualites" value="<?= htmlspecialchars($policy['notes_seuil_mensualites'] ?? '0') ?>" required>
                                    <small class="text-muted"><?= _('Indiquez 0 pour n\'exiger aucun paiement préalable pour la consultation des notes.') ?></small>
                                </div>
                            </div>

                            <hr>

                            <!-- 3. Impression des Bulletins -->
                            <h5 class="text-primary mb-3 mt-3"><i class="ph-duotone ph-file-text me-2"></i><?= _('Impression des Bulletins') ?></h5>
                            <p class="text-muted small"><?= _('Règle de blocage financier lors de l\'édition et de l\'impression des bulletins de notes scolaires.') ?></p>
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <div class="form-check form-switch custom-switch-v1">
                                        <input type="hidden" name="bulletin_seuil_complet" value="0">
                                        <input type="checkbox" class="form-check-input input-primary" name="bulletin_seuil_complet" id="bulletin_seuil_complet" value="1" <?= !empty($policy['bulletin_seuil_complet']) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="bulletin_seuil_complet">
                                            <strong><?= _('Exiger le paiement de toutes les mensualités de la période') ?></strong>
                                        </label>
                                    </div>
                                    <p class="text-muted small ms-5 mt-1"><?= _('Si activé, l\'élève doit avoir soldé l\'intégralité des mensualités du trimestre ou semestre en cours pour que son bulletin soit imprimable.') ?></p>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-sm-12">
                                    <button type="submit" class="btn btn-primary"><i class="ph-duotone ph-floppy-disk me-1"></i><?= _('Enregistrer la Politique') ?></button>
                                    <a href="/settings" class="btn btn-light"><i class="ph-duotone ph-arrow-left me-1"></i><?= _('Annuler') ?></a>
                                </div>
                            </div>
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
    const typeSelect = document.getElementById('activation_seuil_type');
    const valWrapper = document.getElementById('activation_valeur_wrapper');
    const valInput = document.getElementById('activation_seuil_valeur');

    function toggleValueField() {
        if (typeSelect.value === 'montant_minimum') {
            valWrapper.style.display = 'block';
            valInput.setAttribute('required', 'required');
        } else {
            valWrapper.style.display = 'none';
            valInput.removeAttribute('required');
        }
    }

    typeSelect.addEventListener('change', toggleValueField);
    toggleValueField(); // Run once initially
});
</script>

<?php
require_once __DIR__ . '/../layouts/footer_able.php';
$content = ob_get_clean();
echo $content;
?>
