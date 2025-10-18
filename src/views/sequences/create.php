<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Nouvelle Séquence</h1>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Détails de la séquence</h6>
        </div>
        <div class="card-body">
            <form action="/sequences/store" method="POST">
                <?php include '_form.php'; ?>
            </form>
        </div>
    </div>
</div>