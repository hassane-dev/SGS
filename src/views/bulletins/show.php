<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <h1 class="h3 mb-0 text-gray-800">Bulletin de Notes</h1>
        <div>
            <a href="#" class="btn btn-primary" onclick="window.print();">
                <i class="fas fa-print"></i> Imprimer / PDF
            </a>
             <a href="/bulletins" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body" id="bulletin-content">
            <?php
            // Default layout in case of an issue
            $layout = $layout ?? ['header', 'info_eleve', 'tableau_notes', 'resume_moyennes'];

            foreach ($layout as $block_name) {
                $block_path = __DIR__ . '/blocs/_' . $block_name . '.php';
                if (file_exists($block_path)) {
                    include $block_path;
                }
            }
            ?>
        </div>
    </div>
</div>

<style>
@media print {
    .no-print {
        display: none;
    }
    body * {
        visibility: hidden;
    }
    #bulletin-content, #bulletin-content * {
        visibility: visible;
    }
    #bulletin-content {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
}
</style>