<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/"><?= _('Tableau de Bord') ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _('Cahier de Texte') ?></li>
                        </ul>
                    </div>
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= $is_admin ? _('Consultation du Cahier de Texte') : _('Mon Cahier de Texte') ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <?php if (Auth::get('role_name') === 'enseignant'): ?>
            <div class="row mb-4">
                <div class="col-12 text-end">
                    <a href="/cahier-texte/create" class="btn btn-primary d-inline-flex align-items-center">
                        <i class="ph-duotone ph-plus-circle me-2"></i> <?= _('Nouvelle Entrée') ?>
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($is_admin): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                <h5><?= _('Filtres') ?></h5>
            </div>
            <div class="card-body">
                <form action="/cahier-texte" method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="personnel_id" class="form-label"><?= _('Filtrer par enseignant') ?></label>
                        <select name="personnel_id" id="personnel_id" class="form-select">
                            <option value=""><?= _('Tous les enseignants') ?></option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?= $teacher['id_user'] ?>" <?= ($filters['personnel_id_filter'] == $teacher['id_user']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($teacher['prenom'] . ' ' . $teacher['nom']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="classe_id" class="form-label"><?= _('Filtrer par classe') ?></label>
                        <select name="classe_id" id="classe_id" class="form-select">
                            <option value=""><?= _('Toutes les classes') ?></option>
                            <?php foreach ($classes as $classe): ?>
                                 <option value="<?= $classe['id_classe'] ?>" <?= ($filters['classe_id_filter'] == $classe['id_classe']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(Classe::getFormattedName($classe)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="date" class="form-label"><?= _('Filtrer par date') ?></label>
                        <input type="date" name="date" id="date" value="<?= htmlspecialchars($filters['date_filter'] ?? '') ?>" class="form-control">
                    </div>
                    <div class="col-md-3 d-flex">
                        <button type="submit" class="btn btn-info me-2 d-inline-flex align-items-center">
                            <i class="ph-duotone ph-funnel me-2"></i><?= _('Filtrer') ?>
                        </button>
                        <a href="/cahier-texte" class="btn btn-secondary d-inline-flex align-items-center">
                            <i class="ph-duotone ph-arrow-counter-clockwise me-2"></i><?= _('Effacer') ?>
                        </a>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <h5><?= _('Entrées du Cahier de Texte') ?></h5>
                    </div>
                    <div class="card-body table-border-style">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th><?= _('Date') ?></th>
                                        <?php if ($is_admin): ?>
                                            <th><?= _('Enseignant') ?></th>
                                        <?php endif; ?>
                                        <th><?= _('Classe') ?></th>
                                        <th><?= _('Matière') ?></th>
                                        <th><?= _('Contenu') ?></th>
                                        <th class="text-end"><?= _('Actions') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($entries)): ?>
                                        <tr>
                                            <td colspan="<?= $is_admin ? '6' : '5' ?>" class="text-center p-5">
                                                <i class="ph-duotone ph-info fs-1 text-muted mb-2 d-block"></i>
                                                <?= _('Aucune entrée trouvée.') ?>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($entries as $entry): ?>
                                            <tr>
                                                <td><?= htmlspecialchars(date('d/m/Y', strtotime($entry['date_cours']))) ?></td>
                                                <?php if ($is_admin): ?>
                                                    <td><?= htmlspecialchars($entry['prenom_personnel'] . ' ' . $entry['nom_personnel']) ?></td>
                                                <?php endif; ?>
                                                <td><span class="badge bg-light-primary text-primary"><?= htmlspecialchars(Classe::getFormattedName($entry)) ?></span></td>
                                                <td><?= htmlspecialchars($entry['nom_matiere']) ?></td>
                                                <td class="text-truncate" style="max-width: 300px;"><?= htmlspecialchars($entry['contenu_cours']) ?></td>
                                                <td class="text-end">
                                                    <?php if ($is_admin || Auth::get('id') == $entry['personnel_id']): ?>
                                                        <a href="/cahier-texte/edit?id=<?= $entry['cahier_id'] ?>" class="btn btn-sm btn-light-primary me-1">
                                                            <i class="ph-duotone ph-pencil-simple"></i>
                                                        </a>
                                                        <form action="/cahier-texte/destroy" method="POST" class="d-inline-block" onsubmit="return confirm('<?= _('Êtes-vous sûr de vouloir supprimer cette entrée ?') ?>');">
                                                            <input type="hidden" name="id" value="<?= $entry['cahier_id'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-light-danger">
                                                                <i class="ph-duotone ph-trash"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
