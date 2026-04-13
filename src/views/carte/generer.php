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
        const elements = layout.elements || [];
        let qrCounter = 0;

        if (elements.length > 0) {
            // New Format (Fabric.js)
            elements.forEach(elData => {
                const el = document.createElement('div');
                el.className = 'card-element';
                el.style.left = elData.left + 'px';
                el.style.top = elData.top + 'px';
                el.style.width = elData.width + 'px';
                el.style.height = elData.height + 'px';

                if (elData.angle) {
                    el.style.transform = `rotate(${elData.angle}deg)`;
                }

                let content = '';
                switch (elData.type) {
                    case 'photo':
                        content = `<img src="${eleve.photo || '/assets/img/placeholder-photo.png'}" alt="Photo">`;
                        break;
                    case 'logo':
                        content = `<img src="<?= htmlspecialchars($data['modele']['logo_lycee'] ?? '/assets/img/logo-placeholder.png') ?>" alt="Logo">`;
                        break;
                    case 'nom_complet':
                        content = `${eleve.prenom} ${eleve.nom}`;
                        break;
                    case 'matricule':
                        content = `Matricule: ${eleve.id_eleve}`;
                        break;
                    case 'classe':
                        const className = `${classe.niveau} ${classe.serie || ''} ${classe.numero || ''}`.trim();
                        content = `Classe: ${className}`;
                        break;
                    case 'qr_code':
                        qrCounter++;
                        content = `<div id="qrcode-${qrCounter}"></div>`;
                        break;
                    case 'text':
                        content = elData.text;
                        break;
                    case 'rect':
                        el.style.backgroundColor = elData.fill;
                        break;
                    case 'circle':
                        el.style.backgroundColor = elData.fill;
                        el.style.borderRadius = '50%';
                        break;
                    case 'header_left':
                    case 'header_right':
                        content = (elData.text || '').replace(/\n/g, '<br>');
                        break;
                }

                if (elData.fontSize) {
                    el.style.fontSize = elData.fontSize + 'px';
                }
                if (elData.fill && (elData.type === 'text' || elData.type === 'nom_complet' || elData.type === 'matricule' || elData.type === 'classe' || elData.type === 'header_left' || elData.type === 'header_right')) {
                    el.style.color = elData.fill;
                }
                if (elData.textAlign) {
                    el.style.textAlign = elData.textAlign;
                }
                if (elData.fontWeight) {
                    el.style.fontWeight = elData.fontWeight;
                }

                el.innerHTML = content;
                container.appendChild(el);

                if (elData.type === 'qr_code') {
                    new QRCode(document.getElementById(`qrcode-${qrCounter}`), {
                        text: `EleveID:${eleve.id_eleve}`,
                        width: elData.width,
                        height: elData.height,
                    });
                }
            });
        } else {
            // Backward Compatibility: Old format (direct keys in layout object)
            for (const id in layout) {
                if (elementData[id]) {
                    const pos = layout[id];
                    if (typeof pos !== 'object' || pos === null) continue;

                    const el = document.createElement('div');
                    el.className = 'card-element';
                    el.style.left = pos.left;
                    el.style.top = pos.top;
                    el.style.width = pos.width;
                    el.style.height = pos.height;
                    el.innerHTML = elementData[id];
                    container.appendChild(el);

                    if (id === 'qr_code') {
                         new QRCode(document.getElementById('qrcode-container'), {
                            text: `EleveID:${eleve.id_eleve}`,
                            width: parseInt(pos.width) || 100,
                            height: parseInt(pos.height) || 100,
                        });
                    }
                }
            }
        }

    </script>

</body>
</html>
