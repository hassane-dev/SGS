<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Reçu de Paiement - <?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></title>
    <style>
        body { font-family: 'Arial', sans-serif; color: #333; margin: 0; padding: 20px; font-size: 14px; }
        .receipt-container { max-width: 800px; margin: auto; border: 2px solid #333; padding: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        .header img { max-width: 80px; }
        .header-text { text-align: center; flex-grow: 1; }
        .header-text h1 { margin: 0; font-size: 18px; text-transform: uppercase; }
        .header-text p { margin: 2px 0; font-size: 12px; }
        .receipt-title { text-align: center; margin-bottom: 20px; }
        .receipt-title h2 { margin: 0; border: 1px solid #333; display: inline-block; padding: 5px 20px; background: #f0f0f0; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 20px; }
        .info-item { margin-bottom: 5px; }
        .info-label { font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table th, table td { border: 1px solid #333; padding: 10px; text-align: left; }
        table th { background: #f0f0f0; }
        .footer { display: flex; justify-content: space-between; margin-top: 50px; }
        .signature-box { text-align: center; width: 200px; }
        .signature-line { margin-top: 50px; border-top: 1px solid #333; }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
            .receipt-container { border: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <button onclick="window.print();" style="padding: 10px 20px; cursor: pointer;">Imprimer le Reçu</button>
        <button onclick="window.history.back();" style="padding: 10px 20px; cursor: pointer;">Retour</button>
    </div>

    <div class="receipt-container">
        <div class="header">
            <?php if (!empty($lycee['logo'])): ?>
                <img src="<?= htmlspecialchars($lycee['logo']) ?>" alt="Logo">
            <?php else: ?>
                <div style="width: 80px;"></div>
            <?php endif; ?>
            <div class="header-text">
                <h1><?= htmlspecialchars($lycee['nom_lycee']) ?></h1>
                <p><?= htmlspecialchars($lycee['devise']) ?></p>
                <p><?= htmlspecialchars($lycee['ville']) ?> - <?= htmlspecialchars($lycee['tel']) ?></p>
                <p><?= htmlspecialchars($lycee['email']) ?></p>
            </div>
            <div style="width: 80px; text-align: right; font-weight: bold;">
                N° <?= htmlspecialchars($paiement['recu_numero']) ?>
            </div>
        </div>

        <div class="receipt-title">
            <h2>REÇU DE PAIEMENT SCOLARITÉ</h2>
        </div>

        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">Élève :</span> <?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?>
            </div>
            <div class="info-item">
                <span class="info-label">Année Académique :</span> <?= htmlspecialchars($paiement['annee_academique']) ?>
            </div>
            <div class="info-item">
                <span class="info-label">Classe :</span> <?= htmlspecialchars(Classe::getFormattedName($classe)) ?>
            </div>
            <div class="info-item">
                <span class="info-label">Date :</span> <?= date('d/m/Y H:i', strtotime($paiement['date_paiement'])) ?>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Désignation</th>
                    <th>Mode de Paiement</th>
                    <th>Référence</th>
                    <th style="text-align: right;">Montant</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Paiement scolarité - Mois de <?= htmlspecialchars($paiement['mois_ou_sequence']) ?></td>
                    <td><?= htmlspecialchars($paiement['mode_paiement']) ?></td>
                    <td><?= htmlspecialchars($paiement['reference_transaction'] ?? 'N/A') ?></td>
                    <td style="text-align: right; font-weight: bold;"><?= number_format($paiement['montant'], 0, ',', ' ') ?> FCFA</td>
                </tr>
            </tbody>
        </table>

        <p><strong>Arrêté le présent reçu à la somme de :</strong> <span style="text-transform: capitalize; font-style: italic;"><?= htmlspecialchars($paiement['montant']) ?> Francs CFA</span></p>

        <div class="footer">
            <div class="signature-box">
                <p>Le Parent / L'Élève</p>
                <div class="signature-line"></div>
            </div>
            <div class="signature-box">
                <p>Le Comptable</p>
                <div class="signature-line"></div>
                <p style="margin-top: 10px; font-size: 10px;"><?= htmlspecialchars(Auth::user()['prenom'] . ' ' . Auth::user()['nom']) ?></p>
            </div>
        </div>
    </div>
</body>
</html>
