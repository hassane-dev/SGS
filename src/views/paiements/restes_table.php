<div class="row mt-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="mb-0 text-primary">
                            <i class="ph-duotone ph-warning-circle me-2"></i><?= _('Élèves endettés de la classe') ?>
                        </h5>
                    </div>
                    <div class="col-auto">
                        <span class="badge bg-light-danger text-danger"><?= count($restes) ?> <?= _('élèves débiteurs') ?></span>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="table-restes">
                        <thead class="bg-light-layout">
                            <tr>
                                <th class="ps-4"><?= _('Élève') ?></th>
                                <th class="text-end"><?= _('Reste inscription') ?></th>
                                <th class="text-end"><?= _('Reste mensualité') ?></th>
                                <th class="text-end fw-bold text-dark"><?= _('Total reste') ?></th>
                                <th class="text-end pe-4"><?= _('Action') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($restes)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-success">
                                        <i class="ph-duotone ph-check-circle fs-1 d-block mb-2 text-success"></i>
                                        <?= _('Aucun élève de cette classe ne présente actuellement un reste à payer.') ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($restes as $reste): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="d-flex align-items-center">
                                                <div class="flex-shrink-0">
                                                    <img src="<?= $reste['photo'] ?: '/assets/img/default-avatar.png' ?>" class="img-radius wid-40" alt="">
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <h6 class="mb-0"><?= htmlspecialchars($reste['prenom'] . ' ' . $reste['nom']) ?></h6>
                                                    <span class="text-muted small">Mat: <?= htmlspecialchars($reste['matricule'] ?? 'N/A') ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-end text-danger fw-bold">
                                            <?= number_format($reste['reste_inscription'], 0, ',', ' ') ?> FCFA
                                        </td>
                                        <td class="text-end text-danger fw-bold">
                                            <?= number_format($reste['reste_mensualite'], 0, ',', ' ') ?> FCFA
                                        </td>
                                        <td class="text-end text-danger fw-bold fs-6">
                                            <?= number_format($reste['total_reste'], 0, ',', ' ') ?> FCFA
                                        </td>
                                        <td class="text-end pe-4">
                                            <a href="/paiements/regler/<?= $reste['id_eleve'] ?>" class="btn btn-sm btn-primary">
                                                <i class="ph-duotone ph-hand-coins me-1"></i><?= _('Régler') ?>
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
