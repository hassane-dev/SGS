<div class="summary mt-4">
    <form action="/bulletins/appreciation/save" method="POST">
        <input type="hidden" name="eleve_id" value="<?= $bulletin['eleve']['id_eleve'] ?>">
        <input type="hidden" name="sequence_id" value="<?= $bulletin['sequence']['id'] ?>">
        <input type="hidden" name="moyenne_generale" value="<?= $bulletin['moyenne_generale'] ?>">

        <div class="row">
            <div class="col-md-6">
                <p><strong>Moyenne Générale :</strong> <span class="h5"><?= number_format($bulletin['moyenne_generale'], 2) ?> / 20</span></p>

                <?php if (Auth::can('validate', 'bulletin')): ?>
                    <div class="form-group">
                        <label for="rang"><strong>Rang de l'élève</strong></label>
                        <input type="text" name="rang" id="rang" class="form-control" value="<?= htmlspecialchars($bulletin['bulletin_record']['rang'] ?? '') ?>">
                    </div>
                <?php else: ?>
                    <p><strong>Rang :</strong> <?= htmlspecialchars($bulletin['bulletin_record']['rang'] ?? 'Non défini') ?></p>
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <div class="border p-3">
                    <h5>Appréciation du Conseil de Classe</h5>
                    <?php if (Auth::can('validate', 'bulletin')): ?>
                        <div class="form-group">
                            <textarea name="appreciation" class="form-control" rows="3"><?= htmlspecialchars($bulletin['bulletin_record']['appreciation'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="statut"><strong>Statut du bulletin</strong></label>
                            <select name="statut" id="statut" class="form-control">
                                <option value="provisoire" <?= ($bulletin['bulletin_record']['statut'] ?? '') == 'provisoire' ? 'selected' : '' ?>>Provisoire</option>
                                <option value="valide" <?= ($bulletin['bulletin_record']['statut'] ?? '') == 'valide' ? 'selected' : '' ?>>Validé</option>
                                <option value="publie" <?= ($bulletin['bulletin_record']['statut'] ?? '') == 'publie' ? 'selected' : '' ?>>Publié</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success mt-2">Enregistrer l'Appréciation</button>
                    <?php else: ?>
                        <p><?= htmlspecialchars($bulletin['bulletin_record']['appreciation'] ?? 'Aucune appréciation.') ?></p>
                        <p><strong>Statut :</strong> <span class="badge badge-info"><?= ucfirst(htmlspecialchars($bulletin['bulletin_record']['statut'] ?? 'Provisoire')) ?></span></p>
                    <?php endif; ?>
                    <p class="mt-3"><strong>Le Chef d'établissement</strong></p>
                    <?php
                    require_once __DIR__ . '/../../../models/User.php';
                    require_once __DIR__ . '/../../../models/ParametreUtilisateur.php';
                    require_once __DIR__ . '/../../../models/ParamLycee.php';

                    // Find director or proviseur strictly filtered by the student's lycée
                    $lyceeId = $bulletin['eleve']['lycee_id'] ?? null;
                    $dirUser = User::findOneByRoleNameAndLycee('proviseur', $lyceeId) ?: User::findOneByRoleNameAndLycee('directeur', $lyceeId);
                    $dirSettings = null;
                    if ($dirUser) {
                        $dirSettings = ParametreUtilisateur::findByUserId($dirUser['id_user']);
                    }
                    ?>
                    <div style="height: 65px; display: flex; align-items: center; justify-content: start; position: relative; margin-top: 5px; margin-bottom: 5px;">
                        <?php if ($dirSettings && !empty($dirSettings->signature)): ?>
                            <img src="<?= htmlspecialchars($dirSettings->signature) ?>" alt="Signature Directeur" style="max-height: 55px; position: absolute; z-index: 2; left: 20px;">
                        <?php endif; ?>
                        <?php
                        $lyceeParams = ParamLycee::findByLyceeId($lyceeId);
                        if ($lyceeParams && !empty($lyceeParams['tampon_ecole'])):
                        ?>
                            <img src="<?= htmlspecialchars($lyceeParams['tampon_ecole']) ?>" alt="Tampon Établissement" style="max-height: 60px; opacity: 0.75; position: absolute; z-index: 1; left: 10px;">
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>