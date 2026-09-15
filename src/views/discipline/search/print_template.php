<?php
/**
 * Template Officiel d'Impression PDF/A4 Paysage - Registre Disciplinaire SGS (Phase 5.3)
 */

$roleLabels = [
    'auteur_principal' => 'Auteur Principal',
    'co_auteur' => 'Co-auteur',
    'complice' => 'Complice',
    'victime' => 'Victime',
    'temoin' => 'Témoin',
];

$sanctStatutLabels = [
    'prononcee' => 'Prononcée',
    'en_cours' => 'En cours',
    'executee' => 'Exécutée',
    'levee' => 'Levée',
    'annulee' => 'Annulée',
];
?>
<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title) ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        @page {
            size: A4 landscape;
            margin: 12mm 15mm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #212529;
            background: #fff;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .header-logo {
            max-height: 65px;
            width: auto;
        }
        .register-title {
            text-align: center;
            text-transform: uppercase;
            font-size: 16px;
            font-weight: bold;
            color: #0d6efd;
            margin-bottom: 5px;
        }
        .filter-summary {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 10px;
            margin-bottom: 15px;
        }
        .print-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .print-table th {
            background-color: #f1f3f5;
            color: #495057;
            font-weight: bold;
            text-align: left;
            padding: 6px 8px;
            border: 1px solid #dee2e6;
            font-size: 10px;
        }
        .print-table td {
            padding: 6px 8px;
            border: 1px solid #dee2e6;
            font-size: 10px;
        }
        .footer-table {
            width: 100%;
            margin-top: 20px;
            border-top: 1px solid #dee2e6;
            padding-top: 8px;
            font-size: 9px;
            color: #6c757d;
        }
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

    <!-- Barre d'action d'impression -->
    <div class="no-print p-3 bg-light border-bottom mb-4 d-flex justify-content-between align-items-center">
        <div>
            <strong>Impression Officielle du Registre Disciplinaire</strong>
            <span class="text-muted ms-2">(Orientation A4 Paysage)</span>
        </div>
        <div>
            <button onclick="window.print()" class="btn btn-primary btn-sm me-2">
                <i class="ph-duotone ph-printer me-1"></i>Imprimer / Sauvegarder en PDF
            </button>
            <button onclick="window.close()" class="btn btn-outline-secondary btn-sm">Fermer</button>
        </div>
    </div>

    <!-- En-tête Administratif Officiel -->
    <table class="header-table">
        <tr>
            <td style="width: 30%;">
                <strong><?= htmlspecialchars($param_lycee['nom_lycee'] ?? 'ÉTABLISSEMENT SCOLAIRE') ?></strong><br>
                <small><?= htmlspecialchars($param_lycee['sigle'] ?? '') ?></small><br>
                <small><?= htmlspecialchars($param_lycee['devise'] ?? '') ?></small>
            </td>
            <td style="width: 40%; text-align: center;">
                <?php if (!empty($param_lycee['logo'])): ?>
                    <img src="/uploads/logos/<?= htmlspecialchars($param_lycee['logo']) ?>" class="header-logo" alt="Logo">
                <?php endif; ?>
            </td>
            <td style="width: 30%; text-align: right;">
                <small>Téléphone : <?= htmlspecialchars($param_lycee['telephone_1'] ?? 'N/A') ?></small><br>
                <small>Email : <?= htmlspecialchars($param_lycee['email'] ?? 'N/A') ?></small><br>
                <small>Ville : <?= htmlspecialchars($param_lycee['ville'] ?? 'N/A') ?></small>
            </td>
        </tr>
    </table>

    <div class="register-title">
        REGISTRE OFFICIEL DES <?= $tab === 'incidents' ? 'INCIDENTS DISCIPLINAIRES' : 'SANCTIONS DISCIPLINAIRES' ?>
    </div>

    <div class="filter-summary">
        <strong>Périmètre & Filtres Appliqués :</strong>
        Établissement : <strong><?= htmlspecialchars($param_lycee['nom_lycee'] ?? 'N/A') ?></strong> |
        Date d'Impression : <strong><?= date('d/m/Y H:i') ?></strong> |
        Total Enregistrements : <strong><?= count($dataset['items']) ?></strong>
    </div>

    <!-- Tableau du Registre -->
    <?php if (empty($dataset['items'])): ?>
        <div style="text-align: center; padding: 30px; color: #6c757d;">
            Aucun enregistrement ne correspond aux critères filtrés.
        </div>
    <?php else: ?>
        <?php if ($tab === 'incidents'): ?>
            <table class="print-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Réf. Code</th>
                        <th>Date & Heure</th>
                        <th>Type Incident</th>
                        <th>Gravité</th>
                        <th>Élève (Matricule)</th>
                        <th>Classe</th>
                        <th>Rôle</th>
                        <th>Statut</th>
                        <th>Signalé par</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dataset['items'] as $idx => $item): ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td><strong><?= htmlspecialchars($item['incident_code']) ?></strong></td>
                            <td><?= date('d/m/Y', strtotime($item['date_incident'])) ?> <?= htmlspecialchars($item['heure_incident']) ?></td>
                            <td><?= htmlspecialchars($item['type_incident_libelle']) ?></td>
                            <td><?= htmlspecialchars($item['niveau_gravite']) ?></td>
                            <td><?= htmlspecialchars($item['eleve_nom_complet']) ?> (<?= htmlspecialchars($item['eleve_matricule']) ?>)</td>
                            <td><strong><?= htmlspecialchars($item['nom_classe_snapshot'] ?? 'Inconnue') ?></strong></td>
                            <td><?= htmlspecialchars($roleLabels[$item['role_implication']] ?? $item['role_implication']) ?></td>
                            <td><?= ucfirst(str_replace('_', ' ', $item['incident_statut'])) ?></td>
                            <td><?= htmlspecialchars($item['signale_par_nom'] ?? 'Système') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <table class="print-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Élève (Matricule)</th>
                        <th>Classe</th>
                        <th>Type Sanction</th>
                        <th>Réf. Incident</th>
                        <th>Date Décision</th>
                        <th>Période Exécution</th>
                        <th>Durée</th>
                        <th>Statut</th>
                        <th>Décidé par</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dataset['items'] as $idx => $item): ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td><strong><?= htmlspecialchars($item['eleve_nom_complet']) ?></strong> (<?= htmlspecialchars($item['eleve_matricule']) ?>)</td>
                            <td><strong><?= htmlspecialchars($item['nom_classe_snapshot'] ?? 'Inconnue') ?></strong></td>
                            <td><?= htmlspecialchars($item['type_sanction_libelle']) ?></td>
                            <td><?= htmlspecialchars($item['incident_code'] ?? 'Directe') ?></td>
                            <td><?= date('d/m/Y', strtotime($item['date_decision'])) ?></td>
                            <td>
                                <?php if (!empty($item['date_debut_execution'])): ?>
                                    <?= date('d/m/Y', strtotime($item['date_debut_execution'])) ?> au <?= date('d/m/Y', strtotime($item['date_fin_execution'])) ?>
                                <?php else: ?>
                                    Non planifiée
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= ($item['duree_jours'] > 0 ? $item['duree_jours'] . ' j ' : '') . ($item['duree_heures'] > 0 ? $item['duree_heures'] . ' h' : '-') ?>
                            </td>
                            <td><?= htmlspecialchars($sanctStatutLabels[$item['sanction_statut']] ?? $item['sanction_statut']) ?></td>
                            <td><?= htmlspecialchars($item['prononcee_par_nom'] ?? 'Système') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Pied de Page Officiel -->
    <table class="footer-table">
        <tr>
            <td style="width: 50%;">Document Officiel Édité le <?= date('d/m/Y à H:i') ?> • SGS Discipline & Vie Scolaire</td>
            <td style="width: 50%; text-align: right;">Page 1 sur 1</td>
        </tr>
    </table>

</body>
</html>
