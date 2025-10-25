<?php
$navItems = $navItems ?? [];
$userItems = $userItems ?? [];
$brand = $brand ?? null;
$brandUrl = $brandUrl ?? base_url('/');
$navbarId = $navbarId ?? 'layoutNavbar';
?>

<nav class="main-header navbar navbar-expand-lg navbar-light layout-navbar shadow-sm">
    <div class="container-fluid">
        <?php if (!empty($brand)): ?>
            <a class="navbar-brand d-lg-none font-weight-bold text-uppercase mb-0" href="<?= esc($brandUrl) ?>">
                <?= esc($brand) ?>
            </a>
        <?php endif; ?>

        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#<?= esc($navbarId) ?>"
                aria-controls="<?= esc($navbarId) ?>" aria-expanded="false" aria-label="Alternar navegación">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse layout-navbar-collapse" id="<?= esc($navbarId) ?>">
            <ul class="navbar-nav layout-nav-left">
                <?php foreach ($navItems as $item): ?>
                    <li class="nav-item<?= !empty($item['active']) ? ' active' : '' ?>">
                        <a class="nav-link" href="<?= esc($item['url'] ?? '#') ?>">
                            <?= esc($item['label'] ?? '') ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <?php if (!empty($brand)): ?>
                <a class="navbar-brand layout-navbar-brand d-none d-lg-inline-block font-weight-bold text-uppercase mb-0"
                   href="<?= esc($brandUrl) ?>">
                    <?= esc($brand) ?>
                </a>
            <?php endif; ?>

            <ul class="navbar-nav layout-nav-right">
                <?php foreach ($userItems as $item): ?>
                    <li class="nav-item<?= !empty($item['active']) ? ' active' : '' ?>">
                        <a class="nav-link" href="<?= esc($item['url'] ?? '#') ?>">
                            <?= esc($item['label'] ?? '') ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</nav>
