<!-- Force file recognition -->
<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<!-- Link to the new stylesheet -->
<link rel="stylesheet" href="/assets/css/card-editor.css">

<style>
    /* Keep the card dimensions but move other styles to the CSS file */
    #card-preview {
        /* Standard credit card ratio: 85.6 / 54 = ~1.585 */
        width: 550px;
        height: 347px; /* 550 / 1.585 = ~347 */
        position: relative;
        overflow: hidden;
        margin: auto; /* Center the card */
    }
</style>

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
                                <!-- Card Preview Area -->
                                <div class="col-lg-9">
                                    <h5 class="mb-3 text-center"><?= _('Card Preview') ?></h5>
                                    <div id="card-preview">
                                        <!-- Static Header Elements -->
                                        <div class="card-header-section">
                                            <div class="header-left" id="header_left" contenteditable="true">
                                                <!-- Content will be loaded by JS -->
                                            </div>
                                            <div class="header-center">
                                                <img src="<?= htmlspecialchars($params_lycee['logo'] ?? '/assets/img/logo-placeholder.png') ?>" alt="Logo">
                                            </div>
                                            <div class="header-right" id="header_right" contenteditable="true">
                                                <!-- Content will be loaded by JS -->
                                            </div>
                                        </div>
                                        <!-- Draggable elements will be added here by JS -->
                                    </div>
                                </div>

                                <!-- Available Elements & Properties -->
                                <div class="col-lg-3">
                                    <h5 class="mb-3"><?= _('Palette') ?></h5>
                                    <div id="element-list" class="list-group">
                                        <button type="button" data-element="photo" class="list-group-item list-group-item-action"><i class="ti ti-user-circle me-2"></i><?= _('Photo') ?></button>
                                        <button type="button" data-element="nom_complet" class="list-group-item list-group-item-action"><i class="ti ti-text-caption me-2"></i><?= _('Full Name') ?></button>
                                        <button type="button" data-element="matricule" class="list-group-item list-group-item-action"><i class="ti ti-hash me-2"></i><?= _('ID Number') ?></button>
                                        <button type="button" data-element="classe" class="list-group-item list-group-item-action"><i class="ti ti-school me-2"></i><?= _('Class') ?></button>
                                        <button type="button" data-element="qr_code" class="list-group-item list-group-item-action"><i class="ti ti-qrcode me-2"></i><?= _('QR Code') ?></button>
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

<!-- We need jQuery UI for draggable and resizable -->
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">

