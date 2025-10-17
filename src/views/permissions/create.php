<h1 class="mb-4">Créer une nouvelle Permission</h1>

<div class="card">
    <div class="card-body">
        <form action="/permissions/store" method="POST">
            <?php
                $is_edit = false;
                $permission = []; // Initialise une permission vide
                include '_form.php';
            ?>
        </form>
    </div>
</div>