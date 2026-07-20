<!-- Force file recognition -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= _("Cartes d'Identité Scolaire") ?></title>
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
            .card-wrapper { border: none !important; margin: 0 !important; box-shadow: none !important; page-break-after: always; }
        }
        body {
            background-color: #f8f9fa;
            font-family: 'Arial', sans-serif;
        }
        .card-wrapper {
            width: 85.6mm;
            height: 53.98mm;
            position: relative;
            background-color: #fff;
            border: 1px solid #ddd;
            margin: 20px auto;
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

    <div id="cards-container">
        <!-- Cards will be injected here -->
    </div>

    <div class="text-center mt-4 no-print mb-5">
        <button onclick="window.print()" class="btn btn-primary btn-lg shadow">
            <i class="ph ph-printer"></i> <?= _('Imprimer les Cartes') ?>
        </button>
        <?php if (count($data['students']) === 1): ?>
            <a href="/eleves/details?id=<?= $data['students'][0]['eleve']['id_eleve'] ?>" class="btn btn-outline-secondary btn-lg ms-2">
                <?= _('Retour') ?>
            </a>
        <?php else: ?>
            <a href="/classes/show?id=<?= $data['classe']['id_classe'] ?>" class="btn btn-outline-secondary btn-lg ms-2">
                <?= _('Retour') ?>
            </a>
        <?php endif; ?>
    </div>

    <script>
        const modelData = <?= json_encode($data['modele']) ?>;
        const layout = JSON.parse(modelData.layout_data || '{"elements":[]}');
        const students = <?= json_encode($data['students']) ?>;
        const classe = <?= json_encode($data['classe']) ?>;
        const lycee = <?= json_encode($data['lycee']) ?>;
        const annee = <?= json_encode($data['annee']) ?>;
        const mainContainer = document.getElementById('cards-container');

        // Scaling factor from Editor (2x 96DPI) to Print (96DPI)
        const scale = 324 / 647;

        students.forEach((studentData, index) => {
            const eleve = studentData.eleve;
            const secureToken = studentData.secure_token;

            const cardWrapper = document.createElement('div');
            cardWrapper.className = 'card-wrapper';
            cardWrapper.id = `card-${index}`;

            // Set background
            if (modelData.background) {
                cardWrapper.style.backgroundImage = `url(${modelData.background})`;
            }

            const elements = layout.elements || [];
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
                        content = (elData.text || '{nom_complet}').replace('{nom_complet}', `${eleve.prenom} ${eleve.nom}`.toUpperCase());
                        break;
                    case 'matricule':
                        content = (elData.text || '{matricule}').replace('{matricule}', `${eleve.identifiant_public || eleve.id_eleve}`);
                        break;
                    case 'classe':
                        const className = `${classe.niveau} ${classe.serie ? ' / ' + classe.serie : ''} ${classe.numero || ''}`.trim();
                        content = (elData.text || '{classe}').replace('{classe}', className);
                        break;
                    case 'serie':
                        content = (elData.text || '{serie}').replace('{serie}', `${classe.serie || 'N/A'}`);
                        break;
                    case 'annee':
                        content = (elData.text || '{annee}').replace('{annee}', `${annee ? annee.libelle : '2024-2025'}`);
                        break;
                    case 'date_naissance':
                        content = (elData.text || '{date_naissance}').replace('{date_naissance}', `${eleve.date_naissance || 'N/A'}`);
                        break;
                    case 'sexe':
                        content = (elData.text || '{sexe}').replace('{sexe}', `${eleve.sexe || 'N/A'}`);
                        break;
                    case 'signature_directeur':
                        content = `<img src="${lycee.signature_directeur || '/assets/img/placeholder-signature.png'}" alt="Signature">`;
                        break;
                    case 'tampon_ecole':
                        content = `<img src="${lycee.tampon_ecole || '/assets/img/placeholder-tampon.png'}" alt="Tampon">`;
                        break;
                    case 'qr_code':
                        qrCounter++;
                        el.classList.add('qr-code-container');
                        const qrId = `qrcode-${index}-${qrCounter}`;
                        content = `<div id="${qrId}" style="width:100%; height:100%; position: relative;"></div>`;
                        if (modelData.logo_lycee) {
                            content += `<div class="qr-logo-overlay" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 25%; height: 25%; background: white; padding: 2px; border-radius: 2px; display: flex; align-items: center; justify-content: center;"><img src="${modelData.logo_lycee}" style="max-width: 100%; max-height: 100%; object-fit: contain;"></div>`;
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
                        content = (lycee.header_primary || elData.text || '').replace(/\n/g, '<br>');
                        el.setAttribute('dir', 'auto');
                        break;
                    case 'header_right':
                        content = (lycee.header_secondary || lycee.nom_lycee || elData.text || '').replace(/\n/g, '<br>');
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
                if (elData.fontFamily) {
                    el.style.fontFamily = elData.fontFamily;
                }
                if (elData.opacity !== undefined) {
                    el.style.opacity = elData.opacity;
                }

                el.innerHTML = content;
                cardWrapper.appendChild(el);
            });

            mainContainer.appendChild(cardWrapper);

            // Generate QR Codes after adding to DOM
            let currentQr = 0;
            elements.forEach(elData => {
                if (elData.type === 'qr_code') {
                    currentQr++;
                    const qrId = `qrcode-${index}-${currentQr}`;
                    new QRCode(document.getElementById(qrId), {
                        text: secureToken,
                        width: elData.width * scale,
                        height: elData.height * scale,
                        correctLevel: QRCode.CorrectLevel.H
                    });
                }
            });
        });
    </script>

</body>
</html>
