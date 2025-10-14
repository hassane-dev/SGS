<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Reçu d'Inscription - <?= htmlspecialchars($eleve['nom'] . ' ' . $eleve['prenom']) ?></title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .receipt-container {
            max-width: 800px;
            margin: 50px auto;
            border: 2px solid #000;
            padding: 30px;
            background-color: #f9f9f9;
        }
        .receipt-header {
            text-align: center;
            margin-bottom: 40px;
        }
        .receipt-header h1 {
            font-size: 2.5rem;
            margin: 0;
        }
        .receipt-header p {
            font-size: 1rem;
            margin: 0;
        }
        .receipt-footer {
            text-align: center;
            margin-top: 50px;
            font-size: 0.9rem;
            color: #555;
        }
        @media print {
            body { -webkit-print-color-adjust: exact; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

<div class="receipt-container">
    <div class="receipt-header">
        <h1>REÇU DE PAIEMENT</h1>
        <p><?= htmlspecialchars($lycee['nom_lycee']) ?></p>
        <p><?= htmlspecialchars($lycee['adresse'] . ' - ' . $lycee['ville']) ?></p>
        <p>Tel: <?= htmlspecialchars($lycee['telephone']) ?></p>
    </div>

    <h4>Reçu N°: <?= htmlspecialchars($inscription['id_inscription']) ?></h4>
    <p><strong>Date:</strong> <?= date('d/m/Y', strtotime($inscription['date_inscription'])) ?></p>

    <hr>

    <p><strong>Reçu de:</strong> <?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></p>
    <p><strong>Classe:</strong> <?= htmlspecialchars($inscription['nom_classe']) ?></p>
    <p><strong>Année Académique:</strong> <?= htmlspecialchars($inscription['annee_academique']) ?></p>

    <table class="table table-bordered mt-4">
        <thead>
            <tr>
                <th>Description</th>
                <th class="text-right">Montant (XOF)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Frais d'inscription</td>
                <td class="text-right"><?= htmlspecialchars(number_format($inscription['montant_total'], 2)) ?></td>
            </tr>
        </tbody>
        <tfoot>
            <tr class="font-weight-bold">
                <td>Montant Versé</td>
                <td class="text-right"><?= htmlspecialchars(number_format($inscription['montant_verse'], 2)) ?></td>
            </tr>
            <tr class="font-weight-bold <?= $inscription['reste_a_payer'] > 0 ? 'text-danger' : '' ?>">
                <td>Reste à Payer</td>
                <td class="text-right"><?= htmlspecialchars(number_format($inscription['reste_a_payer'], 2)) ?></td>
            </tr>
        </tfoot>
    </table>

    <div class="receipt-footer">
        <p>Merci pour votre paiement.</p>
    </div>
</div>

<div class="text-center mt-4 no-print">
    <button onclick="window.print();" class="btn btn-primary">Imprimer le Reçu</button>
    <a href="/eleves/details?id=<?= $eleve['id_eleve'] ?>" class="btn btn-secondary">Retour aux Détails</a>
</div>

</body>
</html>