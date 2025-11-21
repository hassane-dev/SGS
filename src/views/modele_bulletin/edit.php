<?php
require_once __DIR__ . '/../layouts/header_able.php';
require_once __DIR__ . '/../layouts/sidebar_able.php';
?>

<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= _('Report Card Template Editor') ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- [ Main Content ] start -->
        <div class="row">
            <div class="col-md-12">
                <div class="alert alert-info">
                    <i class="fas fa-arrows-alt"></i>
                    <?= _('Use drag-and-drop to reorder the report card sections. The layout is saved automatically.') ?>
                </div>
                <div id="save-status" class="mb-3"></div>
            </div>
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5><?= _('Template Structure') ?></h5>
                    </div>
                    <div class="card-body">
                        <div id="bulletin-editor" class="list-group">
                            <?php
                            $layout = $template['layout_data'] ?? ['header', 'info_eleve', 'tableau_notes', 'resume_moyennes'];
                            $block_names = [
                                'header' => _('Header (School Name, etc.)'),
                                'info_eleve' => _('Student Information'),
                                'tableau_notes' => _('Grades Table'),
                                'resume_moyennes' => _('Summary (Averages and Appreciations)')
                            ];

                            foreach ($layout as $block_id): ?>
                                <div class="list-group-item" data-id="<?= $block_id ?>">
                                    <i class="fas fa-grip-vertical me-2"></i>
                                    <strong><?= $block_names[$block_id] ?? _('Unknown Block') ?></strong>
                                    <p class="small text-muted mb-0"><?= _('This is a structural preview of the block.') ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5><?= _('Information') ?></h6>
                    </div>
                    <div class="card-body">
                        <p><?= _('This template will apply to all report cards generated for this school.') ?></p>
                        <p><?= _('The order you define here will be the display order on the final report card.') ?></p>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ Main Content ] end -->
    </div>
</div>

<!-- Include Sortable.js from a CDN -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const editor = document.getElementById('bulletin-editor');
    const saveStatus = document.getElementById('save-status');
    const templateId = <?= $template['id'] ?>;

    new Sortable(editor, {
        animation: 150,
        ghostClass: 'bg-primary-light',
        handle: '.fa-grip-vertical',
        onEnd: function (evt) {
            const order = Array.from(editor.children).map(child => child.dataset.id);
            saveLayout(order);
        }
    });

    function saveLayout(order) {
        saveStatus.innerHTML = `<div class="alert alert-warning"><?= _('Saving...') ?></div>`;

        fetch('/modele-bulletin/save', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                'template_id': templateId,
                'layout_order[]': order.join(',') // Sending as comma-separated string
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                saveStatus.innerHTML = `<div class="alert alert-success"><?= _('Layout saved successfully!') ?></div>`;
            } else {
                saveStatus.innerHTML = `<div class="alert alert-danger"><?= _('Error:') ?> ${data.message}</div>`;
            }
            setTimeout(() => { saveStatus.innerHTML = ''; }, 3000);
        })
        .catch(error => {
            saveStatus.innerHTML = `<div class="alert alert-danger"><?= _('A communication error occurred.') ?></div>`;
            setTimeout(() => { saveStatus.innerHTML = ''; }, 3000);
        });
    }
});
</script>

<style>
.list-group-item {
    cursor: grab;
}
</style>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
