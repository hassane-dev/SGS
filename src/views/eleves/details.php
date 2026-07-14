<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<!-- [ Main Content ] start -->
<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= _('Détails de l\'Élève') ?></h2>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <a href="/carte/generer?eleve_id=<?= $eleve['id_eleve'] ?>" class="btn btn-outline-primary" target="_blank">
                            <i class="ph-duotone ph-cardholder me-1"></i>
                            <?= _('Imprimer la carte') ?>
                        </a>
                        <a href="/mensualites/show-form?eleve_id=<?= $eleve['id_eleve'] ?>" class="btn btn-success">
                            <?= _('Payer Mensualité') ?>
                        </a>
                        <?php if (Auth::can('manage', 'boutique')): ?>
                            <a href="/boutique/achats?eleve_id=<?= $eleve['id_eleve'] ?>" class="btn btn-primary">
                                <i class="ph-duotone ph-shopping-cart me-1"></i>
                                <?= _('Boutique') ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- [ Main Content ] start -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="card mb-0">
                    <div class="card-body p-2">
                        <ul class="nav nav-pills" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" href="/eleves/details?id=<?= $eleve['id_eleve'] ?>"><i class="ph-duotone ph-user me-2"></i>Dossier & Informations</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="/eleves/parametres-financiers?id=<?= $eleve['id_eleve'] ?>"><i class="ph-duotone ph-currency-dollar me-2"></i>Paramètres Financiers</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Informations Personnelles -->
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><?= _('Informations Personnelles') ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($eleve['identifiant_public'])): ?>
                            <p><strong><?= _('Identifiant Public / Matricule') ?>:</strong> <span class="badge bg-light-primary text-primary"><?= htmlspecialchars($eleve['identifiant_public'] ?? '') ?></span></p>
                        <?php endif; ?>
                        <p><strong><?= _('Nom & Prénom') ?>:</strong> <?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></p>
                        <p><strong><?= _('Date de Naissance') ?>:</strong> <?= htmlspecialchars($eleve['date_naissance']) ?></p>
                        <p><strong><?= _('Lieu de Naissance') ?>:</strong> <?= htmlspecialchars($eleve['lieu_naissance']) ?></p>
                        <p><strong><?= _('Sexe') ?>:</strong> <?= htmlspecialchars($eleve['sexe']) ?></p>
                        <p><strong><?= _('Nationalité') ?>:</strong> <?= htmlspecialchars($eleve['nationalite']) ?></p>
                        <p><strong><?= _('Adresse') ?>:</strong> <?= htmlspecialchars($eleve['quartier']) ?></p>
                    </div>
                </div>
            </div>

            <!-- Historique des Inscriptions -->
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><?= _('Historique des Inscriptions') ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th><?= _('Année Académique') ?></th>
                                        <th><?= _('Classe') ?></th>
                                        <th><?= _('Montant Total') ?></th>
                                        <th><?= _('Montant Versé') ?></th>
                                        <th><?= _('Reste à Payer') ?></th>
                                        <th><?= _('Date') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($inscriptions as $inscription): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($inscription['annee_academique']) ?></td>
                                            <td><?= htmlspecialchars($inscription['nom_classe']) ?></td>
                                            <td><?= htmlspecialchars(number_format($inscription['montant_total'], 2)) ?></td>
                                            <td><?= htmlspecialchars(number_format($inscription['montant_verse'], 2)) ?></td>
                                            <td class="<?= $inscription['reste_a_payer'] > 0 ? 'text-danger' : 'text-success' ?>">
                                                <?= htmlspecialchars(number_format($inscription['reste_a_payer'], 2)) ?>
                                            </td>
                                            <td><?= htmlspecialchars(date('d/m/Y', strtotime($inscription['date_inscription']))) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Historique des Paiements Mensuels -->
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><?= _('Historique des Paiements Mensuels') ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th><?= _('Année Académique') ?></th>
                                        <th><?= _('Mois/Séquence') ?></th>
                                        <th><?= _('Montant Versé') ?></th>
                                        <th><?= _('Date') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($mensualites as $mensualite): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($mensualite['annee_academique']) ?></td>
                                            <td><?= htmlspecialchars($mensualite['mois_ou_sequence']) ?></td>
                                            <td><?= htmlspecialchars(number_format($mensualite['montant_verse'], 2)) ?></td>
                                            <td><?= htmlspecialchars(date('d/m/Y', strtotime($mensualite['date_paiement']))) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 text-end">
                <a href="/eleves" class="btn btn-secondary mt-2"><?= _('Retour à la liste') ?></a>
            </div>
        </div>
        <!-- [ Main Content ] end -->
    </div>
</div>
<!-- [ Main Content ] end -->

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
