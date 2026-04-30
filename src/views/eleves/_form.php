<link rel="stylesheet" href="/assets/libs/cropper/cropper.min.css">

<div class="row g-3">
    <div class="col-md-6">
        <label for="nom" class="form-label"><?= _('Nom') ?></label>
        <input type="text" class="form-control" id="nom" name="nom" value="<?= $eleve['nom'] ?? '' ?>" required>
    </div>
    <div class="col-md-6">
        <label for="prenom" class="form-label"><?= _('Prénom') ?></label>
        <input type="text" class="form-control" id="prenom" name="prenom" value="<?= $eleve['prenom'] ?? '' ?>" required>
    </div>

    <div class="col-md-6">
        <label for="date_naissance" class="form-label"><?= _('Date de Naissance') ?></label>
        <input type="date" class="form-control" id="date_naissance" name="date_naissance" value="<?= $eleve['date_naissance'] ?? '' ?>">
    </div>
    <div class="col-md-6">
        <label for="lieu_naissance" class="form-label"><?= _('Lieu de Naissance') ?></label>
        <input type="text" class="form-control" id="lieu_naissance" name="lieu_naissance" value="<?= $eleve['lieu_naissance'] ?? '' ?>">
    </div>

    <div class="col-md-6">
        <label for="sexe" class="form-label"><?= _('Sexe') ?></label>
        <select class="form-select" id="sexe" name="sexe">
            <option value="Masculin" <?= (isset($eleve['sexe']) && $eleve['sexe'] == 'Masculin') ? 'selected' : '' ?>><?= _('Masculin') ?></option>
            <option value="Féminin" <?= (isset($eleve['sexe']) && $eleve['sexe'] == 'Féminin') ? 'selected' : '' ?>><?= _('Féminin') ?></option>
        </select>
    </div>
    <div class="col-md-6">
        <label for="nationalite" class="form-label"><?= _('Nationalité') ?></label>
        <input type="text" class="form-control" id="nationalite" name="nationalite" value="<?= $eleve['nationalite'] ?? '' ?>">
    </div>

    <div class="col-12">
        <label for="quartier" class="form-label"><?= _('Quartier / Adresse') ?></label>
        <input type="text" class="form-control" id="quartier" name="quartier" value="<?= $eleve['quartier'] ?? '' ?>">
    </div>
</div>

<h5 class="border-bottom pb-2 mt-4 mb-3"><?= _('Informations des Parents / Tuteur') ?></h5>

<div class="row g-3">
    <div class="col-md-6">
        <label for="nom_pere" class="form-label"><?= _('Nom du Père') ?></label>
        <input type="text" class="form-control" id="nom_pere" name="nom_pere" value="<?= $eleve['nom_pere'] ?? '' ?>">
    </div>
    <div class="col-md-6">
        <label for="profession_pere" class="form-label"><?= _('Profession du Père') ?></label>
        <input type="text" class="form-control" id="profession_pere" name="profession_pere" value="<?= $eleve['profession_pere'] ?? '' ?>">
    </div>

    <div class="col-md-6">
        <label for="nom_mere" class="form-label"><?= _('Nom de la Mère') ?></label>
        <input type="text" class="form-control" id="nom_mere" name="nom_mere" value="<?= $eleve['nom_mere'] ?? '' ?>">
    </div>
    <div class="col-md-6">
        <label for="profession_mere" class="form-label"><?= _('Profession de la Mère') ?></label>
        <input type="text" class="form-control" id="profession_mere" name="profession_mere" value="<?= $eleve['profession_mere'] ?? '' ?>">
    </div>

    <div class="col-12">
        <label for="tel_parent" class="form-label"><?= _('Téléphone du Parent / Tuteur') ?></label>
        <input type="tel" class="form-control" id="tel_parent" name="tel_parent" value="<?= $eleve['tel_parent'] ?? '' ?>">
    </div>
</div>

<h5 class="border-bottom pb-2 mt-4 mb-3"><?= _('Contact de l\'élève (Optionnel)') ?></h5>
<div class="row g-3">
    <div class="col-md-6">
        <label for="email" class="form-label"><?= _('Email') ?></label>
        <input type="email" class="form-control" id="email" name="email" value="<?= $eleve['email'] ?? '' ?>">
    </div>
    <div class="col-md-6">
        <label for="telephone" class="form-label"><?= _('Téléphone') ?></label>
        <input type="tel" class="form-control" id="telephone" name="telephone" value="<?= $eleve['telephone'] ?? '' ?>">
    </div>
