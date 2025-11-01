<?= $this->extend('layouts/base') ?>

<?= $this->section('content') ?>
  <h1><?= esc($title) ?></h1>
  <p>Bienvenido al panel del médico. Desde aquí podrás acceder a tus pacientes, diagnósticos y planes de cuidado.</p>
<?= $this->endSection() ?>
