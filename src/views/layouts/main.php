<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Gestion Scolaire' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="/css/layout.css">
</head>
<body>

<div class="wrapper">
    <?php require_once 'sidebar.php'; ?>

    <div id="content" class="main-content-wrapper">
        <?php require_once 'header.php'; ?>

        <main class="container-fluid p-4">
            <?= $content ?? '' ?>
        </main>

        <?php require_once 'footer.php'; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const sidebarCollapse = document.getElementById('sidebarCollapse');
        const sidebar = document.querySelector('.sidebar');

        if (sidebarCollapse && sidebar) {
            sidebarCollapse.addEventListener('click', function () {
                sidebar.classList.toggle('active');
            });
        }
    });
</script>
</body>
</html>