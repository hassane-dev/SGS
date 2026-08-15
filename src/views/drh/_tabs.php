<?php
// src/views/drh/_tabs.php
$currentTab = $activeTab ?? 'index';
?>
<ul class="nav nav-pills mb-4 gap-2">
    <?php if (Auth::can('view_all', 'drh')): ?>
    <li class="nav-item">
        <a class="nav-link <?= $currentTab === 'dashboard' ? 'active' : '' ?>" href="/drh/dashboard">
            <i class="ph-duotone ph-chart-pie-slice me-1"></i>
            <?= _('Cockpit DRH') ?>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $currentTab === 'index' ? 'active' : '' ?>" href="/drh">
            <i class="ph-duotone ph-users-three me-1"></i>
            <?= _('Annuaire du Personnel') ?>
        </a>
    </li>
    <?php endif; ?>

    <?php if (Auth::can('create', 'drh')): ?>
    <li class="nav-item">
        <a class="nav-link <?= $currentTab === 'create' ? 'active' : '' ?>" href="/drh/create">
            <i class="ph-duotone ph-user-plus me-1"></i>
            <?= _('Nouveau Personnel') ?>
        </a>
    </li>
    <?php endif; ?>
</ul>
