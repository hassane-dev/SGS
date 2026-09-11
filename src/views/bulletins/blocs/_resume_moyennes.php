<?php
require_once __DIR__ . '/../../../services/EvaluationCalculationService.php';

$moyenneGen = $bulletin['moyenne_generale'] ?? null;
$institutionalAppreciation = EvaluationCalculationService::getInstitutionalAppreciation($moyenneGen);
$bulletinStatut = $bulletin['bulletin_record']['statut'] ?? 'provisoire';

$statusBadgeClass = 'bg-secondary';
if ($bulletinStatut === 'valide') {
    $statusBadgeClass = 'bg-success';
} elseif ($bulletinStatut === 'publie') {
    $statusBadgeClass = 'bg-info';
}
?>

<div class="summary mt-4">
    <div class="row">
        <div class="col-md-6">
            <p><strong><?= _('Moyenne Générale') ?> :</strong> <span class="h5"><?= ($moyenneGen !== null) ? number_format($moyenneGen, 2) . ' / 20' : _('N/A') ?></span></p>

            <p><strong><?= _('Rang') ?> :</strong> <?= htmlspecialchars($bulletin['bulletin_record']['rang'] ?? _('Non défini')) ?></p>

            <p><strong><?= _('Statut du bulletin') ?> :</strong>
                <span class="badge <?= $statusBadgeClass ?>"><?= _(ucfirst(htmlspecialchars($bulletinStatut))) ?></span>
            </p>
        </div>
        <div class="col-md-6">
            <div class="border p-3 rounded">
                <h5><?= _('Appréciation du Conseil de Classe') ?></h5>
                <p class="fst-italic border-bottom pb-2"><?= htmlspecialchars($bulletin['bulletin_record']['appreciation_conseil_classe'] ?? _('Aucune appréciation du conseil de classe.')) ?></p>

                <h5 class="mt-3"><?= _('Appréciation Générale') ?></h5>
                <p class="fw-bold text-primary fs-5 mb-3"><?= htmlspecialchars($institutionalAppreciation) ?></p>

                <p class="mt-3"><strong><?= _("Le Chef d'établissement") ?></strong></p>
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
</div>