</div>

<h5 class="border-bottom pb-2 mt-4 mb-3"><?= _('Photo de l\'élève') ?></h5>
<div class="mb-3">
    <div class="d-flex align-items-center gap-3">
        <div id="photo-preview-container">
            <?php if (isset($eleve['photo']) && $eleve['photo']): ?>
                <img src="<?= htmlspecialchars($eleve['photo']) ?>" alt="Photo de l'élève" class="img-thumbnail" id="photo-preview" style="width: 150px; height: 200px; object-fit: cover;">
            <?php else: ?>
                <img src="/assets/img/default-avatar.png" alt="Photo de l'élève" class="img-thumbnail" id="photo-preview" style="width: 150px; height: 200px; object-fit: cover;">
            <?php endif; ?>
        </div>
        <div>
            <button type="button" class="btn btn-outline-primary mb-2 d-block w-100" id="btn-browse-photo">
                <i class="ph-duotone ph-folder-open me-2"></i><?= _('Choisir un fichier') ?>
            </button>
            <button type="button" class="btn btn-outline-primary d-block w-100" id="btn-camera-photo">
                <i class="ph-duotone ph-camera me-2"></i><?= _('Prendre une photo') ?>
            </button>
            <input type="file" id="input-photo" accept="image/*" class="d-none">
            <input type="file" id="input-camera" accept="image/*" capture="camera" class="d-none">
            <input type="hidden" name="cropped_photo" id="cropped_photo">
        </div>
    </div>
    <small class="text-muted d-block mt-2"><?= _('Format recommandé : Portrait (3:4). La photo sera automatiquement redimensionnée.') ?></small>
</div>

<!-- Modal de Recadrage -->
<div class="modal fade" id="cropperModal" tabindex="-1" aria-labelledby="cropperModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cropperModalLabel"><?= _('Recadrer la photo') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="img-container">
                    <img id="cropper-image" src="" style="max-width: 100%;">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= _('Annuler') ?></button>
                <button type="button" class="btn btn-primary" id="btn-crop-save"><?= _('Valider le recadrage') ?></button>
            </div>
        </div>
    </div>
</div>

<script src="/assets/libs/cropper/cropper.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputPhoto = document.getElementById('input-photo');
    const inputCamera = document.getElementById('input-camera');
    const btnBrowse = document.getElementById('btn-browse-photo');
    const btnCamera = document.getElementById('btn-camera-photo');
    const photoPreview = document.getElementById('photo-preview');
    const cropperModal = new bootstrap.Modal(document.getElementById('cropperModal'));
    const cropperImage = document.getElementById('cropper-image');
    const btnCropSave = document.getElementById('btn-crop-save');
    const croppedPhotoInput = document.getElementById('cropped_photo');

    let cropper;

    btnBrowse.addEventListener('click', () => inputPhoto.click());
    btnCamera.addEventListener('click', () => inputCamera.click());

    [inputPhoto, inputCamera].forEach(input => {
        input.addEventListener('change', function(e) {
            const files = e.target.files;
            if (files && files.length > 0) {
                const file = files[0];
                const reader = new FileReader();
                reader.onload = function(event) {
                    cropperImage.src = event.target.result;
                    cropperModal.show();
                };
                reader.readAsDataURL(file);
            }
        });
    });

    document.getElementById('cropperModal').addEventListener('shown.bs.modal', function() {
        cropper = new Cropper(cropperImage, {
            aspectRatio: 3 / 4,
            viewMode: 1,
            autoCropArea: 1,
        });
    });

    document.getElementById('cropperModal').addEventListener('hidden.bs.modal', function() {
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
        // Reset inputs to allow selecting the same file again
        inputPhoto.value = '';
        inputCamera.value = '';
    });

    btnCropSave.addEventListener('click', function() {
        if (cropper) {
            const canvas = cropper.getCroppedCanvas({
                width: 300,
                height: 400,
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high',
            });

            const croppedDataUrl = canvas.toDataURL('image/jpeg', 0.8);
            photoPreview.src = croppedDataUrl;
            croppedPhotoInput.value = croppedDataUrl;
            cropperModal.hide();
        }
    });
});
</script>
