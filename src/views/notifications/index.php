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
                            <h2 class="mb-0"><?= _('Toutes les Notifications') ?></h2>
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
                        <ul class="list-group list-group-flush">
                            <?php if (empty($notifications)): ?>
                                <li class="list-group-item text-center"><?= _('Vous n\'avez aucune notification.') ?></li>
                            <?php else: ?>
                                <?php foreach ($notifications as $notif): ?>
                                    <li class="list-group-item <?= $notif['is_read'] ? 'bg-light' : '' ?>">
                                        <div class="d-flex w-100 justify-content-between">
                                            <p class="mb-1"><?= htmlspecialchars($notif['message']) ?></p>
                                            <small><?= date('d/m/Y H:i', strtotime($notif['created_at'])) ?></small>
                                        </div>
                                        <a href="/notifications/mark-as-read?id=<?= $notif['id'] ?>&redirect_to=<?= urlencode($notif['link']) ?>" class="btn btn-sm btn-outline-primary mt-2">
                                            <?= _('Voir les détails') ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ Main Content ] end -->
    </div>
</div>
<!-- [ Main Content ] end -->

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
