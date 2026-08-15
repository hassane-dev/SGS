<?php
// src/views/drh/_tabs.php
$currentTab = $activeTab ?? 'index';
?>
<ul class="nav nav-pills mb-4 gap-2">
    <?php if (Auth::can('view_all', 'drh')): ?>
    <li class="nav-item">
        <a class="nav-link <?= $currentTab === 'dashboard' ? 'active' : '' ?>" href="/drh/dashboard">
            <i class="ph-duotone ph-chart-pie-slice me-1">
            <?= _('Cockpit DRH') ?>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $currentTab === 'index' ? 'active' : '' ?>" href="/drh">
            <i class="ph-duotone ph-users-three me-1">
            <?= _('Annuaire du Personnel') ?>
        </a>
    </li>
    <?php endif; ?>

    <?php if (Auth::can('create', 'drh')): ?>
    <li class="nav-item">
        <a class="nav-link <?= $currentTab === 'create' ? 'active' : '' ?>" href="/drh/create">
            <i class="ph-duotone ph-user-plus me-1">
            <?= _('Nouveau Personnel') ?>
        </a>
    </li>
    <?php endif; ?>

    <?php if (Auth::can('export', 'drh')): ?>
    <li class="nav-item ms-auto">
        <a class="btn btn-outline-secondary" href="/drh/export<?= !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '' ?>">
            <i class="ph-duotone ph-download-simple me-1">
            <?= _('Exporter (CSV)') ?>
        </a>
    </li>
    <?php endif; ?>
</ul>
