<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Reçu de Scolarité - <?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></title>
    <style>
        body { font-family: 'Arial', sans-serif; color: #333; margin: 0; padding: 20px; font-size: 14px; line-height: 1.4; }
        .receipt-container { max-width: 800px; margin: auto; border: 2px solid #333; padding: 20px; position: relative; background: #fff; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        .header img { max-width: 100px; max-height: 100px; }
        .header-text { text-align: center; flex-grow: 1; }
        .header-text h1 { margin: 0; font-size: 20px; text-transform: uppercase; color: #000; }
        .header-text p { margin: 2px 0; font-size: 11px; }
        .receipt-title { text-align: center; margin-bottom: 20px; }
        .receipt-title h2 { margin: 0; border: 2px solid #000; display: inline-block; padding: 8px 30px; background: #f8f9fa; text-transform: uppercase; font-size: 18px; }
        .info-section { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px; }
        .info-box { border: 1px solid #ddd; padding: 10px; border-radius: 4px; }
        .info-label { font-weight: bold; color: #555; font-size: 11px; text-transform: uppercase; display: block; margin-bottom: 3px; }
        .info-value { font-size: 14px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table th, table td { border: 1px solid #333; padding: 12px; text-align: left; }
        table th { background: #f0f0f0; font-size: 12px; text-transform: uppercase; }
        .amount-words { font-style: italic; margin-bottom: 20px; padding: 10px; background: #fdfdfe; border-left: 4px solid #333; }
        .footer { display: flex; justify-content: space-between; margin-top: 40px; }
        .signature-box { text-align: center; width: 220px; }
        .signature-line { margin-top: 45px; border-top: 1px solid #333; padding-top: 5px; font-weight: bold; }
        .watermark { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-45deg); font-size: 80px; color: rgba(0,0,0,0.05); pointer-events: none; white-space: nowrap; font-weight: bold; text-transform: uppercase; }
        @media print {
            .no-print { display: none; }
            body { padding: 0; background: #fff; }
            .receipt-container { border: 2px solid #000; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px; text-align: center;">
        <button onclick="window.print();" style="padding: 12px 25px; cursor: pointer; background: #28a745; color: #fff; border: none; border-radius: 4px; font-weight: bold; font-size: 16px;">
            🖨️ IMPRIMER LE REÇU
        </button>
        <button onclick="window.close();" style="padding: 12px 25px; cursor: pointer; background: #6c757d; color: #fff; border: none; border-radius: 4px; font-weight: bold; font-size: 16px; margin-left: 10px;">
            Fermer
        </button>
    </div>

    <div class="receipt-container">
        <div class="watermark">SCOLARITÉ</div>

        <div class="header">
            <?php if (!empty($lycee['logo'])): ?>
                <img src="<?= htmlspecialchars($lycee['logo'] ?? '') ?>" alt="Logo">
            <?php else: ?>
                <div style="width: 100px;"></div>
            <?php endif; ?>
            <div class="header-text">
                <h1><?= htmlspecialchars($lycee['nom_lycee'] ?? '') ?></h1>
                <p><?= htmlspecialchars($lycee['devise'] ?? '') ?></p>
                <p><?= htmlspecialchars(($lycee['quartier'] ?? '') . ' ' . ($lycee['ville'] ?? '')) ?> - BP: <?= htmlspecialchars($lycee['boite_postale'] ?? 'N/A') ?></p>
                <p>Tel: <?= htmlspecialchars($lycee['tel'] ?? '') ?> / Email: <?= htmlspecialchars($lycee['email'] ?? '') ?></p>
            </div>
            <div style="width: 100px; text-align: right;">
                <span class="info-label">N° Reçu</span>
                <span class="info-value" style="font-size: 16px; color: #28a745;"><?= htmlspecialchars($paiement['recu_numero'] ?? '') ?></span>
            </div>
        </div>

        <div class="receipt-title">
            <h2>Reçu de Scolarité Mensuelle</h2>
        </div>

        <div class="info-section">
            <div class="info-box">
                <span class="info-label">Élève</span>
                <span class="info-value"><?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></span>
                <?php if (!empty($eleve['identifiant_public'])): ?>
                    <br><span class="info-label" style="margin-top: 5px;">Matricule: <?= htmlspecialchars($eleve['identifiant_public'] ?? '') ?></span>
                <?php endif; ?>
            </div>
            <div class="info-box">
                <span class="info-label">Année Académique</span>
                <span class="info-value"><?= htmlspecialchars($paiement['annee_academique'] ?? '') ?></span>
            </div>
            <div class="info-box">
                <span class="info-label">Classe</span>
                <span class="info-value"><?= htmlspecialchars($classe ? Classe::getFormattedName($classe) : '') ?></span>
            </div>
            <div class="info-box">
                <span class="info-label">Date du versement</span>
                <span class="info-value"><?= date('d/m/Y à H:i', strtotime($paiement['date_paiement'])) ?></span>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Désignation de la mensualité</th>
                    <th>Mode</th>
                    <th>Référence</th>
                    <th style="text-align: right;">Montant Versé</th>
                </tr>
            </thead>
            <tbody>
                <?php $totalMens = 0; ?>
                <?php if (isset($mensualites) && is_array($mensualites)): ?>
                    <?php foreach ($mensualites as $m): ?>
                        <?php $totalMens += (float)$m['montant']; ?>
                        <tr>
                            <td>
                                Paiement scolarité - Mois de <strong><?= htmlspecialchars($m['mois_ou_sequence'] ?? '') ?></strong>
                            </td>
                            <td><?= htmlspecialchars($m['mode_paiement'] ?? '') ?></td>
                            <td><?= htmlspecialchars($m['reference_transaction'] ?? 'Espèces') ?></td>
                            <td style="text-align: right; font-weight: bold;">
                                <?= number_format($m['montant'], 0, ',', ' ') ?> FCFA
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php $totalMens = (float)$paiement['montant']; ?>
                    <tr>
                        <td>
                            Paiement scolarité - Mois de <strong><?= htmlspecialchars($paiement['mois_ou_sequence'] ?? '') ?></strong>
                        </td>
                        <td><?= htmlspecialchars($paiement['mode_paiement'] ?? '') ?></td>
                        <td><?= htmlspecialchars($paiement['reference_transaction'] ?? 'Espèces') ?></td>
                        <td style="text-align: right; font-weight: bold;">
                            <?= number_format($paiement['montant'], 0, ',', ' ') ?> FCFA
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr style="background: #f0f0f0;">
                    <td colspan="3" style="text-align: right; font-weight: bold; text-transform: uppercase;">Total versé</td>
                    <td style="text-align: right; font-weight: bold; font-size: 16px;"><?= number_format($totalMens, 0, ',', ' ') ?> FCFA</td>
                </tr>
            </tfoot>
        </table>

        <div class="amount-words">
            <strong>Arrêté le présent reçu à la somme de :</strong><br>
            <span style="text-transform: capitalize; font-weight: bold;"><?= number_format($totalMens, 0, ',', ' ') ?> Francs CFA</span>
        </div>

        <div class="footer">
            <div class="signature-box">
                <p>Le Parent / L'Élève</p>
                <div class="signature-line">Signature</div>
            </div>
            <?php
            require_once __DIR__ . '/../../models/ParametreUtilisateur.php';
            $caissierSettings = null;
            if (!empty($caissier['id_user'])) {
                $caissierSettings = ParametreUtilisateur::findByUserId($caissier['id_user']);
            }
            ?>
            <div class="signature-box" style="position: relative;">
                <p>Le Caissier / Comptable</p>
                <div style="height: 60px; display: flex; align-items: center; justify-content: center; position: relative;">
                    <?php if ($caissierSettings && !empty($caissierSettings->signature)): ?>
                        <img src="<?= htmlspecialchars($caissierSettings->signature) ?>" alt="Signature" style="max-height: 50px; position: absolute; z-index: 2;">
                    <?php endif; ?>
                    <?php if (!empty($lycee['tampon_ecole'])): ?>
                        <img src="<?= htmlspecialchars($lycee['tampon_ecole']) ?>" alt="Tampon Établissement" style="max-height: 55px; opacity: 0.75; position: absolute; z-index: 1;">
                    <?php endif; ?>
                </div>
                <div class="signature-line" style="margin-top: 5px;">
                    <?= htmlspecialchars(($caissier['prenom'] ?? '') . ' ' . ($caissier['nom'] ?? '')) ?>
                </div>
                <p style="font-size: 10px; margin-top: 5px;">Validé informatiquement</p>
            </div>
        </div>

        <div style="margin-top: 30px; text-align: center; font-size: 10px; color: #999; border-top: 1px dashed #ddd; padding-top: 10px;">
            Ce reçu est une pièce comptable officielle. Merci de le conserver pour toute réclamation.<br>
            Généré le <?= date('d/m/Y H:i:s') ?> par <?= htmlspecialchars(Auth::user()['prenom'] . ' ' . Auth::user()['nom']) ?>
        </div>
    </div>
</body>
</html>