<script>
$(function() {
    let cardModel = JSON.parse($('#layout_data_input').val() || '{"elements":[], "headers":{}}');
    let layoutElements = cardModel.elements || [];
    let headers = cardModel.headers || {
        left: `MINISTERE DE L'EDUCATION\nREGION...\nDEPARTEMENT...`,
        right: `<?= htmlspecialchars($params_lycee['nom_lycee'] ?? 'NOM DU LYCEE') ?>\n<?= htmlspecialchars($params_lycee['devise'] ?? 'DEVISE') ?>\n<?= htmlspecialchars($annee_academique['nom_annee'] ?? 'ANNEE ACADEMIQUE') ?>`
    };
    let selectedElement = null;

    // Function to save the complete card model (elements + headers)
    function saveLayout() {
        headers.left = $('#header_left').html().replace(/<br\s*[\/]?>/gi, "\n").trim();
        headers.right = $('#header_right').html().replace(/<br\s*[\/]?>/gi, "\n").trim();

        const newElementLayout = [];
        $('#card-preview .draggable').each(function() {
            const element = $(this);
            const position = element.position();
            newElementLayout.push({
                id: element.attr('id'),
                type: element.data('element-type'),
                content: element.data('content-placeholder'),
                x: Math.round(position.left),
                y: Math.round(position.top),
                width: Math.round(element.outerWidth()),
                height: Math.round(element.outerHeight()),
                fontSize: element.css('font-size'),
                color: element.css('color')
            });
        });

        const fullModel = {
            elements: newElementLayout,
            headers: headers
        };
        $('#layout_data_input').val(JSON.stringify(fullModel));
        console.log("Layout Saved:", JSON.stringify(fullModel, null, 2));
    }

    // Function to create a draggable element on the canvas
    function createElement(data) {
        const { id, type, content, x, y, width, height, fontSize, color } = data;

        let elementHtml = '';
        const iconHtml = {
            nom_complet: '<i class="ti ti-text-caption draggable-placeholder-icon"></i>',
            matricule: '<i class="ti ti-hash draggable-placeholder-icon"></i>',
            classe: '<i class="ti ti-school draggable-placeholder-icon"></i>'
        };

        switch(type) {
            case 'photo':
                elementHtml = `<img src="/assets/img/placeholder-photo.png" alt="photo" style="width:100%; height:100%; object-fit: cover;">`;
                break;
            case 'qr_code':
                elementHtml = `<img src="/assets/img/placeholder-qr.png" alt="qr" style="width:100%; height:100%; object-fit: cover;">`;
                break;
            default:
                // For text elements, add an icon and the placeholder content
                elementHtml = `${iconHtml[type] || ''}<span>${content}</span>`;
                break;
        }

        const element = $(`<div class="draggable" id="${id}" data-element-type="${type}">${elementHtml}</div>`);

        // Store metadata on the element itself
        element.data('element-type', type);
        element.data('content-placeholder', content);

        element.css({
            left: `${x}px`,
            top: `${y}px`,
            width: `${width}px`,
            height: `${height}px`,
            fontSize: fontSize || '12px',
            color: color || '#000000'
        });

        element.on('click', function(e) {
            e.stopPropagation();
            selectElement($(this));
        });

        element.draggable({
            containment: '#card-preview',
            stop: saveLayout
        }).resizable({
            containment: '#card-preview',
            stop: saveLayout
        });

        $('#card-preview').append(element);
    }

    // Function to handle element selection
    function selectElement(element) {
        selectedElement = element;
        $('.draggable').css('border-color', '#a9a9a9');
        element.css('border-color', '#007bff'); // Highlight selected

        const type = element.data('element-type');
        if (type !== 'photo' && type !== 'qr_code') {
            $('#element-properties').removeClass('d-none');
            $('#font_size').val(parseInt(element.css('font-size')));
            // Need to convert rgb color to hex for the color input
            const rgb = element.css('color').match(/\d+/g);
            const hex = rgb ? '#' + (+rgb[0]).toString(16).padStart(2, '0') + (+rgb[1]).toString(16).padStart(2, '0') + (+rgb[2]).toString(16).padStart(2, '0') : '#000000';
            $('#font_color').val(hex);
        } else {
            $('#element-properties').addClass('d-none');
        }
    }

    function deselectAll() {
        if (selectedElement) {
            selectedElement.css('border-color', '#a9a9a9');
        }
        selectedElement = null;
        $('#element-properties').addClass('d-none');
    }

    // Initial load of existing layout elements
    function loadInitialLayout() {
        // Load background
        const initialBackground = $('input[name="current_background"]').val();
        if (initialBackground) {
            $('#card-preview').css('background-image', `url(${initialBackground})`);
        }
        // Load headers
        $('#header_left').html(headers.left.replace(/\n/g, '<br>'));
        $('#header_right').html(headers.right.replace(/\n/g, '<br>'));

        // Load draggable elements
        layoutElements.forEach(data => createElement(data));
    }

    loadInitialLayout();

    // Event Listeners
    $('#header_left, #header_right').on('blur', saveLayout);
    $('#card-preview').on('click', deselectAll);

    $('#font_size, #font_color').on('input', function() {
        if (selectedElement) {
            const prop = $(this).attr('id') === 'font_size' ? 'fontSize' : 'color';
            const value = $(this).attr('id') === 'font_size' ? `${$(this).val()}px` : $(this).val();
            selectedElement.css(prop, value);
            saveLayout(); // Save on property change
        }
    });

    $('#background_image').on('change', function(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#card-preview').css('background-image', `url(${e.target.result})`);
            }
            reader.readAsDataURL(file);
        }
    });


    // Add new elements from the palette
    $('#element-list button').on('click', function() {
        const type = $(this).data('element');
        const id = `${type}_${new Date().getTime()}`; // Create a unique ID to allow multiple elements of the same type

        if (type === 'photo' || type === 'qr_code') {
             if ($(`#card-preview .draggable[data-element-type="${type}"]`).length > 0) {
                alert(`L'élément "${$(this).text()}" ne peut être ajouté qu'une seule fois.`);
                return; // Prevent adding more than one photo or qr code
            }
        }


        const newElementData = {
            id: id,
            type: type,
            content: $(this).text(),
            x: 20,
            y: 20,
            width: 150,
            height: (type === 'photo' || type === 'qr_code') ? 150 : 40
        };

        createElement(newElementData);
        saveLayout(); // Immediately save the new element's position
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
