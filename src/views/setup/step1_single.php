<?php require_once __DIR__ . '/../layouts/header_centered.php'; ?>

<div class="col-lg-10 col-xl-9">
    <div class="card shadow-lg border-0 rounded-3">
        <div class="card-header bg-primary text-white p-4 rounded-top-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h3 class="h4 fw-bold text-white mb-1">
                        <i class="ph-duotone ph-buildings me-2 fs-3 align-middle"></i><?= _('Assistant d\'Installation - Établissement Mono-école') ?>
                    </h3>
                    <p class="mb-0 text-white-50 small"><?= _('Initialisation complète et sécurisée du socle académique, administratif et financier.') ?></p>
                </div>
                <span class="badge bg-white text-primary fw-bold px-3 py-2 fs-6">SGS v2.0</span>
            </div>
        </div>

        <div class="card-body p-4 p-md-5">
            <?php if (!empty($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                    <i class="ph-duotone ph-warning-circle me-2 fs-5 align-middle"></i>
                    <strong><?= htmlspecialchars($_SESSION['error_message']) ?></strong>
                    <?php unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>

            <!-- Wizard Step Navigation Indicator -->
            <div class="row g-2 mb-4 text-center border-bottom pb-3">
                <div class="col setup-step-nav active" id="step-nav-1">
                    <div class="p-2 rounded bg-light border">
                        <i class="ph-duotone ph-user-circle fs-4 text-primary d-block mb-1"></i>
                        <span class="fw-bold fs-7 d-none d-md-inline"><?= _('1. Admin') ?></span>
                    </div>
                </div>
                <div class="col setup-step-nav" id="step-nav-2">
                    <div class="p-2 rounded bg-light border">
                        <i class="ph-duotone ph-buildings fs-4 text-secondary d-block mb-1"></i>
                        <span class="fw-bold fs-7 d-none d-md-inline"><?= _('2. École') ?></span>
                    </div>
                </div>
                <div class="col setup-step-nav" id="step-nav-3">
                    <div class="p-2 rounded bg-light border">
                        <i class="ph-duotone ph-calendar fs-4 text-secondary d-block mb-1"></i>
                        <span class="fw-bold fs-7 d-none d-md-inline"><?= _('3. Année') ?></span>
                    </div>
                </div>
                <div class="col setup-step-nav" id="step-nav-4">
                    <div class="p-2 rounded bg-light border">
                        <i class="ph-duotone ph-gear fs-4 text-secondary d-block mb-1"></i>
                        <span class="fw-bold fs-7 d-none d-md-inline"><?= _('4. Paramètres') ?></span>
                    </div>
                </div>
                <div class="col setup-step-nav" id="step-nav-5">
                    <div class="p-2 rounded bg-light border">
                        <i class="ph-duotone ph-clock fs-4 text-secondary d-block mb-1"></i>
                        <span class="fw-bold fs-7 d-none d-md-inline"><?= _('5. Périodes') ?></span>
                    </div>
                </div>
            </div>

            <form action="/setup/finish" method="POST" id="setupForm">
                <?= csrf_field() ?>
                <input type="hidden" name="install_mode" value="single">

                <!-- STEP 1: Compte Administrateur Initial -->
                <div class="setup-tab-pane active" id="tab-step-1">
                    <div class="d-flex align-items-center mb-3">
                        <i class="ph-duotone ph-user-plus text-primary fs-3 me-2"></i>
                        <h4 class="h5 fw-bold mb-0"><?= _('Étape 1 : Compte de l\'Administrateur Général') ?></h4>
                    </div>
                    <p class="text-muted small mb-4"><?= _('Cet utilisateur disposera du rôle Admin Local pour gérer intégralement l\'établissement.') ?></p>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="admin_prenom" class="form-label fw-semibold"><?= _('Prénom') ?> <span class="text-danger">*</span></label>
                            <input type="text" name="admin_prenom" id="admin_prenom" class="form-control" required placeholder="Ex: Jean">
                        </div>
                        <div class="col-md-6">
                            <label for="admin_nom" class="form-label fw-semibold"><?= _('Nom') ?> <span class="text-danger">*</span></label>
                            <input type="text" name="admin_nom" id="admin_nom" class="form-control" required placeholder="Ex: Dupont">
                        </div>
                        <div class="col-12">
                            <label for="admin_email" class="form-label fw-semibold"><?= _('Adresse Email (Identifiant de connexion)') ?> <span class="text-danger">*</span></label>
                            <input type="email" name="admin_email" id="admin_email" class="form-control" required placeholder="admin@ecole.com">
                        </div>
                        <div class="col-12">
                            <label for="admin_pass" class="form-label fw-semibold"><?= _('Mot de passe') ?> <span class="text-danger">*</span></label>
                            <input type="password" name="admin_pass" id="admin_pass" class="form-control" required minlength="4" placeholder="••••••••">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                        <button type="button" class="btn btn-primary px-4 fw-bold btn-next" onclick="goToStep(2)">
                            <?= _('Suivant : Informations de l\'école') ?> <i class="ph-duotone ph-arrow-right ms-1"></i>
                        </button>
                    </div>
                </div>

                <!-- STEP 2: Identité de l'Établissement -->
                <div class="setup-tab-pane d-none" id="tab-step-2">
                    <div class="d-flex align-items-center mb-3">
                        <i class="ph-duotone ph-buildings text-primary fs-3 me-2"></i>
                        <h4 class="h5 fw-bold mb-0"><?= _('Étape 2 : Identité de l\'Établissement') ?></h4>
                    </div>
                    <p class="text-muted small mb-4"><?= _('Renseignez les coordonnées officielles de votre école.') ?></p>

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label for="nom_lycee" class="form-label fw-semibold"><?= _('Nom Officiel du Lycée / Collège') ?> <span class="text-danger">*</span></label>
                            <input type="text" name="nom_lycee" id="nom_lycee" class="form-control" required placeholder="Ex: Lycée d'Excellence Avenir">
                        </div>
                        <div class="col-md-4">
                            <label for="type_lycee" class="form-label fw-semibold"><?= _('Statut Juridique') ?> <span class="text-danger">*</span></label>
                            <select name="type_lycee" id="type_lycee" class="form-select" required>
                                <option value="prive" selected><?= _('Privé') ?></option>
                                <option value="public"><?= _('Public') ?></option>
                                <option value="parapublic"><?= _('Parapublic') ?></option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="sigle" class="form-label fw-semibold"><?= _('Sigle / Abréviation') ?></label>
                            <input type="text" name="sigle" id="sigle" class="form-control" placeholder="Ex: LEA">
                        </div>
                        <div class="col-md-4">
                            <label for="tel" class="form-label fw-semibold"><?= _('Téléphone de contact') ?></label>
                            <input type="text" name="tel" id="tel" class="form-control" placeholder="+241 00 00 00 00">
                        </div>
                        <div class="col-md-4">
                            <label for="lycee_email" class="form-label fw-semibold"><?= _('Email Établissement') ?></label>
                            <input type="email" name="lycee_email" id="lycee_email" class="form-control" placeholder="contact@ecole.com">
                        </div>
                        <div class="col-md-6">
                            <label for="ville" class="form-label fw-semibold"><?= _('Ville') ?></label>
                            <input type="text" name="ville" id="ville" class="form-control" placeholder="Ex: Libreville">
                        </div>
                        <div class="col-md-6">
                            <label for="quartier" class="form-label fw-semibold"><?= _('Quartier / Adresse') ?></label>
                            <input type="text" name="quartier" id="quartier" class="form-control" placeholder="Ex: Akanda">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                        <button type="button" class="btn btn-outline-secondary px-4 fw-bold" onclick="goToStep(1)">
                            <i class="ph-duotone ph-arrow-left me-1"></i> <?= _('Précédent') ?>
                        </button>
                        <button type="button" class="btn btn-primary px-4 fw-bold" onclick="goToStep(3)">
                            <?= _('Suivant : Année Académique') ?> <i class="ph-duotone ph-arrow-right ms-1"></i>
                        </button>
                    </div>
                </div>

                <!-- STEP 3: Année Académique -->
                <div class="setup-tab-pane d-none" id="tab-step-3">
                    <div class="d-flex align-items-center mb-3">
                        <i class="ph-duotone ph-calendar text-primary fs-3 me-2"></i>
                        <h4 class="h5 fw-bold mb-0"><?= _('Étape 3 : Année Académique Initiale') ?></h4>
                    </div>
                    <p class="text-muted small mb-4"><?= _('Définissez les dates exactes marquant le début et la fin de l\'année scolaire.') ?></p>

                    <?php
                        $currYear = (int)date('Y');
                        $defaultLibelle = $currYear . '-' . ($currYear + 1);
                        $defaultDebut = $currYear . '-09-01';
                        $defaultFin = ($currYear + 1) . '-06-30';
                    ?>

                    <div class="row g-3">
                        <div class="col-12">
                            <label for="annee_libelle" class="form-label fw-semibold"><?= _('Libellé de l\'Année Académique') ?> <span class="text-danger">*</span></label>
                            <input type="text" name="annee_libelle" id="annee_libelle" class="form-control" required value="<?= $defaultLibelle ?>" placeholder="Ex: 2025-2026">
                        </div>
                        <div class="col-md-6">
                            <label for="annee_date_debut" class="form-label fw-semibold"><?= _('Date de début officielle') ?> <span class="text-danger">*</span></label>
                            <input type="date" name="annee_date_debut" id="annee_date_debut" class="form-control" required value="<?= $defaultDebut ?>" onchange="recalculatePeriods()">
                        </div>
                        <div class="col-md-6">
                            <label for="annee_date_fin" class="form-label fw-semibold"><?= _('Date de clôture officielle') ?> <span class="text-danger">*</span></label>
                            <input type="date" name="annee_date_fin" id="annee_date_fin" class="form-control" required value="<?= $defaultFin ?>" onchange="recalculatePeriods()">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                        <button type="button" class="btn btn-outline-secondary px-4 fw-bold" onclick="goToStep(2)">
                            <i class="ph-duotone ph-arrow-left me-1"></i> <?= _('Précédent') ?>
                        </button>
                        <button type="button" class="btn btn-primary px-4 fw-bold" onclick="goToStep(4)">
                            <?= _('Suivant : Paramètres Généraux') ?> <i class="ph-duotone ph-arrow-right ms-1"></i>
                        </button>
                    </div>
                </div>

                <!-- STEP 4: Paramètres Généraux -->
                <div class="setup-tab-pane d-none" id="tab-step-4">
                    <div class="d-flex align-items-center mb-3">
                        <i class="ph-duotone ph-gear text-primary fs-3 me-2"></i>
                        <h4 class="h5 fw-bold mb-0"><?= _('Étape 4 : Paramètres Généraux') ?></h4>
                    </div>
                    <p class="text-muted small mb-4"><?= _('Choisissez le découpage académique institutionnel et la monnaie de fonctionnement.') ?></p>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="sequence_annuelle" class="form-label fw-semibold"><?= _('Organisation Académique') ?> <span class="text-danger">*</span></label>
                            <select name="sequence_annuelle" id="sequence_annuelle" class="form-select" required onchange="recalculatePeriods()">
                                <option value="Trimestrielle" selected><?= _('Trimestrielle (3 Périodes)') ?></option>
                                <option value="Semestrielle"><?= _('Semestrielle (2 Périodes)') ?></option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="devise_pays" class="form-label fw-semibold"><?= _('Devise / Monnaie') ?> <span class="text-danger">*</span></label>
                            <input type="text" name="devise_pays" id="devise_pays" class="form-control" required value="FCFA">
                            <input type="hidden" name="monnaie" value="FCFA">
                        </div>
                        <div class="col-md-6">
                            <label for="mode_cycle" class="form-label fw-semibold"><?= _('Structure des Cycles') ?></label>
                            <select name="mode_cycle" id="mode_cycle" class="form-select">
                                <option value="separe_ceg_lycee" selected><?= _('Séparé (CEG / Collège + Lycée)') ?></option>
                                <option value="lycee_unique"><?= _('Lycée Unique') ?></option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="nb_langue" class="form-label fw-semibold"><?= _('Régime Linguistique') ?></label>
                            <select name="nb_langue" id="nb_langue" class="form-select">
                                <option value="1" selected><?= _('Monolingue (Français)') ?></option>
                                <option value="2"><?= _('Bilingue (Français / Anglais)') ?></option>
                            </select>
                            <input type="hidden" name="langue_1" value="Francais">
                            <input type="hidden" name="langue_2" value="Anglais">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                        <button type="button" class="btn btn-outline-secondary px-4 fw-bold" onclick="goToStep(3)">
                            <i class="ph-duotone ph-arrow-left me-1"></i> <?= _('Précédent') ?>
                        </button>
                        <button type="button" class="btn btn-primary px-4 fw-bold" onclick="goToStep(5)">
                            <?= _('Suivant : Configurer les Périodes') ?> <i class="ph-duotone ph-arrow-right ms-1"></i>
                        </button>
                    </div>
                </div>

                <!-- STEP 5: Paramétrage Académique & Périodes -->
                <div class="setup-tab-pane d-none" id="tab-step-5">
                    <div class="d-flex align-items-center mb-3">
                        <i class="ph-duotone ph-clock text-primary fs-3 me-2"></i>
                        <h4 class="h5 fw-bold mb-0"><?= _('Étape 5 : Paramétrage des Périodes Académiques') ?></h4>
                    </div>
                    <p class="text-muted small mb-4"><?= _('Saisissez les dates explicites des périodes. Elles doivent couvrir l\'année académique sans chevauchement.') ?></p>

                    <div id="periodsContainer" class="row g-3">
                        <!-- Populated dynamically by JavaScript based on sequence_annuelle and year dates -->
                    </div>

                    <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                        <button type="button" class="btn btn-outline-secondary px-4 fw-bold" onclick="goToStep(4)">
                            <i class="ph-duotone ph-arrow-left me-1"></i> <?= _('Précédent') ?>
                        </button>
                        <button type="submit" class="btn btn-success btn-lg px-5 fw-bold">
                            <i class="ph-duotone ph-check-circle me-1"></i> <?= _('Terminer & Initialiser SGS') ?>
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
let currentStep = 1;

function goToStep(step) {
    // Validate current step inputs
    const currentPane = document.getElementById('tab-step-' + currentStep);
    const inputs = currentPane.querySelectorAll('input[required], select[required]');
    let valid = true;
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            valid = false;
        } else {
            input.classList.remove('is-invalid');
        }
    });

    if (!valid && step > currentStep) {
        alert('<?= _("Veuillez remplir tous les champs obligatoires avant de continuer.") ?>');
        return;
    }

    // Hide all panes
    for (let i = 1; i <= 5; i++) {
        const pane = document.getElementById('tab-step-' + i);
        const nav = document.getElementById('step-nav-' + i);
        if (pane) pane.classList.add('d-none');
        if (nav) {
            nav.querySelector('div').classList.remove('bg-primary', 'text-white');
            nav.querySelector('div').classList.add('bg-light');
            nav.querySelector('i').classList.replace('text-white', 'text-secondary');
        }
    }

    // Show target pane
    const targetPane = document.getElementById('tab-step-' + step);
    const targetNav = document.getElementById('step-nav-' + step);
    if (targetPane) targetPane.classList.remove('d-none');
    if (targetNav) {
        targetNav.querySelector('div').classList.add('bg-primary', 'text-white');
        targetNav.querySelector('div').classList.remove('bg-light');
        targetNav.querySelector('i').classList.replace('text-secondary', 'text-white');
    }

    currentStep = step;

    if (step === 5) {
        recalculatePeriods();
    }
}

