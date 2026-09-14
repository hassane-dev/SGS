<?php
$title = _("Registre des Sanctions Disciplinaires");
require_once __DIR__ . '/../../layouts/header_able.php';
require_once __DIR__ . '/../../layouts/sidebar_able.php';

$canManage = Auth::can('manage_sanctions', 'discipline');
?>

<div class="pc-container">
    <div class="pc-content">

        <!-- Breadcrumb -->
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h5 class="m-b-10"><?= _("Vie Scolaire & Discipline") ?></h5>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/home"><?= _("Accueil") ?></a></li>
                            <li class="breadcrumb-item"><a href="/discipline/sanctions"><?= _("Discipline") ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _("Sanctions") ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Flash messages -->
        <?php if (!empty($_SESSION['flash_success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="ph-duotone ph-check-circle me-2 fs-5"></i>
                <?= htmlspecialchars($_SESSION['flash_success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>

        <?php if (!empty($_SESSION['flash_error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="ph-duotone ph-warning-circle me-2 fs-5"></i>
                <?= htmlspecialchars($_SESSION['flash_error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <!-- Action & Filter Card -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><?= _("Registre Général des Sanctions") ?></h5>
                <?php if ($canManage): ?>
                    <a href="/discipline/sanctions/create" class="btn btn-primary d-inline-flex align-items-center gap-1">
                        <i class="ph-duotone ph-plus fs-5"></i><?= _("Prononcer une sanction") ?>
                    </a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <form action="/discipline/sanctions" method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="type_sanction_id" class="form-label"><?= _("Type de Sanction") ?></label>
                        <select name="type_sanction_id" id="type_sanction_id" class="form-select">
                            <option value=""><?= _("Tous les types") ?></option>
                            <?php foreach ($typesSanctions as $ts): ?>
                                <option value="<?= $ts['id'] ?>" <?= ($filters['type_sanction_id'] == $ts['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($ts['libelle']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="statut" class="form-label"><?= _("Statut") ?></label>
                        <select name="statut" id="statut" class="form-select">
                            <option value=""><?= _("Tous les statuts") ?></option>
                            <option value="prononcee" <?= ($filters['statut'] === 'prononcee') ? 'selected' : '' ?>><?= _("Prononcée") ?></option>
                            <option value="en_cours" <?= ($filters['statut'] === 'en_cours') ? 'selected' : '' ?>><?= _("En cours d'exécution") ?></option>
                            <option value="executee" <?= ($filters['statut'] === 'executee') ? 'selected' : '' ?>><?= _("Exécutée") ?></option>
                            <option value="levee" <?= ($filters['statut'] === 'levee') ? 'selected' : '' ?>><?= _("Levée") ?></option>
                            <option value="annulee" <?= ($filters['statut'] === 'annulee') ? 'selected' : '' ?>><?= _("Annulée") ?></option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="classe_id" class="form-label"><?= _("Classe") ?></label>
                        <select name="classe_id" id="classe_id" class="form-select">
                            <option value=""><?= _("Toutes les classes") ?></option>
                            <?php foreach ($classes as $cls): ?>
                                <option value="<?= $cls['id_classe'] ?>" <?= ($filters['classe_id'] == $cls['id_classe']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cls['niveau'] . ($cls['serie'] ? ' ' . $cls['serie'] : '') . ($cls['numero'] ? ' ' . $cls['numero'] : '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="date_debut" class="form-label"><?= _("Du") ?></label>
                        <input type="date" name="date_debut" id="date_debut" class="form-control" value="<?= htmlspecialchars($filters['date_debut'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="date_fin" class="form-label"><?= _("Au") ?></label>
                        <input type="date" name="date_fin" id="date_fin" class="form-control" value="<?= htmlspecialchars($filters['date_fin'] ?? '') ?>">
                    </div>
                    <div class="col-12 text-end mt-3">
                        <a href="/discipline/sanctions" class="btn btn-outline-secondary me-2"><?= _("Réinitialiser") ?></a>
                        <button type="submit" class="btn btn-secondary d-inline-flex align-items-center gap-1">
                            <i class="ph-duotone ph-funnel"></i><?= _("Filtrer") ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Sanctions Table Card -->
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th><?= _("Date Décision") ?></th>
                                <th><?= _("Élève") ?></th>
                                <th><?= _("Classe") ?></th>
                                <th><?= _("Type de Sanction") ?></th>
                                <th><?= _("Motif") ?></th>
                                <th><?= _("Prononcée par") ?></th>
                                <th><?= _("Statut") ?></th>
                                <th class="text-end"><?= _("Action") ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($sanctions)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-5">
                                        <i class="ph-duotone ph-gavel fs-1 d-block mb-2 text-primary"></i>
                                        <?= _("Aucune sanction disciplinaire enregistrée.") ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($sanctions as $s): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?= date('d/m/Y', strtotime($s['date_decision'])) ?></div>
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?= htmlspecialchars($s['eleve_nom'] . ' ' . $s['eleve_prenom']) ?></div>
                                            <span class="text-muted small"><?= htmlspecialchars($s['eleve_matricule']) ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($s['classe_niveau'] . ($s['classe_serie'] ? ' ' . $s['classe_serie'] : '') . ($s['classe_numero'] ? ' ' . $s['classe_numero'] : '')) ?></td>
                                        <td class="fw-bold"><?= htmlspecialchars($s['type_libelle']) ?></td>
                                        <td><?= htmlspecialchars($s['motif']) ?></td>
                                        <td><?= htmlspecialchars($s['prononcee_prenom'] . ' ' . $s['prononcee_nom']) ?></td>
                                        <td>
                                            <?php
                                            $badgeStatut = match($s['statut']) {
                                                'prononcee' => 'bg-light-primary text-primary',
                                                'en_cours' => 'bg-light-warning text-warning',
                                                'executee' => 'bg-light-success text-success',
                                                'levee' => 'bg-light-info text-info',
                                                'annulee' => 'bg-light-secondary text-muted',
                                                default => 'bg-light-secondary text-secondary'
                                            };
                                            $labelStatut = match($s['statut']) {
                                                'prononcee' => _("Prononcée"),
                                                'en_cours' => _("En cours"),
                                                'executee' => _("Exécutée"),
                                                'levee' => _("Levée"),
                                                'annulee' => _("Annulée"),
                                                default => htmlspecialchars($s['statut'])
                                            };
                                            ?>
                                            <span class="badge <?= $badgeStatut ?>"><?= $labelStatut ?></span>
                                        </td>
                                        <td class="text-end">
                                            <a href="/discipline/sanctions/show?id=<?= $s['id'] ?>" class="btn btn-sm btn-light-primary d-inline-flex align-items-center gap-1">
                                                <i class="ph-duotone ph-eye"></i><?= _("Consulter") ?>
                                            </a>
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

<?php require_once __DIR__ . '/../../layouts/footer_able.php'; ?>