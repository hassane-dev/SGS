<!-- Force file recognition -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Carte d'Identité Scolaire</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
<body class="bg-gray-100">

    <div id="card-container">
        <!-- Elements will be injected by JS -->
    </div>

    <div class="text-center mt-8 no-print">
        <button onclick="window.print()" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            Imprimer la Carte
        </button>
        <a href="/eleves/details?id=<?= $data['eleve']['id_eleve'] ?>" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded ml-4">
            Retour
        </a>
    </div>

    <script>
        const layout = JSON.parse(<?= json_encode($data['modele']['layout_data']) ?>);
        const eleve = <?= json_encode($data['eleve']) ?>;
        const classe = <?= json_encode($data['classe']) ?>;
        const container = document.getElementById('card-container');

        // Set background
        const background = "<?= $data['modele']['background'] ?>";
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
