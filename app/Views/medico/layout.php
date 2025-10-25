<?php
$brand = null;
$brandUrl = site_url('medico/home'); // dashboard con estadísticas del médico
$navItems = [
    ['label' => 'Home', 'url' => site_url('medico/home')],
    ['label' => 'Pacientes', 'url' => site_url('medico/pacientes')],
    ['label' => 'Diagnosticos', 'url' => site_url('medico/diagnosticos')],
    ['label' => 'Planes de cuidado', 'url' => site_url('medico/planes')],
];
$userItems = [
    ['label' => 'Mi Perfil', 'url' => site_url('medico/perfil')],
    ['label' => 'Seleccionar rol', 'url' => site_url('auth/select-role')],
    ['label' => 'Cerrar sesión', 'url' => site_url('auth/logout')],
];
?>

<?= $this->extend('layouts/base') ?>

<?= $this->section('navbar') ?>
<?= view('layouts/partials/navbar', [
    'navItems' => $navItems,
    'userItems' => $userItems,
    'brand' => $brand,
    'brandUrl' => $brandUrl,
    'navbarId' => 'navbarMedico',
]) ?>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
    <?= $this->renderSection('content') ?>
<?= $this->endSection() ?>
