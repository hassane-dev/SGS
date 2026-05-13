<!-- Force file recognition -->
<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<!-- Link to the new stylesheet -->
<link rel="stylesheet" href="/assets/css/card-editor.css">


<div class="pc-container">
    <div class="pc-content">
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= _('Card Template Editor') ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success" role="alert">
                <?= _('Template saved successfully!') ?>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <form action="/modele-carte/edit" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="nom_modele" value="Default Card Model">
                            <input type="hidden" name="layout_data" id="layout_data_input" value="<?= htmlspecialchars($modele['layout_data'] ?? '{}') ?>">
                             <input type="hidden" name="current_background" value="<?= htmlspecialchars($modele['background'] ?? '') ?>">

                            <div class="row">
                                <!-- Palette Area -->
                                <div class="col-lg-3">
                                    <div class="alert alert-primary py-2 mb-3" style="font-size: 0.85rem;">
                                        <i class="ph-duotone ph-info fs-5"></i> <?= _('Le modèle professionnel CR80 est prêt. Ajustez simplement les couleurs et le fond.') ?>
                                    </div>

                                    <h5 class="mb-3"><?= _('1. Couleurs du Thème') ?></h5>
                                    <div class="row g-2 mb-4">
                                        <div class="col-4">
                                            <button type="button" class="btn btn-sm btn-outline-primary w-100 theme-preset" data-primary="#0056b3" data-header="#f0f4f8" title="Blue Business">
                                                <div style="height:20px; background:#0056b3; border-radius:3px;"></div>
                                            </button>
                                        </div>
                                        <div class="col-4">
                                            <button type="button" class="btn btn-sm btn-outline-success w-100 theme-preset" data-primary="#198754" data-header="#f8fff9" title="Forest Green">
                                                <div style="height:20px; background:#198754; border-radius:3px;"></div>
                                            </button>
                                        </div>
                                        <div class="col-4">
                                            <button type="button" class="btn btn-sm btn-outline-danger w-100 theme-preset" data-primary="#dc3545" data-header="#fff8f8" title="Academic Red">
                                                <div style="height:20px; background:#dc3545; border-radius:3px;"></div>
                                            </button>
                                        </div>
                                        <div class="col-4">
                                            <button type="button" class="btn btn-sm btn-outline-dark w-100 theme-preset" data-primary="#212529" data-header="#f8f9fa" title="Classic Black">
                                                <div style="height:20px; background:#212529; border-radius:3px;"></div>
                                            </button>
                                        </div>
                                        <div class="col-4">
                                            <button type="button" class="btn btn-sm btn-outline-warning w-100 theme-preset" data-primary="#ffc107" data-header="#fffdf5" title="Golden Sun">
                                                <div style="height:20px; background:#ffc107; border-radius:3px;"></div>
                                            </button>
                                        </div>
                                        <div class="col-4">
                                            <button type="button" class="btn btn-sm btn-outline-secondary w-100 theme-preset" data-primary="#6c757d" data-header="#f8f9fa" title="Corporate Grey">
                                                <div style="height:20px; background:#6c757d; border-radius:3px;"></div>
                                            </button>
                                        </div>
                                    </div>

                                    <h5 class="mb-3 mt-4"><?= _('2. Fond & Filigrane') ?></h5>
                                    <div class="mb-4">
                                        <input class="form-control form-control-sm" type="file" id="background_image" name="background_image" accept="image/*">
                                        <small class="text-muted" style="font-size: 0.75rem;"><?= _('Sélectionnez une image pour l\'arrière-plan.') ?></small>
                                    </div>

                                    <hr>

                                    <div class="d-grid gap-2">
                                        <button class="btn btn-sm btn-light text-start" type="button" data-bs-toggle="collapse" data-bs-target="#advancedPalette">
                                            <i class="ph-duotone ph-caret-down"></i> <?= _('Personnalisation Manuelle (Avancé)') ?>
                                        </button>
                                    </div>

                                    <div class="collapse mt-3" id="advancedPalette">
                                        <h6 class="mb-3 text-uppercase" style="font-size: 0.7rem; letter-spacing: 1px;"><?= _('Ajouter des éléments') ?></h6>
                                        <div id="palette" class="row g-2">
                                            <div class="col-6">
                                                <div class="palette-item" draggable="true" data-type="photo" title="<?= _('Student Photo') ?>">
                                                    <i class="ph-duotone ph-user-circle fs-2"></i>
                                                    <span><?= _('Photo') ?></span>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="palette-item" draggable="true" data-type="logo" title="<?= _('School Logo') ?>">
                                                    <i class="ph-duotone ph-building fs-2"></i>
                                                    <span><?= _('Logo') ?></span>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="palette-item" draggable="true" data-type="nom_complet" title="<?= _('Full Name') ?>">
                                                    <i class="ph-duotone ph-text-aa fs-2"></i>
                                                    <span><?= _('Nom') ?></span>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="palette-item" draggable="true" data-type="qr_code" title="<?= _('QR Code') ?>">
                                                    <i class="ph-duotone ph-qr-code fs-2"></i>
                                                    <span><?= _('QR Code') ?></span>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="palette-item" draggable="true" data-type="rect" title="<?= _('Rectangle') ?>">
                                                    <i class="ph-duotone ph-square fs-2"></i>
                                                    <span><?= _('Forme') ?></span>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="palette-item" draggable="true" data-type="text" title="<?= _('Static Text') ?>">
                                                    <i class="ph-duotone ph-text-t fs-2"></i>
                                                    <span><?= _('Texte') ?></span>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="palette-item" draggable="true" data-type="date_naissance" title="<?= _('Birth Date') ?>">
                                                    <i class="ph-duotone ph-calendar fs-2"></i>
                                                    <span><?= _('Né(e) le') ?></span>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="palette-item" draggable="true" data-type="sexe" title="<?= _('Gender') ?>">
                                                    <i class="ph-duotone ph-gender-intersex fs-2"></i>
                                                    <span><?= _('Sexe') ?></span>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="palette-item" draggable="true" data-type="signature_directeur" title="<?= _('Director Signature') ?>">
                                                    <i class="ph-duotone ph-signature fs-2"></i>
                                                    <span><?= _('Signature') ?></span>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="palette-item" draggable="true" data-type="tampon_ecole" title="<?= _('School Stamp') ?>">
                                                    <i class="ph-duotone ph-seal fs-2"></i>
                                                    <span><?= _('Tampon') ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="element-properties" class="d-none">
                                        <hr>
                                        <h5 class="mb-3 mt-4"><?= _('Element Properties') ?></h5>
                                        <div class="mb-2">
                                            <label for="font_size" class="form-label"><?= _('Font Size (px)') ?></label>
                                            <input type="number" id="font_size" class="form-control form-control-sm" min="8">
                                        </div>
                                        <div class="mb-2">
                                            <label for="font_color" class="form-label"><?= _('Color') ?></label>
                                            <input type="color" id="font_color" class="form-control form-control-sm form-control-color">
                                        </div>
                                    </div>

                                </div>

                                <!-- Card Preview Area -->
                                <div class="col-lg-9">
                                    <h5 class="mb-3 text-center"><?= _('Card Preview') ?></h5>
                                    <div id="card-container" style="display: flex; justify-content: center; background: #f4f7fa; padding: 20px; border-radius: 8px; border: 1px solid #e0e0e0; overflow: auto;">
                                        <div style="box-shadow: 0 10px 30px rgba(0,0,0,0.1); line-height: 0;">
                                            <canvas id="card-canvas"></canvas>
                                        </div>
                                    </div>
                                    <div class="mt-3 text-muted text-center">
                                        <p class="mb-1"><strong><?= _('Conseil de Personnalisation :') ?></strong></p>
                                        <small><i class="ph-duotone ph-info"></i> <?= _('Utilisez les "Theme Colors" à gauche pour changer instantanément le style. Vous pouvez déplacer les éléments existants, mais nous recommandons de garder la structure professionnelle par défaut.') ?></small>
                                    </div>
                                </div>
                            </div>

                                    <div class="mt-4 d-flex justify-content-between">
                                        <button type="button" id="reset-template" class="btn btn-outline-danger">
                                            <i class="ph-duotone ph-arrow-counter-clockwise"></i> <?= _('Reset to Default') ?>
                                        </button>
                                <button type="submit" class="btn btn-primary">
                                            <i class="ph-duotone ph-floppy-disk"></i> <?= _('Save Template') ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Fabric.js Library -->
