<?php
$userItems = $userItems ?? [];
?>

<nav class="main-header navbar navbar-expand navbar-light layout-navbar shadow-sm">
    <div class="container-fluid">
        <ul class="navbar-nav align-items-center layout-nav-left">
            <li class="nav-item">
                <a class="nav-link layout-navbar-toggle" data-widget="pushmenu" href="#" role="button"
                   aria-label="Alternar menú lateral">
                    <i class="fas fa-bars"></i>
                </a>
            </li>
        </ul>

        <ul class="navbar-nav ml-auto layout-nav-right">
            <?php foreach ($userItems as $item): ?>
                <?php $iconClass = $item['icon'] ?? 'fas fa-circle'; ?>
                <li class="nav-item<?= !empty($item['active']) ? ' active' : '' ?>">
                    <a class="nav-link" href="<?= esc($item['url'] ?? '#') ?>">
                        <i class="layout-nav-right-icon <?= esc($iconClass, 'attr') ?>" aria-hidden="true"></i>
                        <span class="layout-nav-right-label"><?= esc($item['label'] ?? '') ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</nav>
