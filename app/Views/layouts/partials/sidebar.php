<?php
$navItems = $navItems ?? [];
$brand = $brand ?? 'HealthPro';
$brandUrl = $brandUrl ?? base_url('/');
?>

<aside class="main-sidebar sidebar-light-primary elevation-3 layout-sidebar">
    <a href="<?= esc($brandUrl) ?>" class="brand-link layout-sidebar-brand bg-primary">
        <i class="nav-icon fas fa-heartbeat text-white mr-2" aria-hidden="true"></i>
        <span class="brand-text font-weight-semibold text-white"><?= esc($brand) ?></span>
    </a>

    <div class="sidebar">
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column layout-sidebar-nav" data-widget="treeview" role="menu"
                data-accordion="false">
                <?php foreach ($navItems as $item): ?>
                    <?php $isActive = !empty($item['active']); ?>
                    <li class="nav-item">
                        <a href="<?= esc($item['url'] ?? '#') ?>" class="nav-link<?= $isActive ? ' active' : '' ?>">
                            <i class="nav-icon <?= esc($item['icon'] ?? 'fas fa-circle') ?>"></i>
                            <p><?= esc($item['label'] ?? '') ?></p>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
    </div>
</aside>
