<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label for="nom">Nom</label>
            <input type="text" class="form-control" id="nom" name="nom" value="<?= $eleve['nom'] ?? '' ?>" required>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="prenom">Prénom</label>
            <input type="text" class="form-control" id="prenom" name="prenom" value="<?= $eleve['prenom'] ?? '' ?>" required>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-6">
        <div class="form-group">
            <label for="date_naissance">Date de Naissance</label>
            <input type="date" class="form-control" id="date_naissance" name="date_naissance" value="<?= $eleve['date_naissance'] ?? '' ?>">
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="lieu_naissance">Lieu de Naissance</label>
            <input type="text" class="form-control" id="lieu_naissance" name="lieu_naissance" value="<?= $eleve['lieu_naissance'] ?? '' ?>">
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-6">
        <div class="form-group">
            <label for="sexe">Sexe</label>
            <select class="form-control" id="sexe" name="sexe">
                <option value="Masculin" <?= (isset($eleve['sexe']) && $eleve['sexe'] == 'Masculin') ? 'selected' : '' ?>>Masculin</option>
                <option value="Féminin" <?= (isset($eleve['sexe']) && $eleve['sexe'] == 'Féminin') ? 'selected' : '' ?>>Féminin</option>
            </select>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="nationalite">Nationalité</label>
            <input type="text" class="form-control" id="nationalite" name="nationalite" value="<?= $eleve['nationalite'] ?? '' ?>">
        </div>
    </div>
</div>

<div class="form-group mt-3">
    <label for="quartier">Quartier / Adresse</label>
    <input type="text" class="form-control" id="quartier" name="quartier" value="<?= $eleve['quartier'] ?? '' ?>">
</div>

<hr>
<h5 class="mt-4">Informations des Parents / Tuteur</h5>

<div class="row mt-3">
    <div class="col-md-6">
        <div class="form-group">
            <label for="nom_pere">Nom du Père</label>
            <input type="text" class="form-control" id="nom_pere" name="nom_pere" value="<?= $eleve['nom_pere'] ?? '' ?>">
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="profession_pere">Profession du Père</label>
            <input type="text" class="form-control" id="profession_pere" name="profession_pere" value="<?= $eleve['profession_pere'] ?? '' ?>">
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-6">
        <div class="form-group">
            <label for="nom_mere">Nom de la Mère</label>
            <input type="text" class="form-control" id="nom_mere" name="nom_mere" value="<?= $eleve['nom_mere'] ?? '' ?>">
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="profession_mere">Profession de la Mère</label>
            <input type="text" class="form-control" id="profession_mere" name="profession_mere" value="<?= $eleve['profession_mere'] ?? '' ?>">
        </div>
    </div>
</div>

<div class="form-group mt-3">
    <label for="tel_parent">Téléphone du Parent / Tuteur</label>
    <input type="tel" class="form-control" id="tel_parent" name="tel_parent" value="<?= $eleve['tel_parent'] ?? '' ?>">
</div>

<hr>
<h5 class="mt-4">Contact de l'élève (Optionnel)</h5>
<div class="row mt-3">
    <div class="col-md-6">
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" class="form-control" id="email" name="email" value="<?= $eleve['email'] ?? '' ?>">
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label for="telephone">Téléphone</label>
            <input type="tel" class="form-control" id="telephone" name="telephone" value="<?= $eleve['telephone'] ?? '' ?>">
        </div>
    </div>
</div>

<hr>

<div class="form-group mt-3">
    <label for="classe_id">Classe d'Inscription</label>
    <select class="form-control" id="classe_id" name="classe_id" required>
        <option value="">-- Sélectionner une classe --</option>
        <?php foreach ($classes as $classe): ?>
            <option value="<?= $classe['id_classe'] ?>" <?= (isset($eleve['classe_id']) && $eleve['classe_id'] == $classe['id_classe']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($classe['nom_classe']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <small class="form-text text-muted">L'élève sera pré-inscrit dans cette classe en attente de la validation du paiement.</small>
</div>

<div class="form-group mt-3">
    <label for="photo">Photo</label>
    <input type="file" class="form-control-file" id="photo" name="photo">
    <?php if (isset($eleve['photo']) && $eleve['photo']): ?>
        <img src="<?= htmlspecialchars($eleve['photo']) ?>" alt="Photo de l'élève" class="img-thumbnail mt-2" width="100">
    <?php endif; ?>
</div>