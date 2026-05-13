<!-- Force file recognition -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Carte d'Identité Scolaire - <?= htmlspecialchars($data['eleve']['nom'] . ' ' . $data['eleve']['prenom']) ?></title>
    <link href="/assets/css/bootstrap.min.css" rel="stylesheet">
    <script src="/assets/libs/qrcode/qrcode.min.js"></script>
    <style>
        @page {
            size: 85.6mm 53.98mm;
            margin: 0;
        }
        @media print {
            body { -webkit-print-color-adjust: exact; margin: 0; }
            .no-print { display: none; }
            #card-container { border: none !important; margin: 0 !important; box-shadow: none !important; }
        }
        body {
            background-color: #f8f9fa;
            font-family: 'Arial', sans-serif;
        }
        #card-container {
            width: 85.6mm;
            height: 53.98mm;
            position: relative;
            background-color: #fff;
            border: 1px solid #ddd;
            margin: 50px auto;
            overflow: hidden;
            background-size: cover;
            background-position: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .card-element {
            position: absolute;
            box-sizing: border-box;
            line-height: 1.2;
        }
        .card-element img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .qr-code-container {
            background: white;
            padding: 2px;
        }
        .qr-logo-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 20%;
            height: 20%;
            background: white;
            border-radius: 2px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1px;
        }
        .qr-logo-overlay img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
    </style>
</head>
<body>

    <div id="card-container">
        <!-- Elements will be injected here -->
    </div>

    <div class="text-center mt-4 no-print">
        <button onclick="window.print()" class="btn btn-primary btn-lg shadow">
            <i class="ph ph-printer"></i> Imprimer la Carte
        </button>
        <a href="/eleves/details?id=<?= $data['eleve']['id_eleve'] ?>" class="btn btn-outline-secondary btn-lg ms-2">
            Retour
        </a>
    </div>

    <script>
        const modelData = <?= json_encode($data['modele']) ?>;
        const layout = JSON.parse(modelData.layout_data || '{"elements":[]}');
        const eleve = <?= json_encode($data['eleve']) ?>;
        const classe = <?= json_encode($data['classe']) ?>;
        const lycee = <?= json_encode($data['lycee']) ?>;
        const annee = <?= json_encode($data['annee']) ?>;
        const secureToken = "<?= $data['secure_token'] ?>";
        const container = document.getElementById('card-container');

        // Scaling factor from Editor (2x 96DPI) to Print (96DPI)
        // Editor width was 647px for 85.6mm.
        // 85.6mm @ 96DPI is ~324px.
        const scale = 324 / 647;

        // Set background
        if (modelData.background) {
            container.style.backgroundImage = `url(${modelData.background})`;
        }

        const elements = layout.elements || [];
        const isMultilingual = <?= (!empty($data['lycee']['multilingue_actif']) && ($data['lycee']['nb_langue'] ?? 1) > 1) ? 'true' : 'false' ?>;
        let qrCounter = 0;

        elements.forEach(elData => {
            const el = document.createElement('div');
            el.className = 'card-element';
            el.style.left = (elData.left * scale) + 'px';
            el.style.top = (elData.top * scale) + 'px';
            el.style.width = (elData.width * scale) + 'px';
            el.style.height = (elData.height * scale) + 'px';

            if (elData.angle) {
                el.style.transform = `rotate(${elData.angle}deg)`;
            }

            let content = '';
            switch (elData.type) {
                case 'photo':
                    content = `<img src="${eleve.photo || '/assets/img/default-avatar.png'}" alt="Photo">`;
                    break;
                case 'logo':
                    content = `<img src="${modelData.logo_lycee || '/assets/img/logo-placeholder.png'}" alt="Logo">`;
                    break;
                case 'nom_complet':
                    content = `${eleve.prenom} ${eleve.nom}`.toUpperCase();
                    break;
                case 'matricule':
                    content = `${eleve.id_eleve}`;
                    break;
                case 'classe':
                    const className = `${classe.niveau} ${classe.serie ? ' / ' + classe.serie : ''} ${classe.numero || ''}`.trim();
                    content = className;
                    break;
                case 'serie':
                    content = `${classe.serie || 'N/A'}`;
                    break;
                case 'annee':
                    content = `${annee ? annee.libelle : '2024-2025'}`;
                    break;
                case 'date_naissance':
                    content = `${eleve.date_naissance || 'N/A'}`;
                    break;
                case 'sexe':
                    content = `${eleve.sexe || 'N/A'}`;
                    break;
                case 'signature_directeur':
                    content = `<img src="${lycee.signature_directeur || '/assets/img/placeholder-signature.png'}" alt="Signature">`;
                    break;
                case 'tampon_ecole':
                    content = `<img src="${lycee.tampon_ecole || '/assets/img/placeholder-tampon.png'}" alt="Tampon" style="opacity:0.6;">`;
                    break;
                case 'qr_code':
                    qrCounter++;
                    el.classList.add('qr-code-container');
                    content = `<div id="qrcode-${qrCounter}" style="width:100%; height:100%;"></div>`;
                    if (modelData.logo_lycee) {
                        content += `<div class="qr-logo-overlay"><img src="${modelData.logo_lycee}"></div>`;
                    }
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
                    // If isMultilingual and this is the original "primary" placeholder, but it's on the left, it might be the secondary language.
                    // But our logic in editor now swaps them.
                    content = (elData.text || lycee.header_primary || '').replace(/\n/g, '<br>');
                    el.setAttribute('dir', 'auto');
                    break;
                case 'header_right':
                    content = (elData.text || lycee.header_secondary || lycee.nom_lycee || '').replace(/\n/g, '<br>');
                    el.setAttribute('dir', 'auto');
                    break;
            }

            if (elData.fontSize) {
                el.style.fontSize = (elData.fontSize * scale) + 'px';
            }
            if (elData.fill) {
                if (['text', 'nom_complet', 'matricule', 'classe', 'serie', 'annee', 'header_left', 'header_right'].includes(elData.type)) {
                    el.style.color = elData.fill;
                } else if (['rect', 'circle'].includes(elData.type)) {
                    el.style.backgroundColor = elData.fill;
                }
            }
            if (elData.stroke) {
                el.style.border = `${(elData.strokeWidth || 1) * scale}px solid ${elData.stroke}`;
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
                    text: secureToken,
                    width: elData.width * scale,
                    height: elData.height * scale,
                    correctLevel: QRCode.CorrectLevel.H // High error correction for logo overlay
                });
            }
        });
    </script>

</body>
</html>
