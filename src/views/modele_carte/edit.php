<!-- Force file recognition -->
<?php require_once __DIR__ . '/../layouts/header.php'; ?>

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

<div class="container mx-auto">
    <h2 class="text-2xl font-bold mb-6"><?= _('Card Template Editor') ?></h2>

    <?php if (isset($_GET['success'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?= _('Template saved successfully!') ?></span>
        </div>
    <?php endif; ?>

    <form action="/modele-carte/edit" method="POST">
        <input type="hidden" name="nom_modele" value="Default Card Model">
        <input type="hidden" name="layout_data" id="layout_data_input" value="<?= htmlspecialchars($modele['layout_data'] ?? '{}') ?>">

        <div class="flex gap-8">
            <!-- Card Preview Area -->
            <div class="flex-grow">
                <h3 class="text-lg font-semibold mb-2"><?= _('Preview') ?></h3>
                <div id="card-preview">
                    <!-- Draggable elements will be added here by JS -->
                </div>
            </div>

            <!-- Available Elements -->
            <div class="w-1/4">
                <h3 class="text-lg font-semibold mb-2"><?= _('Available Elements') ?></h3>
                <ul id="element-list" class="space-y-2">
                    <li><button type="button" data-element="photo" class="w-full p-2 bg-gray-200 rounded hover:bg-gray-300"><?= _('Photo') ?></button></li>
                    <li><button type="button" data-element="nom_complet" class="w-full p-2 bg-gray-200 rounded hover:bg-gray-300"><?= _('Full Name') ?></button></li>
                    <li><button type="button" data-element="matricule" class="w-full p-2 bg-gray-200 rounded hover:bg-gray-300"><?= _('ID Number') ?></button></li>
                    <li><button type="button" data-element="classe" class="w-full p-2 bg-gray-200 rounded hover:bg-gray-300"><?= _('Class') ?></button></li>
                    <li><button type="button" data-element="qr_code" class="w-full p-2 bg-gray-200 rounded hover:bg-gray-300"><?= _('QR Code') ?></button></li>
                </ul>
            </div>
        </div>

        <div class="mt-8 flex justify-end">
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                <?= _('Save Template') ?>
            </button>
        </div>
    </form>
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

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
