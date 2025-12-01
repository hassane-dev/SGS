<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h5 class="m-b-10">Ajouter un Cours à l'Emploi du Temps</h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/home">Tableau de Bord</a></li>
                            <li class="breadcrumb-item"><a href="/emploi-du-temps">Emploi du Temps</a></li>
                            <li class="breadcrumb-item" aria-current="page">Ajouter un Cours</li>
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
                        <h5>Formulaire d'Ajout de Cours</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_GET['error']) && $_GET['error'] === 'conflict'): ?>
                            <div class="alert alert-danger" role="alert">
                                Conflit détecté ! Le professeur ou la classe est déjà occupé(e) à cette heure.
                            </div>
                        <?php endif; ?>

                        <form action="/emploi-du-temps/store" method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="classe_id" class="form-label">Classe</label>
                                    <select id="classe_id" name="classe_id" class="form-select" required>
                                        <option value="">Sélectionner une classe</option>
                                        <?php foreach ($data['classes'] as $classe): ?>
                                            <option value="<?= $classe['id_classe'] ?>"><?= htmlspecialchars($classe['nom_classe']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="matiere_id" class="form-label">Matière</label>
                                    <select id="matiere_id" name="matiere_id" class="form-select" required>
                                        <option value="">Sélectionner une matière</option>
                                        <?php foreach ($data['matieres'] as $matiere): ?>
                                            <option value="<?= $matiere['id_matiere'] ?>"><?= htmlspecialchars($matiere['nom_matiere']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="professeur_id" class="form-label">Professeur</label>
                                    <select id="professeur_id" name="professeur_id" class="form-select" required>
                                        <option value="">Sélectionner un professeur</option>
                                        <?php foreach ($data['professeurs'] as $prof): ?>
                                            <option value="<?= $prof['id_user'] ?>"><?= htmlspecialchars($prof['prenom'] . ' ' . $prof['nom']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="salle_id" class="form-label">Salle</label>
                                    <select id="salle_id" name="salle_id" class="form-select" required>
                                        <option value="">Sélectionner une salle</option>
                                        <?php foreach ($data['salles'] as $salle): ?>
                                            <option value="<?= $salle['id_salle'] ?>"><?= htmlspecialchars($salle['nom_salle']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                             <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="jour" class="form-label">Jour</label>
                                    <select id="jour" name="jour" class="form-select" required>
                                        <option value="Lundi">Lundi</option>
                                        <option value="Mardi">Mardi</option>
                                        <option value="Mercredi">Mercredi</option>
                                        <option value="Jeudi">Jeudi</option>
                                        <option value="Vendredi">Vendredi</option>
                                        <option value="Samedi">Samedi</option>
                                    </select>
                                </div>
                                 <div class="col-md-6 mb-3">
                                    <input type="hidden" name="annee_academique_id" value="<?= htmlspecialchars($data['annee_academique_id']) ?>">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="heure_debut" class="form-label">Heure de Début</label>
                                    <input type="time" id="heure_debut" name="heure_debut" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="heure_fin" class="form-label">Heure de Fin</label>
                                    <input type="time" id="heure_fin" name="heure_fin" class="form-control" required>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                            <a href="/emploi-du-temps" class="btn btn-secondary">Annuler</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ Main Content ] end -->
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
