<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Éditeur de Modèle de Bulletin</h1>

    <div class="alert alert-info">
        <i class="fas fa-arrows-alt"></i>
        Utilisez le glisser-déposer pour réorganiser les blocs du bulletin. La mise en page est enregistrée automatiquement.
    </div>

    <div id="save-status" class="mb-3"></div>

    <div class="row">
        <div class="col-md-8">
            <div id="bulletin-editor" class="list-group">
                <?php
                // Default block structure in case the template is empty
                $layout = $template['layout_data'] ?? ['header', 'info_eleve', 'tableau_notes', 'resume_moyennes'];
                $block_names = [
                    'header' => 'En-tête (Nom du lycée, etc.)',
                    'info_eleve' => 'Informations de l\'élève',
                    'tableau_notes' => 'Tableau des Notes',
                    'resume_moyennes' => 'Résumé (Moyennes et Appréciations)'
                ];

                foreach ($layout as $block_id): ?>
                    <div class="list-group-item" data-id="<?= $block_id ?>">
                        <i class="fas fa-grip-vertical mr-2"></i>
                        <strong><?= $block_names[$block_id] ?? 'Bloc inconnu' ?></strong>
                        <p class="small text-muted mb-0">Ceci est un aperçu structurel du bloc.</p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-header">
                    <h6 class="m-0 font-weight-bold text-primary">Informations</h6>
                </div>
                <div class="card-body">
                    <p>Ce modèle s'appliquera à tous les bulletins générés pour ce lycée.</p>
                    <p>L'ordre que vous définissez ici sera l'ordre d'affichage sur le bulletin final.</p>
                </div>
            </div>
        </div>
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
        ghostClass: 'blue-background-class',
        handle: '.fa-grip-vertical',
        onEnd: function (evt) {
            const order = Array.from(editor.children).map(child => child.dataset.id);
            saveLayout(order);
        }
    });

    function saveLayout(order) {
        saveStatus.innerHTML = '<div class="alert alert-warning">Enregistrement...</div>';

        fetch('/modele-bulletin/save', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                'template_id': templateId,
                'layout_order[]': order
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                saveStatus.innerHTML = '<div class="alert alert-success">Mise en page enregistrée avec succès !</div>';
            } else {
                saveStatus.innerHTML = `<div class="alert alert-danger">Erreur: ${data.message}</div>`;
            }
            setTimeout(() => { saveStatus.innerHTML = ''; }, 3000);
        })
        .catch(error => {
            saveStatus.innerHTML = '<div class="alert alert-danger">Une erreur de communication est survenue.</div>';
            setTimeout(() => { saveStatus.innerHTML = ''; }, 3000);
        });
    }
});
</script>

<style>
.list-group-item {
    cursor: grab;
}
.blue-background-class {
    background-color: #cce5ff;
}
</style>