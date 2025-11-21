<!-- Force file recognition -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Carte d'Identité Scolaire</title>
    <!-- Using Bootstrap 5 for consistency -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.rawgit.com/davidshimjs/qrcodejs/gh-pages/qrcode.min.js"></script>
    <style>
        @media print {
            body { -webkit-print-color-adjust: exact; }
            .no-print { display: none; }
        }
        #card-container {
            width: 85.6mm;
            height: 53.98mm;
            position: relative;
            border: 1px solid #000;
            margin: 20px auto;
            background-size: cover;
            background-position: center;
        }
        .card-element {
            position: absolute;
            box-sizing: border-box;
            overflow: hidden;
            white-space: nowrap;
        }
        .card-element img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
    </style>
</head>
<body class="bg-light">

    <div id="card-container">
        <!-- Elements will be injected by JS -->
    </div>

    <div class="text-center mt-4 no-print">
        <button onclick="window.print()" class="btn btn-primary">
            Imprimer la Carte
        </button>
        <a href="/eleves/details?id=<?= $data['eleve']['id_eleve'] ?>" class="btn btn-secondary ms-2">
            Retour
        </a>
    </div>

    <script>
        const layout = JSON.parse(<?= json_encode($data['modele']['layout_data']) ?>);
        const eleve = <?= json_encode($data['eleve']) ?>;
        const classe = <?= json_encode($data['classe']) ?>;
        const container = document.getElementById('card-container');

        // Set background
        const background = "<?= htmlspecialchars($data['modele']['background'] ?? '', ENT_QUOTES, 'UTF-8') ?>";
        if (background) {
            if (background.startsWith('#') || background.startsWith('rgb')) {
                container.style.backgroundColor = background;
            } else {
                container.style.backgroundImage = `url(${background})`;
            }
        }

        // Data mapping
        const elementData = {
            photo: `<img src="${eleve.photo}" alt="Photo">`,
            nom_complet: `${eleve.prenom} ${eleve.nom}`,
            matricule: `Matricule: ${eleve.id_eleve}`, // Simplified
            classe: `Classe: ${classe.nom_classe}`,
            qr_code: '<div id="qrcode-container"></div>'
        };

        // Position elements
        for (const id in layout) {
            if (elementData[id]) {
                const pos = layout[id];
                const el = document.createElement('div');
                el.className = 'card-element';
                el.style.left = pos.left;
                el.style.top = pos.top;
                el.style.width = pos.width;
                el.style.height = pos.height;
                el.innerHTML = elementData[id];
                container.appendChild(el);
            }
        }

        // Generate QR Code
        const qrContainer = document.getElementById('qrcode-container');
        if (qrContainer) {
            new QRCode(qrContainer, {
                text: `EleveID:${eleve.id_eleve}`,
                width: parseInt(layout.qr_code.width) || 100,
                height: parseInt(layout.qr_code.height) || 100,
            });
        }
    </script>

</body>
</html>
