<h1 class="mb-4">Modifier la Permission</h1>

<div class="card">
    <div class="card-body">
        <form action="/permissions/update" method="POST">
            <input type="hidden" name="id_permission" value="<?= htmlspecialchars($permission['id_permission']) ?>">
            <?php
                $is_edit = true;
                include '_form.php';
            ?>
        </form>
    </div>
</div>