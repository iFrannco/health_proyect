<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title ?? 'HealthPro') ?></title>

    <link rel="stylesheet" href="<?= base_url('adminlte/plugins/fontawesome-free/css/all.min.css'); ?>">
    <link rel="stylesheet" href="<?= base_url('adminlte/dist/css/adminlte.min.css'); ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/layout.css'); ?>">
    <?= $this->renderSection('styles') ?>
</head>
<body class="hold-transition sidebar-mini layout-fixed accent-primary">
<script>
(function () {
    try {
        if (localStorage.getItem('layout.sidebar.collapsed') === 'true') {
            document.body.classList.add('sidebar-collapse');
        }
    } catch (error) {}
})();
</script>
<div class="wrapper">
    <?= $this->renderSection('navbar') ?: view('layouts/partials/navbar') ?>
    <?= $this->renderSection('sidebar') ?: view('layouts/partials/sidebar') ?>

    <div class="content-wrapper">
        <div class="content">
            <div class="container-fluid">
                <?= $this->renderSection('content') ?>
            </div>
        </div>
    </div>

    <?= view('layouts/partials/footer') ?>
</div>

<script src="<?= base_url('adminlte/plugins/jquery/jquery.min.js'); ?>"></script>
<script src="<?= base_url('adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js'); ?>"></script>
<script src="<?= base_url('adminlte/dist/js/adminlte.min.js'); ?>"></script>
<script src="<?= base_url('assets/js/layout.js'); ?>"></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
