<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Gestion Scolaire' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            display: flex;
            min-height: 100vh;
            flex-direction: column;
        }
        .main-wrapper {
            display: flex;
            flex: 1;
        }
        #sidebar {
            width: 250px;
            min-height: 100vh;
            background: #343a40;
            color: #fff;
            transition: all 0.3s;
        }
        #content {
            width: 100%;
            padding: 20px;
            transition: all 0.3s;
        }
        #sidebar.toggled {
            margin-left: -250px;
        }
        .sidebar-header {
            padding: 20px;
            background: #495057;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
        }
        @media (max-width: 768px) {
            #sidebar {
                margin-left: -250px;
            }
            #sidebar.toggled {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>

<div class="main-wrapper">
    <!-- Sidebar -->
    <nav id="sidebar">
        <?php include __DIR__ . '/_sidebar.php'; ?>
    </nav>

    <!-- Page Content -->
    <div id="content">
        <!-- Header -->
        <?php include __DIR__ . '/_header.php'; ?>

        <!-- Main Content -->
        <div class="mt-4">
            <?= $content ?? '' ?>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="footer">
    <?php include __DIR__ . '/_footer.php'; ?>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('sidebarCollapse').addEventListener('click', function () {
        document.getElementById('sidebar').classList.toggle('toggled');
    });
</script>
</body>
</html>