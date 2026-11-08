<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?= _("Confirmer la Réinscription Scolaire") ?></h1>
        <a href="/reinscription" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> <?= _("Retour à la recherche") ?></a>
    </div>

    <?php if (isset($_GET['error'])): ?>
        <?php if ($_GET['error'] === 'enrolled'): ?>
            <div class="alert alert-danger shadow-sm"><i class="fas fa-exclamation-triangle me-2"></i><?= _("Cet élève est déjà inscrit pour l'année académique en cours dans cet établissement.") ?></div>
        <?php elseif ($_GET['error'] === 'class_full'): ?>
            <div class="alert alert-danger shadow-sm"><i class="fas fa-ban me-2"></i><?= _("La classe sélectionnée est complète (capacité maximale atteinte). Veuillez choisir une autre classe ou solliciter une dérogation administrative.") ?></div>
        <?php elseif ($_GET['error'] === 'exclu'): ?>
            <div class="alert alert-danger shadow-sm"><i class="fas fa-user-slash me-2"></i><?= _("Réinscription impossible : l'élève a fait l'objet d'une décision d'exclusion scolaire définitive.") ?></div>
        <?php elseif ($_GET['error'] === 'deliberation'): ?>
            <div class="alert alert-warning shadow-sm"><i class="fas fa-gavel me-2"></i><?= _("Réinscription suspendue : le dossier de l'élève est en cours de délibération par le conseil de classe.") ?></div>
        <?php elseif ($_GET['error'] === 'incomplete'): ?>
            <div class="alert alert-warning shadow-sm"><i class="fas fa-file-alt me-2"></i><?= _("Dossier incomplet : les résultats scolaires de l'année précédente ne permettent pas encore d'établir une décision définitive.") ?></div>
        <?php elseif ($_GET['error'] === 'end_of_secondary'): ?>
            <div class="alert alert-info shadow-sm"><i class="fas fa-graduation-cap me-2"></i><?= _("Fin de cursus secondaire : l'élève a terminé le cycle de Terminale.") ?></div>
        <?php elseif ($_GET['error'] === 'no_history'): ?>
            <div class="alert alert-warning shadow-sm">
                <i class="fas fa-user-plus me-2"></i>
                <?= _("Cet élève ne possède aucun historique scolaire permettant une réinscription. Veuillez utiliser le module d'inscription initiale.") ?>
                <div class="mt-2">
                    <a href="/inscriptions" class="btn btn-sm btn-primary"><i class="fas fa-arrow-right me-1"></i><?= _("Aller aux Inscriptions initiales") ?></a>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-danger shadow-sm"><i class="fas fa-exclamation-circle me-2"></i><?= _("Une erreur est survenue lors du traitement de la réinscription.") ?></div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="row">
        <!-- Colonne 1 : Fiche Élève et Situation Pédagogique -->
        <div class="col-lg-6 mb-4">
            <!-- Carte 1 : Informations de l'Élève -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0"><i class="fas fa-user-graduate me-2 text-primary"></i><?= _("Informations de l'Élève") ?></h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <th class="text-muted" style="width: 40%;"><?= _("Matricule / Identifiant") ?> :</th>
                            <td class="fw-bold font-monospace"><?= htmlspecialchars($eleve['matricule'] ?? $eleve['identifiant_public'] ?? 'N/A') ?></td>
                        </tr>
                        <tr>
                            <th class="text-muted"><?= _("Nom complet") ?> :</th>
                            <td class="fw-bold fs-5 text-dark"><?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></td>
                        </tr>
                        <tr>
                            <th class="text-muted"><?= _("Date de naissance") ?> :</th>
                            <td><?= !empty($eleve['date_naissance']) ? htmlspecialchars(date('d/m/Y', strtotime($eleve['date_naissance']))) : 'N/A' ?></td>
                        </tr>
                        <tr>
                            <th class="text-muted"><?= _("Dernière classe") ?> :</th>
                            <td>
                                <?php if (!empty($latestEnrollment)): ?>
                                    <span class="badge bg-light-primary text-primary fs-6"><?= htmlspecialchars(Classe::getFormattedName($latestEnrollment)) ?></span>
                                    <span class="text-muted ms-1">(<?= htmlspecialchars($latestEnrollment['nom_cycle'] ?? '') ?>)</span>
                                <?php else: ?>
                                    <span class="text-muted"><?= _("Aucune classe précédente enregistrée") ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted"><?= _("Année scolaire préc.") ?> :</th>
                            <td>
                                <?= !empty($latestEnrollment['annee_libelle']) ? htmlspecialchars($latestEnrollment['annee_libelle']) : 'N/A' ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Carte 2 : Situation Pédagogique -->
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0"><i class="fas fa-balance-scale me-2 text-primary"></i><?= _("Situation Pédagogique & Décision") ?></h5>
                </div>
                <div class="card-body">
                    <?php
                        $dec = $pedagogicalSituation['decision'] ?? 'non_determine';
                        $badgeClass = 'bg-secondary';
                        $iconClass = 'fa-question-circle';
                        if ($dec === 'admis') { $badgeClass = 'bg-success'; $iconClass = 'fa-check-circle'; }
                        elseif ($dec === 'redoublement') { $badgeClass = 'bg-warning text-dark'; $iconClass = 'fa-redo'; }
                        elseif ($dec === 'deliberation') { $badgeClass = 'bg-info text-dark'; $iconClass = 'fa-gavel'; }
                        elseif ($dec === 'exclu') { $badgeClass = 'bg-danger'; $iconClass = 'fa-times-circle'; }
                    ?>
                    <div class="d-flex align-items-center mb-3">
                        <span class="badge <?= $badgeClass ?> fs-6 py-2 px-3 me-2">
                            <i class="fas <?= $iconClass ?> me-1"></i>
                            <?= htmlspecialchars($pedagogicalSituation['decision_libelle'] ?? _("Non déterminé")) ?>
                        </span>
                        <?php if ($pedagogicalSituation['annual_average'] !== null): ?>
                            <span class="fs-5 fw-bold ms-auto">
                                <?= _("Moyenne Annuelle") ?> : <span class="text-primary font-monospace"><?= number_format((float)$pedagogicalSituation['annual_average'], 2, ',', ' ') ?> / 20</span>
                            </span>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($pedagogicalSituation['justification'])): ?>
                        <div class="alert alert-light border mb-0">
                            <strong><?= _("Justification") ?> :</strong>
                            <p class="mb-0 text-muted"><?= htmlspecialchars($pedagogicalSituation['justification']) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Colonne 2 : Proposition d'Affectation et Confirmation -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title text-white mb-0"><i class="fas fa-clipboard-check me-2"></i><?= _("Proposition d'Affectation & Capacités") ?></h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($active_year)): ?>
                        <div class="alert alert-info py-2 px-3 mb-3">
                            <i class="fas fa-calendar-alt me-1"></i> <?= _("Année scolaire cible") ?> : <strong><?= htmlspecialchars($active_year['libelle']) ?></strong>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-danger py-2 px-3 mb-3">
                            <i class="fas fa-exclamation-triangle me-1"></i> <?= _("Aucune année académique active n'est définie pour cet établissement.") ?>
                        </div>
                    <?php endif; ?>

                    <!-- Encadré Niveau & Progression -->
                    <?php if ($destination['can_proceed']): ?>
                        <div class="border rounded p-3 bg-light mb-3">
                            <h6 class="fw-bold text-dark mb-2"><?= _("Progression Pédagogique Validée") ?></h6>
                            <div class="row g-2">
                                <div class="col-6">
                                    <small class="text-muted d-block"><?= _("Niveau de destination") ?></small>
                                    <span class="fs-5 fw-bold text-primary"><?= htmlspecialchars($destination['niveau']) ?></span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block"><?= _("Série") ?></small>
                                    <span class="fs-5 fw-bold text-secondary"><?= !empty($destination['serie']) ? htmlspecialchars($destination['serie']) : _("Tronc commun / N/A") ?></span>
                                </div>
                            </div>
                            <div class="mt-2 text-muted small">
                                <i class="fas fa-info-circle me-1"></i><?= htmlspecialchars($destination['reason'] ?? '') ?>
                            </div>
                        </div>

                        <!-- Analyse des Places Disponibles -->
                        <?php if (!empty($availableData['all_complete'])): ?>
                            <div class="alert alert-danger mb-3">
                                <h6 class="alert-heading fw-bold"><i class="fas fa-door-closed me-2"></i><?= _("Toutes les classes de ce niveau sont complètes !") ?></h6>
                                <p class="mb-0 small"><?= _("Aucune place disponible dans ce niveau pour l'année scolaire cible. Une intervention administrative avec dérogation est requise pour autoriser un dépassement de capacité.") ?></p>
                            </div>
                        <?php elseif (!empty($suggestedClasse)): ?>
                            <div class="alert alert-success mb-3">
                                <h6 class="alert-heading fw-bold"><i class="fas fa-check-circle me-2"></i><?= _("Classe Recommandée Automatiquement") ?> : <?= htmlspecialchars($suggestedClasse['nom_classe']) ?></h6>
                                <div class="row g-2 mt-1 small">
                                    <div class="col-4">
                                        <strong><?= _("Capacité") ?> :</strong> <?= $suggestedClasse['capacite'] !== null ? (int)$suggestedClasse['capacite'] : _("Non configurée") ?>
                                    </div>
                                    <div class="col-4">
                                        <strong><?= _("Effectif actuel") ?> :</strong> <?= (int)$suggestedClasse['effectif'] ?>
                                    </div>
                                    <div class="col-4">
                                        <strong><?= _("Places restantes") ?> :</strong>
                                        <span class="badge bg-success"><?= (int)$suggestedClasse['places_restantes'] ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Formulaire d'affectation -->
                        <form action="/reinscription/process" method="POST">
                            <?= csrf_field() ?>
                            <input type="hidden" name="eleve_id" value="<?= $eleve['id_eleve'] ?>">
                            <input type="hidden" name="annee_academique_id" value="<?= $active_year['id'] ?? '' ?>">

                            <div class="mb-3">
                                <label for="classe_id" class="form-label fw-bold"><?= _("Sélectionner la classe de destination") ?> :</label>
                                <select class="form-select" id="classe_id" name="classe_id" required>
                                    <option value="">-- <?= _("Choisir une classe") ?> --</option>
                                    <?php foreach ($classes as $classe): ?>
                                        <?php
                                            $isComplete = !empty($classe['is_complete']);
                                            $isUnconfigured = !empty($classe['is_unconfigured']);
                                            $isSuggested = (!empty($suggestedClasse) && (int)$classe['id_classe'] === (int)$suggestedClasse['id_classe']);
                                            $remText = '';
                                            if ($isUnconfigured) {
                                                $remText = ' - ' . _("CAPACITÉ NON CONFIGURÉE (bloquée)");
                                            } elseif ($isComplete) {
                                                $remText = ' - ' . _("COMPLÈTE (0 place)");
                                            } elseif (isset($classe['places_restantes'])) {
                                                $remText = ' - ' . sprintf(_("%d places restantes"), (int)$classe['places_restantes']);
                                            }
                                        ?>
                                        <option value="<?= $classe['id_classe'] ?>"
                                            <?= $isSuggested ? 'selected' : '' ?>
                                            <?= ($isComplete && empty($_POST['derogation_capacite'])) ? 'class="text-danger"' : '' ?>
                                        >
                                            <?= htmlspecialchars($classe['nom_classe']) ?> (<?= htmlspecialchars($classe['nom_cycle'] ?? '') ?>)<?= $remText ?><?= $isSuggested ? ' ★ [' . _("Recommandée") . ']' : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <?php if (!empty($availableData['all_complete'])): ?>
                                <div class="form-check mb-3 p-3 border rounded bg-light-warning">
                                    <input class="form-check-input" type="checkbox" id="derogation_capacite" name="derogation_capacite" value="1" required>
                                    <label class="form-check-label text-dark fw-bold small" for="derogation_capacite">
                                        <?= _("Dérogation administrative exceptionnelle : j'autorise le sur-effectif pour inscrire l'élève malgré la capacité maximale atteinte.") ?>
                                    </label>
                                </div>
                            <?php endif; ?>

                            <div class="mt-4 d-flex justify-content-between align-items-center">
                                <a href="/reinscription" class="btn btn-secondary"><?= _("Annuler") ?></a>
                                <button type="submit" class="btn btn-primary btn-lg shadow-sm">
                                    <i class="fas fa-check-circle me-1"></i> <?= _("Confirmer la Réinscription") ?>
                                </button>
                            </div>
                        </form>

                    <?php else: ?>
                        <!-- Blocage explicite : progression impossible -->
                        <div class="alert alert-danger py-3">
                            <h5 class="alert-heading fw-bold mb-2"><i class="fas fa-ban me-2"></i><?= _("Réinscription Ordinaire Non Autorisée") ?></h5>
                            <p class="mb-0"><?= htmlspecialchars($destination['reason'] ?? _("La situation scolaire actuelle de l'élève ne permet pas une réinscription automatique.")) ?></p>
                        </div>

                        <?php if ($destination['type'] === 'no_history'): ?>
                            <div class="alert alert-warning mt-3 border">
                                <i class="fas fa-info-circle me-2"></i>
                                <?= _("Cet élève ne possède aucun historique scolaire permettant une réinscription. Veuillez utiliser le module d'inscription initiale.") ?>
                            </div>
                            <div class="mt-3">
                                <a href="/inscriptions" class="btn btn-primary me-2">
                                    <i class="fas fa-user-plus me-1"></i> <?= _("Aller au module d'inscription initiale") ?>
                                </a>
                                <a href="/reinscription" class="btn btn-secondary"><?= _("Retour") ?></a>
                            </div>
                        <?php elseif ($destination['type'] === 'deliberation' || $destination['type'] === 'incomplete'): ?>
                            <div class="mt-3">
                                <a href="/admissions" class="btn btn-outline-primary me-2">
                                    <i class="fas fa-gavel me-1"></i> <?= _("Accéder aux Délibérations & Décisions") ?>
                                </a>
                                <a href="/reinscription" class="btn btn-secondary"><?= _("Retour") ?></a>
                            </div>
                        <?php else: ?>
                            <div class="mt-3">
                                <a href="/reinscription" class="btn btn-secondary"><?= _("Retour à la recherche") ?></a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
