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
                            <h2 class="mb-0"><?= _('Modifier le Rôle') ?>: <span class="text-primary"><?= htmlspecialchars($role['nom_role']) ?></span></h2>
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
                        <form action="/roles/update" method="POST">
                            <input type="hidden" name="id_role" value="<?= htmlspecialchars($role['id_role']) ?>">

                            <div class="row">
                                <!-- Role Details -->
                                <div class="col-md-4">
                                    <h5 class="border-bottom pb-2 mb-3"><?= _('Détails du Rôle') ?></h5>
                                    <div class="mb-3">
                                        <label for="nom_role" class="form-label"><?= _('Nom du Rôle') ?></label>
                                        <input type="text" name="nom_role" id="nom_role" class="form-control" value="<?= htmlspecialchars($role['nom_role']) ?>" required>
                                    </div>

                                    <?php if (Auth::can('view_all_lycees', 'lycee')): ?>
                                    <div class="mb-3">
                                        <label for="lycee_id" class="form-label"><?= _('Portée') ?></label>
                                        <select name="lycee_id" id="lycee_id" class="form-select">
                                            <option value="" <?= !$role['lycee_id'] ? 'selected' : '' ?>><?= _('Rôle Global') ?></option>
                                            <?php foreach ($lycees as $lycee): ?>
                                                <option value="<?= $lycee['id_lycee'] ?>" <?= $role['lycee_id'] == $lycee['id_lycee'] ? 'selected' : '' ?>><?= _('Spécifique à') ?>: <?= htmlspecialchars($lycee['nom_lycee']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <?php else: ?>
                                        <input type="hidden" name="lycee_id" value="<?= htmlspecialchars($role['lycee_id']) ?>">
                                    <?php endif; ?>
                                </div>

                                <!-- Permissions -->
                                <div class="col-md-8">
                                    <h5 class="border-bottom pb-2 mb-3"><?= _('Permissions') ?></h5>
                                    <div class="row">
                                        <?php
                                        // Group permissions by resource
                                        $grouped_permissions = [];
                                        foreach ($permissions as $permission) {
                                            $grouped_permissions[$permission['resource']][] = $permission;
                                        }
                                        ?>

                                        <?php foreach ($grouped_permissions as $resource => $perms): ?>
                                            <div class="col-md-6 col-lg-4 mb-3">
                                                <fieldset class="border rounded p-3 h-100">
                                                    <legend class="px-2 float-none w-auto fs-6"><?= _(ucfirst(str_replace('_', ' ', $resource))) ?></legend>
                                                    <?php foreach ($perms as $permission): ?>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" name="permissions[]" value="<?= $permission['id_permission'] ?>" id="perm_<?= $permission['id_permission'] ?>"
                                                                <?= in_array($permission['id_permission'], $role_permission_ids) ? 'checked' : '' ?>>
                                                            <label class="form-check-label" for="perm_<?= $permission['id_permission'] ?>">
                                                                <?= _(ucfirst($permission['action'])) ?>
                                                            </label>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </fieldset>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 d-flex justify-content-end">
                                <a href="/roles" class="btn btn-secondary me-2"><?= _('Annuler') ?></a>
                                <button type="submit" class="btn btn-primary">
                                    <?= _('Mettre à jour') ?>
                                </button>
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
