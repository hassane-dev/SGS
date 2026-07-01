<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= _('Reçu de Vente') ?> - <?= $vente['recu_numero'] ?></title>
    <style>
        body { font-family: 'Arial', sans-serif; font-size: 14px; color: #333; margin: 0; padding: 20px; }
        .receipt-container { max-width: 800px; margin: auto; border: 1px solid #eee; padding: 30px; box-shadow: 0 0 10px rgba(0,0,0,0.05); }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #007bff; padding-bottom: 20px; margin-bottom: 20px; }
        .school-info h2 { margin: 0; color: #007bff; }
        .receipt-title { text-align: center; margin: 20px 0; }
        .receipt-title h1 { margin: 0; text-transform: uppercase; letter-spacing: 2px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .info-box { background: #f8f9fa; padding: 15px; border-radius: 5px; }
        .info-box h4 { margin: 0 0 10px 0; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th { background: #007bff; color: #fff; text-align: left; padding: 12px; }
        td { padding: 12px; border-bottom: 1px solid #eee; }
        .total-row td { font-weight: bold; font-size: 1.2em; border-top: 2px solid #333; }
        .footer { text-align: center; margin-top: 50px; font-size: 0.9em; color: #777; }
        .signature-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 100px; margin-top: 40px; }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
            .receipt-container { box-shadow: none; border: none; }
        }
    </style>
</head>
<body>

<div class="no-print" style="margin-bottom: 20px; text-align: center;">
    <button onclick="window.print()" style="padding: 10px 20px; background: #007bff; color: #fff; border: none; border-radius: 5px; cursor: pointer;">
        <?= _('Imprimer le reçu') ?>
    </button>
    <a href="/boutique/achats?eleve_id=<?= $vente['eleve_id'] ?>" style="margin-left: 10px;"><?= _('Retour') ?></a>
</div>

<div class="receipt-container">
    <div class="header">
        <div class="school-info">
            <h2><?= htmlspecialchars($lycee['nom_lycee'] ?? '') ?></h2>
            <p><?= htmlspecialchars($lycee['ville'] ?? '') ?>, <?= htmlspecialchars($lycee['quartier'] ?? '') ?></p>
            <p><?= _('Tél') ?>: <?= htmlspecialchars($lycee['tel'] ?? '') ?></p>
        </div>
        <div class="receipt-no">
            <h3 style="margin: 0;"><?= _('REÇU') ?> #<?= $vente['recu_numero'] ?? '' ?></h3>
            <p><?= _('Date') ?>: <?= date('d/m/Y H:i', strtotime($vente['date_vente'] ?? 'now')) ?></p>
        </div>
    </div>

    <div class="receipt-title">
        <h1><?= _('Reçu de Vente Boutique') ?></h1>
    </div>

    <div class="info-grid">
        <div class="info-box">
            <h4><?= _('Élève') ?></h4>
            <p><strong><?= htmlspecialchars(($vente['eleve_nom'] ?? '') . ' ' . ($vente['eleve_prenom'] ?? '')) ?></strong></p>
            <p><?= _('ID') ?>: <?= $vente['eleve_id'] ?? '' ?></p>
        </div>
        <div class="info-box">
            <h4><?= _('Vendu par') ?></h4>
            <p><?= htmlspecialchars(($vente['user_nom'] ?? '') . ' ' . ($vente['user_prenom'] ?? '')) ?></p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th><?= _('Article') ?></th>
                <th><?= _('Prix Unitaire') ?></th>
                <th><?= _('Quantité') ?></th>
                <th style="text-align: right;"><?= _('Total') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['nom_article'] ?? '') ?></td>
                    <td><?= number_format($item['prix_unitaire'], 0, ',', ' ') ?> FCFA</td>
                    <td><?= $item['quantite'] ?></td>
                    <td style="text-align: right;"><?= number_format($item['prix_unitaire'] * $item['quantite'], 0, ',', ' ') ?> FCFA</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="3" style="text-align: right;"><?= _('TOTAL À PAYER') ?></td>
                <td style="text-align: right; color: #007bff;"><?= number_format($vente['montant_total'], 0, ',', ' ') ?> FCFA</td>
            </tr>
        </tfoot>
    </table>

    <div class="signature-grid">
        <div style="text-align: center;">
            <p><?= _('Signature de l\'élève') ?></p>
            <div style="height: 80px;"></div>
            <p>.........................................</p>
        </div>
        <div style="text-align: center;">
            <p><?= _('Le Caissier') ?></p>
            <div style="height: 80px;"></div>
            <p><strong><?= htmlspecialchars($vente['user_nom'] ?? '') ?></strong></p>
        </div>
    </div>

    <div class="footer">
        <p><?= _('Merci pour votre achat !') ?></p>
        <p><small><?= htmlspecialchars($lycee['nom_lycee'] ?? '') ?> - <?= date('Y') ?></small></p>
    </div>
</div>

</body>
</html>
