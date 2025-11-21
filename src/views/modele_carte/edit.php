<!-- Force file recognition -->
<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<style>
    #card-preview {
        width: 539px; /* ISO ID-1, 85.6mm * 6.35 px/mm (approx) */
        height: 339px; /* 53.98mm * 6.35 px/mm */
        border: 2px dashed #ccc;
        position: relative;
        background-color: #f0f0f0;
        overflow: hidden;
    }
    .draggable {
        position: absolute;
        cursor: move;
        border: 1px solid #a2a2a2;
        padding: 5px;
        background-color: rgba(255, 255, 255, 0.8);
    }
    .draggable img {
        width: 100%;
        height: 100%;
        object-fit: cover;
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
                        <form action="/modele-carte/edit" method="POST">
                            <input type="hidden" name="nom_modele" value="Default Card Model">
                            <input type="hidden" name="layout_data" id="layout_data_input" value="<?= htmlspecialchars($modele['layout_data'] ?? '{}') ?>">

                            <div class="row">
                                <!-- Card Preview Area -->
                                <div class="col-md-9">
                                    <h5 class="mb-3"><?= _('Preview') ?></h5>
                                    <div id="card-preview">
                                        <!-- Draggable elements will be added here by JS -->
                                    </div>
                                </div>

                                <!-- Available Elements -->
                                <div class="col-md-3">
                                    <h5 class="mb-3"><?= _('Available Elements') ?></h5>
                                    <div id="element-list" class="d-grid gap-2">
                                        <button type="button" data-element="photo" class="btn btn-light"><?= _('Photo') ?></button>
                                        <button type="button" data-element="nom_complet" class="btn btn-light"><?= _('Full Name') ?></button>
                                        <button type="button" data-element="matricule" class="btn btn-light"><?= _('ID Number') ?></button>
                                        <button type="button" data-element="classe" class="btn btn-light"><?= _('Class') ?></button>
                                        <button type="button" data-element="qr_code" class="btn btn-light"><?= _('QR Code') ?></button>
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
    let layoutData = JSON.parse($('#layout_data_input').val() || '{}');

    // Function to save the current layout to the hidden input
    function saveLayout() {
        const currentLayout = {};
        $('#card-preview .draggable').each(function() {
            const element = $(this);
            currentLayout[element.attr('id')] = {
                left: element.css('left'),
                top: element.css('top'),
                width: element.css('width'),
                height: element.css('height')
            };
        });
        $('#layout_data_input').val(JSON.stringify(currentLayout));
    }

    // Function to create a draggable element
    function createElement(id, placeholder, initialData) {
        const element = $(`<div class="draggable" id="${id}">${placeholder}</div>`);
        if (id === 'photo') {
            element.html('<img src="/assets/img/placeholder-photo.png" alt="photo">');
        }
        if (id === 'qr_code') {
             element.html('<img src="/assets/img/placeholder-qr.png" alt="qr">');
        }

        element.css({
            left: initialData.left || '10px',
            top: initialData.top || '10px',
            width: initialData.width || '100px',
            height: initialData.height || (id === 'photo' || id === 'qr_code' ? '100px' : 'auto')
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

    // Load existing layout
    for (const id in layoutData) {
        const placeholder = $(`button[data-element="${id}"]`).text() || id;
        createElement(id, placeholder, layoutData[id]);
    }

    // Add new elements from the list
    $('#element-list button').on('click', function() {
        const id = $(this).data('element');
        if ($('#' + id).length === 0) { // Prevent adding duplicates
            createElement(id, $(this).text(), {});
            saveLayout();
        }
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
