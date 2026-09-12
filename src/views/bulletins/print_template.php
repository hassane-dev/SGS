<?php
/**
 * Unified Official Report Card Print Template (Modèle Officiel Bilingue / Monolingue)
 *
 * Variables required in scope:
 * - $bulletinsData: array of bulletin items generated via Bulletin::generateForStudent()
 * - $lycee: array of ParamLycee / lycee details (nom_lycee, logo, adresse, telephone, etc.)
 * - $paramGeneral: array of ParamGeneral settings (nb_langue, langue_1, langue_2)
 */

require_once __DIR__ . '/../../helpers/BulletinI18nHelper.php';
require_once __DIR__ . '/../../services/EvaluationCalculationService.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/ParametreUtilisateur.php';

if (empty($bulletinsData)) {
    $bulletinsData = [];
}

$paramGeneral = $paramGeneral ?? ['nb_langue' => 1, 'langue_1' => 'fr_FR'];
$lycee = $lycee ?? [];
$isBilingual = ((int)($paramGeneral['nb_langue'] ?? 1) === 2 && !empty($paramGeneral['langue_2']));
$isFullPage = $isFullPage ?? true;
?>
<?php if ($isFullPage): ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= _('Bulletin Scolaire Officiel') ?></title>
<?php endif; ?>
    <style>
        /* Base Screen & Page Setup */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11pt;
            color: #111;
            background-color: #f4f6f8;
            padding: 20px;
        }

        .no-print-toolbar {
            position: sticky;
            top: 0;
            z-index: 9999;
            background: #ffffff;
            padding: 15px 25px;
            margin-bottom: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .no-print-toolbar h4 {
            margin: 0;
            font-size: 1.2rem;
            color: #2b343b;
        }

        .btn-print {
            background-color: #0d6efd;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: background 0.2s ease;
        }

        .btn-print:hover {
            background-color: #0b5ed7;
        }

        .btn-close-view {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 10px 18px;
            font-size: 0.95rem;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
        }

        /* Printable Paper Wrapper */
        .print-container {
            max-width: 210mm;
            margin: 0 auto;
        }

        .bulletin-sheet {
            background: #ffffff;
            width: 210mm;
            min-height: 297mm;
            padding: 15mm;
            margin: 0 auto 20px auto;
            border-radius: 4px;
            box-shadow: 0 0 10px rgba(0,0,0,0.08);
            position: relative;
            page-break-after: always;
            break-after: page;
        }

        .bulletin-sheet:last-child {
            page-break-after: avoid;
            break-after: avoid;
        }

        /* Watermark for Provisional Bulletins */
        .watermark-provisoire {
            position: absolute;
            top: 40%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 4.5rem;
            font-weight: 900;
            color: rgba(220, 53, 69, 0.12);
            text-transform: uppercase;
            letter-spacing: 12px;
            pointer-events: none;
            white-space: nowrap;
            z-index: 1;
        }

        /* SHARED ADMINISTRATIVE HEADER STYLES (MATCHING ID CARD IDENTITY) */
        .admin-header-wrapper {
            margin-bottom: 12px;
            border-bottom: 2px solid #222;
            padding-bottom: 10px;
        }

        .admin-header-grid {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            gap: 15px;
            text-align: center;
        }

        .admin-header-col {
            font-size: 8.5pt;
            line-height: 1.3;
            color: #222;
        }

        .admin-header-left {
            text-align: left;
            font-weight: bold;
            text-transform: uppercase;
        }

        .admin-header-right {
            text-align: right;
            font-weight: bold;
            text-transform: uppercase;
        }

        .admin-header-center {
            text-align: center;
        }

        .admin-school-logo {
            max-height: 75px;
            max-width: 150px;
            object-fit: contain;
            display: block;
            margin: 0 auto 4px auto;
        }

        .admin-school-name {
            font-size: 14pt;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #000;
            line-height: 1.2;
        }

        .admin-school-devise {
            font-size: 9pt;
            font-style: italic;
            font-weight: 600;
            color: #333;
            margin-top: 2px;
        }

        .admin-header-details {
            margin-top: 6px;
            text-align: center;
            font-size: 8.5pt;
            color: #444;
            border-top: 1px dashed #ccc;
            padding-top: 4px;
        }

        .admin-header-arrete {
            font-style: italic;
            font-size: 8pt;
            color: #555;
            margin-top: 2px;
        }

        .document-title {
            font-size: 14pt;
            font-weight: 800;
            text-transform: uppercase;
            margin-top: 8px;
            text-align: center;
            color: #1a252f;
            letter-spacing: 1.5px;
        }

        .meta-pills {
            display: flex;
            justify-content: center;
            gap: 15px;
            font-size: 9.5pt;
            margin-top: 4px;
            font-weight: 600;
        }

        /* Student Metadata Grid */
        .student-info-box {
            width: 100%;
            border: 1px solid #333;
            border-radius: 4px;
            padding: 8px 12px;
            margin-bottom: 12px;
            background-color: #fafafa;
        }

        .student-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            row-gap: 5px;
            column-gap: 15px;
            font-size: 9.5pt;
        }

        .student-info-grid div {
            line-height: 1.3;
        }

        .lbl {
            font-weight: bold;
            color: #222;
        }

        /* Grades Table */
        .grades-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
            margin-bottom: 12px;
        }

        .grades-table th, .grades-table td {
            border: 1px solid #222;
            padding: 5px 6px;
            text-align: center;
            vertical-align: middle;
        }

        .grades-table th {
            background-color: #e9ecef;
            font-weight: bold;
            color: #000;
            text-transform: uppercase;
            font-size: 8.5pt;
        }

        .grades-table .subject-col {
            text-align: left;
            font-weight: bold;
            width: 25%;
        }

        .grades-table .apprec-col {
            text-align: left;
            width: 28%;
            font-style: italic;
            font-size: 8.5pt;
        }

        .grades-table tfoot td {
            font-weight: bold;
            background-color: #f1f3f5;
        }

        /* Summary Box */
        .summary-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 10px;
        }

        .summary-card {
            border: 1px solid #333;
            border-radius: 4px;
            padding: 8px 12px;
            font-size: 9.5pt;
            background: #fafafa;
        }

        .summary-card h5 {
            font-size: 10pt;
            text-transform: uppercase;
            border-bottom: 1px solid #ccc;
            padding-bottom: 4px;
            margin-bottom: 6px;
            color: #111;
        }

        .stat-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
        }

        .highlight-avg {
            font-size: 12pt;
            font-weight: bold;
            color: #0d6efd;
        }

        /* Signatures Section */
        .signatures-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 15px;
            font-size: 9.5pt;
            text-align: center;
        }

        .signature-box {
            border: 1px dashed #666;
            border-radius: 4px;
            height: 80px;
            padding: 6px;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .signature-title {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9pt;
        }

        .stamp-img {
            max-height: 50px;
            max-width: 120px;
            object-fit: contain;
            margin: 0 auto;
        }

        .rtl-text {
            direction: rtl;
            unicode-bidi: embed;
            font-family: 'Amiri', 'Traditional Arabic', serif;
        }

        .bulletin-sheet.rtl-doc {
            direction: rtl;
            text-align: right;
        }

        .bulletin-sheet.rtl-doc .subject-col,
        .bulletin-sheet.rtl-doc .apprec-col {
            text-align: right;
        }

        .bulletin-sheet.rtl-doc .admin-header-wrapper {
            text-align: center;
        }

        /* STRICT PRINT STYLES - STOPS ALL BROWSER TECHNICAL HEADERS/FOOTERS/URLS/COUNTERS */
        @page {
            size: A4 portrait;
            margin: 0;
        }

        @media print {
            html, body {
                background-color: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .no-print-toolbar, .pc-sidebar, .pc-header, .pc-container, .no-print, header, footer, nav {
                display: none !important;
            }

            .print-container {
                max-width: 100% !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            .bulletin-sheet {
                width: 100% !important;
                margin: 0 !important;
                padding: 10mm 15mm !important;
                box-shadow: none !important;
                border-radius: 0 !important;
                min-height: 100vh !important;
                page-break-before: always;
            }

            .bulletin-sheet:first-child {
                page-break-before: avoid;
            }
        }
    </style>
<?php if ($isFullPage): ?>
</head>
<body>

    <div class="no-print-toolbar">
        <div>
            <h4><?= _("Impression des Bulletins Officiels") ?></h4>
            <span class="text-muted small"><?= sprintf(_("%d bulletin(s) prêt(s) pour l'impression"), count($bulletinsData)) ?></span>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="javascript:history.back()" class="btn-close-view"><?= _("Retour") ?></a>
            <button class="btn-print" onclick="window.print();">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H7a2 2 0 00-2 2v4h10z"></path></svg>
                <?= _("Lancer l'impression / PDF") ?>
            </button>
        </div>
    </div>
<?php endif; ?>

    <div class="print-container">
        <?php foreach ($bulletinsData as $bIndex => $bData): ?>
            <?php
            $eleve = $bData['eleve'] ?? [];
            $seq = $bData['sequence'] ?? [];
            $matieres = $bData['matieres'] ?? [];
            $evalCols = $bData['evaluation_columns'] ?? [];
            $bRecord = $bData['bulletin_record'] ?? [];
            $isProvisoire = !empty($bData['is_provisoire']) || ($bRecord['statut'] ?? '') === 'provisoire';
            $moyGen = $bData['moyenne_generale'] ?? null;
            $institutionalApprec = EvaluationCalculationService::getInstitutionalAppreciation($moyGen);
            $lyceeId = $eleve['lycee_id'] ?? Auth::getLyceeId();
            $currentLycee = ($lyceeId && (!isset($lycee['id']) || (int)$lycee['id'] === (int)$lyceeId)) ? $lycee : (ParamLycee::findByLyceeId($lyceeId) ?: $lycee);
            $currentParamGeneral = ($lyceeId && (!isset($paramGeneral['lycee_id']) || (int)$paramGeneral['lycee_id'] === (int)$lyceeId)) ? $paramGeneral : (ParamGeneral::findByLyceeId($lyceeId) ?: $paramGeneral);

            // Fetch headmaster signature/cachet for target school
            $dirUser = User::findOneByRoleNameAndLycee('proviseur', $lyceeId) ?: User::findOneByRoleNameAndLycee('directeur', $lyceeId);
            $dirSettings = $dirUser ? ParametreUtilisateur::findByUserId($dirUser['id_user']) : null;

            $isRtlDoc = BulletinI18nHelper::isRtl($currentParamGeneral['langue_1'] ?? 'fr_FR') && ((int)($currentParamGeneral['nb_langue'] ?? 1) === 1);
            ?>
            <div class="bulletin-sheet <?= $isRtlDoc ? 'rtl-doc' : '' ?>" <?= $isRtlDoc ? 'dir="rtl"' : '' ?>>

                <?php if ($isProvisoire): ?>
                    <div class="watermark-provisoire">
                        <?= BulletinI18nHelper::label('BULLETIN PROVISOIRE', $currentParamGeneral) ?>
                    </div>
                <?php endif; ?>

                <!-- Shared Administrative Header (Reused from ID Card Identity) -->
                <?php include __DIR__ . '/../layouts/_header_administrative.php'; ?>

                <!-- Document Title & Meta -->
                <div class="document-title">
                    <?= BulletinI18nHelper::label('BULLETIN SCOLAIRE', $currentParamGeneral) ?>
                </div>
                <div class="meta-pills mb-3">
                    <span><?= BulletinI18nHelper::label('Année Académique', $currentParamGeneral) ?>: <?= htmlspecialchars($eleve['annee_academique'] ?? '') ?></span>
                    <span>•</span>
                    <span><?= BulletinI18nHelper::label('Séquence', $currentParamGeneral) ?>: <?= htmlspecialchars($seq['nom'] ?? '') ?></span>
                </div>

                <!-- Student Information Grid -->
                <div class="student-info-box">
                    <div class="student-info-grid">
                        <div>
                            <span class="lbl"><?= BulletinI18nHelper::label('Nom & Prénom', $currentParamGeneral) ?> :</span>
                            <strong><?= htmlspecialchars(($eleve['nom'] ?? '') . ' ' . ($eleve['prenom'] ?? '')) ?></strong>
                        </div>
                        <div>
                            <span class="lbl"><?= BulletinI18nHelper::label('Classe', $currentParamGeneral) ?> :</span>
                            <strong><?= htmlspecialchars($eleve['nom_classe'] ?? '') ?></strong>
                        </div>
                        <?php if (!empty($eleve['identifiant_public'])): ?>
                            <div>
                                <span class="lbl"><?= BulletinI18nHelper::label('Matricule', $currentParamGeneral) ?> :</span>
                                <strong><?= htmlspecialchars($eleve['identifiant_public']) ?></strong>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($eleve['date_naissance'])): ?>
                            <div>
                                <span class="lbl"><?= BulletinI18nHelper::label('Date de Naissance', $currentParamGeneral) ?> :</span>
                                <?= htmlspecialchars($eleve['date_naissance']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Grades Table -->
                <table class="grades-table">
                    <thead>
                        <tr>
                            <th class="subject-col"><?= BulletinI18nHelper::label('Matières', $currentParamGeneral) ?></th>
                            <?php foreach ($evalCols as $col): ?>
                                <th><?= htmlspecialchars($col['label']) ?></th>
                            <?php endforeach; ?>
                            <th><?= BulletinI18nHelper::label('Moyenne / 20', $currentParamGeneral) ?></th>
                            <th><?= BulletinI18nHelper::label('Coef', $currentParamGeneral) ?></th>
                            <th><?= BulletinI18nHelper::label('Total Points', $currentParamGeneral) ?></th>
                            <th class="apprec-col"><?= BulletinI18nHelper::label("Appreciations de l'enseignant", $currentParamGeneral) ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($matieres as $m): ?>
                            <tr>
                                <td class="subject-col"><?= htmlspecialchars($m['nom']) ?></td>
                                <?php foreach ($evalCols as $col): ?>
                                    <?php $v = $m['evaluation_values'][$col['key']] ?? null; ?>
                                    <td><?= ($v !== null) ? number_format((float)$v, 2) : '-' ?></td>
                                <?php endforeach; ?>
                                <td><strong><?= number_format((float)$m['note'], 2) ?></strong></td>
                                <td><?= htmlspecialchars($m['coefficient']) ?></td>
                                <td><strong><?= number_format((float)$m['total_points'], 2) ?></strong></td>
                                <td class="apprec-col"><?= htmlspecialchars($m['appreciation'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td class="subject-col"><?= BulletinI18nHelper::label('Totaux', $currentParamGeneral) ?></td>
                            <?php if (!empty($evalCols)): ?>
                                <td colspan="<?= count($evalCols) ?>"></td>
                            <?php endif; ?>
                            <td></td>
                            <td><?= htmlspecialchars($bData['total_coefficients'] ?? '0') ?></td>
                            <td><?= number_format((float)($bData['total_points'] ?? 0), 2) ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>

                <?php
                // Automatic institutional distinction calculation (SSoT)
                $institutionalDistinction = EvaluationCalculationService::getInstitutionalDistinction($moyGen);
                ?>
                <!-- Summary Box -->
                <div class="summary-container">
                    <div class="summary-card">
                        <h5><?= BulletinI18nHelper::label('Moyenne', $currentParamGeneral) ?> & <?= BulletinI18nHelper::label('Rang', $currentParamGeneral) ?></h5>
                        <div class="stat-row">
                            <span><?= BulletinI18nHelper::label('Moyenne Générale', $currentParamGeneral) ?> :</span>
                            <span class="highlight-avg"><?= ($moyGen !== null) ? number_format($moyGen, 2) . ' / 20' : 'N/A' ?></span>
                        </div>
                        <div class="stat-row">
                            <span><?= BulletinI18nHelper::label('Rang', $currentParamGeneral) ?> :</span>
                            <strong><?= htmlspecialchars($bRecord['rang'] ?? _('Non défini')) ?></strong>
                        </div>
                    </div>

                    <div class="summary-card">
                        <h5><?= BulletinI18nHelper::label('DISTINCTION / PALMARÈS', $currentParamGeneral) ?></h5>
                        <div style="margin-top: 6px;">
                            <span class="lbl"><?= BulletinI18nHelper::label('Distinction', $currentParamGeneral) ?> :</span>
                            <strong style="color: #198754; display: block; font-size: 11pt; margin-top: 4px;">
                                <?= !empty($institutionalDistinction) ? BulletinI18nHelper::label($institutionalDistinction, $currentParamGeneral) : BulletinI18nHelper::label('Aucune distinction', $currentParamGeneral) ?>
                            </strong>
                        </div>
                    </div>
                </div>

                <div class="summary-card mt-3" style="width: 100%;">
                    <h5><?= BulletinI18nHelper::label('Appréciation Générale', $currentParamGeneral) ?></h5>
                    <div style="margin-bottom: 6px;">
                        <span class="lbl"><?= BulletinI18nHelper::label('Appréciation Générale', $currentParamGeneral) ?> :</span>
                        <strong style="color: #0d6efd; display: inline-block; font-size: 10.5pt; margin-left: 6px;">
                            <?= BulletinI18nHelper::label($institutionalApprec, $currentParamGeneral) ?>
                        </strong>
                    </div>
                    <div style="border-top: 1px dashed #ccc; padding-top: 6px; margin-top: 6px;">
                        <span class="lbl"><?= BulletinI18nHelper::label('Appréciation du Conseil de Classe', $currentParamGeneral) ?> :</span>
                        <p style="font-style: italic; font-size: 9pt; color: #333; margin-top: 4px; margin-bottom: 0;">
                            <?= htmlspecialchars($bRecord['appreciation_conseil_classe'] ?? _('Aucune appréciation du conseil de classe.')) ?>
                        </p>
                    </div>
                </div>

                <!-- Signatures -->
                <div class="signatures-grid">
                    <div class="signature-box">
                        <div class="signature-title"><?= BulletinI18nHelper::label('Appréciation du Conseil de Classe', $currentParamGeneral) ?></div>
                    </div>
                    <div class="signature-box">
                        <div class="signature-title"><?= BulletinI18nHelper::label("Le Chef d'établissement", $currentParamGeneral) ?></div>
                        <?php if ($dirSettings && !empty($dirSettings->signature)): ?>
                            <div>
                                <img src="<?= htmlspecialchars($dirSettings->signature) ?>" class="stamp-img" alt="Signature">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        <?php endforeach; ?>
    </div>

<?php if ($isFullPage): ?>
</body>
</html>
<?php endif; ?>