function recalculatePeriods() {
    const org = document.getElementById('sequence_annuelle').value;
    const yearStart = document.getElementById('annee_date_debut').value;
    const yearFin = document.getElementById('annee_date_fin').value;
    const container = document.getElementById('periodsContainer');

    if (!yearStart || !yearFin) return;

    const startDate = new Date(yearStart);
    const endDate = new Date(yearFin);

    container.innerHTML = '';

    if (org === 'Semestrielle') {
        // 2 Semestres
        const midMs = startDate.getTime() + (endDate.getTime() - startDate.getTime()) / 2;
        const midDate = new Date(midMs);
        const midDateStr = midDate.toISOString().split('T')[0];
        const dayAfterMid = new Date(midMs + 86400000).toISOString().split('T')[0];

        const pData = [
            { nom: 'Semestre 1', start: yearStart, end: midDateStr },
            { nom: 'Semestre 2', start: dayAfterMid, end: yearFin }
        ];

        pData.forEach((p, idx) => {
            container.innerHTML += renderPeriodRow(idx, p.nom, p.start, p.end);
        });
    } else {
        // 3 Trimestres
        const totalMs = endDate.getTime() - startDate.getTime();
        const t1End = new Date(startDate.getTime() + (totalMs / 3)).toISOString().split('T')[0];
        const t2Start = new Date(startDate.getTime() + (totalMs / 3) + 86400000).toISOString().split('T')[0];
        const t2End = new Date(startDate.getTime() + (2 * totalMs / 3)).toISOString().split('T')[0];
        const t3Start = new Date(startDate.getTime() + (2 * totalMs / 3) + 86400000).toISOString().split('T')[0];

        const pData = [
            { nom: 'Trimestre 1', start: yearStart, end: t1End },
            { nom: 'Trimestre 2', start: t2Start, end: t2End },
            { nom: 'Trimestre 3', start: t3Start, end: yearFin }
        ];

        pData.forEach((p, idx) => {
            container.innerHTML += renderPeriodRow(idx, p.nom, p.start, p.end);
        });
    }
}

function renderPeriodRow(index, defaultNom, defaultStart, defaultEnd) {
    return `
        <div class="col-12 p-3 border rounded bg-light">
            <div class="row g-3 align-items-center">
                <div class="col-md-4">
                    <label class="form-label fw-bold small mb-1"><?= _('Nom de la période') ?></label>
                    <input type="text" name="periods[${index}][nom]" class="form-control" value="${defaultNom}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold small mb-1"><?= _('Date de début') ?></label>
                    <input type="date" name="periods[${index}][date_debut]" class="form-control" value="${defaultStart}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold small mb-1"><?= _('Date de fin') ?></label>
                    <input type="date" name="periods[${index}][date_fin]" class="form-control" value="${defaultEnd}" required>
                </div>
            </div>
        </div>
    `;
}

// Initial setup
document.addEventListener('DOMContentLoaded', () => {
    recalculatePeriods();
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>