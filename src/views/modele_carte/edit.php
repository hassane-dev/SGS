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
                                    <h5 class="mb-3"><?= _('Palette') ?></h5>
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
                                                <span><?= _('Name') ?></span>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="palette-item" draggable="true" data-type="matricule" title="<?= _('ID Number') ?>">
                                                <i class="ph-duotone ph-identification-card fs-2"></i>
                                                <span><?= _('ID') ?></span>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="palette-item" draggable="true" data-type="classe" title="<?= _('Class') ?>">
                                                <i class="ph-duotone ph-graduation-cap fs-2"></i>
                                                <span><?= _('Class') ?></span>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="palette-item" draggable="true" data-type="serie" title="<?= _('Academic Series') ?>">
                                                <i class="ph-duotone ph-books fs-2"></i>
                                                <span><?= _('Série') ?></span>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="palette-item" draggable="true" data-type="annee" title="<?= _('Academic Year') ?>">
                                                <i class="ph-duotone ph-calendar fs-2"></i>
                                                <span><?= _('Année') ?></span>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="palette-item" draggable="true" data-type="qr_code" title="<?= _('QR Code') ?>">
                                                <i class="ph-duotone ph-qr-code fs-2"></i>
                                                <span><?= _('QR') ?></span>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="palette-item" draggable="true" data-type="rect" title="<?= _('Rectangle') ?>">
                                                <i class="ph-duotone ph-square fs-2"></i>
                                                <span><?= _('Rect') ?></span>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="palette-item" draggable="true" data-type="circle" title="<?= _('Circle') ?>">
                                                <i class="ph-duotone ph-circle fs-2"></i>
                                                <span><?= _('Circle') ?></span>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="palette-item" draggable="true" data-type="text" title="<?= _('Static Text') ?>">
                                                <i class="ph-duotone ph-text-t fs-2"></i>
                                                <span><?= _('Text') ?></span>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="palette-item" draggable="true" data-type="header_left" title="<?= _('Header Left') ?>">
                                                <i class="ph-duotone ph-layout fs-2"></i>
                                                <span><?= _('H. Left') ?></span>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="palette-item" draggable="true" data-type="header_right" title="<?= _('Header Right') ?>">
                                                <i class="ph-duotone ph-layout fs-2"></i>
                                                <span><?= _('H. Right') ?></span>
                                            </div>
                                        </div>
                                    </div>

                                    <hr>

                                    <h5 class="mb-3 mt-4"><?= _('Card Properties') ?></h5>
                                    <div class="mb-3">
                                        <label for="background_image" class="form-label"><?= _('Background Image') ?></label>
                                        <input class="form-control" type="file" id="background_image" name="background_image" accept="image/*">
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
                                        <small><i class="ph-duotone ph-info"></i> <?= _('Drag items from the palette to the card. Use Delete/Backspace to remove selected items.') ?></small>
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
    let headers = cardModel.headers || {
        left: `<?= str_replace(["\r\n", "\n", "\r"], "\\n", addslashes($params_lycee['header_primary'] ?? "REPUBLIQUE DU TCHAD\nUnité - Travail - Progrès\n**********\nMINISTERE DE L'EDUCATION")) ?>`,
        right: `<?= str_replace(["\r\n", "\n", "\r"], "\\n", addslashes($params_lycee['header_secondary'] ?? ($params_lycee['nom_lycee'] ?? 'NOM DU LYCEE'))) ?>`
    };

    // Helper to create objects
    const creators = {
        logo: (options) => {
            const logoUrl = '<?= htmlspecialchars($params_lycee['logo'] ?? '') ?>' || '/assets/img/placeholder-photo.png';
            fabric.Image.fromURL(logoUrl, function(img) {
                if (!img) {
                    const rect = new fabric.Rect({
                        left: options.left || 273,
                        top: options.top || 10,
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
                    left: options.left || 273,
                    top: options.top || 10,
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
            const text = new fabric.IText(options.text || headers.left, {
                left: options.left || 10,
                top: options.top || 10,
                fontSize: options.fontSize || 10,
                textAlign: 'center',
                fontFamily: 'Arial',
                ...options
            });
            text.set('elementType', 'header_left');
            canvas.add(text);
            canvas.setActiveObject(text);
        },
        serie: (options) => {
            const text = new fabric.IText('SÉRIE: Scientifique', {
                left: options.left || 200,
                top: options.top || 280,
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
                left: options.left || 200,
                top: options.top || 310,
                fontSize: options.fontSize || 14,
                fontFamily: 'Arial',
                ...options
            });
            text.set('elementType', 'annee');
            canvas.add(text);
            canvas.setActiveObject(text);
        },
        header_right: (options) => {
            const text = new fabric.IText(options.text || headers.right, {
                left: options.left || 400,
                top: options.top || 10,
                fontSize: options.fontSize || 10,
                textAlign: 'center',
                fontFamily: 'Arial',
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
                        left: options.left || 20,
                        top: options.top || 20,
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
                    left: options.left || 20,
                    top: options.top || 20,
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
                        left: options.left || 430,
                        top: options.top || 230,
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
                    left: options.left || 430,
                    top: options.top || 230,
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
                left: options.left || 200,
                top: options.top || 180,
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
                left: options.left || 200,
                top: options.top || 220,
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
                left: options.left || 200,
                top: options.top || 250,
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
                left: options.left || 150,
                top: options.top || 50,
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
                left: options.left || 50,
                top: options.top || 50,
                fill: options.fill || '#e0e0e0',
                width: options.width || 100,
                height: options.height || 50,
                ...options
            });
            rect.set('elementType', 'rect');
            canvas.add(rect);
            canvas.setActiveObject(rect);
        },
        circle: (options) => {
            const circle = new fabric.Circle({
                left: options.left || 50,
                top: options.top || 50,
                fill: options.fill || '#e0e0e0',
                radius: options.radius || 30,
                ...options
            });
            circle.set('elementType', 'circle');
            canvas.add(circle);
            canvas.setActiveObject(circle);
        }
    };

    // Function to save the complete card model
    function saveLayout() {
        const elements = canvas.getObjects().map(obj => {
            const state = obj.toJSON(['elementType', 'id']);

            const data = {
                type: obj.elementType,
                left: Math.round(obj.left),
                top: Math.round(obj.top),
                width: Math.round(obj.width * obj.scaleX),
                height: Math.round(obj.height * obj.scaleY),
                scaleX: obj.scaleX,
                scaleY: obj.scaleY,
                angle: obj.angle,
                fill: obj.fill,
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
            headers: headers
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

        const defaultModel = [
            { type: 'rect', left: 0, top: 0, width: 647, height: 100, fill: '#f8f9fa' }, // Header Background
            { type: 'header_left', left: 10, top: 15, fontSize: 10, textAlign: 'center' },
            { type: 'header_right', left: 400, top: 15, fontSize: 10, textAlign: 'center' },
            { type: 'logo', left: 283, top: 10, width: 80, height: 80 },
            { type: 'rect', left: 0, top: 97, width: 647, height: 3, fill: '#003366' }, // Separator line
            { type: 'photo', left: 30, top: 130, width: 140, height: 180 },
            { type: 'nom_complet', left: 200, top: 140, fontSize: 28, fontWeight: 'bold', fill: '#003366' },
            { type: 'matricule', left: 200, top: 190, fontSize: 18, fill: '#333333' },
            { type: 'classe', left: 200, top: 230, fontSize: 18, fill: '#333333' },
            { type: 'serie', left: 200, top: 270, fontSize: 16, fill: '#666666' },
            { type: 'annee', left: 200, top: 310, fontSize: 16, fill: '#666666' },
            { type: 'qr_code', left: 480, top: 260, width: 120, height: 120 },
            { type: 'rect', left: 0, top: 378, width: 647, height: 30, fill: '#003366' }, // Footer Bar
            { type: 'text', left: 10, top: 385, fontSize: 10, fill: '#ffffff', text: 'CARTE D\'IDENTITÉ SCOLAIRE OFFICIELLE' }
        ];

        defaultModel.forEach(el => {
            if (creators[el.type]) creators[el.type](el);
        });
        setTimeout(saveLayout, 1000);
    }

    $('#reset-template').on('click', function() {
        if (confirm("<?= _('Are you sure you want to reset the template to the default professional model? This will erase your current changes.') ?>")) {
            applyDefaultLayout();
        }
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

        // If no elements saved, load a professional default model
        if (layoutElements.length === 0) {
            applyDefaultLayout();
        } else {
            // Elements saved in DB
            layoutElements.forEach(el => {
                if (creators[el.type]) {
                    creators[el.type](el);
                }
            });
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
