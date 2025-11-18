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


<div class="mb-3">
    <label for="photo" class="form-label"><?= _('Photo') ?></label>
    <input type="file" class="form-control" id="photo" name="photo">
    <?php if (isset($eleve['photo']) && $eleve['photo']): ?>
        <img src="<?= htmlspecialchars($eleve['photo']) ?>" alt="Photo de l'élève" class="img-thumbnail mt-2" width="100">
    <?php endif; ?>
</div>
