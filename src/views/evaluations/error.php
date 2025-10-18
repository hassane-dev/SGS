<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-danger">
            <h6 class="m-0 font-weight-bold text-white"><?= htmlspecialchars($title ?? 'Erreur') ?></h6>
        </div>
        <div class="card-body">
            <div class="alert alert-danger">
                <?= htmlspecialchars($message ?? 'Une erreur inattendue est survenue.') ?>
            </div>
            <a href="/evaluations/select_class" class="btn btn-secondary">Retour à la sélection</a>
        </div>
    </div>
</div>