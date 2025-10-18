<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container mt-4">
    <h1>Réinscription d'un Élève</h1>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">La demande de réinscription a été soumise avec succès.</div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">Rechercher un élève</div>
        <div class="card-body">
            <form action="/reinscription/search" method="POST">
                <div class="mb-3">
                    <label for="search_term" class="form-label">Entrez le matricule, le nom ou le prénom de l'élève</label>
                    <input type="text" class="form-control" id="search_term" name="search_term" required>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Rechercher</button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>