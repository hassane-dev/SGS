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
                            <h2 class="mb-0"><?= _('Modifier l\'Année Académique') ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- [ Main Content ] start -->
        <div class="row">
            <div class="col-md-8 col-lg-6 mx-auto">
                <div class="card">
                     <div class="card-header">
                        <h5><?= _('Édition de l\'année: ') . htmlspecialchars($annee['libelle'] ?? '') ?></h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['error_message'])): ?>
                            <div class="alert alert-danger"><?= $_SESSION['error_message'] ?></div>
                            <?php unset($_SESSION['error_message']); ?>
                        <?php endif; ?>

                        <?php
                            $is_edit = true;
                            $form_action = "/annees-academiques/update";
                            $old_post = $_SESSION['old_post'] ?? [];
                            unset($_SESSION['old_post']);

                            require '_form.php';
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ Main Content ] end -->
    </div>
</div>
<!-- [ Main Content ] end -->

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>