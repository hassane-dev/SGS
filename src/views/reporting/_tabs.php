<?php
// src/views/reporting/_tabs.php
?>
<ul class="nav nav-pills mb-4">
    <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'dashboard' ? 'active' : '' ?>" href="/reporting?lycee_id=<?= htmlspecialchars($selectedLyceeId) ?>">
            <i class="ph-duotone ph-chart-pie me-2"></i><?= _("Cockpit Exécutif") ?>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'kpis' ? 'active' : '' ?>" href="/reporting/kpis?lycee_id=<?= htmlspecialchars($selectedLyceeId) ?>">
            <i class="ph-duotone ph-list me-2"></i><?= _("Catalogue KPI") ?>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'analyse' ? 'active' : '' ?>" href="/reporting/analyse?lycee_id=<?= htmlspecialchars($selectedLyceeId) ?>">
            <i class="ph-duotone ph-clock-counter-clockwise me-2"></i><?= _("Analyses Temporelles") ?>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'comparaison' ? 'active' : '' ?>" href="/reporting/comparaison">
            <i class="ph-duotone ph-chart-bar me-2"></i><?= _("Comparaison Établissements") ?>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $activeTab === 'previsions' ? 'active' : '' ?>" href="/reporting/previsions?lycee_id=<?= htmlspecialchars($selectedLyceeId) ?>">
            <i class="ph-duotone ph-trend-up me-2"></i><?= _("Prévisions Financières") ?>
        </a>
    </li>
</ul>