<script src="/assets/libs/fabric/fabric.min.js"></script>

<script>
$(function() {
    // Initialize Fabric Canvas - CR80 Standard: 85.6mm x 53.98mm
    // At 96 DPI: ~324px x 204px. Using a multiplier (e.g. 2x) for better editing.
    // 85.6mm is ~3.37 inches. 3.37 * 96 * 2 = 647px
    // 53.98mm is ~2.125 inches. 2.125 * 96 * 2 = 408px
    const canvas = new fabric.Canvas('card-canvas', {
        width: 647,
        height: 408,
        backgroundColor: '#ffffff',
        preserveObjectStacking: true
    });

    let cardModel = JSON.parse($('#layout_data_input').val() || '{"elements":[], "headers":{}}');
    let layoutElements = cardModel.elements || [];
    let headers = {
        left: `<?= str_replace(["\r\n", "\n", "\r"], "\\n", addslashes($params_lycee['header_primary'] ?? "RÉPUBLIQUE DU TCHAD\nUnité - Travail - Progrès\n**********\nMINISTÈRE DE L'ÉDUCATION NATIONALE")) ?>`,
        right: `<?= str_replace(["\r\n", "\n", "\r"], "\\n", addslashes($params_lycee['header_secondary'] ?? ($params_lycee['nom_lycee'] ?? 'NOM DU LYCÉE'))) ?>`
    };

    // Helper to create objects
    const creators = {
        logo: (options) => {
            const logoUrl = '<?= htmlspecialchars($params_lycee['logo'] ?? '') ?>' || '/assets/img/placeholder-photo.png';
            fabric.Image.fromURL(logoUrl, function(img) {
                if (!img) {
                    const rect = new fabric.Rect({
                        left: options.left !== undefined ? options.left : 273,
                        top: options.top !== undefined ? options.top : 10,
                        width: options.width || 100,
                        height: options.height || 100,
                        fill: '#eeeeee',
                        ...options
                    });
                    rect.set('elementType', 'logo');
                    canvas.add(rect);
                    canvas.setActiveObject(rect);
                    return;
                }
                img.set({
                    left: options.left !== undefined ? options.left : 273,
                    top: options.top !== undefined ? options.top : 10,
                    scaleX: (options.width || 100) / img.width,
                    scaleY: (options.height || 100) / img.height,
                    ...options
                });
                img.set('elementType', 'logo');
                canvas.add(img);
                canvas.setActiveObject(img);
            });
        },
        header_left: (options) => {
            const text = new fabric.IText(headers.left, {
                left: options.left !== undefined ? options.left : 10,
                top: options.top !== undefined ? options.top : 10,
                fontSize: options.fontSize || 10,
                textAlign: 'center',
                fontFamily: 'Arial',
                editable: false, // Administrative headers should be edited in settings
                ...options
            });
            text.set('elementType', 'header_left');
            canvas.add(text);
            canvas.setActiveObject(text);
        },
        serie: (options) => {
            const text = new fabric.IText('SÉRIE: Scientifique', {
                left: options.left !== undefined ? options.left : 200,
                top: options.top !== undefined ? options.top : 280,
                fontSize: options.fontSize || 14,
                fontFamily: 'Arial',
                ...options
            });
            text.set('elementType', 'serie');
            canvas.add(text);
            canvas.setActiveObject(text);
        },
        annee: (options) => {
            const text = new fabric.IText('ANNÉE: 2024-2025', {
                left: options.left !== undefined ? options.left : 200,
                top: options.top !== undefined ? options.top : 310,
                fontSize: options.fontSize || 14,
                fontFamily: 'Arial',
                ...options
            });
            text.set('elementType', 'annee');
            canvas.add(text);
            canvas.setActiveObject(text);
        },
        header_right: (options) => {
            const text = new fabric.IText(headers.right, {
                left: options.left !== undefined ? options.left : 400,
                top: options.top !== undefined ? options.top : 10,
                fontSize: options.fontSize || 10,
                textAlign: 'center',
                fontFamily: 'Arial',
                editable: false,
                ...options
            });
            text.set('elementType', 'header_right');
            canvas.add(text);
            canvas.setActiveObject(text);
        },
        photo: (options) => {
            fabric.Image.fromURL('/assets/img/placeholder-photo.png', function(img) {
                if (!img) {
                    // Create a rectangle as fallback
                    const rect = new fabric.Rect({
                        left: options.left !== undefined ? options.left : 20,
                        top: options.top !== undefined ? options.top : 20,
                        width: options.width || 100,
                        height: options.height || 120,
                        fill: '#cccccc',
                        ...options
                    });
                    rect.set('elementType', 'photo');
                    canvas.add(rect);
                    canvas.setActiveObject(rect);
                    return;
                }
                img.set({
                    left: options.left !== undefined ? options.left : 20,
                    top: options.top !== undefined ? options.top : 20,
                    scaleX: (options.width || 100) / img.width,
                    scaleY: (options.height || 120) / img.height,
                    ...options
                });
                img.set('elementType', 'photo');
                canvas.add(img);
                canvas.setActiveObject(img);
            });
        },
        qr_code: (options) => {
            fabric.Image.fromURL('/assets/img/placeholder-qr.png', function(img) {
                if (!img) {
                    const rect = new fabric.Rect({
                        left: options.left !== undefined ? options.left : 430,
                        top: options.top !== undefined ? options.top : 230,
                        width: options.width || 100,
                        height: options.height || 100,
                        fill: '#dddddd',
                        ...options
                    });
                    rect.set('elementType', 'qr_code');
                    canvas.add(rect);
                    canvas.setActiveObject(rect);
                    return;
                }
                img.set({
                    left: options.left !== undefined ? options.left : 430,
                    top: options.top !== undefined ? options.top : 230,
                    scaleX: (options.width || 100) / img.width,
                    scaleY: (options.height || 100) / img.height,
                    ...options
                });
                img.set('elementType', 'qr_code');
                canvas.add(img);
                canvas.setActiveObject(img);
            });
        },
        nom_complet: (options) => {
            const text = new fabric.IText('PRÉNOM NOM', {
                left: options.left !== undefined ? options.left : 200,
                top: options.top !== undefined ? options.top : 180,
                fontSize: options.fontSize || 24,
                fontWeight: 'bold',
                fontFamily: 'Arial',
                ...options
            });
            text.set('elementType', 'nom_complet');
            canvas.add(text);
            canvas.setActiveObject(text);
        },
        matricule: (options) => {
            const text = new fabric.IText('MATRICULE: 2024-X123', {
                left: options.left !== undefined ? options.left : 200,
                top: options.top !== undefined ? options.top : 220,
                fontSize: options.fontSize || 16,
                fontFamily: 'Arial',
                ...options
            });
            text.set('elementType', 'matricule');
            canvas.add(text);
            canvas.setActiveObject(text);
        },
        classe: (options) => {
            const text = new fabric.IText('CLASSE: Tle C', {
                left: options.left !== undefined ? options.left : 200,
                top: options.top !== undefined ? options.top : 250,
                fontSize: options.fontSize || 16,
                fontFamily: 'Arial',
                ...options
            });
            text.set('elementType', 'classe');
            canvas.add(text);
            canvas.setActiveObject(text);
        },
        text: (options) => {
            const text = new fabric.IText('Texte Statique', {
                left: options.left !== undefined ? options.left : 150,
                top: options.top !== undefined ? options.top : 50,
                fontSize: options.fontSize || 14,
                fontFamily: 'Arial',
                ...options
            });
            text.set('elementType', 'text');
            canvas.add(text);
            canvas.setActiveObject(text);
        },
        rect: (options) => {
            const rect = new fabric.Rect({
                left: options.left !== undefined ? options.left : 50,
                top: options.top !== undefined ? options.top : 50,
                fill: options.fill || '#e0e0e0',
                width: options.width || 100,
                height: options.height || 50,
                stroke: options.stroke || null,
                strokeWidth: options.strokeWidth || 0,
                ...options
            });
            rect.set('elementType', 'rect');
            canvas.add(rect);
            canvas.setActiveObject(rect);
        },
        circle: (options) => {
            const circle = new fabric.Circle({
                left: options.left !== undefined ? options.left : 50,
                top: options.top !== undefined ? options.top : 50,
                fill: options.fill || '#e0e0e0',
                radius: options.radius || 30,
                ...options
            });
            circle.set('elementType', 'circle');
            canvas.add(circle);
            canvas.setActiveObject(circle);
        },
        date_naissance: (options) => {
            const text = new fabric.IText('Né le: 01/01/2010', {
                left: options.left !== undefined ? options.left : 210,
                top: options.top !== undefined ? options.top : 350,
                fontSize: 14,
                fontFamily: 'Arial',
                ...options
            });
            text.set('elementType', 'date_naissance');
            canvas.add(text);
            canvas.setActiveObject(text);
        },
        sexe: (options) => {
            const text = new fabric.IText('Sexe: M', {
                left: options.left !== undefined ? options.left : 210,
                top: options.top !== undefined ? options.top : 370,
                fontSize: 14,
                fontFamily: 'Arial',
                ...options
            });
            text.set('elementType', 'sexe');
            canvas.add(text);
            canvas.setActiveObject(text);
        },
        signature_directeur: (options) => {
            const signatureUrl = '<?= htmlspecialchars($params_lycee['signature_directeur'] ?? '') ?>' || '/assets/img/placeholder-signature.png';
            fabric.Image.fromURL(signatureUrl, function(img) {
                if (!img) {
                    const rect = new fabric.Rect({
                        left: options.left !== undefined ? options.left : 450,
                        top: options.top !== undefined ? options.top : 330,
                        width: options.width || 120,
                        height: options.height || 50,
                        fill: '#eeeeee',
                        ...options
                    });
                    rect.set('elementType', 'signature_directeur');
                    canvas.add(rect);
                    return;
                }
                img.set({
                    left: options.left !== undefined ? options.left : 450,
                    top: options.top !== undefined ? options.top : 330,
                    scaleX: (options.width || 120) / img.width,
                    scaleY: (options.height || 50) / img.height,
                    ...options
                });
                img.set('elementType', 'signature_directeur');
                canvas.add(img);
                canvas.setActiveObject(img);
            });
        },
        tampon_ecole: (options) => {
            const tamponUrl = '<?= htmlspecialchars($params_lycee['tampon_ecole'] ?? '') ?>' || '/assets/img/placeholder-tampon.png';
            fabric.Image.fromURL(tamponUrl, function(img) {
                if (!img) {
                    const circle = new fabric.Circle({
                        left: options.left !== undefined ? options.left : 400,
                        top: options.top !== undefined ? options.top : 280,
                        radius: 40,
                        fill: '#eeeeee',
                        opacity: 0.5,
                        ...options
                    });
                    circle.set('elementType', 'tampon_ecole');
                    canvas.add(circle);
                    return;
                }
                img.set({
                    left: options.left !== undefined ? options.left : 400,
                    top: options.top !== undefined ? options.top : 280,
                    scaleX: (options.width || 80) / img.width,
                    scaleY: (options.height || 80) / img.height,
                    opacity: 0.6,
                    ...options
                });
                img.set('elementType', 'tampon_ecole');
                canvas.add(img);
                canvas.setActiveObject(img);
            });
        }
    };

    // Function to save the complete card model
    function saveLayout() {
        const elements = canvas.getObjects().map(obj => {
            const data = {
                id: obj.id || null,
                type: obj.elementType,
                left: Math.round(obj.left),
                top: Math.round(obj.top),
                width: Math.round(obj.width * obj.scaleX),
                height: Math.round(obj.height * obj.scaleY),
                scaleX: obj.scaleX,
                scaleY: obj.scaleY,
                angle: obj.angle,
                fill: obj.fill,
                stroke: obj.stroke || null,
                strokeWidth: obj.strokeWidth || 0,
                fontSize: obj.fontSize,
                text: obj.text,
                textAlign: obj.textAlign,
                fontWeight: obj.fontWeight,
                fontFamily: obj.fontFamily,
                radius: obj.radius,
                opacity: obj.opacity
            };

            // Remove undefined or null properties
            Object.keys(data).forEach(key => (data[key] == null) && delete data[key]);
            return data;
        });

        const fullModel = {
            elements: elements,
            headers: headers,
            version: '2.1' // Updated professional model version
        };
        $('#layout_data_input').val(JSON.stringify(fullModel));
    }

    // Drag and Drop implementation
    const paletteItems = document.querySelectorAll('.palette-item');
    paletteItems.forEach(item => {
        item.addEventListener('dragstart', (e) => {
            e.dataTransfer.setData('text/plain', item.dataset.type);
        });
    });

    const canvasWrapper = canvas.getElement().parentElement;

    canvasWrapper.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'copy';
    });

    canvasWrapper.addEventListener('drop', (e) => {
        e.preventDefault();
        const type = e.dataTransfer.getData('text/plain');

        // Accurate coordinate calculation using pointer
        const pointer = canvas.getPointer(e);
        const x = pointer.x;
        const y = pointer.y;

        if (creators[type]) {
            creators[type]({
                left: x,
                top: y
            });
            saveLayout();
        }
    });

    // Handle Selection & Properties
    canvas.on('selection:created', (e) => updatePropertiesPanel(e.selected[0]));
    canvas.on('selection:updated', (e) => updatePropertiesPanel(e.selected[0]));
    canvas.on('selection:cleared', () => $('#element-properties').addClass('d-none'));
    canvas.on('object:modified', saveLayout);

    function updatePropertiesPanel(obj) {
        $('#element-properties').removeClass('d-none');
        if (obj.type === 'i-text' || obj.type === 'text' || obj.elementType?.includes('text') || obj.elementType?.includes('header') || ['nom_complet', 'matricule', 'classe'].includes(obj.elementType)) {
            $('#font_size').closest('.mb-2').show();
            $('#font_size').val(obj.fontSize);
            $('#font_color').val(obj.fill);
        } else {
            $('#font_size').closest('.mb-2').hide();
            $('#font_color').val(obj.fill);
        }
    }

    $('#font_size').on('input', function() {
        const obj = canvas.getActiveObject();
        if (obj && (obj.type === 'i-text' || obj.type === 'text')) {
            obj.set('fontSize', parseInt($(this).val()));
            canvas.renderAll();
            saveLayout();
        }
    });

    $('#font_color').on('input', function() {
        const obj = canvas.getActiveObject();
        if (obj) {
            obj.set('fill', $(this).val());
            canvas.renderAll();
            saveLayout();
        }
    });

    // Background Image
    $('#background_image').on('change', function(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                fabric.Image.fromURL(e.target.result, function(img) {
                    canvas.setBackgroundImage(img, canvas.renderAll.bind(canvas), {
                        scaleX: canvas.width / img.width,
                        scaleY: canvas.height / img.height
                    });
                });
            }
            reader.readAsDataURL(file);
        }
    });

    // Function to apply default professional layout
    function applyDefaultLayout() {
        canvas.clear();
        canvas.setBackgroundColor('#ffffff', canvas.renderAll.bind(canvas));

        const isMultilingual = <?= (!empty($params_generaux['multilingue_actif']) && ($params_generaux['nb_langue'] ?? 1) > 1) ? 'true' : 'false' ?>;

        const defaultModel = [
            // High-end background elements
            { type: 'rect', left: 0, top: 0, width: 647, height: 408, fill: '#ffffff', id: 'bg_main' },
            { type: 'text', text: 'OFFICIEL '.repeat(20), left: -100, top: 200, fontSize: 40, fill: '#f1f1f1', opacity: 0.2, angle: -30, selectable: false, id: 'watermark' },
            { type: 'rect', left: 0, top: 0, width: 647, height: 110, fill: '#f8f9fa', id: 'header_bg' },
            { type: 'rect', left: 0, top: 107, width: 647, height: 6, fill: '#0056b3', id: 'header_accent' },

            // Header Content
            { type: 'logo', left: 283, top: 15, width: 80, height: 80 },

            // Institutional Header (Left as in image)
            { type: 'header_left', text: headers.left, left: 20, top: 25, width: 200, fontSize: 9, textAlign: 'center', fontWeight: 'bold', fill: '#333' },

            // School Header (Right as in image)
            { type: 'header_right', text: headers.right, left: 427, top: 25, width: 200, fontSize: 9, textAlign: 'center', fontWeight: 'bold', fill: '#333' }
        ];

        const moreElements = [
            // Photo Area with Frame
            { type: 'rect', left: 35, top: 145, width: 150, height: 190, fill: '#fff', stroke: '#0056b3', strokeWidth: 2, id: 'photo_frame' },
            { type: 'photo', left: 40, top: 150, width: 140, height: 180 },

            // Student Info with labels (Matching image spacing)
            { type: 'text', text: 'NOM ET PRÉNOMS', left: 210, top: 145, fontSize: 11, fontWeight: 'bold', fill: '#0056b3' },
            { type: 'nom_complet', left: 210, top: 165, fontSize: 32, fontWeight: 'bold', fill: '#222' },

            { type: 'text', text: 'MATRICULE', left: 210, top: 215, fontSize: 11, fontWeight: 'bold', fill: '#0056b3' },
            { type: 'matricule', left: 210, top: 235, fontSize: 22, fontWeight: 'bold', fill: '#444' },

            { type: 'text', text: 'CLASSE / SÉRIE', left: 210, top: 275, fontSize: 11, fontWeight: 'bold', fill: '#0056b3' },
            { type: 'classe', left: 210, top: 295, fontSize: 20, fontWeight: 'bold', fill: '#444' },

            { type: 'text', text: 'ANNÉE SCOLAIRE', left: 210, top: 335, fontSize: 11, fontWeight: 'bold', fill: '#0056b3' },
            { type: 'annee', left: 210, top: 355, fontSize: 18, fontWeight: 'bold', fill: '#444' },

            // Signature & Stamp
            { type: 'signature_directeur', left: 480, top: 340, width: 100, height: 40 },
            { type: 'tampon_ecole', left: 450, top: 310, width: 70, height: 70 },
            { type: 'text', text: 'LE DIRECTEUR', left: 480, top: 325, fontSize: 9, fontWeight: 'bold', fill: '#333', id: 'dir_label' },

            // Security QR Code Area
            { type: 'qr_code', left: 510, top: 145, width: 100, height: 100 },
            { type: 'text', text: 'SECURE QR CODE', left: 510, top: 130, fontSize: 8, fill: '#999', id: 'qr_label' },

            // Professional Footer
            { type: 'rect', left: 0, top: 383, width: 647, height: 25, fill: '#0056b3', id: 'footer_bar' },
            { type: 'text', left: 0, top: 388, width: 647, fontSize: 10, fill: '#ffffff', text: 'CARTE D\'IDENTITÉ SCOLAIRE - DOCUMENT OFFICIEL', textAlign: 'center' }
        ];

        const finalModel = [...defaultModel, ...moreElements];

        finalModel.forEach(el => {
            if (creators[el.type]) creators[el.type](el);
        });

        // Ensure background elements stay at the bottom and are locked
        canvas.getObjects().forEach(obj => {
            if (obj.id && (obj.id.includes('bg') || obj.id.includes('accent') || obj.id.includes('watermark'))) {
                obj.set('selectable', false);
                obj.set('evented', false);
                canvas.sendToBack(obj);
            }
        });
        setTimeout(saveLayout, 1000);
    }

    $('#reset-template').on('click', function() {
        if (confirm("<?= _('Are you sure you want to reset the template to the default professional model? This will erase your current changes.') ?>")) {
            applyDefaultLayout();
        }
    });

    $('.theme-preset').on('click', function() {
        const primaryColor = $(this).data('primary');
        const headerBg = $(this).data('header');

        canvas.getObjects().forEach(obj => {
            // Apply primary color to accent elements and labels
            if (obj.id === 'header_accent' || obj.id === 'footer_bar') {
                obj.set('fill', primaryColor);
            }
            if (obj.id === 'photo_frame') {
                obj.set('stroke', primaryColor);
            }
            if (obj.id === 'header_bg') {
                obj.set('fill', headerBg);
            }

            // Text elements that act as labels (NOM ET PRÉNOMS, etc)
            if (obj.type === 'i-text' && ['NOM ET PRÉNOMS', 'MATRICULE', 'CLASSE / SÉRIE', 'ANNÉE SCOLAIRE'].includes(obj.text)) {
                obj.set('fill', primaryColor);
            }

            // Name highlighted with primary color
            if (obj.elementType === 'nom_complet') {
                obj.set('fill', primaryColor);
            }
        });

        canvas.renderAll();
        saveLayout();
    });

    // Initial Load
    function loadInitialLayout() {
        // Background
        const initialBackground = $('input[name="current_background"]').val();
        if (initialBackground) {
            fabric.Image.fromURL(initialBackground, function(img) {
                canvas.setBackgroundImage(img, canvas.renderAll.bind(canvas), {
                    scaleX: canvas.width / img.width,
                    scaleY: canvas.height / img.height
                });
            });
        }

        // Force transition to new model if version is old or missing
        const currentVersion = '2.1';
        const isCompatibleModel = cardModel.version === currentVersion;

        if (layoutElements.length === 0 || !isCompatibleModel) {
            applyDefaultLayout();
        } else {
            // Elements saved in DB
            layoutElements.forEach(el => {
                if (creators[el.type]) {
                    creators[el.type](el);
                }
            });
            // Re-apply background locks
            setTimeout(() => {
                canvas.getObjects().forEach(obj => {
                    if (obj.id && (obj.id.includes('bg') || obj.id.includes('accent') || obj.id.includes('watermark'))) {
                        obj.set('selectable', false);
                        obj.set('evented', false);
                        canvas.sendToBack(obj);
                    }
                });
                canvas.renderAll();
            }, 500);
        }
    }

    loadInitialLayout();

    // Double click to change image for Photo/Logo/QR
    canvas.on('mouse:dblclick', function(options) {
        if (options.target && (options.target.elementType === 'photo' || options.target.elementType === 'logo' || options.target.elementType === 'qr_code')) {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = 'image/*';
            input.onchange = function(e) {
                const file = e.target.files[0];
                const reader = new FileReader();
                reader.onload = function(f) {
                    const data = f.target.result;
                    fabric.Image.fromURL(data, function(img) {
                        const target = options.target;
                        target.setSrc(data, function() {
                            canvas.renderAll();
                            saveLayout();
                        });
                    });
                };
                reader.readAsDataURL(file);
            };
            input.click();
        }
    });

    // Delete object with Delete key
    window.addEventListener('keydown', (e) => {
        if (e.key === 'Delete' || e.key === 'Backspace') {
            if (canvas.getActiveObject() && !canvas.getActiveObject().isEditing) {
                canvas.remove(canvas.getActiveObject());
                saveLayout();
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
