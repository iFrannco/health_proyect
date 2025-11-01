<?= $this->extend('layouts/base') ?>

<?= $this->section('content') ?>
  <h1><?= esc($title) ?></h1>
  <p>Bienvenido al panel de administración. Aquí podrás gestionar usuarios, planes estándar y estadísticas.</p>
<?= $this->endSection() ?>
