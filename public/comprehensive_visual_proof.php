<?php
$params_lycee = [
    'nom_lycee' => 'LYCÉE MODERNE DE RÉFÉRENCE',
    'logo' => '/assets/img/logo-placeholder.png',
    'header_primary' => "RÉPUBLIQUE DU TCHAD\nUnité - Travail - Progrès\n**********\nMINISTÈRE DE L'ÉDUCATION NATIONALE",
    'header_secondary' => "LYCÉE MODERNE DE RÉFÉRENCE\nDEUXIÈME LANGUE OFFICIELLE",
    'signature_directeur' => '/assets/img/placeholder-signature.png',
    'tampon_ecole' => '/assets/img/placeholder-tampon.png'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Visual Proof - Professional Card Engine v2.1</title>
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/card-editor.css">
    <script src="/assets/libs/jquery.min.js"></script>
    <script src="/assets/libs/fabric/fabric.min.js"></script>
    <style>
        body { padding: 20px; background: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .proof-container { max-width: 1400px; margin: auto; }
        .section-card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); margin-bottom: 30px; }
        #editor-canvas-container, #preview-canvas-container {
            background: #e9ecef; padding: 30px; border-radius: 8px; display: flex; justify-content: center;
        }
        .canvas-shadow { box-shadow: 0 10px 40px rgba(0,0,0,0.15); border-radius: 4px; overflow: hidden; border: 1px solid #ddd; }
        .badge-pro { background: #0056b3; color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="proof-container">
        <div class="text-center mb-5">
            <h1 class="fw-bold">SYNTHÈSE VISUELLE : MOTEUR DE CARTES V2.1</h1>
            <p class="text-muted">Démonstration du modèle moderne prédéfini, bilingue et sécurisé</p>
        </div>

        <div class="row">
            <!-- Part 1: The Editor -->
            <div class="col-lg-6">
                <div class="section-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="mb-0">1. L'Éditeur Professionnel</h4>
                        <span class="badge-pro">INTERFACE ADMIN</span>
                    </div>
                    <div class="row mb-3">
                        <div class="col-4"><div class="p-2 border rounded text-center small bg-light">THEMES PRO</div></div>
                        <div class="col-4"><div class="p-2 border rounded text-center small bg-light">BILINGUE</div></div>
                        <div class="col-4"><div class="p-2 border rounded text-center small bg-light">OFFLINE</div></div>
                    </div>
                    <div id="editor-canvas-container">
                        <div class="canvas-shadow">
                            <canvas id="canvas-editor"></canvas>
                        </div>
                    </div>
                    <div class="mt-3 text-center">
                        <button class="btn btn-sm btn-primary">Sauvegarder le Template</button>
                        <button class="btn btn-sm btn-outline-secondary ms-2">Réinitialiser (Pro)</button>
                    </div>
                </div>
            </div>

            <!-- Part 2: The Generated Result -->
            <div class="col-lg-6">
                <div class="section-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="mb-0">2. Rendu Final (Impression)</h4>
                        <span class="badge-pro" style="background:#198754;">PDF / CR80</span>
                    </div>
                    <div class="row mb-3">
                        <div class="col-4"><div class="p-2 border rounded text-center small bg-light">QR SÉCURISÉ</div></div>
                        <div class="col-4"><div class="p-2 border rounded text-center small bg-light">FILIGRANE</div></div>
                        <div class="col-4"><div class="p-2 border rounded text-center small bg-light">SIGNÉ / TAMPONNÉ</div></div>
                    </div>
                    <div id="preview-canvas-container">
                        <div class="canvas-shadow">
                            <canvas id="canvas-preview"></canvas>
                        </div>
                    </div>
                    <div class="mt-3 text-center">
                        <button class="btn btn-sm btn-success px-4">Lancer l'Impression</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="alert alert-primary text-center mt-2 shadow-sm">
            <i class="ph ph-info"></i> <strong>Note technique :</strong> Le système détecte automatiquement les informations administratives (Tchad/Lycée) et génère un QR code unique signé par hash SHA-256.
        </div>
    </div>

    <script>
        const editor = new fabric.Canvas('canvas-editor', { width: 500, height: 315, backgroundColor: '#fff' });
        const preview = new fabric.Canvas('canvas-preview', { width: 500, height: 315, backgroundColor: '#fff' });
        const scale = 500 / 647;

        const hL = `<?= str_replace("\n", "\\n", addslashes($params_lycee['header_primary'])) ?>`;
        const hR = `<?= str_replace("\n", "\\n", addslashes($params_lycee['header_secondary'])) ?>`;

        function drawCard(c, color = '#0056b3', isFinal = false) {
            c.clear();
            const s = scale;

            // Watermark (only for final)
            if (isFinal) {
                c.add(new fabric.IText("OFFICIEL ".repeat(8), {
                    left: -50, top: 150, fontSize: 30*s, fill: '#f1f1f1', opacity: 0.4, angle: -30, selectable: false
                }));
            }

            // Bg & Accents
            c.add(new fabric.Rect({ left: 0, top: 0, width: 647*s, height: 110*s, fill: '#f8f9fa', selectable: false }));
            c.add(new fabric.Rect({ left: 0, top: 107*s, width: 647*s, height: 6*s, fill: color, selectable: false }));
            c.add(new fabric.Rect({ left: 0, top: 383*s, width: 647*s, height: 25*s, fill: color, selectable: false }));

            // Headers
            c.add(new fabric.IText(hL, { left: 20*s, top: 20*s, fontSize: 8*s, textAlign: 'center', fontWeight: 'bold', fill: '#333' }));
            c.add(new fabric.IText(hR, { left: 427*s, top: 20*s, fontSize: 8*s, textAlign: 'center', fontWeight: 'bold', fill: '#333' }));

            // Title
            c.add(new fabric.IText("CARTE D'IDENTITÉ SCOLAIRE", {
                left: 0, top: 85*s, fontSize: 14*s, textAlign: 'center', fontWeight: 'bold', fill: color, width: 647*s
            }));

            // Photo Frame & Info
            c.add(new fabric.Rect({ left: 35*s, top: 145*s, width: 150*s, height: 190*s, fill: '#fff', stroke: color, strokeWidth: 2*s }));

            const name = isFinal ? "JEAN BAPTISTE DUPONT" : "NOM ET PRÉNOMS";
            c.add(new fabric.IText(name, { left: 210*s, top: 165*s, fontSize: 24*s, fill: color, fontWeight: 'bold' }));
            c.add(new fabric.IText("MATRICULE: " + (isFinal ? "2024-X99" : "2024-ABC"), { left: 210*s, top: 235*s, fontSize: 18*s, fontWeight: 'bold', fill: '#444' }));
            c.add(new fabric.IText("CLASSE: Terminale C2", { left: 210*s, top: 295*s, fontSize: 16*s, fontWeight: 'bold', fill: '#444' }));
            c.add(new fabric.IText("ANNÉE: 2024 - 2025", { left: 210*s, top: 355*s, fontSize: 14*s, fontWeight: 'bold', fill: '#444' }));

            // Footer
            c.add(new fabric.IText("CARTE D'IDENTITÉ SCOLAIRE - DOCUMENT OFFICIEL", {
                left: 0, top: 388*s, fontSize: 9*s, textAlign: 'center', fill: '#fff', width: 647*s
            }));

            // Sign/Stamp
            c.add(new fabric.IText("LE DIRECTEUR", { left: 480*s, top: 325*s, fontSize: 8*s, fontWeight: 'bold', fill: '#333' }));

            // Placeholders
            c.add(new fabric.Rect({ left: 283*s, top: 15*s, width: 80*s, height: 80*s, fill: '#eee', stroke: '#ddd' })); // Logo
            c.add(new fabric.Rect({ left: 40*s, top: 150*s, width: 140*s, height: 180*s, fill: '#eee' })); // Photo
            c.add(new fabric.Rect({ left: 510*s, top: 145*s, width: 100*s, height: 100*s, fill: '#eee', stroke: '#ddd' })); // QR

            if (isFinal) {
                // Signature & Stamp
                c.add(new fabric.Rect({ left: 450*s, top: 310*s, width: 70*s, height: 70*s, fill: '#eee', opacity: 0.6, stroke: color }));
                c.add(new fabric.IText("TAMPON", { left: 460*s, top: 338*s, fontSize: 8*s, fill: '#999' }));
            }

            c.renderAll();
        }

        drawCard(editor, '#0056b3', false);
        drawCard(preview, '#198754', true);
    </script>
</body>
</html>
