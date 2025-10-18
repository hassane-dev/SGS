<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Modifier la Matière</h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Détails de la matière</h6>
        </div>
        <div class="card-body">
            <form action="/matieres/update" method="POST">
                <input type="hidden" name="id_matiere" value="<?= htmlspecialchars($matiere['id_matiere']) ?>">
                <?php include '_form.php'; ?>
            </form>
        </div>
    </div>
</div>