<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<!-- [ Main Content ] start -->
<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= _('Modifier l\'élève') ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- [ Main Content ] start -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form action="/eleves/update" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="id_eleve" value="<?= $eleve['id_eleve'] ?>">
                            <?php
                                $eleve = $eleve ?? []; // Ensure $eleve is an array
                                include '_form.php';
                            ?>
                            <div class="mt-4 d-flex justify-content-end">
                                <a href="/eleves" class="btn btn-secondary me-2"><?= _('Annuler') ?></a>
                                <button type="submit" class="btn btn-primary"><?= _('Mettre à jour') ?></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ Main Content ] end -->
    </div>
</div>
<!-- [ Main Content ] end -->

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
