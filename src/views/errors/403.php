<?php
include __DIR__ . '/../layouts/header_able.php';
?>

<div class="pc-container">
    <div class="pc-content">
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h5 class="m-b-10">Accès Interdit</h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/">Accueil</a></li>
                            <li class="breadcrumb-item">Erreur 403</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-body text-center">
                        <h1 class="display-1 text-danger">403</h1>
                        <h2>Accès Refusé</h2>
                        <p class="lead">Désolé, vous n'avez pas les permissions nécessaires pour accéder à cette page.</p>
                        <a href="/" class="btn btn-primary">Retour à l'accueil</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include __DIR__ . '/../layouts/footer_able.php';
?>
