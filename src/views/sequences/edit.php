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
                            <h5 class="m-b-10">Séquences Annuelles</h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/home">Tableau de Bord</a></li>
                            <li class="breadcrumb-item"><a href="/sequences">Liste des Séquences</a></li>
                            <li class="breadcrumb-item" aria-current="page">Modifier la Séquence</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- [ Main Content ] start -->
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Modifier la séquence</h5>
                    </div>
                    <div class="card-body">
                        <form action="/sequences/update" method="POST">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($sequence['id']) ?>">
                            <?php include '_form.php'; ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ Main Content ] end -->
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
