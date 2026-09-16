<?php
/**
 * Vue d'impression officielle A4 du Procès-Verbal de Conseil de Discipline (Phase 6.4)
 *
 * Exige les variables :
 * - $council: Array des informations du conseil de discipline
 * - $membres: Array des membres du conseil avec snapshots et présence
 * - $eleves: Array des élèves convoqués, votes, décisions, motivations et sanctions liées
 * - $paramLycee: Array des paramètres de l'établissement
 */

$lyceeId = $council['lycee_id'] ?? 1;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Procès-Verbal Officiel - ' . $council['code']) ?></title>
    <link rel="stylesheet" href="/assets/fonts/tabler-icons.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        @page {
            size: A4 portrait;
            margin: 15mm 15mm 20mm 15mm;
        }

        body {
            font-family: 'Public Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: #fff;
            color: #1d2630;
            font-size: 11pt;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }

        .pv-container {
            width: 100%;
            max-width: 210mm;
            margin: 0 auto;
            padding: 20px;
            box-sizing: border-box;
        }

        .pv-header-title {
            text-align: center;
            border-bottom: 2px solid #2ca87f;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }

        .pv-header-title h1 {
            font-size: 16pt;
            font-weight: 700;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 5px 0;
        }

        .pv-header-title h2 {
            font-size: 13pt;
            font-weight: 600;
            color: #2ca87f;
            margin: 0;
        }

        .pv-section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }

        .pv-section-title {
            font-size: 12pt;
            font-weight: 700;
            color: #1e293b;
            background-color: #f8fafc;
            padding: 8px 12px;
            border-left: 4px solid #2ca87f;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .pv-grid-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px 15px;
        }

        .pv-meta-item {
            font-size: 10.5pt;
        }

        .pv-meta-item strong {
            color: #475569;
        }

        .table-pv {
            width: 100%;
            border-collapse: collapse;
            font-size: 10pt;
            margin-bottom: 15px;
        }

        .table-pv th, .table-pv td {
            border: 1px solid #cbd5e1;
            padding: 8px 10px;
            text-align: left;
            vertical-align: top;
        }

        .table-pv th {
            background-color: #f1f5f9;
            color: #334155;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 9pt;
        }

        .table-pv tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .badge-pv {
            display: inline-block;
            padding: 3px 8px;
            font-size: 8.5pt;
            font-weight: 600;
            border-radius: 4px;
            text-transform: uppercase;
        }

        .badge-sanctionne { background-color: #fee2e2; color: #991b1b; }
        .badge-relaxe { background-color: #dcfce7; color: #166534; }
        .badge-averti { background-color: #fef3c7; color: #92400e; }
        .badge-reoriente { background-color: #e0e7ff; color: #3730a3; }
        .badge-en_attente { background-color: #f1f5f9; color: #475569; }

        .pv-signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 40px;
            page-break-inside: avoid;
        }

        .pv-signature-box {
            border: 1px dashed #94a3b8;
            border-radius: 6px;
            padding: 15px;
            min-height: 110px;
            text-align: center;
        }

        .pv-signature-title {
            font-weight: 700;
            font-size: 10.5pt;
            color: #1e293b;
            margin-bottom: 5px;
        }

        .pv-signature-name {
            font-size: 10pt;
            color: #64748b;
            font-style: italic;
        }

        .no-print-bar {
            background: #0f172a;
            color: #fff;
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 9999;
        }

        @media print {
            .no-print-bar, .pc-sidebar, .pc-header, header, footer, button {
                display: none !important;
            }
            body {
                background: #fff;
            }
            .pv-container {
                max-width: 100%;
                padding: 0;
            }
        }
    </style>
</head>
<body>

<div class="no-print-bar">
    <div>
        <strong><?= htmlspecialchars($council['titre']) ?></strong> &mdash; Procès-Verbal Officiel
    </div>
    <div>
        <button onclick="window.print()" class="btn btn-primary btn-sm me-2">
            <i class="ph-duotone ph-printer me-1"></i> Imprimer le PV (A4)
        </button>
        <button onclick="window.close()" class="btn btn-light btn-sm">
            Fermer
        </button>
    </div>
</div>

<div class="pv-container">

    <!-- 1. En-tête administratif -->
    <div class="mb-3">
        <?php if (file_exists(__DIR__ . '/../../layouts/_header_administrative.php')): ?>
            <?php include __DIR__ . '/../../layouts/_header_administrative.php'; ?>
        <?php else: ?>
            <div class="text-center mb-3">
                <h3 class="mb-0 fw-bold"><?= htmlspecialchars($paramLycee['nom_lycee'] ?? 'ÉTABLISSEMENT SCOLAIRE') ?></h3>
                <p class="text-muted small"><?= htmlspecialchars($paramLycee['ville'] ?? '') ?></p>
            </div>
        <?php endif; ?>
    </div>

    <div class="pv-header-title">
        <h1>Procès-Verbal de Conseil de Discipline</h1>
        <h2>Code séance : <?= htmlspecialchars($council['code']) ?></h2>
    </div>

    <!-- MÉTADONNÉES DE LA SÉANCE -->
    <div class="pv-section">
        <div class="pv-section-title">1. Informations sur la séance</div>
        <div class="pv-grid-meta">
            <div class="pv-meta-item"><strong>Titre de la séance :</strong> <?= htmlspecialchars($council['titre']) ?></div>
            <div class="pv-meta-item"><strong>Date du Conseil :</strong> <?= date('d/m/Y', strtotime($council['date_conseil'])) ?></div>
            <div class="pv-meta-item"><strong>Horaire :</strong> <?= htmlspecialchars($council['heure_debut'] ?? 'N/A') ?> &ndash; <?= htmlspecialchars($council['heure_fin'] ?? 'N/A') ?></div>
            <div class="pv-meta-item"><strong>Lieu :</strong> <?= htmlspecialchars($council['lieu'] ?? 'Salle des conseils') ?></div>
            <div class="pv-meta-item"><strong>Président :</strong> M./Mme <?= htmlspecialchars($council['president_nom'] ?? 'N/A') ?></div>
            <div class="pv-meta-item"><strong>Secrétaire :</strong> M./Mme <?= htmlspecialchars($council['secretaire_nom'] ?? 'Non désigné') ?></div>
            <div class="pv-meta-item"><strong>Statut séance :</strong> <?= strtoupper(htmlspecialchars($council['statut'])) ?></div>
            <div class="pv-meta-item"><strong>Année Académique :</strong> <?= htmlspecialchars($council['annee_libelle'] ?? 'Active') ?></div>
        </div>
    </div>

    <!-- 2. COMPOSITION DU CONSEIL ET PRÉSENCES -->
    <div class="pv-section">
        <div class="pv-section-title">2. Composition du Conseil et Feuille de présence</div>
        <table class="table-pv">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th>Nom & Prénom (Snapshot)</th>
                    <th>Qualité / Fonction</th>
                    <th style="width: 15%; text-align: center;">Droit de vote</th>
                    <th style="width: 15%; text-align: center;">Présence</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($membres)): ?>
                    <?php foreach ($membres as $idx => $m): ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td><strong><?= htmlspecialchars($m['nom_snapshot']) ?></strong></td>
                            <td><?= htmlspecialchars($m['fonction_snapshot'] ?? ucfirst($m['qualite_membre'])) ?></td>
                            <td style="text-align: center;">
                                <?= $m['a_droit_vote'] ? '<span class="badge bg-light-success text-success">Oui</span>' : '<span class="badge bg-light-secondary text-secondary">Non</span>' ?>
                            </td>
                            <td style="text-align: center;">
                                <?= $m['est_present'] ? '<strong style="color: #166534;">Présent</strong>' : '<span style="color: #991b1b;">Absent</span>' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center text-muted">Aucun membre consigné.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- 3. ÉLÈVES CONVOQUÉS & CONVOCATION -->
    <div class="pv-section">
        <div class="pv-section-title">3. Dossiers des Élèves Convoqués</div>
        <table class="table-pv">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th>Élève (Matricule)</th>
                    <th>Classe</th>
                    <th>Motif de convocation</th>
                    <th style="width: 15%; text-align: center;">Présence élève</th>
                    <th style="width: 20%;">Représentant légal</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($eleves)): ?>
                    <?php foreach ($eleves as $idx => $el): ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td>
                                <strong><?= htmlspecialchars($el['eleve_nom_complet']) ?></strong><br>
                                <small class="text-muted">Mat: <?= htmlspecialchars($el['eleve_matricule'] ?? 'N/A') ?></small>
                            </td>
                            <td><?= htmlspecialchars($el['nom_classe_snapshot'] ?? 'N/A') ?></td>
                            <td><?= nl2br(htmlspecialchars($el['motif_convocation'])) ?></td>
                            <td style="text-align: center;">
                                <?= $el['presence_eleve'] ? '<span style="color: #166534; font-weight:600;">Présent</span>' : '<span style="color: #991b1b;">Absent</span>' ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($el['nom_representant_legal'] ?? 'Non renseigné') ?><br>
                                <small class="text-muted">Présence: <?= $el['presence_representant_legal'] ? 'Oui' : 'Non' ?></small>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center text-muted">Aucun élève convoqué à cette séance.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- 4. DÉLIBÉRATIONS, VOTES & DÉCISION DU CONSEIL -->
    <div class="pv-section">
        <div class="pv-section-title">4. Délibérations, Décompte des votes et Décisions</div>
        <?php if (!empty($eleves)): ?>
            <?php foreach ($eleves as $idx => $el): ?>
                <div style="border: 1px solid #cbd5e1; border-radius: 6px; padding: 12px; margin-bottom: 15px; background: #fff;">
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px; margin-bottom: 10px;">
                        <div>
                            <strong style="font-size: 11pt; color: #0f172a;">
                                Dossier #<?= $idx + 1 ?> : <?= htmlspecialchars($el['eleve_nom_complet']) ?>
                            </strong>
                            <span class="text-muted ms-2">(Classe: <?= htmlspecialchars($el['nom_classe_snapshot'] ?? 'N/A') ?>)</span>
                        </div>
                        <div>
                            <?php
                            $st = $el['decision_statut'];
                            $classBadge = 'badge-' . $st;
                            $labelMap = [
                                'en_attente' => 'En attente',
                                'relaxe' => 'Relaxé',
                                'averti' => 'Averti',
                                'reoriente' => 'Réorienté',
                                'sanctionne' => 'Sanctionné'
                            ];
                            ?>
                            <span class="badge-pv <?= $classBadge ?>">
                                Décision : <?= $labelMap[$st] ?? ucfirst($st) ?>
                            </span>
                        </div>
                    </div>

                    <!-- VOTES & MAJORITÉ -->
                    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 15px; margin-bottom: 10px;">
                        <div style="background: #f8fafc; padding: 10px; border-radius: 4px; border: 1px solid #e2e8f0; font-size: 9.5pt;">
                            <div style="font-weight: 700; color: #334155; margin-bottom: 5px;">Décompte des votes :</div>
                            <div>Votes Pour : <strong><?= (int)$el['votes_pour'] ?></strong></div>
                            <div>Votes Contre : <strong><?= (int)$el['votes_contre'] ?></strong></div>
                            <div>Abstentions : <strong><?= (int)$el['abstentions'] ?></strong></div>
                            <?php $totalV = (int)$el['votes_pour'] + (int)$el['votes_contre'] + (int)$el['abstentions']; ?>
                            <div style="border-top: 1px solid #cbd5e1; margin-top: 5px; padding-top: 3px;">
                                Total votants : <strong><?= $totalV ?></strong>
                                <?php if ($totalV > 0): ?>
                                    <br><small class="text-muted">Résultat : <?= ($el['votes_pour'] > $el['votes_contre']) ? 'Majorité Pour' : 'Majorité Contre / Égalité' ?></small>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- MOTIVATION DE LA DÉCISION -->
                        <div style="font-size: 9.5pt;">
                            <strong style="color: #334155;">Motivation & Considérants du Conseil :</strong>
                            <p style="margin: 5px 0 0 0; color: #1e293b; font-style: italic; white-space: pre-line;">
                                <?= !empty($el['motivation_decision']) ? htmlspecialchars($el['motivation_decision']) : 'Aucune motivation saisie.' ?>
                            </p>
                        </div>
                    </div>

                    <!-- 5. DÉTAILS DE LA SANCTION RATTACHÉE (SI SANCTIONNÉ) -->
                    <?php if ($el['decision_statut'] === 'sanctionne' && !empty($el['sanction_details'])): ?>
                        <?php $sanc = $el['sanction_details']; ?>
                        <div style="background: #fff5f5; border: 1px solid #fecaca; border-radius: 4px; padding: 10px; margin-top: 10px; font-size: 9.5pt;">
                            <div style="font-weight: 700; color: #991b1b; margin-bottom: 4px;">
                                <i class="ph-duotone ph-warning-circle me-1"></i> Sanction prononcée #<?= $sanc['id'] ?> : <?= htmlspecialchars($sanc['type_libelle']) ?> (Code: <?= htmlspecialchars($sanc['type_code']) ?>)
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; color: #7f1d1d;">
                                <div>Date décision : <strong><?= date('d/m/Y', strtotime($sanc['date_decision'])) ?></strong></div>
                                <div>Durée jours : <strong><?= $sanc['duree_jours'] ?: 'N/A' ?></strong></div>
                                <div>Durée heures : <strong><?= $sanc['duree_heures'] ?: 'N/A' ?></strong></div>
                            </div>
                            <?php if (!empty($sanc['date_debut_execution'])): ?>
                                <div style="margin-top: 4px; color: #7f1d1d;">
                                    Période d'exécution : <strong><?= date('d/m/Y', strtotime($sanc['date_debut_execution'])) ?></strong> au <strong><?= !empty($sanc['date_fin_execution']) ? date('d/m/Y', strtotime($sanc['date_fin_execution'])) : 'N/A' ?></strong>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php elseif ($el['decision_statut'] === 'sanctionne'): ?>
                        <div style="background: #fff5f5; border: 1px dashed #fecaca; border-radius: 4px; padding: 8px; margin-top: 10px; font-size: 9pt; color: #991b1b;">
                            Sanction enregistrée ID #<?= (int)($el['sanction_id'] ?? 0) ?>.
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-muted">Aucune délibération enregistrée.</p>
        <?php endif; ?>
    </div>

    <!-- OBSERVATIONS GÉNÉRALES DU CONSEIL -->
    <?php if (!empty($council['observations_generales'])): ?>
        <div class="pv-section">
            <div class="pv-section-title">5. Observations générales & Synthèse</div>
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px; font-size: 10pt;">
                <?= nl2br(htmlspecialchars($council['observations_generales'])) ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- 6. BLOCS DE SIGNATURES OFFICIELLES -->
    <div class="pv-signatures">
        <div class="pv-signature-box">
            <div class="pv-signature-title">Le Secrétaire de séance</div>
            <div class="pv-signature-name">
                <?= htmlspecialchars($council['secretaire_nom'] ?? 'Nom et Signature') ?>
            </div>
        </div>

        <div class="pv-signature-box">
            <div class="pv-signature-title">Le Président du Conseil de Discipline</div>
            <div class="pv-signature-name">
                M./Mme <?= htmlspecialchars($council['president_nom'] ?? 'Nom et Signature') ?>
            </div>
        </div>
    </div>

</div>

</body>
</html>
