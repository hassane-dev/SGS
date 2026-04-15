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
                                                <i class="ti ti-user-circle fs-2"></i>
                                                <span><?= _('Photo') ?></span>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="palette-item" draggable="true" data-type="logo" title="<?= _('School Logo') ?>">
                                                <i class="ti ti-building-community fs-2"></i>
                                                <span><?= _('Logo') ?></span>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="palette-item" draggable="true" data-type="nom_complet" title="<?= _('Full Name') ?>">
                                                <i class="ti ti-text-caption fs-2"></i>
                                                <span><?= _('Name') ?></span>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="palette-item" draggable="true" data-type="matricule" title="<?= _('ID Number') ?>">
                                                <i class="ti ti-hash fs-2"></i>
                                                <span><?= _('ID') ?></span>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="palette-item" draggable="true" data-type="classe" title="<?= _('Class') ?>">
                                                <i class="ti ti-school fs-2"></i>
                                                <span><?= _('Class') ?></span>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="palette-item" draggable="true" data-type="qr_code" title="<?= _('QR Code') ?>">
                                                <i class="ti ti-qrcode fs-2"></i>
                                                <span><?= _('QR') ?></span>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="palette-item" draggable="true" data-type="rect" title="<?= _('Rectangle') ?>">
                                                <i class="ti ti-square fs-2"></i>
                                                <span><?= _('Rect') ?></span>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="palette-item" draggable="true" data-type="circle" title="<?= _('Circle') ?>">
                                                <i class="ti ti-circle fs-2"></i>
                                                <span><?= _('Circle') ?></span>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="palette-item" draggable="true" data-type="text" title="<?= _('Static Text') ?>">
                                                <i class="ti ti-typography fs-2"></i>
                                                <span><?= _('Text') ?></span>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="palette-item" draggable="true" data-type="header_left" title="<?= _('Header Left') ?>">
                                                <i class="ti ti-layout-align-left fs-2"></i>
                                                <span><?= _('H. Left') ?></span>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="palette-item" draggable="true" data-type="header_right" title="<?= _('Header Right') ?>">
                                                <i class="ti ti-layout-align-right fs-2"></i>
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
                                        <small><i class="ti ti-info-circle"></i> <?= _('Drag items from the palette to the card. Use Delete/Backspace to remove selected items.') ?></small>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 text-end">
                                <button type="submit" class="btn btn-primary">
                                    <?= _('Save Template') ?>
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
    // Initialize Fabric Canvas
    const canvas = new fabric.Canvas('card-canvas', {
        width: 550,
        height: 347,
        backgroundColor: '#ffffff',
        preserveObjectStacking: true
    });

    let cardModel = JSON.parse($('#layout_data_input').val() || '{"elements":[], "headers":{}}');
    let layoutElements = cardModel.elements || [];
    let headers = cardModel.headers || {
        left: `MINISTERE DE L'EDUCATION\nREGION...\nDEPARTEMENT...`,
        right: `<?= htmlspecialchars($params_lycee['nom_lycee'] ?? 'NOM DU LYCEE') ?>\n<?= htmlspecialchars($params_lycee['devise'] ?? 'DEVISE') ?>\n<?= htmlspecialchars($annee_academique['nom_annee'] ?? 'ANNEE ACADEMIQUE') ?>`
    };

    // Helper to create objects
    const creators = {
        logo: (options) => {
            fabric.Image.fromURL('<?= htmlspecialchars($params_lycee['logo'] ?? '/assets/img/logo-placeholder.png') ?>', function(img) {
                img.set({
                    left: options.left || 225,
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
                fontSize: options.fontSize || 8,
                textAlign: 'center',
                fontFamily: 'Arial',
                ...options
            });
            text.set('elementType', 'header_left');
            canvas.add(text);
            canvas.setActiveObject(text);
        },
        header_right: (options) => {
            const text = new fabric.IText(options.text || headers.right, {
                left: options.left || 300,
                top: options.top || 10,
                fontSize: options.fontSize || 8,
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
            const text = new fabric.IText('NOM COMPLET', {
                left: options.left || 150,
                top: options.top || 150,
                fontSize: options.fontSize || 20,
                fontWeight: 'bold',
                fontFamily: 'Arial',
                ...options
            });
            text.set('elementType', 'nom_complet');
            canvas.add(text);
            canvas.setActiveObject(text);
        },
        matricule: (options) => {
            const text = new fabric.IText('MATRICULE: 000000', {
                left: options.left || 150,
                top: options.top || 180,
                fontSize: options.fontSize || 16,
                fontFamily: 'Arial',
                ...options
            });
            text.set('elementType', 'matricule');
            canvas.add(text);
            canvas.setActiveObject(text);
        },
        classe: (options) => {
            const text = new fabric.IText('CLASSE: 6ème', {
                left: options.left || 150,
                top: options.top || 210,
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
            e.dataTransfer.setData('type', item.dataset.type);
        });
    });

    const canvasWrapper = canvas.getElement().parentElement;

    canvasWrapper.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'copy';
    });

    canvasWrapper.addEventListener('drop', (e) => {
        e.preventDefault();
        const type = e.dataTransfer.getData('type');

        // Accurate coordinate calculation
        const rect = canvas.getElement().getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;

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

        // Elements
        layoutElements.forEach(el => {
            if (creators[el.type]) {
                creators[el.type](el);
            }
        });
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
