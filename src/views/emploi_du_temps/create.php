<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h5 class="m-b-10"><?= _("Ajouter un Cours à l'Emploi du Temps") ?></h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/home"><?= _('Tableau de Bord') ?></a></li>
                            <li class="breadcrumb-item"><a href="/emploi-du-temps"><?= _('Emploi du Temps') ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _('Ajouter un Cours') ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- [ Main Content ] start -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><?= _("Formulaire d'Ajout de Cours") ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_GET['error']) && $_GET['error'] === 'conflict'): ?>
                            <div class="alert alert-danger" role="alert">
                                <?= _("Conflit détecté ! Le professeur ou la classe est déjà occupé(e) à cette heure.") ?>
                            </div>
                        <?php endif; ?>

                        <form action="/emploi-du-temps/store" method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="classe_id" class="form-label"><?= _('Classe') ?></label>
                                    <select id="classe_id" name="classe_id" class="form-select" required>
                                        <option value=""><?= _('Sélectionner une classe') ?></option>
                                        <?php foreach ($data['classes'] as $classe): ?>
                                            <option value="<?= $classe['id_classe'] ?>"><?= htmlspecialchars(Classe::getFormattedName($classe)) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="matiere_id" class="form-label"><?= _('Matière') ?></label>
                                    <select id="matiere_id" name="matiere_id" class="form-select" required>
                                        <option value=""><?= _('Sélectionner une matière') ?></option>
                                        <?php foreach ($data['matieres'] as $matiere): ?>
                                            <option value="<?= $matiere['id_matiere'] ?>"><?= htmlspecialchars($matiere['nom_matiere']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="professeur_id" class="form-label"><?= _('Professeur') ?></label>
                                    <select id="professeur_id" name="professeur_id" class="form-select" required>
                                        <option value=""><?= _('Sélectionner un professeur') ?></option>
                                        <?php foreach ($data['professeurs'] as $prof): ?>
                                            <option value="<?= $prof['id_user'] ?>"><?= htmlspecialchars($prof['prenom'] . ' ' . $prof['nom']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="salle_id" class="form-label"><?= _('Salle') ?></label>
                                    <select id="salle_id" name="salle_id" class="form-select">
                                        <option value=""><?= _('Sélectionner une salle (optionnel)') ?></option>
                                        <?php foreach ($data['salles'] as $salle): ?>
                                            <option value="<?= $salle['id_salle'] ?>"><?= htmlspecialchars($salle['nom_salle']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                             <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="jour" class="form-label"><?= _('Jour') ?></label>
                                    <select id="jour" name="jour" class="form-select" required>
                                        <option value="Lundi"><?= _('Lundi') ?></option>
                                        <option value="Mardi"><?= _('Mardi') ?></option>
                                        <option value="Mercredi"><?= _('Mercredi') ?></option>
                                        <option value="Jeudi"><?= _('Jeudi') ?></option>
                                        <option value="Vendredi"><?= _('Vendredi') ?></option>
                                        <option value="Samedi"><?= _('Samedi') ?></option>
                                    </select>
                                </div>
                                 <div class="col-md-6 mb-3">
                                    <input type="hidden" name="annee_academique_id" value="<?= htmlspecialchars($data['annee_academique_id']) ?>">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="heure_debut" class="form-label"><?= _('Heure de Début') ?></label>
                                    <input type="time" id="heure_debut" name="heure_debut" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="heure_fin" class="form-label"><?= _('Heure de Fin') ?></label>
                                    <input type="time" id="heure_fin" name="heure_fin" class="form-control" required>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary"><?= _('Enregistrer') ?></button>
                            <a href="/emploi-du-temps" class="btn btn-secondary"><?= _('Annuler') ?></a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ Main Content ] end -->
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
