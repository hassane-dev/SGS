<?php require_once __DIR__ . '/../layouts/header_able.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <!-- [ breadcrumb ] start -->
        <div class="page-header d-print-none">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= _('Journal Comptable Unique') ?></h2>
                        </div>
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/home"><?= _('Accueil') ?></a></li>
                            <li class="breadcrumb-item"><a href="/paiements"><?= _('Comptabilité') ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _('Journal Comptable') ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ breadcrumb ] end -->

        <!-- Printable Header Section (Only visible during print) -->
        <div class="d-none d-print-block mb-4">
            <div class="text-center">
                <h2><?= htmlspecialchars($title) ?></h2>
                <h5><?= htmlspecialchars(Auth::user()['nom'] ?? '') ?></h5>
                <p class="mb-1"><?= _('Généré le') ?> <?= date('d/m/Y H:i') ?></p>
                <p class="text-muted small"><?= _('Période du') ?> <?= htmlspecialchars($filters['date_debut'] ?: 'début') ?> <?= _('au') ?> <?= htmlspecialchars($filters['date_fin'] ?: 'fin') ?></p>
            </div>
            <hr>
        </div>

        <!-- [ Main Content ] start -->
        <div class="row">
            <!-- Filter Section -->
            <div class="col-12 d-print-none">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-0 py-3">
                        <h5 class="mb-0"><i class="ph-duotone ph-funnel me-2"></i><?= _('Filtres de recherche') ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="/paiements/journal" id="journalFilterForm" class="row g-3">
                            <input type="hidden" name="view_type" id="view_type_input" value="<?= htmlspecialchars($filters['view_type']) ?>">

                            <!-- Etablissement (Mode Multi-Etablissement) -->
                            <?php if (count($lycees) > 1): ?>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold"><?= _('Établissement') ?></label>
                                    <select name="lycee_id" class="form-select" onchange="this.form.submit()">
                                        <?php foreach ($lycees as $l): ?>
                                            <option value="<?= $l['id'] ?>" <?= $l['id'] == $selected_lycee_id ? 'selected' : '' ?>><?= htmlspecialchars($l['nom_lycee']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endif; ?>

                            <!-- Année Académique (Filtre Obligatoire) -->
                            <div class="col-md-3">
                                <label class="form-label fw-bold"><?= _('Année Académique') ?> <span class="text-danger">*</span></label>
                                <select name="annee_academique_id" class="form-select" required onchange="this.form.submit()">
                                    <?php foreach ($annees as $an): ?>
                                        <option value="<?= $an['id'] ?>" <?= $an['id'] == $selected_annee_id ? 'selected' : '' ?>><?= htmlspecialchars($an['libelle']) ?> <?= $an['est_active'] ? '('._('active').')' : '' ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Cycle (CEG / Lycée) -->
                            <div class="col-md-3">
                                <label class="form-label fw-bold"><?= _('Cycle') ?></label>
                                <select name="cycle_id" id="cycle_select" class="form-select" onchange="this.form.submit()">
                                    <option value=""><?= _('Tous les cycles') ?></option>
                                    <?php foreach ($cycles as $cy): ?>
                                        <option value="<?= $cy['id_cycle'] ?>" data-name="<?= htmlspecialchars($cy['nom_cycle']) ?>" <?= $cy['id_cycle'] == $filters['cycle_id'] ? 'selected' : '' ?>><?= htmlspecialchars($cy['nom_cycle']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Niveau -->
                            <div class="col-md-3">
                                <label class="form-label fw-bold"><?= _('Niveau') ?></label>
                                <select name="niveau" class="form-select" onchange="this.form.submit()">
                                    <option value=""><?= _('Tous les niveaux') ?></option>
                                    <?php foreach ($niveaux as $niv): ?>
                                        <option value="<?= htmlspecialchars($niv) ?>" <?= $niv === $filters['niveau'] ? 'selected' : '' ?>><?= htmlspecialchars($niv) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Série (Dynamic field disabled for CEG) -->
                            <div class="col-md-3" id="serie_filter_container">
                                <label class="form-label fw-bold"><?= _('Série') ?></label>
                                <select name="serie" id="serie_select" class="form-select" onchange="this.form.submit()">
                                    <option value=""><?= _('Toutes les séries') ?></option>
                                    <?php foreach ($series as $ser): ?>
                                        <option value="<?= htmlspecialchars($ser) ?>" <?= $ser === $filters['serie'] ? 'selected' : '' ?>><?= htmlspecialchars($ser) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Numéro de classe -->
                            <div class="col-md-3">
                                <label class="form-label fw-bold"><?= _('Numéro de classe') ?></label>
                                <select name="numero" class="form-select" onchange="this.form.submit()">
                                    <option value=""><?= _('Tous les numéros') ?></option>
                                    <?php foreach ($numeros as $num): ?>
                                        <option value="<?= htmlspecialchars($num) ?>" <?= $num == $filters['numero'] ? 'selected' : '' ?>><?= htmlspecialchars($num) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Dates filter -->
                            <div class="col-md-3">
                                <label class="form-label fw-bold"><?= _('Période du') ?></label>
                                <input type="date" name="date_debut" class="form-control" value="<?= htmlspecialchars($filters['date_debut']) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold"><?= _('au') ?></label>
                                <input type="date" name="date_fin" class="form-control" value="<?= htmlspecialchars($filters['date_fin']) ?>">
                            </div>

                            <!-- Operation Type filter -->
                            <div class="col-md-3">
                                <label class="form-label fw-bold"><?= _('Type d\'opération') ?></label>
                                <select name="operation" class="form-select">
                                    <option value=""><?= _('Toutes') ?></option>
                                    <option value="inscription" <?= $filters['operation'] === 'inscription' ? 'selected' : '' ?>><?= _('Inscription') ?></option>
                                    <option value="mensualite" <?= $filters['operation'] === 'mensualite' ? 'selected' : '' ?>><?= _('Mensualité') ?></option>
                                    <option value="annulation" <?= $filters['operation'] === 'annulation' ? 'selected' : '' ?>><?= _('Annulation') ?></option>
                                    <option value="remboursement" <?= $filters['operation'] === 'remboursement' ? 'selected' : '' ?>><?= _('Remboursement') ?></option>
                                </select>
                            </div>

                            <!-- Student Search -->
                            <div class="col-md-6">
                                <label class="form-label fw-bold"><?= _('Recherche Élève (Nom, Prénom, Matricule, Reçu)') ?></label>
                                <div class="input-group">
                                    <input type="text" name="search" class="form-control" placeholder="<?= _('Saisir nom, prénom, matricule, reçu...') ?>" value="<?= htmlspecialchars($filters['search']) ?>">
                                    <button type="submit" class="btn btn-primary"><i class="ph-duotone ph-magnifying-glass me-1"></i><?= _('Filtrer') ?></button>
                                    <button type="button" onclick="window.print()" class="btn btn-secondary"><i class="ph-duotone ph-printer me-1"></i><?= _('Imprimer') ?></button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Journal and Navigation Tabs -->
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 pt-4 pb-0 d-print-none">
                        <ul class="nav nav-tabs card-header-tabs" id="journalTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link <?= $filters['view_type'] === 'detailed' ? 'active fw-bold text-primary' : '' ?>" href="#" onclick="switchView('detailed')">
                                    <i class="ph-duotone ph-list-bullets me-1"></i><?= _('Vue 1 : Journal Détaillé') ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $filters['view_type'] === 'receipt' ? 'active fw-bold text-primary' : '' ?>" href="#" onclick="switchView('receipt')">
                                    <i class="ph-duotone ph-receipt me-1"></i><?= _('Vue 2 : Journal par Reçu') ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $filters['view_type'] === 'class' ? 'active fw-bold text-primary' : '' ?>" href="#" onclick="switchView('class')">
                                    <i class="ph-duotone ph-buildings me-1"></i><?= _('Vue 3 : Synthèse par Classe') ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $filters['view_type'] === 'student' ? 'active fw-bold text-primary' : '' ?>" href="#" onclick="switchView('student')">
                                    <i class="ph-duotone ph-student me-1"></i><?= _('Vue 4 : Synthèse par Élève') ?>
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div class="card-body p-0">
                        <!-- Vue 1 : Journal Détaillé -->
                        <?php if ($filters['view_type'] === 'detailed'): ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light-layout">
                                        <tr>
                                            <th class="ps-4"><?= _('Date & Heure') ?></th>
                                            <th><?= _('Élève (Identifiant)') ?></th>
                                            <th><?= _('Opération') ?></th>
                                            <th class="text-end"><?= _('Montant') ?></th>
                                            <th><?= _('Mode') ?></th>
                                            <th><?= _('Reçu N°') ?></th>
                                            <th><?= _('Opérateur') ?></th>
                                            <th class="d-print-none"><?= _('Réf Origine') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($entries)): ?>
                                            <tr>
                                                <td colspan="8" class="text-center py-5 text-muted">
                                                    <i class="ph-duotone ph-folder-open fs-1 d-block mb-2"></i>
                                                    <?= _('Aucune opération enregistrée dans le journal pour les critères sélectionnés.') ?>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php
                                            $totalJournal = 0.00;
                                            foreach ($entries as $entry):
                                                $totalJournal += (float)$entry['montant'];

                                                $opClass = 'bg-light-success text-success';
                                                $opLabel = _('Mensualité');
                                                if ($entry['operation'] === 'inscription') {
                                                    $opClass = 'bg-light-info text-info';
                                                    $opLabel = _('Inscription');
                                                } elseif ($entry['operation'] === 'annulation') {
                                                    $opClass = 'bg-light-danger text-danger';
                                                    $opLabel = _('Annulation');
                                                } elseif ($entry['operation'] === 'remboursement') {
                                                    $opClass = 'bg-light-warning text-warning';
                                                    $opLabel = _('Remboursement');
                                                }
                                            ?>
                                                <tr>
                                                    <td class="ps-4">
                                                        <?= date('d/m/Y H:i', strtotime($entry['date_creation'])) ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($entry['eleve_id']): ?>
                                                            <span class="fw-bold"><?= htmlspecialchars($entry['eleve_prenom'] . ' ' . $entry['eleve_nom']) ?></span>
                                                            <br>
                                                            <small class="text-muted">Mat: <?= htmlspecialchars($entry['eleve_matricule'] ?? 'N/A') ?></small>
                                                            <?php if (!empty($entry['classe_niveau'])): ?>
                                                                <br><small class="text-secondary"><?= htmlspecialchars($entry['classe_niveau'] . ' ' . ($entry['classe_serie'] ?? '') . ' ' . ($entry['classe_numero'] ?? '')) ?></small>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">N/A</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge <?= $opClass ?>"><?= $opLabel ?></span>
                                                    </td>
                                                    <td class="text-end fw-bold <?= $entry['montant'] < 0 ? 'text-danger' : 'text-dark' ?>">
                                                        <?= number_format($entry['montant'], 0, ',', ' ') ?> FCFA
                                                    </td>
                                                    <td>
                                                        <?= htmlspecialchars($entry['mode_paiement'] ?? 'N/A') ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($entry['recu_numero']): ?>
                                                            <span class="badge bg-light-secondary text-dark"><?= htmlspecialchars($entry['recu_numero']) ?></span>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?= htmlspecialchars($entry['user_prenom'] . ' ' . $entry['user_nom']) ?>
                                                    </td>
                                                    <td class="d-print-none text-muted small">
                                                        <code><?= htmlspecialchars($entry['reference_origine'] ?? '') ?></code>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <tr class="table-light fw-bold fs-6">
                                                <td colspan="3" class="ps-4 text-end text-uppercase"><?= _('Bilan Net') ?></td>
                                                <td class="text-end text-primary"><?= number_format($totalJournal, 0, ',', ' ') ?> FCFA</td>
                                                <td colspan="5"></td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>

                        <!-- Vue 2 : Journal par reçu -->
                        <?php if ($filters['view_type'] === 'receipt'): ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light-layout">
                                        <tr>
                                            <th class="ps-4"><?= _('N° Reçu') ?></th>
                                            <th><?= _("Date d'émission") ?></th>
                                            <th><?= _('Élève (Identifiant)') ?></th>
                                            <th class="text-end"><?= _('Total Encaissé') ?></th>
                                            <th class="text-center"><?= _("Nombre d'écritures") ?></th>
                                            <th><?= _('Opérateur') ?></th>
                                            <th class="text-center d-print-none"><?= _('Actions') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($receipt_entries)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center py-5 text-muted">
                                                    <i class="ph-duotone ph-receipt fs-1 d-block mb-2"></i>
                                                    <?= _('Aucun reçu enregistré pour les critères sélectionnés.') ?>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php
                                            $totalReceipts = 0.00;
                                            foreach ($receipt_entries as $r):
                                                $totalReceipts += $r['total_encaisse'];
                                            ?>
                                                <tr>
                                                    <td class="ps-4"><span class="badge bg-light-dark text-dark fs-6"><?= htmlspecialchars($r['recu_numero']) ?></span></td>
                                                    <td><?= date('d/m/Y H:i', strtotime($r['date'])) ?></td>
                                                    <td>
                                                        <span class="fw-bold"><?= htmlspecialchars($r['eleve_prenom'] . ' ' . $r['eleve_nom']) ?></span>
                                                        <br><small class="text-muted">Mat: <?= htmlspecialchars($r['eleve_matricule']) ?></small>
                                                    </td>
                                                    <td class="text-end fw-bold text-primary"><?= number_format($r['total_encaisse'], 0, ',', ' ') ?> FCFA</td>
                                                    <td class="text-center"><span class="badge bg-light-primary text-primary"><?= $r['count_entries'] ?></span></td>
                                                    <td><?= htmlspecialchars($r['user_prenom'] . ' ' . $r['user_nom']) ?></td>
                                                    <td class="text-center d-print-none">
                                                        <a href="/recu/print?numero=<?= urlencode($r['recu_numero']) ?>" target="_blank" class="btn btn-sm btn-icon btn-light-primary" title="<?= _('Imprimer le reçu') ?>">
                                                            <i class="ph-duotone ph-printer fs-5"></i>
                                                        </a>
                                                        <?php if ($r['eleve_id']): ?>
                                                            <a href="/paiements/show/<?= $r['eleve_id'] ?>" class="btn btn-sm btn-icon btn-light-secondary" title="<?= _('Dossier Élève') ?>">
                                                                <i class="ph-duotone ph-eye fs-5"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <tr class="table-light fw-bold fs-6">
                                                <td colspan="3" class="ps-4 text-end text-uppercase"><?= _('Bilan Net') ?></td>
                                                <td class="text-end text-primary"><?= number_format($totalReceipts, 0, ',', ' ') ?> FCFA</td>
                                                <td colspan="4"></td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>

                        <!-- Vue 3 : Synthèse par classe -->
                        <?php if ($filters['view_type'] === 'class'): ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light-layout">
                                        <tr>
                                            <th class="ps-4"><?= _('Cycle') ?></th>
                                            <th><?= _('Niveau') ?></th>
                                            <th><?= _('Série') ?></th>
                                            <th><?= _('Numéro') ?></th>
                                            <th class="text-center"><?= _('Effectif') ?></th>
                                            <th class="text-end"><?= _('Total Payé') ?></th>
                                            <th class="text-end"><?= _('Reste à Payer') ?></th>
                                            <th class="text-center d-print-none"><?= _('Actions') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($class_synthesis)): ?>
                                            <tr>
                                                <td colspan="8" class="text-center py-5 text-muted">
                                                    <i class="ph-duotone ph-buildings fs-1 d-block mb-2"></i>
                                                    <?= _('Aucune classe enregistrée ou correspondant aux critères.') ?>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php
                                            $totalClassPaid = 0.00;
                                            $totalClassReste = 0.00;
                                            foreach ($class_synthesis as $c):
                                                $totalClassPaid += $c['total_paye'];
                                                $totalClassReste += $c['reste'];
                                            ?>
                                                <tr>
                                                    <td class="ps-4"><span class="badge bg-light-primary text-primary"><?= htmlspecialchars($c['nom_cycle']) ?></span></td>
                                                    <td class="fw-bold"><?= htmlspecialchars($c['niveau']) ?></td>
                                                    <td><?= !empty($c['serie']) ? htmlspecialchars($c['serie']) : '<span class="text-muted">-</span>' ?></td>
                                                    <td><?= htmlspecialchars($c['numero']) ?></td>
                                                    <td class="text-center fw-bold"><?= $c['effectif'] ?></td>
                                                    <td class="text-end fw-bold text-success"><?= number_format($c['total_paye'], 0, ',', ' ') ?> FCFA</td>
                                                    <td class="text-end fw-bold text-danger"><?= number_format($c['reste'], 0, ',', ' ') ?> FCFA</td>
                                                    <td class="text-center d-print-none">
                                                        <a href="/mensualites/class/<?= $c['classe_id'] ?>" class="btn btn-sm btn-light-primary" title="<?= _('Tableau de bord de classe') ?>">
                                                            <i class="ph-duotone ph-chart-bar me-1"></i><?= _('Détails') ?>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <tr class="table-light fw-bold fs-6">
                                                <td colspan="5" class="ps-4 text-end text-uppercase"><?= _('Totaux') ?></td>
                                                <td class="text-end text-success"><?= number_format($totalClassPaid, 0, ',', ' ') ?> FCFA</td>
                                                <td class="text-end text-danger"><?= number_format($totalClassReste, 0, ',', ' ') ?> FCFA</td>
                                                <td></td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>

                        <!-- Vue 4 : Synthèse par élève -->
                        <?php if ($filters['view_type'] === 'student'): ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light-layout">
                                        <tr>
                                            <th class="ps-4"><?= _('Élève (Identifiant)') ?></th>
                                            <th><?= _('Classe') ?></th>
                                            <th class="text-end"><?= _('Total Dû') ?></th>
                                            <th class="text-end"><?= _('Total Payé') ?></th>
                                            <th class="text-end"><?= _('Solde Restant') ?></th>
                                            <th class="text-center d-print-none"><?= _('Actions') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($student_synthesis)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-5 text-muted">
                                                    <i class="ph-duotone ph-student fs-1 d-block mb-2"></i>
                                                    <?= _('Aucun élève trouvé pour les critères sélectionnés.') ?>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php
                                            $totalStudentDu = 0.00;
                                            $totalStudentPaid = 0.00;
                                            $totalStudentReste = 0.00;
                                            foreach ($student_synthesis as $s):
                                                $totalStudentDu += $s['total_du'];
                                                $totalStudentPaid += $s['total_paye'];
                                                $totalStudentReste += $s['solde_restant'];
                                            ?>
                                                <tr>
                                                    <td class="ps-4">
                                                        <span class="fw-bold"><?= htmlspecialchars($s['prenom'] . ' ' . $s['nom']) ?></span>
                                                        <br><small class="text-muted">Mat: <?= htmlspecialchars($s['matricule'] ?? 'N/A') ?></small>
                                                    </td>
                                                    <td><span class="badge bg-light-secondary text-dark"><?= htmlspecialchars($s['nom_classe']) ?></span></td>
                                                    <td class="text-end fw-bold"><?= number_format($s['total_du'], 0, ',', ' ') ?> FCFA</td>
                                                    <td class="text-end fw-bold text-success"><?= number_format($s['total_paye'], 0, ',', ' ') ?> FCFA</td>
                                                    <td class="text-end fw-bold <?= $s['solde_restant'] > 0 ? 'text-danger' : 'text-dark' ?>"><?= number_format($s['solde_restant'], 0, ',', ' ') ?> FCFA</td>
                                                    <td class="text-center d-print-none">
                                                        <button class="btn btn-sm btn-light-info me-1" type="button" data-bs-toggle="collapse" data-bs-target="#history-<?= $s['id_eleve'] ?>" aria-expanded="false">
                                                            <i class="ph-duotone ph-clock-counter-clockwise me-1"></i><?= _('Historique') ?>
                                                        </button>
                                                        <a href="/paiements/show/<?= $s['id_eleve'] ?>" class="btn btn-sm btn-light-primary">
                                                            <i class="ph-duotone ph-eye me-1"></i><?= _('Fiche') ?>
                                                        </a>
                                                    </td>
                                                </tr>
                                                <!-- Collapsible History Row -->
                                                <tr class="collapse d-print-none" id="history-<?= $s['id_eleve'] ?>">
                                                    <td colspan="6" class="bg-light-layout p-4">
                                                        <div class="card border-0 shadow-sm m-0">
                                                            <div class="card-header bg-white py-2">
                                                                <h6 class="mb-0 text-primary"><i class="ph-duotone ph-list-bullets me-1"></i><?= _('Écritures de l\'élève dans la période') ?></h6>
                                                            </div>
                                                            <div class="card-body p-0">
                                                                <?php if (empty($s['history'])): ?>
                                                                    <p class="text-muted p-3 mb-0 text-center"><?= _('Aucune écriture trouvée pour cet élève dans cette période.') ?></p>
                                                                <?php else: ?>
                                                                    <div class="table-responsive">
                                                                        <table class="table table-sm table-hover align-middle mb-0">
                                                                            <thead class="table-light">
                                                                                <tr>
                                                                                    <th><?= _('Date') ?></th>
                                                                                    <th><?= _('Opération') ?></th>
                                                                                    <th class="text-end"><?= _('Montant') ?></th>
                                                                                    <th><?= _('Mode') ?></th>
                                                                                    <th><?= _('Reçu') ?></th>
                                                                                    <th><?= _('Opérateur') ?></th>
                                                                                </tr>
                                                                            </thead>
                                                                            <tbody>
                                                                                <?php foreach ($s['history'] as $h): ?>
                                                                                    <tr>
                                                                                        <td><?= date('d/m/Y H:i', strtotime($h['date_creation'])) ?></td>
                                                                                        <td>
                                                                                            <?php
                                                                                            $opCl = 'bg-light-success text-success';
                                                                                            if ($h['operation'] === 'inscription') $opCl = 'bg-light-info text-info';
                                                                                            elseif ($h['operation'] === 'annulation') $opCl = 'bg-light-danger text-danger';
                                                                                            elseif ($h['operation'] === 'remboursement') $opCl = 'bg-light-warning text-warning';
                                                                                            ?>
                                                                                            <span class="badge <?= $opCl ?>"><?= htmlspecialchars($h['operation']) ?></span>
                                                                                        </td>
                                                                                        <td class="text-end fw-bold <?= $h['montant'] < 0 ? 'text-danger' : 'text-dark' ?>"><?= number_format($h['montant'], 0, ',', ' ') ?> FCFA</td>
                                                                                        <td><?= htmlspecialchars($h['mode_paiement'] ?? '-') ?></td>
                                                                                        <td><?= htmlspecialchars($h['recu_numero'] ?? '-') ?></td>
                                                                                        <td><?= htmlspecialchars($h['user_prenom'] . ' ' . $h['user_nom']) ?></td>
                                                                                    </tr>
                                                                                <?php endforeach; ?>
                                                                            </tbody>
                                                                        </table>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <tr class="table-light fw-bold fs-6">
                                                <td colspan="2" class="ps-4 text-end text-uppercase"><?= _('Totaux') ?></td>
                                                <td class="text-end text-dark"><?= number_format($totalStudentDu, 0, ',', ' ') ?> FCFA</td>
                                                <td class="text-end text-success"><?= number_format($totalStudentPaid, 0, ',', ' ') ?> FCFA</td>
                                                <td class="text-end text-danger"><?= number_format($totalStudentReste, 0, ',', ' ') ?> FCFA</td>
                                                <td></td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ Main Content ] end -->
    </div>
</div>

<script>
function switchView(type) {
    document.getElementById('view_type_input').value = type;
    document.getElementById('journalFilterForm').submit();
}

document.addEventListener('DOMContentLoaded', function() {
    const cycleSelect = document.getElementById('cycle_select');
    const serieSelect = document.getElementById('serie_select');

    function toggleFilters() {
        if (!cycleSelect || !serieSelect) return;
        const selectedOption = cycleSelect.options[cycleSelect.selectedIndex];
        const cycleName = selectedOption ? selectedOption.getAttribute('data-name') : '';

        if (cycleName === 'CEG' || (cycleName && cycleName.includes('CEG')) || (cycleName && cycleName.includes('Middle'))) {
            serieSelect.value = '';
            serieSelect.disabled = true;
        } else {
            serieSelect.disabled = false;
        }
    }

    if (cycleSelect && serieSelect) {
        cycleSelect.addEventListener('change', toggleFilters);
        toggleFilters();
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer_able.php'; ?>
